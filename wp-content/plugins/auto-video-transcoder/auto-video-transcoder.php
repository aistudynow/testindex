<?php
/**
 * Plugin Name: Auto Video Transcoder (MP4 → WebM/AV1)
 * Description: Automatically converts uploaded MP4 files to modern WebM (VP9/AV1) variants with ffmpeg, generates posters,
 *              and optionally rewrites content to serve the most efficient source. Includes WP-CLI helpers.
 * Version: 1.1.0
 * Author: Your Name
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * License: GPLv2 or later
 * Text Domain: avt
 */

if (!defined('ABSPATH')) {
    exit;
}

class AVT_Plugin {
    private const OPT_KEY     = 'avt_options';
    private const META_KEY    = 'avt';
    private const RETRY_HOOK  = 'avt_retry_transcode';
    private const OPTION_PAGE = 'avt-settings';

    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ?? (self::$instance = new self());
    }

    private function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_settings_page']);

        add_filter('upload_mimes', [$this, 'allow_video_mimes']);

        add_action(self::RETRY_HOOK, [$this, 'on_retry_transcode']);
        add_action('add_attachment', [$this, 'on_add_attachment']);
        add_filter('wp_generate_attachment_metadata', [$this, 'on_generate_attachment_metadata'], 20, 2);

        add_filter('the_content', [$this, 'filter_the_content_videos'], 20);
        add_action('transition_post_status', [$this, 'on_transition_post_status'], 20, 3);

       add_filter('wp_get_attachment_url', [$this, 'prefer_webm_attachment_url'], 10, 2);
add_filter('the_content', [$this, 'rewrite_upload_mp4_urls_to_webm'], 19);

       
        register_activation_hook(__FILE__, [__CLASS__, 'on_activate']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'on_uninstall']);
    }

    /* --------------------------------------------------------------------- */
    /* Options & Environment                                                 */
    /* --------------------------------------------------------------------- */

    public static function defaults(): array {
        return [
            'ffmpeg_path'      => '',
            'enable_vp9'       => 1,
            'enable_av1'       => 0,
            'crf_vp9'          => 32,
            'crf_av1'          => 35,
            'audio_bitrate'    => 128,
            'max_width'        => 1920,
            'generate_poster'  => 1,
            'delete_originals' => 0,
            'publish_rewrite'  => 'auto',
            'runtime_rewrite'  => 1,
        ];
    }

    public static function get_options(): array {
        $options = get_option(self::OPT_KEY, []);
        return wp_parse_args($options, self::defaults());
    }

    public static function on_activate(): void {
        if (!get_option(self::OPT_KEY)) {
            add_option(self::OPT_KEY, self::defaults(), '', false);
        }
    }

    public static function on_uninstall(): void {
        delete_option(self::OPT_KEY);
    }

    public function allow_video_mimes(array $mimes): array {
        $mimes['webm'] = 'video/webm';
        return $mimes;
    }

    private static function uploads_map_url_to_path(string $url)
    {
        $uploads = wp_get_upload_dir();
        if (empty($uploads['baseurl']) || empty($uploads['basedir'])) {
            return false;
        }
        if (strpos($url, $uploads['baseurl']) !== 0) {
            return false;
        }
        $relative = ltrim(substr($url, strlen($uploads['baseurl'])), '/');
        return wp_normalize_path($uploads['basedir'] . '/' . $relative);
    }

    private static function with_ext(string $path, string $extension): string
    {
        $info = pathinfo($path);
        return $info['dirname'] . '/' . $info['filename'] . '.' . ltrim($extension, '.');
    }

    private static function detect_ffmpeg(string $configured): string
    {
        if (!empty($configured)) {
            return $configured;
        }
        if (!self::can_shell()) {
            return '';
        }
        $path = trim((string) @shell_exec('command -v ffmpeg || which ffmpeg'));
        return $path ?: '';
    }

    private static function can_shell(): bool
    {
        return function_exists('shell_exec');
    }

    private static function abs_to_uploads_rel(string $absolute): string
    {
        $uploads = wp_get_upload_dir();
        $basedir = isset($uploads['basedir']) ? rtrim(wp_normalize_path($uploads['basedir']), '/') : '';
        $absolute = wp_normalize_path($absolute);
        if ($basedir && strpos($absolute, $basedir) === 0) {
            return ltrim(substr($absolute, strlen($basedir)), '/');
        }
        return $absolute;
    }




public function prefer_webm_attachment_url(string $url, int $post_id): string {
    if (is_admin()) return $url; // don’t affect editor/admin
    $path = self::uploads_map_url_to_path($url);
    if (!$path || !preg_match('~\.mp4($|\?)~i', $path)) return $url;

    $av1 = self::with_ext($path, 'av1.webm');
    $vp9 = self::with_ext($path, 'webm');

    if (file_exists($av1)) return self::with_ext($url, 'av1.webm');
    if (file_exists($vp9)) return self::with_ext($url, 'webm');
    return $url; // fallback
}

public function rewrite_upload_mp4_urls_to_webm(string $content): string {
    if (is_admin()) return $content;

    $uploads = wp_get_upload_dir();
    $baseurl = $uploads['baseurl'];
    if (!$baseurl) return $content;

    $pattern = '~(?P<url>' . preg_quote($baseurl, '~') . '/[^"\'>\s]+?\.mp4)(?P<q>\?[^"\'>\s]*)?~i';

    $content = preg_replace_callback($pattern, function ($m) {
        $url  = $m['url'] . ($m['q'] ?? '');
        $path = self::uploads_map_url_to_path($url);
        if (!$path) return $url;

        $av1 = self::with_ext($path, 'av1.webm');
        $vp9 = self::with_ext($path, 'webm');

        if (file_exists($av1)) return self::with_ext($m['url'], 'av1.webm') . ($m['q'] ?? '');
        if (file_exists($vp9)) return self::with_ext($m['url'], 'webm') . ($m['q'] ?? '');
        return $url;
    }, $content);

    return $content;
}




// Put this inside class AVT_Plugin (e.g., after can_shell()/abs_to_uploads_rel()).
private function is_upload_ajax(): bool {
    // 1) Classic media modal / admin-ajax
    if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
        $action = $_REQUEST['action'] ?? '';
        if ($action === 'upload-attachment') {
            return true;
        }
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (is_string($script) && strpos($script, 'async-upload.php') !== false) {
            return true;
        }
    }

    // 2) REST API (block editor, modern uploads)
    if ((function_exists('wp_is_json_request') && wp_is_json_request()) || (defined('REST_REQUEST') && REST_REQUEST)) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (is_string($uri) && strpos($uri, '/wp-json/wp/v2/media') !== false) {
            return true;
        }
    }

    // 3) Fallback check for classic path
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return is_string($script) && strpos($script, 'async-upload.php') !== false;
}








    /* --------------------------------------------------------------------- */
    /* Settings                                                              */
    /* --------------------------------------------------------------------- */

    public function register_settings(): void
    {
        register_setting(self::OPT_KEY, self::OPT_KEY, ['sanitize_callback' => [$this, 'sanitize_options']]);

        add_settings_section(
            'avt_main',
            __('Transcoding Settings', 'avt'),
            function () {
                echo '<p>' . esc_html__('Convert uploaded MP4 files to modern formats with ffmpeg.', 'avt') . '</p>';
                $this->env_notice();
            },
            self::OPT_KEY
        );

        add_settings_field(
            'ffmpeg_path',
            __('ffmpeg path', 'avt'),
            function () {
                $options = self::get_options();
                printf(
                    '<input type="text" size="40" name="%1$s[ffmpeg_path]" value="%2$s" placeholder="/usr/bin/ffmpeg" />',
                    esc_attr(self::OPT_KEY),
                    esc_attr($options['ffmpeg_path'])
                );
                echo '<p class="description">' . esc_html__('Leave blank to auto-detect (requires shell_exec).', 'avt') . '</p>';
            },
            self::OPT_KEY,
            'avt_main'
        );

        add_settings_field(
            'enable_vp9',
            __('Create WebM (VP9)', 'avt'),
            function () {
                $options = self::get_options();
                printf(
                    '<input type="checkbox" name="%1$s[enable_vp9]" value="1" %2$s />',
                    esc_attr(self::OPT_KEY),
                    checked(1, $options['enable_vp9'], false)
                );
                printf(
                    ' CRF <input type="number" min="10" max="50" name="%1$s[crf_vp9]" value="%2$d" style="width:80px" />',
                    esc_attr(self::OPT_KEY),
                    intval($options['crf_vp9'])
                );
            },
            self::OPT_KEY,
            'avt_main'
        );

        add_settings_field(
            'enable_av1',
            __('Create WebM (AV1)', 'avt'),
            function () {
                $options = self::get_options();
                printf(
                    '<input type="checkbox" name="%1$s[enable_av1]" value="1" %2$s />',
                    esc_attr(self::OPT_KEY),
                    checked(1, $options['enable_av1'], false)
                );
                printf(
                    ' CRF <input type="number" min="18" max="50" name="%1$s[crf_av1]" value="%2$d" style="width:80px" />',
                    esc_attr(self::OPT_KEY),
                    intval($options['crf_av1'])
                );
            },
            self::OPT_KEY,
            'avt_main'
        );

        add_settings_field(
            'audio_bitrate',
            __('Audio bitrate (kbps)', 'avt'),
            function () {
                $options = self::get_options();
                printf(
                    '<input type="number" min="64" max="320" name="%1$s[audio_bitrate]" value="%2$d" />',
                    esc_attr(self::OPT_KEY),
                    intval($options['audio_bitrate'])
                );
            },
            self::OPT_KEY,
            'avt_main'
        );

        add_settings_field(
            'max_width',
            __('Max width (px)', 'avt'),
            function () {
                $options = self::get_options();
                printf(
                    '<input type="number" min="0" max="7680" name="%1$s[max_width]" value="%2$d" /> <span class="description">%3$s</span>',
                    esc_attr(self::OPT_KEY),
                    intval($options['max_width']),
                    esc_html__('0 = no scaling', 'avt')
                );
            },
            self::OPT_KEY,
            'avt_main'
        );

        add_settings_field(
            'generate_poster',
            __('Generate poster image', 'avt'),
            function () {
                $options = self::get_options();
                printf(
                    '<input type="checkbox" name="%1$s[generate_poster]" value="1" %2$s />',
                    esc_attr(self::OPT_KEY),
                    checked(1, $options['generate_poster'], false)
                );
            },
            self::OPT_KEY,
            'avt_main'
        );

        add_settings_section(
            'avt_behavior',
            __('Behavior', 'avt'),
            function () {
                echo '<p>' . esc_html__('Control deletion and content rewriting.', 'avt') . '</p>';
            },
            self::OPT_KEY
        );

        add_settings_field(
            'delete_originals',
            __('Delete original MP4 after convert', 'avt'),
            function () {
                $options = self::get_options();
                printf(
                    '<input type="checkbox" name="%1$s[delete_originals]" value="1" %2$s /> <span class="description">%3$s</span>',
                    esc_attr(self::OPT_KEY),
                    checked(1, $options['delete_originals'], false),
                    esc_html__('Removes the MP4 fallback once a WebM exists.', 'avt')
                );
            },
            self::OPT_KEY,
            'avt_behavior'
        );

        add_settings_field(
            'publish_rewrite',
            __('On publish, rewrite <video> to', 'avt'),
            function () {
                $options = self::get_options();
                ?>
                <select name="<?php echo esc_attr(self::OPT_KEY); ?>[publish_rewrite]">
                    <option value="none" <?php selected($options['publish_rewrite'], 'none'); ?>><?php esc_html_e('No change', 'avt'); ?></option>
                    <option value="webm_only" <?php selected($options['publish_rewrite'], 'webm_only'); ?>><?php esc_html_e('WebM only', 'avt'); ?></option>
                    <option value="auto" <?php selected($options['publish_rewrite'], 'auto'); ?>><?php esc_html_e('Auto (prefer AV1 → VP9 → MP4)', 'avt'); ?></option>
                </select>
                <?php
            },
            self::OPT_KEY,
            'avt_behavior'
        );

        add_settings_field(
            'runtime_rewrite',
            __('Rewrite at view-time (non-destructive)', 'avt'),
            function () {
                $options = self::get_options();
                printf(
                    '<input type="checkbox" name="%1$s[runtime_rewrite]" value="1" %2$s />',
                    esc_attr(self::OPT_KEY),
                    checked(1, $options['runtime_rewrite'], false)
                );
            },
            self::OPT_KEY,
            'avt_behavior'
        );
    }

    public function sanitize_options(array $input): array
    {
        $defaults = self::defaults();
        $output   = [];

        $output['ffmpeg_path']      = isset($input['ffmpeg_path']) ? trim(wp_unslash($input['ffmpeg_path'])) : '';
        $output['enable_vp9']       = empty($input['enable_vp9']) ? 0 : 1;
        $output['enable_av1']       = empty($input['enable_av1']) ? 0 : 1;
        $output['crf_vp9']          = isset($input['crf_vp9']) ? max(10, min(50, intval($input['crf_vp9']))) : $defaults['crf_vp9'];
        $output['crf_av1']          = isset($input['crf_av1']) ? max(18, min(50, intval($input['crf_av1']))) : $defaults['crf_av1'];
        $output['audio_bitrate']    = isset($input['audio_bitrate']) ? max(64, min(320, intval($input['audio_bitrate']))) : $defaults['audio_bitrate'];
        $output['max_width']        = isset($input['max_width']) ? max(0, min(7680, intval($input['max_width']))) : $defaults['max_width'];
        $output['generate_poster']  = empty($input['generate_poster']) ? 0 : 1;
        $output['delete_originals'] = empty($input['delete_originals']) ? 0 : 1;

        $valid_modes = ['none', 'webm_only', 'auto'];
        $mode = isset($input['publish_rewrite']) ? $input['publish_rewrite'] : $defaults['publish_rewrite'];
        $output['publish_rewrite'] = in_array($mode, $valid_modes, true) ? $mode : $defaults['publish_rewrite'];

        $output['runtime_rewrite'] = empty($input['runtime_rewrite']) ? 0 : 1;

        return $output;
    }

    public function add_settings_page(): void
    {
        add_options_page(
            __('Video Transcoder', 'avt'),
            __('Video Transcoder', 'avt'),
            'manage_options',
            self::OPTION_PAGE,
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Video Transcoder', 'avt'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPT_KEY);
                do_settings_sections(self::OPT_KEY);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function env_notice(): void
    {
        $can_shell = self::can_shell();
        $ffmpeg    = self::detect_ffmpeg(self::get_options()['ffmpeg_path']);
        echo '<div style="margin:8px 0;padding:10px;background:#fff;border:1px solid #ccd0d4;">';
        echo '<strong>' . esc_html__('Environment:', 'avt') . '</strong> ';
        echo 'shell_exec: ' . ($can_shell ? '<span style="color:green">Yes</span>' : '<span style="color:#a00">No</span>');
        echo ' &nbsp; ffmpeg: ' . ($ffmpeg ? '<span style="color:green">' . esc_html($ffmpeg) . '</span>' : '<span style="color:#a00">' . esc_html__('Not found', 'avt') . '</span>');
        echo '<br />' . esc_html__('Ubuntu install hint:', 'avt') . ' <code>sudo apt update && sudo apt install ffmpeg</code>';
        echo '</div>';
    }

    /* --------------------------------------------------------------------- */
    /* Upload Handling                                                       */
    /* --------------------------------------------------------------------- */

    public function on_add_attachment(int $attachment_id): void
{
    if ($this->is_upload_ajax()) {
        // Fast path: background it to avoid breaking the uploader response
        $this->schedule_transcode_retry($attachment_id);
        return;
    }

    $metadata = wp_get_attachment_metadata($attachment_id) ?: [];
    $result   = $this->handle_attachment($attachment_id, $metadata, true, false);
    if ($result['changed']) {
        wp_update_attachment_metadata($attachment_id, $result['metadata']);
    }
}

   public function on_generate_attachment_metadata($metadata, int $attachment_id)
{
    if ($this->is_upload_ajax()) {
        $this->schedule_transcode_retry($attachment_id);
        return $metadata;
    }

    $metadata = is_array($metadata) ? $metadata : [];
    $result   = $this->handle_attachment($attachment_id, $metadata, true, false);
    return $result['metadata'];
}

    public function on_retry_transcode(int $attachment_id): void
    {
        $metadata = wp_get_attachment_metadata($attachment_id) ?: [];
        $result   = $this->handle_attachment($attachment_id, $metadata, false, false);
        if ($result['changed']) {
            wp_update_attachment_metadata($attachment_id, $result['metadata']);
        }
    }

    /**
     * Execute transcoding for an attachment.
     *
     * @return array{metadata:array, changed:bool, status:string, created:array, failed:array, errors:array}
     */
    private function handle_attachment(int $attachment_id, array $metadata, bool $allow_retry, bool $force): array
    {
        $original_metadata = $metadata;

        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            return [
                'metadata' => $metadata,
                'changed'  => false,
                'status'   => 'missing-file',
                'created'  => [],
                'failed'   => [],
                'errors'   => [],
            ];
        }

        if (!$this->is_supported_video($attachment_id, $file)) {
            return [
                'metadata' => $metadata,
                'changed'  => false,
                'status'   => 'skipped',
                'created'  => [],
                'failed'   => [],
                'errors'   => [],
            ];
        }

        $options  = self::get_options();
        $metadata = $this->ensure_metadata_structure($metadata);
        $variants = $metadata[self::META_KEY]['variants'];

        $this->cleanup_variants($variants, $file);
        $metadata[self::META_KEY]['variants'] = $variants;

        $errors = is_array($metadata[self::META_KEY]['last_error'] ?? []) ? $metadata[self::META_KEY]['last_error'] : [];
        unset($errors['environment'], $errors['ffmpeg']);

        if (!self::can_shell()) {
            $errors['environment'] = $this->build_error_record('', __('PHP shell_exec() is disabled, so ffmpeg cannot run.', 'avt'));
            $metadata = $this->store_errors($metadata, $errors);
            $metadata[self::META_KEY]['last_attempt'] = time();
            $this->cancel_transcode_retry($attachment_id);
            $metadata[self::META_KEY]['pending_retry'] = false;
            return [
                'metadata' => $metadata,
                'changed'  => $metadata !== $original_metadata,
                'status'   => 'environment',
                'created'  => [],
                'failed'   => array_keys($errors),
                'errors'   => $metadata[self::META_KEY]['last_error'] ?? [],
            ];
        }

        $ffmpeg = self::detect_ffmpeg($options['ffmpeg_path']);
        if (!$ffmpeg) {
            $errors['ffmpeg'] = $this->build_error_record('', __('ffmpeg binary not found. Install it or set the full path in the plugin settings.', 'avt'));
            $metadata = $this->store_errors($metadata, $errors);
            $metadata[self::META_KEY]['last_attempt'] = time();
            $this->cancel_transcode_retry($attachment_id);
            $metadata[self::META_KEY]['pending_retry'] = false;
            return [
                'metadata' => $metadata,
                'changed'  => $metadata !== $original_metadata,
                'status'   => 'environment',
                'created'  => [],
                'failed'   => array_keys($errors),
                'errors'   => $metadata[self::META_KEY]['last_error'] ?? [],
            ];
        }

        $created = [];
        $failed  = [];

        $variant_defs = [
            'webm_av1' => ['enabled' => !empty($options['enable_av1']), 'extension' => 'av1.webm'],
            'webm_vp9' => ['enabled' => !empty($options['enable_vp9']), 'extension' => 'webm'],
        ];

        foreach ($variant_defs as $key => $def) {
            if (!$def['enabled']) {
                unset($variants[$key], $errors[$key]);
                continue;
            }

            $destination = self::with_ext($file, $def['extension']);
            $already_exists = file_exists($destination);

            if ($already_exists && !$force) {
                $variants[$key] = basename($destination);
                unset($errors[$key]);
                continue;
            }

            if ($force && $already_exists) {
                @unlink($destination);
            }

            if ($key === 'webm_av1') {
                [$ok, $output, $command] = $this->transcode_av1($ffmpeg, $file, $destination, $options);
            } else {
                [$ok, $output, $command] = $this->transcode_vp9($ffmpeg, $file, $destination, $options);
            }

            if ($ok) {
                $variants[$key] = basename($destination);
                unset($errors[$key]);
                $created[] = $key;
            } else {
                unset($variants[$key]);
                $errors[$key] = $this->build_error_record($command, $output);
                $failed[] = $key;
            }
        }

        $metadata[self::META_KEY]['variants'] = $variants;

        if (!empty($options['generate_poster'])) {
            $poster_path = self::with_ext($file, 'jpg');
            if ($force && file_exists($poster_path)) {
                @unlink($poster_path);
            }
            if ($this->extract_poster($ffmpeg, $file, $poster_path)) {
                $metadata[self::META_KEY]['variants']['poster'] = basename($poster_path);
            }
        } else {
            unset($metadata[self::META_KEY]['variants']['poster']);
        }

        $metadata[self::META_KEY]['last_attempt'] = time();
        $metadata = $this->store_errors($metadata, $errors);

        $needs_retry = $this->needs_retry($variant_defs, $metadata[self::META_KEY]['variants']);
        if ($allow_retry && $needs_retry) {
            $this->schedule_transcode_retry($attachment_id);
            $metadata[self::META_KEY]['pending_retry'] = true;
        } else {
            $this->cancel_transcode_retry($attachment_id);
            unset($metadata[self::META_KEY]['pending_retry']);
        }

        if (!empty($options['delete_originals']) && $this->has_any_variant($metadata[self::META_KEY]['variants'])) {
            $this->maybe_replace_original($attachment_id, $file, $metadata);
        }

        $status = 'noop';
        if ($created && $failed) {
            $status = 'partial';
        } elseif ($created) {
            $status = 'success';
        } elseif ($failed && !$this->has_any_variant($metadata[self::META_KEY]['variants'])) {
            $status = 'failed';
        } elseif ($needs_retry) {
            $status = 'pending';
        }

        return [
            'metadata' => $metadata,
            'changed'  => $metadata !== $original_metadata,
            'status'   => $status,
            'created'  => $created,
            'failed'   => $failed,
            'errors'   => $metadata[self::META_KEY]['last_error'] ?? [],
        ];
    }

    private function ensure_metadata_structure(array $metadata): array
{
    if (!isset($metadata[self::META_KEY]) || !is_array($metadata[self::META_KEY])) {
        $metadata[self::META_KEY] = [];
    }
    if (!isset($metadata[self::META_KEY]['variants']) || !is_array($metadata[self::META_KEY]['variants'])) {
        $metadata[self::META_KEY]['variants'] = [];
    }
    if (!isset($metadata[self::META_KEY]['last_error']) || !is_array($metadata[self::META_KEY]['last_error'])) {
        $metadata[self::META_KEY]['last_error'] = [];
    }
    return $metadata;
}

    private function cleanup_variants(array &$variants, string $file): void
    {
        foreach (['webm_av1' => 'av1.webm', 'webm_vp9' => 'webm', 'poster' => 'jpg'] as $key => $ext) {
            $expected = self::with_ext($file, $ext);
            if (!empty($variants[$key]) && !file_exists($expected)) {
                unset($variants[$key]);
            }
        }
    }

    private function needs_retry(array $variant_defs, array $variants): bool
    {
        foreach ($variant_defs as $key => $def) {
            if (!$def['enabled']) {
                continue;
            }
            if (empty($variants[$key])) {
                return true;
            }
        }
        return false;
    }

    private function has_any_variant(array $variants): bool
    {
        return !empty($variants['webm_av1']) || !empty($variants['webm_vp9']);
    }

    private function maybe_replace_original(int $attachment_id, string $file, array &$metadata): void
    {
        if (!preg_match('/\.mp4$/i', $file)) {
            return;
        }

        $av1 = self::with_ext($file, 'av1.webm');
        $vp9 = self::with_ext($file, 'webm');

        $target = null;
        if (file_exists($av1)) {
            $target = $av1;
        } elseif (file_exists($vp9)) {
            $target = $vp9;
        }

        if ($target && file_exists($file)) {
            @unlink($file);
        }

        if ($target && file_exists($target)) {
            update_attached_file($attachment_id, $target);
            wp_update_post([
                'ID'            => $attachment_id,
                'post_mime_type'=> 'video/webm',
            ]);
            $metadata['file'] = self::abs_to_uploads_rel($target);
        }
    }

    private function schedule_transcode_retry(int $attachment_id): void
    {
        if (!function_exists('wp_schedule_single_event') || !function_exists('wp_next_scheduled')) {
            return;
        }
        $delay = defined('MINUTE_IN_SECONDS') ? MINUTE_IN_SECONDS : 60;
        if (!wp_next_scheduled(self::RETRY_HOOK, [$attachment_id])) {
            wp_schedule_single_event(time() + $delay, self::RETRY_HOOK, [$attachment_id]);
        }
    }

    private function cancel_transcode_retry(int $attachment_id): void
    {
        if (function_exists('wp_next_scheduled') && function_exists('wp_unschedule_event')) {
            while ($timestamp = wp_next_scheduled(self::RETRY_HOOK, [$attachment_id])) {
                wp_unschedule_event($timestamp, self::RETRY_HOOK, [$attachment_id]);
            }
        }
    }

    private function build_error_record(string $command, string $output): array
    {
        $command = trim($command);
        $output  = trim($output);
        if ($output !== '' && strlen($output) > 4000) {
            $output = substr($output, 0, 4000) . '…';
        }
        return [
            'timestamp' => time(),
            'command'   => $command,
            'output'    => $output,
        ];
    }

    private function store_errors(array $metadata, array $errors): array
    {
        $errors = array_filter($errors);
        if ($errors) {
            $metadata[self::META_KEY]['last_error'] = $errors;
        } else {
            unset($metadata[self::META_KEY]['last_error']);
        }
        return $metadata;
    }

    private function is_supported_video(int $attachment_id, string $file): bool
    {
        $mime = get_post_mime_type($attachment_id);
        if (in_array($mime, ['video/mp4', 'video/mpeg', 'video/quicktime', 'application/mp4'], true)) {
            return true;
        }
        return (bool) preg_match('/\.mp4($|\?.*)/i', $file);
    }



    private function scale_filter(int $max_width): string
{
    if ($max_width <= 0) return '';
    $w = intval($max_width);
    return "-vf " . escapeshellarg("scale=min(iw\\,{$w}):-2");
}
    
    

    private function transcode_vp9(string $ffmpeg, string $src, string $dst, array $options): array
    {
        if (file_exists($dst)) {
            return [true, '', ''];
        }
        $crf = intval($options['crf_vp9']);
        $ab  = intval($options['audio_bitrate']);
        $vf  = $this->scale_filter(intval($options['max_width']));

        $command = sprintf(
            "%s -y -i %s -c:v libvpx-vp9 -crf %d -b:v 0 -pix_fmt yuv420p -row-mt 1%s -c:a libopus -b:a %dk %s 2>&1",
            escapeshellarg($ffmpeg),
            escapeshellarg($src),
            $crf,
            $vf ? ' ' . $vf : '',
            $ab,
            escapeshellarg($dst)
        );

        $output = shell_exec($command) ?? '';
        return [file_exists($dst), $output, $command];
    }





   private function transcode_av1(string $ffmpeg, string $src, string $dst, array $options): array
{
    if (file_exists($dst)) { return [true, '', '']; }
    $crf = intval($options['crf_av1']);
    $ab  = intval($options['audio_bitrate']);
    $vf  = $this->scale_filter(intval($options['max_width']));

    $command = sprintf(
        "%s -y -i %s -c:v libaom-av1 -crf %d -b:v 0 -pix_fmt yuv420p -cpu-used 4%s -c:a libopus -b:a %dk %s 2>&1",
        escapeshellarg($ffmpeg),
        escapeshellarg($src),
        $crf,
        $vf ? ' ' . $vf : '',
        $ab,
        escapeshellarg($dst)
    );

    $output = shell_exec($command) ?? '';
    return [file_exists($dst), $output, $command];
}





    private function extract_poster(string $ffmpeg, string $src, string $dst): bool
    {
        if (file_exists($dst)) {
            return true;
        }
        $command = sprintf(
            "%s -y -ss 00:00:01 -i %s -vframes 1 %s 2>&1",
            escapeshellarg($ffmpeg),
            escapeshellarg($src),
            escapeshellarg($dst)
        );
        shell_exec($command);
        return file_exists($dst);
    }

    /* --------------------------------------------------------------------- */
    /* Content rewriting                                                     */
    /* --------------------------------------------------------------------- */

    public function filter_the_content_videos(string $content): string
    {
        $options = self::get_options();
        if (empty($options['runtime_rewrite'])) {
            return $content;
        }
        if (!class_exists('DOMDocument')) {
            return $content;
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $loaded = $doc->loadHTML('<?xml encoding="utf-8" ?>' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if (!$loaded) {
            libxml_clear_errors();
            return $content;
        }

        $uploads = wp_get_upload_dir();
        $baseurl = $uploads['baseurl'];

        $videos = $doc->getElementsByTagName('video');
        $nodes = [];
        foreach ($videos as $video) {
            $nodes[] = $video;
        }

        foreach ($nodes as $video) {
            $urls = [];
            if ($video->hasAttribute('src')) {
                $urls[] = $video->getAttribute('src');
            }
            foreach (iterator_to_array($video->getElementsByTagName('source')) as $source) {
                if ($source->hasAttribute('src')) {
                    $urls[] = $source->getAttribute('src');
                }
            }
            $urls = array_unique($urls);

            foreach ($urls as $url) {
                if (strpos($url, $baseurl) !== 0) {
                    continue;
                }
                $path = self::uploads_map_url_to_path($url);
                if (!$path) {
                    continue;
                }

                $vp9 = self::with_ext($path, 'webm');
                $av1 = self::with_ext($path, 'av1.webm');

                if (file_exists($av1)) {
                    $src = $doc->createElement('source');
                    $src->setAttribute('src', self::with_ext($url, 'av1.webm'));
                    $src->setAttribute('type', 'video/webm; codecs="av01"');
                    $video->insertBefore($src, $video->firstChild);
                }
                if (file_exists($vp9)) {
                    $src = $doc->createElement('source');
                    $src->setAttribute('src', self::with_ext($url, 'webm'));
                    $src->setAttribute('type', 'video/webm');
                    $video->insertBefore($src, $video->firstChild);
                }
            }
        }

        $html = $doc->saveHTML();
        libxml_clear_errors();
        return preg_replace('/^<\?xml.*?\?>/i', '', $html);
    }

    public function on_transition_post_status(string $new_status, string $old_status, \WP_Post $post): void
    {
        if ($new_status !== 'publish' || !in_array($post->post_type, ['post', 'page'], true)) {
            return;
        }

        $options = self::get_options();
        $mode    = $options['publish_rewrite'];
        if ($mode === 'none') {
            return;
        }
        if (!class_exists('DOMDocument')) {
            return;
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $loaded = $doc->loadHTML('<?xml encoding="utf-8" ?>' . $post->post_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if (!$loaded) {
            libxml_clear_errors();
            return;
        }

        $uploads = wp_get_upload_dir();
        $baseurl = $uploads['baseurl'];

        $videos   = $doc->getElementsByTagName('video');
        $nodes    = [];
        $toDelete = [];
        foreach ($videos as $video) {
            $nodes[] = $video;
        }

        foreach ($nodes as $video) {
            $urls = [];
            if ($video->hasAttribute('src')) {
                $urls[] = $video->getAttribute('src');
            }
            foreach (iterator_to_array($video->getElementsByTagName('source')) as $source) {
                if ($source->hasAttribute('src')) {
                    $urls[] = $source->getAttribute('src');
                }
            }
            $urls = array_unique($urls);
            if (!$urls) {
                continue;
            }

            $best_sources = [];
            foreach ($urls as $url) {
                if (strpos($url, $baseurl) !== 0) {
                    continue;
                }
                $path = self::uploads_map_url_to_path($url);
                if (!$path) {
                    continue;
                }

                $av1 = self::with_ext($path, 'av1.webm');
                $vp9 = self::with_ext($path, 'webm');

                if ($mode === 'webm_only') {
                    if (file_exists($av1)) {
                        $best_sources[] = ['url' => self::with_ext($url, 'av1.webm'), 'type' => 'video/webm; codecs="av01"'];
                    }
                    if (file_exists($vp9)) {
                        $best_sources[] = ['url' => self::with_ext($url, 'webm'), 'type' => 'video/webm'];
                    }
                    if (!empty($options['delete_originals']) && preg_match('~\.mp4($|\?)~i', $url)) {
                        $fs = self::uploads_map_url_to_path($url);
                        if ($fs && file_exists($fs)) {
                            $toDelete[] = $fs;
                        }
                    }
                } else { // auto
                    if (file_exists($av1)) {
                        $best_sources[] = ['url' => self::with_ext($url, 'av1.webm'), 'type' => 'video/webm; codecs="av01"'];
                    } elseif (file_exists($vp9)) {
                        $best_sources[] = ['url' => self::with_ext($url, 'webm'), 'type' => 'video/webm'];
                    } else {
                        $best_sources[] = ['url' => $url, 'type' => 'video/mp4'];
                    }
                }
            }

            if ($best_sources) {
                while ($video->firstChild) {
                    $video->removeChild($video->firstChild);
                }
                $video->removeAttribute('src');

                foreach ($best_sources as $source) {
                    $el = $doc->createElement('source');
                    $el->setAttribute('src', $source['url']);
                    $el->setAttribute('type', $source['type']);
                    $video->appendChild($el);
                }
            }
        }

        $html = $doc->saveHTML();
        libxml_clear_errors();
        $html = preg_replace('/^<\?xml.*?\?>/i', '', $html);

        remove_action('transition_post_status', [$this, 'on_transition_post_status'], 20);
        wp_update_post([
            'ID'           => $post->ID,
            'post_content' => $html,
        ]);
        add_action('transition_post_status', [$this, 'on_transition_post_status'], 20, 3);

        if (!empty($options['delete_originals']) && $toDelete) {
            $toDelete = array_unique($toDelete);
            foreach ($toDelete as $path) {
                $has_webm = file_exists(self::with_ext($path, 'webm')) || file_exists(self::with_ext($path, 'av1.webm'));
                if ($has_webm && file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }

    /* --------------------------------------------------------------------- */
    /* WP-CLI                                                                */
    /* --------------------------------------------------------------------- */

    public static function cli_transcode(array $args, array $assoc): void
    {
        $options = self::get_options();
        $ffmpeg  = self::detect_ffmpeg($options['ffmpeg_path']);
        if (!$ffmpeg) {
            if (defined('WP_CLI') && WP_CLI) {
                \WP_CLI::error('ffmpeg not found. Install it or set the path in settings.');
            }
            return;
        }

        $query = new \WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'post_mime_type' => ['video/mp4'],
            'fields'         => 'ids',
        ]);

        $count = 0;
        foreach ($query->posts as $attachment_id) {
            self::cli_transcode_one($attachment_id, false);
            $count++;
            if (defined('WP_CLI') && WP_CLI) {
                \WP_CLI::log("Processed attachment #{$attachment_id}");
            }
        }

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Processed {$count} attachments.");
        }
    }

    public static function cli_transcode_one(int $attachment_id, bool $echo_result = true): bool
    {
        $instance = self::instance();
        $metadata = wp_get_attachment_metadata($attachment_id) ?: [];
        $result   = $instance->handle_attachment($attachment_id, $metadata, false, true);
        if ($result['changed']) {
            wp_update_attachment_metadata($attachment_id, $result['metadata']);
        }

        $variants = $result['metadata'][self::META_KEY]['variants'] ?? [];
        $success  = $result['status'] === 'success' || $result['status'] === 'noop' || $result['status'] === 'partial';

        if ($echo_result && defined('WP_CLI') && WP_CLI) {
            if ($success) {
                $available = array_keys(array_filter($variants));
                $available = $available ? implode(', ', $available) : 'none';
                \WP_CLI::success("Attachment #{$attachment_id} transcoded (variants: {$available}).");
            } else {
                $errors = $result['errors'];
                $messages = [];
                foreach ($errors as $key => $error) {
                    $messages[] = $key . ': ' . (isset($error['output']) ? $error['output'] : '');
                }
                $messages = $messages ? implode("\n", $messages) : 'Unknown error';
                \WP_CLI::warning("Attachment #{$attachment_id} failed: {$messages}");
            }
        }

        return $success;
    }
}

if (defined('WP_CLI') && WP_CLI) {
    if (!class_exists('AVT_CLI_Command')) {
        class AVT_CLI_Command extends \WP_CLI_Command {
            /**
             * Transcode MP4 attachments to WebM/AV1 (bulk or single).
             *
             * ## OPTIONS
             *
             * [--id=<attachment_id>]
             * : Only transcode a single attachment ID.
             *
             * ## EXAMPLES
             *     wp avt transcode
             *     wp avt transcode --id=123
             */
            public function transcode($args, $assoc_args)
            {
                if (!empty($assoc_args['id'])) {
                    $id   = intval($assoc_args['id']);
                    $post = get_post($id);
                    if (!$post || $post->post_type !== 'attachment') {
                        \WP_CLI::error("Attachment {$id} not found.");
                        return;
                    }
                    AVT_Plugin::cli_transcode_one($id);
                } else {
                    AVT_Plugin::cli_transcode($args, $assoc_args);
                }
            }
        }
    }
    \WP_CLI::add_command('avt', 'AVT_CLI_Command');
}

AVT_Plugin::instance();