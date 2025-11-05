<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
?>
<form role="search" method="get" class="search-form wp-block-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div class="search-form-icon"><?php
		$icon = foxiz_get_option( 'header_search_custom_icon' );
		if ( ! empty( $icon['url'] ) ) {
			echo '<span class="search-icon-svg"></span>';
		} else {
			echo '<i class="rbi rbi-search" aria-hidden="true"></i>';
		}
		?></div>
	<label class="search-form-input">
		<span class="screen-reader-text"><?php foxiz_html_e( 'Search for:', 'foxiz' ) ?></span>
		<input type="search" class="search-field"
		       placeholder="<?php echo esc_attr( foxiz_get_option( 'search_placeholder', foxiz_html__( 'Search Headlines, News...', 'foxiz' ) ) ); ?>"
		       value="<?php echo esc_attr( get_search_query() ); ?>"
		       name="s">
		<?php if ( isset( $_GET['post_type'] ) ) : ?>
			<input type="hidden" class="is-hidden" value="<?php echo sanitize_text_field( $_GET['post_type'] ); ?>" name="post_type">
		<?php endif; ?>
	</label>
	<div class="search-form-submit">
		<input type="submit" value="<?php foxiz_html_e( 'Search', 'foxiz' ); ?>">
	</div>
</form>