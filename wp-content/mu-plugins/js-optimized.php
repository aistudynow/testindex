<?php
/**
 * Plugin Name: WD JS Optimizations
 * Description: Relocates Foxiz script controls into a shared mu-plugin and tightens the whitelist pipeline.
 */

defined( 'ABSPATH' ) || exit;


/**
 * Is this the front-end login page (/login-3/, /login/, /sign-in/)?
 */
if ( ! function_exists( 'wd4_js_is_login_view' ) ) {
    function wd4_js_is_login_view(): bool {
        // Explicit page ID, if you ever set WD_LOGIN_PAGE_ID
        if ( defined( 'WD_LOGIN_PAGE_ID' ) && WD_LOGIN_PAGE_ID && is_page( (int) WD_LOGIN_PAGE_ID ) ) {
            return true;
        }

        // Template check
        if ( is_page_template( 'login.php' ) ) {
            return true;
        }

        // Slug check
        if ( is_page() ) {
            $id   = get_queried_object_id();
            $slug = $id ? get_post_field( 'post_name', $id ) : '';
            if ( $slug && in_array( $slug, array( 'login-3', 'login', 'sign-in' ), true ) ) {
                return true;
            }
        }

        // Fallback: look at URL path
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $path     = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
            $segments = array_filter( array_map( 'trim', explode( '/', trim( $path, '/' ) ) ) );
            $last     = $segments ? end( $segments ) : '';
            if ( $last && in_array( $last, array( 'login-3', 'login', 'sign-in' ), true ) ) {
                return true;
            }
        }

        return false;
    }
}



/**
 * -------------------------------------------------------------------------
 * Inline parameter bootstrap for legacy Foxiz bundles
 * -------------------------------------------------------------------------
 */
if ( ! function_exists( 'wd4_js_print_core_params' ) ) {
    function wd4_js_print_core_params(): void {
    if ( is_admin() ) {
        return;
    }

    // Do not output Foxiz JS params on the front login page
    if ( wd4_js_is_login_view() ) {
        return;
    }

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
            return;
        }

        printf( '<script id="foxiz-core-js-extra">%s</script>', $core_script );
        printf( '<script id="foxiz-ui-js-extra">%s</script>', $ui_script );
    }
}
add_action( 'wp_head', 'wd4_js_print_core_params', 1 );

/**
 * -------------------------------------------------------------------------
 * Context detection + whitelisting helpers
 * -------------------------------------------------------------------------
 */
if ( ! function_exists( 'wd4_js_detect_view_context' ) ) {
    function wd4_js_detect_view_context(): string {
        if ( is_front_page() || is_home() ) {
            return 'home';
        }
        if ( function_exists( 'is_product_category' ) && is_product_category() ) {
            return 'category';
        }
        if ( is_category() || is_tag() || is_tax() ) {
            return 'category';
        }
        if ( is_search() ) {
            return 'search';
        }
        if ( is_author() ) {
            return 'author';
        }
        if ( is_singular( 'post' ) ) {
            return 'post';
        }
        if ( is_page() ) {
            return 'page';
        }
        return 'other';
    }
}

if ( ! function_exists( 'wd4_js_get_allowed_handles_by_context' ) ) {
    function wd4_js_get_allowed_handles_by_context( string $context ): array {
        $defer_handle = function_exists( 'wd4_should_defer_styles' ) && wd4_should_defer_styles() ? 'wd-defer-css' : '';

        $map = array(
            'home'     => array( 'main', 'pagination', 'lazy', $defer_handle ),
            'category' => array( 'main', 'pagination', 'lazy', $defer_handle ),
            'search'   => array(),
            'author'   => array(),
            'post'     => array( 'comment', 'download', 'main', 'lazy', 'pagination', 'foxiz-core', $defer_handle, 'tw-facade' ),
            'page'     => array( 'comment', 'download', 'main', 'lazy', 'pagination', 'foxiz-core', $defer_handle, 'tw-facade' ),
            'other'    => array(),
        );

        $list = $map[ $context ] ?? array();
        $list = array_values( array_unique( array_filter( $list, 'strlen' ) ) );

        return (array) apply_filters( 'my_allowed_js_handles', $list, $context );
    }
}

if ( ! function_exists( 'my_detect_view_context' ) ) {
    function my_detect_view_context(): string {
        return wd4_js_detect_view_context();
    }
}

if ( ! function_exists( 'my_get_allowed_js_handles_by_context' ) ) {
    function my_get_allowed_js_handles_by_context( string $context ): array {
        return wd4_js_get_allowed_handles_by_context( $context );
    }
}

/**
 * -------------------------------------------------------------------------
 * Contextual enqueues and inline payloads
 * -------------------------------------------------------------------------
 */
if ( ! function_exists( 'wd4_js_register_context_only_scripts' ) ) {
     function wd4_js_register_context_only_scripts(): void {
    $context = wd4_js_detect_view_context();

    // On front-end login (/login-3/ etc.) we do NOT enqueue Foxiz scripts at all
    if ( wd4_js_is_login_view() ) {
        return;
    }

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
            if ( function_exists( 'wd4_should_defer_styles' ) && wd4_should_defer_styles() ) {
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
            if ( function_exists( 'wd4_should_defer_styles' ) && wd4_should_defer_styles() ) {
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

            $sentinel_patch = <<<'JS'
(function(){
  var Module = window.FOXIZ_MAIN_SCRIPT;
  if (!Module || Module.__wd4SentinelPatched) return;
  if (typeof Module.ajaxRenderHTML !== 'function') return;

  var original = Module.ajaxRenderHTML;
  Module.ajaxRenderHTML = function(block, uuid, response, action){
    original.call(this, block, uuid, response, action);
    try {
      if (!block || !block.querySelector) return;
      var inner = block.querySelector('.block-inner');
      var sentinel = inner ? inner.querySelector('.pagination-infinite') : null;
      if (inner && sentinel && sentinel !== inner.lastElementChild) {
        inner.appendChild(sentinel);
      }
    } catch (err) {
      if (window.console && console.warn) {
        console.warn('WD4 pagination sentinel reposition failed', err);
      }
    }
  };

  Module.__wd4SentinelPatched = true;
})();
JS;

            wp_add_inline_script( 'pagination', $sentinel_patch, 'after' );
            return;
        }

        if ( in_array( $context, array( 'post', 'page' ), true ) ) {
            wp_enqueue_script( 'comment', $comment, array(), '1.0.0', true );
            wp_enqueue_script( 'main', $main, array(), '09900899.0.0', true );
            wp_enqueue_script( 'lazy', $lazy, array(), '09918.0.0', true );
            wp_enqueue_script( 'pagination', $pagination_js, array(), '885.0.1', true );
            wp_enqueue_script( 'download', $download, array(), '000.0.0', true );
            wp_enqueue_script( 'foxiz-core', $core_js, array(), '7.0.0', true );
            if ( function_exists( 'wd4_should_defer_styles' ) && wd4_should_defer_styles() ) {
                wp_enqueue_script( 'wd-defer-css', $defer_js, array(), '2.0.0', true );
            }
            wp_enqueue_script( 'tw-facade', $tw_facade_js, array(), '188609.0.0', true );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_js_register_context_only_scripts', 20 );

if ( ! function_exists( 'my_register_context_only_scripts' ) ) {
    function my_register_context_only_scripts(): void {
        wd4_js_register_context_only_scripts();
    }
}

/**
 * -------------------------------------------------------------------------
 * Inline nav measure bootstrap (requires main.js)
 * -------------------------------------------------------------------------
 */
if ( ! function_exists( 'wd4_bootstrap_nav_measure_inline' ) ) {
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

        $context = wd4_js_detect_view_context();
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
}
add_action( 'wp_enqueue_scripts', 'wd4_bootstrap_nav_measure_inline', 99 );

/**
 * -------------------------------------------------------------------------
 * Core script tuning (defer + preload fallbacks)
 * -------------------------------------------------------------------------
 */
if ( ! function_exists( 'wd4_mark_core_script_deferred' ) ) {
    function wd4_mark_core_script_deferred(): void {
        if ( is_admin() ) {
            return;
        }

        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return;
        }

        if ( function_exists( 'wp_script_add_data' ) && wp_script_is( 'foxiz-core', 'registered' ) ) {
            wp_script_add_data( 'foxiz-core', 'defer', true );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_mark_core_script_deferred', 40 );

if ( ! function_exists( 'wd4_get_core_script_inline_payload' ) ) {
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
}

if ( ! function_exists( 'wd4_generate_inline_core_script_tag' ) ) {
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
}

if ( ! function_exists( 'wd4_preload_core_script_hint' ) ) {
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

        $context = wd4_js_detect_view_context();
        if ( ! in_array( $context, array( 'post', 'page' ), true ) ) {
            return;
        }

        static $printed = false;
        if ( $printed ) {
            return;
        }

        $printed = true;

        $src       = '';
        $handle    = 'foxiz-core';
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
}
add_action( 'wp_head', 'wd4_preload_core_script_hint', 6 );

if ( ! function_exists( 'wd4_enforce_core_script_priorities' ) ) {
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
}

/**
 * -------------------------------------------------------------------------
 * Queue pruning
 * -------------------------------------------------------------------------
 */
if ( ! function_exists( 'wd4_js_prune_script_queue' ) ) {
    function wd4_js_prune_script_queue( array $allowed ): void {
        global $wp_scripts;

        if ( ! ( $wp_scripts instanceof WP_Scripts ) ) {
            return;
        }

        foreach ( (array) $wp_scripts->queue as $handle ) {
            if ( ! in_array( $handle, $allowed, true ) ) {
                wp_dequeue_script( $handle );
                wp_deregister_script( $handle );
            }
        }
    }
}

if ( ! function_exists( 'wd4_js_disable_all_js_except_whitelisted' ) ) {
    function wd4_js_disable_all_js_except_whitelisted(): void {
    $doing_ajax = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();
    if ( is_admin() || $doing_ajax ) {
        return;
    }

    // On the front login view we handle JS separately, do not run the generic pruner
    if ( wd4_js_is_login_view() ) {
        return;
    }

    $context = wd4_js_detect_view_context();
    $targets = array( 'home', 'category', 'search', 'author', 'post', 'page' );
        if ( ! in_array( $context, $targets, true ) ) {
            return;
        }

        $allowed = wd4_js_get_allowed_handles_by_context( $context );
        if ( empty( $allowed ) ) {
            $allowed = array();
        }

        wd4_js_prune_script_queue( $allowed );

        add_action( 'wp_print_scripts', static function () use ( $allowed ): void {
            wd4_js_prune_script_queue( $allowed );
        }, PHP_INT_MAX );

        add_action( 'wp_print_footer_scripts', static function () use ( $allowed ): void {
            wd4_js_prune_script_queue( $allowed );
        }, PHP_INT_MAX );
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_js_disable_all_js_except_whitelisted', PHP_INT_MAX );

if ( ! function_exists( 'my_disable_all_js_except_whitelisted' ) ) {
    function my_disable_all_js_except_whitelisted(): void {
        wd4_js_disable_all_js_except_whitelisted();
    }
}


/**
 * On the front-end login page, strip Foxiz/theme JS so only core + reCAPTCHA remain.
 */
function wd4_js_strip_theme_js_on_login(): void {
    if ( is_admin() || ! wd4_js_is_login_view() ) {
        return;
    }

    /**
     * Handles to remove on the front-end login page.
     *
     * NOTE: <script id="HANDLE-js"> means the handle is just "HANDLE".
     */
    $block = array(
        // Foxiz / theme JS
        'foxiz-core',
        'main',
        'lazy',
        'pagination',
        'comment',
        'download',
        'tw-facade',
        'wd-defer-css',
        'wd-comment-toggle',          // <script id="wd-comment-toggle-js">

        // Newsletter download validation
        'wns-download-validation',    // <script id="wns-download-validation-js">

        // Contact Form 7
        'swv',                        // <script id="swv-js">
        'contact-form-7',             // <script id="contact-form-7-js">

        // Core block helpers (not needed on bare login page)
        'wp-hooks',                   // <script id="wp-hooks-js">
        'wp-i18n',                    // <script id="wp-i18n-js">
    );

    foreach ( $block as $handle ) {
        wp_dequeue_script( $handle );
        wp_deregister_script( $handle );
    }
}
add_action( 'wp_print_scripts', 'wd4_js_strip_theme_js_on_login', PHP_INT_MAX );
add_action( 'wp_print_footer_scripts', 'wd4_js_strip_theme_js_on_login', PHP_INT_MAX );
