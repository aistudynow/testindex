<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

?><!DOCTYPE html>
<html <?php language_attributes();
if ( foxiz_is_amp() ) {
	echo ' [class]="collapse ? \'collapse-activated\' : \'collapse-deactivated\'"';
} ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="profile" href="https://gmpg.org/xfn/11" />
  

	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> data-theme="<?php echo foxiz_get_theme_mode(); ?>">
<?php
wp_body_open();
foxiz_top_ad();
?><div class="site-outer">
        <?php foxiz_render_header(); ?>
        <main id="main-content" class="site-main" role="main" tabindex="-1">
                <div class="site-wrap">