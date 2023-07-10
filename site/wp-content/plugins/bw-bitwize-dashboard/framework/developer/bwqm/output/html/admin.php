<?php


class BWQM_Output_Html_Admin extends BWQM_Output_Html {

	public function __construct( BWQM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'bwqm/output/menus', array( $this, 'admin_menu' ), 60 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['current_screen'] ) ) {
			return;
		}

		echo '<div class="bwqm bwqm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . esc_html( $this->collector->name() ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td class="bwqm-ltr">get_current_screen()</td>';
		echo '<td class="bwqm-has-inner">';

		echo '<table class="bwqm-inner" cellspacing="0">';
		echo '<tbody>';
		foreach ( $data['current_screen'] as $key => $value ) {
			echo '<tr>';
			echo '<td>' . esc_html( $key ) . '</td>';
			echo '<td>' . esc_html( $value ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';

		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td class="bwqm-ltr">$pagenow</td>';
		echo "<td>{$data['pagenow']}</td>";
		echo '</tr>';

		$screens = array(
			'edit'            => true,
			'edit-comments'   => true,
			'edit-tags'       => true,
			'link-manager'    => true,
			'plugins'         => true,
			'plugins-network' => true,
			'sites-network'   => true,
			'themes-network'  => true,
			'upload'          => true,
			'users'           => true,
			'users-network'   => true,
		);

		if ( !empty( $data['current_screen'] ) and isset( $screens[$data['current_screen']->base] ) ) {

			# And now, WordPress' legendary inconsistency comes into play:

			if ( !empty( $data['current_screen']->taxonomy ) ) {
				$col = $data['current_screen']->taxonomy;
			} else if ( !empty( $data['current_screen']->post_type ) ) {
				$col = $data['current_screen']->post_type . '_posts';
			} else {
				$col = $data['current_screen']->base;
			}

			if ( !empty( $data['current_screen']->post_type ) and empty( $data['current_screen']->taxonomy ) ) {
				$cols = $data['current_screen']->post_type . '_posts';
			} else {
				$cols = $data['current_screen']->id;
			}

			if ( 'edit-comments' == $col ) {
				$col = 'comments';
			} else if ( 'upload' == $col ) {
				$col = 'media';
			} else if ( 'link-manager' == $col ) {
				$col = 'link';
			}

			echo '<tr>';
			echo '<td rowspan="2">' . __( 'Column Filters', BW_TD ) . '</td>';
			echo "<td colspan='2'>manage_<span class='bwqm-current'>{$cols}</span>_columns</td>";
			echo '</tr>';
			echo '<tr>';
			echo "<td colspan='2'>manage_<span class='bwqm-current'>{$data['current_screen']->id}</span>_sortable_columns</td>";
			echo '</tr>';

			echo '<tr>';
			echo '<td rowspan="1">' . __( 'Column Action', BW_TD ) . '</td>';
			echo "<td colspan='2'>manage_<span class='bwqm-current'>{$col}</span>_custom_column</td>";
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_bwqm_output_html_admin( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'admin' ) ) {
		$output['admin'] = new BWQM_Output_Html_Admin( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/html', 'register_bwqm_output_html_admin', 70, 2 );
