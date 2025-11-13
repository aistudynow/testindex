<?php
/**
 * Plugin Name: WD CSS Optimizations
 * Description: Moves inline CSS and stylesheet deferral helpers into a mu-plugin for reuse.
 */

defined( 'ABSPATH' ) || exit;



/**
 * Replace newsletter download email gates with a login CTA for guests and
 * streamline the form for members so they can trigger downloads in one click.
 */
function wd4_transform_download_forms( string $content ): string {
    if ( ! wd4_is_front_context() ) {
        return $content;
    }

    if ( ! is_singular() ) {
        return $content;
    }

    if ( false === stripos( $content, 'download-form' ) ) {
        return $content;
    }

    if ( ! class_exists( 'DOMDocument' ) ) {
        return $content;
    }

    $libxml_previous_state = libxml_use_internal_errors( true );

    $dom           = new DOMDocument( '1.0', 'UTF-8' );
    $wrapped       = '<div>' . $content . '</div>';
    $loaded        = $dom->loadHTML( '<?xml encoding="UTF-8"?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    $login_url     = wp_login_url( get_permalink() );
    $login_url_attr = esc_url_raw( $login_url );

    if ( ! $loaded ) {
        libxml_clear_errors();
        if ( null !== $libxml_previous_state ) {
            libxml_use_internal_errors( $libxml_previous_state );
        }
        return $content;
    }

    $xpath = new DOMXPath( $dom );
    $forms = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' download-form ')]" );

    if ( ! $forms || 0 === $forms->length ) {
        libxml_clear_errors();
        if ( null !== $libxml_previous_state ) {
            libxml_use_internal_errors( $libxml_previous_state );
        }
        return $content;
    }

    $logged_in     = is_user_logged_in();
    $current_email = '';
    $host_fallback = (string) parse_url( home_url(), PHP_URL_HOST );

    if ( $logged_in ) {
        $user = wp_get_current_user();
        if ( $user instanceof WP_User && ! empty( $user->user_email ) ) {
            $current_email = sanitize_email( $user->user_email );
        }
    }

    if ( '' === $current_email ) {
        $sanitized_host = preg_replace( '/[^a-z0-9.\-]+/i', '', $host_fallback );
        $current_email  = $sanitized_host ? 'member@' . $sanitized_host : 'member@example.com';
    }

    foreach ( $forms as $form ) {
        if ( ! $form instanceof DOMElement ) {
            continue;
        }

        if ( $logged_in ) {
            $form->setAttribute( 'class', trim( $form->getAttribute( 'class' ) . ' download-form-logged-in' ) );

            $email_input = $xpath->query( ".//input[@name='EMAIL']", $form )->item( 0 );

            if ( $email_input instanceof DOMElement ) {
                $email_input->setAttribute( 'type', 'hidden' );
                $email_input->setAttribute( 'value', $current_email );
                $email_input->removeAttribute( 'placeholder' );
                $email_input->removeAttribute( 'required' );
                if ( ! $email_input->hasAttribute( 'autocomplete' ) ) {
                    $email_input->setAttribute( 'autocomplete', 'email' );
                }
            } else {
                $hidden = $dom->createElement( 'input' );
                $hidden->setAttribute( 'type', 'hidden' );
                $hidden->setAttribute( 'name', 'EMAIL' );
                $hidden->setAttribute( 'value', $current_email );
                $form->appendChild( $hidden );
            }

            $post_input  = $xpath->query( ".//input[@name='postId']", $form )->item( 0 );
            $block_input = $xpath->query( ".//input[@name='blockId']", $form )->item( 0 );

            $form_post_id = 0;
            if ( $post_input instanceof DOMElement ) {
                $form_post_id = (int) $post_input->getAttribute( 'value' );
            }

            $form_block_id = '';
            if ( $block_input instanceof DOMElement ) {
                $form_block_id = trim( (string) $block_input->getAttribute( 'value' ) );
            }

            $direct_url = '';
            if ( $form_post_id > 0 && function_exists( 'wns_resolve_file_url' ) ) {
                $resolved = wns_resolve_file_url( $form_post_id, $form_block_id );
                if ( is_string( $resolved ) && '' !== $resolved ) {
                    $direct_url = esc_url_raw( $resolved );
                }
            }

            if ( '' !== $direct_url ) {
                $form->setAttribute( 'data-direct-download-url', $direct_url );

                $filename = '';
                $path     = (string) parse_url( $direct_url, PHP_URL_PATH );
                if ( '' !== $path ) {
                    $filename = sanitize_file_name( wp_basename( $path ) );
                }

                if ( '' !== $filename ) {
                    $form->setAttribute( 'data-direct-download-filename', $filename );
                }

                $file_input = $xpath->query( ".//input[@name='fileUrl']", $form )->item( 0 );
                if ( $file_input instanceof DOMElement ) {
                    $file_input->setAttribute( 'type', 'hidden' );
                    $file_input->setAttribute( 'value', $direct_url );
                } else {
                    $file_hidden = $dom->createElement( 'input' );
                    $file_hidden->setAttribute( 'type', 'hidden' );
                    $file_hidden->setAttribute( 'name', 'fileUrl' );
                    $file_hidden->setAttribute( 'value', $direct_url );
                    $form->appendChild( $file_hidden );
                }
            }

            $submit = $xpath->query( ".//input[@type='submit']", $form )->item( 0 );
            if ( $submit instanceof DOMElement ) {
                $submit->setAttribute( 'type', 'submit' );
                if ( ! $submit->hasAttribute( 'value' ) || '' === trim( $submit->getAttribute( 'value' ) ) ) {
                    $submit->setAttribute( 'value', 'Download Now' );
                }
            }

            continue;
        }

        $existing_class = $form->getAttribute( 'class' );
        $form->setAttribute( 'class', trim( $existing_class . ' requires-login' ) );
        $form->setAttribute( 'data-requires-login', '1' );
        $form->setAttribute( 'data-login-url', $login_url_attr );

        $email_inputs = $xpath->query( ".//input[@name='EMAIL']", $form );
        if ( $email_inputs ) {
            foreach ( $email_inputs as $email_input ) {
                if ( $email_input instanceof DOMNode && $email_input->parentNode ) {
                    $email_input->parentNode->removeChild( $email_input );
                }
            }
        }

        $submit = $xpath->query( ".//input[@type='submit' or @type='button']", $form )->item( 0 );
        $login_label = 'Log in to Download';

        $login_link = $dom->createElement( 'a', $login_label );
        $login_link->setAttribute( 'href', $login_url_attr );
        $login_link->setAttribute( 'class', 'download-login-button' );
        $login_link->setAttribute( 'rel', 'nofollow noopener' );
        $login_link->setAttribute( 'data-login-url', $login_url_attr );
        $login_link->setAttribute( 'role', 'button' );

        if ( $submit instanceof DOMElement && $submit->parentNode ) {
            $existing_classes = trim( $submit->getAttribute( 'class' ) );
            if ( '' !== $existing_classes ) {
                $login_link->setAttribute(
                    'class',
                    trim( $existing_classes . ' download-login-button' )
                );
            }

            $submit->parentNode->replaceChild( $login_link, $submit );
        } else {
            $form->appendChild( $login_link );
        }

        $notice = $xpath->query( ".//*[contains(concat(' ', normalize-space(@class), ' '), ' notice-text ')]", $form )->item( 0 );
        if ( $notice instanceof DOMElement ) {
            while ( $notice->firstChild ) {
                $notice->removeChild( $notice->firstChild );
            }
            $notice->appendChild( $dom->createTextNode( 'Please log in to download this file.' ) );
        }
    }

    $output = '';
    $container = $dom->getElementsByTagName( 'div' )->item( 0 );
    if ( $container instanceof DOMNode ) {
        foreach ( $container->childNodes as $child ) {
            $output .= $dom->saveHTML( $child );
        }
    }

    libxml_clear_errors();
    if ( null !== $libxml_previous_state ) {
        libxml_use_internal_errors( $libxml_previous_state );
    }

    return $output ?: $content;
}
add_filter( 'the_content', 'wd4_transform_download_forms', 25 );


