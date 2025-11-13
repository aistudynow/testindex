<?php
/**
 * Author archive template with portfolio-style hero.
 *
 * @package Foxiz Child
 */

declare( strict_types=1 );

get_header();

$author_object = get_queried_object();
$author_id     = $author_object instanceof WP_User ? (int) $author_object->ID : 0;
$author_user   = $author_id ? get_user_by( 'id', $author_id ) : null;

$display_name = '';
if ( $author_id ) {
    $display_name = get_the_author_meta( 'display_name', $author_id );
}
if ( ! $display_name && $author_object instanceof WP_User ) {
    $display_name = $author_object->display_name;
}
if ( ! $display_name && $author_user instanceof WP_User ) {
    $display_name = $author_user->display_name;
}

$compact_display_name        = $display_name;
$compact_default_member_name = __( 'Member', 'foxiz-child' );
if ( $author_user instanceof WP_User && function_exists( 'wd4_get_compact_user_name' ) ) {
    $resolved_compact = wd4_get_compact_user_name( $author_user );
    if ( '' !== $resolved_compact ) {
        $compact_display_name = $resolved_compact;
    }
}
if ( '' === $compact_display_name && $display_name ) {
    $compact_display_name = $display_name;
}
if ( '' === $compact_display_name ) {
    $compact_display_name = $compact_default_member_name;
}

$is_own_profile = $author_id && is_user_logged_in() && get_current_user_id() === $author_id;




$avatar_error            = '';
$profile_update_message = '';
$profile_update_error   = '';

if ( $is_own_profile && isset( $_GET['profile-updated'] ) ) {
    $profile_flag = sanitize_text_field( wp_unslash( $_GET['profile-updated'] ) );

    if ( '1' === $profile_flag ) {
        $profile_update_message = __( 'Profile details updated.', 'foxiz-child' );
    }
}

/**
 * Handle compact name updates for the profile owner.
 */
if (
    $is_own_profile &&
    isset( $_POST['wd4_profile_fields_nonce'] )
) {
    $profile_nonce = sanitize_text_field( wp_unslash( $_POST['wd4_profile_fields_nonce'] ) );

    if ( wp_verify_nonce( $profile_nonce, 'wd4_update_profile_fields' ) ) {
        $new_first_name = isset( $_POST['wd4_profile_first_name'] )
            ? sanitize_text_field( wp_unslash( $_POST['wd4_profile_first_name'] ) )
            : '';
        $new_short_name = isset( $_POST['wd4_profile_short_name'] )
            ? sanitize_text_field( wp_unslash( $_POST['wd4_profile_short_name'] ) )
            : '';

        update_user_meta( $author_id, 'first_name', $new_first_name );
        update_user_meta( $author_id, 'wd4_short_display_name', $new_short_name );

        $redirect_url = add_query_arg( 'profile-updated', '1', get_author_posts_url( $author_id ) );

        wp_safe_redirect( $redirect_url );
        exit;
    } else {
        $profile_update_error = __( 'We could not verify your request. Please try again.', 'foxiz-child' );
    }
}





/**
 * Handle front-end avatar upload for the profile owner.
 */
if (
    $is_own_profile &&
    current_user_can( 'upload_files' ) &&
    ! empty( $_FILES['wd4_author_avatar']['name'] )
) {
    if (
        isset( $_POST['wd4_author_avatar_nonce'] ) &&
        wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['wd4_author_avatar_nonce'] ) ),
            'wd4_update_author_avatar'
        )
    ) {
        $file = $_FILES['wd4_author_avatar'];

        // 1) Basic upload error check.
        if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
            $avatar_error = __( 'Upload failed. Please try again.', 'foxiz-child' );
        } else {
            // 2) Enforce max file size: 400 KB.
            $max_size_bytes = 400 * 1024; // 400 KB
            if ( (int) $file['size'] > $max_size_bytes ) {
                $avatar_error = __( 'Image too large. Please upload an image under 400 KB.', 'foxiz-child' );
            } else {
                // 3) Allow only safe image types.
                $allowed_mimes = array(
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png'          => 'image/png',
                    'gif'          => 'image/gif',
                    'webp'         => 'image/webp',
                );

                $filetype = wp_check_filetype_and_ext(
                    $file['tmp_name'],
                    $file['name'],
                    $allowed_mimes
                );

                if ( empty( $filetype['ext'] ) || empty( $filetype['type'] ) ) {
                    $avatar_error = __( 'Invalid file type. Please upload a JPG, PNG, GIF, or WEBP image.', 'foxiz-child' );
                } elseif ( 0 !== strpos( $filetype['type'], 'image/' ) ) {
                    // MIME type must be image/*.
                    $avatar_error = __( 'Invalid file type. Only image files are allowed.', 'foxiz-child' );
                } else {
                    // 4) Extra safety: verify it’s really an image.
                    $image_info = @getimagesize( $file['tmp_name'] );
                    if ( ! is_array( $image_info ) ) {
                        $avatar_error = __( 'The uploaded file is not a valid image.', 'foxiz-child' );
                    }
                }
            }
        }

        // Only proceed to upload if everything above passed.
        if ( '' === $avatar_error ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $attachment_id = media_handle_upload(
                'wd4_author_avatar',
                0,
                array(
                    'post_title' => $display_name ? $display_name . ' avatar' : 'Author avatar',
                )
            );

            if ( is_wp_error( $attachment_id ) ) {
                $avatar_error = $attachment_id->get_error_message();
            } else {
                update_user_meta( $author_id, 'author_image_id', (int) $attachment_id );
                // Avoid resubmission on refresh.
                wp_safe_redirect( get_author_posts_url( $author_id ) );
                exit;
            }
        }
    } else {
        $avatar_error = __( 'We could not verify your request. Please refresh and try again.', 'foxiz-child' );
    }
}


$job         = $author_id ? get_the_author_meta( 'job', $author_id ) : '';
$description = $author_id ? get_the_author_meta( 'description', $author_id ) : '';
$website     = $author_id ? get_the_author_meta( 'user_url', $author_id ) : '';

/**
 * HERO AVATAR
 * - Prefer local author_image_id with WordPress srcset.
 * - Fallback to Gravatar with small srcset (160 / 320).
 */

$avatar_img_html = '';
$author_user     = null;

if ( $author_id ) {
    $avatar_meta_id = (int) get_the_author_meta( 'author_image_id', $author_id );
    if ( $avatar_meta_id ) {
        // Use WP responsive image system for local avatar.
        $avatar_img_html = wp_get_attachment_image(
            $avatar_meta_id,
            'large',
            false,
            array(
                'class'         => 'wd4-author-hero__image',
                'loading'       => 'eager',
                'decoding'      => 'async',
                'fetchpriority' => 'high',
                'sizes'         => '(max-width: 480px) 260px, (max-width: 1024px) 320px, 380px',
            )
        );
    }
}





if ( ! $avatar_img_html && $author_id ) {
    // Gravatar fallback with small srcset to reduce LCP weight.
    if ( ! $author_user ) {
        $author_user = get_user_by( 'id', $author_id );
    }
    $default_avatar_url = '';

    if ( function_exists( 'wd4_get_default_avatar_url' ) ) {
        $default_avatar_url = wd4_get_default_avatar_url( $author_user );
    }

    if ( empty( $default_avatar_url ) ) {
        $default_avatar_url = 'mm';
    }

    $avatar_160 = get_avatar_url(
        $author_id,
        array(
            'size'    => 160,
            'default' => $default_avatar_url,
        )
    );
    $avatar_320 = get_avatar_url(
        $author_id,
        array(
            'size'    => 320,
            'default' => $default_avatar_url,
        )
    );

    $avatar_srcset     = sprintf(
        '%1$s 160w, %2$s 320w',
        esc_url( $avatar_160 ),
        esc_url( $avatar_320 )
    );
    
    
    
    
    
    
    $avatar_sizes_attr = '(max-width: 480px) 260px, (max-width: 1024px) 320px, 380px';
    $avatar_alt        = $display_name ? $display_name : __( 'Author avatar', 'foxiz-child' );

    $avatar_img_html   = sprintf(
        '<img class="wd4-author-hero__image" src="%1$s" srcset="%2$s" sizes="%3$s" alt="%4$s" width="320" height="320" loading="eager" decoding="async" fetchpriority="high" />',
        esc_url( $avatar_320 ),
        esc_attr( $avatar_srcset ),
        esc_attr( $avatar_sizes_attr ),
        esc_attr( $avatar_alt )
    );
}

$greeting_text = $author_id ? get_user_meta( $author_id, 'profile_greeting', true ) : '';
$greeting_text = $greeting_text ? $greeting_text : __( 'Hello!', 'foxiz-child' );

$hero_name_title = '';
if ( $display_name ) {
    $hero_name_title = $display_name;
} elseif ( $compact_display_name && $compact_default_member_name !== $compact_display_name ) {
    $hero_name_title = $compact_display_name;
}

$headline_role = $job ? wp_strip_all_tags( $job ) : '';

if ( ! $headline_role ) {
    if ( $author_user && user_can( $author_user, 'manage_options' ) ) {
        $headline_role = __( 'Author & Creator', 'foxiz-child' );
    } else {
        $headline_role = __( 'Subscriber', 'foxiz-child' );
    }
}

$testimonial_text = $author_id ? get_user_meta( $author_id, 'profile_testimonial', true ) : '';
if ( ! $testimonial_text && $description ) {
    $testimonial_text = wp_trim_words( wp_strip_all_tags( $description ), 40, '&hellip;' );
}

/**
 * Bio text block for hero (used especially on mobile/tablet).
 */
$bio_text = $author_id ? get_user_meta( $author_id, 'profile_bio', true ) : '';
if ( ! $bio_text && $description ) {
    $bio_text = wp_strip_all_tags( $description );
}
if ( ! $bio_text ) {
    $bio_text = $display_name
        ? sprintf( __( '%s has not added a bio yet.', 'foxiz-child' ), $display_name )
        : __( 'This contributor has not added a bio yet.', 'foxiz-child' );
}

$stat_primary_value = $author_id ? get_user_meta( $author_id, 'profile_stat_primary_value', true ) : '';
$stat_primary_label = $author_id ? get_user_meta( $author_id, 'profile_stat_primary_label', true ) : '';
if ( ! $stat_primary_value ) {
    $post_count         = $author_id ? count_user_posts( $author_id ) : 0;
    $stat_primary_value = number_format_i18n( (int) $post_count );
}
if ( ! $stat_primary_label ) {
    $stat_primary_label = __( 'Published Pieces', 'foxiz-child' );
}

$registered_timestamp = 0;
if ( $author_id ) {
    $registered = get_the_author_meta( 'user_registered', $author_id );
    if ( $registered ) {
        $registered_timestamp = strtotime( $registered );
    }
}

$experience_years = $author_id ? get_user_meta( $author_id, 'profile_experience_years', true ) : '';
if ( ! $experience_years && $registered_timestamp ) {
    $diff_seconds     = time() - $registered_timestamp;
    $years_calculated = (int) floor( $diff_seconds / YEAR_IN_SECONDS );
    if ( $years_calculated < 1 ) {
        $years_calculated = 1;
    }
    $experience_years = (string) $years_calculated;
}
if ( ! $experience_years ) {
    $experience_years = '5';
}

$experience_label = $author_id ? get_user_meta( $author_id, 'profile_experience_label', true ) : '';
if ( ! $experience_label ) {
    $experience_label = __( 'Years Experience', 'foxiz-child' );
}

$rating_value = $author_id ? get_user_meta( $author_id, 'profile_rating', true ) : '';
if ( ! $rating_value ) {
    $rating_value = '5';
}
$rating_value = (int) max( 0, min( 5, (int) $rating_value ) );

$cta_primary_label = $author_id ? get_user_meta( $author_id, 'profile_cta_primary_label', true ) : '';
$cta_primary_url   = $author_id ? get_user_meta( $author_id, 'profile_cta_primary_url', true ) : '';
if ( ! $cta_primary_label ) {
    $cta_primary_label = __( 'Contact Me', 'foxiz-child' );
}
if ( ! $cta_primary_url ) {
    $cta_primary_url = $website;
}

$cta_secondary_label = $author_id ? get_user_meta( $author_id, 'profile_cta_secondary_label', true ) : '';
$cta_secondary_url   = $author_id ? get_user_meta( $author_id, 'profile_cta_secondary_url', true ) : '';
if ( ! $cta_secondary_label ) {
    $cta_secondary_label = __( 'Follow', 'foxiz-child' );
}
if ( ! $cta_secondary_url ) {
    $cta_secondary_url = $author_id ? get_author_feed_link( $author_id ) : '';
}

$show_primary_cta   = $cta_primary_url;
$show_secondary_cta = $cta_secondary_url;


$is_admin_profile = $author_user && user_can( $author_user, 'manage_options' );

$account_age_seconds = $registered_timestamp ? time() - $registered_timestamp : 0;
$account_age_months  = $account_age_seconds > 0 ? floor( $account_age_seconds / MONTH_IN_SECONDS ) : 0;

$subscriber_level            = '';
$subscriber_level_star       = 0;
$subscriber_duration_message = '';

if ( ! $is_admin_profile ) {
    if ( $account_age_months >= 12 ) {
        $subscriber_level      = __( 'Level 5', 'foxiz-child' );
        $subscriber_level_star = 5;
        $subscriber_duration_message = __( 'Member for 1+ years', 'foxiz-child' );
    } elseif ( $account_age_months >= 3 ) {
        $subscriber_level      = __( 'Level 3', 'foxiz-child' );
        $subscriber_level_star = 3;
        $subscriber_duration_message = __( 'Member for 3+ months', 'foxiz-child' );
    } elseif ( $account_age_months >= 1 ) {
        $subscriber_level      = __( 'Level 2', 'foxiz-child' );
        $subscriber_level_star = 2;
        $subscriber_duration_message = __( 'Member for 1+ month', 'foxiz-child' );
    } else {
        $subscriber_level      = __( 'Level 1', 'foxiz-child' );
        $subscriber_level_star = 1;
        $subscriber_duration_message = __( 'New member', 'foxiz-child' );
    }
}



?>


<main id="primary" class="site-main wd4-author-profile wd4-frontpage">
    <div class="wd4-author-profile__container">
        <section class="wd4-author-hero" aria-labelledby="wd4-author-hero-title">
            <div class="wd4-author-hero__greeting">
                <span class="wd4-author-hero__greeting-bubble">
                    <?php echo esc_html( $greeting_text ); ?>
                    <svg class="wd4-author-hero__greeting-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 12L6 9M6 9L3 6M6 9H21" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                        <path d="M3 18L6 15M6 15L3 12M6 15H15" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                    </svg>
                </span>
            </div>

            <header class="wd4-author-hero__header">
                <h1 class="wd4-author-hero__title" id="wd4-author-hero-title">
                    <span class="wd4-author-hero__title-dark">
                        <?php esc_html_e( "I'm", 'foxiz-child' ); ?>
                    </span>
                    <span class="wd4-author-hero__title-accent"<?php echo $hero_name_title ? ' title="' . esc_attr( $hero_name_title ) . '"' : ''; ?>>
                        <?php echo esc_html( $compact_display_name ); ?>
                    </span>
                </h1>
                <p class="wd4-author-hero__subtitle">
                    <?php echo esc_html( $headline_role ); ?>
                </p>
            </header>

            <div class="wd4-author-hero__content">
                <?php if ( $testimonial_text ) : ?>
                    <aside class="wd4-author-hero__testimonial">
                        <span class="wd4-author-hero__quote-mark" aria-hidden="true">&quot;</span>
                        <p class="wd4-author-hero__testimonial-text">
                            <?php echo esc_html( $testimonial_text ); ?>
                        </p>
                        <div class="wd4-author-hero__stat-block">
                            <span class="wd4-author-hero__stat-number">
                                <?php echo esc_html( $stat_primary_value ); ?>+
                            </span>
                            <span class="wd4-author-hero__stat-label">
                                <?php echo esc_html( $stat_primary_label ); ?>
                            </span>
                        </div>
                    </aside>
                <?php endif; ?>

                <div class="wd4-author-hero__circle" role="presentation">
                    <span class="wd4-author-hero__circle-glow"></span>
                    <?php if ( $avatar_img_html ) : ?>
                        <?php echo $avatar_img_html; ?>
                    <?php endif; ?>

                    <svg class="wd4-author-hero__icon-right" viewBox="0 0 60 60" aria-hidden="true">
                        <path d="M15 20 L25 15 L20 25 Z" fill="currentColor" />
                        <path d="M25 30 L35 25 L30 35 Z" fill="currentColor" />
                        <path d="M35 15 L45 10 L40 20 Z" fill="currentColor" />
                    </svg>

                    <svg class="wd4-author-hero__icon-left" viewBox="0 0 40 40" aria-hidden="true">
                        <path d="M5 20 C5 20, 8 10, 15 15 C22 20, 18 30, 10 25 Z" stroke="currentColor" stroke-width="2" fill="none" />
                    </svg>
                </div>

                <?php if ( $bio_text ) : ?>
                    <p class="wd4-author-hero__bio">
                        <?php echo esc_html( $bio_text ); ?>
                    </p>
                <?php endif; ?>

                <div class="wd4-author-hero__buttons">
                    <?php if ( $show_primary_cta ) : ?>
                        <a
                            class="wd4-author-hero__btn wd4-author-hero__btn--primary"
                            href="<?php echo esc_url( $cta_primary_url ); ?>"
                            target="_blank"
                            rel="noopener"
                        >
                            <?php echo esc_html( $cta_primary_label ); ?>
                            <svg class="wd4-author-hero__btn-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M7 17L17 7M17 7H7M17 7V17" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if ( $show_secondary_cta ) : ?>
                        <a
                            class="wd4-author-hero__btn wd4-author-hero__btn--secondary"
                            href="<?php echo esc_url( $cta_secondary_url ); ?>"
                        >
                            <?php echo esc_html( $cta_secondary_label ); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ( $is_own_profile && current_user_can( 'upload_files' ) ) : ?>
                    <form class="wd4-author-avatar-form" method="post" enctype="multipart/form-data">
                        <label class="wd4-author-avatar-form__label">
                            <?php esc_html_e( 'Update profile image', 'foxiz-child' ); ?>
                            <input type="file" name="wd4_author_avatar" accept="image/*" />
                        </label>
                        <?php wp_nonce_field( 'wd4_update_author_avatar', 'wd4_author_avatar_nonce' ); ?>
                        <button type="submit" class="wd4-author-avatar-form__submit">
                            <?php esc_html_e( 'Save', 'foxiz-child' ); ?>
                        </button>
                        <?php if ( $avatar_error ) : ?>
                            <p class="wd4-author-avatar-form__error">
                                <?php echo esc_html( $avatar_error ); ?>
                            </p>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>





               
               
                <?php if ( $is_admin_profile ) : ?>
                    <aside class="wd4-author-hero__stats-right" aria-label="<?php esc_attr_e( 'Author accolades', 'foxiz-child' ); ?>">
                        <div
                            class="wd4-author-hero__stars"
                            role="img"
                            aria-label="<?php printf( esc_attr__( '%d star rating', 'foxiz-child' ), $rating_value ); ?>"
                        >
                            <?php for ( $i = 0; $i < $rating_value; $i++ ) : ?>
                                <svg class="wd4-author-hero__star" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <div class="wd4-author-hero__experience-number">
                            <?php echo esc_html( $experience_years ); ?> <?php esc_html_e( 'Years', 'foxiz-child' ); ?>
                        </div>
                        <div class="wd4-author-hero__experience-label">
                            <?php echo esc_html( $experience_label ); ?>
                        </div>
                    </aside>
                <?php elseif ( $subscriber_level ) : ?>
                    <div class="wd4-author-hero__subscriber-card" aria-label="<?php esc_attr_e( 'Subscriber status', 'foxiz-child' ); ?>">
                        <span class="wd4-author-hero__subscriber-level">
                            <?php echo esc_html( $subscriber_level ); ?>
                        </span>
                        <div class="wd4-author-hero__subscriber-stars" aria-hidden="true">
                            <?php for ( $i = 0; $i < $subscriber_level_star; $i++ ) : ?>
                                <svg class="wd4-author-hero__star" viewBox="0 0 24 24">
                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <?php if ( $subscriber_duration_message ) : ?>
                            <span class="wd4-author-hero__subscriber-duration">
                                <?php echo esc_html( $subscriber_duration_message ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
               
               
               
               
               
               
            </div>
        </section>
    </div>

    <div class="container">
        <div class="mainContainer">
            <section class="wd4-author-feed">
                <?php if ( have_posts() ) : ?>
                    <div class="secHdg">
                        <span class="hdgStyle">
                            <span><?php esc_html_e( 'More stories', 'foxiz-child' ); ?></span>
                        </span>
                    </div>
                    <div class="sortDec">
                        <?php
                        printf(
                            esc_html__( 'Latest posts and guides from %s.', 'foxiz-child' ),
                            esc_html( $display_name ? $display_name : get_the_author() )
                        );
                        ?>
                    </div>

                    <div class="wd4-author-feed__list">
                        <?php
                        $wd4_rendered_posts = 0;

                        while ( have_posts() && $wd4_rendered_posts < 4 ) :
                            the_post();
                            $wd4_rendered_posts++;

                            $primary_category      = get_the_category();
                            $primary_category      = ! empty( $primary_category ) ? $primary_category[0] : null;
                            $primary_category_link = $primary_category instanceof WP_Term ? get_category_link( $primary_category ) : '';

                            $meta_segments = array( get_the_date() );
                            $reading_time  = function_exists( 'foxiz_reading_time' ) ? foxiz_reading_time( get_the_ID() ) : '';
                            if ( $reading_time ) {
                                $meta_segments[] = $reading_time;
                            }
                            ?>
                            <article <?php post_class( 'cartHolder listView timeAgo' ); ?>>
                                <a
                                    class="storyLink"
                                    href="<?php the_permalink(); ?>"
                                    aria-label="<?php printf( esc_attr__( 'Read "%s"', 'foxiz-child' ), wp_strip_all_tags( get_the_title() ) ); ?>"
                                ></a>

                                <figure>
                                    <?php
                                    if ( has_post_thumbnail() ) {
                                        echo wd4_frontpage_image(
                                            get_the_ID(),
                                            'wd4-frontpage-feed',
                                            array(
                                                'class'            => 'wp-post-image',
                                                'loading'          => 'lazy',
                                                'decoding'         => 'async',
                                                'sizes'            => implode(
                                                    ', ',
                                                    array(
                                                        '(max-width: 767px) 110px',
                                                        '(max-width: 1023px) 150px',
                                                        '180px',
                                                    )
                                                ),
                                                'max_srcset_width' => 240,
                                            )
                                        );
                                    }
                                    ?>
                                </figure>

                                <h2 class="hdg3">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <div class="cardMeta">
                                    <?php if ( $primary_category instanceof WP_Term && ! is_wp_error( $primary_category_link ) ) : ?>
                                        <a class="cardMeta__cat" href="<?php echo esc_url( $primary_category_link ); ?>">
                                            <?php echo esc_html( $primary_category->name ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <span class="cardMeta__time">
                                        <?php echo esc_html( implode( ' · ', array_filter( $meta_segments ) ) ); ?>
                                    </span>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>

                    <?php
                    the_posts_pagination(
                        array(
                            'mid_size'  => 1,
                            'prev_text' => esc_html__( 'Prev', 'foxiz-child' ),
                            'next_text' => esc_html__( 'Next', 'foxiz-child' ),
                        )
                    );
                    ?>
                <?php else : ?>
                    <div class="cartHolder">
                        <div class="sortDec wd4-emptyFeed">
                            <?php esc_html_e( 'There are no posts to display for this author yet.', 'foxiz-child' ); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php
get_footer();
