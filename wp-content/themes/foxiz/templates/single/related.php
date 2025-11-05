<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_layout_related_1' ) ) {
	function foxiz_get_layout_related_1( $settings ) {

		if ( empty( $settings['post_id'] ) ) {
			$settings['post_id'] = get_the_ID();
		}

		$_query = foxiz_get_related_data( $settings );
		if ( empty( $_query ) || ! method_exists( $_query, 'have_posts' ) || ! $_query->have_posts() ) {
			return false;
		}

		$flag = true;
		if ( empty( $settings['heading_tag'] ) ) {
			$settings['heading_tag'] = 'h4';
		}

		$class_name          = 'related-sec related-1';
		$title_class_name    = 'h5';
		$subtitle_class_name = 'h6';

		if ( ! empty( $settings['width'] ) ) {
			$class_name .= ' is-width-' . trim( $settings['width'] );
			if ( 'wide' === $settings['width'] ) {
				$title_class_name    = 'h4';
				$subtitle_class_name = 'h5';
			}
		} else {
			$class_name .= ' is-width-right';
		}

		if ( ! empty( $settings['style'] ) ) {
			$class_name .= ' is-style-' . trim( $settings['style'] );
		}
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<div class="inner">
				<?php if ( ! empty( $settings['heading'] ) ) {
					echo foxiz_get_heading( [
							'title'    => $settings['heading'],
							'layout'   => $settings['heading_layout'],
							'html_tag' => $settings['heading_tag'],
							'classes'  => 'none-toc',
					] );
				} ?>
				<div class="block-inner">
					<?php while ( $_query->have_posts() ) :
						$_query->the_post();
						if ( $flag ) {
							foxiz_list_small_2( [
									'featured_position' => 'right',
									'crop_size'         => 'thumbnail',
									'title_tag'         => 'div',
									'title_classes'     => $title_class_name,
							] );
							$flag = false;
						} else {
							foxiz_list_inline( [
									'title_tag'     => 'div',
									'title_classes' => $subtitle_class_name,
							] );
						}
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_get_layout_related_2' ) ) {
	function foxiz_get_layout_related_2( $settings ) {

		if ( empty( $settings['post_id'] ) ) {
			$settings['post_id'] = get_the_ID();
		}

		$_query = foxiz_get_related_data( $settings );
		if ( empty( $_query ) || ! method_exists( $_query, 'have_posts' ) || ! $_query->have_posts() ) {
			return false;
		}

		if ( empty( $settings['heading_tag'] ) ) {
			$settings['heading_tag'] = 'h4';
		}

		$class_name = 'related-sec related-2';
		$class_name .= ! empty( $settings['width'] ) ? ' is-width-' . trim( $settings['width'] ) : ' is-width-right';

		if ( ! empty( $settings['style'] ) ) {
			$class_name .= ' is-style-' . trim( $settings['style'] );
		}

		$listing_params = [
				'featured_position' => 'right',
				'crop_size'         => 'thumbnail',
				'title_tag'         => 'div',
				'title_classes'     => 'h5 none-toc',
		];

		if ( 'wide' === $settings['width'] ) {
			$listing_params['title_classes'] = 'h4 none-toc';
			$listing_params['entry_meta']    = [ 'update' ];
		}
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<div class="inner block-list-small-2">
				<?php if ( ! empty( $settings['heading'] ) ) {
					echo foxiz_get_heading( [
							'title'    => $settings['heading'],
							'layout'   => $settings['heading_layout'],
							'html_tag' => $settings['heading_tag'],
							'classes'  => 'none-toc',
					] );
				} ?>
				<div class="block-inner">
					<?php while ( $_query->have_posts() ) :
						$_query->the_post();
						foxiz_list_small_2( $listing_params );
					endwhile;
					wp_reset_postdata();
					?></div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_get_layout_related_3' ) ) {
	function foxiz_get_layout_related_3( $settings ) {

		if ( empty( $settings['post_id'] ) ) {
			$settings['post_id'] = get_the_ID();
		}

		$_query = foxiz_get_related_data( $settings );
		if ( empty( $_query ) || ! method_exists( $_query, 'have_posts' ) || ! $_query->have_posts() ) {
			return false;
		}

		if ( empty( $settings['heading_tag'] ) ) {
			$settings['heading_tag'] = 'h4';
		}

		$class_name = 'related-sec related-3';
		$class_name .= ! empty( $settings['width'] ) ? ' is-width-' . trim( $settings['width'] ) : ' is-width-right';

		if ( ! empty( $settings['style'] ) ) {
			$class_name .= ' is-style-' . trim( $settings['style'] );
		}
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<div class="inner block-small block-hrc hrc-1">
				<?php if ( ! empty( $settings['heading'] ) ) {
					echo foxiz_get_heading( [
							'title'    => $settings['heading'],
							'layout'   => $settings['heading_layout'],
							'html_tag' => $settings['heading_tag'],
							'classes'  => 'none-toc',
					] );
				} ?>
				<div class="block-inner">
					<?php foxiz_loop_hierarchical_1( [
							'title_tag'     => 'div',
							'sub_title_tag' => 'div',
							'crop_size'     => 'foxiz_crop_g1',
							'title_classes' => 'h4 none-toc',
					], $_query );
					wp_reset_postdata();
					?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_get_layout_related_4' ) ) {
	function foxiz_get_layout_related_4( $settings ) {

		if ( empty( $settings['post_id'] ) ) {
			$settings['post_id'] = get_the_ID();
		}

		$_query = foxiz_get_related_data( $settings );
		if ( empty( $_query ) || ! method_exists( $_query, 'have_posts' ) || ! $_query->have_posts() ) {
			return false;
		}

		if ( empty( $settings['heading_tag'] ) ) {
			$settings['heading_tag'] = 'h3';
		}

		$settings['title_classes'] = ! empty( $settings['title_tag'] ) ? trim( $settings['title_tag'] ) : 'h4';
		$settings['title_tag']     = 'div';

		$class_name = 'related-sec related-4';
		$class_name .= ! empty( $settings['width'] ) ? ' is-width-' . trim( $settings['width'] ) : ' is-width-right';

		if ( ! empty( $settings['style'] ) ) {
			$class_name .= ' is-style-' . trim( $settings['style'] );
		}
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<div class="inner">
				<?php if ( ! empty( $settings['heading'] ) ) {
					echo foxiz_get_heading( [
							'title'    => $settings['heading'],
							'layout'   => $settings['heading_layout'],
							'html_tag' => $settings['heading_tag'],
							'classes'  => 'none-toc',
					] );
				} ?>
				<div class="block-inner">
					<?php while ( $_query->have_posts() ) :
						$_query->the_post();
						foxiz_list_inline( $settings );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_get_layout_related_5' ) ) {
	function foxiz_get_layout_related_5( $settings ) {

		if ( empty( $settings['post_id'] ) ) {
			$settings['post_id'] = get_the_ID();
		}

		$settings['title_classes'] = 'none-toc';

		$_query = foxiz_get_related_data( $settings );
		if ( empty( $_query ) || ! method_exists( $_query, 'have_posts' ) || ! $_query->have_posts() ) {
			return false;
		}

		if ( empty( $settings['heading_tag'] ) ) {
			$settings['heading_tag'] = 'h3';
		}

		$settings['title_classes'] = ! empty( $settings['title_tag'] ) ? trim( $settings['title_tag'] ) : 'h4';
		$settings['title_tag']     = 'div';

		$class_name = 'related-sec related-5';
		$class_name .= ! empty( $settings['width'] ) ? ' is-width-' . trim( $settings['width'] ) : ' is-width-right';

		if ( ! empty( $settings['style'] ) ) {
			$class_name .= ' is-style-' . trim( $settings['style'] );
		}
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<div class="inner">
				<?php if ( ! empty( $settings['heading'] ) ) {
					echo foxiz_get_heading( [
							'title'    => $settings['heading'],
							'layout'   => $settings['heading_layout'],
							'html_tag' => $settings['heading_tag'],
							'classes'  => 'none-toc',
					] );
				} ?>
				<div class="block-inner">
					<?php while ( $_query->have_posts() ) :
						$_query->the_post();
						foxiz_list_inline( $settings );
					endwhile;
					wp_reset_postdata();
					?></div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_get_layout_related_6' ) ) {
	function foxiz_get_layout_related_6( $settings ) {

		if ( empty( $settings['post_id'] ) ) {
			$settings['post_id'] = get_the_ID();
		}

		$_query = foxiz_get_related_data( $settings );
		if ( empty( $_query ) || ! method_exists( $_query, 'have_posts' ) || ! $_query->have_posts() ) {
			return false;
		}

		if ( empty( $settings['heading_tag'] ) ) {
			$settings['heading_tag'] = 'h3';
		}

		$title_class_name = ! empty( $settings['title_tag'] ) ? trim( $settings['title_tag'] ) : 'h5';

		$class_name = 'related-sec related-6';
		$class_name .= ! empty( $settings['width'] ) ? ' is-width-' . trim( $settings['width'] ) : ' is-width-wide';

		if ( ! empty( $settings['style'] ) ) {
			$class_name .= ' is-style-' . trim( $settings['style'] );
		}
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<div class="inner block-grid-small-1 rb-columns rb-col-3 is-gap-10">
				<?php if ( ! empty( $settings['heading'] ) ) {
					echo foxiz_get_heading( [
							'title'    => $settings['heading'],
							'layout'   => $settings['heading_layout'],
							'html_tag' => $settings['heading_tag'],
							'classes'  => 'none-toc',
					] );
				} ?>
				<div class="block-inner">
					<?php foxiz_loop_grid_small_1( [
							'title_tag'       => 'div',
							'columns'         => 3,
							'columns_tablet'  => 3,
							'columns_mobile'  => 1,
							'crop_size'       => 'foxiz_crop_g1',
							'design_override' => true,
							'title_classes'   => $title_class_name,
					], $_query );
					wp_reset_postdata();
					?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_inline_content_related' ) ) {
	function foxiz_inline_content_related( $data, $content ) {

		if ( empty( $data ) ) {
			$data = [];
		}

		if ( ! is_single() || is_singular( [
						'product',
						'web-story',
						'rb-etemplate',
						'forum',
						'topic',
						'reply',
				] ) ) {
			return $data;
		}

		$setting   = rb_get_meta( 'inline_related' );
		$positions = foxiz_get_single_setting( 'inline_related_pos' );

		if ( ( ! empty( $setting ) && '-1' === (string) $setting ) || empty( $positions ) ) {
			return $data;
		}

		$shortcode = trim( foxiz_get_option( 'single_post_inline_related' ) );
		if ( empty( $shortcode ) || false !== strpos( $content, '"related-sec' ) || false !== strpos( $content, 'rb-gut-related' ) ) {
			return $data;
		}

		$positions = array_map( 'absint', explode( ',', $positions ) );

		foreach ( $positions as $position ) {
			array_push( $data, [
					'render'    => do_shortcode( $shortcode ),
					'positions' => [ $position ],
			] );
		}

		return $data;
	}
}