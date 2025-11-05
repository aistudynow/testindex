<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_author_card_1' ) ) {
	function foxiz_author_card_1( $settings = [] ) {

		if ( empty( $settings['author'] ) ) {
			return;
		}
		$author_id   = $settings['author'];
		$total_posts = 0;

		if ( ! empty( $settings['feat_lazyload'] ) ) {
			$is_lazy = ( 'none' === $settings['feat_lazyload'] ) ? false : true;
		} else {
			$is_lazy = foxiz_get_option( 'lazy_load' );
		}

		$description     = get_the_author_meta( 'description', $author_id );
		$author_image_id = (int) get_the_author_meta( 'author_image_id', $author_id );

		if ( ! empty( $settings['count_posts'] ) && '1' === (string) $settings['count_posts'] ) {
			$total_posts = count_user_posts( $author_id );
		}
		if ( ! empty( $settings['description_length'] ) ) {
			$length = absint( $settings['description_length'] );
		}
		?>
		<div class="a-card a-card-1">
			<div class="a-card-inner box-inner">
				<div class="a-card-content">
					<div class="a-card-name">
						<a class="h4 nice-name" href="<?php echo get_author_posts_url( $author_id ); ?>"><?php the_author_meta( 'display_name', $author_id ); ?></a>
						<?php if ( $total_posts > 0 ) : ?>
							<span class="a-card-count is-meta"><?php
								echo strip_tags( $total_posts ) . ' ';
								if ( (string) $total_posts === '1' ) {
									foxiz_html_e( 'Article', 'foxiz' );
								} else {
									foxiz_html_e( 'Articles', 'foxiz' );
								} ?></span>
						<?php endif; ?>
					</div>
					<div class="description-text rb-text"><?php
						if ( ! empty( $length ) ) {
							echo wp_trim_words( $description, $length, '...' );
						} else {
							foxiz_render_inline_html( $description );
						}
						?></div>
					<?php if ( ! empty( $settings['follow'] ) && '1' === (string) $settings['follow'] ) {
						foxiz_follow_trigger( [ 'id' => $author_id, 'type' => 'author' ] );
					} ?>
				</div>
				<div class="a-card-feat">
					<a href="<?php echo get_author_posts_url( $author_id ); ?>"><?php
						if ( $author_image_id !== 0 ) {
							echo foxiz_get_avatar_by_attachment( $author_image_id, 'full', $is_lazy );
						} else {
							echo get_avatar( $author_id, 400 );
						}
						?></a>
					<?php
					if ( get_the_author_meta( 'job', $author_id ) ) : ?>
						<span class="author-job is-meta"><?php the_author_meta( 'job', $author_id ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'foxiz_author_card_2' ) ) {
	function foxiz_author_card_2( $settings = [] ) {

		if ( empty( $settings['author'] ) ) {
			return;
		}
		$author_id   = $settings['author'];
		$total_posts = 0;

		if ( ! empty( $settings['feat_lazyload'] ) ) {
			$is_lazy = ( 'none' === $settings['feat_lazyload'] ) ? false : true;
		} else {
			$is_lazy = foxiz_get_option( 'lazy_load' );
		}

		$description     = get_the_author_meta( 'description', $author_id );
		$author_image_id = (int) get_the_author_meta( 'author_image_id', $author_id );

		if ( ! empty( $settings['count_posts'] ) && '1' === (string) $settings['count_posts'] ) {
			$total_posts = count_user_posts( $author_id );
		}
		if ( ! empty( $settings['description_length'] ) ) {
			$length = absint( $settings['description_length'] );
		}
		?>
		<div class="a-card a-card-2">
			<div class="a-card-inner box-inner">
				<div class="a-card-content">
					<div class="a-card-feat">
						<a href="<?php echo get_author_posts_url( $author_id ); ?>"><?php
							if ( $author_image_id !== 0 ) {
								echo foxiz_get_avatar_by_attachment( $author_image_id, 'full', $is_lazy );
							} else {
								echo get_avatar( $author_id, 400 );
							}
							?></a>
					</div>
					<div class="a-card-meta">
						<?php
						if ( get_the_author_meta( 'job', $author_id ) ) : ?>
							<span class="author-job is-meta"><?php the_author_meta( 'job', $author_id ); ?></span>
						<?php
						endif;
						if ( $total_posts > 0 ) : ?>
							<span class="a-card-count is-meta"><?php
								echo strip_tags( $total_posts ) . ' ';
								if ( (string) $total_posts === '1' ) {
									foxiz_html_e( 'Article', 'foxiz' );
								} else {
									foxiz_html_e( 'Articles', 'foxiz' );
								} ?></span>
						<?php endif; ?>
					</div>
					<div class="a-card-name">
						<a class="h4 nice-name" href="<?php echo get_author_posts_url( $author_id ); ?>"><?php the_author_meta( 'display_name', $author_id ); ?></a>
					</div>
					<div class="description-text rb-text"><?php
						if ( ! empty( $length ) ) {
							echo wp_trim_words( $description, $length, '...' );
						} else {
							foxiz_render_inline_html( $description );
						}
						?></div>
					<?php
					if ( ! empty( $settings['follow'] ) && '1' === (string) $settings['follow'] ) {
						foxiz_follow_trigger( [ 'id' => $author_id, 'type' => 'author' ] );
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}
}