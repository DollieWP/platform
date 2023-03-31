<?php
/**
 * Plugin Name: Platform Worker
 * Description: This plugin powers our management platform.
 * Version:     3.0.9
 * Text Domain: platform
 * Domain Path: /languages/
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Bail out if the plugin is already loaded
if ( defined( 'PLATFORM_VERSION' ) ) {
	return;
}

define( 'PLATFORM_PLUGIN_SLUG', 'platform' );
define( 'PLATFORM_VERSION', '3.0.9' );

if ( defined( 'WPD_PLATFORM_IS_MU' ) && WPD_PLATFORM_IS_MU ) {
	if ( ! defined( 'PLATFORM_PLUGIN_DIR' ) ) {
		define( 'PLATFORM_PLUGIN_DIR', WPMU_PLUGIN_DIR . '/platform/' );
	}
	define( 'PLATFORM_PLUGIN_URL', WPMU_PLUGIN_URL . '/platform/' );
} else {
	define( 'PLATFORM_PLUGIN_FILE', __FILE__ );
	if ( ! defined( 'PLATFORM_PLUGIN_DIR' ) ) {
		define( 'PLATFORM_PLUGIN_DIR', plugin_dir_path( PLATFORM_PLUGIN_FILE ) );
	}
	define( 'PLATFORM_PLUGIN_URL', plugins_url( '/', PLATFORM_PLUGIN_FILE ) );
}

// Autoload
require_once 'bootstrap.php';

/**
 * Load our Platform Code
 */
add_action( 'plugins_loaded', 'pf_load_platform_code', 11 );
function pf_load_platform_code() {
	\WPD_Platform\Plugin::instance();
}

// Register the plugin activation hook.
register_activation_hook( __FILE__, array( \WPD_Platform\Plugin::instance(), 'activate_plugin' ) );

define( 'WPD_PARTNER_ID', '[[partnerId]]' );
