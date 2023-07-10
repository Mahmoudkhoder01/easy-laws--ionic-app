<?php


class BWQM_Dispatcher_Headers extends BWQM_Dispatcher {

	public $id = 'headers';

	public function __construct( BWQM_Plugin $bwqm ) {
		parent::__construct( $bwqm );
	}

	public function init() {

		if ( ! $this->user_can_view() ) {
			return;
		}

		if ( BWQM_Util::is_ajax() ) {
			ob_start();
		}

	}

	public function before_output() {

		require_once $this->bwqm->plugin_path( 'output/Headers.php' );

		BWQM_Util::include_files( $this->bwqm->plugin_path( 'output/headers' ) );

	}

	public function after_output() {

		# flush once, because we're nice
		if ( BWQM_Util::is_ajax() and ob_get_length() ) {
			ob_flush();
		}

	}

	public function is_active() {

		if ( ! $this->user_can_view() ) {
			return false;
		}

		# If the headers have already been sent then we can't do anything about it
		if ( headers_sent() ) {
			return false;
		}

		return true;

	}

}

function register_bwqm_dispatcher_headers( array $dispatchers, BWQM_Plugin $bwqm ) {
	$dispatchers['headers'] = new BWQM_Dispatcher_Headers( $bwqm );
	return $dispatchers;
}

add_filter( 'bwqm/dispatchers', 'register_bwqm_dispatcher_headers', 10, 2 );
