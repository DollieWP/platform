<?php

namespace WPD_Platform\Factories;

use WPD_Platform\Services\LoginService;

class ExternalHost extends BaseHost {
	public function __construct() {
		parent::__construct();
		$this->host_type = self::TYPE_2;
		$token           = $this->get_db_token();
		$this->set_token( $token );

		add_action( 'init', [ LoginService::instance(), 'do_autologin' ], - 1 );
		add_action( 'template_redirect', [ LoginService::instance(), 'do_autologin' ], 12 );

		$path = preg_replace( '/\/wp-content.*$/', '', __DIR__ );
		if ( ! defined( 'PLATFORM_WORDPRESS_DIR' ) ) {
			define( 'PLATFORM_WORDPRESS_DIR', $path );
		}

		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	public function base_access() {

		$valid_passwords = [ 'container' => $this->get_token() ];
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
		$path = preg_replace( '/\/wp-content.*$/', '', __DIR__ );
		if ( ! defined( 'PLATFORM_WORDPRESS_DIR' ) ) {
			define( 'PLATFORM_WORDPRESS_DIR', $path );
		}

		$valid_passwords = [ 'container' => $this->get_token() ];
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

	public function is_connected() {
		return get_option( 'wpd_connection_id' );
	}

	/**
     * Save site hash and partner hash in DB.
     *
	 * @param $site_id
	 *
	 * @return void
	 */
	public function setup_connection( $site_id ) {
		update_option( 'wpd_connection_id', $site_id );
		update_option( 'wpd_connection_partner_hash', $this->get_partner_hash() );

	}

	public function remove_connection() {
		delete_option( 'wpd_connection_id' );
		delete_option( self::TOKEN_OPTION );

		return true;
	}

    public function get_partner_hash() {
	    $partner_hash = '';

	    if ( defined( 'WPD_PARTNER_ID' ) && ! empty( WPD_PARTNER_ID ) ) {
            return WPD_PARTNER_ID;
	    }

	    return get_option( 'wpd_connection_partner_hash', $partner_hash ) ;
    }

	public function register_site() {

		if ( $this->is_connected() ) {
			return;
		}

		$current_user = wp_get_current_user();

		if ( $current_user === null ) {
			return;
		}

		$api_host = defined( 'WPD_WORKER_API_URL' ) ? WPD_WORKER_API_URL : self::API_URL;
		$response = wp_remote_post( $api_host . "external-sites/connect", [
			'headers' => array(
				'Authorization' => $this->get_token()
			),
			'body'      => [
				'partner_hash' => $this->get_partner_hash(),
				'name'         => get_bloginfo( 'name' ),
				'description'  => get_bloginfo( 'description' ),
				'uri'          => site_url(),
				'username'     => $current_user->user_login,
				'email'        => $current_user->user_email,
				'wp_version'   => get_bloginfo( 'version' ),
				'php_version'  => phpversion(),
			],
			'timeout'   => 30,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		] );

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return;
		}

		// Get the response data
		$response_data = wp_remote_retrieve_body( $response );

		if ( empty( $response_data ) ) {
			return;
		}

		$response_data = json_decode( $response_data );

		if ( ! is_object( $response_data ) ) {
			return;
		}

		$this->setup_connection( $response_data->id );
		set_transient( 'wpd_connection_status', 'success', 10 );

	}

	/**
	 * Display connection messages in admin.
	 * @return void
	 */
	public function admin_notices() {

		$transient = get_transient( 'wpd_connection_status' );

		if ( $transient === 'success' && $this->is_connected() ) {
			?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Your site has been connected to site management platform!', 'platform' ); ?></p>
            </div>
			<?php
		} elseif ( $transient === 'failure' ) {
			?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( 'There was an error connecting your site to management platform. Please contact support!', 'platform' ); ?></p>
            </div>
			<?php
		}
	}
}
