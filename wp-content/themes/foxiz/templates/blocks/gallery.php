<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'foxiz_get_simple_gallery' ) ) {
	/**
	 * @param array $settings
	 * @param null $_query
	 *
	 * @return false|string
	 */
	function foxiz_get_simple_gallery( $settings = [], $_query = null ) {

		$settings = wp_parse_args( $settings, [
			'uuid' => '',
			'name' => 'simple_gallery',
		] );

		if ( empty( $settings['columns'] ) ) {
			$settings['columns'] = 3;
		}
		if ( empty( $settings['column_gap'] ) ) {
			$settings['column_gap'] = 20;
		}

		if ( ! empty( $settings['center_mode'] ) && ( '-1' === (string) $settings['center_mode'] ) ) {
			$settings['center_mode'] = false;
		}

		$settings['classes'] = 'block-simple-gallery';

		if ( ! empty( $settings['image_style'] ) ) {
			$settings['classes'] .= ' is-style-' . $settings['image_style'];
		}

		if ( ! empty( $settings['feat_lazyload'] ) && strpos( $settings['feat_lazyload'], 'e-' ) !== false ) {
			$settings['eager_images'] = (int) str_replace( 'e-', '', $settings['feat_lazyload'] );
		}

		$is_lazy    = foxiz_get_option( 'lazy_load' );
		$loop_index = 1;

		ob_start();
		foxiz_block_open_tag( $settings );
		foxiz_block_inner_open_tag( $settings );
		if ( is_array( $settings['gallery_data'] ) ) {
			foreach ( $settings['gallery_data'] as $item ) {
				if ( ! empty( $settings['eager_images'] ) ) {
					$settings['feat_lazyload'] = ( $loop_index <= $settings['eager_images'] ) ? 'none' : '1';
					$loop_index ++;
				}
				if ( ! empty( $settings['feat_lazyload'] ) ) {
					$is_lazy = ( 'none' === $settings['feat_lazyload'] ) ? false : true;
				}
				echo foxiz_get_simple_gallery_item( $item, $is_lazy );
			}
		}
		foxiz_block_inner_close_tag( $settings );
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_get_simple_gallery_item' ) ) {
	function foxiz_get_simple_gallery_item( $item = [], $is_lazy = true ) {

		$output = '<div class="simple-gallery-item">';
		if ( ! empty( $item['image']['id'] ) ) {
			$alt_text = trim( get_post_meta( $item['image']['id'], '_wp_attachment_image_alt', true ) );
			if ( empty( $alt_text ) && ! empty( $item['title'] ) ) {
				$alt_text = trim( $item['title'] );
			}
			$attrs = [ 'alt' => esc_attr( $alt_text ) ];
			if ( ! foxiz_is_amp() ) {
				if ( ! $is_lazy ) {
					$attrs['fetchpriority'] = 'high';
					$attrs['loading']       = 'eager';
				} else {
					$attrs['loading'] = 'lazy';
				}
			}
			$image = wp_get_attachment_image( $item['image']['id'], 'full', false, $attrs );
		} elseif ( ! empty( $image['url'] ) ) {
			$attr  = $is_lazy ? 'loading="lazy"' : 'loading="eager" fetchpriority="high"';
			$image = '<img src="' . $image['url'] . '" alt="' . esc_html__( 'gallery image', 'foxiz' ) . '" ' . $attr . '>';
		}
		if ( ! empty( $image ) ) {
			$output .= '<div class="simple-gallery-image">';
			if ( ! empty( $item['link']['url'] ) ) {
				$output .= foxiz_render_elementor_link( $item['link'], $image, '', $item['title'] );
			} else {
				$output .= $image;
			}
			if ( ! empty( $item['meta'] ) ) {
				$output .= '<div class="p-categories simple-gallery-meta">' . esc_html( $item['meta'] ) . '</div>';
			}
			$output .= '</div>';
		}
		if ( ! empty( $item['title'] ) ) {
			$output .= '<span class="simple-gallery-title h4">';
			if ( ! empty( $item['link']['url'] ) ) {
				$output .= foxiz_render_elementor_link( $item['link'], esc_html( $item['title'] ) );
			} else {
				$output .= foxiz_strip_tags( $item['title'] );
			}
			$output .= '</span>';
		}

		if ( ! empty( $item['description'] ) ) {
			$output .= '<span class="simple-gallery-desc">';
			$output .= foxiz_strip_tags( $item['description'] );
			$output .= '</span>';
		}

		$output .= '</div>';

		return $output;
	}
}


if ( ! function_exists( 'foxiz_get_lightbox_gallery' ) ) {
	function foxiz_get_lightbox_gallery( $settings = [], $_query = null ) {

		$settings = wp_parse_args( $settings, [
			'uuid' => '',
			'name' => 'lightbox_gallery',
		] );

		if ( empty( $settings['columns'] ) ) {
			$settings['columns'] = 3;
		}
		if ( empty( $settings['column_gap'] ) ) {
			$settings['column_gap'] = 20;
		}

		if ( ! empty( $settings['center_mode'] ) && ( '-1' === (string) $settings['center_mode'] ) ) {
			$settings['center_mode'] = false;
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'full';
		}
		$settings['classes'] = 'block-lightbox-gallery';

		if ( ! empty( $settings['image_style'] ) ) {
			$settings['classes'] .= ' is-style-' . $settings['image_style'];
		}

		if ( ! empty( $settings['content_layout'] ) && 'overlay' === $settings['content_layout'] ) {
			$settings['classes'] .= ' gallery-overlay-content';
		}

		if ( ! empty( $settings['feat_lazyload'] ) && strpos( $settings['feat_lazyload'], 'e-' ) !== false ) {
			$settings['eager_images'] = (int) str_replace( 'e-', '', $settings['feat_lazyload'] );
		}
		$is_lazy       = foxiz_get_option( 'lazy_load' );
		$items         = [];
		$uuid          = ! empty( $settings['uuid'] ) ? $settings['uuid'] : 'u0';
		$loop_index    = 1;
		$gallery_index = 1;

		$output = foxiz_get_block_open_tag( $settings, null );

		if ( ! empty( $settings['grid_layout'] ) && 'flex' === $settings['grid_layout'] ) {
			$output .= '<div class="block-inner">';
		} else {
			$output .= '<div class="pure-masonry">';
		}

		if ( is_array( $settings['gallery_data'] ) ) {
			foreach ( $settings['gallery_data'] as $item ) {

				if ( empty( $item['image']['id'] ) ) {
					continue;
				}

				$key = $uuid . '_' . $item['image']['id'] . '_' . $gallery_index;

				$data = [ 'key' => $key ];

				$attachment_id  = $item['image']['id'];
				$content_buffer = '';

				/** Check lazyload */
				if ( ! empty( $settings['eager_images'] ) ) {
					$settings['feat_lazyload'] = ( $loop_index <= $settings['eager_images'] ) ? 'none' : '1';
					$loop_index ++;
				}

				if ( ! empty( $settings['feat_lazyload'] ) ) {
					$is_lazy = ( 'none' === $settings['feat_lazyload'] ) ? false : true;
				}
				$data['image']   = wp_get_attachment_image( $attachment_id, 'full' );
				$data['title']   = ! empty( $item['title'] ) ? foxiz_strip_tags( $item['title'] ) : get_the_title( $attachment_id );
				$data['excerpt'] = ! empty( $item['excerpt'] ) ? foxiz_strip_tags( $item['excerpt'] ) : wp_get_attachment_caption( $attachment_id );

				if ( empty( $item['description'] ) ) {
					$attachment          = get_post( $attachment_id );
					$data['description'] = foxiz_strip_tags( $attachment->post_content );
				} else {
					$data['description'] = foxiz_strip_tags( $item['description'] );
				}
				if ( ! empty( $data['excerpt'] ) ) {
					$content_buffer .= '<div class="h4 gallery-item-excerpt">' . $data['excerpt'] . '</div>';
				}
				if ( ! empty( $data['description'] ) ) {
					$content_buffer .= '<div class="gallery-item-desc description-text is-excerpt-color">' . $data['description'] . '</div>';
				}

				$output .= '<div class="lightbox-gallery-outer elementor-repeater-item-' . ( ! empty( $item['_id'] ) ? esc_attr( $item['_id'] ) : 'default' ) . '">';
				$output .= '<div class="p-wrap gallery-popup-trigger lightbox-gallery-item" data-index="' . esc_attr( $key ) . '">';
				$output .= '<div class="p-featured">';
				if ( ! $is_lazy ) {
					$output .= wp_get_attachment_image( $attachment_id, $settings['crop_size'], false, [ 'loading' => 'eager' ] );
				} else {
					$output .= wp_get_attachment_image( $attachment_id, $settings['crop_size'], false, [ 'loading' => 'lazy' ] );
				}
				$output .= '</div>';

				if ( ! empty( $content_buffer ) ) {
					$output .= '<div class="gallery-item-content">' . $content_buffer . '</div>';
				}
				$output .= '</div>';
				$output .= '</div>';

				$items[ $key ] = $data;
				$gallery_index ++;
			}
		}

		$output .= '</div>';
		$output .= '</div>';

		// Initialize the global gallery lightbox data if not already set
		if ( ! isset( $GLOBALS['foxiz_galleries_data'] ) || ! is_array( $GLOBALS['foxiz_galleries_data'] ) ) {
			$GLOBALS['foxiz_galleries_data'] = [];
		}

		// Support multiple galleries simultaneously in the popup
		// Merge new gallery items into the existing global dataset
		$GLOBALS['foxiz_galleries_data'] = array_merge( $GLOBALS['foxiz_galleries_data'], $items );

		return $output;

	}
}