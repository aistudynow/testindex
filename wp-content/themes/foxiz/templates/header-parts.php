<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_render_header' ) ) {
	function foxiz_render_header() {

		foxiz_render_header_template();

		/** Start tracking queried posts after header */
		$GLOBALS['foxiz_queried_ids'] = [];
	}
}

if ( ! function_exists( 'foxiz_render_header_template' ) ) {

	function foxiz_render_header_template() {

		if ( foxiz_is_amp() ) {
			foxiz_render_header_amp();

			return;
		}

		if ( is_singular( 'web-story' ) ) {
			return;
		}

		$header = foxiz_get_header_style();
		if ( ! empty( $header['style'] ) && 'rb_template' === $header['style'] ) {
			foxiz_render_header_rb_template( $header['shortcode'] );

			return;
		}

		$func = 'foxiz_render_header_' . $header['style'];
		if ( function_exists( $func ) ) {
			call_user_func( $func );
		} else {
			foxiz_render_header_1();
		}
	}
}

if ( ! function_exists( 'foxiz_render_text_logo' ) ) {
	function foxiz_render_text_logo( $settings = [] ) {

		$blog_name  = get_bloginfo( 'name' );
		$class_name = 'logo-wrap is-text-logo site-branding';
		if ( ! empty( $settings['transparent'] ) ) {
			$class_name = ' is-logo-transparent';
		}
		?>
	<div class="<?php echo strip_tags( $class_name ); ?>">
		<?php if ( is_front_page() && ! isset( $GLOBALS['foxiz_h1_rendered'] ) ) :
			$GLOBALS['foxiz_h1_rendered'] = true; ?>
			<h1 class="logo-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo strip_tags( $blog_name ) ?>"><?php foxiz_render_inline_html( $blog_name ); ?></a>
			</h1>
		<?php else: ?>
			<p class="logo-title h1">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo strip_tags( $blog_name ) ?>"><?php foxiz_render_inline_html( $blog_name ); ?></a>
			</p>
		<?php endif;
		if ( get_bloginfo( 'description' ) ) : ?>
			<p class="site-description is-hidden"><?php bloginfo( 'description' ); ?></p>
		<?php endif; ?>
		</div><?php
	}
}

if ( ! function_exists( 'foxiz_get_logo_html' ) ) {
	function foxiz_get_logo_html( $logo, $retina_logo = false, $classes = 'logo-default', $mode = 'default', $loading = 'eager' ) {

		if ( empty( $logo['url'] ) ) {
			return false;
		}

		$output = '<img class="' . strip_tags( $classes ) . '"';
		if ( ! empty( $mode ) && 'disabled' !== $mode ) {
			$output .= ' data-mode="' . strip_tags( $mode ) . '"';
		}
		if ( ! empty( $logo['height'] ) ) {
			$output .= ' height="' . strip_tags( $logo['height'] ) . '"';
		}
		if ( ! empty( $logo['width'] ) ) {
			$output .= ' width="' . strip_tags( $logo['width'] ) . '"';
		}

		$output .= ' src="' . esc_url( $logo['url'] ) . '"';
		$output .= ' alt="' . get_bloginfo( 'name' ) . '"';

		if ( ! foxiz_is_amp() ) {
			$output .= ( $loading === 'eager' )
					? ' decoding="async" loading="eager" fetchpriority="high"'
					: ' decoding="async" loading="lazy"';
		}
		$output .= '>';
		return $output;
	}
}




if ( ! function_exists('foxiz_get_logo_picture') ) {
	function foxiz_get_logo_picture( $default, $dark = [], $classes = 'logo-picture', $loading = 'eager' ) {
		if ( empty( $default['url'] ) ) {
			return '';
		}

		$w = ! empty( $default['width'] )  ? (int) $default['width']  : 0;
		$h = ! empty( $default['height'] ) ? (int) $default['height'] : 0;

		$is_lazy = ( $loading !== 'eager' );

		// <img> gets the priority hints (not <source>)
		$img_attr  = ' alt="' . esc_attr( get_bloginfo('name') ) . '" decoding="async"';
		$img_attr .= $w ? ' width="' . esc_attr($w) . '"' : '';
		$img_attr .= $h ? ' height="' . esc_attr($h) . '"' : '';
		$img_attr .= $is_lazy ? ' loading="lazy" fetchpriority="low"' : ' loading="eager"';

		$out  = '<picture class="' . esc_attr($classes) . '">';

		// optional dark logo via prefers-color-scheme
		if ( ! empty( $dark['url'] ) ) {
			$out .= '<source media="(prefers-color-scheme: dark)" srcset="' . esc_url( $dark['url'] ) . '">';
		}

		// default (light) logo as the <img> fallback
		$out .= '<img src="' . esc_url( $default['url'] ) . '"' . $img_attr . '>';

		$out .= '</picture>';

		return $out;
	}
}





if ( ! function_exists( 'foxiz_render_logo' ) ) {
	function foxiz_render_logo( $settings = [] ) {

		if ( empty( $settings['logo']['url'] ) ) {
			foxiz_render_text_logo();

			return;
		}

		$blog_name = get_bloginfo( 'name' );
		$classes   = [];
		$classes[] = 'logo-wrap';
		if ( ! empty( $settings['classes'] ) ) {
			$classes[] = $settings['classes'];
		}
		$classes[] = 'is-image-logo site-branding';
		if ( foxiz_is_svg( $settings['logo']['url'] ) ) {
			$classes[] = 'is-logo-svg';
		}
		?>
		<div class="<?php echo implode( ' ', $classes ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo" title="<?php echo strip_tags( $blog_name ); ?>">
				<?php
				
				
				
				if ( empty( $settings['dark_logo']['url'] ) ) {
    $settings['dark_logo'] = $settings['logo'];
}

// One <picture> for light/dark; default is eager (no fetchpriority=high)
echo foxiz_get_logo_picture( $settings['logo'], $settings['dark_logo'], 'logo-picture desktop', 'eager' );

// Optional transparent variant (lazy/low), shown only in a transparent header state via CSS
if ( ! empty( $settings['transparent_logo']['url'] ) ) {
    echo foxiz_get_logo_picture( $settings['transparent_logo'], [], 'logo-picture logo-transparent desktop', 'lazy' );
}

				
				
				
				if ( is_front_page() && foxiz_get_option( 'front_page_h1' ) && empty( $settings['disable_info'] ) && ! isset( $GLOBALS['foxiz_h1_rendered'] ) ) :
					$GLOBALS['foxiz_h1_rendered'] = true; ?>
					<h1 class="logo-title is-hidden"><?php foxiz_render_inline_html( $blog_name ); ?></h1>
					<?php if ( get_bloginfo( 'description' ) ) : ?>
					<p class="site-description is-hidden"><?php bloginfo( 'description' ); ?></p>
				<?php endif;
				endif; ?>
			</a>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_mobile_logo' ) ) {
	function foxiz_render_mobile_logo( $settings = [] ) {

		$settings['disable_info']     = true;
		$settings['transparent_logo'] = [];

		if ( empty( $settings['mobile_logo']['url'] ) ) {
			$settings['classes'] = 'mobile-logo-wrap';
			foxiz_render_logo( $settings );

			return;
		}

		$blog_name    = get_bloginfo( 'name' );
		$class_name   = [];
		$class_name[] = 'mobile-logo-wrap is-image-logo site-branding';
		if ( foxiz_is_svg( $settings['mobile_logo']['url'] ) ) {
			$class_name[] = 'is-logo-svg';
		}
		?>
		<div class="<?php echo implode( ' ', $class_name ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo strip_tags( $blog_name ) ?>">
				<?php
				if ( empty( $settings['dark_mobile_logo']['url'] ) ) {
					if ( ! empty( $settings['dark_logo']['url'] ) ) {
						$settings['dark_mobile_logo'] = $settings['dark_logo'];
					} else {
						$settings['dark_mobile_logo'] = $settings['mobile_logo'];
					}
				}
				if ( empty( $settings['dark_mobile_logo']['url'] ) ) {
    $settings['dark_mobile_logo'] = ! empty( $settings['dark_logo']['url'] )
        ? $settings['dark_logo']
        : $settings['mobile_logo'];
}

// mobile version; default eager (no fetchpriority=high)
echo foxiz_get_logo_picture( $settings['mobile_logo'], $settings['dark_mobile_logo'], 'logo-picture mobile', 'eager' );

	
				?>
			</a>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_top_site' ) ) {
	/**
	 * render top site
	 */
	function foxiz_render_top_site() {

		do_action( 'foxiz_top_site' );
	}
}

if ( ! function_exists( 'foxiz_render_main_menu' ) ) {
	/**
	 * @param string $classes
	 * @param false $sub_scheme
	 */
	function foxiz_render_main_menu( $classes = '', $sub_scheme = false ) {

		$class_name = 'main-menu-wrap';
		if ( ! empty( $classes ) ) {
			$class_name .= ' ' . $classes;
		}

		$args = [
				'theme_location' => 'foxiz_main',
				'menu_id'        => false,
				'container'      => '',
				'menu_class'     => 'main-menu rb-menu large-menu',
				'walker'         => new Foxiz_Walker_Nav_Menu(),
				'depth'          => 0,
				'items_wrap'     => '<ul id="%1$s" class="%2$s" itemscope itemtype="' . foxiz_protocol() . '://www.schema.org/SiteNavigationElement">%3$s</ul>',
				'echo'           => true,
				'fallback_cb'    => 'foxiz_navigation_fallback',
				'fallback_name'  => esc_html__( 'Main Menu', 'foxiz' ),
		];

		if ( ! empty( $sub_scheme ) ) {
			$args['sub_scheme'] = 'light-scheme';
		}
		?>
		<nav id="site-navigation" class="<?php echo strip_tags( $class_name ); ?>" aria-label="<?php esc_attr_e( 'main menu', 'foxiz' ); ?>"><?php wp_nav_menu( $args ); ?></nav>
		<?php
	}
}

if ( ! function_exists( 'foxiz_render_nav_right' ) ) {
	/**
	 * @param $settings
	 */
	function foxiz_render_nav_right( $settings ) {

		if ( ! empty( $settings['header_login_icon'] ) ) {
			foxiz_header_user( $settings );
		}
		if ( ! empty( $settings['header_socials'] ) ) {
			foxiz_header_socials( $settings );
		}
		foxiz_header_mini_cart();
		if ( ! empty( $settings['header_notification'] ) ) {
			foxiz_header_notification( $settings );
		}
		if ( ! empty( $settings['header_search_icon'] ) ) {
			foxiz_header_search( $settings );
		}
		if ( ! empty( $settings['single_font_resizer'] ) ) {
			foxiz_header_font_resizer();
		}
		foxiz_dark_mode_switcher();
	}
}

if ( ! function_exists( 'foxiz_header_user' ) ) {
	function foxiz_header_user( $settings = [] ) {

		$login_redirect  = foxiz_get_option( 'login_redirect' );
		$logout_redirect = foxiz_get_option( 'logout_redirect' );
		$icon            = foxiz_get_option( 'login_custom_icon' );
		$login_label     = ! empty( $settings['label_text'] ) ? $settings['label_text'] : foxiz_html__( 'Sign In', 'foxiz' );

		if ( ! isset( $settings['header_login_menu'] ) ) {
			$settings['header_login_menu'] = foxiz_get_option( 'header_login_menu' );
		}
		if ( ! isset( $settings['logged_gravatar'] ) ) {
			$settings['logged_gravatar'] = foxiz_get_option( 'logged_gravatar' );
		}
		if ( empty( $login_redirect ) ) {
			$login_redirect = foxiz_get_current_permalink();
		}
		if ( empty( $logout_redirect ) ) {
			$logout_redirect = foxiz_get_current_permalink();
		}
		if ( empty( $settings['login_icon'] ) && ! empty( $icon['url'] ) ) {
			$settings['login_icon'] = $icon['url'];
		} ?>
		<div class="wnav-holder widget-h-login header-dropdown-outer">
			<?php if ( is_user_logged_in() && ! is_admin() ) :
				global $current_user; ?>
				<a class="dropdown-trigger is-logged header-element" href="#" rel="nofollow" role="button" aria-label="<?php foxiz_html_e( 'Toggle user account menu', 'foxiz' ); ?>">
					<?php if ( ! empty( $settings['logged_gravatar'] ) ) : ?>
						<span class="logged-avatar"><?php
							$author_image_id = (int) get_the_author_meta( 'author_image_id', $current_user->ID );
							if ( $author_image_id !== 0 ) {
								echo foxiz_get_avatar_by_attachment( $author_image_id, 'thumbnail', false );
							} else {
								echo get_avatar( $current_user->ID, 60 );
							}
							?></span>
					<?php endif; ?>
					<span class="logged-welcome"><?php echo foxiz_html__( 'Hi,', 'foxiz' ) . '<strong>' . foxiz_strip_tags( $current_user->display_name ) . '</strong>'; ?></span>
				</a>
				<div class="header-dropdown user-dropdown">
					<?php if ( ! empty( $settings['header_login_menu'] ) ) {
						wp_nav_menu( [
								'menu'        => $settings['header_login_menu'],
								'menu_class'  => 'logged-user-menu',
								'menu_id'     => false,
								'container'   => false,
								'depth'       => 1,
								'echo'        => true,
								'fallback_cb' => '__return_false',
						] );
					}
					?>
					<div class="logout-wrap">
						<a class="logout-url" href="<?php echo wp_logout_url( $logout_redirect ); ?>" rel="nofollow"><?php echo foxiz_html__( 'Sign Out', 'foxiz' ); ?>
							<i class="rbi rbi-logout"></i></a>
					</div>
				</div>
			<?php else :
				if ( empty( $settings['header_login_layout'] ) ) : ?>
					<a href="<?php echo wp_login_url( $login_redirect ); ?>" class="login-toggle is-login header-element" data-title="<?php echo esc_attr( $login_label ); ?>"<?php if ( ! foxiz_get_option( 'disable_login_popup' ) ) {
						echo ' role="button"';
					} ?> rel="nofollow" aria-label="<?php echo esc_attr( $login_label ); ?>"><?php
						if ( ! empty( $settings['login_icon'] ) ) {
							echo '<span class="login-icon-svg"></span>';
						} else {
							echo '<i class="rbi rbi-user wnav-icon"></i>';
						} ?></a>
				<?php elseif ( '1' === $settings['header_login_layout'] ) : ?>
					<a href="<?php echo wp_login_url( $login_redirect ); ?>" class="login-toggle is-login is-btn header-element" rel="nofollow"<?php if ( ! foxiz_get_option( 'disable_login_popup' ) ) {
						echo ' role="button"';
					} ?> aria-label="<?php echo esc_attr( $login_label ); ?>"><span><?php foxiz_render_inline_html( $login_label ); ?></span></a>
				<?php else : ?>
					<a href="<?php echo wp_login_url( $login_redirect ); ?>" class="login-toggle is-login is-btn is-btn-icon header-element" rel="nofollow"<?php if ( ! foxiz_get_option( 'disable_login_popup' ) ) {
						echo ' role="button"';
					} ?> aria-label="<?php echo esc_attr( $login_label ); ?>"><?php
						if ( ! empty( $settings['login_icon'] ) ) {
							echo '<span class="login-icon-svg"></span>';
						} else {
							echo '<i class="rbi rbi-user wnav-icon"></i>';
						} ?><span><?php foxiz_render_inline_html( $login_label ); ?></span></a>
				<?php endif;
			endif; ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_header_search' ) ) {
	function foxiz_header_search( $settings = [] ) {

		$classes       = [ 'icon-holder header-element search-btn' ];
		$form_settings = [
				'placeholder'  => '',
				'icon'         => [],
				'ajax_search'  => false,
				'color_scheme' => '',
		];

		if ( ! isset( $settings['ajax_search'] ) ) {
			$form_settings['ajax_search'] = foxiz_get_option( 'ajax_search' );
		} elseif ( ! empty( $settings['ajax_search'] ) ) {
			$form_settings['ajax_search'] = true;
		}
		if ( ! empty( $settings['limit'] ) ) {
			$form_settings['limit'] = $settings['limit'];
		}
		if ( ! empty( $settings['header_search_custom_icon']['url'] ) ) {
			$form_settings['icon']['url'] = $settings['header_search_custom_icon']['url'];
		}
		if ( ! empty( $settings['header_search_placeholder'] ) ) {
			$form_settings['placeholder'] = $settings['header_search_placeholder'];
		}

		if ( empty( $settings['header_search_mode'] ) || 'search' === $settings['header_search_mode'] ) {
			$classes[] = 'search-trigger';
		} else {
			$classes[] = 'more-trigger';
		}
		if ( ! empty( $settings['search_label'] ) ) {
			$classes[] = 'has-label';
		}
		if ( isset( $settings['header_search_scheme'] ) ) {
			$settings['sub_scheme'] = $settings['header_search_scheme'];
		}
		if ( ! empty( $settings['sub_scheme'] ) ) {
			$form_settings['color_scheme'] = true;
		}
		if ( ! empty( $settings['post_type'] ) ) {
			$form_settings['post_type'] = $settings['post_type'];
		} elseif ( isset( $_GET['post_type'] ) ) {
			$form_settings['post_type'] = sanitize_text_field( $_GET['post_type'] );
		}
		?>
		<div class="wnav-holder w-header-search header-dropdown-outer">
			<a href="#" role="button" data-title="<?php foxiz_html_e( 'Search', 'foxiz' ); ?>" class="<?php echo strip_tags( join( ' ', $classes ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'foxiz' ); ?>">
				<?php if ( ! empty( $form_settings['icon']['url'] ) ) {
					echo '<span class="search-icon-svg"></span>';
				} else {
					echo '<i class="rbi rbi-search wnav-icon" aria-hidden="true"></i>';
				} ?>
				<?php if ( ! empty( $settings['search_label'] ) ) : ?>
					<span class="header-search-label meta-text"><?php echo strip_tags( $settings['search_label'] ); ?></span>
				<?php endif; ?>
			</a>
			<?php if ( empty( $settings['header_search_mode'] ) || 'search' === $settings['header_search_mode'] ) : ?>
				<div class="header-dropdown">
					<div class="header-search-form is-icon-layout">
						<?php foxiz_search_form( $form_settings ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_header_search_form' ) ) {
	function foxiz_header_search_form( $settings = [] ) {

		$class_name = 'header-search-form is-form-layout';

		$form_settings = [
				'placeholder'  => '',
				'icon'         => [],
				'ajax_search'  => false,
				'color_scheme' => '',
		];

		if ( ! empty( $settings['header_search_custom_icon']['url'] ) ) {
			$form_settings['icon']['url'] = $settings['header_search_custom_icon']['url'];
		}
		if ( ! empty( $settings['limit'] ) ) {
			$form_settings['limit'] = $settings['limit'];
		}
		if ( ! empty( $settings['taxonomies'] ) ) {
			$form_settings['taxonomies'] = $settings['taxonomies'];
		}
		if ( ! empty( $settings['search_type'] ) ) {
			$form_settings['search_type'] = $settings['search_type'];
			$class_name                   .= ' is-search-' . $settings['search_type'];
		}
		if ( ! empty( $settings['header_search_placeholder'] ) ) {
			$form_settings['placeholder'] = $settings['header_search_placeholder'];
		}
		if ( ! empty( $settings['header_search_style'] ) ) {
			$class_name .= ' search-form-' . $settings['header_search_style'];
		}
		if ( ! empty( $settings['ajax_search'] ) ) {
			$form_settings['ajax_search'] = true;
		}
		if ( ! empty( $settings['follow'] ) && '1' === (string) $settings['follow'] ) {
			$form_settings['follow'] = true;
		}
		if ( isset( $settings['header_search_scheme'] ) ) {
			$settings['sub_scheme'] = $settings['header_search_scheme'];
		}
		if ( ! empty( $settings['sub_scheme'] ) ) {
			$form_settings['color_scheme'] = true;
		}
		if ( isset( $settings['desc_source'] ) ) {
			$form_settings['desc_source'] = $settings['desc_source'];
		}
		if ( ! empty( $settings['search_type'] ) && 'category' === $settings['search_type'] ) {
			$form_settings['no_submit'] = true;
		}
		if ( ! empty( $settings['post_type'] ) ) {
			$form_settings['post_type'] = $settings['post_type'];
		} elseif ( isset( $_GET['post_type'] ) ) {
			$form_settings['post_type'] = sanitize_text_field( $_GET['post_type'] );
		}
		?>
		<div class="<?php echo strip_tags( $class_name ); ?>">
			<?php if ( ! empty( $settings['header_search_heading'] ) ) : ?>
				<span class="h5"><?php foxiz_render_inline_html( $settings['header_search_heading'] ); ?></span>
			<?php endif;
			foxiz_search_form( $form_settings );
			?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_burger_icon' ) ) {
	function foxiz_burger_icon() { ?>
		<span class="burger-icon"><span></span><span></span><span></span></span>
	<?php }
}

if ( ! function_exists( 'foxiz_header_more' ) ) {
	function foxiz_header_more( $settings ) {

		if ( empty( $settings['more'] ) ) {
			return false;
		} ?>
		<div class="more-section-outer menu-has-child-flex menu-has-child-mega-columns layout-col-<?php echo foxiz_get_option( 'more_column', 2 ); ?>">
			<a class="more-trigger icon-holder" href="#" rel="nofollow" role="button" data-title="<?php foxiz_html_e( 'More', 'foxiz' ); ?>" aria-label="<?php esc_attr_e( 'more', 'foxiz' ); ?>">
				<span class="dots-icon"><span></span><span></span><span></span></span> </a>
			<div id="rb-more" class="more-section flex-dropdown">
				<div class="more-section-inner">
					<div class="more-content">
						<?php if ( ! empty( $settings['more_search'] ) ) {
							foxiz_header_search_form( $settings );
						}
						if ( is_active_sidebar( 'foxiz_sidebar_more' ) ) : ?>
							<div class="mega-columns">
								<?php dynamic_sidebar( 'foxiz_sidebar_more' ); ?>
							</div>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $settings['more_footer_menu'] ) || ! empty( $settings['more_footer_copyright'] ) ) : ?>
						<div class="collapse-footer">
							<?php if ( ! empty( $settings['more_footer_menu'] ) ) : ?>
								<div class="collapse-footer-menu"><?php
									wp_nav_menu( [
											'menu'        => $settings['more_footer_menu'],
											'menu_id'     => false,
											'container'   => false,
											'menu_class'  => 'collapse-footer-menu-inner',
											'depth'       => 1,
											'echo'        => true,
											'fallback_cb' => '__return_false',
									] );
									?></div>
							<?php endif;
							if ( ! empty( $settings['more_footer_copyright'] ) ) : ?>
								<div class="collapse-copyright"><?php foxiz_render_inline_html( str_replace( '{year}', date( 'Y' ), $settings['more_footer_copyright'] ) ); ?></div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_header_mobile' ) ) {
	function foxiz_header_mobile( $settings = [] ) {

		$layout = '';

		if ( is_singular() ) {
			$page_mh_template = trim( rb_get_meta( 'mh_template' ) );
			if ( ! empty( $page_mh_template ) ) {
				$settings['mh_template'] = $page_mh_template;
			}
		}
		if ( ! empty( $settings['mh_template'] ) && ! foxiz_is_amp() ) {
			$layout = 'template';
		} elseif ( ! empty( $settings['mh_layout'] ) ) {
			$layout = $settings['mh_layout'];
		} ?>
		<div id="header-mobile" class="header-mobile mh-style-<?php echo foxiz_get_option( 'mh_divider', 'shadow' ); ?>">
			<div class="header-mobile-wrap">
				<?php
				switch ( $layout ) {
					case '1' :
						foxiz_header_mobile_layout_center( $settings );
						break;
					case '2' :
						foxiz_header_mobile_layout_left_logo( $settings );
						break;
					case '3':
						foxiz_header_mobile_layout_top_logo( $settings );
						break;
					case 'template' :
						echo do_shortcode( trim( $settings['mh_template'] ) );
						break;
					default:
						foxiz_header_mobile_layout_default( $settings );
				}
				echo foxiz_get_mobile_quick_access(); ?>
			</div>
			<?php foxiz_mobile_collapse( $settings ); ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_get_mobile_quick_access' ) ) {
	function foxiz_get_mobile_quick_access() {

		return wp_nav_menu( [
				'theme_location'  => 'foxiz_mobile_quick',
				'container_class' => 'mobile-qview',
				'menu_class'      => 'mobile-qview-inner',
				'depth'           => 1,
				'echo'            => false,
				'fallback_cb'     => '__return_false',
		] );
	}
}

if ( ! function_exists( 'foxiz_header_mobile_layout_default' ) ) {
	function foxiz_header_mobile_layout_default( $settings = [] ) { ?>
		<div class="mbnav edge-padding">
			<div class="navbar-left">
				<?php
				foxiz_mobile_toggle_btn();
				foxiz_render_mobile_logo( $settings );
				?>
			</div>
			<div class="navbar-right">
				<?php
				foxiz_mobile_header_mini_cart();
				foxiz_mobile_search_icon();
				if ( ! empty( $settings['single_font_resizer'] ) ) {
					foxiz_header_font_resizer();
				}
				foxiz_dark_mode_switcher(); ?>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_header_mobile_layout_center' ) ) {
	function foxiz_header_mobile_layout_center( $settings = [] ) { ?>
		<div class="mbnav mbnav-center edge-padding">
			<div class="navbar-left">
				<?php
				foxiz_mobile_toggle_btn();
				if ( ! empty( $settings['single_font_resizer'] ) ) {
					foxiz_header_font_resizer();
				} ?>
			</div>
			<div class="navbar-center">
				<?php foxiz_render_mobile_logo( $settings ); ?>
			</div>
			<div class="navbar-right">
				<?php
				foxiz_mobile_header_mini_cart();
				foxiz_mobile_search_icon();
				foxiz_dark_mode_switcher(); ?>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_header_mobile_layout_left_logo' ) ) {
	function foxiz_header_mobile_layout_left_logo( $settings = [] ) { ?>
		<div class="mbnav edge-padding">
			<div class="navbar-left">
				<?php foxiz_render_mobile_logo( $settings ); ?>
			</div>
			<div class="navbar-right">
				<?php
				foxiz_mobile_header_mini_cart();
				foxiz_mobile_search_icon();
				if ( ! empty( $settings['single_font_resizer'] ) ) {
					foxiz_header_font_resizer();
				}
				foxiz_dark_mode_switcher();
				foxiz_mobile_toggle_btn();
				?>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_header_mobile_layout_top_logo' ) ) {
	function foxiz_header_mobile_layout_top_logo( $settings = [] ) { ?>
		<div class="mbnav is-top-logo edge-padding mh-top-style-<?php echo foxiz_get_option( 'mh_top_divider', 'gray' ); ?>">
			<div class="mlogo-top">
				<?php foxiz_render_mobile_logo( $settings ); ?>
			</div>
			<div class="navbar-left">
				<?php foxiz_mobile_toggle_btn(); ?>
			</div>
			<div class="navbar-right">
				<?php
				foxiz_mobile_header_mini_cart();
				foxiz_mobile_search_icon();
				if ( ! empty( $settings['single_font_resizer'] ) ) {
					foxiz_header_font_resizer();
				}
				foxiz_dark_mode_switcher();
				?>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_mobile_toggle_btn' ) ) {
	function foxiz_mobile_toggle_btn() { ?>
		<div class="mobile-toggle-wrap">
			<?php if ( ! foxiz_is_amp() ) : ?>
				<a href="#" class="mobile-menu-trigger" role="button" rel="nofollow" aria-label="<?php esc_attr_e( 'Open mobile menu', 'foxiz' ); ?>"><?php foxiz_burger_icon(); ?></a>
			<?php else : ?>
				<span class="mobile-menu-trigger" on="tap:AMP.setState({collapse: !collapse})"><?php foxiz_burger_icon(); ?></span>
			<?php endif; ?>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_mobile_collapse' ) ) {
	function foxiz_mobile_collapse( $settings = [] ) {

		if ( ! empty( $settings['collapse_template'] ) && ! empty( $settings['collapse_template_display'] ) && 'replace' === $settings['collapse_template_display'] && ! foxiz_is_amp() ) {
			echo '<div class="mobile-collapse is-collapse-template"><div class="collapse-holder">' . do_shortcode( trim( $settings['collapse_template'] ) ) . '</div></div>';

			return;
		}

		$settings['ajax_search'] = false;
		$login_redirect          = foxiz_get_option( 'login_redirect' );
		if ( empty( $login_redirect ) ) {
			$login_redirect = foxiz_get_current_permalink();
		}
		$login_redirect = esc_url( $login_redirect );
		?>
		<div class="mobile-collapse">
			<div class="collapse-holder">
				<div class="collapse-inner">
					<?php if ( foxiz_get_option( 'mobile_search_form' ) ) : ?>
						<div class="mobile-search-form edge-padding"><?php foxiz_header_search_form( $settings ); ?></div>
					<?php endif; ?>
					<nav class="mobile-menu-wrap edge-padding">
						<?php wp_nav_menu( [
								'theme_location' => 'foxiz_mobile',
								'menu_id'        => 'mobile-menu',
								'menu_class'     => 'mobile-menu',
								'container'      => false,
								'depth'          => 2,
								'echo'           => true,
								'fallback_cb'    => 'foxiz_navigation_fallback',
								'fallback_name'  => esc_html__( 'Mobile Menu', 'foxiz' ),
						] ); ?>
					</nav>
					<?php if ( ! empty( $settings['collapse_template'] ) && ( empty( $settings['collapse_template_display'] ) || 'replace' !== $settings['collapse_template_display'] ) && ! foxiz_is_amp() ) {
						echo '<div class="collapse-template">' . do_shortcode( trim( $settings['collapse_template'] ) ) . '</div>';
					} ?>
					<div class="collapse-sections">
						<?php if ( ! empty( $settings['mobile_login'] ) && ! foxiz_is_amp() ) : ?>
							<div class="mobile-login">
								<?php if ( ! is_user_logged_in() ) : ?>
									<span class="mobile-login-title h6"><?php
										if ( foxiz_get_option( 'mobile_login_label' ) ) {
											foxiz_render_inline_html( foxiz_get_option( 'mobile_login_label' ) );
										} else {
											foxiz_html_e( 'Have an existing account?', 'foxiz' );
										} ?></span>
									<a href="<?php echo wp_login_url( $login_redirect ); ?>" class="login-toggle is-login is-btn" rel="nofollow"><?php foxiz_html_e( 'Sign In', 'foxiz' ); ?></a>
								<?php else :
									global $current_user;
									$logout_redirect = foxiz_get_option( 'logout_redirect' );
									if ( empty( $logout_redirect ) ) {
										$logout_redirect = foxiz_get_current_permalink();
									}
									?>
									<span class="mobile-login-title"><?php echo foxiz_html__( 'Hi,', 'foxiz' ) . '<strong>' . foxiz_strip_tags( $current_user->display_name ) . '</strong>'; ?></span>
									<a class="mobile-logout-btn is-btn" href="<?php echo wp_logout_url( $logout_redirect ); ?>" rel="nofollow"><?php echo foxiz_html__( 'Sign Out', 'foxiz' ); ?></a>
								<?php endif; ?>
							</div>
						<?php endif;

						if ( foxiz_get_option( 'header_login_menu_mobile' ) && is_user_logged_in() ) {
							$logged_menu = foxiz_get_option( 'header_login_menu' );
							if ( ! empty( $logged_menu ) ) {
								wp_nav_menu( [
										'menu'        => $logged_menu,
										'menu_class'  => 'logged-mobile-menu',
										'menu_id'     => false,
										'container'   => false,
										'depth'       => 1,
										'echo'        => true,
										'fallback_cb' => '__return_false',
								] );
							}
						}

						if ( ! empty( $settings['mobile_social'] ) ) : ?>
							<div class="mobile-socials">
								<span class="mobile-social-title h6"><?php foxiz_html_e( 'Follow US', 'foxiz' ); ?></span>
								<?php echo foxiz_get_social_list( $settings ); ?>
							</div>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $settings['mobile_footer_menu'] ) || ! empty( $settings['mobile_copyright'] ) ) : ?>
						<div class="collapse-footer">
							<?php if ( ! empty( $settings['mobile_footer_menu'] ) ) : ?>
								<div class="collapse-footer-menu"><?php
									wp_nav_menu( [
											'menu'        => $settings['mobile_footer_menu'],
											'menu_id'     => false,
											'container'   => false,
											'menu_class'  => 'collapse-footer-menu-inner',
											'depth'       => 1,
											'echo'        => true,
											'fallback_cb' => '__return_false',
									] );
									?></div>
							<?php endif;
							if ( ! empty( $settings['mobile_copyright'] ) ) : ?>
								<div class="collapse-copyright"><?php foxiz_render_inline_html( str_replace( '{year}', date( 'Y' ), $settings['mobile_copyright'] ) ); ?></div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_dark_mode_switcher' ) ) {
	function foxiz_dark_mode_switcher() {

		if ( foxiz_is_amp() ) {
			return;
		}

		if ( '1' !== (string) foxiz_get_option( 'dark_mode' ) ) {
			if ( is_admin() ) {
				echo '<span class="rb-admin-info">' . esc_html__( 'Please enable the dark mode in the Theme Options.', 'foxiz' ) . '</div>';
			}

			return;
		} ?>
		<div class="dark-mode-toggle-wrap">
			<div class="dark-mode-toggle">
                <span class="dark-mode-slide">
                    <i class="dark-mode-slide-btn mode-icon-dark" data-title="<?php foxiz_html_e( 'Switch to Light', 'foxiz' ); ?>"><?php echo foxiz_get_switcher_icon( 'dark' ); ?></i>
                    <i class="dark-mode-slide-btn mode-icon-default" data-title="<?php foxiz_html_e( 'Switch to Dark', 'foxiz' ); ?>"><?php echo foxiz_get_switcher_icon(); ?></i>
                </span>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_get_switcher_icon' ) ) {
	function foxiz_get_switcher_icon( $mode = 'light' ) {

		$option_key = ( 'dark' === $mode ) ? 'dark_mode_dark_icon' : 'dark_mode_light_icon';
		$icon       = foxiz_get_option( $option_key );

		if ( ! empty( $icon['id'] ) ) {
			$content = foxiz_get_svg_content( $icon['id'] );
		}

		return ! empty( $content ) ? $content : foxiz_get_svg( 'mode-' . $mode );
	}
}

if ( ! function_exists( 'foxiz_header_notification' ) ) {
	function foxiz_header_notification( $settings = [] ) {

		if ( foxiz_is_amp() ) {
			return false;
		}

		$class_name = 'notification-content';
		$icon       = foxiz_get_option( 'notification_custom_icon' );
		if ( isset( $settings['header_notification_scheme'] ) ) {
			$settings['sub_scheme'] = $settings['header_notification_scheme'];
		}
		if ( ! empty( $settings['sub_scheme'] ) ) {
			$class_name .= ' light-scheme';
		}
		?>
		<div class="wnav-holder header-dropdown-outer">
			<div class="dropdown-trigger notification-icon notification-trigger">
          <span class="notification-icon-inner" data-title="<?php foxiz_html_e( 'Notification', 'foxiz' ); ?>">
              <span class="notification-icon-holder">
              <?php if ( ! empty( $icon['url'] ) ) : ?>
	              <span class="notification-icon-svg"></span>
              <?php else : ?>
	              <i class="rbi rbi-notification wnav-icon" aria-hidden="true"></i>
              <?php endif; ?>
              <span class="notification-info"></span>
              </span>
          </span>
			</div>
			<div class="header-dropdown notification-dropdown">
				<div class="notification-popup">
					<div class="notification-header">
						<span class="h4"><?php foxiz_html_e( 'Notification', 'foxiz' ); ?></span>
						<?php if ( ! empty( $settings['header_notification_url'] ) ) : ?>
							<a class="notification-url meta-text" href="<?php echo esc_url( $settings['header_notification_url'] ); ?>"><?php foxiz_html_e( 'Show More', 'foxiz' ); ?>
								<i class="rbi rbi-cright" aria-hidden="true"></i></a>
						<?php endif; ?>
					</div>
					<div class="<?php echo strip_tags( $class_name ); ?>">
						<div class="scroll-holder">
							<div class="rb-notification ecat-l-dot is-feat-right" data-interval="<?php echo foxiz_get_option( 'notification_reload', 6 ); ?>"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'foxiz_header_font_resizer' ) ) {
	function foxiz_header_font_resizer() {

		if ( ! is_single() || foxiz_is_amp() ) {
			return;
		} ?>
		<div class="wnav-holder font-resizer">
			<a href="#" role="button" class="font-resizer-trigger" data-title="<?php foxiz_html_e( 'Font Resizer', 'foxiz' ) ?>"><span class="screen-reader-text"><?php foxiz_html_e( 'Font Resizer', 'foxiz' ) ?></span><strong><?php echo foxiz_html__( 'Aa', 'foxiz' ) ?></strong></a>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_header_alert' ) ) {
	function foxiz_header_alert( $settings ) {

		$alert_bar = rb_get_meta( 'alert_bar', get_the_ID() );

		if ( ! empty( $alert_bar ) && '-1' === (string) $alert_bar ) {
			return;
		}

		if ( ! empty( $alert_bar ) && '1' === (string) $alert_bar ) {
			echo foxiz_get_header_alert( $settings );

			return;
		}

		if ( empty( $settings['alert_bar'] ) || ( ! empty( $settings['alert_home'] ) && ! is_front_page() ) ) {
			return;
		}

		echo foxiz_get_header_alert( $settings );
	}
}

if ( ! function_exists( 'foxiz_get_header_alert' ) ) {
	function foxiz_get_header_alert( $settings ) {

		if ( empty( $settings['alert_content'] ) || empty( $settings['alert_url'] ) ) {
			return false;
		}

		$class_name = 'header-alert edge-padding';
		if ( ! empty( $settings['alert_sticky_hide'] ) ) {
			$class_name .= ' is-sticky-hide';
		}
		$output = '<a id="header-alert" class="' . strip_tags( $class_name ) . '" href="' . esc_url( $settings['alert_url'] ) . '" target="_blank" rel="nofollow noopener">';
		$output .= foxiz_strip_tags( $settings['alert_content'] );
		$output .= '</a>';

		return $output;
	}
}

if ( ! function_exists( 'foxiz_top_ad' ) ) {
	/**
	 * @return false|void
	 */
	function foxiz_top_ad() {

		if ( ! foxiz_get_option( 'ad_top_code' ) && ! foxiz_get_option( 'ad_top_image' ) ) {
			return;
		}

		if ( get_the_ID() ) {
			$disable_top_ad = rb_get_meta( 'disable_top_ad', get_the_ID() );
			if ( ! empty( $disable_top_ad ) && '-1' === (string) $disable_top_ad ) {
				return;
			}
		}

		$classes = 'top-site-ad';

		if ( foxiz_get_option( 'ad_top_animation' ) && ! foxiz_is_amp() ) {
			$classes .= ' yes-animation';
		}

		if ( foxiz_get_option( 'ad_top_spacing' ) ) {
			$classes .= ' no-spacing';
		}

		if ( foxiz_get_option( 'ad_top_type' ) && ! foxiz_is_amp() ) {
			$settings = [
					'code'         => foxiz_get_option( 'ad_top_code' ),
					'size'         => foxiz_get_option( 'ad_top_size' ),
					'desktop_size' => foxiz_get_option( 'ad_top_desktop_size' ),
					'tablet_size'  => foxiz_get_option( 'ad_top_tablet_size' ),
					'mobile_size'  => foxiz_get_option( 'ad_top_mobile_size' ),
			];
			if ( foxiz_get_adsense( $settings ) ) {
				$classes .= ' is-code';
				echo '<div class="' . strip_tags( $classes ) . '">' . foxiz_get_adsense( $settings ) . '</div>';
			}
		} else {
			$settings = [
					'image'         => foxiz_get_option( 'ad_top_image' ),
					'dark_image'    => foxiz_get_option( 'ad_top_dark_image' ),
					'destination'   => foxiz_get_option( 'ad_top_destination' ),
					'description'   => foxiz_get_option( 'ad_top_description' ),
					'feat_lazyload' => 'none',
			];
			if ( foxiz_get_ad_image( $settings ) ) {
				$classes .= ' is-image';
				echo '<div class="' . strip_tags( $classes ) . '">' . foxiz_get_ad_image( $settings ) . '</div>';
			}
		}
	}
}

if ( ! function_exists( 'foxiz_header_socials' ) ) {
	function foxiz_header_socials( $settings = [] ) {

		if ( ! empty( $settings['header_socials'] ) ) :
			?>
			<div class="header-social-list wnav-holder"><?php echo foxiz_get_social_list( $settings ); ?></div>
		<?php endif;
	}
}

if ( ! function_exists( 'foxiz_header_mini_cart' ) ) {
	function foxiz_header_mini_cart() {

		if ( ! foxiz_get_option( 'wc_mini_cart' ) ) {
			return;
		}

		foxiz_header_mini_cart_html();
	}
}

if ( ! function_exists( 'foxiz_mobile_header_mini_cart' ) ) {
	function foxiz_mobile_header_mini_cart() {

		if ( ! foxiz_get_option( 'wc_mobile_mini_cart' ) ) {
			return;
		}

		foxiz_header_mini_cart_html( false );
	}
}

if ( ! function_exists( 'foxiz_header_mini_cart_html' ) ) {
	function foxiz_header_mini_cart_html( $dropdown_section = true ) {

		if ( ! class_exists( 'Woocommerce' ) || foxiz_is_amp() ) {
			return;
		}

		$class_name = 'cart-link';
		$cart_icon  = foxiz_get_option( 'cart_custom_icon' );
		if ( ! empty( $dropdown_section ) ) {
			$class_name .= ' dropdown-trigger';
		}

		$cart = WC()->cart;
		?>
		<aside class="header-mini-cart wnav-holder header-dropdown-outer">
			<a class="<?php echo strip_tags( $class_name ); ?>" href="<?php echo esc_url( wc_get_cart_url() ) ?>" data-title="<?php foxiz_attr_e( 'View Cart', 'foxiz' ); ?>" aria-label="<?php foxiz_attr_e( 'View Cart', 'foxiz' ); ?>">
                <span class="cart-icon"><?php
	                if ( ! empty( $cart_icon['url'] ) ) : ?><span class="cart-icon-svg"></span><?php
	                else: ?><i class="wnav-icon rbi rbi-cart" aria-hidden="true"></i><?php
	                endif;
	                if ( foxiz_get_option( 'cart_counter' ) ) : ?>
		                <span class="cart-counter"><?php
			                if ( ! $cart || ! $cart instanceof \WC_Cart ) {
				                echo '0';
			                } else {
				                foxiz_render_inline_html( $cart->get_cart_contents_count() );
			                } ?></span>
	                <?php endif; ?></span>
				<?php if ( foxiz_get_option( 'total_amount' ) ) : ?>
					<span class="total-amount"><?php
						if ( ! $cart || ! $cart instanceof \WC_Cart ) {
							echo '0';
						} else {
							echo WC()->cart->get_cart_subtotal();
						} ?></span>
				<?php endif; ?>
			</a>
			<?php if ( $dropdown_section && ! is_admin() ): ?>
				<div class="header-dropdown mini-cart-dropdown">
					<div class="mini-cart-wrap woocommerce">
						<div class="widget_shopping_cart_content">
							<?php woocommerce_mini_cart(); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</aside>
	<?php }
}

if ( ! function_exists( 'foxiz_get_search_icon_svg' ) ) {
	function foxiz_get_search_icon_svg() {

		$icon = foxiz_get_option( 'header_search_custom_icon' );
		if ( ! empty( $icon['url'] ) ) {
			return '<span class="search-icon-svg"></span>';
		} else {
			return '<i class="rbi rbi-search wnav-icon" aria-hidden="true"></i>';
		}
	}
}

if ( ! function_exists( 'foxiz_mobile_search_icon' ) ) {
	function foxiz_mobile_search_icon() {
		if ( foxiz_is_amp() && foxiz_get_option( 'mobile_amp_search' ) ) : ?>
			<span class="mobile-menu-trigger mobile-search-icon" on="tap:AMP.setState({collapse: !collapse})"><?php echo foxiz_get_search_icon_svg(); ?></span>
		<?php elseif ( foxiz_get_option( 'mobile_search' ) ) : ?>
			<a role="button" href="#" class="mobile-menu-trigger mobile-search-icon" aria-label="<?php esc_attr_e( 'search', 'foxiz' ); ?>"><?php echo foxiz_get_search_icon_svg(); ?></a>
		<?php endif;
	}
}