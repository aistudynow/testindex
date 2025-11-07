<?php
if ( ! defined( 'ABSPATH' ) ) {
    return;
}







/**
 * Ensure remote Google Fonts fall back to system fonts immediately while the
 * custom font files are still downloading. Without this parameter the browser
 * blocks text rendering until the remote CSS arrives, which hurts the Largest
 * Contentful Paint score on slower connections. Appending `display=swap` keeps
 * the typography identical once the fonts finish loading but lets the initial
 * paint happen using safe fallbacks.
 */
function wd4_append_font_display_param( string $src, string $handle ): string {
    unset( $handle );

    if ( false === strpos( $src, 'fonts.googleapis.com' ) ) {
        return $src;
    }

    if ( false !== strpos( $src, 'display=' ) ) {
        return $src;
    }

    $augmented = add_query_arg( 'display', 'swap', $src );

    return is_string( $augmented ) ? $augmented : $src;
}
add_filter( 'style_loader_src', 'wd4_append_font_display_param', 10, 2 );















// Enable or disable inline CSS loading (true = ON, false = OFF)
define( 'WD4_INLINE_CSS_ENABLED', true );


if ( ! function_exists( 'wd4_should_load_remote_fonts' ) ) {
    /**
     * Gatekeeper that lets us short-circuit any Google Fonts requests. The
     * default returns false so headings fall back to fast system fonts, but a
     * filter can re-enable the remote styles if we ever need them again.
     */
    function wd4_should_load_remote_fonts(): bool {
        return (bool) apply_filters( 'wd4_should_load_remote_fonts', false );
    }
}



// Master switch for deferred styles (off by default to prevent late HTML
// mutations that trigger style recalculation bursts).
if ( ! defined( 'WD4_DEFER_STYLES_ENABLED' ) ) {
    define( 'WD4_DEFER_STYLES_ENABLED', false );
}

// Flag to opt into the verbose performance debugging helpers.
if ( ! defined( 'WD4_PERF_DEBUG_ENABLED' ) ) {
    define( 'WD4_PERF_DEBUG_ENABLED', false );
}





/**
 * Determine whether we should adjust front-end only performance helpers.
 */
function wd4_is_front_context(): bool {
    if ( is_admin() ) {
        return false;
    }
    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return false;
    }
    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return false;
    }

    return true;
}








/**
 * Trim the Foxiz share option map so only a compact list of networks render.
 */
function wd4_filter_share_option_matrix( array $options ): array {
    $all_networks = array(
        'facebook',
        'twitter',
        'flipboard',
        'pinterest',
        'whatsapp',
        'linkedin',
        'tumblr',
        'reddit',
        'vk',
        'telegram',
        'threads',
        'bsky',
        'email',
        'copy',
        'print',
        'native',
    );

    $allowed_networks = array( 'twitter', 'linkedin', 'copy', 'whatsapp' );
    $share_groups      = array( 'top', 'left', 'bottom', 'sticky' );

    $options['share_top']    = 0;
    $options['share_left']   = 0;
    $options['share_sticky'] = 0;
    $options['share_bottom'] = 1;

    foreach ( $share_groups as $group ) {
        foreach ( $all_networks as $network ) {
            $key = 'share_' . $group . '_' . $network;

            if ( isset( $options[ $key ] ) ) {
                $options[ $key ] = in_array( $network, $allowed_networks, true ) ? 1 : 0;
            }
        }
    }

    return $options;
}

/**
 * Override the in-memory option store before Foxiz renders the share templates.
 */
function wd4_optimize_theme_share_options(): void {
    if ( ! wd4_is_front_context() ) {
        return;
    }

    if ( ! defined( 'FOXIZ_TOS_ID' ) ) {
        return;
    }

    $options = get_option( FOXIZ_TOS_ID, array() );
    if ( ! is_array( $options ) ) {
        return;
    }

    $trimmed = wd4_filter_share_option_matrix( $options );

    add_filter(
        'pre_option_' . FOXIZ_TOS_ID,
        static function () use ( $trimmed ) {
            return $trimmed;
        }
    );

    $GLOBALS[ FOXIZ_TOS_ID ] = $trimmed;
}
add_action( 'init', 'wd4_optimize_theme_share_options', 8 );



/**
 * Stop the parent theme from requesting remote Google Fonts so the hero title
 * can render with local system fonts immediately.
 */
function wd4_disable_remote_google_fonts(): void {
    if ( ! wd4_is_front_context() ) {
        return;
    }

    if ( wd4_should_load_remote_fonts() ) {
        return;
    }

    wp_dequeue_style( 'foxiz-font' );
    wp_deregister_style( 'foxiz-font' );
}
add_action( 'wp_enqueue_scripts', 'wd4_disable_remote_google_fonts', 20 );







/**
 * Upgrade the first above-the-fold thumbnail to an eager, high-priority fetch so
 * the browser discovers the Largest Contentful Paint image immediately. The
 * Elementor daily feed cards output their thumbnails with `loading="lazy"` and
 * `fetchpriority="low"`, which delays the request on mobile where the list sits
 * directly under the hero. Switching only the first lazy image to eager keeps
 * overall concurrency low while ensuring Core Web Vitals sees the asset early.
 */
function wd4_promote_first_feed_thumbnail( array $attr, $attachment, $size ): array {
    unset( $attachment, $size );

    if ( ! wd4_is_front_context() ) {
        return $attr;
    }

    if ( ! ( is_front_page() || is_home() ) ) {
        return $attr;
    }

    if ( empty( $attr['loading'] ) || 'lazy' !== $attr['loading'] ) {
        return $attr;
    }

    static $promoted = false;

    if ( $promoted ) {
        return $attr;
    }

    $attr['loading']       = 'eager';
    $attr['fetchpriority'] = 'high';
    $attr['decoding']      = 'async';

    $promoted = true;

    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'wd4_promote_first_feed_thumbnail', 20, 3 );












if ( ! function_exists( 'foxiz_render_share_list' ) ) {
    /**
     * Render a compact share list that keeps the DOM footprint tiny while
     * preserving the theme class hooks for existing JavaScript behaviours.
     */
    function foxiz_render_share_list( array $settings ) {
        if ( empty( $settings ) ) {
            return;
        }

        $post_id = ! empty( $settings['post_id'] ) ? (int) $settings['post_id'] : get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        $permalink = get_permalink( $post_id );
        if ( ! $permalink ) {
            return;
        }

        $title       = get_the_title( $post_id );
        $encoded_url = rawurlencode( $permalink );
        $encoded_txt = rawurlencode( html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) ) );

        $show_labels = ! empty( $settings['social_name'] );

        $networks = array(
            'twitter'  => array(
                'class' => 'icon-twitter share-trigger',
                'icon'  => 'rbi-twitter',
                'label' => esc_html__( 'X', 'foxiz-child' ),
                'url'   => sprintf( 'https://twitter.com/intent/tweet?text=%s&url=%s', $encoded_txt, $encoded_url ),
                'attrs' => array( 'data-bound-share' => '1' ),
            ),
            'linkedin' => array(
                'class' => 'icon-linkedin share-trigger',
                'icon'  => 'rbi-linkedin',
                'label' => esc_html__( 'LinkedIn', 'foxiz-child' ),
                'url'   => sprintf( 'https://linkedin.com/shareArticle?mini=true&url=%s&title=%s', $encoded_url, $encoded_txt ),
                'attrs' => array( 'data-bound-share' => '1' ),
            ),
            'whatsapp' => array(
                'class' => 'icon-whatsapp share-trigger',
                'icon'  => 'rbi-whatsapp',
                'label' => esc_html__( 'WhatsApp', 'foxiz-child' ),
                'url'   => sprintf( 'https://api.whatsapp.com/send?text=%s%%20%s', $encoded_txt, $encoded_url ),
                'attrs' => array(
                    'data-bound-share' => '1',
                ),
            ),
            'copy'     => array(
                'class' => 'icon-copy',
                'icon'  => 'rbi-link-o',
                'label' => esc_html__( 'Copy Link', 'foxiz-child' ),
                'url'   => '#',
                'attrs' => array(
                    'rel'        => 'nofollow',
                    'role'       => 'button',
                    'data-copy'  => esc_url( $permalink ),
                    'data-link'  => esc_url( $permalink ),
                    'data-copied'=> esc_attr__( 'Copied!', 'foxiz-child' ),
                    'data-bound-copy' => '1',
                ),
            ),
        );

        $output = array();

        foreach ( $networks as $key => $config ) {
            if ( empty( $settings[ $key ] ) ) {
                continue;
            }

            $classes = array( 'share-action', $config['class'], 'share-' . $key );

            if ( 'copy' === $key ) {
                $classes[] = 'copy-trigger';
            }

            $attr = array(
                'class'      => implode( ' ', array_map( 'sanitize_html_class', $classes ) ),
                'href'       => 'copy' === $key ? '#' : esc_url( $config['url'] ),
                'target'     => 'copy' === $key ? '' : '_blank',
                'rel'        => 'copy' === $key ? 'nofollow' : 'nofollow noopener',
                'role'       => 'listitem',
                'aria-label' => 'copy' === $key
                    ? esc_html__( 'Copy article link', 'foxiz-child' )
                    : sprintf( esc_html__( 'Share on %s', 'foxiz-child' ), $config['label'] ),
                'data-title' => $config['label'],
            );

            if ( ! empty( $config['attrs'] ) && is_array( $config['attrs'] ) ) {
                foreach ( $config['attrs'] as $name => $value ) {
                    $attr[ $name ] = $value;
                }
            }

            if ( 'copy' === $key ) {
                $attr['data-copy']  = esc_url( $permalink );
                $attr['data-link']  = esc_url( $permalink );
                $attr['data-title'] = $title ? esc_attr( $title ) : '';
            }

            $attr_string = '';
            foreach ( $attr as $name => $value ) {
                if ( '' === $value ) {
                    continue;
                }

                $attr_string .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
            }

            $icon  = sprintf( '<i class="rbi %s" aria-hidden="true"></i>', esc_attr( $config['icon'] ) );
            $label = $show_labels ? sprintf( '<span>%s</span>', esc_html( $config['label'] ) ) : '';

            $output[] = sprintf( '<a%s>%s%s</a>', $attr_string, $icon, $label );
        }

        echo implode( '', $output );
    }
}

/**
 * Slim down the default comment form to remove rarely used fields.
 */
function wd4_slim_comment_form_fields( array $fields ): array {
    unset( $fields['url'], $fields['cookies'] );
    return $fields;
}
add_filter( 'comment_form_default_fields', 'wd4_slim_comment_form_fields' );

add_filter( 'comment_form_defaults', function ( array $defaults ): array {
    $defaults['comment_notes_before'] = '';
    return $defaults;
} );

add_action( 'wp_enqueue_scripts', function () {
    if ( ! wd4_is_front_context() || ! is_singular() ) {
        return;
    }

    if ( ! comments_open() && 0 === get_comments_number() ) {
        return;
    }

    wp_enqueue_script(
        'wd-comment-toggle',
        get_stylesheet_directory_uri() . '/js/comment-toggle.js',
        [],
        '20241128',
        true
    );
} );










add_filter( 'show_admin_bar', function( $show ) {
    if ( ! is_admin() ) {
        return false; // no admin bar on the front-end
    }
    return $show;
});


if ( WD4_PERF_DEBUG_ENABLED ) {
add_action( 'wp_footer', function () {
    // Only for admins to avoid spamming real users
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <script>
    if ('PerformanceObserver' in window) {
      const po = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (!entry.hadRecentInput && entry.value > 0) {
            console.log('[CLS] value:', entry.value.toFixed(4), 'time:', entry.startTime.toFixed(1));
            console.log('sources:',
              entry.sources.map(s => {
                const el = s.node;
                if (!el) return null;
                const tag = el.tagName ? el.tagName.toLowerCase() : '';
                const id  = el.id ? ('#'+el.id) : '';
                const cls = el.className && el.className.toString
                  ? '.'+el.className.toString().trim().split(/\s+/).join('.')
                  : '';
                return tag + id + cls;
              })
            );
          }
        }
      });
      po.observe({ type: 'layout-shift', buffered: true });
    }
    </script>
    <?php
}, 99 );



// functions.php in child theme

// functions.php in child theme

add_action( 'wp_head', function () {
    // Only run for admins
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <script>
    (function () {
      if (!('performance' in window)) return;

      // Change this to your liking:
      // - /$^/  => ignore nothing (capture all)
      // - /gtag|adsbygoogle/ => ignore GA/AdSense etc
      const IGNORE_STACK = /gtag|adsbygoogle|show_ads_impl|www-embed-player|facebook|twitter|ytimg/i;

      const SLOW_EVENT_THRESHOLD = 10; // ms, for "slow" scroll/resize/etc

      const debugData = {
        layoutReads: [],
        longTasks: [],
        layoutShifts: [],
        scrollResizeHandlers: [],
        scrollResizeCalls: [],
        eventSlowCalls: [],
        mutations: [],
        cssWarnings: [],
        cssAnimEvents: []
      };

      function shouldIgnore(stack) {
        return IGNORE_STACK.test(stack);
      }

      /************* 1) Layout reads (JS forced reflow) *************/
      function recordLayoutRead(label, node) {
        const stack = (new Error()).stack || '';
        if (shouldIgnore(stack)) return;
        debugData.layoutReads.push({
          t: performance.now(),
          label,
          node: node && (node.tagName + (node.id ? ('#' + node.id) : '')),
          stack
        });
      }

      function wrapLayoutGetter(proto, prop) {
        const d = Object.getOwnPropertyDescriptor(proto, prop);
        if (!d || !d.get) return;
        Object.defineProperty(proto, prop, {
          get: function() {
            recordLayoutRead(prop, this);
            return d.get.call(this);
          }
        });
      }

      ['offsetWidth','offsetHeight','clientWidth','clientHeight','scrollTop','scrollLeft']
        .forEach(p => wrapLayoutGetter(Element.prototype, p));

      const _gbcr = Element.prototype.getBoundingClientRect;
      Element.prototype.getBoundingClientRect = function(...a){
        recordLayoutRead('getBoundingClientRect', this);
        return _gbcr.apply(this, a);
      };

      const _gcs = window.getComputedStyle;
      window.getComputedStyle = function(el, ...a){
        recordLayoutRead('getComputedStyle', el);
        return _gcs.call(window, el, ...a);
      };

      /************* 2) Scroll / resize / wheel / touchmove measurement *************/
      const origAdd = EventTarget.prototype.addEventListener;
      EventTarget.prototype.addEventListener = function(type, listener, options){
        if (typeof listener !== 'function') {
          return origAdd.call(this, type, listener, options);
        }

        const stack = (new Error()).stack || '';

        if (['scroll','resize','wheel','touchmove'].includes(type) && !shouldIgnore(stack)) {
          const targetName = this && this.constructor && this.constructor.name;

          // Remember who registered the handler
          debugData.scrollResizeHandlers.push({
            type,
            target: targetName,
            at: (stack.split('\n')[2] || '').trim()
          });

          // Wrap listener to measure each call
          const wrapped = function(...args) {
            const start = performance.now();
            const result = listener.apply(this, args);
            const dur = performance.now() - start;

            debugData.scrollResizeCalls.push({
              type,
              target: targetName,
              dur: Math.round(dur)
            });

            if (dur > SLOW_EVENT_THRESHOLD) {
              debugData.eventSlowCalls.push({
                type,
                target: targetName,
                dur: Math.round(dur),
                at: (stack.split('\n')[2] || '').trim()
              });
            }
            return result;
          };

          return origAdd.call(this, type, wrapped, options);
        }

        return origAdd.call(this, type, listener, options);
      };

      /************* 3) Long tasks (JS > 50 ms) *************/
      if ('PerformanceObserver' in window &&
          PerformanceObserver.supportedEntryTypes &&
          PerformanceObserver.supportedEntryTypes.indexOf('longtask') !== -1) {
        const longTaskObserver = new PerformanceObserver(list => {
          list.getEntries().forEach(e => {
            debugData.longTasks.push({
              t: Math.round(e.startTime),
              dur: Math.round(e.duration),
              name: e.name || 'longtask'
            });
          });
        });
        longTaskObserver.observe({entryTypes:['longtask']});
      }

      /************* 4) Layout shifts (CLS-ish) *************/
      if ('PerformanceObserver' in window &&
          PerformanceObserver.supportedEntryTypes &&
          PerformanceObserver.supportedEntryTypes.indexOf('layout-shift') !== -1) {
        const lsObserver = new PerformanceObserver(list => {
          list.getEntries().forEach(e => {
            if (e.hadRecentInput) return; // ignore user input shifts
            debugData.layoutShifts.push({
              t: Math.round(e.startTime),
              value: e.value
            });
          });
        });
        lsObserver.observe({entryTypes:['layout-shift']});
      }

      /************* 5) CSS animation / transition events *************/
      document.addEventListener('animationstart', function(e){
        debugData.cssAnimEvents.push({
          kind: 'animation',
          name: e.animationName,
          target: e.target.tagName + (e.target.id ? ('#'+e.target.id) : '')
        });
      }, true);

      document.addEventListener('transitionrun', function(e){
        debugData.cssAnimEvents.push({
          kind: 'transition',
          property: e.propertyName,
          target: e.target.tagName + (e.target.id ? ('#'+e.target.id) : '')
        });
      }, true);

      /************* 6) Static CSS scan for “bad” animated properties *************/
      function scanCSSForWarnings() {
  // focus on actual heavy properties
  const badProps = [
    'visibility',
    'top','left','right','bottom',
    'width','height','margin','padding',
    'box-shadow'
  ];

  const warnings = [];
  for (const sheet of document.styleSheets) {
    let rules;
    try { rules = sheet.cssRules; }
    catch(e){ continue; } // cross-origin
    if (!rules) continue;

    const node = sheet.ownerNode;
    // this will show 'main-inline', 'single-inline', etc.
    const src = sheet.href || (node && node.id) || 'inline';

    // ignore admin stuff: dashicons + admin bar
    if (/dashicons\.min\.css|admin-bar\.min\.css/.test(src)) {
      continue;
    }

    for (const rule of rules) {
      if (!rule.style) continue;
      const s        = rule.style;
      const selector = rule.selectorText || '';
      const trProps  = (s.transitionProperty || s.transition || '').toString();
      const animProp = (s.animationName || s.animation || '').toString();

      badProps.forEach(prop => {
        if (trProps.includes(prop)) {
          warnings.push({ type: 'transition', prop, selector, stylesheet: src });
        }
        if (animProp.includes(prop)) {
          warnings.push({ type: 'animation', prop, selector, stylesheet: src });
        }
      });
    }
  }
  debugData.cssWarnings = warnings;
}


      document.addEventListener('DOMContentLoaded', scanCSSForWarnings);

      /************* 7) MutationObserver (DOM changes) *************/
      if ('MutationObserver' in window) {
        const mo = new MutationObserver(list => {
          for (const m of list) {
            if (debugData.mutations.length > 200) break; // keep it sane
            debugData.mutations.push({
              type: m.type,
              target: m.target && (m.target.tagName + (m.target.id ? ('#'+m.target.id) : '')),
              added: m.addedNodes ? m.addedNodes.length : 0,
              removed: m.removedNodes ? m.removedNodes.length : 0,
              attr: m.attributeName || ''
            });
          }
        });
        mo.observe(document.documentElement, {
          childList: true,
          attributes: true,
          subtree: true
        });
      }

      /************* 8) Dump summary *************/
      window.__perfDebug = debugData;

      window.addEventListener('load', () => {
        try {
          console.groupCollapsed('[Perf debug] Layout reads (JS forced reflow)');
          const lr = debugData.layoutReads.map(r => ({
            t: Math.round(r.t),
            read: r.label,
            node: r.node,
            at: (r.stack.split('\n')[2] || '').trim()
          }));
          console.table(lr.slice(0, 50));
          console.groupEnd();

          console.groupCollapsed('[Perf debug] Long tasks (>50ms)');
          console.table(debugData.longTasks);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] Scroll/resize handlers registered');
          console.table(debugData.scrollResizeHandlers);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] Slow scroll/resize/wheel/touchmove calls (> ' + SLOW_EVENT_THRESHOLD + 'ms)');
          console.table(debugData.eventSlowCalls);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] Layout shifts (CLS candidates)');
          console.table(debugData.layoutShifts);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] CSS animation/transition events');
          console.table(debugData.cssAnimEvents);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] CSS warnings (animated heavy properties)');
          console.table(debugData.cssWarnings);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] DOM mutations (first 50)');
          console.table(debugData.mutations.slice(0, 50));
          console.groupEnd();

          console.info('[Perf debug] totals', {
            layoutReads: debugData.layoutReads.length,
            longTasks: debugData.longTasks.length,
            scrollResizeHandlers: debugData.scrollResizeHandlers.length,
            layoutShifts: debugData.layoutShifts.length,
            cssWarnings: debugData.cssWarnings.length,
            cssAnimEvents: debugData.cssAnimEvents.length,
            mutations: debugData.mutations.length,
            slowEventCalls: debugData.eventSlowCalls.length
          });
        } catch(e) {
          console.warn('Perf debug output failed', e);
        }
      });

    })();
    </script>
    <?php
}, 0);

}

























add_action('wp_enqueue_scripts', function () {
    if (is_singular('post')) {
        $css = <<<CSS
/* SINGLE FULL-WIDTH – no second column, no negative gutters */
.single-standard-8.without-sidebar .grid-container {
  display: block;
  margin-right: 0 !important;
  margin-left:  0 !important;
}
.single-standard-8.without-sidebar .grid-container > .s-ct,
.single-standard-8.without-sidebar .grid-container > .sidebar-wrap {
  width: 100% !important;
  max-width: 100%;
  padding-right: 0 !important;
  padding-left:  0 !important;
}
@media (min-width: 992px) {
  .single-standard-8.without-sidebar .grid-container > .s-ct {
    flex: 0 0 100% !important;
    width: 100% !important;
  }
  .single-standard-8.without-sidebar .grid-container > .sidebar-wrap {
    display: none !important;
  }
}
/* CLS guards */
.s-ct .single-header { contain: layout paint; }
.s-ct .smeta-extra,
.s-ct .single-right-meta,
.s-ct .t-shared-sec {
  min-height: 36px;
  align-items: center;
}
:root { font-synthesis-weight: auto; }
.s-ct .wp-block-embed:not(.wp-block-embed-youtube) .wp-block-embed__wrapper {
  display: block;
  aspect-ratio: 16/9;
}
CSS;
        wp_add_inline_style('single', $css); // prints after single.css
    }
}, 999);














/**
 * -------------------------------------------------------------------------
 * Images: attributes + sizes
 * -------------------------------------------------------------------------
 */
add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment, $size) {
    // Only touch non-LCP images on single posts
    if ( is_singular('post') && $attachment->ID !== get_post_thumbnail_id() ) {
        if ( !empty($attr['loading']) && $attr['loading'] === 'lazy' ) {
            $attr['fetchpriority'] = 'low';
        }
        $attr['decoding'] = $attr['decoding'] ?? 'async';
    }
    return $attr;
}, 20, 3);

add_filter('wp_calculate_image_sizes', function ($sizes, $size, $image_src, $image_meta, $attachment_id) {
    if ( is_admin() ) { return $sizes; }

    $requested = is_array($size) ? (isset($size[0]) ? $size[0].'x'.($size[1] ?? $size[0]) : '') : (string) $size;

    if ( is_singular('post') && ( $requested === 'full' || $requested === 'large' ) ) {
        return '(min-width:1440px) 1200px, (min-width:1024px) 960px, (min-width:700px) 720px, 92vw';
    }

    $map = [
        'foxiz_crop_g1' => '(max-width:480px) 100vw, 330px',
        'foxiz_crop_g2' => '(max-width:560px) 95vw, 420px',
        'foxiz_crop_g3' => '(max-width:700px) 95vw, 615px',
        'foxiz_crop_o1' => '(max-width:920px) 92vw, 860px',
        'medium'        => '(max-width:800px) 92vw, 300px',
        'medium_large'  => '(max-width:1024px) 92vw, 768px',
        'large'         => '(max-width:1200px) 92vw, 1024px',
    ];

    if ( isset($map[$requested]) ) {
        return $map[$requested];
    }

    if ( strpos($sizes, 'auto,') === 0 ) {
        return trim(substr($sizes, 5));
    }

    return $sizes;
}, 10, 5);









/**
 * -------------------------------------------------------------------------
 * Stylesheet deferral helpers
 * -------------------------------------------------------------------------
 */
function wd4_should_defer_styles(): bool {
    if ( defined( 'WD4_DEFER_STYLES_ENABLED' ) && ! WD4_DEFER_STYLES_ENABLED ) {
        return false;
    }
    $is_ajax = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();

    if ( is_admin() || $is_ajax || is_customize_preview() ) {
        return false;
    }
    if ( function_exists( 'wd4_is_front_login_page' ) && wd4_is_front_login_page() ) {
        return false;
    }
    if ( is_search() ) {
        return false;
    }

    // PSI highlights long recalculation bursts on single posts when the
    // stylesheet promotion script batches large chunks of CSS at once.
    // Let those templates load their styles synchronously so the browser can
    // calculate layout incrementally during parse instead of after idle.
    if ( is_singular( 'post' ) ) {
        return false;
    }

    if ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) {
        return false;
    }
    return (bool) apply_filters( 'wd4_enable_deferred_styles', true );
}

function wd4_is_frontend_request(): bool {
    if ( is_admin() ) return false;
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return false;
    if ( defined( 'DOING_CRON' ) && DOING_CRON ) return false;
    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) return false;
    return true;
}

function wd4_get_inline_styles_map(): array {
    $map = array(
        'main'         => 'css/header/main.css',
        'slider'       => 'css/header/slider.css',
        'social'       => 'css/header/social.css',
        'divider'      => 'css/header/divider.css',
        'grid'         => 'css/header/grid.css',
        'footer'       => 'css/header/footer.css',
        'catheader'    => 'css/header/catheader.css',
        'single'       => 'css/header/single/single.css',
        'sidebar'      => 'css/header/single/sidebar.css',
        'email'        => 'css/header/single/email.css',
        'download'     => 'css/header/single/download.css',
        'sharesingle'  => 'css/header/single/sharesingle.css',
        'related'      => 'css/header/single/related.css',
        'author'       => 'css/header/single/author.css',
        'comment'      => 'css/header/single/comment.css',
        'searchheader' => 'css/header/searchheader.css',
        'fixgrid'      => 'css/header/fixgrid.css',
        'login'        => 'css/login.css',
        'my-account'   => 'css/profile.css',
    );
    return (array) apply_filters( 'wd4_inline_styles_map', $map );
}

function wd4_load_inline_stylesheet( string $relative_path ): string {
    static $cache = array();

    $key = ltrim( $relative_path, '/\\' );
    if ( array_key_exists( $key, $cache ) ) return $cache[ $key ];

    $themes_dir = trailingslashit( dirname( get_stylesheet_directory() ) );
    $base       = wp_normalize_path( $themes_dir );
    $path       = wp_normalize_path( $themes_dir . $key );

    if ( 0 !== strpos( $path, $base ) || ! file_exists( $path ) || ! is_readable( $path ) ) {
        $cache[ $key ] = '';
        return $cache[ $key ];
    }

    $contents = file_get_contents( $path );
    if ( false === $contents ) {
        $cache[ $key ] = '';
        return $cache[ $key ];
    }

    $contents = trim( $contents );
    $cache[ $key ] = $contents;
    return $cache[ $key ];
}

function wd4_inline_style_loader_tag( string $html, string $handle, string $href, string $media ): string {
    // If feature disabled, return normal <link> tag
    if ( ! defined('WD4_INLINE_CSS_ENABLED') || ! WD4_INLINE_CSS_ENABLED ) {
        return $html;
    }

    $map = wd4_get_inline_styles_map();
    if ( ! isset( $map[ $handle ] ) ) {
        return $html;
    }

    $css = wd4_load_inline_stylesheet( $map[ $handle ] );
    if ( '' === $css ) {
        return $html;
    }

    $media_attr = ( $media && 'all' !== $media ) ? sprintf( ' media="%s"', esc_attr( $media ) ) : '';
    return sprintf( "<style id='%s-inline'%s>%s</style>\n", esc_attr( $handle ), $media_attr, $css );
}

add_filter( 'style_loader_tag', 'wd4_inline_style_loader_tag', 5, 4 );

function wd4_get_deferred_style_handles(): array {
    $handles = array(
        'single','sidebar','email','download','sharesingle',
        'related','author','comment','grid','footer',
    );
    $inline = array_keys( wd4_get_inline_styles_map() );
    if ( $inline ) {
        $handles = array_values( array_diff( $handles, $inline ) );
    }
    return (array) apply_filters( 'wd4_deferred_style_handles', $handles );
}

function wd4_output_critical_css(): void {
    if ( ! wd4_should_defer_styles() ) return;

    $inline_map = wd4_get_inline_styles_map();
    // Only skip if 'main' is mapped AND actually loads non-empty CSS
    if ( isset( $inline_map['main'] ) && '' !== wd4_load_inline_stylesheet( $inline_map['main'] ) ) {
        return;
    }

    $critical_css = <<<'CSS'
:root{--g-color:#ff184e;--nav-bg:#fff;--nav-bg-from:#fff;--nav-bg-to:#fff;--nav-color:#282828;--nav-height:60px;--mbnav-height:42px;--menu-fsize:17px;--menu-fweight:600;--menu-fspace:-.02em;--submenu-fsize:13px;--submenu-fweight:500;--submenu-fspace:-.02em;--shadow-7:#00000012}
html,body{margin:0;padding:0}
body{font-family:"Encode Sans Condensed",sans-serif;line-height:1.6;color:#282828;background:#fff}
ul{margin:0;padding:0;list-style:none}
a{color:inherit;text-decoration:none}
.edge-padding{padding-right:20px;padding-left:20px}
.rb-container{width:100%;max-width:1280px;margin:0 auto}
.header-wrap{position:relative}
.navbar-outer{position:relative;width:100%;z-index:110}
.navbar-wrap{position:relative;z-index:999;background:linear-gradient(to right,var(--nav-bg-from) 0%,var(--nav-bg-to) 100%)}
.navbar-inner{display:flex;align-items:stretch;justify-content:space-between;min-height:var(--nav-height);max-width:100%}
.navbar-left,.navbar-right,.navbar-center{display:flex;align-items:center}
.navbar-left{flex:1 1 auto}
.logo-wrap{display:flex;align-items:center;margin-right:20px;max-height:100%}
.logo-wrap img{display:block;max-height:var(--nav-height);width:auto;height:auto}
.main-menu{display:flex;align-items:center;flex-flow:row wrap;gap:5px;font-size:var(--menu-fsize);font-weight:var(--menu-fweight);letter-spacing:var(--menu-fspace)}
.main-menu>li{position:relative;display:flex;align-items:center}
.main-menu>li>a{display:flex;align-items:center;height:var(--nav-height);padding:0 12px;color:var(--nav-color);white-space:nowrap}
.header-mobile{display:none}
.header-mobile-wrap{position:relative;z-index:99;display:flex;flex-direction:column;background:var(--nav-bg)}
.mbnav{display:flex;align-items:center;min-height:var(--mbnav-height)}
.mobile-toggle-wrap{display:flex;align-items:stretch}
.mobile-menu-trigger{display:flex;align-items:center;padding-right:10px;cursor:pointer}
.header-mobile .navbar-right{display:flex;justify-content:flex-end}
.header-mobile .navbar-right>*{display:flex;align-items:center;height:100%;color:inherit}
.header-mobile .mobile-search-icon{margin-left:auto}
.privacy-bar{position:fixed;inset:auto auto 24px 24px;max-width:min(26rem,calc(100vw - 48px));display:none;opacity:0;pointer-events:none;z-index:2147483647;transform:translateY(10px);transition:opacity .2s ease,transform .2s ease;color:#fff}
.privacy-bar.activated{display:block;opacity:1;pointer-events:auto;transform:translateY(0)}
.privacy-inner{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:7px;background:rgba(15,18,23,.92);box-shadow:0 10px 24px rgba(15,18,23,.18)}
.privacy-dismiss-btn{display:inline-flex;align-items:center;justify-content:center;min-height:2.25rem;padding:0 1.4rem;border-radius:999px;border:0;background:var(--g-color);color:#fff;font-weight:600}
@media (max-width:1024px){.navbar-wrap{display:none}.header-mobile{display:flex}}
@media (max-width:640px){.privacy-bar{inset:auto 16px 16px 16px;max-width:calc(100vw - 32px)}.privacy-inner{flex-direction:column;align-items:stretch;text-align:center;gap:.65rem}.privacy-dismiss-btn{width:100%}}
CSS;

    $critical_css = preg_replace( '/\s+/', ' ', trim( $critical_css ) );
    if ( $critical_css ) {
        printf( "<style id='wd4-critical-css'>%s</style>\n", $critical_css );
    }
}
add_action( 'wp_head', 'wd4_output_critical_css', 20 );

function wd4_filter_style_loader_tag( string $html, string $handle, string $href, string $media ): string {
    if ( ! wd4_should_defer_styles() ) return $html;

    $deferred = wd4_get_deferred_style_handles();
    if ( ! in_array( $handle, $deferred, true ) ) return $html;

    $media_attribute = ( $media && 'all' !== $media ) ? $media : '';

    global $wd4_deferred_styles_registry;
    if ( ! is_array( $wd4_deferred_styles_registry ) ) {
        $wd4_deferred_styles_registry = array();
    }

    $wd4_deferred_styles_registry[ $handle ] = array(
        'href'  => $href,
        'media' => $media_attribute,
    );

    $data_media = $media_attribute ? sprintf( ' data-media="%s"', esc_attr( $media_attribute ) ) : '';

    return sprintf(
        '<link rel="preload" as="style" data-defer-style id="%1$s" href="%2$s"%3$s />',
        esc_attr( $handle ),
        esc_url( $href ),
        $data_media
    );
}
add_filter( 'style_loader_tag', 'wd4_filter_style_loader_tag', 20, 4 );

function wd4_output_deferred_styles_noscript(): void {
    if ( ! wd4_should_defer_styles() ) return;

    global $wd4_deferred_styles_registry;
    if ( empty( $wd4_deferred_styles_registry ) ) return;

    echo "<noscript>\n";
    foreach ( $wd4_deferred_styles_registry as $handle => $data ) {
        $href  = esc_url( $data['href'] );
        $media = ! empty( $data['media'] ) ? sprintf( ' media="%s"', esc_attr( $data['media'] ) ) : ' media="all"';
        printf( "    <link rel='stylesheet' id='%1$s' href='%2$s'%3$s />\n", esc_attr( $handle ), $href, $media );
    }
    echo "</noscript>\n";
}
add_action( 'wp_head', 'wd4_output_deferred_styles_noscript', 110 );

function wd4_enqueue_defer_css_script(): void {
    if ( ! wd4_should_defer_styles() ) return;

    $script_handle = 'wd-defer-css';
    $script_src    = get_stylesheet_directory_uri() . '/js/defer-css.js';

    wp_enqueue_script( $script_handle, $script_src, array(), '1.1.0', true );

    if ( function_exists( 'wp_script_add_data' ) ) {
        wp_script_add_data( $script_handle, 'strategy', 'defer' );
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_enqueue_defer_css_script', 200 );








/**
 * -------------------------------------------------------------------------
 * Head cleanup and payload reduction
 * -------------------------------------------------------------------------
 */
function wd4_disable_emojis(): void {
    if ( ! wd4_is_frontend_request() ) return;

    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_print_footer_scripts', 'print_emoji_detection_script' );
    remove_action( 'embed_head', 'print_emoji_detection_script' );
    remove_action( 'embed_print_styles', 'print_emoji_styles' );

    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

    add_filter( 'emoji_svg_url', '__return_false' );
    add_filter( 'option_use_smilies', 'wd4_filter_disable_emojis_option' );
}
add_action( 'init', 'wd4_disable_emojis', 5 );

function wd4_filter_disable_emojis_option(): string { return '0'; }

function wd4_disable_emojis_tinymce( $plugins ) {
    if ( ! wd4_is_frontend_request() ) return $plugins;
    if ( ! is_array( $plugins ) ) return $plugins;
    return array_diff( $plugins, array( 'wpemoji' ) );
}
add_filter( 'tiny_mce_plugins', 'wd4_disable_emojis_tinymce' );

function wd4_remove_emoji_dns_prefetch( array $urls, string $relation_type ): array {
    if ( 'dns-prefetch' !== $relation_type ) return $urls;
    if ( ! wd4_is_frontend_request() ) return $urls;

    $filtered = array();
    foreach ( $urls as $url ) {
        $href = wd4_get_hint_href( $url );
        if ( '' === $href || false === strpos( $href, 's.w.org' ) ) {
            $filtered[] = $url;
        }
    }
    return $filtered;
}
add_filter( 'wp_resource_hints', 'wd4_remove_emoji_dns_prefetch', 9, 2 );

function wd4_cleanup_head_links(): void {
    if ( ! wd4_is_frontend_request() ) return;

    $targets = apply_filters(
        'wd4_head_cleanup_actions',
        array(
            array( 'wp_head', 'feed_links_extra', 3 ),
            array( 'wp_head', 'feed_links', 2 ),
            array( 'wp_head', 'rsd_link', 10 ),
            array( 'wp_head', 'wlwmanifest_link', 10 ),
            array( 'wp_head', 'wp_generator', 10 ),
            array( 'wp_head', 'wp_shortlink_wp_head', 10 ),
            array( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 ),
        )
    );

    foreach ( $targets as $target ) {
        if ( ! is_array( $target ) || count( $target ) < 2 ) continue;

        $hook     = $target[0];
        $callback = $target[1];
        $priority = $target[2] ?? 10;
        $args     = $target[3] ?? 0;

        remove_action( $hook, $callback, $priority, $args );
    }
}
add_action( 'init', 'wd4_cleanup_head_links', 8 );

function wd4_disable_wp_embed(): void {
    if ( ! wd4_is_frontend_request() ) return;
    if ( ! apply_filters( 'wd4_disable_wp_embed', true ) ) return;

    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
    remove_action( 'template_redirect', 'rest_output_link_header', 11 );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );

    add_action( 'wp_enqueue_scripts', 'wd4_deregister_wp_embed', 100 );
}
add_action( 'init', 'wd4_disable_wp_embed', 9 );
function wd4_deregister_wp_embed(): void { wp_deregister_script( 'wp-embed' ); }


/**
 * -------------------------------------------------------------------------
 * Resource hints helpers (preconnect only)
 * -------------------------------------------------------------------------
 */
function wd4_get_hint_href( $hint ): string {
    if ( is_array( $hint ) && isset( $hint['href'] ) ) return trim( (string) $hint['href'] );
    if ( is_string( $hint ) ) return trim( $hint );
    return '';
}

function wd4_extract_origin( string $url ): string {
    $parts = wp_parse_url( $url );
    if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) return '';
    $origin = strtolower( $parts['scheme'] . '://' . $parts['host'] );
    if ( isset( $parts['port'] ) ) $origin .= ':' . $parts['port'];
    return $origin;
}

function wd4_normalize_resource_hint_entry( $entry ): array {
    if ( is_array( $entry ) ) {
        $href = wd4_get_hint_href( $entry );
        if ( '' === $href ) return array();
        $normalized = array( 'href' => $href );
        if ( isset( $entry['as'] ) ) {
            $as = trim( (string) $entry['as'] );
            if ( '' !== $as ) $normalized['as'] = $as;
        }
        if ( isset( $entry['type'] ) ) {
            $type = trim( (string) $entry['type'] );
            if ( '' !== $type ) $normalized['type'] = $type;
        }
        if ( isset( $entry['crossorigin'] ) ) {
            $crossorigin = strtolower( trim( (string) $entry['crossorigin'] ) );
            if ( in_array( $crossorigin, array( 'anonymous', 'use-credentials' ), true ) ) {
                $normalized['crossorigin'] = $crossorigin;
            }
        }
        return $normalized;
    }
    $href = wd4_get_hint_href( $entry );
    if ( '' === $href ) return array();
    return array( 'href' => $href );
}

function wd4_filter_out_resource_hints( array $urls, array $candidates ): array {
    if ( empty( $urls ) || empty( $candidates ) ) return $urls;

    $remove = array();
    foreach ( $candidates as $candidate ) {
        $entry = wd4_normalize_resource_hint_entry( $candidate );
        if ( empty( $entry['href'] ) ) continue;
        $remove[ $entry['href'] ] = true;
    }

    if ( empty( $remove ) ) return $urls;

    $filtered = array();
    foreach ( $urls as $url ) {
        $href = wd4_get_hint_href( $url );
        if ( '' !== $href && isset( $remove[ $href ] ) ) continue;
        $filtered[] = $url;
    }

    return $filtered;
}

function wd4_collect_preconnect_origins(): array {
    static $cache = null;
    if ( null !== $cache ) return $cache;

    if ( is_admin() || is_feed() ) { $cache = array(); return $cache; }
    if ( function_exists('wp_doing_ajax') && wp_doing_ajax() ) { $cache = array(); return $cache; }
    if ( function_exists('is_amp_endpoint') && is_amp_endpoint() ) { $cache = array(); return $cache; }

    $origins = array();
    $home_origin = wd4_extract_origin( home_url( '/' ) );
    if ( $home_origin ) $origins[] = $home_origin;

    // Fonts
    $origins[] = 'https://fonts.googleapis.com';
    $origins[] = array('href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous');
    
    
    
  // Fonts
    if ( wd4_should_load_remote_fonts() ) {
        $origins[] = 'https://fonts.googleapis.com';
        $origins[] = array('href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous');
    }
  
    

    // Defer CSS helper (e.g., Cloudflare)
    if ( wd4_should_defer_styles() ) $origins[] = 'https://cloudflare.com';

    $origins = apply_filters('wd4_preconnect_origins', $origins);

    $normalized = array();
    foreach ( (array) $origins as $origin ) {
        $entry = wd4_normalize_resource_hint_entry($origin);
        if ( empty($entry['href']) ) continue;
        $normalized[$entry['href']] = $entry;
    }
    $cache = array_values($normalized);
    return $cache;
}

function wd4_add_resource_hints( array $urls, string $relation_type ): array {
    if ( is_admin() || is_feed() ) return $urls;
    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) return $urls;
    if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) return $urls;

    if ( 'preconnect' === $relation_type ) {
        $urls = wd4_filter_out_resource_hints( $urls, wd4_collect_preconnect_origins() );
    }
    return $urls;
}
add_filter( 'wp_resource_hints', 'wd4_add_resource_hints', 10, 2 );

function wd4_output_preconnect_links(): void {
    $origins = wd4_collect_preconnect_origins();
    if ( empty( $origins ) ) return;

    foreach ( $origins as $origin ) {
        $href        = $origin['href'];
        $crossorigin = isset( $origin['crossorigin'] ) ? sprintf( " crossorigin='%s'", esc_attr( $origin['crossorigin'] ) ) : '';
        printf( "<link rel='preconnect' href='%s'%s>\n", esc_url( $href ), $crossorigin );
    }
}
add_action( 'wp_head', 'wd4_output_preconnect_links', 3 );


/**
 * -------------------------------------------------------------------------
 * Theme stylesheet helpers
 * -------------------------------------------------------------------------
 */
function wd4_kill_foxiz_css(): void {
    foreach ( array( 'foxiz-main-css', 'foxiz-main', 'foxiz-style', 'foxiz-global' ) as $handle ) {
        wp_dequeue_style( $handle );
        wp_deregister_style( $handle );
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_kill_foxiz_css', 1000 );

defined( 'WD_LOGIN_PAGE_ID' ) || define( 'WD_LOGIN_PAGE_ID', 0 );

function wd4_is_front_login_page(): bool {
    if ( WD_LOGIN_PAGE_ID ) return is_page( WD_LOGIN_PAGE_ID );
    return is_page( 'login-3' );
}

function wd4_enqueue_styles(): void {
    $is_login   = wd4_is_front_login_page();
    $is_account = function_exists( 'is_account_page' ) && is_account_page();

    if ( is_front_page() || is_home() ) {
        wp_enqueue_style( 'main',    'https://aistudynow.com/wp-content/themes/css/header/main.css',   array(), '998580591100565677766876655777999980.0' );
        wp_enqueue_style( 'slider',  'https://aistudynow.com/wp-content/themes/css/header/slider.css', array(), '968999090099987980.0' );
        wp_enqueue_style( 'divider', 'https://aistudynow.com/wp-content/themes/css/header/divider.css', array(), '66997876655777999980.0' );
        wp_enqueue_style( 'grid',    'https://aistudynow.com/wp-content/themes/css/header/grid.css',   array(), '0655777999980.0' );
        wp_enqueue_style( 'fixgrid', 'https://aistudynow.com/wp-content/themes/css/header/fixgrid.css',   array(), '999809785667876655777999980.0' );
        wp_enqueue_style( 'footer',  'https://aistudynow.com/wp-content/themes/css/header/footer.css', array(), '8667876655777999980.0' );
    }

    if ( is_category() ) {
        wp_enqueue_style( 'main',      'https://aistudynow.com/wp-content/themes/css/header/main.css',      array(), '8591117667876655777999980.0' );
        wp_enqueue_style( 'catheader', 'https://aistudynow.com/wp-content/themes/css/header/catheader.css', array(), '91661667876655777999980.0' );
        wp_enqueue_style( 'grid',      'https://aistudynow.com/wp-content/themes/css/header/grid.css',      array(), '6678766557779799980.0' );
        wp_enqueue_style( 'footer',    'https://aistudynow.com/wp-content/themes/css/header/footer.css',    array(), '8667876655777999980.0' );
    }

    if ( is_singular( 'post' ) ) {
        wp_enqueue_style( 'main',        'https://aistudynow.com/wp-content/themes/css/header/main.css',               array(), '0833990966976444448885909999880.0' );
        wp_enqueue_style( 'single',      'https://aistudynow.com/wp-content/themes/css/header/single/single.css',      array(), '49957907999099980.00' );

        wp_enqueue_style( 'email',       'https://aistudynow.com/wp-content/themes/css/header/single/email.css',       array(), '667876655777999980.0' );
        wp_enqueue_style( 'download',    'https://aistudynow.com/wp-content/themes/css/header/single/download.css',    array(), '497667876655777999980.0' );
        wp_enqueue_style( 'sharesingle', 'https://aistudynow.com/wp-content/themes/css/header/single/sharesingle.css', array(), '5667876655777999980.0' );

        wp_enqueue_style( 'author',      'https://aistudynow.com/wp-content/themes/css/header/single/author.css',      array(), '8667876655777999980.0' );
        wp_enqueue_style( 'comment',     'https://aistudynow.com/wp-content/themes/css/header/single/comment.css',     array(), '99667876655777999980.0' );
        wp_enqueue_style( 'footer',      'https://aistudynow.com/wp-content/themes/css/header/footer.css',             array(), '8667876655777999980.0' );
    }

    if ( $is_login ) {
        wp_enqueue_style( 'login', 'https://aistudynow.com/wp-content/themes/css/login.css', array(), '974777977.2.0' );
    }

    if ( is_search() ) {
        wp_enqueue_style( 'main',         'https://aistudynow.com/wp-content/themes/css/header/main.css',        array(), '8667876655777999980.0' );
        wp_enqueue_style( 'searchheader', 'https://aistudynow.com/wp-content/themes/css/header/searchheader.css', array(), '667876655777999980.0' );
        wp_enqueue_style( 'grid',         'https://aistudynow.com/wp-content/themes/css/header/grid.css',         array(), '667876655777999980.0' );
        wp_enqueue_style( 'fixgrid',      'https://aistudynow.com/wp-content/themes/css/header/fixgrid.css',      array(), '667876655777999980.0' );
        wp_enqueue_style( 'footer',       'https://aistudynow.com/wp-content/themes/css/header/footer.css',       array(), '667876655777999980.0' );
    }

    if ( $is_account ) {
        wp_enqueue_style( 'my-account', 'https://aistudynow.com/wp-content/themes/css/profile.css', array(), '1.80.0' );
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_enqueue_styles', 20 );

function wd4_prune_styles(): void {
    if ( is_admin() || wd4_is_front_login_page() || is_search() ) return;
    if ( ! ( is_front_page() || is_home() || is_category() || is_singular( 'post' ) ) ) return;

    global $wp_styles;
    if ( ! ( $wp_styles instanceof WP_Styles ) ) return;

    $allowed = array(
        'main','cat','login','search','single',
        'slider','pro-crusal','fixgrid','crusal','searchheader','front',
        'login2','header-mobile','profile','search-mobile','menu-mobile','sidebar-mobile',
        'divider','footer','grid','social','catheader','sidebar','related','email','download','sharesingle','author','comment',
        'dashicons','style','theme-style','foxiz-style',
    );

    if ( is_user_logged_in() ) {
        $allowed[] = 'admin-bar';
    }

    foreach ( (array) $wp_styles->queue as $handle ) {
        if ( ! in_array( $handle, $allowed, true ) ) {
            wp_dequeue_style( $handle );
            wp_deregister_style( $handle );
        }
    }
}
add_action( 'wp_print_styles', 'wd4_prune_styles', PHP_INT_MAX );

add_action( 'login_enqueue_scripts', function (): void {
    wp_enqueue_style( 'login', 'https://aistudynow.com/wp-content/themes/css/login.css', array(), '97488777977.2.0' );
} );


/**
 * -------------------------------------------------------------------------
 * Foxiz block scripting helpers
 * -------------------------------------------------------------------------
 */
add_action( 'wp_head', function (): void {
    if ( is_admin() ) return;

    $params = array(
        'ajaxurl'         => admin_url( 'admin-ajax.php' ),
        'security'        => wp_create_nonce( 'foxiz-ajax' ),
        'darkModeID'      => 'RubyDarkMode',
        'yesPersonalized' => '',
        'cookieDomain'    => '',
        'cookiePath'      => '/',
    );

    $ui = array(
        'sliderSpeed'  => '5000',
        'sliderEffect' => 'slide',
        'sliderFMode'  => '1',
    );

    $core_script = 'var foxizCoreParams = ' . wp_json_encode( $params ) . ';';
    $ui_script   = 'window.foxizParams = ' . wp_json_encode( $ui ) . ';';

    if ( function_exists( 'wp_print_inline_script_tag' ) ) {
        echo wp_print_inline_script_tag( $core_script, array( 'id' => 'foxiz-core-js-extra' ) );
        echo wp_print_inline_script_tag( $ui_script, array( 'id' => 'foxiz-ui-js-extra' ) );
    } else {
        printf( '<script id="foxiz-core-js-extra">%s</script>', $core_script );
        printf( '<script id="foxiz-ui-js-extra">%s</script>', $ui_script );
    }
}, 1 );

function my_detect_view_context(): string {
    if ( is_front_page() || is_home() ) return 'home';
    if ( function_exists( 'is_product_category' ) && is_product_category() ) return 'category';
    if ( is_category() || is_tag() || is_tax() ) return 'category';
    if ( is_search() ) return 'search';
    if ( is_author() ) return 'author';
    if ( is_singular( 'post' ) ) return 'post';
    if ( is_page() ) return 'page';
    return 'other';
}

function my_get_allowed_js_handles_by_context( string $context ): array {



    $defer_handle = wd4_should_defer_styles() ? 'wd-defer-css' : '';

    $allowed = array(
        'home'     => array( 'main', 'pagination', 'lazy', $defer_handle ),
        'category' => array( 'main', 'pagination', 'lazy', $defer_handle ),
        'search'   => array(),
        'author'   => array(),
        'post'     => array('comment','download','main','lazy','pagination','foxiz-core', $defer_handle, 'tw-facade'),
        'page'     => array('comment','download','main','lazy','pagination','foxiz-core', $defer_handle, 'tw-facade'),
        'other'    => array(),
   );
    $list = $allowed[ $context ] ?? array();
    $list = array_values( array_unique( $list ) );
    return (array) apply_filters( 'my_allowed_js_handles', $list, $context );
}

function my_register_context_only_scripts(): void {
    $context = my_detect_view_context();

    $main          = 'https://aistudynow.com/wp-content/themes/js/main.js';
    $lazy          = 'https://aistudynow.com/wp-content/themes/js/lazy.js';
    $pagination_js = 'https://aistudynow.com/wp-content/themes/js/pagination.js';
    $comment       = 'https://aistudynow.com/wp-content/themes/js/comment.js';
    $download      = 'https://aistudynow.com/wp-content/plugins/newsletter-11/assets/js/download-form-validation.js';
    $core_js       = 'https://aistudynow.com/wp-content/themes/js/core.js';
    $defer_js      = 'https://aistudynow.com/wp-content/themes/js/defer-css.js';
    $tw_facade_js  = 'https://aistudynow.com/wp-content/themes/js/tw-facade.js';
    if ( 'home' === $context ) {
        wp_enqueue_script( 'main', $main, array(), '2.0.0', true );
        wp_enqueue_script( 'pagination', $pagination_js, array(), '1.0.1', true );
        if ( wd4_should_defer_styles() ) {
            wp_enqueue_script( 'wd-defer-css', $defer_js, array(), '2.0.0', true );
        }

        $home_block_globals = <<<'JS'
var uid_cfc8f6c = {"uuid":"uid_cfc8f6c","category":"208","name":"grid_flex_2","order":"date_post","posts_per_page":"12","pagination":"load_more","unique":"1","crop_size":"foxiz_crop_g1","entry_category":"bg-4","title_tag":"h2","entry_meta":["author","category"],"review_meta":"-1","excerpt_source":"tagline","readmore":"Read More","block_structure":"thumbnail, meta, title","divider_style":"solid","post_not_in":"5403,5400,5395,5392","paged":"1","page_max":"1"};
var uid_0d9c5d1 = {"uuid":"uid_0d9c5d1","category":"212","name":"grid_flex_2","order":"date_post","posts_per_page":"8","pagination":"load_more","unique":"1","crop_size":"foxiz_crop_g1","entry_category":"bg-4","title_tag":"h2","entry_meta":["author","category"],"review_meta":"-1","excerpt_source":"tagline","readmore":"Read More","block_structure":"thumbnail, meta, title","divider_style":"solid","post_not_in":"5403,5400,5395,5392,5374,5306,5210,5180","paged":"1","page_max":"4"};
var uid_c9675dd = {"uuid":"uid_c9675dd","category":"209","name":"grid_flex_2","order":"date_post","posts_per_page":"12","pagination":"load_more","unique":"1","crop_size":"foxiz_crop_g1","entry_category":"bg-4","title_tag":"h2","entry_meta":["author","category"],"review_meta":"-1","excerpt_source":"tagline","readmore":"Read More","block_structure":"thumbnail, meta, title","divider_style":"solid","post_not_in":"5403,5400,5395,5392,5374,5306,5210,5180,5328,5291,5257,5239,5216,5192,5151,5124","paged":"1","page_max":"1"};
var uid_1c5cfd6 = {"uuid":"uid_1c5cfd6","category":"215","name":"grid_flex_2","order":"date_post","posts_per_page":"12","pagination":"load_more","unique":"1","crop_size":"foxiz_crop_g1","entry_category":"bg-4","title_tag":"h2","entry_meta":["author","category"],"review_meta":"-1","excerpt_source":"tagline","readmore":"Read More","block_structure":"thumbnail, meta, title","divider_style":"solid","post_not_in":"5403,5400,5395,5392,5374,5306,5210,5180,5328,5291,5257,5239,5216,5192,5151,5124,5080,5077,4925,4914,4580","paged":"1","page_max":"1"};
JS;
        wp_add_inline_script( 'pagination', $home_block_globals, 'before' );
        return;
    }

    if ( 'category' === $context ) {
        wp_enqueue_script( 'main', $main, array(), '4.0.0', true );
        wp_enqueue_script( 'pagination', $pagination_js, array(), '1.0.1', true );
        if ( wd4_should_defer_styles() ) {
            wp_enqueue_script( 'wd-defer-css', $defer_js, array(), '2.0.0', true );
        }

        global $wp_query;
        $qo             = get_queried_object();
        $taxonomy       = isset( $qo->taxonomy ) ? $qo->taxonomy : 'category';
        $term_id        = isset( $qo->term_id ) ? (int) $qo->term_id : 0;
        $page_max       = (int) ( $wp_query ? $wp_query->max_num_pages : 1 );
        $posts_per_page = (int) get_query_var( 'posts_per_page', get_option( 'posts_per_page' ) );
        $paged          = (int) max( 1, get_query_var( 'paged' ) );

        $settings = array(
            'uuid'            => null,
            'name'            => 'grid_flex_2',
            'order'           => 'date_post',
            'posts_per_page'  => (string) $posts_per_page,
            'pagination'      => null,
            'unique'          => '1',
            'crop_size'       => 'foxiz_crop_g1',
            'entry_category'  => 'bg-4',
            'title_tag'       => 'h2',
            'entry_meta'      => array( 'author', 'category' ),
            'review_meta'     => '-1',
            'excerpt_source'  => 'tagline',
            'readmore'        => 'Read More',
            'block_structure' => 'thumbnail, meta, title',
            'divider_style'   => 'solid',
            'entry_tax'       => $taxonomy,
            'category'        => (string) $term_id,
            'paged'           => (string) $paged,
            'page_max'        => (string) $page_max,
        );

        wp_add_inline_script( 'pagination', 'var foxizCoreParams = ' . wp_json_encode( array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'security' => wp_create_nonce( 'foxiz-ajax' ) ) ) . ';', 'before' );
        wp_add_inline_script( 'pagination', 'window.foxizParams = ' . wp_json_encode( array( 'sliderSpeed' => '5000', 'sliderEffect' => 'slide', 'sliderFMode' => '1' ) ) . ';', 'before' );

        $bootstrap = sprintf(
            <<<'JS'
(function(){
  var btn = document.querySelector('.pagination-wrap .loadmore-trigger');
  var block = (btn && (btn.closest('.block-wrap') || btn.closest('.archive-block') || btn.closest('.site-main'))) || document.querySelector('.block-wrap, .archive-block, .site-main');
  if (!block) return;
  if (!block.id) { block.id = 'uid_' + Math.random().toString(36).slice(2, 9); }
  var settings = %s;
  settings.uuid = block.id;
  var hasLoadMoreBtn = !!document.querySelector('.pagination-wrap .loadmore-trigger');
  var hasSentinel    = !!block.querySelector('.pagination-infinite');
  var mode = hasLoadMoreBtn ? 'load_more' : (hasSentinel ? 'infinite_scroll' : 'infinite_scroll');
  settings.pagination = mode;
  window[block.id] = settings;
  if (mode === 'infinite_scroll') {
    var inner = block.querySelector('.block-inner') || block;
    var sentinel = inner.querySelector('.pagination-infinite');
    if (!sentinel) {
      sentinel = document.createElement('div');
      sentinel.className = 'pagination-infinite';
      sentinel.innerHTML = '<i class="rb-loader" aria-hidden="true"></i>';
      inner.appendChild(sentinel);
    }
    var wrap = block.querySelector('.pagination-wrap');
    if (wrap) { wrap.style.display = 'none'; }
  }
})();
JS,
            wp_json_encode( $settings )
        );

        wp_add_inline_script( 'pagination', $bootstrap, 'before' );
        return;
    }

    if ( in_array( $context, array( 'post', 'page' ), true ) ) {
        wp_enqueue_script( 'comment', $comment, array(), '1.0.0', true );
        wp_enqueue_script( 'main', $main, array(), '9900899.0.0', true );
        wp_enqueue_script( 'lazy', $lazy, array(), '9918.0.0', true );
        wp_enqueue_script( 'pagination', $pagination_js, array(), '5.0.1', true );
        wp_enqueue_script( 'download', $download, array(), '000.0.0', true );
        wp_enqueue_script( 'foxiz-core', $core_js, array(), '7.0.0', true );
        if ( wd4_should_defer_styles() ) {
            wp_enqueue_script( 'wd-defer-css', $defer_js, array(), '2.0.0', true );
        }
        wp_enqueue_script( 'tw-facade', $tw_facade_js, array(), '188609.0.0', true );
    }
    
    
    
    
    
}
add_action( 'wp_enqueue_scripts', 'my_register_context_only_scripts', 20 );




function wd4_bootstrap_nav_measure_inline(): void {
    static $added = false;

    if ( $added ) {
        return;
    }

    if ( is_admin() ) {
        return;
    }

    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return;
    }

    $context = my_detect_view_context();
    if ( ! in_array( $context, array( 'home', 'category', 'post', 'page' ), true ) ) {
        return;
    }

    if ( ! wp_script_is( 'main', 'enqueued' ) ) {
        return;
    }

    $path = trailingslashit( get_stylesheet_directory() ) . 'js/nav-measure-lite.js';
    if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
        return;
    }

    $script = file_get_contents( $path );
    if ( false === $script ) {
        return;
    }

    $script = trim( $script );
    if ( '' === $script ) {
        return;
    }

    wp_add_inline_script( 'main', $script, 'after' );
    $added = true;
}
add_action( 'wp_enqueue_scripts', 'wd4_bootstrap_nav_measure_inline', 99 );








function wd4_mark_core_script_deferred(): void {
    if ( is_admin() ) return;
    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) return;

    if ( function_exists( 'wp_script_add_data' ) && wp_script_is( 'foxiz-core', 'registered' ) ) {
        wp_script_add_data( 'foxiz-core', 'defer', true );
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_mark_core_script_deferred', 40 );













function wd4_get_core_script_inline_payload(): string {
    static $cached = null;

    if ( null !== $cached ) {
        return $cached;
    }

    $paths = array(
        trailingslashit( get_stylesheet_directory() ) . 'js/core.js',
        trailingslashit( get_template_directory() ) . 'js/core.js',
        trailingslashit( WP_CONTENT_DIR ) . 'themes/js/core.js',
    );

    foreach ( $paths as $path ) {
        if ( ! is_readable( $path ) ) {
            continue;
        }

        $contents = file_get_contents( $path );
        if ( false === $contents ) {
            continue;
        }

        $contents = trim( $contents );
        if ( '' === $contents ) {
            continue;
        }

        $cached = $contents;
        return $cached;
    }

    $cached = '';
    return $cached;
}

function wd4_generate_inline_core_script_tag(): string {
    $payload = wd4_get_core_script_inline_payload();
    if ( '' === $payload ) {
        return '';
    }

    if ( function_exists( 'wp_print_inline_script_tag' ) ) {
        return wp_print_inline_script_tag( $payload, array( 'id' => 'foxiz-core-js' ) );
    }

    return sprintf( '<script id="foxiz-core-js">%s</script>', $payload );
}

function wd4_preload_core_script_hint(): void {
    if ( is_admin() ) {
        return;
    }

    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return;
    }

    if ( '' !== wd4_get_core_script_inline_payload() ) {
        return;
    }

    $context = my_detect_view_context();
    if ( ! in_array( $context, array( 'post', 'page' ), true ) ) {
        return;
    }

    static $printed = false;
    if ( $printed ) {
        return;
    }

    $printed = true;

    $src = '';
    $handle = 'foxiz-core';
    $wp_scripts = wp_scripts();

    if ( $wp_scripts instanceof WP_Scripts && isset( $wp_scripts->registered[ $handle ] ) ) {
        $registered = $wp_scripts->registered[ $handle ];
        $src        = $registered->src;

        if ( $src && 0 === strpos( $src, '//' ) ) {
            $src = ( is_ssl() ? 'https:' : 'http:' ) . $src;
        } elseif ( $src && false === strpos( $src, '://' ) ) {
            $src = trailingslashit( $wp_scripts->base_url ) . ltrim( $src, '/' );
        }

        $ver = $registered->ver;
        if ( $ver && false === strpos( $src, '?ver=' ) ) {
            $src = add_query_arg( 'ver', $ver, $src );
        }
    }

    if ( empty( $src ) ) {
        $src = 'https://aistudynow.com/wp-content/themes/js/core.js?ver=4.0.0';
    }

    printf(
        '<link rel="preload" as="script" href="%s" fetchpriority="high" />' . "\n",
        esc_url( $src )
    );
}
add_action( 'wp_head', 'wd4_preload_core_script_hint', 6 );

function wd4_enforce_core_script_priorities( string $tag ): string {
    if ( false === stripos( $tag, ' defer' ) ) {
        $updated = preg_replace( '/<script\s+/i', '<script defer ', $tag, 1 );
        if ( null !== $updated ) {
            $tag = $updated;
        } else {
            $tag = str_replace( '<script', '<script defer', $tag );
        }
    }

    if ( false === stripos( $tag, 'fetchpriority' ) ) {
        $updated = preg_replace( '/<script\s+/i', '<script fetchpriority="high" ', $tag, 1 );
        if ( null !== $updated ) {
            $tag = $updated;
        } else {
            $tag = str_replace( '<script', '<script fetchpriority="high"', $tag );
        }
    }

    return $tag;
}












function my_disable_all_js_except_whitelisted(): void {
    $doing_ajax = function_exists('wp_doing_ajax') && wp_doing_ajax();
    if ( is_admin() || $doing_ajax ) return;

    global $wp_scripts;
    if ( ! ( $wp_scripts instanceof WP_Scripts ) ) return;

    $context = my_detect_view_context();
    $targets = array( 'home', 'category', 'search', 'author', 'post', 'page' );
    if ( ! in_array( $context, $targets, true ) ) return;

    $allowed = array_values( array_unique( array_filter( my_get_allowed_js_handles_by_context( $context ), 'strlen' ) ) );

    foreach ( (array) $wp_scripts->queue as $handle ) {
        if ( ! in_array( $handle, $allowed, true ) ) {
            wp_dequeue_script( $handle );
            wp_deregister_script( $handle );
        }
    }

    add_action( 'wp_print_scripts', function () use ( $allowed ): void {
        global $wp_scripts;
        if ( ! $wp_scripts ) return;
        foreach ( (array) $wp_scripts->queue as $handle ) {
            if ( ! in_array( $handle, $allowed, true ) ) {
                wp_dequeue_script( $handle );
                wp_deregister_script( $handle );
            }
        }
    }, PHP_INT_MAX );

    add_action( 'wp_print_footer_scripts', function () use ( $allowed ): void {
        global $wp_scripts;
        if ( ! $wp_scripts ) return;
        foreach ( (array) $wp_scripts->queue as $handle ) {
            if ( ! in_array( $handle, $allowed, true ) ) {
                wp_dequeue_script( $handle );
                wp_deregister_script( $handle );
            }
        }
    }, PHP_INT_MAX );
}
add_action( 'wp_enqueue_scripts', 'my_disable_all_js_except_whitelisted', PHP_INT_MAX );
































/**
 * -------------------------------------------------------------------------
 * Misc integrations (non-AdSense)
 * -------------------------------------------------------------------------
 */

/* Keep body aria-hidden fixer */
add_action( 'wp_footer', function (): void {
    ?>
    <script>
    (function(){
      var body = document.body;
      if (!body) { return; }
      if (body.getAttribute('aria-hidden') === 'true') { body.removeAttribute('aria-hidden'); }
      var obs = new MutationObserver(function(mutations){
        for (var i = 0; i < mutations.length; i++) {
          var m = mutations[i];
          if (m.type === 'attributes' && m.attributeName === 'aria-hidden' && body.getAttribute('aria-hidden') === 'true') {
            body.removeAttribute('aria-hidden');
          }
        }
      });
      obs.observe(body, { attributes: true, attributeFilter: ['aria-hidden'] });
    })();
    </script>
    <?php
}, 100 );

function allow_json_mime( array $mimes ): array {
    $mimes['json'] = 'application/json';
    return $mimes;
}
add_filter( 'upload_mimes', 'allow_json_mime' );

add_action( 'shutdown', function (): void {
    while ( ob_get_level() > 0 ) {
        @ob_end_flush();
    }
}, PHP_INT_MAX );




