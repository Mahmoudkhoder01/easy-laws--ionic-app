<?php


if ( ! class_exists( 'BWQM_Dispatcher' ) ) {
abstract class BWQM_Dispatcher {

	public function __construct( BWQM_Plugin $bwqm ) {
		$this->bwqm = $bwqm;

		if ( !defined( 'BWQM_COOKIE' ) ) {
			define( 'BWQM_COOKIE', 'query_monitor_' . COOKIEHASH );
		}

	}

	abstract public function is_active();

	public function init() {
		// nothing
	}

	public function before_output() {
		// nothing
	}

	public function after_output() {
		// nothing
	}

	public function user_can_view() {

		if ( !did_action( 'plugins_loaded' ) ) {
			return false;
		}

		if ( current_user_can( 'can_bitwize' ) ) {
			return true;
		}

		return $this->user_verified();

	}

	public function user_verified() {
		if ( isset( $_COOKIE[BWQM_COOKIE] ) ) {
			return $this->verify_cookie( stripslashes( $_COOKIE[BWQM_COOKIE] ) );
		}
		return false;
	}

	public static function verify_cookie( $value ) {
		if ( $old_user_id = wp_validate_auth_cookie( $value, 'logged_in' ) ) {
			return user_can( $old_user_id, 'can_bitwize' );
		}
		return false;
	}

}
}
