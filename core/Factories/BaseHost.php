<?php

namespace WPD_Platform\Factories;

use WPD_Platform\Utils\HostInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

abstract class BaseHost implements HostInterface {

	/**
	 * @var string Where we store our token
	 */
	private $token;
	protected $host_type;
	private $secret = '';

	public function __construct() {
	}

	public function get_db_token() {
		$token = get_option( self::TOKEN_OPTION );

		if ( ! $token ) {
			$token = $this->generate_db_token();
		}

		return $token;
	}

	/**
	 * @throws \Exception
	 */
	private function generate_db_token() {
		$token = bin2hex( random_bytes( 20 ) );
		update_option( self::TOKEN_OPTION, $token );

		return $token;
	}

	public function get_token(): string {
		return $this->token;
	}

	public function get_secret(): string {
		return $this->secret;
	}

	protected function set_token( $token ): void {
		$this->token = $token;
	}

	protected function set_secret( $secret ): void {
		$this->secret = $secret;
	}

	public function get_type() {
		return $this->host_type;
	}

	public function register_site() {
	}

	public function is_connected() {
		return false;
	}

	public function is_type_internal() {
		return $this->get_type() === self::TYPE_1;
	}

	public function is_type_external() {
		return $this->get_type() === self::TYPE_2;
	}
}
