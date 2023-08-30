<?php

namespace WPD_Platform;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Authentication
 *
 * @package Dollie\Platform
 */
class Authentication {

	/**
	 * AutoLogin constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'do_autologin' ], - 1 );
		add_action( 'login_message', [ $this, 'do_login_fail' ], - 1 );
	}

	public function do_login_fail( $message ) {
		if ( empty( $message ) && isset( $_GET['token-expired'] ) ) {
			return '<div class="notice notice-error" style="background: #dd7575;padding: 10px;color: #FFF;font-weight: 400;">' .
				   '<h3>Login Failed</h3><br' .
				   '>Sorry, you already logged in using this link. Please refresh your Site Dashboard to get a new one-time login link.' .
				   '</div>';
		}

		return $message;
	}

	/**
	 * Auto login logic
	 */
	public function do_autologin() {
		if ( ! isset( $_GET['s5token'] ) ) {
			return;
		}

		if ( get_option( 'wfp_flush_new_install' ) !== 'yes' ) {

			// Clear Object Cache.
			wp_cache_flush();
			update_option( 'wfp_flush_new_install', 'yes' );
		}

		// Check token.
		$meta_key    = 'wpd_login_token';
		$found_users = get_users(
			array(
				'meta_key'   => $meta_key,
				'meta_value' => sanitize_text_field( $_GET['s5token'] ),
				'number'     => 1,
			)
		);

		if ( $found_users ) {

			// Just one entry.
			foreach ( $found_users as $user ) {

				// Regenerate the token.
				update_user_meta( $user->ID, $meta_key, sha1( mt_rand( 1, 90000 ) . 'WPDSALT' ) );

				// Login as this user.
				wp_clear_auth_cookie();
				wp_set_current_user( $user->ID, $user->user_login );
				wp_set_auth_cookie( $user->ID, true, is_ssl() );

				if ( is_user_logged_in() ) {
					if ( $_GET['location'] ) {
						wp_safe_redirect( trailingslashit( get_site_url() ) . $_GET['location'] );
					} else {
						wp_safe_redirect( admin_url() );
					}
					exit;
				}
			}
		} else {
			wp_redirect( wp_login_url() . '?token-expired' );
			exit;
		}
	}
}
