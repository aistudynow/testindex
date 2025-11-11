<?php
/**
 * Plugin Name: WD JS Optimizations
 * Description: Relocates Foxiz script controls into a shared mu-plugin and tightens the whitelist pipeline.
 */

defined( 'ABSPATH' ) || exit();

/**
 * Detect whether we are on the front-end login page (/login-3/, /login/, /sign-in/).
 */
if ( ! function_exists( 'wd4_js_is_login_view' ) ) {
    function wd4_js_is_login_view(): bool {
        // Explicit login page ID constant.
        if ( defined( 'WD_LOGIN_PAGE_ID' ) && WD_LOGIN_PAGE_ID && is_page( (int) WD_LOGIN_PAGE_ID ) ) {
            return true;
        }

        // Login template.
        if ( is_page_template( 'login.php' ) ) {
            return true;
        }

        // Common login slugs.
        if ( is_page() ) {
            $id   = get_queried_object_id();
            $slug = $id ? get_post_field( 'post_name', $id ) : '';
            if ( $slug && in_array( $slug, array( 'login-3', 'login', 'sign-in' ), true ) ) {
                return true;
            }
        }

        // Path-based fallback (handles pretty URLs even if query is broken).
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
 * Global Foxiz parameter bootstrap.
 */
if ( ! function_exists( 'wd4_js_print_core_params' ) ) {
    function wd4_js_print_core_params(): void {
        if ( is_admin() || wd4_js_is_login_view() ) {
            return;
        }

        $current_url = home_url( '/' );

        if ( isset( $GLOBALS['wp']->request ) && is_string( $GLOBALS['wp']->request ) && $GLOBALS['wp']->request ) {
            $current_url = home_url( '/' . ltrim( $GLOBALS['wp']->request, '/' ) );
        }

        if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
            $current_url = esc_url_raw( $current_url . '?' . wp_unslash( (string) $_SERVER['QUERY_STRING'] ) );
        }

        $current_user_email = '';
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            if ( $user instanceof WP_User && $user->user_email ) {
                $current_user_email = sanitize_email( $user->user_email );
            }
        }

        $params = array(
            'ajaxurl'          => admin_url( 'admin-ajax.php' ),
            'security'         => wp_create_nonce( 'foxiz-ajax' ),
            'darkModeID'       => 'RubyDarkMode',
            'yesPersonalized'  => '',
            'cookieDomain'     => '',
            'cookiePath'       => '/',
            'isLoggedIn'       => is_user_logged_in(),
            'currentUserEmail' => $current_user_email,
            'loginUrl'         => wp_login_url( $current_url ),
        );

        $ui = array(
            'sliderSpeed'  => '5000',
            'sliderEffect' => 'slide',
            'sliderFMode'  => '1',
        );

        $blocks = array(
            array(
                'id'   => 'foxiz-core-js-extra',
                'code' => 'window.foxizCoreParams = window.foxizCoreParams || ' . wp_json_encode( $params ) . ';',
            ),
            array(
                'id'   => 'foxiz-ui-js-extra',
                'code' => 'window.foxizParams = window.foxizParams || ' . wp_json_encode( $ui ) . ';',
            ),
        );

        foreach ( $blocks as $block ) {
            if ( function_exists( 'wp_print_inline_script_tag' ) ) {
                echo wp_print_inline_script_tag( $block['code'], array( 'id' => $block['id'] ) );
            } else {
                printf( '<script id="%1$s">%2$s</script>', esc_attr( $block['id'] ), $block['code'] );
            }
        }
    }
}
add_action( 'wp_head', 'wd4_js_print_core_params', 1 );

/**
 * Script catalog helpers.
 */
if ( ! function_exists( 'wd4_js_get_script_catalog' ) ) {
    function wd4_js_get_script_catalog(): array {
        static $catalog = null;

        if ( null !== $catalog ) {
            return $catalog;
        }

        $catalog = array(
            'comment'      => array(
                'src'       => 'https://aistudynow.com/wp-content/themes/js/comment.js',
                'deps'      => array(),
                'versions'  => array( 'default' => '1.0.0' ),
                'in_footer' => true,
            ),
            'main'         => array(
                'src'       => 'https://aistudynow.com/wp-content/themes/js/main.js',
                'deps'      => array(),
                'versions'  => array(
                    'default'  => '18999.0.0',
                    'home'     => '221.0.0',
                    'category' => '432.0.0',
                ),
                'in_footer' => true,
            ),
            'lazy'         => array(
                'src'       => 'https://aistudynow.com/wp-content/themes/js/lazy.js',
                'deps'      => array(),
                'versions'  => array( 'default' => '0718.0.0' ),
                'in_footer' => true,
            ),
            'infinity'   => array(
                'src'       => 'https://aistudynow.com/wp-content/themes/js/category-infinite.js',
                'deps'      => array(),
                'versions'  => array(
                    'default'  => '885.0.1',
                    'home'     => '1.0.1',
                    'category' => '1.0.1',
                ),
                'in_footer' => true,
            ),
            'download'     => array(
                'src'       => 'https://aistudynow.com/wp-content/plugins/newsletter-11/assets/js/download-form-validation.js',
                'deps'      => array(),
                'versions'  => array( 'default' => '000.0.0' ),
                'in_footer' => true,
            ),
            'foxiz-core'   => array(
                'src'       => 'https://aistudynow.com/wp-content/themes/js/core.js',
                'deps'      => array(),
                'versions'  => array( 'default' => '12.0.0' ),
                'in_footer' => true,
            ),
            'wd-defer-css' => array(
                'src'       => 'https://aistudynow.com/wp-content/themes/js/defer-css.js',
                'deps'      => array(),
                'versions'  => array( 'default' => '2.0.0' ),
                'in_footer' => true,
            ),
            'tw-facade'    => array(
                'src'       => 'https://aistudynow.com/wp-content/themes/js/tw-facade.js',
                'deps'      => array(),
                'versions'  => array( 'default' => '188609.0.0' ),
                'in_footer' => true,
            ),
        );

        return $catalog;
    }
}

if ( ! function_exists( 'wd4_js_get_script_config' ) ) {
    function wd4_js_get_script_config( string $handle ): array {
        $catalog = wd4_js_get_script_catalog();
        return $catalog[ $handle ] ?? array();
    }
}

if ( ! function_exists( 'wd4_js_get_script_version' ) ) {
    function wd4_js_get_script_version( string $handle, string $context ) {
        $config   = wd4_js_get_script_config( $handle );
        $versions = $config['versions'] ?? array();

        if ( isset( $versions[ $context ] ) && '' !== $versions[ $context ] ) {
            return $versions[ $context ];
        }

        if ( isset( $versions['default'] ) ) {
            return $versions['default'];
        }

        return $config['ver'] ?? false;
    }
}

if ( ! function_exists( 'wd4_js_enqueue_script_for_context' ) ) {
    function wd4_js_enqueue_script_for_context( string $handle, string $context ): void {
        $config = wd4_js_get_script_config( $handle );
        if ( empty( $config ) ) {
            return;
        }

        $src = $config['src'] ?? '';
        if ( '' === $src ) {
            return;
        }

        $deps      = $config['deps'] ?? array();
        $in_footer = isset( $config['in_footer'] ) ? (bool) $config['in_footer'] : true;
        $version   = wd4_js_get_script_version( $handle, $context );

        wp_enqueue_script( $handle, $src, $deps, $version, $in_footer );
    }
}

/**
 * Context detection helpers.
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
            'home'     => array( 'main', 'lazy', $defer_handle ),
            'category' => array( 'main', 'infinity', 'lazy', $defer_handle ),
            'search'   => array(),
            'author'   => array(),
            'post'     => array( 'download', 'main', 'lazy', 'foxiz-core', $defer_handle, 'tw-facade' ),
            'page'     => array( 'download', 'main', 'lazy', 'foxiz-core', $defer_handle, 'tw-facade' ),
            'other'    => array(),
        );

        $list = $map[ $context ] ?? array();
        $list = array_values( array_unique( array_filter( $list, 'strlen' ) ) );

        return (array) apply_filters( 'my_allowed_js_handles', $list, $context );
    }
}

/**
 * Back-compat aliases for code living in the theme.
 */
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
 * Enqueue scripts for the detected context and attach inline payloads.
 */
if ( ! function_exists( 'wd4_js_register_context_only_scripts' ) ) {
    function wd4_js_register_context_only_scripts(): void {
        if ( is_admin() || wd4_js_is_login_view() ) {
            return;
        }

        $context = wd4_js_detect_view_context();
        $allowed = wd4_js_get_allowed_handles_by_context( $context );

        if ( empty( $allowed ) ) {
            return;
        }

        foreach ( $allowed as $handle ) {
            wd4_js_enqueue_script_for_context( $handle, $context );
        }

        // Home page: pre-bootstrap AJAX blocks.
        if ( 'home' === $context && wp_script_is( 'pagination', 'enqueued' ) ) {
            $home_block_globals = <<<'JS'
var uid_cfc8f6c={"uuid":"uid_cfc8f6c","category":"208","name":"grid_flex_2","order":"date_post","posts_per_page":"12","pagination":"load_more","unique":"1","crop_size":"foxiz_crop_g1","entry_category":"bg-4","title_tag":"h2","entry_meta":["author","category"],"review_meta":"-1","excerpt_source":"tagline","readmore":"Read More","block_structure":"thumbnail, meta, title","divider_style":"solid","post_not_in":"5403,5400,5395,5392","paged":"1","page_max":"1"},
    uid_0d9c5d1={"uuid":"uid_0d9c5d1","category":"212","name":"grid_flex_2","order":"date_post","posts_per_page":"8","pagination":"load_more","unique":"1","crop_size":"foxiz_crop_g1","entry_category":"bg-4","title_tag":"h2","entry_meta":["author","category"],"review_meta":"-1","excerpt_source":"tagline","readmore":"Read More","block_structure":"thumbnail, meta, title","divider_style":"solid","post_not_in":"5403,5400,5395,5392,5374,5306,5210,5180","paged":"1","page_max":"4"},
    uid_c9675dd={"uuid":"uid_c9675dd","category":"209","name":"grid_flex_2","order":"date_post","posts_per_page":"12","pagination":"load_more","unique":"1","crop_size":"foxiz_crop_g1","entry_category":"bg-4","title_tag":"h2","entry_meta":["author","category"],"review_meta":"-1","excerpt_source":"tagline","readmore":"Read More","block_structure":"thumbnail, meta, title","divider_style":"solid","post_not_in":"5403,5400,5395,5392,5374,5306,5210,5180,5328,5291,5257,5239,5216,5192,5151,5124","paged":"1","page_max":"1"},
    uid_1c5cfd6={"uuid":"uid_1c5cfd6","category":"215","name":"grid_flex_2","order":"date_post","posts_per_page":"12","pagination":"load_more","unique":"1","crop_size":"foxiz_crop_g1","entry_category":"bg-4","title_tag":"h2","entry_meta":["author","category"],"review_meta":"-1","excerpt_source":"tagline","readmore":"Read More","block_structure":"thumbnail, meta, title","divider_style":"solid","post_not_in":"5403,5400,5395,5392,5374,5306,5210,5180,5328,5291,5257,5239,5216,5192,5151,5124,5080,5077,4925,4914,4580","paged":"1","page_max":"1"};
JS;
            wp_add_inline_script( 'pagination', $home_block_globals, 'before' );
        }

        // Category archives: bootstrap grid + infinite scroll sentinel.
        if ( 'category' === $context && wp_script_is( 'pagination', 'enqueued' ) ) {
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

            $ajax_bootstrap = 'window.foxizCoreParams = window.foxizCoreParams || {};Object.assign(window.foxizCoreParams,' . wp_json_encode(
                array(
                    'ajaxurl'  => admin_url( 'admin-ajax.php' ),
                    'security' => wp_create_nonce( 'foxiz-ajax' ),
                )
            ) . ');';
            $ui_bootstrap   = 'window.foxizParams = window.foxizParams || {};Object.assign(window.foxizParams,' . wp_json_encode(
                array(
                    'sliderSpeed'  => '5000',
                    'sliderEffect' => 'slide',
                    'sliderFMode'  => '1',
                )
            ) . ');';

            wp_add_inline_script( 'pagination', $ajax_bootstrap, 'before' );
            wp_add_inline_script( 'pagination', $ui_bootstrap, 'before' );

            $bootstrap = sprintf(
                <<<'JS'
(function(){
  var btn=document.querySelector(".pagination-wrap .loadmore-trigger");
  var block=(btn&&(btn.closest(".block-wrap")||btn.closest(".archive-block")||btn.closest(".site-main")))||document.querySelector(".block-wrap, .archive-block, .site-main");
  if(!block)return;
  if(!block.id){block.id="uid_"+Math.random().toString(36).slice(2,9);}
  var settings=%s;
  settings.uuid=block.id;
  var hasLoadMore=!!document.querySelector(".pagination-wrap .loadmore-trigger");
  var inner=block.querySelector(".block-inner")||block;
  var sentinel=inner.querySelector(".pagination-infinite");
  var mode=hasLoadMore?"load_more":(sentinel?"infinite_scroll":"infinite_scroll");
  settings.pagination=mode;
  window[block.id]=settings;
  if(mode==="infinite_scroll"){
    if(!sentinel){
      sentinel=document.createElement("div");
      sentinel.className="pagination-infinite";
      sentinel.innerHTML='<i class="rb-loader" aria-hidden="true"></i>';
      inner.appendChild(sentinel);
    }
    var wrap=document.querySelector(".pagination-wrap");
    if(wrap){wrap.style.display="none";}
  }
})();
JS,
                wp_json_encode( $settings )
            );

            wp_add_inline_script( 'pagination', $bootstrap, 'before' );

            $sentinel_patch = <<<'JS'
(function(){
  var M=window.FOXIZ_MAIN_SCRIPT;
  if(!M||M.__wd4SentinelPatched||typeof M.ajaxRenderHTML!=="function")return;
  var original=M.ajaxRenderHTML;
  M.ajaxRenderHTML=function(block,uuid,response,action){
    original.call(this,block,uuid,response,action);
    try{
      if(!block||!block.querySelector)return;
      var inner=block.querySelector(".block-inner");
      var sentinel=inner&&inner.querySelector(".pagination-infinite");
      if(inner&&sentinel&&sentinel!==inner.lastElementChild){
        inner.appendChild(sentinel);
      }
    }catch(e){
      if(window.console&&console.warn){
        console.warn("WD4 pagination sentinel reposition failed",e);
      }
    }
  };
  M.__wd4SentinelPatched=true;
})();
JS;
            wp_add_inline_script( 'pagination', $sentinel_patch, 'after' );
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
 * Inline nav measurement helper (depends on main.js).
 */
if ( ! function_exists( 'wd4_bootstrap_nav_measure_inline' ) ) {
    function wd4_bootstrap_nav_measure_inline(): void {
        static $added = false;

        if ( $added || is_admin() ) {
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



if ( ! function_exists( 'wd4_bootstrap_profile_modal_inline' ) ) {
    function wd4_bootstrap_profile_modal_inline(): void {
        // Don’t run in admin or on the special login views.
        if ( is_admin() || wd4_js_is_login_view() ) {
            return;
        }

        // Only add this if the "main" script is actually enqueued.
        if ( ! wp_script_is( 'main', 'enqueued' ) ) {
            return;
        }

        $script = <<<'JS'
(function () {
  var body  = document.body;
  var modal = document.getElementById('wd4-profile-modal');

  if (!modal) return;

  function openModal() {
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    body.classList.add('wd4-profile-modal-open');

    var focusTarget = modal.querySelector(
      '[autofocus], input, select, textarea, button, [href]'
    );
    if (focusTarget) {
      try { focusTarget.focus(); } catch (e) {}
    }
  }

  function closeModal() {
    if (modal.hasAttribute('hidden')) return;
    modal.setAttribute('hidden', 'hidden');
    modal.setAttribute('aria-hidden', 'true');
    body.classList.remove('wd4-profile-modal-open');
  }

  document.addEventListener('click', function (e) {
    var openBtn = e.target.closest('[data-wd4-profile-modal-open]');
    if (openBtn) {
      e.preventDefault();
      openModal();
      return;
    }

    var closeBtn = e.target.closest('[data-wd4-profile-modal-close]');
    if (closeBtn) {
      e.preventDefault();
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
      closeModal();
    }
  });
})();
JS;

        // Attach after "main.js" so it runs once main is loaded.
        wp_add_inline_script( 'main', $script, 'after' );
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_bootstrap_profile_modal_inline', 101 );









/**
 * Core script tuning helpers (defer, preload, fallback).
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

if ( ! function_exists( 'wd4_resolve_core_script_src' ) ) {
    function wd4_resolve_core_script_src(): string {
        $handle     = 'foxiz-core';
        $wp_scripts = wp_scripts();
        $src        = '';

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
            $config = wd4_js_get_script_config( $handle );
            $src    = $config['src'] ?? '';

            if ( $src ) {
                $ver = wd4_js_get_script_version( $handle, wd4_js_detect_view_context() );
                if ( $ver && false === strpos( $src, '?ver=' ) ) {
                    $src = add_query_arg( 'ver', $ver, $src );
                }
            }
        }

        return $src;
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

        printf(
            '<link rel="preload" as="script" href="%s" fetchpriority="high" />' . "\n",
            esc_url( wd4_resolve_core_script_src() )
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

if ( ! function_exists( 'wd4_js_ensure_core_script_enqueued' ) ) {
    function wd4_js_ensure_core_script_enqueued(): void {
        if ( is_admin() || wd4_js_is_login_view() ) {
            return;
        }

        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return;
        }

        $context = wd4_js_detect_view_context();
        if ( ! in_array( $context, array( 'post', 'page' ), true ) ) {
            return;
        }

        if ( wp_script_is( 'foxiz-core', 'enqueued' ) || wp_script_is( 'foxiz-core', 'to_print' ) || wp_script_is( 'foxiz-core', 'done' ) ) {
            return;
        }

        wd4_js_enqueue_script_for_context( 'foxiz-core', $context );
    }
}
add_action( 'wp_print_scripts', 'wd4_js_ensure_core_script_enqueued', 5 );
add_action( 'wp_print_footer_scripts', 'wd4_js_ensure_core_script_enqueued', 5 );

if ( ! function_exists( 'wd4_print_core_script_fallback' ) ) {
    function wd4_print_core_script_fallback(): void {
        if ( is_admin() || wd4_js_is_login_view() ) {
            return;
        }

        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return;
        }

        $context = wd4_js_detect_view_context();
        if ( ! in_array( $context, array( 'post', 'page' ), true ) ) {
            return;
        }

        if ( wp_script_is( 'foxiz-core', 'done' ) || wp_script_is( 'foxiz-core', 'to_print' ) ) {
            return;
        }

        $payload = wd4_get_core_script_inline_payload();
        if ( '' !== $payload && function_exists( 'wp_print_inline_script_tag' ) ) {
            echo wp_print_inline_script_tag( $payload, array( 'id' => 'foxiz-core-js' ) );
            return;
        }

        if ( '' !== $payload ) {
            printf( '<script id="foxiz-core-js">%s</script>' . "\n", $payload );
            return;
        }

        printf(
            '<script id="foxiz-core-js" defer fetchpriority="high" src="%s"></script>' . "\n",
            esc_url( wd4_resolve_core_script_src() )
        );
    }
}
add_action( 'wp_footer', 'wd4_print_core_script_fallback', 120 );

add_filter(
    'script_loader_tag',
    static function ( string $tag, string $handle ): string {
        if ( 'foxiz-core' === $handle ) {
            return wd4_enforce_core_script_priorities( $tag );
        }

        return $tag;
    },
    25,
    2
);

/**
 * Queue pruning.
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
            }
        }
    }
}

if ( ! function_exists( 'wd4_js_disable_all_js_except_whitelisted' ) ) {
    function wd4_js_disable_all_js_except_whitelisted(): void {
        $doing_ajax = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();
        if ( is_admin() || $doing_ajax || wd4_js_is_login_view() ) {
            return;
        }

        $context = wd4_js_detect_view_context();
        $targets = array( 'home', 'category', 'search', 'author', 'post', 'page' );
        if ( ! in_array( $context, $targets, true ) ) {
            return;
        }

        $allowed = wd4_js_get_allowed_handles_by_context( $context );
        wd4_js_prune_script_queue( $allowed );

        add_action(
            'wp_print_scripts',
            static function () use ( $allowed ): void {
                wd4_js_prune_script_queue( $allowed );
            },
            PHP_INT_MAX
        );

        add_action(
            'wp_print_footer_scripts',
            static function () use ( $allowed ): void {
                wd4_js_prune_script_queue( $allowed );
            },
            PHP_INT_MAX
        );
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_js_disable_all_js_except_whitelisted', PHP_INT_MAX );

if ( ! function_exists( 'my_disable_all_js_except_whitelisted' ) ) {
    function my_disable_all_js_except_whitelisted(): void {
        wd4_js_disable_all_js_except_whitelisted();
    }
}

/**
 * Strip legacy jQuery payloads on the front-end.
 */
if ( ! function_exists( 'wd4_js_remove_frontend_jquery' ) ) {
    function wd4_js_remove_frontend_jquery(): void {
        if ( is_admin() ) {
            return;
        }

        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return;
        }

        $handles = array( 'jquery', 'jquery-core', 'jquery-migrate' );

        foreach ( $handles as $handle ) {
            wp_dequeue_script( $handle );
            wp_deregister_script( $handle );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_js_remove_frontend_jquery', PHP_INT_MAX - 1 );
add_action( 'wp_print_scripts', 'wd4_js_remove_frontend_jquery', PHP_INT_MAX );
add_action( 'wp_print_footer_scripts', 'wd4_js_remove_frontend_jquery', PHP_INT_MAX );

/**
 * On the front-end login page, strip Foxiz/theme JS so only core + reCAPTCHA remain.
 */
if ( ! function_exists( 'wd4_js_strip_theme_js_on_login' ) ) {
    function wd4_js_strip_theme_js_on_login(): void {
        if ( is_admin() || ! wd4_js_is_login_view() ) {
            return;
        }

        $block = array(
            'foxiz-core',
            'main',
            'lazy',
            'pagination',
            'comment',
            'download',
            'tw-facade',
            'wd-defer-css',
            'wd-comment-toggle',
            'wns-download-validation',
            'swv',
            'contact-form-7',
            'wp-hooks',
            'wp-i18n',
        );

        foreach ( $block as $handle ) {
            wp_dequeue_script( $handle );
            wp_deregister_script( $handle );
        }
    }
}
add_action( 'wp_print_scripts', 'wd4_js_strip_theme_js_on_login', PHP_INT_MAX );
add_action( 'wp_print_footer_scripts', 'wd4_js_strip_theme_js_on_login', PHP_INT_MAX );
