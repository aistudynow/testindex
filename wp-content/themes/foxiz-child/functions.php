<?php
if ( ! defined( 'ABSPATH' ) ) {
    return;
}



/**
 * Ensure remote Google Fonts fall back to system fonts immediately while the
 * custom font files are still downloading. Without this parameter the browser
 * blocks text rendering until the remote CSS arrives, which hurts the Largest
 * Contentful Paint score on slower connections. Appending `display=swap` keeps
 * the typography identical once the fonts finish loading but lets the initial
 * paint happen using safe fallbacks.
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


















if ( ! function_exists( 'wd4_should_load_remote_fonts' ) ) {
    /**
     * Gatekeeper that lets us short-circuit any Google Fonts requests. The
     * default returns false so headings fall back to fast system fonts, but a
     * filter can re-enable the remote styles if we ever need them again.
     */
    function wd4_should_load_remote_fonts(): bool {
        return (bool) apply_filters( 'wd4_should_load_remote_fonts', false );
    }
}



// Master switch for deferred styles (off by default to prevent late HTML
// mutations that trigger style recalculation bursts).
if ( ! defined( 'WD4_DEFER_STYLES_ENABLED' ) ) {
    define( 'WD4_DEFER_STYLES_ENABLED', false );
}

// Flag to opt into the verbose performance debugging helpers.
if ( ! defined( 'WD4_PERF_DEBUG_ENABLED' ) ) {
    define( 'WD4_PERF_DEBUG_ENABLED', false );
}





/**
 * Determine whether we should adjust front-end only performance helpers.
 */
function wd4_is_front_context(): bool {
    if ( is_admin() ) {
        return false;
    }
    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return false;
    }
    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return false;
    }

    return true;
}








/**
 * Trim the Foxiz share option map so only a compact list of networks render.
 */
function wd4_filter_share_option_matrix( array $options ): array {
    $all_networks = array(
        'facebook',
        'twitter',
        'flipboard',
        'pinterest',
        'whatsapp',
        'linkedin',
        'tumblr',
        'reddit',
        'vk',
        'telegram',
        'threads',
        'bsky',
        'email',
        'copy',
        'print',
        'native',
    );

    $allowed_networks = array( 'twitter', 'linkedin', 'whatsapp', 'copy' );
    $share_groups      = array( 'top', 'left', 'bottom', 'sticky' );

    $options['share_top']    = 0;
    $options['share_left']   = 0;
    $options['share_sticky'] = 0;
    $options['share_bottom'] = 1;

    foreach ( $share_groups as $group ) {
        foreach ( $all_networks as $network ) {
            $key = 'share_' . $group . '_' . $network;

            if ( isset( $options[ $key ] ) ) {
                $options[ $key ] = in_array( $network, $allowed_networks, true ) ? 1 : 0;
            }
        }
    }

    return $options;
}

/**
 * Override the in-memory option store before Foxiz renders the share templates.
 */
function wd4_optimize_theme_share_options(): void {
    if ( ! wd4_is_front_context() ) {
        return;
    }

    if ( ! defined( 'FOXIZ_TOS_ID' ) ) {
        return;
    }

    $options = get_option( FOXIZ_TOS_ID, array() );
    if ( ! is_array( $options ) ) {
        return;
    }

    $trimmed = wd4_filter_share_option_matrix( $options );

    add_filter(
        'pre_option_' . FOXIZ_TOS_ID,
        static function () use ( $trimmed ) {
            return $trimmed;
        }
    );

    $GLOBALS[ FOXIZ_TOS_ID ] = $trimmed;
}
add_action( 'init', 'wd4_optimize_theme_share_options', 8 );



/**
 * Stop the parent theme from requesting remote Google Fonts so the hero title
 * can render with local system fonts immediately.
 */
function wd4_disable_remote_google_fonts(): void {
    if ( ! wd4_is_front_context() ) {
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
 * Generate the list style card markup used on category archives and
 * asynchronous pagination responses.
 */
function wd4_get_category_card_markup( $post ): string {
    $post = get_post( $post );

    if ( ! ( $post instanceof WP_Post ) ) {
        return '';
    }

    $permalink  = get_permalink( $post );
    $title      = get_the_title( $post );
    $title_attr = wp_strip_all_tags( $title );
    $label      = sprintf( __( 'Read "%s"', 'foxiz-child' ), $title_attr );

    $classes = get_post_class( array( 'cartHolder', 'listView', 'timeAgo' ), $post );
    $classes = is_array( $classes ) ? implode( ' ', array_map( 'sanitize_html_class', $classes ) ) : 'cartHolder listView timeAgo';

    $image_html = '';
    if ( has_post_thumbnail( $post ) ) {
        $image_html = wd4_frontpage_image(
            $post->ID,
            'wd4-frontpage-feed',
            array(
                'class'            => 'wp-post-image',
                'loading'          => 'lazy',
                'decoding'         => 'async',
                'sizes'            => implode(
                    ', ',
                    array(
                        '(max-width: 599px) 42vw',
                        '(max-width: 1023px) 172px',
                        '(max-width: 1439px) 188px',
                        '208px',
                    )
                ),
                'max_srcset_width' => 360,
            )
        );
    }

    $primary_category = get_the_category( $post->ID );
    $primary_category = ! empty( $primary_category ) ? $primary_category[0] : null;

    $category_link = '';
    $category_name = '';
    if ( $primary_category instanceof WP_Term ) {
        $category_link = get_category_link( $primary_category );

        if ( ! is_wp_error( $category_link ) ) {
            $category_name = $primary_category->name;
        } else {
            $category_link = '';
        }
    }

    $meta_segments = array( get_the_date( '', $post ) );
    if ( function_exists( 'foxiz_reading_time' ) ) {
        $reading_time = foxiz_reading_time( $post->ID );
        if ( $reading_time ) {
            $meta_segments[] = $reading_time;
        }
    }

    $meta_text = implode( ' · ', array_filter( $meta_segments ) );

    ob_start();
    ?>
    <article class="<?php echo esc_attr( $classes ); ?>">
        <a class="storyLink" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
            <span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
        </a>
        <figure>
            <span>
                <?php if ( $image_html ) : ?>
                    <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>
            </span>
        </figure>

        <h3 class="hdg3"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>

        <div class="cardMeta">
            <?php if ( $category_link && $category_name ) : ?>
                <a class="cardMeta__cat" href="<?php echo esc_url( $category_link ); ?>"><?php echo esc_html( $category_name ); ?></a>
            <?php endif; ?>

            <?php if ( $meta_text ) : ?>
                <span class="cardMeta__time"><?php echo esc_html( $meta_text ); ?></span>
            <?php endif; ?>
        </div>
    </article>
    <?php

    return trim( ob_get_clean() );
}

/**
 * Normalize admin-ajax payloads that may arrive as arrays or JSON strings.
 */
function wd4_normalize_ajax_query_data( $raw ): array {
    if ( is_array( $raw ) ) {
        return wp_unslash( $raw );
    }

    if ( is_string( $raw ) ) {
        $raw = wp_unslash( $raw );

        $decoded = json_decode( $raw, true );
        if ( is_array( $decoded ) ) {
            return $decoded;
        }

        parse_str( $raw, $parsed );
        if ( is_array( $parsed ) ) {
            return $parsed;
        }
    }

    return array();
}

/**
 * AJAX endpoint that powers the category archive infinite scroll.
 */
function wd4_ajax_category_pagination(): void {
    if ( ! function_exists( 'wp_doing_ajax' ) || ! wp_doing_ajax() ) {
        return;
    }

    $nonce_ok = check_ajax_referer( 'foxiz-ajax', 'security', false );
    if ( ! $nonce_ok ) {
        wp_send_json( array( 'content' => '', 'error' => 'invalid_nonce' ) );
    }

    $payload = isset( $_REQUEST['data'] ) ? $_REQUEST['data'] : array();
    $data    = wd4_normalize_ajax_query_data( $payload );

    $paged = isset( $data['page_next'] ) ? (int) $data['page_next'] : ( isset( $data['paged'] ) ? (int) $data['paged'] : 1 );
    if ( $paged < 1 ) {
        $paged = 1;
    }

    $posts_per_page = isset( $data['posts_per_page'] ) ? (int) $data['posts_per_page'] : 0;
    if ( $posts_per_page < 1 ) {
        $posts_per_page = (int) get_option( 'posts_per_page', 10 );
    }

    $taxonomy = isset( $data['entry_tax'] ) ? sanitize_key( (string) $data['entry_tax'] ) : 'category';

    $term_ids = array();
    if ( isset( $data['category'] ) ) {
        if ( is_array( $data['category'] ) ) {
            $term_ids = array_map( 'intval', $data['category'] );
        } else {
            $term_ids = array( (int) $data['category'] );
        }
    }

    $term_ids = array_values( array_filter( $term_ids ) );

    $query_args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'paged'               => $paged,
        'posts_per_page'      => $posts_per_page,
    );

    if ( $term_ids && taxonomy_exists( $taxonomy ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $term_ids,
            ),
        );
    }

    $query   = new WP_Query( $query_args );
    $content = '';

    if ( $query->have_posts() ) {
        foreach ( $query->posts as $post ) {
            $content .= wd4_get_category_card_markup( $post );
        }
    }

    wp_reset_postdata();

    wp_send_json(
        array(
            'content'  => $content,
            'paged'    => $paged,
            'page_max' => (int) $query->max_num_pages,
        )
    );
}
add_action( 'wp_ajax_rblivep', 'wd4_ajax_category_pagination' );
add_action( 'wp_ajax_nopriv_rblivep', 'wd4_ajax_category_pagination' );



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
    if ( ! wd4_is_front_context() ) {
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
 * the browser discovers the Largest Contentful Paint image immediately. The
 * Elementor daily feed cards output their thumbnails with `loading="lazy"` and
 * `fetchpriority="low"`, which delays the request on mobile where the list sits
 * directly under the hero. Switching only the first lazy image to eager keeps
 * overall concurrency low while ensuring Core Web Vitals sees the asset early.
 */
function wd4_promote_first_feed_thumbnail( array $attr, $attachment, $size ): array {
    unset( $attachment, $size );

    if ( ! wd4_is_front_context() ) {
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












if ( ! function_exists( 'foxiz_render_share_list' ) ) {
    /**
     * Render a compact share list that keeps the DOM footprint tiny while
     * preserving the theme class hooks for existing JavaScript behaviours.
     */
    function foxiz_render_share_list( array $settings ) {
        if ( empty( $settings ) ) {
            return;
        }

        $post_id = ! empty( $settings['post_id'] ) ? (int) $settings['post_id'] : get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        $permalink = get_permalink( $post_id );
        if ( ! $permalink ) {
            return;
        }

        $title       = get_the_title( $post_id );
        $encoded_url = rawurlencode( $permalink );
        $encoded_txt = rawurlencode( html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) ) );

        $show_labels = ! empty( $settings['social_name'] );

    
    
    
    
    
        $networks = array(
            'twitter'  => array(
                'class' => 'icon-twitter share-trigger',
                'icon'  => 'rbi-twitter',
                'label' => esc_html__( 'X', 'foxiz-child' ),
                'url'   => sprintf( 'https://twitter.com/intent/tweet?text=%s&url=%s', $encoded_txt, $encoded_url ),
                'attrs' => array( 'data-bound-share' => '1' ),
            ),
            'facebook' => array(
                'class' => 'icon-facebook share-trigger',
                'icon'  => 'rbi-facebook',
                'label' => esc_html__( 'Facebook', 'foxiz-child' ),
                'url'   => sprintf( 'https://www.facebook.com/sharer/sharer.php?u=%s', $encoded_url ),
                'attrs' => array( 'data-bound-share' => '1' ),
            ),
            'telegram' => array(
                'class' => 'icon-telegram share-trigger',
                'icon'  => 'rbi-telegram',
                'label' => esc_html__( 'Telegram', 'foxiz-child' ),
                'url'   => sprintf( 'https://t.me/share/url?url=%s&text=%s', $encoded_url, $encoded_txt ),
                'attrs' => array( 'data-bound-share' => '1' ),
            ),
            'whatsapp' => array(
                'class' => 'icon-whatsapp share-trigger',
                'icon'  => 'rbi-whatsapp',
                'label' => esc_html__( 'WhatsApp', 'foxiz-child' ),
                'url'   => sprintf( 'https://api.whatsapp.com/send?text=%s%%20%s', $encoded_txt, $encoded_url ),
                'attrs' => array(
                    'data-bound-share' => '1',
                ),
            ),
        );

        $output = array();

        foreach ( $networks as $key => $config ) {
            if ( empty( $settings[ $key ] ) ) {
                continue;
            }

            $classes = array( 'share-action', 'rbi', $config['icon'], $config['class'], 'share-' . $key );

            if ( $show_labels ) {
                $classes[] = 'share-action--labeled';
            }

            $attr = array(
                'class'      => implode( ' ', array_map( 'sanitize_html_class', $classes ) ),
                'href'       => esc_url( $config['url'] ),
                'target'     => '_blank',
                'rel'        => 'nofollow noopener',
                'role'       => 'listitem',
                'aria-label' => sprintf( esc_html__( 'Share on %s', 'foxiz-child' ), $config['label'] ),
                'data-title' => $config['label'],
            );

            if ( ! empty( $config['attrs'] ) && is_array( $config['attrs'] ) ) {
                foreach ( $config['attrs'] as $name => $value ) {
                    $attr[ $name ] = $value;
                }
            }

            $attr_string = '';
            foreach ( $attr as $name => $value ) {
                if ( '' === $value ) {
                    continue;
                }

                $attr_string .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
            }

            $icon  = '<span class="share-action__icon" aria-hidden="true"></span>';
            $label = $show_labels ? sprintf( '<span class="share-action__label">%s</span>', esc_html( $config['label'] ) ) : '';

            $output[] = sprintf( '<a%s>%s</a>', $attr_string, $icon . $label );
        }

        echo implode( '', $output );
    }
}

/**
 * Slim down the default comment form to remove rarely used fields.
 */
function wd4_slim_comment_form_fields( array $fields ): array {
    unset( $fields['url'], $fields['cookies'] );
    return $fields;
}
add_filter( 'comment_form_default_fields', 'wd4_slim_comment_form_fields' );

add_filter( 'comment_form_defaults', function ( array $defaults ): array {
    $defaults['comment_notes_before'] = '';
    return $defaults;
} );

add_action( 'wp_enqueue_scripts', function () {
    if ( ! wd4_is_front_context() || ! is_singular() ) {
        return;
    }

    if ( ! comments_open() && 0 === get_comments_number() ) {
        return;
    }

    wp_enqueue_script(
        'wd-comment-toggle',
        get_stylesheet_directory_uri() . '/js/comment-toggle.js',
        [],
        '20241128',
        true
    );
} );










add_filter( 'show_admin_bar', function( $show ) {
    if ( ! is_admin() ) {
        return false; // no admin bar on the front-end
    }
    return $show;
});


if ( WD4_PERF_DEBUG_ENABLED ) {
add_action( 'wp_footer', function () {
    // Only for admins to avoid spamming real users
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <script>
    if ('PerformanceObserver' in window) {
      const po = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (!entry.hadRecentInput && entry.value > 0) {
            console.log('[CLS] value:', entry.value.toFixed(4), 'time:', entry.startTime.toFixed(1));
            console.log('sources:',
              entry.sources.map(s => {
                const el = s.node;
                if (!el) return null;
                const tag = el.tagName ? el.tagName.toLowerCase() : '';
                const id  = el.id ? ('#'+el.id) : '';
                const cls = el.className && el.className.toString
                  ? '.'+el.className.toString().trim().split(/\s+/).join('.')
                  : '';
                return tag + id + cls;
              })
            );
          }
        }
      });
      po.observe({ type: 'layout-shift', buffered: true });
    }
    </script>
    <?php
}, 99 );



// functions.php in child theme

// functions.php in child theme

add_action( 'wp_head', function () {
    // Only run for admins
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <script>
    (function () {
      if (!('performance' in window)) return;

      // Change this to your liking:
      // - /$^/  => ignore nothing (capture all)
      // - /gtag|adsbygoogle/ => ignore GA/AdSense etc
      const IGNORE_STACK = /gtag|adsbygoogle|show_ads_impl|www-embed-player|facebook|twitter|ytimg/i;

      const SLOW_EVENT_THRESHOLD = 10; // ms, for "slow" scroll/resize/etc

      const debugData = {
        layoutReads: [],
        longTasks: [],
        layoutShifts: [],
        scrollResizeHandlers: [],
        scrollResizeCalls: [],
        eventSlowCalls: [],
        mutations: [],
        cssWarnings: [],
        cssAnimEvents: []
      };

      function shouldIgnore(stack) {
        return IGNORE_STACK.test(stack);
      }

      /************* 1) Layout reads (JS forced reflow) *************/
      function recordLayoutRead(label, node) {
        const stack = (new Error()).stack || '';
        if (shouldIgnore(stack)) return;
        debugData.layoutReads.push({
          t: performance.now(),
          label,
          node: node && (node.tagName + (node.id ? ('#' + node.id) : '')),
          stack
        });
      }

      function wrapLayoutGetter(proto, prop) {
        const d = Object.getOwnPropertyDescriptor(proto, prop);
        if (!d || !d.get) return;
        Object.defineProperty(proto, prop, {
          get: function() {
            recordLayoutRead(prop, this);
            return d.get.call(this);
          }
        });
      }

      ['offsetWidth','offsetHeight','clientWidth','clientHeight','scrollTop','scrollLeft']
        .forEach(p => wrapLayoutGetter(Element.prototype, p));

      const _gbcr = Element.prototype.getBoundingClientRect;
      Element.prototype.getBoundingClientRect = function(...a){
        recordLayoutRead('getBoundingClientRect', this);
        return _gbcr.apply(this, a);
      };

      const _gcs = window.getComputedStyle;
      window.getComputedStyle = function(el, ...a){
        recordLayoutRead('getComputedStyle', el);
        return _gcs.call(window, el, ...a);
      };

      /************* 2) Scroll / resize / wheel / touchmove measurement *************/
      const origAdd = EventTarget.prototype.addEventListener;
      EventTarget.prototype.addEventListener = function(type, listener, options){
        if (typeof listener !== 'function') {
          return origAdd.call(this, type, listener, options);
        }

        const stack = (new Error()).stack || '';

        if (['scroll','resize','wheel','touchmove'].includes(type) && !shouldIgnore(stack)) {
          const targetName = this && this.constructor && this.constructor.name;

          // Remember who registered the handler
          debugData.scrollResizeHandlers.push({
            type,
            target: targetName,
            at: (stack.split('\n')[2] || '').trim()
          });

          // Wrap listener to measure each call
          const wrapped = function(...args) {
            const start = performance.now();
            const result = listener.apply(this, args);
            const dur = performance.now() - start;

            debugData.scrollResizeCalls.push({
              type,
              target: targetName,
              dur: Math.round(dur)
            });

            if (dur > SLOW_EVENT_THRESHOLD) {
              debugData.eventSlowCalls.push({
                type,
                target: targetName,
                dur: Math.round(dur),
                at: (stack.split('\n')[2] || '').trim()
              });
            }
            return result;
          };

          return origAdd.call(this, type, wrapped, options);
        }

        return origAdd.call(this, type, listener, options);
      };

      /************* 3) Long tasks (JS > 50 ms) *************/
      if ('PerformanceObserver' in window &&
          PerformanceObserver.supportedEntryTypes &&
          PerformanceObserver.supportedEntryTypes.indexOf('longtask') !== -1) {
        const longTaskObserver = new PerformanceObserver(list => {
          list.getEntries().forEach(e => {
            debugData.longTasks.push({
              t: Math.round(e.startTime),
              dur: Math.round(e.duration),
              name: e.name || 'longtask'
            });
          });
        });
        longTaskObserver.observe({entryTypes:['longtask']});
      }

      /************* 4) Layout shifts (CLS-ish) *************/
      if ('PerformanceObserver' in window &&
          PerformanceObserver.supportedEntryTypes &&
          PerformanceObserver.supportedEntryTypes.indexOf('layout-shift') !== -1) {
        const lsObserver = new PerformanceObserver(list => {
          list.getEntries().forEach(e => {
            if (e.hadRecentInput) return; // ignore user input shifts
            debugData.layoutShifts.push({
              t: Math.round(e.startTime),
              value: e.value
            });
          });
        });
        lsObserver.observe({entryTypes:['layout-shift']});
      }

      /************* 5) CSS animation / transition events *************/
      document.addEventListener('animationstart', function(e){
        debugData.cssAnimEvents.push({
          kind: 'animation',
          name: e.animationName,
          target: e.target.tagName + (e.target.id ? ('#'+e.target.id) : '')
        });
      }, true);

      document.addEventListener('transitionrun', function(e){
        debugData.cssAnimEvents.push({
          kind: 'transition',
          property: e.propertyName,
          target: e.target.tagName + (e.target.id ? ('#'+e.target.id) : '')
        });
      }, true);

      /************* 6) Static CSS scan for “bad” animated properties *************/
      function scanCSSForWarnings() {
  // focus on actual heavy properties
  const badProps = [
    'visibility',
    'top','left','right','bottom',
    'width','height','margin','padding',
    'box-shadow'
  ];

  const warnings = [];
  for (const sheet of document.styleSheets) {
    let rules;
    try { rules = sheet.cssRules; }
    catch(e){ continue; } // cross-origin
    if (!rules) continue;

    const node = sheet.ownerNode;
    // this will show 'main-inline', 'single-inline', etc.
    const src = sheet.href || (node && node.id) || 'inline';

    // ignore admin stuff: dashicons + admin bar
    if (/dashicons\.min\.css|admin-bar\.min\.css/.test(src)) {
      continue;
    }

    for (const rule of rules) {
      if (!rule.style) continue;
      const s        = rule.style;
      const selector = rule.selectorText || '';
      const trProps  = (s.transitionProperty || s.transition || '').toString();
      const animProp = (s.animationName || s.animation || '').toString();

      badProps.forEach(prop => {
        if (trProps.includes(prop)) {
          warnings.push({ type: 'transition', prop, selector, stylesheet: src });
        }
        if (animProp.includes(prop)) {
          warnings.push({ type: 'animation', prop, selector, stylesheet: src });
        }
      });
    }
  }
  debugData.cssWarnings = warnings;
}


      document.addEventListener('DOMContentLoaded', scanCSSForWarnings);

      /************* 7) MutationObserver (DOM changes) *************/
      if ('MutationObserver' in window) {
        const mo = new MutationObserver(list => {
          for (const m of list) {
            if (debugData.mutations.length > 200) break; // keep it sane
            debugData.mutations.push({
              type: m.type,
              target: m.target && (m.target.tagName + (m.target.id ? ('#'+m.target.id) : '')),
              added: m.addedNodes ? m.addedNodes.length : 0,
              removed: m.removedNodes ? m.removedNodes.length : 0,
              attr: m.attributeName || ''
            });
          }
        });
        mo.observe(document.documentElement, {
          childList: true,
          attributes: true,
          subtree: true
        });
      }

      /************* 8) Dump summary *************/
      window.__perfDebug = debugData;

      window.addEventListener('load', () => {
        try {
          console.groupCollapsed('[Perf debug] Layout reads (JS forced reflow)');
          const lr = debugData.layoutReads.map(r => ({
            t: Math.round(r.t),
            read: r.label,
            node: r.node,
            at: (r.stack.split('\n')[2] || '').trim()
          }));
          console.table(lr.slice(0, 50));
          console.groupEnd();

          console.groupCollapsed('[Perf debug] Long tasks (>50ms)');
          console.table(debugData.longTasks);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] Scroll/resize handlers registered');
          console.table(debugData.scrollResizeHandlers);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] Slow scroll/resize/wheel/touchmove calls (> ' + SLOW_EVENT_THRESHOLD + 'ms)');
          console.table(debugData.eventSlowCalls);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] Layout shifts (CLS candidates)');
          console.table(debugData.layoutShifts);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] CSS animation/transition events');
          console.table(debugData.cssAnimEvents);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] CSS warnings (animated heavy properties)');
          console.table(debugData.cssWarnings);
          console.groupEnd();

          console.groupCollapsed('[Perf debug] DOM mutations (first 50)');
          console.table(debugData.mutations.slice(0, 50));
          console.groupEnd();

          console.info('[Perf debug] totals', {
            layoutReads: debugData.layoutReads.length,
            longTasks: debugData.longTasks.length,
            scrollResizeHandlers: debugData.scrollResizeHandlers.length,
            layoutShifts: debugData.layoutShifts.length,
            cssWarnings: debugData.cssWarnings.length,
            cssAnimEvents: debugData.cssAnimEvents.length,
            mutations: debugData.mutations.length,
            slowEventCalls: debugData.eventSlowCalls.length
          });
        } catch(e) {
          console.warn('Perf debug output failed', e);
        }
      });

    })();
    </script>
    <?php
}, 0);

}





add_action('wp_enqueue_scripts', function () {
    if (is_singular('post')) {
        $css = <<<CSS
/* SINGLE FULL-WIDTH – no second column, no negative gutters */
.single-standard-8.without-sidebar .grid-container {
  display: block;
  margin-right: 0 !important;
  margin-left:  0 !important;
}
.single-standard-8.without-sidebar .grid-container > .s-ct,
.single-standard-8.without-sidebar .grid-container > .sidebar-wrap {
  width: 100% !important;
  max-width: 100%;
  padding-right: 0 !important;
  padding-left:  0 !important;
}
@media (min-width: 992px) {
  .single-standard-8.without-sidebar .grid-container > .s-ct {
    flex: 0 0 100% !important;
    width: 100% !important;
  }
  .single-standard-8.without-sidebar .grid-container > .sidebar-wrap {
    display: none !important;
  }
}
/* CLS guards */
.s-ct .single-header { contain: layout paint; }
.s-ct .smeta-extra,
.s-ct .single-right-meta,
.s-ct .t-shared-sec {
  min-height: 36px;
  align-items: center;
}
:root { font-synthesis-weight: auto; }
.s-ct .wp-block-embed:not(.wp-block-embed-youtube) .wp-block-embed__wrapper {
  display: block;
  aspect-ratio: 16/9;
}
CSS;
        wp_add_inline_style('single', $css); // prints after single.css
    }
}, 999);














/**
 * -------------------------------------------------------------------------
 * Images: attributes + sizes
 * -------------------------------------------------------------------------
 */
add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment, $size) {
    // Only touch non-LCP images on single posts
    if ( is_singular('post') && $attachment->ID !== get_post_thumbnail_id() ) {
        if ( !empty($attr['loading']) && $attr['loading'] === 'lazy' ) {
            $attr['fetchpriority'] = 'low';
        }
        $attr['decoding'] = $attr['decoding'] ?? 'async';
    }
    return $attr;
}, 20, 3);

add_filter('wp_calculate_image_sizes', function ($sizes, $size, $image_src, $image_meta, $attachment_id) {
    if ( is_admin() ) { return $sizes; }

    $requested = is_array($size) ? (isset($size[0]) ? $size[0].'x'.($size[1] ?? $size[0]) : '') : (string) $size;

    if ( is_singular('post') && ( $requested === 'full' || $requested === 'large' ) ) {
        return '(min-width:1440px) 1200px, (min-width:1024px) 960px, (min-width:700px) 720px, 92vw';
    }

    $map = [
        'foxiz_crop_g1' => '(max-width:480px) 100vw, 330px',
        'foxiz_crop_g2' => '(max-width:560px) 95vw, 420px',
        'foxiz_crop_g3' => '(max-width:700px) 95vw, 615px',
        'foxiz_crop_o1' => '(max-width:920px) 92vw, 860px',
        'medium'        => '(max-width:800px) 92vw, 300px',
        'medium_large'  => '(max-width:1024px) 92vw, 768px',
        'large'         => '(max-width:1200px) 92vw, 1024px',
    ];

    if ( isset($map[$requested]) ) {
        return $map[$requested];
    }

    if ( strpos($sizes, 'auto,') === 0 ) {
        return trim(substr($sizes, 5));
    }

    return $sizes;
}, 10, 5);













/**
 * -------------------------------------------------------------------------
 * Head cleanup and payload reduction
 * -------------------------------------------------------------------------
 */
function wd4_disable_emojis(): void {
    if ( ! wd4_is_frontend_request() ) return;

    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_print_footer_scripts', 'print_emoji_detection_script' );
    remove_action( 'embed_head', 'print_emoji_detection_script' );
    remove_action( 'embed_print_styles', 'print_emoji_styles' );

    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

    add_filter( 'emoji_svg_url', '__return_false' );
    add_filter( 'option_use_smilies', 'wd4_filter_disable_emojis_option' );
}
add_action( 'init', 'wd4_disable_emojis', 5 );

function wd4_filter_disable_emojis_option(): string { return '0'; }

function wd4_disable_emojis_tinymce( $plugins ) {
    if ( ! wd4_is_frontend_request() ) return $plugins;
    if ( ! is_array( $plugins ) ) return $plugins;
    return array_diff( $plugins, array( 'wpemoji' ) );
}
add_filter( 'tiny_mce_plugins', 'wd4_disable_emojis_tinymce' );

function wd4_remove_emoji_dns_prefetch( array $urls, string $relation_type ): array {
    if ( 'dns-prefetch' !== $relation_type ) return $urls;
    if ( ! wd4_is_frontend_request() ) return $urls;

    $filtered = array();
    foreach ( $urls as $url ) {
        $href = wd4_get_hint_href( $url );
        if ( '' === $href || false === strpos( $href, 's.w.org' ) ) {
            $filtered[] = $url;
        }
    }
    return $filtered;
}
add_filter( 'wp_resource_hints', 'wd4_remove_emoji_dns_prefetch', 9, 2 );

function wd4_cleanup_head_links(): void {
    if ( ! wd4_is_frontend_request() ) return;

    $targets = apply_filters(
        'wd4_head_cleanup_actions',
        array(
            array( 'wp_head', 'feed_links_extra', 3 ),
            array( 'wp_head', 'feed_links', 2 ),
            array( 'wp_head', 'rsd_link', 10 ),
            array( 'wp_head', 'wlwmanifest_link', 10 ),
            array( 'wp_head', 'wp_generator', 10 ),
            array( 'wp_head', 'wp_shortlink_wp_head', 10 ),
            array( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 ),
        )
    );

    foreach ( $targets as $target ) {
        if ( ! is_array( $target ) || count( $target ) < 2 ) continue;

        $hook     = $target[0];
        $callback = $target[1];
        $priority = $target[2] ?? 10;
        $args     = $target[3] ?? 0;

        remove_action( $hook, $callback, $priority, $args );
    }
}
add_action( 'init', 'wd4_cleanup_head_links', 8 );

function wd4_disable_wp_embed(): void {
    if ( ! wd4_is_frontend_request() ) return;
    if ( ! apply_filters( 'wd4_disable_wp_embed', true ) ) return;

    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
    remove_action( 'template_redirect', 'rest_output_link_header', 11 );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );

    add_action( 'wp_enqueue_scripts', 'wd4_deregister_wp_embed', 100 );
}
add_action( 'init', 'wd4_disable_wp_embed', 9 );
function wd4_deregister_wp_embed(): void { wp_deregister_script( 'wp-embed' ); }


/**
 * -------------------------------------------------------------------------
 * Resource hints helpers (preconnect only)
 * -------------------------------------------------------------------------
 */
function wd4_get_hint_href( $hint ): string {
    if ( is_array( $hint ) && isset( $hint['href'] ) ) return trim( (string) $hint['href'] );
    if ( is_string( $hint ) ) return trim( $hint );
    return '';
}

function wd4_extract_origin( string $url ): string {
    $parts = wp_parse_url( $url );
    if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) return '';
    $origin = strtolower( $parts['scheme'] . '://' . $parts['host'] );
    if ( isset( $parts['port'] ) ) $origin .= ':' . $parts['port'];
    return $origin;
}

function wd4_normalize_resource_hint_entry( $entry ): array {
    if ( is_array( $entry ) ) {
        $href = wd4_get_hint_href( $entry );
        if ( '' === $href ) return array();
        $normalized = array( 'href' => $href );
        if ( isset( $entry['as'] ) ) {
            $as = trim( (string) $entry['as'] );
            if ( '' !== $as ) $normalized['as'] = $as;
        }
        if ( isset( $entry['type'] ) ) {
            $type = trim( (string) $entry['type'] );
            if ( '' !== $type ) $normalized['type'] = $type;
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
    if ( '' === $href ) return array();
    return array( 'href' => $href );
}

function wd4_filter_out_resource_hints( array $urls, array $candidates ): array {
    if ( empty( $urls ) || empty( $candidates ) ) return $urls;

    $remove = array();
    foreach ( $candidates as $candidate ) {
        $entry = wd4_normalize_resource_hint_entry( $candidate );
        if ( empty( $entry['href'] ) ) continue;
        $remove[ $entry['href'] ] = true;
    }

    if ( empty( $remove ) ) return $urls;

    $filtered = array();
    foreach ( $urls as $url ) {
        $href = wd4_get_hint_href( $url );
        if ( '' !== $href && isset( $remove[ $href ] ) ) continue;
        $filtered[] = $url;
    }

    return $filtered;
}



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
    if ( null !== $cache ) return $cache;

    if ( is_admin() || is_feed() ) { $cache = array(); return $cache; }
    if ( function_exists('wp_doing_ajax') && wp_doing_ajax() ) { $cache = array(); return $cache; }
    if ( function_exists('is_amp_endpoint') && is_amp_endpoint() ) { $cache = array(); return $cache; }

    $origins = array();
    $home_origin = wd4_extract_origin( home_url( '/' ) );
    if ( $home_origin ) $origins[] = $home_origin;

    // Fonts
    $origins[] = 'https://fonts.googleapis.com';
    $origins[] = array('href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous');
    
    
    
  // Fonts
    if ( wd4_should_load_remote_fonts() ) {
        $origins[] = 'https://fonts.googleapis.com';
        $origins[] = array('href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous');
    }
  
    

    // Defer CSS helper (e.g., Cloudflare)
    if ( wd4_should_defer_styles() ) $origins[] = 'https://cloudflare.com';

    $origins = apply_filters('wd4_preconnect_origins', $origins);

    $normalized = array();
    foreach ( (array) $origins as $origin ) {
        $entry = wd4_normalize_resource_hint_entry($origin);
        if ( empty($entry['href']) ) continue;
        $normalized[$entry['href']] = $entry;
    }
    $cache = array_values($normalized);
    return $cache;
}

function wd4_add_resource_hints( array $urls, string $relation_type ): array {
    if ( is_admin() || is_feed() ) return $urls;
    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) return $urls;
    if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) return $urls;

    if ( 'preconnect' === $relation_type ) {
        $urls = wd4_filter_out_resource_hints( $urls, wd4_collect_preconnect_origins() );
    }
    return $urls;
}
add_filter( 'wp_resource_hints', 'wd4_add_resource_hints', 10, 2 );

function wd4_output_preconnect_links(): void {
    $origins = wd4_collect_preconnect_origins();
    if ( empty( $origins ) ) return;

    foreach ( $origins as $origin ) {
        $href        = $origin['href'];
        $crossorigin = isset( $origin['crossorigin'] ) ? sprintf( " crossorigin='%s'", esc_attr( $origin['crossorigin'] ) ) : '';
        printf( "<link rel='preconnect' href='%s'%s>\n", esc_url( $href ), $crossorigin );
    }
}
add_action( 'wp_head', 'wd4_output_preconnect_links', 3 );







function wd4_strip_head_markup_fragments( string $html ): string {
    if ( false !== stripos( $html, 'application/ld+json' ) ) {
        $updated = preg_replace( '#<script\b[^>]*type=[\"\']application/ld\+json[\"\'][^>]*>.*?</script>#is', '', $html );
        if ( is_string( $updated ) ) {
            $html = $updated;
        }
    }

    if ( false !== stripos( $html, 'icons.woff2' ) ) {
        $updated = preg_replace( '#<link\b[^>]*rel=[\"\']preload[\"\'][^>]*icons\.woff2[^>]*>#i', '', $html );
        if ( is_string( $updated ) ) {
            $html = $updated;
        }
    }

    return $html;
}

function wd4_enable_head_markup_scrubber(): void {
    if ( function_exists( 'wd4_is_frontend_request' ) ) {
        if ( ! wd4_is_frontend_request() ) {
            return;
        }
    } else {
        if (
            is_admin()
            || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
            || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() )
        ) {
            return;
        }
    }

    if (
        is_feed()
        || ( function_exists( 'is_embed' ) && is_embed() )
        || ( function_exists( 'is_robots' ) && is_robots() )
        || is_trackback()
    ) {
        return;
    }

    if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
        return;
    }

    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        return;
    }

    static $started = false;

    if ( $started ) {
        return;
    }

    $started = true;

    ob_start( 'wd4_strip_head_markup_fragments' );
}
add_action( 'template_redirect', 'wd4_enable_head_markup_scrubber', 0 );









/**
 * -------------------------------------------------------------------------
 * Misc integrations (non-AdSense)
 * -------------------------------------------------------------------------
 */

/* Keep body aria-hidden fixer */
add_action( 'wp_footer', function (): void {
    ?>
    <script>
    (function(){
      var body = document.body;
      if (!body) { return; }
      if (body.getAttribute('aria-hidden') === 'true') { body.removeAttribute('aria-hidden'); }
      var obs = new MutationObserver(function(mutations){
        for (var i = 0; i < mutations.length; i++) {
          var m = mutations[i];
          if (m.type === 'attributes' && m.attributeName === 'aria-hidden' && body.getAttribute('aria-hidden') === 'true') {
            body.removeAttribute('aria-hidden');
          }
        }
      });
      obs.observe(body, { attributes: true, attributeFilter: ['aria-hidden'] });
    })();
    </script>
    <?php
}, 100 );

function allow_json_mime( array $mimes ): array {
    $mimes['json'] = 'application/json';
    return $mimes;
}
add_filter( 'upload_mimes', 'allow_json_mime' );




add_action( 'shutdown', function (): void {
    while ( ob_get_level() > 0 ) {
        @ob_end_flush();
    }
}, PHP_INT_MAX );
