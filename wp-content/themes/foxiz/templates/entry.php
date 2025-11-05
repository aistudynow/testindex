<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_post_classes' ) ) {
	function foxiz_get_post_classes( $settings ) {

		$classes = [ 'p-wrap' ];
		if ( is_sticky() ) {
			$classes[] = 'sticky';
		}
		if ( ! empty( $settings['post_classes'] ) ) {
			$classes[] = $settings['post_classes'];
		}
		if ( ( ! empty( $settings['carousel'] ) && '1' === (string) $settings['carousel'] ) || ( ! empty( $settings['slider'] ) && '1' === (string) $settings['slider'] ) ) {
			$classes[] = 'swiper-slide';
		}

		if ( has_post_format( 'video' ) && rb_get_meta( 'video_preview' ) ) {
			$classes[] = 'preview-trigger';
		}

		return join( ' ', $classes );
	}
}

if ( ! function_exists( 'foxiz_entry_title' ) ) {
	function foxiz_entry_title( $settings = [] ) {

		$post_id    = get_the_ID();
		$holder_tag = false;

		$classes = 'entry-title';
		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( ! empty( $settings['title_classes'] ) ) {
			$classes .= ' ' . $settings['title_classes'];
		}

		if ( ! empty( $settings['counter'] ) && '-1' !== (string) $settings['counter'] ) {
			$classes .= ' counter-el-' . esc_attr( $settings['counter'] );

			// adjustments in flex display to properly hover effects
			$holder_tag = in_array( $settings['counter'], [ 'inline-b', 'circle-b', 'circle-sqb' ] );
		}

		$classes    = apply_filters( 'foxiz_entry_title_classes', $classes, $post_id );
		$post_title = get_the_title();
		if ( strlen( $post_title ) === 0 ) {
			$post_title = get_the_date( '', $post_id );
		}

		$link     = get_permalink();
		$rel_attr = 'bookmark';
		if ( foxiz_is_sponsored_post() && foxiz_get_single_setting( 'sponsor_redirect' ) ) {
			$link     = rb_get_meta( 'sponsor_url' );
			$rel_attr = 'noopener nofollow';
		}
		echo '<' . strip_tags( $settings['title_tag'] ) . ' class="' . strip_tags( $classes ) . '">';
		if ( ! empty( $settings['title_prefix'] ) ) {
			foxiz_render_inline_html( $settings['title_prefix'] );
		}
		if ( $holder_tag ) {
			echo '<span>';
		}
		?>
		<a class="p-url" href="<?php echo esc_url( $link ); ?>" rel="<?php echo strip_tags( $rel_attr ); ?>"><?php
		if ( foxiz_is_live_blog( $post_id ) ) {
			echo '<span class="live-tag"></span>';
		}
		foxiz_render_inline_html( $post_title );
		?></a><?php
		if ( $holder_tag ) {
			echo '</span>';
		}
		echo '</' . strip_tags( $settings['title_tag'] ) . '>';
	}
}

if ( ! function_exists( 'foxiz_entry_readmore' ) ) {
	function foxiz_entry_readmore( $settings = [] ) {

		if ( empty( $settings['readmore'] ) ) {
			return;
		}

		$link         = get_permalink();
		$is_sponsored = false;
		if ( foxiz_is_sponsored_post() && foxiz_get_single_setting( 'sponsor_redirect' ) ) {
			$link         = rb_get_meta( 'sponsor_url' );
			$is_sponsored = true;
		}
		?>
		<div class="p-link">
			<a class="p-readmore" href="<?php echo esc_url( $link ); ?>" <?php if ( $is_sponsored ) {
				echo ' rel="noopener nofollow" ';
			} ?> aria-label="<?php echo esc_attr( get_the_title() ); ?>"><span><?php
					echo apply_filters( 'foxiz_read_more', $settings['readmore'] ); ?></span><?php if ( foxiz_get_option( 'readmore_icon' ) ) : ?>
					<i class="rbi rbi-cright" aria-hidden="true"></i><?php endif; ?>
			</a>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_excerpt' ) ) {
	function foxiz_entry_excerpt( $settings = [] ) {

		if ( ! empty( $settings['hide_excerpt_t'] ) &&
		     str_word_count( wp_strip_all_tags( get_the_title() ) ) > (int) $settings['hide_excerpt_t'] ) {
			return;
		}

		$classes = 'entry-summary';

		if ( ! empty( $settings['hide_excerpt'] ) ) {
			switch ( $settings['hide_excerpt'] ) {
				case 'mobile' :
					$classes .= ' mobile-hide';
					break;
				case 'tablet' :
					$classes .= ' tablet-hide';
					break;
				case 'all' :
					$classes .= ' mobile-hide tablet-hide';
					break;
			}
		}

		if ( ! empty( $settings['excerpt_source'] ) && 'moretag' === $settings['excerpt_source'] ) :
			$classes .= ' entry-content rbct'; ?>
			<p class="<?php echo strip_tags( $classes ); ?>"><?php the_content( '' ); ?></p>
		<?php else :
			if ( empty( $settings['excerpt_length'] ) || 0 > $settings['excerpt_length'] ) {
				return;
			}
			if ( ! empty( $settings['excerpt_source'] ) && 'tagline' === $settings['excerpt_source'] && rb_get_meta( 'tagline' ) ) :
				$tagline = wp_trim_words( rb_get_meta( 'tagline' ), intval( $settings['excerpt_length'] ), '<span class="summary-dot">&hellip;</span>' ); ?>
				<p class="<?php echo strip_tags( $classes ); ?>"><?php foxiz_render_inline_html( $tagline ); ?></p>
			<?php else :
				$excerpt = get_post_field( 'post_excerpt', get_the_ID() );
				if ( ! empty( $excerpt ) ) {
					$output = wp_trim_words( $excerpt, intval( $settings['excerpt_length'] ), '<span class="summary-dot">&hellip;</span>' );
				}
				if ( empty( $output ) ) {
					if ( 'page' === get_post_type() && get_post_meta( get_the_ID(), '_elementor_data', true ) ) {
						return;
					}
					$output = get_the_content( '' );
					$output = strip_shortcodes( $output );
					$output = excerpt_remove_blocks( $output );
					$output = preg_replace( "~(?:\[/?)[^/\]]+/?\]~s", '', $output );
					$output = str_replace( ']]>', ']]&gt;', $output );
					$output = wp_strip_all_tags( $output );
					$output = wp_trim_words( $output, intval( $settings['excerpt_length'] ), '<span class="summary-dot">&hellip;</span>' );
				}
				if ( empty( $output ) ) {
					return;
				}
				?><p class="<?php echo strip_tags( $classes ); ?>"><?php foxiz_render_inline_html( $output ); ?></p>
			<?php endif;
		endif;
	}
}

if ( ! function_exists( 'foxiz_get_entry_meta' ) ) {
	function foxiz_get_entry_meta( $settings = [] ) {

		if ( empty( $settings['entry_meta'] ) || ! is_array( $settings['entry_meta'] ) || ! array_filter( $settings['entry_meta'] ) ) {
			return false;
		}

		if ( $settings['entry_meta'][0] === '_disabled' ) {
			return false;
		}

		ob_start();
		foreach ( $settings['entry_meta'] as $key ) {
			switch ( $key ) {
				case 'avatar' :
					foxiz_entry_meta_avatar( $settings );
					break;
				case 'date' :
					foxiz_entry_meta_date( $settings );
					break;
				case 'author' :
					if ( ! empty( $settings['yes_single'] ) ) {
						foxiz_entry_meta_author_single( $settings );
					} else {
						foxiz_entry_meta_author( $settings );
					}
					break;
				case 'category' :
					foxiz_entry_meta_category( $settings );
					break;
				case 'tag' :
					foxiz_entry_meta_tag( $settings );
					break;
				case 'comment' :
					foxiz_entry_meta_comment( $settings );
					break;
				case 'view' :
					foxiz_entry_meta_view( $settings );
					break;
				case 'update' :
					foxiz_entry_meta_updated( $settings );
					break;
				case 'read' :
					foxiz_entry_meta_read_time( $settings );
					break;
				case 'custom' :
					foxiz_entry_meta_user_custom( $settings );
					break;
				case 'play' :
					foxiz_entry_meta_play( $settings );
					break;
				case 'duration' :
					foxiz_entry_meta_duration( $settings );
					break;
				case 'index' :
					foxiz_entry_meta_index( $settings );
					break;
				case 'bookmark' :
					foxiz_entry_meta_bookmark( $settings );
					break;
				case 'like' :
					foxiz_entry_meta_like( $settings );
					break;
				default :
					foxiz_entry_meta_flex( $settings, $key );
			}
		}

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_entry_meta' ) ) {
	function foxiz_entry_meta( $settings ) {

		if ( 'product' === get_post_type() && function_exists( 'woocommerce_template_loop_price' ) ) {
			woocommerce_template_loop_price();

			return;
		}

		if ( foxiz_is_sponsored_post() && ! empty( $settings['sponsor_meta'] ) ) {
			echo foxiz_get_entry_sponsored( $settings );

			return;
		}

		if ( ! empty( $settings['review'] ) && ( 'replace' === $settings['review'] ) && ! empty( foxiz_get_entry_review( $settings ) ) ) {
			echo foxiz_get_entry_review( $settings );

			return;
		}

		$settings = foxiz_extra_meta_labels( $settings );

		if ( foxiz_get_entry_meta( $settings ) ) {
			$class_name   = [];
			$class_name[] = 'p-meta';
			if ( ! empty( $settings['entry_meta']['avatar'] ) ) {
				$class_name[] = 'has-avatar';
			}
			if ( ! empty( $settings['bookmark'] ) ) {
				$class_name[] = 'has-bookmark';
			} ?>
			<div class="<?php echo join( ' ', $class_name ); ?>">
				<div class="meta-inner is-meta">
					<?php echo foxiz_get_entry_meta( $settings ); ?>
				</div>
				<?php if ( ! empty( $settings['bookmark'] ) ) {
					foxiz_bookmark_trigger( get_the_ID() );
				} ?>
			</div>
		<?php }
	}
}

if ( ! function_exists( 'foxiz_entry_meta_date' ) ) {
	function foxiz_entry_meta_date( $settings ) {

		$post_id = get_the_ID();
		$p_label = '';
		$s_label = '';
		$classes = [];
		$format  = ! empty( $settings['date_format'] ) ? trim( $settings['date_format'] ) : '';

		if ( ! isset( $settings['human_time'] ) ) {
			$settings['human_time'] = foxiz_get_option( 'human_time' );
		}
		if ( ! empty( $settings['p_label_date'] ) ) {
			$p_label = $settings['p_label_date'];
		} elseif ( ! empty( $settings['has_date_label'] ) && empty( $settings['human_time'] ) ) {
			$p_label = foxiz_html__( 'Published:', 'foxiz' ) . ' ';
		}

		if ( ! empty( $settings['s_label_date'] ) ) {
			$s_label = $settings['s_label_date'];
		}

		if ( ! empty( $settings['human_time'] ) ) {
			$date_string = sprintf( foxiz_html__( '%s ago', 'foxiz' ), human_time_diff( get_post_time( 'U', true, $post_id ) ) );
		} else {
			$date_string = get_the_date( $format, $post_id );
		}

		$classes[] = 'meta-el meta-date';
		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'date', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'date', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'date' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'date' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
	<div class="<?php echo join( ' ', $classes ); ?>">
		<?php if ( foxiz_get_option( 'meta_date_icon' ) ) {
			echo '<i class="rbi rbi-clock" aria-hidden="true"></i>';
		}
		?>
		<time <?php if ( ! foxiz_get_option( 'force_modified_date' ) ) {
			echo 'class="date published"';
		} ?> datetime="<?php echo get_the_date( DATE_W3C, $post_id ); ?>"><?php foxiz_render_inline_html( $p_label . $date_string . $s_label ); ?></time>
		</div><?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_author' ) ) {
	/**
	 * @param array $settings
	 */
	function foxiz_entry_meta_author( $settings = [] ) {

		$post_id = get_the_ID();

		/** multiple authors supported */
		if ( function_exists( 'get_post_authors' ) ) {
			$author_data = get_post_authors( $post_id );
			if ( is_array( $author_data ) && count( $author_data ) >= 1 ) {
				foxiz_entry_meta_authors( $settings, $author_data );

				return;
			}
		}

		/** single author */
		$author_id = get_post_field( 'post_author', $post_id );

		$classes = [ 'meta-el meta-author' ];

		$p_label = '';
		$s_label = ! empty( $settings['s_label_author'] ) ? $settings['s_label_author'] : '';

		if ( ! empty( $settings['p_label_author'] ) ) {
			$p_label = $settings['p_label_author'];
		} else {
			if ( ! isset( $settings['meta_label_by'] ) ) {
				$settings['meta_label_by'] = foxiz_get_option( 'meta_author_label' );
			}
			if ( ! empty( $settings['meta_label_by'] ) ) {
				$p_label = foxiz_html__( 'By', 'foxiz' );
			}
		}

		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'author', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'author', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'author' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'author' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php if ( $p_label ): ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $p_label ); ?></span>
			<?php endif;
			echo '<a href="' . get_author_posts_url( $author_id ) . '">' . get_the_author_meta( 'display_name', $author_id ) . '</a>';
			if ( $s_label ) : ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $s_label ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_authors' ) ) {
	/**
	 * @param       $settings
	 * @param array $author_data
	 */
	function foxiz_entry_meta_authors( $settings, $author_data = [] ) {

		if ( ! is_array( $author_data ) ) {
			return;
		}

		$classes = [];
		$p_label = '';
		$s_label = ! empty( $settings['s_label_author'] ) ? $settings['s_label_author'] : '';

		if ( ! empty( $settings['p_label_author'] ) ) {
			$p_label = $settings['p_label_author'];
		} else {
			if ( ! isset( $settings['meta_label_by'] ) ) {
				$settings['meta_label_by'] = foxiz_get_option( 'meta_author_label' );
			}
			if ( ! empty( $settings['meta_label_by'] ) ) {
				$p_label = foxiz_html__( 'By', 'foxiz' );
			}
		}

		$classes[] = 'meta-el meta-author co-authors';

		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'author', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'author', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'author' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'author' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		if ( $s_label ) {
			$classes[] = 'has-suffix';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php if ( $p_label ): ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $p_label ); ?></span>
			<?php endif;
			foreach ( $author_data as $author ) : ?>
				<div class="meta-separate">
					<a href="<?php echo get_author_posts_url( $author->ID ) ?>"><?php the_author_meta( 'display_name', $author->ID ); ?></a>
				</div>
			<?php endforeach;
			if ( $s_label ) : ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $s_label ); ?></span>
			<?php endif; ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_entry_meta_avatar' ) ) {
	function foxiz_entry_meta_avatar( $settings ) {

		$post_id = get_the_ID();
		$classes = [];
		$lazy    = empty( $settings['feat_lazyload'] ) || 'none' !== $settings['feat_lazyload'];

		if ( empty( $settings['avatar_size'] ) ) {
			$settings['avatar_size'] = 44;
		}
		$classes[] = 'meta-el meta-avatar';
		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'avatar', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'avatar', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( function_exists( 'get_post_authors' ) ) {
			$author_data = get_post_authors( $post_id );
			if ( is_array( $author_data ) && count( $author_data ) >= 1 ) {
				$classes[] = 'meta-el multiple-avatar';
				?>
				<div class="<?php echo join( ' ', $classes ); ?>">
					<?php foreach ( $author_data as $author ) {
						$auth_id         = $author->ID;
						$author_image_id = (int) get_the_author_meta( 'author_image_id', $auth_id );
						if ( $author_image_id !== 0 ) {
							echo foxiz_get_avatar_by_attachment( $author_image_id, 'thumbnail', $lazy );
						} else {
							echo get_avatar( $auth_id, absint( $settings['avatar_size'] ), '', get_the_author_meta( 'display_name', $auth_id ) );
						}
					} ?>
				</div>
				<?php return;
			}
		}
		$author_id   = get_post_field( 'post_author', $post_id );
		$author_name = get_the_author_meta( 'display_name', $author_id );
		?>
		<a class="<?php echo join( ' ', $classes ); ?>" href="<?php echo get_author_posts_url( $author_id ); ?>" rel="nofollow" aria-label="<?php echo sprintf( foxiz_html__( 'Visit posts by %s', 'foxiz' ), $author_name ); ?>"><?php
			$author_image_id = (int) get_the_author_meta( 'author_image_id', $author_id );
			if ( $author_image_id !== 0 ) {
				echo foxiz_get_avatar_by_attachment( $author_image_id, 'thumbnail', $lazy );
			} else {
				echo get_avatar( $author_id, absint( $settings['avatar_size'] ), '', $author_name );
			}
			?></a>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_category' ) ) {
	function foxiz_entry_meta_category( $settings, $primary = true ) {

		$taxonomy = 'category';
		if ( 'podcast' === get_post_type() ) {
			$taxonomy = 'series';
		}

		if ( ! empty( $settings['taxonomy'] ) && ! empty( $settings['post_type'] ) && 'post' !== $settings['post_type'] ) {
			$taxonomy = $settings['taxonomy'];
		}

		$categories = get_the_terms( get_the_ID(), $taxonomy );

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return;
		}

		$classes = [ 'meta-el meta-tax meta-bold' ];
		$limit   = absint( foxiz_get_option( 'max_entry_meta', 999 ) );
		$index   = 1;

		if ( $primary && 'category' === $taxonomy ) {
			$primary_category = rb_get_meta( 'primary_category' );
		}
		$s_label = ! empty( $settings['s_label_category'] ) ? $settings['s_label_category'] : '';

		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'category', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'category', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'category' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'category' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		if ( $s_label ) {
			$classes[] = 'has-suffix';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php
			if ( foxiz_get_option( 'meta_category_icon', false ) ) {
				echo '<i class="rbi rbi-archive" aria-hidden="true"></i>';
			}
			if ( ! empty( $settings['p_label_category'] ) ) : ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $settings['p_label_category'] ); ?></span>
			<?php endif;
			if ( ! empty( $primary_category ) ) : ?>
				<a class="category-<?php echo strip_tags( $primary_category ); ?>" href="<?php echo foxiz_get_term_link( $primary_category ); ?>"><?php echo get_cat_name( $primary_category ); ?></a>
			<?php else :
				foreach ( $categories as $category ) : ?>
					<a class="meta-separate term-i-<?php echo strip_tags( $category->term_id ); ?>" href="<?php echo foxiz_get_term_link( $category->term_id ); ?>"><?php foxiz_render_inline_html( $category->name ); ?></a>
					<?php
					if ( $index >= $limit ) {
						break;
					}
					$index ++;
				endforeach;
			endif;
			if ( $s_label ) : ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $s_label ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_tag' ) ) {
	function foxiz_entry_meta_tag( $settings ) {

		$tags = get_the_tags( get_the_ID() );
		if ( empty( $tags ) || is_wp_error( $tags ) ) {
			return;
		}

		$default_label = foxiz_get_option( 'meta_tag_label' ) ? foxiz_html__( 'Tags:', 'foxiz' ) . ' ' : '';

		$limit   = absint( foxiz_get_option( 'max_entry_meta', 999 ) );
		$index   = 1;
		$p_label = isset( $settings['p_label_tag'] ) ? $settings['p_label_tag'] : $default_label;
		$s_label = isset( $settings['s_label_tag'] ) ? $settings['s_label_tag'] : '';

		$classes = [ 'meta-el meta-tax' ];

		if ( foxiz_get_option( 'meta_tag_important' ) ) {
			$classes[] = 'meta-bold';
		}
		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'tag', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'tag', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'tag' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'tag' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		if ( $s_label ) {
			$classes[] = 'has-suffix';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php if ( ! empty( $p_label ) ): ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $p_label ); ?></span>
			<?php endif;
			foreach ( $tags as $tag ) : ?>
				<a class="meta-separate term-i-<?php echo strip_tags( $tag->term_id ); ?>" href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" rel="tag"><?php echo strip_tags( $tag->name ); ?></a>
				<?php
				if ( $index >= $limit ) {
					break;
				}
				$index ++;
			endforeach;
			if ( ! empty( $s_label ) ) : ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $s_label ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_comment' ) ) {
	function foxiz_entry_meta_comment( $settings ) {

		$post_id = get_the_ID();

		if ( ! comments_open( $post_id ) ) {
			return;
		}

		// Compatible with Disqus plugin
		$comment_string = get_comments_number_text();

		$p_label = ! empty( $settings['p_label_comment'] ) ? $settings['p_label_comment'] : '';
		$s_label = ! empty( $settings['s_label_comment'] ) ? $settings['s_label_comment'] : '';

		$classes   = [];
		$classes[] = 'meta-el meta-comment';

		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'comment', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'comment', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'comment' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'comment' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php if ( foxiz_get_option( 'meta_comment_icon' ) ) : ?>
				<i class="rbi rbi-comment" aria-hidden="true"></i>
			<?php endif; ?>
			<a href="<?php echo get_comments_link( $post_id ); ?>"><?php foxiz_render_inline_html( $p_label . $comment_string . $s_label ); ?></a>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_view' ) ) {
	function foxiz_entry_meta_view( $settings ) {

		if ( ! function_exists( 'pvc_get_post_views' ) || ! class_exists( 'Post_Views_Counter' ) ) {
			return;
		}

		$post_id = get_the_ID();
		$classes = [];

		$count     = pvc_get_post_views( $post_id );
		$fake_view = rb_get_meta( 'start_view', $post_id );
		if ( ! empty( $fake_view ) ) {
			$count = intval( $count ) + intval( $fake_view );
		}
		if ( empty( $count ) ) {
			return;
		}

		$p_label = ! empty( $settings['p_label_view'] ) ? $settings['p_label_view'] : '';
		$s_label = ! empty( $settings['s_label_view'] ) ? $settings['s_label_view'] : '';

		if ( foxiz_get_option( 'meta_view_pretty_number' ) ) {
			$count = foxiz_pretty_number( $count );
		}

		if ( $p_label || $s_label ) {
			$view_string = $count;
		} else {
			if ( '1' === (string) $count ) {
				$view_string = foxiz_html__( '1 View', 'foxiz' );
			} else {
				$view_string = sprintf( foxiz_html__( '%s Views' ), $count );
			}
		}

		$classes[] = 'meta-el meta-view';
		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'view', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'view', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'view' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'view' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php
			if ( foxiz_get_option( 'meta_view_icon' ) ) {
				echo '<i class="rbi rbi-chart" aria-hidden="true"></i>';
			}
			foxiz_render_inline_html( $p_label . $view_string . $s_label );
			?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_updated' ) ) {
	function foxiz_entry_meta_updated( $settings ) {

		$post_id = get_the_ID();
		$p_label = '';
		$s_label = '';
		$classes = [ 'meta-el meta-update' ];
		$format  = ! empty( $settings['date_format'] ) ? trim( $settings['date_format'] ) : '';

		if ( ! isset( $settings['human_time'] ) && empty( $format ) ) {
			$settings['human_time'] = foxiz_get_option( 'human_time' );
		}
		if ( ! empty( $settings['p_label_update'] ) ) {
			$p_label = $settings['p_label_update'];
		} elseif ( ! empty( $settings['has_date_label'] ) && empty( $settings['human_time'] ) ) {
			$p_label = foxiz_html__( 'Last updated:', 'foxiz' ) . ' ';
		}
		if ( ! empty( $settings['s_label_date'] ) ) {
			$s_label = $settings['s_label_date'];
		}
		if ( ! empty( $settings['human_time'] ) ) {
			$date_string = sprintf( foxiz_html__( '%s ago', 'foxiz' ), human_time_diff( get_post_modified_time( 'U', true, $post_id ) ) );
			$classes[]   = 'human-format';
		} else {
			$date_string = get_the_modified_date( $format, $post_id );
		}
		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'update', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'update', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'update' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'update' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php if ( foxiz_get_option( 'meta_updated_icon' ) ) {
				echo '<i class="rbi rbi-time" aria-hidden="true"></i>';
			} ?>
			<time <?php if ( ! foxiz_get_option( 'force_modified_date' ) ) {
				echo 'class="updated"';
			} ?> datetime="<?php echo get_the_modified_date( DATE_W3C, $post_id ); ?>"><?php foxiz_render_inline_html( $p_label . $date_string . $s_label ); ?></time>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_read_time' ) ) {
	function foxiz_entry_meta_read_time( $settings ) {

		$post_id    = get_the_ID();
		$classes    = [];
		$count      = get_post_meta( $post_id, 'foxiz_content_total_word', true );
		$read_speed = intval( foxiz_get_option( 'read_speed' ) );

		if ( empty( $count ) ) {
			$count = foxiz_update_word_count( $post_id );
		}
		if ( $count < 1 ) {
			return;
		}

		$count = absint( $count );
		if ( empty( $read_speed ) ) {
			$read_speed = 130;
		}
		$minutes = floor( $count / $read_speed );
		$second  = floor( ( $count / $read_speed ) * 60 ) % 60;
		if ( $second > 30 ) {
			$minutes ++;
		}

		$p_label = ! empty( $settings['p_label_read'] ) ? $settings['p_label_read'] : '';
		$s_label = ! empty( $settings['s_label_read'] ) ? $settings['s_label_read'] : '';

		if ( $p_label || $s_label ) {
			$read_string = $minutes;
		} else {
			$read_string = sprintf( foxiz_html__( '%s Min Read', 'foxiz' ), $minutes );
		}

		$classes[] = 'meta-el meta-read';
		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'read', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'read', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'read' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'read' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>"><?php if ( foxiz_get_option( 'meta_read_icon', false ) ) {
				echo '<i class="rbi rbi-watch" aria-hidden="true"></i>';
			}
			foxiz_render_inline_html( $p_label . $read_string . $s_label ) ?></div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_user_custom' ) ) {
	function foxiz_entry_meta_user_custom( $settings ) {

		$post_id = get_the_ID();
		$classes = [];

		$custom_info = rb_get_meta( 'meta_custom', $post_id );

		if ( empty( $custom_info ) ) {
			foxiz_entry_meta_user_custom_fallback( $settings );

			return;
		}

		$meta_custom_icon = foxiz_get_option( 'meta_custom_icon' );
		$meta_custom_text = foxiz_get_option( 'meta_custom_text' );
		$meta_custom_pos  = foxiz_get_option( 'meta_custom_pos' );

		$p_label = ! empty( $settings['p_label_custom'] ) ? $settings['p_label_custom'] : '';
		$s_label = ! empty( $settings['s_label_custom'] ) ? $settings['s_label_custom'] : '';

		$custom_string = $custom_info;
		if ( empty( $p_label ) && empty( $s_label ) ) {
			$custom_string = $custom_info . ' ' . $meta_custom_text;
			if ( ! empty( $meta_custom_pos ) && 'begin' === $meta_custom_pos ) {
				$custom_string = $meta_custom_text . ' ' . $custom_info;
			}
		}

		$classes[] = 'meta-el meta-custom';
		if ( foxiz_get_option( 'meta_custom_important' ) ) {
			$classes[] = 'meta-bold';
		}
		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'custom', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'custom', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'custom' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'custom' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php if ( ! empty( $meta_custom_icon ) ) : ?>
				<i class="<?php echo strip_tags( $meta_custom_icon ); ?>" aria-hidden="true"></i>
			<?php endif;
			foxiz_render_inline_html( $p_label . $custom_string . $s_label ); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_duration' ) ) {
	function foxiz_entry_meta_duration( $settings ) {

		$post_id  = get_the_ID();
		$classes  = [];
		$duration = rb_get_meta( 'duration', $post_id );

		if ( empty( $duration ) ) {
			return;
		}

		$p_label = ! empty( $settings['p_label_duration'] ) ? $settings['p_label_duration'] : '';
		$s_label = ! empty( $settings['s_label_duration'] ) ? $settings['s_label_duration'] : '';

		if ( empty( $p_label ) && ! empty( $settings['has_duration_label'] ) ) {
			$p_label = esc_html__( 'Duration:', 'foxiz' ) . ' ';
		}

		$duration_string = ltrim( $duration, '00:' );
		$classes[]       = 'meta-el meta-duration';
		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'duration', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'duration', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'duration' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'duration' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>"><?php foxiz_render_inline_html( $p_label . $duration_string . $s_label ); ?></div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_index' ) ) {
	function foxiz_entry_meta_index( $settings ) {

		$post_id    = get_the_ID();
		$classes    = [];
		$post_index = rb_get_meta( 'post_index', $post_id );
		if ( empty( $post_index ) ) {
			return;
		}
		$p_label = ! empty( $settings['p_label_index'] ) ? $settings['p_label_index'] : '';
		$s_label = ! empty( $settings['s_label_index'] ) ? $settings['s_label_index'] : '';

		$classes[] = 'meta-el meta-index meta-bold';
		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'index', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'index', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'index' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'index' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>"><?php foxiz_render_inline_html( $p_label . $post_index . $s_label ); ?></div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_bookmark' ) ) {
	function foxiz_entry_meta_bookmark( $settings ) {

		if ( foxiz_is_amp() || ! foxiz_get_option( 'bookmark_system' ) ) {
			return;
		}

		$classes = [ 'meta-el meta-bookmark' ];

		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'bookmark', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'bookmark', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'bookmark' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'bookmark' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>"><?php foxiz_bookmark_trigger( get_the_ID() ); ?></div>
	<?php }
}

if ( ! function_exists( 'foxiz_entry_meta_play' ) ) {
	function foxiz_entry_meta_play( $settings ) {

		if ( function_exists( 'foxiz_podcast_entry_meta_play' ) ) {
			foxiz_podcast_entry_meta_play( $settings );
		}
	}
}


if ( ! function_exists( 'foxiz_entry_meta_user_custom_fallback' ) ) {
	function foxiz_entry_meta_user_custom_fallback( $settings ) {

		$meta = foxiz_get_option( 'meta_custom_fallback' );

		if ( ! $meta ) {
			return;
		}

		switch ( $meta ) {
			case 'date' :
				foxiz_entry_meta_date( $settings );
				break;
			case 'author' :
				foxiz_entry_meta_author( $settings );
				break;
			case 'category' :
				foxiz_entry_meta_category( $settings );
				break;
			case 'tag' :
				foxiz_entry_meta_tag( $settings );
				break;
			case 'comment' :
				foxiz_entry_meta_comment( $settings );
				break;
			case 'view' :
				foxiz_entry_meta_view( $settings );
				break;
			case 'update' :
				foxiz_entry_meta_updated( $settings );
				break;
			case 'read' :
				foxiz_entry_meta_read_time( $settings );
				break;
		};
	}
}

if ( ! function_exists( 'foxiz_entry_featured_image' ) ) {
	function foxiz_entry_featured_image( $settings = [] ) {

		$attrs = [ 'class' => 'featured-img' ];

		$is_lazy = foxiz_get_option( 'lazy_load' );

		if ( ! empty( $settings['feat_lazyload'] ) ) {
			$is_lazy = ( 'none' === $settings['feat_lazyload'] ) ? false : true;
		}

		if ( ! $is_lazy ) {
			$attrs['fetchpriority'] = 'high';
			$attrs['loading']       = 'eager';
		} else {
			$attrs['loading'] = 'lazy';
		}
		$url = get_permalink();
		if ( foxiz_is_sponsored_post() && foxiz_get_single_setting( 'sponsor_redirect' ) && rb_get_meta( 'sponsor_url' ) ) {
			$url = rb_get_meta( 'sponsor_url' );
		} ?>
		<a class="p-flink" href="<?php echo esc_url( $url ); ?>" title="<?php echo wp_strip_all_tags( get_the_title() ); ?>">
			<?php the_post_thumbnail( $settings['crop_size'], $attrs ); ?>
		</a>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_featured' ) ) {
	function foxiz_entry_featured( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
				'featured_classes' => '',
				'crop_size'        => '1536x1536',
				'format'           => '',
		] );

		$classes   = [];
		$classes[] = 'p-featured';
		if ( ! empty( $settings['featured_classes'] ) ) {
			$classes[] = $settings['featured_classes'];
		}
		if ( has_post_format( 'video' ) ) {
			$video_preview = wp_get_attachment_url( rb_get_meta( 'video_preview' ) );
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php
			foxiz_entry_featured_image( $settings );
			foxiz_entry_format_absolute( $settings );
			if ( empty( $settings['none_featured_extra'] ) ) {
				do_action( 'foxiz_featured_image' );
			}
			if ( current_user_can( 'edit_posts' ) ) {
				if ( ! isset( $settings['edit_link'] ) ) {
					$settings['edit_link'] = foxiz_get_option( 'edit_link' );
				}
				if ( ! empty( $settings['edit_link'] ) ) {
					edit_post_link( esc_html__( 'edit', 'foxiz' ) );
				}
			}
			if ( ! empty( $video_preview ) )  : ?>
				<div class="preview-video" data-source="<?php echo esc_url( $video_preview ); ?>" data-type="<?php echo foxiz_get_video_mine_type( $video_preview ); ?>"></div>
			<?php endif;
			?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_featured_only' ) ) {
	function foxiz_featured_only( $settings = [] ) {

		if ( ! empty( $settings['crop_size'] ) && foxiz_has_featured_image( $settings['crop_size'] ) ) : ?>
			<div class="feat-holder"><?php foxiz_entry_featured( $settings ); ?></div>
		<?php endif;
	}
}

if ( ! function_exists( 'foxiz_featured_with_category' ) ) {
	function foxiz_featured_with_category( $settings = [] ) {

		if ( ! empty( $settings['crop_size'] ) && foxiz_has_featured_image( $settings['crop_size'] ) ) : ?>
			<div class="feat-holder">
				<?php
				foxiz_entry_featured( $settings );

				$settings['top_classes'] = 'light-scheme';
				foxiz_entry_top( $settings );
				?></div>
		<?php endif;
	}
}

if ( ! function_exists( 'foxiz_get_entry_format' ) ) {
	function foxiz_get_entry_format( $settings ) {

		$classes   = [];
		$classes[] = 'p-format';

		switch ( get_post_format() ) {
			case 'video' :
				if ( ! foxiz_get_option( 'post_icon_video' ) ) {
					return false;
				}
				$classes[] = 'format-video';

				return '<span class="' . join( ' ', $classes ) . '"><i class="rbi rbi-video" aria-hidden="true"></i></span>';

			case 'gallery' :
				if ( ! foxiz_get_option( 'post_icon_gallery' ) ) {
					return false;
				}
				$gallery   = rb_get_meta( 'gallery_data' );
				$gallery   = explode( ',', $gallery );
				$classes[] = 'format-gallery';

				return '<span class="' . join( ' ', $classes ) . '"><i class="rbi rbi-gallery" aria-hidden="true"></i><span class="gallery-count meta-text">' . count( $gallery ) . '</span></span>';
			case 'audio' :
				if ( ! foxiz_get_option( 'post_icon_audio' ) ) {
					return false;
				}
				$classes[] = 'format-radio';

				return '<span class="' . join( ' ', $classes ) . '"><i class="rbi rbi-audio" aria-hidden="true"></i></span>';
			default:
				return false;
		}
	}
}

if ( ! function_exists( 'foxiz_get_entry_categories' ) ) {
	function foxiz_get_entry_categories( $settings ) {

		if ( empty( $settings['entry_category'] ) ) {
			return false;
		}

		$output   = '';
		$rel      = '';
		$classes  = [];
		$taxonomy = 'category';

		$max_category_key = 'max_categories';
		$max_tag_key      = 'max_post_tags';

		if ( ! empty( $settings['is_singular'] ) ) {
			$max_category_key = 'single_max_categories';
			$max_tag_key      = 'single_max_categories';
		}

		if ( empty( $settings['entry_tax'] ) ) {
			if ( ! empty( $settings['taxonomy'] ) ) {
				$taxonomy = $settings['taxonomy'];
			} elseif ( 'podcast' === get_post_type() ) {
				$taxonomy = 'series';
			}
		} else {
			$taxonomy = $settings['entry_tax'];
		}

		$categories   = get_the_terms( get_the_ID(), $taxonomy );
		$primary_id   = '';
		$primary_name = '';

		if ( ! isset( $settings['only_primary'] ) ) {

			if ( 'category' === $taxonomy ) {
				$primary_id   = rb_get_meta( 'primary_category' );
				$primary_name = get_cat_name( $primary_id );
			} elseif ( 'post_tag' == $taxonomy ) {
				$primary_id = rb_get_meta( 'primary_tag' );

				if ( is_numeric( $primary_id ) ) {
					$tag = get_term( $primary_id, 'post_tag' );
				} else {
					$tag = get_term_by( 'name', $primary_id, 'post_tag' );
				}

				if ( ! empty( $tag ) && ! is_wp_error( $tag ) ) {
					$primary_name = $tag->name;
					$primary_id   = $tag->term_id;
				} else {
					$primary_name = '';
				}
			}
		}

		if ( 'post_tag' === $taxonomy ) {
			$max = absint( foxiz_get_option( $max_tag_key ) );
		} else {
			$max = absint( foxiz_get_option( $max_category_key ) );
		}

		$max   = empty( $max ) ? 99999 : $max;
		$index = 1;

		$classes[] = 'p-categories';
		if ( ! empty( $primary_name ) ) {
			$classes[] = 'is-primary';
		}
		if ( ! empty( $settings['category_classes'] ) ) {
			$classes[] = $settings['category_classes'];
		}

		if ( 'category' === $taxonomy ) {
			$rel = 'rel="category"';
		}

		if ( empty( $primary_id ) || empty ( $primary_name ) ) :
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
				foreach ( $categories as $category ) :
					$output .= '<a class="p-category category-id-' . strip_tags( $category->term_id ) . '" href="' . foxiz_get_term_link( $category->term_id ) . '" ' . $rel . '>';
					$output .= strip_tags( $category->name );
					$output .= '</a>';

					$index ++;
					if ( $index > $max ) {
						break;
					}
				endforeach;
			endif;
		else :
			$output .= '<a class="p-category category-id-' . strip_tags( $primary_id ) . '" href="' . foxiz_get_term_link( $primary_id ) . '" ' . $rel . '>';
			$output .= strip_tags( $primary_name );
			$output .= '</a>';
		endif;

		if ( ! empty( $output ) ) {
			return '<div class="' . join( ' ', $classes ) . '">' . $output . '</div>';
		}

		return false;
	}
}

if ( ! function_exists( 'foxiz_entry_format_absolute' ) ) {
	function foxiz_entry_format_absolute( $settings = [] ) {

		if ( empty( $settings['entry_format'] ) || 'after-category' === $settings['entry_format'] ) {
			return;
		}

		$layout    = explode( ',', $settings['entry_format'] );
		$classes   = [];
		$classes[] = 'p-format-overlay format-style-' . $layout[0];
		if ( ! empty( $layout[1] ) ) {
			$classes[] = 'format-size-' . $layout[1];
		}
		if ( foxiz_get_entry_format( $settings ) ) {
			echo '<div class="' . join( ' ', $classes ) . '">' . foxiz_get_entry_format( $settings ) . '</div>';
		}
	}
}

if ( ! function_exists( 'foxiz_get_entry_top' ) ) {
	function foxiz_get_entry_top( $settings = [] ) {

		$format_buffer   = '';
		$category_buffer = '';

		if ( ! empty( $settings['entry_format'] ) && 'after-category' === $settings['entry_format'] ) {
			$format_buffer = foxiz_get_entry_format( $settings );
		}

		$settings['top_classes'] = empty( $settings['top_classes'] ) ? 'p-top' : $settings['top_classes'] . ' p-top';

		if ( ! empty( $settings['hide_category'] ) ) {
			switch ( $settings['hide_category'] ) {
				case 'mobile' :
					$settings['top_classes'] .= ' mobile-hide';
					break;
				case 'tablet' :
					$settings['top_classes'] .= ' tablet-hide';
					break;
				case 'all' :
					$settings['top_classes'] .= ' mobile-hide tablet-hide';
					break;
			}
		}

		if ( empty ( $format_buffer ) ) {
			$settings['category_classes'] = $settings['top_classes'];
		}

		if ( ! empty( $settings['entry_category'] ) ) {
			$category_buffer = foxiz_get_entry_categories( $settings );
		}

		if ( empty( $format_buffer ) && empty( $category_buffer ) ) {
			return false;
		}

		if ( ! empty ( $format_buffer ) ) {
			$output = '<div class="' . strip_tags( $settings['top_classes'] ) . '">';
			$output .= $category_buffer;
			$output .= '<aside class="p-format-inline">' . $format_buffer . '</aside>';
			$output .= '</div>';

			return $output;
		}

		return $category_buffer;
	}
}

if ( ! function_exists( 'foxiz_entry_top' ) ) {
	function foxiz_entry_top( $settings = [] ) {

		echo foxiz_get_entry_top( $settings );
	}
}

if ( ! function_exists( 'foxiz_entry_divider' ) ) {
	function foxiz_entry_divider( $settings = [] ) {

		if ( empty( $settings['divider_style'] ) ) {
			$settings['divider_style'] = 'solid';
		}

		$class_name = 'p-divider is-divider-' . $settings['divider_style'];
		if ( ! empty( $settings['hide_divider'] ) ) {
			switch ( $settings['hide_divider'] ) {
				case 'mobile' :
					$class_name .= ' mobile-hide';
					break;
				case 'tablet' :
					$class_name .= ' tablet-hide';
					break;
				case 'all' :
					$class_name .= ' mobile-hide tablet-hide';
					break;
			}
		}

		echo '<div class="' . strip_tags( $class_name ) . '"></div>';
	}
}

if ( ! function_exists( 'foxiz_entry_review' ) ) {
	function foxiz_entry_review( $settings ) {

		if ( empty( $settings['review'] ) || 'replace' === $settings['review'] ) {
			return;
		}
		echo foxiz_get_entry_review( $settings );
	}
}

if ( ! function_exists( 'foxiz_get_entry_review' ) ) {
	function foxiz_get_entry_review( $settings ) {

		$review = foxiz_get_review_settings();

		if ( ! is_array( $review ) ) {
			$review_medata = get_post_meta( get_the_ID(), 'foxiz_block_review_metadata', true );

			if ( empty( $review_medata ) ) {
				return false;
			}

			/** set review data */
			$review = $review_medata;
		}

		if ( empty( $review['average'] ) ) {
			$review['average'] = 0;
		}

		if ( empty( $settings['review_meta'] ) ) {
			$settings['review_meta'] = '0';
		}

		$class_name = 'review-meta is-meta is-rstyle-' . trim( $settings['review_meta'] );
		if ( ! empty( $review['type'] ) && 'score' === $review['type'] ) {
			$class_name .= ' type-score';
		} else {
			$class_name .= ' type-star';
		}

		if ( 4 == $settings['review_meta'] || 5 == $settings['review_meta'] ) {
			unset( $review['meta'] );
		}

		if ( 'replace' === $settings['review'] && ! empty( $settings['bookmark'] ) ) {
			$class_name .= ' has-bookmark';
		}

		if ( ! empty( $review['type'] ) && 'score' === $review['type'] ) {
			$buffer_1 = foxiz_get_review_line( $review['average'] );
			$buffer_2 = $review['average'] . '</strong> ' . foxiz_html__( 'out of 10', 'foxiz' );
		} else {
			$buffer_1 = foxiz_get_review_stars( $review['average'] );
			$buffer_2 = $review['average'] . '</strong> ' . foxiz_html__( 'out of 5', 'foxiz ' );
		}

		$output = '<div class="' . strip_tags( $class_name ) . '">';
		$output .= '<div class="review-meta-inner">' . $buffer_1;
		if ( ! empty( $settings['review_meta'] ) && '-1' !== $settings['review_meta'] ) {
			$output .= '<div class="review-extra">';
			$output .= '<span class="review-description"><strong class="meta-bold">' . $buffer_2 . '</span>';
			if ( ! empty( $review['meta'] ) ) {
				$output .= '<span class="extra-meta meta-bold">' . foxiz_strip_tags( $review['meta'] ) . '</span>';
			}
			$output .= '</div>';
		}
		$output .= '</div>';
		if ( 'replace' === $settings['review'] && ! empty( $settings['bookmark'] ) ) {
			$output .= foxiz_get_bookmark_trigger( get_the_ID() );
		}
		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_review_line' ) ) {
	function foxiz_get_review_line( $average = 0 ) {

		$output = '<span class="rline-wrap">';
		for ( $i = 1; $i <= 5; $i ++ ) {
			if ( ceil( floatval( $average ) / 2 ) >= $i ) {
				$output .= '<span class="rline activated"></span>';
			} else {
				$output .= '<span class="rline"></span>';
			}
		}
		$output .= '</span>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_review_stars' ) ) {
	function foxiz_get_review_stars( $average = 0 ) {

		$output = '<span class="rstar-wrap">';
		$output .= '<span class="rstar-bg" style="width:' . floatval( $average ) * 100 / 5 . '%"></span>';
		for ( $i = 1; $i <= 5; $i ++ ) {
			$output .= '<span class="rstar"><i class="rbi rbi-star" aria-hidden="true"></i></span>';
		}
		$output .= '</span>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_author_info' ) ) {
	function foxiz_get_author_info( $author_id = '' ) {

		if ( ! $author_id ) {
			return false;
		}

		$author_description = get_the_author_meta( 'description', $author_id );

		if ( empty( $author_description ) ) {
			return false;
		}

		$output          = '';
		$author_url      = get_author_posts_url( $author_id );
		$author_name     = get_the_author_meta( 'display_name', $author_id );
		$author_job      = get_the_author_meta( 'job', $author_id );
		$author_image_id = (int) get_the_author_meta( 'author_image_id', $author_id );
		$social_list     = foxiz_get_social_list( foxiz_get_user_socials( $author_id ), true, false );

		$output .= '<div class="ubox">';
		$output .= '<div class="ubox-header">';
		$output .= '<div class="author-info-wrap">';
		$output .= '<a class="author-avatar" href="' . $author_url . '" rel="nofollow" aria-label="' . sprintf( foxiz_html__( 'Visit posts by %s', 'foxiz' ), $author_name ) . '">';
		if ( $author_image_id !== 0 ) {
			$output .= foxiz_get_avatar_by_attachment( $author_image_id );
		} else {
			$output .= get_avatar( $author_id, 100 );
		}
		$output .= '</a>';
		$output .= '<div class="is-meta">';
		$output .= '<div class="nname-info meta-author">';
		$output .= '<span class="meta-label">' . foxiz_html__( 'By', 'foxiz' ) . '</span>';
		if ( ! is_author() ) {
			$output .= '<a class="nice-name" href="' . $author_url . '">' . $author_name . '</a>';
		} else {
			$output .= '<span class="nice-name">' . $author_name . '</span>';
		}
		if ( foxiz_is_author_tick( $author_id ) ) {
			$output .= '<i class="verified-tick rbi rbi-wavy"></i>';
		}
		$output .= '</div>';
		if ( ! empty( $author_job ) ) {
			$output .= '<span class="author-job">' . $author_job . '</span>';
		}
		$output .= '</div>';
		$output .= '</div>';

		if ( ! empty( $social_list ) ) {
			$output .= '<div class="usocials tooltips-n meta-text">';
			$output .= '<span class="ef-label">' . foxiz_html__( 'Follow: ', 'foxiz' ) . '</span>';
			$output .= $social_list;
			$output .= '</div>';
		}
		$output .= '</div>';
		$output .= '<div class="bio-description rb-text">' . foxiz_strip_tags( $author_description ) . '</div>';
		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_entry_sponsored' ) ) {
	function foxiz_get_entry_sponsored( $settings = [] ) {

		$post_id = get_the_ID();

		if ( ! foxiz_is_sponsored_post( $post_id ) ) {
			return false;
		}

		if ( ! empty( $settings['sponsor_meta'] ) && '2' === (string) $settings['sponsor_meta'] ) {
			$label = false;
			$icon  = false;
		} else {
			$label = foxiz_get_option( 'sponsor_meta_text' );
			$icon  = foxiz_get_option( 'sponsor_meta_icon' );

			if ( empty( $label ) ) {
				$label = foxiz_html__( 'Sponsored by', 'foxiz' );
			}
		}

		$sponsor_url        = rb_get_meta( 'sponsor_url', $post_id );
		$sponsor_name       = rb_get_meta( 'sponsor_name', $post_id );
		$sponsor_logo       = rb_get_meta( 'sponsor_logo', $post_id );
		$sponsor_logo_light = rb_get_meta( 'sponsor_logo_light', $post_id );
		$loading_attr       = '';

		$sponsor_attachment       = ! empty( $sponsor_logo ) ? wp_get_attachment_image_src( $sponsor_logo, 'full' ) : [];
		$sponsor_light_attachment = ! empty( $sponsor_logo_light ) ? wp_get_attachment_image_src( $sponsor_logo_light, 'full' ) : [];

		/** try to get light logo */
		if ( empty( $sponsor_attachment[0] ) && ! empty( $sponsor_light_attachment[0] ) ) {
			$sponsor_attachment = $sponsor_light_attachment;
		}

		$class_name = 'sponsor-meta meta-text';
		$class_name .= ! empty( $sponsor_attachment[0] ) ? ' sponsor-logo' : ' sponsor-text';
		if ( empty( $sponsor_light_attachment[0] ) ) {
			$class_name .= ' sponsor-s-logo';
		}
		if ( empty( $sponsor_url ) ) {
			$sponsor_url = '#';
		}
		if ( ! foxiz_is_amp() ) {
			if ( empty( $settings['feat_lazyload'] ) || 'none' !== $settings['feat_lazyload'] ) {
				$loading_attr = 'loading="lazy" decoding="async"';
			} else {
				$loading_attr = 'loading="eager" decoding="async"';
			}
		}
		$output = '';
		$output .= '<div class="' . $class_name . '">';
		$output .= '<div class="sponsor-inner">';
		$output .= '<a class="sponsor-link" href="' . esc_url( $sponsor_url ) . '" target="_blank" rel="noopener nofollow" aria-label="' . strip_tags( $sponsor_name ) . '">';
		if ( $icon ) {
			$output .= '<div class="sponsor-icon"><i class="rbi rbi-notification" aria-hidden="true"></i></div>';
		}
		if ( $label ) {
			$output .= '<span class="sponsor-label">' . foxiz_strip_tags( $label ) . '</span>';
		}
		$output .= '<div class="sponlogo-wrap meta-bold">';
		if ( ! empty( $sponsor_attachment[0] ) ) {
			$output .= '<img ' . $loading_attr;
			$output .= substr( $sponsor_attachment[0], - 4 ) === '.svg'
					? ' class="sponsor-brand-logo sponsor-brand-svg"'
					: ' class="sponsor-brand-logo"';
			if ( ! empty( $sponsor_light_attachment[0] ) ) {
				$output .= ' data-mode="default"';
			}
			$output .= ' src="' . esc_url( $sponsor_attachment[0] ) . '" width="' . ( ! empty( $sponsor_attachment[1] ) ? strip_tags( $sponsor_attachment[1] ) : '1' ) . '" height="' . ( ! empty( $sponsor_attachment[2] ) ? strip_tags( $sponsor_attachment[2] ) : '1' ) . '" alt="' . strip_tags( $sponsor_name ) . '" />';

			if ( ! empty( $sponsor_light_attachment[0] ) ) {
				$output .= '<img ' . $loading_attr;
				$output .= substr( $sponsor_light_attachment[0], - 4 ) === '.svg'
						? ' class="sponsor-brand-logo sponsor-brand-svg"'
						: ' class="sponsor-brand-logo"';
				$output .= ' data-mode="dark" src="' . esc_url( $sponsor_light_attachment[0] ) . '" width="' . ( ! empty( $sponsor_light_attachment[1] ) ? strip_tags( $sponsor_light_attachment[1] ) : '' ) . '" height="' . ( ! empty( $sponsor_light_attachment[2] ) ? strip_tags( $sponsor_light_attachment[2] ) : '' ) . '" alt="' . strip_tags( $sponsor_name ) . '" />';
			}
		} else {
			$output .= '<span class="sponsor-brand-text">' . esc_html( $sponsor_name ) . '</span>';
		}
		$output .= '</div>';
		$output .= '</a>';
		$output .= '</div>';
		if ( ! empty( $settings['bookmark'] ) ) {
			$output .= foxiz_get_bookmark_trigger( $post_id );
		}
		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_video_embed' ) ) {
	function foxiz_get_video_embed( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		if ( 'video' !== get_post_format( $post_id ) ) {
			return false;
		}

		$self_hosted_video_id = rb_get_meta( 'video_hosted', $post_id );
		$auto_play            = boolval( foxiz_get_single_setting( 'video_autoplay' ) );
		if ( ! empty( $self_hosted_video_id ) ) {
			add_filter( 'wp_video_shortcode_library', '__return_empty_string' );

			return wp_video_shortcode( [
					'src'      => wp_get_attachment_url( $self_hosted_video_id ),
					'autoplay' => $auto_play,
					'perload'  => 'auto',
			] );
		} else {
			$video_url = trim( rb_get_meta( 'video_url', $post_id ) );
			$video_url = apply_filters( 'ruby_post_video_url', $video_url, $post_id );
			$embed     = wp_oembed_get( $video_url, [
					'width'  => 740,
					'height' => 415,
			] );

			if ( ! empty( $embed ) ) {
				return $embed;
			}

			$embed = trim( rb_get_meta( 'video_embed', $post_id ) );
			if ( ! empty( $embed ) ) {
				return force_balance_tags( foxiz_strip_tags( $embed, '<iframe><video><audio>' ) );
			}
		}
	}
}

if ( ! function_exists( 'foxiz_get_audio_embed' ) ) {
	function foxiz_get_audio_embed( $post_id = '', $autoplay = false ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$self_hosted_audio_id = rb_get_meta( 'audio_hosted', $post_id );
		if ( ! empty( $self_hosted_audio_id ) ) {
			return foxiz_get_audio_hosted( [
					'src'      => wp_get_attachment_url( $self_hosted_audio_id ),
					'autoplay' => $autoplay,
					'preload'  => 'auto',
			] );
		} else {
			$classes = 'external-embed embed-holder';
			if ( $autoplay ) {
				$classes .= ' is-autoplay';
			}
			$audio_url = trim( rb_get_meta( 'audio_url', $post_id ) );
			$audio_url = apply_filters( 'ruby_post_video_url', $audio_url, $post_id );
			$embed     = wp_oembed_get( $audio_url, [ 'height' => 400, 'width' => 900 ] );
			if ( ! empty( $embed ) ) {
				return '<div class="' . strip_tags( $classes ) . '">' . $embed . '</div>';
			} else {
				$embed = rb_get_meta( 'audio_embed', $post_id );
				if ( ! empty( $embed ) ) {
					return '<div ="' . strip_tags( $classes ) . '">' . force_balance_tags( foxiz_strip_tags( $embed, '<iframe><video><audio>' ) ) . '</div>';
				}
			}
		}
	}
}

if ( ! function_exists( 'foxiz_get_attachment_caption' ) ) {
	function foxiz_get_attachment_caption( $attachment_id = '', $classes = '' ) {

		if ( ! wp_get_attachment_caption( $attachment_id ) ) {
			return false;
		}
		$class_name = 'feat-caption meta-text';
		if ( ! empty( $classes ) ) {
			$class_name .= ' ' . $classes;
		}

		return '<div class="' . strip_tags( $class_name ) . '"><span class="caption-text meta-bold">' . wp_get_attachment_caption( $attachment_id ) . '</span></div>';
	}
}

if ( ! function_exists( 'foxiz_get_audio_hosted' ) ) {
	function foxiz_get_audio_hosted( $settings = [] ) {

		if ( empty( $settings['src'] ) ) {
			return false;
		}

		$fileurl = $settings['src'];
		$type    = wp_check_filetype( $settings['src'], wp_get_mime_types() );
		if ( empty( $type['type'] ) ) {
			$type['type'] = 'audio/mpeg';
		}
		$defaults_attrs = [
				'autoplay' => '',
				'preload'  => 'auto',
				'class'    => 'self-hosted-audio podcast-player full-podcast-player',
				'style'    => 'width: 100%;',
		];
		$output         = '';
		$attrs_string   = '';
		$settings       = shortcode_atts( $defaults_attrs, $settings );
		foreach ( $settings as $k => $v ) {
			if ( ! empty( $v ) ) {
				$attrs_string .= $k . '="' . strip_tags( $v ) . '" ';
			}
		}
		$output .= '<audio ' . $attrs_string . ' controls="controls">';
		$output .= '<source type="' . $type['type'] . '" src="' . esc_url( $fileurl ) . '" />';
		$output .= '</audio>';

		if ( wp_script_is( 'foxiz-player', 'registered' ) && ! foxiz_is_amp() ) {
			wp_enqueue_script( 'foxiz-player' );
		}

		return $output;
	}
}

if ( ! function_exists( 'foxiz_entry_meta_like' ) ) {
	function foxiz_entry_meta_like( $settings ) {

		if ( foxiz_is_amp() ) {
			return;
		}

		$classes = [ 'meta-el meta-like' ];
		$post_id = get_the_ID();

		if ( ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( 'date', $settings['tablet_hide_meta'] ) ) {
			$classes[] = 'tablet-hide';
		}
		if ( ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( 'date', $settings['mobile_hide_meta'] ) ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && 'date' === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && 'date' === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>" data-like="<?php echo strip_tags( $post_id ); ?>">
			<span class="el-like like-trigger" data-title="<?php foxiz_html_e( 'Like', 'foxiz' ); ?>"><i class="rbi rbi-like"></i><span class="like-count"><?php echo foxiz_get_post_likes( $post_id ); ?></span></span>
			<span class="el-dislike dislike-trigger" data-title="<?php foxiz_html_e( 'Dislike', 'foxiz' ); ?>"><i class="rbi rbi-dislike"></i><span class="dislike-count"><?php echo foxiz_get_post_dislikes( $post_id ); ?></span></span>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_flex' ) ) {
	function foxiz_entry_meta_flex( $settings, $key = '' ) {

		if ( empty( $key ) ) {
			return;
		}

		if ( 'post_type' === $key ) {
			$post_type                           = get_post_type();
			$settings['meta_custom_field_value'] = get_post_type_object( $post_type )->labels->singular_name;
			foxiz_entry_meta_custom_field( $settings, $key );

			return;
		}

		$meta_value = get_post_meta( get_the_ID(), $key, true );
		if ( ! empty( $meta_value ) ) {
			if ( ! is_array( $meta_value ) ) {
				$settings['meta_custom_field_value'] = $meta_value;
				foxiz_entry_meta_custom_field( $settings, $key );
			}
		} else {
			foxiz_entry_meta_tax( $settings, $key );
		}
	}
}

if ( ! function_exists( 'foxiz_entry_meta_custom_field' ) ) {
	function foxiz_entry_meta_custom_field( $settings, $key ) {

		if ( empty( $settings['meta_custom_field_value'] ) ) {
			return;
		}

		$p_label = ! empty( $settings[ 'p_label_' . $key ] ) ? $settings[ 'p_label_' . $key ] : '';
		$s_label = ! empty( $settings[ 's_label_' . $key ] ) ? $settings[ 's_label_' . $key ] : '';

		$classes   = [];
		$classes[] = 'meta-el meta-field-' . $key;

		$tablet_hide_meta = ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( $key, $settings['tablet_hide_meta'] );
		$mobile_hide_meta = ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( $key, $settings['mobile_hide_meta'] );

		if ( $tablet_hide_meta ) {
			$classes[] = 'tablet-hide';
		}
		if ( $mobile_hide_meta ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && $key === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && $key === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}

		echo '<span class="' . join( ' ', $classes ) . '">' . foxiz_strip_tags( $p_label . $settings['meta_custom_field_value'] . $s_label ) . '</span>';
	}
}

if ( ! function_exists( 'foxiz_entry_meta_tax' ) ) {
	function foxiz_entry_meta_tax( $settings, $key ) {

		$terms = get_the_terms( get_the_ID(), $key );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}
		$limit = absint( foxiz_get_option( 'max_entry_meta', 999 ) );
		$index = 1;

		$s_label = ! empty( $settings[ 's_label_' . $key ] ) ? $settings[ 's_label_' . $key ] : '';

		$classes   = [ 'meta-el meta-tax meta-bold' ];
		$classes[] = 'meta-tax-' . $key;

		$tablet_hide_meta = ! empty( $settings['tablet_hide_meta'] ) && is_array( $settings['tablet_hide_meta'] ) && in_array( $key, $settings['tablet_hide_meta'] );
		$mobile_hide_meta = ! empty( $settings['mobile_hide_meta'] ) && is_array( $settings['mobile_hide_meta'] ) && in_array( $key, $settings['mobile_hide_meta'] );

		if ( $tablet_hide_meta ) {
			$classes[] = 'tablet-hide';
		}
		if ( $mobile_hide_meta ) {
			$classes[] = 'mobile-hide';
		}
		if ( ! empty( $settings['mobile_last'] ) && $key === $settings['mobile_last'] ) {
			$classes[] = 'mobile-last-meta';
		}
		if ( ! empty( $settings['tablet_last'] ) && $key === $settings['tablet_last'] ) {
			$classes[] = 'tablet-last-meta';
		}
		if ( $s_label ) {
			$classes[] = 'has-suffix';
		}
		echo '<span class="' . join( ' ', $classes ) . '">';
		if ( ! empty( $settings[ 'p_label_' . $key ] ) ) {
			echo '<span class="meta-label">' . foxiz_strip_tags( $settings[ 'p_label_' . $key ] ) . '</span>';
		}
		foreach ( $terms as $category ) {
			echo '<a class="meta-separate term-i-' . strip_tags( $category->term_id ) . '" href="' . foxiz_get_term_link( $category->term_id ) . '">' . foxiz_strip_tags( $category->name ) . '</a>';
			if ( $index >= $limit ) {
				break;
			}
			$index ++;
		}
		if ( $s_label ) {
			echo '<span class="meta-label">' . foxiz_strip_tags( $s_label ) . '</span>';
		}
		echo '</span>';
	}
}

if ( ! function_exists( 'foxiz_entry_teaser_images' ) ) {
	function foxiz_entry_teaser_images( $settings ) {

		$post_id        = get_the_ID();
		$attachment_ids = foxiz_get_content_images( $post_id );

		if ( empty( $attachment_ids ) || ! is_array( $attachment_ids ) ) {
			return;
		}

		$flag  = 1;
		$attrs = [];
		if ( empty( $settings['teaser_size'] ) ) {
			$settings['teaser_size'] = 'thumbnail';
		}
		if ( empty( $settings['teaser_col'] ) ) {
			$settings['teaser_col'] = 3;
		}
		$attrs['loading'] = ( ! empty( $settings['feat_lazyload'] ) && 'none' === $settings['feat_lazyload'] ) ? 'eager' : 'lazy';
		$is_clickable     = ! empty( $settings['teaser_link'] ) && 'yes' === $settings['teaser_link'];

		if ( $is_clickable ) {
			echo '<a class="p-teaser" href="' . get_permalink( $post_id ) . '">';
		} else {
			echo '<div class="p-teaser">';
		}
		foreach ( $attachment_ids as $id => $image_link ) {
			$is_image = wp_attachment_is_image( $id );
			if ( $is_image ) {
				echo '<div class="teaser-item">' . wp_get_attachment_image( $id, $settings['teaser_size'], false, $attrs ) . '</div>';
			} elseif ( ! empty( $image_link ) ) {
				echo '<div class="teaser-item"><img decoding="async" src="' . esc_url( $image_link ) . '" alt="' . strip_tags( 'Teaser', 'foxiz' ) . '" loading="' . $attrs['loading'] . '"/></div>';
			}
			if ( intval( $settings['teaser_col'] ) <= $flag ) {
				break;
			}
			$flag ++;
		}
		if ( $is_clickable ) {
			echo '</a>';
		} else {
			echo '</div>';
		}
	}
}

if ( ! function_exists( 'foxiz_get_avatar_by_attachment' ) ) {
	function foxiz_get_avatar_by_attachment( $attachment_id, $size = 'thumbnail', $lazy = true ) {

		return wp_get_attachment_image( $attachment_id, $size, true, [
				'loading' => ( $lazy ) ? 'lazy' : 'eager',
				'class'   => 'photo avatar',
		] );
	}
}