<?php


class BWQM_Collector_Admin extends BWQM_Collector {

	public $id = 'admin';

	public function name() {
		return __( 'Admin Screen', BW_TD );
	}

	public function process() {

		global $pagenow;

		if ( isset( $_GET['page'] ) && get_current_screen() != null ) {
			$this->data['base'] = get_current_screen()->base;
		} else {
			$this->data['base'] = $pagenow;
		}

		$this->data['pagenow'] = $pagenow;
		$this->data['current_screen'] = get_current_screen();

	}

}

function register_bwqm_collector_admin( array $collectors, BW_QueryMonitor $bwqm ) {
	$collectors['admin'] = new BWQM_Collector_Admin;
	return $collectors;
}

if ( is_admin() ) {
	add_filter( 'bwqm/collectors', 'register_bwqm_collector_admin', 10, 2 );
}
