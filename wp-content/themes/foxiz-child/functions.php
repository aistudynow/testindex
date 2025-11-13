<?php
if ( ! defined( 'ABSPATH' ) ) {
    return;
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
    if ( has_post_thumbnail( $post ) && function_exists( 'wd4_frontpage_image' ) ) {
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
 * Build a unique signature for a category feed so cached responses can detect
 * when new posts are published or existing posts change.
 *
 * @param WP_Term|null $term Category term instance.
 */
function wd4_get_category_feed_signature( ?WP_Term $term ): string {
    if ( ! ( $term instanceof WP_Term ) ) {
        return '';
    }

    $latest_posts = get_posts(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'posts_per_page'      => 1,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'no_found_rows'       => true,
            'fields'              => 'ids',
            'tax_query'           => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => array( (int) $term->term_id ),
                ),
            ),
        )
    );

    $latest_id = $latest_posts ? (int) $latest_posts[0] : 0;

    $modified_gmt = '';

    if ( $latest_id > 0 ) {
        $latest_post = get_post( $latest_id );

        if ( $latest_post instanceof WP_Post ) {
            $modified_gmt = $latest_post->post_modified_gmt ?: $latest_post->post_modified;

            if ( ! $modified_gmt ) {
                $modified_gmt = $latest_post->post_date_gmt ?: $latest_post->post_date;
            }

            if ( $modified_gmt ) {
                $timestamp    = strtotime( $modified_gmt ) ?: time();
                $modified_gmt = gmdate( 'c', $timestamp );
            }
        }
    }

    if ( ! $latest_id && ! $modified_gmt ) {
        return 'empty-' . (int) $term->term_id;
    }

    $payload = array(
        'term'     => (int) $term->term_id,
        'latest'   => $latest_id,
        'modified' => $modified_gmt,
    );

    $encoded = wp_json_encode( $payload );

    if ( ! is_string( $encoded ) || '' === $encoded ) {
        return '';
    }

    return wp_hash( $encoded );
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
 * Inline CSS tweaks for single posts.
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( is_singular( 'post' ) ) {
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
        wp_add_inline_style( 'single', $css ); // prints after single.css
    }
}, 999);

/**
 * -------------------------------------------------------------------------
 * Head cleanup and payload reduction
 * -------------------------------------------------------------------------
 */
function wd4_disable_emojis(): void {
    if ( ! function_exists( 'wd4_is_frontend_request' ) || ! wd4_is_frontend_request() ) {
        return;
    }

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

function wd4_filter_disable_emojis_option(): string {
    return '0';
}

function wd4_disable_emojis_tinymce( $plugins ) {
    if ( ! function_exists( 'wd4_is_frontend_request' ) || ! wd4_is_frontend_request() ) {
        return $plugins;
    }
    if ( ! is_array( $plugins ) ) {
        return $plugins;
    }
    return array_diff( $plugins, array( 'wpemoji' ) );
}
add_filter( 'tiny_mce_plugins', 'wd4_disable_emojis_tinymce' );

function wd4_remove_emoji_dns_prefetch( array $urls, string $relation_type ): array {
    if ( 'dns-prefetch' !== $relation_type ) {
        return $urls;
    }
    if ( ! function_exists( 'wd4_is_frontend_request' ) || ! wd4_is_frontend_request() ) {
        return $urls;
    }

    $filtered = array();
    foreach ( $urls as $url ) {
        $href = is_array( $url ) && isset( $url['href'] ) ? trim( (string) $url['href'] ) : ( is_string( $url ) ? trim( $url ) : '' );
        if ( '' === $href || false === strpos( $href, 's.w.org' ) ) {
            $filtered[] = $url;
        }
    }
    return $filtered;
}
add_filter( 'wp_resource_hints', 'wd4_remove_emoji_dns_prefetch', 9, 2 );

function wd4_cleanup_head_links(): void {
    if ( ! function_exists( 'wd4_is_frontend_request' ) || ! wd4_is_frontend_request() ) {
        return;
    }

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
        if ( ! is_array( $target ) || count( $target ) < 2 ) {
            continue;
        }

        $hook     = $target[0];
        $callback = $target[1];
        $priority = $target[2] ?? 10;
        $args     = $target[3] ?? 0;

        remove_action( $hook, $callback, $priority, $args );
    }
}
add_action( 'init', 'wd4_cleanup_head_links', 8 );

function wd4_disable_wp_embed(): void {
    if ( ! function_exists( 'wd4_is_frontend_request' ) || ! wd4_is_frontend_request() ) {
        return;
    }
    if ( ! apply_filters( 'wd4_disable_wp_embed', true ) ) {
        return;
    }

    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
    remove_action( 'template_redirect', 'rest_output_link_header', 11 );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );

    add_action( 'wp_enqueue_scripts', 'wd4_deregister_wp_embed', 100 );
}
add_action( 'init', 'wd4_disable_wp_embed', 9 );

function wd4_deregister_wp_embed(): void {
    wp_deregister_script( 'wp-embed' );
}

/**
 * Strip some heavy head markup (JSON-LD, icon font preload).
 */
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
 * Query parameter hygiene
 * -------------------------------------------------------------------------
 */

/**
 * Redirect legacy replytocom URLs to their canonical comment permalinks.
 */
function wd4_redirect_replytocom_to_comment(): void {
    if ( ! wd4_is_front_context() ) {
        return;
    }

    $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
    if ( 'GET' !== $method ) {
        return;
    }

    if ( empty( $_GET['replytocom'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    $comment_id = absint( $_GET['replytocom'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( $comment_id <= 0 ) {
        return;
    }

    $comment_link = get_comment_link( $comment_id );
    if ( ! $comment_link || is_wp_error( $comment_link ) ) {
        return;
    }

    wp_safe_redirect( $comment_link, 301 );
    exit;
}
add_action( 'template_redirect', 'wd4_redirect_replytocom_to_comment', 1 );

/**
 * Return the list of query parameters that should remain indexable.
 */
function wd4_allowed_public_query_keys(): array {
    $allowed = array();

    return apply_filters( 'wd4_allowed_public_query_keys', $allowed );
}

/**
 * Detect unexpected query variables that should be marked as noindex.
 *
 * @return array<string>
 */
function wd4_disallowed_query_keys(): array {
    if ( empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return array();
    }

    $allowed    = array_map( 'strtolower', wd4_allowed_public_query_keys() );
    $disallowed = array();

    foreach ( array_keys( $_GET ) as $raw_key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $key = strtolower( (string) $raw_key );

        if ( '' === $key ) {
            continue;
        }

        if ( in_array( $key, $allowed, true ) ) {
            continue;
        }

        if ( 0 === strpos( $key, 'utm_' ) ) {
            $disallowed[] = $key;
            continue;
        }

        $disallowed[] = $key;
    }

    return array_values( array_unique( $disallowed ) );
}

/**
 * Flag front-end requests that should be hidden from search engines.
 */
function wd4_mark_request_noindex( bool $strip_canonical = false ): void {
    if ( ! has_filter( 'wp_headers', 'wd4_append_noindex_header' ) ) {
        add_filter( 'wp_headers', 'wd4_append_noindex_header' );
    }

    if ( ! has_action( 'wp_head', 'wd4_render_noindex_meta' ) ) {
        add_action( 'wp_head', 'wd4_render_noindex_meta', 0 );
    }

    if ( $strip_canonical && ! has_filter( 'get_canonical_url', 'wd4_force_queryless_canonical' ) ) {
        add_filter( 'get_canonical_url', 'wd4_force_queryless_canonical', 10, 2 );
    }
}

/**
 * Flag front-end requests that should be hidden from search engines.
 */
function wd4_flag_request_for_noindex(): void {
    if ( ! wd4_is_front_context() ) {
        return;
    }

    $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
    if ( 'GET' !== $method ) {
        return;
    }

    $should_block = false;

    $disallowed = wd4_disallowed_query_keys();
    if ( ! empty( $disallowed ) ) {
        $should_block = true;
    }

    if ( function_exists( 'is_search' ) && is_search() ) {
        $should_block = true;
    }

    $is_paged = false;
    if ( function_exists( 'is_paged' ) && is_paged() ) {
        $is_paged = true;
    } elseif ( get_query_var( 'paged' ) > 1 ) {
        $is_paged = true;
    }

    if ( $is_paged ) {
        $should_block = true;
    }

    if ( ! $should_block ) {
        return;
    }

    wd4_mark_request_noindex( true );
}
add_action( 'template_redirect', 'wd4_flag_request_for_noindex', 2 );

/**
 * Determine whether the current request targets a JSON workflow asset inside the uploads directory.
 */
function wd4_is_json_workflow_request(): bool {
    if ( ! wd4_is_front_context() ) {
        return false;
    }

    if ( is_attachment() ) {
        $mime = get_post_mime_type();
        if ( is_string( $mime ) && false !== stripos( $mime, 'json' ) ) {
            return true;
        }
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
    if ( '' === $request_uri ) {
        return false;
    }

    $path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    if ( '' === $path ) {
        return false;
    }

    $trimmed_path = trim( $path, '/' );
    if ( '' !== $trimmed_path && 0 === stripos( $trimmed_path, 'wp-json' ) ) {
        return false;
    }

    if ( '.json' !== strtolower( substr( $path, -5 ) ) ) {
        return false;
    }

    $uploads = wp_get_upload_dir();
    if ( empty( $uploads['baseurl'] ) ) {
        return true;
    }

    $uploads_path = (string) wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );
    if ( '' === $uploads_path ) {
        return true;
    }

    return false !== strpos( $path, rtrim( $uploads_path, '/' ) . '/' );
}

/**
 * Apply a noindex directive to JSON workflow downloads so crawlers ignore them.
 */
function wd4_noindex_json_workflows(): void {
    if ( ! wd4_is_json_workflow_request() ) {
        return;
    }

    wd4_mark_request_noindex( false );
}
add_action( 'template_redirect', 'wd4_noindex_json_workflows', 3 );

/**
 * Append an X-Robots-Tag header so crawlers ignore disallowed query URLs.
 */
function wd4_append_noindex_header( array $headers ): array {
    if ( isset( $headers['X-Robots-Tag'] ) ) {
        $directives = array_map( 'trim', explode( ',', (string) $headers['X-Robots-Tag'] ) );

        if ( ! in_array( 'noindex', $directives, true ) ) {
            $directives[] = 'noindex';
        }

        if ( ! in_array( 'follow', $directives, true ) ) {
            $directives[] = 'follow';
        }

        $headers['X-Robots-Tag'] = implode( ', ', array_filter( $directives ) );
    } else {
        $headers['X-Robots-Tag'] = 'noindex, follow';
    }

    return $headers;
}

/**
 * Render a meta robots directive for disallowed query strings.
 */
function wd4_render_noindex_meta(): void {
    echo "<meta name='robots' content='noindex,follow'>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Force canonical links to drop query parameters and pagination segments when
 * we block indexing.
 */
function wd4_force_queryless_canonical( $canonical, $post ) {
    unset( $post );

    global $wp;

    $request_path = '';
    if ( isset( $wp->request ) && is_string( $wp->request ) ) {
        $request_path = trim( $wp->request );
    }

    if ( '' !== $request_path ) {
        $request_path = preg_replace( '#/page/\d+/?$#i', '', $request_path );
    }

    $request_path = trim( (string) $request_path, '/' );

    if ( '' === $request_path ) {
        return home_url( '/' );
    }

    $canonical_path = user_trailingslashit( $request_path );
    $canonical_url  = home_url( '/' . ltrim( $canonical_path, '/' ) );

    return is_string( $canonical_url ) ? $canonical_url : $canonical;
}

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

/**
 * Flush all buffers at shutdown.
 */
add_action( 'shutdown', function (): void {
    while ( ob_get_level() > 0 ) {
        @ob_end_flush();
    }
}, PHP_INT_MAX );

if ( ! function_exists( 'wd4_get_current_url' ) ) {
    /**
     * Resolve the active front-end URL for form redirects.
     */
    function wd4_get_current_url(): string {
        $host = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( (string) $_SERVER['HTTP_HOST'] ) : '';
        $path = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';

        if ( '' === $host ) {
            return home_url( '/' );
        }

        $scheme = is_ssl() ? 'https://' : 'http://';
        $raw    = $scheme . $host . $path;

        return wp_validate_redirect( $raw, home_url( '/' ) );
    }
}














/**
 * Retrieve saved contact/lead configuration options.
 */
function wd4_get_contact_options(): array {
    $options = get_option( 'wd4_contact_options', array() );

    if ( ! is_array( $options ) ) {
        $options = array();
    }

    $default_faq = array(
        array(
            'question' => __( 'Do you offer payment plans?', 'foxiz-child' ),
            'answer'   => __( 'Yes, I do! For more information, simply indicate in the "How can I serve you?" section of the contact form that you\'re interested in hearing more about payment plan options.', 'foxiz-child' ),
        ),
        array(
            'question' => __( 'How far in advance should I reach out to work with you?', 'foxiz-child' ),
            'answer'   => __( 'I typically book my projects 3-6 months in advance, so my honest answer is sooner rather than later. It\'s just me over here at BTL, so I can only take on a limited amount of projects at a time.', 'foxiz-child' ) . "

" . __( 'If our timelines don\'t align, I still have options—my website copy guide, my website copywriting course, site audits, or referrals to other talented copywriters I trust.', 'foxiz-child' ),
        ),
        array(
            'question' => __( 'If you write my copy, will it still sound like me?', 'foxiz-child' ),
            'answer'   => __( 'Absolutely. My research and writing process is designed to capture your voice perfectly so your copy feels like a true representation of you and your brand.', 'foxiz-child' ) . "

" . __( 'You\'ll complete an in-depth brand persona questionnaire before we begin, and we\'ll review everything together on a kickoff call to make sure I know your voice inside and out.', 'foxiz-child' ),
        ),
        array(
            'question' => __( 'Do you also design websites or just write them?', 'foxiz-child' ),
            'answer'   => __( 'I stick to the words, but I have an extensive list of website designers I love working with across every platform.', 'foxiz-child' ) . "

" . __( 'Let me know what you need and I\'ll help you find the right fit—or I can point you toward a template that comes with copy prompts straight from me.', 'foxiz-child' ),
        ),
    );

    $defaults = array(
        'testimonial_guide_attachment_id'   => 0,
        'hero_subtitle'                     => __( 'So your story needs an author?', 'foxiz-child' ),
        'hero_title'                        => __( "Sounds great,
I\'ll grab my pen.", 'foxiz-child' ),
        'hero_image_attachment_id'          => 0,
        'hero_image_large_attachment_id'    => 0,
        'hero_image_alt'                    => '',
        'banner_message'                    => __( 'Ever felt sooo freaking annoying while selling online? Come to my "YOU\'RE NOT ANNOYING" workshop to fix that!', 'foxiz-child' ),
        'banner_link_text'                  => __( 'my "YOU\'RE NOT ANNOYING" workshop', 'foxiz-child' ),
        'banner_link_url'                   => '#',
        'contact_heading'                   => __( "Well, actually, I\'ll grab my laptop... but you know what I mean.", 'foxiz-child' ),
        'contact_intro'                     => __( 'Whether you need fresh new copy for your website, your next online course launch, your big-deal email funnel, or <em>another</em> cool copywriting project you have in mind, <strong>I\'m ready to get drafting.</strong>', 'foxiz-child' ),
        'contact_box_text'                  => __( 'For inquiries about interviews, podcast appearances, guest education, and live event education, please email me directly:', 'foxiz-child' ),
        'contact_box_email'                 => 'sara@betweenthelinescopy.com',
        'contact_recipients_raw'            => "23scienceinsights@gmail.com",
        'services_raw'                      => "Website Copywriting
Email Marketing
Launch Strategy
Copy Mentorship
Other",
        'contact_form_title'                => __( 'Now Booking February/March 2026', 'foxiz-child' ),
        'contact_top_left'                  => __( 'Get in touch with me', 'foxiz-child' ),
        'contact_top_right'                 => __( "Let's work together", 'foxiz-child' ),
        'faq_heading'                       => __( 'Frequently Asked Questions', 'foxiz-child' ),
        'faq_items'                         => $default_faq,
        'author_title'                      => __( 'About the author', 'foxiz-child' ),
        'author_content'                    => __( '<strong>Sara Joelle</strong> is a copywriter and marketing mentor specializing in website and sales page copywriting, 1:1 mentorship and consulting, cheeky humor, oversharing, and the occasional bad hair day.', 'foxiz-child' ) . "

" . __( 'Using her <em>sales-focused storytelling</em> method—and a well-documented obsession with writing copy that consistently gets results—Sara helps brands and businesses of all shapes and sizes connect with their ideal clients, increase their revenue, and feel confident leading their next success story.', 'foxiz-child' ) . "

" . __( 'As an extrovert by nature, social anxiety\'s worst nightmare, and personal client cheerleader by choice, Sara is someone you\'d really benefit from having in your corner. And no, she <em>definitely</em> didn\'t just write this whole bio about herself. Because that would be... weird. <em>*awkward laughter*</em>', 'foxiz-child' ),
        'author_image_attachment_id'        => 0,
        'optin_title'                       => __( 'The BTL Glowing Testimonial Guide', 'foxiz-child' ),
        'optin_text'                        => __( 'People love to hear from other people, and they\'re quick to trust their opinions, which is why adding elements of social proof on your website—like bomb reviews and testimonials—can exponentially improve the success of your business.', 'foxiz-child' ) . "

" . __( 'Download my free Glowing Testimonial Guide to get access to the exact questions I ask my clients on my own feedback form so you can start getting the best testimonials, too! And the best part? This form comes with an auto-linked spreadsheet that will organize all of your data for you. Game-changer.', 'foxiz-child' ),
        'social_links_raw'                  => '',
    );

    $options = wp_parse_args( $options, $defaults );

    if ( empty( $options['faq_items'] ) || ! is_array( $options['faq_items'] ) ) {
        $options['faq_items'] = $default_faq;
    }

    return $options;
}

/**
 * Register theme settings for contact and opt-in forms.
 */
function wd4_register_contact_settings(): void {
    register_setting(
        'wd4_contact_options',
        'wd4_contact_options',
        'wd4_sanitize_contact_options'
    );

    add_settings_section(
        'wd4_contact_hero_banner',
        __( 'Hero & Banner Content', 'foxiz-child' ),
        function (): void {
            echo '<p>' . esc_html__( 'Update the hero imagery and banner messaging that appear at the top of the contact page.', 'foxiz-child' ) . '</p>';
        },
        'wd4-contact-settings'
    );

    add_settings_field(
        'wd4_contact_hero_subtitle',
        __( 'Hero subtitle', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_hero_banner',
        array(
            'id'        => 'hero_subtitle',
            'label_for' => 'wd4_hero_subtitle',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_hero_title',
        __( 'Hero title', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_hero_banner',
        array(
            'id'          => 'hero_title',
            'label_for'   => 'wd4_hero_title',
            'type'        => 'textarea',
            'rows'        => 3,
            'description' => __( 'Line breaks are preserved. You can also add <br> tags for manual wrapping.', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_hero_image',
        __( 'Hero image', 'foxiz-child' ),
        'wd4_render_contact_media_field',
        'wd4-contact-settings',
        'wd4_contact_hero_banner',
        array(
            'id'          => 'hero_image_attachment_id',
            'label_for'   => 'wd4_hero_image_attachment_id',
            'description' => __( 'Recommended minimum size: 1600x1000px.', 'foxiz-child' ),
            'frame_title' => __( 'Choose hero image', 'foxiz-child' ),
            'frame_button'=> __( 'Use this image', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_hero_image_large',
        __( 'Hero image (large)', 'foxiz-child' ),
        'wd4_render_contact_media_field',
        'wd4-contact-settings',
        'wd4_contact_hero_banner',
        array(
            'id'          => 'hero_image_large_attachment_id',
            'label_for'   => 'wd4_hero_image_large_attachment_id',
            'description' => __( 'Optional higher-resolution image to include in the hero srcset.', 'foxiz-child' ),
            'frame_title' => __( 'Choose large hero image', 'foxiz-child' ),
            'frame_button'=> __( 'Use this image', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_hero_alt',
        __( 'Hero image alt text', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_hero_banner',
        array(
            'id'        => 'hero_image_alt',
            'label_for' => 'wd4_hero_image_alt',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_banner_message',
        __( 'Banner message', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_hero_banner',
        array(
            'id'          => 'banner_message',
            'label_for'   => 'wd4_banner_message',
            'type'        => 'textarea',
            'rows'        => 3,
            'description' => __( 'Use %s where the workshop link should appear.', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_banner_link_text',
        __( 'Banner link text', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_hero_banner',
        array(
            'id'        => 'banner_link_text',
            'label_for' => 'wd4_banner_link_text',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_banner_link_url',
        __( 'Banner link URL', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_hero_banner',
        array(
            'id'         => 'banner_link_url',
            'label_for'  => 'wd4_banner_link_url',
            'input_type' => 'url',
            'type'       => 'text',
        )
    );

    add_settings_section(
        'wd4_contact_main_content',
        __( 'Contact Content', 'foxiz-child' ),
        function (): void {
            echo '<p>' . esc_html__( 'Control the intro copy, contact box, services, and form labels.', 'foxiz-child' ) . '</p>';
        },
        'wd4-contact-settings'
    );

    add_settings_field(
        'wd4_contact_heading',
        __( 'Contact heading', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'        => 'contact_heading',
            'label_for' => 'wd4_contact_heading',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_intro',
        __( 'Contact intro text', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'          => 'contact_intro',
            'label_for'   => 'wd4_contact_intro',
            'type'        => 'textarea',
            'rows'        => 4,
            'description' => __( 'Basic HTML such as <em> or <strong> is allowed.', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_box_text',
        __( 'Contact box text', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'          => 'contact_box_text',
            'label_for'   => 'wd4_contact_box_text',
            'type'        => 'textarea',
            'rows'        => 3,
            'description' => __( 'Displayed inside the bordered box under the intro.', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_box_email',
        __( 'Contact email address', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'         => 'contact_box_email',
            'label_for'  => 'wd4_contact_box_email',
            'type'       => 'text',
            'input_type' => 'email',
        )
    );

    add_settings_field(
        'wd4_contact_recipients',
        __( 'Notification recipients', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'          => 'contact_recipients_raw',
            'label_for'   => 'wd4_contact_recipients',
            'type'        => 'textarea',
            'rows'        => 3,
            'description' => __( 'Enter one email address per line. Messages will be sent to these addresses.', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_services',
        __( 'Service options', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'          => 'services_raw',
            'label_for'   => 'wd4_contact_services',
            'type'        => 'textarea',
            'rows'        => 4,
            'description' => __( 'Enter one service per line to populate the select menu.', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_form_title',
        __( 'Form heading', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'        => 'contact_form_title',
            'label_for' => 'wd4_contact_form_title',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_top_left',
        __( 'Sticky label (left)', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'        => 'contact_top_left',
            'label_for' => 'wd4_contact_top_left',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_top_right',
        __( 'Sticky label (right)', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'        => 'contact_top_right',
            'label_for' => 'wd4_contact_top_right',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_social_links',
        __( 'Social links', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_main_content',
        array(
            'id'          => 'social_links_raw',
            'label_for'   => 'wd4_contact_social_links',
            'type'        => 'textarea',
            'rows'        => 4,
            'description' => __( 'Format each line as Label|https://example.com|optional-icon-class.', 'foxiz-child' ),
        )
    );

    add_settings_section(
        'wd4_contact_faq',
        __( 'FAQ Section', 'foxiz-child' ),
        function (): void {
            echo '<p>' . esc_html__( 'Manage the frequently asked questions displayed beneath the form.', 'foxiz-child' ) . '</p>';
        },
        'wd4-contact-settings'
    );

    add_settings_field(
        'wd4_contact_faq_heading',
        __( 'FAQ heading', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_faq',
        array(
            'id'        => 'faq_heading',
            'label_for' => 'wd4_contact_faq_heading',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_faq_items',
        __( 'FAQ entries', 'foxiz-child' ),
        'wd4_render_contact_faq_field',
        'wd4-contact-settings',
        'wd4_contact_faq'
    );

    add_settings_section(
        'wd4_contact_author_optin',
        __( 'Author & Opt-in', 'foxiz-child' ),
        function (): void {
            echo '<p>' . esc_html__( 'Update the author spotlight and opt-in copy that appear after the FAQ.', 'foxiz-child' ) . '</p>';
        },
        'wd4-contact-settings'
    );

    add_settings_field(
        'wd4_contact_author_title',
        __( 'Author section title', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_author_optin',
        array(
            'id'        => 'author_title',
            'label_for' => 'wd4_author_title',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_author_content',
        __( 'Author biography', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_author_optin',
        array(
            'id'          => 'author_content',
            'label_for'   => 'wd4_author_content',
            'type'        => 'textarea',
            'rows'        => 5,
            'description' => __( 'Separate paragraphs with blank lines.', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_author_image',
        __( 'Author image', 'foxiz-child' ),
        'wd4_render_contact_media_field',
        'wd4-contact-settings',
        'wd4_contact_author_optin',
        array(
            'id'          => 'author_image_attachment_id',
            'label_for'   => 'wd4_author_image_attachment_id',
            'description' => __( 'Recommended minimum width: 400px.', 'foxiz-child' ),
            'frame_title' => __( 'Choose author image', 'foxiz-child' ),
            'frame_button'=> __( 'Use this image', 'foxiz-child' ),
        )
    );

    add_settings_field(
        'wd4_contact_optin_title',
        __( 'Opt-in title', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_author_optin',
        array(
            'id'        => 'optin_title',
            'label_for' => 'wd4_optin_title',
            'type'      => 'text',
        )
    );

    add_settings_field(
        'wd4_contact_optin_text',
        __( 'Opt-in copy', 'foxiz-child' ),
        'wd4_render_contact_option_field',
        'wd4-contact-settings',
        'wd4_contact_author_optin',
        array(
            'id'          => 'optin_text',
            'label_for'   => 'wd4_optin_text',
            'type'        => 'textarea',
            'rows'        => 4,
            'description' => __( 'Separate paragraphs with blank lines.', 'foxiz-child' ),
        )
    );

    add_settings_section(
        'wd4_contact_lead_assets',
        __( 'Lead Magnet Settings', 'foxiz-child' ),
        function (): void {
            echo '<p>' . esc_html__( 'Configure the downloadable assets that are delivered after visitors request them on the contact page.', 'foxiz-child' ) . '</p>';
        },
        'wd4-contact-settings'
    );

    add_settings_field(
        'wd4_contact_testimonial_guide',
        __( 'Testimonial Guide File', 'foxiz-child' ),
        'wd4_render_testimonial_guide_field',
        'wd4-contact-settings',
        'wd4_contact_lead_assets'
    );
}

add_action( 'admin_init', 'wd4_register_contact_settings' );

function wd4_sanitize_contact_options( $raw ): array {
    if ( ! is_array( $raw ) ) {
        $raw = array();
    }

    $sanitized = array();

    $rich_text_sanitizer = static function ( $value ): string {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $value = wp_kses_post( $value );
        $value = str_replace( array( "\r\n", "\r" ), "\n", $value );

        return trim( $value );
    };

    $normalize_list = static function ( $value ): array {
        if ( ! is_string( $value ) ) {
            return array();
        }

        $lines = preg_split( '/\r\n|\r|\n/', $value );
        if ( ! is_array( $lines ) ) {
            $lines = array();
        }

        $items = array();
        foreach ( $lines as $line ) {
            $line = sanitize_text_field( $line );
            if ( '' !== $line ) {
                $items[] = $line;
            }
        }

        return $items;
    };

    $int_fields = array(
        'testimonial_guide_attachment_id',
        'hero_image_attachment_id',
        'hero_image_large_attachment_id',
        'author_image_attachment_id',
    );

    foreach ( $int_fields as $field ) {
        if ( isset( $raw[ $field ] ) ) {
            $sanitized[ $field ] = max( 0, (int) $raw[ $field ] );
        }
    }

    $text_fields = array(
        'hero_subtitle',
        'hero_image_alt',
        'banner_link_text',
        'contact_heading',
        'contact_form_title',
        'contact_top_left',
        'contact_top_right',
        'author_title',
        'optin_title',
    );

    foreach ( $text_fields as $field ) {
        if ( isset( $raw[ $field ] ) ) {
            $sanitized[ $field ] = sanitize_text_field( $raw[ $field ] );
        }
    }

    if ( isset( $raw['contact_box_email'] ) ) {
        $email = sanitize_email( $raw['contact_box_email'] );
        $sanitized['contact_box_email'] = is_email( $email ) ? $email : '';
    }

    if ( isset( $raw['banner_link_url'] ) ) {
        $url = esc_url_raw( $raw['banner_link_url'] );
        $sanitized['banner_link_url'] = $url ? $url : '';
    }

    $rich_fields = array(
        'hero_title',
        'banner_message',
        'contact_intro',
        'contact_box_text',
        'author_content',
        'optin_text',
    );

    foreach ( $rich_fields as $field ) {
        if ( isset( $raw[ $field ] ) ) {
            $sanitized[ $field ] = $rich_text_sanitizer( $raw[ $field ] );
        }
    }

    if ( isset( $raw['services_raw'] ) ) {
        $services = $normalize_list( $raw['services_raw'] );
        $sanitized['services_raw'] = implode( "\n", $services );
    }

    if ( isset( $raw['contact_recipients_raw'] ) ) {
        $lines  = preg_split( '/\r\n|\r|\n/', (string) $raw['contact_recipients_raw'] );
        $recips = array();
        if ( is_array( $lines ) ) {
            foreach ( $lines as $line ) {
                $email = sanitize_email( $line );
                if ( $email && is_email( $email ) ) {
                    $recips[] = $email;
                }
            }
        }
        $sanitized['contact_recipients_raw'] = implode( "\n", array_unique( $recips ) );
    }

    if ( isset( $raw['social_links_raw'] ) ) {
        $lines  = preg_split( '/\r\n|\r|\n/', (string) $raw['social_links_raw'] );
        $output = array();
        if ( is_array( $lines ) ) {
            foreach ( $lines as $line ) {
                $parts = array_map( 'trim', explode( '|', $line ) );
                if ( empty( $parts[0] ) || empty( $parts[1] ) ) {
                    continue;
                }

                $label = sanitize_text_field( $parts[0] );
                $url   = esc_url_raw( $parts[1] );
                if ( ! $url ) {
                    continue;
                }

                $icon = '';
                if ( ! empty( $parts[2] ) ) {
                    $raw_icons = preg_split( '/\s+/', $parts[2] );
                    if ( is_array( $raw_icons ) ) {
                        $clean_icons = array();
                        foreach ( $raw_icons as $raw_icon ) {
                            $clean_icon = sanitize_html_class( $raw_icon );
                            if ( $clean_icon ) {
                                $clean_icons[] = $clean_icon;
                            }
                        }
                        if ( ! empty( $clean_icons ) ) {
                            $icon = implode( ' ', array_unique( $clean_icons ) );
                        }
                    }
                }

                $entry = $label . '|' . $url;
                if ( $icon ) {
                    $entry .= '|' . $icon;
                }

                $output[] = $entry;
            }
        }
        $sanitized['social_links_raw'] = implode( "\n", $output );
    }

    if ( isset( $raw['faq_items'] ) && is_array( $raw['faq_items'] ) ) {
        $faq_items = array();
        foreach ( $raw['faq_items'] as $item ) {
            if ( ! is_array( $item ) ) {
                $item = array();
            }

            $question = isset( $item['question'] ) ? sanitize_text_field( $item['question'] ) : '';
            $answer   = isset( $item['answer'] ) ? $rich_text_sanitizer( $item['answer'] ) : '';

            $faq_items[] = array(
                'question' => $question,
                'answer'   => $answer,
            );
        }

        $sanitized['faq_items'] = $faq_items;
    }

    $defaults = wd4_get_contact_options();

    return wp_parse_args( $sanitized, $defaults );
}

/**
 * Render a generic text or textarea field for the contact settings page.
 *
 * @param array $args Field configuration.
 */
function wd4_render_contact_option_field( array $args ): void {
    $options = wd4_get_contact_options();
    $id      = $args['id'];
    $value   = isset( $options[ $id ] ) ? $options[ $id ] : '';
    $type    = isset( $args['type'] ) ? $args['type'] : 'text';
    $input   = isset( $args['input_type'] ) ? $args['input_type'] : 'text';
    $label   = isset( $args['label_for'] ) ? $args['label_for'] : $id;
    $class   = isset( $args['class'] ) ? $args['class'] : 'regular-text';
    $rows    = isset( $args['rows'] ) ? (int) $args['rows'] : 4;
    $placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
    $description = isset( $args['description'] ) ? $args['description'] : '';

    if ( 'textarea' === $type ) {
        printf(
            '<textarea class="%1$s" id="%2$s" name="wd4_contact_options[%3$s]" rows="%4$d" placeholder="%5$s">%6$s</textarea>',
            esc_attr( $class ),
            esc_attr( $label ),
            esc_attr( $id ),
            max( 2, $rows ),
            esc_attr( $placeholder ),
            esc_textarea( (string) $value )
        );
    } else {
        printf(
            '<input type="%1$s" class="%2$s" id="%3$s" name="wd4_contact_options[%4$s]" value="%5$s" placeholder="%6$s" />',
            esc_attr( $input ),
            esc_attr( $class ),
            esc_attr( $label ),
            esc_attr( $id ),
            esc_attr( (string) $value ),
            esc_attr( $placeholder )
        );
    }

    if ( $description ) {
        echo '<p class="description">' . wp_kses_post( $description ) . '</p>';
    }
}

/**
 * Render a media selector used for hero and author images.
 *
 * @param array $args Field configuration.
 */
function wd4_render_contact_media_field( array $args ): void {
    $options      = wd4_get_contact_options();
    $id           = $args['id'];
    $field_id     = isset( $args['label_for'] ) ? $args['label_for'] : 'wd4_' . $id;
    $attachment   = isset( $options[ $id ] ) ? (int) $options[ $id ] : 0;
    $media_type   = isset( $args['media_type'] ) && 'file' === $args['media_type'] ? 'file' : 'image';
    $button_label = isset( $args['button'] ) ? $args['button'] : ( 'file' === $media_type ? __( 'Choose file', 'foxiz-child' ) : __( 'Choose image', 'foxiz-child' ) );
    $remove_label = isset( $args['remove'] ) ? $args['remove'] : __( 'Remove', 'foxiz-child' );
    $description  = isset( $args['description'] ) ? $args['description'] : '';
    $frame_title  = isset( $args['frame_title'] ) ? $args['frame_title'] : ( 'file' === $media_type ? __( 'Select file', 'foxiz-child' ) : __( 'Select image', 'foxiz-child' ) );
    $frame_button = isset( $args['frame_button'] ) ? $args['frame_button'] : ( 'file' === $media_type ? __( 'Use this file', 'foxiz-child' ) : __( 'Use this image', 'foxiz-child' ) );
    $placeholder  = isset( $args['placeholder'] ) ? $args['placeholder'] : ( 'file' === $media_type ? __( 'No file selected.', 'foxiz-child' ) : __( 'No image selected.', 'foxiz-child' ) );

    $url        = '';
    $preview    = '';
    $file_label = '';

    if ( $attachment ) {
        $url = wp_get_attachment_url( $attachment ) ?: '';

        if ( 'image' === $media_type ) {
            $preview = wp_get_attachment_image_url( $attachment, 'medium' );
            if ( ! $preview ) {
                $preview = $url;
            }
        } else {
            $attachment_post = get_post( $attachment );
            if ( $attachment_post instanceof WP_Post ) {
                $file_label = $attachment_post->post_title;
            }

            if ( ! $file_label && $url ) {
                $file_label = wp_basename( $url );
            }
        }
    }

    ?>
    <div class="wd4-media-selector" data-media-type="<?php echo esc_attr( $media_type ); ?>" data-title="<?php echo esc_attr( $frame_title ); ?>" data-button="<?php echo esc_attr( $frame_button ); ?>" data-placeholder="<?php echo esc_attr( $placeholder ); ?>">
        <input type="hidden" class="wd4-media-id" id="<?php echo esc_attr( $field_id ); ?>" name="wd4_contact_options[<?php echo esc_attr( $id ); ?>]" value="<?php echo esc_attr( $attachment ); ?>" />
        <div class="wd4-media-preview">
            <?php if ( 'image' === $media_type ) : ?>
                <?php if ( $preview ) : ?>
                    <img src="<?php echo esc_url( $preview ); ?>" alt="" />
                <?php else : ?>
                    <span class="wd4-media-placeholder"><?php echo esc_html( $placeholder ); ?></span>
                <?php endif; ?>
            <?php else : ?>
                <?php if ( $url ) : ?>
                    <a class="wd4-media-file" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $file_label ? $file_label : $url ); ?></a>
                <?php else : ?>
                    <span class="wd4-media-placeholder"><?php echo esc_html( $placeholder ); ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <p>
            <button type="button" class="button wd4-media-upload"><?php echo esc_html( $button_label ); ?></button>
            <button type="button" class="button wd4-media-remove" <?php disabled( ! $attachment ); ?>><?php echo esc_html( $remove_label ); ?></button>
        </p>
        <input type="text" class="regular-text wd4-media-url" value="<?php echo esc_attr( $url ); ?>" readonly />
        <?php if ( $description ) : ?>
            <p class="description"><?php echo wp_kses_post( $description ); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render the FAQ repeater fields for the settings page.
 */
function wd4_render_contact_faq_field(): void {
    $options = wd4_get_contact_options();
    $items   = isset( $options['faq_items'] ) && is_array( $options['faq_items'] ) ? $options['faq_items'] : array();
    $count   = max( 4, count( $items ) );
    ?>
    <div class="wd4-faq-settings">
        <?php for ( $i = 0; $i < $count; $i++ ) :
            $question   = isset( $items[ $i ]['question'] ) ? $items[ $i ]['question'] : '';
            $answer     = isset( $items[ $i ]['answer'] ) ? $items[ $i ]['answer'] : '';
            $question_id = 'wd4_faq_question_' . $i;
            $answer_id   = 'wd4_faq_answer_' . $i;
            ?>
            <div class="wd4-faq-settings-item">
                <label for="<?php echo esc_attr( $question_id ); ?>"><?php printf( esc_html__( 'Question %d', 'foxiz-child' ), $i + 1 ); ?></label>
                <input type="text" class="regular-text" id="<?php echo esc_attr( $question_id ); ?>" name="wd4_contact_options[faq_items][<?php echo esc_attr( $i ); ?>][question]" value="<?php echo esc_attr( $question ); ?>" />
                <label for="<?php echo esc_attr( $answer_id ); ?>" class="wd4-faq-answer-label"><?php esc_html_e( 'Answer', 'foxiz-child' ); ?></label>
                <textarea id="<?php echo esc_attr( $answer_id ); ?>" name="wd4_contact_options[faq_items][<?php echo esc_attr( $i ); ?>][answer]" rows="4" class="large-text"><?php echo esc_textarea( $answer ); ?></textarea>
            </div>
        <?php endfor; ?>
    </div>
    <p class="description"><?php esc_html_e( 'Leave a question blank to hide it on the contact page.', 'foxiz-child' ); ?></p>
    <?php
}

/**
 * Retrieve the configured service options for the contact form.
 */
function wd4_get_contact_services(): array {
    $options  = wd4_get_contact_options();
    $services = array();

    if ( ! empty( $options['services_raw'] ) && is_string( $options['services_raw'] ) ) {
        $lines = preg_split( '/\r\n|\r|\n/', $options['services_raw'] );
        if ( is_array( $lines ) ) {
            foreach ( $lines as $line ) {
                $label = trim( sanitize_text_field( $line ) );
                if ( '' !== $label ) {
                    $services[] = $label;
                }
            }
        }
    }

    return $services;
}

/**
 * Retrieve sanitized FAQ items for front-end rendering.
 */
function wd4_get_contact_faq_items(): array {
    $options = wd4_get_contact_options();
    $items   = array();

    if ( isset( $options['faq_items'] ) && is_array( $options['faq_items'] ) ) {
        foreach ( $options['faq_items'] as $item ) {
            if ( empty( $item['question'] ) && empty( $item['answer'] ) ) {
                continue;
            }

            $items[] = array(
                'question' => isset( $item['question'] ) ? $item['question'] : '',
                'answer'   => isset( $item['answer'] ) ? $item['answer'] : '',
            );
        }
    }

    return $items;
}

/**
 * Retrieve configured social links for the contact layout.
 */
function wd4_get_contact_social_links(): array {
    $options = wd4_get_contact_options();
    $links   = array();

    if ( ! empty( $options['social_links_raw'] ) && is_string( $options['social_links_raw'] ) ) {
        $lines = preg_split( '/\r\n|\r|\n/', $options['social_links_raw'] );
        if ( is_array( $lines ) ) {
            foreach ( $lines as $line ) {
                $parts = array_map( 'trim', explode( '|', $line ) );
                if ( empty( $parts[0] ) || empty( $parts[1] ) ) {
                    continue;
                }

                $icon = '';
                if ( ! empty( $parts[2] ) ) {
                    $raw_icons = preg_split( '/\s+/', $parts[2] );
                    if ( is_array( $raw_icons ) ) {
                        $clean_icons = array();
                        foreach ( $raw_icons as $raw_icon ) {
                            $clean_icon = sanitize_html_class( $raw_icon );
                            if ( $clean_icon ) {
                                $clean_icons[] = $clean_icon;
                            }
                        }
                        if ( ! empty( $clean_icons ) ) {
                            $icon = implode( ' ', array_unique( $clean_icons ) );
                        }
                    }
                }

                $links[] = array(
                    'label' => $parts[0],
                    'url'   => $parts[1],
                    'icon'  => $icon,
                );
            }
        }
    }

    return $links;
}

/**
 * Convert rich text blocks into individual paragraphs.
 */
function wd4_contact_text_to_paragraphs( string $text ): array {
    $text = str_replace( array( "\r\n", "\r" ), "\n", $text );
    $parts = preg_split( '/\n{2,}/', trim( $text ) );

    if ( ! is_array( $parts ) ) {
        return array();
    }

    return array_values( array_filter( array_map( 'trim', $parts ) ) );
}

/**
 * Override contact email recipients based on saved settings.
 */
function wd4_contact_option_recipients( array $recipients ): array {
    $options = wd4_get_contact_options();
    $raw     = isset( $options['contact_recipients_raw'] ) ? (string) $options['contact_recipients_raw'] : '';
    $emails  = array();

    if ( '' !== $raw ) {
        $lines = preg_split( '/\r\n|\r|\n/', $raw );
        if ( is_array( $lines ) ) {
            foreach ( $lines as $line ) {
                $email = sanitize_email( $line );
                if ( $email && is_email( $email ) ) {
                    $emails[] = $email;
                }
            }
        }
    }

    if ( empty( $emails ) && ! empty( $options['contact_box_email'] ) ) {
        $fallback = sanitize_email( $options['contact_box_email'] );
        if ( $fallback && is_email( $fallback ) ) {
            $emails[] = $fallback;
        }
    }

    return empty( $emails ) ? $recipients : array_values( array_unique( $emails ) );
}
add_filter( 'wd4_contact_recipients', 'wd4_contact_option_recipients', 10, 1 );
/**
 * Render the settings field used to select the testimonial guide asset.
 */
function wd4_render_testimonial_guide_field(): void {
    wd4_render_contact_media_field(
        array(
            'id'           => 'testimonial_guide_attachment_id',
            'label_for'    => 'wd4_testimonial_guide_attachment_id',
            'media_type'   => 'file',
            'button'       => __( 'Choose file', 'foxiz-child' ),
            'remove'       => __( 'Remove', 'foxiz-child' ),
            'frame_title'  => __( 'Select or upload the testimonial guide', 'foxiz-child' ),
            'frame_button' => __( 'Use this file', 'foxiz-child' ),
            'placeholder'  => __( 'No file selected.', 'foxiz-child' ),
            'description'  => __( 'Upload the downloadable guide that should be delivered after opt-in submissions.', 'foxiz-child' ),
        )
    );
}

/**
 * Add a simple settings page for the child theme contact features.
 */
function wd4_register_contact_settings_page(): void {
    add_theme_page(
        __( 'Contact & Lead Settings', 'foxiz-child' ),
        __( 'Contact Leads', 'foxiz-child' ),
        'manage_options',
        'wd4-contact-settings',
        'wd4_render_contact_settings_page'
    );
}
add_action( 'admin_menu', 'wd4_register_contact_settings_page' );

/**
 * Render the contact settings administration page.
 */
function wd4_render_contact_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    wp_enqueue_media();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Contact & Lead Settings', 'foxiz-child' ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'wd4_contact_options' );
            do_settings_sections( 'wd4-contact-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <script>
        (function($){
            function getPlaceholder($container){
                return $container.data('placeholder') || '';
            }

            function renderPlaceholder($container){
                var placeholder = getPlaceholder($container);
                var $preview = $container.find('.wd4-media-preview');
                $preview.empty();
                if (placeholder) {
                    $('<span/>', {
                        'class': 'wd4-media-placeholder',
                        'text': placeholder
                    }).appendTo($preview);
                }
            }

            function choosePreviewUrl(attachment, mediaType){
                if (!attachment) {
                    return '';
                }

                if ('image' !== mediaType) {
                    return attachment.url || '';
                }

                if (attachment.sizes) {
                    var candidates = ['medium_large', 'large', 'medium', 'full'];
                    for (var i = 0; i < candidates.length; i++) {
                        var size = candidates[i];
                        if (attachment.sizes[size] && attachment.sizes[size].url) {
                            return attachment.sizes[size].url;
                        }
                    }
                }

                return attachment.url || '';
            }

            function updateMediaSelector($container, attachment){
                var mediaType = $container.data('media-type') || 'image';
                var id = attachment && attachment.id ? attachment.id : '';
                var url = attachment ? (attachment.url || '') : '';
                var label = '';

                if (attachment) {
                    label = attachment.filename || attachment.title || '';
                }

                $container.find('.wd4-media-id').val(id);
                $container.find('.wd4-media-url').val(url);
                $container.find('.wd4-media-remove').prop('disabled', ! url);

                if (! url) {
                    renderPlaceholder($container);
                    return;
                }

                var $preview = $container.find('.wd4-media-preview');
                if ('image' === mediaType) {
                    var previewUrl = choosePreviewUrl(attachment, mediaType) || url;
                    var $img = $preview.find('img');
                    if (! $img.length) {
                        $preview.empty();
                        $img = $('<img/>').appendTo($preview);
                    }
                    $img.attr('src', previewUrl);
                } else {
                    var display = label || url;
                    $preview.empty();
                    $('<a/>', {
                        'class': 'wd4-media-file',
                        'href': url,
                        'text': display,
                        'target': '_blank',
                        'rel': 'noopener noreferrer'
                    }).appendTo($preview);
                }
            }

            function handleUpload(e){
                e.preventDefault();
                var $container = $(this).closest('.wd4-media-selector');
                if (! $container.length) {
                    return;
                }

                var mediaType = $container.data('media-type') || 'image';
                var frameArgs = {
                    title: $container.data('title') || '<?php echo esc_js( __( 'Select media', 'foxiz-child' ) ); ?>',
                    button: {
                        text: $container.data('button') || '<?php echo esc_js( __( 'Use this media', 'foxiz-child' ) ); ?>'
                    },
                    multiple: false
                };

                if ('image' === mediaType) {
                    frameArgs.library = { type: 'image' };
                }

                var frame = wp.media(frameArgs);
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    updateMediaSelector($container, attachment);
                });
                frame.open();
            }

            function handleRemove(e){
                e.preventDefault();
                var $container = $(this).closest('.wd4-media-selector');
                if (! $container.length) {
                    return;
                }
                updateMediaSelector($container, null);
            }

            $('.wd4-media-upload').on('click', handleUpload);
            $('.wd4-media-remove').on('click', handleRemove);
        })(jQuery);
    </script>
    <?php
}

/**
 * Resolve the testimonial guide asset details.
 */
function wd4_get_testimonial_guide_asset(): array {
    $options       = wd4_get_contact_options();
    $attachment_id = isset( $options['testimonial_guide_attachment_id'] ) ? (int) $options['testimonial_guide_attachment_id'] : 0;

    $url  = '';
    $path = '';

    if ( $attachment_id > 0 ) {
        $url = wp_get_attachment_url( $attachment_id ) ?: '';

        $file_path = get_attached_file( $attachment_id );
        if ( is_string( $file_path ) && file_exists( $file_path ) ) {
            $path = $file_path;
        }
    }

    return array(
        'id'   => $attachment_id,
        'url'  => $url,
        'path' => $path,
    );
}

/**
 * Handle submissions from the bespoke contact layout.
 */
function wd4_handle_contact_form_submission(): void {
    $redirect = wp_get_referer();
    if ( ! $redirect ) {
        $redirect = home_url( '/contact/' );
    }

    $redirect = remove_query_arg( array( 'wd4_contact', 'wd4_contact_code' ), $redirect );

    $nonce = isset( $_POST['wd4_contact_nonce'] ) ? wp_unslash( (string) $_POST['wd4_contact_nonce'] ) : '';
    if ( ! wp_verify_nonce( $nonce, 'wd4_contact_submit' ) ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_contact'      => 'error',
                    'wd4_contact_code' => 'invalid_nonce',
                ),
                $redirect
            )
        );
        exit;
    }

    $ip_address = '';
    if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip_address = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
    }
    $ip_address = apply_filters( 'wd4_contact_request_ip', $ip_address );

    $honeypot = isset( $_POST['wd4_contact_hp'] ) ? trim( (string) wp_unslash( $_POST['wd4_contact_hp'] ) ) : '';
    if ( '' !== $honeypot ) {
        do_action( 'wd4_contact_honeypot_triggered', $honeypot, $ip_address );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_contact'      => 'error',
                    'wd4_contact_code' => 'invalid_request',
                ),
                $redirect
            )
        );
        exit;
    }

    $raw_timestamp      = isset( $_POST['wd4_contact_ts'] ) ? wp_unslash( (string) $_POST['wd4_contact_ts'] ) : '';
    $form_timestamp     = (int) $raw_timestamp;
    $current_time       = time();
    $max_window         = (int) apply_filters( 'wd4_contact_submission_window', DAY_IN_SECONDS );
    $max_future_drift   = (int) apply_filters( 'wd4_contact_submission_future_drift', 300 );
    $timestamp_is_valid = $form_timestamp > 0;

    if ( $timestamp_is_valid && $max_future_drift > 0 ) {
        $timestamp_is_valid = $form_timestamp <= ( $current_time + $max_future_drift );
    }

    if ( $timestamp_is_valid && $max_window > 0 ) {
        $timestamp_is_valid = ( $current_time - $form_timestamp ) <= $max_window;
    }

    if ( ! $timestamp_is_valid ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_contact'      => 'error',
                    'wd4_contact_code' => 'stale',
                ),
                $redirect
            )
        );
        exit;
    }

    $raw_name     = isset( $_POST['name'] ) ? wp_unslash( (string) $_POST['name'] ) : '';
    $raw_email    = isset( $_POST['email'] ) ? wp_unslash( (string) $_POST['email'] ) : '';
    $raw_business = isset( $_POST['business'] ) ? wp_unslash( (string) $_POST['business'] ) : '';
    $raw_website  = isset( $_POST['website'] ) ? wp_unslash( (string) $_POST['website'] ) : '';
    $raw_service  = isset( $_POST['service'] ) ? wp_unslash( (string) $_POST['service'] ) : '';
    $raw_message  = isset( $_POST['message'] ) ? wp_unslash( (string) $_POST['message'] ) : '';

    $name     = sanitize_text_field( $raw_name );
    $email    = sanitize_email( $raw_email );
    $business = sanitize_text_field( $raw_business );
    $website  = esc_url_raw( $raw_website );
    $service  = sanitize_text_field( $raw_service );
    $message  = sanitize_textarea_field( $raw_message );

    if ( empty( $name ) || empty( $message ) || ! is_email( $email ) ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_contact'      => 'error',
                    'wd4_contact_code' => 'missing_fields',
                ),
                $redirect
            )
        );
        exit;
    }

    $rate_limit_max    = (int) apply_filters( 'wd4_contact_rate_limit_max', 5 );
    $rate_limit_window = (int) apply_filters( 'wd4_contact_rate_limit_window', HOUR_IN_SECONDS );

    if ( $rate_limit_max > 0 && $rate_limit_window > 0 && $ip_address ) {
        $rate_key = 'wd4_contact_rl_' . md5( $ip_address );
        $attempts = (int) get_transient( $rate_key );

        if ( $attempts >= $rate_limit_max ) {
            wp_safe_redirect(
                add_query_arg(
                    array(
                        'wd4_contact'      => 'error',
                        'wd4_contact_code' => 'rate_limited',
                    ),
                    $redirect
                )
            );
            exit;
        }

        set_transient( $rate_key, $attempts + 1, $rate_limit_window );
    }

    $form_data = array(
        'name'     => $name,
        'email'    => $email,
        'business' => $business,
        'website'  => $website,
        'service'  => $service,
        'message'  => $message,
    );

    $recipients = apply_filters(
        'wd4_contact_recipients',
        array( '23scienceinsights@gmail.com' ),
        $form_data
    );

    if ( ! is_array( $recipients ) ) {
        $recipients = array( '23scienceinsights@gmail.com' );
    }

    $recipients = array_values( array_filter( array_map( 'sanitize_email', $recipients ), 'is_email' ) );

    if ( empty( $recipients ) ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_contact'      => 'error',
                    'wd4_contact_code' => 'no_recipient',
                ),
                $redirect
            )
        );
        exit;
    }

    $subject = apply_filters(
        'wd4_contact_subject',
        sprintf(
            /* translators: %s: Contact form submitter name. */
            __( 'New contact inquiry from %s', 'foxiz-child' ),
            $name
        ),
        $form_data
    );

    $body_lines = array(
        sprintf( 'Name: %s', $name ),
        sprintf( 'Email: %s', $email ),
    );

    if ( $business ) {
        $body_lines[] = sprintf( 'Business: %s', $business );
    }

    if ( $website ) {
        $body_lines[] = sprintf( 'Website: %s', $website );
    }

    if ( $service ) {
        $body_lines[] = sprintf( 'Service: %s', $service );
    }

    $body_lines[] = '';
    $body_lines[] = 'Message:';
    $body_lines[] = $message;

    $email_body = apply_filters( 'wd4_contact_email_body', implode( "\n", $body_lines ), $form_data );

    $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
    if ( $email && is_email( $email ) ) {
        $reply_to = $name ? sprintf( '%s <%s>', $name, $email ) : $email;
        $headers[] = 'Reply-To: ' . $reply_to;
    }

    $sent = wp_mail( $recipients, $subject, $email_body, $headers );

    if ( ! $sent ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_contact'      => 'error',
                    'wd4_contact_code' => 'send_failed',
                ),
                $redirect
            )
        );
        exit;
    }

    do_action( 'wd4_contact_form_submitted', $form_data );

    wp_safe_redirect(
        add_query_arg(
            array( 'wd4_contact' => 'success' ),
            $redirect
        )
    );
    exit;
}
add_action( 'admin_post_wd4_contact_submit', 'wd4_handle_contact_form_submission' );
add_action( 'admin_post_nopriv_wd4_contact_submit', 'wd4_handle_contact_form_submission' );

/**
 * Handle submissions from the testimonial guide opt-in form.
 */
function wd4_handle_testimonial_optin_submission(): void {
    $redirect = wp_get_referer();
    if ( ! $redirect ) {
        $redirect = home_url( '/contact/' );
    }

    $redirect = remove_query_arg( array( 'wd4_optin', 'wd4_optin_code' ), $redirect );

    $nonce = isset( $_POST['wd4_testimonial_nonce'] ) ? wp_unslash( (string) $_POST['wd4_testimonial_nonce'] ) : '';
    if ( ! wp_verify_nonce( $nonce, 'wd4_testimonial_optin' ) ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_optin'      => 'error',
                    'wd4_optin_code' => 'invalid_nonce',
                ),
                $redirect
            )
        );
        exit;
    }

    $raw_first_name = isset( $_POST['first_name'] ) ? wp_unslash( (string) $_POST['first_name'] ) : '';
    $raw_email      = isset( $_POST['email'] ) ? wp_unslash( (string) $_POST['email'] ) : '';

    $first_name = sanitize_text_field( $raw_first_name );
    $email      = sanitize_email( $raw_email );

    if ( ! is_email( $email ) ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_optin'      => 'error',
                    'wd4_optin_code' => 'missing_fields',
                ),
                $redirect
            )
        );
        exit;
    }

    $asset     = wd4_get_testimonial_guide_asset();
    $asset_url = $asset['url'];
    $asset_path = $asset['path'];

    if ( ! $asset_url && ! $asset_path ) {
        $asset_url = apply_filters( 'wd4_testimonial_optin_fallback_url', '', $asset );
    }

    if ( ! $asset_url && ! $asset_path ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_optin'      => 'error',
                    'wd4_optin_code' => 'no_asset',
                ),
                $redirect
            )
        );
        exit;
    }

    $form_data = array(
        'first_name' => $first_name,
        'email'      => $email,
        'asset_url'  => $asset_url,
        'asset_path' => $asset_path,
    );

    $subject = apply_filters(
        'wd4_testimonial_optin_subject',
        __( 'Your Glowing Testimonial Guide', 'foxiz-child' ),
        $form_data
    );

    $greeting = $first_name
        ? sprintf( /* translators: %s: Subscriber first name. */ __( 'Hi %s,', 'foxiz-child' ), $first_name )
        : __( 'Hi there,', 'foxiz-child' );

    $body_lines = array(
        $greeting,
        '',
        __( 'Thanks for requesting the BTL Glowing Testimonial Guide. You can download your copy using the link below:', 'foxiz-child' ),
    );

    if ( $asset_url ) {
        $body_lines[] = $asset_url;
        $body_lines[] = '';
    }

    $body_lines[] = __( 'If the link does not work, simply reply to this email and we will make sure you receive the guide.', 'foxiz-child' );
    $body_lines[] = '';
    $body_lines[] = sprintf( __( 'Sent from %s', 'foxiz-child' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

    $email_body = apply_filters( 'wd4_testimonial_optin_email_body', implode( "\n", $body_lines ), $form_data );

    $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

    $attachments = array();
    if ( $asset_path ) {
        $attachments[] = $asset_path;
    }

    $sent = wp_mail( $email, $subject, $email_body, $headers, $attachments );

    if ( ! $sent ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'wd4_optin'      => 'error',
                    'wd4_optin_code' => 'send_failed',
                ),
                $redirect
            )
        );
        exit;
    }

    do_action( 'wd4_testimonial_optin_submitted', $form_data );

    $notify_admin = apply_filters( 'wd4_testimonial_optin_notify_admin', true, $form_data );
    if ( $notify_admin ) {
        $admin_recipients = apply_filters( 'wd4_testimonial_optin_admin_recipients', array( get_option( 'admin_email' ) ), $form_data );

        if ( ! is_array( $admin_recipients ) ) {
            $admin_recipients = array( get_option( 'admin_email' ) );
        }

        $admin_recipients = array_values( array_filter( array_map( 'sanitize_email', $admin_recipients ), 'is_email' ) );

        if ( $admin_recipients ) {
            $admin_subject = apply_filters(
                'wd4_testimonial_optin_admin_subject',
                __( 'New testimonial guide request', 'foxiz-child' ),
                $form_data
            );

            $admin_body = array(
                sprintf( 'Email: %s', $email ),
            );

            if ( $first_name ) {
                $admin_body[] = sprintf( 'First name: %s', $first_name );
            }

            if ( $asset_url ) {
                $admin_body[] = sprintf( 'Guide URL: %s', $asset_url );
            }

            wp_mail( $admin_recipients, $admin_subject, implode( "\n", $admin_body ), array( 'Content-Type: text/plain; charset=UTF-8' ) );
        }
    }

    wp_safe_redirect(
        add_query_arg(
            array( 'wd4_optin' => 'success' ),
            $redirect
        )
    );
    exit;
}
add_action( 'admin_post_wd4_testimonial_optin_submit', 'wd4_handle_testimonial_optin_submission' );
add_action( 'admin_post_nopriv_wd4_testimonial_optin_submit', 'wd4_handle_testimonial_optin_submission' );