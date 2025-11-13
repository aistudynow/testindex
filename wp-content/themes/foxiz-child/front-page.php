<?php
/**
 * Custom front page that mirrors the Elementor homepage layout while
 * remaining fully template-driven.
 *
 * @package Foxiz Child
 */

declare( strict_types=1 );

get_header();

$tagline = get_bloginfo( 'description', 'display' );
$tagline = $tagline ?: __( 'Latest stories and curated collections for AI creators.', 'foxiz-child' );

$featured_query = new WP_Query(
    array(
        'post_type'           => 'post',
        'posts_per_page'      => 1,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    )
);

$featured_post = $featured_query->have_posts() ? $featured_query->posts[0] : null;
wp_reset_postdata();

$featured_post_id = $featured_post instanceof WP_Post ? (int) $featured_post->ID : 0;

$feed_query = new WP_Query(
    array(
        'post_type'           => 'post',
        'posts_per_page'      => 6,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'post__not_in'        => $featured_post_id ? array( $featured_post_id ) : array(),
    )
);




$category_sections = array(
    array(
        'slug'  => 'ai-news',
        'title' => __( 'AI News', 'foxiz-child' ),
    ),
    array(
        'slug'  => 'comfyui-workflows',
        'title' => __( 'ComfyUI Workflows', 'foxiz-child' ),
    ),
    array(
        'slug'  => 'how-to-guides',
        'title' => __( 'How-To Guides', 'foxiz-child' ),
    ),
    array(
        'slug'  => 'lora',
        'title' => __( 'LoRA Tutorials', 'foxiz-child' ),
    ),
);

$category_sections = array_map(
    static function ( $section ) {
        $title = isset( $section['title'] ) ? $section['title'] : '';

        $section['tagline'] = sprintf(
            /* translators: %s: Category title such as "AI News". */
            __( 'View all %s articles', 'foxiz-child' ),
            $title
        );

        return $section;
    },
    $category_sections
);
?>




<main id="primary" class="site-main wd4-frontpage">
    <div class="container">
        <div class="mainContainer">
            <?php if ( $featured_post instanceof WP_Post ) : ?>
                <?php
                $post = $featured_post;
                setup_postdata( $post );

                $primary_category      = get_the_category();
                $primary_category      = ! empty( $primary_category ) ? $primary_category[0] : null;
                $primary_category_link = $primary_category instanceof WP_Term ? get_category_link( $primary_category ) : '';
                $featured_meta_segments = array( get_the_date() );

                $estimated_reading_time = function_exists( 'foxiz_reading_time' ) ? foxiz_reading_time( get_the_ID() ) : '';
                if ( $estimated_reading_time ) {
                    $featured_meta_segments[] = $estimated_reading_time;
                }
                ?>
                <article <?php post_class( 'cartHolder bigCart' ); ?>>
                   
                   
                     <figure>
                        <a class="storyImage" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
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
                        </a>
                    </figure>

                    <h3 class="hdg3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

                    <div class="sortDec" hidden aria-hidden="true">
                        <?php echo esc_html( wp_trim_words( get_the_excerpt(), 40, '&hellip;' ) ); ?>
                    </div>

                    <?php
                    // No category/date metadata block on the featured card per design request.
                    ?>
                </article>
                <?php wp_reset_postdata(); ?>
            <?php endif; ?>

            <?php if ( $feed_query->have_posts() ) : ?>
                <div class="secHdg">
                    <span class="hdgStyle"><span><?php esc_html_e( 'Latest updates', 'foxiz-child' ); ?></span></span>
                </div>

                <?php if ( $tagline ) : ?>
                    <div class="sortDec" hidden aria-hidden="true"><?php echo esc_html( $tagline ); ?></div>
                <?php endif; ?>

                <?php
                while ( $feed_query->have_posts() ) :
                    $feed_query->the_post();

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
                wp_reset_postdata();
                ?>
            <?php else : ?>
                 <div class="cartHolder">
        <div class="sortDec wd4-emptyFeed"><?php esc_html_e( 'Publish a few posts to populate the feed.', 'foxiz-child' ); ?></div>
    </div>
            <?php endif; ?>

            <?php
            foreach ( $category_sections as $section ) :
                $term = get_category_by_slug( $section['slug'] );

                if ( ! ( $term instanceof WP_Term ) ) {
                    continue;
                }

                $section_link  = get_category_link( $term );
                $section_query = new WP_Query(
                    array(
                        'post_type'           => 'post',
                        'posts_per_page'      => 10,
                        'ignore_sticky_posts' => true,
                        'no_found_rows'       => true,
                        'tax_query'           => array(
                            array(
                                'taxonomy' => 'category',
                                'field'    => 'slug',
                                'terms'    => $section['slug'],
                            ),
                        ),
                    )
                );

                if ( ! $section_query->have_posts() ) {
                    continue;
                }
                ?>
                <div class="mt20">
                    <div class="secHdg">
                        <span class="hdgStyle"><span><?php echo esc_html( $section['title'] ); ?></span></span>
                    </div>

                    <div class="htSlider">
                        <div class="htsHeader">
                            <div class="hdg2">
                                <?php if ( ! is_wp_error( $section_link ) && $section_link ) : ?>
                                    <a href="<?php echo esc_url( $section_link ); ?>"><?php echo esc_html( $section['title'] ); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html( $section['title'] ); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <ul>
                            <?php
                            while ( $section_query->have_posts() ) :
                                $section_query->the_post();

                                $primary_category      = get_the_category();
                                $primary_category      = ! empty( $primary_category ) ? $primary_category[0] : null;
                                $primary_category_link = $primary_category instanceof WP_Term ? get_category_link( $primary_category ) : '';
                                ?>
                                <li <?php post_class(); ?>>
                                   
                                    <figure>
                                        <a class="storyImage" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                                            <?php
                                            if ( has_post_thumbnail() ) {
                                                echo wd4_frontpage_image(
                                                    get_the_ID(),
                                                    'wd4-frontpage-slider',
                                                    array(
                                                        'class'            => 'wp-post-image',
                                                        'loading'          => 'lazy',
                                                        'decoding'         => 'async',
                                                        'sizes'            => implode(
                                                            ', ',
                                                            array(
                                                                '(max-width: 599px) 48vw',
                                                                '(max-width: 1023px) 216px',
                                                                '(max-width: 1439px) 252px',
                                                                '280px',
                                                            )
                                                        ),
                                                        'max_srcset_width' => 600,
                                                    )
                                                );
                                            }
                                            ?>
                                        </a>
                                    </figure>
                                    <p><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></p>
                                 
                                 
                                 
                                 
                                     <div class="cardMeta">
                                        <?php if ( $primary_category instanceof WP_Term && ! is_wp_error( $primary_category_link ) ) : ?>
                                            <a class="cardMeta__cat" href="<?php echo esc_url( $primary_category_link ); ?>"><?php echo esc_html( $primary_category->name ); ?></a>
                                        <?php endif; ?>
                                        <span class="cardMeta__time"><?php echo esc_html( get_the_date() ); ?></span>
                                    </div>
                                </li>
                                <?php
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </ul>
                    </div>

                    <?php if ( ! is_wp_error( $section_link ) && $section_link ) : ?>
                        <div class="viewMoreButton">
                            <a href="<?php echo esc_url( $section_link ); ?>"><?php echo esc_html( $section['tagline'] ); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            endforeach;
            ?>
        </div>
    </div>
</main>

<?php
get_footer();