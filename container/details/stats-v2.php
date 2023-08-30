<?php
if ( ! defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) ) {
	define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
}
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', false );
}
if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
	define( 'WP_DEBUG_DISPLAY', false );
}

function wpd_platform_base_dir() {
	$path = dirname( __FILE__ );
	while ( true ) {
		if ( file_exists( $path . '/wp-config.php' ) ) {
			return $path . '/';
		}
		$path = dirname( $path );
	}
}

// Require the wp-load.php file (which loads wp-config.php and bootstraps WordPress).
require wpd_platform_base_dir() . '/wp-load.php';

if ( ! isset( $_GET['full'] ) ) {
	define( 'SHORTINIT', true );
}

// Restrict access to page.
\WPD_Platform\Plugin::instance()->get_host()->base_access();

$site_data = \WPD_Platform\Services\StatsService::instance()->get( isset( $_GET['full'] ) );

echo json_encode( $site_data, JSON_PRETTY_PRINT );
