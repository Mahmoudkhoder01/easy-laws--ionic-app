<?php


class BWQM_Output_Html_Theme extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 60 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['stylesheet'] ) ) {
			return;
		}

		$child_theme = ( $data['stylesheet'] != $data['template'] );

		echo '<div class="bwqm bwqm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>' . __( 'Template File', BW_TD ) . '</td>';
		if ( $child_theme ) {
			echo '<td>' . self::output_filename( $data['theme_template'], $data['template_path'] ) . '</td>';
		} else {
			echo '<td>' . self::output_filename( $data['template_file'], $data['template_path'] ) . '</td>';
		}
		echo '</tr>';

		echo '<tr>';
		if ( $child_theme ) {
			echo '<td>' . __( 'Child Theme', BW_TD ) . '</td>';
		} else {
			echo '<td>' . __( 'Theme', BW_TD ) . '</td>';
		}
		echo '<td>' . esc_html( $data['stylesheet'] ) . '</td>';
		echo '</tr>';

		if ( $child_theme ) {
			echo '<tr>';
			echo '<td>' . __( 'Parent Theme', BW_TD ) . '</td>';
			echo '<td>' . esc_html( $data['template'] ) . '</td>';
			echo '</tr>';
		}

		if ( !empty( $data['body_class'] ) ) {

			echo '<tr>';
			echo '<td rowspan="' . count( $data['body_class'] ) . '">' . __( 'Body Classes', BW_TD ) . '</td>';
			$first = true;

			foreach ( $data['body_class'] as $class ) {

				if ( !$first ) {
					echo '<tr>';
				}

				echo '<td>' . esc_html( $class ) . '</td>';
				echo '</tr>';

				$first = false;

			}

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		if ( isset( $data['template_file'] ) ) {
			$menu[] = $this->menu( array(
				'title' => sprintf( __( 'Template: %s', BW_TD ), $data['template_file'] )
			) );
		}
		return $menu;

	}

}

function register_bwqm_output_html_theme( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'theme' ) ) {
		$output['theme'] = new BWQM_Output_Html_Theme( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_theme', 70, 2 );
