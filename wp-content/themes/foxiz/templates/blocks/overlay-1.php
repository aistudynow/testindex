<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_overlay_1' ) ) {
	/**
	 * @param array $settings
	 * @param null  $_query
	 *
	 * @return false|string
	 */
	function foxiz_get_overlay_1( $settings = [], $_query = null ) {

		$settings = wp_parse_args( $settings, [
			'uuid' => '',
			'name' => 'overlay_1',
		] );

		$settings['classes'] = 'block-overlay overlay-1';
		$settings['classes'] .= ! empty( $settings['overlay_scheme'] ) ? ' dark-overlay-scheme' : '';

		if ( ! empty( $settings['middle_mode'] ) ) {
			switch ( $settings['middle_mode'] ) {
				case  '1' :
					$settings['classes'] .= ' p-bg-overlay';
					break;
				case  '2' :
					$settings['classes'] .= ' p-top-gradient';
					break;
				default :
					$settings['classes'] .= ' p-gradient';
			}
		} else {
			$settings['classes'] .= ' p-gradient';
		}

		$settings = foxiz_detect_dynamic_query( $settings );

		if ( ! empty( $settings['slider'] ) && '1' === (string) $settings['slider'] ) {
			$settings['columns']    = false;
			$settings['column_gap'] = false;
		}

		$settings['no_found_rows'] = true;
		$min_posts                 = 1;

		if ( ! $_query ) {
			$_query = foxiz_query( $settings );
		}

		$settings = foxiz_get_design_builder_block( $settings );

		ob_start();
		foxiz_block_open_tag( $settings, $_query );
		if ( ! $_query->have_posts() || $_query->post_count < $min_posts ) {
			foxiz_error_posts( $_query, $min_posts );
		} else {
			foxiz_block_inner_open_tag( $settings );
			foxiz_loop_overlay_1( $settings, $_query );
			foxiz_block_inner_close_tag( $settings );
			wp_reset_postdata();
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_loop_overlay_1' ) ) {
	/**
	 * @param $settings
	 * @param $_query
	 */
	function foxiz_loop_overlay_1( $settings, $_query ) {

		$loop_index = 1;
		if ( ! empty( $settings['slider'] ) && $_query->post_count > 1 ) : ?>
			<div class="post-slider swiper-container pre-load" <?php foxiz_slider_attrs( $settings ); ?>>
				<div class="swiper-wrapper">
					<?php while ( $_query->have_posts() ) :
						$_query->the_post();
						if ( ! empty( $settings['eager_images'] ) ) {
							$settings['feat_lazyload'] = ( $loop_index <= $settings['eager_images'] ) ? 'none' : '1';
							$loop_index ++;
						}
						foxiz_overlay_1( $settings );
					endwhile;
					?>
				</div>
				<?php if ( ! empty( $settings['slider_dot'] && '1' === (string) $settings['slider_dot'] ) ) : ?>
					<div class="slider-pagination slider-pagination-top"></div>
				<?php endif;
				if ( ! empty( $settings['slider_nav'] && '1' === (string) $settings['slider_nav'] ) ) : ?>
					<div class="slider-prev rbi rbi-cleft"></div>
					<div class="slider-next rbi rbi-cright"></div>
				<?php endif; ?>
			</div>
		<?php else :
			while ( $_query->have_posts() ) :
				$_query->the_post();
				if ( ! empty( $settings['eager_images'] ) ) {
					$settings['feat_lazyload'] = ( $loop_index <= $settings['eager_images'] ) ? 'none' : '1';
					$loop_index ++;
				}
				foxiz_overlay_1( $settings );
			endwhile;
		endif;
	}
}


