<?php
/**
 * Plugin Name: MU – Kill Ruby Icon Font
 * Description: Stops the Foxiz/Ruby icon font (icons.css / ruby-icon.css / icons.woff2) from loading so icons can be handled by custom CSS (e.g. SVG masks) instead. No markup rewriting.
 * Author: you
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend check: only run on real front-end page loads.
 */
if ( ! function_exists( 'mu_kill_ruby_is_frontend' ) ) {
    function mu_kill_ruby_is_frontend(): bool {
        if ( is_admin() ) {
            return false;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return false;
        }
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return false;
        }
        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return false;
        }
        return true;
    }
}

/**
 * Match icon helper stylesheets (various possible paths/names).
 */
if ( ! function_exists( 'mu_kill_ruby_is_icon_css_url' ) ) {
    function mu_kill_ruby_is_icon_css_url( string $url ): bool {
        return (bool) preg_match(
            '~(^|/)(icon-helpers\.css|ruby-icon\.css|shared/ruby-icon\.css|icons\.css|foxiz/assets/fonts/icons\.css|assets/fonts/icons\.css)(\?|$)~i',
            $url
        );
    }
}

/**
 * Match icon font files (icons.woff2 / ruby-icons.woff2).
 */
if ( ! function_exists( 'mu_kill_ruby_is_icon_font_url' ) ) {
    function mu_kill_ruby_is_icon_font_url( string $url ): bool {
        return (bool) preg_match(
            '~/fonts/(?:icons|ruby-icons)\.woff2?(\?|$)~i',
            $url
        );
    }
}

/**
 * 1) Prevent icon CSS from being printed (enqueued styles).
 */
add_filter(
    'style_loader_tag',
    function ( $html, $handle, $href, $media ) {
        if ( ! is_string( $href ) || $href === '' ) {
            return $html;
        }
        if ( ! mu_kill_ruby_is_icon_css_url( $href ) ) {
            return $html;
        }

        // Kill the <link rel="stylesheet" ...> tag completely.
        return '';
    },
    10,
    4
);

/**
 * 2) Dequeue common icon CSS handles defensively.
 */
add_action(
    'wp_enqueue_scripts',
    function () {
        foreach ( array(
            'ruby-icon',
            'ruby-icons',
            'icons-css',
            'foxiz-icons',
            'foxiz/assets/fonts/icons.css',
            'icon-helpers',
            'ruby-icon-css',
            'icons',
        ) as $h ) {
            wp_dequeue_style( $h );
            wp_deregister_style( $h );
        }
    },
    1000
);

/**
 * 3) Filter the final HTML output to scrub:
 *    - preloads to icons.woff2 / ruby-icons.woff2
 *    - <link rel="stylesheet"> to icon helper CSS
 *    - @font-face / @import for ruby-icon in inline <style>
 *    - inline style="font-family: ... ruby-icon ..."
 */
if ( ! function_exists( 'mu_kill_ruby_icon_filter_html' ) ) {

    function mu_kill_ruby_icon_filter_html( string $html ): string {
        if ( $html === '' ) {
            return $html;
        }

        // Quick cheap check to skip work if nothing relevant is in the HTML.
        if (
            stripos( $html, 'ruby-icon' ) === false
            && stripos( $html, '/fonts/icons.' ) === false
            && stripos( $html, '/fonts/ruby-icons.' ) === false
            && stripos( $html, 'icons.css' ) === false
        ) {
            return $html;
        }

        // 3.1 Remove <link rel="preload" ... href=".../fonts/icons|ruby-icons.woff2"...>
        $html = preg_replace(
            '~<link[^>]*\brel\s*=\s*([\'"])preload\1[^>]*\bhref\s*=\s*([\'"])[^>"\'\s]*?/fonts/(?:icons|ruby-icons)\.woff2?[^>"\'\s]*\2[^>]*>~i',
            '',
            $html
        );

        // 3.2 Remove any stylesheet tag that points to an icon helper CSS.
        $html = preg_replace(
            '~<link\s+[^>]*rel=["\']stylesheet["\'][^>]*href=["\'][^"\']*(?:icon-helpers\.css|ruby-icon\.css|shared/ruby-icon\.css|icons\.css|foxiz/assets/fonts/icons\.css|assets/fonts/icons\.css)[^"\']*["\'][^>]*>\s*~i',
            '',
            $html
        );

        // 3.3 Strip inline @font-face for ruby-icon and @import of icon CSS from <style> tags.
        $html = preg_replace_callback(
            '~<style\b([^>]*)>(.*?)</style>~is',
            function ( $m ) {
                $attrs = $m[1];
                $css   = $m[2];

                // Kill imports to icon helper CSS.
                $css = preg_replace(
                    '~@import[^;]*?(icon-helpers|ruby-icon|icons)\.css[^;]*;~i',
                    '',
                    $css
                );

                // Kill @font-face blocks that declare the ruby icon font.
                $css = preg_replace(
                    '~@font-face\s*\{[^{}]*?font-family\s*:\s*(["\'])?\s*ruby-icon\s*\1?[^{}]*\}~i',
                    '',
                    $css
                );

                // Remove any font-family: ... ruby-icon ...; declarations.
                $css = preg_replace(
                    '~font-family\s*:\s*[^;{}]*\bruby-icon\b[^;{}]*;~i',
                    '',
                    $css
                );

                return '<style' . $attrs . '>' . $css . '</style>';
            },
            $html
        );

        // 3.4 Clean inline style="font-family: ruby-icon" occurrences.
        $html = preg_replace_callback(
            '~\sstyle\s*=\s*([\'"])(.*?)\1~is',
            function ( $m ) {
                $q   = $m[1];
                $css = $m[2];

                // Drop any font-family rules that reference ruby-icon.
                $css = preg_replace(
                    '~font-family\s*:\s*[^;{}]*\bruby-icon\b[^;{}]*;~i',
                    '',
                    $css
                );

                // Normalize whitespace.
                $css = trim( preg_replace( '/\s{2,}/', ' ', $css ) );

                return $css === ''
                    ? '' // no inline style left
                    : ' style=' . $q . $css . $q;
            },
            $html
        );

        return $html;
    }
}

/**
 * Start output buffering on frontend to run the HTML filter above.
 */
add_action(
    'template_redirect',
    function () {
        if ( ! mu_kill_ruby_is_frontend() ) {
            return;
        }

        ob_start( 'mu_kill_ruby_icon_filter_html' );
    },
    0
);

/**
 * 4) Remove resource hints that point to the icon fonts or icon CSS.
 */
add_filter(
    'wp_resource_hints',
    function ( array $urls, string $rel ) {
        if ( empty( $urls ) ) {
            return $urls;
        }

        $rel = strtolower( $rel );
        if ( ! in_array( $rel, array( 'preconnect', 'preload', 'prefetch' ), true ) ) {
            return $urls;
        }

        $out = array();

        foreach ( $urls as $u ) {
            $href = is_array( $u ) ? ( $u['href'] ?? '' ) : (string) $u;

            if ( $href === '' ) {
                $out[] = $u;
                continue;
            }

            if ( mu_kill_ruby_is_icon_font_url( $href ) ) {
                // Skip icon font URLs.
                continue;
            }

            if ( mu_kill_ruby_is_icon_css_url( $href ) ) {
                // Skip icon CSS URLs.
                continue;
            }

            $out[] = $u;
        }

        return $out;
    },
    10,
    2
);
