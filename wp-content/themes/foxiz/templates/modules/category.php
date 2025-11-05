<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_child_terms_post_count' ) ) {
	function foxiz_get_child_terms_post_count( $term ) {

		if ( empty( $term ) || ! isset( $term->term_id ) ) {
			return 0;
		}

		$transient_key = 'foxiz_term_count_' . $term->term_id;

		$cached_count = get_transient( $transient_key );

		if ( false !== $cached_count ) {
			return (int) $cached_count;
		}

		global $wpdb;

		$query = $wpdb->prepare(
				"SELECT SUM(count) as total_posts
                FROM {$wpdb->prefix}term_taxonomy
                WHERE parent = %d",
				$term->term_id
		);

		$result = $wpdb->get_var( $query );

		set_transient( $transient_key, $result, 3600 ); // Cache for 1 hour

		return (int) $result;
	}

}

if ( ! function_exists( 'foxiz_get_category_block_params' ) ) {
	function foxiz_get_category_block_params( $settings ) {

		$params = shortcode_atts( [
				'uuid'         => '',
				'name'         => '',
				'followed'     => '',
				'tax_followed' => '',
				'feat'         => '',
				'crop_size'    => '',
				'title_tag'    => '',
				'follow'       => '',
				'count_posts'  => '',
				'display_mode' => '',
				'feat_ids'     => '',

		], $settings );

		$ids      = [];
		$feat_ids = [];

		foreach ( $settings['categories'] as $item ) {
			if ( ! empty( $item['tax_id'] ) ) {
				$id = intval( $item['tax_id'] );
			} elseif ( ! empty( $item['category'] ) ) {
				$term = get_term_by( 'slug', $item['category'], 'category' );
				if ( $term ) {
					$id = $term->term_id;
				}
			}
			if ( ! empty( $id ) ) {
				$ids[] = $id;

				if ( ! empty( $item['tax_image']['id'] ) ) {
					$feat_ids[ $id ] = (int) $item['tax_image']['id'];
				}
			}
		}

		$params['categories'] = implode( ',', $ids );
		$params['feat_ids']   = wp_json_encode( $feat_ids );

		return $params;
	}
}

if ( ! function_exists( 'foxiz_taxonomy_count' ) ) {
	function foxiz_taxonomy_count( $term, $include_child = false ) {

		if ( empty( $term ) || ! isset( $term->term_id ) ) {
			return;
		}

		$total = isset( $term->count ) ? (int) $term->count : 0;

		if ( $include_child ) {
			$total += foxiz_get_child_terms_post_count( $term );
		}

		if ( empty( $total ) ) {
			return;
		}

		echo '<span class="cbox-count is-meta">';
		if ( 1 < $total ) {
			echo strip_tags( $total ) . ' ' . foxiz_html__( 'Articles', 'foxiz' );
		} else {
			echo strip_tags( $total ) . ' ' . foxiz_html__( 'Article', 'foxiz' );
		}
		echo '</span>';
	}
}

if ( ! function_exists( 'foxiz_taxonomy_description' ) ) {
	function foxiz_taxonomy_description( $term ) {

		if ( empty( $term->description ) ) {
			return;
		}

		echo '<span class="cbox-count is-meta">' . wp_trim_words( $term->description, 12 ) . '</span>';
	}
}

if ( ! function_exists( 'foxiz_categories_localize_script' ) ) {
	function foxiz_categories_localize_script( $settings ) {

		$js_settings = [
				'block_type' => 'category',
		];
		$localize    = 'foxiz-global';
		foreach ( $settings as $key => $val ) {
			if ( ! empty( $val ) ) {
				$js_settings[ $key ] = $val;
			}
		}
		wp_localize_script( $localize, $settings['uuid'], $js_settings );
	}
}

if ( ! function_exists( 'foxiz_merge_saved_terms' ) ) {
	function foxiz_merge_saved_terms( $settings ) {

		$term_ids = [];
		if ( ! empty( $settings['followed'] ) && '-1' !== (string) $settings['followed'] ) {
			$term_ids = Foxiz_Personalize::get_instance()->get_categories_followed();
		}

		if ( ! empty( $settings['categories'] ) ) {
			$term_ids = array_merge( $term_ids, explode( ',', $settings['categories'] ) );
		}

		return array_unique( $term_ids );
	}
}

if ( ! function_exists( 'foxiz_render_follow_redirect' ) ) {
	function foxiz_render_follow_redirect( $settings = [] ) {

		if ( empty( $settings['url'] ) ) {
			return false;
		}
		?>
		<div class="follow-redirect-wrap">
			<a href="<?php echo esc_url( $settings['url'] ); ?>" class="follow-redirect">
				<i class="rbi rbi-plus" aria-hidden="true"></i><span class="meta-text"><?php foxiz_html_e( 'Add More', 'foxiz' ); ?></span>
			</a>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_category_item_search' ) ) {
	function foxiz_category_item_search( $settings = [] ) {

		if ( ! empty( $settings['cid'] ) ) {
			$term = get_term( $settings['cid'] );
		} elseif ( ! empty( $settings['slug'] ) ) {
			$term = get_term_by( 'slug', $settings['slug'], 'category' );
		}

		if ( empty( $term ) || is_wp_error( $term ) ) {
			return;
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h4';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g1';
		}
		$id             = $term->term_id;
		$taxonomy       = $term->taxonomy;
		$link           = foxiz_get_term_link( $term );
		$metas          = rb_get_term_meta( 'foxiz_category_meta', $id );
		$featured_array = [];

		if ( ! empty( $metas['featured_image'] ) ) {
			$featured_array = $metas['featured_image'];
		} ?>
		<div class="<?php echo 'cbox cbox-search is-cbox-' . $term->term_id; ?>">
			<?php if ( foxiz_get_category_featured( $featured_array, [], $settings['crop_size'] ) ) : ?>
				<div class="cbox-featured-holder">
					<a class="cbox-featured" aria-label="<?php echo esc_attr( $term->name ); ?>" href="<?php echo esc_url( $link ); ?>"><?php foxiz_render_category_featured( $featured_array, [], $settings['crop_size'] ); ?></a>
				</div>
			<?php endif; ?>
			<div class="cbox-content">
				<?php echo '<' . strip_tags( $settings['title_tag'] ) . ' class="cbox-title">';
				echo '<a class="p-url" href="' . esc_url( $link ) . '" rel="' . ( ( 'category' === $taxonomy ) ? 'category' : 'tag' ) . '">' . strip_tags( $term->name ) . '</a>';
				echo '</' . strip_tags( $settings['title_tag'] ) . '>';
				if ( empty( $settings['desc_source'] ) ) {
					foxiz_taxonomy_count( $term );
				} elseif ( 'desc' === $settings['desc_source'] ) {
					foxiz_taxonomy_description( $term );
				} elseif ( '2' === (string) $settings['desc_source'] ) {
					foxiz_taxonomy_count( $term, true );
				} ?>
			</div>
			<?php
			if ( ! empty( $settings['follow'] ) ) {
				foxiz_follow_trigger( [ 'id' => $id, 'name' => $term->name ] );
			}
			?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_category_item_1' ) ) {
	function foxiz_category_item_1( $settings = [] ) {

		if ( ! empty( $settings['cid'] ) ) {
			$term = get_term( $settings['cid'] );
		} elseif ( ! empty( $settings['slug'] ) ) {
			$term = get_term_by( 'slug', $settings['slug'], 'category' );
		}

		if ( empty( $term ) || is_wp_error( $term ) ) {
			return;
		}

		$id       = $term->term_id;
		$taxonomy = $term->taxonomy;

		if ( count( $settings['allowed_tax'] ) &&
		     ! in_array( $id, $settings['selected_ids'] ) &&
		     ! in_array( $taxonomy, $settings['allowed_tax'] )
		) {
			return;
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h4';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g1';
		}

		$link                = foxiz_get_term_link( $term );
		$featured_array      = [];
		$featured_urls_array = [];

		if ( ! empty( $settings['feat_ids'][ $id ] ) ) {
			$featured_array = [ (int) $settings['feat_ids'][ $id ] ];
		} else {

			$metas = rb_get_term_meta( 'foxiz_category_meta', $id );
			if ( ! empty( $metas['featured_image'] ) ) {
				$featured_array = $metas['featured_image'];
			}
			if ( ! empty( $metas['featured_image_urls'] ) ) {
				$featured_urls_array = $metas['featured_image_urls'];
			}
		}
		?>
		<div class="<?php echo 'cbox cbox-1 is-cbox-' . $term->term_id; ?>">
			<div class="cbox-inner">
				<a class="cbox-featured" aria-label="<?php echo esc_attr( $term->name ); ?>" href="<?php echo esc_url( $link ); ?>"><?php foxiz_render_category_featured( $featured_array, $featured_urls_array, $settings['crop_size'] ); ?></a>
				<div class="cbox-body">
					<div class="cbox-content">
						<?php echo '<' . strip_tags( $settings['title_tag'] ) . ' class="cbox-title">';
						echo '<a class="p-url" href="' . esc_url( $link ) . '" rel="' . ( ( 'category' === $taxonomy ) ? 'category' : 'tag' ) . '">' . strip_tags( $term->name ) . '</a>';
						echo '</' . strip_tags( $settings['title_tag'] ) . '>';
						if ( ! empty( $settings['count_posts'] ) && '-1' !== (string) $settings['count_posts'] ) {
							foxiz_taxonomy_count( $term, '2' === (string) $settings['count_posts'] );
						} ?>
					</div>
					<?php if ( ! empty( $settings['follow'] ) && '1' === (string) $settings['follow'] ) {
						foxiz_follow_trigger( [ 'id' => $id, 'name' => $term->name ] );
					} ?>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_category_item_2' ) ) {
	function foxiz_category_item_2( $settings = [] ) {

		if ( ! empty( $settings['cid'] ) ) {
			$term = get_term( $settings['cid'] );
		} elseif ( ! empty( $settings['slug'] ) ) {
			$term = get_term_by( 'slug', $settings['slug'], 'category' );
		}

		if ( empty( $term ) || is_wp_error( $term ) ) {
			return;
		}

		$id       = $term->term_id;
		$taxonomy = $term->taxonomy;

		if ( count( $settings['allowed_tax'] ) &&
		     ! in_array( $id, $settings['selected_ids'] ) &&
		     ! in_array( $taxonomy, $settings['allowed_tax'] )
		) {
			return;
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g1';
		}

		$link                = foxiz_get_term_link( $term );
		$featured_array      = [];
		$featured_urls_array = [];

		if ( ! empty( $settings['feat_ids'][ $id ] ) ) {
			$featured_array = [ (int) $settings['feat_ids'][ $id ] ];
		} else {

			$metas = rb_get_term_meta( 'foxiz_category_meta', $id );
			if ( ! empty( $metas['featured_image'] ) ) {
				$featured_array = $metas['featured_image'];
			}
			if ( ! empty( $metas['featured_image_urls'] ) ) {
				$featured_urls_array = $metas['featured_image_urls'];
			}
		}

		?>
		<div class="<?php echo 'cbox cbox-2 is-cbox-' . $term->term_id; ?>">
			<div class="cbox-inner">
				<a class="cbox-featured is-overlay" aria-label="<?php echo esc_attr( $term->name ); ?>" href="<?php echo esc_url( $link ); ?>"><?php foxiz_render_category_featured( $featured_array, $featured_urls_array, $settings['crop_size'] ); ?></a>
				<div class="cbox-overlay overlay-wrap light-scheme">
					<div class="cbox-body">
						<div class="cbox-content">
							<?php echo '<' . strip_tags( $settings['title_tag'] ) . ' class="cbox-title">';
							echo '<a class="p-url" href="' . esc_url( $link ) . '" rel="' . ( ( 'category' === $taxonomy ) ? 'category' : 'tag' ) . '">' . strip_tags( $term->name ) . '</a>';
							echo '</' . strip_tags( $settings['title_tag'] ) . '>';
							if ( ! empty( $settings['count_posts'] ) && '-1' !== (string) $settings['count_posts'] ) {
								foxiz_taxonomy_count( $term, '2' === (string) $settings['count_posts'] );
							} ?>
						</div>
						<?php if ( ! empty( $settings['follow'] ) && '1' === (string) $settings['follow'] ) {
							foxiz_follow_trigger( [ 'id' => $id, 'name' => $term->name, 'classes' => 'is-light' ] );
						} ?>
					</div>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_category_item_3' ) ) {
	function foxiz_category_item_3( $settings = [] ) {

		if ( ! empty( $settings['cid'] ) ) {
			$term = get_term( $settings['cid'] );
		} elseif ( ! empty( $settings['slug'] ) ) {
			$term = get_term_by( 'slug', $settings['slug'], 'category' );
		}

		if ( empty( $term ) || is_wp_error( $term ) ) {
			return;
		}

		$id       = $term->term_id;
		$taxonomy = $term->taxonomy;

		if ( count( $settings['allowed_tax'] ) &&
		     ! in_array( $id, $settings['selected_ids'] ) &&
		     ! in_array( $taxonomy, $settings['allowed_tax'] )
		) {
			return;
		}

		$description = true;
		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g2';
		}
		if ( ! empty( $settings['description'] ) && '-1' === (string) $settings['description'] ) {
			$description = false;
		}

		$link                = foxiz_get_term_link( $term );
		$featured_array      = [];
		$featured_urls_array = [];

		if ( ! empty( $settings['feat_ids'][ $id ] ) ) {
			$featured_array = [ (int) $settings['feat_ids'][ $id ] ];
		} else {

			$metas = rb_get_term_meta( 'foxiz_category_meta', $id );
			if ( ! empty( $metas['featured_image'] ) ) {
				$featured_array = $metas['featured_image'];
			}
			if ( ! empty( $metas['featured_image_urls'] ) ) {
				$featured_urls_array = $metas['featured_image_urls'];
			}
		}
		?>
		<div class="<?php echo 'cbox cbox-3 is-cbox-' . $term->term_id; ?>">
			<div class="cbox-inner">
				<a class="cbox-featured is-overlay" aria-label="<?php echo esc_attr( $term->name ); ?>" href="<?php echo esc_url( $link ); ?>"><?php foxiz_render_category_featured( $featured_array, $featured_urls_array, $settings['crop_size'] ); ?></a>
				<div class="cbox-overlay overlay-wrap light-scheme">
					<div class="cbox-body">
						<div class="cbox-top cbox-content">
							<?php
							if ( ! empty( $settings['count_posts'] ) && '-1' !== (string) $settings['count_posts'] ) {
								foxiz_taxonomy_count( $term, '2' === (string) $settings['count_posts'] );
							}
							echo '<' . strip_tags( $settings['title_tag'] ) . ' class="cbox-title">';
							echo '<a class="p-url" href="' . esc_url( $link ) . '" rel="' . ( ( 'category' === $taxonomy ) ? 'category' : 'tag' ) . '">' . strip_tags( $term->name ) . '</a>';
							echo '</' . strip_tags( $settings['title_tag'] ) . '>';
							?>
						</div>
						<?php if ( ! empty( $term->description ) && $description ): ?>
							<div class="cbox-center cbox-description">
								<?php echo wp_trim_words( $term->description, 25 ); ?>
							</div>
						<?php endif;
						if ( ! empty( $settings['follow'] ) && '1' === (string) $settings['follow'] ) {
							echo '<div class="cbox-bottom">';
							foxiz_follow_trigger( [ 'id' => $id, 'name' => $term->name, 'classes' => 'is-light' ] );
							echo '</div>';
						} ?>
					</div>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_category_item_4' ) ) {
	function foxiz_category_item_4( $settings = [] ) {

		if ( ! empty( $settings['cid'] ) ) {
			$term = get_term( $settings['cid'] );
		} elseif ( ! empty( $settings['slug'] ) ) {
			$term = get_term_by( 'slug', $settings['slug'], 'category' );
		}

		if ( empty( $term ) || is_wp_error( $term ) ) {
			return;
		}

		$id       = $term->term_id;
		$taxonomy = $term->taxonomy;

		if ( count( $settings['allowed_tax'] ) &&
		     ! in_array( $id, $settings['selected_ids'] ) &&
		     ! in_array( $taxonomy, $settings['allowed_tax'] )
		) {
			return;
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g1';
		}

		$link                = foxiz_get_term_link( $term );
		$featured_array      = [];
		$featured_urls_array = [];

		if ( ! empty( $settings['feat_ids'][ $id ] ) ) {
			$featured_array = [ (int) $settings['feat_ids'][ $id ] ];
		} else {
			$metas = rb_get_term_meta( 'foxiz_category_meta', $id );
			if ( ! empty( $metas['featured_image'] ) ) {
				$featured_array = $metas['featured_image'];
			}
			if ( ! empty( $metas['featured_image_urls'] ) ) {
				$featured_urls_array = $metas['featured_image_urls'];
			}
		}
		?>
		<div class="<?php echo 'cbox cbox-4 is-cbox-' . $term->term_id; ?>">
			<div class="cbox-inner">
				<?php if ( ! empty( $settings['follow'] ) && '1' === (string) $settings['follow'] ) {
					foxiz_follow_trigger( [ 'id' => $id, 'name' => $term->name, 'classes' => 'is-light' ] );
				} ?>
				<a class="cbox-featured" aria-label="<?php echo esc_attr( $term->name ); ?>" href="<?php echo esc_url( $link ); ?>"><?php foxiz_render_category_featured( $featured_array, $featured_urls_array, $settings['crop_size'] ); ?></a>
				<div class="cbox-body">
					<div class="cbox-content">
						<?php if ( ! empty( $settings['count_posts'] ) && '-1' !== (string) $settings['count_posts'] ) {
							foxiz_taxonomy_count( $term, '2' === (string) $settings['count_posts'] );
						}
						echo '<' . strip_tags( $settings['title_tag'] ) . ' class="cbox-title">';
						echo '<a class="p-url" href="' . esc_url( $link ) . '" rel="' . ( ( ! empty( $term->taxonomy ) && 'category' === $term->taxonomy ) ? 'category' : 'tag' ) . '">' . strip_tags( $term->name ) . '</a>';
						echo '</' . strip_tags( $settings['title_tag'] ) . '>';
						?>
					</div>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_category_item_5' ) ) {
	function foxiz_category_item_5( $settings = [] ) {

		if ( ! empty( $settings['cid'] ) ) {
			$term = get_term( $settings['cid'] );
		} elseif ( ! empty( $settings['slug'] ) ) {
			$term = get_term_by( 'slug', $settings['slug'], 'category' );
		}

		if ( empty( $term ) || is_wp_error( $term ) ) {
			return;
		}

		$id       = $term->term_id;
		$taxonomy = $term->taxonomy;

		if ( count( $settings['allowed_tax'] ) &&
		     ! in_array( $id, $settings['selected_ids'] ) &&
		     ! in_array( $taxonomy, $settings['allowed_tax'] )
		) {
			return;
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h4';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g1';
		}

		$link                = foxiz_get_term_link( $term );
		$featured_array      = [];
		$featured_urls_array = [];

		if ( ! empty( $settings['feat_ids'][ $id ] ) ) {
			$featured_array = [ (int) $settings['feat_ids'][ $id ] ];
		} else {
			$metas = rb_get_term_meta( 'foxiz_category_meta', $id );
			if ( ! empty( $metas['featured_image'] ) ) {
				$featured_array = $metas['featured_image'];
			}
			if ( ! empty( $metas['featured_image_urls'] ) ) {
				$featured_urls_array = $metas['featured_image_urls'];
			}
		}
		?>
		<div class="<?php echo 'cbox cbox-5 is-cbox-' . $term->term_id; ?>">
			<div class="cbox-featured-holder">
				<?php if ( ! empty( $settings['follow'] ) && '1' === (string) $settings['follow'] ) : ?>
					<span class="cbox-featured"><?php foxiz_render_category_featured( $featured_array, $featured_urls_array, $settings['crop_size'] ); ?></span>
					<?php foxiz_follow_trigger( [
							'id'      => $id,
							'type'    => 'category',
							'classes' => 'is-light',
					] );
				else : ?>
					<a class="cbox-featured" aria-label="<?php echo esc_attr( $term->name ); ?>" href="<?php echo esc_url( $link ); ?>"><?php foxiz_render_category_featured( $featured_array, $featured_urls_array, $settings['crop_size'] ); ?></a>
				<?php endif; ?>
			</div>
			<div class="cbox-content">
				<?php echo '<' . strip_tags( $settings['title_tag'] ) . ' class="cbox-title">';
				echo '<a class="p-url" href="' . esc_url( $link ) . '" rel="' . ( ( ! empty( $term->taxonomy ) && 'category' === $term->taxonomy ) ? 'category' : 'tag' ) . '">' . strip_tags( $term->name ) . '</a>';
				echo '</' . strip_tags( $settings['title_tag'] ) . '>';
				if ( ! empty( $settings['count_posts'] ) && '-1' !== (string) $settings['count_posts'] ) {
					foxiz_taxonomy_count( $term, '2' === (string) $settings['count_posts'] );
				} ?>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_category_item_6' ) ) {
	function foxiz_category_item_6( $settings = [] ) {

		if ( ! empty( $settings['cid'] ) ) {
			$term = get_term( $settings['cid'] );
		} elseif ( ! empty( $settings['slug'] ) ) {
			$term = get_term_by( 'slug', $settings['slug'], 'category' );
		}

		if ( empty( $term ) || is_wp_error( $term ) ) {
			return;
		}

		$id       = $term->term_id;
		$taxonomy = $term->taxonomy;

		if ( count( $settings['allowed_tax'] ) &&
		     ! in_array( $id, $settings['selected_ids'] ) &&
		     ! in_array( $taxonomy, $settings['allowed_tax'] )
		) {
			return;
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h4';
		}

		$link                = foxiz_get_term_link( $term );
		$featured_array      = [];
		$featured_urls_array = [];

		if ( ! empty( $settings['feat_ids'][ $id ] ) ) {
			$featured_array = [ (int) $settings['feat_ids'][ $id ] ];
		} else {
			$metas = rb_get_term_meta( 'foxiz_category_meta', $id );
			if ( ! empty( $metas['featured_image'] ) ) {
				$featured_array = $metas['featured_image'];
			}
			if ( ! empty( $metas['featured_image_urls'] ) ) {
				$featured_urls_array = $metas['featured_image_urls'];
			}
		}
		?>
		<div class="<?php echo 'cbox cbox-6 is-cbox-' . $term->term_id; ?>">
			<?php if ( ! empty( $settings['feat'] ) && '1' === (string) $settings['feat'] && foxiz_get_category_featured( $featured_array, $featured_urls_array, 'small' ) ) : ?>
				<div class="cbox-featured-holder">
					<a class="cbox-featured" aria-label="<?php echo esc_attr( $term->name ); ?>" href="<?php echo esc_url( $link ); ?>"><?php echo foxiz_get_category_featured( $featured_array, $featured_urls_array, 'small' ); ?></a>
				</div>
			<?php endif; ?>
			<div class="cbox-content">
				<?php echo '<' . strip_tags( $settings['title_tag'] ) . ' class="cbox-title">';
				echo '<a class="p-url" href="' . esc_url( $link ) . '" rel="' . ( ( 'category' === $taxonomy ) ? 'category' : 'tag' ) . '">' . strip_tags( $term->name ) . '</a>';
				echo '</' . strip_tags( $settings['title_tag'] ) . '>';
				if ( ! empty( $settings['count_posts'] ) && '-1' !== (string) $settings['count_posts'] ) {
					foxiz_taxonomy_count( $term, '2' === (string) $settings['count_posts'] );
				} ?>
			</div>
			<?php
			if ( ! empty( $settings['follow'] ) && '1' === (string) $settings['follow'] ) {
				foxiz_follow_trigger( [ 'id' => $id, 'name' => $term->name ] );
			} ?>
		</div>
	<?php }
}