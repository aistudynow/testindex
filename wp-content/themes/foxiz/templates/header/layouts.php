<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_render_header_1' ) ) {
	function foxiz_render_header_1() {

		$settings  = foxiz_get_header_settings( 'hd1' );
		$classes   = [];
		$classes[] = 'header-wrap rb-section header-set-1 header-1';
		if ( ! empty( $settings['hd1_width'] ) ) {
			$classes[] = 'header-fw';
		} else {
			$classes[] = 'header-wrapper';
		}
		if ( ! empty( $settings['nav_style'] ) ) {
			$classes[] = 'style-' . $settings['nav_style'];
		} else {
			$classes[] = 'style-shadow';
		}
		if ( foxiz_get_mobile_quick_access() ) {
			$classes[] = 'has-quick-menu';
		}
		?>
		<div id="site-header" class="<?php echo join( ' ', $classes ); ?>">
			<?php foxiz_render_top_site();
			foxiz_reading_process_indicator();
			?>
			<div id="navbar-outer" class="navbar-outer">
				<div id="sticky-holder" class="sticky-holder">
					<div class="navbar-wrap">
						<div class="rb-container edge-padding">
							<div class="navbar-inner">
								<div class="navbar-left">
									<?php
									foxiz_render_logo( $settings );
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
			<?php foxiz_header_ad_widget_section(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_header_2' ) ) {
	function foxiz_render_header_2() {

		$settings  = foxiz_get_header_settings( 'hd1' );
		$classes   = [];
		$classes[] = 'header-wrap rb-section header-set-1 header-2';
		if ( ! empty( $settings['hd1_width'] ) ) {
			$classes[] = 'header-fw';
		} else {
			$classes[] = 'header-wrapper';
		}
		if ( ! empty( $settings['nav_style'] ) ) {
			$classes[] = 'style-' . $settings['nav_style'];
		} else {
			$classes[] = 'style-shadow';
		}
		if ( foxiz_get_mobile_quick_access() ) {
			$classes[] = 'has-quick-menu';
		}
		?>
		<div id="site-header" class="<?php echo join( ' ', $classes ); ?>">
			<?php foxiz_render_top_site();
			foxiz_reading_process_indicator();
			?>
			<div id="navbar-outer" class="navbar-outer">
				<div id="sticky-holder" class="sticky-holder">
					<div class="navbar-wrap">
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
			<?php foxiz_header_ad_widget_section(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_header_3' ) ) {
	function foxiz_render_header_3() {

		$settings  = foxiz_get_header_settings( 'hd1' );
		$classes   = [];
		$classes[] = 'header-wrap rb-section header-set-1 header-3';
		if ( ! empty( $settings['hd1_width'] ) ) {
			$classes[] = 'header-fw';
		} else {
			$classes[] = 'header-wrapper';
		}
		if ( ! empty( $settings['nav_style'] ) ) {
			$classes[] = 'style-' . $settings['nav_style'];
		} else {
			$classes[] = 'style-shadow';
		}
		if ( foxiz_get_mobile_quick_access() ) {
			$classes[] = 'has-quick-menu';
		}
		?>
		<div id="site-header" class="<?php echo join( ' ', $classes ); ?>">
			<?php foxiz_render_top_site();
			foxiz_reading_process_indicator();
			?>
			<div id="navbar-outer" class="navbar-outer">
				<div id="sticky-holder" class="sticky-holder">
					<div class="navbar-wrap">
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
			<?php foxiz_header_ad_widget_section(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_header_4' ) ) {
	function foxiz_render_header_4() {

		$settings  = foxiz_get_header_settings( 'hd4' );
		$classes   = [];
		$classes[] = 'header-wrap rb-section header-4';
		if ( ! empty( $settings['hd4_width'] ) && 'full' === $settings['hd4_width'] ) {
			$classes[] = 'header-fw';
		} else {
			$classes[] = 'header-wrapper';
		}
		if ( foxiz_get_mobile_quick_access() ) {
			$classes[] = 'has-quick-menu';
		}
		?>
		<div id="site-header" class="<?php echo join( ' ', $classes ); ?>">
			<?php
			foxiz_render_top_site();
			foxiz_reading_process_indicator();
			?>
			<div class="logo-sec">
				<div class="logo-sec-inner rb-container edge-padding">
					<div class="logo-sec-left"><?php foxiz_render_logo( $settings ); ?></div>
					<div class="logo-sec-right">
						<?php
						if ( ! empty( $settings['header_socials'] ) ) {
							foxiz_header_socials( $settings );
						}
						if ( ! empty( $settings['header_search_icon'] ) ) {
							$settings['header_search_heading'] = '';
							foxiz_header_search_form( $settings );
						}
						?></div>
				</div>
			</div>
			<div id="navbar-outer" class="navbar-outer">
				<div id="sticky-holder" class="sticky-holder">
					<div class="navbar-wrap">
						<div class="rb-container edge-padding">
							<div class="navbar-inner">
								<div class="navbar-left">
									<?php
									foxiz_render_main_menu( false, $settings['sub_scheme'] );
									foxiz_header_more( $settings );
									foxiz_single_sticky();
									?>
								</div>
								<div class="navbar-right">
									<?php
									if ( ! empty( $settings['header_login_icon'] ) ) {
										foxiz_header_user( $settings );
									}
									foxiz_header_mini_cart();
									if ( ! empty( $settings['header_notification'] ) ) {
										foxiz_header_notification( $settings );
									}
									if ( ! empty( $settings['single_font_resizer'] ) ) {
										foxiz_header_font_resizer();
									}
									foxiz_dark_mode_switcher();
									?>
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
			<?php foxiz_header_ad_widget_section(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_header_5' ) ) {
	function foxiz_render_header_5() {

		$settings  = foxiz_get_header_settings( 'hd5' );
		$classes   = [];
		$classes[] = 'header-wrap rb-section header-5';

		if ( empty( $settings['hd5_width'] ) ) {
			$classes[] = 'header-fw';
		} else {
			$classes[] = 'header-wrapper';
		}
		if ( ! empty( $settings['nav_style'] ) ) {
			$classes[] = 'style-' . $settings['nav_style'];
		}
		if ( foxiz_get_mobile_quick_access() ) {
			$classes[] = 'has-quick-menu';
		}
		?>
		<div id="site-header" class="<?php echo join( ' ', $classes ); ?>">
			<?php
			foxiz_render_top_site();
			foxiz_reading_process_indicator();
			?>
			<div class="logo-sec">
				<div class="logo-sec-inner rb-container edge-padding">
					<div class="logo-sec-left">
						<?php
						if ( ! empty( $settings['header_login_icon'] ) ) {
							foxiz_header_user( $settings );
						}
						if ( ! empty( $settings['header_socials'] ) ) {
							foxiz_header_socials( $settings );
						}
						?>
					</div>
					<div class="logo-sec-center"><?php foxiz_render_logo( $settings ); ?></div>
					<div class="logo-sec-right">
						<div class="navbar-right">
							<?php
							foxiz_header_mini_cart();
							if ( ! empty( $settings['header_notification'] ) ) {
								foxiz_header_notification( $settings );
							}
							if ( ! empty( $settings['header_search_icon'] ) ) {
								$settings['header_search_mode'] = 'search';
								foxiz_header_search( $settings );
							}
							if ( ! empty( $settings['single_font_resizer'] ) ) {
								foxiz_header_font_resizer();
							}
							foxiz_dark_mode_switcher();
							?>
						</div>
					</div>
				</div>
			</div>
			<div id="navbar-outer" class="navbar-outer">
				<div id="sticky-holder" class="sticky-holder">
					<div class="navbar-wrap">
						<div class="rb-container edge-padding">
							<div class="navbar-inner">
								<div class="navbar-center">
									<?php
									foxiz_render_main_menu( false, $settings['sub_scheme'] );
									foxiz_header_more( $settings );
									foxiz_single_sticky();
									?>
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
			<?php foxiz_header_ad_widget_section(); ?>
		</div>
		<?php
	}
}

/** amp header */
if ( ! function_exists( 'foxiz_render_header_amp' ) ) {
	function foxiz_render_header_amp() {

		$classes   = [];
		$settings  = foxiz_get_option();
		$classes[] = 'header-wrap header-set-1 rb-section';
		if ( foxiz_get_mobile_quick_access() ) {
			$classes[] = 'has-quick-menu';
		} ?>
		<header id="amp-header" class="<?php echo join( ' ', $classes ); ?>">
			<div id="navbar-outer" class="navbar-outer">
				<?php
				foxiz_header_mobile( $settings );
				foxiz_header_alert( $settings );
				?>
			</div>
		</header>
		<?php
		if ( function_exists( 'foxiz_amp_ad' ) ) {
			foxiz_amp_ad( [
				'type'      => foxiz_get_option( 'amp_header_ad_type' ),
				'client'    => foxiz_get_option( 'amp_header_adsense_client' ),
				'slot'      => foxiz_get_option( 'amp_header_adsense_slot' ),
				'size'      => foxiz_get_option( 'amp_header_adsense_size' ),
				'custom'    => foxiz_get_option( 'amp_header_ad_code' ),
				'classname' => 'header-amp-ad amp-ad-wrap',
			] );
		}
	}
}

if ( ! function_exists( 'foxiz_render_header_rb_template' ) ) {
	/**
	 * @param string $shortcode
	 *
	 * @return false
	 */
	function foxiz_render_header_rb_template( $shortcode = '' ) {

		if ( empty( $shortcode ) ) {
			foxiz_render_header_1();

			return false;
		}
		?>
		<div id="site-header" class="header-wrap rb-section header-template">
			<?php
			foxiz_render_top_site();
			foxiz_reading_process_indicator(); ?>
			<div class="navbar-outer navbar-template-outer">
				<div id="header-template-holder"><?php
					echo '<div class="header-template-inner">' . do_shortcode( $shortcode ) . '</div>';
					foxiz_header_mobile( foxiz_get_option() );
					?></div>
			</div>
			<?php foxiz_header_ad_widget_section(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_header_none' ) ) {
	function foxiz_render_header_none() { ?>
		<div id="site-header" class="header-none">
			<div class="navbar-outer">
				<div id="header-template-holder"><?php foxiz_header_mobile( foxiz_get_option() ); ?></div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_header_none_mobile' ) ) {
	function foxiz_render_header_none_mobile() { ?>
		<div id="site-header" class="header-none"></div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_header_ad_widget_section' ) ) {
	function foxiz_header_ad_widget_section() {

		if ( is_404() && ! foxiz_get_option( 'page_404_ads' ) ) {
			return;
		}

		if ( get_the_ID() ) {
			$disable_ad = rb_get_meta( 'disable_header_ad', get_the_ID() );
			if ( ! empty( $disable_ad ) && '-1' === (string) $disable_ad ) {
				return;
			}
		}

		if ( is_active_sidebar( 'foxiz_header_ad' ) ) {
			dynamic_sidebar( 'foxiz_header_ad' );
		}
	}
}