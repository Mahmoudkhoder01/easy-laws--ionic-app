<?php

if( !defined( 'ABSPATH' ) )exit;
if( !class_exists( 'BWRowSeparator' ) ) {

    class BWRowSeparator
    {
        function __construct() {
            add_action( 'init', array( $this, 'createShortcode' ) , 999 );
            add_shortcode( 'row_separator', array( $this, 'renderShortcode' ) );
        }

        public function createShortcode() {
            if( !function_exists( 'vc_map' ) ) {
                return;
            }

            require_once( 'row_separators/lib/svgs.php' );

            $svgs = bwrow_row_separators_get_svgs();
            $svgValues = array();

            foreach( $svgs as $key => $svgData ) {
                $svgValues[$svgData['name']] = $key;
            }

            vc_map( array(
                "name" => __( 'Row Separator', BW_TD ),
                "base" => "row_separator",
                "icon" => plugins_url( 'row_separators/images/vc-row_separator.png', __FILE__ ),
                "description" => __( 'A cool top/bottom separator for your row.', BW_TD ),
                "admin_enqueue_css" => plugins_url( 'row_separators/css/admin.css', __FILE__ ),
                "category" => __( 'Row*', BW_TD ),
                "params" => array(
                    array(
                        "type" => "dropdown",
                        "holder" => "span",
                        "heading" => __( 'Row Separator Type / Location', BW_TD ),
                        "param_name" => "location",
                        "value" => array(
                            __( 'Top Separator', BW_TD ) => 'top',
                            __( 'Bottom Separator', BW_TD ) => 'bottom',
                        ) ,
                        "description" => __( 'Choose whether this is a top or bottom separator.', BW_TD ),
                    ) ,
                    array(
                        "type" => "dropdown",
                        "holder" => "span",
                        "heading" => __( 'Row Separator', BW_TD ),
                        "param_name" => "separator",
                        "value" => $svgValues,
                        "description" => __( 'Choose the design of the row separator.', BW_TD ),
                    ) ,
                    array(
                        "type" => "checkbox",
                        "heading" => __( 'Flip Separator Horizontally', BW_TD ),
                        "param_name" => "flip",
                        "value" => array(
                            __( 'Flip the separator horizontally', BW_TD ) => '1',
                        ) ,
                        "description" => __( 'You can flip the separator horizontally for more variation.', BW_TD ),
                    ) ,
                    array(
                        "type" => "textfield",
                        "heading" => __( 'Height Scale', BW_TD ),
                        "param_name" => "scale",
                        "value" => '1',
                        "description" => __( 'You can scale the separator to be larger or smaller. Use value between 0 and 1 to make the separator smaller, and more than 1 to make it larger.', BW_TD ),
                    ) ,
                    array(
                        "type" => "colorpicker",
                        "heading" => __( 'Decoration 1 Color', BW_TD ),
                        "param_name" => "color1",
                        "value" => '#95A5A6',
                        "description" => __( 'Separator designs have 1-2 decoration colors, pick the color for the first decoration here.', BW_TD ),
                    ) ,
                    array(
                        "type" => "colorpicker",
                        "heading" => __( 'Decoration 2 Color', BW_TD ),
                        "param_name" => "color2",
                        "value" => '#BDC3C7',
                        "description" => __( 'Separator designs have 1-2 decoration colors, pick the color for the second decoration here.', BW_TD ),
                    ) ,
                    array(
                        "type" => "textfield",
                        "heading" => __( 'Decoration 1 Opacity', BW_TD ),
                        "param_name" => "opacity1",
                        "value" => '1',
                        "description" => __( 'A value of 0-1. 0 means fully transparent, 1 means fully opaque. Put 0 here to remove the decor.', BW_TD ),
                    ) ,
                    array(
                        "type" => "textfield",
                        "heading" => __( 'Decoration 2 Opacity', BW_TD ),
                        "param_name" => "opacity2",
                        "value" => '1',
                        "description" => __( 'A value of 0-1. 0 means fully transparent, 1 means fully opaque. Put 0 here to remove the decor.', BW_TD ),
                    ) ,
                    array(
                        "group" => __( "Stroke & Outlines", BW_TD ),
                        "type" => "textfield",
                        "heading" => __( 'Main Outline / Stroke Thickness', BW_TD ),
                        "param_name" => "main_stroke_width",
                        "value" => '0',
                        "description" => __( 'Place a number greater than zero here to enable outlines', BW_TD ),
                    ) ,
                    array(
                        "group" => __( "Stroke & Outlines", BW_TD ),
                        "type" => "colorpicker",
                        "heading" => __( 'Main Outline / Stroke Color', BW_TD ),
                        "param_name" => "main_stroke_color",
                        "value" => '#222222',
                    ) ,
                    array(
                        "group" => __( "Stroke & Outlines", BW_TD ),
                        "type" => "textfield",
                        "heading" => __( 'Decoration 1 Outline / Stroke Thickness', BW_TD ),
                        "param_name" => "stroke1_width",
                        "value" => '0',
                        "description" => __( 'Place a number greater than zero here to enable outlines', BW_TD ),
                    ) ,
                    array(
                        "group" => __( "Stroke & Outlines", BW_TD ),
                        "type" => "colorpicker",
                        "heading" => __( 'Decoration 1 Outline / Stroke Color', BW_TD ),
                        "param_name" => "stroke1_color",
                        "value" => '#222222',
                    ) ,
                    array(
                        "group" => __( "Stroke & Outlines", BW_TD ),
                        "type" => "textfield",
                        "heading" => __( 'Decoration 2 Outline / Stroke Thickness', BW_TD ),
                        "param_name" => "stroke2_width",
                        "value" => '0',
                        "description" => __( 'Place a number greater than zero here to enable outlines', BW_TD ),
                    ) ,
                    array(
                        "group" => __( "Stroke & Outlines", BW_TD ),
                        "type" => "colorpicker",
                        "heading" => __( 'Decoration 2 Outline / Stroke Color', BW_TD ),
                        "param_name" => "stroke2_color",
                        "value" => '#222222',
                    ) ,
                ) ,
            ) );
        }

        public function renderShortcode( $atts, $content = null ) {
            $defaults = array(
                'location' => 'top',
                'separator' => 'slant-decor1',
                'flip' => '',
                'scale' => '1',
                'color1' => '#95A5A6',
                'color2' => '#BDC3C7',
                'opacity1' => '1',
                'opacity2' => '1',
                'main_stroke_width' => '0',
                'main_stroke_color' => '#222222',
                'stroke1_width' => '0',
                'stroke1_color' => '#222222',
                'stroke2_width' => '0',
                'stroke2_color' => '#222222',
            );
            if( empty( $atts ) ) {
                $atts = array();
            }
            $atts = array_merge( $defaults, $atts );

            require_once( 'row_separators/lib/svgs.php' );
            $svgs = bwrow_row_separators_get_svgs();

            if( empty( $svgs[$atts['separator']] ) ) {
                return '';
            }

            wp_enqueue_style( __CLASS__, plugins_url( 'row_separators/css/style.css', __FILE__ ), array() , VERSION_BW_TD );
            wp_enqueue_script( __CLASS__, plugins_url( 'row_separators/js/min/script-min.js', __FILE__ ), array(
                'jquery'
            ) , VERSION_BW_TD, true );

            $ret = '';

            $svgClasses = 'bwrow_separator';
            $svgClasses.= ' bwrow_sep_' . $atts['location'];
            $svgClasses.= empty( $atts['flip'] ) ? '' : ' bwrow_sep_flip';

            $svg = $svgs[$atts['separator']]['svg'];

            $height = (int)$svgs[$atts['separator']]['height'] * (float)$atts['scale'];

            $style = '';
            if( !empty( $atts['main_stroke_width'] ) && $atts['main_stroke_width'] !== '0' ) {
                $style.= "stroke-width: " . $atts['main_stroke_width'] . ";";
                $style.= "stroke:" . $atts['main_stroke_color'] . ";";
            }
            $svg = preg_replace( "/\{main\}/", $style, $svg );

            $style = '';
            if( !empty( $atts['opacity1'] ) && !empty( $atts['color1'] ) && $atts['opacity1'] !== '0' && $atts['color1'] !== 'transparent' ) {
                $style = "opacity: " . $atts['opacity1'] . ";";
                $style.= "fill: " . $atts['color1'] . ";";

                if( !empty( $atts['stroke1_width'] ) && $atts['stroke1_width'] !== '0' ) {
                    $style.= "stroke-width: " . $atts['stroke1_width'] . ";";
                    $style.= "stroke:" . $atts['stroke1_color'] . ";";
                }

                $svg = preg_replace( "/\{decor1\}/", $style, $svg );
            }
            else {

                $svg = preg_replace( '/[\s\n]?<[^>]+\{decor1\}[^>]+>[\s\n]?/', '', $svg );
            }

            $style = '';
            if( !empty( $atts['opacity2'] ) && !empty( $atts['color2'] ) && $atts['opacity2'] !== '0' && $atts['color2'] !== 'transparent' ) {
                $style = "opacity: " . $atts['opacity2'] . ";";
                $style.= "fill: " . $atts['color2'] . ";";

                if( !empty( $atts['stroke2_width'] ) && $atts['stroke2_width'] !== '0' ) {
                    $style.= "stroke-width: " . $atts['stroke2_width'] . ";";
                    $style.= "stroke:" . $atts['stroke2_color'] . ";";
                }

                $svg = preg_replace( "/\{decor2\}/", $style, $svg );
            }
            else {
                $svg = preg_replace( '/[\s\n]?<[^>]+\{decor2\}[^>]+>[\s\n]?/', '', $svg );
            }

            $viewBoxHeight = (int)$svgs[$atts['separator']]['height'];
            $viewBoxWidth = (int)$svgs[$atts['separator']]['width'];
            $ret.= '<svg preserveAspectRatio="none" class="' . $svgClasses . '" viewBox="0 0 ' . $viewBoxWidth . ' ' . $viewBoxHeight . '" style="display: none; width: 100%; height: calc(' . $height . ' / ' . $svgs[$atts['separator']]['width'] . ' * 100vw)" data-height="' . $height . '">' . $svg . "</svg>";

            return $ret;
        }
    }

    new BWRowSeparator();
}
?>
