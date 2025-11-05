<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Foxiz_Ajax', false ) ) {
	class Foxiz_Ajax {

		private static $instance;
		public $style = '';

		public static function get_instance() {

			if ( self::$instance === null ) {
				return new self();
			}

			return self::$instance;
		}

		public function __construct() {

			self::$instance = $this;
			add_action( 'init', [ $this, 'register_endpoint' ], 10 );
			add_action( 'template_redirect', [ $this, 'endpoint_redirect' ] );

			add_action( 'wp_ajax_nopriv_rblivep', [ $this, 'pagination' ] );
			add_action( 'wp_ajax_rblivep', [ $this, 'pagination' ] );
			add_action( 'wp_ajax_nopriv_rbsearch', [ $this, 'live_search' ] );
			add_action( 'wp_ajax_rbsearch', [ $this, 'live_search' ] );
			add_action( 'wp_ajax_nopriv_rbpersonalizeb', [ $this, 'personalize_block' ] );
			add_action( 'wp_ajax_rbpersonalizeb', [ $this, 'personalize_block' ] );
			add_action( 'wp_ajax_nopriv_rbpersonalizecat', [ $this, 'personalize_categories' ] );
			add_action( 'wp_ajax_rbpersonalizecat', [ $this, 'personalize_categories' ] );
			add_action( 'wp_ajax_nopriv_rbnotification', [ $this, 'notification' ] );
			add_action( 'wp_ajax_rbnotification', [ $this, 'notification' ] );
			add_action( 'wp_ajax_nopriv_rbvoting', [ $this, 'voting' ] );
			add_action( 'wp_ajax_rbvoting', [ $this, 'voting' ] );
		}

		public function pagination() {

			if ( empty( $_GET['data'] ) || empty( $_GET['data']['name'] ) ) {
				wp_send_json( '' );
				wp_die();
			}

			$settings = $this->validate( $_GET['data'] );

			$settings['no_found_rows'] = false;
			$settings['feat_lazyload'] = 'none';
			$paged                     = 2;

			if ( isset( $settings['page_next'] ) ) {
				$paged = absint( $settings['page_next'] );
			}
			if ( empty( $settings['posts_per_page'] ) ) {
				$settings['posts_per_page'] = get_option( 'posts_per_page' );
			}

			/** ajax for custom query */
			if ( ! empty( $settings['content_source'] ) ) {
				switch ( $settings['content_source'] ) {
					case 'related':
						$_query = foxiz_query_related( $settings, $paged );
						break;
					case 'recommended':
						$settings['paged'] = $paged;
						$_query            = Foxiz_Personalize::get_instance()->recommended_query( $settings );
						break;
					case 'saved':
						$settings['paged'] = $paged;
						$_query            = Foxiz_Personalize::get_instance()->saved_posts_query( $settings );
						break;
					case 'history':
						$settings['paged'] = $paged;
						$_query            = Foxiz_Personalize::get_instance()->reading_history_query( $settings );
						break;
				}
			} else {
				$_query = foxiz_query( $settings, $paged );
			}

			$response = [];

			if ( ! empty( $_query ) && $_query->have_posts() ) {
				if ( ! empty( $_query->paged ) ) {
					$response['paged'] = $_query->paged;
				} else {
					$response['paged'] = $paged;
				}
				if ( $response['paged'] >= $settings['page_max'] ) {
					$response['notice'] = $this->end_list_info();
				}
				$response['content'] = $this->render( $settings, $_query );
				wp_reset_postdata();
			} else {
				$response['paged']   = $settings['page_max'] + 99;
				$response['content'] = $this->end_list_info();
			}

			wp_send_json( $response );
		}

		/**
		 * @param $settings
		 *
		 * @return array|mixed|string
		 * validate input
		 */
		function validate( $settings ) {

			if ( is_array( $settings ) ) {
				$sanitized = [];

				foreach ( $settings as $key => $value ) {
					$clean_key               = sanitize_key( $key );
					$sanitized[ $clean_key ] = $this->validate( $value );
				}

				return $sanitized;

			} elseif ( is_string( $settings ) ) {
				return foxiz_strip_tags( stripslashes( $settings ) );
			}

			return '';
		}

		/**
		 * @param $settings
		 * @param $_query
		 *
		 * @return false|string
		 * render
		 */
		function render( $settings, $_query ) {

			ob_start();
			$func = 'foxiz_loop_' . trim( $settings['name'] );

			if ( function_exists( $func ) ) {
				call_user_func_array( $func, [ $settings, $_query ] );
			}

			return ob_get_clean();
		}

		/**
		 * @return string
		 * end list info
		 */
		function end_list_info() {

			$output = '<div class="p-wrap end-list-info is-meta"><i class="rbi rbi-chart" aria-hidden="true"></i><span>';
			$output .= foxiz_html__( 'You\'ve reached the end of the list!', 'foxiz' );
			$output .= '</span></div>';

			return $output;
		}

		public function endpoint_redirect() {

			if ( ! is_singular( 'post' ) ) {
				return;
			}

			if ( get_query_var( 'rbsnp' ) ) {

				$GLOBALS['foxiz_rbsnp'] = true;
				$file     = '/templates/single/next-posts.php';
				$template = locate_template( $file );
				if ( $template ) {
					include( $template );
				}
				exit;
			} elseif ( get_query_var( 'rblive' ) ) {
				$file     = '/templates/single/live.php';
				$template = locate_template( $file );
				if ( $template ) {
					include( $template );
				}
				exit;
			}
		}

		public function register_endpoint() {

			add_rewrite_endpoint( 'rbsnp', EP_PERMALINK );
			add_rewrite_endpoint( 'rblive', EP_PERMALINK );
			flush_rewrite_rules();
		}

		/** live search */
		function live_search() {

			if ( empty( $_GET['s'] ) ) {
				wp_send_json( '' );
				wp_die();
			}

			if ( empty( $_GET['search'] ) || 'category' === $_GET['search'] ) {
				$this->search_categories();
			} else {
				$this->search_posts();
			}
		}

		public function search_posts() {

			$input     = sanitize_text_field( $_GET['s'] );
			$limit     = ! empty( $_GET['limit'] ) ? absint( $_GET['limit'] ) : foxiz_get_option( 'ajax_search_limit', 4 );
			$post_type = ! empty( $_GET['ptype'] ) ? sanitize_text_field( $_GET['ptype'] ) : '';

			if ( $limit > 10 ) {
				$limit = 10;
			}

			$params = [
					's'              => $input,
					'posts_per_page' => intval( $limit ),
					'post_status'    => 'publish',
			];

			if ( empty( $post_type ) ) {
				$post_type = strip_tags( foxiz_get_option( 'search_post_types' ) );
			}
			if ( ! empty( $post_type ) ) {
				$post_type = array_map( 'trim', explode( ',', $post_type ) );
			} else {
				$post_type = get_post_types( [ 'exclude_from_search' => false ] );
			}

			$exclude_post_types = foxiz_get_option( 'search_type_disallow' );
			if ( ! empty( $exclude_post_types ) ) {
				$exclude_post_types = array_map( 'trim', explode( ',', $exclude_post_types ) );
				$post_type          = array_diff( $post_type, $exclude_post_types );
			}

			$params['post_type'] = $post_type;
			$_query              = new WP_Query( $params );

			$response = '<div class="block-inner live-search-inner p-middle">';
			if ( $_query->have_posts() ) {
				ob_start();
				while ( $_query->have_posts() ) :
					$_query->the_post();
					foxiz_list_small_2( [
							'featured_position' => 'left',
							'entry_meta'        => [ 'update', 'index' ],
							'title_index'       => '1',
							'title_tag'         => 'div',
							'title_classes'     => 'h5',
							'middle_mode'       => '1',
							'edit_link'         => false,
					] );
				endwhile;
				$response .= ob_get_clean();
				$response .= '<div class="live-search-link"><a class="is-btn" href="' . get_search_link( $input ) . '">' . foxiz_html__( 'More Results', 'foxiz' ) . '</a></div>';
			} else {
				$response .= '<div class="search-no-result">' . foxiz_html__( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'foxiz' ) . '</div>';
			}

			$response .= '</div>';
			wp_send_json( $response );
		}

		public function search_categories() {

			$input       = sanitize_text_field( $_GET['s'] );
			$follow      = ! empty( $_GET['follow'] ) ? 1 : 0;
			$limit       = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 4;
			$desc_source = isset( $_GET['dsource'] ) ? sanitize_text_field( $_GET['dsource'] ) : 0;

			if ( $limit > 6 ) {
				$limit = 6;
			}

			$params = [
					'search'     => $input,
					'number'     => $limit,
					'hide_empty' => true,
			];

			if ( ! empty( $_GET['tax'] ) ) {
				$taxonomies = sanitize_text_field( $_GET['tax'] );
				if ( 'all' !== $taxonomies ) {
					$taxonomies         = explode( ',', $taxonomies );
					$taxonomies         = array_map( 'trim', $taxonomies );
					$params['taxonomy'] = $taxonomies;
				}
			} else {
				$params['taxonomy'] = [ 'category' ];
			}
			$taxonomies = get_terms( $params );
			$response   = '<div class="block-inner live-search-inner">';
			if ( ! empty( $taxonomies ) ) {
				ob_start();
				foreach ( $taxonomies as $category ) {
					foxiz_category_item_search( [
							'cid'         => $category->term_id,
							'follow'      => $follow,
							'count_posts' => 1,
							'desc_source' => $desc_source,
					] );
				}
				$response .= ob_get_clean();
			} else {
				$response .= '<div class="search-no-result">' . foxiz_html__( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'foxiz' ) . '</div>';
			}
			$response .= '</div>';

			wp_send_json( $response );
		}

		public function personalize_block() {

			if ( empty( $_GET['data'] ) || empty( $_GET['data']['name'] ) ) {
				wp_send_json( '' );
				wp_die();
			}

			$settings = $this->validate( $_GET['data'] );

			if ( ! empty( $settings['content_source'] ) ) {
				$_query = foxiz_personalize_query( $settings );
			} else {
				$_query = foxiz_query( $settings );
			}

			$func = 'foxiz_live_block_' . trim( $settings['name'] );

			ob_start();
			if ( function_exists( $func ) ) {
				call_user_func_array( $func, [ $settings, $_query ] );
			}

			wp_send_json( ob_get_clean() );
		}

		public function personalize_categories() {

			if ( empty( $_GET['data'] ) || empty( $_GET['data']['name'] ) ) {
				wp_send_json( '' );
				wp_die();
			}

			$settings = $this->validate( $_GET['data'] );
			$func     = 'foxiz_live_get_' . trim( $settings['name'] );

			ob_start();
			if ( function_exists( $func ) ) {
				call_user_func_array( $func, [ $settings ] );
			}
			wp_send_json( ob_get_clean() );
		}

		/** get notification */
		function notification() {

			$response = [
					'content' => '',
					'count'   => '',
			];

			$duration = absint( foxiz_get_option( 'notification_duration' ) );
			if ( empty( $duration ) ) {
				$db_after = '72 hours ago';
			} else {
				$db_after = $duration . ' hours ago';
			}
			$_query = new WP_Query( [
					'post_type'      => 'post',
					'no_found_rows'  => true,
					'post_status'    => 'publish',
					'order'          => 'DESC',
					'posts_per_page' => 9,
					'date_query'     => [
							[ 'after' => $db_after ],
					],
			] );

			ob_start();
			if ( $_query->have_posts() ) :
				$response['count'] = $_query->post_count; ?>
				<div class="block-inner">
					<?php
					foxiz_loop_list_small_2( [
							'design_override'  => true,
							'title_tag'        => 'div',
							'title_classes'    => 'h5',
							'edit_link'        => false,
							'bookmark'         => false,
							'entry_category'   => true,
							'human_time'       => true,
							'featured_classes' => 'ratio-v2',
							'entry_meta'       => [ 'update' ],
					],
							$_query );
					?>
				</div>
			<?php endif;
			$response['content'] = ob_get_clean();

			wp_reset_postdata();

			if ( empty( $response['content'] ) ) {
				$response['content'] = '<span class="is-meta empty-notification">' . foxiz_html__( 'Stay Tuned! Check back later for the latest updates.', 'foxiz' ) . '</span>';
			}

			wp_send_json( $response );
		}

		public function voting() {

			if ( empty( $_GET['pid'] ) || ! class_exists( 'Foxiz_Personalize_Helper' ) || empty( $_GET['value'] ) ) {
				wp_send_json_error();
			}

			$post_id  = absint( $_GET['pid'] );
			$reaction = esc_attr( $_GET['value'] );

			switch ( $reaction ) {
				case 'like':
					Foxiz_Personalize_Helper::get_instance()->save_vote( 'like', $post_id );
					break;
				case 'dislike':
					Foxiz_Personalize_Helper::get_instance()->save_vote( 'dislike', $post_id );
					break;
				case 'rmlike' :
					Foxiz_Personalize_Helper::get_instance()->delete_vote( 'like', $post_id );
					break;
				case 'rmdislike' :
					Foxiz_Personalize_Helper::get_instance()->delete_vote( 'dislike', $post_id );
					break;
			}

			wp_send_json_success( $post_id );
		}
	}
}

/** load */
Foxiz_Ajax::get_instance();