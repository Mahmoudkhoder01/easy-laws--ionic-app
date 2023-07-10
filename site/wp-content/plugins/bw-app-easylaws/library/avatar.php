<?php
// Make sure holder.js library is enqueued http://holderjs.com/
class App_Avatar
{
    protected static $instance;
	public function __construct(){}

	public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function holder( $atts = array() ){
        $d = array(
            'width' => '300',
            'height' => '200',
            'bg' => '000000',
            'fg' => 'ffffff',
            'text' => '',
            'size' => '',
            'font' => '',
            'align' => '', // left, right
            'outline' => '', //yes
            'lineWrap' => '', // 0.5
            'auto' => 'yes'
        );
        $a = shortcode_atts( $d, $atts );
        $url = 'holder.js/'.$a['width'].'x'.$a['height'].'?';
        $b = '';
        unset($a['width']);
        unset($a['height']);
        foreach($a as $k => $v){
            if($a[$k]){
                $b .= $k.'='.$v.'&';
            }
        }
        $url .= rtrim($b,'&');
        return $url;
    }

	public function avatar($name='', $size=128){
        if(!$size || !is_numeric($size)) $size = 128;
		if($name !== ''){
			$colors  = $this->generate_colors( $name );
			$bg = $colors['bgcolor'];
			$fg = $colors['color'];
			$text = $this->get_first_char( $name );
			return $this->holder( array(
                'width' => $size,
                'height' => $size,
                'size' => round($size/3),
                'text' => $text,
                'bg' => $bg,
                'fg' => $fg,
            ));
		}
	}

	public function generate_colors( $string = '' ) {
		if ( '' == $string || strlen($string)<3 ){
			$red   = (int) mt_rand( 60, 230 );
			$blue  = (int) mt_rand( 60, 230 );
			$green = (int) mt_rand( 60, 230 );
		}else{
			$txt   = str_replace(' ','',$string);
			$arr   = str_split($txt);
			$count = count($arr);
			$mid   = (int) $count/2;
			$red   = (int) (60 + $this->rnd($arr[0]) );
			$blue  = (int) (60 + $this->rnd($arr[$mid]) );
			$green = (int) (60 + $this->rnd($arr[$count-1]) );
		}
		$bgcolor = dechex( $red ) . dechex( $blue ) . dechex( $green );
		$color   = 'ffffff';
		return compact( 'bgcolor', 'color' );
	}

	public function get_first_char( $string = '' ) {
		if ( '' == $string )
			return $string;
		$words = explode(" ", $string);
		$acronym = "";
		$m = 0;
		foreach ($words as $w) {
		 	if($m<3) $acronym .= $w[0];
		 	$m++;
		}
		return strtoupper($acronym);
	}

	public function letter_to_number($dest){
		if (is_numeric($dest)) return $dest;
        if ($dest)
            return ord(strtolower($dest)) - 96;
        else
            return 0;
    }

    public function rnd($n = ''){
    	if('' == $n) return 0;
    	$m = $this->letter_to_number($n);
    	$x = 230 - 60;
    	$v = (int) ($m*$m)/3;
    	if($v > $x) $v = $x;
    	return $v;
    }
}

function app_avatar() { return App_Avatar::instance(); }
$GLOBALS['app_avatar'] = app_avatar();
