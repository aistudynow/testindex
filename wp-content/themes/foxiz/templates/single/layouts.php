<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_single_post' ) ) {
	function foxiz_single_post() {

		if ( ! isset( $GLOBALS['foxiz_queried_ids'] ) ) {
			$GLOBALS['foxiz_queried_ids'] = [];
		}
		array_push( $GLOBALS['foxiz_queried_ids'], get_the_ID() );

		if ( 'attachment' === get_post_type() ) {
			foxiz_render_single_attachment();

			return;
		}

		/** only for default pos type */
		if ( is_singular( 'post' ) ) {
			if ( ! empty( foxiz_get_option( 'ajax_next_cat' ) ) ) {
				$post_prev = get_previous_post( true );
			} else {
				$post_prev = get_previous_post();
			}
		}

		if ( ( ( foxiz_get_single_setting( 'ajax_next_post' ) && ! empty( $post_prev ) ) || get_query_var( 'rbsnp' ) ) && ! foxiz_is_amp() ) :
			$class_name = 'single-post-infinite';
			if ( ! empty( foxiz_get_option( 'ajax_next_hide_sidebar' ) ) ) {
				$class_name .= ' none-mobile-sb';
			} ?>
			<div id="single-post-infinite" class="<?php echo strip_tags( $class_name ); ?>" data-nextposturl="<?php echo esc_url( get_permalink( $post_prev ) ); ?>">
				<div class="single-post-outer activated" data-postid="<?php echo get_the_ID(); ?>" data-postlink="<?php echo esc_url( get_permalink() ); ?>">
					<?php foxiz_render_single_post(); ?>
				</div>
			</div>
			<div id="single-infinite-point" class="single-infinite-point pagination-wrap">
				<i class="rb-loader" aria-hidden="true"></i>
			</div>
		<?php else :
			foxiz_render_single_post();
		endif;
	}
}

if ( ! function_exists( 'foxiz_render_single_post' ) ) {
	function foxiz_render_single_post() {

		$layout = foxiz_get_single_layout();

		if ( 'stemplate' !== $layout['layout'] ) {
			$func = 'foxiz_render_single_' . $layout['layout'];
			if ( function_exists( $func ) ) {
				call_user_func( $func );
			}
		} else {
			foxiz_single_open_tag();
			echo do_shortcode( $layout['shortcode'] );
			if ( isset( $GLOBALS['foxiz_rbsnp'] ) && $GLOBALS['foxiz_rbsnp'] ) {
				do_action( 'foxiz_elementor_single_style', $layout['shortcode'] );
			}
			foxiz_single_close_tag();
		}
	}
}