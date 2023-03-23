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

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
function wpd_platform_base_dir () {
    $path = dirname(__FILE__);
    while (true) {
        if (file_exists($path."/wp-config.php")) {
            return $path."/";
        }
        $path = dirname($path);
    }
}
// Require the wp-load.php file (which loads wp-config.php and bootstraps WordPress)
require wpd_platform_base_dir() . '/wp-load.php';


define( 'SHORTINIT', true );

// Restrict access to page.
if ( $_COOKIE['wpd_allow_access'] == false) {
    \WPD_Platform\Plugin::instance()->get_host()->restrict_access();
}


function adminer_object() {

	class AdminerSoftware extends Adminer {

		public function name() {
			return 'Database Admin';
		}

		private function replace_path_consts( $source, $path ) {
			$replacements = array(
				'__FILE__' => "'$path'",
				'__DIR__'  => "'" . dirname( $path ) . "'",
			);

			$old = array_keys( $replacements );
			$new = array_values( $replacements );

			return str_replace( $old, $new, $source );
		}

		private function get_wp_config_code() {
			$wp_config_path    = wpd_platform_base_dir() . '/wp-config.php';
			$wp_config_code    = explode( "\n", file_get_contents( $wp_config_path ) );
			$found_wp_settings = false;
			$lines_to_run      = array();

			foreach ( $wp_config_code as $line ) {
				if ( preg_match( '/^\s*require.+wp-settings\.php/', $line ) ) {
					$found_wp_settings = true;
					continue;
				}

				$lines_to_run[] = $line;
			}

			if ( ! $found_wp_settings ) {
				die( 'Strange wp-config.php file: wp-settings.php is not loaded directly.' );
			}

			$source = implode( "\n", $lines_to_run );
			$source = $this->replace_path_consts( $source, $wp_config_path );

			return preg_replace( '|^\s*\<\?php\s*|', '', $source );
		}

		public function credentials() {
			eval( $this->get_wp_config_code() );

			return array( DB_HOST, DB_USER, DB_PASSWORD );
		}

		public function database() {
			return DB_NAME;
		}

		public function loginForm() {
			eval( $this->get_wp_config_code() );

			?>
			<table cellspacing="0">
				<tr>
					<th>System
					<td>
						<select name='auth[driver]'>
							<option value="server" selected>MySQL
						</select>

				<tr>
					<th>Database
					<td><input name="auth[db]" value="<?php echo DB_NAME; ?>" readonly>
			</table>
			<input type="hidden" name="auth[password]" value="x">
			<input type="hidden" name="auth[username]" id="username" value="x">
			<input type="hidden" name="auth[server]" value="<?php echo DB_HOST; ?>">
			<p><input type="submit" value="<?php echo lang( 'Login' ); ?>">

			<?php
			echo checkbox( 'auth[permanent]', 1, $_COOKIE['adminer_permanent'], lang( 'Permanent login' ) ) . "\n";

			return true;
		}
	}

	return new AdminerSoftware();
}

require './adminer.php';
