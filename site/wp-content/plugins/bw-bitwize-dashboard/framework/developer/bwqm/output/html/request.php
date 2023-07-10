<?php


class BWQM_Output_Html_Request extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 50 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="bwqm bwqm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<tbody>';

		foreach ( array(
			'request'       => __( 'Request', BW_TD ),
			'matched_rule'  => __( 'Matched Rule', BW_TD ),
			'matched_query' => __( 'Matched Query', BW_TD ),
			'query_string'  => __( 'Query String', BW_TD ),
		) as $item => $name ) {

			if ( !isset( $data['request'][$item] ) ) {
				continue;
			}

			if ( ! empty( $data['request'][$item] ) ) {
				if ( in_array( $item, array( 'request', 'matched_query', 'query_string' ) ) ) {
					$value = self::format_url( $data['request'][$item] );
				} else {
					$value = esc_html( $data['request'][$item] );
				}
			} else {
				$value = '<em>' . __( 'none', BW_TD ) . '</em>';
			}

			echo '<tr>';
			echo '<td valign="top">' . $name . '</td>';
			echo '<td valign="top" colspan="2">' . $value . '</td>';
			echo '</tr>';
		}

		$rowspan = isset( $data['qvars'] ) ? count( $data['qvars'] ) : 1;

		echo '<tr>';
		echo '<td rowspan="' . $rowspan . '">' . __( 'Query Vars', BW_TD ) . '</td>';

		if ( !empty( $data['qvars'] ) ) {

			$first = true;

			foreach( $data['qvars'] as $var => $value ) {

				if ( !$first ) {
					echo '<tr>';
				}

				if ( isset( $data['plugin_qvars'][$var] ) ) {
					echo "<td valign='top'><span class='bwqm-current'>{$var}</span></td>";
				} else {
					echo "<td valign='top'>{$var}</td>";
				}

				if ( is_array( $value ) or is_object( $value ) ) {
					echo '<td valign="top"><pre>';
					print_r( $value );
					echo '</pre></td>';
				} else {
					$value = esc_html( $value );
					echo "<td valign='top'>{$value}</td>";
				}

				echo '</tr>';

				$first = false;

			}

		} else {

			echo '<td colspan="2"><em>' . __( 'none', BW_TD ) . '</em></td>';
			echo '</tr>';

		}

		if ( !empty( $data['multisite'] ) ) {

			$rowspan = count( $data['multisite'] );

			echo '<tr>';
			echo '<td rowspan="' . $rowspan . '">' . __( 'Multisite', BW_TD ) . '</td>';

			$first = true;

			foreach( $data['multisite'] as $var => $value ) {

				if ( !$first ) {
					echo '<tr>';
				}

				echo "<td valign='top'>{$var}</td>";

				echo '<td valign="top"><pre>';
				print_r( $value );
				echo '</pre></td>';

				echo '</tr>';

				$first = false;

			}
		}

		if ( !empty( $data['queried_object'] ) ) {

			$vars = get_object_vars( $data['queried_object'] );

			echo '<tr>';
			echo '<td valign="top">' . __( 'Queried Object', BW_TD ) . '</td>';
			echo '<td valign="top" colspan="2" class="bwqm-has-inner">';
			echo '<div class="bwqm-inner-toggle">' . $data['queried_object_title'] . ' (' . get_class( $data['queried_object'] ) . ' object) (<a href="#" class="bwqm-toggle" data-on="' . esc_attr__( 'Show', BW_TD ) . '" data-off="' . esc_attr__( 'Hide', BW_TD ) . '">' . __( 'Show', BW_TD ) . '</a>)</div>';

			echo '<div class="bwqm-toggled">';
			self::output_inner( $vars );
			echo '</div>';

			echo '</td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$data  = $this->collector->get_data();
		$count = isset( $data['plugin_qvars'] ) ? count( $data['plugin_qvars'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'Request', BW_TD )
			: __( 'Request (+%s)', BW_TD );

		$menu[] = $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( $count ) )
		) );
		return $menu;

	}

}

function register_bwqm_output_html_request( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'request' ) ) {
		$output['request'] = new BWQM_Output_Html_Request( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_request', 60, 2 );
