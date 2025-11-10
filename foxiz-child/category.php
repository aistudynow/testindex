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

                <?php if ( have_posts() ) : ?>
                    <div class="secHdg">
                        <span class="hdgStyle"><span><?php esc_html_e( 'More stories', 'foxiz-child' ); ?></span></span>
                    </div>
                <?php endif; ?>

                <?php
                while ( have_posts() ) :
                    the_post();

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
                        <a class="storyLink" href="<?php the_permalink(); ?>" aria-label="<?php printf( esc_attr__( 'Read "%s"', 'foxiz-child' ), wp_strip_all_tags( get_the_title() ) ); ?>"></a>
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
                            ?>
                        </figure>

                        <h3 class="hdg3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

                        <div class="cardMeta">
                            <?php if ( $primary_category instanceof WP_Term && ! is_wp_error( $primary_category_link ) ) : ?>
                                <a class="cardMeta__cat" href="<?php echo esc_url( $primary_category_link ); ?>"><?php echo esc_html( $primary_category->name ); ?></a>
                            <?php endif; ?>
                            <span class="cardMeta__time"><?php echo esc_html( implode( ' · ', array_filter( $meta_segments ) ) ); ?></span>
                        </div>
                    </article>
                    <?php
                endwhile;

                the_posts_pagination(
                    array(
                        'mid_size'  => 1,
                        'prev_text' => esc_html__( 'Prev', 'foxiz-child' ),
                        'next_text' => esc_html__( 'Next', 'foxiz-child' ),
                    )
                );
            else :
                ?>
                <div class="cartHolder">
                    <div class="sortDec wd4-emptyFeed"><?php esc_html_e( 'There are no posts to display in this category yet.', 'foxiz-child' ); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
get_footer();