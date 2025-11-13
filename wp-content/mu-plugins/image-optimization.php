<?php
/**
 * Plugin Name: WD4 Image Optimization
 * Description: Frontend image variants, sizes, and logo optimizations.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Limit a srcset string to candidates that are no wider than the provided
 * maximum. This keeps high-density devices from grabbing 768px variants when
 * a 300px asset is all we need for the front-page cards.
 */
function wd4_frontpage_limit_srcset_width( string $srcset, int $max_width ): string {
    if ( '' === $srcset || $max_width <= 0 ) {
        return $srcset;
    }

    $candidates = array_filter( array_map( 'trim', explode( ',', $srcset ) ) );
    if ( empty( $candidates ) ) {
        return $srcset;
    }

    $filtered = array();

    foreach ( $candidates as $candidate ) {
        if ( preg_match( '/\s(\d+)w$/', $candidate, $matches ) ) {
            $width = (int) $matches[1];

            if ( $width <= $max_width ) {
                $filtered[] = $candidate;
            }
        }
    }

    return $filtered ? implode( ', ', $filtered ) : $srcset;
}

/**
 * Map logical context sizes to on-disk variant definitions.
 */
function wd4_frontpage_variant_definitions(): array {
    return array(
        'wd4-frontpage-hero' => array(
            array(
                'key'    => 'wd4-frontpage-hero',
                'width'  => 960,
                'height' => 0,
                'crop'   => false,
            ),
            array(
                'key'    => 'wd4-frontpage-hero-720',
                'width'  => 720,
                'height' => 0,
                'crop'   => false,
            ),
            array(
                'key'    => 'wd4-frontpage-hero-640',
                'width'  => 640,
                'height' => 0,
                'crop'   => false,
            ),
            array(
                'key'    => 'wd4-frontpage-hero-480',
                'width'  => 480,
                'height' => 0,
                'crop'   => false,
            ),
        ),
        'wd4-frontpage-feed' => array(
            array(
                'key'    => 'wd4-frontpage-feed',
                'width'  => 360,
                'height' => 0,
                'crop'   => false,
            ),
            array(
                'key'    => 'wd4-frontpage-feed-240',
                'width'  => 240,
                'height' => 0,
                'crop'   => false,
            ),
        ),
        'wd4-frontpage-slider' => array(
            array(
                'key'    => 'wd4-frontpage-slider-600',
                'width'  => 600,
                'height' => 0,
                'crop'   => false,
            ),
            array(
                'key'    => 'wd4-frontpage-slider',
                'width'  => 420,
                'height' => 0,
                'crop'   => false,
            ),
            array(
                'key'    => 'wd4-frontpage-slider-280',
                'width'  => 280,
                'height' => 0,
                'crop'   => false,
            ),
        ),
        'wd4-frontpage-logo' => array(
            array(
                'key'    => 'wd4-frontpage-logo',
                'width'  => 220,
                'height' => 0,
                'crop'   => false,
            ),
            array(
                'key'    => 'wd4-frontpage-logo-2x',
                'width'  => 440,
                'height' => 0,
                'crop'   => false,
            ),
        ),
    );
}

/**
 * Ensure the requested attachment size has lightweight variants available.
 */
function wd4_frontpage_prime_variants( int $attachment_id, string $size ): void {
    static $primed = array();

    if ( isset( $primed[ $attachment_id ][ $size ] ) ) {
        return;
    }

    $definitions = wd4_frontpage_variant_definitions();
    if ( ! isset( $definitions[ $size ] ) ) {
        $primed[ $attachment_id ][ $size ] = true;

        return;
    }

    if ( ! function_exists( 'wp_get_image_editor' ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    foreach ( $definitions[ $size ] as $variant ) {
        wd4_frontpage_ensure_variant( $attachment_id, $variant );
    }

    $primed[ $attachment_id ][ $size ] = true;
}

/**
 * Create a resized attachment copy if it does not already exist.
 */
function wd4_frontpage_ensure_variant( int $attachment_id, array $variant ): void {
    $size_key = isset( $variant['key'] ) ? (string) $variant['key'] : '';
    $width    = isset( $variant['width'] ) ? (int) $variant['width'] : 0;
    $height   = isset( $variant['height'] ) ? (int) $variant['height'] : 0;
    $crop     = ! empty( $variant['crop'] );

    if ( ! $size_key || $width <= 0 ) {
        return;
    }

    $metadata = wp_get_attachment_metadata( $attachment_id );
    if ( ! is_array( $metadata ) || empty( $metadata['file'] ) ) {
        return;
    }

    if ( isset( $metadata['sizes'][ $size_key ] ) ) {
        return;
    }

    $uploads = wp_upload_dir();
    if ( ! empty( $uploads['error'] ) ) {
        return;
    }

    $relative_path = $metadata['file'];
    $absolute_path = trailingslashit( $uploads['basedir'] ) . $relative_path;

    if ( ! file_exists( $absolute_path ) ) {
        return;
    }

    $editor = wp_get_image_editor( $absolute_path );
    if ( is_wp_error( $editor ) ) {
        return;
    }

    if ( method_exists( $editor, 'set_quality' ) ) {
        $editor->set_quality( 82 );
    }

    $resize_height = $height > 0 ? $height : null;
    $resize        = $editor->resize( $width, $resize_height, $crop );
    if ( is_wp_error( $resize ) ) {
        return;
    }

    $dest_file = $editor->generate_filename( $size_key );
    $saved     = $editor->save( $dest_file );
    if ( is_wp_error( $saved ) ) {
        return;
    }

    $metadata['sizes'][ $size_key ] = array(
        'file'      => wp_basename( $saved['path'] ),
        'width'     => (int) $saved['width'],
        'height'    => (int) $saved['height'],
        'mime-type' => $saved['mime-type'],
    );

    wp_update_attachment_metadata( $attachment_id, $metadata );
}

/**
 * Generate a constrained attachment image tailored for the custom front page.
 */
function wd4_frontpage_image( int $post_id, string $size, array $args = array() ): string {
    $attachment_id = get_post_thumbnail_id( $post_id );

    if ( ! $attachment_id ) {
        return '';
    }

    return wd4_generate_image_markup( $attachment_id, $size, $args, $post_id );
}

/**
 * Generate a tailored `<img>` tag for a specific attachment.
 */
function wd4_generate_image_markup( int $attachment_id, string $size, array $args = array(), int $post_id = 0 ): string {
    if ( ! $attachment_id ) {
        return '';
    }

    wd4_frontpage_prime_variants( $attachment_id, $size );

    $image = wp_get_attachment_image_src( $attachment_id, $size );
    if ( ! $image ) {
        return '';
    }

    $defaults = array(
        'class'            => '',
        'loading'          => 'lazy',
        'decoding'         => 'async',
        'sizes'            => '',
        'fetchpriority'    => '',
        'max_srcset_width' => 0,
    );

    $args = wp_parse_args( $args, $defaults );

    $attributes = array(
        'src'      => $image[0],
        'width'    => (int) $image[1],
        'height'   => (int) $image[2],
        'loading'  => $args['loading'],
        'decoding' => $args['decoding'],
    );

    if ( $args['class'] ) {
        $attributes['class'] = $args['class'];
    }

    if ( $args['sizes'] ) {
        $attributes['sizes'] = $args['sizes'];
    }

    if ( $args['fetchpriority'] ) {
        $attributes['fetchpriority'] = $args['fetchpriority'];
    }

    $alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
    if ( '' === $alt_text && $post_id > 0 ) {
        $alt_text = get_the_title( $post_id );
    }
    $attributes['alt'] = wp_strip_all_tags( (string) $alt_text );

    $srcset = wp_get_attachment_image_srcset( $attachment_id, $size );
    if ( $srcset ) {
        $max_width = (int) $args['max_srcset_width'];
        if ( $max_width > 0 ) {
            $srcset = wd4_frontpage_limit_srcset_width( $srcset, $max_width );
        }

        $attributes['srcset'] = $srcset;
    }

    $html_parts = array();
    foreach ( $attributes as $name => $value ) {
        if ( '' === $value || null === $value ) {
            continue;
        }

        $html_parts[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
    }

    if ( empty( $html_parts ) ) {
        return '';
    }

    return '<img ' . implode( ' ', $html_parts ) . ' />';
}

/**
 * Swap the site logo for a lighter-weight responsive variant.
 */
function wd4_optimize_custom_logo_markup( string $html ): string {
    if ( function_exists( 'wd4_is_front_context' ) && ! wd4_is_front_context() ) {
        return $html;
    }

    $logo_id = (int) get_theme_mod( 'custom_logo' );
    if ( ! $logo_id ) {
        return $html;
    }

    $image = wd4_generate_image_markup(
        $logo_id,
        'wd4-frontpage-logo',
        array(
            'class'            => 'custom-logo',
            'loading'          => 'eager',
            'decoding'         => 'async',
            'sizes'            => '(max-width: 767px) 150px, 180px',
            'max_srcset_width' => 480,
        )
    );

    if ( '' === $image ) {
        return $html;
    }

    $home_url  = esc_url( home_url( '/' ) );
    $site_name = get_bloginfo( 'name', 'display' );

    return sprintf(
        '<a href="%1$s" class="custom-logo-link" rel="home" aria-label="%2$s">%3$s</a>',
        $home_url,
        esc_attr( $site_name ),
        $image
    );
}
add_filter( 'get_custom_logo', 'wd4_optimize_custom_logo_markup' );

/**
 * Upgrade the first above-the-fold thumbnail to an eager, high-priority fetch so
 * the browser discovers the Largest Contentful Paint image immediately.
 */
function wd4_promote_first_feed_thumbnail( array $attr, $attachment, $size ): array {
    unset( $attachment, $size );

    if ( function_exists( 'wd4_is_front_context' ) && ! wd4_is_front_context() ) {
        return $attr;
    }

    if ( ! ( is_front_page() || is_home() ) ) {
        return $attr;
    }

    if ( empty( $attr['loading'] ) || 'lazy' !== $attr['loading'] ) {
        return $attr;
    }

    static $promoted = false;

    if ( $promoted ) {
        return $attr;
    }

    $attr['loading']       = 'eager';
    $attr['fetchpriority'] = 'high';
    $attr['decoding']      = 'async';

    $promoted = true;

    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'wd4_promote_first_feed_thumbnail', 20, 3 );

/**
 * -------------------------------------------------------------------------
 * Images: attributes + sizes
 * -------------------------------------------------------------------------
 */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment, $size ) {
    // Only touch non-LCP images on single posts
    if ( is_singular( 'post' ) && $attachment->ID !== get_post_thumbnail_id() ) {
        if ( ! empty( $attr['loading'] ) && $attr['loading'] === 'lazy' ) {
            $attr['fetchpriority'] = 'low';
        }
        $attr['decoding'] = $attr['decoding'] ?? 'async';
    }
    return $attr;
}, 20, 3 );

add_filter( 'wp_calculate_image_sizes', function ( $sizes, $size, $image_src, $image_meta, $attachment_id ) {
    unset( $image_src, $image_meta, $attachment_id );

    if ( is_admin() ) {
        return $sizes;
    }

    $requested = is_array( $size )
        ? ( isset( $size[0] ) ? $size[0] . 'x' . ( $size[1] ?? $size[0] ) : '' )
        : (string) $size;

    if ( is_singular( 'post' ) && ( $requested === 'full' || $requested === 'large' ) ) {
        return '(min-width:1440px) 1200px, (min-width:1024px) 960px, (min-width:700px) 720px, 92vw';
    }

    $map = array(
        'foxiz_crop_g1' => '(max-width:480px) 100vw, 330px',
        'foxiz_crop_g2' => '(max-width:560px) 95vw, 420px',
        'foxiz_crop_g3' => '(max-width:700px) 95vw, 615px',
        'foxiz_crop_o1' => '(max-width:920px) 92vw, 860px',
        'medium'        => '(max-width:800px) 92vw, 300px',
        'medium_large'  => '(max-width:1024px) 92vw, 768px',
        'large'         => '(max-width:1200px) 92vw, 1024px',
    );

    if ( isset( $map[ $requested ] ) ) {
        return $map[ $requested ];
    }

    if ( strpos( $sizes, 'auto,' ) === 0 ) {
        return trim( substr( $sizes, 5 ) );
    }

    return $sizes;
}, 10, 5 );
