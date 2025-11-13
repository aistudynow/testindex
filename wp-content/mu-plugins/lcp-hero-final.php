<?php
/**
 * Plugin Name: MU – LCP Hero (fast paint preload)
 * Description: Preload the single-post featured image as the LCP candidate.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_head', function () {
    if ( ! is_singular( 'post' ) ) return;
    if ( ! has_post_thumbnail() )   return;

    $thumb_id = get_post_thumbnail_id();
    if ( ! $thumb_id ) return;

    $hero_size = 'large';
    $hero      = wp_get_attachment_image_src( $thumb_id, $hero_size );
    if ( ! $hero ) return;

    list( $url, $width, $height ) = $hero;
    $srcset = wp_get_attachment_image_srcset( $thumb_id, $hero_size );

    if ( $srcset ) {
        $sizes = sprintf(
            '(max-width: %1$dpx) 100vw, %1$dpx',
            (int) $width
        );
        ?>
        <link rel="preload"
              as="image"
              fetchpriority="high"
              href="<?php echo esc_url( $url ); ?>"
              imagesrcset="<?php echo esc_attr( $srcset ); ?>"
              imagesizes="<?php echo esc_attr( $sizes ); ?>">
        <?php
    } else {
        ?>
        <link rel="preload"
              as="image"
              fetchpriority="high"
              href="<?php echo esc_url( $url ); ?>">
        <?php
    }
}, 19);
