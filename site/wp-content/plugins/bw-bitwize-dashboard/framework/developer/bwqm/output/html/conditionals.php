<?php


class BWQM_Output_Html_Conditionals extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 1000 );
	}

	public function output() {

		$data = $this->collector->get_data();

		$cols = 6;
		$i = 0;
		$w = floor( 100 / $cols );

		echo '<div class="bwqm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $cols . '">' . esc_html( $this->collector->name() ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['conds']['true'] as $cond ) {
			$i++;
			if ( 1 === $i%$cols ) {
				echo '<tr>';
			}
			echo '<td class="bwqm-ltr bwqm-true" width="' . $w . '%">' . $cond . '()</td>';
			if ( 0 === $i%$cols ) {
				echo '</tr>';
			}
		}

		foreach ( $data['conds']['false'] as $cond ) {
			$i++;
			if ( 1 === $i%$cols ) {
				echo '<tr>';
			}
			echo '<td class="bwqm-ltr bwqm-false" width="' . $w . '%">' . $cond . '()</td>';
			if ( 0 === $i%$cols ) {
				echo '</tr>';
			}
		}

		$fill = ( $cols - ( $i % $cols ) );
		if ( $fill and ( $fill != $cols ) ) {
			echo '<td colspan="' . $fill . '">&nbsp;</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		foreach ( $data['conds']['true'] as $cond ) {
			$menu[] = $this->menu( array(
				'title' => $cond . '()',
				'id'    => 'query-monitor-' . $cond,
				'meta'  => array( 'classname' => 'bwqm-true bwqm-ltr' )
			) );
		}

		return $menu;

	}

}

function register_bwqm_output_html_conditionals( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'conditionals' ) ) {
		$output['conditionals'] = new BWQM_Output_Html_Conditionals( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_conditionals', 50, 2 );
