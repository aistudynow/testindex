<?php
/**
 * Plugin Name: Auto AVIF/WebP Converter
 * Description: Automatically converts uploaded JPG/PNG images to AVIF and/or WebP at configurable compression levels, optionally deletes originals, and can rewrite published post content to only use modern formats. Includes bulk conversion (WP-CLI).
 * Version: 1.1.0
 * Author: Your Name
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * License: GPLv2 or later
 * Text Domain: aawc
 */

if (!defined('ABSPATH')) { exit; }

class AAWC_Plugin {
    const OPT_KEY = 'aawc_options';
    const NONCE_KEY = 'aawc_settings_nonce';

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }

    private function __construct() {
        // Settings / admin
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_settings_page']);

        // Upload + conversion
        add_filter('wp_generate_attachment_metadata', [$this, 'on_generate_attachment_metadata'], 20, 2);

        // Delivery (swap <img> src and srcset on the fly)
        add_filter('wp_get_attachment_image_attributes', [$this, 'filter_img_attributes'], 20, 3);
        add_filter('wp_calculate_image_srcset',        [$this, 'filter_srcset'], 20, 5);

        // Optional content filter (runtime)
        add_filter('the_content', [$this, 'filter_the_content_images'], 20);

        // Content rewrite on publish (persist changes + optional deletion)
        add_action('transition_post_status', [$this, 'on_transition_post_status'], 20, 3);

        // Allow AVIF/WebP uploads
        add_filter('upload_mimes', [$this, 'allow_modern_mimes']);

        // Activation/Uninstall
        register_activation_hook(__FILE__, [__CLASS__, 'on_activate']);
        register_uninstall_hook(__FILE__, ['AAWC_Plugin', 'on_uninstall']);

        // WP-CLI (bulk convert)
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('aawc regenerate', ['AAWC_Plugin', 'cli_regenerate']);
        }
    }

    // ---------- Defaults & Utils ----------

    public static function defaults() : array {
        return [
            // format enable + quality
            'enable_avif'          => 1,
            'enable_webp'          => 1,
            'quality_avif'         => 60,
            'quality_webp'         => 60,

            // sizes + delivery
            'convert_sizes'        => 1,
            'delivery_priority'    => 'avif_webp', // or 'webp_avif'
            'content_filter'       => 1,

            // CLI fallback encoders
            'cli_fallback'         => 0,

            // NEW: deletion + per-source targets + publish rewrite
            'delete_originals'     => 0,                 // delete JPG/PNG once modern exists (danger!)
            'png_target'           => 'both',            // 'avif' | 'webp' | 'both'
            'jpeg_target'          => 'both',            // 'avif' | 'webp' | 'both'
            'publish_rewrite'      => 'auto',            // 'none' | 'webp' | 'avif' | 'auto'
            'sideload_remote'  => 1,
        ];
    }

    public static function get_options() : array {
        $opts = get_option(self::OPT_KEY, []);
        return wp_parse_args($opts, self::defaults());
    }

    public static function is_supported_upload_mime($mime) : bool {
        return in_array($mime, ['image/jpeg','image/png'], true);
    }

    public static function prefers($format) : bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $accept = strtolower($accept);
        if ($format === 'avif')  { return strpos($accept, 'image/avif') !== false; }
        if ($format === 'webp')  { return strpos($accept, 'image/webp') !== false; }
        return false;
    }

    public static function uploads_map_url_to_path($url) {
    $uploads = wp_get_upload_dir();
    if (empty($uploads['baseurl']) || empty($uploads['basedir'])) return false;
    if (strpos($url, $uploads['baseurl']) !== 0) return false;

    $relative = ltrim(substr($url, strlen($uploads['baseurl'])), '/');
    // NEW: drop query/fragment and decode
    $relative = urldecode(preg_replace('~[?#].*$~', '', $relative));

    return wp_normalize_path($uploads['basedir'] . '/' . $relative);
}


    public static function with_ext($filePath, $newExt) {
        $pi = pathinfo($filePath);
        return $pi['dirname'] . '/' . $pi['filename'] . '.' . ltrim($newExt, '.');
    }

    public static function file_exists_for_url_with_ext($url, $newExt) {
    $path = self::uploads_map_url_to_path($url);
    if (!$path) return false;

    $altPath = self::with_ext($path, $newExt);
    if (!file_exists($altPath)) return false;

    // Rebuild URL: replace extension BEFORE query/fragment
    $parts = parse_url($url);
    $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
    $host   = $parts['host'] ?? '';
    $port   = isset($parts['port']) ? ':'.$parts['port'] : '';
    $pathU  = $parts['path'] ?? '';
    $query  = isset($parts['query']) ? '?'.$parts['query'] : '';
    $frag   = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

    // swap extension in path
    $pathU = preg_replace('~\.[^.\/]+$~', '.' . ltrim($newExt, '.'), $pathU);

    $altUrl = $scheme . $host . $port . $pathU . $query . $frag;
    return [$altPath, $altUrl];
}


    private static function abs_to_uploads_rel($abs) {
        $uploads = wp_get_upload_dir();
        $basedir = rtrim(wp_normalize_path($uploads['basedir']), '/');
        $abs = wp_normalize_path($abs);
        if (strpos($abs, $basedir) === 0) {
            return ltrim(substr($abs, strlen($basedir)), '/');
        }
        return $abs;
    }

    private function format_for_mime_pref($mime, array $opts) {
        $pref = ($mime === 'image/png') ? $opts['png_target'] : $opts['jpeg_target'];
        if (!in_array($pref, ['avif','webp','both'], true)) $pref = 'both';
        return $pref;
    }

    private function preferred_order() {
        $opts = self::get_options();
        if ($opts['delivery_priority'] === 'webp_avif') {
            return ['webp','avif'];
        }
        return ['avif','webp'];
    }

    // ---------- Activation/Uninstall ----------

    public static function on_activate() {
        if (!get_option(self::OPT_KEY)) {
            add_option(self::OPT_KEY, self::defaults(), '', false);
        }
    }

    public static function on_uninstall() {
        delete_option(self::OPT_KEY);
    }

    // ---------- Settings ----------

    public function register_settings() {
        register_setting(self::OPT_KEY, self::OPT_KEY, ['sanitize_callback' => [$this, 'sanitize_options']]);

        add_settings_section('aawc_main', __('Conversion Settings', 'aawc'), function(){
            echo '<p>' . esc_html__('Choose formats and compression levels. Lower quality = smaller files.', 'aawc') . '</p>';
            $this->env_notice();
        }, self::OPT_KEY);

        add_settings_field('enable_avif', __('Enable AVIF', 'aawc'), function(){
            $o = self::get_options();
            echo '<input type="checkbox" name="'.esc_attr(self::OPT_KEY).'[enable_avif]" value="1" '.checked(1, $o['enable_avif'], false).' />';
        }, self::OPT_KEY, 'aawc_main');

        add_settings_field('quality_avif', __('AVIF Quality (0–100)', 'aawc'), function(){
            $o = self::get_options();
            echo '<input type="number" min="0" max="100" name="'.esc_attr(self::OPT_KEY).'[quality_avif]" value="'.esc_attr($o['quality_avif']).'" />';
        }, self::OPT_KEY, 'aawc_main');

        add_settings_field('enable_webp', __('Enable WebP', 'aawc'), function(){
            $o = self::get_options();
            echo '<input type="checkbox" name="'.esc_attr(self::OPT_KEY).'[enable_webp]" value="1" '.checked(1, $o['enable_webp'], false).' />';
        }, self::OPT_KEY, 'aawc_main');

        add_settings_field('quality_webp', __('WebP Quality (0–100)', 'aawc'), function(){
            $o = self::get_options();
            echo '<input type="number" min="0" max="100" name="'.esc_attr(self::OPT_KEY).'[quality_webp]" value="'.esc_attr($o['quality_webp']).'" />';
        }, self::OPT_KEY, 'aawc_main');

        add_settings_field('convert_sizes', __('Convert All Sizes', 'aawc'), function(){
            $o = self::get_options();
            echo '<label><input type="checkbox" name="'.esc_attr(self::OPT_KEY).'[convert_sizes]" value="1" '.checked(1, $o['convert_sizes'], false).' /> ';
            esc_html_e('Also convert all generated thumbnails (recommended).', 'aawc');
            echo '</label>';
        }, self::OPT_KEY, 'aawc_main');

        add_settings_field('delivery_priority', __('Delivery Priority', 'aawc'), function(){
            $o = self::get_options();
            ?>
            <select name="<?php echo esc_attr(self::OPT_KEY); ?>[delivery_priority]">
                <option value="avif_webp" <?php selected($o['delivery_priority'], 'avif_webp'); ?>><?php esc_html_e('Prefer AVIF, then WebP', 'aawc'); ?></option>
                <option value="webp_avif" <?php selected($o['delivery_priority'], 'webp_avif'); ?>><?php esc_html_e('Prefer WebP, then AVIF', 'aawc'); ?></option>
            </select>
            <?php
        }, self::OPT_KEY, 'aawc_main');
        
        
        
        
      




        add_settings_field('content_filter', __('Rewrite <img> at runtime', 'aawc'), function(){
            $o = self::get_options();
            echo '<label><input type="checkbox" name="'.esc_attr(self::OPT_KEY).'[content_filter]" value="1" '.checked(1, $o['content_filter'], false).' /> ';
            esc_html_e('Swap image URLs inside post content at view-time (non-destructive).', 'aawc');
            echo '</label>';
        }, self::OPT_KEY, 'aawc_main');

        add_settings_field('cli_fallback', __('Allow CLI Fallbacks', 'aawc'), function(){
            $o = self::get_options();
            echo '<label><input type="checkbox" name="'.esc_attr(self::OPT_KEY).'[cli_fallback]" value="1" '.checked(1, $o['cli_fallback'], false).' /> ';
            esc_html_e('Use cwebp/avifenc if PHP libraries lack support (requires packages installed on server).', 'aawc');
            echo '</label>';
        }, self::OPT_KEY, 'aawc_main');
        
        


      // --- NEW advanced controls ---
add_settings_section('aawc_advanced', __('Advanced Controls', 'aawc'), function(){
    echo '<p>' . esc_html__('Control originals deletion, per-source target format, and publish-time rewrites. Deleting originals removes JPG/PNG fallback—enable only if you are sure.', 'aawc') . '</p>';
}, self::OPT_KEY);

add_settings_field('sideload_remote', __('Sideload remote images on publish', 'aawc'), function(){
    $o = self::get_options();
    echo '<label><input type="checkbox" name="'.esc_attr(self::OPT_KEY).'[sideload_remote]" value="1" '.checked(1, $o['sideload_remote'], false).' /> ';
    esc_html_e('When publishing, download off-site JPG/PNG images into the Media Library, convert to AVIF/WebP, and rewrite content to local URLs.', 'aawc');
    echo '</label>';
}, self::OPT_KEY, 'aawc_advanced');

        add_settings_field('delete_originals', __('Delete Originals After Conversion', 'aawc'), function(){
            $o = self::get_options();
            echo '<label><input type="checkbox" name="'.esc_attr(self::OPT_KEY).'[delete_originals]" value="1" '.checked(1, $o['delete_originals'], false).' /> ';
            esc_html_e('Dangerous: permanently removes the original JPG/PNG once a modern format exists.', 'aawc');
            echo '</label>';
        }, self::OPT_KEY, 'aawc_advanced');

        add_settings_field('png_target', __('When source is PNG', 'aawc'), function(){
            $o = self::get_options();
            ?>
            <select name="<?php echo esc_attr(self::OPT_KEY); ?>[png_target]">
                <option value="both" <?php selected($o['png_target'], 'both'); ?>><?php esc_html_e('Create AVIF + WebP', 'aawc'); ?></option>
                <option value="webp" <?php selected($o['png_target'], 'webp'); ?>><?php esc_html_e('Create WebP only', 'aawc'); ?></option>
                <option value="avif" <?php selected($o['png_target'], 'avif'); ?>><?php esc_html_e('Create AVIF only', 'aawc'); ?></option>
            </select>
            <?php
        }, self::OPT_KEY, 'aawc_advanced');

        add_settings_field('jpeg_target', __('When source is JPEG', 'aawc'), function(){
            $o = self::get_options();
            ?>
            <select name="<?php echo esc_attr(self::OPT_KEY); ?>[jpeg_target]">
                <option value="both" <?php selected($o['jpeg_target'], 'both'); ?>><?php esc_html_e('Create AVIF + WebP', 'aawc'); ?></option>
                <option value="webp" <?php selected($o['jpeg_target'], 'webp'); ?>><?php esc_html_e('Create WebP only', 'aawc'); ?></option>
                <option value="avif" <?php selected($o['jpeg_target'], 'avif'); ?>><?php esc_html_e('Create AVIF only', 'aawc'); ?></option>
            </select>
            <?php
        }, self::OPT_KEY, 'aawc_advanced');

        add_settings_field('publish_rewrite', __('On publish, rewrite post images to', 'aawc'), function(){
            $o = self::get_options();
            ?>
            <select name="<?php echo esc_attr(self::OPT_KEY); ?>[publish_rewrite]">
                <option value="none" <?php selected($o['publish_rewrite'], 'none'); ?>><?php esc_html_e('No change (don’t touch content)', 'aawc'); ?></option>
                <option value="webp" <?php selected($o['publish_rewrite'], 'webp'); ?>><?php esc_html_e('WebP only', 'aawc'); ?></option>
                <option value="avif" <?php selected($o['publish_rewrite'], 'avif'); ?>><?php esc_html_e('AVIF only', 'aawc'); ?></option>
                <option value="auto" <?php selected($o['publish_rewrite'], 'auto'); ?>><?php esc_html_e('Auto (prefer AVIF→WebP if available)', 'aawc'); ?></option>
            </select>
            <?php
        }, self::OPT_KEY, 'aawc_advanced');
    }

    public function env_notice() {
        $hasImagick = class_exists('Imagick');
        $hasGDWebP  = function_exists('imagewebp');
        $hasGDAvif  = function_exists('imageavif'); // PHP 8.1+
        echo '<div style="margin:8px 0;padding:10px;background:#fff;border:1px solid #ccd0d4;">';
        echo '<strong>Environment:</strong> ';
        echo 'Imagick: ' . ($hasImagick ? '<span style="color:green">Yes</span>' : '<span style="color:#a00">No</span>');
        echo ' &nbsp; GD WebP: ' . ($hasGDWebP ? '<span style="color:green">Yes</span>' : '<span style="color:#a00">No</span>');
        echo ' &nbsp; GD AVIF: ' . ($hasGDAvif ? '<span style="color:green">Yes</span>' : '<span style="color:#a00">No</span>');
        echo '<br/>If AVIF/WebP are not supported by PHP libraries, you can install CLI encoders on Ubuntu:<br/><code>sudo apt update && sudo apt install webp libavif-bin</code>';
        echo '</div>';
    }

    public function add_settings_page() {
        add_options_page(
            __('AVIF/WebP Converter', 'aawc'),
            __('AVIF/WebP Converter', 'aawc'),
            'manage_options',
            'aawc-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) { return; }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AVIF/WebP Converter', 'aawc'); ?></h1>
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

    public function sanitize_options($in) {
        $d = self::defaults();
        $out = [];
        $out['enable_avif']       = empty($in['enable_avif']) ? 0 : 1;
        $out['enable_webp']       = empty($in['enable_webp']) ? 0 : 1;
        $out['quality_avif']      = isset($in['quality_avif']) ? max(0, min(100, intval($in['quality_avif']))) : $d['quality_avif'];
        $out['quality_webp']      = isset($in['quality_webp']) ? max(0, min(100, intval($in['quality_webp']))) : $d['quality_webp'];
        $out['convert_sizes']     = empty($in['convert_sizes']) ? 0 : 1;
        $out['delivery_priority'] = ($in['delivery_priority'] ?? $d['delivery_priority']) === 'webp_avif' ? 'webp_avif' : 'avif_webp';
        $out['content_filter']    = empty($in['content_filter']) ? 0 : 1;
        $out['cli_fallback']      = empty($in['cli_fallback']) ? 0 : 1;
        $out['sideload_remote'] = empty($in['sideload_remote']) ? 0 : 1;


        $validPref = ['both','webp','avif'];
        $pngt  = $in['png_target']  ?? $d['png_target'];
        $jpgt  = $in['jpeg_target'] ?? $d['jpeg_target'];
        $out['png_target']  = in_array($pngt,  $validPref, true) ? $pngt  : 'both';
        $out['jpeg_target'] = in_array($jpgt, $validPref, true) ? $jpgt : 'both';

        $validRewrite = ['none','webp','avif','auto'];
        $pr = $in['publish_rewrite'] ?? $d['publish_rewrite'];
        $out['publish_rewrite'] = in_array($pr, $validRewrite, true) ? $pr : 'auto';

        $out['delete_originals'] = empty($in['delete_originals']) ? 0 : 1;
        return $out;
    }

    // ---------- Upload Hook ----------

    public function on_generate_attachment_metadata($metadata, $attachment_id) {
        $mime = get_post_mime_type($attachment_id);
        if (!self::is_supported_upload_mime($mime)) { return $metadata; }

        $opts = self::get_options();
        $orig = get_attached_file($attachment_id);
        if (!$orig || !file_exists($orig)) { return $metadata; }

        // Remember original size files for potential deletion
        $originalSizeFiles = [];
        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $info) {
                if (!empty($info['file'])) { $originalSizeFiles[$size] = $info['file']; }
            }
        }

        // Respect per-source target selection
        $targetPref = $this->format_for_mime_pref($mime, $opts);
        $wantAvif   = !empty($opts['enable_avif']) && ($targetPref === 'avif' || $targetPref === 'both');
        $wantWebp   = !empty($opts['enable_webp']) && ($targetPref === 'webp' || $targetPref === 'both');

        // Convert original
        if ($wantAvif) { $this->convert_to_format($orig, self::with_ext($orig,'avif'), 'avif', intval($opts['quality_avif']), !empty($opts['cli_fallback'])); }
        if ($wantWebp) { $this->convert_to_format($orig, self::with_ext($orig,'webp'), 'webp', intval($opts['quality_webp']), !empty($opts['cli_fallback'])); }

        // Convert sizes
        if (!empty($opts['convert_sizes']) && !empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            $dir = pathinfo($orig, PATHINFO_DIRNAME);
            foreach ($metadata['sizes'] as $size => $info) {
                if (empty($info['file'])) { continue; }
                $sizePath = $dir . '/' . $info['file'];
                if ($wantAvif) { $this->convert_to_format($sizePath, self::with_ext($sizePath,'avif'), 'avif', intval($opts['quality_avif']), !empty($opts['cli_fallback'])); }
                if ($wantWebp) { $this->convert_to_format($sizePath, self::with_ext($sizePath,'webp'), 'webp', intval($opts['quality_webp']), !empty($opts['cli_fallback'])); }
            }
        }

        // Decide primary format if deleting originals
        if (!empty($opts['delete_originals'])) {
            $primaryFmt = $this->decide_primary_format_for_deletion($mime, $opts);
            if ($primaryFmt) {
                $metadata = $this->swap_primary_and_delete_originals($attachment_id, $orig, $metadata, $originalSizeFiles, $primaryFmt);
            }
        }

        // Annotate metadata
        if (empty($metadata['aawc'])) { $metadata['aawc'] = []; }
        $metadata['aawc']['converted'] = [
            'avif' => (bool)$wantAvif,
            'webp' => (bool)$wantWebp,
            'ts'   => time(),
        ];

        return $metadata;
    }

    private function decide_primary_format_for_deletion($srcMime, $opts) {
        $pref = $this->format_for_mime_pref($srcMime, $opts); // 'avif','webp','both'
        $order = $this->preferred_order(); // ['avif','webp'] or ['webp','avif']
        if ($pref === 'avif' || $pref === 'webp') {
            return $pref;
        }
        // If both, pick delivery priority
        return $order[0];
    }

    private function swap_primary_and_delete_originals($attachment_id, $origPath, $metadata, $originalSizeFiles, $primaryFmt) {
        // Ensure target exists
        $newMain = self::with_ext($origPath, $primaryFmt);
        if (!file_exists($newMain)) { return $metadata; }

        // Update attached file and mime type
        update_attached_file($attachment_id, $newMain);
        $newMime = ($primaryFmt === 'webp') ? 'image/webp' : 'image/avif';
        wp_update_post(['ID' => $attachment_id, 'post_mime_type' => $newMime]);

        // Update metadata 'file' to new relative path
        $metadata['file'] = self::abs_to_uploads_rel($newMain);

        // Update size file names where possible and delete old originals
        $dir = pathinfo($origPath, PATHINFO_DIRNAME);

        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $info) {
                // Old original path (for deletion)
                $oldRel = $originalSizeFiles[$size] ?? '';
                $oldAbs = $oldRel ? ($dir . '/' . $oldRel) : '';

                // New target path (if exists)
                if (!empty($info['file'])) {
                    $cand = $dir . '/' . $info['file'];
                    $newCand = self::with_ext($cand, $primaryFmt);
                    if (file_exists($newCand)) {
                        // swap metadata to point to new extension
                        $metadata['sizes'][$size]['file'] = basename($newCand);
                        // delete old original if exists
                        if ($oldAbs && file_exists($oldAbs)) { @unlink($oldAbs); }
                    }
                }
            }
        }

        // Delete the original main file (jpg/png) only if target exists
        if (file_exists($origPath)) { @unlink($origPath); }

        // Persist updated metadata immediately
        wp_update_attachment_metadata($attachment_id, $metadata);

        return $metadata;
    }

    // ---------- Conversion Core (Imagick → GD → CLI) ----------

    private function convert_to_format($src, $dest, $format, $quality, $allowCliFallback) {
        if (!file_exists($src)) { return false; }
        // Try Imagick first
        if ($this->convert_with_imagick($src, $dest, $format, $quality)) { return true; }
        // Then GD (requires imageavif for AVIF)
        if ($this->convert_with_gd($src, $dest, $format, $quality)) { return true; }
        // Optional CLI fallback
        if ($allowCliFallback && $this->convert_with_cli($src, $dest, $format, $quality)) { return true; }
        return false;
    }

    private function convert_with_imagick($src, $dest, $format, $quality) {
        if (!class_exists('Imagick')) { return false; }
        try {
            $img = new Imagick($src);

            // Preserve transparency + orientation
            if (method_exists($img, 'setImageAlphaChannel')) {
                $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            }
            if (method_exists($img, 'getImageOrientation')) {
                switch ($img->getImageOrientation()) {
                    case Imagick::ORIENTATION_RIGHTTOP:  $img->rotateImage('#000', 90); break;
                    case Imagick::ORIENTATION_BOTTOMRIGHT:$img->rotateImage('#000', 180); break;
                    case Imagick::ORIENTATION_LEFTBOTTOM:$img->rotateImage('#000', 270); break;
                }
                if (method_exists($img, 'setImageOrientation')) {
                    $img->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
                }
            }

            $formatUpper = strtoupper($format);
            $supported = $img->queryFormats($formatUpper);
            if (empty($supported)) {
                if ($formatUpper !== 'WEBP' && $formatUpper !== 'AVIF') return false;
            }

            $img->setImageFormat($formatUpper);
            if (method_exists($img, 'setImageCompressionQuality')) {
                $img->setImageCompressionQuality(max(0, min(100, $quality)));
            }
            if ($formatUpper === 'WEBP') {
                $img->setOption('webp:method', '6');
                $img->setOption('webp:auto-filter', 'true');
            } elseif ($formatUpper === 'AVIF') {
                $img->setOption('heic:speed', '6'); // ignored if unavailable
            }

            $ok = $img->writeImage($dest);
            $img->destroy();
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function convert_with_gd($src, $dest, $format, $quality) {
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $im  = null;
        if ($ext === 'jpg' || $ext === 'jpeg') {
            if (!function_exists('imagecreatefromjpeg')) return false;
            $im = @imagecreatefromjpeg($src);
        } elseif ($ext === 'png') {
            if (!function_exists('imagecreatefrompng')) return false;
            $im = @imagecreatefrompng($src);
        } else {
            return false;
        }
        if (!$im) return false;

        imagealphablending($im, true);
        imagesavealpha($im, true);

        $ok = false;
        if ($format === 'webp' && function_exists('imagewebp')) {
            $ok = imagewebp($im, $dest, max(0, min(100, $quality)));
        } elseif ($format === 'avif' && function_exists('imageavif')) {
            $ok = imageavif($im, $dest, max(0, min(100, $quality)));
        }
        imagedestroy($im);
        return (bool)$ok;
    }

    private function convert_with_cli($src, $dest, $format, $quality) {
        if (!function_exists('shell_exec')) return false;

        if ($format === 'webp') {
            $cwebp = trim(shell_exec('command -v cwebp || which cwebp'));
            if (!$cwebp) return false;
            $q = max(0, min(100, intval($quality)));
            $cmd = escapeshellcmd($cwebp) . ' -quiet -q ' . intval($q) . ' ' . escapeshellarg($src) . ' -o ' . escapeshellarg($dest) . ' 2>&1';
            shell_exec($cmd);
            return file_exists($dest);
        } elseif ($format === 'avif') {
            $avifenc = trim(shell_exec('command -v avifenc || which avifenc'));
            if (!$avifenc) return false;
            $q = max(0, min(100, intval($quality)));
            $avifq = max(0, min(63, 63 - intval(round($q * 0.63))));
            $cmd = escapeshellcmd($avifenc) . ' -s 8 --min 0 --max ' . intval($avifq) . ' ' . escapeshellarg($src) . ' -o ' . escapeshellarg($dest) . ' 2>&1';
            shell_exec($cmd);
            return file_exists($dest);
        }
        return false;
    }

    // ---------- Delivery Filters ----------

    public function allow_modern_mimes($mimes) {
        $mimes['webp'] = 'image/webp';
        $mimes['avif'] = 'image/avif';
        return $mimes;
    }

    private function pick_best_format_available_for_url($url) {
        $opts = self::get_options();
        foreach ($this->preferred_order() as $fmt) {
            if (!$opts["enable_{$fmt}"]) { continue; }
            if ($fmt === 'avif' && !self::prefers('avif')) continue;
            if ($fmt === 'webp' && !self::prefers('webp')) continue;
            $found = self::file_exists_for_url_with_ext($url, $fmt);
            if ($found) { return $found[1]; }
        }
        return null;
    }

    public function filter_img_attributes($attr, $attachment, $size) {
        if (empty($attr['src'])) return $attr;
        $best = $this->pick_best_format_available_for_url($attr['src']);
        if ($best) { $attr['src'] = $best; }
        return $attr;
    }

    public function filter_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        if (empty($sources) || !is_array($sources)) return $sources;
        $opts = self::get_options();

        $targetFmt = null;
        foreach ($this->preferred_order() as $fmt) {
            if ($opts["enable_{$fmt}"] && self::prefers($fmt)) { $targetFmt = $fmt; break; }
        }
        if (!$targetFmt) return $sources;

        foreach ($sources as $w => $entry) {
            if (empty($entry['url'])) continue;
            $alt = self::file_exists_for_url_with_ext($entry['url'], $targetFmt);
            if ($alt) { $sources[$w]['url'] = $alt[1]; }
        }
        return $sources;
    }

    public function filter_the_content_images($content) {
        $opts = self::get_options();
        if (empty($opts['content_filter'])) return $content;

        $fmtOk = (self::prefers('avif') && $opts['enable_avif']) || (self::prefers('webp') && $opts['enable_webp']);
        if (!$fmtOk) return $content;

        if (!class_exists('DOMDocument')) return $content;

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $encoding = '<?xml encoding="utf-8" ?>';
        $loaded = $doc->loadHTML($encoding . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if (!$loaded) { libxml_clear_errors(); return $content; }

        $imgs = $doc->getElementsByTagName('img');
        $toProcess = [];
        foreach ($imgs as $img) { $toProcess[] = $img; }

        foreach ($toProcess as $img) {
            $src = $img->getAttribute('src');
            if (!$src) continue;

            $uploads = wp_get_upload_dir();
            if (empty($uploads['baseurl']) || strpos($src, $uploads['baseurl']) !== 0) continue;

            $best = $this->pick_best_format_available_for_url($src);
            if ($best) {
                $img->setAttribute('src', $best);
                if ($img->hasAttribute('srcset')) {
                    $srcset = $img->getAttribute('srcset');
                    $parts = array_map('trim', explode(',', $srcset));
                    $newparts = [];
                    foreach ($parts as $p) {
                        $bits = preg_split('/\s+/', $p);
                        $u = array_shift($bits);
                        $alt = self::file_exists_for_url_with_ext($u, pathinfo($best, PATHINFO_EXTENSION));
                        if ($alt) { $u = $alt[1]; }
                        $newparts[] = trim($u . ' ' . implode(' ', $bits));
                    }
                    $img->setAttribute('srcset', implode(', ', $newparts));
                }
            }
        }

        $html = $doc->saveHTML();
        libxml_clear_errors();
        $html = preg_replace('/^<\?xml.*?\?>/i', '', $html);
        return $html;
    }

    // ---------- Publish-time rewrite (persist + optional deletion) ----------

    public function on_transition_post_status($new_status, $old_status, $post) {
        if ($new_status !== 'publish') { return; }
        if ($post->post_type !== 'post' && $post->post_type !== 'page') { return; }

        $opts = self::get_options();
        $mode = $opts['publish_rewrite']; // 'none'|'webp'|'avif'|'auto'
        if ($mode === 'none') { return; }

        $content = $post->post_content;
        if (!class_exists('DOMDocument')) { return; }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $encoding = '<?xml encoding="utf-8" ?>';
        $loaded = $doc->loadHTML($encoding . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if (!$loaded) { libxml_clear_errors(); return; }

        $uploads = wp_get_upload_dir();
        $baseurl = $uploads['baseurl'];

        $imgs = $doc->getElementsByTagName('img');
        $changed = false;
        $toDeletePaths = [];

        $decide = function($url) use ($mode) {
            if ($mode === 'webp') return 'webp';
            if ($mode === 'avif') return 'avif';
            // auto: prefer avif, then webp if available
            // we’ll check existence below
            return 'auto';
        };







        // Collect nodes first (NodeList is live)
       // Collect nodes first (NodeList is live)
$nodes = [];
foreach ($imgs as $img) { $nodes[] = $img; }

foreach ($nodes as $img) {
    $src = $img->getAttribute('src');
    if (!$src) { continue; }

    $isLocal = (strpos($src, $baseurl) === 0);

    // 1) Remote → sideload (if enabled)
    if (!$isLocal && !empty($opts['sideload_remote']) && $this->is_remote_candidate_url($src)) {
        $new_id = $this->sideload_remote_image($src, $post->ID);
        if ($new_id) {
            // choose best local (AVIF/WebP) according to publish mode & what exists
            $localBest = $this->pick_best_local_after_import($new_id, $mode);
            if ($localBest) {
                $img->setAttribute('src', $localBest);
                $changed = true;

                // refresh srcset from the new attachment, then normalize to chosen ext if present
                $new_srcset = wp_get_attachment_image_srcset($new_id, 'full');
                if ($new_srcset) {
                    $parts = array_map('trim', explode(',', $new_srcset));
                    $extChosen = strtolower(pathinfo(parse_url($localBest, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                    $newparts = [];
                    foreach ($parts as $p) {
                        $bits = preg_split('/\s+/', $p);
                        $u = array_shift($bits);
                        if (in_array($extChosen, ['avif','webp'], true)) {
                            $alt = self::file_exists_for_url_with_ext($u, $extChosen);
                            if ($alt) { $u = $alt[1]; }
                        }
                        $newparts[] = trim($u . ' ' . implode(' ', $bits));
                    }
                    $img->setAttribute('srcset', implode(', ', $newparts));
                } else {
                    if ($img->hasAttribute('srcset')) { $img->removeAttribute('srcset'); }
                }
            }
        }
        // refresh for next step
        $src = $img->getAttribute('src');
        $isLocal = (strpos($src, $baseurl) === 0);
    }

    // 2) If still not local (e.g., remote not supported), skip
    if (!$isLocal) { continue; }

    // 3) Existing local-uploads rewrite (unchanged logic)
    $fmt = $decide($src);
    $targetExts = ($fmt === 'auto') ? ['avif','webp'] : [$fmt];

    $chosenUrl = null;
    foreach ($targetExts as $ext) {
        $alt = self::file_exists_for_url_with_ext($src, $ext);
        if ($alt) { $chosenUrl = $alt[1]; break; }
    }
    if ($chosenUrl) {
        if (!empty($opts['delete_originals'])) {
            $origPath = self::uploads_map_url_to_path($src);
            if ($origPath && file_exists($origPath)) { $toDeletePaths[] = $origPath; }
        }

        $img->setAttribute('src', $chosenUrl);
        $changed = true;

        if ($img->hasAttribute('srcset')) {
            $srcset = $img->getAttribute('srcset');
            $parts = array_map('trim', explode(',', $srcset));
            $newparts = [];
            foreach ($parts as $p) {
                $bits = preg_split('/\s+/', $p);
                $u = array_shift($bits);
                $picked = null;
                foreach ($targetExts as $ext) {
                    $alt = self::file_exists_for_url_with_ext($u, $ext);
                    if ($alt) { $picked = $alt[1]; break; }
                }
                $newparts[] = trim(($picked ?: $u) . ' ' . implode(' ', $bits));
                if ($picked && !empty($opts['delete_originals'])) {
                    $origPath = self::uploads_map_url_to_path($u);
                    if ($origPath && file_exists($origPath)) { $toDeletePaths[] = $origPath; }
                }
            }
            $img->setAttribute('srcset', implode(', ', $newparts));
        }
    }
}


        if ($changed) {
            $html = $doc->saveHTML();
            libxml_clear_errors();
            $html = preg_replace('/^<\?xml.*?\?>/i', '', $html);

            // Avoid recursion loops
            remove_action('transition_post_status', [$this, 'on_transition_post_status'], 20);
            wp_update_post(['ID' => $post->ID, 'post_content' => $html]);
            add_action('transition_post_status', [$this, 'on_transition_post_status'], 20, 3);

            // Delete queued originals (best-effort)
            if (!empty($opts['delete_originals'])) {
                $toDeletePaths = array_unique($toDeletePaths);
                foreach ($toDeletePaths as $p) {
                    // Only remove if a modern counterpart exists to prevent broken images
                    $hasModern = file_exists(self::with_ext($p, 'webp')) || file_exists(self::with_ext($p, 'avif'));
                    if ($hasModern && file_exists($p)) { @unlink($p); }
                }
            }
        }
    }
    
    
    
    
    
    

    // ---------- WP-CLI Bulk Conversion ----------

    public static function cli_regenerate($args, $assoc_args) {
        $opts = self::get_options();
        $q = new WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'fields'         => 'ids',
        ]);
        $count = 0;
        foreach ($q->posts as $att_id) {
            $file = get_attached_file($att_id);
            if ($file && file_exists($file)) {
                // Generate as per prefs
                $meta = wp_get_attachment_metadata($att_id);
                $mime = get_post_mime_type($att_id);
                $targetPref = self::instance()->format_for_mime_pref($mime, $opts);
                $wantAvif = !empty($opts['enable_avif']) && ($targetPref === 'avif' || $targetPref === 'both');
                $wantWebp = !empty($opts['enable_webp']) && ($targetPref === 'webp' || $targetPref === 'both');

                if ($wantAvif) self::instance()->convert_to_format($file, self::with_ext($file,'avif'), 'avif', intval($opts['quality_avif']), !empty($opts['cli_fallback']));
                if ($wantWebp) self::instance()->convert_to_format($file, self::with_ext($file,'webp'), 'webp', intval($opts['quality_webp']), !empty($opts['cli_fallback']));

                if (!empty($opts['convert_sizes']) && !empty($meta['sizes'])) {
                    $dir = pathinfo($file, PATHINFO_DIRNAME);
                    foreach ($meta['sizes'] as $s) {
                        if (empty($s['file'])) continue;
                        $p = $dir . '/' . $s['file'];
                        if ($wantAvif) self::instance()->convert_to_format($p, self::with_ext($p,'avif'), 'avif', intval($opts['quality_avif']), !empty($opts['cli_fallback']));
                        if ($wantWebp) self::instance()->convert_to_format($p, self::with_ext($p,'webp'), 'webp', intval($opts['quality_webp']), !empty($opts['cli_fallback']));
                    }
                }

                // Delete originals and switch primary if set
                if (!empty($opts['delete_originals'])) {
                    $originalSizeFiles = [];
                    if (!empty($meta['sizes'])) {
                        foreach ($meta['sizes'] as $size => $info) {
                            if (!empty($info['file'])) $originalSizeFiles[$size] = $info['file'];
                        }
                    }
                    $primaryFmt = self::instance()->decide_primary_format_for_deletion($mime, $opts);
                    $meta = self::instance()->swap_primary_and_delete_originals($att_id, $file, $meta, $originalSizeFiles, $primaryFmt);
                }

                $count++;
                if (defined('WP_CLI') && WP_CLI) {
                    WP_CLI::log("Converted attachment #$att_id");
                }
            }
        }
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::success("Processed {$count} attachments.");
        }
    }
    
    
    
    private function is_remote_candidate_url(string $url): bool {
    if (!preg_match('~^https?://~i', $url)) return false;                 // only http(s)
    $uploads = wp_get_upload_dir();
    if (!empty($uploads['baseurl']) && strpos($url, $uploads['baseurl']) === 0) return false; // already local
    // Image we know how to convert (extend if you like)
    return (bool) preg_match('~\.(jpe?g|png)(\?.*)?$~i', parse_url($url, PHP_URL_PATH) ?? '');
}

/**
 * Download a remote image into Media Library, return attachment ID (or 0 on failure).
 * Also triggers metadata generation -> your converters run automatically.
 */
private function sideload_remote_image(string $url, int $post_id = 0): int {
    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('media_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $tmp = download_url($url, 30);
    if (is_wp_error($tmp)) return 0;

    // Detect real type
    $ft = wp_check_filetype_and_ext($tmp, basename(parse_url($url, PHP_URL_PATH) ?: ''));
    $ext = $ft['ext'];
    if (!in_array($ext, ['jpg','jpeg','png'], true)) {
        @unlink($tmp);
        return 0;
    }

    $basename = 'image-' . wp_generate_password(8, false) . '.' . $ext;
    $file_array = ['name' => $basename, 'tmp_name' => $tmp];

    $att_id = media_handle_sideload($file_array, $post_id);
    if (is_wp_error($att_id)) {
        @unlink($tmp);
        return 0;
    }
    return (int) $att_id;
}


/** Choose best local URL by publish mode + what exists */
private function pick_best_local_after_import(int $att_id, string $mode = 'auto'): string {
    $url  = wp_get_attachment_url($att_id);
    $path = self::uploads_map_url_to_path($url);
    if (!$url || !$path) return $url;

    $candidates = [];
    if ($mode === 'webp') $candidates = ['webp'];
    elseif ($mode === 'avif') $candidates = ['avif'];
    else $candidates = ['avif','webp']; // auto: prefer AVIF, then WebP

    foreach ($candidates as $ext) {
        $alt_path = self::with_ext($path, $ext);
        if (file_exists($alt_path)) {
            return self::with_ext($url, $ext);
        }
    }
    return $url; // fallback to whatever WP saved
}

    
    
}

// Bootstrap
AAWC_Plugin::instance();
