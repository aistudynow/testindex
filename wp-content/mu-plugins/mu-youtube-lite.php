<?php
/**
 * Plugin Name: MU – YouTube Lite (click-to-load, no autoplay, mobile-optimized poster)
 * Description: Rewrites YouTube iframes to a lightweight placeholder. Clicking inserts a PAUSED player (no autoplay).
 */
if (!defined('ABSPATH')) exit;

/* ---------- CSS (loaded late and attached to theme handle if possible) ---------- */
add_action('wp_enqueue_scripts', function () {
  if (is_admin()) return;

  $css = <<<CSS
  
/* ===== WP wrapper neutralization + baseline gap fixes ===== */
figure.wp-block-embed,
.wp-block-embed__wrapper{
  line-height: 0 !important;
  font-size: 0 !important;
}

.wp-embed-responsive .wp-has-aspect-ratio .wp-block-embed__wrapper::before,
.wp-embed-aspect-16-9 .wp-block-embed__wrapper::before{
  content: none !important;
  display: none !important;
}

.wp-embed-aspect-16-9 .wp-block-embed__wrapper,
.wp-has-aspect-ratio .wp-block-embed__wrapper,
.wp-block-embed__wrapper{
  position: static !important;
  padding: 0 !important;
  width: 100% !important;
  overflow: visible !important; /* .yt-lite handles clipping */
}

/* Optional: control spacing on the outer figure instead of .yt-lite */
figure.wp-block-embed.wp-block-embed-youtube{
  margin: 16px 0; /* adjust to taste */
}

/* ===== The real 16:9 box ===== */
.yt-lite{
  display: block !important;
  position: relative !important;
  overflow: hidden !important;
  background: #000 !important;
  border-radius: 8px !important;
  isolation: isolate !important;
  aspect-ratio: 16 / 9 !important;
  margin: 0 !important;         /* keep zero so it never looks like a border */
  outline: 0 !important;
  line-height: 0 !important;    /* prevents inline/baseline gaps */
  cursor: pointer;
}

/* ===== Fill the box (poster + iframe) ===== */
.yt-lite__poster,
.yt-lite iframe{
  position: absolute !important;
  inset: 0 !important;
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  object-position: 50% 50% !important;
  border: 0 !important;
  border-radius: inherit !important;
  transform: translateZ(0);      /* GPU rounding guard */
  display: block;
  background: #000;
}

/* Sliver-proof overdraw: hide 0–1px seams on some DPRs */
@supports (inset: 0){
  .yt-lite__poster{ inset: -0.5px !important; }
}

/* ===== Play button (forced visible) ===== */
.yt-lite__button{
  position: absolute; left: 50%; top: 50%;
  transform: translate(-50%, -50%);
  width: 68px; height: 48px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(0,0,0,.6);
  border-radius: 14px; border: 0;
  z-index: 3; pointer-events: none; opacity: 1; visibility: visible;
  box-shadow: 0 0 0 2px rgba(255,255,255,.25) inset;
}
.yt-lite__button svg{
  width: 22px; height: 22px; display: block; fill: #fff;
  filter: drop-shadow(0 1px 2px rgba(0,0,0,.35));
}

/* Keyboard focus ring */
.yt-lite:focus-visible{
  box-shadow: 0 0 0 3px rgba(255,255,255,.6);
}

/* ===== Neutralize any old facade CSS from theme ===== */
.has-yt-facade .yt-facade,
.has-yt-facade .yt-facade__thumb,
.has-yt-facade .yt-facade__icon{ all: unset; }

/* If your theme has a generic rule like .s-ct img{height:auto}, this keeps our poster correct */
.s-ct .yt-lite__poster{ height: 100% !important; }


CSS;

  // If the theme enqueues a 'main' stylesheet, piggyback to ensure our CSS prints after it.
  if (wp_style_is('main', 'enqueued') || wp_style_is('main', 'registered')) {
    wp_add_inline_style('main', $css);
  } else {
    wp_register_style('yt-lite-inline', false, [], null);
    wp_enqueue_style('yt-lite-inline');
    wp_add_inline_style('yt-lite-inline', $css);
  }
}, 99); // late -> beat theme styles

/**
 * Helper: poster srcset + sizes.
 */


/* Replace YouTube iframes inside the_content */
add_filter('the_content', function (string $c): string {
  if (is_admin() || !is_singular() || is_feed()) return $c;
  
  

  $c = preg_replace_callback(
    '#<iframe[^>]+src=["\']https?:\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/embed\/([A-Za-z0-9_-]{11})[^"\']*["\'][^>]*>\s*<\/iframe>#i',
    
    
    
    function ($m) {
  $id   = $m[1];

  // 1) poster candidates (320/480/640 only)
  $base = esc_attr("https://www.youtube.com/embed/$id?rel=0&modestbranding=1&playsinline=1");
  $src_320 = "https://i.ytimg.com/vi/$id/mqdefault.jpg";  // 320w
  $src_480 = "https://i.ytimg.com/vi/$id/hqdefault.jpg";  // 480w
  $src_640 = "https://i.ytimg.com/vi/$id/sddefault.jpg";  // 640w

  $srcset_attr = esc_attr("$src_320 320w, $src_480 480w, $src_640 640w");
  $sizes_attr  = esc_attr('(min-width:700px) 640px, 92vw'); // cap at 640 on wider screens

  // Use the 640 file as `src`
  $fallback = esc_url($src_640);

  // 2) reserve layout without waiting for CSS
  $wrap_style = 'aspect-ratio:16/9;display:block;position:relative;isolation:isolate;';

  // 3) inline button styles
  $btn_inline = 'position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);'.
                'width:68px;height:48px;display:flex;align-items:center;justify-content:center;'.
                'background:rgba(0,0,0,.6);border-radius:14px;border:0;pointer-events:none;z-index:3;'.
                'box-shadow:0 0 0 2px rgba(255,255,255,.25) inset;';

  $svg = '<svg viewBox="0 0 36 36" width="22" height="22" style="display:block;fill:#fff" '.
         'aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg"><polygon points="14,10 26,18 14,26"/></svg>';

  return '<div class="yt-lite" role="button" tabindex="0" aria-label="YouTube video" '.
          'data-src="'.$base.'" style="'.$wrap_style.'">'.
            '<img class="yt-lite__poster" src="'.$fallback.'" srcset="'.$srcset_attr.'" sizes="'.$sizes_attr.'" '.
                 'width="1280" height="720" alt="" loading="lazy" decoding="async" fetchpriority="low" '.
                 'referrerpolicy="strict-origin-when-cross-origin">'.
            '<span class="yt-lite__button" style="'.$btn_inline.'" aria-hidden="true">'.$svg.'</span>'.
         '</div>'.
         '<noscript><iframe width="560" height="315" referrerpolicy="strict-origin-when-cross-origin" '.
         'src="https://www.youtube.com/embed/'.$id.'?rel=0&modestbranding=1&playsinline=1" title="YouTube video" loading="lazy" '.
         'allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></noscript>';
},
    $c
  );

  return $c;
}, 16);

/* JS: insert a PAUSED iframe on interaction (no autoplay) */
add_action('wp_footer', function () { ?>
<script>
(function(w,d){
  'use strict';
  var nodes = d.querySelectorAll('.yt-lite');
  if(!nodes.length) return;

  function urlNoAutoplay(base){
    try{
      var u = new URL(base, w.location.href);
      u.searchParams.set('autoplay', '0'); // keep paused
      u.searchParams.set('playsinline', '1');
      u.searchParams.delete('mute');
      try { u.searchParams.set('origin', w.location.origin); } catch(e){}
      return u.toString();
    }catch(e){ return base; }
  }
  function toIframe(el){
    if (el.__ytDone) return;
    el.__ytDone = true;
    var i = d.createElement('iframe');
    i.src = urlNoAutoplay(el.getAttribute('data-src'));
    i.title = el.getAttribute('aria-label') || 'YouTube video';
    i.allow = 'accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    i.setAttribute('allowfullscreen','');
    i.referrerPolicy = 'strict-origin-when-cross-origin';
    i.loading = 'eager';
    i.style.position='absolute'; i.style.inset='0'; i.style.width='100%'; i.style.height='100%'; i.style.border='0';
    el.innerHTML = ''; el.appendChild(i);
  }
  function activate(target){
    var el = target && target.closest && target.closest('.yt-lite');
    if (!el) return false; toIframe(el); return true;
  }
  d.addEventListener('click', function(e){ if (activate(e.target)) e.preventDefault(); }, {passive:false});
  d.addEventListener('keydown', function(e){ if ((e.key==='Enter'||e.key===' ') && activate(e.target)) e.preventDefault(); }, {passive:false});
})(window, document);
</script>
<?php }, 20);

/* Optional resource hints */
add_action('wp_head', function () {
  if (is_admin() || is_feed()) return;
  echo "<link rel='preconnect' href='https://www.youtube.com' crossorigin>\n";
  echo "<link rel='preconnect' href='https://i.ytimg.com' crossorigin>\n";
  echo "<link rel='preconnect' href='https://googlevideo.com' crossorigin>\n";
}, 3);
