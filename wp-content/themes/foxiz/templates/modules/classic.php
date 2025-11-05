<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_classic_1' ) ) {
	/**
	 * @param $settings
	 */
	function foxiz_classic_1( $settings = [] ) {

		$settings['post_classes']  = 'p-grid p-classic-1 p-grid-1';
		$settings['title_classes'] = 'h1';
		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h2';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_o1';
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
