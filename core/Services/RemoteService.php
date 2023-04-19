<?php

namespace WPD_Platform\Services;

use WPD_Platform\Plugin;
use WPD_Platform\Singleton;

class RemoteService extends Singleton {

	/**
	 * Store registered remote access actions and callbacks.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Stores request time for debug.
	 *
	 * @var int
	 */
	protected $timer = 0;

	/**
	 * Stores current processed action.
	 *
	 * @var string
	 */
	protected $current_action = '';

	/**
	 * Stores current action params being processed.
	 *
	 * @var array
	 */
	protected $current_params = array();


	public function run() {

		// don't continue if is not a remote request
		if ( ! $this->is_remote_request() ) {
			return;
		}

		$this->register_actions();

		// Get the json data.

		// Get body.
		$raw_json = file_get_contents( 'php://input' );

		// Get body.
		$body = json_decode( $raw_json );

		// Validate request Key.
		$this->validate_request( $raw_json );

		if ( ! Plugin::instance()->get_host()->is_connected() ) {
			$this->send_json_error(
				array(
					'code'    => 'not_connected',
					'message' => __( 'Site is not connected', 'platform' ),
				)
			);
		}

		// Action name is required.
		if ( ! isset( $body->action ) ) {
			$this->send_json_error(
				array(
					'code'    => 'invalid_params',
					'message' => __( 'The action parameter is missing', 'platform' ),
				)
			);
		}

		// Params are required.
		if ( ! isset( $body->params ) ) {
			$this->send_json_error(
				array(
					'code'    => 'invalid_params',
					'message' => __( 'The params object is missing', 'platform' ),
				)
			);
		}

		$this->timer          = microtime( true );
		$this->current_action = $body->action;
		$this->current_params = $body->params;

		$this->process_action();

	}

	/**
	 * @return void
	 */
	protected function register_actions() {
		$actions        = array(
			//'sync'         => 'action_sync',
			'status'       => 'action_status',
			'upgrade'      => 'action_upgrade',
			'core_upgrade' => 'action_core_upgrade',
			'login_token'  => 'action_login_token',
			'export_db'    => 'action_export_db',
			//'activate'     => 'action_activate',
			//'deactivate'   => 'action_deactivate',
			//'install'      => 'action_install',
			//'delete'       => 'action_delete',
		);
		$custom_actions = apply_filters( 'wpd_platform_register_hub_action', [] );

		foreach ( $actions as $action => $callback ) {
			// Register action.
			$this->register_action( $action, array( $this, $callback ) );
		}

		if ( ! empty( $custom_actions ) ) {
			foreach ( $actions as $action => $callback ) {

				// Check action is not already registered and valid.
				if ( ! isset( $this->actions[ $action ] ) && is_callable( $callback ) ) {
					$this->register_action( $action, $callback );
				}
			}
		}
	}

	/**
	 * @param $action
	 * @param $callback
	 *
	 * @return void
	 */
	public function register_action( $action, $callback ) {
		$this->actions[ $action ] = $callback;
	}

	/**
	 * Check if current request is for remote data.
	 *
	 * @access protected
	 *
	 * @return bool
	 */
	protected function is_remote_request() {
		return ! empty( $_GET['wpd-platform'] ); // phpcs:ignore
	}

	/**
	 * @return void
	 */
	private function validate_request( $data ) {
		$headers = getallheaders();
		if ( ! isset( $headers['Authorization'] ) || Plugin::instance()->get_host()->get_token() !== $headers['Authorization'] ) {
			header( "HTTP/1.1 401 Unauthorized" );
			exit;
		}
	}

	/**
	 * Run request.
	 * @return void
	 */
	private function process_action() {
		// Continue only if valid action.
		if ( isset( $this->actions[ $this->current_action ] ) ) {

			// Execute request action.
			call_user_func(
				$this->actions[ $this->current_action ],
				$this->current_params,
				$this->current_action,
				$this
			);


		} else {
			// Invalid action.
			wp_send_json_error(
				array(
					'code'    => 'unregistered_action',
					'message' => 'This action is not registered',
				)
			);
		}
	}

	/**
	 * @param $params
	 * @param $action
	 *
	 * @return void
	 */
	public function action_status( $params, $action ) {
		$full = ! empty( $params->full );

		$this->send_json_success(
			StatsService::instance()->get( $full )
		);
	}

	/**
	 * Gets the login token.
	 *
	 * @param $params
	 * @param $action
	 *
	 * @return void
	 */
	public function action_login_token( $params, $action ) {
		$username = $params->username ?? null;
		$token    = LoginService::instance()->get_login_token( $username );

		if ( empty( $token ) ) {
			$this->send_json_error(
				[
					'errors' => [
						'No user found'
					]
				]
			);

			return;
		}

		$this->send_json_success(
			[
				'token' => $token,
				'Token' => $token
			]
		);
	}

	public function action_upgrade( $params, $action ) {

		$upgraded = [];
		$errors   = [];

		// Process plugins.
		if ( isset( $params->plugins ) ) {
			$params->plugins = ! is_array( $params->plugins ) ? explode( ' ', $params->plugins ) : $params->plugins;

			if ( is_array( $params->plugins ) ) {
				foreach ( $params->plugins as $plugin ) {
					$upgrade_data = $this->upgrade_call( $plugin, 'plugin' );
					$upgraded[]   = $upgrade_data['upgraded'];
					$errors[]     = $upgrade_data['error'];
				}
			}

		}

		// Process themes.
		if ( isset( $params->themes ) ) {
			$params->themes = ! is_array( $params->themes ) ? explode( ' ', $params->themes ) : $params->themes;

			if ( is_array( $params->themes ) ) {
				foreach ( $params->themes as $theme ) {
					$upgrade_data = $this->upgrade_call( $theme, 'theme' );
					$upgraded[]   = $upgrade_data['upgraded'];
					$errors[]     = $upgrade_data['error'];
				}
			}
		}

		if ( ! empty( $upgraded ) ) {
			$this->send_json_success( compact( 'upgraded', 'errors' ) );
		} else {
			$this->send_json_error( compact( 'upgraded', 'errors' ) );
		}

	}

	/**
	 * Run the upgrade call and prepare result.
	 *
	 * @param $item
	 * @param $type
	 *
	 * @return string[]
	 */
	private function upgrade_call( $item, $type = 'plugin' ) {

		$pid      = "{$type}:{$item}";
		$success  = UpdateService::instance()->upgrade( $pid );
		$response = [
			'upgraded' => '',
			'error'    => '',
		];

		if ( $success ) {
			$response['upgraded'] = array(
				'file'        => $item,
				'log'         => UpdateService::instance()->get_log(),
				'new_version' => UpdateService::instance()->get_version(),
			);
		} else {
			$error             = UpdateService::instance()->get_error();
			$response['error'] = array(
				'file'    => $item,
				'code'    => $error['code'],
				'message' => $error['message'],
				'log'     => UpdateService::instance()->get_log(),
			);
		}

		return $response;

	}

	/**
	 * Upgrades to the latest WP core version, major or minor.
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @return void
	 *
	 */
	public function action_core_upgrade( $params, $action ) {

		// Upgrade core WP.
		$success = UpdateService::instance()->upgrade_core();
		if ( $success ) {
			$this->send_json_success(
				array(
					'log'         => UpdateService::instance()->get_log(),
					'new_version' => UpdateService::instance()->get_version(),
				)
			);
		} else {
			$error = UpdateService::instance()->get_error();
			$this->send_json_error(
				array(
					'code'    => $error['code'],
					'message' => $error['message'],
					'data'    => array( 'log' => UpdateService::instance()->get_log() ),
				)
			);
		}
	}


	/**
	 * Exports the full db.
	 *
	 * @param $params
	 * @param $action
	 *
	 * @return void
	 */
	public function action_export_db( $params, $action ) {
		$path     = $params->path ?? null;
		$filename = $params->filename ?? null;

		if ( empty( $path ) || empty( $filename ) ) {
			$this->send_json_error(
				[
					'errors' => [
						'Path and filename are required defined'
					]
				]
			);

			return;
		}

		$sql       = '';
		$db_name   = DB_NAME;
		$db_user   = DB_USER;
		$db_pass   = DB_PASSWORD;
		$db_host   = DB_HOST;
		$dir       = trailingslashit( WP_CONTENT_DIR ) . trailingslashit( $path );
		$full_path = $dir . $filename;

		// Check connection
		$mysqli = new \mysqli( $db_host, $db_user, $db_pass, $db_name );
		if ( $mysqli->connect_errno ) {
			$this->send_json_error(
				[
					'errors' => [
						"Failed to connect to MySQL: " . $mysqli->connect_error
					]
				]
			);
			exit;
		}

		ob_start();

		// Export the db structure and data
		$mysqli->query( 'SET NAMES "utf8"' );
		$tables = $mysqli->query( 'SHOW TABLES' );
		while ( $row = $tables->fetch_row() ) {
			$table      = $row[0];
			$result     = $mysqli->query( 'SELECT * FROM ' . $table );
			$num_fields = $result->field_count;

			$sql  .= 'DROP TABLE IF EXISTS ' . $table . ";\n";
			$row2 = $mysqli->query( 'SHOW CREATE TABLE ' . $table )->fetch_row();
			$sql  .= $row2[1] . ";\n\n";

			for ( $i = 0; $i < $num_fields; $i ++ ) {
				while ( $row3 = $result->fetch_row() ) {
					$sql .= 'INSERT INTO ' . $table . ' VALUES(';
					for ( $j = 0; $j < $num_fields; $j ++ ) {
						$row3[ $j ] = addslashes( $row3[ $j ] );
						if ( isset( $row3[ $j ] ) ) {
							$sql .= '"' . $row3[ $j ] . '"';
						} else {
							$sql .= '""';
						}
						if ( $j < ( $num_fields - 1 ) ) {
							$sql .= ',';
						}
					}
					$sql .= ");\n";
				}
			}
			$sql .= "\n\n";
		}

		ob_end_clean();

		wp_mkdir_p( $dir );
		$gzipped = gzencode( $sql, 9 );

		file_put_contents( $full_path, $gzipped );

		$this->send_json_success(
			[
				'path' => trailingslashit( WP_CONTENT_URL ) . trailingslashit( $path ) . $filename,
			]
		);
	}

	/**
	 * Return success results for API to the hub
	 *
	 * @param mixed $data Data to encode as JSON, then print and die.
	 * @param int $status_code The HTTP status code to output, defaults to 200.
	 *
	 * @return void
	 */
	protected function send_json_success( $data = null, $status_code = null ) {

		// Log it if turned on.
		if ( $this->is_remote_request() && defined( 'WPD_API_DEBUG' ) && WPD_API_DEBUG ) {
			$req_time   = round( ( microtime( true ) - $this->timer ), 4 ) . 's';
			$req_status = is_null( $status_code ) ? 200 : $status_code;
			$log        = '[WPD API call response] %s %s %s %s';
			$log        .= "\n   Response: (success) %s\n";
			$msg        = sprintf(
				$log,
				$_GET['wpd-platform'], // phpcs:ignore
				$this->current_action,
				$req_status,
				$req_time,
				wp_json_encode( $data, JSON_PRETTY_PRINT )
			);
			error_log( $msg ); // phpcs:ignore
		}

		wp_send_json_success( $data, $status_code );
	}

	/**
	 * Return error results for API to the hub.
	 *
	 * @param mixed $data Data to encode as JSON, then print and die.
	 * @param int $status_code The HTTP status code to output, defaults to 200.
	 *
	 * @return void
	 */
	protected function send_json_error( $data = null, $status_code = null ) {

		// Log it if turned on.
		if ( $this->is_remote_request() && defined( 'WPD_API_DEBUG' ) && WPD_API_DEBUG ) {
			$req_time   = round( ( microtime( true ) - $this->timer ), 4 ) . 's';
			$req_status = is_null( $status_code ) ? 200 : $status_code;
			$log        = '[WPD API call response] %s %s %s %s';
			$log        .= "\n   Response: (error) %s\n";
			$msg        = sprintf(
				$log,
				$_GET['wpd-platform'], // phpcs:ignore
				$this->current_action,
				$req_status,
				$req_time,
				wp_json_encode( $data, JSON_PRETTY_PRINT )
			);
			error_log( $msg ); // phpcs:ignore
		}

		wp_send_json_error( $data, $status_code );
	}
}
