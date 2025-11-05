<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_grid_1' ) ) {
	function foxiz_grid_1( $settings = [] ) {

		$settings['post_classes'] = 'p-grid p-grid-1';
		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g2';
		}

		foxiz_post_open_tag( $settings );
		foxiz_featured_with_category( $settings );
		foxiz_entry_title( $settings );
		foxiz_entry_review( $settings );
		foxiz_entry_excerpt( $settings );
		foxiz_entry_meta( $settings );
		foxiz_entry_readmore( $settings );
		foxiz_post_close_tag();
	}
}

if ( ! function_exists( 'foxiz_grid_2' ) ) {
	function foxiz_grid_2( $settings = [] ) {

		$settings['post_classes'] = 'p-grid p-grid-2';
		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g2';
		}
		foxiz_post_open_tag( $settings );
		foxiz_featured_only( $settings );
		foxiz_entry_top( $settings );
		foxiz_entry_title( $settings );
		foxiz_entry_review( $settings );
		foxiz_entry_excerpt( $settings );
		foxiz_entry_meta( $settings );
		foxiz_entry_readmore( $settings );
		foxiz_post_close_tag();
	}
}

if ( ! function_exists( 'foxiz_grid_small_1' ) ) {
	/**
	 * @param $settings
	 */
	function foxiz_grid_small_1( $settings = [] ) {

		$settings['post_classes'] = 'p-grid p-grid-small-1';
		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h4';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g1';
		}

		foxiz_post_open_tag( $settings );
		foxiz_featured_with_category( $settings );
		?>
		<div class="p-content">
			<?php
			foxiz_entry_title( $settings );
			foxiz_entry_review( $settings );
			foxiz_entry_excerpt( $settings );
			foxiz_entry_meta( $settings );
			foxiz_entry_readmore( $settings );
			?>
		</div>
		<?php
		foxiz_post_close_tag();
	}
}

if ( ! function_exists( 'foxiz_grid_box_1' ) ) {
	function foxiz_grid_box_1( $settings = [] ) {

		if ( empty( $settings['box_style'] ) ) {
			$settings['box_style'] = 'bg';
		}
		$settings['post_classes'] = 'p-grid p-box p-grid-box-1 box-' . $settings['box_style'];

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g2';
		}

		foxiz_post_open_tag( $settings ); ?>
		<div class="grid-box">
			<?php foxiz_featured_with_category( $settings );
			foxiz_entry_title( $settings );
			foxiz_entry_review( $settings );
			foxiz_entry_excerpt( $settings );
			foxiz_entry_meta( $settings );
			foxiz_entry_readmore( $settings );
			?>
		</div>
		<?php foxiz_post_close_tag();
	}
}

if ( ! function_exists( 'foxiz_grid_box_2' ) ) {
	function foxiz_grid_box_2( $settings = [] ) {

		$settings['post_classes'] = 'p-grid p-box p-grid-box-2 box-' . $settings['box_style'];

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g2';
		}

		foxiz_post_open_tag( $settings );
		?>
		<div class="grid-box">
			<?php foxiz_featured_only( $settings );
			foxiz_entry_top( $settings );
			foxiz_entry_title( $settings );
			foxiz_entry_review( $settings );
			foxiz_entry_excerpt( $settings );
			foxiz_entry_meta( $settings );
			foxiz_entry_readmore( $settings );
			?>
		</div>
		<?php foxiz_post_close_tag();
	}
}

if ( ! function_exists( 'foxiz_grid_flex_1' ) ) {
	function foxiz_grid_flex_1( $settings = [] ) {

		if ( empty( $settings['block_structure'] ) || ! is_array( $settings['block_structure'] ) ) {
			return;
		}

		$settings['post_classes'] = 'p-grid p-grid-1';
		if ( ! empty( $settings['box_style'] ) ) {
			$settings['post_classes'] = 'p-box p-grid-box-1 box-' . $settings['box_style'];
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g2';
		}

		foxiz_post_open_tag( $settings );
		if ( ! empty( $settings['box_style'] ) ) {
			echo '<div class="grid-box">';
		}
		foreach ( $settings['block_structure'] as $element ) :
			switch ( $element ) {
				case 'thumbnail' :
					foxiz_featured_with_category( $settings );
					break;
				case 'title' :
					foxiz_entry_title( $settings );
					break;
				case 'excerpt' :
					foxiz_entry_excerpt( $settings );
					break;
				case 'meta' :
					foxiz_entry_meta( $settings );
					break;
				case 'review' :
					echo foxiz_get_entry_review( $settings );
					break;
				case 'readmore' :
					foxiz_entry_readmore( $settings );
					break;
				case 'divider' :
					foxiz_entry_divider( $settings );
					break;
				case 'images' :
					foxiz_entry_teaser_images( $settings );
					break;
				default:
					break;
			}
		endforeach;
		if ( ! empty( $settings['box_style'] ) ) {
			echo '</div>';
		}
		foxiz_post_close_tag();
	}
}

if ( ! function_exists( 'foxiz_grid_flex_2' ) ) {
	function foxiz_grid_flex_2( $settings = [] ) {

		if ( empty( $settings['block_structure'] ) || ! is_array( $settings['block_structure'] ) ) {
			return;
		}
		$settings['post_classes'] = 'p-grid p-grid-2';
		if ( ! empty( $settings['box_style'] ) ) {
			$settings['post_classes'] = 'p-box p-grid-box-2 box-' . $settings['box_style'];
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g2';
		}

		foxiz_post_open_tag( $settings );
		if ( ! empty( $settings['box_style'] ) ) {
			echo '<div class="grid-box">';
		}
		foreach ( $settings['block_structure'] as $element ) :
			switch ( $element ) {
				case 'thumbnail' :
					foxiz_featured_only( $settings );
					break;
				case 'category' :
					foxiz_entry_top( $settings );
					break;
				case 'title' :
					foxiz_entry_title( $settings );
					break;
				case 'excerpt' :
					foxiz_entry_excerpt( $settings );
					break;
				case 'meta' :
					foxiz_entry_meta( $settings );
					break;
				case 'review' :
					echo foxiz_get_entry_review( $settings );
					break;
				case 'readmore' :
					foxiz_entry_readmore( $settings );
					break;
				case 'divider' :
					foxiz_entry_divider( $settings );
					break;
				case 'images' :
					foxiz_entry_teaser_images( $settings );
					break;
				default:
					break;
			}
		endforeach;
		if ( ! empty( $settings['box_style'] ) ) {
			echo '</div>';
		}
		foxiz_post_close_tag();
	}
}