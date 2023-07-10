<?php


class BWQM_Collector_DB_Components extends BWQM_Collector {

	public $id = 'db_components';

	public function name() {
		return __( 'Queries by Component', BW_TD );
	}

	public function process() {

		if ( $dbq = BWQM_Collectors::get( 'db_queries' ) ) {
			if ( isset( $dbq->data['component_times'] ) ) {
				$this->data['times'] = $dbq->data['component_times'];
				usort( $this->data['times'], 'BWQM_Collector::sort_ltime' );
			}
			if ( isset( $dbq->data['types'] ) ) {
				$this->data['types'] = $dbq->data['types'];
			}
		}

	}

}

function register_bwqm_collector_db_components( array $collectors, BW_QueryMonitor $bwqm ) {
	$collectors['db_components'] = new BWQM_Collector_DB_Components;
	return $collectors;
}

add_filter( 'bwqm/collectors', 'register_bwqm_collector_db_components', 20, 2 );
