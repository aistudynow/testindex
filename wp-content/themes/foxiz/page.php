<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

get_header();
if ( have_posts() ) :
	while ( have_posts() ) : the_post();
		$document = false;
		if ( foxiz_is_elementor_active() ) {

			$doc_type = true;
			if ( foxiz_is_amp() ) {
				$doc_type = Elementor\Plugin::$instance->documents->get_document_type( 'wp-page' );
			}

			if ( $doc_type ) {
				$document = Elementor\Plugin::$instance->documents->get( get_the_ID() );
			}
		}
		if ( $document && $document->is_built_with_elementor() ) {
			the_content();
		} else {
			foxiz_single_page();
		}
	endwhile;
endif;
get_footer();