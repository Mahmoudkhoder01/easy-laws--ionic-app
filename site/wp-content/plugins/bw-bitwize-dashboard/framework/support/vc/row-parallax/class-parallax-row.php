<?php

if ( ! class_exists( 'BWVCAParallaxRow' ) ) {

class BWVCAParallaxRow {

	function __construct() {
		add_filter( 'init', array( $this, 'createRowShortcodes' ), 999 );

		add_shortcode( 'parallax_row', array( $this, 'createShortcode' ) );

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
		    "name" => __( 'Parallax Row Background', BW_TD ),
		    "base" => "parallax_row",
			"icon" => plugins_url( 'images/vc-parallax.png', __FILE__ ),
			"description" => __( 'Add a parallax bg to your row.', BW_TD ),
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
					"heading" => __( "Background Image Parallax", BW_TD ),
					"param_name" => "direction",
					"value" => array(
						"Up" => "up",
						"Down" => "down",
						"Left" => "left",
						"Right" => "right",
						"Fixed" => "fixed",
					),
					"description" => __( "Choose the direction of your parallax. <strong>Note that browsers render fixed directions very slow since they aren't hardware accelerated.</strong>", BW_TD ),
				),
				array(
					"type" => "textfield",
					"class" => "",
					"heading" => __( "Parallax Speed", BW_TD ),
					"param_name" => "speed",
					"value" => "0.3",
					"description" => __( "The movement speed, value should be between 0.1 and 1.0. A lower number means slower scrolling speed.", BW_TD ),
				),
				array(
					"type" => "dropdown",
					"class" => "",
					"heading" => __( "Background Style / Repeat", BW_TD ),
					"param_name" => "background_repeat",
					"value" => array(
						__( "Cover Whole Row (covers the whole row)", BW_TD ) => "",
						__( "Repeating Image Pattern", BW_TD ) => "repeat",
					),
					"description" => __( "Select whether the background image above should cover the whole row, or whether the image is a background seamless pattern.", BW_TD ),
				),
				array(
					"type" => "dropdown",
					"class" => "",
					"heading" => __( "Background Position / Alignment", BW_TD ),
					"param_name" => "background_position",
					"value" => array(
						__( "Centered", BW_TD ) => "",
						__( "Left (only applies to up, down parallax or fixed)", BW_TD ) => "left",
						__( "Right (only applies to up, down parallax or fixed)", BW_TD ) => "right",
						__( "Top (only applies to left or right parallax)", BW_TD ) => "top",
						__( "Bottom (only applies to left or right parallax)", BW_TD ) => "bottom",
					),
					"description" => __( "The alignment of the background / parallax image. Note that this most likely will only be noticeable in smaller screens, if the row is large enough, the image will most likely be fully visible. Use this if you want to ensure that a certain area will always be visible in your parallax in smaller screens.", BW_TD ),
				),
				array(
					"type" => "textfield",
					"class" => "",
					"heading" => __( "Opacity", BW_TD ),
					"param_name"  => "opacity",
					"value" => "100",
					"description" => __( "You may set the opacity level for your parallax. You can add a background color to your row and add an opacity here to tint your parallax. <strong>Please choose an integer value between 1 and 100.</strong>", BW_TD ),
				),
				array(
					"type" => "checkbox",
					"class" => "",
					"param_name" => "enable_mobile",
					"value" => array( __( "Check this to enable the parallax effect in mobile devices", BW_TD ) => "parallax-enable-mobile" ),
					"description" => __( "Parallax effects would most probably cause slowdowns when your site is viewed in mobile devices. If the device width is less than 980 pixels, then it is assumed that the site is being viewed in a mobile device.", BW_TD ),
				),
			),
		) );
	}


	public function createShortcode( $atts, $content = null ) {
        $defaults = array(
			'image' => '',
			'direction' => 'up',
			'speed' => '0.3',
			'background_repeat' => '',
			'background_position' => '',
			'opacity' => '100',
			'enable_mobile' => '',
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

		return  "<div class='bwvca_parallax_row' " .
			"data-bg-align='" . esc_attr( $atts['background_position'] ) . "' " .
			"data-direction='" . esc_attr( $atts['direction'] ) . "' " .
	        "data-opacity='" . esc_attr( $atts['opacity'] ) . "' " .
			"data-velocity='" . esc_attr( (float) $atts['speed'] * -1 ) . "' " .
			"data-mobile-enabled='" . esc_attr( $atts['enable_mobile'] ) . "' " .
			"data-bg-height='" . esc_attr( $bgImageHeight ) . "' " .
			"data-bg-width='" . esc_attr( $bgImageWidth ) . "' " .
			"data-bg-image='" . esc_attr( $bgImage ) . "' " .
			"data-bg-repeat='" . esc_attr( empty( $atts['background_repeat'] ) ? 'false' : 'true' ) . "' " .
			"style='display: none'></div>";
	}
}

new BWVCAParallaxRow();

}
