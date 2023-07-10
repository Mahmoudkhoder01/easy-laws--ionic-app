<?php


class BWQM_Output_Html_Environment extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 110 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div id="' . esc_attr( $this->collector->id() ) . '">';

		echo '<div class="bwqm bwqm-half">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">PHP</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>version</td>';
		echo "<td>{$data['php']['version']}</td>";
		echo '</tr>';
		echo '<tr>';
		echo '<td>user</td>';
		if ( !empty( $data['php']['user'] ) ) {
			echo '<td>' . esc_html( $data['php']['user'] ) . '</td>';
		} else {
			echo '<td><em>' . __( 'Unknown', BW_TD ) . '</em></td>';
		}
		echo '</tr>';

		foreach ( $data['php']['variables'] as $key => $val ) {

			$append = '';

			if ( $val['after'] != $val['before'] ) {
				$append .= '<br><span class="bwqm-info">' . sprintf( __( 'Overridden at runtime from %s', BW_TD ), $val['before'] ) . '</span>';
			}

			echo '<tr>';
			echo "<td>{$key}</td>";
			echo "<td>{$val['after']}{$append}</td>";
			echo '</tr>';
		}

		$error_levels = implode( '<br>', $this->collector->get_error_levels( $data['php']['error_reporting'] ) );

		echo '<tr>';
		echo '<td>error_reporting</td>';
		echo "<td>{$data['php']['error_reporting']}<br><span class='bwqm-info'>{$error_levels}</span></td>";
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		if ( isset( $data['db'] ) ) {

			foreach ( $data['db'] as $id => $db ) {

				if ( 1 == count( $data['db'] ) ) {
					$name = 'Database';
				} else {
					$name = 'Database: ' . $id;
				}

				echo '<div class="bwqm bwqm-half">';
				echo '<table cellspacing="0">';
				echo '<thead>';
				echo '<tr>';
				echo '<th colspan="2">' . esc_html( $name ) . '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';

				echo '<tr>';
				echo '<td>driver</td>';
				echo '<td>' . $db['driver'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>version</td>';
				echo '<td>' . $db['version'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>user</td>';
				echo '<td>' . $db['user'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>host</td>';
				echo '<td>' . $db['host'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>database</td>';
				echo '<td>' . $db['name'] . '</td>';
				echo '</tr>';

				echo '<tr>';

				$first  = true;
				$warn   = __( "This value may not be optimal. Check the recommended configuration for '%s'.", BW_TD );
				$search = __( 'https://www.google.com/search?q=mysql+performance+%s', BW_TD );

				foreach ( $db['variables'] as $setting ) {

					$key = $setting->Variable_name;
					$val = $setting->Value;
					$prepend = '';
					$show_warning = false;

					if ( ( true === $db['vars'][$key] ) and empty( $val ) ) {
						$show_warning = true;
					} else if ( is_string( $db['vars'][$key] ) and ( $val !== $db['vars'][$key] ) ) {
						$show_warning = true;
					}

					if ( $show_warning ) {
						$prepend .= '&nbsp;<span class="bwqm-info">(<a href="' . esc_url( sprintf( $search, $key ) ) . '" target="_blank" title="' . esc_attr( sprintf( $warn, $key ) ) . '">' . __( 'Help', BW_TD ) . '</a>)</span>';
					}

					if ( is_numeric( $val ) and ( $val >= ( 1024*1024 ) ) ) {
						$prepend .= '<br><span class="bwqm-info">~' . size_format( $val ) . '</span>';
					}

					$class = ( $show_warning ) ? 'bwqm-warn' : '';

					if ( !$first ) {
						echo "<tr class='{$class}'>";
					}

					$key = esc_html( $key );
					$val = esc_html( $val );

					echo "<td>{$key}</td>";
					echo "<td>{$val}{$prepend}</td>";

					echo '</tr>';

					$first = false;

				}

				echo '</tbody>';
				echo '</table>';
				echo '</div>';

			}

		}

		echo '<div class="bwqm bwqm-half bwqm-clear">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">WordPress</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['wp'] as $key => $val ) {

			echo '<tr>';
			echo "<td>{$key}</td>";
			echo "<td>{$val}</td>";
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '<div class="bwqm bwqm-half">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'Server', BW_TD ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>software</td>';
		echo "<td>{$data['server']['name']}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td>version</td>';
		if ( !empty( $data['server']['version'] ) ) {
			echo '<td>' . esc_html( $data['server']['version'] ) . '</td>';
		} else {
			echo '<td><em>' . __( 'Unknown', BW_TD ) . '</em></td>';
		}
		echo '</tr>';

		echo '<tr>';
		echo '<td>address</td>';
		if ( !empty( $data['server']['address'] ) ) {
			echo '<td>' . esc_html( $data['server']['address'] ) . '</td>';
		} else {
			echo '<td><em>' . __( 'Unknown', BW_TD ) . '</em></td>';
		}
		echo '</tr>';

		echo '<tr>';
		echo '<td>host</td>';
		echo "<td>{$data['server']['host']}</td>";
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '</div>';

	}

}

function register_bwqm_output_html_environment( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'environment' ) ) {
		$output['environment'] = new BWQM_Output_Html_Environment( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_environment', 120, 2 );
