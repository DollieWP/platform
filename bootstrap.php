<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * PSR4 autoloader using Composer + fallback
 *
 * @since 1.0.0
 */
if ( file_exists( PLATFORM_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require PLATFORM_PLUGIN_DIR . 'vendor/autoload.php';
} else {

	/**
	 * Custom autoloader function for dollie plugin.
	 *
	 * @access private
	 *
	 * @param string $class_name Class name to load.
	 *
	 * @return bool True if the class was loaded, false otherwise.
	 */
	function _wpd_platform_autoload( $class_name ) {
		$namespace = 'WPD_Platform';

		if ( strpos( $class_name, $namespace . '\\' ) !== 0 ) {
			return false;
		}

		$parts = explode( '\\', substr( $class_name, strlen( $namespace . '\\' ) ) );

		$path = PLATFORM_PLUGIN_DIR . 'core';
		foreach ( $parts as $part ) {
			$path .= '/' . $part;
		}
		$path .= '.php';

		if ( ! file_exists( $path ) ) {
			return false;
		}

		require_once $path;

		return true;
	}

	spl_autoload_register( '_wpd_platform_autoload' );
}


