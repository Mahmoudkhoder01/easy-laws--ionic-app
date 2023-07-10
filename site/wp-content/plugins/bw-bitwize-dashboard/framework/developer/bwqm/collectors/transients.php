<?php


class BWQM_Collector_Transients extends BWQM_Collector {

	public $id = 'transients';

	public function name() {
		return __( 'Transients', BW_TD );
	}

	public function __construct() {
		parent::__construct();
		# See http://core.trac.wordpress.org/ticket/24583
		add_action( 'setted_site_transient', array( $this, 'action_setted_site_transient' ), 10, 3 );
		add_action( 'setted_transient',      array( $this, 'action_setted_blog_transient' ), 10, 3 );
	}

	public function tear_down() {
		parent::tear_down();
		remove_action( 'setted_site_transient', array( $this, 'action_setted_site_transient' ), 10 );
		remove_action( 'setted_transient',      array( $this, 'action_setted_blog_transient' ), 10 );
	}

	public function action_setted_site_transient( $transient, $value = null, $expiration = null ) {
		$this->setted_transient( $transient, 'site', $value, $expiration );
	}

	public function action_setted_blog_transient( $transient, $value = null, $expiration = null ) {
		$this->setted_transient( $transient, 'blog', $value, $expiration );
	}

	public function setted_transient( $transient, $type, $value = null, $expiration = null ) {
		$trace = new BWQM_Backtrace( array(
			'ignore_items' => 1 # Ignore the action_setted_(site|blog)_transient method
		) );
		$this->data['trans'][] = array(
			'transient'  => $transient,
			'trace'      => $trace,
			'type'       => $type,
			'value'      => $value,
			'expiration' => $expiration,
		);
	}

}

# Load early in case a plugin is setting transients when it initialises instead of after the `plugins_loaded` hook
BWQM_Collectors::add( new BWQM_Collector_Transients );
