<?php
/**
 * Plugin Name: MU – Viewport Guard
 * Description: Ensures every front-end response ships with a mobile-optimized viewport meta tag.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mu_viewport_guard_is_front_html() {
    if ( is_admin() ) {
        return false;
    }

    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return false;
    }

    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return false;
    }

    if ( function_exists( 'is_feed' ) && is_feed() ) {
        return false;
    }

    if ( function_exists( 'is_embed' ) && is_embed() ) {
        return false;
    }

    return true;
}

function mu_viewport_guard_start_buffer() {
    if ( ! mu_viewport_guard_is_front_html() ) {
        return;
    }

    ob_start( function ( $html ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        if ( stripos( $html, '<meta' ) !== false && preg_match( '~<meta\s+name="viewport"~i', $html ) ) {
            return $html;
        }

        $insertion = "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, viewport-fit=cover\" />\n";

        if ( false !== stripos( $html, '</head>' ) ) {
            return preg_replace( '~</head>~i', $insertion . '</head>', $html, 1 );
        }

        return $html . $insertion;
    } );
}
add_action( 'template_redirect', 'mu_viewport_guard_start_buffer', 0 );