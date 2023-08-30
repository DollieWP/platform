<?php

namespace WPD_Platform;

use WPD_Platform\Services\RemoteService;

class Hooks {

	public function __construct() {
		add_action( 'init', [ RemoteService::instance(), 'run' ], 999 );

		add_action( 'admin_menu', [ Admin::instance(), 'settings_page' ] );
		add_action( 'wp_ajax_dollie_connect_remove_site', [ Admin::instance(), 'ajax_callback_remove_site' ] );
		add_action( 'wp_ajax_dollie_connect_site', [ Admin::instance(), 'ajax_callback_connect_site' ] );
		add_filter( 'plugin_row_meta', [ Whitelabel::instance(), 'whitelabel' ], 10, 2 );
		add_action( 'wpd_platform/after/register_site', [ Whitelabel::instance(), 'after_register_site' ] );

		add_action( 'upgrader_process_complete', [ Admin::instance(), 'trigger_update_hook' ] );
		add_action( 'activated_plugin', [ Admin::instance(), 'trigger_update_hook' ] );
		add_action( 'deactivated_plugin', [ Admin::instance(), 'trigger_update_hook' ] );
		add_action( 'deleted_plugin', [ Admin::instance(), 'trigger_update_hook' ] );
		add_action( 'after_switch_theme', [ Admin::instance(), 'trigger_update_hook' ] );
		add_action( 'deleted_theme', [ Admin::instance(), 'trigger_update_hook' ] );
		add_action( 'user_register', [ Admin::instance(), 'trigger_update_hook' ] );
		add_action( 'wp_update_user', [ Admin::instance(), 'trigger_update_hook' ] );
		add_action( 'deleted_user', [ Admin::instance(), 'trigger_update_hook' ] );
	}
}
