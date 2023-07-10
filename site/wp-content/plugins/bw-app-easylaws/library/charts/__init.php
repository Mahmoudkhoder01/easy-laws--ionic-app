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
    	$this->url = plugins_url('library/charts', APP_PLUGIN_FILE);
    }

	public function init(){
		add_action('admin_enqueue_scripts', array($this, 'add_assets' ));
		add_shortcode( 'app_chart', array($this, 'shortcode' ));
		add_shortcode( 'circle_loader'         , array( $this, 'render_circle_loader' ) );
 		add_shortcode( 'circle_loader_icon'    , array( $this, 'render_circle_loader_icon' ) );
 		add_shortcode( 'progress_bar'          , array( $this, 'render_progress_bar' ) );
 		add_shortcode( 'progress_bar_vertical' , array( $this, 'render_progress_bar_vertical' ) );
	}

	public function frontend(){
		add_action('wp_enqueue_scripts', array($this, 'add_assets' ));
	}

	function add_assets() {
		wp_enqueue_style('app-charts-css', $this->url.'/css.css', APP_VERSION);
		wp_enqueue_script('app-charts', $this->url.'/chart.min.js', 'jquery', APP_VERSION );
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

	function shuffle($arr = []){
		$keys = array_keys($arr);
		shuffle($keys);
		$new = [];
		foreach($keys as $key) {
		    $new[$key] = $arr[$key];
		}
		return $new;
	}

	function shortcode( $atts ) {
		$sep = "*|*";
		extract( shortcode_atts(
			array(
				'type'             => 'bar', //pie, doughnut, polarArea, bar, horizontalBar, line, radar
				'title'			   => '',
				'legend'		   => 'top', // top, right, bottom, left
				'labels'           => 'Jan,Feb,March',
				'data' 			   => '30,50,100'.$sep.'20,90,75', // FOR line,bar, radar
				'data_labels'      => 'Set a'.$sep.'set b', // FOR line,bar, radar
				'data_types'       => '', // bar *|* line FOR line, bar, radar (for mixed charts, use BAR base)
				'fill'             => '', // yes, no FOR line
				'colors'           => '',
			), $atts )
		);

		$name    = 'chart_'.uniqid();
		$default_colors = '#ff6384,#ff9f40,#ffcd56,#4bc0c0,#36a2eb,#9966ff,#c9cbcf';
		if($colors){
			$colors   = explode(',', str_replace(' ','',$colors));
		} else {
			$colors   = explode(',', $default_colors);
			$colors   = $this->shuffle($colors);
		}

		$labels = explode(',',$labels);

		$chart_data = "{labels: [";
		for ($j = 0; $j < count($labels); $j++ ) {
			$chart_data .= "'$labels[$j]'";
			$this->trailing_comma($j, count($labels), $chart_data);
		}
		$chart_data .= 	"], datasets : [";

		if ($type === 'line' || $type == 'bar' || $type == 'horizontalBar' || $type === 'radar') {
			$data = explode($sep, str_replace(' ', '', $data));
			$data_labels = explode($sep, $data_labels);
			$total    = count($data);
			$this->compare_fill($data, $colors);

			$data_types = $data_types ? explode($sep, str_replace(' ', '', $data_types)) : '';

			for ($i = 0; $i < $total; $i++) {
				$sub_type = $data_types ? $data_types[$i] : $type;
				$fill_lines = ($fill == 'yes') ? 1 : 0;

				$chart_data .= "{
					type: '{$sub_type}',
					label: '{$data_labels[$i]}',
					backgroundColor: 'rgba({$this->hex2rgb( $colors[$i] )},0.65)',
					borderColor: '{$colors[$i]}',
					borderWidth: 2,
					data: [{$data[$i]}],
					fill: {$fill_lines}
				}";
				$this->trailing_comma($i, $total, $chart_data);
			}
		} else {
			if(strpos($data, $sep) !== false){
				$data = explode($sep, $data)[0];
			}
			$data = explode(',', str_replace(' ', '', $data));
			$this->compare_fill($data, $colors);
			$dd = implode(',', $data);
			$dc = "'" . implode("','", $colors) . "'";
			$chart_data .= "{ data: [{$dd}], backgroundColor: [{$dc}] }";
		}
		$chart_data .=	']}';

		$title = $title ? "title:{ display:true, text:'{$title}' }," : '';

		$legend = ($legend && $legend !== 'none') ? "legend: { position: '{$legend}'}," : 'legend: false,';

		$type = str_replace('*', '', $type);

		return "
		<div style='width:100%;'><canvas id='$name'></canvas></div>
		<script>
		var {$name}_config = {
			type: '{$type}',
			options: {
                responsive: true,
                {$legend}
                {$title}
                tooltips: { mode: 'index', intersect: false },
                hover: { mode: 'nearest', intersect: true }
            },
            data: {$chart_data}
		}
		jQuery(document).ready(function($){
            var {$name}_ctx = document.getElementById('{$name}').getContext('2d');
            window.{$name}_chart = new Chart({$name}_ctx, {$name}_config);
        });
		</script>
		";
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
