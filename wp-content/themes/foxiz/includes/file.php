<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

include_once foxiz_get_file_path( 'includes/fallback-functions.php' );
include_once foxiz_get_file_path( 'includes/fonts.php' );

/** ADMIN */
if ( is_admin() ) {
	include_once foxiz_get_file_path( 'backend/setting-helpers.php' );
	include_once foxiz_get_file_path( 'backend/hooks.php' );
	include_once foxiz_get_file_path( 'backend/activation/class-activation.php' );
	include_once foxiz_get_file_path( 'backend/theme-options.php' );
	include_once foxiz_get_file_path( 'backend/single-settings.php' );
	include_once foxiz_get_file_path( 'backend/tax-settings.php' );
	include_once foxiz_get_file_path( 'backend/mega-menu.php' );
	include_once foxiz_get_file_path( 'backend/dynamic-css.php' );
}

/** HELPER FUNCTIONS */
include_once foxiz_get_file_path( 'includes/helpers.php' );

/** PERSONALIZE */
include_once foxiz_get_file_path( 'personalize/personalize.php' );
include_once foxiz_get_file_path( 'personalize/parts.php' );
include_once foxiz_get_file_path( 'personalize/reading-list.php' );

/** THEME FUNCTIONS */
include_once foxiz_get_file_path( 'includes/sidebars.php' );
include_once foxiz_get_file_path( 'includes/menu.php' );
include_once foxiz_get_file_path( 'includes/actions.php' );
include_once foxiz_get_file_path( 'includes/query.php' );

/** PODCAST */
if ( foxiz_get_option( 'podcast_supported' ) ) {
	include_once foxiz_get_file_path( 'podcast/init.php' );
}

/** AJAX */
include_once foxiz_get_file_path( 'templates/ajax.php' );

/** TEMPLATES PARTS */
include_once foxiz_get_file_path( 'templates/parts.php' );
include_once foxiz_get_file_path( 'templates/header-parts.php' );
include_once foxiz_get_file_path( 'templates/footer-parts.php' );

/** TEMPLATES */
include_once foxiz_get_file_path( 'templates/entry.php' );
include_once foxiz_get_file_path( 'templates/popup.php' );
include_once foxiz_get_file_path( 'templates/blog.php' );
include_once foxiz_get_file_path( 'templates/page.php' );

/** HEADER */
include_once foxiz_get_file_path( 'templates/header/layouts.php' );
include_once foxiz_get_file_path( 'templates/header/transparent.php' );

/** SINGLE */
include_once foxiz_get_file_path( 'templates/single/templates.php' );
include_once foxiz_get_file_path( 'templates/single/reviews.php' );
include_once foxiz_get_file_path( 'templates/single/layouts.php' );
include_once foxiz_get_file_path( 'templates/single/footer.php' );
include_once foxiz_get_file_path( 'templates/single/related.php' );
include_once foxiz_get_file_path( 'templates/single/attachment.php' );

include_once foxiz_get_file_path( 'templates/single/standard-8.php' );

include_once foxiz_get_file_path( 'templates/single/video-1.php' );
include_once foxiz_get_file_path( 'templates/single/video-1a.php' );
include_once foxiz_get_file_path( 'templates/single/video-2.php' );
include_once foxiz_get_file_path( 'templates/single/video-3.php' );
include_once foxiz_get_file_path( 'templates/single/video-4.php' );
include_once foxiz_get_file_path( 'templates/single/audio-1.php' );
include_once foxiz_get_file_path( 'templates/single/audio-1a.php' );
include_once foxiz_get_file_path( 'templates/single/audio-2.php' );
include_once foxiz_get_file_path( 'templates/single/audio-3.php' );
include_once foxiz_get_file_path( 'templates/single/audio-4.php' );
include_once foxiz_get_file_path( 'templates/single/gallery-1.php' );
include_once foxiz_get_file_path( 'templates/single/gallery-2.php' );
include_once foxiz_get_file_path( 'templates/single/gallery-3.php' );

/** MODULES */
include_once foxiz_get_file_path( 'templates/modules/author.php' );
include_once foxiz_get_file_path( 'templates/modules/category.php' );
include_once foxiz_get_file_path( 'templates/modules/classic.php' );
include_once foxiz_get_file_path( 'templates/modules/grid.php' );
include_once foxiz_get_file_path( 'templates/modules/list.php' );
include_once foxiz_get_file_path( 'templates/modules/overlay.php' );

/** BLOCKS */
include_once foxiz_get_file_path( 'templates/blocks/authors.php' );
include_once foxiz_get_file_path( 'templates/blocks/breaking-news.php' );
include_once foxiz_get_file_path( 'templates/blocks/categories.php' );
include_once foxiz_get_file_path( 'templates/blocks/classic-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/gallery.php' );
include_once foxiz_get_file_path( 'templates/blocks/grid-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/grid-2.php' );
include_once foxiz_get_file_path( 'templates/blocks/grid-box-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/grid-box-2.php' );
include_once foxiz_get_file_path( 'templates/blocks/grid-flex-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/grid-flex-2.php' );
include_once foxiz_get_file_path( 'templates/blocks/grid-personalize-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/grid-personalize-2.php' );
include_once foxiz_get_file_path( 'templates/blocks/grid-small-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/heading.php' );
include_once foxiz_get_file_path( 'templates/blocks/hierarchical-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/hierarchical-2.php' );
include_once foxiz_get_file_path( 'templates/blocks/hierarchical-3.php' );
include_once foxiz_get_file_path( 'templates/blocks/list-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/list-2.php' );
include_once foxiz_get_file_path( 'templates/blocks/list-box-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/list-box-2.php' );
include_once foxiz_get_file_path( 'templates/blocks/list-flex.php' );
include_once foxiz_get_file_path( 'templates/blocks/list-personalize.php' );
include_once foxiz_get_file_path( 'templates/blocks/list-small-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/list-small-2.php' );
include_once foxiz_get_file_path( 'templates/blocks/list-small-3.php' );
include_once foxiz_get_file_path( 'templates/blocks/newsletter.php' );
include_once foxiz_get_file_path( 'templates/blocks/overlay-1.php' );
include_once foxiz_get_file_path( 'templates/blocks/overlay-2.php' );
include_once foxiz_get_file_path( 'templates/blocks/overlay-flex.php' );
include_once foxiz_get_file_path( 'templates/blocks/overlay-personalize.php' );
include_once foxiz_get_file_path( 'templates/blocks/playlist.php' );
include_once foxiz_get_file_path( 'templates/blocks/quick-links.php' );
include_once foxiz_get_file_path( 'templates/blocks/tax-accordion.php' );

/** Woocommerce */
if ( class_exists( 'WooCommerce' ) ) {
	include_once foxiz_get_file_path( 'woocommerce/hooks.php' );
	include_once foxiz_get_file_path( 'templates/blocks/product-grid.php' );
}