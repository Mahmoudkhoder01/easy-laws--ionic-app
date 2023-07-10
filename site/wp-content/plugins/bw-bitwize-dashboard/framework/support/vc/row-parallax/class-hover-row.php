<?php

if ( ! class_exists( 'BWVCAHoverRow' ) ) {

class BWVCAHoverRow {

	function __construct() {
		add_filter( 'init', array( $this, 'createRowShortcodes' ), 999 );

		add_shortcode( 'hover_row', array( $this, 'createShortcode' ) );

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
		    "name" => __( 'Hover Row Background', BW_TD ),
		    "base" => "hover_row",
			"icon" => plugins_url( 'images/vc-hover.png', __FILE__ ),
			"description" => __( 'Add a hover bg to your row.', BW_TD ),
			"category" => __( 'Row*', BW_TD ),
		    "params" => array(
				array(
					"type" => "attach_image",
					"class" => "",
					"heading" => __( "Background Image", BW_TD ),
					"param_name" => "image",
					"description" => __( "Select your background image. <strong>Make sure that your image is of high resolution, we will resize the image to make it fit.</strong><br><strong>For optimal performance, try keeping your images close to 1600 x 900 pixels</strong>", BW_TD ),
				),
				array(
					"type" => "dropdown",
					"class" => "",
					"heading" => __( "Hover Type", BW_TD ),
					"param_name" => "type",
					"value" => array(
						__( "Move", BW_TD ) => "move",
						__( "Tilt", BW_TD ) => "tilt",
					),
					"description" => __( "Choose the type of effect when the row is hovered on.", BW_TD ),
				),
				array(
					"type" => "textfield",
					"class" => "",
					"heading" => __( "Move/Tilt Amount", BW_TD ),
					"param_name" => "amount",
					"value" => "30",
					"description" => __( "The move (pixels) or tilt (degrees) amount when the background is hovered on. For tilt types, the maximum allowed amount is <code>45 degrees</code>", BW_TD ),
				),
				array(
					"type" => "textfield",
					"class" => "",
					"heading" => __( "Opacity", BW_TD ),
					"param_name"  => "opacity",
					"value" => "100",
					"description" => __( "You may set the opacity level for your background. You can add a background color to your row and add an opacity here to tint your background. <strong>Please choose an integer value between 1 and 100.</strong>", BW_TD ),
				),
				array(
					"type" => "checkbox",
					"class" => "",
					"heading" => __( "Invert Move/Tilt Movement", BW_TD ),
					"param_name" => "inverted",
					"value" => array( __( "Check this to invert the movement of the effect with regards the direction of the mouse", BW_TD ) => "inverted" ),
				),
			),
		) );
	}


	public function createShortcode( $atts, $content = null ) {
        $defaults = array(
			'image' => '',
			'type' => 'move',
			'amount' => '30',
			'opacity' => '100',
			'inverted' => '',
        );
		if ( empty( $atts ) ) {
			$atts = array();
		}
		$atts = array_merge( $defaults, $atts );

		if ( empty( $atts['image'] ) ) {
			return '';
		}

        wp_enqueue_script( 'bwvca_parallax', plugins_url( 'assets/js/min/script-min.js', __FILE__ ), array( 'jquery' ), BWD_VERSION, true );
        wp_enqueue_style( 'bwvca_parallax', plugins_url( 'assets/css/style.css', __FILE__ ), array(), BWD_VERSION );

		// Jetpack issue, Photon is not giving us the image dimensions
		// This snippet gets the dimensions for us
		add_filter( 'jetpack_photon_override_image_downsize', '__return_true' );
		$imageInfo = wp_get_attachment_image_src( $atts['image'], 'full' );
		remove_filter( 'jetpack_photon_override_image_downsize', '__return_true' );

		$attachmentImage = wp_get_attachment_image_src( $atts['image'], 'full' );
		if ( empty( $attachmentImage ) ) {
			return '';
		}

		$bgImageWidth = $imageInfo[1];
		$bgImageHeight = $imageInfo[2];
		$bgImage = $attachmentImage[0];

		return  "<div class='bwvca_hover_row' " .
			"data-bg-image='" . esc_url( $bgImage ) . "' " .
			"data-type='" . esc_attr( $atts['type'] ) . "' " .
			"data-amount='" . esc_attr( $atts['amount'] ) . "' " .
	        "data-opacity='" . esc_attr( $atts['opacity'] ) . "' " .
			"data-inverted='" . esc_attr( empty( $atts['inverted'] ) ? 'false' : 'true' ) . "' " .
			"style='display: none'></div>";
	}
}

new BWVCAHoverRow();

}
