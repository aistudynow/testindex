<?php
/**
 * Category archive template that mirrors the custom front-page layout.
 *
 * @package Foxiz Child
 */

declare( strict_types=1 );

get_header();

$term            = get_queried_object();
$term_name       = $term instanceof WP_Term ? $term->name : '';
$term_description = '';

if ( $term instanceof WP_Term ) {
    $term_description = term_description( (int) $term->term_id, 'category' );
}
?>

<main id="primary" class="site-main wd4-frontpage wd4-category">
    <div class="block-inner">
        <div class="container">
            <div class="mainContainer">
                <?php if ( $term_name ) : ?>
                    <div class="secHdg">
                        <h1 class="hdgStyle"><span><?php echo esc_html( $term_name ); ?></span></h1>
                    </div>
                <?php else : ?>
                    <h1 class="screen-reader-text"><?php esc_html_e( 'Category archive', 'foxiz-child' ); ?></h1>
                <?php endif; ?>

                <?php if ( $term_description ) : ?>
                    <div class="wd4-category__description">
                        <?php echo wp_kses_post( $term_description ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( have_posts() ) : ?>
                    <?php if ( ! is_paged() ) : ?>
                        <?php
                        the_post();

                        $hero_excerpt = wp_trim_words( get_the_excerpt(), 40, '&hellip;' );
                        ?>
                        <article <?php post_class( 'cartHolder bigCart' ); ?>>
                            <a class="storyLink" href="<?php the_permalink(); ?>" aria-label="<?php printf( esc_attr__( 'Read "%s"', 'foxiz-child' ), wp_strip_all_tags( get_the_title() ) ); ?>"></a>
                            <figure>
                                <?php
                                if ( has_post_thumbnail() ) {
                                    echo wd4_frontpage_image(
                                        get_the_ID(),
                                        'wd4-frontpage-hero',
                                        array(
                                            'class'            => 'wp-post-image',
                                            'loading'          => 'eager',
                                            'fetchpriority'    => 'high',
                                            'decoding'         => 'async',
                                            'sizes'            => implode(
                                                ', ',
                                                array(
                                                    '(max-width: 599px) 96vw',
                                                    '(max-width: 1023px) 88vw',
                                                    '(max-width: 1439px) 640px',
                                                    '720px',
                                                )
                                            ),
                                            'max_srcset_width' => 720,
                                        )
                                    );
                                }
                                ?>
                            </figure>

                            <h2 class="hdg3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                            <?php if ( $hero_excerpt ) : ?>
                                <div class="sortDec"><?php echo esc_html( $hero_excerpt ); ?></div>
                            <?php endif; ?>
                        </article>
                    <?php endif; ?>

                    <?php
                    $has_feed_posts = have_posts();

                    if ( $has_feed_posts ) :
                        if ( ! is_paged() ) :
                            ?>
                            <div class="secHdg">
                                <span class="hdgStyle"><span><?php esc_html_e( 'More stories', 'foxiz-child' ); ?></span></span>
                            </div>
                            <?php
                        endif;
                        ?>
                        <div id="wd4-category-feed" class="wd4-category__feed">
                            <?php
                            while ( have_posts() ) :
                                the_post();

                                echo wd4_get_category_card_markup( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            endwhile;
                            ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="cartHolder">
                        <div class="sortDec wd4-emptyFeed"><?php esc_html_e( 'There are no posts to display in this category yet.', 'foxiz-child' ); ?></div>
                    </div>
                <?php endif; ?>

                <?php
                global $wp_query;
                $max_pages    = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 1;
                $current_page = max( 1, (int) get_query_var( 'paged', 1 ) );

                $next_page_url = '';

                if ( $current_page < $max_pages ) {
                    $next_page_url = get_pagenum_link( $current_page + 1 );
                }

                $category_cache_key    = '';
                $category_feed_signature = '';

                if ( $term instanceof WP_Term ) {
                    $category_cache_key     = sanitize_key( $term->taxonomy . '-' . $term->term_id );
                    $category_feed_signature = wd4_get_category_feed_signature( $term );
                }

                if ( $next_page_url ) :
                    ?>
                    <div
                        class="pagination-infinite"
                        data-next="<?php echo esc_url( $next_page_url ); ?>"
                        <?php if ( $category_feed_signature ) : ?>data-feed-signature="<?php echo esc_attr( $category_feed_signature ); ?>"<?php endif; ?>
                        <?php if ( $category_cache_key ) : ?>data-cache-key="<?php echo esc_attr( $category_cache_key ); ?>"<?php endif; ?>
                        data-cache-ttl="21600000"
                        data-cache-limit="6"
                    >
                        <div class="infinite-trigger" role="status" aria-live="polite">
                            <i class="rb-loader" aria-hidden="true"></i>
                            <span class="pagination-infinite__label"><?php esc_html_e( 'Loading more posts…', 'foxiz-child' ); ?></span>
                            <span class="screen-reader-text"><?php esc_html_e( 'Loading more posts…', 'foxiz-child' ); ?></span>
                        </div>
                        <button type="button" class="pagination-infinite__retry" data-retry-label="<?php esc_attr_e( 'Try again', 'foxiz-child' ); ?>"><?php esc_html_e( 'Load more posts', 'foxiz-child' ); ?></button>
                    </div>
                    <?php
                endif;

                $pagination_fallback = get_the_posts_pagination(
                    array(
                        'mid_size'  => 1,
                        'prev_text' => esc_html__( 'Prev', 'foxiz-child' ),
                        'next_text' => esc_html__( 'Next', 'foxiz-child' ),
                    )
                );

                if ( $pagination_fallback ) :
                    ?>
                    <noscript>
                        <?php echo wp_kses_post( $pagination_fallback ); ?>
                    </noscript>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();