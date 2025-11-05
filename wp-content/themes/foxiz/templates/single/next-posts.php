<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

global $post;

$foxiz_next_url    = '';
$foxiz_current_url = get_permalink();
$foxiz_post_id     = get_the_ID();
$foxiz_next_button = foxiz_get_option( 'ajax_next_button' );

if ( ! empty( foxiz_get_option( 'ajax_next_cat' ) ) ) {
	$foxiz_post_prev = get_previous_post( true );
} else {
	$foxiz_post_prev = get_previous_post();
}
if ( ! empty( $foxiz_post_prev ) ) {
	$foxiz_next_url = get_permalink( $foxiz_post_prev );
}
wp_reset_postdata();

$foxiz_classes = 'single-post-outer';
if ( ! empty( $foxiz_next_button ) ) {
	$foxiz_classes .= ' has-continue-reading';
}
if ( have_posts() ) :
	while ( have_posts() ) {
		the_post(); ?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<meta http-equiv="X-UA-Compatible" content="IE=edge" />
			<meta name="robots" content="noindex, nofollow" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<link rel="profile" href="https://gmpg.org/xfn/11" />
			<link rel="canonical" href="<?php echo esc_url( $foxiz_current_url ); ?>" />
		</head>
		<body>
		<div class="<?php echo strip_tags( $foxiz_classes ); ?>" data-postid="<?php echo esc_attr( $foxiz_post_id ); ?>" data-postlink="<?php echo esc_url( get_permalink() ); ?>" data-nextposturl="<?php echo esc_url( $foxiz_next_url ); ?>">
			<?php foxiz_render_single_post(); ?>
			<?php if ( ! empty( $foxiz_next_button ) ) : ?>
				<div class="continue-reading">
					<a href="<?php echo esc_url( $foxiz_current_url ); ?>" class="continue-reading-btn is-btn"><?php foxiz_html_e( 'Continue Reading', 'foxiz' ); ?></a>
				</div>
			<?php else :
				/** add read history */
				if ( foxiz_get_option( 'reading_history' ) && class_exists( 'Foxiz_Personalize_Helper' ) ) {
					Foxiz_Personalize_Helper::get_instance()->save_history( $foxiz_post_id );
				}
			endif; ?>
		</div>
		</body>
		</html>
		<?php
	}
endif;

die();
