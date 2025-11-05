<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

define( 'FOXIZ_THEME_VERSION', '2.6.9' );
define( 'FOXIZ_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'FOXIZ_THEME_URI', trailingslashit( esc_url( get_template_directory_uri() ) ) );
define( 'FOXIZ_CHILD_THEME_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'FOXIZ_CHILD_THEME_URI', trailingslashit( esc_url( get_stylesheet_directory_uri() ) ) );
defined( 'FOXIZ_TOS_ID' ) || define( 'FOXIZ_TOS_ID', 'foxiz_theme_options' );

include_once FOXIZ_THEME_DIR . 'includes/core-functions.php';
include_once FOXIZ_THEME_DIR . 'includes/file.php';

add_action( 'after_setup_theme', 'foxiz_theme_setup', 10 );
add_action( 'wp_enqueue_scripts', 'foxiz_register_script_frontend', 990 );

/** setup */
if ( ! function_exists( 'foxiz_theme_setup' ) ) {
	function foxiz_theme_setup() {

		// load_theme_textdomain
		$locale          = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$loco_path       = WP_LANG_DIR . '/loco/themes/foxiz-' . $locale . '.mo';
		$theme_lang_path = WP_LANG_DIR . '/themes/foxiz-' . $locale . '.mo';
		if ( is_readable( $loco_path ) ) {
			load_textdomain( 'foxiz', $loco_path );
		} elseif ( file_exists( $theme_lang_path ) ) {
			load_textdomain( 'foxiz', $theme_lang_path );
		} else {
			load_theme_textdomain( 'foxiz', get_theme_file_path( 'languages' ) );
		}

		if ( ! isset( $GLOBALS['content_width'] ) ) {
			$GLOBALS['content_width'] = 1170;
		}

		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'html5', [
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'script',
			'style',
		] );
		add_theme_support( 'post-formats', [ 'gallery', 'video', 'audio' ] );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'woocommerce', [
			'gallery_thumbnail_image_width' => 110,
			'thumbnail_image_width'         => 300,
			'single_image_width'            => 760,
		] );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		if ( ! foxiz_get_option( 'widget_block_editor' ) ) {
			remove_theme_support( 'widgets-block-editor' );
		}
		register_nav_menus( [
			'foxiz_main'         => esc_html__( 'Main Menu', 'foxiz' ),
			'foxiz_mobile'       => esc_html__( 'Mobile Menu', 'foxiz' ),
			'foxiz_mobile_quick' => esc_html__( 'Mobile Quick Access', 'foxiz' ),
		] );

		$sizes = foxiz_calc_crop_sizes();
		foreach ( $sizes as $crop_id => $size ) {
			add_image_size( $crop_id, $size[0], $size[1], $size[2] );
		}
	}
}

/* register scripts */
if ( ! function_exists( 'foxiz_register_script_frontend' ) ) {
	function foxiz_register_script_frontend() {

		$style_deps  = [];
		$script_deps = [
			'jquery',
			'jquery-waypoints',
			'rbswiper',
			'jquery-magnific-popup',
		];

		$main_filename        = 'main';
		$woocommerce_filename = 'woocommerce';
		$podcast_filename     = 'podcast';

		if ( is_rtl() ) {
			$main_filename        = 'rtl';
			$woocommerce_filename = 'woocommerce-rtl';
			$podcast_filename     = 'podcast-rtl';
		}

		$gfont_url = Foxiz_Font::get_font_url();

		if ( ! empty( $gfont_url ) ) {
			wp_register_style( 'foxiz-font', esc_url_raw( $gfont_url ), [], FOXIZ_THEME_VERSION, 'all' );
			$style_deps[] = 'foxiz-font';
		}

		if ( foxiz_get_option( 'font_awesome' ) ) {
			wp_deregister_style( 'font-awesome' );
			wp_register_style( 'font-awesome', foxiz_get_file_uri( 'assets/css/font-awesome.css' ), [], '6.1.1', 'all' );
			$style_deps[] = 'font-awesome';
		}

		wp_register_style( 'foxiz-main', foxiz_get_file_uri( 'assets/css/' . $main_filename . '.css' ), [], FOXIZ_THEME_VERSION, 'all' );
		wp_add_inline_style( 'foxiz-main', foxiz_get_dynamic_css() );
		$style_deps[] = 'foxiz-main';

		if ( foxiz_get_option( 'podcast_supported' ) ) {
			wp_register_style( 'foxiz-podcast', foxiz_get_file_uri( 'assets/css/' . $podcast_filename . '.css' ), [], FOXIZ_THEME_VERSION, 'all' );
			$style_deps[] = 'foxiz-podcast';
		}

		if ( ! foxiz_is_amp() ) {

			wp_register_style( 'foxiz-print', foxiz_get_file_uri( 'assets/css/print.css' ), [], FOXIZ_THEME_VERSION, 'all' );
			$style_deps[] = 'foxiz-print';

			if ( class_exists( 'WooCommerce' ) ) {
				wp_deregister_style( 'yith-wcwl-font-awesome' );
				wp_register_style( 'foxiz-woocommerce', foxiz_get_file_uri( 'assets/css/' . $woocommerce_filename . '.css' ), [], FOXIZ_THEME_VERSION, 'all' );
				$style_deps[] = 'foxiz-woocommerce';
			}
		}

		wp_register_style( 'foxiz-style', get_stylesheet_uri(), $style_deps, FOXIZ_THEME_VERSION, 'all' );
		wp_enqueue_style( 'foxiz-style' );

		if ( ! foxiz_is_amp() ) {

			wp_register_script( 'html5', foxiz_get_file_uri( 'assets/js/html5shiv.min.js' ), [], '3.7.3' );
			wp_script_add_data( 'html5', 'conditional', 'lt IE 9' );

			if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
				wp_enqueue_script( 'comment-reply' );
			}

			wp_register_script( 'jquery-waypoints', foxiz_get_file_uri( 'assets/js/jquery.waypoints.min.js' ), [ 'jquery' ], '3.1.1', true );
			wp_register_script( 'rbswiper', foxiz_get_file_uri( 'assets/js/rbswiper.min.js' ), [], '6.8.4', true );
			wp_register_script( 'jquery-magnific-popup', foxiz_get_file_uri( 'assets/js/jquery.mp.min.js' ), [ 'jquery' ], '1.1.0', true );

			if ( foxiz_get_option( 'site_tooltips' ) && ! foxiz_is_wc_pages() ) {
				wp_register_script( 'rb-tipsy', foxiz_get_file_uri( 'assets/js/jquery.tipsy.min.js' ), [ 'jquery' ], '1.0', true );
				$script_deps[] = 'rb-tipsy';
			}

			if ( foxiz_get_option( 'single_post_highlight_shares' ) ) {
				wp_register_script( 'highlight-share', foxiz_get_file_uri( 'assets/js/highlight-share.js' ), '1.1.0', true );
				$script_deps[] = 'highlight-share';
			}

			if ( foxiz_get_option( 'back_top' ) ) {
				wp_register_script( 'jquery-uitotop', foxiz_get_file_uri( 'assets/js/jquery.ui.totop.min.js' ), [ 'jquery' ], 'v1.2', true );
				$script_deps[] = 'jquery-uitotop';
			}

			if ( class_exists( 'FOXIZ_CORE' ) ) {
				if ( foxiz_get_option( 'bookmark_system' ) ) {
					wp_register_script( 'foxiz-personalize', foxiz_get_file_uri( 'assets/js/personalized.js' ), [
						'jquery',
						'foxiz-core',
					], FOXIZ_THEME_VERSION, true );
					$script_deps[] = 'foxiz-personalize';
				}
				$script_deps[] = 'foxiz-core';
			}
			wp_register_script( 'foxiz-global', foxiz_get_file_uri( 'assets/js/global.js' ), $script_deps, FOXIZ_THEME_VERSION, true );
			wp_localize_script( 'foxiz-global', 'foxizParams', foxiz_get_js_settings() );
			wp_enqueue_script( 'foxiz-global' );
		}
	}
}

