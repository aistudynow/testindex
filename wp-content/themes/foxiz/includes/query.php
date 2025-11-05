<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_order_query_params' ) ) {
	function foxiz_get_order_query_params( $params, $order ) {

		switch ( $order ) {

			case 'date_post' :
				$params['orderby'] = 'date';
				$params['order']   = 'DESC';
				break;
			case 'update' :
				$params['orderby'] = 'modified';
				$params['order']   = 'DESC';
				break;
			case 'comment_count' :
				$params['orderby'] = 'comment_count';
				$params['order']   = 'DESC';
				break;
			case 'post_type' :
				$params['orderby'] = 'type';
				break;
			case 'popular':
				$params['suppress_filters'] = false;
				$params['fields']           = '';
				$params['orderby']          = 'post_views';
				$params['order']            = 'DESC';
				break;
			case 'popular_1d':
				$params['suppress_filters'] = false;
				$params['fields']           = '';
				$params['orderby']          = 'post_views';
				$params['order']            = 'DESC';
				$params['date_query']       = [
					[
						'after'  => '1 day ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'popular_2d':
				$params['suppress_filters'] = false;
				$params['fields']           = '';
				$params['orderby']          = 'post_views';
				$params['order']            = 'DESC';
				$params['date_query']       = [
					[
						'after'  => '2 days ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'popular_3d':
				$params['suppress_filters'] = false;
				$params['fields']           = '';
				$params['orderby']          = 'post_views';
				$params['order']            = 'DESC';
				$params['date_query']       = [
					[
						'after'  => '3 days ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'popular_w':
				$params['suppress_filters'] = false;
				$params['fields']           = '';
				$params['orderby']          = 'post_views';
				$params['order']            = 'DESC';
				$params['date_query']       = [
					[
						'after'  => '7 days ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'popular_m':
				$params['suppress_filters'] = false;
				$params['fields']           = '';
				$params['orderby']          = 'post_views';
				$params['order']            = 'DESC';
				$params['date_query']       = [
					[
						'after'  => '1 month ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'popular_3m':
				$params['suppress_filters'] = false;
				$params['fields']           = '';
				$params['orderby']          = 'post_views';
				$params['order']            = 'DESC';
				$params['date_query']       = [
					[
						'after'  => '3 months ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'popular_6m':
				$params['suppress_filters'] = false;
				$params['fields']           = '';
				$params['orderby']          = 'post_views';
				$params['order']            = 'DESC';
				$params['date_query']       = [
					[
						'after'  => '6 months ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'popular_y':
				$params['suppress_filters'] = false;
				$params['fields']           = '';
				$params['orderby']          = 'post_views';
				$params['order']            = 'DESC';
				$params['date_query']       = [
					[
						'after'  => '1 year ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'top_review' :
				$params['meta_key']  = 'foxiz_review_average';
				$params['meta_type'] = 'NUMERIC';
				$params['orderby']   = 'meta_value_num';
				$params['order']     = 'DESC';
				break;
			case 'top_review_3d' :
				$params['meta_key']   = 'foxiz_review_average';
				$params['meta_type']  = 'NUMERIC';
				$params['orderby']    = 'meta_value_num';
				$params['order']      = 'DESC';
				$params['date_query'] = [
					[
						'after'  => '3 days ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'top_review_w' :
				$params['meta_key']   = 'foxiz_review_average';
				$params['meta_type']  = 'NUMERIC';
				$params['orderby']    = 'meta_value_num';
				$params['order']      = 'DESC';
				$params['date_query'] = [
					[
						'after'  => '7 days ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'top_review_m' :
				$params['meta_key']   = 'foxiz_review_average';
				$params['meta_type']  = 'NUMERIC';
				$params['orderby']    = 'meta_value_num';
				$params['order']      = 'DESC';
				$params['date_query'] = [
					[
						'after'  => '1 month ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'top_review_3m' :
				$params['meta_key']   = 'foxiz_review_average';
				$params['meta_type']  = 'NUMERIC';
				$params['orderby']    = 'meta_value_num';
				$params['order']      = 'DESC';
				$params['date_query'] = [
					[
						'after'  => '3 month ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'top_review_6m' :
				$params['meta_key']   = 'foxiz_review_average';
				$params['meta_type']  = 'NUMERIC';
				$params['orderby']    = 'meta_value_num';
				$params['order']      = 'DESC';
				$params['date_query'] = [
					[
						'after'  => '6 month ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'top_review_y' :
				$params['meta_key']   = 'foxiz_review_average';
				$params['meta_type']  = 'NUMERIC';
				$params['orderby']    = 'meta_value_num';
				$params['order']      = 'DESC';
				$params['date_query'] = [
					[
						'after'  => '1 year ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'last_review' :
				$params['meta_key'] = 'foxiz_review_average';
				$params['orderby']  = 'date';
				$params['order']    = 'DESC';
				break;
			case 'sponsored' :
				$params['meta_key'] = 'foxiz_sponsored';
				$params['orderby']  = 'date';
				$params['order']    = 'DESC';
				break;
			case 'rand':
				$params['orderby'] = 'rand';
				break;
			case 'rand_3d':
				$params['orderby']    = 'rand';
				$params['date_query'] = [
					[
						'after'  => '3 days ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'rand_w':
				$params['orderby']    = 'rand';
				$params['date_query'] = [
					[
						'after'  => '1 week ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'rand_m':
				$params['orderby']    = 'rand';
				$params['date_query'] = [
					[
						'after'  => '1 month ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'rand_3m':
				$params['orderby']    = 'rand';
				$params['date_query'] = [
					[
						'after'  => '3 months ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'rand_6m':
				$params['orderby']    = 'rand';
				$params['date_query'] = [
					[
						'after'  => '6 months ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'rand_y':
				$params['orderby']    = 'rand';
				$params['date_query'] = [
					[
						'after'  => '1 year ago',
						'column' => 'post_date_gmt',
					],
				];
				break;
			case 'alphabetical_order_decs':
				$params['orderby'] = 'title';
				$params['order']   = 'DECS';
				break;
			case 'alphabetical_order_asc':
				$params['orderby'] = 'title';
				$params['order']   = 'ASC';
				break;
			case 'by_input' :
				$params['orderby'] = 'post__in';
				break;
			case 'relevance' :
				$params['orderby'] = 'relevance';
				break;
			case 'post_index' :
				$params['meta_key'] = 'ruby_index';
				$params['orderby']  = 'meta_value';
				$params['order']    = 'ASC';
				break;
			case 'post_index_desc' :
				$params['meta_key'] = 'ruby_index';
				$params['orderby']  = 'meta_value';
				$params['order']    = 'DECS';
				break;
			case 'new_live' :
				$params['meta_query'] = [
					[
						'key'   => 'ruby_live_blog',
						'value' => 'yes',
					],
				];
				$params['orderby']    = 'date';
				$params['order']      = 'DESC';
				break;
			case 'update_live' :
				$params['meta_query'] = [
					[
						'key'   => 'ruby_live_blog',
						'value' => 'yes',
					],
				];
				$params['orderby']    = 'modified';
				$params['order']      = 'DESC';
				break;
			case 'new_flive' :
				$params['meta_query'] = [
					[
						'key'     => 'ruby_live_blog',
						'compare' => 'IN',
						'value'   => [ 'yes', 'archive' ],
					],
				];
				$params['orderby']    = 'date';
				$params['order']      = 'DESC';
				break;
			case 'update_flive' :
				$params['meta_query'] = [
					[
						'key'     => 'ruby_live_blog',
						'compare' => 'IN',
						'value'   => [ 'yes', 'archive' ],
					],
				];
				$params['orderby']    = 'modified';
				$params['order']      = 'DESC';
				break;
			default :
				$params['orderby'] = 'date';
				$params['order']   = 'DESC';
		}

		return $params;
	}
}

if ( ! function_exists( 'foxiz_query' ) ) {
	function foxiz_query( $data = [], $paged = null ) {

		if ( ! empty( $data['query_mode'] ) && 'global' === $data['query_mode'] && ! foxiz_is_template_preview() ) {

			/** custom query for dynamic templates */
			if ( ! empty( $GLOBALS['ruby_template_query'] ) ) {
				$_query = $GLOBALS['ruby_template_query'];
				unset( $GLOBALS['ruby_template_query'] );

				return $_query;
			}

			/** global query builder */
			global $wp_query;

			if ( empty( $wp_query->is_singular ) ) {

				$_query = $wp_query;
				if ( ! empty( $data['unique'] ) && ! empty( $GLOBALS['foxiz_queried_ids'] ) && is_array( $GLOBALS['foxiz_queried_ids'] ) ) {
					$params                 = $_query->query_vars;
					$params['post__not_in'] = (array) $GLOBALS['foxiz_queried_ids'];

					/** get new WP_Query */
					$_query = new WP_Query( $params );
					$_query->set( 'foxiz_queried_ids', $GLOBALS['foxiz_queried_ids'] );
				}

				foxiz_add_queried_ids( $_query );

				return $_query;
			}
		}

		$data = shortcode_atts( [
			'categories'          => '',
			'category'            => '',
			'category_not_in'     => '',
			'author'              => '',
			'author_in'           => '',
			'format'              => '',
			'tags'                => '',
			'posts_per_page'      => '',
			'no_found_rows'       => false,
			'offset'              => '',
			'order'               => 'date_post',
			'post_type'           => 'post',
			'meta_key'            => '',
			'post_in'             => '',
			'post_not_in'         => '',
			'tag_not_in'          => '',
			's'                   => '',
			'tax_query'           => [],
			'unique'              => '',
			'ignore_sticky_posts' => 1,
			'taxonomy'            => '',
			'tax_slugs'           => '',
			'tax_operator'        => '',
		], $data );

		if ( ! class_exists( 'Post_Views_Counter' ) && in_array( $data['order'], [
				'popular',
				'popular_3d',
				'popular_w',
				'popular_m',
				'popular_3m',
				'popular_6m',
				'popular_y',
			] )
		) {
			$data['order'] = 'comment_count';
		}

		$params   = [];
		$taxonomy = 'category';

		if ( ! empty( $data['post_type'] ) && 'podcast' == $data['post_type'] ) {
			$taxonomy = 'series';
		}

		if ( empty( $data['post_type'] ) ) {
			$params['post_type'] = 'post';
		} else {
			$params['post_type'] = $data['post_type'];
		}

		$params['post_status']         = 'publish';
		$params['ignore_sticky_posts'] = $data['ignore_sticky_posts'];
		$params['no_found_rows']       = boolval( $data['no_found_rows'] );
		$params['tax_query']           = [];

		if ( ! empty( $data['posts_per_page'] ) ) {
			$params['posts_per_page'] = intval( $data['posts_per_page'] );
		}

		if ( empty( $data['categories'] ) ) {
			$data['categories'] = $data['category'];
		}

		if ( ! empty( $data['categories'] ) && 'all' !== $data['categories'] ) {
			if ( ! is_array( $data['categories'] ) ) {
				$data['categories'] = explode( ',', $data['categories'] );
			}
			$data['categories'] = array_map( 'absint', $data['categories'] );

			$params['tax_query'][] = [
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $data['categories'],
				'operator' => 'IN',
			];
		} else {
			if ( ! empty( $data['category_not_in'] ) && ! is_array( $data['category_not_in'] ) ) {
				$data['category_not_in'] = explode( ',', $data['category_not_in'] );
				$data['category_not_in'] = array_map( 'absint', $data['category_not_in'] );

				$params['tax_query'][] = [
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $data['category_not_in'],
					'operator' => 'NOT IN',
				];
			}
		}

		/** custom tax query */
		if ( ! empty( $data['taxonomy'] ) && ! empty( $data['tax_slugs'] ) ) {
			$data['taxonomy'] = trim( $data['taxonomy'] );

			if ( ! is_array( $data['tax_slugs'] ) ) {
				$data['tax_slugs'] = explode( ',', $data['tax_slugs'] );
			}
			$data['tax_slugs'] = array_map( 'trim', $data['tax_slugs'] );

			if ( ! empty( $data['tax_operator'] ) && 'not' === $data['tax_operator'] ) {
				$data['tax_operator'] = 'NOT IN';
			} elseif ( ! empty( $data['tax_operator'] ) && 'and' === $data['tax_operator'] ) {
				$data['tax_operator'] = 'AND';
			} else {
				$data['tax_operator'] = 'IN';
			}
			/** reset tax query */
			if ( 'post' !== $params['post_type'] ) {
				$params['tax_query'] = [];
			}

			$params['tax_query'][] = [
				'taxonomy' => $data['taxonomy'],
				'field'    => 'slug',
				'terms'    => $data['tax_slugs'],
				'operator' => $data['tax_operator'],
			];
		}

		if ( ! empty( $data['post_in'] ) ) {
			if ( is_string( $data['post_in'] ) ) {
				$params['post__in'] = explode( ',', $data['post_in'] );
			} elseif ( is_array( $data['post_in'] ) ) {
				$params['post__in'] = $data['post_in'];
			}
		} else {
			$excluded_ids = [];
			if ( ! empty( $data['post_not_in'] ) && is_string( $data['post_not_in'] ) ) {
				$excluded_ids = explode( ',', $data['post_not_in'] );
			} elseif ( is_array( $data['post_not_in'] ) ) {
				$excluded_ids = $data['post_not_in'];
			}
			if ( isset( $GLOBALS['foxiz_queried_ids'] ) && count( $GLOBALS['foxiz_queried_ids'] ) && ! empty( $data['unique'] ) ) {
				$excluded_ids = array_merge( $excluded_ids, $GLOBALS['foxiz_queried_ids'] );
			}
			if ( is_array( $excluded_ids ) ) {
				$params['post__not_in'] = array_unique( $excluded_ids );
			}
		}

		if ( ! empty( $data['author'] ) ) {
			$params['author'] = $data['author'];
		} elseif ( ! empty( $data['author_in'] ) && is_array( $data['author_in'] ) ) {
			$params['author__in'] = $data['author_in'];
		}

		if ( ! empty( $data['format'] ) ) {
			if ( 'default' !== $data['format'] ) {
				$params['tax_query'][] = [
					'taxonomy' => 'post_format',
					'field'    => 'slug',
					'terms'    => [ 'post-format-' . trim( $data['format'] ) ],
				];
			} else {
				$params['tax_query'][] = [
					'taxonomy' => 'post_format',
					'field'    => 'slug',
					'terms'    => [ 'post-format-gallery', 'post-format-video', 'post-format-audio' ],
					'operator' => 'NOT IN',
				];
			}
		}

		if ( ! empty( $data['tax_query'] ) ) {
			$params['tax_query'][] = $data['tax_query'];
		}

		/** post per page */
		if ( ! empty( $paged ) && $paged > 1 ) {
			$params['paged'] = absint( $paged );
		}

		if ( ! empty( $data['offset'] ) ) {
			if ( $paged > 1 ) {
				$params['offset'] = absint( $data['offset'] ) + absint( ( $paged - 1 ) * absint( $data['posts_per_page'] ) );
			} else {
				$params['offset'] = absint( $data['offset'] );
			}
			unset( $params['paged'] );
		}

		if ( ! empty( $data['tags'] ) ) {
			if ( ! is_array( $data['tags'] ) ) {
				$data['tags'] = explode( ',', $data['tags'] );
			}
			if ( count( $data['tags'] ) ) {
				$data['tags']          = array_map( 'trim', $data['tags'] );
				$params['tax_query'][] = [
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => $data['tags'],
					'operator' => 'IN',
				];
			}
		}

		if ( ! empty( $data['tag_not_in'] ) ) {
			if ( ! is_array( $data['tag_not_in'] ) ) {
				$data['tag_not_in'] = explode( ',', $data['tag_not_in'] );
			}
			if ( count( $data['tag_not_in'] ) ) {
				$data['tag_not_in']    = array_map( 'trim', $data['tag_not_in'] );
				$params['tax_query'][] = [
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => $data['tag_not_in'],
					'operator' => 'NOT IN',
				];
			}
		}

		if ( ! empty( $data['meta_key'] ) ) {
			$params['meta_key'] = $data['meta_key'];
			$params['orderby']  = 'meta_value_num';
		}

		/** set search query */
		if ( ! empty( $data['s'] ) ) {
			$params['s']   = $data['s'];
			$data['order'] = 'relevance';
		}

		$params = foxiz_get_order_query_params( $params, $data['order'] );

		$_query = new WP_Query( $params );
		if ( ! empty( $GLOBALS['foxiz_queried_ids'] ) && is_array( $GLOBALS['foxiz_queried_ids'] ) ) {
			$_query->set( 'foxiz_queried_ids', $GLOBALS['foxiz_queried_ids'] );
		}
		foxiz_add_queried_ids( $_query );
		do_action( 'foxiz_after_query', $_query, $data );

		return $_query;
	}
}

if ( ! function_exists( 'foxiz_query_related' ) ) {
	/**
	 * @param array $data
	 * @param int   $paged
	 *
	 * @return WP_Query
	 */
	function foxiz_query_related( $data = [], $paged = 1 ) {

		$data = wp_parse_args( $data, [
			'related_id'     => '',
			'where'          => '',
			'posts_per_page' => '',
			'post_in'        => '',
			'post_not_in'    => '',
			'no_found_rows'  => false,
			'orderby'        => '',
			'categories'     => [],
			'tags'           => [],
		] );

		if ( empty( $data['related_id'] ) ) {
			$data['related_id'] = get_the_ID();
		}

		$post_type = get_post_type( $data['related_id'] );
		$taxonomy  = 'category';

		if ( 'podcast' === $post_type ) {
			$taxonomy = 'series';
		} elseif ( 'post' !== $post_type ) {
			$taxonomies = get_object_taxonomies( $post_type );
			if ( ! empty( $taxonomies[0] ) ) {
				$taxonomy = $taxonomies[0];
			}
		}

		if ( empty( $data['where'] ) ) {
			$data['where'] = foxiz_get_option( 'single_post_related_where', 'all' );
		}

		if ( empty( $data['posts_per_page'] ) ) {
			$data['posts_per_page'] = foxiz_get_option( 'single_post_related_total' );
		}

		if ( empty( $data['orderby'] ) ) {
			$data['orderby'] = foxiz_get_option( 'single_post_related_order' );
			if ( empty( $data['orderby'] ) ) {
				$data['orderby'] = 'rand';
			}
		}

		$params = [
			'ignore_sticky_posts' => 1,
			'post_status'         => 'publish',
			'post_type'           => $post_type,
			'orderby'             => $data['orderby'],
			'no_found_rows'       => boolval( $data['no_found_rows'] ),
		];

		if ( ! empty( $data['post_in'] ) ) {
			if ( is_string( $data['post_in'] ) ) {
				$params['post__in'] = explode( ',', $data['post_in'] );
			} elseif ( is_array( $data['post_in'] ) ) {
				$params['post__in'] = $data['post_in'];
			}
			$params['orderby']        = 'post__in';
			$params['posts_per_page'] = - 1;
		} else {

			if ( ! empty( $paged ) ) {
				$params['paged'] = $paged;
			}
			if ( ! empty( $data['posts_per_page'] ) ) {
				$params['posts_per_page'] = (int) $data['posts_per_page'];
			} else {
				$params['posts_per_page'] = (int) get_option( 'posts_per_page' );
			}

			if ( ! empty( $data['meta_key'] ) ) {
				$params['meta_key'] = $data['meta_key'];
			}

			if ( empty( $data['categories'] ) ) {
				$terms = get_the_terms( $data['related_id'], $taxonomy );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$data['categories'] = wp_list_pluck( $terms, 'term_id' );
				}
			}

			if ( empty( $data['tags'] ) ) {
				$tags = get_the_tags( $data['related_id'] );
				if ( $tags && is_array( $tags ) ) {
					foreach ( $tags as $tag ) {
						$data['tags'][] = $tag->slug;
					}
				}
			}

			if ( 'tag' === $data['where'] && ! count( $data['tags'] ) ) {
				$data['where'] = 'all';
			}

			switch ( $data['where'] ) {
				case 'all':
					if ( count( $data['categories'] ) && count( $data['tags'] ) ) {
						$params['tax_query'] = [
							'relation' => 'OR',
							[
								'taxonomy' => $taxonomy,
								'field'    => 'term_id',
								'terms'    => $data['categories'],
							],
							[
								'taxonomy' => 'post_tag',
								'field'    => 'slug',
								'terms'    => $data['tags'],
							],
						];
					} elseif ( ! count( $data['categories'] ) && count( $data['tags'] ) ) {
						$params['tax_query'] = [
							[
								'taxonomy' => 'post_tag',
								'field'    => 'slug',
								'terms'    => $data['tags'],
							],
						];
					} elseif ( count( $data['categories'] ) && ! count( $data['tags'] ) ) {
						$params['tax_query'] = [
							[
								'taxonomy' => $taxonomy,
								'field'    => 'term_id',
								'terms'    => $data['categories'],
							],
						];
					}
					break;
				case 'tag' :
					$params['tax_query'] = [
						[
							'taxonomy' => 'post_tag',
							'field'    => 'slug',
							'terms'    => $data['tags'],
						],
					];
					break;
				default :
					$params['tax_query'] = [
						[
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => $data['categories'],
						],
					];
			}

			$excluded_ids   = [];

			$excluded_ids[] = $data['related_id'];
			if ( ! empty( $data['post_not_in'] ) && is_string( $data['post_not_in'] ) ) {
				$excluded_ids = explode( ',', $data['post_not_in'] );
			} elseif ( is_array( $data['post_not_in'] ) ) {
				$excluded_ids = $data['post_not_in'];
			}
			if ( isset( $GLOBALS['foxiz_queried_ids'] ) && count( $GLOBALS['foxiz_queried_ids'] ) ) {
				$excluded_ids = array_merge( $excluded_ids, $GLOBALS['foxiz_queried_ids'] );
			}
			if ( is_array( $excluded_ids ) ) {
				$params['post__not_in'] = array_unique( $excluded_ids );
			}
		}

		$_query = new WP_Query( $params );

		$_query->set( 'content_source', 'related' );
		$_query->set( 'related_id', $data['related_id'] );
		$_query->set( 'related_total', $params['posts_per_page'] );

		if ( ! empty( $GLOBALS['foxiz_queried_ids'] ) && is_array( $GLOBALS['foxiz_queried_ids'] ) ) {
			$_query->set( 'foxiz_queried_ids', $GLOBALS['foxiz_queried_ids'] );
		}
		foxiz_add_queried_ids( $_query );

		return $_query;
	}
}

if ( ! function_exists( 'foxiz_personalize_query' ) ) {
	/**
	 * @param $settings
	 *
	 * @return false|mixed|WP_Query|null
	 */
	function foxiz_personalize_query( $settings ) {

		if ( ! empty( $settings['content_source'] ) ) {
			if ( 'saved' === $settings['content_source'] ) {
				return Foxiz_Personalize::get_instance()->saved_posts_query( $settings );
			} elseif ( 'history' === $settings['content_source'] ) {
				return Foxiz_Personalize::get_instance()->reading_history_query( $settings );
			}
		}

		return Foxiz_Personalize::get_instance()->recommended_query( $settings );
	}
}

if ( ! function_exists( 'foxiz_add_queried_ids' ) ) {
	function foxiz_add_queried_ids( $_query ) {

		if ( ! isset( $GLOBALS['foxiz_queried_ids'] ) ) {
			$GLOBALS['foxiz_queried_ids'] = [];
		}

		if ( ! empty( $_query->posts ) ) {
			$post_ids = wp_list_pluck( $_query->posts, 'ID' );
			if ( is_array( $post_ids ) ) {
				$GLOBALS['foxiz_queried_ids'] = array_unique( array_merge( $GLOBALS['foxiz_queried_ids'], $post_ids ) );
			}
		}
	}
}