<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

get_header();
$foxiz_settings = foxiz_get_archive_page_settings( 'blog_' );
if ( have_posts() ) {
	foxiz_blog_embed_template( $foxiz_settings );
	foxiz_the_blog( $foxiz_settings );
	foxiz_blog_embed_template_bottom( $foxiz_settings );
} else {
	foxiz_blog_empty();
}
get_footer();