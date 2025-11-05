<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_list_1' ) ) {
	/**
	 * @param array $settings
	 * @param null  $_query
	 *
	 * @return false|string
	 */
	function foxiz_get_list_1( $settings = [], $_query = null ) {

		$settings = wp_parse_args( $settings, [
			'uuid' => '',
			'name' => 'list_1',
		] );

		$settings = foxiz_detect_dynamic_query( $settings );

		if ( empty( $settings['pagination'] ) ) {
			$settings['no_found_rows'] = true;
		}

		$settings['classes'] = 'block-big block-list block-list-1';
		$min_posts           = 1;

		if ( ! $_query ) {
			$_query = foxiz_query( $settings );
		}

		$settings = foxiz_get_design_standard_block( $settings, 'list_1' );

		ob_start();
		foxiz_block_open_tag( $settings, $_query );
		if ( ! $_query->have_posts() || $_query->post_count < $min_posts ) {
			foxiz_error_posts( $_query, $min_posts );
		} else {
			foxiz_block_inner_open_tag( $settings );
			foxiz_loop_list_1( $settings, $_query );
			foxiz_block_inner_close_tag( $settings );
			foxiz_render_pagination( $settings, $_query );
			wp_reset_postdata();
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_loop_list_1' ) ) {
	/**
	 * @param  $settings
	 * @param  $_query
	 */
	function foxiz_loop_list_1( $settings, $_query ) {

		$loop_index = 1;
		while ( $_query->have_posts() ) :
			$_query->the_post();
			if ( ! empty( $settings['eager_images'] ) ) {
				$settings['feat_lazyload'] = ( $loop_index <= $settings['eager_images'] ) ? 'none' : '1';
				$loop_index ++;
			}
			foxiz_list_1( $settings );
		endwhile;
	}
}