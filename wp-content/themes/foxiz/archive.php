<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

get_header();
$foxiz_settings = foxiz_get_flex_archive_settings();
foxiz_archive_page_header( $foxiz_settings );
if ( have_posts() ) {
	foxiz_the_blog( $foxiz_settings );
} else {
	foxiz_blog_empty();
}
get_footer();