<?php


class BWQM_Collector_DB_Callers extends BWQM_Collector {

	public $id = 'db_callers';

	public function name() {
		return __( 'Queries by Caller', BW_TD );
	}

	public function process() {

		if ( $dbq = BWQM_Collectors::get( 'db_queries' ) ) {
			if ( isset( $dbq->data['times'] ) ) {
				$this->data['times'] = $dbq->data['times'];
				usort( $this->data['times'], 'BWQM_Collector::sort_ltime' );
			}
			if ( isset( $dbq->data['types'] ) ) {
				$this->data['types'] = $dbq->data['types'];
			}
		}

	}

}

function register_bwqm_collector_db_callers( array $collectors, BW_QueryMonitor $bwqm ) {
	$collectors['db_callers'] = new BWQM_Collector_DB_Callers;
	return $collectors;
}

add_filter( 'bwqm/collectors', 'register_bwqm_collector_db_callers', 20, 2 );
