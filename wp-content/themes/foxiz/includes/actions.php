<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

add_filter( 'get_archives_link', 'foxiz_archives_widget_span', 10 );
add_filter( 'wp_list_categories', 'foxiz_cat_widget_span', 10 );
add_filter( 'ruby_content_elements', 'foxiz_inline_content_related', 8, 2 );
add_filter( 'ruby_content_elements', 'foxiz_add_single_inline_ad', 10 );
add_filter( 'body_class', 'foxiz_set_body_classes', 20 );
add_filter( 'widget_tag_cloud_args', 'foxiz_widget_tag_cloud_args', 10 );
add_filter( 'comment_form_defaults', 'foxiz_add_comment_placeholder', 10 );

if ( ! function_exists( 'foxiz_add_comment_placeholder' ) ) {
	function foxiz_add_comment_placeholder( $defaults ) {

		if ( ! empty( $defaults['fields']['author'] ) ) {
			$defaults['fields']['author'] = str_replace( '<input', '<input placeholder="' . foxiz_html__( 'Your name', 'foxiz' ) . '"', $defaults['fields']['author'] );
		}
		if ( ! empty( $defaults['fields']['email'] ) ) {
			$defaults['fields']['email'] = str_replace( '<input', '<input placeholder="' . foxiz_html__( 'Your email', 'foxiz' ) . '"', $defaults['fields']['email'] );
		}

		if ( ! empty( $defaults['fields']['url'] ) ) {
			$defaults['fields']['url'] = str_replace( '<input', '<input placeholder="' . foxiz_html__( 'Your website', 'foxiz' ) . '"', $defaults['fields']['url'] );
		}

		if ( ! empty( $defaults['comment_field'] ) ) {
			$defaults['comment_field'] = str_replace( '<textarea', '<textarea placeholder="' . foxiz_html__( 'Leave a Comment', 'foxiz' ) . '"', $defaults['comment_field'] );
		}

		return $defaults;
	}
}

if ( ! function_exists( 'foxiz_get_js_settings' ) ) {
	/**
	 * @return array
	 */
	function foxiz_get_js_settings() {

		$settings = foxiz_get_option();
		$params   = [];

		if ( empty( $settings['slider_speed'] ) ) {
			$params['sliderSpeed'] = 5000;
		} else {
			$params['sliderSpeed'] = (int) $settings['slider_speed'];
		}

		if ( ! empty( $settings['slider_effect'] ) ) {
			$params['sliderEffect'] = 'fade';
		} else {
			$params['sliderEffect'] = 'slide';
		}

		if ( ! empty( $settings['slider_fmode'] ) ) {
			$params['sliderFMode'] = true;
		} else {
			$params['sliderFMode'] = false;
		}

		if ( ! empty( $settings['ajax_next_crawler'] ) ) {
			$params['crwLoadNext'] = 1;
		}

		if ( ! empty( $settings['adblock_detector'] ) ) {
			$params['adDetectorMethod'] = (int) $settings['adblock_detector'];
		}

		if ( is_single() ) {

			if ( function_exists( 'foxiz_get_twitter_name' ) ) {
				$params['twitterName'] = foxiz_get_twitter_name();
			}
			if ( ! empty( $settings['single_post_highlight_shares'] ) ) {
				$params['highlightShares'] = 1;
			}
			if ( ! empty( $settings['single_post_highlight_share_facebook'] ) ) {
				$params['highlightShareFacebook'] = 1;
			}
			if ( ! empty( $settings['single_post_highlight_share_twitter'] ) ) {
				$params['highlightShareTwitter'] = 1;
			}
			if ( ! empty( $settings['single_post_highlight_share_reddit'] ) ) {
				$params['highlightShareReddit'] = 1;
			}
			$ajax_limit = foxiz_get_single_setting( 'ajax_limit' );
			if ( ! empty( $ajax_limit ) ) {
				$params['singleLoadNextLimit'] = absint( $ajax_limit );
			} else {
				$params['singleLoadNextLimit'] = 20;
			}
			if ( ! empty( $settings['reading_history'] ) ) {
				$params['yesReadingHis'] = get_the_ID();
			}
			if ( ! empty( $settings['live_blog_interval'] ) ) {
				$params['liveInterval'] = (int) $settings['live_blog_interval'];
			}
		}

		return $params;
	}
}

if ( ! function_exists( 'foxiz_set_body_classes' ) ) {
	function foxiz_set_body_classes( $classes ) {

		$classes[] = 'menu-ani-' . trim( foxiz_get_option( 'menu_hover_effect', 1 ) );
		$classes[] = 'hover-ani-' . trim( foxiz_get_option( 'hover_effect', 1 ) );
		$classes[] = 'btn-ani-' . trim( foxiz_get_option( 'btn_hover_effect', 1 ) );
		$classes[] = 'btn-transform-' . trim( foxiz_get_option( 'btn_hover_ani', 1 ) );
		$classes[] = 'is-rm-' . trim( foxiz_get_option( 'readmore_style', 1 ) );
		$classes[] = 'lmeta-' . foxiz_get_option( 'live_blog_meta', 'dot' );
		$classes[] = 'loader-' . foxiz_get_option( 'loader_style', 1 );
		$classes[] = 'dark-sw-' . foxiz_get_option( 'dark_mode_style', 1 );
		$classes[] = 'mtax-' . foxiz_get_option( 'meta_tax_style', 1 );

		if ( foxiz_get_option( 'menu_glass_effect' ) ) {
			$classes[] = 'menu-glass-effect';

			if ( foxiz_get_option( 'menu_template_glass_effect' ) ) {
				$classes[] = 't-menu-glass-effect';
			}
		}

		if ( foxiz_get_option( 'table_contents_scroll' ) ) {
			$classes[] = 'toc-smooth';
		}

		$header_style = foxiz_get_header_style();
		$classes[]    = 'is-hd-' . $header_style['style'];

		switch ( $header_style['style'] ) {
			case  't1' :
				$classes[] = 'yes-hd-transparent is-hd-1';
				break;
			case  't2' :
				$classes[] = 'yes-hd-transparent is-hd-2';
				break;
			case  't3' :
				$classes[] = 'yes-hd-transparent is-hd-3';
				break;
		}

		if ( is_single() ) {

			$layout = foxiz_get_single_layout();

			if ( ! empty( $layout['layout'] ) ) {

				if ( $layout['layout'] !== 'stemplate' ) {
					$classes[] = 'is-' . str_replace( '_', '-', $layout['layout'] );
					if ( foxiz_get_option( 'single_post_centered' ) ) {
						$classes[] = 'centered-header';
					}
				} elseif ( ! foxiz_is_amp() ) {
					$classes[] = 'is-stemplate';
				}
			}

			if ( foxiz_get_option( 'single_post_sticky_title' ) ) {
				$classes[] = 'is-mstick yes-tstick';
			}

			if ( foxiz_get_option( 'single_iframe_responsive' ) && class_exists( 'Classic_Editor' ) ) {
				$classes[] = 'res-iframe-classic';
			}
		}

		if ( foxiz_get_option( 'back_top' ) ) {
			$classes[] = 'is-backtop';
			if ( ! foxiz_get_option( 'mobile_back_top' ) ) {
				$classes[] = 'none-m-backtop';
			}
		}

		$classes[] = foxiz_get_option( 'exclusive_style' ) ? 'exclusive-style-' . trim( foxiz_get_option( 'exclusive_style' ) ) : '';

		if ( foxiz_get_option( 'sticky' ) ) {
			$classes[] = 'is-mstick';

			if ( foxiz_get_option( 'smart_sticky' ) ) {
				$classes[] = 'is-smart-sticky';
			}
		}

		if ( foxiz_get_option( 'dark_mode_image_opacity' ) ) {
			$classes[] = 'dark-opacity';
		}

		$ad_top = foxiz_get_option( 'ad_top_image' );
		if ( ! empty( $ad_top['url'] ) && ! foxiz_get_option( 'ad_top_type' ) && foxiz_get_option( 'ad_top_animation' ) ) {
			$classes[] = 'top-spacing';
		}

		$optimized_load = (string) foxiz_get_option( 'dark_mode_cookie' );

		if ( $optimized_load === '1' ) {
			$classes[] = 'is-cmode';
		}

		if ( foxiz_get_option( 'js_count' ) ) {
			$classes[] = 'is-jscount';
		}

		if ( foxiz_is_amp() ) {
			$classes[] = 'yes-amp';
		}

		return $classes;
	}
}

if ( ! function_exists( 'foxiz_cat_widget_span' ) ) {
	/**
	 * @param $str
	 *
	 * @return mixed|string|string[]
	 */
	function foxiz_cat_widget_span( $str ) {

		$pos = strpos( $str, '</a> (' );
		if ( false !== $pos ) {
			$str = str_replace( '</a> (', '<span class="count">', $str );
			$str = str_replace( ')', '</span></a>', $str );
		}

		return $str;
	}
}

if ( ! function_exists( 'foxiz_archives_widget_span' ) ) {
	/**
	 * @param $str
	 *
	 * @return mixed|string|string[]
	 */
	function foxiz_archives_widget_span( $str ) {

		$pos = strpos( $str, '</a>&nbsp;(' );
		if ( false !== $pos ) {
			$str = str_replace( '</a>&nbsp;(', '<span class="count">', $str );
			$str = str_replace( ')', '</span></a>', $str );
		}

		return $str;
	}
}

if ( ! function_exists( 'foxiz_widget_tag_cloud_args' ) ) {
	function foxiz_widget_tag_cloud_args( $args ) {

		$args['largest']  = 1;
		$args['smallest'] = 1;
		$args['number']   = 100;

		return $args;
	}
}
