<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_quick_links' ) ) {
	function foxiz_get_quick_links( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
			'uuid'         => '',
			'overflow'     => '',
			'name'         => 'quick-links',
			'header'       => '',
			'quick_links'  => [],
			'layout'       => '1',
			'hover_effect' => 'underline',
			'source'       => '',
		] );

		$settings['classes'] = 'block-qlinks qlayout-' . $settings['layout'];

		if ( '3' === (string) $settings['layout'] ) {
			$settings['classes'] .= ' qlayout-1';
			$settings['classes'] .= ' effect-' . $settings['hover_effect'];
		} elseif ( '1' === (string) $settings['layout'] ) {
			$settings['classes'] .= ' effect-' . $settings['hover_effect'];
		}

		switch ( $settings['overflow'] ) {
			case '3' :
				$settings['classes'] .= ' yes-nowrap qlinks-scroll';
				break;
			case '2' :
				$settings['classes'] .= ' yes-wrap';
				break;
			default :
				$settings['classes'] .= ' res-nowrap qlinks-scroll';
		}

		switch ( $settings['source'] ) {
			case 'tax' :
				$inner = foxiz_quick_items_from_tax( $settings );
				break;
			case 'both' :
				$inner = foxiz_quick_items_from_input( $settings );
				$inner .= foxiz_quick_items_from_tax( $settings );
				break;
			case 'sub' :
				$inner = foxiz_quick_items_from_subterms( $settings );
				$inner .= foxiz_quick_items_from_input( $settings );
				break;
			default :
				$inner = foxiz_quick_items_from_input( $settings );
		}

		if ( empty( $inner ) ) {
			return false;
		}

		$output = foxiz_get_block_open_tag( $settings );

		$output .= '<ul class="qlinks-inner">';
		if ( ! empty( $settings['header'] ) ) {
			$output .= '<li class="qlink qlinks-heading">';
			$output .= '<div class="qlink-label">';
			$output .= foxiz_strip_tags( $settings['header'] );
			$output .= '</div></li>';
		}
		$output .= $inner;
		$output .= '</ul>';
		$output .= foxiz_get_block_close_tag();

		return $output;
	}
}

if ( ! function_exists( 'foxiz_quick_items_from_input' ) ) {
	function foxiz_quick_items_from_input( $settings ) {

		if ( empty( $settings['quick_links'] ) || ! is_array( $settings['quick_links'] ) ) {
			return false;
		}

		$output = '';
		foreach ( $settings['quick_links'] as $item ) {
			if ( empty( $item['url']['url'] ) ) {
				continue;
			}
			$title  = isset( $item['title'] ) ? $item['title'] : '';
			$output .= '<li class="qlink h5">' . foxiz_render_elementor_link( $item['url'], $title ) . '</li>';
		}

		return $output;
	}
}


if ( ! function_exists( 'foxiz_quick_items_from_tax' ) ) {
	function foxiz_quick_items_from_tax( $settings ) {

		if ( empty( $settings['source_tax'] ) ) {
			return false;
		}

		$number = ! empty( $settings['total'] ) ? absint( $settings['total'] ) : 10;
		$tax    = array_map( 'trim', explode( ',', $settings['source_tax'] ) );

		$terms = get_terms( [
			'taxonomy'   => $tax,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'hide_empty' => true,
			'number'     => $number,
		] );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return false;
		}

		$output = '';
		foreach ( $terms as $term ) {
			$output .= '<li class="qlink h5"><a href="' . esc_url( get_term_link( $term ) ) . '">' . $term->name . '</a></li>';
		}

		return $output;
	}
}

if ( ! function_exists( 'foxiz_quick_items_from_subterms' ) ) {
	function foxiz_quick_items_from_subterms( $settings = [] ) {

		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return false;
		}

		$current_term = get_queried_object();

		$subterms = get_terms( [
			'taxonomy'   => $current_term->taxonomy,
			'parent'     => $current_term->term_id,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'hide_empty' => true,
			'number'     => 0,
		] );

		if ( empty( $subterms ) || is_wp_error( $subterms ) ) {
			return false;
		}

		$output = '';
		foreach ( $subterms as $term ) {
			$output .= '<li class="qlink h5"><a href="' . esc_url( get_term_link( $term ) ) . '">' . foxiz_strip_tags( $term->name ) . '</a></li>';
		}

		return $output;
	}
}