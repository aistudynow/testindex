<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

add_filter( 'nav_menu_item_title', 'foxiz_add_menu_title', 10, 4 );

if ( ! function_exists( 'foxiz_add_menu_title' ) ) {
	function foxiz_add_menu_title( $title, $item, $args, $depth ) {

		$settings = rb_get_cached_nav_item_meta( 'foxiz_menu_meta', $item->ID );

		$output = '<span>';
		if ( ! empty( $settings['icon'] ) ) {
			$output .= '<i class="menu-item-icon ' . esc_attr( $settings['icon'] ) . '" aria-hidden="true"></i>';
		} elseif ( ! empty( $settings['icon_id'] ) ) {
			$output .= '<span class="menu-item-svg">' . foxiz_get_svg_content( $settings['icon_id'] ) . '</span>';
		} elseif ( ! empty( $settings['icon_image'] ) ) {
			if ( ! empty( $settings['dark_icon_image'] ) ) {
				$output .= '<span class="menu-item-svg">';
				$output .= '<img decoding="async" data-mode="default" height="256" width="256" src="' . esc_url( $settings['icon_image'] ) . '" alt="' . esc_attr( $title ) . '">';
				$output .= '<img decoding="async" data-mode="dark" height="256" width="256" src="' . esc_url( $settings['dark_icon_image'] ) . '" alt="' . esc_attr( $title ) . '">';
				$output .= '</span>';
			} else {
				$output .= '<span class="menu-item-svg"><img decoding="async" class="icon-svg" height="256" width="256" src="' . esc_url( $settings['icon_image'] ) . '" alt="' . esc_attr( $title ) . '"></span>';
			}
		}
		$output .= $title;
		if ( ! empty( $settings['sub_label'] ) ) {
			$output .= '<span class="menu-sub-title meta-text">' . esc_html( $settings['sub_label'] ) . '</span>';
		}
		$output .= '</span>';

		return $output;
	}
}

if ( ! class_exists( 'Foxiz_Walker_Nav_Menu', false ) ) {
	class Foxiz_Walker_Nav_Menu extends Walker_Nav_Menu {

		static function get_menu_id( $args ) {

			if ( ! empty( $args->menu->term_id ) ) {
				return intval( $args->menu->term_id );
			} elseif ( ! empty( $args->menu ) ) {
				$menu = wp_get_nav_menu_object( $args->menu );

				if ( empty( $menu->term_id ) ) {
					return false;
				}

				return $menu->term_id;
			}

			return false;
		}

		public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {

			$settings = rb_get_cached_nav_item_meta( 'foxiz_menu_meta', $item->ID );

			if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
				$t = '';
				$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

			$classes   = empty( $item->classes ) ? [] : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;
			if ( empty( $depth ) && 'category' === $item->object && ! empty( $settings['category'] ) ) {
				$classes[] = 'menu-item-has-children menu-has-child-mega is-child-wide';
				if ( ! empty ( $settings['layout'] ) ) {
					$classes[] = 'mega-hierarchical';
				}
			} elseif ( empty( $depth ) && ( 'custom' === $item->object ) && ! empty ( $settings['mega_shortcode'] ) ) {
				$classes[] = 'menu-item-has-children menu-has-child-mega menu-has-child-mega-template';
				if ( empty( $settings['mega_width'] ) ) {
					$classes[] = 'is-child-wide';
				}
			} elseif ( empty( $depth ) && ( 'custom' === $item->object ) && ( ! empty ( $settings['columns'] ) ) ) {
				$classes[] = 'menu-item-has-children menu-has-child-mega menu-has-child-mega-columns';
				if ( empty( $settings['mega_width'] ) ) {
					$classes[] = 'is-child-wide';
				}
				if ( ! empty( $settings['mega_shortcode'] ) ) {
					$settings['columns_per_row'] = 1;
				}
				if ( ! empty ( $settings['columns_per_row'] ) ) {
					$classes[] = 'layout-col-' . $settings['columns_per_row'];
				}
			}

			$args = apply_filters( 'nav_menu_item_args', $args, $item, $depth );

			$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

			$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
			$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

			$output .= $indent . '<li' . $id . $class_names . '>';

			$atts           = [];
			$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
			$atts['target'] = ! empty( $item->target ) ? $item->target : '';
			if ( '_blank' === $item->target && empty( $item->xfn ) ) {
				$atts['rel'] = 'noopener';
			} else {
				$atts['rel'] = $item->xfn;
			}
			$atts['href']         = ! empty( $item->url ) ? $item->url : '';
			$atts['aria-current'] = $item->current ? 'page' : '';

			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
					$value      = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}

			$title = apply_filters( 'the_title', $item->title, $item->ID );

			$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

			$item_output = $args->before;
			$item_output .= '<a' . $attributes . '>';
			$item_output .= $args->link_before . $title . $args->link_after;
			$item_output .= '</a>';
			$item_output .= $args->after;

			if ( empty( $depth ) && 'category' === $item->object && ! empty( $settings['category'] ) ) {
				/** mega category */
				$mega_category_classes = 'mega-dropdown is-mega-category';
				if ( in_array( 'menu-item-has-children', $item->classes, true ) ) {
					$mega_category_classes .= ' mega-menu-has-children';
				}

				if ( empty( $settings['sub_scheme'] ) ) {
					if ( ! empty( $args->sub_scheme ) ) {
						$mega_category_classes .= ' ' . esc_attr( $args->sub_scheme );
					}
				} elseif ( '1' === (string) $settings['sub_scheme'] ) {
					$mega_category_classes .= ' light-scheme';
				}

				$inline_style = '';
				if ( ! empty( $settings['mega_background'] ) ) {
					$inline_style = 'style="--mega-bg:' . esc_attr( $settings['mega_background'] ) . ';"';
				}
				$item_output .= '<div class="' . esc_attr( $mega_category_classes ) . '" ' . $inline_style . '>';
				$item_output .= '<div class="rb-container edge-padding">';
				$item_output .= '<div class="mega-dropdown-inner">';
			} elseif ( empty( $depth ) && ( 'custom' === $item->object ) && ! empty ( $settings['mega_shortcode'] ) ) {

				if ( ! empty( $settings['mega_width'] ) ) {
					$mega_classes = 'flex-dropdown is-mega-template';
					$inline_style = 'style="width:' . absint( $settings['mega_width'] ) . 'px;';
					if ( ! empty( $settings['mega_left'] ) ) {
						$inline_style .= 'left:' . esc_attr( $settings['mega_left'] ) . 'px';
						$mega_classes .= ' mega-has-left';
					}
					$inline_style .= '"';
					$item_output  .= '<div class="' . $mega_classes . '" ' . $inline_style . '>';
				} else {
					$item_output .= '<div class="mega-dropdown is-mega-template">';
				}

				$item_output .= '<div class="mega-template-inner">';
			} elseif ( empty( $depth ) && ( 'custom' === $item->object ) && ! empty ( $settings['columns'] ) ) {

				/** mega columns */
				if ( ! empty( $settings['mega_width'] ) ) {
					$mega_classes = 'flex-dropdown is-mega-column';
					$inline_style = 'style="width:' . absint( $settings['mega_width'] ) . 'px;';
					if ( ! empty( $settings['mega_left'] ) ) {
						$inline_style .= 'left:' . esc_attr( $settings['mega_left'] ) . 'px';
						$mega_classes .= ' mega-has-left';
					}
					$inline_style .= '"';
					$item_output  .= '<div class="' . $mega_classes . '" ' . $inline_style . '>';
				} else {
					$item_output .= '<div class="mega-dropdown is-mega-column">';
				}

				$item_output .= '<div class="rb-container edge-padding">';
				$item_output .= '<div class="mega-dropdown-inner">';
			}

			/** filter */
			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}

		public function end_el( &$output, $item, $depth = 0, $args = null ) {

			$settings = rb_get_cached_nav_item_meta( 'foxiz_menu_meta', $item->ID );

			if ( empty( $depth ) && 'category' === $item->object && ! empty( $settings['category'] ) ) {
				$output .= $this->category_mega_menu( $item, $settings );
				$output .= '</div></div></div>';
			} elseif ( empty( $depth ) && ( 'custom' === $item->object ) && ! empty ( $settings['mega_shortcode'] ) ) {
				$output .= $this->column_mega_menu( $item, $settings );
				$output .= '</div></div>';
			} elseif ( empty( $depth ) && ( 'custom' === $item->object ) && ( ! empty ( $settings['columns'] ) ) ) {
				$output .= $this->column_mega_menu( $item, $settings );
				$output .= '</div></div></div>';
			}

			if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
				$t = '';
				$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$output .= "</li>{$n}";
		}

		/**
		 * @param $item
		 * @param $settings
		 *
		 * @return string
		 */
		public function category_mega_menu( $item, $settings ) {

			if ( ! empty ( $settings['layout'] ) ) {
				return $this->blog_hierarchical( $item );
			} else {
				return $this->blog_default( $item );
			}
		}

		/** mega category default */
		public function blog_default( $item ) {

			$output = '';

			if ( ! empty( $item->object_id ) ) {

				$output .= '<div class="mega-header mega-header-fw">';
				$output .= '<span class="h4">' . esc_html( $item->title ) . '</span>';
				$output .= '<a class="mega-link is-meta" href="' . esc_url( $item->url ) . '"';
				if ( ! empty( $item->target ) ) {
					$output .= 'target="' . $item->target . '"';
				}
				$output .= '><span>' . foxiz_html__( 'Show More', 'foxiz' ) . '</span><i class="rbi rbi-cright" aria-hidden="true"></i></a>';
				$output .= '</div>';

				$output .= foxiz_get_grid_small_1( [
						'uuid'           => 'mega-listing-' . $item->ID,
						'columns'        => 5,
						'posts_per_page' => 5,
						'title_tag'      => 'div',
						'crop_size'      => 'foxiz_crop_g1',
						'title_classes'  => 'h4',
						'review'         => 'replace',
						'entry_meta'     => 'update',
						'entry_category' => '-1',
						'excerpt_length' => '-1',
						'category'       => $item->object_id,
				] );
			}

			return $output;
		}

		/**
		 * @param $item
		 *
		 * @return string
		 */
		public function blog_hierarchical( $item ) {

			$output = '';

			if ( ! empty( $item->object_id ) ) {
				$data        = rb_get_term_meta( 'foxiz_category_meta', $item->object_id );
				$description = term_description( $item->object_id );
				$featured    = '';
				if ( isset( $data['featured_image'] ) ) {
					$featured = $data['featured_image'];
				}

				$output .= '<div class="mega-col mega-col-intro">';
				$output .= '<div class="h3">';
				$output .= '<a class="p-url" href="' . esc_url( $item->url ) . '"';
				if ( ! empty( $item->target ) ) {
					$output .= 'target="' . $item->target . '"';
				}
				$output .= '>' . esc_html( $item->title ) . '</a></div>';
				$output .= '<div class="category-hero-wrap">' . foxiz_get_category_hero( $featured ) . '</div>';
				if ( ! empty( $description ) ) {
					$output .= '<div class="cbox-description">' . wp_trim_words( $description, 25 ) . '</div>';
				}
				$output .= '<a class="mega-link p-readmore" href="' . esc_url( $item->url ) . '"';
				if ( ! empty( $item->target ) ) {
					$output .= 'target="' . $item->target . '"';
				}
				$output .= '><span>' . foxiz_html__( 'Show More', 'foxiz' ) . '</span><i class="rbi rbi-cright" aria-hidden="true"></i></a>';
				$output .= '</div>';

				$output .= '<div class="mega-col mega-col-trending">';
				$output .= '<div class="mega-header">';
				$output .= '<i class="rbi rbi-trending" aria-hidden="true"></i><span class="h4">' . foxiz_html__( 'Top News', 'foxiz' ) . '</span>';
				$output .= '</div>';

				$output .= foxiz_get_list_small_2( [
						'uuid'           => 'mega-listing-trending-' . $item->ID,
						'posts_per_page' => 3,
						'title_tag'      => 'div',
						'title_classes'  => 'h4',
						'category'       => $item->object_id,
						'order'          => 'comment_count',
						'review'         => 'replace',
						'entry_category' => '-1',
						'excerpt_length' => '-1',
						'entry_meta'     => 'update',
						'entry_format'   => 'bottom',
						'readmore'       => '-1',
				] );

				$output .= '</div>';

				$output .= '<div class="mega-col mega-col-latest">';
				$output .= '<div class="mega-header">';
				$output .= '<i class="rbi rbi-clock" aria-hidden="true"></i><span class="h4">' . foxiz_html__( 'Latest News', 'foxiz' ) . '</span>';
				$output .= '</div>';

				$output .= foxiz_get_list_small_1( [
						'uuid'               => 'mega-listing-latest-' . $item->ID,
						'posts_per_page'     => 4,
						'title_tag'          => 'div',
						'title_classes'      => 'h4',
						'category'           => $item->object_id,
						'review'             => 'replace',
						'entry_format'       => 'false',
						'review_description' => '-1',
						'entry_category'     => '-1',
						'entry_meta'         => 'update',
						'excerpt_length'     => '-1',
						'readmore'           => '-1',
						'bottom_border'      => 'gray',
						'last_bottom_border' => '-1',
				] );

				$output .= '</div>';
			}

			return $output;
		}

		/**
		 * @param $item
		 * @param $settings
		 *
		 * @return false|string
		 */
		public function column_mega_menu( $item, $settings ) {

			ob_start();

			if ( ! empty( $settings['mega_shortcode'] ) ) : ?>
				<?php echo do_shortcode( stripslashes( $settings['mega_shortcode'] ) ); ?>
			<?php elseif ( ! empty( $settings['columns'] ) && is_active_sidebar( $settings['columns'] ) ) : ?>
				<div class="mega-columns">
					<?php dynamic_sidebar( $settings['columns'] ); ?>
				</div>
			<?php endif;

			return ob_get_clean();
		}
	}
}