<?php

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'BWD_Avatar' ) ) :
class BWD_Avatar
{
    protected static $instance;
	public function __construct(){}

	public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	public function init(){
		add_filter('get_avatar', array($this, 'get_avatar'), 11, 3);
        add_action('admin_enqueue_scripts',  array( $this, 'enqueue'));
	}

    public function enqueue(){
        wp_enqueue_script('holderjs', BWD_URL.'/framework/assets/js/holder.js', array('jquery'), BWD_VERSION, TRUE);
    }

	public function get_avatar($avatar, $id_or_email, $size){
		$user_id = 0;

        if( is_numeric( $id_or_email ) ) {
            $user_id = (int)$id_or_email;
        } elseif( is_object( $id_or_email ) ) {
            if( !empty( $id_or_email->user_id ) ) {
                $user_id = (int)$id_or_email->user_id;
            }
        } else {
            $user = get_user_by( 'email', $id_or_email );
            if( $user ) {
                $user_id = $user->ID;
            }
        }
		$user_info = get_userdata( $user_id );
		if($user_info){
			$name = $user_info->display_name;
		}else{
			$name = '+';
		}
		$initials = $this->get_img($name, $size);
		// $avatar = preg_replace( "/src=\"(.*?)\"/i", "src=\"" . $initials . "\"", $avatar );
		// $avatar = preg_replace( "/src='(.*?)'/i", "src='" . $initials . "'", $avatar );
		// $avatar = preg_replace( "/srcset=\"(.*?)\"/i", "srcset=\"" . $initials . "\"", $avatar );
		// $avatar = preg_replace( "/srcset='(.*?)'/i", "srcset='" . $initials . "'", $avatar );

        $avatar = preg_replace( "/src=\"(.*?)\"/i", "data-src=\"" . $initials . "\"", $avatar );
		$avatar = preg_replace( "/src='(.*?)'/i", "data-src='" . $initials . "'", $avatar );
		$avatar = preg_replace( "/srcset=\"(.*?)\"/i", "", $avatar );
		$avatar = preg_replace( "/srcset='(.*?)'/i", "", $avatar );
		return $avatar;
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
            'auto' => 'yes',
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

	public function get_img($name='', $size=128){
		if($name !== ''){
			$colors  = $this->generate_colors( $name );
			$bg = $colors['bgcolor'];
			$fg = $colors['color'];
			$text = $this->get_first_char( $name );
            if(strlen($text) > 2){
                $fs = round($size/4);
            } else {
                $fs = round($size/3);
            }
			// return BW_Placeholder()->base64($size, $size, $bgcolor, $color, $text);
            return $this->holder( array(
                'width' => $size,
                'height' => $size,
                'size' => $fs,
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
endif; // class_exists

function bwd_avatar() {
    return BWD_Avatar::instance();
}
bwd_avatar()->init();
$GLOBALS['BWD_Avatar'] = bwd_avatar();
