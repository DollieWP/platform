<?php
/*
Description: Functions to improve platform performance and security. Thanks to WPEngine and Pantheon go out for
laying the groundwork.
Version: 0.0.4
License: GPLv2
 */

add_action(
	'init',
	function () {
		if ( isset( $_GET['S5_HEALTH_CHECK'] ) ) {
			http_response_code( 299 );
			exit;
		}
	}
);

add_filter( 'pre_comment_content', 'pf_die_on_long_comment', 9999 );
function pf_die_on_long_comment( $text ) {
	if ( strlen( $text ) > 13000 ) {
		/** @noinspection ForgottenDebugOutputInspection */
		wp_die(
			'This comment is longer than the maximum allowed size and has been dropped.',
			'Comment Declined',
			array( 'response' => 413 )
		);
	}

	return $text;
}

// example: password has been changed, but Object Cache still holds old password, and therefore prevents login
if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
	add_filter( 'pf_authenticate_user', 'pf_refresh_user' );
	function pf_refresh_user( $user ) {
		wp_cache_delete( $user->user_login, 'userlogins' );

		return get_user_by( 'login', $user->user_login );
	}
}

add_action( 'template_redirect', 'pf_woo_logout', 10, 2 );
function pf_woo_logout() {
	if ( class_exists( 'woocommerce' ) ) {
		// Get current URL
		if (
			( isset( $_SERVER['HTTPS'] ) &&
			  ( $_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1 ) ) ||
			( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
			  $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
		) {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}
		$uri_parts          = explode( '?', $_SERVER['REQUEST_URI'], 2 );
		$url                = $protocol . $_SERVER['SERVER_NAME'] . $uri_parts[0];
		$logout_endpoint    = get_option( 'woocommerce_logout_endpoint' );
		$myaccount_endpoint = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );

		// If current URL is the WooCommerce log-out URL force logout.
		if ( strpos( $url, $myaccount_endpoint . $logout_endpoint ) !== false ) {
			wp_logout();
		}
	}
}
