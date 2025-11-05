<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_hierarchical_3' ) ) {
	function foxiz_get_hierarchical_3( $settings = [], $_query = null ) {

		$settings = wp_parse_args( $settings, [
			'uuid' => '',
			'name' => 'hierarchical_3',
		] );
		$settings = foxiz_detect_dynamic_query( $settings );

		$settings['no_found_rows'] = true;
		$min_posts                 = 2;
		$settings['classes']       = 'block-hrc hrc-3 p-gradient';

		if ( ! $_query ) {
			$_query = foxiz_query( $settings );
		}
		$settings = foxiz_get_design_builder_block( $settings );
		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h2';
		}
		$settings['featured_classes'] = 'ratio-v1';

		ob_start();
		foxiz_block_open_tag( $settings, $_query );
		if ( ! $_query->have_posts() || $_query->post_count < $min_posts ) {
			foxiz_error_posts( $_query, $min_posts );
		} else {
			foxiz_loop_hierarchical_3( $settings, $_query );
			wp_reset_postdata();
		}
		foxiz_block_close_tag();

		return ob_get_clean();
	}
}

if ( ! function_exists( 'foxiz_loop_hierarchical_3' ) ) {
	/**
	 * @param  $settings
	 * @param  $_query
	 */
	function foxiz_loop_hierarchical_3( $settings, $_query ) {

		$flag   = true;
		$inline = [
			'title_tag'     => 'span',
			'title_classes' => 'h5',
		];
		if ( ! empty( $settings['sub_title_tag'] ) ) {
			$inline['title_classes'] = $settings['sub_title_tag'];
		}

		ob_start();
		while ( $_query->have_posts() ) {
			$_query->the_post();

			if ( $flag ) {
				$flag = false;
				continue;
			}
			foxiz_list_inline( $inline );
		}
		$buffer = ob_get_clean();

		$_query->rewind_posts();

		$settings['post_classes'] = 'p-overlay-hrc p-highlight holder-wrap';
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g3';
		}

		while ( $_query->have_posts() ) :
			$_query->the_post();
			foxiz_post_open_tag( $settings );
			foxiz_entry_featured( $settings ); ?>
			<div class="overlay-wrap light-scheme">
				<div class="overlay-inner">
					<div class="p-content">
						<?php
						foxiz_entry_top( $settings );
						foxiz_entry_title( $settings );
						foxiz_entry_review( $settings );
						foxiz_entry_excerpt( $settings );
						foxiz_entry_meta( $settings );
						foxiz_entry_readmore( $settings );
						?>
					</div>
					<div class="block-inner"><?php
						if ( ! empty( $buffer ) ) {
							echo html_entity_decode( $buffer );
						} ?>
					</div>
				</div>
			</div>
			<?php foxiz_post_close_tag();
			break;
		endwhile;
	}
}
