<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_product_grid' ) ) {
	/**
	 * @param array $settings
	 * @param null  $_query
	 *
	 * @return false|string
	 */
	function foxiz_get_product_grid( $settings = [], $_query = null ) {

		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'foxiz_wc_strip_wrapper' ) ) {
			return false;
		}

		$settings = wp_parse_args( $settings, [
			'uuid' => '',
			'name' => 'product_grid',
		] );

		if ( ! empty( $settings['center_mode'] ) && ( '-1' === (string) $settings['center_mode'] ) ) {
			$settings['center_mode'] = false;
		}

		$settings['classes']       = 'block-grid block-product-grid woocommerce';
		$settings['inner_classes'] = 'products';

		if ( ! empty( $settings['mobile_layout'] ) && 'list' === (string) $settings['mobile_layout'] ) {
			$settings['classes'] .= ' is-m-list';
		}

		if ( ! empty( $settings['tablet_layout'] ) && 'list' === (string) $settings['tablet_layout'] ) {
			$settings['classes'] .= ' is-t-list';
		}

		if ( ! empty( $settings['desktop_layout'] ) && 'list' === (string) $settings['desktop_layout'] ) {
			$settings['classes'] .= ' is-d-list';
		}

		if ( ! empty( $settings['display_ratio'] ) ) {
			$settings['classes'] .= ' yes-ratio';
		}

		if ( ! empty( $settings['featured_list_position'] ) ) {
			$settings['classes'] .= ' res-feat-' . $settings['featured_list_position'];
		}

		if ( ! empty( $settings['box_style'] ) ) {
			$settings['classes'] .= ' is-boxed-' . $settings['box_style'] . ' cart-layout-visible';
		} else {
			$settings['classes'] .= ' cart-layout-0';
		}

		unset( $settings['mobile_layout'] );
		if ( empty( $settings['columns'] ) ) {
			$settings['columns'] = 4;
		}
		if ( empty( $settings['column_gap'] ) ) {
			$settings['column_gap'] = 20;
		}

		ob_start();

		if ( ! empty( $settings['crop_size'] ) ) {
			$GLOBALS['foxiz_product_thumb_size'] = $settings['crop_size'];
		}
		foxiz_block_open_tag( $settings );
		foxiz_block_inner_open_tag( $settings );
		echo foxiz_wc_strip_wrapper( do_shortcode( $settings['shortcode'] ) );
		foxiz_block_inner_close_tag( $settings );
		wp_reset_postdata();
		foxiz_block_close_tag();

		$GLOBALS['foxiz_product_thumb_size'] = false;

		return ob_get_clean();
	}
}
