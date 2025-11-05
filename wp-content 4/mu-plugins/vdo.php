<?php
/**
 * Plugin Name: MU – vdo.ai (no-iframe, idle + near-viewport)
 * Description: Inserts one vdo.ai slot after the first paragraph and loads the vdo.ai script after LCP/idle and when the slot is near the viewport.
 */

if (!defined('ABSPATH')) exit;

function mu_vdoai_is_amp(): bool {
    return function_exists('is_amp_endpoint') && is_amp_endpoint();
}

/* ===================
   Config (filterable)
   =================== */
function mu_vdoai_noifr_cfg(): array {
    $cfg = [
        // Your vdo.ai site key script (protocol-relative is fine)
        'src'           => '//a.vdo.ai/core/v-aistudynow/vdo.ai.js',

        // Reserve some space so the ad doesn’t shift layout
        'aspect_ratio'  => '16 / 9',
        'min_height'    => 280,          // px

        // Load when the slot is near the viewport
        'root_margin'   => '600px 0px',

        // Where enabled
        'enabled'       => !mu_vdoai_is_amp() && is_singular(['post','page']),

        // Accessibility label
        'aria_label'    => 'Advertisement',
    ];
    return apply_filters('mu_vdoai_noifr_cfg', $cfg);
}

/* ----------------------------------
   1) Inject placeholder into content
   ---------------------------------- */
add_filter('the_content', function ($content){
    if (is_admin() || is_feed() || is_search() || is_archive() || is_front_page() || is_home()) return $content;
    if (mu_vdoai_is_amp()) return $content;

    $cfg = mu_vdoai_noifr_cfg();
    if (empty($cfg['enabled'])) return $content;

    // If a slot is already present, don’t add another
    if (strpos($content, 'id="v-aistudynow"') !== false || strpos($content, 'id="vdo-slot"') !== false) {
        return $content;
    }

    $placeholder =
      '<div class="vdo-wrap">' .
        '<div id="v-aistudynow" role="complementary" aria-label="'.esc_attr($cfg['aria_label']).'"></div>' .
      '</div>';

    $p = stripos($content, '</p>');
    return ($p !== false)
        ? substr($content, 0, $p+4) . $placeholder . substr($content, $p+4)
        : $content . $placeholder;
}, 20);

/* ------------------------------
   2) Lightweight CSS (no iframe)
   ------------------------------ */
add_action('wp_head', function () {
    if (is_admin() || mu_vdoai_is_amp()) return;
    $cfg = mu_vdoai_noifr_cfg(); if (empty($cfg['enabled'])) return;

    $ratio = preg_replace('~[^0-9/.\s]~', '', (string)($cfg['aspect_ratio'] ?? '16 / 9')) ?: '16 / 9';
    $minh  = max(0, (int)($cfg['min_height'] ?? 280));
    ?>
    <style id="mu-vdoai-noifr-css">
      .vdo-wrap{ margin:16px 0; }
      #v-aistudynow{
        position: relative;
        width: 100%;
        aspect-ratio: <?php echo $ratio; ?>;
        min-height: <?php echo $minh; ?>px;
        contain: layout paint style;
        content-visibility: auto;
        background: transparent;
        overflow: hidden;
      }
      #v-aistudynow:empty::before{
        content:"";
        position:absolute; inset:0;
        background: repeating-linear-gradient(45deg, #f6f7f8 0 12px, #eee 12px 24px);
        opacity:.25;
        display:block;
      }
    </style>
    <?php
}, 2);

/* ----------------------------------------
   3) Preconnects (helps when we finally load)
   ---------------------------------------- */
add_action('wp_head', function () {
    if (is_admin() || mu_vdoai_is_amp()) return;
    $cfg = mu_vdoai_noifr_cfg(); if (empty($cfg['enabled'])) return;

    echo "<link rel='preconnect' href='https://a.vdo.ai'>\n";
    echo "<link rel='preconnect' href='https://imasdk.googleapis.com' crossorigin>\n";
    echo "<link rel='preconnect' href='https://securepubads.g.doubleclick.net' crossorigin>\n";
    echo "<link rel='preconnect' href='https://googleads.g.doubleclick.net' crossorigin>\n";
}, 3);

/* ----------------------------------------------------
   4) Footer JS: after LCP/idle + near-viewport, inject vdo.ai script
   ---------------------------------------------------- */
add_action('wp_footer', function () {
    if (is_admin() || mu_vdoai_is_amp()) return;
    $cfg = mu_vdoai_noifr_cfg(); if (empty($cfg['enabled'])) return;

    $src = esc_js($cfg['src']);
    $rm  = esc_js($cfg['root_margin']);
    ?>
    <script id="mu-vdoai-noifr-js">
    (function(w,d){
      'use strict';
      var slot = d.getElementById('v-aistudynow');
      if (!slot) return;

      var DEBUG = !!w.vdo_debug;
      function log(){ if (DEBUG && w.console) try{ console.log.apply(console, ['[vdo.ai/no-iframe]'].concat([].slice.call(arguments))); }catch(e){} }

      var injected = false;
      function alreadyLoaded(){
        var scripts = d.getElementsByTagName('script');
        for (var i=0;i<scripts.length;i++){
          var s = scripts[i].getAttribute('src') || '';
          if (s.indexOf('a.vdo.ai/core/v-aistudynow/vdo.ai.js') !== -1) return true;
        }
        return false;
      }

      function inject(){
        if (injected || alreadyLoaded()) { log('script already present'); return; }
        injected = true;
        log('inject vdo.ai script');

        var s = d.createElement('script');
        s.async = true;
        s.defer = true;
        s.src = '<?php echo $src; ?>';
        d.head.appendChild(s);
      }

      function afterLCP(cb){
        var done=false;
        function go(){ if(!done){ done=true; try{cb();}catch(e){} } }

        if ('PerformanceObserver' in w) {
          try{
            var po = new PerformanceObserver(function(list){
              if (list.getEntries && list.getEntries().length){ po.disconnect(); go(); }
            });
            po.observe({type:'largest-contentful-paint', buffered:true});
            w.addEventListener('load', function(){ setTimeout(go, 1200); }, {once:true});
            w.addEventListener('pointerdown', go, {once:true, passive:true});
          }catch(e){ setTimeout(go, 1500); }
        } else {
          w.addEventListener('load', function(){ setTimeout(go, 1500); }, {once:true});
        }
      }






     afterLCP(function(){
  function startObserver(){
    if (!('IntersectionObserver' in w)) {
      setTimeout(inject, 2000);
      return;
    }
    var io = new IntersectionObserver(function(es){
      for (var i=0;i<es.length;i++){
        if (es[i].isIntersecting){ io.disconnect(); inject(); break; }
      }
    }, {rootMargin:'<?php echo $rm; ?>'});
    io.observe(slot);
  }

  var started = false;
  function onScrollOnce(){
    if (started) return;
    started = true;
    w.removeEventListener('scroll', onScrollOnce);
    w.removeEventListener('wheel', onScrollOnce);
    startObserver();
  }

  w.addEventListener('scroll', onScrollOnce, {once:true, passive:true});
  w.addEventListener('wheel', onScrollOnce, {once:true, passive:true});

  setTimeout(function(){
    if (!started) { onScrollOnce(); }
  }, 8000);
});



      // Safety fallback
      
    })(window, document);
    </script>
    <?php
}, 20);
