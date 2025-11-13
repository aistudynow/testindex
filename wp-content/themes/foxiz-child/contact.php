<?php
/**
 * Contact page layout partial used by page-contact.php.
 *
 * @package Foxiz Child
 */

$hero_defaults = array(
    'url'    => 'https://static.showit.co/1600/BMXlKnSZb2KncO0g1clwgg/146140/kmw-12192024-kleist-creative-brand-shoot-248.jpg',
    'width'  => 1600,
    'height' => 1040,
);

$hero_large_defaults = array(
    'url'    => 'https://static.showit.co/2400/BMXlKnSZb2KncO0g1clwgg/146140/kmw-12192024-kleist-creative-brand-shoot-248.jpg',
    'width'  => 2400,
    'height' => 1560,
);

$options = function_exists( 'wd4_get_contact_options' ) ? wd4_get_contact_options() : array();

$hero_image_id       = isset( $options['hero_image_attachment_id'] ) ? (int) $options['hero_image_attachment_id'] : 0;
$hero_image_large_id = isset( $options['hero_image_large_attachment_id'] ) ? (int) $options['hero_image_large_attachment_id'] : 0;

$hero_image_url        = $hero_defaults['url'];
$hero_image_width      = $hero_defaults['width'];
$hero_image_height     = $hero_defaults['height'];
$hero_image_large_url  = $hero_large_defaults['url'];
$hero_image_large_width = $hero_large_defaults['width'];

if ( $hero_image_id ) {
    $image_data = wp_get_attachment_image_src( $hero_image_id, 'full' );
    if ( is_array( $image_data ) ) {
        $hero_image_url    = $image_data[0];
        $hero_image_width  = (int) $image_data[1];
        $hero_image_height = (int) $image_data[2];
    }
}

if ( $hero_image_large_id ) {
    $large_data = wp_get_attachment_image_src( $hero_image_large_id, 'full' );
    if ( is_array( $large_data ) ) {
        $hero_image_large_url   = $large_data[0];
        $hero_image_large_width = (int) $large_data[1];
    }
}

$hero_image_srcset_parts = array();

if ( $hero_image_id ) {
    $attachment_srcset = wp_get_attachment_image_srcset( $hero_image_id, 'full' );
    if ( $attachment_srcset ) {
        $hero_image_srcset_parts = array_map( 'trim', explode( ',', $attachment_srcset ) );
    }
}

if ( $hero_image_url ) {
    $hero_image_srcset_parts[] = esc_url_raw( $hero_image_url ) . ' ' . max( 1, $hero_image_width ) . 'w';
}

if ( $hero_image_large_url && $hero_image_large_url !== $hero_image_url ) {
    $hero_image_srcset_parts[] = esc_url_raw( $hero_image_large_url ) . ' ' . max( 1, $hero_image_large_width ) . 'w';
}

$hero_image_srcset_parts = array_unique( array_filter( array_map( 'trim', $hero_image_srcset_parts ) ) );
$hero_image_srcset       = implode( ', ', $hero_image_srcset_parts );

$hero_image_sizes = '100vw';
$hero_image_alt   = isset( $options['hero_image_alt'] ) ? (string) $options['hero_image_alt'] : '';
if ( '' === $hero_image_alt ) {
    $hero_image_alt = get_bloginfo( 'name' );
}

$hero_image_url        = apply_filters( 'wd4_contact_hero_image', $hero_image_url, $options );
$hero_image_large_url  = apply_filters( 'wd4_contact_hero_image_large', $hero_image_large_url, $options );
$hero_image_width      = (int) apply_filters( 'wd4_contact_hero_image_width', $hero_image_width, $options );
$hero_image_height     = (int) apply_filters( 'wd4_contact_hero_image_height', $hero_image_height, $options );
$hero_image_large_width = (int) apply_filters( 'wd4_contact_hero_image_large_width', $hero_image_large_width, $options );
$hero_image_sizes      = apply_filters( 'wd4_contact_hero_image_sizes', $hero_image_sizes, $options );
$hero_image_alt        = apply_filters( 'wd4_contact_hero_image_alt', $hero_image_alt, $options );
$hero_image_srcset     = apply_filters( 'wd4_contact_hero_image_srcset', $hero_image_srcset, $options );

if ( $hero_image_url ) {
    add_action(
        'wp_head',
        static function () use ( $hero_image_url, $hero_image_srcset, $hero_image_sizes ) {
            $attributes = array(
                "rel='preload'",
                "as='image'",
                "href='" . esc_url( $hero_image_url ) . "'",
            );

            if ( $hero_image_srcset ) {
                $attributes[] = "imagesrcset='" . esc_attr( $hero_image_srcset ) . "'";
            }

            if ( $hero_image_sizes ) {
                $attributes[] = "imagesizes='" . esc_attr( $hero_image_sizes ) . "'";
            }

            printf( "<link %s />\n", implode( ' ', $attributes ) );
        },
        5
    );
}

$hero_subtitle       = isset( $options['hero_subtitle'] ) ? (string) $options['hero_subtitle'] : '';
$hero_title_raw      = isset( $options['hero_title'] ) ? (string) $options['hero_title'] : '';
$banner_message_raw  = isset( $options['banner_message'] ) ? (string) $options['banner_message'] : '';
$banner_link_text    = isset( $options['banner_link_text'] ) ? (string) $options['banner_link_text'] : '';
$banner_link_url     = isset( $options['banner_link_url'] ) ? (string) $options['banner_link_url'] : '';
$contact_heading     = isset( $options['contact_heading'] ) ? (string) $options['contact_heading'] : '';
$contact_intro_raw   = isset( $options['contact_intro'] ) ? (string) $options['contact_intro'] : '';
$contact_box_text_raw = isset( $options['contact_box_text'] ) ? (string) $options['contact_box_text'] : '';
$contact_box_email   = isset( $options['contact_box_email'] ) ? (string) $options['contact_box_email'] : '';
$contact_form_title  = isset( $options['contact_form_title'] ) ? (string) $options['contact_form_title'] : '';
$contact_top_left    = isset( $options['contact_top_left'] ) ? (string) $options['contact_top_left'] : '';
$contact_top_right   = isset( $options['contact_top_right'] ) ? (string) $options['contact_top_right'] : '';
$faq_heading         = isset( $options['faq_heading'] ) ? (string) $options['faq_heading'] : '';
$author_title        = isset( $options['author_title'] ) ? (string) $options['author_title'] : '';
$author_content_raw  = isset( $options['author_content'] ) ? (string) $options['author_content'] : '';
$optin_title         = isset( $options['optin_title'] ) ? (string) $options['optin_title'] : '';
$optin_text_raw      = isset( $options['optin_text'] ) ? (string) $options['optin_text'] : '';

$services      = function_exists( 'wd4_get_contact_services' ) ? wd4_get_contact_services() : array();
$faq_items     = function_exists( 'wd4_get_contact_faq_items' ) ? wd4_get_contact_faq_items() : array();
$social_links  = function_exists( 'wd4_get_contact_social_links' ) ? wd4_get_contact_social_links() : array();
$text_to_paras = function_exists( 'wd4_contact_text_to_paragraphs' ) ? 'wd4_contact_text_to_paragraphs' : null;

$contact_box_paragraphs = $text_to_paras ? $text_to_paras( $contact_box_text_raw ) : array_filter( array( $contact_box_text_raw ) );
$author_paragraphs      = $text_to_paras ? $text_to_paras( $author_content_raw ) : array_filter( array( $author_content_raw ) );
$optin_paragraphs       = $text_to_paras ? $text_to_paras( $optin_text_raw ) : array_filter( array( $optin_text_raw ) );

$hero_title_display = str_replace( array( "\r\n", "\r", "\n" ), '<br>', $hero_title_raw );
$contact_intro_display = $contact_intro_raw;
$contact_box_email_display = $contact_box_email ? antispambot( $contact_box_email ) : '';

$author_image_id  = isset( $options['author_image_attachment_id'] ) ? (int) $options['author_image_attachment_id'] : 0;
$author_image_html = '';

if ( $author_image_id ) {
    $author_image_html = wp_get_attachment_image(
        $author_image_id,
        'large',
        false,
        array(
            'class'    => 'wd4-about-author-photo',
            'loading'  => 'lazy',
            'decoding' => 'async',
        )
    );
}

if ( ! $author_image_html ) {
    $author_image_html = sprintf(
        '<img src="%1$s" alt="%2$s" loading="lazy" decoding="async" width="400" height="533">',
        esc_url( 'https://static.showit.co/400/3U3LJOFHQKOcl3RM_YfzMQ/146140/screenshot_2024-06-17_at_7_26_21_am.png' ),
        esc_attr( $author_title ? $author_title : get_bloginfo( 'name' ) )
    );
}

$banner_link_html = '';
if ( $banner_link_text && $banner_link_url ) {
    $banner_link_html = sprintf(
        '<a href="%1$s" target="_self" rel="noopener">%2$s</a>',
        esc_url( $banner_link_url ),
        esc_html( $banner_link_text )
    );
}

$banner_message_display = $banner_message_raw;
if ( $banner_link_html ) {
    if ( false !== strpos( $banner_message_display, '%s' ) ) {
        $banner_message_display = sprintf( $banner_message_display, $banner_link_html );
    } else {
        $banner_message_display = trim( $banner_message_display . ' ' . $banner_link_html );
    }
}

$banner_message_display = trim( $banner_message_display );

get_header();

$admin_user = get_user_by( 'email', get_option( 'admin_email' ) );

if ( ! $admin_user instanceof WP_User ) {
    $avatar_candidates = get_users(
        array(
            'meta_key'     => 'author_image_id',
            'meta_value'   => 0,
            'meta_compare' => '>',
            'meta_type'    => 'NUMERIC',
            'number'       => 1,
            'orderby'      => 'ID',
            'order'        => 'ASC',
        )
    );

    if ( ! empty( $avatar_candidates ) ) {
        $admin_user = $avatar_candidates[0];
    }
}

if ( ! $admin_user instanceof WP_User ) {
    $admin_candidates = get_users(
        array(
            'role__in' => array( 'administrator' ),
            'number'   => 1,
            'orderby'  => 'ID',
            'order'    => 'ASC',
        )
    );

    if ( ! empty( $admin_candidates ) ) {
        $admin_user = $admin_candidates[0];
    }
}

$admin_user    = apply_filters( 'wd4_contact_admin_user', $admin_user instanceof WP_User ? $admin_user : null );
$admin_display = $admin_user instanceof WP_User ? $admin_user->display_name : get_bloginfo( 'name' );
if ( empty( $admin_display ) ) {
    $admin_display = __( 'Site Administrator', 'foxiz-child' );
}

$avatar_size = (int) apply_filters( 'wd4_contact_admin_avatar_size', 60 );
if ( $avatar_size <= 0 ) {
    $avatar_size = 60;
}

$settings = array();
if ( function_exists( 'foxiz_get_option' ) ) {
    $settings['logged_gravatar'] = foxiz_get_option( 'logged_gravatar' );
}

$current_user  = $admin_user instanceof WP_User ? $admin_user : null;
$avatar_markup = '';

if ( $current_user instanceof WP_User ) {
    $author_image_id = (int) get_user_meta( $current_user->ID, 'author_image_id', true );

    if ( 0 !== $author_image_id ) {
        if ( function_exists( 'foxiz_get_avatar_by_attachment' ) ) {
            $avatar_markup = foxiz_get_avatar_by_attachment( $author_image_id, 'thumbnail', false );
        }

        if ( empty( $avatar_markup ) ) {
            $avatar_markup = wp_get_attachment_image(
                $author_image_id,
                array( $avatar_size, $avatar_size ),
                false,
                array(
                    'class'    => 'photo avatar wd4-contact-avatar-img',
                    'loading'  => 'eager',
                    'decoding' => 'async',
                    'alt'      => $admin_display,
                )
            );
        }
    }

    if ( empty( $avatar_markup ) ) {
        $avatar_markup = get_avatar(
            $current_user->ID,
            $avatar_size,
            '',
            $admin_display,
            array(
                'class'      => 'photo avatar wd4-contact-avatar-img',
                'extra_attr' => 'loading="eager" decoding="async"',
            )
        );
    }
}

$contact_status      = isset( $_GET['wd4_contact'] ) ? sanitize_key( (string) wp_unslash( $_GET['wd4_contact'] ) ) : '';
$contact_status_code = isset( $_GET['wd4_contact_code'] ) ? sanitize_key( (string) wp_unslash( $_GET['wd4_contact_code'] ) ) : '';
$contact_notice      = '';
$contact_notice_type = '';
$form_timestamp      = (int) apply_filters( 'wd4_contact_form_timestamp', time() );

if ( 'success' === $contact_status ) {
    $contact_notice_type = 'success';
    $contact_notice      = __( 'Thank you for your inquiry! We will get back to you soon.', 'foxiz-child' );
} elseif ( 'error' === $contact_status ) {
    $contact_notice_type = 'error';

    switch ( $contact_status_code ) {
        case 'invalid_nonce':
            $contact_notice = __( 'We could not verify your submission. Please try again.', 'foxiz-child' );
            break;
        case 'invalid_request':
            $contact_notice = __( 'We were unable to process your request. Please try again.', 'foxiz-child' );
            break;
        case 'missing_fields':
            $contact_notice = __( 'Please complete the required fields (name, email, and message) before submitting.', 'foxiz-child' );
            break;
        case 'no_recipient':
            $contact_notice = __( 'We could not route your message at this time. Please try again later.', 'foxiz-child' );
            break;
        case 'rate_limited':
            $contact_notice = __( 'You have reached the submission limit. Please wait a bit before trying again.', 'foxiz-child' );
            break;
        case 'stale':
            $contact_notice = __( 'Your form session expired. Please refresh the page and submit again.', 'foxiz-child' );
            break;
        case 'send_failed':
        default:
            $contact_notice = __( 'Something went wrong while sending your message. Please try again in a moment.', 'foxiz-child' );
            break;
    }
}

$optin_status      = isset( $_GET['wd4_optin'] ) ? sanitize_key( (string) wp_unslash( $_GET['wd4_optin'] ) ) : '';
$optin_status_code = isset( $_GET['wd4_optin_code'] ) ? sanitize_key( (string) wp_unslash( $_GET['wd4_optin_code'] ) ) : '';
$optin_notice      = '';
$optin_notice_type = '';

if ( 'success' === $optin_status ) {
    $optin_notice_type = 'success';
    $optin_notice      = __( 'Success! Check your inbox for the Glowing Testimonial Guide.', 'foxiz-child' );
} elseif ( 'error' === $optin_status ) {
    $optin_notice_type = 'error';

    switch ( $optin_status_code ) {
        case 'invalid_nonce':
            $optin_notice = __( 'We could not verify your request. Please submit the form again.', 'foxiz-child' );
            break;
        case 'missing_fields':
            $optin_notice = __( 'Please provide a valid email address so we can send the guide.', 'foxiz-child' );
            break;
        case 'no_asset':
            $optin_notice = __( 'The guide is not available yet. Please try again later.', 'foxiz-child' );
            break;
        case 'send_failed':
        default:
            $optin_notice = __( 'We had trouble emailing the guide. Please try again shortly.', 'foxiz-child' );
            break;
    }
}

$contact_notice_live = 'error' === $contact_notice_type ? 'assertive' : 'polite';
$optin_notice_live   = 'error' === $optin_notice_type ? 'assertive' : 'polite';
?>

<main id="primary" class="site-main wd4-contact-page">
    <style>
        @font-face {
            font-family: 'Helvetica Neue Bold';
            src: url('https://static.showit.co/file/M_gQSc002IEHuwO1GGYBBg/146140/helveticaneuebold-webfont.woff') format('woff');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Helvetica Neue Light';
            src: url('https://static.showit.co/file/sDwDSb6YQgeP5ZXTtt6vuQ/146140/helveticaneuelight-webfont.woff') format('woff');
            font-weight: 300;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Helvetica Neue Light Italic';
            src: url('https://static.showit.co/file/rFyT4yqspoX-xfHW4U9hBA/146140/helveticaneuelightitalic-webfont.woff') format('woff');
            font-weight: 300;
            font-style: italic;
            font-display: swap;
        }

        @font-face {
            font-family: 'Mercenary Medium';
            src: url('https://static.showit.co/file/IN6c5sCISvwE5IHTieKwKw/146140/mercenary-medium-webfont.woff') format('woff');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Ivy Presto';
            src: url('https://static.showit.co/file/pwoBGetlTEGqgXEH8nOLwg/146140/ivypresto_display_thin-webfont.woff') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Prairie Script Bold';
            src: url('https://static.showit.co/file/pk5SX8pYRxSO3B08LdvN3g/146140/prairie-script-bold-webfont.woff') format('woff');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }

        .wd4-contact-page {
            --wd4-color-primary: #5D87A9;
            --wd4-color-dark: #1D3145;
            --wd4-color-secondary: #132133;
            --wd4-color-light: #FFFFFF;
            --wd4-color-muted: #b8b8b8;
            --wd4-focus-color: #5D87A9;
            --wd4-spacing-section: clamp(3.25rem, 8vw, 5.5rem);
            color: var(--wd4-color-dark);
            font-family: 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            line-height: 1.6;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            background-color: var(--wd4-color-light);
        }

        .wd4-contact-page * {
            box-sizing: border-box;
        }

        .wd4-contact-page :focus-visible {
            outline: 2px solid var(--wd4-focus-color);
            outline-offset: 3px;
        }

        .wd4-contact-page a {
            color: inherit;
        }

        .wd4-contact-container {
            width: min(100%, 1200px);
            margin: 0 auto;
            padding: 0 clamp(1rem, 5vw, 2rem);
        }

        @media (max-width: 480px) {
            .wd4-contact-container {
                padding-inline: clamp(0.75rem, 4vw, 1.25rem);
            }
        }

        .wd4-contact-banner {
            background-color: #75A5CB;
            color: var(--wd4-color-light);
            padding: 0.75rem clamp(1rem, 4vw, 1.5rem);
            text-align: center;
            position: relative;
            z-index: 6;
        }

        .wd4-contact-banner strong {
            font-weight: 700;
        }

        .wd4-contact-banner a {
            text-decoration: underline;
        }

        .wd4-contact-banner-close {
            position: absolute;
            inset-inline-end: clamp(0.75rem, 3vw, 1.25rem);
            inset-block-start: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: inherit;
            font-size: 1.5rem;
            cursor: pointer;
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .wd4-contact-hero {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: clamp(20rem, 60vh, 34rem);
            padding: clamp(2rem, 6vh, 4.5rem) clamp(1.5rem, 6vw, 3rem);
            background-color: #0b1a2d;
            color: var(--wd4-color-light);
            overflow: hidden;
            isolation: isolate;
        }

        .wd4-contact-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0.25) 50%, rgba(0, 0, 0, 0.7) 100%);
            z-index: 1;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .wd4-contact-hero-picture {
            position: absolute;
            inset: 0;
            margin: 0;
            z-index: 0;
        }

        .wd4-contact-hero-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            filter: saturate(110%) contrast(95%);
        }

        .wd4-contact-hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            gap: clamp(0.5rem, 2vw, 1.5rem);
            padding: clamp(1rem, 3vw, 2rem);
            max-width: min(100%, 620px);
        }

        .wd4-contact-hero-subtitle {
            font-family: 'Prairie Script Bold', 'Brush Script MT', cursive;
            font-size: clamp(1.35rem, 3vw, 2.25rem);
            letter-spacing: -0.08em;
            margin: 0;
        }

        .wd4-contact-hero-title {
            font-family: 'Ivy Presto', serif;
            font-size: clamp(2.75rem, 8vw, 6.25rem);
            line-height: 1;
            margin: 0;
            text-wrap: balance;
        }

        @media (max-width: 768px) {
            .wd4-contact-hero-title br {
                display: none;
            }
        }

        .wd4-contact-section {
            position: relative;
            z-index: 1;
            background-color: var(--wd4-color-light);
            padding: clamp(3rem, 6vw, 4.5rem) 0 clamp(3rem, 6vw, 5rem);
        }

        .wd4-contact-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: clamp(0.5rem, 2vw, 2rem);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            margin-bottom: clamp(2rem, 5vw, 3rem);
            position: sticky;
            top: clamp(0rem, env(safe-area-inset-top), 2rem);
            background-color: rgba(255, 255, 255, 0.97);
            z-index: 100;
            padding: 1.25rem 0;
            font-family: 'Mercenary Medium', 'Mercenary', sans-serif;
            transition: box-shadow 0.3s ease;
        }

        .wd4-contact-top-row.wd4-stuck {
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 899px) {
            .wd4-contact-top-row {
                position: static;
                text-align: center;
                flex-direction: column;
                letter-spacing: 0.12em;
            }
        }

        .wd4-contact-layout {
            display: grid;
            gap: clamp(2.5rem, 6vw, 5rem);
        }

        .wd4-contact-left {
            display: grid;
            gap: clamp(1.5rem, 4vw, 2.5rem);
            max-width: min(100%, 520px);
        }

        .wd4-contact-heading {
            font-family: 'Ivy Presto', serif;
            font-weight: 400;
            font-size: clamp(2.5rem, 6vw, 4rem);
            line-height: 1.08;
            margin: 0;
            color: var(--wd4-color-dark);
        }

        .wd4-contact-heading-avatar {
            display: inline-flex;
            vertical-align: baseline;
            margin-left: 0.18em;
            width: clamp(3rem, 6vw, 3.7rem);
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            border: 1px solid rgba(19, 33, 51, 0.2);
            background-color: #d9d9d9;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transform: translateY(0.25em);
        }

        .wd4-contact-heading-avatar .logged-avatar,
        .wd4-contact-heading-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        .wd4-contact-heading-avatar .rbi {
            font-size: clamp(1.75rem, 4vw, 2.25rem);
            color: var(--wd4-color-secondary);
        }

        .wd4-contact-intro {
            font-size: clamp(1rem, 1.3vw + 0.9rem, 1.15rem);
            margin: 0;
            max-width: 42ch;
        }

        .wd4-contact-intro em {
            font-family: 'Helvetica Neue Light Italic', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-style: normal;
        }

        .wd4-contact-intro strong,
        .wd4-contact-box strong {
            font-family: 'Helvetica Neue Bold', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-weight: 400;
        }

        .wd4-contact-box {
            border: 1px solid rgba(19, 33, 51, 0.18);
            border-radius: 18px;
            padding: clamp(1.5rem, 4vw, 2rem);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 18px 45px rgba(17, 37, 55, 0.08);
        }

        .wd4-contact-box p {
            font-size: 0.9375rem;
            line-height: 1.65;
            margin: 0 0 0.75rem;
        }

        .wd4-contact-box p:last-child {
            margin-bottom: 0;
        }

        .wd4-contact-box-email {
            font-size: 1.1rem;
            color: var(--wd4-color-dark);
            font-family: 'Mercenary Medium', 'Mercenary', sans-serif;
            text-decoration: underline;
            word-break: break-word;
        }

        .wd4-contact-social-wrapper {
            display: grid;
            gap: 0.75rem;
        }

        .wd4-contact-social {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.25rem;
        }

        .wd4-contact-social-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.75rem;
            font-family: 'Mercenary Medium', 'Mercenary', sans-serif;
        }

        .wd4-contact-social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background-color: var(--wd4-color-dark);
            color: var(--wd4-color-light);
            font-size: 1rem;
        }

        .wd4-contact-social-link:hover .wd4-contact-social-icon {
            background-color: var(--wd4-color-primary);
        }

        .wd4-contact-social-label {
            white-space: nowrap;
        }

        .wd4-contact-right {
            position: relative;
            padding: clamp(1.25rem, 3vw, 2rem) 0;
            display: grid;
            gap: clamp(1.5rem, 3vw, 2.5rem);
            max-width: min(100%, 720px);
        }

        @media (min-width: 900px) {
            .wd4-contact-layout {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.3fr);
                align-items: start;
            }

            .wd4-contact-left {
                position: sticky;
                top: 100px;
                align-self: flex-start;
            }

            .wd4-contact-right {
                padding-inline: clamp(1.25rem, 3vw, 2rem);
            }
        }

        .wd4-contact-right-inner {
            position: relative;
            padding: clamp(1.5rem, 3.5vw, 2.25rem);
            background: rgba(255, 255, 255, 0.96);
            border-radius: 22px;
            box-shadow: 0 24px 60px rgba(17, 37, 55, 0.12);
            display: grid;
            gap: clamp(1.5rem, 3vw, 2.25rem);
        }

        .wd4-contact-notice {
            margin: 0;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            font-size: 0.95rem;
            line-height: 1.55;
            border: 1px solid transparent;
        }

        .wd4-contact-notice--success {
            background-color: #edf7ed;
            border-color: #c1e7c3;
            color: #1d4d1d;
        }

        .wd4-contact-notice--error {
            background-color: #fdecea;
            border-color: #f5c6cb;
            color: #611a15;
        }

        .wd4-form-title {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(1.3rem, 2.4vw, 1.75rem);
            font-style: italic;
            margin: 0;
            text-align: left;
        }

        .wd4-contact-form {
            display: grid;
            gap: clamp(1rem, 2.5vw, 1.5rem);
        }

        .wd4-contact-form .wd4-form-group {
            margin: 0;
            display: grid;
            gap: 0.5rem;
        }

        .wd4-contact-form label {
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 0.04em;
        }

        .wd4-contact-form input,
        .wd4-contact-form textarea,
        .wd4-contact-form select {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--wd4-color-muted);
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.85);
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--wd4-color-dark);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            appearance: none;
        }

        .wd4-contact-form textarea {
            min-height: 7.5rem;
            resize: vertical;
        }

        .wd4-contact-honeypot {
            position: absolute !important;
            inset-inline-start: -100vw;
            width: 1px;
            height: 1px;
            overflow: hidden;
            opacity: 0;
        }

        .wd4-contact-form input:focus-visible,
        .wd4-contact-form textarea:focus-visible,
        .wd4-contact-form select:focus-visible {
            border-color: var(--wd4-color-primary);
            box-shadow: 0 0 0 3px rgba(93, 135, 169, 0.2);
        }

        .wd4-contact-form select {
            background-image: linear-gradient(45deg, transparent 50%, var(--wd4-color-muted) 50%),
                linear-gradient(135deg, var(--wd4-color-muted) 50%, transparent 50%);
            background-position: calc(100% - 1.2rem) calc(0.9rem), calc(100% - 0.9rem) calc(0.9rem);
            background-size: 0.35rem 0.35rem, 0.35rem 0.35rem;
            background-repeat: no-repeat;
        }

        .wd4-form-submit {
            background-color: var(--wd4-color-primary);
            color: var(--wd4-color-light);
            padding: 0.85rem 2.75rem;
            border: none;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            cursor: pointer;
            font-weight: 500;
            border-radius: 999px;
            transition: background-color 0.2s ease, transform 0.2s ease;
            justify-self: start;
        }

        .wd4-form-submit:hover {
            background-color: rgba(93, 135, 169, 0.85);
            transform: translateY(-1px);
        }

        .wd4-form-submit:active {
            transform: translateY(0);
        }

        @media (max-width: 640px) {
            .wd4-form-submit {
                width: 100%;
                justify-self: stretch;
            }
        }

        .wd4-faq-section {
            background-color: #335C80;
            color: var(--wd4-color-light);
            padding: var(--wd4-spacing-section) 0;
        }

        .wd4-faq-header {
            text-align: center;
            margin-bottom: clamp(2rem, 6vw, 3rem);
        }

        .wd4-faq-header h3 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            margin: 0 0 1rem;
        }

        .wd4-faq-list {
            display: grid;
            gap: 1.25rem;
        }

        .wd4-faq-empty {
            text-align: center;
            font-size: 0.95rem;
            letter-spacing: 0.04em;
            opacity: 0.85;
        }

        .wd4-faq-item {
            border-top: 1px solid rgba(255, 255, 255, 0.25);
            padding-top: 1.25rem;
        }

        .wd4-faq-question {
            width: 100%;
            background: none;
            border: none;
            color: inherit;
            font: inherit;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            cursor: pointer;
            padding: 0;
            text-align: left;
        }

        .wd4-faq-question-text {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(1.35rem, 3vw, 1.85rem);
            line-height: 1.2;
            flex: 1;
        }

        .wd4-faq-toggle {
            font-size: 2rem;
            line-height: 1;
            transition: transform 0.25s ease;
        }

        .wd4-faq-item.is-open .wd4-faq-toggle {
            transform: rotate(45deg);
        }

        .wd4-faq-answer {
            padding-top: 1rem;
            font-size: 1rem;
            line-height: 1.7;
        }

        .wd4-faq-answer[hidden] {
            display: none !important;
        }

        .wd4-about-author {
            background-color: #EFEBE6;
            padding: var(--wd4-spacing-section) 0;
        }

        .wd4-about-author-container {
            display: grid;
            gap: clamp(2rem, 6vw, 4rem);
            align-items: center;
        }

        .wd4-about-author-image {
            width: min(100%, 420px);
            justify-self: center;
        }

        .wd4-about-author-image img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(17, 37, 55, 0.12);
        }

        .wd4-about-author-content {
            display: grid;
            gap: 1.25rem;
            max-width: 48ch;
        }

        .wd4-about-author-content h3 {
            font-family: 'Mercenary Medium', 'Helvetica Neue', Arial, sans-serif;
            font-size: 0.95rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--wd4-color-secondary);
            margin: 0;
        }

        .wd4-about-author-content p {
            margin: 0;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        @media (min-width: 992px) {
            .wd4-about-author-container {
                grid-template-columns: minmax(0, 420px) minmax(0, 1fr);
            }
        }

        .wd4-optin-section {
            background-color: #161616;
            color: var(--wd4-color-light);
            padding: var(--wd4-spacing-section) 0 clamp(4.5rem, 9vw, 6rem);
        }

        .wd4-optin-content {
            max-width: 800px;
            margin: 0 auto;
            display: grid;
            gap: clamp(1.5rem, 4vw, 2.75rem);
            text-align: left;
        }

        .wd4-optin-title {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(2.25rem, 6vw, 4rem);
            line-height: 1.15;
            margin: 0;
        }

        .wd4-optin-text {
            font-size: 1.05rem;
            line-height: 1.7;
            margin: 0;
        }

        .wd4-newsletter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .wd4-newsletter-form input {
            flex: 1 1 220px;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background-color: transparent;
            color: inherit;
            font-size: 0.95rem;
        }

        .wd4-newsletter-form input:focus-visible {
            border-color: var(--wd4-color-light);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.25);
        }

        .wd4-newsletter-form button {
            background-color: var(--wd4-color-light);
            color: var(--wd4-color-secondary);
            padding: 0.85rem 1.75rem;
            border: none;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            cursor: pointer;
            font-weight: 500;
            border-radius: 999px;
        }

        @media (max-width: 640px) {
            .wd4-newsletter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .wd4-newsletter-form button {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .wd4-contact-hero,
            .wd4-contact-hero::before,
            .wd4-contact-top-row,
            .wd4-faq-toggle {
                transition: none !important;
            }
        }
    </style>

    <?php if ( $banner_message_display ) : ?>
        <div class="wd4-contact-banner" id="wd4ContactBanner">
            <div class="wd4-contact-container">
                <p>
                    <strong><?php echo wp_kses_post( $banner_message_display ); ?></strong>
                </p>
                <button class="wd4-contact-banner-close" type="button" aria-label="<?php esc_attr_e( 'Close banner', 'foxiz-child' ); ?>">
                    &times;
                </button>
            </div>
        </div>
    <?php endif; ?>

    <section class="wd4-contact-hero" id="wd4ContactHero">
        <?php if ( $hero_image_url ) : ?>
            <figure class="wd4-contact-hero-picture" aria-hidden="true">
                <picture>
                    <img
                        class="wd4-contact-hero-img"
                        src="<?php echo esc_url( $hero_image_url ); ?>"
                        <?php if ( $hero_image_srcset ) : ?>
                            srcset="<?php echo esc_attr( $hero_image_srcset ); ?>"
                        <?php endif; ?>
                        <?php if ( $hero_image_sizes ) : ?>
                            sizes="<?php echo esc_attr( $hero_image_sizes ); ?>"
                        <?php endif; ?>
                        alt="<?php echo esc_attr( $hero_image_alt ); ?>"
                        width="<?php echo esc_attr( max( 1, $hero_image_width ) ); ?>"
                        height="<?php echo esc_attr( max( 1, $hero_image_height ) ); ?>"
                        loading="eager"
                        decoding="async"
                        fetchpriority="high"
                    />
                </picture>
            </figure>
        <?php endif; ?>
        <div class="wd4-contact-hero-content">
            <?php if ( $hero_subtitle ) : ?>
                <p class="wd4-contact-hero-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
            <?php endif; ?>
            <?php if ( $hero_title_display ) : ?>
                <h1 class="wd4-contact-hero-title"><?php echo wp_kses_post( $hero_title_display ); ?></h1>
            <?php endif; ?>
        </div>
    </section>

    <section class="wd4-contact-section" id="wd4ContactSection">
        <div class="wd4-contact-container">
            <?php if ( $contact_top_left || $contact_top_right ) : ?>
                <div class="wd4-contact-top-row" id="wd4ContactTopRow">
                    <?php if ( $contact_top_left ) : ?>
                        <span><?php echo esc_html( $contact_top_left ); ?></span>
                    <?php endif; ?>
                    <?php if ( $contact_top_right ) : ?>
                        <span><?php echo esc_html( $contact_top_right ); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="wd4-contact-layout">
                <div class="wd4-contact-left">
                    <?php if ( $contact_heading ) : ?>
                        <h2 class="wd4-contact-heading">
                            <span><?php echo esc_html( $contact_heading ); ?></span>
                            <span class="wd4-contact-heading-avatar">
                                <?php if ( ! empty( $avatar_markup ) || ! empty( $settings['logged_gravatar'] ) ) : ?>
                                    <span class="logged-avatar">
                                        <?php
                                        if ( ! empty( $avatar_markup ) ) {
                                            echo $avatar_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        } elseif ( $current_user instanceof WP_User ) {
                                            echo get_avatar(
                                                $current_user->ID,
                                                $avatar_size,
                                                '',
                                                $admin_display,
                                                array(
                                                    'class'      => 'photo avatar wd4-contact-avatar-img',
                                                    'extra_attr' => 'loading="eager" decoding="async"',
                                                )
                                            );
                                        }
                                        ?>
                                    </span>
                                <?php else : ?>
                                    <i class="rbi rbi-user wnav-icon" aria-hidden="true"></i>
                                <?php endif; ?>
                            </span>
                        </h2>
                    <?php endif; ?>

                    <?php if ( $contact_intro_display ) : ?>
                        <p class="wd4-contact-intro"><?php echo wp_kses_post( $contact_intro_display ); ?></p>
                    <?php endif; ?>

                    <?php if ( ( ! empty( $contact_box_paragraphs ) ) || $contact_box_email ) : ?>
                        <div class="wd4-contact-box">
                            <?php foreach ( $contact_box_paragraphs as $paragraph ) : ?>
                                <p><?php echo wp_kses_post( $paragraph ); ?></p>
                            <?php endforeach; ?>
                            <?php if ( $contact_box_email ) : ?>
                                <p>
                                    <a class="wd4-contact-box-email" href="mailto:<?php echo esc_attr( $contact_box_email ); ?>">
                                        <?php echo esc_html( $contact_box_email ); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $social_links ) ) : ?>
                        <div class="wd4-contact-social-wrapper">
                            <ul class="wd4-contact-social" aria-label="<?php esc_attr_e( 'Follow on social media', 'foxiz-child' ); ?>">
                                <?php foreach ( $social_links as $link ) :
                                    $social_url   = isset( $link['url'] ) ? $link['url'] : '';
                                    $social_label = isset( $link['label'] ) ? $link['label'] : '';
                                    $social_icon  = isset( $link['icon'] ) ? $link['icon'] : '';
                                    if ( ! $social_url || ! $social_label ) {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <a class="wd4-contact-social-link" href="<?php echo esc_url( $social_url ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php if ( $social_icon ) : ?>
                                                <span class="wd4-contact-social-icon" aria-hidden="true">
                                                    <i class="<?php echo esc_attr( $social_icon ); ?>"></i>
                                                </span>
                                            <?php endif; ?>
                                            <span class="wd4-contact-social-label"><?php echo esc_html( $social_label ); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="wd4-contact-right">
                    <div class="wd4-contact-right-inner">
                        <?php if ( $contact_notice ) :
                            $notice_classes = 'wd4-contact-notice';
                            if ( $contact_notice_type ) {
                                $notice_classes .= ' wd4-contact-notice--' . $contact_notice_type;
                            }
                            $notice_role = 'success' === $contact_notice_type ? 'status' : 'alert';
                            ?>
                            <div class="<?php echo esc_attr( $notice_classes ); ?>" role="<?php echo esc_attr( $notice_role ); ?>" aria-live="<?php echo esc_attr( $contact_notice_live ); ?>" tabindex="-1">
                                <?php echo esc_html( $contact_notice ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $contact_form_title ) : ?>
                            <h2 class="wd4-form-title"><?php echo esc_html( $contact_form_title ); ?></h2>
                        <?php endif; ?>

                        <form class="wd4-contact-form" id="wd4ContactForm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="on">
                            <input type="hidden" name="action" value="wd4_contact_submit">
                            <input type="hidden" name="wd4_contact_ts" value="<?php echo esc_attr( $form_timestamp ); ?>">
                            <?php wp_nonce_field( 'wd4_contact_submit', 'wd4_contact_nonce' ); ?>
                            <div class="wd4-contact-honeypot" aria-hidden="true">
                                <label for="wd4ContactHP"><?php esc_html_e( 'Leave this field empty', 'foxiz-child' ); ?></label>
                                <input type="text" id="wd4ContactHP" name="wd4_contact_hp" tabindex="-1" autocomplete="off">
                            </div>
                            <div class="wd4-form-group">
                                <label for="wd4Name"><?php esc_html_e( 'Name *', 'foxiz-child' ); ?></label>
                                <input type="text" id="wd4Name" name="name" autocomplete="name" required>
                            </div>

                            <div class="wd4-form-group">
                                <label for="wd4Email"><?php esc_html_e( 'Email *', 'foxiz-child' ); ?></label>
                                <input type="email" id="wd4Email" name="email" autocomplete="email" required>
                            </div>

                            <div class="wd4-form-group">
                                <label for="wd4Business"><?php esc_html_e( 'Business Name', 'foxiz-child' ); ?></label>
                                <input type="text" id="wd4Business" name="business" autocomplete="organization">
                            </div>

                            <div class="wd4-form-group">
                                <label for="wd4Website"><?php esc_html_e( 'Website', 'foxiz-child' ); ?></label>
                                <input type="url" id="wd4Website" name="website" autocomplete="url">
                            </div>

                            <div class="wd4-form-group">
                                <label for="wd4Service"><?php esc_html_e( 'What service are you interested in?', 'foxiz-child' ); ?></label>
                                <select id="wd4Service" name="service">
                                    <option value=""><?php esc_html_e( 'Select a service', 'foxiz-child' ); ?></option>
                                    <?php foreach ( $services as $service ) : ?>
                                        <option value="<?php echo esc_attr( $service ); ?>"><?php echo esc_html( $service ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="wd4-form-group">
                                <label for="wd4Message"><?php esc_html_e( 'How can I serve you? *', 'foxiz-child' ); ?></label>
                                <textarea id="wd4Message" name="message" rows="6" required></textarea>
                            </div>

                            <button type="submit" class="wd4-form-submit"><?php esc_html_e( 'Submit Inquiry', 'foxiz-child' ); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="wd4-faq-section">
        <div class="wd4-contact-container">
            <?php if ( $faq_heading ) : ?>
                <div class="wd4-faq-header">
                    <h3><?php echo esc_html( $faq_heading ); ?></h3>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $faq_items ) ) : ?>
                <div class="wd4-faq-list">
                    <?php foreach ( $faq_items as $item ) :
                        $question = isset( $item['question'] ) ? $item['question'] : '';
                        $answer   = isset( $item['answer'] ) ? $item['answer'] : '';
                        if ( '' === trim( (string) $question ) && '' === trim( (string) $answer ) ) {
                            continue;
                        }
                        $answer_paragraphs = $text_to_paras ? $text_to_paras( $answer ) : array_filter( array( $answer ) );
                        ?>
                        <div class="wd4-faq-item">
                            <button class="wd4-faq-question" type="button" aria-expanded="false">
                                <span class="wd4-faq-question-text"><?php echo esc_html( $question ); ?></span>
                                <span class="wd4-faq-toggle" aria-hidden="true">+</span>
                            </button>
                            <div class="wd4-faq-answer" hidden>
                                <?php foreach ( $answer_paragraphs as $paragraph ) : ?>
                                    <p><?php echo wp_kses_post( $paragraph ); ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="wd4-faq-empty"><?php esc_html_e( 'No frequently asked questions are available right now.', 'foxiz-child' ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="wd4-about-author" id="wd4AboutTheAuthor">
        <div class="wd4-contact-container wd4-about-author-container">
            <div class="wd4-about-author-image">
                <?php echo $author_image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <div class="wd4-about-author-content">
                <?php if ( $author_title ) : ?>
                    <h3><?php echo esc_html( $author_title ); ?></h3>
                <?php endif; ?>
                <?php foreach ( $author_paragraphs as $paragraph ) : ?>
                    <p><?php echo wp_kses_post( $paragraph ); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="wd4-optin-section">
        <div class="wd4-contact-container">
            <div class="wd4-optin-content">
                <?php if ( $optin_title ) : ?>
                    <h2 class="wd4-optin-title"><?php echo esc_html( $optin_title ); ?></h2>
                <?php endif; ?>
                <?php if ( ! empty( $optin_paragraphs ) ) : ?>
                    <div class="wd4-optin-text">
                        <?php foreach ( $optin_paragraphs as $paragraph ) : ?>
                            <p><?php echo wp_kses_post( $paragraph ); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $optin_notice ) :
                    $optin_classes = 'wd4-contact-notice wd4-optin-notice';
                    if ( $optin_notice_type ) {
                        $optin_classes .= ' wd4-contact-notice--' . $optin_notice_type;
                    }
                    $optin_role = 'success' === $optin_notice_type ? 'status' : 'alert';
                    ?>
                    <div class="<?php echo esc_attr( $optin_classes ); ?>" role="<?php echo esc_attr( $optin_role ); ?>" aria-live="<?php echo esc_attr( $optin_notice_live ); ?>" tabindex="-1">
                        <?php echo esc_html( $optin_notice ); ?>
                    </div>
                <?php endif; ?>
                <form class="wd4-newsletter-form" id="wd4TestimonialForm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="on">
                    <input type="hidden" name="action" value="wd4_testimonial_optin_submit">
                    <?php wp_nonce_field( 'wd4_testimonial_optin', 'wd4_testimonial_nonce' ); ?>
                    <input type="text" name="first_name" placeholder="<?php esc_attr_e( 'First name', 'foxiz-child' ); ?>" autocomplete="given-name">
                    <input type="email" name="email" placeholder="<?php esc_attr_e( 'Email address', 'foxiz-child' ); ?>" autocomplete="email" required>
                    <button type="submit"><?php esc_html_e( 'Get the Guide', 'foxiz-child' ); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
    (function() {
        const banner = document.getElementById('wd4ContactBanner');
        if (banner) {
            const closeButton = banner.querySelector('.wd4-contact-banner-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    banner.style.display = 'none';
                });
            }
        }

        const contactSection = document.getElementById('wd4ContactSection');
        const contactTopRow = document.getElementById('wd4ContactTopRow');

        let ticking = false;

        function updateScrollEffects() {
            if (!contactSection || !contactTopRow) {
                return;
            }

            const sectionTop = contactSection.offsetTop;
            const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollPosition >= sectionTop - 24) {
                contactTopRow.classList.add('wd4-stuck');
            } else {
                contactTopRow.classList.remove('wd4-stuck');
            }
        }

        function requestScrollUpdate() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    updateScrollEffects();
                    ticking = false;
                });
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestScrollUpdate, { passive: true });
        window.addEventListener('resize', requestScrollUpdate);
        requestScrollUpdate();

        const faqButtons = document.querySelectorAll('.wd4-faq-question');
        faqButtons.forEach(function(button) {
            const answer = button.nextElementSibling;
            const parent = button.closest('.wd4-faq-item');

            button.addEventListener('click', function() {
                const expanded = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                if (answer) {
                    if (expanded) {
                        answer.setAttribute('hidden', '');
                    } else {
                        answer.removeAttribute('hidden');
                    }
                }
                if (parent) {
                    parent.classList.toggle('is-open', !expanded);
                }
            });
        });

        document.querySelectorAll('[role="alert"], [role="status"]').forEach(function(notice) {
            if (notice.hasAttribute('tabindex')) {
                notice.focus({ preventScroll: true });
            }
        });
    })();
</script>

<?php
get_footer();