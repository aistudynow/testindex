<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_render_single_gallery_2' ) ) {
	function foxiz_render_single_gallery_2() {

		$classes          = [ 'single-standard-7 single-gallery-2' ];
		$sidebar_name     = foxiz_get_single_setting( 'sidebar_name' );
		$sidebar_position = foxiz_get_single_sidebar_position();
		$crop_size        = foxiz_get_single_crop_size( 'foxiz_crop_o1' );

		if ( 'none' === $sidebar_position ) {
			$sidebar_name = false;
		}
		if ( empty( $sidebar_name ) || ! is_active_sidebar( $sidebar_name ) ) {
			$classes[] = 'without-sidebar';
		} else {
			$classes[] = 'is-sidebar-' . $sidebar_position;
			$classes[] = foxiz_get_single_sticky_sidebar();
		} ?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<?php foxiz_single_open_tag(); ?>
			<header class="single-header">
				<div class="rb-small-container edge-padding">
					<?php
					foxiz_single_breadcrumb();
					foxiz_single_entry_category();
					foxiz_single_title( 'fw-headline' );
					foxiz_single_tagline( 'fw-tagline' );
					foxiz_single_header_meta();
					?>
				</div>
				<div class="rb-s-container edge-padding">
					<?php
					foxiz_single_gallery_carousel( $crop_size );
					foxiz_disclosure_box();
					?>
				</div>
			</header>
			<div class="rb-s-container edge-padding">
				<div class="grid-container">
					<div class="s-ct">
						<?php
						foxiz_single_content();
						foxiz_single_author_box();
						foxiz_single_next_prev();
						foxiz_single_comment();
						?>
					</div>
					<?php foxiz_single_sidebar( $sidebar_name ); ?>
				</div>
			</div>
			<?php foxiz_single_close_tag(); ?>
			<div class="rb-s-container edge-padding">
				<?php foxiz_single_footer(); ?>
			</div>
		</div>
		<?php
	}
}