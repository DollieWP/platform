<?php

namespace WPD_Platform\Factories;

use WPD_Platform\Plugin;

class InternalHost extends BaseHost {
	public function __construct() {
		parent::__construct();

		$this->host_type = self::TYPE_1;
		$token           = S5_APP_ID;

		$this->set_token( $token );
		$this->set_secret( S5_APP_SECRET_KEY );

		if ( ! defined( 'PLATFORM_INCLUDES_DIR' ) ) {
			define( 'PLATFORM_INCLUDES_DIR', '/usr/src/dollie/' );
		}

		// Load S5 code
		if ( file_exists( PLATFORM_INCLUDES_DIR . 's5.php' ) ) {
			require PLATFORM_INCLUDES_DIR . 's5.php';
		}

		// Load our Email Setup.
		if ( Plugin::instance()->should_load_email_overrides() && file_exists( PLATFORM_INCLUDES_DIR . 'email.php' ) ) {
			require_once PLATFORM_INCLUDES_DIR . 'email.php';
		}
	}

	public function base_access() {
		if ( ! defined( 'PLATFORM_WORDPRESS_DIR' ) ) {
			define( 'PLATFORM_WORDPRESS_DIR', '/usr/src/app' );
		}

		if ( file_exists( PLATFORM_INCLUDES_DIR . 'wf-config.php' ) ) {
			require_once PLATFORM_INCLUDES_DIR . 'wf-config.php';
		}

		$valid_passwords = [ 'container' => S5_APP_ID ];
		$valid_users     = array_keys( $valid_passwords );

		$user = $_SERVER['PHP_AUTH_USER'];
		$pass = $_SERVER['PHP_AUTH_PW'];

		$validated = in_array( $user, $valid_users ) && $pass == $valid_passwords[ $user ];

		if ( ! $validated ) {
			header( 'WWW-Authenticate: Basic realm="My Realm"' );
			header( 'HTTP/1.0 401 Unauthorized' );
			die( 'Not authorized' );
		}

		return $valid_passwords;
	}

	public function restrict_access() {
		if ( ! defined( 'PLATFORM_WORDPRESS_DIR' ) ) {
			define( 'PLATFORM_WORDPRESS_DIR', '/usr/src/app' );
		}

		if ( file_exists( PLATFORM_INCLUDES_DIR . 'wf-config.php' ) ) {
			require_once PLATFORM_INCLUDES_DIR . 'wf-config.php';
		}

		if ( $_GET['secret'] || $_GET['secret'] != S5_APP_SECRET_KEY ) {
			$cookie_options = array(
				'expires'  => time() + 60 * 30,
				'path'     => '/',
				'domain'   => parse_url( get_site_url(), PHP_URL_HOST ),
				// leading dot for compatibility or use subdomain
				'secure'   => true,
				// or false
				'httponly' => false,
				// or false
				'samesite' => 'None'
				// None || Lax || Strict
			);

			setcookie( 'wpd_allow_access', 1, $cookie_options );
		}

		if ( ! $_GET['secret'] || $_GET['secret'] != S5_APP_SECRET_KEY ) {
			$valid_passwords = [ $this->get_token() => S5_APP_SECRET_KEY ];
			$valid_users     = array_keys( $valid_passwords );

			$user = $_SERVER['PHP_AUTH_USER'];
			$pass = $_SERVER['PHP_AUTH_PW'];

			$validated = in_array( $user, $valid_users ) && $pass == $valid_passwords[ $user ];

			if ( ! $validated ) {
				header( 'WWW-Authenticate: Basic realm="My Realm"' );
				header( 'HTTP/1.0 401 Unauthorized' );
				die( 'Not authorized' );
			}

			return $valid_passwords;
		}
	}

	public function is_connected() {
		return true;
	}
}
