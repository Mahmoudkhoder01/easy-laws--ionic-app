<?php


class BWQM_Output_Html_PHP_Errors extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 10 );
		add_filter( 'bwqm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['errors'] ) ) {
			return;
		}

		echo '<div class="bwqm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'PHP Error', BW_TD ) . '</th>';
		echo '<th class="bwqm-num">' . __( 'Count', BW_TD ) . '</th>';
		echo '<th>' . __( 'Location', BW_TD ) . '</th>';
		echo '<th>' . __( 'Call Stack', BW_TD ) . '</th>';
		echo '<th>' . __( 'Component', BW_TD ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$types = array(
			'warning'    => __( 'Warning', BW_TD ),
			'notice'     => __( 'Notice', BW_TD ),
			'strict'     => __( 'Strict', BW_TD ),
			'deprecated' => __( 'Deprecated', BW_TD ),
		);

		foreach ( $types as $type => $title ) {

			if ( isset( $data['errors'][$type] ) ) {

				echo '<tr>';
				echo '<td rowspan="' . count( $data['errors'][$type] ) . '">' . $title . '</td>';
				$first = true;

				foreach ( $data['errors'][$type] as $error ) {

					if ( !$first ) {
						echo '<tr>';
					}

					$stack     = $error->trace->get_stack();
					$component = $error->trace->get_component();
					if ( $component ) {
						$name = $component->name;
					} else {
						$name = '<em>' . __( 'Unknown', BW_TD ) . '</em>';
					}
					$stack     = implode( '<br>', $stack );
					$message   = str_replace( "href='function.", "target='_blank' href='http://php.net/function.", $error->message );

					$output = esc_html( $error->filename ) . ':' . $error->line;

					echo '<td>' . $message . '</td>';
					echo '<td>' . number_format_i18n( $error->calls ) . '</td>';
					echo '<td>';
					echo self::output_filename( $output, $error->file, $error->line );
					echo '</td>';
					echo '<td class="bwqm-nowrap bwqm-ltr">' . $stack . '</td>';
					echo '<td class="bwqm-nowrap">' . $name . '</td>';
					echo '</tr>';

					$first = false;

				}

			}

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( isset( $data['errors']['warning'] ) ) {
			$class[] = 'bwqm-warning';
		} else if ( isset( $data['errors']['notice'] ) ) {
			$class[] = 'bwqm-notice';
		} else if ( isset( $data['errors']['strict'] ) ) {
			$class[] = 'bwqm-strict';
		} else if ( isset( $data['errors']['deprecated'] ) ) {
			$class[] = 'bwqm-deprecated';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		if ( isset( $data['errors']['warning'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-warnings',
				'title' => sprintf( __( 'PHP Warnings (%s)', BW_TD ), number_format_i18n( count( $data['errors']['warning'] ) ) )
			) );
		}
		if ( isset( $data['errors']['notice'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-notices',
				'title' => sprintf( __( 'PHP Notices (%s)', BW_TD ), number_format_i18n( count( $data['errors']['notice'] ) ) )
			) );
		}
		if ( isset( $data['errors']['strict'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-stricts',
				'title' => sprintf( __( 'PHP Stricts (%s)', BW_TD ), number_format_i18n( count( $data['errors']['strict'] ) ) )
			) );
		}
		if ( isset( $data['errors']['deprecated'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-deprecated',
				'title' => sprintf( __( 'PHP Deprecated (%s)', BW_TD ), number_format_i18n( count( $data['errors']['deprecated'] ) ) )
			) );
		}
		return $menu;

	}

}

function register_bwqm_output_html_php_errors( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'php_errors' ) ) {
		$output['php_errors'] = new BWQM_Output_Html_PHP_Errors( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_php_errors', 110, 2 );
