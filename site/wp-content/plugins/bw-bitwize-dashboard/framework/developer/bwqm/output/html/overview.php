<?php


class BWQM_Output_Html_Overview extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/title', array( $this, 'admin_title' ), 10 );
	}

	public function output() {

		$data = $this->collector->get_data();

		$db_query_num   = null;
		$db_query_types = array();
		$db_queries     = BWQM_Collectors::get( 'db_queries' );

		if ( $db_queries ) {
			# @TODO: make this less derpy:
			$db_queries_data = $db_queries->get_data();
			if ( isset( $db_queries_data['types'] ) ) {
				$db_query_num = $db_queries_data['types'];
				$db_stime = number_format_i18n( $db_queries_data['total_time'], 4 );
			}
		}

		$total_stime = number_format_i18n( $data['time'], 4 );

		echo '<div class="bwqm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';

		$memory_usage = '<br><span class="bwqm-info">' . sprintf( __( '%1$s%% of %2$s kB limit', BW_TD ), number_format_i18n( $data['memory_usage'], 1 ), number_format_i18n( $data['memory_limit'] / 1024 ) ) . '</span>';

		$time_usage = '<br><span class="bwqm-info">' . sprintf( __( '%1$s%% of %2$ss limit', BW_TD ), number_format_i18n( $data['time_usage'], 1 ), number_format_i18n( $data['time_limit'] ) ) . '</span>';

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . __( 'Page generation time', BW_TD ) . '</th>';
		echo '<th scope="col">' . __( 'Peak memory usage', BW_TD ) . '</th>';
		if ( isset( $db_query_num ) ) {
			echo '<th scope="col">' . __( 'Database query time', BW_TD ) . '</th>';
			echo '<th scope="col">' . __( 'Database queries', BW_TD ) . '</th>';
		}
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr>';
		echo "<td>{$total_stime}{$time_usage}</td>";

		if ( empty( $data['memory'] ) ) {
			echo '<td><em>' . __( 'Unknown', BW_TD ) . '</em><br><span class="bwqm-info">' . __( 'Neither memory_get_peak_usage() nor memory_get_usage() are available. Speak to your host and get them to sort it out.', BW_TD ) . '</span></td>';
		} else {
			echo '<td>' . sprintf( __( '%s kB', BW_TD ), number_format_i18n( $data['memory'] / 1024 ) ) . $memory_usage . '</td>';
		}

		if ( isset( $db_query_num ) ) {
			echo "<td>{$db_stime}</td>";
			echo '<td>';

			foreach ( $db_query_num as $type_name => $type_count ) {
				$db_query_types[] = sprintf( '%1$s: %2$s', $type_name, number_format_i18n( $type_count ) );
			}

			echo implode( '<br>', $db_query_types );

			echo '</td>';
		}
		echo '</tr>';
		echo '</tbody>';

		echo '</table>';
		echo '</div>';

	}

	public function admin_title( array $title ) {

		$data = $this->collector->get_data();

		if ( empty( $data['memory'] ) ) {
			$memory = '??';
		} else {
			$memory = number_format_i18n( ( $data['memory'] / 1024 / 1024 ), 2 );
		}

		$title[] = sprintf(
			_x( '%s<small>S</small>', 'page load time', BW_TD ),
			number_format_i18n( $data['time'], 2 )
		);
		$title[] = sprintf(
			_x( '%s<small>MB</small>', 'memory usage', BW_TD ),
			$memory
		);
		return $title;
	}

}

function register_bwqm_output_html_overview( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'overview' ) ) {
		$output['overview'] = new BWQM_Output_Html_Overview( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_overview', 10, 2 );
