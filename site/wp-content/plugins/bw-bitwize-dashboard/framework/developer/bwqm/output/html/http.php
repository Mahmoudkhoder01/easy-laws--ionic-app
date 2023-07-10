<?php


class BWQM_Output_Html_HTTP extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 90 );
		add_filter( 'bwqm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		$total_time = 0;

		echo '<div class="bwqm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0" class="bwqm-sortable">';
		echo '<thead>';
		echo '<tr>';
		echo '<th class="bwqm-sorted-asc">&nbsp;' . $this->build_sorter() . '</th>';
		echo '<th scope="col">' . __( 'HTTP Request', BW_TD ) . '</th>';
		echo '<th scope="col">' . __( 'Response', BW_TD ) . $this->build_filter( 'type', array_keys( $data['types'] ) ) . '</th>';
		echo '<th scope="col">' . __( 'Transport', BW_TD ) . '</th>';
		echo '<th scope="col">' . __( 'Call Stack', BW_TD ) . '</th>';
		echo '<th scope="col">' . __( 'Component', BW_TD ) . $this->build_filter( 'component', wp_list_pluck( $data['component_times'], 'component' ) ) . '</th>';
		echo '<th scope="col" class="bwqm-num">' . __( 'Timeout', BW_TD ) . $this->build_sorter() . '</th>';
		echo '<th scope="col" class="bwqm-num">' . __( 'Time', BW_TD ) . $this->build_sorter() . '</th>';
		echo '</tr>';
		echo '</thead>';

		$vars = '';

		if ( !empty( $data['vars'] ) ) {
			$vars = array();
			foreach ( $data['vars'] as $key => $value ) {
				$vars[] = $key . ': ' . esc_html( $value );
			}
			$vars = implode( '<br>', $vars );
		}

		if ( !empty( $data['http'] ) ) {

			echo '<tbody>';
			$i = 0;

			foreach ( $data['http'] as $key => $row ) {
				$ltime = $row['ltime'];
				$i++;

				$row_attr = array();

				if ( empty( $ltime ) ) {
					$stime = '';
				} else {
					$stime = number_format_i18n( $ltime, 4 );
				}

				if ( is_wp_error( $row['response'] ) ) {
					$response = esc_html( $row['response']->get_error_message() );
					$css      = 'bwqm-warn';
				} else {
					$response = wp_remote_retrieve_response_code( $row['response'] );
					$msg      = wp_remote_retrieve_response_message( $row['response'] );
					$css      = '';

					if ( empty( $response ) ) {
						$response = __( 'n/a', BW_TD );
					} else {
						$response = esc_html( $response . ' ' . $msg );
					}

					if ( intval( $response ) >= 400 ) {
						$css = 'bwqm-warn';
					}

				}

				$method = $row['args']['method'];
				if ( !$row['args']['blocking'] ) {
					$method .= '&nbsp;' . _x( '(non-blocking)', 'non-blocking HTTP transport', BW_TD );
				}
				$url = self::format_url( $row['url'] );

				if ( isset( $row['transport'] ) ) {
					$transport = $row['transport'];
				} else {
					$transport = '';
				}

				$stack     = $row['trace']->get_stack();
				$component = $row['component'];

				$row_attr['data-bwqm-component'] = $component->name;
				$row_attr['data-bwqm-type']      = $row['type'];

				$attr = '';
				foreach ( $row_attr as $a => $v ) {
					$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
				}

				$stack = implode( '<br>', $stack );
				echo "
					<tr{$attr} class='{$css}'>\n
						<td valign='top' class='bwqm-num'>{$i}</td>
						<td valign='top' class='bwqm-url bwqm-ltr bwqm-wrap'>{$method}<br>{$url}</td>\n
						<td valign='top'>{$response}</td>\n
						<td valign='top'>{$transport}</td>\n
						<td valign='top' class='bwqm-nowrap bwqm-ltr'>{$stack}</td>\n
						<td valign='top' class='bwqm-nowrap'>{$component->name}</td>\n
						<td valign='top' class='bwqm-num'>{$row['args']['timeout']}</td>\n
						<td valign='top' class='bwqm-num'>{$stime}</td>\n
					</tr>\n
				";
			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $data['ltime'], 4 );

			echo '<tr>';
			echo '<td colspan="7">' . $vars . '</td>';
			echo "<td class='bwqm-num'>{$total_stime}</td>";
			echo '</tr>';
			echo '</tfoot>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="8" style="text-align:center !important"><em>' . __( 'none', BW_TD ) . '</em></td>';
			echo '</tr>';
			if ( !empty( $vars ) ) {
				echo '<tr>';
				echo '<td colspan="8">' . $vars . '</td>';
				echo '</tr>';
			}
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( isset( $data['errors']['error'] ) ) {
			$class[] = 'bwqm-error';
		} else if ( isset( $data['errors']['warning'] ) ) {
			$class[] = 'bwqm-warning';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		$count = isset( $data['http'] ) ? count( $data['http'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'HTTP Requests', BW_TD )
			: __( 'HTTP Requests (%s)', BW_TD );

		$args = array(
			'title' => sprintf( $title, number_format_i18n( $count ) ),
		);

		if ( isset( $data['errors']['error'] ) ) {
			$args['meta']['classname'] = 'bwqm-error';
		} else if ( isset( $data['errors']['warning'] ) ) {
			$args['meta']['classname'] = 'bwqm-warning';
		}

		$menu[] = $this->menu( $args );

		return $menu;

	}

}

function register_bwqm_output_html_http( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'http' ) ) {
		$output['http'] = new BWQM_Output_Html_HTTP( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_http', 90, 2 );
