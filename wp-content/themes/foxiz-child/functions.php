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
