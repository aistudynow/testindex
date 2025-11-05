<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_overlay_1' ) ) {
	function foxiz_overlay_1( $settings = [] ) {

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h2';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g2';
		}
		$settings['post_classes'] = 'p-highlight p-overlay-1';
		$inner_classes            = 'overlay-inner p-content' . ( empty( $settings['overlay_scheme'] ) ? ' light-scheme' : '' );

		foxiz_post_open_tag( $settings );
		?>
		<div class="overlay-holder">
			<?php foxiz_entry_featured( $settings ); ?>
			<div class="overlay-wrap">
				<div class="<?php echo strip_tags( $inner_classes ); ?>">
					<?php
					foxiz_entry_top( $settings );
					foxiz_entry_title( $settings );
					foxiz_entry_review( $settings );
					foxiz_entry_excerpt( $settings );
					foxiz_entry_meta( $settings );
					foxiz_entry_readmore( $settings );
					?>
				</div>
			</div>
		</div>
		<?php foxiz_post_close_tag();
	}
}

if ( ! function_exists( 'foxiz_overlay_2' ) ) {
	function foxiz_overlay_2( $settings = [] ) {

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g1';
		}

		$settings['post_classes'] = 'p-overlay p-overlay-2';
		$inner_classes            = 'overlay-inner p-content' . ( empty( $settings['overlay_scheme'] ) ? ' light-scheme' : '' );

		foxiz_post_open_tag( $settings );
		?>
		<div class="overlay-holder">
			<?php foxiz_entry_featured( $settings ); ?>
			<div class="overlay-wrap">
				<div class="<?php echo strip_tags( $inner_classes ); ?>">
					<?php
					foxiz_entry_top( $settings );
					foxiz_entry_title( $settings );
					foxiz_entry_review( $settings );
					foxiz_entry_excerpt( $settings );
					foxiz_entry_meta( $settings );
					foxiz_entry_readmore( $settings );
					?>
				</div>
			</div>
		</div>
		<?php foxiz_post_close_tag();
	}
}

if ( ! function_exists( 'foxiz_overlay_flex' ) ) {
	function foxiz_overlay_flex( $settings = [] ) {

		if ( empty( $settings['block_structure'] ) || ! is_array( $settings['block_structure'] ) ) {
			return;
		}

		if ( empty( $settings['title_tag'] ) ) {
			$settings['title_tag'] = 'h3';
		}
		if ( empty( $settings['crop_size'] ) ) {
			$settings['crop_size'] = 'foxiz_crop_g1';
		}
		$settings['post_classes'] = 'p-overlay p-overlay-flex';
		$inner_classes            = 'overlay-inner p-content' . ( empty( $settings['overlay_scheme'] ) ? ' light-scheme' : '' );

		foxiz_post_open_tag( $settings );
		?>
		<div class="overlay-holder">
			<?php foxiz_entry_featured( $settings ); ?>
			<div class="overlay-wrap">
				<div class="<?php echo strip_tags( $inner_classes ); ?>">
					<?php
					foreach ( $settings['block_structure'] as $element ) :
						switch ( $element ) {
							case 'category' :
								foxiz_entry_top( $settings );
								break;
							case 'title' :
								foxiz_entry_title( $settings );
								break;
							case 'excerpt' :
								foxiz_entry_excerpt( $settings );
								break;
							case 'meta' :
								foxiz_entry_meta( $settings );
								break;
							case 'readmore' :
								foxiz_entry_readmore( $settings );
								break;
							case 'divider' :
								foxiz_entry_divider( $settings );
								break;
							case 'review' :
								echo foxiz_get_entry_review( $settings );
								break;
						}
					endforeach; ?>
				</div>
			</div>
		</div>
		<?php foxiz_post_close_tag();
	}
}
