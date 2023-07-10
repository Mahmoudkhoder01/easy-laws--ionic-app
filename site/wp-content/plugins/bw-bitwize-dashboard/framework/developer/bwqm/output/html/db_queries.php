<?php


class BWQM_Output_Html_DB_Queries extends BWQM_Output_Html {

	public $query_row = 0;

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 20 );
		add_filter( 'bwqm/output/title', array( $this, 'admin_title' ), 20 );
		add_filter( 'bwqm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['dbs'] ) ) {
			$this->output_empty_queries();
			return;
		}

		if ( !empty( $data['errors'] ) ) {
			$this->output_error_queries( $data['errors'] );
		}

		if ( !empty( $data['expensive'] ) ) {
			$this->output_expensive_queries( $data['expensive'] );
		}

		foreach ( $data['dbs'] as $name => $db ) {
			$this->output_queries( $name, $db, $data );
		}

	}

	protected function output_empty_queries() {

		echo '<div class="bwqm bwqm-queries" id="' . esc_attr( $this->collector->id() ) . '-wpdb">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Database Queries', BW_TD ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		echo '<tr>';
		echo '<td class="bwqm-warn">';
		_e( 'No database queries were logged because <code>SAVEQUERIES</code> is set to <code>false</code>', BW_TD );
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	protected function output_error_queries( array $errors ) {

		echo '<div class="bwqm bwqm-queries" id="bwqm-query-errors">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="4">' . __( 'Database Errors', BW_TD ) . '</th>';
		echo '</tr>';
		echo '<tr>';
		echo '<th>' . __( 'Query', BW_TD ) . '</th>';
		echo '<th>' . __( 'Call Stack', BW_TD ) . '</th>';
		echo '<th>' . __( 'Component', BW_TD ) . '</th>';
		echo '<th>' . __( 'Error', BW_TD ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $errors as $row ) {
			$this->output_query_row( $row, array( 'sql', 'stack', 'component', 'result' ) );
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	protected function output_expensive_queries( array $expensive ) {

		$dp = strlen( substr( strrchr( BWQM_DB_EXPENSIVE, '.' ), 1 ) );

		echo '<div class="bwqm bwqm-queries" id="bwqm-query-expensive">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="5" class="bwqm-expensive">' . sprintf( __( 'Slow Database Queries (above %ss)', BW_TD ), '<span class="bwqm-expensive">' . number_format_i18n( BWQM_DB_EXPENSIVE, $dp ) . '</span>' ) . '</th>';
		echo '</tr>';
		echo '<tr>';
		echo '<th scope="col">' . __( 'Query', BW_TD ) . '</th>';
		echo '<th scope="col">' . __( 'Caller', BW_TD ) . '</th>';

		if ( isset( $expensive[0]['component'] ) ) {
			echo '<th scope="col">' . __( 'Component', BW_TD ) . '</th>';
		}

		if ( isset( $expensive[0]['result'] ) ) {
			echo '<th scope="col" class="bwqm-num">' . __( 'Affected Rows', BW_TD ) . '</th>';
		}

		echo '<th class="bwqm-num">' . __( 'Time', BW_TD ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $expensive as $row ) {
			$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result', 'time' ) );
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	protected function output_queries( $name, stdClass $db, array $data ) {

		$span = 4;

		if ( $db->has_result ) {
			$span++;
		}
		if ( $db->has_trace ) {
			$span++;
		}

		echo '<div class="bwqm bwqm-queries" id="' . esc_attr( $this->collector->id() ) . '-' . sanitize_title( $name ) . '">';
		echo '<table cellspacing="0" class="bwqm-sortable">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $span . '">' . sprintf( __( '%s Queries', BW_TD ), $name ) . '</th>';
		echo '</tr>';

		if ( !empty( $db->rows ) ) {

			if ( ! $db->has_trace ) {
				echo '<tr>';
				echo '<td colspan="' . $span . '" class="bwqm-warn">' . sprintf( __( 'Extended query information such as the component and affected rows is not available. Query Monitor was unable to symlink its <code>db.php</code> file into place. <a href="%s" target="_blank">See this wiki page for more information.</a>', BW_TD ),
					'https://github.com/johnbillion/query-monitor/wiki/db.php-Symlink'
				) . '</td>';
				echo '</tr>';
			}

			echo '<tr>';
			echo '<th scope="col" class="bwqm-sorted-asc">&nbsp;' . $this->build_sorter() . '</th>';
			echo '<th scope="col">' . __( 'Query', BW_TD ) . $this->build_filter( 'type', array_keys( $db->types ) ) . '</th>';
			echo '<th scope="col">' . __( 'Caller', BW_TD ) . $this->build_filter( 'caller', wp_list_pluck( $data['times'], 'caller' ) ) . '</th>';

			if ( $db->has_trace ) {
				echo '<th scope="col">' . __( 'Component', BW_TD ) . $this->build_filter( 'component', wp_list_pluck( $data['component_times'], 'component' ) ) . '</th>';
			}

			if ( $db->has_result ) {
				if ( empty( $data['errors'] ) ) {
					$class = 'bwqm-num';
				} else {
					$class = '';
				}
				echo '<th scope="col" class="' . $class . '">' . __( 'Rows', BW_TD ) . $this->build_sorter() . '</th>';
			}

			echo '<th scope="col" class="bwqm-num">' . __( 'Time', BW_TD ) . $this->build_sorter() . '</th>';
			echo '</tr>';

		}

		echo '</thead>';

		if ( !empty( $db->rows ) ) {

			echo '<tbody>';

			foreach ( $db->rows as $row ) {
				$this->output_query_row( $row, array( 'row', 'sql', 'caller', 'component', 'result', 'time' ) );
			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $db->total_time, 4 );

			echo '<tr class="bwqm-items-shown bwqm-hide">';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( __( 'Queries in filter: %s', BW_TD ), '<span class="bwqm-items-number">' . number_format_i18n( $db->total_qs ) . '</span>' ) . '</td>';
			echo "<td valign='top' class='bwqm-items-time bwqm-num'>{$total_stime}</td>";
			echo '</tr>';

			echo '<tr>';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( __( 'Total Queries: %s', BW_TD ), number_format_i18n( $db->total_qs ) ) . '</td>';
			echo "<td valign='top' class='bwqm-num'>{$total_stime}</td>";
			echo '</tr>';
			echo '</tfoot>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="' . $span . '" style="text-align:center !important"><em>' . __( 'none', BW_TD ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	protected function output_query_row( array $row, array $cols ) {

		$cols = array_flip( $cols );

		if ( !isset( $row['component'] ) ) {
			unset( $cols['component'] );
		}
		if ( !isset( $row['result'] ) ) {
			unset( $cols['result'] );
		}
		if ( !isset( $row['stack'] ) ) {
			unset( $cols['stack'] );
		}

		$row_attr = array();
		$stime = number_format_i18n( $row['ltime'], 4 );
		$td = $this->collector->is_expensive( $row ) ? ' bwqm-expensive' : '';

		$sql = self::format_sql( $row['sql'] );

		if ( 'SELECT' != $row['type'] ) {
			$sql = "<span class='bwqm-nonselectsql'>{$sql}</span>";
		}

		if ( is_wp_error( $row['result'] ) ) {
			$error  = $row['result']->get_error_message();
			$result = "<td valign='top' class='bwqm-row-result bwqm-row-error'>{$error}</td>\n";
			$row_attr['class'] = 'bwqm-warn';
		} else {
			$result = "<td valign='top' class='bwqm-row-result bwqm-num'>{$row['result']}</td>\n";
		}

		if ( isset( $row['trace'] ) ) {

			$caller         = $row['trace']->get_caller();
			$caller_name    = self::output_filename( $row['caller'], $caller['calling_file'], $caller['calling_line'] );
			$stack          = array();
			$filtered_trace = $row['trace']->get_filtered_trace();
			array_shift( $filtered_trace );

			foreach ( $filtered_trace as $item ) {
				$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
			}

		} else {

			$caller_name = $row['caller'];
			$stack       = explode( ', ', $row['stack'] );
			$stack       = array_reverse( $stack );
			array_shift( $stack );

		}

		if ( isset( $cols['sql'] ) ) {
			$row_attr['data-bwqm-type'] = $row['type'];
		}
		if ( isset( $cols['component'] ) ) {
			$row_attr['data-bwqm-component'] = $row['component']->name;
		}
		if ( isset( $cols['caller'] ) ) {
			$row_attr['data-bwqm-caller'] = $row['caller_name'];
		}
		if ( isset( $cols['time'] ) ) {
			$row_attr['data-bwqm-time'] = $row['ltime'];
		}

		$attr = '';

		foreach ( $row_attr as $a => $v ) {
			$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
		}

		echo "<tr{$attr}>";

		if ( isset( $cols['row'] ) ) {
			echo "<td valign='top' class='bwqm-row-num bwqm-num'>" . ++$this->query_row . "</td>";
		}

		if ( isset( $cols['sql'] ) ) {
			echo "<td valign='top' class='bwqm-row-sql bwqm-ltr bwqm-wrap'>{$sql}</td>";
		}

		if ( isset( $cols['caller'] ) ) {
			echo "<td valign='top' class='bwqm-row-caller bwqm-ltr bwqm-has-toggle'>";

			echo $caller_name;

			if ( !empty( $stack ) ) {
				echo '<a href="#" class="bwqm-toggle" data-on="+" data-off="-">+</a>';
				echo '<div class="bwqm-toggled">' . implode( '<br>', $stack ) . '</div>';
			}

			echo "</td>";
		}

		if ( isset( $cols['stack'] ) ) {
			echo '<td valign="top" class="bwqm-row-caller bwqm-row-stack bwqm-nowrap bwqm-ltr">' . $caller_name . '<br>' . implode( '<br>', $stack ) . '</td>';
		}

		if ( isset( $cols['component'] ) ) {
			echo "<td valign='top' class='bwqm-row-component bwqm-nowrap'>{$row['component']->name}</td>\n";
		}

		if ( isset( $cols['result'] ) ) {
			echo $result;
		}

		if ( isset( $cols['time'] ) ) {
			echo "<td valign='top' class='bwqm-num bwqm-row-time{$td}'>{$stime}</td>\n";
		}

		echo '</tr>';

	}

	public function admin_title( array $title ) {

		$data = $this->collector->get_data();

		if ( isset( $data['dbs'] ) ) {
			foreach ( $data['dbs'] as $db ) {
				$title[] = sprintf(
					_x( '%s<small>S</small>', 'database query time', BW_TD ),
					number_format_i18n( $db->total_time, 4 )
				);
				$title[] = sprintf(
					_x( '%s<small>Q</small>', 'database query number', BW_TD ),
					number_format_i18n( $db->total_qs )
				);
			}
		}
		return $title;
	}

	public function admin_class( array $class ) {

		if ( $this->collector->get_errors() ) {
			$class[] = 'bwqm-error';
		}
		if ( $this->collector->get_expensive() ) {
			$class[] = 'bwqm-expensive';
		}
		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		if ( $errors = $this->collector->get_errors() ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-errors',
				'href'  => '#bwqm-query-errors',
				'title' => sprintf( __( 'Database Errors (%s)', BW_TD ), number_format_i18n( count( $errors ) ) )
			) );
		}
		if ( $expensive = $this->collector->get_expensive() ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-expensive',
				'href'  => '#bwqm-query-expensive',
				'title' => sprintf( __( 'Slow Queries (%s)', BW_TD ), number_format_i18n( count( $expensive ) ) )
			) );
		}

		if ( isset( $data['dbs'] ) and count( $data['dbs'] ) > 1 ) {
			foreach ( $data['dbs'] as $name => $db ) {
				$menu[] = $this->menu( array(
					'title' => sprintf( __( 'Queries (%s)', BW_TD ), esc_html( $name ) ),
					'href'  => sprintf( '#%s-%s', $this->collector->id(), sanitize_title( $name ) ),
				) );
			}
		} else {
			$menu[] = $this->menu( array(
				'title' => __( 'Queries', BW_TD ),
				'href'  => sprintf( '#%s-wpdb', $this->collector->id() ),
			) );
		}

		return $menu;

	}

}

function register_bwqm_output_html_db_queries( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'db_queries' ) ) {
		$output['db_queries'] = new BWQM_Output_Html_DB_Queries( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_db_queries', 20, 2 );
