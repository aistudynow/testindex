<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_list_small_1' ) ) {
	/**
	 * @param array $settings
	 * @param null $_query
	 *
	 * @return false|string
	 */
	function foxiz_get_list_small_1( $settings = [], $_query = null ) {

		$settings = wp_parse_args( $settings, [
			'uuid' => '',
			'name' => 'list_small_1',
		] );
		$settings = foxiz_detect_dynamic_query( $settings );

		$settings['classes'] = 'block-small block-list block-list-small-1';

		if ( empty( $settings['pagination'] ) ) {
			$settings['no_found_rows'] = true;
		} else {
			$settings['classes'] .= ' short-pagination';
		}
		if ( ! empty( $settings['scroll_height'] ) ) {
			$settings['classes'] .= ' is-scroll';
		}

		if ( ! empty( $settings['title_icon'] ) ) {
			$settings['title_prefix'] = '<i class="' . strip_tags( $settings['title_icon'] ) . '" aria-hidden="true"></i>';
		}

		$min_posts = 1;
		if ( ! $_query ) {
			$_query = foxiz_query( $settings );
		}

		$settings = foxiz_get_design_builder_block( $settings );

		ob_start();
		foxiz_block_open_tag( $settings, $_query );
		if ( ! $_query->have_posts() || $_query->post_count < $min_posts ) {
			foxiz_error_posts( $_query, $min_posts );
		} else {
			foxiz_block_inner_open_tag( $settings );
			foxiz_loop_list_small_1( $settings, $_query );
			foxiz_block_inner_close_tag( $settings );
			foxiz_render_pagination( $settings, $_query );
			wp_reset_postdata();
		}
		foxiz_block_close_tag( $settings );

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_loop_list_small_1' ) ) {
	/**
	 * @param  $settings
	 * @param  $_query
	 */
	function foxiz_loop_list_small_1( $settings, $_query ) {

		while ( $_query->have_posts() ) :
			$_query->the_post();
			foxiz_list_small_1( $settings );
		endwhile;
	}
}