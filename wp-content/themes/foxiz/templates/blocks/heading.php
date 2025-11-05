<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_heading' ) ) {
	function foxiz_get_heading( $attrs = [] ) {

		$settings = wp_parse_args( $attrs, [
			'uuid'             => '',
			'layout'           => '',
			'title'            => '',
			'link'             => [],
			'html_tag'         => '',
			'tagline'          => '',
			'tagline_html_tag' => '',
			'tagline_arrow'    => '',
			'classes'          => '',
		] );

		if ( empty( $settings['title'] ) ) {
			return false;
		}

		/** dynamic title */
		if ( strpos( $settings['title'], '{' ) !== false && strpos( $settings['title'], '}' ) !== false ) {
			if ( is_search() ) {
				$settings['title'] = str_replace( [
					'{search}',
					'{archive}',
				], get_search_query( 's' ), $settings['title'] );
			} elseif ( is_author() ) {
				if ( ! empty( get_queried_object()->display_name ) ) {
					$settings['title'] = str_replace( [
						'{author}',
						'{archive}',
					], get_queried_object()->display_name, $settings['title'] );
				}
			} elseif ( is_archive() ) {
				if ( ! empty( get_queried_object()->name ) ) {
					$settings['title'] = str_replace( [
						'{archive}',
						'{category}',
						'{tag}',
						'{taxonomy}',
					], get_queried_object()->name, $settings['title'] );
				}
			}
		}

		if ( empty( $settings['layout'] ) ) {
			$settings['layout'] = foxiz_get_option( 'heading_layout', 1 );
		}

		$class_name = 'block-h heading-layout-' . $settings['layout'];
		if ( ! empty( $settings['tagline_arrow'] ) ) {
			$class_name .= ' tagline-i' . $settings['tagline_arrow'];
		}
		if ( ! empty( $settings['color_scheme'] ) ) {
			$class_name .= ' light-scheme';
		}

		$title_class_name = 'heading-title';
		if ( ! empty( $settings['classes'] ) ) {
			$title_class_name .= ' ' . $settings['classes'];
		}
		if ( empty( $settings['html_tag'] ) ) {
			$settings['html_tag'] = 'h3';
		}
		if ( empty( $settings['tagline_html_tag'] ) ) {
			$settings['tagline_html_tag'] = 'span';
		}
		$output = '<div';
		if ( ! empty( $settings['uuid'] ) ) {
			$output .= ' id="' . esc_attr( $settings['uuid'] ) . '"';
		}
		$output .= ' class="' . esc_attr( $class_name ) . '">';
		$output .= '<div class="heading-inner">';
		$output .= '<' . $settings['html_tag'] . ' class="' . esc_attr( $title_class_name ) . '">';
		if ( ! empty( $settings['link']['url'] ) ) {
			$output .= foxiz_render_elementor_link( $settings['link'], $settings['title'], 'h-link' );
		} else {
			$output .= '<span>' . foxiz_strip_tags( $settings['title'] ) . '</span>';
		}
		$output .= '</' . $settings['html_tag'] . '>';

		if ( ! empty( $settings['tagline'] ) ) {
			$output .= '<div class="heading-tagline h6">';
			if ( ! empty( $settings['link']['url'] ) && ! in_array( (string) $settings['layout'], [
					'c11',
					'11',
					'19',
				] )
			) {
				$output .= foxiz_render_elementor_link( $settings['link'], $settings['tagline'], 'heading-tagline-label' );
			} else {
				$output .= '<' . $settings['tagline_html_tag'] . ' class="heading-tagline-label">' . foxiz_strip_tags( $settings['tagline'] ) . '</' . $settings['tagline_html_tag'] . '>';
			}
			if ( ! empty( $settings['tagline_arrow'] ) ) {
				$output .= '<i class="rbi rbi-cright heading-tagline-icon" aria-hidden="true"></i>';
			}
			$output .= '</div>';
		}

		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}
}