<?php

class APP_API_BASE {

	public function assign($handle){
		$handle = trim($handle);
		if(!$handle) return;
		add_action('app_ajax_'.$handle, array($this, $handle));
		$this->record_user_activity();
	}
	
	function record_user_activity(){
		if($this->rq('user_id')){
			$id = intval($this->rq('user_id'));
			if(!$id) return;
			DB()->update(DB()->prefix.'app_users', ['date_active' => time()], ['ID' => $id]);
		}
	}

    function server_limit(){
    	@set_time_limit(0);
    	@ini_set('max_input_vars', 9000);
    	@ini_set('post_max_size', '2000M');
    	@ini_set('upload_max_filesize', '2000M');
    	@ini_set('memory_limit', '1000M');
    	@ini_set('max_execution_time', '2592000');
    	@ini_set('max_input_time', '2592000');
    }

    function excerpt($excerpt= '', $limit = 180, $after = '...'){
    	if(empty($excerpt)) return '';
	    $excerpt = preg_replace(" (\[.*?\])",'',$excerpt);
	    $excerpt = strip_shortcodes($excerpt);
	    $excerpt = strip_tags($excerpt);
	    $excerpt = str_replace('&nbsp;', ' ', $excerpt);

	    if(strlen($excerpt) <= $limit) $after = '';
	    $excerpt = substr($excerpt, 0, $limit);
	    if(strripos($excerpt, " ")){
	    	$excerpt = substr($excerpt, 0, strripos($excerpt, " "));
	    }
	    $excerpt = trim(preg_replace( '/\s+/', ' ', $excerpt));
	    $excerpt = $excerpt.$after;
	    return $excerpt;
	}

    function get_token(){
    	$headers = apache_request_headers();
		return $headers['X-Auth-Token'];
    }

    function send_json($o = array()){
	    header('Content-Type: application/json; charset=UTF-8');
	    echo json_encode( stripslashes_deep($o) ) ;
	    die();
	}

	function rq($item = ''){
		if ($item) return !empty($_REQUEST[$item]) ? trim($_REQUEST[$item]) : '';
		return '';
	}

	function validate_request(){
		if( $this->rq('user_id') && $this->rq('device_id') ){
			return true;
		}
		return false;
	}

	function send_response($args = []){
		if(!is_array($args)) die();
		$args['valid'] = 'YES';
		$this->send_json($args);
		die();
	}

	function bad_request($reason = ''){
		$this->send_json(['valid' => 'NO', 'reason' => $reason]);
		die();
	}

	function remove_line_breaks($text){
		if($text){
			return str_replace(['<p><br></p>', '<p></p>'], ['', ''], $text);
		}
		return '';
	}

	function increment_used_subject($user_id, $subject_id){
		$t = DB()->prefix.'app_user_used_subjects';
		$user_id = intval($user_id);
		$subject_id = intval($subject_id);
		if(!$user_id || !$subject_id) return;
		$check = DB()->get_var("SELECT ID FROM {$t} WHERE user_id={$user_id} AND subject_id={$subject_id}");
		if($check){
			DB()->query("UPDATE {$t} SET cnt = cnt + 1 WHERE ID={$check}");
		} else {
			DB()->insert($t, [
				'user_id' => $user_id,
				'subject_id' => $subject_id,
				'cnt' => 1,
			]);
		}
	}

	function apply_definitions($text, $definitions = []){
		// $t = DB()->prefix.'app_definitions';
		// $keywords = DB()->get_results("SELECT ID, title from $t");
		$keys = [];
		foreach($definitions as $keyword){
			if(strpos($keyword->title,';')===false){
				$a = ['ID' => $keyword->ID, 'VALUE' => trim($keyword->title)];
				if(!in_array($a, $keys)) $keys[] = $a;
			} else {
				foreach(explode(';', $keyword->title) as $k => $v){
					$v = trim($v);
					if($v) {
						$a = ['ID' => $keyword->ID, 'VALUE' => trim($v)];
						if(!in_array($a, $keys)) $keys[] = $a;
					}
				}
			}
		}
		$prevs = ['ال', 'أل', 'إل', 'ك', 'ب', 'ي', ' '];
		$nexts = [' ', '.', '،', ',', '?', '!'];
		$done = [];
		foreach($keys as $k){
			$before = '<span class="inner-link definition-'.$k['ID'].'" tappable>';
			$after = '</span>';
			$v = $k['VALUE'];
			if(!in_array($v, $done)){

				foreach($prevs as $prev){
					foreach($nexts as $next){
						if(stripos($text, $prev.$v.$next) !== false){
							$text = str_ireplace($prev.$v, $before.$prev.$v.$after, $text);
							$done[] = $v;
							break;
						}
					}
				}

			}

			// $text = preg_replace("/($v)/i", "$before$1$after", $text);
		}
		return $text;
	}

	function upload_folder($sub = ''){
		$upload_dir = wp_upload_dir();
		$dir = $upload_dir['basedir'];
		$url = $upload_dir['baseurl'];
		if($sub){
			$dir = trailingslashit($dir).trailingslashit($sub);
			wp_mkdir_p($dir);
			$url = trailingslashit($url).trailingslashit($sub);
		}
		return [
			'dir' => $dir,
			'url' => $url,
		];
	}

	function base64_image($data){
		$uf = $this->upload_folder('pictures/'.date('Y/m/d'));
		$img = base64_decode( str_replace(' ','+',preg_replace('#^data:image/\w+;base64,#i','',$data)) );

		$info = getimagesizefromstring($img);
		$mime = $info['mime'];
		$ext = app_mimetypes()->to_ext($mime);

		$name = uniqid().'.'.$ext;
		file_put_contents($uf['dir'].$name, $img);
		$url = $uf['url'].$name;
		return $url;
	}

	function base64_m4a($data){
		$uf = $this->upload_folder('audio/'.date('Y/m/d'));
		$data = str_replace('data:audio/x-m4a;base64,', '', $data);
		$data = str_replace(' ', '+', $data);
		$file = base64_decode( $data );

		$name = uniqid().'.m4a';
		file_put_contents($uf['dir'].$name, $file);
		$url = $uf['url'].$name;
		// return $url;
		return [
			'dir' => $uf['dir'].$name,
			'url' =>  $uf['url'].$name,
		];
	}

	function base64_audio($data){
		$uf = $this->upload_folder('audio/'.date('Y/m/d'));
		$file = base64_decode( str_replace(' ','+',preg_replace('#^data:audio/\w+;base64,#i','',$data)) );

		$name = uniqid().'.wav';
		file_put_contents($uf['dir'].$name, $file);
		$url = $uf['url'].$name;
		return $url;
	}
}
