<?php


class BWQM_Collector_Hooks extends BWQM_Collector {

	public $id = 'hooks';

	public function name() {
		return __( 'Hooks', BW_TD );
	}

	public function process() {

		global $wp_actions, $wp_filter;

		$this->hide_qm = ( defined( 'BWQM_HIDE_SELF' ) and BWQM_HIDE_SELF );

		if ( is_admin() and ( $admin = BWQM_Collectors::get( 'admin' ) ) ) {
			$this->data['screen'] = $admin->data['base'];
		} else {
			$this->data['screen'] = '';
		}

		$hooks = $all_parts = $components = array();

		if ( has_filter( 'all' ) ) {

			$hooks['all'] = $this->process_action( 'all', $wp_filter );
			$this->data['warnings']['all_hooked'] = $hooks['all'];

		}

		foreach ( $wp_actions as $name => $count ) {

			$hooks[$name] = $this->process_action( $name, $wp_filter );

			$all_parts    = array_merge( $all_parts, $hooks[$name]['parts'] );
			$components   = array_merge( $components, $hooks[$name]['components'] );

		}

		$this->data['hooks'] = $hooks;
		$this->data['parts'] = array_unique( array_filter( $all_parts ) );
		$this->data['components'] = array_unique( array_filter( $components ) );

	}

	protected function process_action( $name, array $wp_filter ) {

		$actions = $components = array();

		if ( isset( $wp_filter[$name] ) ) {

			# http://core.trac.wordpress.org/ticket/17817
			$action = $wp_filter[$name];

			foreach ( $action as $priority => $callbacks ) {

				foreach ( $callbacks as $callback ) {

					$callback = BWQM_Util::populate_callback( $callback );

					if ( isset( $callback['component'] ) ) {
						if ( $this->hide_qm and ( 'query-monitor' === $callback['component']->context ) ) {
							continue;
						}

						$components[$callback['component']->name] = $callback['component']->name;
					}

					$actions[] = array(
						'priority'  => $priority,
						'callback'  => $callback,
					);

				}

			}

		}

		$parts = array_filter( preg_split( '#[_/-]#', $name ) );

		return array(
			'name'       => $name,
			'actions'    => $actions,
			'parts'      => $parts,
			'components' => $components,
		);

	}

}

function register_bwqm_collector_hooks( array $collectors, BW_QueryMonitor $bwqm ) {
	$collectors['hooks'] = new BWQM_Collector_Hooks;
	return $collectors;
}

add_filter( 'bwqm/collectors', 'register_bwqm_collector_hooks', 20, 2 );
