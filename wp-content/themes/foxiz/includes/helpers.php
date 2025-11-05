<?php
/** Don't load directly */

use Elementor\Core\Files\File_Types\Svg;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_user_socials' ) ) {
	function foxiz_get_user_socials( $author_id = '' ) {

		if ( empty( $author_id ) ) {
			return false;
		}

		return [
				'website'    => get_the_author_meta( 'url', $author_id ),
				'facebook'   => get_the_author_meta( 'facebook', $author_id ),
				'twitter'    => get_the_author_meta( 'twitter_url', $author_id ),
				'youtube'    => get_the_author_meta( 'youtube', $author_id ),
				'instagram'  => get_the_author_meta( 'instagram', $author_id ),
				'pinterest'  => get_the_author_meta( 'pinterest', $author_id ),
				'tiktok'     => get_the_author_meta( 'tiktok', $author_id ),
				'linkedin'   => get_the_author_meta( 'linkedin', $author_id ),
				'medium'     => get_the_author_meta( 'medium', $author_id ),
				'twitch'     => get_the_author_meta( 'twitch', $author_id ),
				'steam'      => get_the_author_meta( 'steam', $author_id ),
				'tumblr'     => get_the_author_meta( 'tumblr', $author_id ),
				'discord'    => get_the_author_meta( 'discord', $author_id ),
				'flickr'     => get_the_author_meta( 'flickr', $author_id ),
				'skype'      => get_the_author_meta( 'skype', $author_id ),
				'snapchat'   => get_the_author_meta( 'snapchat', $author_id ),
				'quora'      => get_the_author_meta( 'quora', $author_id ),
				'myspace'    => get_the_author_meta( 'myspace', $author_id ),
				'bloglovin'  => get_the_author_meta( 'bloglovin', $author_id ),
				'digg'       => get_the_author_meta( 'digg', $author_id ),
				'dribbble'   => get_the_author_meta( 'dribbble', $author_id ),
				'soundcloud' => get_the_author_meta( 'soundcloud', $author_id ),
				'vimeo'      => get_the_author_meta( 'vimeo', $author_id ),
				'reddit'     => get_the_author_meta( 'reddit', $author_id ),
				'vkontakte'  => get_the_author_meta( 'vkontakte', $author_id ),
				'telegram'   => get_the_author_meta( 'telegram', $author_id ),
				'whatsapp'   => get_the_author_meta( 'whatsapp', $author_id ),
				'truth'      => get_the_author_meta( 'truth', $author_id ),
				'threads'    => get_the_author_meta( 'threads', $author_id ),
				'bluesky'    => get_the_author_meta( 'bluesky', $author_id ),
				'rss'        => get_the_author_meta( 'rss', $author_id ),
		];
	}
}

if ( ! function_exists( 'foxiz_is_wc_pages' ) ) {
	function foxiz_is_wc_pages() {

		if ( class_exists( 'WooCommerce' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'foxiz_is_edit_mode' ) ) {
	function foxiz_is_edit_mode() {

		if ( isset( $GLOBALS['foxiz_is_edit_mode'] ) ) {
			return $GLOBALS['foxiz_is_edit_mode'];
		}

		$is_edit_mode = false;

		if (
				foxiz_is_elementor_active() &&
				class_exists( 'Elementor\Plugin' ) &&
				isset( \Elementor\Plugin::$instance->editor ) &&
				\Elementor\Plugin::$instance->editor->is_edit_mode()
		) {
			$is_edit_mode = true;
		}

		$GLOBALS['foxiz_is_edit_mode'] = $is_edit_mode;

		return $is_edit_mode;
	}
}

if ( ! function_exists( 'foxiz_is_template_preview' ) ) {
	function foxiz_is_template_preview() {

		return foxiz_is_edit_mode() || is_singular( 'rb-etemplate' );
	}
}


if ( ! function_exists( 'foxiz_get_video_mine_type' ) ) {
	/**
	 * @param $url
	 *
	 * @return string
	 */
	function foxiz_get_video_mine_type( $url ) {

		/** set default */
		if ( empty( $url ) ) {
			return 'video/mp4';
		}
		if ( false !== strpos( $url, '.webm' ) ) {
			return 'video/webm';
		} elseif ( false !== strpos( $url, '.ogv' ) ) {
			return 'video/ogg';
		} elseif ( false !== strpos( $url, '.avi' ) ) {
			return 'video/avi';
		} elseif ( false !== strpos( $url, '.mpeg' ) || false !== strpos( $url, '.mpg' ) || false !== strpos( $url, '.mpe' ) ) {
			return 'video/mpeg';
		}

		return 'video/mp4';
	}
}

if ( ! function_exists( 'foxiz_get_current_permalink' ) ) {
	function foxiz_get_current_permalink() {

		global $wp;

		return home_url( add_query_arg( [], $wp->request ) );
	}
}

if ( ! function_exists( 'foxiz_get_term_link' ) ) {
	function foxiz_get_term_link( $term, $taxonomy = '' ) {

		if ( ! is_object( $term ) ) {
			$term = (int) $term;
		}

		$link = get_term_link( $term, $taxonomy );
		if ( empty( $link ) || is_wp_error( $link ) ) {
			return '#';
		}

		return $link;
	}
}

if ( ! function_exists( 'foxiz_get_post_likes' ) ) {
	/**
	 * @param string $id
	 *
	 * @return false|string
	 */
	function foxiz_get_post_likes( $id = '' ) {

		$count = get_post_meta( $id, 'rb_total_like', true );
		if ( ! empty( $count ) ) {
			return foxiz_pretty_number( $count );
		}

		return '';
	}
}

if ( ! function_exists( 'foxiz_get_post_dislikes' ) ) {
	/**
	 * @param string $id
	 *
	 * @return false|string
	 */
	function foxiz_get_post_dislikes( $id = '' ) {

		$count = get_post_meta( $id, 'rb_total_dislike', true );
		if ( ! empty( $count ) ) {
			return foxiz_pretty_number( $count );
		}

		return '';
	}
}

/**
 * @param array $settings
 *
 * @return array
 */
if ( ! function_exists( 'foxiz_get_top_post_ids' ) ) {
	function foxiz_get_top_post_ids( $settings = [] ) {

		if ( ! defined( 'JETPACK__VERSION' ) || ! class_exists( 'Automattic\Jetpack\Stats\WPCOM_Stats' ) ) {
			return [];
		}

		$count = 10;
		$days  = 2;

		if ( ! empty( $settings['posts_per_page'] ) ) {
			$count = absint( $settings['posts_per_page'] );
		}

		if ( ! empty( $settings['jetpack_days'] ) ) {
			$days = intval( $settings['jetpack_days'] );
		}

		if ( function_exists( 'wpl_get_blogs_most_liked_posts' ) && ! empty( $settings['jetpack_sort_order'] ) && 'likes' === $settings['jetpack_sort_order'] ) {
			$post_ids = wpl_get_blogs_most_liked_posts();
			if ( ! $post_ids ) {
				return [];
			}

			return array_keys( $post_ids );
		}

		if ( defined( 'IS_WPCOM' ) && IS_WPCOM && function_exists( 'stats_get_daily_history' ) ) {
			$post_views = wp_cache_get( "get_top_posts_$count", 'stats' );
			if ( false === $post_views ) {

				$stats_get_daily_history = stats_get_daily_history(
						false,
						get_current_blog_id(),
						'postviews',
						'post_id',
						false,
						2,
						'',
						$count * 2 + 10,
						true
				);

				$post_views = array_shift( $stats_get_daily_history );
				unset( $post_views[0] );

				wp_cache_add( "get_top_posts_$count", $post_views, 'stats', 1200 );
			}

			return array_keys( $post_views );
		}

		$args = [
				'max'       => 11,
				'summarize' => 1,
				'num'       => $days,
		];

		$data = foxiz_convert_stats_array_to_object( ( new Automattic\Jetpack\Stats\WPCOM_Stats() )->get_top_posts( $args ) );

		if ( ! isset( $data->summary ) || empty( $data->summary->postviews ) ) {
			return [];
		}

		$post_ids = array_filter( wp_list_pluck( $data->summary->postviews, 'id' ) );
		if ( ! $post_ids ) {
			return [];
		}

		return $post_ids;
	}
}

/**
 * @param $stats_array
 *
 * @return mixed|WP_Error
 */
if ( ! function_exists( 'foxiz_convert_stats_array_to_object' ) ) {
	function foxiz_convert_stats_array_to_object( $stats_array ) {

		if ( is_wp_error( $stats_array ) ) {
			return $stats_array;
		}
		$encoded_array = wp_json_encode( $stats_array );
		if ( ! $encoded_array ) {
			return new WP_Error( 'stats_encoding_error', 'Failed to encode stats array' );
		}

		return json_decode( $encoded_array );
	}
}

if ( ! function_exists( 'foxiz_get_dynamic_css' ) ) {
	/**
	 * @return string
	 */
	function foxiz_get_dynamic_css() {

		$output = stripslashes( get_option( 'foxiz_style_cache', '' ) );
		$output .= stripslashes( get_option( 'foxiz_term_style_cache', '' ) );

		/** direct css to fix encoding issues */
		$exclusive = foxiz_get_option( 'exclusive_label' );
		$live      = foxiz_get_option( 'live_label', 'live:' ) . ' ';

		if ( ! empty( $exclusive ) ) {
			$output .= '.entry-title.is-p-protected a:before { content: "' . esc_attr( $exclusive ) . '";display: inline-block; }';
		}
		if ( 'dot' !== foxiz_get_option( 'live_blog_meta' ) ) {
			$output .= '.live-tag:after { content: "' . esc_attr( $live ) . '" }';
		}

		return $output;
	}
}

if ( ! function_exists( 'foxiz_get_content_images' ) ) {
	/**
	 * @param string $post_id
	 *
	 * @return array|mixed|string
	 */
	function foxiz_get_content_images( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$meta_key = 'rb_content_images';

		$images = get_post_meta( $post_id, $meta_key, true );

		if ( ! empty( $images ) ) {
			return $images;
		}

		/** gallery $images */
		$gallery_images = rb_get_meta( 'gallery_data', $post_id );

		if ( 'gallery' === get_post_format( $post_id ) && ! empty( $gallery_images ) ) {
			$images = array_fill_keys( array_map( 'trim', explode( ',', $gallery_images ) ), '#' );
			update_post_meta( $post_id, $meta_key, $images );

			return $images;
		}

		/** get images in post_content */
		$images       = [];
		$counter      = 1;
		$post_content = get_post_field( 'post_content', $post_id );

		$pattern = '/<!--\s*wp:image\s*({.*?})\s*-->.*?<img src="(.*?)"/s';
		preg_match_all( $pattern, $post_content, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$data = json_decode( $match[1], true );
			if ( ! empty( $data ) && is_array( $data ) && ! empty( $match[2] ) ) {
				if ( ! empty( $data['id'] ) ) {
					$images[ $data['id'] ] = $match[2];
				} else {
					$images[] = $match[2];
				}
				$counter ++;
			}
			/** limit images */
			if ( $counter > 5 ) {
				break;
			}
		}

		/** for Classic Editor img tags */
		if ( ! count( $images ) ) {
			$pattern = '/<img[^>]+src="([^">]+)"/';
			preg_match_all( $pattern, $post_content, $matches, PREG_SET_ORDER );

			foreach ( $matches as $match ) {
				if ( ! empty( $match[1] ) ) {
					$images[] = $match[1];
					$counter ++;
				}
				if ( $counter > 5 ) {
					break;
				}
			}
		}

		if ( ! count( $images ) ) {
			$images = '-1';
		}

		update_post_meta( $post_id, $meta_key, $images );

		return $images;
	}
}

if ( ! function_exists( 'foxiz_is_review_post' ) ) {
	/**
	 * @param string $post_id
	 *
	 * @return bool
	 */
	function foxiz_is_review_post( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$review = rb_get_meta( 'review', $post_id );
		if ( empty( $review ) || '-1' === (string) $review ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'foxiz_is_sponsored_post' ) ) {
	function foxiz_is_sponsored_post( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$sponsor = rb_get_meta( 'sponsor_post', $post_id );
		if ( ! empty( $sponsor ) && '1' === (string) $sponsor ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'foxiz_is_live_blog' ) ) {
	function foxiz_is_live_blog( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		if ( 'yes' === get_post_meta( $post_id, 'ruby_live_blog', true ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'foxiz_get_header_style' ) ) {
	/**
	 * @return false|mixed|string|void
	 */
	function foxiz_get_header_style() {

		$params = [
				'style'     => '',
				'shortcode' => '',
		];

		if ( is_singular( 'podcast' ) ) {

			$meta_template = trim( rb_get_meta( 'header_template' ) );
			if ( $meta_template ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => $meta_template,
				];
			}

			$episode_header_style = rb_get_meta( 'header_style' );
			if ( ! empty( $episode_header_style ) && 'default' !== (string) $episode_header_style ) {
				return [
						'style'     => $episode_header_style,
						'shortcode' => '',
				];
			}

			$default_template = trim( foxiz_get_option( 'single_podcast_header_template' ) );
			if ( ! empty( $default_template ) ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => $default_template,
				];
			}

			if ( foxiz_get_option( 'single_podcast_header_style' ) ) {
				$params['style'] = foxiz_get_option( 'single_podcast_header_style' );
			}
		} elseif ( is_page() ) {

			$meta_template = trim( rb_get_meta( 'header_template' ) );
			if ( ! empty( $meta_template ) ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => $meta_template,
				];
			}

			$page_header_style = rb_get_meta( 'header_style' );
			if ( ! empty( $page_header_style ) && 'default' !== (string) $page_header_style ) {
				$params['style'] = $page_header_style;
			}
		} elseif ( is_single() ) {

			$meta_template = trim( rb_get_meta( 'header_template' ) );
			if ( ! empty( $meta_template ) ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => $meta_template,
				];
			}

			$single_header_style = rb_get_meta( 'header_style' );
			if ( ! empty( $single_header_style ) && 'default' !== (string) $single_header_style ) {
				return [
						'style'     => $single_header_style,
						'shortcode' => '',
				];
			}

			/** post_format */
			$post_format = get_post_format( get_the_ID() );
			if ( $post_format ) {
				$option_name     = 'single_post_header_template_' . $post_format;
				$format_template = trim( foxiz_get_option( $option_name ) );
				if ( ! empty( $format_template ) ) {
					return [
							'style'     => 'rb_template',
							'shortcode' => $format_template,
					];
				}
			}

			$default_template = trim( foxiz_get_option( 'single_post_header_template' ) );
			if ( ! empty( $default_template ) ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => $default_template,
				];
			}

			if ( foxiz_get_option( 'single_post_header_style' ) ) {
				$params['style'] = foxiz_get_option( 'single_post_header_style' );
			}
		} elseif ( is_category() ) {

			$categories_data = rb_get_term_meta( 'foxiz_category_meta', get_queried_object_id() );
			if ( ! empty( $categories_data['header_template'] ) ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => trim( $categories_data['header_template'] ),
				];
			}
			if ( empty( $categories_data['header_style'] ) ) {

				$default_template = trim( foxiz_get_option( 'category_header_template' ) );
				if ( ! empty( $default_template ) ) {
					return [
							'style'     => 'rb_template',
							'shortcode' => $default_template,
					];
				}

				if ( foxiz_get_option( 'category_header_style' ) ) {
					$params['style'] = foxiz_get_option( 'category_header_style' );
				}
			} else {
				$params['style'] = $categories_data['header_style'];
			}
		} elseif ( is_search() ) {

			$default_template = trim( foxiz_get_option( 'search_header_template' ) );
			if ( $default_template ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => $default_template,
				];
			}

			if ( foxiz_get_option( 'search_header_style' ) ) {
				$params['style'] = foxiz_get_option( 'search_header_style' );
			}
		} elseif ( is_home() ) {

			$default_template = trim( foxiz_get_option( 'blog_header_template' ) );
			if ( $default_template ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => $default_template,
				];
			}

			if ( foxiz_get_option( 'blog_header_style' ) ) {
				$params['style'] = foxiz_get_option( 'blog_header_style' );
			}
		} elseif ( is_tax( 'series' ) ) {

			$categories_data = rb_get_term_meta( 'foxiz_category_meta', get_queried_object_id() );
			if ( ! empty( $categories_data['header_template'] ) ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => trim( $categories_data['header_template'] ),
				];
			}
			if ( empty( $categories_data['header_style'] ) ) {

				$default_template = trim( foxiz_get_option( 'series_header_template' ) );
				if ( ! empty( $default_template ) ) {
					return [
							'style'     => 'rb_template',
							'shortcode' => $default_template,
					];
				}

				if ( foxiz_get_option( 'series_header_style' ) ) {
					$params['style'] = foxiz_get_option( 'series_header_style' );
				}
			} else {
				$params['style'] = $categories_data['header_style'];
			}
		} elseif ( is_tag() ) {

			$tag_data = rb_get_term_meta( 'foxiz_category_meta', get_queried_object_id() );
			if ( ! empty( $tag_data['header_template'] ) ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => trim( $tag_data['header_template'] ),
				];
			} elseif ( ! empty( $tag_data['header_style'] ) ) {
				$params['style'] = $tag_data['header_style'];
			}
		} elseif ( is_tax() ) {

			$tax_data = rb_get_term_meta( 'foxiz_category_meta', get_queried_object_id() );
			if ( ! empty( $tax_data['header_template'] ) ) {
				return [
						'style'     => 'rb_template',
						'shortcode' => trim( $tax_data['header_template'] ),
				];
			} elseif ( ! empty( $tax_data['header_style'] ) ) {
				$params['style'] = $tax_data['header_style'];
			}
		}

		if ( empty( $params['style'] ) || 'default' === $params['style'] ) {

			$params['style'] = foxiz_get_option( 'header_style' );
			if ( 'rb_template' === $params['style'] ) {
				if ( foxiz_get_option( 'header_template' ) ) {
					$params['shortcode'] = foxiz_get_option( 'header_template' );
				} else {
					$params['style'] = 1;
				}
			}
		}

		return $params;
	}
}

if ( ! function_exists( 'foxiz_get_header_settings' ) ) {
	/**
	 * @param $prefix
	 *
	 * @return array
	 * get header settings
	 */
	function foxiz_get_header_settings( $prefix ) {

		$prefix = trim( $prefix ) . '_';

		$settings = foxiz_get_option();

		$settings['more']       = foxiz_get_option( $prefix . 'more' );
		$settings['nav_style']  = foxiz_get_option( $prefix . 'nav_style' );
		$settings['sub_scheme'] = foxiz_get_option( 'hd1_sub_scheme' );

		if ( is_singular() ) {
			$nav_style = rb_get_meta( 'nav_style' );
			if ( ! empty( $nav_style ) && 'default' !== $nav_style ) {
				$settings['nav_style'] = $nav_style;
			}
		} elseif ( is_category() ) {
			if ( ! empty ( $settings['category_nav_style'] ) ) {
				$settings['nav_style'] = $settings['category_nav_style'];
			}
		} elseif ( is_tax( 'series' ) ) {
			if ( ! empty ( $settings['series_nav_style'] ) ) {
				$settings['nav_style'] = $settings['series_nav_style'];
			}
		} elseif ( is_search() ) {
			if ( ! empty ( $settings['search_nav_style'] ) ) {
				$settings['nav_style'] = $settings['search_nav_style'];
			}
		} elseif ( is_home() ) {
			if ( ! empty ( $settings['blog_nav_style'] ) ) {
				$settings['nav_style'] = $settings['blog_nav_style'];
			}
		}

		$settings['header_socials'] = foxiz_get_option( $prefix . 'header_socials' );
		if ( empty( $settings['transparent_mobile_logo']['url'] ) ) {
			$settings['transparent_mobile_logo'] = foxiz_get_option( 'transparent_logo' );
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_design_standard_block' ) ) {
	/**
	 * @param array $settings
	 * @param string $prefix
	 *
	 * @return array|mixed
	 * get module settings
	 */
	function foxiz_get_design_standard_block( $settings = [], $prefix = '' ) {

		if ( ! empty( $settings['design_override'] ) ) {
			return $settings;
		}

		if ( '_' !== substr( $prefix, - 1 ) ) {
			$prefix = $prefix . '_';
		}
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = foxiz_get_option( $prefix . 'crop_size' );
		}

		if ( empty( $settings['featured_position'] ) ) {
			$settings['featured_position'] = foxiz_get_option( $prefix . 'featured_position' );
		}

		if ( empty( $settings['entry_category'] ) ) {
			$settings['entry_category'] = foxiz_get_option( $prefix . 'entry_category' );
		} elseif ( '-1' === (string) $settings['entry_category'] ) {
			$settings['entry_category'] = false;
		}

		if ( empty( $settings['hide_category'] ) ) {
			$settings['hide_category'] = foxiz_get_option( $prefix . 'hide_category' );
		} elseif ( '-1' === (string) $settings['hide_category'] ) {
			$settings['hide_category'] = false;
		}

		if ( ! empty( $settings['entry_meta_bar'] ) && '-1' === (string) $settings['entry_meta_bar'] ) {
			$settings['entry_meta'] = [];
		} else {
			if ( empty( $settings['entry_meta_bar'] ) || 'custom' !== (string) $settings['entry_meta_bar'] ) {
				$settings['entry_meta'] = foxiz_get_option( $prefix . 'entry_meta' );
			} else {
				if ( ! empty( $settings['entry_meta'] ) ) {
					$settings['entry_meta'] = explode( ',', trim( strval( $settings['entry_meta'] ) ) );
					$settings['entry_meta'] = array_map( 'trim', $settings['entry_meta'] );
				} else {
					$settings['entry_meta'] = [];
				}
			}
		}

		if ( empty( $settings['tablet_hide_meta'] ) ) {
			$settings['tablet_hide_meta'] = foxiz_get_option( $prefix . 'tablet_hide_meta' );
		} else {
			if ( '-1' !== (string) $settings['tablet_hide_meta'] ) {
				$settings['tablet_hide_meta'] = explode( ',', trim( strval( $settings['tablet_hide_meta'] ) ) );
				$settings['tablet_hide_meta'] = array_map( 'trim', $settings['tablet_hide_meta'] );
			} else {
				$settings['tablet_hide_meta'] = false;
			}
		}
		if ( empty( $settings['mobile_hide_meta'] ) ) {
			$settings['mobile_hide_meta'] = foxiz_get_option( $prefix . 'mobile_hide_meta' );
		} else {
			if ( '-1' !== (string) $settings['mobile_hide_meta'] ) {
				$settings['mobile_hide_meta'] = explode( ',', trim( strval( $settings['mobile_hide_meta'] ) ) );
				$settings['mobile_hide_meta'] = array_map( 'trim', $settings['mobile_hide_meta'] );
			} else {
				$settings['mobile_hide_meta'] = false;
			}
		}

		if ( ! empty( $settings['entry_meta'] ) ) {
			if ( ! empty( $settings['tablet_hide_meta'] ) ) {
				$tablet_meta             = array_diff( $settings['entry_meta'], $settings['tablet_hide_meta'] );
				$settings['tablet_last'] = end( $tablet_meta );
			}

			if ( ! empty( $settings['mobile_hide_meta'] ) ) {
				$mobile_meta             = array_diff( $settings['entry_meta'], $settings['mobile_hide_meta'] );
				$settings['mobile_last'] = end( $mobile_meta );
			}
		}

		if ( empty( $settings['review'] ) ) {
			$settings['review'] = foxiz_get_option( $prefix . 'review' );
		} elseif ( '-1' === (string) $settings['review'] ) {
			$settings['review'] = false;
		}

		if ( empty( $settings['review_meta'] ) ) {
			$settings['review_meta'] = foxiz_get_option( $prefix . 'review_meta' );
		} elseif ( '-1' === (string) $settings['review_meta'] ) {
			$settings['review_meta'] = false;
		}

		if ( empty( $settings['entry_format'] ) ) {
			$settings['entry_format'] = foxiz_get_option( $prefix . 'entry_format' );
		} elseif ( '-1' === (string) $settings['entry_format'] ) {
			$settings['entry_format'] = false;
		}

		if ( empty( $settings['bookmark'] ) ) {
			$settings['bookmark'] = foxiz_get_option( $prefix . 'bookmark' );
		} elseif ( '-1' === (string) $settings['bookmark'] ) {
			$settings['bookmark'] = false;
		}

		if ( empty( $settings['excerpt'] ) ) {
			$settings['excerpt_length'] = foxiz_get_option( $prefix . 'excerpt_length' );
			$settings['excerpt_source'] = foxiz_get_option( $prefix . 'excerpt_source' );
		}

		if ( empty( $settings['hide_excerpt'] ) ) {
			$settings['hide_excerpt'] = foxiz_get_option( $prefix . 'hide_excerpt' );
		} elseif ( '-1' === (string) $settings['hide_excerpt'] ) {
			$settings['hide_excerpt'] = false;
		}

		if ( empty( $settings['readmore'] ) ) {
			$settings['readmore'] = foxiz_get_option( $prefix . 'readmore' );
		} elseif ( '-1' === (string) $settings['readmore'] ) {
			$settings['readmore'] = false;
		}

		if ( ! empty( $settings['readmore'] ) ) {
			$settings['readmore'] = foxiz_get_readmore_label();
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = foxiz_get_option( $prefix . 'title_tag' );
		}

		if ( empty( $settings['sub_title_tag'] ) ) {
			$settings['sub_title_tag'] = foxiz_get_option( $prefix . 'sub_title_tag' );
		}

		if ( empty( $settings['sub_sub_title_tag'] ) ) {
			$settings['sub_sub_title_tag'] = foxiz_get_option( $prefix . 'sub_sub_title_tag' );
		}

		if ( ! empty( $settings['sponsor_meta'] ) && '-1' === (string) $settings['sponsor_meta'] ) {
			$settings['sponsor_meta'] = false;
		} elseif ( empty( $settings['sponsor_meta'] ) ) {
			$settings['sponsor_meta'] = foxiz_get_option( $prefix . 'sponsor_meta' );
		}

		if ( empty( $settings['title_classes'] ) ) {
			$settings['title_classes'] = foxiz_get_option( $prefix . 'title_classes' );
		}
		if ( empty( $settings['box_style'] ) ) {
			$settings['box_style'] = foxiz_get_option( $prefix . 'box_style' );
		}
		if ( empty( $settings['center_mode'] ) ) {
			$settings['center_mode'] = foxiz_get_option( $prefix . 'center_mode' );
		} elseif ( '-1' === (string) $settings['center_mode'] ) {
			$settings['center_mode'] = false;
		}
		if ( empty( $settings['middle_mode'] ) ) {
			$settings['middle_mode'] = foxiz_get_option( $prefix . 'middle_mode' );
		}
		if ( ! empty( $settings['slider'] ) && '-1' === (string) $settings['slider'] ) {
			$settings['slider'] = false;
		}
		if ( ! empty( $settings['carousel'] ) && '-1' === (string) $settings['carousel'] ) {
			$settings['carousel'] = false;
		}
		if ( ! empty( $settings['carousel_dot'] ) && '-1' === (string) $settings['carousel_dot'] ) {
			$settings['carousel_dot'] = false;
		}
		if ( ! empty( $settings['carousel_nav'] ) && '-1' === (string) $settings['carousel_nav'] ) {
			$settings['carousel_nav'] = false;
		}
		if ( empty( $settings['slider_play'] ) ) {
			$settings['slider_play'] = foxiz_get_option( 'slider_play' );
		} elseif ( '-1' === (string) $settings['slider_play'] ) {
			$settings['slider_play'] = false;
		}
		if ( empty( $settings['slider_speed'] ) ) {
			$settings['slider_speed'] = foxiz_get_option( 'slider_speed' );
		}
		if ( empty( $settings['slider_fmode'] ) ) {
			$settings['slider_fmode'] = foxiz_get_option( 'slider_fmode' );
		} elseif ( '-1' === (string) $settings['slider_fmode'] ) {
			$settings['slider_fmode'] = false;
		}
		if ( ! empty( $settings['feat_lazyload'] ) && strpos( $settings['feat_lazyload'], 'e-' ) !== false ) {
			$settings['eager_images'] = (int) str_replace( 'e-', '', $settings['feat_lazyload'] );
		}
		/** disable carousel & sliders */
		if ( foxiz_is_amp() ) {
			$settings['carousel'] = false;
			$settings['slider']   = false;
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_design_builder_block' ) ) {
	/**
	 * @param $settings
	 *
	 * @return array|mixed
	 */
	function foxiz_get_design_builder_block( $settings ) {

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		if ( ! empty( $settings['entry_category'] ) && '-1' === (string) $settings['entry_category'] ) {
			$settings['entry_category'] = false;
		}

		if ( ! empty( $settings['entry_format'] ) && '-1' === (string) $settings['entry_format'] ) {
			$settings['entry_format'] = false;
		}

		if ( ! empty( $settings['entry_meta'] ) ) {
			$settings['entry_meta'] = explode( ',', trim( strval( $settings['entry_meta'] ) ) );
			$settings['entry_meta'] = array_map( 'trim', $settings['entry_meta'] );

			if ( ! empty( $settings['tablet_hide_meta'] ) ) {
				if ( '-1' !== (string) $settings['tablet_hide_meta'] ) {
					$settings['tablet_hide_meta'] = explode( ',', trim( strval( $settings['tablet_hide_meta'] ) ) );
					$settings['tablet_hide_meta'] = array_map( 'trim', $settings['tablet_hide_meta'] );
					$tablet_meta                  = array_diff( $settings['entry_meta'], $settings['tablet_hide_meta'] );
					$settings['tablet_last']      = end( $tablet_meta );
				} else {
					$settings['tablet_hide_meta'] = false;
				}
			}

			if ( ! empty( $settings['mobile_hide_meta'] ) ) {
				if ( '-1' !== (string) $settings['mobile_hide_meta'] ) {
					$settings['mobile_hide_meta'] = explode( ',', trim( strval( $settings['mobile_hide_meta'] ) ) );
					$settings['mobile_hide_meta'] = array_map( 'trim', $settings['mobile_hide_meta'] );
					$mobile_meta                  = array_diff( $settings['entry_meta'], $settings['mobile_hide_meta'] );
					$settings['mobile_last']      = end( $mobile_meta );
				} else {
					$settings['mobile_hide_meta'] = false;
				}
			}
		}

		if ( ! empty( $settings['review'] ) && ( '-1' === (string) $settings['review'] ) ) {
			$settings['review'] = false;
		}

		if ( ! empty( $settings['bookmark'] ) && ( '-1' === (string) $settings['bookmark'] ) ) {
			$settings['bookmark'] = false;
		}
		if ( empty( $settings['readmore'] ) || '-1' === (string) $settings['readmore'] ) {
			$settings['readmore'] = false;
		} else {
			$settings['readmore'] = foxiz_get_readmore_label();
		}
		if ( ! empty( $settings['sponsor_meta'] ) && ( '-1' === (string) $settings['sponsor_meta'] ) ) {
			$settings['sponsor_meta'] = false;
		} elseif ( empty( $settings['sponsor_meta'] ) ) {
			$settings['sponsor_meta'] = 1;
		}
		if ( ! empty( $settings['center_mode'] ) && ( '-1' === (string) $settings['center_mode'] ) ) {
			$settings['center_mode'] = false;
		}
		if ( ! empty( $settings['slider'] ) && '-1' === (string) $settings['slider'] ) {
			$settings['slider'] = false;
		}
		if ( ! empty( $settings['carousel'] ) && '-1' === (string) $settings['carousel'] ) {
			$settings['carousel'] = false;
		}
		if ( ! empty( $settings['carousel_dot'] ) && '-1' === (string) $settings['carousel_dot'] ) {
			$settings['carousel_dot'] = false;
		}
		if ( ! empty( $settings['carousel_nav'] ) && '-1' === (string) $settings['carousel_nav'] ) {
			$settings['carousel_nav'] = false;
		}
		if ( empty( $settings['slider_play'] ) ) {
			$settings['slider_play'] = foxiz_get_option( 'slider_play' );
		} elseif ( '-1' === (string) $settings['slider_play'] ) {
			$settings['slider_play'] = false;
		}
		if ( empty( $settings['slider_speed'] ) ) {
			$settings['slider_speed'] = foxiz_get_option( 'slider_speed' );
		}
		if ( empty( $settings['slider_fmode'] ) ) {
			$settings['slider_fmode'] = foxiz_get_option( 'slider_fmode' );
		} elseif ( '-1' === (string) $settings['slider_fmode'] ) {
			$settings['slider_fmode'] = false;
		}
		if ( ! empty( $settings['feat_lazyload'] ) && strpos( $settings['feat_lazyload'], 'e-' ) !== false ) {
			$settings['eager_images'] = (int) str_replace( 'e-', '', $settings['feat_lazyload'] );
		}

		/** disable carousel & sliders */
		if ( foxiz_is_amp() ) {
			$settings['carousel'] = false;
			$settings['slider']   = false;
		}

		if ( ! empty( $settings['query_mode'] ) && 'global' === $settings['query_mode'] ) {
			if ( is_tax() ) {
				$settings['taxonomy'] = get_queried_object()->taxonomy;
			}
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_readmore_label' ) ) {
	/**
	 * @return false|mixed|string|void
	 */
	function foxiz_get_readmore_label() {

		$label = foxiz_get_option( 'readmore_label' );
		if ( empty( $label ) ) {
			return foxiz_html__( 'Read More', 'foxiz' );
		}

		return apply_filters( 'the_title_rss', $label, 10 );
	}
}

if ( ! function_exists( 'foxiz_has_featured_image' ) ) {
	/**
	 * @param string $size
	 *
	 * @return bool
	 * check featured image
	 */
	function foxiz_has_featured_image( $size = 'full' ) {

		if ( ! has_post_thumbnail() ) {
			return false;
		}

		$thumbnail = get_the_post_thumbnail( null, $size );
		if ( empty( $thumbnail ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'foxiz_detect_dynamic_query' ) ) {
	/**
	 * @param $settings
	 *
	 * @return mixed
	 * foxiz_detect_query
	 */
	function foxiz_detect_dynamic_query( $settings ) {

		if ( foxiz_is_template_preview() ) {

			/** Temporarily render layout in editor and preview */
			if ( ! empty( $settings['author'] ) && 'dynamic_author' === $settings['author'] ) {
				$settings['author'] = '';
			}
			if ( ! empty( $settings['category'] ) && 'dynamic' === $settings['category'] ) {
				$settings['category'] = '';
			}
			if ( ! empty( $settings['tags'] ) && in_array( strtolower( trim( $settings['tags'] ) ), [
							'_dynamic_tag',
							'{dynamic}',
					] )
			) {
				$settings['tags'] = '';
			}
			if ( ! empty( $settings['taxonomy'] ) && '{dynamic}' === strtolower( trim( $settings['taxonomy'] ) ) ) {
				$settings['taxonomy']  = '';
				$settings['tax_slugs'] = '';
			}

		} else {

			if ( is_category() ) {
				if ( ! empty( $settings['category'] ) && 'dynamic' === $settings['category'] ) {
					$settings['category'] = get_queried_object_id();
				}

				// Clear dynamic settings
				if ( ! empty( $settings['tags'] ) ) {
					$settings['tags'] = str_replace( [ '_dynamic_tag', '{dynamic}' ], '', $settings['tags'] );
				}
				if ( ! empty( $settings['author'] ) && 'dynamic_author' === $settings['author'] ) {
					$settings['author'] = '';
				}
				if ( ! empty( $settings['taxonomy'] ) && '{dynamic}' === strtolower( trim( $settings['taxonomy'] ) ) ) {
					$settings['taxonomy'] = '';
				}

			} elseif ( is_author() ) {
				if ( ! empty( $settings['author'] ) && 'dynamic_author' === $settings['author'] ) {
					$settings['author'] = get_queried_object_id();
				}

				// Clear dynamic settings
				if ( ! empty( $settings['category'] ) && 'dynamic' === $settings['category'] ) {
					$settings['category'] = '';
				}
				if ( ! empty( $settings['tags'] ) ) {
					$settings['tags'] = str_replace( [ '_dynamic_tag', '{dynamic}' ], '', $settings['tags'] );
				}
				if ( ! empty( $settings['taxonomy'] ) && '{dynamic}' === strtolower( trim( $settings['taxonomy'] ) ) ) {
					$settings['taxonomy'] = '';
				}
				
			} elseif ( is_tag() ) {
				if ( ! empty( $settings['tags'] ) && in_array( strtolower( trim( $settings['tags'] ) ), [
								'_dynamic_tag',
								'{dynamic}',
						] )
				) {
					$settings['tags'] = get_queried_object()->slug;
				}

				// Clear dynamic settings
				if ( ! empty( $settings['category'] ) && 'dynamic' === $settings['category'] ) {
					$settings['category'] = '';
				}
				if ( ! empty( $settings['author'] ) && 'dynamic_author' === $settings['author'] ) {
					$settings['author'] = '';
				}
				if ( ! empty( $settings['taxonomy'] ) && '{dynamic}' === strtolower( trim( $settings['taxonomy'] ) ) ) {
					$settings['taxonomy'] = '';
				}

			} elseif ( is_tax() ) {
				if ( ! empty( $settings['taxonomy'] ) && '{dynamic}' === strtolower( trim( $settings['taxonomy'] ) ) ) {
					$settings['taxonomy']  = get_queried_object()->taxonomy;
					$settings['tax_slugs'] = get_queried_object()->slug;
				}

				// Clear dynamic settings
				if ( ! empty( $settings['category'] ) && 'dynamic' === $settings['category'] ) {
					$settings['category'] = '';
				}
				if ( ! empty( $settings['author'] ) && 'dynamic_author' === $settings['author'] ) {
					$settings['author'] = '';
				}
				if ( ! empty( $settings['tags'] ) ) {
					$settings['tags'] = str_replace( [ '_dynamic_tag', '{dynamic}' ], '', $settings['tags'] );
				}
			} elseif ( is_home() ) {

				// Clear dynamic settings
				if ( ! empty( $settings['category'] ) && 'dynamic' === $settings['category'] ) {
					$settings['category'] = '';
				}
				if ( ! empty( $settings['author'] ) && 'dynamic_author' === $settings['author'] ) {
					$settings['author'] = '';
				}
				if ( ! empty( $settings['tags'] ) ) {
					$settings['tags'] = str_replace( [ '_dynamic_tag', '{dynamic}' ], '', $settings['tags'] );
				}
				if ( ! empty( $settings['taxonomy'] ) && '{dynamic}' === strtolower( trim( $settings['taxonomy'] ) ) ) {
					$settings['taxonomy'] = '';
				}
			}
		}

		if ( empty( $settings['unique'] ) || '-1' === (string) $settings['unique'] ) {
			$settings['unique'] = false;
		}

		/** top post filter */
		if ( defined( 'JETPACK__VERSION' ) ) {
			if ( ! empty( $settings['jetpack_top_posts'] ) && '1' === (string) $settings['jetpack_top_posts'] ) {
				$settings['post_in'] = foxiz_get_top_post_ids( $settings );
				$settings['order']   = 'by_input';
			}
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_single_setting' ) ) {
	/**
	 * @param        $name
	 * @param string $opt_name
	 * @param string $post_id
	 *
	 * @return false|mixed|void
	 */
	function foxiz_get_single_setting( $name, $opt_name = '', $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$setting = rb_get_meta( $name, $post_id );

		if ( empty( $setting ) || 'default' === $setting ) {
			if ( empty( $opt_name ) ) {
				$opt_name = 'single_post_' . $name;
			}
			$setting = foxiz_get_option( $opt_name );
		}

		if ( ! is_array( $setting ) && '-1' === (string) $setting ) {
			return false;
		}

		return $setting;
	}
}

if ( ! function_exists( 'foxiz_get_review_settings' ) ) {
	/**
	 * @param string $post_id
	 *
	 * @return array|false
	 */
	function foxiz_get_review_settings( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		if ( ! foxiz_is_review_post( $post_id ) ) {
			return false;
		}

		$settings = [
				'average'     => '',
				'title'       => rb_get_meta( 'review_title', $post_id ),
				'type'        => foxiz_get_single_setting( 'review_type' ),
				'criteria'    => rb_get_meta( 'review_criteria', $post_id ),
				'user'        => foxiz_get_single_setting( 'user_can_review' ),
				'image'       => foxiz_get_single_setting( 'review_image' ),
				'meta'        => rb_get_meta( 'review_meta', $post_id ),
				'pros'        => rb_get_meta( 'review_pros', $post_id ),
				'cons'        => rb_get_meta( 'review_cons', $post_id ),
				'summary'     => rb_get_meta( 'review_summary', $post_id ),
				'button'      => rb_get_meta( 'review_button', $post_id ),
				'destination' => rb_get_meta( 'review_destination', $post_id ),
				'price'       => rb_get_meta( 'review_price', $post_id ),
				'currency'    => rb_get_meta( 'review_currency', $post_id ),
				'expired'     => rb_get_meta( 'review_price_valid', $post_id ),
				'schema'      => foxiz_get_single_setting( 'review_schema' ),
				'user_rating' => get_post_meta( $post_id, 'foxiz_user_rating', true ),
		];

		if ( is_array( $settings['criteria'] ) ) {
			$index = 0;
			$total = 0;
			foreach ( $settings['criteria'] as $item ) {
				if ( ! empty( $item['rating'] ) ) {
					$value = floatval( $item['rating'] );
					if ( empty( $settings['type'] ) || 'star' === $settings['type'] ) {
						if ( $value > 5 ) {
							$value = 5;
						}
					} else {
						if ( $value > 10 ) {
							$value = 10;
						}
					}

					$total += $value;
					$index ++;
				}
			}

			if ( ! empty( $index ) && ! empty( $total ) ) {
				$settings['average'] = round( $total / $index, 1 );
			}
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_single_sidebar_name' ) ) {
	/**
	 * @param string $post_id
	 *
	 * @return array|false|mixed|void
	 */
	function foxiz_get_single_sidebar_name( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$setting = rb_get_meta( 'sidebar_name', $post_id );
		if ( ! empty( $setting ) && 'default' !== $setting ) {
			return $setting;
		}

		$post_type = get_post_type( $post_id );
		if ( 'post' !== $post_type ) {
			$setting = foxiz_get_option( 'single_' . $post_type . '_sidebar_name' );
			if ( ! empty( $setting ) && 'default' !== $setting ) {
				return $setting;
			}
		}

		return foxiz_get_option( 'single_post_sidebar_name', 'foxiz_sidebar_default' );
	}
}

if ( ! function_exists( 'foxiz_get_single_sidebar_position' ) ) {
	/**
	 * @param string $name
	 * @param string $opt_name
	 * @param string $post_id
	 *
	 * @return false|mixed|string|void
	 */
	function foxiz_get_single_sidebar_position( $name = 'sidebar_position', $opt_name = '', $post_id = '' ) {

		if ( foxiz_is_amp() && foxiz_get_option( 'amp_disable_single_sidebar' ) ) {
			return 'none';
		}

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$setting = '';
		if ( ! empty( $name ) ) {
			$setting = rb_get_meta( $name, $post_id );
		}
		if ( empty( $setting ) || 'default' === $setting ) {
			if ( empty( $opt_name ) ) {
				$opt_name = 'single_post_' . $name;
			}
			$setting = foxiz_get_option( $opt_name );
		}

		if ( empty( $setting ) || 'default' === $setting ) {
			$setting = foxiz_get_option( 'global_sidebar_position' );
		}

		return $setting;
	}
}

/**
 * Retrieves the single post layout configuration.
 *
 * This function stores the layout data globally to optimize performance.
 * If the layout has already been stored, it returns the cached value.
 * Otherwise, it fetches the raw layout data and applies modifications
 * for AMP mode if necessary before caching and returning the result.
 *
 * @return array The single post layout configuration.
 */
if ( ! function_exists( 'foxiz_get_single_layout' ) ) {
	function foxiz_get_single_layout() {

		// Store data layout as a global value to optimize speed.
		if ( ! empty( $GLOBALS['foxiz_single_layout'] ) && is_array( $GLOBALS['foxiz_single_layout'] ) ) {
			return $GLOBALS['foxiz_single_layout'];
		}

		$data = foxiz_get_raw_single_layout();

		if ( foxiz_is_amp() && 'stemplate' === $data['layout'] ) {
			$data['layout'] = 'standard_7';
		}

		$GLOBALS['foxiz_single_layout'] = $data;

		return $data;
	}
}

/**
 * Retrieves the raw single post layout configuration.
 *
 * This function fetches layout settings based on the post ID, post type,
 * and post format. It serves as the base function for determining the
 * single post layout before any modifications (such as AMP adjustments).
 *
 * @return array The raw single post layout configuration.
 */
if ( ! function_exists( 'foxiz_get_raw_single_layout' ) ) {
	function foxiz_get_raw_single_layout() {

		$post_id   = get_the_ID();
		$post_type = get_post_type();
		$format    = get_post_format( $post_id );

		if ( empty( $format ) ) {
			$format = 'standard';
		}

		$data = [
				'format'    => $format,
				'layout'    => '',
				'shortcode' => '',
		];

		/** individual_template */
		$individual_template = trim( rb_get_meta( 'single_template', $post_id ) );
		if ( ! empty( $individual_template ) ) {
			return [
					'format'    => $format,
					'layout'    => 'stemplate',
					'shortcode' => $individual_template,
			];
		}

		if ( 'post' === $post_type ) {
			/** template base on category */
			$category = rb_get_meta( 'primary_category', $post_id );
			if ( empty( $category ) ) {
				$categories = get_the_category( $post_id );
				if ( ! empty( $categories[0] ) ) {
					$category = $categories[0]->term_id;
				}
			}

			if ( ! empty( $category ) ) {
				$template = trim( get_term_meta( $category, '_rb_post_template', true ) );
				if ( ! empty( $template ) ) {
					return [
							'format'    => $format,
							'layout'    => 'stemplate',
							'shortcode' => $template,
					];
				} else {
					$layout = get_term_meta( $category, '_rb_post_layout', true );
					if ( ! empty( $layout ) && 'standard' === (string) $format ) {
						$data['layout'] = $layout;

						return $data;
					}
				}
			}

			/** post format */
			switch ( $format ) {
				case 'video' :

					$individual_layout = rb_get_meta( 'video_layout', $post_id );
					if ( ! empty( $individual_layout ) && 'default' !== $individual_layout ) {
						$data['layout'] = $individual_layout;
					} else {
						$template = trim( foxiz_get_option( 'single_post_video_template' ) );
						if ( ! empty( $template ) ) {
							$data['layout']    = 'stemplate';
							$data['shortcode'] = $template;
						} else {
							$data['layout'] = foxiz_get_option( 'single_post_video_layout' );
						}
					}
					break;
				case 'audio' :

					$individual_layout = rb_get_meta( 'audio_layout', $post_id );
					if ( ! empty( $individual_layout ) && 'default' !== $individual_layout ) {
						$data['layout'] = $individual_layout;
					} else {
						$template = trim( foxiz_get_option( 'single_post_audio_template' ) );
						if ( ! empty( $template ) ) {
							$data['layout']    = 'stemplate';
							$data['shortcode'] = $template;
						} else {
							$data['layout'] = foxiz_get_option( 'single_post_audio_layout' );
						}
					}
					break;
				case 'gallery' :

					$individual_layout = rb_get_meta( 'gallery_layout', $post_id );
					if ( ! empty( $individual_layout ) && 'default' !== $individual_layout ) {
						$data['layout'] = $individual_layout;
					} else {
						$template = trim( foxiz_get_option( 'single_post_gallery_template' ) );
						if ( ! empty( $template ) ) {
							$data['layout']    = 'stemplate';
							$data['shortcode'] = $template;
						} else {
							$data['layout'] = foxiz_get_option( 'single_post_gallery_layout' );
						}
					}
					break;

				default:

					$individual_layout = rb_get_meta( 'layout', $post_id );
					if ( ! empty( $individual_layout ) && 'default' !== $individual_layout ) {
						$data['layout'] = $individual_layout;
					} else {
						$template = trim( foxiz_get_option( 'single_post_standard_template' ) );
						if ( ! empty( $template ) ) {
							$data['layout']    = 'stemplate';
							$data['shortcode'] = $template;
						} else {
							$data['layout'] = foxiz_get_option( 'single_post_layout' );
						}
					}
			}
		} else {
			/** custom post type */
			$individual_layout = rb_get_meta( 'layout', $post_id );
			if ( ! empty( $individual_layout ) && 'default' !== $individual_layout ) {
				$data['layout'] = $individual_layout;
			} else {
				$post_type_template = trim( foxiz_get_option( 'post_type_template_' . $post_type ) );
				if ( ! empty( $post_type_template ) ) {
					$data['layout']    = 'stemplate';
					$data['shortcode'] = $post_type_template;
				} else {
					$post_type_layout = foxiz_get_option( 'post_type_layout_' . $post_type );
					if ( ! empty( $post_type_layout ) && 'default' !== $post_type_layout ) {
						$data['layout'] = $post_type_layout;
					} else {
						$post_type_template = trim( foxiz_get_option( 'post_type_template' ) );
						if ( ! empty( $post_type_template ) ) {
							$data['layout']    = 'stemplate';
							$data['shortcode'] = $post_type_template;
						} else {
							$data['layout'] = foxiz_get_option( 'post_type_layout' );
						}
					}
				}
			}
		}

		if ( empty( $data['layout'] ) ) {
			$data['layout'] = 'standard_1';
		}

		return $data;
	}
}

if ( ! function_exists( 'foxiz_get_related_data' ) ) {
	/**
	 * @param array $settings
	 *
	 * @return WP_Query
	 */
	function foxiz_get_related_data( $settings = [] ) {

		$params = [
				'no_found_rows' => true,
				'unique'        => true,
		];

		if ( ! empty( $settings['total'] ) ) {
			$params['posts_per_page'] = $settings['total'];
		}
		if ( ! empty( $settings['ids'] ) ) {
			$params['post_in'] = strip_tags( $settings['ids'] );
		}
		if ( ! empty( $settings['post_id'] ) ) {
			$params['related_id'] = strip_tags( $settings['post_id'] );
		}
		if ( ! empty( $settings['where'] ) ) {
			$params['where'] = strip_tags( $settings['where'] );
		}
		if ( ! empty( $settings['order'] ) ) {
			$params['orderby'] = strip_tags( $settings['order'] );
		}

		return foxiz_query_related( $params );
	}
}

if ( ! function_exists( 'foxiz_get_single_sticky_setting' ) ) {
	function foxiz_get_single_sticky_setting( $prefix ) {

		$setting = foxiz_get_option( $prefix . '_sticky_sidebar' );
		if ( empty( $setting ) || 'default' === $setting ) {
			$setting = foxiz_get_option( 'sticky_sidebar' );
		}

		if ( '-1' === (string) $setting ) {
			$setting = '';
		}

		return $setting;
	}
}

if ( ! function_exists( 'foxiz_get_single_sticky_sidebar' ) ) {
	function foxiz_get_single_sticky_sidebar( $prefix = 'single_post' ) {

		$setting = (string) foxiz_get_single_sticky_setting( $prefix );

		if ( '2' === $setting ) {
			return 'sticky-last-w';
		} elseif ( '1' === $setting ) {
			return 'sticky-sidebar';
		}

		return '';
	}
}

if ( ! function_exists( 'foxiz_get_category_page_settings' ) ) {
	function foxiz_get_category_page_settings( $category_id = '' ) {

		if ( ! is_category() ) {
			return false;
		}
		$prefix = 'category_';
		if ( empty( $category_id ) ) {
			$category_id = get_queried_object_id();
		}

		$settings = rb_get_term_meta( 'foxiz_category_meta', $category_id );

		$settings['category']      = $category_id;
		$settings['category_name'] = get_cat_name( $category_id );
		$settings['uuid']          = 'uid_c' . $category_id;

		if ( foxiz_get_option( $prefix . 'entry_tag' ) ) {
			$settings['entry_tax'] = 'post_tag';
		}

		if ( empty( $settings['category_header'] ) ) {
			$settings['category_header'] = foxiz_get_option( $prefix . 'category_header' );
		}
		if ( empty( $settings['breadcrumb'] ) ) {
			$settings['breadcrumb'] = foxiz_get_option( $prefix . 'breadcrumb' );
		}

		if ( '-1' === (string) $settings['breadcrumb'] ) {
			$settings['breadcrumb'] = false;
		}

		if ( empty( $settings['featured_image'] ) || ! is_array( $settings['featured_image'] ) || ! count( $settings['featured_image'] ) ) {
			$settings['featured_image'] = foxiz_get_option( $prefix . 'featured_image' );
			if ( ! empty( $settings['featured_image'] ) ) {
				$settings['featured_image'] = explode( ',', $settings['featured_image'] );
			}
		}
		if ( empty( $settings['pattern'] ) ) {
			$settings['pattern'] = foxiz_get_option( $prefix . 'pattern' );
		}
		if ( empty( $settings['subcategory'] ) ) {
			$settings['subcategory'] = foxiz_get_option( $prefix . 'subcategory' );
		}
		if ( '-1' === (string) $settings['subcategory'] ) {
			$settings['subcategory'] = false;
		}
		if ( empty( $settings['follow_category_header'] ) ) {
			$settings['follow_category_header'] = foxiz_get_option( 'follow_category_header' );
		}
		if ( empty( $settings['template'] ) ) {
			$settings['template'] = foxiz_get_option( $prefix . 'template' );
		}
		if ( empty( $settings['template_display'] ) ) {
			$settings['template_display'] = foxiz_get_option( $prefix . 'template_display' );
		}
		if ( empty( $settings['template_global'] ) && empty( $settings['layout'] ) ) {
			$settings['template_global'] = foxiz_get_option( $prefix . 'template_global' );
		}
		if ( empty( $settings['blog_heading'] ) ) {
			$settings['blog_heading'] = foxiz_get_option( $prefix . 'blog_heading' );
		}
		if ( empty( $settings['blog_heading_layout'] ) ) {
			$settings['blog_heading_layout'] = foxiz_get_option( $prefix . 'blog_heading_layout' );
		}
		if ( empty( $settings['blog_heading_tag'] ) ) {
			$settings['blog_heading_tag'] = foxiz_get_option( $prefix . 'blog_heading_tag' );
		}
		if ( empty( $settings['posts_per_page'] ) ) {
			$settings['posts_per_page'] = foxiz_get_option( $prefix . 'posts_per_page' );
		}
		if ( empty( $settings['pagination'] ) ) {
			$settings['pagination'] = foxiz_get_option( $prefix . 'pagination' );
		}
		if ( empty( $settings['layout'] ) ) {
			$settings['layout'] = foxiz_get_option( $prefix . 'layout' );
		}
		if ( empty( $settings['columns'] ) ) {
			$settings['columns'] = foxiz_get_option( $prefix . 'columns' );
		}
		if ( empty( $settings['columns_tablet'] ) ) {
			$settings['columns_tablet'] = foxiz_get_option( $prefix . 'columns_tablet' );
		}
		if ( empty( $settings['columns_mobile'] ) ) {
			$settings['columns_mobile'] = foxiz_get_option( $prefix . 'columns_mobile' );
		}
		if ( empty( $settings['column_gap'] ) ) {
			$settings['column_gap'] = foxiz_get_option( $prefix . 'column_gap' );
		}
		if ( empty( $settings['sidebar_position'] ) ) {
			$settings['sidebar_position'] = foxiz_get_option( $prefix . 'sidebar_position' );
		}
		if ( empty( $settings['sidebar_name'] ) || 'default' === $settings['sidebar_name'] ) {
			$settings['sidebar_name'] = foxiz_get_option( $prefix . 'sidebar_name' );
		}
		if ( empty( $settings['sticky_sidebar'] ) ) {
			$settings['sticky_sidebar'] = foxiz_get_option( $prefix . 'sticky_sidebar' );
			if ( empty( $settings['sticky_sidebar'] ) ) {
				$settings['sticky_sidebar'] = foxiz_get_option( 'sticky_sidebar' );
			}
		}
		if ( '-1' === (string) $settings['sticky_sidebar'] ) {
			$settings['sticky_sidebar'] = false;
		}

		/** blog design */
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = foxiz_get_option( $prefix . 'crop_size' );
		}
		if ( empty( $settings['entry_category'] ) ) {
			$settings['entry_category'] = foxiz_get_option( $prefix . 'entry_category' );
		}

		if ( empty( $settings['entry_meta_bar'] ) ) {
			$settings['entry_meta_bar'] = foxiz_get_option( $prefix . 'entry_meta_bar' );
			if ( ! empty( $settings['entry_meta_bar'] ) && 'custom' === $settings['entry_meta_bar'] ) {
				$settings['entry_meta'] = foxiz_get_option( $prefix . 'entry_meta' );
				if ( is_array( $settings['entry_meta'] ) ) {
					$settings['entry_meta'] = implode( ',', $settings['entry_meta'] );
				}
			}
		}

		if ( empty( $settings['review'] ) ) {
			$settings['review'] = foxiz_get_option( $prefix . 'review' );
		}
		if ( empty( $settings['review_meta'] ) ) {
			$settings['review_meta'] = foxiz_get_option( $prefix . 'review_meta' );
		}
		if ( empty( $settings['entry_format'] ) ) {
			$settings['entry_format'] = foxiz_get_option( $prefix . 'entry_format' );
		}
		if ( empty( $settings['bookmark'] ) ) {
			$settings['bookmark'] = foxiz_get_option( $prefix . 'bookmark' );
		}

		if ( empty( $settings['excerpt'] ) ) {
			$settings['excerpt'] = foxiz_get_option( $prefix . 'excerpt' );
			if ( ! empty( $settings['excerpt'] ) ) {
				$settings['excerpt_length'] = foxiz_get_option( $prefix . 'excerpt_length' );
				$settings['excerpt_source'] = foxiz_get_option( $prefix . 'excerpt_source' );
			}
		}
		if ( empty( $settings['readmore'] ) ) {
			$settings['readmore'] = foxiz_get_option( $prefix . 'readmore' );
		}
		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = foxiz_get_option( $prefix . 'title_tag' );
		}
		if ( empty( $settings['hide_category'] ) ) {
			$settings['hide_category'] = foxiz_get_option( $prefix . 'hide_category' );
		}
		if ( empty( $settings['tablet_hide_meta'] ) ) {
			$settings['tablet_hide_meta'] = foxiz_get_option( $prefix . 'tablet_hide_meta' );
		}
		if ( empty( $settings['mobile_hide_meta'] ) ) {
			$settings['mobile_hide_meta'] = foxiz_get_option( $prefix . 'mobile_hide_meta' );
		}
		if ( empty( $settings['hide_excerpt'] ) ) {
			$settings['hide_excerpt'] = foxiz_get_option( $prefix . 'hide_excerpt' );
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_archive_page_settings' ) ) {
	function foxiz_get_archive_page_settings( $prefix = '', $settings = [] ) {

		if ( empty( $prefix ) ) {
			$prefix = 'archive_';
		}

		$settings['uuid']                = 'uid_archive';
		$settings['archive_header']      = foxiz_get_option( $prefix . 'archive_header' );
		$settings['blog_heading']        = foxiz_get_option( $prefix . 'blog_heading' );
		$settings['blog_heading_layout'] = foxiz_get_option( $prefix . 'blog_heading_layout' );
		$settings['blog_heading_tag']    = foxiz_get_option( $prefix . 'blog_heading_tag' );
		$settings['pattern']             = foxiz_get_option( $prefix . 'pattern' );
		$settings['template']            = foxiz_get_option( $prefix . 'template' );
		$settings['template_bottom']     = foxiz_get_option( $prefix . 'template_bottom' );
		$settings['template_display']    = foxiz_get_option( $prefix . 'template_display' );
		$settings['template_global']     = foxiz_get_option( $prefix . 'template_global' );
		$settings['breadcrumb']          = foxiz_get_option( $prefix . 'breadcrumb' );
		$settings['posts_per_page']      = foxiz_get_option( $prefix . 'posts_per_page' );
		$settings['pagination']          = foxiz_get_option( $prefix . 'pagination' );

		if ( empty( $settings['posts_per_page'] ) ) {
			$settings['posts_per_page'] = get_option( 'posts_per_page' );
		}

		if ( empty( $settings['pagination'] ) ) {
			$settings['pagination'] = 'number';
		}

		$settings['layout']           = foxiz_get_option( $prefix . 'layout' );
		$settings['columns']          = foxiz_get_option( $prefix . 'columns' );
		$settings['columns_tablet']   = foxiz_get_option( $prefix . 'columns_tablet' );
		$settings['columns_mobile']   = foxiz_get_option( $prefix . 'columns_mobile' );
		$settings['column_gap']       = foxiz_get_option( $prefix . 'column_gap' );
		$settings['sidebar_position'] = foxiz_get_option( $prefix . 'sidebar_position' );
		$settings['sidebar_name']     = foxiz_get_option( $prefix . 'sidebar_name' );

		$settings['sticky_sidebar'] = foxiz_get_option( $prefix . 'sticky_sidebar' );
		if ( empty( $settings['sticky_sidebar'] ) ) {
			$settings['sticky_sidebar'] = foxiz_get_option( 'sticky_sidebar' );
		}
		if ( '-1' === (string) $settings['sticky_sidebar'] ) {
			$settings['sticky_sidebar'] = false;
		}

		$settings['crop_size']      = foxiz_get_option( $prefix . 'crop_size' );
		$settings['entry_category'] = foxiz_get_option( $prefix . 'entry_category' );
		$settings['entry_meta_bar'] = foxiz_get_option( $prefix . 'entry_meta_bar' );
		if ( ! empty( $settings['entry_meta_bar'] ) && 'custom' === $settings['entry_meta_bar'] ) {
			$settings['entry_meta'] = foxiz_get_option( $prefix . 'entry_meta' );
			if ( is_array( $settings['entry_meta'] ) ) {
				$settings['entry_meta'] = implode( ',', $settings['entry_meta'] );
			}
		}
		$settings['review']       = foxiz_get_option( $prefix . 'review' );
		$settings['review_meta']  = foxiz_get_option( $prefix . 'review_meta' );
		$settings['entry_format'] = foxiz_get_option( $prefix . 'entry_format' );
		$settings['bookmark']     = foxiz_get_option( $prefix . 'bookmark' );
		$settings['excerpt']      = foxiz_get_option( $prefix . 'excerpt' );
		if ( ! empty( $settings['excerpt'] ) ) {
			$settings['excerpt_length'] = foxiz_get_option( $prefix . 'excerpt_length' );
			$settings['excerpt_source'] = foxiz_get_option( $prefix . 'excerpt_source' );
		}
		$settings['readmore']         = foxiz_get_option( $prefix . 'readmore' );
		$settings['title_tag']        = foxiz_get_option( $prefix . 'title_tag' );
		$settings['hide_category']    = foxiz_get_option( $prefix . 'hide_category' );
		$settings['tablet_hide_meta'] = foxiz_get_option( $prefix . 'tablet_hide_meta' );
		$settings['mobile_hide_meta'] = foxiz_get_option( $prefix . 'mobile_hide_meta' );
		$settings['hide_excerpt']     = foxiz_get_option( $prefix . 'hide_excerpt' );

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_flex_archive_settings' ) ) {
	function foxiz_get_flex_archive_settings() {

		$archive  = get_queried_object();
		$settings = foxiz_get_archive_page_settings();

		if ( is_tax() ) {
			$tax_id           = get_queried_object_id();
			$data             = rb_get_term_meta( 'foxiz_category_meta', $tax_id );
			$settings['uuid'] = 'uid_tax_' . $tax_id;

			$archive_header  = ! empty( $data['archive_header'] ) ? $data['archive_header'] : foxiz_get_option( $archive->taxonomy . '_tax_header', foxiz_get_option( 'tax_header' ) );
			$template_global = ! empty( $data['template_global'] ) ? $data['template_global'] : foxiz_get_option( $archive->taxonomy . '_tax_template_global', foxiz_get_option( 'tax_template_global' ) );
			$posts_per_page  = ! empty( $data['posts_per_page'] ) ? $data['posts_per_page'] : foxiz_get_option( $archive->taxonomy . '_tax_posts_per_page', foxiz_get_option( 'tax_posts_per_page' ) );

			if ( ! empty( $archive_header ) ) {
				$settings['archive_header'] = $archive_header;
			}
			if ( ! empty( $template_global ) ) {
				$settings['template_global'] = $template_global;
			}
			if ( ! empty( $posts_per_page ) ) {
				$settings['posts_per_page'] = $posts_per_page;
			}
			if ( empty( $data['breadcrumb'] ) || '-1' === (string) $data['breadcrumb'] ) {
				$settings['breadcrumb'] = false;
			}
			if ( ! empty( $data['pattern'] ) ) {
				$settings['pattern'] = $data['pattern'];
			}

			return $settings;
		}

		if ( is_post_type_archive() ) {

			$key = $archive->name;

			$archive_header  = foxiz_get_option( $key . '_archive_header' );
			$template_global = foxiz_get_option( $key . '_archive_template_global' );
			$posts_per_page  = foxiz_get_option( $key . '_archive_posts_per_page' );
			if ( ! empty( $archive_header ) ) {
				$settings['archive_header'] = $archive_header;
			}
			if ( ! empty( $template_global ) ) {
				$settings['template_global'] = $template_global;
			}
			if ( ! empty( $posts_per_page ) ) {
				$settings['posts_per_page'] = $posts_per_page;
			}

			return $settings;
		}

		return $settings;
	}
}
if ( ! function_exists( 'foxiz_get_author_page_settings' ) ) {
	function foxiz_get_author_page_settings() {

		$author_id = get_queried_object_id();

		$settings                         = foxiz_get_archive_page_settings( 'author_' );
		$settings['uuid']                 = 'uid_author_' . $author_id;
		$settings['follow_author_header'] = foxiz_get_option( 'follow_author_header' );
		$custom_template                  = get_user_meta( $author_id, 'template_global', true );
		if ( ! empty( $custom_template ) ) {
			$settings['template_global'] = $custom_template;
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_search_page_settings' ) ) {
	function foxiz_get_search_page_settings() {

		$settings                    = foxiz_get_archive_page_settings( 'search_' );
		$settings['search_header']   = foxiz_get_option( 'search_header' );
		$settings['header_scheme']   = foxiz_get_option( 'search_header_scheme' );
		$settings['s']               = get_search_query( 's' );
		$settings['posts_per_page']  = foxiz_get_option( 'search_posts_per_page' );
		$settings['template_global'] = foxiz_get_option( 'search_template_global' );
		$settings['top_template']    = foxiz_get_option( 'search_top_template' );

		if ( empty( $settings['posts_per_page'] ) ) {
			$settings['posts_per_page'] = get_option( 'posts_per_page' );
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_tag_page_settings' ) ) {
	function foxiz_get_tag_page_settings() {

		$settings = foxiz_get_archive_page_settings();
		$tag_id   = get_queried_object_id();
		$data     = rb_get_term_meta( 'foxiz_category_meta', $tag_id );

		$tag_header       = ! empty( $data['archive_header'] ) ? $data['archive_header'] : foxiz_get_option( 'tag_header' );
		$template_global  = ! empty( $data['template_global'] ) ? $data['template_global'] : foxiz_get_option( 'tag_template_global' );
		$posts_per_page   = ! empty( $data['posts_per_page'] ) ? $data['posts_per_page'] : foxiz_get_option( 'tag_posts_per_page' );
		$template         = ! empty( $data['template'] ) ? $data['template'] : foxiz_get_option( 'tag_template' );
		$template_display = ! empty( $data['template_display'] ) ? $data['template_display'] : foxiz_get_option( 'tag_template_display' );

		$settings['uuid'] = 'uid_tag_' . $tag_id;

		if ( empty( $data['breadcrumb'] ) || '-1' === (string) $data['breadcrumb'] ) {
			$settings['breadcrumb'] = false;
		}
		if ( ! empty( $data['pattern'] ) ) {
			$settings['pattern'] = $data['pattern'];
		}
		if ( ! empty( $template_global ) ) {
			$settings['template_global'] = $template_global;
		}
		if ( ! empty( $tag_header ) ) {
			$settings['archive_header'] = $tag_header;
		}
		if ( ! empty( $posts_per_page ) ) {
			$settings['posts_per_page'] = $posts_per_page;
		}
		if ( ! empty( $template ) ) {
			$settings['template'] = $template;
		}
		if ( ! empty( $template_display ) ) {
			$settings['template_display'] = $template_display;
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_page_header_style' ) ) {
	function foxiz_get_page_header_style( $page_id = '' ) {

		if ( empty( $page_id ) ) {
			$page_id = get_the_ID();
		}

		$setting = rb_get_meta( 'page_header_style', $page_id );
		if ( empty( $setting ) || 'default' === $setting ) {
			$setting = foxiz_get_option( 'page_page_header_style' );
		}

		if ( empty( $setting ) ) {
			$setting = '1';
		}

		return $setting;
	}
}

if ( ! function_exists( 'foxiz_get_page_content_width' ) ) {
	/**
	 * @param string $page_id
	 *
	 * @return false|mixed|void
	 */
	function foxiz_get_page_content_width( $page_id = '' ) {

		if ( empty( $page_id ) ) {
			$page_id = get_the_ID();
		}
		$setting = rb_get_meta( 'width_wo_sb', $page_id );

		if ( empty( $setting ) || 'default' === $setting ) {
			$setting = foxiz_get_option( 'page_width_wo_sb' );
		} elseif ( '-1' === (string) $setting ) {
			return false;
		}

		return $setting;
	}
}

if ( ! function_exists( 'foxiz_is_sw_header' ) ) {
	/**
	 * @param string $page_id
	 *
	 * @return false|mixed|void
	 */
	function foxiz_is_sw_header( $page_id = '' ) {

		if ( empty( $page_id ) ) {
			$page_id = get_the_ID();
		}

		$setting = rb_get_meta( 'page_header_width', $page_id );
		if ( empty( $setting ) || 'default' === $setting ) {
			$setting = foxiz_get_option( 'page_header_width' );
		}

		if ( 'small' === $setting ) {
			return true;
		}

		return false;
	}
}

/**
 * @param array $settings
 */
if ( ! function_exists( 'foxiz_carousel_footer' ) ) {
	function foxiz_carousel_footer( $settings = [] ) {

		if ( ! empty( $settings['carousel_dot'] ) || ! empty( $settings['carousel_nav'] ) ) :
			$classes = 'slider-footer';
			if ( ! empty( $settings['color_scheme'] ) ) {
				$classes .= ' light-scheme';
			} ?>
			<div class="<?php echo strip_tags( $classes ); ?>">
				<?php if ( ! empty( $settings['carousel_nav'] ) ) : ?>
					<div class="slider-prev rbi rbi-cleft"></div>
				<?php endif;
				if ( ! empty( $settings['carousel_dot'] ) ) : ?>
					<div class="slider-pagination"></div>
				<?php endif;
				if ( ! empty( $settings['carousel_nav'] ) ) : ?>
					<div class="slider-next rbi rbi-cright"></div>
				<?php endif; ?>
			</div>
		<?php endif;
	}
}

/**
 * @param array $settings
 */
if ( ! function_exists( 'foxiz_carousel_attrs' ) ) {
	function foxiz_carousel_attrs( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
				'columns'             => '3',
				'columns_tablet'      => '2',
				'columns_mobile'      => '1',
				'carousel_gap'        => '',
				'carousel_gap_tablet' => '',
				'carousel_gap_mobile' => '',
				'slider_play'         => '',
				'slider_fmode'        => '',
				'slider_centered'     => '',
		] );

		if ( (string) $settings['carousel_gap'] === '-1' ) {
			$settings['carousel_gap'] = 0;
		}
		if ( (string) $settings['carousel_gap_tablet'] === '-1' ) {
			$settings['carousel_gap_tablet'] = 0;
		}
		if ( (string) $settings['carousel_gap_mobile'] === '-1' ) {
			$settings['carousel_gap_mobile'] = 0;
		}
		if ( ! empty( $settings['carousel_columns'] ) ) {
			$settings['columns'] = $settings['carousel_columns'];
		}
		if ( ! empty( $settings['carousel_columns_tablet'] ) ) {
			$settings['columns_tablet'] = $settings['carousel_columns_tablet'];
		}
		if ( ! empty( $settings['carousel_columns_mobile'] ) ) {
			$settings['columns_mobile'] = $settings['carousel_columns_mobile'];
		}
		if ( empty( $settings['carousel_wide_columns'] ) ) {
			$settings['carousel_wide_columns'] = $settings['columns'];
		}
		if ( empty( $settings['slider_speed'] ) ) {
			$settings['slider_speed'] = 5000;
		}
		if ( empty( $settings['slider_centered'] ) || '-1' === (string) $settings['slider_centered'] ) {
			$settings['slider_centered'] = 0;
		} else {
			$settings['slider_centered'] = 1;
		}

		echo ' data-wcol="' . strip_tags( $settings['carousel_wide_columns'] ) . '"';
		echo ' data-col="' . strip_tags( $settings['columns'] ) . '" data-tcol="' . strip_tags( $settings['columns_tablet'] ) . '" data-mcol="' . strip_tags( $settings['columns_mobile'] ) . '"';
		echo ' data-gap="' . strip_tags( $settings['carousel_gap'] ) . '" data-tgap="' . strip_tags( $settings['carousel_gap_tablet'] ) . '" data-mgap="' . strip_tags( $settings['carousel_gap_mobile'] ) . '"';
		echo ' data-play="' . strip_tags( $settings['slider_play'] ) . '" data-speed="' . strip_tags( $settings['slider_speed'] ) . '" data-fmode="' . strip_tags( $settings['slider_fmode'] ) . '" data-centered="' . strip_tags( $settings['slider_centered'] ) . '" ';
	}
}

/**
 * @param array $settings
 */
if ( ! function_exists( 'foxiz_slider_attrs' ) ) {
	function foxiz_slider_attrs( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
				'slider_play' => '0',
		] );

		if ( empty( $settings['slider_speed'] ) ) {
			$settings['slider_speed'] = 5000;
		}

		echo ' data-play="' . strip_tags( $settings['slider_play'] ) . '" data-speed="' . strip_tags( $settings['slider_speed'] ) . '"';
	}
}

if ( ! function_exists( 'foxiz_get_single_crop_size' ) ) {
	/**
	 * @param string $default
	 *
	 * @return array|false|mixed|string|void
	 */
	function foxiz_get_single_crop_size( $default = 'full' ) {

		$crop_size = rb_get_meta( 'featured_crop_size' );
		if ( empty( $crop_size ) ) {
			$crop_size = foxiz_get_option( 'single_crop_size' );
		}

		if ( empty( $crop_size ) ) {
			$crop_size = $default;
		}

		return $crop_size;
	}
}

if ( ! function_exists( 'foxiz_clear_settings_assets' ) ) {
	/**
	 * @param array $settings
	 *
	 * @return array|mixed
	 * remove params out get methods
	 */
	function foxiz_clear_settings_assets( $settings = [] ) {

		$default = apply_filters( 'foxiz_ajax_params', [
				'uuid'               => '',
				'category'           => '',
				'name'               => '',
				'categories'         => '',
				'category_not_in'    => '',
				'tags'               => '',
				'tag_not_in'         => '',
				'format'             => '',
				'author'             => '',
				'post_in'            => '',
				'post_not_in'        => '',
				'post_type'          => '',
				'order'              => '',
				'posts_per_page'     => '',
				'offset'             => '',
				'query_mode'         => '',
				'builder_pagination' => '',
				'pagination'         => '',
				'unique'             => '',
				'crop_size'          => '',
				'feat_hover'         => '',
				'entry_category'     => '',
				'overlay_category'   => '',
				'hide_category'      => '',
				'title_tag'          => '',
				'title_index'        => '',
				'entry_meta_bar'     => '',
				'entry_meta'         => '',
				'review'             => '',
				'review_meta'        => '',
				'sponsor_meta'       => '',
				'tablet_hide_meta'   => '',
				'mobile_hide_meta'   => '',
				'tablet_last'        => '',
				'mobile_last'        => '',
				'bookmark'           => '',
				'entry_format'       => '',
				'excerpt'            => '',
				'excerpt_length'     => '',
				'excerpt_source'     => '',
				'hide_excerpt'       => '',
				'hide_excerpt_t'     => '',
				'readmore'           => '',
				'counter'            => '',
				'color_scheme'       => '',
				'overlay_scheme'     => '',
				'box_style'          => '',
				'center_mode'        => '',
				'middle_mode'        => '',
				'block_structure'    => '',
				'divider_style'      => '',
				'taxonomy'           => '',
				'tax_slugs'          => '',
				'entry_tax'          => '',
				'tax_operator'       => '',
				'content_source'     => '',
				'live_block'         => '',
				'teaser_size'        => '',
				'teaser_col'         => '',
				'teaser_link'        => '',
		] );

		$settings = shortcode_atts( $default, $settings );

		return array_filter( $settings );
	}
}

if ( ! function_exists( 'foxiz_clear_settings_query' ) ) {
	/**
	 * @param array $settings
	 *
	 * @return array|mixed
	 * clear query for global templates in ajax
	 */
	function foxiz_clear_settings_query( $settings = [] ) {

		unset( $settings['category'], $settings['categories'], $settings['category_not_in'], $settings['tags'], $settings['tag_not_in'], $settings['format'], $settings['author'], $settings['post_not_in'], $settings['post_in'] );

		$settings['order'] = 'date_posts';

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_live_block_localize' ) ) {
	/**
	 * @param $settings
	 */
	function foxiz_live_block_localize( $settings ) {

		if ( ! empty( $settings['content_source'] ) && 'recommended' === $settings['content_source'] ) {
			$GLOBALS['foxiz_yes_recommended'] = true;
		}

		$settings    = foxiz_clear_settings_assets( $settings );
		$js_settings = [];
		$localize    = 'foxiz-global';
		foreach ( $settings as $key => $val ) {
			if ( ! empty( $val ) ) {
				$js_settings[ $key ] = $val;
			}
		}
		if ( ! empty( $settings['localize'] ) ) {
			$localize = $settings['localize'];
		}

		wp_localize_script( $localize, $settings['uuid'], $js_settings );
	}
}

if ( ! function_exists( 'foxiz_is_category_style_none_bg' ) ) {
	/**
	 * @param string $style
	 *
	 * @return bool
	 */
	function foxiz_is_category_style_none_bg( $style = '' ) {

		if ( empty( $style ) ) {
			return false;
		}

		return in_array( $style, [
				'text',
				'text,big',
				'border',
				'border,big',
				'b-dotted',
				'b-dotted,big',
				'b-border',
				'b-border,big',
				'l-dot',
		] );
	}
}

if ( ! function_exists( 'foxiz_extra_meta_labels' ) ) {
	function foxiz_extra_meta_labels( $settings = [] ) {

		if ( empty( $settings['entry_meta'] ) || ! is_array( $settings['entry_meta'] ) || ! array_filter( $settings['entry_meta'] ) ) {
			return false;
		}
		foreach ( $settings['entry_meta'] as $key => $input_meta ) {
			if ( preg_match( '/\{[^}]*\}/', $input_meta, $matches ) ) {
				$meta_key = trim( str_replace( [ '{', '}' ], '', $matches[0] ) );
				$labels   = explode( $matches[0], $input_meta );

				$settings[ 'p_label_' . $meta_key ] = ! empty( $labels[0] ) ? $labels[0] : '';
				$settings[ 's_label_' . $meta_key ] = ! empty( $labels[1] ) ? $labels[1] : '';
				$settings['entry_meta'][ $key ]     = $meta_key;
			}
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_update_word_count' ) ) {
	function foxiz_update_word_count( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		if ( ! function_exists( 'foxiz_count_content' ) ) {
			return '-1';
		}

		if ( in_array( get_post_type( $post_id ), [
				'nav_menu_item',
				'product',
				'comment',
				'rb-etemplate',
				'forum',
				'topic',
				'reply',
				'elementor_library',
		] )
		) {
			return '-1';
		}

		$content = get_post_field( 'post_content', $post_id );
		$count   = foxiz_count_content( $content );
		update_post_meta( $post_id, 'foxiz_content_total_word', $count );

		return $count;
	}
}

if ( ! function_exists( 'foxiz_is_author_tick' ) ) {
	function foxiz_is_author_tick( $author_id = false ) {

		if ( empty( $author_id ) ) {
			return false;
		}

		$stick = (string) get_the_author_meta( 'author_tick', $author_id );

		if ( 'verified' === $stick ) {
			return true;
		} elseif ( '-1' === $stick ) {
			return false;
		}

		if ( user_can( (int) $author_id, 'edit_posts' ) ) {
			return foxiz_get_option( 'author_tick', false );
		}

		return false;
	}
}

if ( ! function_exists( 'foxiz_get_svg_content' ) ) {
	/**
	 * @param string $attachment_id
	 *
	 * @return bool|mixed|string
	 */
	function foxiz_get_svg_content( $attachment_id = '' ) {

		if ( empty( $attachment_id ) || ! class_exists( 'Elementor\Core\Files\File_Types\Svg' ) ) {
			return false;
		}

		$content = Svg::get_inline_svg( (int) $attachment_id );

		return ( ! empty( $content ) && is_string( $content ) ) ? $content : false;
	}
}