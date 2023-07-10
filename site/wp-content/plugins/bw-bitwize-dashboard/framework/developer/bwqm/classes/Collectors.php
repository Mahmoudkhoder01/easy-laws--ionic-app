<?php


if ( ! class_exists( 'BWQM_Collectors' ) ) {
class BWQM_Collectors implements IteratorAggregate {

	private $items = array();

	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	public static function add( BWQM_Collector $collector ) {
		$collectors = self::init();
		$collectors->items[ $collector->id ] = $collector;
	}

	public static function get( $id ) {
		$collectors = self::init();
		if ( isset( $collectors->items[ $id ] ) ) {
			return $collectors->items[ $id ];
		}
		return false;
	}

	public static function init() {
		static $instance;

		if ( !$instance ) {
			$instance = new BWQM_Collectors;
		}

		return $instance;

	}

}
}
