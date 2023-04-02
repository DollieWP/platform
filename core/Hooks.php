<?php

namespace WPD_Platform;

use WPD_Platform\Services\RemoteService;

class Hooks {
	public function __construct() {

		add_action( 'init', [ RemoteService::instance(), 'run' ], 999 );

		add_action( 'admin_menu', [ Admin::instance(), 'settings_page' ] );
		add_action('wp_ajax_dollie_connect_remove_site', [ Admin::instance(), 'ajax_callback_remove_site' ]);
		add_action('wp_ajax_dollie_connect_site', [ Admin::instance(), 'ajax_callback_connect_site' ]);

	}
}
