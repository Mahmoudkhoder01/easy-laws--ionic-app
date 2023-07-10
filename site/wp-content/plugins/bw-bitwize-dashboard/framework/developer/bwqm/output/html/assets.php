<?php

class BWQM_Output_Html_Assets extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus',      array( $this, 'admin_menu' ), 70 );
		add_filter( 'bwqm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['raw'] ) ) {
			return;
		}

		echo '<div class="bwqm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';

		foreach ( array(
			'scripts' => __( 'Scripts', BW_TD ),
			'styles'  => __( 'Styles', BW_TD ),
		) as $type => $type_label ) {

			echo '<thead>';

			if ( 'scripts' != $type ) {
				echo '<tr class="bwqm-totally-legit-spacer">';
				echo '<td colspan="6"></td>';
				echo '</tr>';
			}

			echo '<tr>';
			echo '<th colspan="2">' . $type_label . '</th>';
			echo '<th>' . __( 'Dependencies', BW_TD ) . '</th>';
			echo '<th>' . __( 'Dependents', BW_TD ) . '</th>';
			echo '<th>' . __( 'Version', BW_TD ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( array(
				'missing' => __( 'Missing %s', BW_TD ),
				'broken'  => __( 'Broken Dependencies', BW_TD ),
				'header'  => __( 'Header %s', BW_TD ),
				'footer'  => __( 'Footer %s', BW_TD ),
			) as $position => $position_label ) {

				if ( isset( $data[ $position ][ $type ] ) ) {
					$this->dependency_rows( $data[ $position ][ $type ], $data['raw'][ $type ], sprintf( $position_label, $type_label ) );
				}

			}

			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	protected function dependency_rows( array $handles, WP_Dependencies $dependencies, $label ) {

		$first = true;

		if ( empty( $handles ) ) {
			echo '<tr>';
			echo '<td valign="top" class="bwqm-nowrap">' . $label . '</td>';
			echo '<td valign="top" colspan="5"><em>' . __( 'none', BW_TD ) . '</em></td>';
			echo '</tr>';
			return;
		}

		foreach ( $handles as $handle ) {

			if ( in_array( $handle, $dependencies->done ) ) {
				echo '<tr data-bwqm-subject="' . $handle . '">';
			} else {
				echo '<tr data-bwqm-subject="' . $handle . '" class="bwqm-warn">';
			}

			if ( $first ) {
				$rowspan = count( $handles );
				echo "<th valign='top' rowspan='{$rowspan}' class='bwqm-nowrap'>" . $label . "</th>";
			}

			$this->dependency_row( $dependencies->query( $handle ), $dependencies );

			echo '</tr>';
			$first = false;
		}

	}

	protected function dependency_row( _WP_Dependency $script, WP_Dependencies $dependencies ) {

		if ( empty( $script->ver ) ) {
			$ver = '&nbsp;';
		} else {
			$ver = esc_html( $script->ver );
		}

		if ( empty( $script->src ) ) {
			$src = '&nbsp;';
		} else {
			$src = $script->src;
		}

		$dependents = self::get_dependents( $script, $dependencies );
		$deps = $script->deps;
		sort( $deps );

		foreach ( $deps as & $dep ) {
			if ( ! $dependencies->query( $dep ) ) {
				$dep = sprintf( __( '%s (missing)', BW_TD ), $dep );
			}
		}

		echo '<td valign="top" class="bwqm-wrap">' . $script->handle . '<br><span class="bwqm-info">' . $src . '</span></td>';
		echo '<td valign="top" class="bwqm-nowrap bwqm-highlighter" data-bwqm-highlight="' . implode( ' ', $deps ) . '">' . implode( '<br>', $deps ) . '</td>';
		echo '<td valign="top" class="bwqm-nowrap bwqm-highlighter" data-bwqm-highlight="' . implode( ' ', $dependents ) . '">' . implode( '<br>', $dependents ) . '</td>';
		echo '<td valign="top">' . $ver . '</td>';

	}

	protected static function get_dependents( _WP_Dependency $script, WP_Dependencies $dependencies ) {

		// @TODO move this into the collector
		$dependents = array();
		$handles    = array_unique( array_merge( $dependencies->queue, $dependencies->done ) );

		foreach ( $handles as $handle ) {
			if ( $item = $dependencies->query( $handle ) ) {
				if ( in_array( $script->handle, $item->deps ) ) {
					$dependents[] = $handle;
				}
			}
		}

		sort( $dependents );

		return $dependents;

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( !empty( $data['broken'] ) or !empty( $data['missing'] ) ) {
			// $class[] = 'bwqm-error';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();
		$args = array(
			'title' => $this->collector->name()
		);

		if ( !empty( $data['broken'] ) or !empty( $data['missing'] ) ) {
			$args['meta']['classname'] = 'bwqm-error';
		}

		$menu[] = $this->menu( $args );

		return $menu;

	}

}

function register_bwqm_output_html_assets( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'assets' ) ) {
		$output['assets'] = new BWQM_Output_Html_Assets( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_assets', 80, 2 );
