<?php

namespace WPD_Platform;

class Whitelabel extends Singleton {

	const DB_OPTION = 'wpd_whitelabel';

	/**
	 * Get whitelabel string from DB.
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public function get_text( $string ) {
		$strings = get_option( self::DB_OPTION, [] );

		return $strings[ $string ] ?? $string;
	}

	/**
	 * Filter plugin_row_meta strings
	 *
	 * @param $plugin_name
	 * @param $plugin_data
	 *
	 * @return mixed|string
	 */
	public function whitelabel( $plugin_name, $plugin_data ) {

		if ( isset( $plugin_data['Name'] ) && $plugin_data['Name'] === 'Platform Worker' ) {
			$plugin_name = $this->get_text( 'Platform Worker' );
		}

		return $plugin_name;
	}

	/**
	 * Save the whitelabel data
	 *
	 * @return void
	 */
	public function after_register_site() {
		$current_strings = get_option( self::DB_OPTION, [] );
		$whitelabel_data = [];

		if ( defined( 'WPD_WHITELABEL' ) && ! empty( WPD_WHITELABEL ) && strpos( WPD_WHITELABEL, '[[' ) === false ) {
			$whitelabel_data = @json_decode( WPD_WHITELABEL, true );
		}

		if ( ! is_array( $whitelabel_data ) ) {
			return;
		}

		foreach ( $whitelabel_data as $string => $whitelabel ) {
				$current_strings[ $string ] = $whitelabel;
		}

		update_option( self::DB_OPTION, $current_strings );
	}
}
