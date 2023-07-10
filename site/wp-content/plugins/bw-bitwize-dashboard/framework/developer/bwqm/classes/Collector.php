<?php


if ( ! class_exists( 'BWQM_Collector' ) ) {
abstract class BWQM_Collector {

	protected $data = array(
		'types'           => array(),
		'component_times' => array(),
	);

	public function __construct() {}

	final public function id() {
		return "bwqm-{$this->id}";
	}

	abstract public function name();

	protected function log_type( $type ) {

		if ( isset( $this->data['types'][$type] ) ) {
			$this->data['types'][$type]++;
		} else {
			$this->data['types'][$type] = 1;
		}

	}

	protected function log_component( $component, $ltime, $type ) {

		if ( !isset( $this->data['component_times'][$component->name] ) ) {
			$this->data['component_times'][$component->name] = array(
				'component' => $component->name,
				'calls'     => 0,
				'ltime'     => 0,
				'types'     => array()
			);
		}

		$this->data['component_times'][$component->name]['calls']++;
		$this->data['component_times'][$component->name]['ltime'] += $ltime;

		if ( isset( $this->data['component_times'][$component->name]['types'][$type] ) ) {
			$this->data['component_times'][$component->name]['types'][$type]++;
		} else {
			$this->data['component_times'][$component->name]['types'][$type] = 1;
		}

	}

	public static function timer_stop_float() {
		global $timestart;
		return microtime( true ) - $timestart;
	}

	public static function format_bool_constant( $constant ) {
		if ( !defined( $constant ) ) {
			return 'undefined';
		} else if ( !constant( $constant ) ) {
			return 'false';
		} else {
			return 'true';
		}
	}

	final public function get_data() {
		return $this->data;
	}

	final public function set_id( $id ) {
		$this->id = $id;
	}

	public static function sort_ltime( $a, $b ) {
		if ( $a['ltime'] == $b['ltime'] ) {
			return 0;
		} else {
			return ( $a['ltime'] > $b['ltime'] ) ? -1 : 1;
		}
	}

	public function process() {}

	public function tear_down() {}

}
}
