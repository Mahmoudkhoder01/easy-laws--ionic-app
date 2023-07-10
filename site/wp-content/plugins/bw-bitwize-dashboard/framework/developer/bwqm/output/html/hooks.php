<?php


class BWQM_Output_Html_Hooks extends BWQM_Output_Html {

	public $id = 'hooks';

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 80 );
		add_filter( 'bwqm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['hooks'] ) ) {
			return;
		}

		$row_attr = array();

		if ( is_multisite() and is_network_admin() ) {
			$screen = preg_replace( '|-network$|', '', $data['screen'] );
		} else {
			$screen = $data['screen'];
		}

		echo '<div class="bwqm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Hook', BW_TD ) . $this->build_filter( 'name', $data['parts'] ) . '</th>';
		echo '<th colspan="3">' . __( 'Actions', BW_TD ) . $this->build_filter( 'component', $data['components'], 'subject' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['hooks'] as $hook ) {

			if ( !empty( $screen ) ) {

				if ( false !== strpos( $hook['name'], $screen . '.php' ) ) {
					$hook['name'] = str_replace( '-' . $screen . '.php', '-<span class="bwqm-current">' . $screen . '.php</span>', $hook['name'] );
				} else {
					$hook['name'] = str_replace( '-' . $screen, '-<span class="bwqm-current">' . $screen . '</span>', $hook['name'] );
				}

			}

			$row_attr['data-bwqm-name']      = implode( ' ', $hook['parts'] );
			$row_attr['data-bwqm-component'] = implode( ' ', $hook['components'] );

			$attr = '';

			if ( !empty( $hook['actions'] ) ) {
				$rowspan = count( $hook['actions'] );
			} else {
				$rowspan = 1;
			}

			foreach ( $row_attr as $a => $v ) {
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
			}

			if ( !empty( $hook['actions'] ) ) {

				$first = true;

				foreach ( $hook['actions'] as $action ) {

					if ( isset( $action['callback']['component'] ) ) {
						$component = $action['callback']['component']->name;
					} else {
						$component = '';
					}

					$trattr = $attr . ' data-bwqm-subject="' . esc_attr( $component ) . '"';

					echo "<tr{$trattr}>";

					if ( $first ) {

						echo "<th valign='top' rowspan='{$rowspan}'>";
						echo $hook['name'];
						if ( 'all' === $hook['name'] ) {
							echo '<br><span class="bwqm-warn">';
							_e( 'Warning: The <code>all</code> action is extremely resource intensive. Try to avoid using it.', BW_TD );
							echo '<span>';
						}
						echo '</th>';

					}

					echo '<td valign="top" class="bwqm-num">' . $action['priority'] . '</td>';
					echo '<td valign="top" class="bwqm-ltr">';

					if ( isset( $action['callback']['file'] ) ) {
						echo self::output_filename( esc_html( $action['callback']['name'] ), $action['callback']['file'], $action['callback']['line'] );
					} else {
						echo esc_html( $action['callback']['name'] );
					}

					if ( isset( $action['callback']['error'] ) ) {
						echo '<br><span class="bwqm-warn">';
						printf( __( 'Error: %s', BW_TD ),
							esc_html( $action['callback']['error']->get_error_message() )
						);
						echo '<span>';
					}

					echo '</td>';
					echo '<td valign="top" class="bwqm-nowrap">';
					echo esc_html( $component );
					echo '</td>';
					echo '</tr>';
					$first = false;
				}

			} else {
				echo "<tr{$attr}>";
				echo "<th valign='top'>";
				echo $hook['name'];
				echo '</th>';
				echo '<td colspan="3">&nbsp;</td>';
				echo '</tr>';
			}

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( isset( $data['warnings'] ) ) {
			$class[] = 'bwqm-warning';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();
		$args = array(
			'title' => $this->collector->name(),
		);

		if ( isset( $data['warnings'] ) ) {
			$args['meta']['classname'] = 'bwqm-warning';
		}

		$menu[] = $this->menu( $args );

		return $menu;

	}

}

function register_bwqm_output_html_hooks( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'hooks' ) ) {
		$output['hooks'] = new BWQM_Output_Html_Hooks( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_hooks', 80, 2 );
