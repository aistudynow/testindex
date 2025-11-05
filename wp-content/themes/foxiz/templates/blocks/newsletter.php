<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_newsletter' ) ) {
	function foxiz_get_newsletter( $settings = [] ) {

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h2';
		}

		$classes = [ 'newsletter-box', 'newsletter-style' ];

		if ( empty( $settings['box_style'] ) ) {
			$classes[] = 'is-box-shadow';
		} else {
			$classes[] = 'is-box-' . $settings['box_style'];
		}
		if ( ! empty( $settings['color_scheme'] ) ) {
			$classes[] = 'light-scheme';
		}
		if ( ! empty( $settings['classes'] ) ) {
			$classes[] = $settings['classes'];
		}

		$output = '<div class="' . join( ' ', $classes ) . '">';
		$output .= foxiz_get_newsletter_background( $settings );

		$output .= '<div class="newsletter-inner">';
		$output .= foxiz_get_newsletter_featured( $settings );
		if ( ! empty( $settings['title'] ) || ! empty( $settings['description'] ) ) {
			$output .= '<div class="newsletter-content">';
			if ( ! empty( $settings['title'] ) ) {
				$output .= '<' . $settings['title_tag'] . ' class="newsletter-title">' . foxiz_strip_tags( $settings['title'] );
				$output .= '</' . $settings['title_tag'] . '>';
			}
			if ( ! empty( $settings['description'] ) ) {
				$output .= '<div class="newsletter-description rb-text">' . foxiz_strip_tags( $settings['description'] ) . '</div>';
			}
			$output .= '</div>';
		}
		if ( ! empty( $settings['shortcode'] ) ) {
			$output .= '<div class="newsletter-form">' . do_shortcode( $settings['shortcode'] ) . '</div>';
		}
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_sidebar_newsletter' ) ) {
	function foxiz_get_sidebar_newsletter( $settings = [] ) {

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h2';
		}

		$classes = [ 'newsletter-sb', 'newsletter-style' ];

		if ( empty( $settings['box_style'] ) ) {
			$classes[] = 'is-box-gray-dash';
		} else {
			$classes[] = 'is-box-' . esc_attr( $settings['box_style'] );
		}
		if ( ! empty( $settings['color_scheme'] ) ) {
			$classes[] = 'light-scheme';
		}
		if ( ! empty( $settings['classes'] ) ) {
			$classes[] = $settings['classes'];
		}

		$output = '<div class="' . implode( ' ', $classes ) . '">';
		$output .= foxiz_get_newsletter_background( $settings );

		$output .= '<div class="newsletter-sb-inner newsletter-inner">';
		$output .= foxiz_get_newsletter_featured( $settings );

		if ( ! empty( $settings['title'] ) ) {
			$output .= '<' . $settings['title_tag'] . ' class="newsletter-title">' . esc_html( $settings['title'] );
			$output .= '</' . $settings['title_tag'] . '>';
		}
		if ( ! empty( $settings['description'] ) ) {
			$output .= '<div class="newsletter-description rb-text">' . foxiz_strip_tags( $settings['description'] ) . '</div>';
		}
		$output .= '<div class="newsletter-form">';
		if ( ! empty( $settings['shortcode'] ) ) {
			$output .= do_shortcode( $settings['shortcode'] );
		}
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_newsletter_background' ) ) {
	function foxiz_get_newsletter_background( $settings = [] ) {

		if ( empty( $settings['background']['url'] ) && empty( $settings['dark_background']['url'] ) ) {
			return false;
		}

		$default = ! empty( $settings['background'] ) ? $settings['background'] : [];
		$dark    = ! empty( $settings['dark_background'] ) ? $settings['dark_background'] : [];
		$is_lazy = empty( $settings['feat_lazyload'] ) || $settings['feat_lazyload'] !== 'none';

		$output = '<div class="newsletter-box-bg">';
		$output .= foxiz_e_image_tag( $default, $dark, $is_lazy );
		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_newsletter_featured' ) ) {
	function foxiz_get_newsletter_featured( $settings = [] ) {

		if ( empty( $settings['featured']['url'] ) && empty( $settings['dark_featured']['url'] ) ) {
			return false;
		}

		$default = ! empty( $settings['featured'] ) ? $settings['featured'] : [];
		$dark    = ! empty( $settings['dark_featured'] ) ? $settings['dark_featured'] : [];
		if ( ! empty( $settings['feat_lazyload'] ) ) {
			$is_lazy = ( 'none' === $settings['feat_lazyload'] ) ? false : true;
		} else {
			$is_lazy = foxiz_get_option( 'lazy_load' );
		}
		$output = '<div class="newsletter-featured">';
		$output .= foxiz_e_image_tag( $default, $dark, $is_lazy );
		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_e_image_tag' ) ) {
	function foxiz_e_image_tag( $default = [], $dark = [], $is_lazy = true ) {

		if ( empty( $default['url'] ) && empty( $dark['url'] ) ) {
			return false;
		}

		$output = '';

		if ( empty( $dark['url'] ) ) {
			if ( ! empty( $default['id'] ) ) {
				$output .= wp_get_attachment_image( $default['id'], 'full', false, [
					'loading' => ( ! empty( $is_lazy ) ? 'lazy' : 'eager' ),
				] );
			} else {
				$output .= '<img loading="' . ( ! empty( $is_lazy ) ? 'lazy' : 'eager' ) . '" width="1" height="1" src="' . ( ! empty( $default['url'] ) ? esc_attr( $default['url'] ) : '' ) . '" alt="' . ( ! empty( $default['alt'] ) ? esc_attr( $default['alt'] ) : '' ) . '">';
			}
		} else {
			if ( ! empty( $default['id'] ) ) {
				$output .= wp_get_attachment_image( $default['id'], 'full', false, [
					'loading'   => ( ! empty( $is_lazy ) ? 'lazy' : 'eager' ),
					'data-mode' => 'default',
				] );
			} else {
				$output .= '<img data-mode="default" loading="lazy" width="1" height="1" src="' . ( ! empty( $default['url'] ) ? esc_attr( $default['url'] ) : '' ) . '" alt="' . ( ! empty( $default['alt'] ) ? esc_attr( $default['alt'] ) : '' ) . '">';
			}
			if ( ! empty( $dark['id'] ) ) {
				$output .= wp_get_attachment_image( $dark['id'], 'full', false, [
					'loading'   => ( ! empty( $is_lazy ) ? 'lazy' : 'eager' ),
					'data-mode' => 'dark',
				] );
			} else {
				$output .= '<img loading="' . ( ! empty( $is_lazy ) ? 'lazy' : 'eager' ) . '" width="1" height="1" src="' . ( ! empty( $dark['url'] ) ? esc_attr( $dark['url'] ) : '' ) . '" alt="' . ( ! empty( $dark['alt'] ) ? esc_attr( $dark['alt'] ) : '' ) . '">';
			}
		}

		return $output;
	}
}