<?php
/**
 * Plugin Name: WD4 Font & Resource Optimizations
 * Description: Google Fonts + preconnect / resource hints moved out of the child theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Master switch for deferred styles (off by default to prevent late HTML
 * mutations that trigger style recalculation bursts).
 */
if ( ! defined( 'WD4_DEFER_STYLES_ENABLED' ) ) {
    define( 'WD4_DEFER_STYLES_ENABLED', false );
}

/**
 * Gatekeeper that lets us short-circuit any Google Fonts requests. The
 * default returns false so headings fall back to fast system fonts, but a
 * filter can re-enable the remote styles if we ever need them again.
 */
if ( ! function_exists( 'wd4_should_load_remote_fonts' ) ) {
    function wd4_should_load_remote_fonts(): bool {
        return (bool) apply_filters( 'wd4_should_load_remote_fonts', false );
    }
}

/**
 * Ensure remote Google Fonts fall back to system fonts immediately while the
 * custom font files are still downloading.
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

/**
 * Stop the parent theme from requesting remote Google Fonts so the hero title
 * can render with local system fonts immediately.
 */
function wd4_disable_remote_google_fonts(): void {
    // Only adjust on the front end; wd4_is_front_context() is defined in the theme.
    if ( function_exists( 'wd4_is_front_context' ) && ! wd4_is_front_context() ) {
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
 * -------------------------------------------------------------------------
 * Resource hints helpers (preconnect only)
 * -------------------------------------------------------------------------
 */

function wd4_get_hint_href( $hint ): string {
    if ( is_array( $hint ) && isset( $hint['href'] ) ) {
        return trim( (string) $hint['href'] );
    }
    if ( is_string( $hint ) ) {
        return trim( $hint );
    }
    return '';
}

function wd4_extract_origin( string $url ): string {
    $parts = wp_parse_url( $url );
    if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
        return '';
    }
    $origin = strtolower( $parts['scheme'] . '://' . $parts['host'] );
    if ( isset( $parts['port'] ) ) {
        $origin .= ':' . $parts['port'];
    }
    return $origin;
}

function wd4_normalize_resource_hint_entry( $entry ): array {
    if ( is_array( $entry ) ) {
        $href = wd4_get_hint_href( $entry );
        if ( '' === $href ) {
            return array();
        }
        $normalized = array( 'href' => $href );
        if ( isset( $entry['as'] ) ) {
            $as = trim( (string) $entry['as'] );
            if ( '' !== $as ) {
                $normalized['as'] = $as;
            }
        }
        if ( isset( $entry['type'] ) ) {
            $type = trim( (string) $entry['type'] );
            if ( '' !== $type ) {
                $normalized['type'] = $type;
            }
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
    if ( '' === $href ) {
        return array();
    }

    return array( 'href' => $href );
}

function wd4_filter_out_resource_hints( array $urls, array $candidates ): array {
    if ( empty( $urls ) || empty( $candidates ) ) {
        return $urls;
    }

    $remove = array();
    foreach ( $candidates as $candidate ) {
        $entry = wd4_normalize_resource_hint_entry( $candidate );
        if ( empty( $entry['href'] ) ) {
            continue;
        }
        $remove[ $entry['href'] ] = true;
    }

    if ( empty( $remove ) ) {
        return $urls;
    }

    $filtered = array();
    foreach ( $urls as $url ) {
        $href = wd4_get_hint_href( $url );
        if ( '' !== $href && isset( $remove[ $href ] ) ) {
            continue;
        }
        $filtered[] = $url;
    }

    return $filtered;
}

/**
 * Optional helper to strip icon font preloads, kept here with other font helpers.
 */
function wd4_strip_icon_font_preloads( array $urls ): array {
    if ( empty( $urls ) ) {
        return $urls;
    }

    $filtered = array();

    foreach ( $urls as $url ) {
        $href = wd4_get_hint_href( $url );

        if ( '' !== $href && false !== stripos( $href, '/foxiz/assets/fonts/icons.woff2' ) ) {
            continue;
        }

        $filtered[] = $url;
    }

    return $filtered;
}

function wd4_collect_preconnect_origins(): array {
    static $cache = null;
    if ( null !== $cache ) {
        return $cache;
    }

    if ( is_admin() || is_feed() ) {
        $cache = array();
        return $cache;
    }
    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        $cache = array();
        return $cache;
    }
    if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
        $cache = array();
        return $cache;
    }

    $origins     = array();
    $home_origin = wd4_extract_origin( home_url( '/' ) );
    if ( $home_origin ) {
        $origins[] = $home_origin;
    }

    // Fonts — base preconnect.
    $origins[] = 'https://fonts.googleapis.com';
    $origins[] = array(
        'href'        => 'https://fonts.gstatic.com',
        'crossorigin' => 'anonymous',
    );

    // Fonts — if remote fonts explicitly enabled via filter.
    if ( wd4_should_load_remote_fonts() ) {
        $origins[] = 'https://fonts.googleapis.com';
        $origins[] = array(
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        );
    }

    // Defer CSS helper (e.g., Cloudflare)
    if ( function_exists( 'wd4_should_defer_styles' ) && wd4_should_defer_styles() ) {
        $origins[] = 'https://cloudflare.com';
    }

    $origins = apply_filters( 'wd4_preconnect_origins', $origins );

    $normalized = array();
    foreach ( (array) $origins as $origin ) {
        $entry = wd4_normalize_resource_hint_entry( $origin );
        if ( empty( $entry['href'] ) ) {
            continue;
        }
        $normalized[ $entry['href'] ] = $entry;
    }

    $cache = array_values( $normalized );
    return $cache;
}

function wd4_add_resource_hints( array $urls, string $relation_type ): array {
    if ( is_admin() || is_feed() ) {
        return $urls;
    }
    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return $urls;
    }
    if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
        return $urls;
    }

    if ( 'preconnect' === $relation_type ) {
        $urls = wd4_filter_out_resource_hints( $urls, wd4_collect_preconnect_origins() );
    }

    return $urls;
}
add_filter( 'wp_resource_hints', 'wd4_add_resource_hints', 10, 2 );

function wd4_output_preconnect_links(): void {
    $origins = wd4_collect_preconnect_origins();
    if ( empty( $origins ) ) {
        return;
    }

    foreach ( $origins as $origin ) {
        $href        = $origin['href'];
        $crossorigin = isset( $origin['crossorigin'] )
            ? sprintf( " crossorigin='%s'", esc_attr( $origin['crossorigin'] ) )
            : '';
        printf(
            "<link rel='preconnect' href='%s'%s>\n",
            esc_url( $href ),
            $crossorigin
        );
    }
}
add_action( 'wp_head', 'wd4_output_preconnect_links', 3 );
