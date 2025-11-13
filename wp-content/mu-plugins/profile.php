<?php
/**
 * Plugin Name: WD4 Profile Module
 * Description: Front-end profile / avatar / settings logic moved out of the theme.
 */

defined( 'ABSPATH' ) || exit;





if ( ! function_exists( 'wd4_get_default_avatar_url' ) ) {
    function wd4_get_default_avatar_url( $user = null ) {
        $default_url = apply_filters( 'foxiz_default_profile_avatar', 'https://aistudynow.com/er.png', $user );

        return esc_url_raw( $default_url );
    }
}






if ( ! function_exists( 'wd4_profile_flash_key' ) ) {
    function wd4_profile_flash_key( int $user_id ): string {
        return 'wd4_profile_flash_' . $user_id;
    }
}

if ( ! function_exists( 'wd4_set_profile_flash' ) ) {
    function wd4_set_profile_flash( int $user_id, string $type, string $message ): void {
        if ( '' === trim( $message ) ) {
            return;
        }

        set_transient(
            wd4_profile_flash_key( $user_id ),
            array(
                'type'    => $type,
                'message' => $message,
            ),
            MINUTE_IN_SECONDS
        );
    }
}

if ( ! function_exists( 'wd4_consume_profile_flash' ) ) {
    function wd4_consume_profile_flash( int $user_id ): array {
        static $cache = array();

        if ( isset( $cache[ $user_id ] ) ) {
            return $cache[ $user_id ];
        }

        $flash = get_transient( wd4_profile_flash_key( $user_id ) );
        if ( is_array( $flash ) && ! empty( $flash['message'] ) ) {
            delete_transient( wd4_profile_flash_key( $user_id ) );
            $cache[ $user_id ] = $flash;

            return $flash;
        }

        $cache[ $user_id ] = array();

        return array();
    }
}

if ( ! function_exists( 'wd4_resolve_profile_redirect' ) ) {
    function wd4_resolve_profile_redirect(): string {
        $fallback = wp_get_referer();
        if ( ! $fallback ) {
            $fallback = home_url( '/' );
        }

        $posted_redirect = '';

        if ( isset( $_POST['wd4_profile_redirect'] ) ) {
            $posted_redirect = esc_url_raw( wp_unslash( $_POST['wd4_profile_redirect'] ) );
        } elseif ( isset( $_POST['wd4_avatar_redirect'] ) ) {
            $posted_redirect = esc_url_raw( wp_unslash( $_POST['wd4_avatar_redirect'] ) );
        }

        if ( '' === $posted_redirect ) {
            return wp_validate_redirect( $fallback, home_url( '/' ) );
        }

        return wp_validate_redirect( $posted_redirect, $fallback );
    }
}

if ( ! function_exists( 'wd4_handle_frontend_profile_forms' ) ) {
    function wd4_handle_frontend_profile_forms(): void {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $has_profile_nonce = isset( $_POST['wd4_profile_fields_nonce'] );
        $has_avatar_nonce  = isset( $_POST['wd4_author_avatar_nonce'] );

        if ( ! $has_profile_nonce && ! $has_avatar_nonce ) {
            return;
        }

        $user_id  = get_current_user_id();
        $redirect = wd4_resolve_profile_redirect();

        if ( $has_profile_nonce ) {
            $nonce = sanitize_text_field( wp_unslash( $_POST['wd4_profile_fields_nonce'] ) );

            if ( ! wp_verify_nonce( $nonce, 'wd4_update_profile_fields' ) ) {
                wd4_set_profile_flash( $user_id, 'error', __( 'We could not verify your request. Please try again.', 'foxiz-child' ) );
                wp_safe_redirect( $redirect );
                exit;
            }

            $new_first_name = isset( $_POST['wd4_profile_first_name'] )
                ? sanitize_text_field( wp_unslash( $_POST['wd4_profile_first_name'] ) )
                : '';

            $new_last_name = isset( $_POST['wd4_profile_last_name'] )
                ? sanitize_text_field( wp_unslash( $_POST['wd4_profile_last_name'] ) )
                : '';

            $new_short_name = isset( $_POST['wd4_profile_short_name'] )
                ? sanitize_text_field( wp_unslash( $_POST['wd4_profile_short_name'] ) )
                : '';

            update_user_meta( $user_id, 'first_name', $new_first_name );
            update_user_meta( $user_id, 'last_name', $new_last_name );
            update_user_meta( $user_id, 'wd4_short_display_name', $new_short_name );

            wd4_set_profile_flash( $user_id, 'success', __( 'Profile details updated.', 'foxiz-child' ) );

            wp_safe_redirect( $redirect );
            exit;
        }

        if ( ! $has_avatar_nonce ) {
            return;
        }

        $avatar_nonce = sanitize_text_field( wp_unslash( $_POST['wd4_author_avatar_nonce'] ) );

        if ( ! wp_verify_nonce( $avatar_nonce, 'wd4_update_author_avatar' ) ) {
            wd4_set_profile_flash( $user_id, 'error', __( 'We could not verify your request. Please try again.', 'foxiz-child' ) );
            wp_safe_redirect( $redirect );
            exit;
        }

        if ( empty( $_FILES['wd4_author_avatar']['name'] ) ) {
            wd4_set_profile_flash( $user_id, 'error', __( 'Please choose an image to upload.', 'foxiz-child' ) );
            wp_safe_redirect( $redirect );
            exit;
        }

        if ( ! current_user_can( 'upload_files' ) ) {
            wd4_set_profile_flash( $user_id, 'error', __( 'You do not have permission to upload files.', 'foxiz-child' ) );
            wp_safe_redirect( $redirect );
            exit;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $display_name = trim( (string) get_the_author_meta( 'display_name', $user_id ) );
        $post_title   = $display_name ? $display_name . ' avatar' : __( 'Author avatar', 'foxiz-child' );

        $attachment_id = media_handle_upload(
            'wd4_author_avatar',
            0,
            array(
                'post_title' => $post_title,
            )
        );

        if ( is_wp_error( $attachment_id ) ) {
            wd4_set_profile_flash( $user_id, 'error', $attachment_id->get_error_message() );
            wp_safe_redirect( $redirect );
            exit;
        }

        update_user_meta( $user_id, 'author_image_id', (int) $attachment_id );

        wd4_set_profile_flash( $user_id, 'success', __( 'Profile image updated.', 'foxiz-child' ) );

        wp_safe_redirect( $redirect );
        exit;
    }
}

add_action( 'init', 'wd4_handle_frontend_profile_forms' );







if ( ! function_exists( 'wd4_resolve_avatar_user' ) ) {
    function wd4_resolve_avatar_user( $id_or_email ) {
        if ( $id_or_email instanceof WP_User ) {
            return $id_or_email;
        }

        if ( $id_or_email instanceof WP_Post && ! empty( $id_or_email->post_author ) ) {
            return get_user_by( 'id', (int) $id_or_email->post_author );
        }

        if ( $id_or_email instanceof WP_Comment ) {
            if ( ! empty( $id_or_email->user_id ) ) {
                return get_user_by( 'id', (int) $id_or_email->user_id );
            }

            if ( ! empty( $id_or_email->comment_author_email ) ) {
                return get_user_by( 'email', $id_or_email->comment_author_email );
            }
        }

        if ( is_numeric( $id_or_email ) ) {
            return get_user_by( 'id', (int) $id_or_email );
        }

        if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
            return get_user_by( 'email', $id_or_email );
        }

        return null;
    }
}





if ( ! function_exists( 'wd4_is_placeholder_avatar_url' ) ) {
    function wd4_is_placeholder_avatar_url( $url ) {
        if ( empty( $url ) ) {
            return true;
        }

        $parsed_url = wp_parse_url( (string) $url );
        if ( empty( $parsed_url['host'] ) ) {
            return false;
        }

        if ( false === stripos( $parsed_url['host'], 'gravatar.com' ) ) {
            return false;
        }

        $default_value = '';
        if ( ! empty( $parsed_url['query'] ) ) {
            parse_str( $parsed_url['query'], $query_args );
            if ( ! empty( $query_args['d'] ) ) {
                $default_value = strtolower( rawurldecode( (string) $query_args['d'] ) );
            }
        }

        if ( '' === $default_value ) {
            return false;
        }

        $placeholders = array( 'mm', 'mystery', 'mysteryman', 'mp', 'identicon', '404', 'retro', 'wavatar', 'monsterid', 'blank' );

        return in_array( $default_value, $placeholders, true );
    }
}

if ( ! function_exists( 'wd4_maybe_supply_avatar_data' ) ) {
    function wd4_maybe_supply_avatar_data( $args, $id_or_email ) {
        $existing_url            = isset( $args['url'] ) ? (string) $args['url'] : '';
        $existing_is_placeholder = wd4_is_placeholder_avatar_url( $existing_url );

        if ( ! $existing_is_placeholder && ! empty( $args['found_avatar'] ) && ! empty( $existing_url ) ) {
            return $args;
        }

        $user = wd4_resolve_avatar_user( $id_or_email );
        $size = ! empty( $args['size'] ) ? (int) $args['size'] : 96;
        $url  = '';

        if ( $user instanceof WP_User ) {
            $author_image_id = (int) get_user_meta( $user->ID, 'author_image_id', true );
            if ( $author_image_id ) {
                $attachment = wp_get_attachment_image_src( $author_image_id, 'thumbnail' );
                if ( $attachment ) {
                    $url = $attachment[0];
                }
            }
        }

        if ( empty( $url ) ) {
            $url = wd4_get_default_avatar_url( $user );
        }

        if ( empty( $url ) ) {
            return $args;
        }

        $args['url']          = esc_url_raw( $url );
        $args['found_avatar'] = true;
        $args['height']       = $size;
        $args['width']        = $size;
        $args['class']        = trim( (string) ( $args['class'] ?? '' ) . ' default-avatar' );

        $extra_attr = (string) ( $args['extra_attr'] ?? '' );
        if ( false === stripos( $extra_attr, 'loading=' ) ) {
            $extra_attr .= ' loading="lazy"';
        }
        if ( false === stripos( $extra_attr, 'decoding=' ) ) {
            $extra_attr .= ' decoding="async"';
        }
        $args['extra_attr'] = trim( $extra_attr );

        return $args;
    }
}





add_filter( 'get_avatar_data', 'wd4_maybe_supply_avatar_data', 20, 2 );

if ( ! function_exists( 'wd4_get_compact_user_name' ) ) {
    /**
     * Produce a short, navigation-friendly label for the logged-in user.
     *
     * The logic prefers a custom child-theme nickname, then falls back to the
     * built-in WordPress nickname, first name, and finally the full display
     * name. The result is trimmed to two words and a configurable character
     * budget so long names do not push the mobile navigation out of view.
     */
    function wd4_get_compact_user_name( WP_User $user ): string {
        $candidates = array();

        $custom_short_name = trim( (string) get_user_meta( $user->ID, 'wd4_short_display_name', true ) );
        if ( '' !== $custom_short_name ) {
            $candidates[] = $custom_short_name;
        }

        $first_name = trim( (string) get_user_meta( $user->ID, 'first_name', true ) );
        if ( '' !== $first_name ) {
            $candidates[] = $first_name;
        }

        $nickname = trim( (string) get_user_meta( $user->ID, 'nickname', true ) );
        if ( '' !== $nickname ) {
            $candidates[] = $nickname;
        }

        $display_name = trim( (string) $user->display_name );
        if ( '' !== $display_name ) {
            $candidates[] = $display_name;
        }

        $chosen = '';
        foreach ( $candidates as $candidate ) {
            if ( '' !== $candidate ) {
                $chosen = $candidate;
                break;
            }
        }

        if ( '' === $chosen ) {
            return '';
        }

        $chosen = preg_replace( '/\s+/u', ' ', $chosen );
        $parts  = explode( ' ', $chosen );
        $chosen = implode( ' ', array_slice( $parts, 0, 2 ) );

        $max_length = (int) apply_filters( 'wd4_compact_name_length', 18, $user );
        if ( $max_length > 0 ) {
            if (
                function_exists( 'mb_strlen' ) &&
                function_exists( 'mb_substr' ) &&
                mb_strlen( $chosen ) > $max_length
            ) {
                $chosen = mb_substr( $chosen, 0, $max_length );
            } elseif ( strlen( $chosen ) > $max_length ) {
                $chosen = substr( $chosen, 0, $max_length );
            }
            $chosen = rtrim( $chosen );
        }

        $user_login = trim( (string) $user->user_login );
        if ( '' !== $user_login && 0 === strcasecmp( $chosen, $user_login ) ) {
            $login_max_length = (int) apply_filters( 'wd4_compact_login_length', 5, $user );

            if ( $login_max_length > 0 ) {
                if (
                    function_exists( 'mb_strlen' ) &&
                    function_exists( 'mb_substr' ) &&
                    mb_strlen( $chosen ) > $login_max_length
                ) {
                    $chosen = mb_substr( $chosen, 0, $login_max_length );
                } elseif ( strlen( $chosen ) > $login_max_length ) {
                    $chosen = substr( $chosen, 0, $login_max_length );
                }
                $chosen = rtrim( $chosen );
            }
        }

        return $chosen;
    }
}

if ( ! function_exists( 'wd4_render_short_display_name_field' ) ) {
    /**
     * Add a custom nickname field to the user profile screen so members can
     * control the compact name displayed in tight header spaces.
     */
    function wd4_render_short_display_name_field( WP_User $user ): void {
        if ( ! ( $user instanceof WP_User ) ) {
            return;
        }

        $short_name = trim( (string) get_user_meta( $user->ID, 'wd4_short_display_name', true ) );
        ?>
        <h2><?php esc_html_e( 'Header Nickname', 'foxiz-child' ); ?></h2>
        <p><?php esc_html_e( 'Provide a short name that fits comfortably inside the mobile navigation. Leave blank to fall back to your nickname or first name.', 'foxiz-child' ); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="wd4_short_display_name"><?php esc_html_e( 'Compact display name', 'foxiz-child' ); ?></label>
                </th>
                <td>
                    <input
                        type="text"
                        name="wd4_short_display_name"
                        id="wd4_short_display_name"
                        value="<?php echo esc_attr( $short_name ); ?>"
                        class="regular-text"
                        maxlength="40"
                    />
                </td>
            </tr>
        </table>
        <?php
    }
}

if ( ! function_exists( 'wd4_save_short_display_name_field' ) ) {
    /**
     * Persist the compact nickname field when a profile is updated.
     */
    function wd4_save_short_display_name_field( int $user_id ): void {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        $value = isset( $_POST['wd4_short_display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wd4_short_display_name'] ) ) : '';
        update_user_meta( $user_id, 'wd4_short_display_name', $value );
    }
}

add_action( 'show_user_profile', 'wd4_render_short_display_name_field' );
add_action( 'edit_user_profile', 'wd4_render_short_display_name_field' );
add_action( 'personal_options_update', 'wd4_save_short_display_name_field' );
add_action( 'edit_user_profile_update', 'wd4_save_short_display_name_field' );

if ( ! function_exists( 'wd4_allow_profile_avatar_uploads' ) ) {
    /**
     * Temporarily grant subscribers permission to upload an avatar when they are
     * updating their own profile image from the front end.
     */
    function wd4_allow_profile_avatar_uploads( array $allcaps, array $caps, array $args, $user ): array {
        unset( $caps, $args );

        if ( ! ( $user instanceof WP_User ) ) {
            return $allcaps;
        }

        if ( ! is_user_logged_in() || get_current_user_id() !== (int) $user->ID ) {
            return $allcaps;
        }

        if ( empty( $_FILES['wd4_author_avatar']['name'] ) ) {
            return $allcaps;
        }

        if ( empty( $_POST['wd4_author_avatar_nonce'] ) ) {
            return $allcaps;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['wd4_author_avatar_nonce'] ) );

        if ( ! wp_verify_nonce( $nonce, 'wd4_update_author_avatar' ) ) {
            return $allcaps;
        }

        $allcaps['upload_files'] = true;

        return $allcaps;
    }
}

add_filter( 'user_has_cap', 'wd4_allow_profile_avatar_uploads', 20, 4 );