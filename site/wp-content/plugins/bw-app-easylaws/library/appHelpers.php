<?php

class App_Helpers
{

	public $debug = true;
	public $prefix;

    protected static $_instance = null;
	public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct(){
    	$this->prefix = DB()->prefix;
    	add_action('wp_ajax_nopriv_app_fbconnect', array($this, 'fbconnect'));
        add_action('wp_ajax_app_fbconnect', array($this, 'fbconnect'));

        add_action( 'wp_footer', array($this, 'print_js'), 25 );
    }

    public function array_to_object($arr){
        return json_decode(json_encode($arr));
    }

    public function object_to_array($arr){
        return json_decode(json_encode($arr), true);
    }

    public function is_dev(){
    	return strpos($_SERVER['HTTP_HOST'], 'local.') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;
    }

    public function stripslashes($value) {
		if ( is_array($value) ) {
			$value = array_map( array($this, 'stripslashes'), $value);
		} elseif ( is_object($value) ) {
			$vars = get_object_vars( $value );
			foreach ($vars as $key=>$data) {
				$value->{$key} = $this->stripslashes( $data );
			}
		} elseif ( is_string( $value ) ) {
			// $value = stripslashes($value);
			$value = esc_sql($value);
			$value = stripcslashes(str_replace('\\\\','',$value));
		}

		return $value;
	}

	function server_limit(){
        $safe_mode = @ini_get('safe_mode');
        $memory_limit = @ini_get('memory_limit');
        $max_execution_time = @ini_get('max_execution_time');
        if(!$safe_mode){
            @set_time_limit(0);
            if(intval($max_execution_time) < 300) @ini_set( 'max_execution_time', 300 );
            if(intval($memory_limit) < 256) @ini_set( 'memory_limit', '256M' );
        }
    }

    public function inline_css($html, $css){
        if($html && $css) {
            $css_inline = new CssToInlineStyles($html, $css);
            $html = $css_inline->convert();
        }
        return $html;
    }

    function blank_image(){
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=';
    }

    // EXCEL
    function excel_create($title = '', $desc = ''){
    	$obj = new PHPExcel();
		$obj->getProperties()->setCreator("Broksy")
							 ->setLastModifiedBy("Broksy")
							 ->setTitle($title)
						     ->setSubject($title)
							 ->setDescription($desc)
							 ->setKeywords("Broksy")
							 ->setCategory("Broksy");
    	return $obj;
    }

    function excel_save($name, $obj){
    	// include DIR in name
    	$name = $name.'.xlsx';
		$objWriter = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
		$objWriter->save($name);
    }

    function excel_stream($name, $obj){
    	// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$name.'.xlsx"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$objWriter = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
		$objWriter->save('php://output');
    }

	public function get_countries($val = '', $by = 'iso'){
		return AH_get_countries($val, $by);
	}

	public function get_countries_options($by = 'iso'){
		$cs = AH_get_countries('', $by);
		$o = [];
		foreach($cs as $c){
			$o[$c[$by]] = $c['name'];
		}
		return $o;
	}

    public function pad_number($input, $pad=4){
    	return str_pad($input, $pad, STR_PAD_LEFT);
    }

    public function enqueue_js( $code ) {
		global $app_queued_js;
		if ( empty( $app_queued_js ) ) $app_queued_js = '';
		$app_queued_js .= "\n" . $code . "\n";
	}

	public function print_js() {
		global $app_queued_js;
		if ( ! empty( $app_queued_js ) ) {
			echo "<!-- APP JS -->\n<script type=\"text/javascript\">\njQuery(function($) {";
			$app_queued_js = wp_check_invalid_utf8( $app_queued_js );
			$app_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $app_queued_js );
			$app_queued_js = str_replace( "\r", '', $app_queued_js );
			echo $app_queued_js . "});\n</script>\n";
			unset( $app_queued_js );
		}
	}

	public function enqueue_css( $code ) {
		global $app_queued_css;
		if ( empty( $app_queued_css ) ) $app_queued_css = '';
		$app_queued_css .= "\n" . $code . "\n";
	}

	public function print_css() {
		global $app_queued_css;
		if ( ! empty( $app_queued_css ) ) {
			echo "<!-- APP CSS -->\n<style>\n";
			$app_queued_css = wp_check_invalid_utf8( $app_queued_css );
			$app_queued_css = str_replace( "\r", '', $app_queued_css );
			echo $app_queued_css . "\n</style>\n";
			unset( $app_queued_css );
		}
	}

	function check_session($minutes = 10){
		if(!isset($_SESSION)) session_start();
		$time = $minutes * 60;
		if (isset($_SESSION['APP_LAST_ACTIVITY']) && (time() - $_SESSION['APP_LAST_ACTIVITY'] > $time)) {
            session_unset();
            session_destroy();
        }
        $_SESSION['APP_LAST_ACTIVITY'] = time();

        // if (!isset($_SESSION['APP_CREATED'])) {
        //     $_SESSION['APP_CREATED'] = time();
        // } else if (time() - $_SESSION['APP_CREATED'] > $time) {
        //     session_regenerate_id(true);
        //     $_SESSION['APP_CREATED'] = time();
        // }
	}

	function setcookie( $name, $value, $expire = 0, $secure = false ) {
		if ( ! headers_sent() ) {
			setcookie( $name, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			headers_sent( $file, $line );
			trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );
		}
	}

    public function set_stat($view = 'ad', $id= null){
    	$t = DB()->prefix.'app_stats';
    	$date = gmdate( 'Y-m-d H:i:s' );
    	if($id){
    		DB()->query("INSERT INTO {$t} (view,view_id,date_created,count) VALUES ('{$view}',{$id},'{$date}',1) ON DUPLICATE KEY UPDATE count=count+1;");
    	}
    }

    public function get_age($date){
        if(!$date || $date == '0000-00-00') return;
    	// 31556926 is the number of seconds in a year.
    	return floor((time() - strtotime($date)) / 31556926);
    }

	public function post($k=''){if(!empty($_POST[$k])) return $_POST[$k];return false;}
	public function get($k=''){if(!empty($_GET[$k])) return $_GET[$k];return false;}
	public function req($k=''){if(!empty($_REQUEST[$k])) return $_REQUEST[$k];return false;}

	public function serialize($data){
		if($data) {
			// $data = esc_sql($data);
			return base64_encode(maybe_serialize($data));
		}
	}
	public function unserialize($data){
		if($data){
			return str_ireplace('\\', '', maybe_unserialize(base64_decode($data)));
		}
	}

	public function sanitize($str){
		$filtered = wp_check_invalid_utf8( $str );
		// $filtered = $str;

		if ( strpos($filtered, '<') !== false ) {
			$filtered = wp_pre_kses_less_than( $filtered );
			$filtered = wp_strip_all_tags( $filtered, true );
		} else {
			// $filtered = trim( preg_replace('/[\r\n\t ]+/', ' ', $filtered) );
			$filtered = trim( $filtered );
		}
		$filtered = stripslashes_deep($filtered);
		return apply_filters( 'app_sanitize', $filtered, $str );
	}

	public function sanitize_text($input){
		return sanitize_text_field($input);
	}

	public function sanitize_textarea($input){
		return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $input ) ) );
	}

	public function get_active($sid = 0){
		if ($sid){
			return "<font color=green>YES</font>";
		}else{
			return "<font color=red>NO</font>";
		}
	}

	public function get_years($from = '-50 years', $to = 'now') {
        $f = date('Y', strtotime($from));
        $t = date('Y', strtotime($to));

        $o = array();
        foreach (range($t, $f) as $i) {
            $o[$i] = $i;
        }
        return $o;
    }

    public function get_gender($sel = false) {
        $o = array(
            'male'   => __('Male','ATD'),
            'female' => __('Female','ATD'),
        );
        if ($sel) {
            return $o[$sel];
        }

        return $o;
    }

    public function get_yesno($sel = false) {
        $o = array(
            1 => __('Yes','ATD'),
            0 => __('No','ATD'),
        );
        if ($sel) {
            return $o[$sel];
        }

        return $o;
    }

	public function pay_form($amount=0, $bill_id=''){
    	$form = '';
    	$forms = get_posts( array('post_type' => 'epay_forms', 'posts_per_page' => 1) );
    	if($forms) $form = $forms[0]->ID;
    	$form = apply_filters('app_epay_form_id', $form);
	    $o = do_shortcode('[epay_form id="'.$form.'" amount="'.$amount.'" bill_id="'.$bill_id.'"]');
	    return $o;
	}

	public function get_address_from_latlng($lat='', $lng=''){
		if($lat && $lng){
			$url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&sensor=false';
			$get     = file_get_contents($url);
		    $geoData = json_decode($get);
		    if(isset($geoData->results[0])) {
		        return $geoData->results[0]->formatted_address;
		    }
		    return null;
		}
		return null;
	}

	public function limit_by_word($text, $limit) {
      if (str_word_count($text, 0) > $limit) {
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          $text = substr($text, 0, $pos[$limit]) . '...';
      }
      return $text;
    }

	public function assign_ajax($slug, $func, $only_admins = false){
        add_action('wp_ajax_'.$slug, array( $this, $func ));
        if(!$only_admins){
        	add_action('wp_ajax_nopriv_'.$slug, $func);
        }
    }

    public function generate_password($length = 9, $add_dashes = false, $available_sets = 'luds') {
		$sets = array();
		if(strpos($available_sets, 'l') !== false) $sets[] = 'abcdefghjkmnpqrstuvwxyz';
		if(strpos($available_sets, 'u') !== false) $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		if(strpos($available_sets, 'd') !== false) $sets[] = '23456789';
		if(strpos($available_sets, 's') !== false) $sets[] = '!@#$%&*?';
		$all = '';
		$password = '';
		foreach($sets as $set) {
			$password .= $set[array_rand(str_split($set))];
			$all .= $set;
		}
		$all = str_split($all);
		for($i = 0; $i < $length - count($sets); $i++)
			$password .= $all[array_rand($all)];
		$password = str_shuffle($password);
		if(!$add_dashes) return $password;
		$dash_len = floor(sqrt($length));
		$dash_str = '';
		while(strlen($password) > $dash_len) {
			$dash_str .= substr($password, 0, $dash_len) . '-';
			$password = substr($password, $dash_len);
		}
		$dash_str .= $password;
		return $dash_str;
	}

	public function get_ip() {
        $ipaddress = '';
        if ($_SERVER['HTTP_CLIENT_IP'])
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if($_SERVER['HTTP_X_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if($_SERVER['HTTP_X_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if($_SERVER['HTTP_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if($_SERVER['HTTP_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if($_SERVER['REMOTE_ADDR'])
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = false;
        return apply_filters('app_get_ip', $ipaddress);
    }

	public function convert_youtube($string) {
	    return preg_replace(
	        "/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i",
	        "<iframe src=\"//www.youtube.com/embed/$2\" allowfullscreen></iframe>",
	        $string
	    );
	}

	public function current_page_url(){
    	global $post;
		if ( is_front_page() ) :
			$page_url = home_url();
		else :
			$page_url = 'http';
			if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) $page_url .= "s";
			$page_url .= "://";
			if ( $_SERVER["SERVER_PORT"] != "80" )
				$page_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
					else
				$page_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		endif;

		return esc_url( $page_url );
	}

	public function share_page($title='', $img='', $class=''){
		$url = urlencode($this->current_page_url());
		$img = urlencode($img);

		$html = '
			<div class="share-links '.$class.' clearfix">
				<ul>
					<li>'.__('Share','ATD').':</li>
					<li><a href="http://www.facebook.com/sharer.php?u='.$url.'" target="_blank" class="share-facebook"><i class="fa fa-facebook"></i></a></li>
					<li><a href="https://twitter.com/share?url='.$url.'" target="_blank" class="share-twitter"><i class="fa fa-twitter"></i></a></li>

					<li><a href="https://plus.google.com/share?url='.$url.'" target="_blank" class="share-google-plus"><i class="fa fa-google-plus"></i></a></li>
					<li><a href="http://pinterest.com/pin/create/button/?url='.$url.'&media='.$img.'&description='.$title.'%20'.$url.'" target="_blank" class="share-pinterest"><i class="fa fa-pinterest"></i></a></li>
					<li><a href="mailto:?subject='.$title.'&body='.$url.'" class="share-email"><i class="fa fa-envelope"></i></a></li>
					<!--<li><a class="permalink item-link" href="'.$url.'"><i class="fa fa-link"></i></a></li>-->

				</ul>
			</div>
		';
		return $html;
	}

	public function filter_post($a1, $a2){
		$a1 = array_intersect_key($a1, $a2);
		$validated = app_iv()->validate($a1, $a2);
		if(is_array($validated)) $validated = array_unique($validated);
		if($validated === TRUE){
			$a1['validated'] = true;
			return $a1;
		} else {
			$validated['validated'] = false;
			return $validated;
		}
	}

	public function post_errors($a){
		$o = '';
		unset($a['validated']);
		foreach($a as $k => $v){
			$o .= '<p>__ ' . $v['field'] . ' '.__('provided is not valid','ATD').'</p>';
		}
		return $o;
	}

	public function alert($errors = '', $bg = 'danger', $dismissable = true, $echo = true){
		$x = '';
		if($dismissable) $x = '<span class="close" data-dismiss="alert">×</span>';
		if($errors) {
			if($echo){
				echo '<div class="alert alert-'.$bg.'">'.$x.$errors.'</div>';
			} else {
				return '<div class="alert alert-'.$bg.'">'.$x.$errors.'</div>';
			}
		}
	}

	public function unique_id($l = 8) {
        return substr(md5(uniqid(mt_rand(), true)), 0, $l);
    }

    function to_bytes($number, $from = 'GB'){
	    switch(strtoupper($from)){
	        case "KB":
	            return $number*1024;
	        case "MB":
	            return $number*pow(1024,2);
	        case "GB":
	            return $number*pow(1024,3);
	        case "TB":
	            return $number*pow(1024,4);
	        case "PB":
	            return $number*pow(1024,5);
	        default:
	            return $number;
	    }
	}

    public function format_size($size) {
        $units = explode(' ', 'B KB MB GB TB PB');
        $mod = 1024;
        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }
        $endIndex = strpos($size, ".")+3;
        return substr( $size, 0, $endIndex).' '.$units[$i];
    }

    function folder_size($path){
	    $bytestotal = 0;
	    $path = realpath($path);
	    if($path!==false){
	        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
	            try {
	            	$bytestotal += $object->getSize();
	            } catch (Exception $e){
	            	error_log( $e->getMessage() );
	            }
	        }
	    }
	    return $bytestotal;
	}

    function folder_size_units($path, $unit='MB'){
	    $bytestotal = $this->folder_size($path);
	    $size = 0;
	    $B  = $bytestotal;
	    $KB = $B  / 1024;
	    $MB = $KB / 1024;
	    $GB = $MB / 1024;
	    $TB = $GB / 1024;
	    $PB = $TB / 1024;
	    switch (strtoupper($unit)){
	        case 'KB': $size = $KB; break;
	        case 'MB': $size = $MB; break;
	        case 'GB': $size = $GB; break;
	        case 'TB': $size = $TB; break;
	        case 'PB': $size = $PB; break;
	        default: $size = $B; break;
	    }
	    // $endIndex = strpos($size, ".")+3;
	    // return substr( $size, 0, $endIndex);
	    return $size;
	}

	public function users_upload_dir(){
		$upload_dir = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];
		$upload_base_url = $upload_dir['baseurl'];
		$folder = $upload_base_dir . '/users/';
		wp_mkdir_p($folder);

        return $folder;
	}

    public function get_user_folder($user_id, $sub=''){
		$folder = $this->users_upload_dir() . $user_id . '/';
		if($sub){
			$folder = trailingslashit($folder.$sub);
		}
        wp_mkdir_p($folder);
        return $folder;
    }

    public function save_base64( $data ){
        if(!$data) return false;
        if(!AH()->user()) return false;
        // convert from base64 file
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data));
        $folder = $this->get_user_folder(AH()->user()->ID);
        $name = $this->unique_id().'.png';
        file_put_contents($folder . $name, $data);
        return $name;
    }

    public function upload_square($file, $size = 128){
    	if(!AH()->user()) return false;
		$folder = $this->get_user_folder(AH()->user()->ID);
		$u = new App_Upload($file);
		$u->file_max_size = '5m';
		$u->allowed = array('image/*');

		$u->file_name_body_add = '_'.$size.'x'.$size;

		$u->image_resize          = true;
		$u->image_ratio_crop      = true;
		$u->image_y               = $size;
		$u->image_x               = $size;

		$u->Process($folder);
		$u->Clean();
		if ($u->processed) {
			return array(
				'success' => true,
				'name' => $u->file_dst_name,
			);
		} else {
			return array(
				'success' => false,
				'error' => $u->error,
			);
		}
    }

	public function upload( $file, $is_image = false ){
		if(!AH()->user()) return false;
		$folder = $this->get_user_folder(AH()->user()->ID);
		$u = new App_Upload($file);
		$u->file_max_size = '5m';
		if($is_image){
			$u->allowed = array('image/*');
		} else {
			$u->allowed = array(
            'application/excel',
            'application/mspowerpoint',
            'application/msword',
            'application/pdf',
            'application/plain',
            'application/powerpoint',
            'application/rtf',
            'application/vnd.ms-excel',
            'application/vnd.ms-excel.addin.macroEnabled.12',
            'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'application/vnd.ms-excel.sheet.macroEnabled.12',
            'application/vnd.ms-excel.template.macroEnabled.12',
            'application/vnd.ms-office',
            'application/vnd.ms-officetheme',
            'application/vnd.ms-powerpoint',
            'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'application/vnd.ms-powerpoint.slide.macroEnabled.12',
            'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'application/vnd.ms-powerpoint.template.macroEnabled.12',
            'application/vnd.ms-word',
            'application/vnd.ms-word.document.macroEnabled.12',
            'application/vnd.ms-word.template.macroEnabled.12',
            'application/vnd.oasis.opendocument.chart',
            'application/vnd.oasis.opendocument.database',
            'application/vnd.oasis.opendocument.formula',
            'application/vnd.oasis.opendocument.graphics',
            'application/vnd.oasis.opendocument.graphics-template',
            'application/vnd.oasis.opendocument.image',
            'application/vnd.oasis.opendocument.presentation',
            'application/vnd.oasis.opendocument.presentation-template',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.spreadsheet-template',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.text-master',
            'application/vnd.oasis.opendocument.text-template',
            'application/vnd.oasis.opendocument.text-web',
            'application/vnd.openofficeorg.extension',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'application/vnd.openxmlformats-officedocument.presentationml.template',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'application/vocaltec-media-file',
            'application/wordperfect',
            'application/x-excel',
            'application/x-msexcel',
            'application/x-rtf',
            'image/*',
            'text/plain',
            'text/rtf',
            'text/richtext',
			);
		}
		$u->Process($folder);
		$u->Clean();
		if ($u->processed) {
			if($is_image){
				$this->upload_square($u->file_dst_pathname);
			}
			return array(
				'success' => true,
				'name' => $u->file_dst_name,
				'fullname' => $u->file_dst_pathname,
			);
		} else {
			return array(
				'success' => false,
				'error' => $u->error,
			);
		}
	}

    function format_amount( $amount, $decimals = true ) {
		$thousands_sep = ',';
		$decimal_sep   = '.';
		if ( $decimal_sep == ',' && false !== ( $sep_found = strpos( $amount, $decimal_sep ) ) ) {
			$whole  = substr( $amount, 0, $sep_found );
			$part   = substr( $amount, $sep_found + 1, ( strlen( $amount ) - 1 ) );
			$amount = $whole . '.' . $part;
		}
		if ( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
			$amount = str_replace( ',', '', $amount );
		}
		if ( $thousands_sep == ' ' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
			$amount = str_replace( ' ', '', $amount );
		}
		if ( empty( $amount ) ) {
			$amount = 0;
		}
		$decimals = $decimals ? 2 : 0;
		$formatted = number_format( $amount, $decimals, $decimal_sep, $thousands_sep );
		return $formatted;
	}

	public function get_birthdays_count(){
		$o = 0;
		$o = DB()->get_var("SELECT COUNT(*) FROM {$this->prefix}app_users WHERE date_format(user_dob, '%m-%d') = date_format(now(), '%m-%d')");
		return $o;
	}

	public function fix_display_title($t){
		return ucwords(strtolower($t));
	}

	public function table_exists($table_name=''){
		if($table_name){
			if(DB()->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				return false;
			} else {
				return true;
			}
		}
		return false;
	}

	public function is_plugin_active( $plugin_name ) {
        $active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		return in_array( $plugin_name, $active_plugins ) || array_key_exists( $plugin_name, $active_plugins );
    }

    public function has_newsletter(){
    	return $this->is_plugin_active('bw-app-newsletter/bw-app-newsletter.php');
    }

    public function count_members_by_role($role){
        return DB()->get_var("SELECT COUNT(`ID`) FROM {$this->prefix}app_users WHERE `user_role`='{$role}'");
    }

    public function count_members() {
        return DB()->get_var("SELECT COUNT(ID) FROM {$this->prefix}app_users");
    }

    public function formal_name($first_name = '', $last_name = ''){
    	if($first_name && $last_name){
    		return ucfirst($first_name).' '.ucfirst(substr($last_name, 0, 1)).'.';
    	} elseif ($first_name) {
    		return ucfirst($first_name);
    	} elseif ($last_name) {
    		return ucfirst($last_name);
    	}
    	return '';
    }

    public function random_dark_color()
    {
        $red   = (int) mt_rand(0, 200);
        $blue  = (int) mt_rand(0, 200);
        $green = (int) mt_rand(0, 200);
        return '#' . dechex($red) . dechex($blue) . dechex($green);
    }

    public function calculate_distance__($lat1, $lon1, $lat2, $lon2)
    {
        $R    = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c    = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $d    = $R * $c;
        return $d;
    }

    public function calculate_distance($lat1, $lon1, $lat2, $lon2, $unit = 'M') {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        $miles = 69.09 * rad2deg ( acos ( sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)) ) );

        $unit = strtoupper($unit);

        if ($unit == "K") { // kilometers
            return ($miles * 1.609344);
        } else if ($unit == "N") { // Nautical Miles
            return ($miles * 0.8684);
        } else { // Miles
            return $miles;
        }
    }

    public function get_geo_location()
    {
        $ip  = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        // $ip  = '178.135.110.197';
        // $url = "http://geoip.nekudo.com/api/$ip";
        $url = "http://geoip.sellandsell.com/api/$ip";
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data) {
            $data     = json_decode($data);
            $lat      = $data->location->latitude;
            $lon      = $data->location->longitude;
            // $sun_info = date_sun_info(time(), $lat, $lon);
            $o = array('enabled' => true, 'lat' => $lat, 'lon' => $lon);
        } else {
            $o = array('enabled' => false, 'lat' => '', 'lon' => '');
        }
        return $o;
    }

    public function get_fb_script($role = '') {
        global $APP;
        if (empty($APP['fb_app_id'])) {
            return;
        }

        ob_start();
        ?>
        <script>
            var fb_loaded = false;
            window.fbAsyncInit = function () {
                fb_loaded = true;
                FB.init({appId: '<?php echo $APP['fb_app_id']; ?>', status: true, cookie: true, xfbml: true, version: 'v2.5'});
            };
            function fb_login(){
                if(!fb_loaded) return;
                var fbimg = '';
                FB.login(function(response) {
                    if (response.authResponse){
                        FB.api('/me?fields=id,name,first_name,last_name,picture,email,link,gender,age_range,timezone,birthday,location,friends', function(response) {
                            // fbimg = 'http://graph.facebook.com/'+response.id+'/picture?type=large';
                            fbimg = 'https://graph.facebook.com/'+response.id+'/picture?width=800';
                            jQuery.ajax({
                                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                                data: "action=app_fbconnect&fb_id="+response.id+"&first_name="+response.first_name+"&last_name="+response.last_name+"&user_gender="+response.gender+"&user_email="+response.email+"&fb_link="+response.link+"&fb_image="+fbimg+"&user_role=<?php echo $role; ?>",
                                dataType: 'html',
                                type: 'POST',
                                success:function(data){
                                    if(data == 'OK'){
                                    	window.location = '<?php echo apply_filters('app_login_redirect', site_url());?>';
                                        //document.location.reload();
                                    } else {
                                        alert('Error Occured: ' + data);
                                    }
                                },
                                error: function(){
                                    alert('Something wrong happened.');
                                }
                            });
                        });
                    } else {
                        alert("Unauthorized or cancelled\n\nYour Facebook authentication did not go well :(");
                    }
                },{
                    scope: 'public_profile,email',
                    return_scopes: true
                });
                return false;
            }
            function fb_logout(){
                FB.logout(function(){
                    document.location.reload();
                });
                return false;
            }
            (function (d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        </script>
        <?php
$o = ob_get_clean();
        return $o;
    }

    public function fbconnect() {
        $output = '';
        if (!isset($_POST)) die();
        if ($_POST['action'] != 'app_fbconnect') die();
        if (!isset($_POST['fb_id'])) die();

        unset($_POST['action']);
        extract($_POST);

        if (isset($fb_id) && $fb_id != '' && $fb_id != 'undefined') {
            $users = AU()->get_users(array('where' => "fb_id=$fb_id"));
            if (isset($users[0]->ID) && is_numeric($users[0]->ID)) {
                $returning = $users[0]->ID;
            } else {
                $returning = '';
            }
        } else {
            $returning = '';
        }

        if (AH()->user()) {
            $this->update_fb_info(AH()->user()->ID, $fb_id, $fb_image, $fb_link);
        } else {
            if ($returning != '') {
                AH()->auto_login($returning, true);
                $this->update_fb_info($returning, $fb_id, $fb_image, $fb_link);
            } else if ($user_email != '' && AU()->email_exists($user_email)) {
                $user_id = AU()->email_exists($user_email);
                AH()->auto_login($user_id, true);
                $this->update_fb_info($user_id, $fb_id, $fb_image, $fb_link);
            } else {
                $_POST['user_reg_step'] = 2; // hack to bypass first signup screen
                $user_id                = AH()->register_user($_POST);
                if ($user_id && is_numeric($user_id)) {
                	do_action('app_registration_started', $user_id);
                    AH()->auto_login($user_id, true);
                } else {
                    $output = $user_id; // return errors
                }
            }
        }
        if ($output == '') {
            $output = 'OK';
        }

        echo $output;
        die;
    }

    public function update_fb_info($user_id, $fb_id = '', $fb_image = '', $fb_link = ''){
        AH()->update_user($user_id, array(
            'fb_id'    => $fb_id,
            'fb_link'  => $fb_link,
            'fb_image' => $fb_image,
        ));
    }

    public function get_robohash($email, $size = 128, $set = 'set1', $bg = ''){
    	// bg1, bg2
    	// set1, set2, set3
    	return 'https://robohash.org/'.md5($email).'?set='.$set.'&bgset='.$bg.'&size='.$size.'x'.$size;
    }

    public function get_gravatar($email, $size = 128) {
		$email = strtolower( trim( $email ) );
		$hash = md5( $email );
		$d = 'identicon'; //404 | mm | identicon | monsterid | wavatar | retro | blank
		$url = "https://www.gravatar.com/avatar/".$hash."?d=".$d."&s=".$size;
		return $url;
	}

	public function get_user_image($user = 'self', $size = 128){
		if($user == 'self'){
			$user = AH()->user();
		}
		if(is_object($user) && isset($user->ID)){
			if($user->user_image) return $this->get_user_file($user->ID, $user->user_image);
			if($user->fb_image) return $user->fb_image;
			// if($user->display_name) return app_avatar()->avatar($user->display_name, $size);
			if($user->user_email) return $this->get_robohash($user->user_email, $size);
			// if($user->user_email) return $this->get_gravatar($user->user_email, $size);
		}
		return APP_URL.'/assets/img/no-user.png';
	}

	public function get_user_image_by_id($user_id = null, $size = 128){
		$user = AU()->get_userdata($user_id);
		return $this->get_user_image($user, $size);
	}

    public function get_user_file($user_id, $file){
        $upload_dir = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];
		$upload_base_url = $upload_dir['baseurl'];

        $folder = $upload_base_dir . '/users/'. $user_id . '/'.$file;
        if (file_exists( $folder )){
            return $upload_base_url . '/users/'. $user_id . '/'.$file;
        };

        return '';
    }

    public function get_user($user_id){
    	// if(is_admin()) return false;
    	return AU()->get_userdata($user_id);
    }

    public function user(){
    	if(is_admin()) return false;
		if(AU()->is_user_logged_in()){
			global $app_current_user;
			if(!$app_current_user) AU()->get_currentuserinfo();
			$user = AU()->get_userdata($app_current_user->ID);
		}else{
			$user = false;
		}
		return $user;
	}

	public function get_user_meta($id = 0, $key=''){
		return AU()->get_user_meta($id, $key);
	}

	public function auto_login( $username, $remember=false ) {
		$user_id = NULL;
		ob_start();
		if ( !AU()->is_user_logged_in() ) {
			$user = AU()->get_user_by('id', $username );
			$user_id = $user->ID;
			AU()->set_current_user( $user_id, $username );
			AU()->set_auth_cookie( $user_id, $remember );
			AU()->update_user_field( $user_id, 'user_lastlogin', gmdate( 'Y-m-d H:i:s' ) );
			do_action( 'app_login', $username, $user );
		} else {
			AU()->logout();
			$user = AU()->get_user_by('id', $username );
			$user_id = $user->ID;
			AU()->set_current_user( $user_id, $username );
			AU()->set_auth_cookie( $user_id, $remember );
			AU()->update_user_field( $user_id, 'user_lastlogin', gmdate( 'Y-m-d H:i:s' ) );
			do_action( 'app_login', $username, $user );
		}
		ob_end_clean();
		return $user_id;
	}

	public function login($form){
		global $wp_filter;
		$output = array();
		$username_or_email = $form['username_or_email'];
		$error = '';
		$redirect = '';

		do_action( 'app_login_init' );

		@header( 'X-Frame-Options: SAMEORIGIN' );

		/* remember me */
		(!isset($form['rememberme'])) ? $rememberme = false : $rememberme = true;

		if (!$username_or_email){
			$error = __('You should provide your email or username.');
		}
		if (!$form['user_pass']){
			$error = __('You should provide your password.');
		}

		if (AU()->email_exists($username_or_email)) {
			$user = AU()->get_user_by('email', $username_or_email);
			$username_or_email = $user->user_login;
		}

		if ($error=='' && $username_or_email && $form['user_pass']) {

			$creds = array();
			$creds['user_login'] = $username_or_email;
			$creds['user_password'] = $form['user_pass'];
			$creds['remember'] = $rememberme;
			$login_hook_arr = array();
			$login_hook_arr = $wp_filter['app_login'];
			remove_all_actions('app_login');
			$user = AU()->signon( $creds, false );
			if($login_hook_arr){
				foreach($login_hook_arr as $key=>$value){
					foreach($value as $wp_login_hook){
						add_action('app_login',$login_hook['function'],$key,$login_hook['accepted_args']);
					}
				}
			}
			if ( is_wp_error($user) ) {
				if ( $user->get_error_code() == 'invalid_username') {
					$error = __('Invalid email or username entered');
				} elseif ( $user->get_error_code() == 'incorrect_password') {
					$error = __('The password you entered is incorrect');
				}
				$redirect = add_query_arg(array('errors' => 1) );
			} else {
				$this->auto_login( $user->ID, $rememberme );
				$redirect = apply_filters('app_login_redirect', site_url());
			} // end if wp_error
		} // end if output

		wp_redirect($redirect);
		exit();
	}

    public function fix_roles_name($roles=array()){
    	if(!is_array($roles)) $roles = array($roles);
    	if (!empty($roles)) {
    		$new_roles = array();
    		foreach($roles as $role){
    			$new_roles[] = str_ireplace('subscriber', 'user', $role);
    		}
    		return $new_roles;
    	}
    	return '';
    }

    public function user_exists( $user_id ) {
		$aux = AU()->get_userdata( $user_id );
		if($aux==false){
			return false;
		}
		return true;
	}

    public function set_role($user_id, $role) {
		DB()->update($this->prefix.'app_users', array('user_role' => $role), array('ID'=>$user_id));
	}

    public function unique_user($username = null) {
        if(!$username) return;
        if (AU()->username_exists($username)) {
            $r = str_shuffle("0123456789");
            $r1 = (int)$r[0];
            $r2 = (int)$r[1];
            $username = $username . $r1 . $r2;
        }
        if (AU()->username_exists($username)) {
            $r = str_shuffle("0123456789");
            $r1 = (int)$r[0];
            $r2 = (int)$r[1];
            $username = $username . $r1 . $r2;
        }
        return $username;
    }

	public function register_user($form = null){

		if(is_null($form) || !is_array($form) || empty($form)) return false;

		(!isset($form['first_name'])) ? $form['first_name'] = '' : $form['first_name'] = trim(ucfirst(strtolower($form['first_name'])));
		(!isset($form['last_name'])) ? $form['last_name'] = '' : $form['last_name'] = trim(ucfirst(strtolower($form['last_name'])));

		if(!$form['first_name'] && !$form['last_name'] ){
			return 'First Name OR Last Name are required';
		}
		if(isset($form['user_login']) && $form['user_login']){
			$user_login = $form['user_login'];
		}else{
			$user_login = $form['first_name'].$form['last_name'];
		}

		$user_login = strtolower(sanitize_title($user_login));

		if(AU()->username_exists($user_login)){
			$user_login = AH()->unique_user($user_login);
		}

		$display_name = $form['first_name'].' '.$form['last_name'];

		if( isset($form['user_email']) && is_email($form['user_email']) ){
			$user_email = $form['user_email'];
			if(AU()->email_exists($user_email)){
				return 'Email already registered before, use another email.';
			}
		}else{
			return 'Email is required.';
		}

		if (!isset($form['user_pass'])) {
			$user_pass = wp_generate_password(12, false);
		} else {
			$user_pass = $form['user_pass'];
		}

		// $user_role = apply_filters('app_validate_role', $form['user_role'] );
		$user_role = 'user';

		$form['user_role'] = $user_role;
		$form['user_login'] = $user_login;
		$form['user_pass'] = $user_pass;
		$form['display_name'] = $display_name;
		$form['user_email'] = $user_email;

		$user_id = AU()->insert_user( $form );

        if (is_wp_error($user_id) || empty($user_id)) {
            return 'Unable to create the user, Please contact the webmaster.';
        }

        foreach($form as $key => $form_value) {
    		if ( isset($key) && !in_array($key, AU()->core_user_keys() ) ) {
    			AU()->update_user_meta( $user_id, $key, $form_value );
			}
    	}

    	$_POST = $form;
    	do_action('user_register_after_update', $user_id, $user_pass);

    	return $user_id;
	}

	public function update_user($user_id = 0, $form = null){
		if(!$user_id || !is_numeric($user_id)) return false;

		$avail_keys = AU()->core_user_keys();

		if(is_null($form) || !is_array($form) || empty($form)) return false;

		$user_id = absint($user_id);
    	if (!AH()->user_exists($user_id)) return ('User Doesn\'t exist');

    	$core = array();
    	foreach($form as $key => $form_value) {
    		if ( isset($key) && in_array($key, $avail_keys ) ) {
    			$core[$key] = $form_value;
			}
    	}

    	$core['ID'] = $user_id;

    	AU()->update_user( $core );

    	foreach($form as $key => $form_value) {
    		if ( isset($key) && !in_array($key, $avail_keys ) ) {
    			AU()->update_user_meta( $user_id, $key, $form_value );
			}
    	}

    	$_POST = $form;
    	do_action('edit_user_profile_update', $user_id);

    	return $user_id;
	}

	public function insert_db($table, $data){
		$table = $this->prefix.'app_'.$table;
		if(empty($data)) return false;
		$data = (array) $data;
		DB()->insert($table, $data);
		$lastid = DB()->insert_id;
		return $lastid;
	}

	public function update_db($table, $data, $local_id_field, $the_ID){
		$table = $this->prefix.'app_'.$table;
		if(empty($data)) return false;
		$data = (array) $data;
		DB()->update($table, $data, array($local_id_field => $the_ID));
		return true;
	}

	function parse_csv_file($csvfile) {
	    $csv = Array();
	    $rowcount = 0;
	    if (($handle = fopen($csvfile, "r")) !== FALSE) {
	        $max_line_length = defined('MAX_LINE_LENGTH') ? MAX_LINE_LENGTH : 10000;
	        $header = fgetcsv($handle, $max_line_length);
	        $header_colcount = count($header);
	        while (($row = fgetcsv($handle, $max_line_length)) !== FALSE) {
	            $row_colcount = count($row);
	            if ($row_colcount == $header_colcount) {
	                $entry = array_combine($header, $row);
	                $csv[] = $entry;
	            }
	            else {
	                error_log("csvreader: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
	                return null;
	            }
	            $rowcount++;
	        }
	        //echo "Totally $rowcount rows found\n";
	        fclose($handle);
	    }
	    else {
	        error_log("csvreader: Could not read CSV \"$csvfile\"");
	        return null;
	    }
	    return $csv;
	}

	public function db_to_sql($table, $file, $where = ''){
		if(file_exists($file)) @unlink($file);
		if(!empty($where)) $where = "WHERE $where";
		if( DB()->query("SELECT * from {$table} {$where} INTO OUTFILE '{$file}'") ){
			return true;
		}
		return false;
	}

	public function db_to_csv($table, $file, $where = ''){
		if(file_exists($file)) @unlink($file);
		if(!empty($where)) $where = "WHERE $where";
		if( DB()->query(
			"SELECT * from {$table} {$where}
			INTO OUTFILE '{$file}'
			FIELDS TERMINATED BY ','
			ENCLOSED BY '\"'
			LINES TERMINATED BY '\n';"
		) ){
			return true;
		}
		return false;
	}

	function get_table_list($return_type='all'){
		/*
		 * @param : return_type - string : all, wp, non_wp
		 * @return all tables from db
		 */
		$arr = array();
		$q = "SELECT table_name FROM information_schema.tables WHERE table_schema = '". DB_NAME ."'";
		$data = DB()->get_results($q);
		foreach ($data as $table){
			if (strpos($table->table_name, DB()->prefix)===0){
				$key = str_replace(DB()->prefix, '', $table->table_name);
			} else {
				$key = $table->table_name;
			}
			$arr[$key] = $table->table_name;
		}

		if ($return_type!='all'){
			$tables_wp = DB()->tables('all');
			$native_wp = array();
			foreach ($tables_wp as $k=>$v){
				if (strpos($v, DB()->prefix)===0){
					$key = str_replace(DB()->prefix, '', $v);
				} else {
					$key = $v;
				}
				$native_wp[$key] = $v;
			}

			if ($return_type=='wp'){
				return $native_wp;
			} elseif ($return_type=='non_wp'){
				return array_diff($arr, $native_wp);
			}
		}
		return $arr;
	}

	function backup_DB($tables = array(), $file, $where = ''){
		if(empty($tables)) return false;
		if(file_exists($file)) @unlink($file);
		if(!empty($where)) $where = "WHERE $where";

		$tables = $this->get_table_list();
		$return = '';
		foreach($tables as $table){
			$num_fields = 0;
			$result = DB()->get_results( "SELECT * FROM $table", ARRAY_N);
			$fields = DB()->get_results( "SHOW COLUMNS FROM $table");
			$num_fields = (int) count($fields);

			$return.= 'DROP TABLE IF EXISTS `'.$table.'`;';
			$row2 = DB()->get_row("SHOW CREATE TABLE $table", ARRAY_N);
			$return.= "\n\n".$row2[1].";\n\n";


			foreach($result as $row){
				$return.= 'INSERT INTO `'.$table.'` VALUES(';

				for($j=0; $j < $num_fields; $j++) {
					$row[$j] = addslashes($row[$j]);
					$row[$j] = str_replace("\n","\\n",$row[$j]);
					if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
					if ($j < ($num_fields-1)) { $return.= ','; }
				}

				$return.= ");\n";
			}

			$return.="\n\n\n";
		}

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) $sitename = substr( $sitename, 4 );
		$sitename = str_ireplace('.', '-', $sitename);

		$path_info = wp_upload_dir();
		$dir = $path_info['basedir'] . '/backup-db';
		$url = $path_info['baseurl'] . '/backup-db';

		$name = $sitename.'-db-backup-'.date('M-d-Y').'-'.time();
		$fname = $dir.'/'.$name;
		$furl  = $url.'/'.$name;

		wp_mkdir_p($dir);
		$handle = fopen( $fname.'.sql', 'w+' );
		fwrite( $handle, $return );
		fclose( $handle );

		if (class_exists('ZipArchive')) {
	        $zip = new ZipArchive;
	        $zip->open($fname . ".zip", ZipArchive::CREATE);
	        $zip->addFile($fname.'.sql', $name.'.sql');
	        $zip->close();
	        @unlink($fname.".sql");
	        echo '<a href="'.$furl.'.zip" target="_blank">'.$name.'.zip</a>';
	    } else {
	    	echo '<a href="'.$furl.'.sql" target="_blank">'.$name.'.sql</a>';
	    }

		die();
	}

	function print_r($arr, $json = false){
		echo '<pre>';
		if($json){
			echo json_encode($arr, JSON_PRETTY_PRINT);
		} else {
			print_r($arr);
		}
		echo '</pre>';
	}

	function write_log( $message ) {
        if(!$this->debug) return false;
        $file = APP_PLUGIN_DIR . "/debug.log";
        $handle = fopen( $file, 'ab' );
        $data = date( "[Y-m-d H:i:s]" ) . $message . "\r\n";
        fwrite($handle, $data);
        fclose($handle);
    }

    public function getErrorString($exception){
        $class = get_class($exception);
        $code = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : $exception->getCode();
        return  "***{$class}*** [{$code}] : {$exception->getFile()} [Line {$exception->getLine()}] => {$exception->getMessage()}";
    }

    public function logError($error, $context = 'PHP'){
    	if(!session_id()) session_start();
        if ($error instanceof Exception) {
            $error = self::getErrorString($error);
        }

        $count = isset($_SESSION['error_count']) ? $_SESSION['error_count'] : 0;
        $_SESSION['error_count'] = ++$count;
        if ($count > 50) {
            return 'logged';
        }

        $data = [
            'context' => $context,
            'user_id' => AH()->user() ? AH()->user()->ID : 0,
            'method' => $context,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'ip' => AH()->get_ip(),
            'count' => $count,
        ];

        $this->log($error, $data);
    }

    public function log($message, array $context = []){
    	$upload_dir = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];
		$upload_base_url = $upload_dir['baseurl'];
		$folder = $upload_base_dir;
		$file = trailingslashit($folder).'app_log.log';

		$text = '';
		if(!empty($context)){
			foreach($context as $k => $v){
				$text .= $k.': '.$v.' - ';
			}
			$text = rtrim($text, ' - ');
		}

		$handle = fopen( $file, 'ab' );
        $data = "\r\n". date( "[Y-m-d H:i:s]" ) . $message . "\r\n" . $text . "\r\n";
        fwrite($handle, $data);
        fclose($handle);
    }

}
function AH() {return App_Helpers::instance();}
$GLOBALS['AH'] = AH();
