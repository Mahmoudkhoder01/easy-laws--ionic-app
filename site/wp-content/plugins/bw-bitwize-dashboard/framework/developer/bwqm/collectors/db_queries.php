<?php


if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );
if ( !defined( 'BWQM_DB_EXPENSIVE' ) )
	define( 'BWQM_DB_EXPENSIVE', 0.05 );

class BWQM_Collector_DB_Queries extends BWQM_Collector {

	public $id = 'db_queries';
	public $db_objects = array();

	public function name() {
		return __( 'Database Queries', BW_TD );
	}

	public function get_errors() {
		if ( !empty( $this->data['errors'] ) ) {
			return $this->data['errors'];
		}
		return false;
	}

	public function get_expensive() {
		if ( !empty( $this->data['expensive'] ) ) {
			return $this->data['expensive'];
		}
		return false;
	}

	public static function is_expensive( array $row ) {
		return $row['ltime'] > BWQM_DB_EXPENSIVE;
	}

	public function process() {

		if ( !SAVEQUERIES ) {
			return;
		}

		$this->data['total_qs']   = 0;
		$this->data['total_time'] = 0;
		$this->data['errors']     = array();

		$this->db_objects = apply_filters( 'bwqm/collect/db_objects', array(
			'$wpdb' => $GLOBALS['wpdb']
		) );

		foreach ( $this->db_objects as $name => $db ) {
			if ( is_a( $db, 'wpdb' ) ) {
				$this->process_db_object( $name, $db );
			} else {
				unset( $this->db_objects[ $name ] );
			}
		}

	}

	protected function log_caller( $caller, $ltime, $type ) {

		if ( !isset( $this->data['times'][$caller] ) ) {
			$this->data['times'][$caller] = array(
				'caller' => $caller,
				'calls' => 0,
				'ltime' => 0,
				'types' => array()
			);
		}

		$this->data['times'][$caller]['calls']++;
		$this->data['times'][$caller]['ltime'] += $ltime;

		if ( isset( $this->data['times'][$caller]['types'][$type] ) ) {
			$this->data['times'][$caller]['types'][$type]++;
		} else {
			$this->data['times'][$caller]['types'][$type] = 1;
		}

	}

	public function process_db_object( $id, wpdb $db ) {

		$rows       = array();
		$types      = array();
		$total_time = 0;
		$has_result = false;
		$has_trace  = false;

		foreach ( (array) $db->queries as $query ) {

			# @TODO: decide what I want to do with this:
			if ( false !== strpos( $query[2], 'wp_admin_bar' ) and !isset( $_REQUEST['bwqm_display_admin_bar'] ) ) {
				continue;
			}

			$sql           = $query[0];
			$ltime         = $query[1];
			$stack         = $query[2];
			$has_trace     = isset( $query['trace'] );
			$has_result    = isset( $query['result'] );

			if ( isset( $query['result'] ) ) {
				$result = $query['result'];
			} else {
				$result = null;
			}

			$total_time += $ltime;

			if ( isset( $query['trace'] ) ) {

				$trace       = $query['trace'];
				$component   = $query['trace']->get_component();
				$caller      = $query['trace']->get_caller();
				$caller_name = $caller['id'];
				$caller      = $caller['display'];

			} else {

				$trace     = null;
				$component = null;
				$callers   = explode( ',', $stack );
				$caller    = trim( end( $callers ) );

				if ( false !== strpos( $caller, '(' ) ) {
					$caller_name = substr( $caller, 0, strpos( $caller, '(' ) ) . '()';
				} else {
					$caller_name = $caller;
				}

			}

			$sql  = trim( $sql );
			$type = preg_split( '/\b/', $sql, 2, PREG_SPLIT_NO_EMPTY );
			$type = strtoupper( $type[0] );

			$this->log_type( $type );
			$this->log_caller( $caller_name, $ltime, $type );

			if ( $component ) {
				$this->log_component( $component, $ltime, $type );
			}

			if ( !isset( $types[$type]['total'] ) ) {
				$types[$type]['total'] = 1;
			} else {
				$types[$type]['total']++;
			}

			if ( !isset( $types[$type]['callers'][$caller] ) ) {
				$types[$type]['callers'][$caller] = 1;
			} else {
				$types[$type]['callers'][$caller]++;
			}

			$row = compact( 'caller', 'caller_name', 'stack', 'sql', 'ltime', 'result', 'type', 'component', 'trace' );

			if ( is_wp_error( $result ) ) {
				$this->data['errors'][] = $row;
			}

			if ( self::is_expensive( $row ) ) {
				$this->data['expensive'][] = $row;
			}

			$rows[] = $row;

		}

		$total_qs = count( $rows );

		$this->data['total_qs'] += $total_qs;
		$this->data['total_time'] += $total_time;

		# @TODO put errors in here too:
		# @TODO proper class instead of (object)
		$this->data['dbs'][$id] = (object) compact( 'rows', 'types', 'has_result', 'has_trace', 'total_time', 'total_qs' );

	}

}

function register_bwqm_collector_db_queries( array $collectors, BW_QueryMonitor $bwqm ) {
	$collectors['db_queries'] = new BWQM_Collector_DB_Queries;
	return $collectors;
}

add_filter( 'bwqm/collectors', 'register_bwqm_collector_db_queries', 10, 2 );
