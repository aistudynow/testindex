<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

$cache_id = 'ruby_live_' . get_the_ID();
if ( ! get_transient( $cache_id ) ) {
	ob_start();
	if ( have_posts() ) :
		while ( have_posts() ) {
			the_post();
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>"/>
				<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
				<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
				<meta name="robots" content="noindex, nofollow"/>
				<link rel="profile" href="https://gmpg.org/xfn/11"/>
				<link rel="canonical" href="<?php echo esc_url( get_permalink() ); ?>"/>
			</head>
			<body>
			<div id="rb-live-content" data-total="<?php echo get_post_meta( get_the_ID(), 'ruby_total_live_blocks', true ); ?>"><?php the_content(); ?></div>
			</body>
			</html>
			<?php
		}
	endif;
	$html = ob_get_clean();
	set_transient( $cache_id, $html, 5 * 3600 );
}

echo get_transient( $cache_id );
die();