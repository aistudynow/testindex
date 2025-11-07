<?php
/**
 * Plugin Name: MU – Forced Reflow Tracker (dev-only)
 * Description: Logs stack traces to Console when layout reads happen right after DOM writes (forced reflow suspects). Only active for admins on frontend.
 */
if (!defined('ABSPATH')) exit;





if (!function_exists('mu_reflow_tracker_is_enabled')) {
    function mu_reflow_tracker_is_enabled() {
        $enabled = defined('WD4_PERF_DEBUG_ENABLED') && WD4_PERF_DEBUG_ENABLED;

        return (bool) apply_filters('mu_reflow_tracker_enabled', $enabled);
    }
}

if (!mu_reflow_tracker_is_enabled()) {
    return;
}






add_action('wp_head', function () {
  // frontend only, admins only
  if (is_admin()) return;
  if (function_exists('wp_doing_ajax') && wp_doing_ajax()) return;
  if (!is_user_logged_in() || !current_user_can('manage_options')) return;
  ?>
  <script id="mu-reflow-tracker">
  (function(){
    var LOG_LIMIT = 30, logCount = 0, dirty = false, lastWrite = 0;

    function markDirty(){ dirty = true; lastWrite = performance.now(); }
    function logReflow(kind){
      if (!dirty) return;
      var since = Math.round(performance.now() - lastWrite);
      if (since > 1500) { dirty = false; return; }      // too late to be related
      if (logCount++ >= LOG_LIMIT) return;

      var stack = (new Error()).stack || '';
      stack = stack.split('\n').slice(2).join('\n');    // trim our own frames
      var current = (document.currentScript && document.currentScript.src) ? document.currentScript.src : '';

      console.groupCollapsed('%cForced reflow suspect%c %s after %dms', 'color:#e11d48;font-weight:bold', 'color:inherit', kind, since);
      if (current) console.log('currentScript:', current);
      console.log('stack:\n' + stack);
      console.groupEnd();
    }

    // ---- Writes (mark dirty) ----
    [['Element','setAttribute'],['Element','removeAttribute'],['Element','insertAdjacentHTML'],
     ['Element','insertAdjacentElement'],['Element','append'],['Element','prepend'],
     ['Element','before'],['Element','after'],['Element','replaceWith'],
     ['Node','appendChild'],['Node','insertBefore'],['Node','removeChild'],['Node','replaceChild']
    ].forEach(function(pair){
      var host = window[pair[0]] && window[pair[0]].prototype; if (!host) return;
      var name = pair[1], orig = host[name]; if (!orig || orig.__muWrapped) return;
      Object.defineProperty(host, name, { value: function(){ markDirty(); return orig.apply(this, arguments); }});
      orig.__muWrapped = true;
    });

    if ('DOMTokenList' in window){
      ['add','remove','toggle','replace'].forEach(function(name){
        var orig = DOMTokenList.prototype[name]; if (!orig || orig.__muWrapped) return;
        Object.defineProperty(DOMTokenList.prototype, name, { value: function(){ markDirty(); return orig.apply(this, arguments); }});
        orig.__muWrapped = true;
      });
    }

    if (window.CSSStyleDeclaration){
      var sp = CSSStyleDeclaration.prototype.setProperty;
      if (sp && !sp.__muWrapped){
        Object.defineProperty(CSSStyleDeclaration.prototype, 'setProperty', { value: function(){ markDirty(); return sp.apply(this, arguments); }});
        sp.__muWrapped = true;
      }
    }

    // ---- Reads (log if dirty) ----
    function wrapRead(obj, name, label){
      var orig = obj && obj[name]; if (!orig || orig.__muWrapped) return;
      Object.defineProperty(obj, name, { value: function(){ logReflow(label||name); return orig.apply(this, arguments); }});
      orig.__muWrapped = true;
    }
    function wrapGetter(proto, prop, label){
      var d = Object.getOwnPropertyDescriptor(proto, prop);
      if (!d || !d.get || d.get.__muWrapped) return;
      Object.defineProperty(proto, prop, { get: function(){ logReflow(label||prop); return d.get.call(this); }});
      d.get.__muWrapped = true;
    }

    wrapRead(window,            'getComputedStyle');
    wrapRead(Element.prototype, 'getBoundingClientRect');
    if (window.SVGElement) wrapRead(SVGElement.prototype, 'getBBox', 'getBBox');

    ['offsetWidth','offsetHeight','offsetLeft','offsetTop',
     'clientWidth','clientHeight','clientTop','clientLeft',
     'scrollWidth','scrollHeight'
    ].forEach(function(p){ wrapGetter(HTMLElement.prototype, p, p); });

    setInterval(function(){ if (dirty && performance.now() - lastWrite > 1500) dirty = false; }, 800);
  })();
  </script>
  <?php
}, 0);
