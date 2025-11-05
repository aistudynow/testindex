<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

/** comment template */
if ( post_password_required() || ( ! comments_open() && ! pings_open() ) ) {
	return;
}

$hidden_comment_btn = get_query_var( 'rbsnp' ) ? foxiz_get_option( 'ajax_next_comment_button' ) : foxiz_get_option( 'single_post_comment_button' );
$hidden_comment_btn = ( foxiz_is_amp() || function_exists( 'run_disqus' ) ) ? false : $hidden_comment_btn;

$class_name = 'comment-holder' . ( ! get_comments_number() ? ' no-comment' : '' ) . ( ! empty( $button ) && ! is_page() ? ' is-hidden' : '' );

$class_name = 'comment-holder';
if ( ! get_comments_number() ) {
	$class_name .= ' no-comment';
}

if ( $hidden_comment_btn && ! is_page() ) {
	$class_name .= ' is-hidden';
}

?>
<div class="comment-box-header">
	<?php if ( $hidden_comment_btn ) : ?>
		<span class="comment-box-title h3"><i class="rbi rbi-comment" aria-hidden="true"></i><span class="is-invisible"><?php echo foxiz_get_comment_heading( get_the_ID() ); ?></span></span>
		<a href="#" role="button" class="show-post-comment"><i class="rbi rbi-comment" aria-hidden="true"></i><?php echo foxiz_get_comment_heading( get_the_ID() ); ?>
		</a>
	<?php else: ?>
		<span class="h3"><i class="rbi rbi-comment" aria-hidden="true"></i><?php echo foxiz_get_comment_heading( get_the_ID() ); ?></span>
	<?php endif; ?>
</div>
<div class="<?php echo strip_tags( $class_name ); ?>">
	<div id="comments" class="comments-area">
		<?php if ( have_comments() ) : ?>
			<div class="rb-section">
				<ul class="comment-list entry">
					<?php wp_list_comments( [
									'avatar_size' => 100,
									'style'       => 'ul',
									'short_ping'  => true,
							]
					); ?>
				</ul>
				<?php the_comments_pagination( [
								'prev_text' => '<span class="nav-previous">' . foxiz_html__( '&larr; Older Comments', 'foxiz' ) . '</span>',
								'next_text' => '<span class="nav-next">' . foxiz_html__( 'Newer Comments &rarr;', 'foxiz' ) . '</span>',
						]
				); ?>
			</div>
		<?php endif;
		if ( ! comments_open() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
			<p class="no-comments"><?php echo foxiz_html__( 'Comments are closed.', 'foxiz' ); ?></p>
		<?php endif; ?>
		<?php comment_form(); ?>
	</div>
</div>
