<?php


class BWQM_Collector_Overview extends BWQM_Collector {

	public $id = 'overview';

	public function name() {
		return __( 'Overview', BW_TD );
	}

	public function process() {

		$this->data['time']       = self::timer_stop_float();
		$this->data['time_limit'] = ini_get( 'max_execution_time' );

		if ( !empty( $this->data['time_limit'] ) ) {
			$this->data['time_usage'] = ( 100 / $this->data['time_limit'] ) * $this->data['time'];
		} else {
			$this->data['time_usage'] = 0;
		}

		if ( function_exists( 'memory_get_peak_usage' ) ) {
			$this->data['memory'] = memory_get_peak_usage();
		} else if ( function_exists( 'memory_get_usage' ) ) {
			$this->data['memory'] = memory_get_usage();
		} else {
			$this->data['memory'] = 0;
		}

		$this->data['memory_limit'] = BWQM_Util::convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$this->data['memory_usage'] = ( 100 / $this->data['memory_limit'] ) * $this->data['memory'];

	}

}

function register_bwqm_collector_overview( array $collectors, BW_QueryMonitor $bwqm ) {
	$collectors['overview'] = new BWQM_Collector_Overview;
	return $collectors;
}

add_filter( 'bwqm/collectors', 'register_bwqm_collector_overview', 1, 2 );
