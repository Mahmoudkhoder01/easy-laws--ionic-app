<?php


class BWQM_Dispatchers implements IteratorAggregate {

	private $items = array();

	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	public static function add( BWQM_Dispatcher $dispatcher ) {
		$dispatchers = self::init();
		$dispatchers->items[ $dispatcher->id ] = $dispatcher;
	}

	public static function get( $id ) {
		$dispatchers = self::init();
		if ( isset( $dispatchers->items[ $id ] ) ) {
			return $dispatchers->items[ $id ];
		}
		return false;
	}

	public static function init() {
		static $instance;

		if ( !$instance ) {
			$instance = new BWQM_Dispatchers;
		}

		return $instance;

	}

}
