<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Disable rating for the Smooth Mousewheel plugin since we're including the plugin with parallax
defined( 'BW_DISABLE_SMOOTH_SCROLLING_RATING' ) or define( 'BW_DISABLE_SMOOTH_SCROLLING_RATING', '1' );

require_once( 'regen_thumbs.php' );
require_once( 'class-fullwidth-row.php' );
require_once( 'class-fullheight-row.php' );
require_once( 'class-parallax-row.php' );
require_once( 'class-video-row.php' );
require_once( 'class-hover-row.php' );
require_once( 'class-background-row.php' );

if ( ! class_exists( 'BWVCAParallaxBackgrounds' ) ) {

	class BWVCAParallaxBackgrounds {

		function __construct() {

			// Add plugin specific filters and actions here
			add_action( 'wp_head', array( $this, 'ie9Detector' ) );
		}

		public function ie9Detector() {
			echo "<!--[if IE 9]> <script>var _bwvcaParallaxIE9 = true;</script> <![endif]-->";
		}
	}


	new BWVCAParallaxBackgrounds();
}
