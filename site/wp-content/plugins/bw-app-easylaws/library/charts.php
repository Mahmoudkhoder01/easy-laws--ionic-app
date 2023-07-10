<?php

if ( ! defined( 'ABSPATH' ) ) exit;

final class App_Charts
{
	public $url, $css;

	private static $_instance;
	public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct(){
    	$this->css = array();
    	$this->url = plugins_url('classes/admin/assets/js/charts', APP_PLUGIN_FILE);
    }

	public function init(){
		add_action('wp_enqueue_scripts', array($this, 'add_assets' ));
		add_action('admin_enqueue_scripts', array($this, 'add_assets' ));
		add_action('wp_head', array($this, 'html5_support'));
		add_shortcode('app_chart', array($this, 'shortcode' ));

		add_shortcode( 'circle_loader'         , array( $this, 'render_circle_loader' ) );
 		add_shortcode( 'circle_loader_icon'    , array( $this, 'render_circle_loader_icon' ) );
 		add_shortcode( 'progress_bar'          , array( $this, 'render_progress_bar' ) );
 		add_shortcode( 'progress_bar_vertical' , array( $this, 'render_progress_bar_vertical' ) );
	}

	function html5_support () {
	    echo '<!--[if lte IE 8]><script src="'.$this->url.'excanvas.compiled.js"></script><![endif]-->';
	    echo '<style>.app_charts_canvas {width:100%!important;max-width:100%;}@media screen and (max-width:480px) {div.app-chart-wrap {width:100%!important;float: none!important;margin-left: auto!important;margin-right: auto!important;text-align: center;}}</style>';
	}

	function add_assets() {
		wp_register_style('app-charts-css', $this->url.'/css.css', APP_VERSION);
		wp_register_script('app-charts', $this->url.'/chart.min.js', '', APP_VERSION );
		wp_register_script('app-chart-functions', $this->url.'/functions.js', 'jquery', APP_VERSION, true );

		if(is_admin()){
			wp_enqueue_style('app-charts-css');
			wp_enqueue_script('app-charts');
			wp_enqueue_script('app-chart-functions');
		}
	}

	function compare_fill(&$measure,&$fill) {
		if (count($measure) != count($fill)) {
		    while (count($fill) < count($measure) ) {
		        $fill = array_merge( $fill, array_values($fill) );
		    }
		    $fill = array_slice($fill, 0, count($measure));
		}
	}

	function hex2rgb($hex) {
	   $hex = str_replace("#", "", $hex);

	   if(strlen($hex) == 3) {
	      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	   } else {
	      $r = hexdec(substr($hex,0,2));
	      $g = hexdec(substr($hex,2,2));
	      $b = hexdec(substr($hex,4,2));
	   }

	   $rgb = array($r, $g, $b);
	   return implode(",", $rgb);
	}

	function trailing_comma($incrementor, $count, &$subject) {
		$stopper = $count - 1;
		if ($incrementor !== $stopper) {
			return $subject .= ',';
		}
	}

	function shortcode( $atts ) {
		extract( shortcode_atts(
			array(
				'type'             => 'line', //pie, doughnut, polarArea, bar, line, radar
				'name'             => 'app_chart',
				'title'			   => '',
				'subtitle'         => '',
				'canvaswidth'      => '625',
				'canvasheight'     => '625',
				'width'			   => '100%',
				'height'		   => 'auto',
				'margin'		   => '5px',
				'relativewidth'	   => '0',
				'align'            => '',
				'class'			   => '',
				'labels'           => 'Jan,Feb,March',
				'data'             => '30,50,100',
				'datasets'         => '30,50,100 next 20,90,75',
				'datasets_label'   => 'Set a next set b',
				'colors'           => '#69D2E7,#E0E4CC,#F38630,#96CE7F,#CEBC17,#CE4264',
				'fillopacity'      => '0.7',
				'pointstrokecolor' => '#FFFFFF',
				'animation'		   => 'true',
				'scalefontsize'    => '12',
				'scalefontcolor'   => '#666',
				'scaleoverride'    => 'false',
				'scalesteps' 	   => 'null',
				'scalestepwidth'   => 'null',
				'scalestartvalue'  => 'null'
			), $atts )
		);

		$name    = str_replace(' ', '', $name);
		$data     = explode(',', str_replace(' ', '', $data));
		$datasets = explode("next", str_replace(' ', '', $datasets));
		$datasets_label = explode(" next ", $datasets_label);
		if ($colors != "") {
			$colors   = explode(',', str_replace(' ','',$colors));
		} else {
			$colors = array('#69D2E7','#E0E4CC','#F38630','#96CE7F','#CEBC17','#CE4264');
		}
		$labelstrings = explode(',',$labels);
		(strpos($type, 'lar') !== false ) ? $type = 'PolarArea' : $type = ucwords($type);
		if($title) $title = "<h2>$title</h2>";
		if($subtitle) $subtitle = "<p>$subtitle</p>";

		$currentchart = '
		<div class="app_chart">
			'.$title.$subtitle.'
			<div class="'.$align.' '.$class.' app-chart-wrap" style="max-width: 100%; width:'.$width.'; height:'.$height.';margin:'.$margin.';" data-proportion="'.$relativewidth.'">
				<canvas id="'.$name.'" height="'.$canvasheight.'" width="'.$canvaswidth.'" class="app_charts_canvas" data-proportion="'.$relativewidth.'"></canvas>
			</div>
			<div id="'.$name.'Legend" class="app_legend"></div>
		</div>
		<script>';

		$currentchart .= 'var '.$name.'Ops = {
			animation: '.$animation;

		if ($type == 'Line' || $type == 'Radar' || $type == 'Bar' || $type == 'PolarArea') {
			$currentchart .=	',scaleFontSize: '.$scalefontsize.',';
			$currentchart .=	'scaleFontColor: "'.$scalefontcolor.'",';
			$currentchart .=    'scaleOverride:'   .$scaleoverride.',';
			$currentchart .=    'scaleSteps:' 	   .$scalesteps.',';
			$currentchart .=    'scaleStepWidth:'  .$scalestepwidth.',';
			$currentchart .=    'scaleStartValue:' .$scalestartvalue;
		}

		$currentchart .= '}; ';
		if ($type == 'Line' || $type == 'Radar' || $type == 'Bar' ) {
			$this->compare_fill($datasets, $colors);
			$total    = count($datasets);
			$currentchart .= 'var '.$name.'Data = {';
			$currentchart .= 'labels : [';

			for ($j = 0; $j < count($labelstrings); $j++ ) {
				$currentchart .= '"'.$labelstrings[$j].'"';
				$this->trailing_comma($j, count($labelstrings), $currentchart);
			}
			$currentchart .= 	'],';
			$currentchart .= 'datasets : [';
		} else {
			$this->compare_fill($data, $colors);
			$total = count($data);
			$currentchart .= 'var '.$name.'Data = [';
		}

		for ($i = 0; $i < $total; $i++) {
			if ($type === 'Pie' || $type === 'Doughnut' || $type === 'PolarArea') {
				$currentchart .= '{
					value 	    : '. $data[$i] .',
					color 	    : "'. $colors[$i].'",
					highlight 	: "rgba('. $this->hex2rgb($colors[$i]) .', 0.65)",
					label       : "'. $labelstrings[$i] . '"
				}';
			} else if ($type === 'Bar') {
				$currentchart .= '{
					fillColor 	: "rgba('. $this->hex2rgb( $colors[$i] ) .','.$fillopacity.')",
					strokeColor : "rgba('. $this->hex2rgb( $colors[$i] ) .',1)",
					data 		: ['.$datasets[$i].'],
					label       : "'.$datasets_label[$i].'"
				}';
			} else if ($type === 'Line' || $type === 'Radar') {
				$currentchart .= '{
					fillColor 	: "rgba('. $this->hex2rgb( $colors[$i] ) .','.$fillopacity.')",
					strokeColor : "rgba('. $this->hex2rgb( $colors[$i] ) .',1)",
					pointColor 	: "rgba('. $this->hex2rgb( $colors[$i] ) .',1)",
					pointStrokeColor : "'.$pointstrokecolor.'",
					data 		: ['.$datasets[$i].'],
					label       : "'.$datasets_label[$i].'"
				}';
			}
			$this->trailing_comma($i, $total, $currentchart);
		}

		if ($type == 'Line' || $type == 'Radar' || $type == 'Bar') {
			$currentchart .=	']};';
		} else {
			$currentchart .=	'];';
		}

		$currentchart .= '
			var AppChart'.$name.$type.' = new Chart(document.getElementById("'.$name.'").getContext("2d")).'.$type.'('.$name.'Data,'.$name.'Ops); document.getElementById("'.$name.'Legend").innerHTML = AppChart'.$name.$type.'.generateLegend();</script>
		';
		return $currentchart;
	}

	function random() {
	    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ";
	    $pass = array();
	    $alphaLength = strlen($alphabet) - 1;
	    for ($i = 0; $i < 8; $i++) {
	        $n = mt_rand(0, $alphaLength);
	        $pass[] = $alphabet[$n];
	    }
	    return implode($pass);
	}

	public function dynamic_embed_css($style_code){
	    if(!in_array($style_code, $this->css)){
			array_push($this->css, $style_code);
	    }
	    $this->dynamic_hook_embed_css();
	}

	public function dynamic_hook_embed_css(){
	    if(!empty($this->css)){
		    $code ="\n<!--dynamic styles generated App Shortcodes-->";
		    $code .= "<style type='text/css' scoped>";
	       foreach($this->css as $style_code){
	        $code .= $style_code."\n";
	       }
		    $code .="</style>\n";
		    echo $code;
	    }
	}

    public function render_circle_loader($atts, $content = null) {
		extract(shortcode_atts(array(
		'title'			   => '',
		'number'           => '50',
		'number_color'     => '',
	    'display_number'   => '',
	    'display_number_color'   => '#3CC1B4',
		'symbol'           => '%',
		'width'            => '10',
		'style'            => 'square',
		'track_color'      => '#eeeeee',
		'bar_color'        => '#a0dbe1',
		'link'			   => '',
		'custom_css_class' => '',
		'unique_id'        => ''

		), $atts));

		$content = do_shortcode(shortcode_unautop($content));
		$content = force_balance_tags($content);
		$unique_id = $this->random();

		if($link){
			$link = '<a href="'.$link.'"><i class="fa fa-plus-square"></i></a>';
		}

		$style_code = '
		.app-circle-loader.'.$unique_id.' h4.app-progress-dnumber {color: '.$display_number_color.' !important; font-weight: bold !important; font-size:16px !important;}
		.app-circle-loader.'.$unique_id.' h4.app-progress-dnumber a {color: '.$display_number_color.' !important; font-weight: bold !important; font-size:16px !important;}
		';

		$this->dynamic_embed_css($style_code);

		$output = '
			<div class="app-circle-loader tt-orbit-montserrat '.$unique_id.' '.$custom_css_class.'">
	    		<div class="easyPieChart app-circle-number" data-percent="'.$number.'" data-trackcolor="'.$track_color.'" data-barcolor="'.$bar_color.'" data-linewidth="'.$width.'" data-linecap="'.$style.'">
	          		<span class="app-circle-number-wrap">
	          			<span class="app-circle-number">'.$number.'</span>
	          			'.$symbol.'
	          		</span>
	      		</div>
	      		<div class="loader-details">
	      			<h4 class="app-progress-dnumber">'.$display_number.' '.$link.'</h4>
	      			<i>'.$title.'</i>
	      		</div>
	      	</div>
	    ';

		$style_code ='.app-circle-loader.'.$unique_id.' .app-circle-number-wrap {color:'.$number_color.';}';

		$this->dynamic_embed_css($style_code);

		return $output;
	 }// END shortcode

	public function render_circle_loader_icon($atts, $content = null) {
		extract(shortcode_atts(array(
		'number'           => '50',
		'icon'             => '',
		'icon_color'       => '#d3565a',
		'width'            => '10',
		'style'            => 'square',
		'track_color'      => '#eeeeee',
		'bar_color'        => '#a0dbe1',
		'custom_css_class' => '',
		'unique_id'        => '',
		), $atts));


		$content = do_shortcode(shortcode_unautop($content));
		$content = force_balance_tags($content);
		$unique_id = $this->random();

		if(!empty($icon)){
			$icon_output = '<i class="fa fa-'.$icon.'"></i>';
		}

		$output = '<div class="app-circle-loader-icon tt-orbit-montserrat '.$custom_css_class.' '.$unique_id.'""><div class="easyPieChart app-circle-icon" data-percent="'.$number.'" data-trackcolor="'.$track_color.'" data-barcolor="'.$bar_color.'" data-linewidth="'.$width.'" data-linecap="'.$style.'">'.$icon_output.'<canvas></canvas></div> <div class="loader-details">'.$content.'</div></div>';

		$style_code ='.app-circle-loader-icon.'.$unique_id.' .fa{color:'.$icon_color.';}';

		$this->dynamic_embed_css($style_code);

		return $output;
	}


    public function render_number_counter($atts, $content = null) {
		extract(shortcode_atts(array(
		'number'           => '125',
		'number_color'     => '#000',
		'title'            => 'Lorem Ipsum',
		'title_color'      => '#000',
		'divider_height'   => '4px',
		'divider_color'    => '#e1e1e1',
		'custom_css_class' => '',
		'unique_id'        => ''
		), $atts));

	    $title = do_shortcode(shortcode_unautop($title));
		$title = force_balance_tags($title);
		$unique_id = $this->random();

		$style_code = '.app-counter-wrap.'.$unique_id.' h3.app-counter {color: '.$number_color.';}
		.app-counter-wrap.'.$unique_id.' h3:after {background: '.$divider_color.';height: '.$divider_height.';}
		.app-counter-wrap.'.$unique_id.' h4 {color: '.$title_color.';}';

		$this->dynamic_embed_css($style_code);

		return '<div class="app-counter-wrap '.$custom_css_class.' '.$unique_id.'"><h3 class="app-counter app-zero">'.$number.'</h3><h4>'.$title.'</h4></div>';
	}

    public function render_progress_bar( $atts, $content = null ) {
		extract(shortcode_atts(array(
		  'title'            => 'Lorem Ipsum',
		  'title_color'      => '#000',
		  'number'           => '50',
		  'number_color'     => '#000',
		  'track_color'      => '#e1e1e1',
		  'bar_color'        => '#a2dce2',
		  'symbol'           => '%',
		  'custom_css_class' => '',
		  'unique_id'        => ''
		), $atts));

	    $title = do_shortcode(shortcode_unautop($title));
		$title = force_balance_tags($title);
		$unique_id = $this->random();

		$style_code = '.app-progress-section.'.$unique_id.' h4.pull-left {color: '.$title_color.';}
		.app-progress-section.'.$unique_id.' h4.pull-right {color: '.$number_color.';}
		.app-progress-section.'.$unique_id.' .progress {background: '.$track_color.';}
		.app-progress-section.'.$unique_id.' .progress-bar {background: '.$bar_color.';}';

		$this->dynamic_embed_css($style_code);

		 return '<div class="app-progress-section '.$custom_css_class.' '.$unique_id.'"><div class="progress-title clearfix"><h4 class="pull-left">'.$title.'</h4><h4 class="pull-right"><span class="app-progress-number"><span>'.$number.'</span></span>'.$symbol.'</h4></div><div class="progress"><div class="progress-bar" data-number="'.$number.'"></div></div></div>';

	}

	public function render_progress_bar_vertical($atts, $content = null) {
		extract(shortcode_atts(array(
		'title'            => 'Lorem Ipsum',
	    'title_color'      => '#000',
	    'display_number'   => '',
	    'display_number_color'   => '#3CC1B4',
	    'number'           => '50',
	    'number_color'     => '#535c69',
	    'track_color'      => '#EEF2F4',
	    'bar_color'        => '#a2dce2',
	    'symbol'           => '%',
	    'link' 			   => '',
	    'custom_css_class' => '',
		'unique_id'        => ''
		), $atts));

	    $title = do_shortcode(shortcode_unautop($title));
		$title = force_balance_tags($title);
		$unique_id = $this->random();

		if($link){
			$link = '<a href="'.$link.'"><i class="fa fa-plus-square"></i></a>';
		}

		$style_code = '
		.app-progress-section-vertical.'.$unique_id.' h4.app-progress-dnumber {color: '.$display_number_color.' !important; font-weight: bold !important; font-size:16px !important;}
		.app-progress-section-vertical.'.$unique_id.' h4.app-progress-dnumber a {color: '.$display_number_color.' !important; font-weight: bold !important; font-size:16px !important;}
		.app-progress-section-vertical.'.$unique_id.' h4.app-progress-title {color: '.$title_color.';}
		.app-progress-section-vertical.'.$unique_id.' h4.app-progress-text {color: '.$number_color.';}
		.app-progress-section-vertical.'.$unique_id.' .progress-wrapper {background: '.$track_color.';}
		.app-progress-section-vertical.'.$unique_id.' .progress-bar-vertical {background: '.$bar_color.';}
		';

		$this->dynamic_embed_css($style_code);

		return '
			<div class="app-progress-section-vertical '.$custom_css_class.' '.$unique_id.'">
				<div class="progress-wrapper">
					<div class="progress-bar-vertical" data-number="'.$number.'"></div>
				</div>
				<h4 class="app-progress-dnumber">'.$display_number.' '.$link.'</h4>
				<p><i class="app-progress-title">'.$title.'</i></p>
				<h4 class="app-progress-text">
					<span class="app-progress-number">
						<span>'.$number.'</span>
					</span>
					'.$symbol.'
				</h4>
			</div>';
	}

}
function app_charts() {return App_Charts::instance();}
$GLOBALS['app_charts'] = app_charts();
app_charts()->init();
