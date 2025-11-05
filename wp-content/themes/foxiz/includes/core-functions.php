<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'foxiz_get_file_path' ) ) {
	function foxiz_get_file_path( $file = '' ) {

		$file = ltrim( $file, '/' );
		if ( file_exists( FOXIZ_CHILD_THEME_DIR . $file ) ) {
			return FOXIZ_CHILD_THEME_DIR . $file;
		} elseif ( file_exists( FOXIZ_THEME_DIR . $file ) ) {
			return FOXIZ_THEME_DIR . $file;
		}

		return false;
	}
}

if ( ! function_exists( 'foxiz_get_file_uri' ) ) {
	function foxiz_get_file_uri( $file = '' ) {

		$file = ltrim( $file, '/' );
		if ( file_exists( FOXIZ_CHILD_THEME_DIR . $file ) ) {
			return FOXIZ_CHILD_THEME_URI . $file;
		}

		return FOXIZ_THEME_URI . $file;
	}
}

if ( ! function_exists( 'foxiz_get_option' ) ) {
	/**
	 * @param string $option_name
	 * @param false $default
	 *
	 * @return false|mixed|void
	 */
	function foxiz_get_option( $option_name = '', $default = false ) {

		if ( ! isset( $GLOBALS[ FOXIZ_TOS_ID ] ) ) {
			$GLOBALS[ FOXIZ_TOS_ID ] = get_option( FOXIZ_TOS_ID, [] );
		}

		if ( ! $option_name ) {
			return (array) $GLOBALS[ FOXIZ_TOS_ID ];
		}

		return ! empty( $GLOBALS[ FOXIZ_TOS_ID ][ $option_name ] ) ? $GLOBALS[ FOXIZ_TOS_ID ][ $option_name ] : $default;
	}
}

if ( ! function_exists( 'wp_body_open' ) ) {
	/** ensuring backward compatibility with versions of WordPress older than 5.2. */
	function wp_body_open() {

		do_action( 'wp_body_open' );
	}
}

if ( ! function_exists( 'foxiz_convert_to_id' ) ) {
	function foxiz_convert_to_id( $name ) {

		$name = strtolower( strip_tags( $name ) );
		$name = str_replace( ' ', '-', $name );
		$name = preg_replace( '/[^A-Za-z0-9\-]/', '', $name );

		return substr( $name, 0, 20 );
	}
}
