<?php

function bwd_get_option($slug = '', $default = ''){
    if($slug){
        $o = get_option('BWDO');
        if(isset($o[$slug])) return $o[$slug];
    }
    return $default;
}

if(!function_exists('bw_die')){
	function bw_die_code($code = NULL) {
		switch ($code) {
			case 100: $text = 'Continue'; break;
			case 101: $text = 'Switching Protocols'; break;
			case 200: $text = 'OK'; break;
			case 201: $text = 'Created'; break;
			case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            default: $text = ''; break;
        }
        return $text;
    }

    function bw_die($msg, $code=403){
    	$msg = '<p class="hint">'.$code.': '.bw_die_code($code).'</p><p>'.$msg.'</p>';
        $h = '
            <html>
            <head>
            <title>'.bw_die_code($code).'</title>
            <style>
                body{background:#eee; font: 24px/28px "Arial", Sans-Serif; color:#444; margin:0; padding:0;}
                div.outer{ height:100vh; position:relative; overflow-x:hidden; }
                div.inner{
                    position:absolute;
                    z-index:1;left:0;
                    top:50vh;
                    -webkit-transform:translateY(-50%);
                    -moz-transform:translateY(-50%);
                    -ms-transform:translateY(-50%);
                    -o-transform:translateY(-50%);
                    transform:translateY(-50%);
                    width:100%;
                    text-align:center;
                    opacity:1;
                }
                .cont{display: inline-block; vertical-align: middle; padding:50px; background:#fff; border-radius: 5px; max-width:65%;}
                .hint{font-size:60%; font-weight:bold; color: #888;}
            </style>
            </head>
            <body>
                <div class="outer"><div class="inner"><div class="cont">'.$msg.'</div></div></div>
            </body></html>
        ';

        status_header( $code );
        die($h); exit();
    }
}

if(!function_exists('remove_http')){
	function remove_http($url = ''){
	    if ($url == 'http://' OR $url == 'https://'){
	        return $url;
	    }
	    $matches = substr($url, 0, 7);
	    if ($matches=='http://'){
	        $url = substr($url, 7);
	    }else{
	        $matches = substr($url, 0, 8);
	        if ($matches=='https://')
	            $url = substr($url, 8);
	        }
	    return $url;
	}
}

if(!function_exists('bw_is_url')){
	function bw_is_url($url=''){
		if(filter_var($url, FILTER_VALIDATE_URL) === FALSE){
		    return false;
		}else{
		    return true;
		}
	}
}

/* THUMBNAIL FUNCTIONS */
if(!function_exists('bw_thumburl')){
	function bw_thumburl($postID, $width=NULL, $height=NULL, $selector='bw_thumbnail_image',$single=true){
		global $bw_theme_options;
		$options = $bw_theme_options;
		if($selector=='' || is_null($selector)) $selector = 'bw_thumbnail_image';


		$thumb_image = get_post_thumbnail_id($postID);
		$thumb_img_url = wp_get_attachment_url( $thumb_image, 'full' );

		$image = '';
		if ($thumb_img_url=="") {
			$image = bw_resize( $thumb_img_url, $width, $height, true, false);
		}

		if($image){

			if($single) {
				$img = $image[0];
			} else {
				$img = array (
					0 => $image[0],
					1 => $image[1],
					2 => $image[2]
				);
			}
			return $img;
		}
		return;
	}
}

if(!function_exists('bw_thumb_by_id')){
	function bw_thumb_by_id($id,$w = null,$h = null){
		$url = wp_get_attachment_url($id, 'full');
		//return $url;
		if($url){
			$crop = bw_resize($url,$w,$h);
			return $crop;
		}
		return '';
	}
}

if(!function_exists('date_is_between')){
	function date_is_between($from, $to, $date = 'now') {
	    $date = is_int($date) ? $date : strtotime($date); // convert non timestamps
	    $from = is_int($from) ? $from : strtotime($from); // ..
	    $to = is_int($to) ? $to : strtotime($to);         // ..
	    return ($date > $from) && ($date < $to); // extra parens for clarity
	}
}

	/* RESET ROLES TO WP DEFAULTS
	================================================== */
if(!function_exists('bw_roles_reset')){
	function bw_roles_reset() {
        global $wp_roles;
        if ( ! isset( $wp_roles ) ) $wp_roles = new WP_Roles();
        $wp_roles->roles = array();
        $wp_roles->role_objects = array();
        $wp_roles->role_names = array();
        $wp_roles->use_db = true;

        require_once(ABSPATH . '/wp-admin/includes/schema.php');
        populate_roles();
        $wp_roles->reinit();
    }
}

/* HEX TO RGB COLOR
================================================== */
if(!function_exists('bw_hex2rgb')){
	function bw_hex2rgb( $colour ) {
	        if ( $colour[0] == '#' ) {
	                $colour = substr( $colour, 1 );
	        }
	        if ( strlen( $colour ) == 6 ) {
	                list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
	        } elseif ( strlen( $colour ) == 3 ) {
	                list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
	        } else {
	                return false;
	        }
	        $r = hexdec( $r );
	        $g = hexdec( $g );
	        $b = hexdec( $b );
	        return array( 'red' => $r, 'green' => $g, 'blue' => $b );
	}
}


/* GET COMMENTS COUNT TEXT
================================================== */
if(!function_exists('bw_get_comments_number')){
	function bw_get_comments_number($post_id) {
		$num_comments = get_comments_number($post_id); // get_comments_number returns only a numeric value
		$comments_text = "";

		if ( $num_comments == 0 ) {
			$comments_text = __('0 Comments', 'bitwizeframework');
		} elseif ( $num_comments > 1 ) {
			$comments_text = $num_comments . __(' Comments', 'bitwizeframework');
		} else {
			$comments_text = __('1 Comment', 'bitwizeframework');
		}

		return $comments_text;
	}
}

if(!function_exists('bw_hyperlinks')){
	function bw_hyperlinks($text) {
	    $text = preg_replace('/\b([a-zA-Z]+:\/\/[\w_.\-]+\.[a-zA-Z]{2,6}[\/\w\-~.?=&%#+$*!]*)\b/i',"<a href=\"$1\" class=\"twitter-link\">$1</a>", $text);
	    $text = preg_replace('/\b(?<!:\/\/)(www\.[\w_.\-]+\.[a-zA-Z]{2,6}[\/\w\-~.?=&%#+$*!]*)\b/i',"<a href=\"http://$1\" class=\"twitter-link\">$1</a>", $text);
	    // match name@address
	    $text = preg_replace("/\b([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})\b/i","<a href=\"mailto://$1\" class=\"twitter-link\">$1</a>", $text);
        //mach #trendingtopics. Props to Michael Voigt
	    $text = preg_replace('/([\.|\,|\:|\¡|\¿|\>|\{|\(]?)#{1}(\w*)([\.|\,|\:|\!|\?|\>|\}|\)]?)\s/i', "$1<a href=\"http://twitter.com/#search?q=$2\" class=\"twitter-link\">#$2</a>$3 ", $text);
	    return $text;
	}
}

if(!function_exists('bw_twitter_users')){
	function bw_twitter_users($text) {
	    $text = preg_replace('/([\.|\,|\:|\¡|\¿|\>|\{|\(]?)@{1}(\w*)([\.|\,|\:|\!|\?|\>|\}|\)]?)\s/i', "$1<a href=\"http://twitter.com/$2\" class=\"twitter-user\">@$2</a>$3 ", $text);
	    return $text;
	}
}

if(!function_exists('bw_encode_tweet')){
    function bw_encode_tweet($text) {
        $text = mb_convert_encoding( $text, "HTML-ENTITIES", "UTF-8");
        return $text;
    }
}

// post Submit/Edit Form Helper Function
if( !function_exists( 'is_valid_image' ) ){
	function is_valid_image($file_name){
	    $valid_image_extensions = array( "jpg", "jpeg", "gif", "png" );
	    $exploded_array = explode('.',$file_name);
	    if( !empty($exploded_array) && is_array($exploded_array) ){
	        $ext = array_pop( $exploded_array );
	        return in_array( $ext, $valid_image_extensions );
	    }else{
	        return false;
	    }
	}
}

// Insert Attachment Method for Property Submit Template
if( !function_exists( 'insert_attachment' ) ){
	function insert_attachment( $file_handler, $post_id, $setthumb = false ){
		if ($_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK) __return_false();
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
		$attach_id = media_handle_upload( $file_handler, $post_id );
		if ($setthumb){
			update_post_meta($post_id,'_thumbnail_id',$attach_id);
		}
		return $attach_id;
	}
}


/* VIDEO EMBED FUNCTIONS
================================================== */
if (!function_exists('bw_video_embed')) {
	function bw_video_embed($url, $width = 640, $height = 480) {
		if (strpos($url,'youtube') || strpos($url,'youtu.be')){
			return bw_video_youtube($url, $width, $height);
		} else {
			return bw_video_vimeo($url, $width, $height);
		}
	}
}

if (!function_exists('bw_video_youtube')) {
	function bw_video_youtube($url, $width = 640, $height = 480) {
		preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $url, $video_id);
		return '<iframe itemprop="video" src="http://www.youtube.com/embed/'. $video_id[1] .'?wmode=transparent" width="'. $width .'" height="'. $height .'" ></iframe>';
	}
}

if (!function_exists('bw_video_vimeo')) {
	function bw_video_vimeo($url, $width = 640, $height = 480) {
		preg_match('/http:\/\/vimeo.com\/(\d+)$/', $url, $video_id);
		return '<iframe itemprop="video" src="http://player.vimeo.com/video/'. $video_id[1] .'?title=0&amp;byline=0&amp;portrait=0?wmode=transparent" width="'. $width .'" height="'. $height .'"></iframe>';
	}
}

if (!function_exists('bw_get_embed_src')) {
	function bw_get_embed_src($url) {
		if (strpos($url,'youtube')){
			preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $url, $video_id);
			if (isset($video_id[1])) {
				return 'http://www.youtube.com/embed/'. $video_id[1] .'?autoplay=1&amp;wmode=transparent';
			}
		} else {
			preg_match('/http:\/\/vimeo.com\/(\d+)$/', $url, $video_id);
			if (isset($video_id[1])) {
				return 'http://player.vimeo.com/video/'. $video_id[1] .'?title=0&amp;byline=0&amp;portrait=0&amp;autoplay=1&amp;wmode=transparent';
			}
		}
	}
}

if(!function_exists('bw_embed_swf')){
	function bw_embed_swf($e, $w,$h){
		if($e){
			//$r = rand(0,9999999);
			$r = time();
			return '<embed type="application/x-shockwave-flash" src="'.$e.'" id="flashObject-'.$r.'" name="flashObject-'.$r.'" bgcolor="#ffffff" menu="no" quality="high" wmode="transparent" width="'.$w.'" height="'.$h.'" />';
		}
	}
}

if(!function_exists('bw_string_extension')){
	function bw_string_extension($file_name) {
		$ext = end(explode('.',$file_name));
		//$ext = substr(strrchr($file_name,'.'),1);
		return strtolower($ext);
	}
}

/* SHORTCODE FIX
================================================== */
if(!function_exists('bw_shortcode_fix')){
	function bw_shortcode_fix($content){
	    $array = array (
	        '<p>[' => '[',
	        ']</p>' => ']',
	        ']<br />' => ']'
	    );

	    $content = strtr($content, $array);
	    return $content;
	}
	add_filter('the_content', 'bw_shortcode_fix');
}

if(!function_exists('bw_get_current_page_url')){
	function bw_get_current_page_url(){
    	global $post;
		if ( is_front_page() ) :
			$page_url = home_url();
			else :
			$page_url = 'http';
		if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" )
			$page_url .= "s";
				$page_url .= "://";
				if ( $_SERVER["SERVER_PORT"] != "80" )
			$page_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
				else
			$page_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			endif;

		return apply_filters( 'bw_get_current_page_url', esc_url( $page_url ) );
	}
}


// IMAGE FUNCTIONS
if(!function_exists('base64_encode_image')){
	function base64_encode_image($filename, $mime='gif') {
		if ($filename) {
			$imgbinary = file_get_contents($filename);
			return 'data:image/' . $mime . ';base64,' . base64_encode($imgbinary);
		}
	}
}

if(!function_exists('bw_resize')){
	function bw_resize( $url, $width, $height = null, $crop = null, $single = true ) {
		if(!$url OR !$width ) return false;
		$upload_info = wp_upload_dir();
		$upload_dir = $upload_info['basedir'];
		$upload_url = $upload_info['baseurl'];

		if($height == null) $height = $width;

		$url = str_ireplace(array('http:', 'https:'), '', $url);
		$upload_url = str_ireplace(array('http:', 'https:'), '', $upload_url);

		$rel_path = str_ireplace( $upload_url, '', $url);

		$img_path = $upload_dir . $rel_path;
		if( !file_exists($img_path) OR !getimagesize($img_path) ){
			$pre_url = 'http://placehold.it/'.$width.'x'.$height.'&text=:)';
			$img_url = base64_encode_image($pre_url);
			$dst_w = $width;
			$dst_h = $height;
		}else{
			$info = pathinfo($img_path);
			$ext = $info['extension'];
			list($orig_w,$orig_h) = getimagesize($img_path);
			$dims = image_resize_dimensions($orig_w, $orig_h, $width, $height, $crop);
			$dst_w = $dims[4];
			$dst_h = $dims[5];
			$suffix = "{$dst_w}x{$dst_h}";
			$dst_rel_path = str_replace( '.'.$ext, '', $rel_path);
			$destfilename = "{$upload_dir}{$dst_rel_path}-{$suffix}.{$ext}";
			if($width >= $orig_w) {
				if(!$dst_h) :
					$img_url = $url;
					$dst_w = $orig_w;
					$dst_h = $orig_h;
				else :
					if (file_exists($destfilename) && getimagesize($destfilename)) {
						$img_url = "{$upload_url}{$dst_rel_path}-{$suffix}.{$ext}";
					}
					else {
						if(function_exists('wp_get_image_editor')) {
							$editor = wp_get_image_editor($img_path);
							if ( is_wp_error( $editor ) || is_wp_error( $editor->resize( $width, $height, $crop ) ) )
								return false;
							$resized_file = $editor->save();
							if(!is_wp_error($resized_file)) {
								$resized_rel_path = str_replace( $upload_dir, '', $resized_file['path']);
								$img_url = $upload_url . $resized_rel_path;
							} else {
								return false;
							}
						}
					}
				endif;
			}
			elseif(file_exists($destfilename) && getimagesize($destfilename)) {
				$img_url = "{$upload_url}{$dst_rel_path}-{$suffix}.{$ext}";
			}else {
				if(function_exists('wp_get_image_editor')) {
					$editor = wp_get_image_editor($img_path);
					if ( is_wp_error( $editor ) || is_wp_error( $editor->resize( $width, $height, $crop ) ) )
						return false;
					$resized_file = $editor->save();
					if(!is_wp_error($resized_file)) {
						$resized_rel_path = str_replace( $upload_dir, '', $resized_file['path']);
						$img_url = $upload_url . $resized_rel_path;
					} else {
						return false;
					}
				}
			}
		}

		if($single) {
			$image = $img_url;
		} else {
			$image = array (
				0 => $img_url,
				1 => $dst_w,
				2 => $dst_h
			);
		}
		return $image;
	}
}

if(!function_exists('bw_img')){
	function bw_img($url, $width = null, $height = null, $crop = true, $single = true){
		$post_thumbnail_url = '';
		if( ! is_numeric($width)){
			$image_size = BitwizeImageSizes::get_img_size($width);
			extract($image_size);
		}
		if(is_numeric($url)){
			$post_thumbnail_id = get_post_thumbnail_id($url);

			if($post_thumbnail_id){
				$post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);
			}else{
				return '';
			}
		}else{
			$post_thumbnail_url = $url;
		}
		$url = bw_resize($post_thumbnail_url, $width, $height, $crop);
		if($single){
			return $url;
		}else{
			$o = array();
			$o['url'] = $url;
			$o['width'] = $width;
			$o['height'] = $height;
			return $o;
		}
	}
}

if(!function_exists('bw_show_img')){
	function bw_show_img($url, $width = null, $height = null, $crop = true, $attrs = array(), $lazy_load = false){
		$img_path = bw_img($url, $width, $height, $crop, false);
		if($img_path){
			$extra_attrs = array();
			foreach($attrs as $attr_name => $attr_value){
				$escaped_attr_value = esc_attr($attr_value);
				$extra_attrs[] = "{$attr_name}=\"{$escaped_attr_value}\"";
			}
			return '<img ' . ($lazy_load ? 'data-' : '') . 'src="' . $img_path['url'] . '" width="' . $img_path['width'] . '" height="' . $img_path['height'] . '" ' . (is_string($width) ? (' alt="' . $width . '"') : '') . ' ' . implode(' ', $extra_attrs) . ' />';
		}
	}
}

if(!function_exists('bw_show_img_lazy')){
	function bw_show_img_lazy($url, $width = null, $height = null, $crop = true, $attrs = array()){
		if(isset($attrs['class']))
			$attrs['class'] .= ' hidden-slowly bw-lazy-load';
		else
			$attrs['class'] = 'hidden-slowly bw-lazy-load';
		return bw_show_img($url, $width, $height, $crop, $attrs, true);
	}
}

if(!class_exists('BitwizeImageSizes')){
	class BitwizeImageSizes{
		public static $image_sizes = array();
		public static function get_size($s){
			global $_wp_additional_image_sizes;
			$sizes[$s] = array( 'width' => '', 'height' => '', 'crop' => FALSE );
			if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
				$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
			else
				$sizes[$s]['width'] = get_option( "{$s}_size_w" ); // For default sizes set in options
			if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
				$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
			else
				$sizes[$s]['height'] = get_option( "{$s}_size_h" ); // For default sizes set in options
			if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )
				$sizes[$s]['crop'] = intval( $_wp_additional_image_sizes[$s]['crop'] ); // For theme-added sizes
			else
				$sizes[$s]['crop'] = get_option( "{$s}_crop" ); // For default sizes set in options

			return $sizes[$s];
		}

		public static function get_image_sizes($alias = false){
			foreach ( get_intermediate_image_sizes() as $s ) {
				$sizes[$s] = self::get_size($s);
			}
			if($alias)
				return $sizes[$alias];
			else
				return $sizes;
		}

		public static function get_img_size($alias){
			return self::get_image_sizes($alias);
		}
	}
}

/* LANGUAGE SUPPORT
================================================== */
if(!function_exists('is_bw_rtl')){
	function is_bw_rtl(){
		return trim(get_locale()) == 'ar';
	}
	//Tranlate Archives Months
	/*if(is_bw_rtl()){
		add_filter('get_archives_link', function($list) {
		  	$patterns = array(
		    	'/January/', '/February/', '/March/', '/April/', '/May/', '/June/',
		    	'/July/', '/August/', '/September/', '/October/',  '/November/', '/December/'
		  	);
		  	$replacements = array(
		    	'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
		    	'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
		  	);
		  	$list = preg_replace($patterns, $replacements, $list);
			return $list;
		});
	}*/
}

if(!function_exists('bw_ar_human_time_diff')){
	function bw_ar_human_time_diff( $from, $to = '' ) {
		if ( empty( $to ) )
			$to = time();

		$diff = (int) abs( $to - $from );

		if ( $diff < HOUR_IN_SECONDS ) {
			$mins = round( $diff / MINUTE_IN_SECONDS );
			if ( $mins <= 1 )
				$mins = 1;
			/* translators: min=minute */
			$since = sprintf( _n( '%s دقيقة', '%s دقائق', $mins ), $mins );
		} elseif ( $diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS ) {
			$hours = round( $diff / HOUR_IN_SECONDS );
			if ( $hours <= 1 )
				$hours = 1;
			$since = sprintf( _n( '%s ساعة', '%s ساعات', $hours ), $hours );
		} elseif ( $diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS ) {
			$days = round( $diff / DAY_IN_SECONDS );
			if ( $days <= 1 )
				$days = 1;
			$since = sprintf( _n( '%s يوم', '%s أيام', $days ), $days );
		} elseif ( $diff < 30 * DAY_IN_SECONDS && $diff >= WEEK_IN_SECONDS ) {
			$weeks = round( $diff / WEEK_IN_SECONDS );
			if ( $weeks <= 1 )
				$weeks = 1;
			$since = sprintf( _n( '%s اسبوع', '%s اسابيع', $weeks ), $weeks );
		} elseif ( $diff < YEAR_IN_SECONDS && $diff >= 30 * DAY_IN_SECONDS ) {
			$months = round( $diff / ( 30 * DAY_IN_SECONDS ) );
			if ( $months <= 1 )
				$months = 1;
			$since = sprintf( _n( '%s شهر', '%s اشهر', $months ), $months );
		} elseif ( $diff >= YEAR_IN_SECONDS ) {
			$years = round( $diff / YEAR_IN_SECONDS );
			if ( $years <= 1 )
				$years = 1;
			$since = sprintf( _n( '%s year', '%s years', $years ), $years );
		}

		return $since;
	}
}


if(!function_exists('bw_menu_page_url')){
	function bw_menu_page_url($menu_slug, $echo = true) {
		global $_parent_pages;
		if ( isset( $_parent_pages[$menu_slug] ) ) {
			$parent_slug = $_parent_pages[$menu_slug];
			if ( $parent_slug && ! isset( $_parent_pages[$parent_slug] ) ) {
				$url = admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
			} else {
				$url = admin_url( 'admin.php?page=' . $menu_slug );
			}
		} else {
			$url = '';
		}
		$url = esc_url($url);
		$url = html_entity_decode($url);
		if ( $echo ) echo $url;
		return $url;
	}
}

if(!function_exists('get_data')){
	$data_cached = array();
	function get_data($var = '',$options = 'bwa_aries'){
		global $data_cached;
		if($var==''){
			if(isset($data_cached[$options])) return $data_cached[$options];
			$data = get_option($options);
			$data_cached[$options] = $data;
			return $data;
		}else{
			if(isset($data_cached[$options][$var])) return $data_cached[$options][$var];
			$data = get_option($options);
			if( ! empty($var) && isset($data[$var])){
				$data_cached[$options] = $data;
				return $data[$var];
			}
		}
		return null;
	}
}

if(!function_exists('bw_is_plugin_active')){
	function bw_is_plugin_active( $plugin_name ) {
		$active_plugins = (array) apply_filters('active_plugins', get_option( 'active_plugins', array() ));
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		return in_array( $plugin_name, $active_plugins ) || array_key_exists( $plugin_name, $active_plugins );
	}
}

if(!function_exists('bw_is_plugin_active_force')){
	function bw_is_plugin_active_force( $plugin_name ) {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		return in_array( $plugin_name, $active_plugins ) || array_key_exists( $plugin_name, $active_plugins );
	}
}

if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		return ( bw_is_plugin_active_force( 'woocommerce/woocommerce.php' ) || bw_is_plugin_active_force('bw-ec-shop/bw-ec-shop.php') );
	}
}

if(!function_exists('bw_current_url')){
	function bw_current_url($file = __FILE__, $path=''){$url = str_replace(ABSPATH,get_option('siteurl').'/',dirname($file));if ( $path && is_string( $path ) ) $url .= '/' . ltrim($path, '/');return apply_filters('bitwize_url',$url, $file, $path);}
}

if(!function_exists('bw_current_dir')){
	function bw_current_dir($file = __FILE__, $path=''){$dir = dirname($file);if ( $path && is_string( $path ) ) $dir .= '/' . ltrim($path, '/');return $dir;}
}

if(!function_exists('bw_get_subfolder')){
	function bw_get_subfolder(){return wp_make_link_relative(home_url('', null));}
}

if(!function_exists('bw_plugins_url')){
	function bw_plugins_url( $path = '', $file = __FILE__ ) {$path = wp_normalize_path( $path );$file = wp_normalize_path( $file );$url = str_replace(ABSPATH,get_option('siteurl').'/',dirname($file));$url = set_url_scheme( $url );if ( $path && is_string( $path ) )$url .= '/' . ltrim($path, '/');return apply_filters('bitwize_url',$url, $path, $file);}
}

if(!function_exists('bw_plugin_dir_path')){
	function bw_plugin_dir_path( $file ) {return trailingslashit( dirname( $file ) );}
}

if(!function_exists('bw_plugin_dir_url')){
	function bw_plugin_dir_url( $file ) {return trailingslashit( bw_plugins_url( '', $file ) );}
}

if(!function_exists('bw_register_activation_hook')){
	function bw_register_activation_hook($file, $function='') {$file = basename(dirname($file)).'/'.basename($file);if(!get_option('bw-activate-'.$file)){if($function != '') call_user_func($function);add_option('bw-activate-'.$file,1);}}
}

if(!function_exists('bw_register_dectivation_hook')){
	function bw_register_dectivation_hook($file, $function='') {$file = basename(dirname($file)).'/'.basename($file);if($function != '') call_user_func($function);delete_option('bw-activate-'.$file);}
}

if(!function_exists('bw_print_r')){
	function bw_print_r($e){echo '<pre>';print_r($e);echo '</pre>';}
}

if(!function_exists('bw_debug')){
	function bw_debug($e=''){if ($e=='') return;if(is_array($e)){bw_print_r($e);}else{echo $e;}}
}
