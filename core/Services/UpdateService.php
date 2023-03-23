<?php

namespace WPD_Platform\Services;

use WPD_Platform\Singleton;

class UpdateService extends Singleton {

	/**
	 * Stores the last error that happened during any upgrade/install process.
	 *
	 * @var array With elements 'code' and 'message'.
	 */
	protected $error = false;

	/**
	 * Stores the log from any upgrade/install process.
	 *
	 * @var array
	 */
	protected $log = false;

	/**
	 * Stores the new version after from any upgrade process.
	 *
	 * @var array
	 */
	protected $new_version = false;

	/**
	 * Tracks core update results during processing.
	 *
	 * @var array
	 * @access protected
	 */

	private $site_plugins = null;

	protected $update_results = array();

	/**
	 * @param $slug
	 *
	 * @return string
	 */
	private function get_full_plugin_path( $slug ) {

		// get site plugins.
		if ( empty( $this->site_plugins ) ) {
			$this->site_plugins = get_plugins();
		}

		foreach ( $this->site_plugins as $k => $plugin ) {
			$path = explode( '/', $k );
			if ( $slug === $path[0] ) {
				return $k;
			}
		}

		return $slug;
	}

	/**
	 * Handle upgrade of a single item (plugin/theme).
	 *
	 * @param string $file Item file name.
	 * @param string $type Type (plugin/theme).
	 *
	 * @return array
	 */
	private function process_upgrade( $file, $type ) {
		$response = array(
			'error'       => array(),
			'success'     => false,
			'log'         => false,
			'new_version' => false,
		);

		// Make sure all required files are loaded.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Skin class.
		$skin = new \WP_Ajax_Upgrader_Skin();

		switch ( $type ) {
			case 'plugin':

				$file = $this->get_full_plugin_path( $file );

				// checks for available updates.
				wp_update_plugins();

				// Store the activation status.
				$active_blog    = is_plugin_active( $file );
				$active_network = is_multisite() && is_plugin_active_for_network( $file );

				// Plugin upgrader class.
				$upgrader = new \Plugin_Upgrader( $skin );

				// Run the upgrade process.
				$result = $upgrader->upgrade( $file );

				/*
				 * Note: The following plugin activation is an intended and
				 * needed step. During upgrade() WordPress deactivates the
				 * plugin network- and site-wide. By default the user would
				 * see a upgrade-results page with the option to activate the
				 * plugin again. We skip that screen and restore original state.
				 */
				if ( $active_blog ) {
					activate_plugin( $file, false, false, true );
				}
				if ( $active_network ) {
					activate_plugin( $file, false, true, true );
				}
				break;

			case 'theme':

				// Update the update transient.
				wp_update_themes();

				// Theme upgrader class.
				$upgrader = new \Theme_Upgrader( $skin );

				// Run the upgrade process.
				$result = $upgrader->upgrade( $file );
				break;

			default:
				// Return error for other types.
				$response['error']['code']    = 'UPG.08';
				$response['error']['message'] = __( 'Invalid upgrade call', 'platform' );

				return $response;
		}

		// Reset cache.
		$this->wp_opcache_reset();

		// Set the upgrade log.
		$response['log'] = $skin->get_upgrade_messages();

		// Handle different types of errors.
		if ( is_wp_error( $skin->result ) ) {
			if ( in_array( $skin->result->get_error_code(), array(
				'remove_old_failed',
				'mkdir_failed_ziparchive'
			), true ) ) {
				$response['error']['code']    = 'UPG.10';
				$response['error']['message'] = $skin->get_error_messages();
			} else {
				$response['error']['code']    = 'UPG.04';
				$response['error']['message'] = $skin->result->get_error_message();
			}

			return $response;
		}

		if ( in_array( $skin->get_errors()->get_error_code(), array(
			'remove_old_failed',
			'mkdir_failed_ziparchive'
		), true ) ) {
			$response['error']['code']    = 'UPG.10';
			$response['error']['message'] = $skin->get_error_messages();

			return $response;
		}

		if ( $skin->get_errors()->get_error_code() ) {
			$response['error']['code']    = 'UPG.09';
			$response['error']['message'] = $skin->get_error_messages();

			return $response;
		}

		if ( false === $result ) {
			global $wp_filesystem;

			$response['error']['code']    = 'UPG.05';
			$response['error']['message'] = __( 'Unable to connect to the filesystem. Please confirm your credentials', 'platform' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$response['error']['message'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			return $response;
		}

		if ( true === $result ) {

			// Upgrade is success.
			$response['success'] = true;

			// Get the new version.
			if ( 'plugin' === $type ) {
				/**
				 * Filter to set new plugin version number.
				 *
				 * If you return something other than empty, we won't check for plugin data imagining
				 * that the data is already given.
				 *
				 * @param array $plugin_data Plugin data.
				 * @param string $file Plugin file.
				 *
				 */
				$plugin_data = apply_filters( 'wpd_platform_upgrader_get_plugin_data', array(), $file );

				if ( empty( $plugin_data ) ) {
					// Get new plugin data.
					$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $file );
				}

				// Set new plugin version.
				$response['new_version'] = $plugin_data['Version'];
			} else {
				// Get theme data.
				$theme = wp_get_theme( $file );
				// Set new version.
				$response['new_version'] = $theme->get( 'Version' );
			}

			return $response;
		}

		// An unhandled error occurred.
		$response['error']['code']    = 'UPG.06';
		$response['error']['message'] = __( 'Update failed for an unknown reason', 'platform' );

		return $response;
	}

	/**
	 * Download and install a single plugin/theme update.
	 *
	 * A lot of logic is borrowed from ajax-actions.php
	 *
	 * @param int|string $pid The project ID or a plugin slug.
	 *
	 * @return bool True on success.
	 * @since  4.0.0
	 *
	 */
	public function upgrade( $pid ) {
		$this->clear_error();
		$this->clear_log();
		$this->clear_version();

		if ( is_string( $pid ) ) {
			// No need to check if the plugin exists/is installed. WP will check it.
			list( $type, $filename ) = explode( ':', $pid );
		} else {
			// Can not continue.
			$this->set_error( $pid, 'UPG.07', __( 'Invalid upgrade call', 'platform' ) );

			return false;
		}

		// Permission check.
		if ( ! $this->can_auto_install( $type ) ) {
			$this->set_error( $pid, 'UPG.10', __( 'Insufficient filesystem permissions', 'platform' ) );

			return false;
		}

		// For plugins_api/themes_api.
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		include_once ABSPATH . 'wp-admin/includes/theme-install.php';
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		include_once ABSPATH . 'wp-admin/includes/theme.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';


		// Upgrade the item.
		$result = $this->process_upgrade( $filename, $type );

		// Set the upgrade log.
		$this->log = empty( $result['log'] ) ? false : $result['log'];

		if ( empty( $result['success'] ) ) {
			$this->set_error( $pid, $result['error']['code'], $result['error']['message'] );

			return false;
		}

		// Success.
		$this->new_version = $result['new_version'];

		return true;
	}

	/**
	 * Upgrade WP Core to latest version.
	 **
	 * @return bool True on success.
	 */
	public function upgrade_core() {
		global $wp_version, $wpdb;

		$this->clear_error();
		$this->clear_log();
		$this->clear_version();

		/**
		 * mimic @see wp_maybe_auto_update()
		 */
		include_once ABSPATH . 'wp-admin/includes/admin.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		add_action( 'automatic_updates_complete', array( $this, 'capture_core_update_results' ) );

		add_filter( 'auto_update_core', '__return_true', 99999 ); // temporarily allow core autoupdates
		add_filter( 'allow_major_auto_core_updates', '__return_true', 99999 ); // temporarily allow core autoupdates
		add_filter( 'allow_minor_auto_core_updates', '__return_true', 99999 ); // temporarily allow core autoupdates
		add_filter( 'auto_update_core', '__return_true', 99999 ); // temporarily allow core autoupdates
		add_filter( 'auto_update_theme', '__return_false', 99999 );
		add_filter( 'auto_update_plugin', '__return_false', 99999 );

		// TODO don't send email for successful updates
		// apply_filters( 'auto_core_update_send_email', true, $type, $core_update, $result )

		$upgrader = new \WP_Automatic_Updater();

		if ( $upgrader->is_disabled() || ( defined( 'WP_AUTO_UPDATE_CORE' ) && false === WP_AUTO_UPDATE_CORE ) ) {
			$this->set_error(
				'core',
				'autoupdates_disabled',
				__(
					'You have disabled automatic core updates via define( \'WP_AUTO_UPDATE_CORE\', false ); in your wp-config.php or a filter. Remove that code to allow updating core.',
					'platform'
				)
			);

			return false;
		}

		// Used to see if WP_Filesystem is set up to allow unattended updates.
		$skin = new \Automatic_Upgrader_Skin();
		if ( ! $skin->request_filesystem_credentials( false, ABSPATH, false ) ) {
			$this->set_error( 'core', 'fs_unavailable', __( 'Could not access filesystem.', 'platform' ) ); // this string is from core translation

			return false;
		}

		if ( $upgrader->is_vcs_checkout( ABSPATH ) ) {
			$this->set_error( 'core', 'is_vcs_checkout', __( 'Automatic core updates are disabled when WordPress is checked out from version control.', 'platform' ) );

			return false;
		}

		wp_version_check(); // Check for Core updates
		$updates = get_site_transient( 'update_core' );
		if ( ! $updates || empty( $updates->updates ) ) {
			return false;
		}

		$auto_update = false;
		foreach ( $updates->updates as $update ) {
			if ( 'autoupdate' !== $update->response ) {
				continue;
			}

			if ( ! $auto_update || version_compare( $update->current, $auto_update->current, '>' ) ) {
				$auto_update = $update;
			}
		}

		if ( ! $auto_update ) {
			$this->set_error( 'core', 'update_unavailable', __( 'No WordPress core updates appear available.', 'platform' ) );

			return false;
		}

		// compatibility.
		$php_compat = version_compare( phpversion(), $auto_update->php_version, '>=' );
		if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && empty( $wpdb->is_mysql ) ) {
			$mysql_compat = true;
		} else {
			$mysql_compat = version_compare( $wpdb->db_version(), $auto_update->mysql_version, '>=' );
		}

		if ( ! $php_compat || ! $mysql_compat ) {
			$this->set_error( 'core', 'incompatible', __( 'The new version of WordPress is incompatible with your PHP or MySQL version.', 'platform' ) );

			return false;
		}

		// If this was a critical update failure last try, cannot update.
		$skip         = false;
		$failure_data = get_site_option( 'auto_core_update_failed' );
		if ( $failure_data ) {
			if ( ! empty( $failure_data['critical'] ) ) {
				$skip = true;
			}

			// Don't claim we can update on update-core.php if we have a non-critical failure logged.
			if ( $wp_version == $failure_data['current'] && false !== strpos( $auto_update->current, '.1.next.minor' ) ) {
				$skip = true;
			}

			// Cannot update if we're retrying the same A to B update that caused a non-critical failure.
			// Some non-critical failures do allow retries, like download_failed.
			if ( empty( $failure_data['retry'] ) && $wp_version == $failure_data['current'] && $auto_update->current == $failure_data['attempted'] ) {
				$skip = true;
			}

			if ( $skip ) {
				$this->set_error( 'core', 'previous_failure', __( 'There was a previous failure with this update. Please update manually instead.', 'platform' ) );

				return false;
			}
		}

		// this is the only reason left this would fail
		if ( ! \Core_Upgrader::should_update_to_version( $auto_update->current ) ) {
			$this->set_error(
				'core',
				'autoupdates_disabled',
				__(
					'You have disabled automatic core updates via define( \'WP_AUTO_UPDATE_CORE\', false ); in your wp-config.php or a filter. Remove that code to allow updating core.',
					'platform'
				)

			);

			return false;
		}

		/* -------------------------- */

		// ok we are good to give it a try
		$upgrader->run();

		// check populated var from hook
		if ( ! empty( $this->update_results['core'] ) ) {
			$update_result = $this->update_results['core'][0];

			$result    = $update_result->result;
			$this->log = $update_result->messages;

			// all good.
			if ( ! is_wp_error( $result ) ) {
				$this->new_version = $result;

				return true;
			}

			$error_code = $result->get_error_code();
			$error_msg  = $result->get_error_message();

			// if a rollback was run and errored append that to message.
			if ( $error_code === 'rollback_was_required' && is_wp_error( $result->get_error_data()->rollback ) ) {
				$rollback_result = $result->get_error_data()->rollback;
				$error_msg       .= ' Rollback: ' . $rollback_result->get_error_message();
			}

			$this->set_error( 'core', $error_code, $error_msg );

			return false;
		}

		// An unhandled error occurred.
		$this->set_error( 'core', 'unknown_failure', __( 'Update failed for an unknown reason.', 'platform' ) );

		return false;
	}

	/**
	 * Captures core update results from hook, only way to get them
	 *
	 * @param $results
	 */
	public function capture_core_update_results( $results ) {
		$this->update_results = $results;
	}

	/**
	 * Reset PHP opcache
	 */
	public function wp_opcache_reset() {
		if ( ! function_exists( 'opcache_reset' ) ) {
			return;
		}

		if ( ! empty( ini_get( 'opcache.restrict_api' ) ) && strpos( __FILE__, ini_get( 'opcache.restrict_api' ) ) !== 0 ) {
			return;
		}

		opcache_reset();
	}

	/**
	 * Stores the specific error details.
	 *
	 * @param string $pid The PID that was installed/updated.
	 * @param string $code Error code.
	 * @param string $message Error message.
	 *
	 */
	public function set_error( $pid, $code, $message ) {
		$this->error = array(
			'pid'     => $pid,
			'code'    => $code,
			'message' => $message,
		);

		if ( defined( 'WPD_API_DEBUG' ) && WPD_API_DEBUG ) {
			error_log(
				sprintf( 'WPD Platform Upgrader error: %s - %s.', $code, $message )
			);
		}
	}

	/**
	 * Clears the current error flag.
	 *
	 */
	public function clear_error() {
		$this->error = false;
	}

	/**
	 * Returns the current error details, or false if no error is set.
	 *
	 * @return false|array Either the error details or false (no error).
	 */
	public function get_error() {
		return $this->error;
	}

	/**
	 * Clears the current log.
	 */
	public function clear_log() {
		$this->log = false;
	}

	/**
	 * Returns the current log details, or false if no log is set.
	 *
	 * @return false|array Either the log details or false (no error).
	 */
	public function get_log() {
		return $this->log;
	}

	/**
	 * Clears the last updated version.
	 */
	public function clear_version() {
		$this->new_version = false;
	}

	/**
	 * Returns the current log details, or false if no log is set.
	 *
	 * @return false|array Either the log details or false (no error).
	 */
	public function get_version() {
		return $this->new_version;
	}

	/**
	 * Can plugins be automatically installed?
	 *
	 * @param string $type plugin || theme.
	 *
	 * @return bool
	 */
	public function can_auto_install( $type ) {
		$writable = false;

		if ( ! function_exists( 'get_filesystem_method' ) ) {
			include_once ABSPATH . '/wp-admin/includes/file.php';
		}

		// Are we dealing with direct access FS?
		if ( 'direct' === get_filesystem_method() ) {
			if ( 'plugin' === $type ) {
				$root = WP_PLUGIN_DIR;
			} elseif ( 'language' === $type ) {
				$root = is_dir( WP_LANG_DIR ) ? WP_LANG_DIR : WP_CONTENT_DIR;
			} else {
				$root = WP_CONTENT_DIR . '/themes';
			}

			$writable = is_writable( $root );
		}

		// If we don't have write permissions, do we have FTP settings?
		if ( ! $writable ) {
			$writable = defined( 'FTP_USER' )
			            && defined( 'FTP_PASS' )
			            && defined( 'FTP_HOST' );
		}

		// Lastly, if no other option worked, do we have SSH settings?
		if ( ! $writable ) {
			$writable = defined( 'FTP_USER' )
			            && defined( 'FTP_PUBKEY' )
			            && defined( 'FTP_PRIKEY' );
		}

		return $writable;
	}

}