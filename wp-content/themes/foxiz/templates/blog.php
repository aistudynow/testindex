<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_content_empty' ) ) {
	function foxiz_content_empty() { ?>
		<div class="section-not-found not-found">
			<div class="wrap rb-container gutter-p20">
				<div class="not-found-inner">
					<header class="page-header">
						<h1 class="page-title"><?php foxiz_html_e( 'Nothing Found', 'foxiz' ) ?></h1>
					</header>
					<div class="page-content entry-content rbct">
						<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
							<p><?php esc_html_e( 'Ready to publish your first post?', 'foxiz' ) ?>
								<a href="<?php echo admin_url( 'post-new.php' ); ?>"><?php esc_html_e( ' Get started here', 'foxiz' ); ?></a>
							</p>
						<?php else: ?>
							<p><?php foxiz_html_e( 'It looks like nothing was found at this location. Maybe try a search?', 'foxiz' ); ?></p>
							<?php get_search_form(); ?>
							<?php if ( ! is_front_page() ) : ?>
								<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="return-home h6" title="<?php echo foxiz_attr__( 'Return to Home', 'foxiz' ); ?>"><?php echo foxiz_html__( 'Return to Home', 'foxiz' ); ?></a>
							<?php endif;
						endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_category_page_header' ) ) {
	function foxiz_category_page_header( $settings = [] ) {

		if ( empty( $settings['category_header'] ) ) {
			foxiz_category_page_header_1( $settings );
			return;
		}

		switch ( $settings['category_header'] ) {
			case '2' :
				foxiz_category_page_header_2( $settings );
				break;
			case '3' :
				foxiz_category_page_header_3( $settings );
				break;
			case '4' :
				foxiz_category_page_header_4( $settings );
				break;
			case 'none':
				break;
			default:
				foxiz_category_page_header_1( $settings );
		}
	}
}

if ( ! function_exists( 'foxiz_category_page_header_1' ) ) {
	function foxiz_category_page_header_1( $settings = [] ) {

		$class_name = 'archive-header category-header-1';
		if ( ! empty( $settings['pattern'] ) && '-1' !== (string) $settings['pattern'] ) {
			$class_name .= ' is-pattern pattern-' . $settings['pattern'];
		} else {
			$class_name .= ' solid-bg';
		}
		?>
		<header class="<?php echo strip_tags( $class_name ); ?>">
			<div class="rb-container edge-padding">
				<div class="archive-inner">
					<div class="archive-header-content">
						<?php if ( ! empty( $settings['breadcrumb'] ) ) {
							echo foxiz_get_breadcrumb( 'archive-breadcrumb' );
						}
						foxiz_single_category_title( $settings );
						the_archive_description( '<div class="taxonomy-description rb-text">', '</div>' );
						if ( ! empty( $settings['subcategory'] ) ) {
							foxiz_sub_categories( $settings['category'] );
						} ?>
					</div>
					<?php if ( ! empty( $settings['featured_image'] ) ) {
						if ( empty( $settings['featured_image_urls'] ) ) {
							$settings['featured_image_urls'] = [];
						}
						echo '<div class="category-hero-wrap">';
						foxiz_render_category_hero( $settings['featured_image'], $settings['featured_image_urls'] );
						echo '</div>';
					} ?>
				</div>
			</div>
		</header>
	<?php }
}

if ( ! function_exists( 'foxiz_sub_categories' ) ) {
	function foxiz_sub_categories( $category_id = '' ) {

		$categories = get_categories( [
			'parent'     => (int) $category_id,
			'hide_empty' => true,
		] );

		if ( count( $categories ) ): ?>
			<div class="subcat-wrap">
				<span class="subcat-heading"><i class="rbi rbi-share"></i><?php foxiz_html_e( 'Find More:', 'foxiz' ) ?></span>
				<?php foreach ( $categories as $category ) : ?>
					<span class="sub-cat-item h5"><a href="<?php echo foxiz_get_term_link( $category->term_id ) ?>"><?php foxiz_render_inline_html( $category->name ); ?></a></span>
				<?php endforeach; ?>
			</div>
		<?php endif;
	}
}

if ( ! function_exists( 'foxiz_category_page_header_2' ) ) {
	function foxiz_category_page_header_2( $settings = [] ) {

		$class_name = 'archive-header category-header-2';
		if ( ! empty( $settings['pattern'] ) && '-1' !== (string) $settings['pattern'] ) {
			$class_name .= ' is-pattern pattern-' . $settings['pattern'];
		} else {
			$class_name .= ' solid-bg';
		} ?>
		<header class="<?php echo strip_tags( $class_name ); ?>">
			<div class="rb-container edge-padding">
				<div class="archive-inner">
					<div class="archive-header-content light-scheme">
						<?php if ( ! empty( $settings['breadcrumb'] ) ) {
							echo foxiz_get_breadcrumb( 'archive-breadcrumb' );
						}
						foxiz_single_category_title( $settings );
						the_archive_description( '<div class="taxonomy-description rb-text">', '</div>' );

						if ( ! empty( $settings['subcategory'] ) ) :
							$categories = get_categories( [
								'parent'     => $settings['category'],
								'hide_empty' => false,
							] );
							if ( count( $categories ) ): ?>
								<div class="block-qlinks qlayout-2 yes-wrap">
									<ul class="qlinks-inner">
										<?php foreach ( $categories as $category ) : ?>
											<li class="qlink h6"><a href="<?php echo foxiz_get_term_link( $category->term_id ) ?>"><?php foxiz_render_inline_html( $category->name ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif;
						endif; ?>
					</div>
				</div>
				<div class="category-feat-overlay">
					<?php if ( ! empty( $settings['featured_image'][0] ) ) : ?>
						<img src="<?php echo esc_url( wp_get_attachment_image_url( $settings['featured_image'][0], '2048×2048' ) ); ?>" alt="<?php echo get_post_meta( $settings['featured_image'][0], '_wp_attachment_image_alt', true ); ?>"/>
					<?php endif; ?>
				</div>
			</div>
		</header>
	<?php }
}

if ( ! function_exists( 'foxiz_category_page_header_3' ) ) {
	function foxiz_category_page_header_3( $settings = [] ) {

		$class_name = 'archive-header category-header-3';
		if ( ! empty( $settings['pattern'] ) && '-1' !== (string) $settings['pattern'] ) {
			$class_name .= ' is-pattern pattern-' . $settings['pattern'];
		} else {
			$class_name .= ' solid-bg';
		} ?>
		<header class="<?php echo strip_tags( $class_name ); ?>">
			<div class="rb-container edge-padding archive-header-content">
				<?php
				if ( ! empty( $settings['breadcrumb'] ) ) {
					echo foxiz_get_breadcrumb( 'archive-breadcrumb' );
				}
				foxiz_single_category_title( $settings );
				if ( ! empty( $settings['subcategory'] ) ) {
					foxiz_sub_categories( $settings['category'] );
				}
				?>
			</div>
		</header>
	<?php }
}

if ( ! function_exists( 'foxiz_category_page_header_4' ) ) {
	function foxiz_category_page_header_4( $settings = [] ) {

		$class_name = 'archive-header category-header-4';
		if ( ! empty( $settings['pattern'] ) && '-1' !== (string) $settings['pattern'] ) {
			$class_name .= ' is-pattern pattern-' . $settings['pattern'];
		} else {
			$class_name .= ' solid-bg';
		} ?>
		<header class="<?php echo strip_tags( $class_name ); ?>">
			<div class="rb-container edge-padding archive-header-content">
				<?php
				if ( ! empty( $settings['breadcrumb'] ) ) {
					echo foxiz_get_breadcrumb( 'archive-breadcrumb' );
				}
				foxiz_single_category_title( $settings );
				the_archive_description( '<div class="taxonomy-description rb-text">', '</div>' );
				if ( ! empty( $settings['subcategory'] ) ) :
					$categories = get_categories( [
						'parent'     => $settings['category'],
						'hide_empty' => false,
					] );
					if ( count( $categories ) ): ?>
						<div class="block-qlinks qlayout-2 yes-wrap">
							<ul class="qlinks-inner">
								<?php foreach ( $categories as $category ) : ?>
									<li class="qlink h6">
										<a href="<?php echo foxiz_get_term_link( $category->term_id ) ?>"><?php foxiz_render_inline_html( $category->name ); ?></a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif;
				endif;
				?>
			</div>
		</header>
	<?php }
}

if ( ! function_exists( 'foxiz_archive_page_header' ) ) {
	function foxiz_archive_page_header( $settings = [] ) {

		if ( ! empty( $settings['archive_header'] ) && 'none' === $settings['archive_header'] ) {
			return;
		}

		$class_name = 'archive-header is-archive-page';

		if ( ! empty( $settings['archive_header'] ) && '2' === (string) $settings['archive_header'] ) {
			$class_name .= ' is-centered';
		}
		if ( ! empty( $settings['pattern'] ) && '-1' !== (string) $settings['pattern'] ) {
			$class_name .= ' is-pattern pattern-' . $settings['pattern'];
		} else {
			$class_name .= ' solid-bg';
		} ?>
		<header class="<?php echo strip_tags( $class_name ); ?>">
			<div class="rb-container edge-padding archive-header-content">
				<?php if ( ! empty( $settings['breadcrumb'] ) ) {
					echo foxiz_get_breadcrumb( 'archive-breadcrumb' );
				}
				foxiz_archive_title();
				the_archive_description( '<div class="taxonomy-description rb-text">', '</div>' );
				?>
			</div>
		</header>
	<?php }
}

if ( ! function_exists( 'foxiz_search_page_header' ) ) {
	function foxiz_search_page_header( $settings = [] ) {

		foxiz_search_page_header_form( $settings );
		if ( ! empty( $settings['top_template'] ) ) {
			echo do_shortcode( $settings['top_template'] );
		}
	}
}


if ( ! function_exists( 'foxiz_search_page_header_form' ) ) {
	function foxiz_search_page_header_form( $settings = [] ) {

		if ( ! empty( $settings['search_header'] ) && 'none' === $settings['search_header'] ) {
			return;
		}
		$total = 0;
		global $wp_query;
		if ( ! empty( $wp_query->found_posts ) ) {
			$total = $wp_query->found_posts;
		}
		$class_name = 'search-header';
		if ( ! empty( $settings['header_scheme'] ) ) {
			$class_name .= ' light-scheme';
		}
		if ( ! empty( $settings['search_header'] ) && 'wrapper' === $settings['search_header'] ) : ?>
			<header class="rb-container edge-padding">
			<div class="<?php echo strip_tags( $class_name ); ?>">
		<?php else : ?>
			<header class="<?php echo strip_tags( $class_name ); ?>">
			<div class="rb-container edge-padding">
		<?php endif; ?>
				<div class="search-header-inner">
					<div class="search-header-content">
						<h1 class="search-title"><?php printf( foxiz_html__( 'Search Results for: %s', 'foxiz' ), get_search_query() ); ?></h1>
						<p class="search-subtitle"><?php printf( foxiz_html__( 'Showing %s results for your search', 'foxiz' ), $total ); ?></p>
					</div>
					<div class="search-header-form"><?php get_search_form(); ?></div>
				</div>
			</div>
		</header>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_condition_blog_template' ) ) {
	/**
	 * @param      $template
	 * @param bool $show
	 *  Ensure unique posts work with standard pagination
	 */
	function foxiz_render_condition_blog_template( $template, $show = true ) {

		$template = trim( $template );
		if ( $show ) {
			echo do_shortcode( $template );
		} else {
			do_shortcode( $template );
		}
	}
}

if ( ! function_exists( 'foxiz_blog_embed_template' ) ) {
	function foxiz_blog_embed_template( $settings ) {

		if ( empty( $settings['template'] ) || ! shortcode_exists( 'Ruby_E_Template' ) ) {
			return;
		}
		if ( ! empty( $settings['template_display'] ) && '1' === (string) $settings['template_display'] ) {
			$paged = get_query_var( 'paged' );
		}
		if ( empty( $paged ) || $paged < 2 )  : ?>
			<div class="archive-builder">
				<?php foxiz_render_condition_blog_template( $settings['template'] ); ?>
			</div>
		<?php else :
			foxiz_render_condition_blog_template( $settings['template'], false );
		endif;
	}
}

if ( ! function_exists( 'foxiz_blog_embed_template_bottom' ) ) {
	function foxiz_blog_embed_template_bottom( $settings ) {

		if ( empty( $settings['template_bottom'] ) || ! shortcode_exists( 'Ruby_E_Template' ) ) {
			return;
		}
		if ( ! empty( $settings['template_display'] ) && '1' === (string) $settings['template_display'] ) {
			$paged = get_query_var( 'paged' );
		}
		if ( empty( $paged ) || $paged < 2 )  : ?>
			<div class="archive-builder archive-builder-bottom">
				<?php foxiz_render_condition_blog_template( $settings['template_bottom'] ); ?>
			</div>
		<?php else :
			foxiz_render_condition_blog_template( $settings['template_bottom'], false );
		endif;
	}
}

if ( ! function_exists( 'foxiz_the_blog' ) ) {
	function foxiz_the_blog( $settings = [], $_query = null ) {

		if ( ! empty( $settings['template_global'] ) ) {
			foxiz_blog_template( $settings['template_global'] );

			return;
		}

		if ( empty( $_query ) ) {

			global $wp_query;
			$_query = $wp_query;

			/** remove duplicates posts */
			if ( ! empty( $GLOBALS['foxiz_queried_ids'] ) && is_array( $GLOBALS['foxiz_queried_ids'] ) && count( $GLOBALS['foxiz_queried_ids'] ) ) {
				$params                 = $_query->query_vars;
				$params['post__not_in'] = (array) $GLOBALS['foxiz_queried_ids'];

				/** get new WP_Query */
				$_query = new WP_Query( $params );
				$_query->set( 'foxiz_queried_ids', $GLOBALS['foxiz_queried_ids'] );

				foxiz_add_queried_ids( $_query );
				$settings['unique'] = true;
			}
		}

		$classes   = [];
		$classes[] = 'blog-wrap';
		if ( ! empty( $settings['classes'] ) ) {
			$classes[] = $settings['classes'];
		}
		if ( empty( $settings['sidebar_position'] ) || 'none' === $settings['sidebar_position'] ) {
			$settings['sidebar_name'] = false;
		}
		if ( empty( $settings['sidebar_name'] ) || ! is_active_sidebar( $settings['sidebar_name'] ) ) {
			$classes[] = 'without-sidebar';
		} else {
			$classes[] = 'is-sidebar-' . $settings['sidebar_position'];

			if ( ! empty( $settings['sticky_sidebar'] ) ) {
				if ( '2' === (string) $settings['sticky_sidebar'] ) {
					$classes[] = 'sticky-last-w';
				} else {
					$classes[] = 'sticky-sidebar';
				}
			}
		} ?>
		<div class="<?php echo strip_tags( join( ' ', $classes ) ); ?>">
			<div class="rb-container edge-padding">
				<div class="grid-container">
					<div class="blog-content">
						<?php foxiz_the_blog_heading( $settings ); ?>
						<?php echo foxiz_get_blog_layout( $settings, $_query ); ?>
					</div>
					<?php if ( ! empty( $settings['sidebar_name'] ) && is_active_sidebar( $settings['sidebar_name'] ) ) : ?>
						<div class="blog-sidebar sidebar-wrap">
							<div class="sidebar-inner clearfix">
								<?php dynamic_sidebar( $settings['sidebar_name'] ); ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_get_blog_layout' ) ) {
	function foxiz_get_blog_layout( $settings = [], $_query = null ) {

		$layout = 'classic';
		if ( ! empty( $settings['layout'] ) ) {
			$layout = $settings['layout'];
		}

		switch ( $layout ) {
			case 'classic_1' :
				return foxiz_get_classic_1( $settings, $_query );
			case 'grid_2' :
				return foxiz_get_grid_2( $settings, $_query );
			case 'grid_box_1' :
				return foxiz_get_grid_box_1( $settings, $_query );
			case 'grid_box_2' :
				return foxiz_get_grid_box_2( $settings, $_query );
			case 'grid_small_1' :
				return foxiz_get_grid_small_1( $settings, $_query );
			case 'list_1' :
				return foxiz_get_list_1( $settings, $_query );
			case 'list_2' :
				return foxiz_get_list_2( $settings, $_query );
			case 'list_box_1' :
				return foxiz_get_list_box_1( $settings, $_query );
			case 'list_box_2' :
				return foxiz_get_list_box_2( $settings, $_query );
			case 'grid_1' :
			default:
				return foxiz_get_grid_1( $settings, $_query );
		}
	}
}

if ( ! function_exists( 'foxiz_the_blog_heading' ) ) {
	function foxiz_the_blog_heading( $settings = [] ) {

		if ( empty( $settings['blog_heading'] ) ) {
			return;
		}

		if ( empty( $settings['blog_heading_layout'] ) ) {
			$settings['blog_heading_layout'] = foxiz_get_option( 'heading_layout' );
		}
		if ( empty( $settings['blog_heading_layout'] ) ) {
			$settings['blog_heading_layout'] = 1;
		}
		$classes = false;

		if ( empty( $settings['blog_heading_tag'] ) ) {
			$settings['blog_heading_tag'] = 'span';
			$classes                      = 'h3';
		}

		echo foxiz_get_heading( [
			'title'    => $settings['blog_heading'],
			'html_tag' => $settings['blog_heading_tag'],
			'classes'  => $classes,
			'layout'   => $settings['blog_heading_layout'],
		] );
	}
}

if ( ! function_exists( 'foxiz_author_page_header' ) ) {
	function foxiz_author_page_header( $settings = [] ) {

		$author_id = get_queried_object_id();

		$bio = get_user_meta( $author_id, 'author_bio', true );

		if ( '-1' === (string) $bio || ! foxiz_get_option( 'author_bio' ) ) {
			return;
		}

		$job         = get_the_author_meta( 'job', $author_id );
		$description = get_the_author_meta( 'description', $author_id );

		$class_name = 'archive-header author-header';
		if ( ! empty( $settings['pattern'] ) && '-1' !== (string) $settings['pattern'] ) {
			$class_name .= ' is-pattern pattern-' . $settings['pattern'];
		} else {
			$class_name .= ' solid-bg';
		} ?>
		<header class="<?php echo strip_tags( $class_name ); ?>">
			<div class="rb-container edge-padding">
				<div class="author-header-inner">
					<?php if ( ! empty( $settings['breadcrumb'] ) ) {
						echo foxiz_get_breadcrumb( 'archive-breadcrumb' );
					} ?>
					<div class="ubio">
						<div class="ubio-inner">
							<div class="bio-info bio-avatar"><?php
								$author_image_id = (int) get_the_author_meta( 'author_image_id', $author_id );
								if ( $author_image_id !== 0 ) {
									echo foxiz_get_avatar_by_attachment( $author_image_id, 'medium', false );
								} else {
									echo get_avatar( $author_id, 200 );
								}
							?></div>
							<div class="bio-content">
								<?php if ( ! empty( $settings['follow_author_header'] ) && foxiz_get_option('bookmark_system') && ! foxiz_is_amp() ) : ?>
									<div class="bio-title-wrap b-follow">
										<?php foxiz_author_bio_title( $author_id ); ?>
										<span class="rb-follow follow-trigger" data-name="<?php echo get_the_author_meta( 'display_name', $author_id ); ?>" data-uid="<?php echo strip_tags( $author_id ); ?>"></span>
									</div>
								<?php else:
									foxiz_author_bio_title( $author_id );
								endif;
								if ( ! empty( $description ) ) : ?>
									<div class="bio-description rb-text"><?php echo foxiz_strip_tags( $description ); ?></div>
								<?php endif;
								if ( foxiz_get_social_list( foxiz_get_user_socials( $author_id ), true, false ) ) : ?>
									<div class="usocials meta-text tooltips-n">
										<?php if ( ! empty( $job ) ) : ?>
											<div class="bio-job"><?php foxiz_render_inline_html( $job ); ?></div>
										<?php endif; ?>
										<span class="ef-label"><?php foxiz_html_e( 'Follow: ', 'foxiz' ); ?></span><?php echo foxiz_get_social_list( foxiz_get_user_socials( $author_id ), true, false ); ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<?php if ( foxiz_get_option( 'author_count' ) ) : ?>
							<div class="bio-count-posts">
								<?php $total_posts = count_user_posts( $author_id );
								if ( $total_posts > 0 ) : ?>
									<span class="h1 bio-count"><?php foxiz_render_inline_html( $total_posts ); ?></span>
									<span class="is-meta"><?php
										if ( (string) $total_posts === '1' ) {
											foxiz_html_e( 'Article', 'foxiz' );
										} else {
											foxiz_html_e( 'Articles', 'foxiz' );
										} ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</header>
	<?php }
}


if ( ! function_exists( 'foxiz_author_bio_title' ) ) {
	function foxiz_author_bio_title( $author_id ) { ?>
		<h2 class="bio-title"><?php echo get_the_author_meta( 'display_name', $author_id ); ?>
		<?php if ( foxiz_is_author_tick($author_id) ) : ?><i class="verified-tick rbi rbi-wavy"></i><?php endif; ?>
		</h2>
	<?php
	}
}

if ( ! function_exists( 'foxiz_blog_empty' ) ) {
	function foxiz_blog_empty() { ?>
		<div class="section-empty not-found">
			<div class="rb-container edge-padding">
				<div class="section-empty-inner">
					<h1 class="page-title"><?php foxiz_html_e( 'Oops! Nothing here', 'foxiz' ); ?></h1>
					<div class="page-content entry-content rbct">
						<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
							<p><?php esc_html_e( 'Ready to publish your first post?', 'foxiz' ) ?>
								<a href="<?php echo admin_url( 'post-new.php' ); ?>"><?php esc_html_e( ' Get started here', 'foxiz' ); ?></a>
							</p>
						<?php else: ?>
							<p class="page404-description"><?php foxiz_html_e( 'It seems we can’t find what you’re looking for. Perhaps searching can help.', 'foxiz' ); ?></p>
							<?php get_search_form();
							if ( ! is_front_page() ) : ?>
								<div class="page404-btn-wrap">
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page404-btn is-btn"><?php foxiz_html_e( 'Return to Home', 'foxiz' ); ?></a>
								</div>
							<?php endif;
						endif; ?>
					</div>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_search_empty' ) ) {
	function foxiz_search_empty() { ?>
		<div class="rb-container edge-padding">
			<div class="search-empty">
				<p class="h3"><?php foxiz_html_e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'foxiz' ); ?></p>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_blog_template' ) ) {
    function foxiz_blog_template( $shortcode ) {

        echo '<main id="primary" class="site-main" role="main">';
        echo '<div class="blog-builder">' . do_shortcode( trim( $shortcode ) ) . '</div>';
        echo '</main>';
    }
}

if ( ! function_exists( 'foxiz_archive_title' ) ) {
	function foxiz_archive_title() {
		if ( is_tag() && foxiz_get_option( 'follow_tag_header' ) && foxiz_get_option('bookmark_system') ) : ?>
			<div class="archive-title b-follow">
				<h1><?php echo get_the_archive_title(); ?></h1>
				<span class="rb-follow follow-trigger" data-name="<?php single_term_title(); ?>" data-cid="<?php echo get_queried_object_id(); ?>"></span>
			</div>
		<?php else : ?>
			<h1 class="archive-title"><?php echo get_the_archive_title(); ?></h1>
		<?php endif;
	}
}