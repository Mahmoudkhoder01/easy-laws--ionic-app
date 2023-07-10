<?php


class BWQM_Output_Html_DB_Callers extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 30 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['types'] ) ) {
			return;
		}

		$total_time  = 0;
		$span = count( $data['types'] ) + 2;

		echo '<div class="bwqm bwqm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0" class="bwqm-sortable">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $span . '">' . esc_html( $this->collector->name() ) . '</th>';
		echo '</tr>';
		echo '<tr>';
		echo '<th>' . _x( 'Caller', 'Query caller', BW_TD ) . '</th>';

		foreach ( $data['types'] as $type_name => $type_count ) {
			echo '<th class="bwqm-num">' . $type_name . $this->build_sorter() . '</th>';
		}

		echo '<th class="bwqm-num bwqm-sorted-desc">' . __( 'Time', BW_TD ) . $this->build_sorter() . '</th>';
		echo '</tr>';
		echo '</thead>';

		if ( !empty( $data['times'] ) ) {

			echo '<tbody>';

			foreach ( $data['times'] as $row ) {
				$total_time  += $row['ltime'];
				$stime = number_format_i18n( $row['ltime'], 4 );

				echo '<tr>';
				echo "<td valign='top' class='bwqm-ltr'>{$row['caller']}</td>";

				foreach ( $data['types'] as $type_name => $type_count ) {
					if ( isset( $row['types'][$type_name] ) ) {
						echo "<td valign='top' class='bwqm-num'>" . number_format_i18n( $row['types'][$type_name] ) . "</td>";
					} else {
						echo "<td valign='top' class='bwqm-num'>&nbsp;</td>";
					}
				}

				echo "<td valign='top' class='bwqm-num'>{$stime}</td>";
				echo '</tr>';

			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $total_time, 4 );

			echo '<tr>';
			echo '<td>&nbsp;</td>';

			foreach ( $data['types'] as $type_name => $type_count ) {
				echo '<td class="bwqm-num">' . number_format_i18n( $type_count ) . '</td>';
			}

			echo "<td class='bwqm-num'>{$total_stime}</td>";
			echo '</tr>';

			echo '</tfoot>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="3" style="text-align:center !important"><em>' . __( 'none', BW_TD ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		if ( $dbq = BWQM_Collectors::get( 'db_queries' ) ) {
			$dbq_data = $dbq->get_data();
			if ( isset( $dbq_data['times'] ) ) {
				$menu[] = $this->menu( array(
					'title' => __( 'Queries by Caller', BW_TD )
				) );
			}
		}
		return $menu;

	}

}

function register_bwqm_output_html_db_callers( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'db_callers' ) ) {
		$output['db_callers'] = new BWQM_Output_Html_DB_Callers( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_db_callers', 30, 2 );
