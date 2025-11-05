<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_render_header_t1' ) ) {
	function foxiz_render_header_t1() {

		$settings                = foxiz_get_header_settings( 'hd1' );
		$settings['transparent'] = true;
		$classes                 = 'header-wrap rb-section header-set-1 header-1 header-transparent';
		if ( ! empty( $settings['hd1_width'] ) ) {
			$classes .= ' header-fw';
		}
		?>
		<div id="site-header" class="<?php echo strip_tags( $classes ); ?>">
			<?php foxiz_render_top_site();
			foxiz_reading_process_indicator();
			?>
			<div id="navbar-outer" class="navbar-outer">
				<div id="sticky-holder" class="sticky-holder">
					<div class="navbar-wrap navbar-transparent">
						<div class="rb-container edge-padding">
							<div class="navbar-inner">
								<div class="navbar-left">
									<?php
									foxiz_render_logo( $settings );
									foxiz_render_main_menu( 'transparent-menu', $settings['sub_scheme'] );
									foxiz_header_more( $settings );
									foxiz_single_sticky();
									?>
								</div>
								<div class="navbar-right">
									<?php foxiz_render_nav_right( $settings ); ?>
								</div>
							</div>
						</div>
					</div>
					<?php
					foxiz_header_mobile( $settings );
					foxiz_header_alert( $settings );
					?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_header_t2' ) ) {
	function foxiz_render_header_t2() {

		$settings                = foxiz_get_header_settings( 'hd1' );
		$settings['transparent'] = true;
		$classes                 = 'header-wrap rb-section header-set-1 header-2 header-transparent';
		if ( ! empty( $settings['hd1_width'] ) ) {
			$classes .= ' header-fw';
		}
		?>
		<div id="site-header" class="<?php echo strip_tags( $classes ); ?>">
			<?php foxiz_render_top_site();
			foxiz_reading_process_indicator();
			?>
			<div id="navbar-outer" class="navbar-outer">
				<div id="sticky-holder" class="sticky-holder">
					<div class="navbar-wrap navbar-transparent">
						<div class="rb-container edge-padding">
							<div class="navbar-inner">
								<div class="navbar-left">
									<?php foxiz_render_logo( $settings ); ?>
								</div>
								<div class="navbar-center">
									<?php
									foxiz_render_main_menu( false, $settings['sub_scheme'] );
									foxiz_header_more( $settings );
									foxiz_single_sticky();
									?>
								</div>
								<div class="navbar-right">
									<?php foxiz_render_nav_right( $settings ); ?>
								</div>
							</div>
						</div>
					</div>
					<?php
					foxiz_header_mobile( $settings );
					foxiz_header_alert( $settings );
					?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_header_t3' ) ) {
	function foxiz_render_header_t3() {

		$settings                = foxiz_get_header_settings( 'hd1' );
		$settings['transparent'] = true;
		$classes                 = 'header-wrap rb-section header-set-1 header-3 header-transparent';
		if ( ! empty( $settings['hd1_width'] ) ) {
			$classes .= ' header-fw';
		}
		?>
		<div id="site-header" class="<?php echo strip_tags( $classes ); ?>">
			<?php foxiz_render_top_site();
			foxiz_reading_process_indicator();
			?>
			<div id="navbar-outer" class="navbar-outer">
				<div id="sticky-holder" class="sticky-holder">
					<div class="navbar-wrap navbar-transparent">
						<div class="rb-container edge-padding">
							<div class="navbar-inner">
								<div class="navbar-left">
									<?php
									foxiz_render_logo( $settings );
									?>
								</div>
								<div class="navbar-center">
									<?php
									foxiz_render_main_menu( false, $settings['sub_scheme'] );
									foxiz_header_more( $settings );
									foxiz_single_sticky();
									?>
								</div>
								<div class="navbar-right">
									<?php foxiz_render_nav_right( $settings ); ?>
								</div>
							</div>
						</div>
					</div>
					<?php
					foxiz_header_mobile( $settings );
					foxiz_header_alert( $settings );
					?>
				</div>
			</div>
		</div>
		<?php
	}
}