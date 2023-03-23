<?php

namespace WPD_Platform;

class CLI extends \WP_CLI_Command {
	public function get_token() {
		\WP_CLI::Log( Plugin::instance()->get_host()->get_token() );
	}
}
