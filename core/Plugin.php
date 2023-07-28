<?php

namespace WPD_Platform;

use WPD_Platform\Factories\ExternalHost;
use WPD_Platform\Factories\InternalHost;
use \WP_CLI;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class Plugin {

	private $host;

	/**
	 * Setup and return the singleton pattern.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
			$instance->setup_globals();
			$instance->includes();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once.
	 */
	private function __construct() {

		new Hooks();
	}

	/** Private methods *************************************************/

	/**
	 * Setup default class globals.
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/* Versions **********************************************************/

		$this->version = PLATFORM_VERSION;

		/* Paths *************************************************************/

		// Setup some base path and URL information
		$this->file       = __FILE__;
		$this->basename   = PLATFORM_PLUGIN_SLUG;
		$this->plugin_dir = PLATFORM_PLUGIN_DIR;
		$this->plugin_url = PLATFORM_PLUGIN_URL;

		// Includes
		$this->includes_dir = PLATFORM_PLUGIN_DIR . 'includes';
		$this->includes_url = PLATFORM_PLUGIN_URL . 'includes';

		/* Misc **************************************************************/

		$this->extend = new \stdClass();
		$this->domain = PLATFORM_PLUGIN_SLUG;
	}


	/**
	 * Include the required files.
	 *
	 * @since 1.0.0
	 */
	private function includes() {

		// Set up the host.
		$this->get_host();

		// If WP-CLI is available, load the CLI commands and bail out.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'dollie', '\WPD_Platform\CLI' );

			return;
		}

		// Set up Functionality.
		require $this->plugin_dir . 'setup/platform.php';
		require $this->plugin_dir . 'setup/misc.php';

		new Authentication();

		// Custom plugin updates only when installed as plugin on external site.
		if ( ! $this->is_wpmu() && $this->get_host()->is_type_external()
		     && file_exists( $this->plugin_dir . 'includes/plugin-update-checker/plugin-update-checker.php' ) ) {

			require $this->plugin_dir . 'includes/plugin-update-checker/plugin-update-checker.php';
			PucFactory::buildUpdateChecker(
				'https://control.getdollie.com/releases/?action=get_metadata&slug=platform',
				PLATFORM_PLUGIN_FILE, // Full path to the main plugin file or functions.php.
				'platform'
			);
		}

		// Check if we should disable update checks
		if ( ! defined( 'OSDWPUVERSION' ) && file_exists( $this->plugin_dir . 'includes/disable-wordpress-updates/disable-updates.php' ) && get_option( 'dollie_disable_updates' )
		) {
			require $this->plugin_dir . 'includes/disable-wordpress-updates/disable-updates.php';
		}

		// Load Platform Cache.
		if ( ! defined( 'POWERED_CACHE_VERSION' )
		     && $this->should_load_platform_cache()
		     && file_exists( $this->plugin_dir . 'includes/powered-cache/powered-cache.php' )
		) {
			require $this->plugin_dir . 'includes/powered-cache/powered-cache.php';
			update_option( 'dollie_caching_method', 'powered-cache' );
		}
	}

	private function setup_actions() {

		// Load textdomain.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 *
	 * @uses wp_register_style()
	 * @uses wp_enqueue_script()
	 * @uses wp_enqueue_style()
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'platform-alert', WPMU_PLUGIN_URL . '/platform/assets/js/sweetalert.min.js', false, PLATFORM_VERSION );
		wp_register_style( 'platform-alert-style', WPMU_PLUGIN_URL . '/platform/assets/css/sweetalert.css', false, PLATFORM_VERSION );
		wp_enqueue_style( 'platform-style' );
		wp_enqueue_style( 'platform-alert-style' );
	}

	/**
	 * Decide if we should load platform email overrides. Checks for installed SMTP plugins
	 *
	 * @return bool
	 */
	public function should_load_email_overrides() {
		global $wp_filter;

		$exceptions           = [ 'LogEmailsPlugin', 'wp_staticize_emoji_for_email' ];
		$load_email_overrides = true;

		foreach ( $wp_filter as $key => $val ) {
			if ( false !== strpos( $key, 'phpmailer_init' ) || false !== strpos( $key, 'wp_mail' ) ) {

				foreach ( $val->callbacks as $callback ) {
					foreach ( $callback as $item ) {

						// we have a class
						if ( is_array( $item['function'] ) ) {
							$name = get_class( $item['function'][0] );
						} else {
							$name = $item['function'];
						}

						if ( is_string( $name ) && in_array( $name, $exceptions, true ) ) {
							continue;
						}

						// We have a plugin to handle email delivery
						$load_email_overrides = false;
					}
				}
			}
		}

		return $load_email_overrides;
	}

	/**
	 * @return bool
	 */
	private function should_load_platform_cache() {
		if ( $this->get_host()->is_type_external() ) {
			return false;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$load_platform_cache = true;
		$plugins             = array(
			'hummingbird-performance'           => 'hummingbird-performance/wp-hummingbird.php',
			'wp-rocket'                         => 'wp-rocket/wp-rocket.php',
			'w3-total-cache'                    => 'w3-total-cache/w3-total-cache.php',
			'wp-super-cache'                    => 'wp-super-cache/wp-cache.php',
			'hyper-cache'                       => 'hyper-cache/plugin.php',
			'hyper-cache-extended'              => 'hyper-cache-extended/plugin.php',
			'wp-fast-cache'                     => 'wp-fast-cache/wp-fast-cache.php',
			'flexicache'                        => 'flexicache/wp-plugin.php',
			'wp-fastest-cache'                  => 'wp-fastest-cache/wpFastestCache.php',
			'wp-http-compression'               => 'wp-http-compression/wp-http-compression.php',
			'wordpress-gzip-compression'        => 'wordpress-gzip-compression/ezgz.php',
			'gzip-ninja-speed-compression'      => 'gzip-ninja-speed-compression/gzip-ninja-speed.php',
			'speed-booster-pack'                => 'speed-booster-pack/speed-booster-pack.php',
			'wp-performance-score-booster'      => 'wp-performance-score-booster/wp-performance-score-booster.php',
			'check-and-enable-gzip-compression' => 'check-and-enable-gzip-compression/richards-toolbox.php',
		);

		foreach ( $plugins as $file ) {
			if ( is_plugin_active( $file ) ) {
				$load_platform_cache = false;
				break;
			}
		}

		return $load_platform_cache;
	}

	public function get_host() {
		if ( empty( $this->host ) ) {
			if ( defined( 'S5_APP_ID' ) ) {
				$this->host = new InternalHost();
			} else {
				$this->host = new ExternalHost();
			}
		}

		return $this->host;
	}

	/**
	 * If it is a regular plugin or a must-use plugin.
	 *
	 * @return bool
	 */
	public function is_wpmu() {
		return defined( 'WPD_PLATFORM_IS_MU' ) && WPD_PLATFORM_IS_MU;
	}


	public function activate_plugin() {
		if ( ! $this->is_wpmu() ) {
			$this->get_host()->register_site();
		}
	}

}
