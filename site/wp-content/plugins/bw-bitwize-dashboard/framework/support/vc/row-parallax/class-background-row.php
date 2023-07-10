<?php

if ( ! class_exists( 'BWVCABackgroundRow' ) ) {

class BWVCABackgroundRow {

	function __construct() {
		add_filter( 'init', array( $this, 'createRowShortcodes' ), 999 );

		add_shortcode( 'background_row', array( $this, 'createShortcode' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'adminEnqueueScripts' ) );
	}


	public function adminEnqueueScripts() {
        wp_enqueue_style( 'bw_parallax_admin', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), BWD_VERSION );
	}


	public function createRowShortcodes() {
		if ( ! is_admin() ) {
			return;
		}
		if ( ! function_exists( 'vc_map' ) ) {
			return;
		}

		vc_map( array(
		    "name" => __( 'Row Background', BW_TD ),
		    "base" => "background_row",
			"icon" => plugins_url( 'images/vc-background.png', __FILE__ ),
			"description" => __( 'Add a background image or color to your row.', BW_TD ),
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
					"type" => "colorpicker",
					"class" => "",
					"heading" => __( "Background Color", BW_TD ),
					"param_name" => "color",
					"value" => '',
					"description" => __( "Choose a background color.", BW_TD ),
				),
				array(
					"type" => "dropdown",
					"class" => "",
					"heading" => __( "Background Position", BW_TD ),
					"param_name" => "background_position",
					"value" => array(
						__( "Center", BW_TD ) => "center",
						__( "Theme Default", BW_TD ) => "",
						__( "Left Top", BW_TD ) => "left top",
						__( "Left Center", BW_TD ) => "left center",
						__( "Left Bottom", BW_TD ) => "left bottom",
						__( "Right Top", BW_TD ) => "right top",
						__( "Right Center", BW_TD ) => "right center",
						__( "Right Bottom", BW_TD ) => "right bottom",
						__( "Center Top", BW_TD ) => "center top",
						__( "Center Bottom", BW_TD ) => "center bottom",
					),
				),
				array(
					"type" => "dropdown",
					"class" => "",
					"heading" => __( "Background Image Size", BW_TD ),
					"param_name" => "background_size",
					"value" => array(
						__( "Cover", BW_TD ) => "cover",
						__( "Theme Default", BW_TD ) => "",
						__( "Contain", BW_TD ) => "contain",
						__( "No Repeat", BW_TD ) => "no-repeat",
						__( "Repeat", BW_TD ) => "repeat",
					),
				),
			),
		) );
	}


	public function createShortcode( $atts, $content = null ) {
        $defaults = array(
			'image' => '',
			'color' => '',
			'background_size' => 'cover',
			'background_position' => 'center',
        );
		if ( empty( $atts ) ) {
			$atts = array();
		}
		$atts = array_merge( $defaults, $atts );

		if ( empty( $atts['image'] ) && empty( $atts['color'] ) ) {
			return '';
		}

        wp_enqueue_script( 'bwvca_parallax', plugins_url( 'assets/js/min/script-min.js', __FILE__ ), array( 'jquery' ), BWD_VERSION, true );

		$attachmentImage = wp_get_attachment_image_src( $atts['image'], 'full' );
		$imageURL = '';
		if ( ! empty( $attachmentImage ) ) {
			$imageURL = $attachmentImage[0];
		}

		$style = 'display: none;';
		if ( ! empty( $imageURL ) ) {
			$style .= 'background-image: url(' . esc_url( $imageURL ) . ');';
		}
		if ( ! empty( $atts['color'] ) ) {
			$style .= 'background-color: ' . esc_attr( $atts['color'] ) . ';';
		}
		if ( ! empty( $atts['background_size'] ) ) {
			if ( in_array( $atts['background_size'], array( 'cover', 'contain' ) ) ) {
				$style .= 'background-size: ' . esc_attr( $atts['background_size'] ) . ';';
			} else {
				$style .= 'background-repeat: ' . esc_attr( $atts['background_size'] ) . ';';
			}
		}
		if ( ! empty( $atts['background_position'] ) ) {
			$style .= 'background-position: ' . esc_attr( $atts['background_position'] ) . ';';
		}

		return  "<div class='bwvca_background_row' style='{$style}'></div>";
	}
}

new BWVCABackgroundRow();

}
