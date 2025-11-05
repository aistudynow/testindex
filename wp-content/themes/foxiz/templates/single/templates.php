<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_single_open_tag' ) ) {
	function foxiz_single_open_tag( $classes = '' ) {

		if ( is_sticky() ) {
			$classes .= ' sticky';
		}
		echo '<article id="post-' . get_the_ID() . '" class="' . join( ' ', get_post_class( trim( $classes ) ) ) . '">';
	}
}

if ( ! function_exists( 'foxiz_single_close_tag' ) ) {
	function foxiz_single_close_tag() {

		echo '</article>';
	}
}

if ( ! function_exists( 'foxiz_single_title' ) ) {
	function foxiz_single_title( $classes = '' ) {

		$class_name = 's-title';
		if ( ! empty( $classes ) ) {
			$class_name .= ' ' . strip_tags( $classes );
		} ?>
		<h1 class="<?php echo trim( $class_name ); ?>"><?php the_title(); ?></h1>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_tagline' ) ) {
	function foxiz_single_tagline( $classes = '' ) {

		global $post;

		$post_id = get_the_ID();

		switch ( foxiz_get_option( 'tagline_source' ) ) {
			case 'excerpt' :
				$tagline = $post->post_excerpt;
				break;
			case 'dual' :
				$tagline = rb_get_meta( 'tagline', $post_id );
				if ( empty( $tagline ) ) {
					$tagline = $post->post_excerpt;
				}
				break;
			default:
				$tagline = rb_get_meta( 'tagline', $post_id );
		}

		if ( empty( $tagline ) ) {
			return;
		}

		$html_tag = rb_get_meta( 'tagline_tag', $post_id );
		if ( empty( $html_tag ) ) {
			$html_tag = foxiz_get_option( 'tagline_tag', 'h2' );
		}

		$class_name = 's-tagline';
		if ( ! empty( $classes ) ) {
			$class_name .= ' ' . $classes;
		}

		echo '<' . strip_tags( $html_tag ) . ' class="' . strip_tags( $class_name ) . '">' . foxiz_strip_tags( $tagline ) . '</' . strip_tags( $html_tag ) . '>';
	}
}

if ( ! function_exists( 'foxiz_single_entry_category' ) ) {
	function foxiz_single_entry_category( $prefix = 'single_post' ) {

		if ( 'yes' === get_post_meta( get_the_ID(), 'ruby_live_blog', true ) ) {
			foxiz_entry_live();

			return;
		}

		$entry_category = foxiz_get_option( $prefix . '_entry_category' );
		if ( empty( $entry_category ) ) {
			return;
		}

		$classes = 's-cats';
		$parse   = explode( ',', $entry_category );

		if ( ! empty( $parse[0] ) ) {
			$classes .= ' ecat-' . $parse[0];
		}
		if ( ! empty( $parse[1] ) ) {
			$classes .= ' ecat-size-' . $parse[1];
		}
		if ( foxiz_get_option( 'single_post_entry_category_size' ) ) {
			$classes .= ' custom-size';
		}
		$settings = [
				'entry_category' => true,
				'is_singular'    => true,
		];

		if ( ! foxiz_get_option( 'single_post_primary_category' ) ) {
			$settings['only_primary'] = true;
		}

		$post_type = get_post_type();
		if ( 'post' !== $post_type ) {
			$main_tax = foxiz_get_option( 'post_type_tax_' . $post_type );
			if ( ! empty( $main_tax ) ) {
				$settings['entry_tax'] = $main_tax;
			}
		}

		echo '<div class="' . strip_tags( $classes ) . '">' . foxiz_get_entry_categories( $settings ) . '</div>';
	}
}

if ( ! function_exists( 'foxiz_entry_live' ) ) {
	function foxiz_entry_live() {

		$label = foxiz_get_single_setting( 'live_label' );
		if ( empty( $label ) ) {
			return;
		}
		echo '<div class="s-cats"><span class="meta-live meta-bold"><i class="rbi rbi-checked"></i>' . foxiz_strip_tags( $label ) . '</span></div>';
	}
}

if ( ! function_exists( 'foxiz_single_sidebar' ) ) {
	function foxiz_single_sidebar( $name = '' ) {

		if ( empty( $name ) ) {
			return;
		}

		if ( get_query_var( 'rbsnp' ) && foxiz_get_option( 'ajax_next_sidebar_name' ) ) {
			$name = foxiz_get_option( 'ajax_next_sidebar_name' );
		}

		if ( is_active_sidebar( $name ) ) {
			$border_style = foxiz_get_option( 'single_post_sidebar_border' );
			$class_name   = 'sidebar-wrap single-sidebar';
			if ( ! empty( $border_style ) ) {
				if ( '1' === (string) $border_style ) {
					$border_style = 'gray';
				}
				$class_name .= ' has-border is-border-' . $border_style;
			}
			?>
			<div class="<?php echo strip_tags( $class_name ); ?>">
				<div class="sidebar-inner clearfix">
					<?php dynamic_sidebar( $name ); ?>
				</div>
			</div>
			<?php
		}
	}
}











if ( ! function_exists( 'foxiz_single_standard_featured' ) ) {
	function foxiz_single_standard_featured( $size = 'full', $lazyload = null ) {

		if ( ! has_post_thumbnail() ) {
			return;
		}

		$attrs    = [];
		$lazyload = empty( $lazyload ) ? foxiz_get_option( 'lazy_load_single_feat' ) : ( $lazyload === 'none' ? false : true );

		if ( $lazyload ) {
			$attrs['loading'] = 'lazy';
		} else {
			$attrs['loading']       = 'eager';
			$attrs['fetchpriority'] = 'high';
		}
		?>
		<div class="s-feat"><?php foxiz_single_featured_image( $size, $attrs ); ?></div>
		<?php
	}
}













if ( ! function_exists( 'foxiz_single_featured_image' ) ) {
	function foxiz_single_featured_image( $size = 'full', $attrs = [] ) {

		$attachment_id = get_post_thumbnail_id();
		if ( ! $attachment_id ) {
			return;
		}

		// Treat single post featured image as LCP candidate.
		$is_lcp = is_singular( 'post' ) && is_main_query();

		// Container-based sizes. Adjust if your content widths differ.
		$sizes_string = '(min-width:1440px) 1200px, (min-width:1024px) 960px, (min-width:700px) 720px, 92vw';

		// Prefer smaller default src to reduce risk if srcset gets stripped.
$render_size = ( $size === 'full' ) ? 'large' : $size;
$srcset_try  = wp_get_attachment_image_srcset( $attachment_id, $render_size );

// If we asked for 'large' but the container can exceed 1024px, ensure srcset includes full.
if ( ! empty( $srcset_try ) && $render_size === 'large' ) {
    $full_srcset = wp_get_attachment_image_srcset( $attachment_id, 'full' );
    if ( $full_srcset && strpos( $srcset_try, ' 1280w' ) === false ) {
        // let the browser consider 1280w candidate if it exists
        $srcset_try .= ', ' . wp_get_attachment_image_url( $attachment_id, 'full' ) . ' 1280w';
    }
}

// Absolute fallback if no srcset was generated at all
if ( empty( $srcset_try ) ) {
    $render_size = 'full';
}

		// Base attributes (we'll enforce LCP below).
		$attrs = wp_parse_args(
			$attrs,
			[
				'class'    => 'featured-image',
				'decoding' => 'async',
			]
		);

		// If we have a working srcset, wire it up; otherwise keep markup lean.
		if ( ! empty( $srcset_try ) ) {
			$attrs['srcset'] = $srcset_try;
			if ( empty( $attrs['sizes'] ) ) {
				$attrs['sizes'] = $sizes_string;
			}
		} else {
			unset( $attrs['srcset'], $attrs['sizes'] );
		}

		// Final safety: hard-force eager/high for LCP candidate.
		if ( $is_lcp ) {
    $attrs['loading']       = 'eager';
    $attrs['fetchpriority'] = 'high';
    $attrs['decoding']      = 'auto'; // instead of 'async' for LCP
} else {
			// Sensible defaults for non-LCP images.
			$attrs['loading']       = $attrs['loading']       ?? 'lazy';
			$attrs['fetchpriority'] = $attrs['fetchpriority'] ?? 'auto';
		}

		// Alt fallback.
		if ( empty( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) {
			$attrs['alt'] = get_the_title();
		}

		// Caption/credit (used by lightbox).
		$caption     = rb_get_meta( 'featured_caption', get_the_ID() );
		$attribution = rb_get_meta( 'featured_attribution', get_the_ID() );
		if ( empty( $caption ) ) {
			$caption = get_the_post_thumbnail_caption();
		}

		// Build the tag once with final attributes.
		$html_img = wp_get_attachment_image( $attachment_id, $render_size, false, $attrs );

		if ( foxiz_get_option( 'single_post_featured_lightbox' ) ) {
			$full = wp_get_attachment_image_url( $attachment_id, 'full' );
			?>
			<div class="featured-lightbox-trigger"
				 data-source="<?php echo esc_url( $full ); ?>"
				 data-caption="<?php echo esc_attr( wp_strip_all_tags( $caption ) ); ?>"
				 data-attribution="<?php echo esc_attr( wp_strip_all_tags( $attribution ) ); ?>">
				<?php echo $html_img; ?>
			</div>
			<?php
		} else {
			echo $html_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}










/**
 * Preload the LCP image, mirroring the same fallback logic as above.
 * Remove this if PSI doesn't recommend preload.
 */







if ( ! function_exists( 'foxiz_get_single_featured_caption' ) ) {
	function foxiz_get_single_featured_caption( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$caption     = rb_get_meta( 'featured_caption', $post_id );
		$attribution = rb_get_meta( 'featured_attribution', $post_id );

		if ( empty( $caption ) && ! foxiz_get_option( 'single_post_caption_fallback' ) ) {
			$caption = get_the_post_thumbnail_caption();
		}

		if ( empty( $caption ) ) {
			return false;
		}

		$output  = '<div class="feat-caption meta-text">';
		$output .= '<span class="caption-text meta-bold">' . esc_html( wp_strip_all_tags( $caption ) ) . '</span>';
		if ( ! empty( $attribution ) ) {
			$output .= '<em class="attribution">' . esc_html( wp_strip_all_tags( $attribution ) ) . '</em>';
		}
		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_single_featured_caption' ) ) {
	function foxiz_single_featured_caption( $post_id = '' ) {
		echo foxiz_get_single_featured_caption( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}














if ( ! function_exists( 'foxiz_single_sponsor' ) ) {
	function foxiz_single_sponsor( $post_id = '', $class_name = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		} ?>
		<aside class="smeta-in single-sponsor">
			<?php echo foxiz_get_entry_sponsored( $post_id ); ?>
		</aside>
		<?php
	}
}

if ( ! function_exists( 'foxiz_is_single_share_left' ) ) {
	function foxiz_is_single_share_left( $post_id = '' ) {

		if ( ! foxiz_get_option( 'share_left' ) || ( foxiz_is_amp() && foxiz_get_option( 'amp_disable_left_share' ) ) ) {
			return false;
		}

		$settings = [
				'facebook'  => foxiz_get_option( 'share_left_facebook' ),
				'twitter'   => foxiz_get_option( 'share_left_twitter' ),
				'flipboard' => foxiz_get_option( 'share_left_flipboard' ),
				'pinterest' => foxiz_get_option( 'share_left_pinterest' ),
				'whatsapp'  => foxiz_get_option( 'share_left_whatsapp' ),
				'linkedin'  => foxiz_get_option( 'share_left_linkedin' ),
				'tumblr'    => foxiz_get_option( 'share_left_tumblr' ),
				'reddit'    => foxiz_get_option( 'share_left_reddit' ),
				'vk'        => foxiz_get_option( 'share_left_vk' ),
				'telegram'  => foxiz_get_option( 'share_left_telegram' ),
				'threads'   => foxiz_get_option( 'share_left_threads' ),
				'bsky'      => foxiz_get_option( 'share_left_bsky' ),
				'email'     => foxiz_get_option( 'share_left_email' ),
				'copy'      => foxiz_get_option( 'share_left_copy' ),
				'print'     => foxiz_get_option( 'share_left_print' ),
				'native'    => foxiz_get_option( 'share_left_native' ),
		];

		if ( ! array_filter( $settings ) ) {
			return false;
		}

		$settings['tipsy_gravity'] = is_rtl() ? 'e' : 'w';
		$settings['post_id']       = ! empty( $post_id ) ? $post_id : get_the_ID();

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_single_share_left' ) ) {
	/**
	 * @param array $settings
	 */
	function foxiz_single_share_left( $settings = [] ) {

		if ( ! function_exists( 'foxiz_render_share_list' ) ) {
			return;
		}

		$classes       = 'l-shared-sec-outer';
		$inner_classes = 'l-shared-items effect-fadeout';

		if ( foxiz_get_option( 'share_left_color' ) ) {
			$inner_classes .= ' is-color';
		}
		if ( foxiz_get_option( 'share_left_mobile' ) ) {
			$classes .= ' show-mobile';
		}
		?>
		<div class="<?php echo strip_tags( $classes ); ?>">
			<div class="l-shared-sec">
				<div class="l-shared-header meta-text">
					<i class="rbi rbi-share" aria-hidden="true"></i><span class="share-label"><?php foxiz_html_e( 'SHARE', 'foxiz' ); ?></span>
				</div>
				<div class="<?php echo strip_tags( $inner_classes ); ?>">
					<?php foxiz_render_share_list( $settings ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_is_single_share_top' ) ) {
	function foxiz_is_single_share_top( $post_id = '' ) {

		if ( ! foxiz_get_option( 'share_top' ) ) {
			return false;
		}
		$settings = [
				'facebook'  => foxiz_get_option( 'share_top_facebook' ),
				'twitter'   => foxiz_get_option( 'share_top_twitter' ),
				'flipboard' => foxiz_get_option( 'share_top_flipboard' ),
				'pinterest' => foxiz_get_option( 'share_top_pinterest' ),
				'whatsapp'  => foxiz_get_option( 'share_top_whatsapp' ),
				'linkedin'  => foxiz_get_option( 'share_top_linkedin' ),
				'tumblr'    => foxiz_get_option( 'share_top_tumblr' ),
				'reddit'    => foxiz_get_option( 'share_top_reddit' ),
				'vk'        => foxiz_get_option( 'share_top_vk' ),
				'telegram'  => foxiz_get_option( 'share_top_telegram' ),
				'threads'   => foxiz_get_option( 'share_top_threads' ),
				'bsky'      => foxiz_get_option( 'share_top_bsky' ),
				'email'     => foxiz_get_option( 'share_top_email' ),
				'copy'      => foxiz_get_option( 'share_top_copy' ),
				'print'     => foxiz_get_option( 'share_top_print' ),
				'native'    => foxiz_get_option( 'share_top_native' ),
		];
		if ( ! array_filter( $settings ) ) {
			return false;
		}

		$settings['post_id'] = ! empty( $post_id ) ? $post_id : get_the_ID();

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_single_share_top' ) ) {
	function foxiz_single_share_top( $settings = [] ) {

		if ( ! function_exists( 'foxiz_render_share_list' ) ) {
			return;
		}

		$classes = 't-shared-sec tooltips-n';

		if ( ! empty( $settings['has_read_meta'] ) ) {
			$classes .= ' has-read-meta';
		}
		if ( foxiz_get_option( 'share_top_color' ) ) {
			$classes .= ' is-color';
		} ?>
		<div class="<?php echo strip_tags( $classes ); ?>">
			<div class="t-shared-header is-meta">
				<i class="rbi rbi-share" aria-hidden="true"></i><span class="share-label"><?php foxiz_html_e( 'Share', 'foxiz' ); ?></span>
			</div>
			<div class="effect-fadeout"><?php foxiz_render_share_list( $settings ); ?></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_header_meta' ) ) {
	function foxiz_single_header_meta( $prefix = 'single_post', $settings = [] ) {

		$post_id                   = get_the_ID();
		$classes                   = [];
		$classes[]                 = 'single-meta';
		$settings['prefix_id']     = $prefix;
		$settings['feat_lazyload'] = 'none';
		$post_type                 = get_post_type();

		if ( 'single_post' === $prefix && 'post' !== $post_type && empty( $settings['entry_meta'] ) ) {
			$settings['entry_meta'] = foxiz_get_option( 'single_post_entry_meta_keys_' . $post_type );
		}

		$meta_layout = ! empty( $settings['meta_layout'] ) ? $settings['meta_layout'] : foxiz_get_option( $prefix . '_meta_layout', '0' );
		$divider     = ! empty( $settings['meta_divider'] ) ? $settings['meta_divider'] : (
		! empty( $prefix_divider = foxiz_get_option( $prefix . '_meta_divider' ) ) ? $prefix_divider : foxiz_get_option( 'meta_divider' )
		);

		$big_avatar          = empty( $settings['avatar'] ) ? foxiz_get_option( $prefix . '_avatar' ) : '-1' !== (string) $settings['avatar'];
		$update_date         = empty( $settings['updated_meta'] ) ? foxiz_get_option( $prefix . '_updated_meta' ) : '-1' !== (string) $settings['updated_meta'];
		$min_read            = empty( $settings['min_read'] ) ? foxiz_get_option( $prefix . '_min_read' ) : '-1' !== (string) $settings['min_read'];
		$meta_border         = empty( $settings['meta_border'] ) ? foxiz_get_option( $prefix . '_meta_border' ) : '-1' !== (string) $settings['meta_border'];
		$meta_centered       = empty( $settings['meta_centered'] ) ? foxiz_get_option( $prefix . '_meta_centered' ) : '-1' !== (string) $settings['meta_centered'];
		$meta_author_style   = ! empty( $settings['meta_author_style'] ) ? $settings['meta_author_style'] : foxiz_get_option( $prefix . '_meta_author_style' );
		$meta_bookmark_style = ! empty( $settings['meta_bookmark_style'] ) ? $settings['meta_bookmark_style'] : foxiz_get_option( $prefix . '_meta_bookmark_style' );
		$is_sponsored_post   = foxiz_is_sponsored_post( $post_id );

		if ( ! empty( $divider ) ) {
			$classes[] = 'meta-s-' . $divider;
		}
		$classes[] = 'yes-' . $meta_layout;

		if ( $meta_author_style ) {
			$classes[] = 'is-meta-author-' . $meta_author_style;
		}
		if ( $meta_bookmark_style ) {
			$classes[] = 'is-bookmark-' . $meta_bookmark_style;
		}
		if ( ! empty( $meta_border ) ) {
			$classes[] = 'yes-border';
		}
		if ( ! empty( $meta_centered ) ) {
			$classes[] = 'yes-center';
		}
		if ( $is_sponsored_post ) {
			$classes[] = 'is-sponsored';
		}
		?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php if ( $is_sponsored_post ) :
				foxiz_single_sponsor( $post_id );
			else : ?>
				<div class="smeta-in">
					<?php if ( ! empty( $big_avatar ) ) {
						foxiz_entry_meta_avatar( [
								'avatar_size'   => 120,
								'feat_lazyload' => $settings['feat_lazyload'],
						] );
					} ?>
					<div class="smeta-sec">
						<?php if ( ! empty( $update_date ) ) :
							$format = foxiz_get_option( $prefix . '_update_format' );
							if ( empty( $format ) ) {
								$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
							} ?>
							<div class="smeta-bottom meta-text">
								<time class="updated-date" datetime="<?php echo get_the_modified_date( DATE_W3C ); ?>"><?php
									if ( foxiz_get_option( 'single_post_updated_label' ) ) {
										echo foxiz_html__( 'Last updated:', 'foxiz' ) . ' ';
									}
									echo get_the_modified_date( $format, $post_id );
									?></time>
							</div>
						<?php endif; ?>
						<div class="p-meta">
							<div class="meta-inner is-meta"><?php echo foxiz_get_single_entry_meta( $settings ); ?></div>
						</div>
					</div>
				</div>
			<?php endif;
			$share_settings = foxiz_is_single_share_top( $post_id );
			if ( ! empty( $share_settings ) || ! empty( $min_read ) ) : ?>
				<div class="smeta-extra"><?php
					if ( ! empty( $share_settings ) ) {
						$share_settings['has_read_meta'] = $min_read;
						foxiz_single_share_top( $share_settings );
					}
					if ( ! empty( $min_read ) ) {
						echo '<div class="single-right-meta single-time-read is-meta">';
						foxiz_entry_meta_read_time( $post_id );
						echo '</div>';
					}
					?></div>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_get_single_entry_meta' ) ) {
	function foxiz_get_single_entry_meta( $settings = [] ) {

		$prefix = ! empty( $settings['prefix_id'] ) ? $settings['prefix_id'] : 'single_post';

		if ( empty( $settings['entry_meta'] ) ) {
			$settings['entry_meta'] = ! empty( foxiz_get_option( $prefix . '_entry_meta_keys' ) ) ? foxiz_get_option( $prefix . '_entry_meta_keys' ) : foxiz_get_option( $prefix . '_entry_meta' );
		}

		if ( ! is_array( $settings['entry_meta'] ) ) {
			$settings['entry_meta'] = array_map( 'trim', explode( ',', $settings['entry_meta'] ) );
		}

		if ( ! is_array( $settings['entry_meta'] ) || ! array_filter( $settings['entry_meta'] ) ) {
			return false;
		}

		$settings = foxiz_extra_meta_labels( $settings );

		if ( ! isset( $settings['tablet_hide_meta'] ) ) {
			$settings['tablet_hide_meta'] = foxiz_get_option( $prefix . '_tablet_hide_meta' );
		}
		if ( ! isset( $settings['mobile_hide_meta'] ) ) {
			$settings ['mobile_hide_meta'] = foxiz_get_option( $prefix . '_mobile_hide_meta' );
		}

		if ( is_array( $settings['tablet_hide_meta'] ) ) {
			$tablet_meta             = array_diff( $settings['entry_meta'], $settings['tablet_hide_meta'] );
			$settings['tablet_last'] = end( $tablet_meta );
		}

		if ( is_array( $settings['mobile_hide_meta'] ) ) {
			$mobile_meta             = array_diff( $settings['entry_meta'], $settings['mobile_hide_meta'] );
			$settings['mobile_last'] = end( $mobile_meta );
		}

		$settings['meta_label_by']  = foxiz_get_option( $prefix . '_meta_author_label' );
		$settings['has_author_job'] = foxiz_get_option( $prefix . '_author_job' );
		$settings['has_date_label'] = foxiz_get_option( $prefix . '_meta_date_label' ) ? true : false;
		$settings['date_format']    = foxiz_get_option( $prefix . '_meta_date_format', '' );
		$settings['yes_single']     = true;

		$post_type = get_post_type();
		if ( 'post' !== $post_type ) {
			$main_tax = foxiz_get_option( 'post_type_tax_' . $post_type );
			if ( ! empty( $main_tax ) ) {
				$settings['taxonomy']  = $main_tax;
				$settings['post_type'] = $post_type;
			}
		}

		return foxiz_get_entry_meta( $settings );
	}
}

if ( ! function_exists( 'foxiz_single_content' ) ) {
	function foxiz_single_content() {

		$class_name          = 's-ct-wrap';
		$share_left_settings = foxiz_is_single_share_left();

		if ( ! empty( $share_left_settings ) ) {
			$class_name .= ' has-lsl';
		} ?>
		<div class="<?php echo strip_tags( $class_name ); ?>">
			<div class="s-ct-inner">
				<?php
				if ( ! empty( $share_left_settings ) ) {
					foxiz_single_share_left( $share_left_settings );
				}
				?>
				<div class="e-ct-outer">
					<?php
					foxiz_single_live_blog_header();
					foxiz_single_entry_top();
					foxiz_single_highlights();
					foxiz_single_page_selected();
					foxiz_single_quick_info();
					foxiz_single_entry_content();
					foxiz_single_review();
					foxiz_single_link_pages();
					foxiz_single_entry_bottom();
					if ( ! empty ( foxiz_get_single_entry_footer() ) ) {
						echo foxiz_get_single_entry_footer();
					}
					foxiz_single_newsletter();
					?>
				</div>
			</div>
			<?php
			foxiz_single_share_bottom();
			if ( foxiz_get_single_setting( 'ajax_next_post' ) && foxiz_get_option( 'share_sticky' ) ) {
				echo '<div class="sticky-share-list-buffer">';
				foxiz_single_share_sticky( get_the_ID() );
				echo '</div>';
			}
			foxiz_single_reaction();
			?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_single_entry_content' ) ) {
	function foxiz_single_entry_content() {

		$classes = 'entry-content rbct clearfix';
		if ( foxiz_get_option( 'single_post_highlight_shares' ) ) {
			$classes .= ' is-highlight-shares';
		}
		if ( foxiz_is_live_blog() ) {
			$classes .= ' rb-live-entry';
		} ?>
		<div class="<?php echo strip_tags( $classes ); ?>"><?php the_content(); ?></div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_link_pages' ) ) {
	function foxiz_single_link_pages() {

		/** make theme check happy */
		if ( ! function_exists( 'foxiz_get_single_link_pages' ) ) {
			wp_link_pages();

			return;
		}

		echo foxiz_get_single_link_pages();
	}
}

if ( ! function_exists( 'foxiz_get_single_link_pages' ) ) {
	function foxiz_get_single_link_pages() {

		global $page, $numpages, $multipage, $more;

		if ( ! $multipage ) {
			return false;
		}
		$prev = $page - 1;
		$next = $page + 1;

		$output = '<aside class="pagination-wrap page-links">';
		if ( $prev > 0 ) {
			$output .= '<span class="text-link-prev">';
			$output .= _wp_link_page( $prev ) . '<i class="rbi rbi-cleft" aria-hidden="true"></i><span>' . foxiz_html__( 'Previous Page', 'foxiz' ) . '</span></a>';
			$output .= '</span>';
		}
		$output .= '<span class="number-links">';
		for ( $i = 1; $i <= $numpages; $i ++ ) {
			$link = str_replace( '%', $i, '%' );
			if ( $i !== $page || ! $more && 1 === $page ) {
				$link = _wp_link_page( $i ) . $link . '</a>';
			} elseif ( $i === $page ) {
				$link = '<span class="post-page-numbers current" aria-current="page">' . $link . '</span>';
			}
			$output .= $link;
		}
		$output .= '</span>';
		if ( $next <= $numpages ) {
			$output .= '<span class="text-link-next">';
			$output .= _wp_link_page( $next ) . '<span>' . foxiz_html__( 'Next Page', 'foxiz' ) . '</span><i class="rbi rbi-cright" aria-hidden="true"></i></a>';
			$output .= '</span>';
		}
		$output .= '</aside>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_single_simple_content' ) ) {
	function foxiz_single_simple_content() {

		if ( foxiz_is_wc_pages() ) {
			$classes = 'wc-entry-content';
		} else {
			$classes = 'entry-content rbct';
		} ?>
		<div class="s-ct-inner">
			<div class="e-ct-outer">
				<div class="<?php echo strip_tags( $classes ); ?>">
					<?php
					the_content();
					echo '<div class="clearfix"></div>';
					foxiz_single_link_pages();
					?>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_get_single_entry_footer' ) ) {
	function foxiz_get_single_entry_footer() {

		ob_start();
		foxiz_single_tags();
		foxiz_single_sources();
		foxiz_single_via();
		$output = ob_get_clean();

		if ( empty( $output ) ) {
			return false;
		}

		$class_name = 'efoot';
		$layout     = foxiz_get_option( 'efoot_layout' );
		switch ( $layout ) {
			case 'dark' :
				$class_name .= ' efoot-border p-categories';
				break;
			case 'gray' :
				$class_name .= ' efoot-border is-b-gray p-categories';
				break;
			case 'bg' :
				$class_name .= ' efoot-bg  p-categories';
				break;
			default :
				$class_name .= ' efoot-commas h5';
		}

		return '<div class="' . $class_name . '">' . $output . '</div>';
	}
}

if ( ! function_exists( 'foxiz_single_tags' ) ) {
	function foxiz_single_tags() {

		if ( ! foxiz_get_option( 'single_post_tags' ) || ! get_the_tag_list() ) {
			return;
		} ?>
		<div class="efoot-bar tag-bar">
			<span class="blabel is-meta"><i class="rbi rbi-tag" aria-hidden="true"></i><?php echo foxiz_html__( 'TAGGED:', 'foxiz' ); ?></span><?php echo get_the_tag_list(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_sources' ) ) {
	function foxiz_single_sources() {

		if ( ! foxiz_get_option( 'single_post_sources' ) ) {
			return;
		}
		$sources = rb_get_meta( 'source_data' );
		if ( ! is_array( $sources ) || ! count( $sources ) ) {
			return;
		}
		$links = [];
		foreach ( $sources as $source ) {
			if ( ! empty( $source['name'] ) && ! empty( $source['url'] ) ) {
				$links[] = '<a href="' . esc_url( $source['url'] ) . '" rel="nofollow" target="_blank">' . strip_tags( $source['name'] ) . '</a>';
			}
		}
		if ( empty( $links ) ) {
			return;
		}
		?>
		<div class="efoot-bar source-bar">
			<span class="blabel is-meta"><i class="rbi rbi-archive" aria-hidden="true"></i><?php echo foxiz_html__( 'SOURCES:', 'foxiz' ); ?></span><?php echo join( '', $links ); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_via' ) ) {
	function foxiz_single_via() {

		if ( ! foxiz_get_option( 'single_post_via' ) ) {
			return;
		}
		$via = rb_get_meta( 'via_data' );
		if ( ! is_array( $via ) || ! count( $via ) ) {
			return;
		}
		$links = [];
		foreach ( $via as $item ) {
			if ( ! empty( $item['name'] ) && ! empty( $item['url'] ) ) {
				$links[] = '<a href="' . esc_url( $item['url'] ) . '" rel="nofollow" target="_blank">' . strip_tags( $item['name'] ) . '</a>';
			}
		}
		if ( empty( $links ) ) {
			return;
		} ?>
		<div class="efoot-bar via-bar">
			<span class="blabel is-meta"><i class="rbi rbi-via" aria-hidden="true"></i><?php echo foxiz_html__( 'VIA:', 'foxiz' ); ?></span><?php echo join( '', $links ); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_author_box' ) ) {
	function foxiz_single_author_box( $override = false ) {

		if ( foxiz_is_amp() && foxiz_get_option( 'amp_disable_author' ) ) {
			return;
		}

		if ( ! foxiz_get_option( 'single_post_author_card' ) && ! $override ) {
			return;
		}

		$class_name = 'usr-holder';
		if ( ! $override ) {
			$class_name .= ' entry-sec';
		}

		if ( function_exists( 'get_post_authors' ) ) {
			$author_data = get_post_authors( get_the_ID() );
			if ( is_array( $author_data ) && count( $author_data ) > 1 ) {
				echo '<div class="' . strip_tags( $class_name ) . '">';
				foreach ( $author_data as $author ) {
					echo foxiz_get_author_info( $author->ID );
				}
				echo '</div>';

				return;
			}
		}

		if ( foxiz_get_author_info( get_the_author_meta( 'ID' ) ) ) {
			echo '<div class="' . strip_tags( $class_name ) . '">' . foxiz_get_author_info( get_the_author_meta( 'ID' ) ) . '</div>';
		}
	}
}

if ( ! function_exists( 'foxiz_single_reaction' ) ) {
	function foxiz_single_reaction() {

		if ( ! shortcode_exists( 'ruby_reaction' ) || ! foxiz_get_single_setting( 'reaction' ) || ! is_singular( 'post' ) || foxiz_is_amp() ) {
			return;
		}

		$reaction_title = foxiz_get_option( 'single_post_reaction_title' );
		if ( empty( $reaction_title ) ) {
			$reaction_title = foxiz_html__( 'What do you think?', 'foxiz' );
		} ?>
		<aside class="reaction-sec entry-sec">
			<div class="reaction-heading">
				<span class="h3"><?php foxiz_render_inline_html( apply_filters( 'the_title', $reaction_title, 12 ) ); ?></span>
			</div>
			<div class="reaction-sec-content">
				<?php echo do_shortcode( '[ruby_reaction]' ); ?>
			</div>
		</aside>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_share_bottom' ) ) {
	function foxiz_single_share_bottom( $post_id = '' ) {

		if ( ! foxiz_get_option( 'share_bottom' ) || ! function_exists( 'foxiz_render_share_list' ) ) {
			return;
		}

		$settings = [
				'facebook'  => foxiz_get_option( 'share_bottom_facebook' ),
				'twitter'   => foxiz_get_option( 'share_bottom_twitter' ),
				'flipboard' => foxiz_get_option( 'share_bottom_flipboard' ),
				'pinterest' => foxiz_get_option( 'share_bottom_pinterest' ),
				'whatsapp'  => foxiz_get_option( 'share_bottom_whatsapp' ),
				'linkedin'  => foxiz_get_option( 'share_bottom_linkedin' ),
				'tumblr'    => foxiz_get_option( 'share_bottom_tumblr' ),
				'reddit'    => foxiz_get_option( 'share_bottom_reddit' ),
				'vk'        => foxiz_get_option( 'share_bottom_vk' ),
				'telegram'  => foxiz_get_option( 'share_bottom_telegram' ),
				'threads'   => foxiz_get_option( 'share_bottom_threads' ),
				'bsky'      => foxiz_get_option( 'share_bottom_bsky' ),
				'email'     => foxiz_get_option( 'share_bottom_email' ),
				'copy'      => foxiz_get_option( 'share_bottom_copy' ),
				'print'     => foxiz_get_option( 'share_bottom_print' ),
				'native'    => foxiz_get_option( 'share_bottom_native' ),
		];

		if ( ! array_filter( $settings ) ) {
			return;
		}
		$settings['post_id']     = ! empty( $post_id ) ? $post_id : get_the_ID();
		$settings['social_name'] = true;

		$class_name = 'rbbsl tooltips-n effect-fadeout';
		if ( foxiz_get_option( 'share_bottom_color' ) ) {
			$class_name .= ' is-bg';
		}
		$post_type = get_post_type();
		$label     = ( 'podcast' === $post_type ) ? foxiz_html__( 'Share This Episode', 'foxiz' ) : foxiz_html__( 'Share This Article', 'foxiz' );
		?>
		<div class="e-shared-sec entry-sec">
			<div class="e-shared-header h4">
				<i class="rbi rbi-share" aria-hidden="true"></i><span><?php echo apply_filters( 'rb_share_label', $label, $post_type ) ?></span>
			</div>
			<div class="<?php echo strip_tags( $class_name ); ?>">
				<?php foxiz_render_share_list( $settings ); ?>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_comment' ) ) {
	function foxiz_single_comment( $is_page = false, $override = false ) {

		if ( post_password_required() || ! comments_open() || ( foxiz_get_option( 'single_post_comment' ) && ! $override ) || ( foxiz_is_amp() && foxiz_get_option( 'amp_disable_comment' ) ) ) {
			return;
		}

		$user_rating = foxiz_get_single_setting( 'user_can_review' );

		if ( ( '1' === (string) $user_rating && foxiz_is_review_post() ) || '2' === (string) $user_rating ) {
			comments_template( '/templates/single/review-comment.php' );

			return;
		}

		?>
		<div class="comment-box-wrap entry-sec"><?php comments_template(); ?></div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_get_review_heading' ) ) {
	/**
	 * @param string $post_id
	 *
	 * @return mixed|string
	 */
	function foxiz_get_review_heading( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$output = foxiz_html__( 'Leave a review', 'foxiz' );
		$count  = intval( get_comments_number( $post_id ) );
		if ( $count > 1 ) {
			$output = sprintf( foxiz_html__( '%s Reviews', 'foxiz' ), $count );
		} elseif ( 1 === $count ) {
			$output = foxiz_html__( '1 Review', 'foxiz' );
		}

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_comment_heading' ) ) {
	/**
	 * @param string $post_id
	 *
	 * @return mixed|string
	 */
	function foxiz_get_comment_heading( $post_id = '' ) {

		$output = get_comments_number_text( false, false, false, $post_id );

		if ( strtolower( $output ) === 'no comments' ) {
			$output = foxiz_html__( 'Leave a Comment', 'foxiz' );
		}

		return $output;
	}
}

if ( ! function_exists( 'foxiz_single_newsletter' ) ) {
	function foxiz_single_newsletter() {

		if ( ! foxiz_get_option( 'single_post_newsletter' ) || ! do_shortcode( '[ruby_static_newsletter]' ) ) {
			return;
		} ?>
		<div class="entry-newsletter"><?php echo do_shortcode( '[ruby_static_newsletter]' ); ?></div>
	<?php }
}

if ( ! function_exists( 'foxiz_user_review_list' ) ) {
	/**
	 * @param $comment
	 * @param $args
	 * @param $depth
	 */
	function foxiz_user_review_list( $comment, $args, $depth ) {

		$commenter = wp_get_current_commenter();
		if ( $commenter['comment_author_email'] ) {
			$moderation_note = foxiz_html__( 'Your review is awaiting moderation.', 'foxiz' );
		} else {
			$moderation_note = foxiz_html__( 'Your review is awaiting moderation. This is a preview, your review will be visible after it has been approved.', 'foxiz' );
		}
		$rating_value = get_comment_meta( $comment->comment_ID, 'rbrating', true ); ?>
		<li class="comment_container" id="comment-<?php comment_ID(); ?>">
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<div class="comment-author vcard">
					<?php if ( 0 != $args['avatar_size'] ) {
						echo get_avatar( $comment, $args['avatar_size'] );
					}
					printf( '%s <span class="says">says:</span>', sprintf( '<b class="fn">%s</b>', get_comment_author_link( $comment ) ) ); ?>
				</div>
				<?php if ( '0' === (string) $comment->comment_approved ) : ?>
					<em class="comment-awaiting-moderation"><?php foxiz_render_inline_html( $moderation_note ); ?></em>
				<?php endif; ?>
				<div class="comment-meta comment-metadata commentmetadata">
					<?php if ( ! empty( $rating_value ) ) : ?>
						<span class="review-stars">
						<?php for ( $i = 1; $i <= 5; $i ++ ) {
							if ( $i <= $rating_value ) {
								echo '<i class="rbi rbi-star" aria-hidden="true"></i>';
							} else {
								echo '<i class="rbi rbi-star-o" aria-hidden="true"></i>';
							}
						} ?>
					</span>
					<?php endif;
					edit_comment_link( foxiz_html__( 'Edit', 'foxiz' ) ); ?>
				</div>
				<div class="comment-content">
					<?php comment_text(); ?>
				</div>
				<?php echo get_comment_reply_link(
						array_merge( $args, [
										'add_below' => 'div-comment',
										'depth'     => $depth,
										'max_depth' => $args['max_depth'],
										'before'    => '<span class="comment-reply">',
										'after'     => '</span>',
								]
						)
				); ?>
			</article>
		</li>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_next_prev' ) ) {
	function foxiz_single_next_prev( $override = false ) {

		if ( ( ! foxiz_get_option( 'single_post_next_prev' ) && ! $override ) || ( foxiz_is_amp() && foxiz_get_option( 'amp_disable_single_pagination' ) ) ) {
			return;
		}

		$post_previous = get_adjacent_post( false, '', true );
		$post_next     = get_adjacent_post( false, '', false );
		if ( empty( $post_previous ) && empty( $post_next ) ) {
			return;
		}

		$class_name = 'entry-pagination e-pagi';
		if ( ! $override ) {
			$class_name .= ' entry-sec';
		}
		if ( foxiz_get_option( 'single_post_next_prev_mobile' ) && ! $override ) {
			$class_name .= ' mobile-hide';
		}
		?>
		<div class="<?php echo strip_tags( $class_name ); ?>">
			<div class="inner">
				<?php if ( ! empty( $post_previous ) ) : ?>
					<div class="nav-el nav-left">
						<a href="<?php echo get_permalink( $post_previous->ID ); ?>">
                            <span class="nav-label is-meta">
                                <i class="rbi rbi-angle-left" aria-hidden="true"></i><span><?php echo foxiz_html__( 'Previous Article', 'foxiz' ); ?></span>
                            </span><span class="nav-inner h4">
								<?php echo get_the_post_thumbnail( $post_previous->ID, 'thumbnail' ); ?>
                               <span class="e-pagi-holder"><span class="e-pagi-title p-url"><?php echo get_the_title( $post_previous->ID ); ?></span></span>
                            </span></a>
					</div>
				<?php endif;
				if ( ! empty( $post_next ) ) : ?>
					<div class="nav-el nav-right">
						<a href="<?php echo get_permalink( $post_next->ID ); ?>">
                            <span class="nav-label is-meta">
                                <span><?php echo foxiz_html__( 'Next Article', 'foxiz' ); ?></span><i class="rbi rbi-angle-right" aria-hidden="true"></i>
                            </span><span class="nav-inner h4">
                              <?php echo get_the_post_thumbnail( $post_next->ID, 'thumbnail' ); ?>
                             <span class="e-pagi-holder"><span class="e-pagi-title p-url"><?php echo get_the_title( $post_next->ID ); ?></span></span>
                            </span></a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_single_entry_top' ) ) {
	function foxiz_single_entry_top() {

		if ( foxiz_is_amp() ) {
			if ( function_exists( 'foxiz_amp_ad' ) ) {
				foxiz_amp_ad( [
						'type'      => foxiz_get_option( 'amp_top_single_ad_type' ),
						'client'    => foxiz_get_option( 'amp_top_single_adsense_client' ),
						'slot'      => foxiz_get_option( 'amp_top_single_adsense_slot' ),
						'size'      => foxiz_get_option( 'amp_top_single_adsense_size' ),
						'custom'    => foxiz_get_option( 'amp_top_single_ad_code' ),
						'classname' => 'top-single-amp-ad amp-ad-wrap',
				] );
			}

			return;
		}

		$setting = rb_get_meta( 'entry_top', get_the_ID() );
		if ( ( empty( $setting ) || '-1' !== (string) $setting ) && is_active_sidebar( 'foxiz_entry_top' ) ) : ?>
			<div class="entry-top">
				<?php dynamic_sidebar( 'foxiz_entry_top' ); ?>
			</div>
		<?php endif;
	}
}

if ( ! function_exists( 'foxiz_single_entry_bottom' ) ) {
	function foxiz_single_entry_bottom() {

		if ( foxiz_is_amp() ) {
			if ( function_exists( 'foxiz_amp_ad' ) ) {
				foxiz_amp_ad( [
						'type'      => foxiz_get_option( 'amp_bottom_single_ad_type' ),
						'client'    => foxiz_get_option( 'amp_bottom_single_adsense_client' ),
						'slot'      => foxiz_get_option( 'amp_bottom_single_adsense_slot' ),
						'size'      => foxiz_get_option( 'amp_bottom_single_adsense_size' ),
						'custom'    => foxiz_get_option( 'amp_bottom_single_ad_code' ),
						'classname' => 'bottom-single-amp-ad amp-ad-wrap',
				] );
			}

			return;
		}

		$setting = rb_get_meta( 'entry_bottom', get_the_ID() );
		if ( ( empty( $setting ) || '-1' !== (string) $setting ) && is_active_sidebar( 'foxiz_entry_bottom' ) ) : ?>
			<div class="entry-bottom">
				<?php dynamic_sidebar( 'foxiz_entry_bottom' ); ?>
			</div>
		<?php endif;
	}
}

if ( ! function_exists( 'foxiz_single_highlights' ) ) {
	function foxiz_single_highlights() {

		$highlights = rb_get_meta( 'highlights', get_the_ID() );
		if ( ! is_array( $highlights ) || ! count( $highlights ) ) {
			return;
		}

		$highlight_heading  = foxiz_get_option( 'highlight_heading' );
		$layout             = foxiz_get_option( 'highlight_layout', 1 );
		$class_name         = ( '1' === (string) $layout ) ? 's-hl s-hl-1' : 's-hl s-hl-2';
		$heading_class_name = ( '1' === (string) $layout ) ? 's-hl-heading h1' : 's-hl-heading h3';
		$content_class_name = 's-hl-content ' . foxiz_get_option( 'highlight_size', 'h5' )
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<?php if ( ! empty( $highlight_heading ) ) : ?>
				<div class="<?php echo esc_attr( $heading_class_name ); ?>"><?php foxiz_render_inline_html( $highlight_heading ); ?></div>
			<?php endif; ?>
			<ul class="<?php echo esc_attr( $content_class_name ); ?>">
				<?php foreach ( $highlights as $data ) :
					if ( ! empty( $data['point'] ) ) : ?>
						<li class="hl-point"><?php foxiz_render_inline_html( $data['point'] ); ?></li>
					<?php endif;
				endforeach; ?>
			</ul>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_quick_info' ) ) {
	function foxiz_single_quick_info() {

		if ( ! foxiz_get_option( 'single_post_quick_view' ) || ! is_single() ) {
			return;
		}
		$post_id = get_the_ID();
		if ( foxiz_get_quick_view_sponsored( $post_id ) || foxiz_get_quick_view_review( $post_id ) ) { ?>
			<div class="sqview">
				<?php
				echo foxiz_get_quick_view_sponsored( $post_id );
				echo foxiz_get_quick_view_review( $post_id ); ?>
			</div>
		<?php }
	}
}

if ( ! function_exists( 'foxiz_get_quick_view_sponsored' ) ) {
	function foxiz_get_quick_view_sponsored( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		if ( ! foxiz_is_sponsored_post( $post_id ) ) {
			return false;
		}
		ob_start() ?>
		<div class="qview-box spon-qview">
			<?php echo foxiz_get_entry_sponsored( $post_id ); ?>
		</div>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_get_quick_view_review' ) ) {
	function foxiz_get_quick_view_review( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$settings = foxiz_get_review_settings( $post_id );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return false;
		}
		ob_start(); ?>
		<div class="qview-box review-quickview light-scheme">
			<?php if ( ! empty( $settings['image'] ) ) : ?>
				<?php if ( ! is_array( $settings['image'] ) ) :
					if ( wp_get_attachment_image( $settings['image'] ) ) {
						echo '<div class="review-bg">' . wp_get_attachment_image( $settings['image'], 'full' ) . '</div>';
					} else if ( ! empty( $settings['image']['url'] ) ) : ?>
						<div class="review-bg">
							<img loading="lazy" decoding="async" src="<?php echo esc_url( $settings['image']['url'] ); ?>" alt="<?php echo strip_tags( $settings['image']['url'] ); ?>" height="<?php echo strip_tags( $settings['image']['height'] ); ?>" width="<?php echo strip_tags( $settings['image']['width'] ); ?>">
						</div>
					<?php endif;
				endif;
			endif; ?>
			<div class="review-quickview-holder">
				<div class="review-quickview-inner">
					<div class="review-quickview-meta">
						<?php if ( ! empty( $settings['average'] ) ) : ?>
							<span class="meta-score h4"><?php foxiz_render_inline_html( $settings['average'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $settings['meta'] ) ) : ?>
							<span class="meta-text"><?php foxiz_render_inline_html( $settings['meta'] ); ?></span>
						<?php endif; ?>
					</div>
					<div class="review-heading">
						<?php if ( ! empty( $settings['title'] ) ) : ?>
							<span class="h3"><?php foxiz_render_inline_html( $settings['title'] ); ?></span>
						<?php endif;
						if ( 'star' === $settings['type'] )  :
							echo foxiz_get_review_stars( $settings['average'] );
						else:
							echo foxiz_get_review_line( $settings['average'] );
						endif;
						?>
					</div>
				</div>
				<?php if ( ! empty( $settings['button'] ) && ! empty( $settings['destination'] ) ) : ?>
					<a class="review-btn is-btn" href="<?php echo esc_url( $settings['destination'] ); ?>" target="_blank" rel="nofollow noreferrer"><?php foxiz_render_inline_html( $settings['button'] ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_single_video_embed' ) ) {
	function foxiz_single_video_embed( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$floating = foxiz_get_option( 'single_post_video_float' );

		if ( foxiz_is_amp() ) {
			$floating = false;
		}

		$classes = 'pvideo-embed';
		if ( $floating ) {
			$classes .= ' floating-video';
		}
		if ( foxiz_get_single_setting( 'video_autoplay' ) && ! get_query_var( 'rbsnp' ) ) {
			$classes .= ' is-autoplay';
		}
		if ( rb_get_meta( 'video_hosted', $post_id ) ) {
			$classes .= ' is-self-hosted';
		}

		if ( ! empty( foxiz_get_video_embed( $post_id ) ) ) : ?>
			<div class="<?php echo strip_tags( $classes ); ?>">
				<div class="embed-holder">
					<?php if ( $floating ) : ?>
						<div class="float-holder"><?php echo foxiz_get_video_embed( $post_id ); ?></div>
					<?php else :
						echo foxiz_get_video_embed( $post_id );
					endif; ?>
				</div>
			</div>
		<?php endif;
	}
}

if ( ! function_exists( 'foxiz_single_audio_embed' ) ) {
	function foxiz_single_audio_embed( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$autoplay = boolval( foxiz_get_single_setting( 'audio_autoplay' ) );
		if ( ! empty( foxiz_get_audio_embed( $post_id, $autoplay ) ) ) : ?>
			<div class="paudio-embed">
				<?php echo foxiz_get_audio_embed( $post_id, $autoplay ); ?>
			</div>
		<?php endif;
	}
}

if ( ! function_exists( 'foxiz_amp_gallery' ) ) {
	function foxiz_amp_gallery( $data, $crop_size ) { ?>
		<div class="amp-gallery-wrap">
			<amp-carousel async width="1240" height="695" layout="responsive" type="slides">
				<?php foreach ( $data as $attachment_id ) {
					$image = wp_get_attachment_image_src( $attachment_id, 'full' );
					if ( $image ) {
						[ $src, $width, $height ] = $image;
						$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
						echo '<amp-img src="' . esc_url( $src ) . '" ' . image_hwstring( $width, $height ) . ' alt="' . strip_tags( $alt ) . '"></amp-img>';
					}
				} ?>
			</amp-carousel>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_single_gallery_slider' ) ) {
	function foxiz_single_gallery_slider( $crop_size = 'full', $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$data = rb_get_meta( 'gallery_data', $post_id );
		$data = apply_filters( 'ruby_post_gallery_ids', $data, $post_id );

		if ( empty( $data ) ) {
			return;
		}
		$data = explode( ',', $data );

		/** amp */
		if ( foxiz_is_amp() ) {
			foxiz_amp_gallery( $data, $crop_size );

			return;
		}

		$total    = count( $data );
		$index    = 0;
		$lightbox = foxiz_get_option( 'single_post_gallery_lightbox' );

		if ( $lightbox ) {
			foxiz_add_gallery_lightbox_data( $data, $post_id );
		}
		?>
		<div class="featured-gallery-wrap format-gallery-slider" data-gallery="<?php echo strip_tags( $post_id ); ?>">
			<div id="gallery-slider-<?php echo strip_tags( $post_id ); ?>" class="swiper-container gallery-slider pre-load">
				<div class="swiper-wrapper">
					<?php foreach ( $data as $attachment_id ) : ?>
						<div class="swiper-slide">
							<?php if ( $lightbox ) : ?>
								<div class="gallery-popup-trigger slider-img-holder" data-index="<?php echo esc_attr( 'single_gallery_' . $post_id . '_' . $attachment_id ); ?>">
									<?php echo wp_get_attachment_image( $attachment_id, $crop_size ); ?>
								</div>
							<?php else : ?>
								<div class="slider-img-holder"><?php echo wp_get_attachment_image( $attachment_id, $crop_size ); ?></div>
							<?php endif; ?>
							<?php echo foxiz_get_attachment_caption( $attachment_id, 'slider-caption' ); ?>
						</div>
						<?php $index ++;
					endforeach; ?>
				</div>
				<div class="swiper-pagination swiper-pagination-<?php echo strip_tags( $post_id ); ?>"></div>
			</div>
			<div class="gallery-slider-nav-outer">
				<div class="gallery-slider-info">
					<?php foxiz_render_svg( 'gallery' ); ?>
					<div class="current-slider-info">
						<span class="h4"><?php echo foxiz_html__( 'List of Images', 'foxiz' ); ?></span>
						<span><span class="current-slider-count" data-total="<?php echo strip_tags( $total ); ?>">1</span><?php echo '/' . strip_tags( $total ); ?></span>
					</div>
				</div>
				<div class="gallery-slider-nav-holder">
					<div id="gallery-slider-nav-<?php echo strip_tags( $post_id ); ?>" class="swiper-container gallery-slider-nav">
						<div class="swiper-wrapper pre-load">
							<?php foreach ( $data as $attachment_id ) : ?>
								<div class="swiper-slide">
									<div class="slider-img-holder"><?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?></div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_gallery_carousel' ) ) {
	function foxiz_single_gallery_carousel( $crop_size = 'full', $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$data = rb_get_meta( 'gallery_data', $post_id );
		$data = apply_filters( 'ruby_post_gallery_ids', $data, $post_id );

		if ( empty( $data ) ) {
			return;
		}
		$data = explode( ',', $data );

		/** amp */
		if ( foxiz_is_amp() ) {
			foxiz_amp_gallery( $data, $crop_size );

			return;
		}
		$index    = 0;
		$lightbox = foxiz_get_option( 'single_post_gallery_lightbox' );
		if ( $lightbox ) {
			foxiz_add_gallery_lightbox_data( $data, $post_id );
		}
		?>
		<div class="featured-gallery-wrap format-gallery-carousel" data-gallery="<?php echo strip_tags( $post_id ); ?>">
			<div id="gallery-carousel-<?php echo strip_tags( $post_id ); ?>" class="swiper-container gallery-carousel pre-load">
				<div class="swiper-wrapper">
					<?php foreach ( $data as $attachment_id ) : ?>
						<div class="swiper-slide">
							<?php if ( $lightbox ) : ?>
								<div class="gallery-popup-trigger carousel-img-holder" data-index="<?php echo esc_attr( 'single_gallery_' . $post_id . '_' . $attachment_id ); ?>">
									<?php echo wp_get_attachment_image( $attachment_id, $crop_size ); ?>
								</div>
							<?php else : ?>
								<div class="carousel-img-holder"><?php echo wp_get_attachment_image( $attachment_id, $crop_size ); ?></div>
							<?php endif; ?>
							<?php echo foxiz_get_attachment_caption( $attachment_id, 'slider-caption' ); ?>
						</div>
						<?php $index ++;
					endforeach; ?>
				</div>
				<div class="swiper-scrollbar swiper-scrollbar-<?php echo strip_tags( $post_id ); ?>"></div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_gallery_coverflow' ) ) {
	function foxiz_single_gallery_coverflow( $crop_size = 'full', $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$data = rb_get_meta( 'gallery_data', $post_id );
		$data = apply_filters( 'ruby_post_gallery_ids', $data, $post_id );

		if ( empty( $data ) ) {
			return;
		}

		$data = explode( ',', $data );

		/** amp */
		if ( foxiz_is_amp() ) {
			foxiz_amp_gallery( $data, $crop_size );

			return;
		}

		$index    = 0;
		$lightbox = foxiz_get_option( 'single_post_gallery_lightbox' );
		if ( $lightbox ) {
			foxiz_add_gallery_lightbox_data( $data, $post_id );
		}
		?>
		<div class="featured-gallery-wrap format-gallery-coverflow" data-gallery="<?php echo strip_tags( $post_id ); ?>">
			<div id="gallery-coverflow-<?php echo strip_tags( $post_id ); ?>" class="swiper-container gallery-coverflow pre-load">
				<div class="swiper-wrapper pre-load">
					<?php foreach ( $data as $attachment_id ) : ?>
						<div class="swiper-slide">
							<?php if ( $lightbox ) : ?>
								<div class="gallery-popup-trigger coverflow-img-holder" data-index="<?php echo esc_attr( 'single_gallery_' . $post_id . '_' . $attachment_id ); ?>">
									<?php echo wp_get_attachment_image( $attachment_id, $crop_size ); ?>
								</div>
							<?php else : ?>
								<div class="coverflow-img-holder"><?php echo wp_get_attachment_image( $attachment_id, $crop_size ); ?></div>
							<?php endif; ?>
						</div>
						<?php
						$index ++;
					endforeach; ?>
				</div>
				<div class="swiper-pagination swiper-pagination-<?php echo strip_tags( $post_id ); ?>"></div>
			</div>
		</div>
		<?php
	}
}

/**
 * @param $data
 * @param $post_id
 */
if ( ! function_exists( 'foxiz_add_gallery_lightbox_data' ) ) {
	function foxiz_add_gallery_lightbox_data( $data, $post_id ) {

		if ( ! isset( $GLOBALS['foxiz_galleries_data'] ) || ! is_array( $GLOBALS['foxiz_galleries_data'] ) ) {
			$GLOBALS['foxiz_galleries_data'] = [];
		}

		$items = [];
		foreach ( $data as $attachment_id ) {
			$key        = 'single_gallery_' . $post_id . '_' . $attachment_id;
			$attachment = get_post( $attachment_id );

			if ( $attachment ) {
				$items[ $key ] = [
						'id'          => $attachment_id,
						'key'         => $key,
						'title'       => get_the_title( $attachment ),
						'image'       => wp_get_attachment_image( $attachment_id, 'full' ),
						'excerpt'     => ! empty( $attachment->post_excerpt ) ? foxiz_strip_tags( $attachment->post_excerpt ) : '',
						'description' => ! empty( $attachment->post_content ) ? foxiz_strip_tags( $attachment->post_content ) : '',
				];
			}
		}

		$GLOBALS['foxiz_galleries_data'] = array_merge( $GLOBALS['foxiz_galleries_data'], $items );
	}
}

if ( ! function_exists( 'foxiz_get_single_breadcrumb' ) ) {
	/**
	 * @param string $prefix
	 *
	 * @return false|string
	 */
	function foxiz_get_single_breadcrumb( $prefix = 'single_post' ) {

		if ( ! foxiz_get_option( $prefix . '_breadcrumb' ) ) {
			return false;
		}

		return foxiz_get_breadcrumb( 's-breadcrumb' );
	}
}

if ( ! function_exists( 'foxiz_single_breadcrumb' ) ) {
	/**
	 * @param string $prefix
	 */
	function foxiz_single_breadcrumb( $prefix = 'single_post' ) {

		echo foxiz_get_single_breadcrumb( $prefix );
	}
}

if ( ! function_exists( 'foxiz_single_sticky' ) ) {
	function foxiz_single_sticky() {

		if ( foxiz_get_option( 'single_post_sticky_title' ) && is_single() && ! foxiz_is_amp() ) {
			foxiz_single_sticky_html();
		}
	}
}

if ( ! function_exists( 'foxiz_single_sticky_html' ) ) {
	function foxiz_single_sticky_html() {

		$post_id = get_queried_object_id();
		?>
		<div id="s-title-sticky" class="s-title-sticky">
			<div class="s-title-sticky-left">
				<span class="sticky-title-label"><?php foxiz_html_e( 'Reading:', 'foxiz' ); ?></span>
				<span class="h4 sticky-title"><?php echo get_the_title( $post_id ); ?></span>
			</div>
			<?php foxiz_single_share_sticky( $post_id ); ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_single_share_sticky' ) ) {
	function foxiz_single_share_sticky( $post_id ) {

		if ( ! foxiz_get_option( 'share_sticky' ) || ! function_exists( 'foxiz_render_share_list' ) || foxiz_is_amp() || empty( $post_id ) ) {
			return;
		}

		$settings = [
				'facebook'  => foxiz_get_option( 'share_sticky_facebook' ),
				'twitter'   => foxiz_get_option( 'share_sticky_twitter' ),
				'flipboard' => foxiz_get_option( 'share_sticky_flipboard' ),
				'pinterest' => foxiz_get_option( 'share_sticky_pinterest' ),
				'whatsapp'  => foxiz_get_option( 'share_sticky_whatsapp' ),
				'linkedin'  => foxiz_get_option( 'share_sticky_linkedin' ),
				'tumblr'    => foxiz_get_option( 'share_sticky_tumblr' ),
				'reddit'    => foxiz_get_option( 'share_sticky_reddit' ),
				'vk'        => foxiz_get_option( 'share_sticky_vk' ),
				'telegram'  => foxiz_get_option( 'share_sticky_telegram' ),
				'threads'   => foxiz_get_option( 'share_sticky_threads' ),
				'bsky'      => foxiz_get_option( 'share_sticky_bsky' ),
				'email'     => foxiz_get_option( 'share_sticky_email' ),
				'copy'      => foxiz_get_option( 'share_sticky_copy' ),
				'print'     => foxiz_get_option( 'share_sticky_print' ),
				'native'    => foxiz_get_option( 'share_sticky_native' ),
		];

		if ( ! array_filter( $settings ) ) {
			return;
		}
		$settings['post_id']       = $post_id;
		$settings['tipsy_gravity'] = 'n';

		$class_name = 'sticky-share-list-items effect-fadeout';
		if ( foxiz_get_option( 'share_sticky_color' ) ) {
			$class_name .= ' is-color';
		}
		?>
		<div class="sticky-share-list">
			<div class="t-shared-header meta-text">
				<i class="rbi rbi-share" aria-hidden="true"></i><?php if ( foxiz_get_option( 'share_sticky_label' ) ) : ?>
					<span class="share-label"><?php foxiz_html_e( 'Share', 'foxiz' ); ?></span><?php endif; ?>
			</div>
			<div class="<?php echo strip_tags( $class_name ); ?>"><?php foxiz_render_share_list( $settings ); ?></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_reading_process_indicator' ) ) {
	function foxiz_reading_process_indicator() {

		if ( ! is_single() || ! foxiz_get_option( 'single_post_reading_indicator' ) || foxiz_is_amp() ) {
			return;
		}
		?>
		<div class="reading-indicator"><span id="reading-progress"></span></div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_single_page_selected' ) ) {
	function foxiz_single_page_selected() {

		echo foxiz_get_single_page_selected();
	}
}

if ( ! function_exists( 'foxiz_get_single_page_selected' ) ) {
	function foxiz_get_single_page_selected() {

		if ( foxiz_is_amp() ) {
			return false;
		}

		global $page, $numpages, $multipage, $more;

		$prev = $page - 1;
		$next = $page + 1;

		$headings = rb_get_meta( 'page_selected' );

		if ( ! $multipage || ! is_array( $headings ) || count( $headings ) < $numpages ) {
			return false;
		}

		$output = '<div class="page-selected-outer">';
		$output .= '<div class="page-selected-title meta-text"><span>' . foxiz_html__( 'Section', 'foxiz' ) . '</span></div>';
		$output .= '<div class="page-selected">';
		$output .= '<div class="page-selected-current">';
		if ( ! empty( $headings[ $prev ]['title'] ) ) {
			$output .= '<span class="h4">' . foxiz_strip_tags( $page . ' - ' . $headings[ $prev ]['title'] ) . '</span>';
		}
		$output .= '</div>';
		$output .= '<div class="page-selected-list">';
		$output .= '<div class="page-selected-list-inner">';
		for ( $i = 1; $i <= $numpages; $i ++ ) {
			$link  = '';
			$index = $i - 1;
			if ( ! empty( $headings[ $index ]['title'] ) ) {
				$link = $i . ' - ' . foxiz_strip_tags( $headings[ $index ]['title'] );
			}
			if ( $i !== $page || ! $more && 1 === $page ) {
				$link = '<div class="page-list-item h4">' . _wp_link_page( $i ) . $link . '</a></div>';
			} elseif ( $i === $page ) {
				$link = '<div class="page-list-item h4"><span class="post-page-numbers current">' . $link . '</span></div>';
			}
			$output .= $link;
		}
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		$output .= '<div class="page-selected-nav page-links">';
		$output .= '<div class="text-link-prev">';
		if ( $prev > 0 ) {
			$output .= _wp_link_page( $prev ) . '<i class="rbi rbi-cleft" aria-hidden="true"></i></a>';
		} else {
			$output .= '<span class="post-page-numbers empty-link"><i class="rbi rbi-cleft" aria-hidden="true"></i></span>';
		}
		$output .= '</div>';

		$output .= '<div class="text-link-next">';
		if ( $next <= $numpages ) {
			$output .= _wp_link_page( $next ) . '<i class="rbi rbi-cright" aria-hidden="true"></i></a>';
		} else {
			$output .= '<span class="post-page-numbers empty-link"><i class="rbi rbi-cright" aria-hidden="true"></i></span>';
		}
		$output .= '</div>';
		$output .= '</div>';

		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_single_inline_ad' ) ) {
	function foxiz_get_single_inline_ad( $prefix = 'ad_single_' ) {

		if ( ! foxiz_get_option( $prefix . 'code' ) && ! foxiz_get_option( $prefix . 'image' ) ) {
			return false;
		}

		$classes = 'inline-single-ad ' . $prefix . 'index align' . foxiz_get_option( $prefix . 'align' );

		if ( foxiz_get_option( $prefix . 'type' ) ) {
			$settings = [
					'code'         => foxiz_get_option( $prefix . 'code' ),
					'description'  => foxiz_get_option( $prefix . 'description' ),
					'size'         => foxiz_get_option( $prefix . 'size' ),
					'desktop_size' => foxiz_get_option( $prefix . 'desktop_size' ),
					'tablet_size'  => foxiz_get_option( $prefix . 'tablet_size' ),
					'mobile_size'  => foxiz_get_option( $prefix . 'mobile_size' ),
					'no_spacing'   => 1,
			];
			if ( foxiz_get_adsense( $settings ) ) {
				return '<div class="' . strip_tags( $classes ) . '">' . foxiz_get_adsense( $settings ) . '</div>';
			}
		} else {
			$settings = [
					'description' => foxiz_get_option( $prefix . 'description' ),
					'image'       => foxiz_get_option( $prefix . 'image' ),
					'dark_image'  => foxiz_get_option( $prefix . 'dark_image' ),
					'destination' => foxiz_get_option( $prefix . 'destination' ),
					'no_spacing'  => 1,
			];
			if ( foxiz_get_ad_image( $settings ) ) {
				return '<div class="' . strip_tags( $classes ) . '">' . foxiz_get_ad_image( $settings ) . '</div>';
			}
		}
	}
}

if ( ! function_exists( 'foxiz_get_single_inline_amp_ad' ) ) {
	function foxiz_get_single_inline_amp_ad() {

		if ( ! function_exists( 'foxiz_amp_ad' ) ) {
			return false;
		}
		ob_start();

		foxiz_amp_ad( [
				'type'      => foxiz_get_option( 'amp_inline_single_ad_type' ),
				'client'    => foxiz_get_option( 'amp_inline_single_adsense_client' ),
				'slot'      => foxiz_get_option( 'amp_inline_single_adsense_slot' ),
				'size'      => foxiz_get_option( 'amp_inline_single_adsense_size' ),
				'custom'    => foxiz_get_option( 'amp_inline_single_ad_code' ),
				'classname' => 'inline-single-amp-ad amp-ad-wrap',
		] );

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_add_single_inline_ad' ) ) {
	function foxiz_add_single_inline_ad( $data ) {

		if ( empty( $data ) ) {
			$data = [];
		}

		if ( ! is_single() || ( is_singular( 'podcast' ) && ! foxiz_get_option( 'podcast_inline_ad' ) ) ) {
			return $data;
		}

		/** amp inline ad */
		if ( foxiz_is_amp() && foxiz_get_single_inline_amp_ad() ) {

			$positions = foxiz_get_option( 'amp_ad_single_positions' );

			if ( empty( $positions ) ) {
				$positions = [ 4 ];
			} else {
				$positions = explode( ',', $positions );
			}

			array_push( $data, [
					'render'    => foxiz_get_single_inline_amp_ad(),
					'positions' => $positions,
			] );

			return $data;
		}

		$entry_ad_1  = rb_get_meta( 'entry_ad_1' );
		$positions_1 = foxiz_get_option( 'ad_single_positions' );

		$entry_ad_2  = rb_get_meta( 'entry_ad_2' );
		$positions_2 = foxiz_get_option( 'ad_single_2_positions' );

		$entry_ad_3  = rb_get_meta( 'entry_ad_3' );
		$positions_3 = foxiz_get_option( 'ad_single_3_positions' );

		if ( ( empty( $entry_ad_1 ) || '-1' !== (string) $entry_ad_1 ) && ! empty( $positions_1 ) ) {
			$render = foxiz_get_single_inline_ad();
			if ( ! empty( $render ) ) {
				array_push( $data, [
						'render'    => $render,
						'positions' => array_map( 'intval', explode( ',', $positions_1 ) ),
				] );
			}
		}

		if ( ( empty( $entry_ad_2 ) || '-1' !== (string) $entry_ad_2 ) && ! empty( $positions_2 ) ) {
			$render = foxiz_get_single_inline_ad( 'ad_single_2_' );
			if ( ! empty( $render ) ) {
				array_push( $data, [
						'render'    => $render,
						'positions' => array_map( 'intval', explode( ',', $positions_2 ) ),
				] );
			}
		}

		if ( ( empty( $entry_ad_3 ) || '-1' !== (string) $entry_ad_3 ) && ! empty( $positions_3 ) ) {
			$render = foxiz_get_single_inline_ad( 'ad_single_3_' );
			if ( ! empty( $render ) ) {
				array_push( $data, [
						'render'    => $render,
						'positions' => array_map( 'intval', explode( ',', $positions_3 ) ),
				] );
			}
		}

		return $data;
	}
}

if ( ! function_exists( 'foxiz_single_live_blog_header' ) ) {
	function foxiz_single_live_blog_header() {

		if ( ! foxiz_is_live_blog() ) {
			return;
		}

		$total = get_post_meta( get_the_ID(), 'ruby_total_live_blocks', true );
		if ( 1 === (int) $total ) {
			$total_label = foxiz_html__( 'Post', 'foxiz' );
		} else {
			$total_label = foxiz_html__( 'Posts', 'foxiz' );
		} ?>
		<div class="live-blog-interval">
			<div class="live-blog-total meta-bold">
				<i class="rbi rbi-live"></i><span class="live-count"><?php echo strip_tags( $total ); ?></span><span class="live-count-label"><?php echo strip_tags( $total_label ); ?></span>
			</div>
			<?php if ( ! foxiz_is_amp() ) : ?>
				<div class="live-interval">
					<span class="live-interval-description meta-text"><?php foxiz_html_e( 'Auto Updates', 'foxiz' ); ?></span>
					<label for="live-interval-switcher" class="rb-switch">
						<input id="live-interval-switcher" type="checkbox" class="rb-switch-input" checked="checked">
						<span class="rb-switch-slider"></span>
					</label>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_get_disclosure_box' ) ) {
	function foxiz_get_disclosure_box() {

		$content = foxiz_get_option( 'disclosure_content' );
		$setting = foxiz_get_single_setting( 'disclosure_condition' );

		if ( empty( $content ) || ! $setting ) {
			return false;
		}

		return '<div class="reader-disclosure meta-text is-layout-' . foxiz_get_option( 'disclosure_layout', 'text' ) . '">' . foxiz_strip_tags( $content ) . '</div>';
	}
}

if ( ! function_exists( 'foxiz_disclosure_box' ) ) {
	function foxiz_disclosure_box() {

		echo foxiz_get_disclosure_box();
	}
}

if ( ! function_exists( 'foxiz_get_author_lightbox' ) ) {
	function foxiz_get_author_lightbox( $author_id = '' ) {

		if ( ! $author_id ) {
			return false;
		}

		$output             = '';
		$social_list        = foxiz_get_social_list( foxiz_get_user_socials( $author_id ), true, false );
		$author_description = get_the_author_meta( 'description', $author_id );
		$author_job         = get_the_author_meta( 'job', $author_id );

		if ( empty( $social_list ) && empty( $author_description ) && empty( $author_job ) ) {
			return false;
		}

		$author_url      = get_author_posts_url( $author_id );
		$author_name     = get_the_author_meta( 'display_name', $author_id );
		$author_image_id = (int) get_the_author_meta( 'author_image_id', $author_id );

		$output .= '<div class="ulightbox"><div class="ulightbox-inner">';
		$output .= '<div class="ubox-header">';
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
			$output .= '<a class="nice-name" rel="nofollow" href="' . $author_url . '">' . $author_name . '</a>';
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
		if ( ! empty( $author_description ) ) {
			$output .= '<div class="bio-description">' . wp_trim_words( $author_description, 26, '...' ) . '</div>';
		}
		if ( ! empty( $social_list ) ) {
			$output .= '<div class="ulightbox-footer usocials tooltips-n meta-text">';
			$output .= '<span class="ef-label">' . foxiz_html__( 'Follow: ', 'foxiz' ) . '</span>';
			$output .= $social_list;
			$output .= '</div>';
		}
		$output .= '</div></div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_entry_meta_author_single' ) ) {
	function foxiz_entry_meta_author_single( $settings = [] ) {

		$post_id = get_the_ID();

		/** multiple authors supported */
		if ( function_exists( 'get_post_authors' ) ) {
			$author_data = get_post_authors( $post_id );
			if ( is_array( $author_data ) && count( $author_data ) >= 1 ) {
				foxiz_entry_meta_authors_single( $settings, $author_data );

				return;
			}
		}

		$author_id    = get_post_field( 'post_author', $post_id );
		$bio_lightbox = get_the_author_meta( 'author_bio_lightbox', $author_id );
		if ( empty( $bio_lightbox ) ) {
			$bio_lightbox = foxiz_get_option( 'author_bio_lightbox' );
		}

		$classes      = [ 'meta-el' ];
		$p_label      = '';
		$s_label      = ! empty( $settings['s_label_author'] ) ? $settings['s_label_author'] : '';
		$bio_lightbox = ! empty( $bio_lightbox ) && ( '-1' !== (string) $bio_lightbox );
		$author_job   = ! empty( $settings['has_author_job'] ) ? get_the_author_meta( 'job', $author_id ) : '';

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
			if ( $bio_lightbox ) {
				echo '<div class="ulightbox-holder">';
				echo '<a class="meta-author-url meta-author" href="' . get_author_posts_url( $author_id ) . '">' . get_the_author_meta( 'display_name', $author_id ) . '</a>';
				echo foxiz_get_author_lightbox( $author_id );
				echo '</div>';
			} else {
				echo '<a class="meta-author-url meta-author" href="' . get_author_posts_url( $author_id ) . '">' . get_the_author_meta( 'display_name', $author_id ) . '</a>';
			}
			if ( $s_label ) : ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $s_label ); ?></span>
			<?php elseif ( ! empty( $author_job ) ) : ?>
				<span class="meta-label meta-job">- <?php echo esc_html( $author_job ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_entry_meta_authors_single' ) ) {
	function foxiz_entry_meta_authors_single( $settings, $author_data = [] ) {

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
		$is_show_job = ( count( $author_data ) == 1 );

		$classes[] = 'meta-el co-authors';
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

			foreach ( $author_data as $author ) :
				$author_id = $author->ID;

				$bio_lightbox = get_the_author_meta( 'author_bio_lightbox', $author_id );
				if ( empty( $bio_lightbox ) ) {
					$bio_lightbox = foxiz_get_option( 'author_bio_lightbox' );
				}
				$bio_lightbox = ! empty( $bio_lightbox ) && ( '-1' !== (string) $bio_lightbox );
				$author_job   = ( ! empty( $settings['has_author_job'] ) && $is_show_job ) ? get_the_author_meta( 'job', $author_id ) : '';
				?>
				<div class="meta-separate">
					<?php if ( $bio_lightbox ) {
						echo '<div class="ulightbox-holder">';
						echo '<a class="meta-author-url meta-author" href="' . get_author_posts_url( $author_id ) . '">' . get_the_author_meta( 'display_name', $author_id ) . '</a>';
						echo foxiz_get_author_lightbox( $author_id );
						echo '</div>';
					} else {
						echo '<a class="meta-author-url meta-author" href="' . get_author_posts_url( $author_id ) . '">' . get_the_author_meta( 'display_name', $author_id ) . '</a>';
					}
					if ( $author_job ) : ?>
						<span class="meta-label meta-job">- <?php echo esc_html( $author_job ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach;
			if ( $s_label ) : ?>
				<span class="meta-label"><?php foxiz_render_inline_html( $s_label ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}
}