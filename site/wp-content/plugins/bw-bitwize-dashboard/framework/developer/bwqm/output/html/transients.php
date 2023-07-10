<?php


class BWQM_Output_Html_Transients extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 100 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="bwqm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Transient Set', BW_TD ) . '</th>';
		if ( is_multisite() ) {
			echo '<th>' . __( 'Type', BW_TD ) . '</th>';
		}
		if ( !empty( $data['trans'] ) and isset( $data['trans'][0]['expiration'] ) ) {
			echo '<th>' . __( 'Expiration', BW_TD ) . '</th>';
		}
		echo '<th>' . __( 'Call Stack', BW_TD ) . '</th>';
		echo '<th>' . __( 'Component', BW_TD ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		if ( !empty( $data['trans'] ) ) {

			echo '<tbody>';

			foreach ( $data['trans'] as $row ) {
				$stack = $row['trace']->get_stack();
				$transient = str_replace( array(
					'_site_transient_',
					'_transient_'
				), '', $row['transient'] );
				$type = ( is_multisite() ) ? "<td valign='top'>{$row['type']}</td>\n" : '';
				if ( 0 === $row['expiration'] ) {
					$row['expiration'] = '<em>' . __( 'none', BW_TD ) . '</em>';
				}
				$expiration = ( isset( $row['expiration'] ) ) ? "<td valign='top'>{$row['expiration']}</td>\n" : '';

				$component = $row['trace']->get_component();

				$stack = implode( '<br>', $stack );
				echo "
					<tr>\n
						<td valign='top'>{$transient}</td>\n
						{$type}
						{$expiration}
						<td valign='top' class='bwqm-nowrap bwqm-ltr'>{$stack}</td>\n
						<td valign='top' class='bwqm-nowrap'>{$component->name}</td>\n
					</tr>\n
				";
			}

			echo '</tbody>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="4" style="text-align:center !important"><em>' . __( 'none', BW_TD ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$data  = $this->collector->get_data();
		$count = isset( $data['trans'] ) ? count( $data['trans'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'Transients Set', BW_TD )
			: __( 'Transients Set (%s)', BW_TD );

		$menu[] = $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( $count ) )
		) );
		return $menu;

	}

}

function register_bwqm_output_html_transients( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'transients' ) ) {
		$output['transients'] = new BWQM_Output_Html_Transients( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_transients', 100, 2 );
