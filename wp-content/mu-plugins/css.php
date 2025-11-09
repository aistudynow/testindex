<?php
/**
 * Allow the block editor preview iframe to load by relaxing the CSP frame-src directive.
 * (CSP logic itself can live elsewhere; this file is focused on theme CSS + login helpers.)
 */

defined( 'ABSPATH' ) || exit;

/**
 * -------------------------------------------------------------------------
 * Global config & flags
 * -------------------------------------------------------------------------
 */

// Front-end login page ID (0 = auto-detect by slug/template).
defined( 'WD_LOGIN_PAGE_ID' ) || define( 'WD_LOGIN_PAGE_ID', 0 );

// Global flag: are we on the front-end login page (e.g. /login-3/)?
$GLOBALS['wd4_is_login_view'] = false;



add_filter( 'login_url', function ( string $login_url, string $redirect, bool $force_reauth ): string {
    if ( is_admin() ) {
        return $login_url;
    }

    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return $login_url;
    }

    if ( false === stripos( $login_url, 'redirect_to=' ) ) {
        return $login_url;
    }

    $cleaned = remove_query_arg( 'redirect_to', $login_url );

    return is_string( $cleaned ) ? $cleaned : $login_url;
}, 20, 3 );




/**
 * -------------------------------------------------------------------------
 * Kill Foxiz CSS so we can fully control styles
 * -------------------------------------------------------------------------
 */

function wd4_kill_foxiz_css(): void {
    foreach ( [ 'foxiz-main-css', 'foxiz-main', 'foxiz-style', 'foxiz-global' ] as $handle ) {
        wp_dequeue_style( $handle );
        wp_deregister_style( $handle );
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_kill_foxiz_css', 1000 );


/**
 * -------------------------------------------------------------------------
 * Page helpers
 * -------------------------------------------------------------------------
 */

/**
 * Get current queried page/post ID (with safe fallback to global $post).
 */
function wd4_get_current_page_id(): int {
    $id = get_queried_object_id();
    if ( $id ) {
        return (int) $id;
    }

    global $post;
    return ( is_object( $post ) && isset( $post->ID ) ) ? (int) $post->ID : 0;
}

/**
 * Get current page slug (post_name) or empty string.
 */
function wd4_get_current_page_slug(): string {
    $page_id = wd4_get_current_page_id();
    if ( ! $page_id ) {
        return '';
    }

    $slug = get_post_field( 'post_name', $page_id );
    return is_string( $slug ) ? $slug : '';
}

/**
 * Detect if current view is our *front-end* login page (not /wp-login.php).
 */
function wd4_is_front_login_page(): bool {
    // 1) Explicit page template.
    if ( is_page_template( 'login.php' ) ) {
        return true;
    }

    $page_id = wd4_get_current_page_id();
    $slug    = wd4_get_current_page_slug();

    // 2) Hard-coded page ID.
    if ( WD_LOGIN_PAGE_ID && $page_id && (int) WD_LOGIN_PAGE_ID === $page_id ) {
        return true;
    }

    // 3) Common login slugs.
    if ( $slug && in_array( $slug, [ 'login', 'login-3', 'sign-in' ], true ) ) {
        return true;
    }

    // 4) Allow themes/plugins to override.
    return (bool) apply_filters( 'wd4_is_front_login_page', false, $page_id, $slug );
}

/**
 * Compute the $wd4_is_login_view flag once per request, after the main query is ready.
 */



function wd4_bootstrap_login_flag(): void {
    $GLOBALS['wd4_is_login_view'] = wd4_is_front_login_page();
}
add_action( 'wp', 'wd4_bootstrap_login_flag' );





/**
 * Determine whether the current request should be treated as the front-end login view.
 *
 * The `$wd4_is_login_view` flag is normally populated on the `wp` hook, but some logic
 * (like CSS enqueues) executes earlier. Those early hooks can call this helper to
 * eagerly detect the login request and prime the global flag so the rest of the
 * pipeline sees a consistent state.
 */
function wd4_is_login_view_context(): bool {
    global $wd4_is_login_view;

    if ( $wd4_is_login_view ) {
        return true;
    }

    $is_login_view = wd4_is_front_login_page();

    if ( ! $is_login_view ) {
        $request_path = '';

        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $request_path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
        }

        if ( '' !== $request_path ) {
            $segments   = array_filter( array_map( 'trim', explode( '/', trim( $request_path, '/' ) ) ) );
            $last_segment = $segments ? end( $segments ) : '';

            if ( $last_segment && in_array( $last_segment, array( 'login', 'login-3', 'sign-in' ), true ) ) {
                $is_login_view = true;
            }
        }
    }

    if ( $is_login_view ) {
        $wd4_is_login_view = true;
    }

    return $is_login_view;
}






/**
 * -------------------------------------------------------------------------
 * Pretty login URL helpers
 * -------------------------------------------------------------------------
 */

/**
 * Get the pretty front-end login URL (cached per request).
 */
function wd4_get_front_login_url(): string {
    static $cached = null;

    if ( null !== $cached ) {
        return $cached;
    }

    $page_id = WD_LOGIN_PAGE_ID ? (int) WD_LOGIN_PAGE_ID : 0;

    // If we are *on* the login page, use that ID.
    if ( ! $page_id && wd4_is_front_login_page() ) {
        $page_id = wd4_get_current_page_id();
    }

    // Try specific slug "login-3".
    if ( ! $page_id ) {
        $maybe_login = get_page_by_path( 'login-3' );
        if ( is_object( $maybe_login ) && isset( $maybe_login->ID ) ) {
            $page_id = (int) $maybe_login->ID;
        }
    }

    // Fallback to generic candidate slugs.
    if ( ! $page_id ) {
        foreach ( [ 'login', 'sign-in' ] as $slug ) {
            $page = get_page_by_path( $slug );
            if ( is_object( $page ) && isset( $page->ID ) ) {
                $page_id = (int) $page->ID;
                break;
            }
        }
    }

     // If we found a page, use its permalink.
    if ( $page_id ) {
        $permalink = get_permalink( $page_id );
        if ( $permalink ) {
            return $cached = $permalink;
        }
    }

    // Hard fallback to /login-3/ or site root.
    $fallback = home_url( '/login-3/' );
    if ( ! $fallback ) {
        $fallback = home_url( '/' );
    }

    return $cached = $fallback;
}

/**
 * Force login pages to always use login.php template when we are on the front login view.
 */
function wd4_force_login_template( string $template ): string {
    if ( is_admin() || ! is_page() || ! wd4_is_login_view_context() ) {
        return $template;
    }

    $override = locate_template( 'login.php' );
    return $override ?: $template;
}
add_filter( 'template_include', 'wd4_force_login_template', 99 );

/**
 * Canonicalize login URLs when ?redirect_to is present: redirect to pretty login URL.
 */
function wd4_canonicalize_front_login_request(): void {
    if ( ! wd4_is_login_view_context() || empty( $_GET['redirect_to'] ) ) {
        return;
    }

    $target = wd4_get_front_login_url();
    if ( ! $target ) {
        return;
    }

    wp_safe_redirect( $target, 301 );
    exit;
}
add_action( 'template_redirect', 'wd4_canonicalize_front_login_request', 1 );

/**
 * Add login-specific body classes on front login view.
 */
function wd4_login_body_class( array $classes ): array {
    if ( wd4_is_login_view_context() ) {
        if ( ! in_array( 'wd4-login-template', $classes, true ) ) {
            $classes[] = 'wd4-login-template';
        }
        if ( ! in_array( 'wd-login-page', $classes, true ) ) {
            $classes[] = 'wd-login-page';
        }
    }

    return $classes;
}
add_filter( 'body_class', 'wd4_login_body_class' );

/**
 * Replace core login_url() with our pretty front-end login URL.
 */
function wd4_force_pretty_login_url( string $login_url, string $redirect, bool $force_reauth ): string {
    unset( $redirect );

    $target = wd4_get_front_login_url();
    if ( ! $target ) {
        return $login_url;
    }

    // Strip any old redirect/reauth args from target.
    $clean = remove_query_arg( [ 'redirect_to', 'reauth' ], $target );

    if ( $force_reauth ) {
        $clean = add_query_arg( 'reauth', '1', $clean );
    }

    return $clean;
}
add_filter( 'login_url', 'wd4_force_pretty_login_url', 10, 3 );


/**
 * -------------------------------------------------------------------------
 * CSS meta helpers
 * -------------------------------------------------------------------------
 */

/**
 * Inline the contents of a child-theme CSS file into an existing enqueued handle.
 * (Used to inject login.css rules on top of the registered style.)
 */
function wd4_inline_child_stylesheet( string $handle, string $path ): void {
    if ( empty( $path ) || ! file_exists( $path ) ) {
        return;
    }

    $css = file_get_contents( $path );
    if ( false === $css || '' === $css ) {
        return;
    }

    wp_add_inline_style( $handle, $css );
}

/**
 * Get path/URL/version for a CSS file inside the *child theme*.
 * Example: css/login.css
 */
function wd4_get_child_stylesheet_meta( string $relative ): array {
    $relative = ltrim( $relative, '/' );
    $path     = trailingslashit( get_stylesheet_directory() ) . $relative;
    $uri      = trailingslashit( get_stylesheet_directory_uri() ) . $relative;

    // Version based on filemtime for automatic cache-busting.
    $version  = file_exists( $path ) ? (string) filemtime( $path ) : (string) wp_get_theme()->get( 'Version' );

    return [
        'path' => $path,
        'uri'  => $uri,
        'ver'  => $version,
    ];
}

/**
 * Get src/deps/ver for CSS files stored under: /wp-content/themes/css/
 * (Not the theme directory; this is your custom shared CSS folder.)
 */
function wd4_get_theme_style_meta( string $relative ): array {
    $relative = ltrim( $relative, '/' );

    $base_dir = trailingslashit( WP_CONTENT_DIR ) . 'themes/css/';
    $base_url = trailingslashit( content_url( 'themes/css' ) );

    $path = $base_dir . $relative;
    $src  = $base_url . $relative;

    // Auto version by filemtime; fallback to theme version.
    $ver  = file_exists( $path ) ? (string) filemtime( $path ) : (string) wp_get_theme()->get( 'Version' );

    return [
        'src'  => $src,
        'deps' => [],
        'ver'  => $ver,
    ];
}

/**
 * Central registry of all CSS handles + their files.
 * You only edit paths/deps/version logic here, not all over the code.
 */
function wd4_get_style_registry(): array {
    static $registry = null;

    if ( null !== $registry ) {
        return $registry;
    }

    $registry = [
        // Header/base
        'main'        => wd4_get_theme_style_meta( 'header/main.css' ),
        'front'       => wd4_get_theme_style_meta( 'header/front.css' ),
        'footer'      => wd4_get_theme_style_meta( 'header/footer.css' ),
        'pages'       => wd4_get_theme_style_meta( 'header/pages.css' ),
        'grid'        => wd4_get_theme_style_meta( 'header/grid.css' ),
        'fixgrid'     => wd4_get_theme_style_meta( 'header/fixgrid.css' ),
        'login-view'  => wd4_get_theme_style_meta( 'header/login.css' ),

        // Profile / account / author
        'profile'     => wd4_get_theme_style_meta( 'header/profile.css' ),

        // Single post pieces
        'single'      => wd4_get_theme_style_meta( 'header/single/single.css' ),
        'email'       => wd4_get_theme_style_meta( 'header/single/email.css' ),
        'download'    => wd4_get_theme_style_meta( 'header/single/download.css' ),
        'sharesingle' => wd4_get_theme_style_meta( 'header/single/sharesingle.css' ),
        'author'      => wd4_get_theme_style_meta( 'header/single/author.css' ),
        'comment'     => wd4_get_theme_style_meta( 'header/single/comment.css' ),
    ];

    // Example: profile.css depends on main.css.
    $registry['profile']['deps'] = [ 'main' ];

    return $registry;
}

/**
 * Enqueue a style by handle using the registry above.
 */
function wd4_enqueue_theme_style( string $handle ): void {
    $styles = wd4_get_style_registry();

    if ( empty( $styles[ $handle ] ) ) {
        return;
    }

    $s = $styles[ $handle ];
    wp_enqueue_style( $handle, $s['src'], $s['deps'], $s['ver'] );
}


/**
 * -------------------------------------------------------------------------
 * View helpers (account / author)
 * -------------------------------------------------------------------------
 */

/**
 * Treat WooCommerce account page and author archives as "account views".
 * Both will use profile.css.
 */
function wd4_is_account_view(): bool {
    $is_wc_account   = function_exists( 'is_account_page' ) && is_account_page();
    $is_author       = is_author();
    $is_account_view = $is_wc_account || $is_author;

    return (bool) apply_filters( 'wd4_is_account_view', $is_account_view );
}


/**
 * -------------------------------------------------------------------------
 * Main enqueue logic (decides which CSS loads on which view)
 * -------------------------------------------------------------------------
 */

function wd4_enqueue_styles(): void {
    $is_login_view = wd4_is_login_view_context();

    // 1) Front login page (pretty URL, not /wp-login.php)
    if ( $is_login_view ) {


        wd4_enqueue_theme_style( 'main' );
        wd4_enqueue_theme_style( 'login-view' );
        wd4_enqueue_theme_style( 'footer' );

        
        return; // Stop here; login view uses a minimal bundle.
    }

    // 2) Account / author views (Woo account + /author/*)
     if ( wd4_is_account_view() ) {
          wd4_enqueue_theme_style( 'main' );
          wd4_enqueue_theme_style( 'profile' );
          wd4_enqueue_theme_style( 'footer' );
          return;
    }

    // 3) Front page / blog home
    if ( is_front_page() || is_home() ) {
        wd4_enqueue_theme_style( 'main' );
        wd4_enqueue_theme_style( 'front' );
        wd4_enqueue_theme_style( 'footer' );
    }

    // 4) Category archives
    if ( is_category() ) {
        wd4_enqueue_theme_style( 'main' );
        wd4_enqueue_theme_style( 'front' );
        wd4_enqueue_theme_style( 'footer' );
    }
  
   // 5) Static pages
    if ( is_page() ) {
        wd4_enqueue_theme_style( 'main' );
        wd4_enqueue_theme_style( 'pages' );
        wd4_enqueue_theme_style( 'footer' );
    }

    // 6) Single posts
    if ( is_singular( 'post' ) ) {
        wd4_enqueue_theme_style( 'main' );
        wd4_enqueue_theme_style( 'single' );
        wd4_enqueue_theme_style( 'email' );
        wd4_enqueue_theme_style( 'download' );
        wd4_enqueue_theme_style( 'sharesingle' );
        wd4_enqueue_theme_style( 'author' );
        wd4_enqueue_theme_style( 'comment' );
        wd4_enqueue_theme_style( 'footer' );
    }
  
  

    // 7) Search results
    if ( is_search() ) {
        wd4_enqueue_theme_style( 'main' );
        wd4_enqueue_theme_style( 'front' );
        wd4_enqueue_theme_style( 'grid' );
        wd4_enqueue_theme_style( 'fixgrid' );
        wd4_enqueue_theme_style( 'footer' );
    }
}
add_action( 'wp_enqueue_scripts', 'wd4_enqueue_styles', 20 );


/**
 * -------------------------------------------------------------------------
 * Prune extra styles on main views (keep CSS lean)
 * -------------------------------------------------------------------------
 */

function wd4_expand_allowed_handles_with_inline( array $handles ): array {
    $expanded = [];

    foreach ( $handles as $handle ) {
        $expanded[] = $handle;
        $expanded[] = $handle . '-inline-css';
    }

    return array_values( array_unique( $expanded ) );
}

function wd4_prune_styles(): void {
    if ( is_admin() || wd4_is_login_view_context() ) {
        return;
    }

    global $wp_styles;
    if ( ! ( $wp_styles instanceof WP_Styles ) ) {
        return;
    }

    // Handles we allow to remain enqueued on those views.
    
    $allowed = [];

    if ( wd4_is_account_view() ) {
        $allowed = [ 'main', 'profile', 'footer' ];
    } elseif ( is_front_page() || is_home() ) {
        $allowed = [ 'main', 'front', 'footer' ];
    } elseif ( is_category() ) {
        $allowed = [ 'main', 'front', 'footer' ];
    } elseif ( is_singular( 'post' ) ) {
        $allowed = [ 'main', 'single', 'email', 'download', 'sharesingle', 'author', 'sidebar', 'related', 'comment', 'footer' ];
    } elseif ( is_page() ) {
        $allowed = [ 'main', 'pages', 'footer' ];
    } elseif ( is_search() ) {
        $allowed = [ 'main', 'front', 'grid', 'fixgrid', 'footer' ];
    } else {
        return;
    }

    if ( is_user_logged_in() ) {
        $allowed[] = 'admin-bar';
    }

    $allowed = wd4_expand_allowed_handles_with_inline( $allowed );



    foreach ( (array) $wp_styles->queue as $handle ) {
        if ( ! in_array( $handle, $allowed, true ) ) {
            wp_dequeue_style( $handle );
            wp_deregister_style( $handle );
        }
    }
}
add_action( 'wp_print_styles', 'wd4_prune_styles', PHP_INT_MAX );

function wd4_prune_login_styles(): void {
    if ( is_admin() || ! wd4_is_login_view_context() ) {
        return;
    }

    global $wp_styles;
    if ( ! ( $wp_styles instanceof WP_Styles ) ) {
        return;
    }

    // Only allow these on /login-3/ etc.
     $allowed = [ 'main', 'login-view', 'footer' ];

    if ( is_user_logged_in() ) {
        $allowed[] = 'admin-bar';
    }

     $allowed = wd4_expand_allowed_handles_with_inline( $allowed );

    foreach ( (array) $wp_styles->queue as $handle ) {
        if ( ! in_array( $handle, $allowed, true ) ) {
            wp_dequeue_style( $handle );
            wp_deregister_style( $handle );
        }
    }
}
add_action( 'wp_print_styles', 'wd4_prune_login_styles', PHP_INT_MAX - 1 );
