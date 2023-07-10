<?php


class BWQM_Output_Html_Debug_Bar extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 200 );
	}

	public function output() {

		$target = get_class( $this->collector->get_panel() );

		echo '<div class="bwqm bwqm-debug-bar" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html( $this->collector->name() ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td valign="top">';
		echo '<div id="debug-menu-target-' . esc_attr( $target ) . '" class="debug-menu-target bwqm-debug-bar-output">';

		$this->collector->render();

		echo '</div>';
		echo '</td>';
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_bwqm_output_html_debug_bar( array $output, BWQM_Collectors $collectors ) {
	global $debug_bar;

	if ( empty( $debug_bar ) ) {
		return $output;
	}

	foreach ( $debug_bar->panels as $panel ) {
		$panel_id  = strtolower( get_class( $panel ) );
		$collector = BWQM_Collectors::get( "debug_bar_{$panel_id}" );

		if ( $collector and $collector->is_visible() ) {
			$output["debug_bar_{$panel_id}"] = new BWQM_Output_Html_Debug_Bar( $collector );
		}
	}

	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_debug_bar', 200, 2 );
