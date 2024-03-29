<?php


class BWQM_Collector_Assets extends BWQM_Collector {

	public $id = 'assets';

	public function __construct() {
		parent::__construct();
		add_action( 'admin_print_footer_scripts', array( $this, 'action_print_footer_scripts' ) );
		add_action( 'wp_print_footer_scripts',    array( $this, 'action_print_footer_scripts' ) );
		add_action( 'admin_head',                 array( $this, 'action_head' ), 999 );
		add_action( 'wp_head',                    array( $this, 'action_head' ), 999 );
		add_action( 'login_head',                 array( $this, 'action_head' ), 999 );
	}

	public function action_head() {
		global $wp_scripts, $wp_styles;

		$this->data['header']['styles'] = $wp_styles->done;
		$this->data['header']['scripts'] = $wp_scripts->done;

	}

	public function action_print_footer_scripts() {
		global $wp_scripts, $wp_styles;

		if ( empty( $this->data['header'] ) ) {
			return;
		}

		// @TODO remove the need for these raw scripts & styles to be collected
		$this->data['raw']['scripts'] = $wp_scripts;
		$this->data['raw']['styles']  = $wp_styles;

		$this->data['footer']['scripts'] = array_diff( $wp_scripts->done, $this->data['header']['scripts'] );
		$this->data['footer']['styles']  = array_diff( $wp_styles->done, $this->data['header']['styles'] );

	}

	public function process() {
		if ( !isset( $this->data['raw'] ) ) {
			return;
		}

		foreach ( array( 'scripts', 'styles' ) as $type ) {
			foreach ( array( 'header', 'footer' ) as $position ) {
				if ( empty( $this->data[ $position ][ $type ] ) ) {
					$this->data[ $position ][ $type ] = array();
				}
			}
			$raw = $this->data['raw'][ $type ];
			$broken = array_values( array_diff( $raw->queue, $raw->done ) );
			$missing = array();

			if ( !empty( $broken ) ) {
				foreach ( $broken as $key => $handle ) {
					if ( $item = $raw->query( $handle ) ) {
						$broken = array_merge( $broken, $this->get_broken_dependencies( $item, $raw ) );
					} else {
						unset( $broken[ $key ] );
						$missing[] = $handle;
					}
				}

				if ( !empty( $broken ) ) {
					$this->data['broken'][ $type ] = array_unique( $broken );
				}
			}

			if ( ! empty( $missing ) ) {
				$this->data['missing'][ $type ] = array_unique( $missing );
				foreach ( $this->data['missing'][ $type ] as $handle ) {
					$raw->add( $handle, false );
				}
			}

		}
	}

	protected function get_broken_dependencies( _WP_Dependency $item, WP_Dependencies $dependencies ) {

		$broken = array();

		foreach ( $item->deps as $handle ) {

			if ( $dep = $dependencies->query( $handle ) ) {
				$broken = array_merge( $broken, $this->get_broken_dependencies( $dep, $dependencies ) );
			} else {
				$broken[] = $item->handle;
			}

		}

		return $broken;

	}

	public function name() {
		return __( 'Scripts & Styles', BW_TD );
	}

}

function register_bwqm_collector_assets( array $collectors, BW_QueryMonitor $bwqm ) {
	$collectors['assets'] = new BWQM_Collector_Assets;
	return $collectors;
}

add_filter( 'bwqm/collectors', 'register_bwqm_collector_assets', 10, 2 );
