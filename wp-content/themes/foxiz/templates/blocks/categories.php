<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_update_tax_list_settings' ) ) {
	function foxiz_update_tax_list_settings( $settings ) {

		$settings['selected_ids'] = [];
		$settings['allowed_tax']  = [];

		if ( ! empty( $settings['categories'] ) ) {
			$settings['selected_ids'] = explode( ',', $settings['categories'] );
			$settings['selected_ids'] = array_map( 'trim', $settings['selected_ids'] );
		}

		if ( ! empty( $settings['tax_followed'] ) ) {
			$settings['allowed_tax'] = explode( ',', $settings['tax_followed'] );
			$settings['allowed_tax'] = array_map( 'trim', $settings['allowed_tax'] );
		} else {
			if ( '1' === $settings['followed'] ) {
				$settings['allowed_tax'] = [ 'category' ];
			} elseif ( '2' == $settings['followed'] ) {
				$settings['allowed_tax'] = [ 'post_tag' ];
			}
		}

		if ( ! empty( $settings['feat_ids'] ) ) {
			$settings['feat_ids'] = json_decode( $settings['feat_ids'], true );
		}

		return $settings;
	}
}

if ( ! function_exists( 'foxiz_get_categories_1' ) ) {
	function foxiz_get_categories_1( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
				'uuid'       => '',
				'name'       => 'categories_1',
				'categories' => [],
		] );

		if ( empty( $settings['followed'] ) || '-1' === (string) $settings['followed'] || ! foxiz_get_option( 'bookmark_system' ) ) {
			$settings['display_mode'] = 'direct';
		}

		$settings['classes'] = 'block-categories block-categories-1';
		if ( empty( $settings['columns'] ) ) {
			$settings['columns'] = 4;
		}
		if ( empty( $settings['column_gap'] ) ) {
			$settings['column_gap'] = 10;
		}

		$params = foxiz_get_category_block_params( $settings );

		if ( empty( $settings['display_mode'] ) ) {
			$settings['classes'] .= ' is-ajax-categories';
			foxiz_categories_localize_script( $params );
		}

		ob_start();
		foxiz_block_open_tag( $settings );
		if ( foxiz_is_edit_mode() ) {
			foxiz_live_get_categories_1( $params );
		} else {
			if ( empty( $settings['display_mode'] ) && foxiz_get_option( 'bookmark_system' ) ) {
				echo '<div class="block-loader">' . foxiz_get_svg( 'loading', '', 'animation' ) . '</div>';
			} else {
				foxiz_live_get_categories_1( $params );
			}
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_live_get_categories_1' ) ) {
	function foxiz_live_get_categories_1( $settings = [] ) {

		$term_ids = foxiz_merge_saved_terms( $settings );
		if ( ! count( $term_ids ) ) {
			return;
		}
		$settings = foxiz_update_tax_list_settings( $settings );
		?>
		<div class="block-inner">
			<?php foreach ( $term_ids as $term_id ) :
				$settings['cid'] = $term_id;
				foxiz_category_item_1( $settings );
			endforeach; ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_get_categories_2' ) ) {
	function foxiz_get_categories_2( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
				'uuid'       => '',
				'name'       => 'categories_2',
				'categories' => [],
		] );

		if ( empty( $settings['followed'] ) || '-1' === (string) $settings['followed'] || ! foxiz_get_option( 'bookmark_system' ) ) {
			$settings['display_mode'] = 'direct';
		}

		$settings['classes'] = 'block-categories block-categories-2';

		if ( ! empty( $settings['gradient'] ) && '-1' === (string) $settings['gradient'] ) {
			$settings['classes'] .= ' no-gradient';
		}
		if ( empty( $settings['columns'] ) ) {
			$settings['columns'] = 4;
		}
		if ( empty( $settings['column_gap'] ) ) {
			$settings['column_gap'] = 5;
		}

		$params = foxiz_get_category_block_params( $settings );
		if ( empty( $settings['display_mode'] ) ) {
			$settings['classes'] .= ' is-ajax-categories';
			foxiz_categories_localize_script( $params );
		}

		ob_start();
		foxiz_block_open_tag( $settings );
		if ( foxiz_is_edit_mode() ) {
			foxiz_live_get_categories_2( $params );
		} else {
			if ( empty( $settings['display_mode'] ) && foxiz_get_option( 'bookmark_system' ) ) {
				echo '<div class="block-loader">' . foxiz_get_svg( 'loading', '', 'animation' ) . '</div>';
			} else {
				foxiz_live_get_categories_2( $params );
			}
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_live_get_categories_2' ) ) {
	function foxiz_live_get_categories_2( $settings = [] ) {

		$term_ids = foxiz_merge_saved_terms( $settings );
		if ( ! count( $term_ids ) ) {
			return;
		}
		$settings = foxiz_update_tax_list_settings( $settings );
		?>
		<div class="block-inner">
			<?php foreach ( $term_ids as $term_id ) :
				$settings['cid'] = $term_id;
				foxiz_category_item_2( $settings );
			endforeach; ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_get_categories_3' ) ) {
	function foxiz_get_categories_3( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
				'uuid'       => '',
				'name'       => 'categories_3',
				'categories' => [],
		] );

		if ( empty( $settings['followed'] ) || '-1' === (string) $settings['followed'] || ! foxiz_get_option( 'bookmark_system' ) ) {
			$settings['display_mode'] = 'direct';
		}

		$settings['classes'] = 'block-categories block-categories-3';
		if ( ! empty( $settings['gradient'] ) && '-1' === (string) $settings['gradient'] ) {
			$settings['classes'] .= ' no-gradient';
		}
		if ( empty( $settings['columns'] ) ) {
			$settings['columns'] = 4;
		}
		if ( empty( $settings['column_gap'] ) ) {
			$settings['column_gap'] = 5;
		}

		$params = foxiz_get_category_block_params( $settings );
		if ( empty( $settings['display_mode'] ) ) {
			$settings['classes'] .= ' is-ajax-categories';
			foxiz_categories_localize_script( $params );
		}

		ob_start();
		foxiz_block_open_tag( $settings );
		if ( foxiz_is_edit_mode() ) {
			foxiz_live_get_categories_3( $params );
		} else {
			if ( empty( $settings['display_mode'] ) && foxiz_get_option( 'bookmark_system' ) ) {
				echo '<div class="block-loader">' . foxiz_get_svg( 'loading', '', 'animation' ) . '</div>';
			} else {
				foxiz_live_get_categories_3( $params );
			}
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_live_get_categories_3' ) ) {
	function foxiz_live_get_categories_3( $settings = [] ) {

		$term_ids = foxiz_merge_saved_terms( $settings );
		if ( ! count( $term_ids ) ) {
			return;
		}
		$settings = foxiz_update_tax_list_settings( $settings );
		?>
		<div class="block-inner">
			<?php foreach ( $term_ids as $term_id ) :
				$settings['cid'] = $term_id;
				foxiz_category_item_3( $settings );
			endforeach; ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_get_categories_4' ) ) {
	function foxiz_get_categories_4( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
				'uuid'       => '',
				'name'       => 'categories_4',
				'categories' => [],
		] );

		if ( empty( $settings['followed'] ) || '-1' === (string) $settings['followed'] || ! foxiz_get_option( 'bookmark_system' ) ) {
			$settings['display_mode'] = 'direct';
		}

		$settings['classes'] = 'block-categories block-categories-4';
		if ( empty( $settings['columns'] ) ) {
			$settings['columns'] = 4;
		}
		if ( empty( $settings['column_gap'] ) ) {
			$settings['column_gap'] = 10;
		}

		$params = foxiz_get_category_block_params( $settings );
		if ( empty( $settings['display_mode'] ) ) {
			$settings['classes'] .= ' is-ajax-categories';
			foxiz_categories_localize_script( $params );
		}

		ob_start();
		foxiz_block_open_tag( $settings );
		if ( foxiz_is_edit_mode() ) {
			foxiz_live_get_categories_4( $params );
		} else {
			if ( empty( $settings['display_mode'] ) && foxiz_get_option( 'bookmark_system' ) ) {
				echo '<div class="block-loader">' . foxiz_get_svg( 'loading', '', 'animation' ) . '</div>';
			} else {
				foxiz_live_get_categories_4( $params );
			}
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_live_get_categories_4' ) ) {
	function foxiz_live_get_categories_4( $settings = [] ) {

		$term_ids = foxiz_merge_saved_terms( $settings );
		if ( ! count( $term_ids ) ) {
			return;
		}
		$settings = foxiz_update_tax_list_settings( $settings );
		?>
		<div class="block-inner">
			<?php foreach ( $term_ids as $term_id ) :
				$settings['cid'] = $term_id;
				foxiz_category_item_4( $settings );
			endforeach; ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_get_categories_5' ) ) {
	function foxiz_get_categories_5( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
				'uuid'       => '',
				'name'       => 'categories_5',
				'categories' => [],
		] );

		if ( empty( $settings['followed'] ) || '-1' === (string) $settings['followed'] || ! foxiz_get_option( 'bookmark_system' ) ) {
			$settings['display_mode'] = 'direct';
		}

		$settings['classes'] = 'block-categories block-categories-5';
		if ( empty( $settings['columns'] ) ) {
			$settings['columns'] = 4;
		}
		if ( empty( $settings['column_gap'] ) ) {
			$settings['column_gap'] = 10;
		}

		$params = foxiz_get_category_block_params( $settings );
		if ( empty( $settings['display_mode'] ) ) {
			$settings['classes'] .= ' is-ajax-categories';
			foxiz_categories_localize_script( $params );
		}

		ob_start();
		foxiz_block_open_tag( $settings );
		if ( foxiz_is_edit_mode() ) {
			foxiz_live_get_categories_5( $params );
		} else {
			if ( empty( $settings['display_mode'] ) && foxiz_get_option( 'bookmark_system' ) ) {
				echo '<div class="block-loader">' . foxiz_get_svg( 'loading', '', 'animation' ) . '</div>';
			} else {
				foxiz_live_get_categories_5( $params );
			}
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_live_get_categories_5' ) ) {
	function foxiz_live_get_categories_5( $settings = [] ) {

		$term_ids = foxiz_merge_saved_terms( $settings );
		if ( ! count( $term_ids ) ) {
			return;
		}
		$settings = foxiz_update_tax_list_settings( $settings );
		?>
		<div class="block-inner">
			<?php foreach ( $term_ids as $term_id ) :
				$settings['cid'] = $term_id;
				foxiz_category_item_5( $settings );
			endforeach; ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_get_categories_6' ) ) {
	function foxiz_get_categories_6( $settings = [] ) {

		$settings = wp_parse_args( $settings, [
				'uuid'       => '',
				'name'       => 'categories_6',
				'categories' => [],
		] );

		if ( empty( $settings['followed'] ) || '-1' === (string) $settings['followed'] || ! foxiz_get_option( 'bookmark_system' ) ) {
			$settings['display_mode'] = 'direct';
		}

		$settings['classes'] = 'block-categories block-categories-6';

		$params = foxiz_get_category_block_params( $settings );
		if ( empty( $settings['display_mode'] ) ) {
			$settings['classes'] .= ' is-ajax-categories';
			foxiz_categories_localize_script( $params );
		}

		ob_start();
		foxiz_block_open_tag( $settings );
		if ( foxiz_is_edit_mode() ) {
			foxiz_live_get_categories_6( $params );
		} else {
			if ( empty( $settings['display_mode'] ) && foxiz_get_option( 'bookmark_system' ) ) {
				echo '<div class="block-loader">' . foxiz_get_svg( 'loading', '', 'animation' ) . '</div>';
			} else {
				foxiz_live_get_categories_6( $params );
			}
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_live_get_categories_6' ) ) {
	function foxiz_live_get_categories_6( $settings = [] ) {

		$term_ids = foxiz_merge_saved_terms( $settings );
		if ( ! count( $term_ids ) ) {
			return;
		}
		$settings = foxiz_update_tax_list_settings( $settings );
		?>
		<div class="categories-6-inner">
			<?php foreach ( $term_ids as $term_id ) :
				$settings['cid'] = $term_id;
				foxiz_category_item_6( $settings );
			endforeach; ?>
		</div>
	<?php }
}
