<?php

namespace WPD_Platform;

use WPD_Platform\Services\RemoteService;

class Hooks {
	public function __construct() {

		add_action( 'init', [ RemoteService::instance(), 'run' ], 999 );

	}
}
