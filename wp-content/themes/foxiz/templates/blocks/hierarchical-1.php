<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_hierarchical_1' ) ) {
	/**
	 * @param $settings
	 *
	 * @return false|string
	 */
	function foxiz_get_hierarchical_1( $settings ) {

		$settings = wp_parse_args( $settings, [
			'uuid' => '',
			'name' => 'hierarchical_1',
		] );

		$settings = foxiz_detect_dynamic_query( $settings );

		$settings['classes'] = 'block-hrc hrc-1';
		if ( empty( $settings['pagination'] ) ) {
			$settings['no_found_rows'] = true;
		} else {
			$settings['classes'] .= ' short-pagination';
		}

		$min_posts = 2;
		$_query    = foxiz_query( $settings );
		$settings  = foxiz_get_design_builder_block( $settings );

		ob_start();
		foxiz_block_open_tag( $settings, $_query );
		if ( ! $_query->have_posts() || $_query->post_count < $min_posts ) {
			foxiz_error_posts( $_query, $min_posts );
		} else {
			foxiz_block_inner_open_tag( $settings );
			foxiz_loop_hierarchical_1( $settings, $_query );
			foxiz_block_inner_close_tag( $settings );
			foxiz_render_pagination( $settings, $_query );
			wp_reset_postdata();
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_loop_hierarchical_1' ) ) {
	/**
	 * @param  $settings
	 * @param  $_query
	 */
	function foxiz_loop_hierarchical_1( $settings, $_query ) {

		$flag = true;
		while ( $_query->have_posts() ) :
			$_query->the_post();
			if ( $flag ) {
				foxiz_grid_1( $settings );
				$settings['title_tag'] = ! empty( $settings['sub_title_tag'] ) ? $settings['sub_title_tag'] : 'h5';
				$flag                  = false;
			} else {
				foxiz_list_inline( $settings );
			}

		endwhile;
	}
}
