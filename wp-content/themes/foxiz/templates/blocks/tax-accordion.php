<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_tax_based_accordion' ) ) {
	function foxiz_get_tax_based_accordion( $settings = [] ) {

		if ( empty( $settings['categories'] || ! is_array( $settings['categories'] ) ) ) {
			return false;
		}

		$is_edit_mode = ! empty( $settings['yes_edit_mode'] ) ? 'yes' : 'no';
		$cache_key = 'foxiz_tax_based_' . $settings['uuid'];

		if ( 'no' === $is_edit_mode ) {
			$cached_output = get_transient( $cache_key );
			if ( $cached_output !== false ) {
				return $cached_output;
			}
		}

		$params = [
			'post_per_pages' => ! empty( $settings['posts_per_page'] ) ? (int) $settings['posts_per_page'] : 10,
		];

		$order         = ! empty( $settings['order'] ) ? $settings['order'] : 'alphabetical_order_asc';
		$tax_title_tag = ! empty( $settings['tax_title_tag'] ) ? $settings['tax_title_tag'] : 'h3';
		$title_tag     = ! empty( $settings['title_tag'] ) ? $settings['title_tag'] : 'h6';
		if ( ! empty( $settings['title_icon'] ) ) {
			$title_prefix = '<i class="' . strip_tags( $settings['title_icon'] ) . '" aria-hidden="true"></i>';
		}

		$params              = foxiz_get_order_query_params( $params, $order );
		$settings['classes'] = 'block-tax-accordion';
		$index               = 1;

		$output = foxiz_get_block_open_tag( $settings );
		foreach ( $settings['categories'] as $item ) {

			if ( ! empty( $item['tax_id'] ) ) {
				$term = get_term( (int) $item['tax_id'] );
			} elseif ( ! empty( $item['category'] ) ) {
				$term = get_term_by( 'slug', $item['category'], 'category' );
			}

			if ( empty( $term ) || is_wp_error( $term ) ) {
				continue;
			}

			$tax_title = ! empty( $item['tax_title'] ) ? $item['tax_title'] : $term->name;
			$output    .= '<div class="tax-accordion-item" data-tab="' . $term->term_id . '">';
			$output    .= '<' . strip_tags( $tax_title_tag ) . ' class="tax-title tax-accordion-trigger">';
			$output    .= foxiz_strip_tags( $tax_title );
			$output    .= '</' . strip_tags( $tax_title_tag ) . '>';

			$output .= '<div class="tax-accordion-sub"><div class="tax-accordion-sub-inner">';

			if ( 'no' === $is_edit_mode || ( 'yes' === $is_edit_mode && $index < 2 ) ) {

				$params['post_type'] = ! empty( $item['post_type'] ) ? $item['post_type'] : 'post';
				$params['tax_query'] = [
					[
						'taxonomy' => $term->taxonomy,
						'field'    => 'term_id',
						'terms'    => $term->term_id,
						'operator' => 'IN',
					],
				];

				$_query = new WP_Query( $params );
				if ( $_query->have_posts() ) {
					while ( $_query->have_posts() ) {
						$_query->the_post();
						$post_title = get_the_title();
						if ( strlen( $post_title ) === 0 ) {
							$post_title = get_the_date();
						}
						$output .= '<' . strip_tags( $title_tag ) . ' class="entry-title">';
						if ( ! empty( $title_prefix ) ) {
							$output .= foxiz_strip_tags( $title_prefix );
						}
						$output .= '<a class="p-url" href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $post_title . '</a>';
						$output .= '</' . strip_tags( $title_tag ) . '>';
					}
				}
				wp_reset_postdata();
			}

			$output .= '</div></div></div>';
			$index ++;
		}

		$output .= '</div>';

		if ( 'no' === $is_edit_mode ) {
			$time = ! empty( $settings['cache_interval'] ) ? (int) $settings['cache_interval'] : 7200;
			set_transient( $cache_key, $output, $time );
		}

		return $output;
	}
}