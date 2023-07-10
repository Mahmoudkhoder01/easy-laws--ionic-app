<?php

if ( ! class_exists( 'BWVCAVideoRow' ) ) {

class BWVCAVideoRow {

	public static $videoID = 0;

	function __construct() {
		add_filter( 'init', array( $this, 'createRowShortcodes' ), 999 );

		add_shortcode( 'video_row', array( $this, 'createShortcode' ) );

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
		    "name" => __( 'Video Row Background', BW_TD ),
		    "base" => "video_row",
			"icon" => plugins_url( 'images/vc-video.png', __FILE__ ),
			"description" => __( 'Add a video bg to your row.', BW_TD ),
			"category" => __( 'Row*', BW_TD ),
		    "params" => array(
				array(
					"type" => "textfield",
					"class" => "",
					"heading" => __( "YouTube or Vimeo URL or Video ID", BW_TD ),
					"param_name" => "video",
					"value" => "",
					"description" => __( "Enter the URL to the video or the video ID of your YouTube or Vimeo video you want to use as your background. If your URL isn't showing a video, try inputting the video ID instead. <em>Ads will show up in the video if it has them.</em> <strong>Tip: newly uploaded videos may not display right away and might show an error message</strong><br><br><strong>Videos will not show up in mobile devices because they handle videos differently. In those cases, please put in a background image the normal way (in the <em>Design Options</em> tab in the row background) and that will be shown instead.</strong><br /><br />Only videos set as public or unlisted can be used, private videos will not work.", BW_TD ),
				),
				array(
					"type" => "checkbox",
					"class" => "",
					"heading" => __( "Mute Video", BW_TD ),
					"param_name" => "mute",
					"value" => array( __( "Mute the video.", BW_TD ) => "mute" ),
				),
				array(
					"type" => "checkbox",
					"class" => "",
					"heading" => __( "YouTube force HD", BW_TD ),
					"param_name" => "force_hd",
					"value" => array( __( "Force YouTube video to load in HD 720p. Vimeo plus or PRO can force HD loading via their video's settings.", BW_TD ) => "forcehd" ),
				),
				array(
					"type" => "textfield",
					"class" => "",
					"heading" => __( "Video Aspect Ratio", BW_TD ),
					"param_name" => "aspect_ratio",
					"value" => '16:9',
					"description" => __( "The video will be resized to maintain this aspect ratio, this is to prevent the video from showing any black bars. Enter an aspect ratio here such as: &quot;16:9&quot;, &quot;4:3&quot; or &quot;16:10&quot;. The default is &quot;16:9&quot;", BW_TD ),
				),
				array(
					"type" => "textfield",
					"class" => "",
					"heading" => __( "Opacity", BW_TD ),
					"param_name"  => "opacity",
					"value" => "100",
					"description" => __( "You may set the opacity level for your parallax. You can add a background color to your row and add an opacity here to tint your parallax. <strong>Please choose an integer value between 1 and 100.</strong>", BW_TD ),
				),
			),
		) );
	}


	public function createShortcode( $atts, $content = null ) {
        $defaults = array(
			'video' => '',
			'mute' => '',
			'force_hd' => '',
			'aspect_ratio' => '16:9',
			'opacity' => '100',
        );
		if ( empty( $atts ) ) {
			$atts = array();
		}
		$atts = array_merge( $defaults, $atts );

		if ( empty( $atts['video'] ) ) {
			return '';
		}

        wp_enqueue_script( 'bwvca_parallax', plugins_url( 'assets/js/min/script-min.js', __FILE__ ), array( 'jquery' ), BWD_VERSION, true );
        wp_enqueue_style( 'bwvca_parallax', plugins_url( 'assets/css/style.css', __FILE__ ), array(), BWD_VERSION );

		self::$videoID++;

		$videoMeta = self::getVideoProvider( $atts['video'] );
		if ( $videoMeta['type'] == 'youtube' ) {
            $videoDiv = "<div class='click-overrider'></div><div style='visibility: hidden' id='video-" . self::$videoID . "' data-youtube-video-id='" . esc_attr( $videoMeta['id'] ) . "' data-force-hd='" . ( $atts['force_hd'] == 'forcehd' ? 'true' : 'false' ) . "' data-mute='" . ( $atts['mute'] == 'mute' ? 'true' : 'false' ) . "' data-video-aspect-ratio='" . esc_attr( $atts['aspect_ratio'] ) . "'><div id='video-" . self::$videoID . "-inner'></div></div>";
        } else {
            $videoDiv = '<script src="//f.vimeocdn.com/js/froogaloop2.min.js"></script><div class="click-overrider"></div><div id="video-' . self::$videoID . '" data-vimeo-video-id="' . esc_attr( $videoMeta['id'] ) . '" data-mute="' . ( $atts['mute'] == 'mute' ? 'true' : 'false' ) . '" data-video-aspect-ratio="' . esc_attr( $atts['aspect_ratio'] ) . '"><iframe id="video-iframe-' . self::$videoID . '" src="//player.vimeo.com/video/' . $videoMeta['id'] . '?api=1&player_id=video-iframe-' . self::$videoID . '&html5=1&autopause=0&autoplay=1&badge=0&byline=0&loop=1&title=0" frameborder="0"></iframe></div>';
        }


		return  "<div class='bwvca_video_row' " .
	        "data-mute='" . esc_attr( $atts['mute'] ) . "' " .
	        "data-opacity='" . esc_attr( $atts['opacity'] ) . "' " .
			"style='display: none'>" .
			$videoDiv .
			"</div>";
	}


	/**
	 * Gets the Video ID & Provider from a video URL or ID
	 *
	 * @param 	$videoString string The URL or ID of a video
	 * @return	array container whether the video is a YouTube video or a Vimeo video along with the video ID
	 * @since	3.0
	 */
	protected static function getVideoProvider( $videoString ) {

		$videoString = trim( $videoString );

		/*
		 * Check for YouTube
		 */

		$videoID = false;
		if ( preg_match( '/youtube\.com\/watch\?v=([^\&\?\/]+)/', $videoString, $id ) ) {
			if ( count( $id > 1 ) ) {
				$videoID = $id[1];
			}
		} else if ( preg_match( '/youtube\.com\/embed\/([^\&\?\/]+)/', $videoString, $id ) ) {
			if ( count( $id > 1 ) ) {
				$videoID = $id[1];
			}
		} else if ( preg_match( '/youtube\.com\/v\/([^\&\?\/]+)/', $videoString, $id ) ) {
			if ( count( $id > 1 ) ) {
				$videoID = $id[1];
			}
		} else if ( preg_match( '/youtu\.be\/([^\&\?\/]+)/', $videoString, $id ) ) {
			if ( count( $id > 1 ) ) {
				$videoID = $id[1];
			}
		}

		if ( ! empty( $videoID ) ) {
			return array(
				'type' => 'youtube',
				'id' => $videoID
			);
		}

		/*
		 * Check for Vimeo
		 */

		if ( preg_match( '/vimeo\.com\/(\w*\/)*(\d+)/', $videoString, $id ) ) {
			if ( count( $id > 1 ) ) {
				$videoID = $id[ count( $id ) - 1 ];
			}
		}

		if ( ! empty( $videoID ) ) {
			return array(
				'type' => 'vimeo',
				'id' => $videoID
			);
		}

		/*
		 * Non-URL form
		 */

		if ( preg_match( '/^\d+$/', $videoString ) ) {
			return array(
				'type' => 'vimeo',
				'id' => $videoString
			);
		}

		return array(
			'type' => 'youtube',
			'id' => $videoString
		);
	}
}

new BWVCAVideoRow();

}
