<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_render_single_attachment' ) ) {
	function foxiz_render_single_attachment() {

		$classes   = [];
		$classes[] = 'single-standard-1 without-sidebar'; ?>
		<div class="<?php echo join( ' ', $classes ); ?>">
			<div class="rb-s-container edge-padding">
				<?php foxiz_single_open_tag(); ?>
				<div class="grid-container">
					<div class="block-inner">
						<div class="s-ct">
							<header class="single-header">
								<?php
								foxiz_single_breadcrumb();
								foxiz_single_title();
								foxiz_single_tagline();
								foxiz_single_header_meta();
								?>
							</header>
							<?php foxiz_single_simple_content(); ?>
						</div>
					</div>
				</div>
				<?php
				foxiz_single_close_tag(); ?>
			</div>
		</div>
		<?php
	}
}