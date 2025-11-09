<?php
/**
 * Allow the block editor preview iframe to load by relaxing the CSP frame-src directive.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wd4_csp_allow_blob_frame_src' ) ) {
    /**
     * Append the blob: scheme to any frame-src directive inside the provided CSP value.
     */
    function wd4_csp_allow_blob_frame_src( string $policy ): string {
        if ( '' === $policy || false === stripos( $policy, 'frame-src' ) ) {
            return $policy;
        }

        return (string) preg_replace_callback(
            '/(frame-src\s+)([^;]+)/i',
            static function ( array $matches ): string {
                $sources = preg_split( '/\s+/', trim( $matches[2] ) );
                if ( ! is_array( $sources ) ) {
                    return $matches[0];
                }

                $sources = array_filter( $sources, static function ( $source ): bool {
                    return '' !== trim( (string) $source );
                } );

                $sources_lower = array_map( 'strtolower', $sources );

                if ( ! in_array( 'blob:', $sources_lower, true ) ) {
                    $sources[] = 'blob:';
                }

                return $matches[1] . implode( ' ', $sources );
            },
            $policy
        );
    }
}

if ( ! function_exists( 'wd4_csp_allow_blob_header_list' ) ) {
    /**
     * Inspect the outgoing header list and ensure any CSP directives allow blob iframes.
     */
    function wd4_csp_allow_blob_header_list(): void {
        if ( ! function_exists( 'headers_list' ) ) {
            return;
        }

        $existing_headers = headers_list();
        if ( empty( $existing_headers ) ) {
            return;
        }

        $targets      = array( 'content-security-policy', 'content-security-policy-report-only' );
        $replacements = array();

        foreach ( $existing_headers as $header_line ) {
            $segments = explode( ':', $header_line, 2 );
            if ( 2 !== count( $segments ) ) {
                continue;
            }

            $name  = trim( $segments[0] );
            $lower = strtolower( $name );

            if ( ! in_array( $lower, $targets, true ) ) {
                continue;
            }

            $value = trim( $segments[1] );
            if ( '' === $value ) {
                continue;
            }

            $replacements[ $lower ] = array(
                'name'  => $name,
                'value' => wd4_csp_allow_blob_frame_src( $value ),
            );
        }

        if ( empty( $replacements ) ) {
            return;
        }

        foreach ( $replacements as $lower => $data ) {
            header_remove( $lower );
            header( $data['name'] . ': ' . $data['value'], true );
        }
    }
}

if ( ! function_exists( 'wd4_csp_allow_blob_wp_headers' ) ) {
    /**
     * Filter the headers array before it is sent to the client.
     */
    function wd4_csp_allow_blob_wp_headers( array $headers ): array {
        foreach ( array( 'Content-Security-Policy', 'Content-Security-Policy-Report-Only' ) as $header_name ) {
            if ( isset( $headers[ $header_name ] ) && is_string( $headers[ $header_name ] ) ) {
                $headers[ $header_name ] = wd4_csp_allow_blob_frame_src( $headers[ $header_name ] );
            }
        }

        return $headers;
    }
}

add_action( 'send_headers', 'wd4_csp_allow_blob_header_list', PHP_INT_MAX );
add_filter( 'wp_headers', 'wd4_csp_allow_blob_wp_headers', PHP_INT_MAX );