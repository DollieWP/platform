<?php

namespace WPD_Platform\Services;

use WPD_Platform\Singleton;

class LoginService extends Singleton {

	/**
	 * Auto login logic
	 */
	public function do_autologin() {
		if ( ! isset( $_GET['s5token'] ) ) {
			return;
		}

		// make sure to redirect to wp site domain
		$current_url = $this->current_location();
		$site_url    = str_replace( [ 'http://', 'https://' ], '', home_url() );

		if ( strpos( $current_url, $site_url ) === false ) {
			$current_parse = parse_url( $current_url );
			$url           = str_replace( $current_parse['host'], $site_url, $current_url );
			wp_redirect( $url );
			exit;
		}

		// Check token

		$meta_key    = 'wpd_login_token';
		$found_users = get_users( array(
			'meta_key'   => $meta_key,
			'meta_value' => sanitize_text_field( $_GET['s5token'] ),
			'number'     => 1
		) );

		if ( $found_users ) {

			// Just one entry
			foreach ( $found_users as $user ) {

				// Regenerate the token
				update_user_meta( $user->ID, $meta_key, sha1( mt_rand( 1, 90000 ) . 'WPDSALT' ) );

				// Login as this user
				wp_clear_auth_cookie();
				wp_set_current_user( $user->ID, $user->user_login );
				wp_set_auth_cookie( $user->ID, true, is_ssl() );

				if ( is_user_logged_in() ) {
					if ( $_GET['location'] ) {
						wp_safe_redirect( get_site_url() . $_GET['location'] );
					} else {
						wp_safe_redirect( admin_url() );
					}
					exit;
				}

				if ( $_GET['location'] ) {
					wp_safe_redirect( get_site_url() . $_GET['location'] );
				} else {
					wp_safe_redirect( admin_url() );
				}
				exit;
			}
		}

	}

	private function current_location() {
		if ( ( isset( $_SERVER['HTTPS'] ) &&
		       ( $_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 ) ) ||
		     ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
		       $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) ) {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}

		return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Get login token used for one click login.
	 *
	 * @param null|string $username
	 *
	 * @return string
	 */
	public function get_login_token( $username = null ) {

		// Include the now instantiated global $wpdb Class for use
		global $wpdb;
		$wpdb->hide_errors();

		// based on user get or token
		// sql in wpdb_usermeta get by wpd_login_token
		// if not exists generate and save in usermeta

		$user     = false;
		$meta_key = 'wpd_login_token';

		if ( ! empty( $username ) ) {

			$username   = sanitize_text_field( $username );
			$user_query = $wpdb->prepare(
				"SELECT ID, user_login
				FROM {$wpdb->users}
				WHERE {$wpdb->users}.user_login = %s
				",
				$username
			);
			$user       = $wpdb->get_row( $user_query );
		}
		if ( empty( $user ) ) {
			$user_query = $wpdb->prepare(
				"SELECT u.ID, u.user_login
				FROM {$wpdb->users} u, {$wpdb->usermeta} m
				WHERE u.ID = m.user_id
				AND m.meta_key LIKE 'wp_capabilities'
				AND m.meta_value LIKE '%administrator%'"
			);
			$user       = $wpdb->get_row( $user_query );
		}

		// User exists.
		if ( $user ) {
			$user_id = $user->ID;

			$user_meta_query = $wpdb->prepare(
				"SELECT meta_value
				FROM {$wpdb->usermeta}
				WHERE {$wpdb->usermeta}.user_id = %d
				AND {$wpdb->usermeta}.meta_key = %s
				",
				$user_id,
				$meta_key
			);

			// Get from meta.
			if ( $user_meta = $wpdb->get_row( $user_meta_query ) ) {
				return $user_meta->meta_value;
			}

			$token = sha1( mt_rand( 1, 90000 ) . 'WPDSALT' );

			$wpdb->insert(
				$wpdb->usermeta,
				[
					'user_id'    => $user_id,
					'meta_key'   => $meta_key,
					'meta_value' => $token,
				],
				[ '%d', '%s', '%s' ]
			);

			if ( $wpdb->insert_id ) {
				return $token;
			}
		}

		return '';

	}

}
