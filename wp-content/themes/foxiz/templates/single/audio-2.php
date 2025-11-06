<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_render_single_audio_2' ) ) {
	function foxiz_render_single_audio_2() {

		$classes          = [ 'single-standard-2 single-embed-2' ];
		$sidebar_name     = foxiz_get_single_setting( 'sidebar_name' );
		$sidebar_position = foxiz_get_single_sidebar_position();

		if ( 'none' === $sidebar_position ) {
			$sidebar_name = false;
		}

		$yes_hosted = rb_get_meta( 'audio_hosted' );

		if ( ! foxiz_get_audio_embed( get_the_ID() ) ) {
			$classes[] = 'no-sfeat';
		}
		if ( ! empty( $yes_hosted ) ) {
			$classes[] = 'yes-audio-hosted';
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
				<div class="single-header-inner">
					<div class="embed-bg-overlay" style="background-image: url(<?php the_post_thumbnail_url( 'full' ); ?>);"></div>
					<div class="rb-s-container edge-padding light-scheme">
						<?php
						foxiz_single_breadcrumb();
						foxiz_single_entry_category();
						foxiz_single_title( 'fw-headline' );
						foxiz_single_tagline( 'fw-tagline' );
						foxiz_single_header_meta();
						foxiz_disclosure_box();
						if ( ! empty( $yes_hosted ) ) {
							echo '<div class="audio-hosted-2 light-scheme">';
							foxiz_single_audio_embed();
							echo '</div>';
						} else {
							foxiz_single_audio_embed();
						}
						?>
					</div>
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
			<div class="single-footer rb-s-container edge-padding">
				<?php foxiz_single_footer(); ?>
			</div>
		</div>
		<?php
	}
}