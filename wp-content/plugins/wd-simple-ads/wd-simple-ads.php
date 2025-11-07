<?php
/**
 * Plugin Name: WD Simple Ads (Flexible In-Content, Per-Paragraph Codes + Lazy AdSense)
 * Description: Paste AdSense/HTML in Settings → WD Simple Ads. Supports different ad code per paragraph position, after special blocks (e.g., wp-block-file, Foxiz download), after first paragraph, after header, after content, and footer. PageSpeed-friendly: preconnects, reserved-height CSS, lazy loader that loads adsbygoogle once and only after slot has real width. Skips on AMP.
 * Version: 1.3.4
 * Author: WD
 */

if (!defined('ABSPATH')) exit;

final class WD_Simple_Ads {
    const OPT_KEY = 'wdads_options';
    private static $instance = null;

    public static function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Admin UI
        add_action('admin_menu',  [$this, 'add_menu']);
        add_action('admin_init',  [$this, 'register_settings']);

        // Frontend injections
        add_action('wp_body_open',      [$this, 'inject_above_header'], 10);
        add_action('template_redirect', [$this, 'enable_after_header_buffer'], 0);
        add_filter('the_content',       [$this, 'inject_after_first_paragraph'], 18);
        add_filter('the_content',       [$this, 'inject_after_paragraph_map'], 19);
        add_filter('the_content',       [$this, 'inject_after_blocks'], 20);
        add_filter('the_content',       [$this, 'inject_after_content'], 999);
        add_action('wp_footer',         [$this, 'inject_footer'], 20);

        add_shortcode('wdads', [$this, 'shortcode']);
    }

    /* ---------------- Helpers ---------------- */

    private function opts(): array {
        $defaults = [
            'disable_on_amp' => '1',
            // fixed placements
            'above_header'   => '',
            'after_header'   => '',
            'after_first'    => '',
            'after_content'  => '',
            'footer'         => '',
            // per-paragraph map
            'p_map'          => [],
            'p_max'          => '8',
            // after blocks
            'block_classes'  => 'wp-block-file, wp-block-foxiz-elements-download, gb-download',
            'block_code'     => '',
            // where to apply in-content placements
            'content_post_types' => ['post','page'],
        ];
        $saved = (array) get_option(self::OPT_KEY, []);
        if (empty($saved['content_post_types'])) $saved['content_post_types'] = ['post','page'];
        if (is_string($saved['content_post_types'])) {
            $saved['content_post_types'] = array_filter(array_map('trim', explode(',', $saved['content_post_types'])));
        }
        if (empty($saved['p_map']) || !is_array($saved['p_map'])) {
            $saved['p_map'] = [];
        }
        return array_merge($defaults, $saved);
    }

    private function is_amp(): bool {
        return function_exists('is_amp_endpoint') && is_amp_endpoint();
    }

    private function can_print_front(): bool {
        if (is_admin() || is_feed() || (function_exists('wp_doing_ajax') && wp_doing_ajax())) return false;
        return true;
    }

    private function current_is_allowed_post_type(array $allowed): bool {
        if (!is_singular()) return false;
        return in_array(get_post_type(), $allowed, true);
    }

    /** Strip loader + inline pushes; ensure CLS-safe wrapper for lone <ins> */
   
   /** Strip loader + inline pushes; ensure CLS-safe wrapper for lone <ins> */
private function clean_adsense_code(string $code): string {
    if ($code === '') return $code;

    // Strip loader script
    // Ensure display:block on ins, always (wrapper or not)
$code = preg_replace(
    '#(<ins\b[^>]*class=["\'][^"\']*\badsbygoogle\b[^"\']*["\'][^>]*)(>)#i',
    '$1 style="display:block"$2',
    $code,
    1
);


    // Strip inline pushes
    $code = preg_replace(
        '#<script[^>]*>\s*\(adsbygoogle\s*=\s*window\.adsbygoogle\s*\|\|\s*\[\]\)\.push\(\s*\{\s*\}\s*\)\s*;\s*</script>#is',
        '',
        $code
    );

    // ✅ Strip any pre-set loader/status attrs users might paste
    $code = preg_replace('#\sdata-loaded=(["\']).*?\1#i', '', $code);
    $code = preg_replace('#\sdata-adsbygoogle-status=(["\']).*?\1#i', '', $code);
    $code = preg_replace('#\sdata-ad-status=(["\']).*?\1#i', '', $code);

    $code = trim($code);

    // If there's an ins.adsbygoogle but no .ad-slot wrapper, wrap it
    if ((stripos($code, 'class="adsbygoogle') !== false || stripos($code, "class='adsbygoogle") !== false)
        && stripos($code, 'class="ad-slot') === false && stripos($code, "class='ad-slot") === false) {

        // Ensure display:block on ins (helps auto-format slots)
        $code = preg_replace(
            '#(<ins\b[^>]*class=["\'][^"\']*\badsbygoogle\b[^"\']*["\'][^>]*)(>)#i',
            '$1 style="display:block"$2',
            $code, 1
        );

        $code = '<div class="ad-slot ad-slot--constrained" role="complementary" aria-label="Advertisement" style="--ad-h:280px;">'
      . $code
      . '</div>';
    }

    return $code;
}

   
   
   
   

    private function echo_code(string $code): void {
        $code = $this->clean_adsense_code($code);
        if ($code === '') return;

        if (current_user_can('unfiltered_html')) {
            echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            echo wp_kses_post($code);
        }
    }

    private function get_max_inserts(): int {
        $n = (int) ($this->opts()['p_max'] ?? 0);
        return $n > 0 ? $n : 999;
    }

    /* ---------------- Admin UI ---------------- */

    public function add_menu(): void {
        add_options_page('WD Simple Ads','WD Simple Ads','manage_options','wd-simple-ads',[$this,'render_settings_page']);
    }

    public function register_settings(): void {
        register_setting('wdads_settings', self::OPT_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [$this,'sanitize_options'],
            'default'           => [],
        ]);
    }

    public function sanitize_options($input): array {
        $out = [];
        $out['disable_on_amp']   = empty($input['disable_on_amp']) ? '0' : '1';
        $out['p_max']            = (string) max(1, (int) ($input['p_max'] ?? 8));
        $out['block_classes']    = isset($input['block_classes']) ? sanitize_text_field($input['block_classes']) : '';
        $out['content_post_types']= isset($input['content_post_types']) ? (array) $input['content_post_types'] : ['post','page'];

        foreach (['above_header','after_header','after_first','after_content','footer','block_code'] as $k) {
            $val = isset($input[$k]) ? (string) $input[$k] : '';
            $out[$k] = current_user_can('unfiltered_html') ? $val : wp_kses_post($val);
        }

        // Paragraph map
        $out['p_map'] = [];
        if (!empty($input['p_map']) && is_array($input['p_map'])) {
            $seen = [];
            foreach ($input['p_map'] as $row) {
                $pos  = isset($row['pos'])  ? (int) $row['pos']  : 0;
                $code = isset($row['code']) ? (string) $row['code'] : '';
                if ($pos > 0 && $code !== '' && !isset($seen[$pos])) {
                    $seen[$pos]    = true;
                    $out['p_map'][]= ['pos'=>$pos, 'code'=> (current_user_can('unfiltered_html') ? $code : wp_kses_post($code))];
                }
            }
            usort($out['p_map'], fn($a,$b)=> $a['pos'] <=> $b['pos']);
        }

        return $out;
    }

    public function render_settings_page(): void {
        $o = $this->opts(); ?>
        <div class="wrap">
            <h1>WD Simple Ads</h1>

            <form method="post" action="options.php">
                <?php settings_fields('wdads_settings'); ?>

                <h2>Global Settings</h2>
                <p>Configure your AdSense or HTML ad placements here.</p>
                <p>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[disable_on_amp]" value="1" <?php checked($o['disable_on_amp'],'1'); ?>>
                        Disable on AMP
                    </label><br>
                    <em>Skip printing ads on AMP endpoints.</em>
                </p>

                <h2>Fixed Placements</h2>
                <?php
                $fixed = [
                    'above_header'=>'Above Header (wp_body_open)',
                    'after_header'=>'After Header',
                    'after_first'=>'After First Paragraph',
                    'after_content'=>'After Content',
                    'footer'=>'Footer (wp_footer)',
                ];
                foreach ($fixed as $key=>$label) { ?>
                    <p><strong><?php echo esc_html($label); ?></strong><br>
                    <textarea name="<?php echo esc_attr(self::OPT_KEY . '['.$key.']'); ?>" rows="6" style="width:100%;font-family:monospace;"><?php echo esc_textarea($o[$key]); ?></textarea></p>
                <?php } ?>

                <h2>In-Content (Paragraphs & Blocks)</h2>
                <p><strong>Per-paragraph codes:</strong> add rows like (3 → your ad code). Paste full AdSense snippet or just <code>&lt;ins class="adsbygoogle"&gt;</code>; the plugin will clean and wrap.</p>

                <table id="wd-pmap" class="widefat">
                    <thead><tr><th>Paragraph #</th><th>Ad Code</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($o['p_map'])) $o['p_map'] = [['pos'=>'','code'=>'']]; ?>
                    <?php foreach ($o['p_map'] as $i=>$row): ?>
                        <tr>
                            <td><input type="number" min="1" name="<?php echo esc_attr(self::OPT_KEY); ?>[p_map][<?php echo (int)$i; ?>][pos]" value="<?php echo esc_attr($row['pos']); ?>" style="width:80px"></td>
                            <td><textarea name="<?php echo esc_attr(self::OPT_KEY); ?>[p_map][<?php echo (int)$i; ?>][code]" rows="5" style="width:100%;font-family:monospace;"><?php echo esc_textarea($row['code']); ?></textarea></td>
                            <td><button type="button" class="button remove-row">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="add-row">+ Add Row</button></p>

                <script>
                (function(){
                    const tbl=document.querySelector('#wd-pmap tbody');
                    const add=document.getElementById('add-row');
                    add.addEventListener('click',()=>{
                        const i=tbl.querySelectorAll('tr').length;
                        tbl.insertAdjacentHTML('beforeend',
`<tr>
  <td><input type="number" min="1" name="<?php echo esc_attr(self::OPT_KEY); ?>[p_map][${i}][pos]" style="width:80px"></td>
  <td><textarea name="<?php echo esc_attr(self::OPT_KEY); ?>[p_map][${i}][code]" rows="5" style="width:100%;font-family:monospace;"></textarea></td>
  <td><button type="button" class="button remove-row">Remove</button></td>
</tr>`);
                    });
                    tbl.addEventListener('click', e=>{
                        if(e.target.classList.contains('remove-row')){
                            e.target.closest('tr').remove();
                        }
                    });
                })();
                </script>

                <p><strong>After Blocks (CSS classes)</strong><br>
                    <input type="text" name="<?php echo esc_attr(self::OPT_KEY); ?>[block_classes]" value="<?php echo esc_attr($o['block_classes']); ?>" style="width:100%;" placeholder="e.g. wp-block-file, wp-block-foxiz-elements-download, gb-download">
                    <br><em>Insert after any &lt;div class="…token…"…&gt;…&lt;/div&gt; that matches.</em>
                </p>

                <p><strong>Code for Block Matches</strong><br>
                    <textarea name="<?php echo esc_attr(self::OPT_KEY); ?>[block_code]" rows="6" style="width:100%;font-family:monospace;"><?php echo esc_textarea($o['block_code']); ?></textarea>
                </p>

                <p><strong>Max inserts per post</strong>:
                    <input type="number" min="1" step="1" name="<?php echo esc_attr(self::OPT_KEY); ?>[p_max]" value="<?php echo esc_attr($o['p_max']); ?>" style="width:100px">
                    <span class="description">Caps total paragraph+block insertions.</span>
                </p>

                <?php submit_button(); ?>
            </form>
        </div>
    <?php }

    /* ---------------- Frontend ---------------- */

    public function enable_after_header_buffer(): void {
        if (!$this->can_print_front()) return;
        $o = $this->opts();
        if ($o['after_header'] === '') return;
        if ($o['disable_on_amp'] === '1' && $this->is_amp()) return;

        ob_start(function ($html) use ($o) {
            ob_start(); $this->echo_code($o['after_header']); $rendered = ob_get_clean();
            $code = "\n<!-- WD Simple Ads: after_header -->\n$rendered\n<!-- /WD Simple Ads: after_header -->\n";

            $r = preg_replace('/<\/header\s*>/i', '</header>' . $code, $html, 1);
            if ($r !== null && $r !== $html) return $r;
            $r = preg_replace('/(<main\b[^>]*>)/i', '$1' . $code, $html, 1);
            if ($r !== null && $r !== $html) return $r;
            return $html;
        });

        add_action('shutdown', function () {
            while (ob_get_level() > 0) { @ob_end_flush(); }
        }, PHP_INT_MAX);
    }

    public function inject_above_header(): void {
        if (!$this->can_print_front()) return;
        $o = $this->opts();
        if ($o['disable_on_amp'] === '1' && $this->is_amp()) return;
        if ($o['above_header'] === '') return;
        echo "\n<!-- WD Simple Ads: above_header -->\n";
        $this->echo_code($o['above_header']);
        echo "\n<!-- /WD Simple Ads: above_header -->\n";
    }

    public function inject_after_first_paragraph($content){
        if (is_admin() || is_feed()) return $content;
        $o = $this->opts();
        if ($o['disable_on_amp'] === '1' && $this->is_amp()) return $content;
        if (!is_singular() || !$this->current_is_allowed_post_type($o['content_post_types'])) return $content;
        if ($o['after_first'] === '') return $content;

        $snippet = (function(){ ob_start(); $this->echo_code($this->opts()['after_first']); return ob_get_clean(); })->call($this);
        return preg_replace('#</p>#i', '</p>'."\n<div class=\"wd-simple-ads-after-first\">\n{$snippet}\n</div>\n", $content, 1);
    }

    public function inject_after_paragraph_map($content){
        if (is_admin() || is_feed()) return $content;
        $o = $this->opts();
        if ($o['disable_on_amp'] === '1' && $this->is_amp()) return $content;
        if (!is_singular() || !$this->current_is_allowed_post_type($o['content_post_types'])) return $content;
        if (empty($o['p_map'])) return $content;

        // Build map: pos => code
        $map = [];
        foreach ($o['p_map'] as $row) {
            $p = (int) ($row['pos'] ?? 0);
            $c = (string) ($row['code'] ?? '');
            if ($p > 0 && $c !== '' && !isset($map[$p])) $map[$p] = $c;
        }
        if (empty($map)) return $content;

        $parts = preg_split('#(</p>)#i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!$parts || count($parts) < 3) return $content;

        $out = ''; $para = 0; $inserted = 0; $max = $this->get_max_inserts();
        for ($i=0; $i<count($parts); $i+=2) {
            $out .= $parts[$i];
            if (isset($parts[$i+1])) {
                $out .= $parts[$i+1]; // </p>
                $para++;
                if ($inserted < $max && isset($map[$para])) {
                    $rendered = (function($c){ ob_start(); $this->echo_code($c); return ob_get_clean(); })->call($this, $map[$para]);
                    $out .= "\n<div class=\"wd-simple-ads-after-p wd-simple-ads-after-p-{$para}\">\n{$rendered}\n</div>\n";
                    $inserted++;
                }
            }
        }
        return $out;
    }

    public function inject_after_blocks($content){
        if (is_admin() || is_feed()) return $content;
        $o = $this->opts();
        if ($o['disable_on_amp'] === '1' && $this->is_amp()) return $content;
        if (!is_singular() || !$this->current_is_allowed_post_type($o['content_post_types'])) return $content;

        $code = (string) $o['block_code'];
        if ($code === '') return $content;

        $tokens = array_filter(array_map('trim', explode(',', (string)$o['block_classes'])));
        if (empty($tokens)) return $content;

        $inserted = 0; $max = $this->get_max_inserts();
        $lower = strtolower($content); $offset = 0;

        $find_end_of_div = function(string $html, int $openTagEndPos): int {
            $len = strlen($html); $pos = $openTagEndPos; $depth = 1;
            while ($pos < $len && $depth > 0) {
                $nextOpen  = stripos($html, '<div', $pos);
                $nextClose = stripos($html, '</div>', $pos);
                if ($nextClose === false && $nextOpen === false) return $len;
                if ($nextOpen !== false && $nextOpen < $nextClose) {
                    $gt = strpos($html, '>', $nextOpen); if ($gt === false) return $len;
                    $depth++; $pos = $gt + 1;
                } else { $depth--; $pos = $nextClose + 6; }
            }
            return $pos;
        };

        while ($inserted < $max) {
            $foundPos = -1; $openTagEnd = -1; $matchedToken = '';
            foreach ($tokens as $t) {
                $tokPos = stripos($lower, $t, $offset);
                if ($tokPos === false) continue;
                $divPos = strripos(substr($lower, 0, $tokPos+1), '<div');
                if ($divPos === false) continue;
                $gt = strpos($lower, '>', $tokPos);
                if ($gt === false || $divPos > $gt) continue;
                $openFrag = substr($lower, $divPos, $gt - $divPos + 1);
                if (strpos($openFrag, $t) === false || stripos($openFrag, 'class=') === false) continue;
                $foundPos = $divPos; $openTagEnd = $gt + 1; $matchedToken = $t; break;
            }
            if ($foundPos === -1) break;

            $endPos   = $find_end_of_div($content, $openTagEnd);
            $before   = substr($content, 0, $endPos);
            $after    = substr($content, $endPos);
            $rendered = (function($c){ ob_start(); $this->echo_code($c); return ob_get_clean(); })->call($this, $code);

// Sanitize the token so it’s a valid CSS class
$class_suffix = sanitize_html_class($matchedToken);

$injection = "\n<div class=\"wd-simple-ads-after-block wd-simple-ads-after-{$class_suffix}\">\n{$rendered}\n</div>\n";



            $content  = $before . $injection . $after;
            $offset   = $endPos + strlen($injection);
            $lower    = strtolower($content);
            $inserted++;
        }
        return $content;
    }

    public function inject_after_content($content){
        if (is_admin() || is_feed()) return $content;
        $o = $this->opts();
        if ($o['disable_on_amp'] === '1' && $this->is_amp()) return $content;
        if (!is_singular() || !$this->current_is_allowed_post_type($o['content_post_types'])) return $content;
        if ($o['after_content'] === '') return $content;

        $rendered = (function(){ ob_start(); $this->echo_code($this->opts()['after_content']); return ob_get_clean(); })->call($this);
        return $content . "\n<div class=\"wd-simple-ads-after-content\">\n{$rendered}\n</div>\n";
    }

    public function inject_footer(): void {
        if (!$this->can_print_front()) return;
        $o = $this->opts();
        if ($o['disable_on_amp'] === '1' && $this->is_amp()) return;
        if ($o['footer'] === '') return;
        echo "\n<!-- WD Simple Ads: footer -->\n";
        $this->echo_code($o['footer']);
        echo "\n<!-- /WD Simple Ads: footer -->\n";
    }

    public function shortcode($atts=[]): string {
        $atts = shortcode_atts(['id'=>''], $atts, 'wdads');
        $id   = sanitize_key($atts['id']);
        if (!$id) return '';
        $o = $this->opts();
        if ($o['disable_on_amp'] === '1' && $this->is_amp()) return '';
        if (!isset($o[$id]) || $o[$id] === '') return '';
        ob_start(); $this->echo_code($o[$id]); return ob_get_clean();
    }
}

// Boot
WD_Simple_Ads::instance();

/* ---------------- Frontend helpers (once per page) ---------------- */

// Keep only the first adsbygoogle.js if any other plugin/theme prints it
add_action('template_redirect', function () {
    ob_start(function ($html) {
        $pattern = '#<script[^>]+src="https://pagead2\.googlesyndication\.com/pagead/js/adsbygoogle\.js\?client=[^"]+"[^>]*></script>#i';
        $seen = 0;
        return preg_replace_callback($pattern, function ($m) use (&$seen) {
            $seen++; return $seen === 1 ? $m[0] : '';
        }, $html);
    });
    add_action('shutdown', function () {
        while (ob_get_level() > 0) @ob_end_flush();
    }, PHP_INT_MAX);
}, 0);

// Preconnects
add_action('wp_head', function () {
  if (is_admin() || is_feed()) return;
  echo "<link rel='preconnect' href='https://pagead2.googlesyndication.com' crossorigin>\n";
  echo "<link rel='preconnect' href='https://googleads.g.doubleclick.net' crossorigin>\n";
  echo "<link rel='preconnect' href='https://securepubads.g.doubleclick.net' crossorigin>\n";
}, 3);

// CLS-safe CSS (NOTE: no content-visibility here on ad-slot!)





add_filter('wp_kses_allowed_html', function ($tags, $context) {
    if ($context !== 'post') return $tags;

    // Allow AdSense <ins> with style + any data-* (client, slot, layout, etc.)
    $tags['ins'] = [
        'class'      => true,
        'style'      => true,
        'data-*'     => true,
        'role'       => true,
        'aria-label' => true,
        'aria-hidden'=> true,
    ];

    // Keep wrappers flexible too
    foreach (['div','span'] as $el) {
        if (!isset($tags[$el])) $tags[$el] = [];
        $tags[$el]['class']      = true;
        $tags[$el]['style']      = true;
        $tags[$el]['data-*']     = true;
        $tags[$el]['role']       = true;
        $tags[$el]['aria-label'] = true;
        $tags[$el]['aria-hidden']= true;
    }
    return $tags;
}, 10, 2);









add_action('wp_enqueue_scripts', function () {
  if (is_admin()) return;

  $css = <<<CSS
/* Base wrapper + slot */
.ad-slot{
  display:block;
  width:100%;
  contain:size layout paint;
  --ad-h:250px;              /* base first */
  min-height:var(--ad-h);
}
.ad-slot .adsbygoogle{
  display:block;
  width:100%;
  min-height:var(--ad-h);
  margin:0 !important; outline:0 !important; border:0 !important; padding:0 !important;
}

/* Locked heights by breakpoint (example) */
@media (min-width: 640px){  .ad-slot { --ad-h: 280px; } }
@media (min-width:1024px){  .ad-slot { --ad-h: 90px;  } }

/* Centering and optional width caps */
.ad-slot--constrained { margin:24px auto; }
@media (min-width:768px){   .ad-slot--constrained{ max-width:728px; } }
@media (min-width:1024px){  .ad-slot--constrained{ max-width:800px; } }
@media (min-width:1280px){  .ad-slot--constrained{ max-width:970px; } }
CSS;

  wp_register_style('wdads-inline', false, [], null);
  wp_enqueue_style('wdads-inline');
  wp_add_inline_style('wdads-inline', $css);
}, 20);







// Lazy loader: render only when visible AND width > 0 (prevents "availableWidth=0")
add_action('wp_footer', function () { ?>









<script>
(function(w,d){
'use strict';

/*** ---- LCP/idle gate --------------------------------------------- ***/

function afterLCP(cb){
  var done = false;
  function go(){ if (!done){ done = true; w.__ads_ok = true; try{ cb(); }catch(_){} } }

  // 1) Unconditional safety fallback
  setTimeout(go, 2000);

  // 2) If the page is already loaded by the time this script runs (footer case)
  if (d.readyState === 'complete') {
    setTimeout(go, 100);
  }

  // 3) Try to catch LCP (works in most modern browsers)
  if ('PerformanceObserver' in w) {
    try {
      var po = new PerformanceObserver(function(list){
        var entries = list && list.getEntries ? list.getEntries() : [];
        if (entries.length) { try{ po.disconnect(); }catch(_){ } go(); }
      });
      po.observe({ type: 'largest-contentful-paint', buffered: true });
    } catch(_) {}
  }

  // 4) load + first interaction also open the gate
  w.addEventListener('load', function(){ setTimeout(go, 800); }, { once:true });
  w.addEventListener('pointerdown', go, { once:true, passive:true });
}

/*** ----------------------------------------------------------------- ***/

var CLIENT = 'ca-pub-9101284402640935';
var SRC = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client='+CLIENT;
var DEBUG = false;
function log(){ if (DEBUG && w.console) try{ console.log.apply(console, arguments);}catch(e){} }

function ensureLib(cb){
  if (!w.__ads_ok) { return setTimeout(function(){ ensureLib(cb); }, 120); }
  if (w.adsbygoogle && typeof w.adsbygoogle.push==='function') return cb();
  var s = d.querySelector('script[src^="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"]');
  if (!s){
    s = d.createElement('script');
    s.async = true; s.crossOrigin = 'anonymous'; s.src = SRC;
    (d.head||d.documentElement).appendChild(s);
    log('[wdads] injected adsbygoogle.js');
  }
  var tries=0;(function wait(){
    if (w.adsbygoogle && typeof w.adsbygoogle.push==='function') { log('[wdads] lib ready'); return cb(); }
    if (++tries>150){ log('[wdads] lib failed to load'); return; }
    setTimeout(wait,60);
  })();
}

/** Minimal width helper (only used in a fallback) */
function widthReady(ins){
  var w1 = ins ? (ins.offsetWidth || (ins.getBoundingClientRect && ins.getBoundingClientRect().width) || 0) : 0;
  if (w1) return w1;
  var slot = ins && ins.closest ? ins.closest('.ad-slot') : null;
  return slot ? (slot.offsetWidth || (slot.getBoundingClientRect && slot.getBoundingClientRect().width) || 0) : 0;
}

/** Wait until the slot actually has width (prefers ResizeObserver) */
function whenSlotHasWidth(ins, threshold, cb){
  var need = (threshold || 40);
  var target = (ins && ins.closest) ? (ins.closest('.ad-slot') || ins) : ins;
  if (!target) return;

  if ('ResizeObserver' in window){
    var ro = new ResizeObserver(function(entries){
      for (var i=0;i<entries.length;i++){
        var rect = entries[i].contentRect || {};
        var w = rect.width || 0;
        if (w >= need){
          try { ro.disconnect(); } catch(_){}
          cb(w);
          return;
        }
      }
    });
    ro.observe(target);
  } else {
    (function poll(){
      var w = widthReady(ins) || 0;
      if (w >= need) cb(w);
      else requestAnimationFrame(poll);
    })();
  }
}

/** Keep only ONE syncHeight(), use RO contentRect (no offsetHeight reads) */
function syncHeight(ins){
  var slot = ins.closest && ins.closest('.ad-slot');
  if (!slot) return;

  if ('ResizeObserver' in window){
    var ro = new ResizeObserver(function(entries){
      for (var i = 0; i < entries.length; i++){
        var h = Math.ceil((entries[i].contentRect && entries[i].contentRect.height) || 0);
        if (h > 0) slot.style.setProperty('--ad-h', h + 'px');
      }
    });
    ro.observe(ins);
  } else {
    var tries = 0;
    (function tick(){
      var h = ins.clientHeight | 0;
      if (h > 0) slot.style.setProperty('--ad-h', h + 'px');
      if (++tries < 20) setTimeout(tick, 150);
    })();
  }
}

function raf2(fn){ requestAnimationFrame(function(){ requestAnimationFrame(fn); }); }




function attemptRender(ins){
  if (!ins || ins.dataset.loaded) return;
  if (!w.__ads_ok){
    setTimeout(function(){ attemptRender(ins); }, 180);
    return;
  }

  if (!/display\s*:\s*block/i.test(ins.getAttribute('style')||'')) {
    ins.style.display = 'block';
  }

  whenSlotHasWidth(ins, 40, function(){
    ensureLib(function(){
      requestAnimationFrame(function(){ requestAnimationFrame(function(){
        try{
          (w.adsbygoogle = w.adsbygoogle || []).push({});
          ins.dataset.loaded = '1';

          requestAnimationFrame(function(){ requestAnimationFrame(function(){
            syncHeight(ins);
          }); });

        } catch(e){
          if (String(e && e.message || e).indexOf('No slot size') > -1){
            setTimeout(function(){ attemptRender(ins); }, 120);
          }
        }
      });});
    });
  });
}






function observe(ins){
  var slot = ins.closest ? (ins.closest('.ad-slot') || ins) : ins;

  if ('IntersectionObserver' in w){
    var io = new IntersectionObserver(function(es){
      for (var i = 0; i < es.length; i++){
        if (es[i].isIntersecting){
          io.disconnect();
          // attemptRender will itself wait for width via whenSlotHasWidth
          attemptRender(ins);
          break;
        }
      }
    }, { rootMargin:'200px 0px', threshold: 0.01 });

    io.observe(slot);
  } else {
    attemptRender(ins);
  }
}

function ready(fn){ d.readyState!=='loading' ? fn() : d.addEventListener('DOMContentLoaded', fn, {once:true}); }

afterLCP(function(){
  ready(function(){
    var nodes = d.querySelectorAll('.ad-slot ins.adsbygoogle');
    log('[wdads] slots afterLCP:', nodes.length);
    nodes.forEach(observe);

    // Throttled resize: no width reads here; let attemptRender handle waiting
    var rid = 0;
    w.addEventListener('resize', function(){
      if (rid) return;
      rid = requestAnimationFrame(function(){
        rid = 0;
        d.querySelectorAll('.ad-slot ins.adsbygoogle:not([data-loaded="1"])')
         .forEach(function(ins){ attemptRender(ins); });
      });
    });
  });
});
})(window,document);
</script>









<?php }, 20);


// Remove theme's js-lazy-video behavior on core/video blocks to reduce CLS
add_filter( 'render_block', function( $content, $block ) {
    if ( ($block['blockName'] ?? '') !== 'core/video' ) {
        return $content;
    }

    // Remove js-lazy-video class
    $content = preg_replace(
        '/\sclass="([^"]*?)\bjs-lazy-video\b([^"]*?)"/i',
        ' class="$1$2"',
        $content
    );

    // Remove any data-lazy-* attributes
    $content = preg_replace(
        '/\sdata-lazy-[a-zA-Z0-9_-]+="[^"]*"/i',
        '',
        $content
    );

    return $content;
}, 20, 2 );

