<?php

if ( ! class_exists( 'BWVCAParallaxFullheightRow' ) ) {

class BWVCAParallaxFullheightRow {

	function __construct() {
		add_filter( 'init', array( $this, 'createRowShortcodes' ), 999 );
		add_shortcode( 'fullheight_row', array( $this, 'createShortcode' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'adminEnqueueScripts' ) );
	}


	public function adminEnqueueScripts() {
        wp_enqueue_style( 'bwvca_parallax_admin', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), BWD_VERSION );
	}


	public function createRowShortcodes() {
		if ( ! is_admin() ) {
			return;
		}
		if ( ! function_exists( 'vc_map' ) ) {
			return;
		}

		vc_map( array(
		    "name" => __( 'Full-Height Row', BW_TD ),
		    "base" => "fullheight_row",
			"icon" => plugins_url( 'images/vc-fullheight.png', __FILE__ ),
			"description" => __( 'Add this to a row to make it full-height.', BW_TD ),
			"category" => __( 'Row*', BW_TD ),
		    "params" => array(
				array(
					"type" => "dropdown",
					"heading" => __( 'Row Content Location', BW_TD ),
					"param_name" => "content_location",
					"value" => array(
						__( 'Center', BW_TD ) => 'center',
						__( 'Top', BW_TD ) => 'top',
						__( 'Bottom', BW_TD ) => 'bottom',
					),
                    "description" => __( 'When your row height gets stretched, your content can be smaller than your row height. Choose the location here.<br><br><em>Please remove your row&apos;s top and bottom margins to make this work correctly.</em>', BW_TD ),
				),
			),
		) );
	}


	public function createShortcode( $atts, $content = null ) {
        $defaults = array(
			'content_location' => 'center',
        );
		if ( empty( $atts ) ) {
			$atts = array();
		}
		$atts = array_merge( $defaults, $atts );

        wp_enqueue_script( 'bwvca_parallax', plugins_url( 'assets/js/min/script-min.js', __FILE__ ), array( 'jquery' ), BWD_VERSION, true );
        wp_enqueue_style( 'bwvca_parallax', plugins_url( 'assets/css/style.css', __FILE__ ), array(), BWD_VERSION );

		// We just add a placeholder for this
		return '<div class="bwvca_fullheight_row" data-content-location="' . esc_attr( $atts['content_location'] ) . '" style="display: none"></div>';
	}
}

new BWVCAParallaxFullheightRow();

}
