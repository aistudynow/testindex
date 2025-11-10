<?php
/**
 * Plugin Name: WD CSS Optimizations
 * Description: Moves inline CSS and stylesheet deferral helpers into a mu-plugin for reuse.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WD4_INLINE_CSS_ENABLED' ) ) {
    define( 'WD4_INLINE_CSS_ENABLED', true );
}

if ( ! function_exists( 'wd4_should_defer_styles' ) ) {
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
}

if ( ! function_exists( 'wd4_is_frontend_request' ) ) {
    function wd4_is_frontend_request(): bool {
        if ( is_admin() ) return false;
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return false;
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) return false;
        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) return false;
        return true;
    }
}

if ( ! function_exists( 'wd4_get_inline_styles_map' ) ) {
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
            'front'        => 'css/header/front.css',
            'login-view'   => 'css/header/login.css',
            'profile'   => 'css/header/profile.css',
            'infinity'   => 'css/header/category-archive.css',
        );
        return (array) apply_filters( 'wd4_inline_styles_map', $map );
    }
}

if ( ! function_exists( 'wd4_load_inline_stylesheet' ) ) {
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
}

if ( ! function_exists( 'wd4_inline_style_loader_tag' ) ) {
    function wd4_inline_style_loader_tag( string $html, string $handle, string $href, string $media ): string {
        // If feature disabled, return normal <link> tag
        if ( ! defined( 'WD4_INLINE_CSS_ENABLED' ) || ! WD4_INLINE_CSS_ENABLED ) {
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
}

if ( ! function_exists( 'wd4_get_deferred_style_handles' ) ) {
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
}

if ( ! function_exists( 'wd4_output_critical_css' ) ) {
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
}

if ( ! function_exists( 'wd4_filter_style_loader_tag' ) ) {
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
}

if ( ! function_exists( 'wd4_output_deferred_styles_noscript' ) ) {
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
}

if ( ! function_exists( 'wd4_enqueue_defer_css_script' ) ) {
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
}
