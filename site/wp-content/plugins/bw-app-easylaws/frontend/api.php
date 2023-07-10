<?php
class APP_WEB_API {
	public $prx, $u, $user_id;

	private static $_instance;
    public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

	public function __construct(){
		$this->prx = DB()->prefix.'app_';
		$this->cookie_email = 'easylaws_user_email';
		$this->cookie_key = 'easylaws_user_key';
		$this->__init();
	}

	function __init(){
		$this->u = $this->user();
		$this->user_id = $this->u ? $this->u->ID : '';
	}

	function rq($item = ''){
		if ($item) return !empty($_REQUEST[$item]) ? trim($_REQUEST[$item]) : '';
		return '';
	}

	function send_response($args = []){
		if(!is_array($args)) die();
		$args['valid'] = 'YES';
		return $args;
	}

	function bad_request($reason = ''){
		return ['valid' => 'NO', 'reason' => $reason];
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
		// $definitions = DB()->get_results("SELECT ID, title from $t");
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
			$before = '<a href="#" class="inner-link" data-id="'.$k['ID'].'">';
			$after = '</a>';
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
		// return $url;
		return [
			'dir' => $uf['dir'].$name,
			'url' =>  $uf['url'].$name,
		];
	}


	function get_subject_by_id($id){
		$id = intval($id);
		if(!$id) return $this->bad_request('No ID');
		$t = $this->prx.'subjects';
		$row = DB()->get_row("SELECT * FROM {$t} WHERE ID={$id}");
		if(!$row) return $this->bad_request('No Results');
		$row->image = get_subject_image($row->image);
		$row->color = get_subject_color($row->color);
		return $this->send_response(['subject' => $row]);
	}



	function get_used_subjects(){
		$t = $this->prx.'user_used_subjects';
		$ts = $this->prx.'subjects';
		$user_id = $this->user_id;
		if(!$user_id) return $this->bad_request('No User');

		$subjects = [];
		$subjects = DB()->get_results("SELECT * FROM {$t} WHERE user_id={$user_id} ORDER BY cnt DESC");
		$subjects = array_map(function($item) use ($ts) {
			$subj = DB()->get_row("SELECT title,image from {$ts} WHERE ID={$item->subject_id}");
			if($subj){
				$item->ID = $item->subject_id;
				$item->title = $subj->title;
				$item->image = get_subject_image($subj->image);
			}
			return $item;
		}, $subjects);
		return $this->send_response([
			'results' => $subjects,
		]);
	}

	function did_you_know(){
		$t = $this->prx.'questions';
		$q = DB()->get_results("SELECT ID,did_you_know from {$t} WHERE status=1 AND trashed=0 AND CHAR_LENGTH(did_you_know) > 10 ORDER BY rand() LIMIT 20");
		return $this->send_response([ 'results' => $q ]);
	}

	function get_dashboard(){
		$ts = $this->prx.'subjects';
		$tus = $this->prx.'user_used_subjects';
		$tl = $this->prx.'user_likes';
		$tq = $this->prx.'questions';
		$user_id = $this->user_id;

		// most visited subjects
		$subjects = DB()->get_results("SELECT * FROM {$ts} ORDER BY views DESC LIMIT 10");
		$subjects = array_map(function($item){
			$item->image = get_subject_image($item->image);
			$item->color = get_subject_color($item->color);
			return $item;
		}, $subjects);

		// used subjects
		if($user_id){
			$used_subjects = DB()->get_results("SELECT * FROM {$tus} WHERE user_id={$user_id} ORDER BY cnt DESC LIMIT 10");
			$used_subjects = array_map(function($item) use ($ts) {
				$subj = DB()->get_row("SELECT title,image,color from {$ts} WHERE ID={$item->subject_id}");
				if($subj){
					$item->ID = $item->subject_id;
					$item->title = $subj->title;
					$item->image = get_subject_image($subj->image);
					$item->color = get_subject_color($subj->color);
				}
				return $item;
			}, $used_subjects);
		} else {
			$used_subjects = [];
		}

		// liked subjects
		if($user_id){
			$liked_subjects = DB()->get_results("SELECT * FROM {$tl} WHERE user_id={$user_id} AND `type`=1 ORDER BY date_created DESC LIMIT 10");
			$liked_subjects = array_map(function($item) use ($ts) {
				$subj = DB()->get_row("SELECT title,image,color from {$ts} WHERE ID={$item->subject_id}");
				if($subj){
					$item->ID = $item->subject_id;
					$item->title = $subj->title;
					$item->image = get_subject_image($subj->image);
					$item->color = get_subject_color($subj->color);
				}
				return $item;
			}, $liked_subjects);
		} else {
			$liked_subjects = [];
			$liked_subjects = DB()->get_results("SELECT * FROM {$ts} ORDER BY likes DESC LIMIT 10");
    		$liked_subjects = array_map(function($item){
    			$item->image = get_subject_image($item->image);
				$item->color = get_subject_color($item->color);
    			return $item;
    		}, $liked_subjects);
		}

		// liked questions
		if($user_id){
			$liked_qn = DB()->get_results("SELECT * FROM {$tl} WHERE user_id={$user_id} AND `type`=0 ORDER BY date_created DESC LIMIT 10");
			$liked_qn = array_map(function($item) use ($tq) {
				$title = DB()->get_var("SELECT title from {$tq} WHERE ID={$item->question_id}");
				$item->ID = $item->question_id;
				$item->title = $title;
				$item->color = app_get_question_color($item->question_id);
				return $item;
			}, $liked_qn);
		} else {
			$liked_qn = [];
		}

		return [
			'subjects' => $subjects,
			'used_subjects' => $used_subjects,
			'liked_subjects' => $liked_subjects,
			'liked_questions' => $liked_qn,
		];
	}

	function get_dyk($limit = 1){
		$tq = $this->prx.'questions';
		$did_you_know = DB()->get_results("SELECT ID,did_you_know from {$tq} WHERE status=1 AND trashed=0 AND CHAR_LENGTH(did_you_know) > 10 ORDER BY rand() LIMIT $limit");
		if($did_you_know){
			if($limit === 1){
				return $did_you_know[0];
			} else {
				return $did_you_know;
			}
		}
		return [];
	}

	function set_request($details = '', $wav = ''){
		$t = $this->prx.'requests';
		$user_id = $this->user_id;
		if($user_id){
			$file = ['url' => '', 'dir' => ''];
			$this->server_limit();
			if($wav) $file = $this->base64_audio($wav);
			DB()->insert($t, [
				'user_id' => $user_id,
				'details' => $details,
				'file' => $file['url'],
				'date_created' => time(),
			]);

			$user = app_user_object($user_id);
			$comment = nl2br($details);
			$subject = 'New Request: FROM "'.$user->name.'"';
			$message = "
				<p>A new request has been generated</p>
				<p><b>User:</b> {$user->name} (Email: {$user->email}, Phone: {$user->phone})</p>
				<p><b>Comment:</b> {$comment}</p>
			";
			$att = $wav ? [$file['dir']] : [];
			// app_notify_admins($subject, $message, $att);

			return $this->send_response();
		} else { return $this->bad_request('Invalid Input'); }
	}

	function set_search_history($keyword){
		$t = $this->prx.'user_search_history';
		$user_id = $this->user_id;
		if($user_id && $keyword){
			$check = DB()->get_var("SELECT ID FROM $t WHERE user_id={$user_id} AND keyword='${$keyword}'");
			if($check){
				DB()->update($t, ['date_created' => time()], ['ID' => $check]);
			} else {
				DB()->insert($t, [
					'user_id' => $user_id,
					'keyword' => $keyword,
					'date_created' => time(),
				]);
			}
			return $this->send_response();
		} else { return $this->bad_request('Invalid Input'); }
	}

	function get_browsing_history($page = 1){
		$t = $this->prx.'user_browsing_history';
		$user_id = $this->user_id;
		if(!$user_id) return $this->bad_request();

		$per_page = 20;
		$page = intval($page);
		$page = $page ? $page : 1;
		$offset = ($page - 1) * $per_page;

		$where = "WHERE `user_id`={$user_id}";
		$order = "date_created DESC";

		$total = (int) DB()->get_var("SELECT COUNT(*) FROM {$t} {$where}");
		$total_pages = ceil($total / $per_page);
        $results = DB()->get_results("SELECT * FROM {$t} {$where} ORDER BY {$order} LIMIT {$offset},{$per_page}");

		return $this->send_response( [
			'page' => $page,
			'total_pages' => $total_pages,
			'total' => $total,
			'per_page' => $per_page,
			'results' => $results
		] );
	}

	function set_browsing_history($question_id, $title = ''){
		$t = $this->prx.'user_browsing_history';
		$user_id = $this->user_id;
		$question_id = intval($question_id);

		$check = DB()->get_var("SELECT ID FROM $t WHERE user_id={$user_id} AND question_id={$question_id}");
		if($check){
			DB()->update($t, [
				'dat' => date('Y-m-d'),
				'date_created' => time(),
			], ['ID' => $check]);
			return $this->send_response();
		} else {
			if($user_id && $question_id && $title && !$check){
				DB()->insert($t, [
					'user_id' => $user_id,
					'question_id' => $question_id,
					'title' => $title,
					'dat' => date('Y-m-d'),
					'date_created' => time(),
				]);
				return $this->send_response();
			} else { return $this->bad_request('Invalid Input'); }
		}
	}

	function comment_vote($comment_id, $direction = 'up'){
		$t = $this->prx.'user_comment_votes';
		$tq = $this->prx.'question_comments';
		$user_id = $this->user_id;
		$comment_id = intval($comment_id);

		if($user_id && $comment_id && $direction){
			$direction = $direction == 'down' ? 0 : 1;
			$user_check = DB()->get_var("SELECT status FROM {$this->prx}users WHERE ID={$user_id}");
			$vote_check = DB()->get_var("SELECT ID FROM {$t} WHERE user_id={$user_id} AND comment_id={$comment_id}");
			if($user_check && !$vote_check){
				if($direction){
					DB()->query("UPDATE $tq SET votes_up = votes_up + 1 WHERE ID={$comment_id}");
				} else {
					DB()->query("UPDATE $tq SET votes_down = votes_down + 1 WHERE ID={$comment_id}");
				}
				DB()->insert($t, [
					'user_id' => $user_id,
					'comment_id' => $comment_id,
					'direction' => $direction,
					'date_created' => time(),
				]);
				return $this->send_response(['results' => $direction]);
			} else { return $this->bad_request('Already Voted'); }
		} else { return $this->bad_request('Invalid Input'); }
	}

	function vote($question_id, $direction = 'up'){
		$t = $this->prx.'user_votes';
		$tq = $this->prx.'questions';
		$user_id = $this->user_id;
		$question_id = intval($question_id);

		if($user_id && $question_id && $direction){
			$direction = $direction == 'down' ? 0 : 1;
			$user_check = DB()->get_var("SELECT status FROM {$this->prx}users WHERE ID={$user_id}");
			if(!$user_check) return $this->bad_request('User not found');
			$vote_check = DB()->get_row("SELECT ID, direction FROM {$t} WHERE user_id={$user_id} AND question_id={$question_id}");

			if(!$vote_check){
				if($direction){
					DB()->query("UPDATE $tq SET votes_up = votes_up + 1 WHERE ID={$question_id}");
				} else {
					DB()->query("UPDATE $tq SET votes_down = votes_down + 1 WHERE ID={$question_id}");
				}
				DB()->insert($t, [
					'user_id' => $user_id,
					'question_id' => $question_id,
					'direction' => $direction,
					'date_created' => time(),
				]);
				return $this->send_response(['results' => $direction]);
			} elseif ($vote_check->direction != $direction){
				if($direction){
					DB()->query("UPDATE $tq SET votes_up = votes_up + 1 WHERE ID={$question_id}");
					DB()->query("UPDATE $tq SET votes_down = votes_down - 1 WHERE ID={$question_id}");
				} else {
					DB()->query("UPDATE $tq SET votes_down = votes_down + 1 WHERE ID={$question_id}");
					DB()->query("UPDATE $tq SET votes_up = votes_up - 1 WHERE ID={$question_id}");
				}
				DB()->update($t, [
					'direction' => $direction,
					'date_created' => time(),
				], ['ID' => $vote_check->ID]);
				return $this->send_response(['results' => $direction]);
			} else { return $this->bad_request('Already Voted'); }
		} else { return $this->bad_request('Invalid Input'); }
	}

	function like_delete($id, $type = 0){
		$t = $this->prx.'user_likes';
		$tq = $this->prx.'questions';
		$ts = $this->prx.'subjects';
		$user_id = $this->user_id;
		$id = intval($id);
		$type = intval($type) == 1 ? 1 : 0;

		if(!$user_id) return $this->bad_request('No User supplied');
		$user_check = DB()->get_var("SELECT status FROM {$this->prx}users WHERE ID={$user_id}");
		if(!$user_check) return $this->bad_request('User Not Allowed');
		if(!$id) return $this->bad_request('Invalid Input');

		if($type){ // subject
			DB()->query("UPDATE {$ts} SET likes = likes - 1 WHERE ID={$id}");
			$deleted = 'subject';
			DB()->delete($t, ['user_id' => $user_id, 'subject_id' => $id]);
		} else { //question
			DB()->query("UPDATE {$tq} SET likes = likes - 1 WHERE ID={$id}");
			$deleted = 'Question';
			DB()->delete($t, ['user_id' => $user_id, 'question_id' => $id]);
		}
		return $this->send_response(['deleted' => $deleted]);
	}

	function like($id, $type = 0){
		$t = $this->prx.'user_likes';
		$tq = $this->prx.'questions';
		$ts = $this->prx.'subjects';
		$user_id = $this->user_id;
		$id = intval($id);
		$type = intval($type) == 1 ? 1 : 0;

		if(!$user_id) return $this->bad_request('No User supplied');
		$user_check = DB()->get_var("SELECT status FROM {$this->prx}users WHERE ID={$user_id}");
		if(!$user_check) return $this->bad_request('User Not Allowed');

		if($type){ // subject
			if(!$id) return $this->bad_request('Invalid Input');
			$like_check = DB()->get_var("SELECT ID FROM {$t} WHERE user_id={$user_id} AND subject_id={$id}");
			if($like_check){
				$like = 0;
				DB()->query("UPDATE {$ts} SET likes = likes - 1 WHERE ID={$id}");
				DB()->delete($t, ['user_id' => $user_id, 'subject_id' => $id]);
			} else {
				$like = 1;
				DB()->query("UPDATE {$ts} SET likes = likes + 1 WHERE ID={$id}");
				DB()->insert($t, [
					'user_id' => $user_id,
					'subject_id' => $id,
					'type' => $type,
					'date_created' => time(),
				]);
			}
			return $this->send_response(['results' => $like]);
		} else { //question
			if(!$id) return $this->bad_request('Invalid Input');
			$like_check = DB()->get_var("SELECT ID FROM {$t} WHERE user_id={$user_id} AND question_id={$id}");
			if($like_check){
				$like = 0;
				DB()->query("UPDATE {$tq} SET likes = likes - 1 WHERE ID={$id}");
				DB()->delete($t, ['user_id' => $user_id, 'question_id' => $id]);
			} else {
				$like = 1;
				DB()->query("UPDATE {$tq} SET likes = likes + 1 WHERE ID={$id}");
				DB()->insert($t, [
					'user_id' => $user_id,
					'question_id' => $id,
					'type' => $type,
					'date_created' => time(),
				]);
			}
			return $this->send_response(['results' => $like]);
		}
	}

	function get_likes($type = 1, $page = 1){
		$t = $this->prx.'user_likes';
		$tq = $this->prx.'questions';
		$ts = $this->prx.'subjects';

		$user_id = $this->user_id;
		if(!$user_id) return $this->bad_request();
		$type = intval($type) == 1 ? 1 : 0;

		$per_page = 20;
		$page = intval($page);
		$page = $page ? $page : 1;
		$offset = ($page - 1) * $per_page;

		$where = "WHERE `type`={$type} AND `user_id`={$user_id}";
		$order = "date_created DESC";

		$total = (int) DB()->get_var("SELECT COUNT(*) FROM {$t} {$where}");
		$total_pages = ceil($total / $per_page);
        $results = DB()->get_results("SELECT * FROM {$t} {$where} ORDER BY {$order} LIMIT {$offset},{$per_page}");

        $table = $type ? $ts : $tq;

        $results = array_map(function($item) use ($table, $type){
        	$ID = $type ? $item->subject_id : $item->question_id;
        	$row = DB()->get_row("SELECT * FROM {$table} WHERE ID={$ID}");

        	$item->date = date('Y-m-d', $item->date_created);
        	$item->title = $row->title ? $row->title : '---';
        	if($type){
        		$item->color = get_subject_color($row->color);
        		$item->image = get_subject_image($row->image);
        	}
        	return $item;
        }, $results);
		return $this->send_response( [
			'page' => $page,
			'total_pages' => $total_pages,
			'total' => $total,
			'per_page' => $per_page,
			'results' => $results
		] );
	}

	function comment($question_id, $details = ''){
		$t = $this->prx.'question_comments';
		$tq = $this->prx.'questions';
		$tu = $this->prx.'users';
		$user_id = $this->user_id;
		$question_id = intval($question_id);
		$details = wp_unslash($details);

		if($user_id && $question_id && details){
			$user = DB()->get_row("SELECT * FROM {$tu} WHERE ID={$user_id}");

			if($user && $user->status){
				$status = 0;
				if($user->is_admin){
					$status = 1;
					DB()->query("UPDATE {$tq} SET comments = comments + 1 WHERE ID={$question_id}");
				}
				DB()->insert($t, [
					'user_id' => $user_id,
					'question_id' => $question_id,
					'details' => $details,
					'status' => $status, // auto approve comments for admins
					'date_created' => time(),
				]);

				$last_id = DB()->insert_id;
				$user = app_user_object($user_id);
				$question = DB()->get_var("SELECT title FROM {$tq} WHERE ID={$question_id}");
				$comment = nl2br($details);
				$status_translated = $status ? 'Approved' : 'Pending';
				$link = site_url()."/dashboard/admin.php?page=app-comments&id={$last_id}&action=";
				$subject = 'Please moderate: "'.$question.'"';
				$message = "
					<p>A new comment on the question \"{$question}\" (ID: {$question_id}) is waiting for your approval</p>
					<p><b>User:</b> {$user->name} (Email: {$user->email}, Phone: {$user->phone})</p>
					<p><b>Comment:</b> {$comment}</p>
					<p><b>Status:</b> {$status_translated}</p>
					<p><b>Action:</b> <a href=\"{$link}approve\">Approve it</a> - <a href=\"{$link}unapprove\">UnApprove it</a></p>
				";
				// app_notify_admins($subject, $message);

				return $this->send_response();
			} else { return $this->bad_request('User Not Allowed'); }
		} else { return $this->bad_request('Invalid Input'); }
	}

	function arabicDate($time, $withtime = true){
		$months = [
			'Jan' => 'كانون ثاني',
			'Feb' => 'شباط',
			'Mar' => 'آذار',
			'Apr' => 'نيسان',
			'May' => 'أيار',
			'Jun' => 'حزيران',
			'Jul' => 'تموز',
			'Aug' => 'آب',
			'Sep' => 'أيلول',
			'Oct' => 'تشرين أول',
			'Nov' => 'تشرين ثاني',
			'Dec' => 'كانون أول',
		];

		$days = [
			'Sun' => 'الأحد',
			'Mon' => 'الاثنين',
			'Tue' => 'الثلاثاء',
			'Wed' => 'الأربعاء',
			'Thu' => 'الخميس',
			'Fri' => 'الجمعة',
			'Sat' => 'السبت',
		];

		$am_pm = ['AM' => 'صباحاً', 'PM' => 'مساءً'];

		$day = $days[date('D', $time)];
	    $month = $months[date('M', $time)];
	    $am_pm = $am_pm[date('A', $time)];
	    // $date = $day . ' ' . date('d', $time) . ' - ' . $month . ' - ' . date('Y', $time) . '   ' . date('h:i', $time);
	    $date = date('d', $time) . ' - ' . $month . ' - ' . date('Y', $time) . '   ' . date('h:i', $time);
	    if($withtime) $date = $date.' ' . $am_pm;
	    $numbers_ar = ["٠", "١", "٢", "٣", "٤", "٥", "٦", "٧", "٨", "٩"];
	    $numbers_en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

	    return str_replace($numbers_en, $numbers_ar, $date);
	}

	function get_definition($id){
		$t = $this->prx.'definitions';
		$id = intval($id);
		if($id){
			$result = DB()->get_row("SELECT * FROM $t WHERE ID={$id}");
			if($result){
				$result->details = $this->remove_line_breaks($result->details);
				$result->tags = app_tags_translate($result->tags);
				$result->examples = app_repeat_translate($this->remove_line_breaks($result->examples));
        		$result->notes = app_repeat_translate($this->remove_line_breaks($result->notes));
				return $this->send_response( ['results' => $result ] );
			} else { return $this->bad_request(); }
		} else { return $this->bad_request(); }
	}

	function get_reference($id){
		$t = $this->prx.'references';
		$id = intval($id);
		if($id){
			$result = DB()->get_row("SELECT * FROM $t WHERE ID={$id}");
			if($result){
				$result->parent = $result->parent ? reference_by_id($result->parent) : '';
				return $this->send_response( ['results' => $result ] );
			} else { return $this->bad_request(); }
		} else { return $this->bad_request(); }
	}

	function get_tags(){
		$t = $this->prx.'tags';
		$tq = $this->prx.'questions';
		$result = DB()->get_results("SELECT *, (SELECT COUNT(ID) FROM {$tq} WHERE FIND_IN_SET({$t}.ID, tags)>0) AS tag_count FROM $t order by tag_count DESC");
		if($result){
			$result = array_map(function($item){
				$item->count = $item->tag_count;
				return $item;
			}, $result);
			return $this->send_response( ['results' => $result ] );
		} else { return $this->bad_request(); }
	}

	function get_subject_ancestors( $post, $implode = false ) {
		$t = DB()->prefix.'app_subjects';
		$post = intval($post);
		if(!$post) return;
		$post = DB()->get_row("SELECT ID,parent,title FROM $t WHERE ID=$post");
		if(!$post) return '';
		if (empty($post->parent) || $post->parent == $post->ID || $post->parent == 0) {
			if($implode){
				return $post->title;
			} else {
				return array($post->title);
			}
		}
		$ancestors = array();
		$ancestors_names = array();
		$id = $ancestors[] = $post->parent;
		$ancestors_names[] = '<a href="'.site_url('subject/'.$post->ID).'">'.$post->title.'</a>';

		while ($ancestor = DB()->get_row("SELECT ID,parent,title FROM $t WHERE ID=$id") ) {
			if ( !$ancestor->parent || ($ancestor->parent == $post->ID) || in_array($ancestor->parent, $ancestors) ) {
				$id = $ancestors[] = $ancestor->parent;
				$ancestors_names[] = '<a href="'.site_url('subject/'.$ancestor->ID).'">'.$ancestor->title.'</a>';
				break;
			}

			$id = $ancestors[] = $ancestor->parent;
			$ancestors_names[] = '<a href="'.site_url('subject/'.$ancestor->ID).'">'.$ancestor->title.'</a>';
		}
		$o = array_reverse($ancestors_names);
		if($implode){
			return implode($implode, $o);
		} else {
			return $o;
		}
	}

	function get_subject_row($id){
		$t = DB()->prefix.'app_subjects';
		$row = DB()->get_row("SELECT * FROM $t WHERE ID=$id");
		if($row){
			$row->image = get_subject_image($row->image, 'mobile-thumb');
	    	$row->color = get_subject_color($row->color);
		}
		return $row;
	}

	function get_subjects($args = []){
		$args = wp_parse_args($args, [
			'ID' => '',
			'parent' => '',
			'hide_empty' => true,
		]);
		$t = $this->prx.'subjects';
		$subjects = get_subjects_new($args);
		return $subjects;
	}

	function get_keywords($words){
    	if(empty($words)) return '';
    	$t = DB()->prefix.'app_keywords';
		$lines = [];
		$keywords = [];
		$ws = explode(' ', $words);
		
		foreach($ws as $w){
			if(strlen($w) < 2) continue;
			$res = DB()->get_results("SELECT details FROM $t WHERE title LIKE '%$w%' OR FIND_IN_SET('$w', details)>0 OR FIND_IN_SET(' $w', details)>0 OR FIND_IN_SET('$w ', details)>0");
			foreach($res as $r){
				$lines[] = $r->details;
			}
		}
		foreach($lines as $line){
			$keys = array_map('trim', explode(',', $line));
			$keywords = array_merge($keywords, $keys);
		}
		return $keywords;
    }

	function adjacent_questions($ID, $cat = ''){
		$ID = intval($ID);
		$cat = intval($cat);
		if(!$ID || !$cat) return ['next' => '', 'prev' => ''];
		$t = $this->prx.'questions';
		$ids = DB()->get_col("SELECT ID FROM $t WHERE status=1 AND trashed=0 AND FIND_IN_SET({$cat}, `categories`)>0 ORDER BY menu_order ASC, title ASC");
		$i = array_search($ID, $ids);
		if(!$i && $i !== 0) return ['next' => '', 'prev' => ''];
		$c = count($ids);
		$next = $i == $c ? 0 : $i+1;
		$prev = $i == 0 ? $c - 1 : $i - 1;
		return ['next' => $ids[$next], 'prev' => $ids[$prev]];
	}

	function is_subject_liked($ID){
		$t = $this->prx.'user_likes';
		$user_id = $this->user_id;
		$ID = intval($ID);
		if($user_id && $ID) {
			$check = DB()->get_var("SELECT ID FROM {$t} WHERE user_id={$user_id} AND subject_id={$ID}");
			return $check ? true : false;
		}
		return false;
	}

	function get_questions($args = []){
		$args = extract(wp_parse_args($args, [
			'cat' => '',
			'tag' => '',
			'order_by' => '',
			's' => '',
		]));
		$page = app_rq('page');
		$t = $this->prx.'questions';
		$ts = $this->prx.'subjects';
		$tl = $this->prx.'user_likes';
		$t_tags = $this->prx.'tags';
		$t_keywords = $this->prx.'keywords';
		$per_page = 20;

		$user_id = $this->user_id;
		$orderby = $orderby ? 'T.'.$orderby : 'T.date_created';
		$order = $order ? $order : 'DESC';

		$order = "$orderby $order";

		$page = $page ? $page : 1;
		$offset = ($page - 1) * $per_page;

		$is_subject_liked = 0;
		if($cat){
			// increment views
			DB()->query("UPDATE {$ts} SET views = views + 1 WHERE ID={$cat}");
			if($user_id) {
				$this->increment_used_subject($user_id, $cat);

				$like_check = DB()->get_var("SELECT ID FROM {$tl} WHERE user_id={$user_id} AND subject_id={$cat}");
				$is_subject_liked = $like_check ? 1 : 0;
			}
		}

		$select = "T.*";
		$join = "";
		$group = "";

		$where = "WHERE T.status=1 AND T.trashed=0";
		if($cat) $where .= " AND (FIND_IN_SET({$cat}, T.categories)>0)";
		if($tag) $where .= " AND (FIND_IN_SET({$tag}, T.tags)>0)";

		if($cat || $tag){
			$order = 'T.menu_order ASC, T.title ASC';
		}

		$subjects = [];
		$keywords = [];
		$s_orig = '';
		if ($s) {
		    $s_orig = $s;

			// $trans = app_trans()->go($s);
			// $st = $trans['term'];
			// $s = $trans['query'];
			$s  = __normalize_arabic($s);
			$s = sql_search_text($s);

			$s_title = sql_search_array('title_striped', $s);
			$s_details = sql_search_array('details_striped', $s);
			
			$keywords = $this->get_keywords($s);
			$k_title = sql_search_array('title_striped', $keywords);
			$k_details = sql_search_array('details_striped', $keywords);

			$select .= ", ((1.5 * (MATCH(T.title_striped) AGAINST ('$s' IN BOOLEAN MODE))) + (0.6 * (MATCH(T.details_striped) AGAINST ('$s' IN BOOLEAN MODE)))) AS relevance";

			$where .= " AND ( (MATCH(T.title_striped, T.details_striped) AGAINST ('$s' IN BOOLEAN MODE))    $s_title $s_details $k_title $k_details  )";
			// $where .= " GROUP BY T.ID";
			$order = "relevance DESC";

			if($page == 1){
				$subjects = get_subjects_new(['search' => $s_orig, 'hide_empty' => true]);
			}
		}

		$total = (int) DB()->get_var("SELECT COUNT(ID) FROM {$t} T {$where}");

		$query = "SELECT $select FROM {$t} T {$where} ORDER BY {$order} LIMIT {$offset},{$per_page}";
		$results = DB()->get_results($query);

		if($s_orig){
			$subtotal = absint($total) + count($subjects) + count($more);
			$has_results = $subtotal > 0 ? 1 : 0;
			$tsl = $this->prx.'search_log';
			$month = date('Y-m');
			DB()->query("INSERT INTO {$tsl} (`keyword`, `month`, `has_results`) VALUES ('{$s_orig}', '{$month}', {$has_results}) ON DUPLICATE KEY UPDATE count=count+1;");

			DB()->insert($this->prx.'search_dump',[
				'user_id' => $user_id ? $user_id : 0,
				'keyword' => $s_orig,
				'date_created' => time(),
				'has_results' => $has_results,
				'count' => $subtotal,
				'app_version' => $app_version,
			]);

		}

        $results = array_map(function($item) use ($definitions){
        	$item->excerpt = $this->excerpt($item->details);
        	$details = $this->remove_line_breaks($item->details);
	        $item->details = $details;
	        $item->tags = app_tags_translate($item->tags);
	        $item->references = app_references_translate($item->references);
	        $item->examples = app_repeat_translate($this->remove_line_breaks($item->examples));
	        $item->notes = app_repeat_translate($this->remove_line_breaks($item->notes));
	        $item->links = app_repeat_translate($this->remove_line_breaks($item->links));
	        $item->votes = intval($item->votes_up) - intval($item->votes_down);
	        $item->cat_ancestors = get_subject_ancestors_array($item->categories);
	        $item->images = get_question_images($item->images);
	        $item->videos = get_question_videos($item->videos);
	        $item->cat = $this->get_question_cat($item->categories);
	        return $item;

        }, $results);

		$total_pages = ceil($total / $per_page);
		$total = $total + count($subjects);
		
		return [
			'page' => $page,
			'total_pages' => $total_pages,
			'total_subjects' => count($subjects),
			'total' => $total,
			'per_page' => $per_page,
			'keywords' => $keywords,
			'subjects' => $subjects,
			'results' => $results,
			'is_subject_liked' => $is_subject_liked,
			'query' => $query,
		];
	}

	function get_question_cat($ID){
	    if(strpos(',', $ID) !== false){
			$ID = explode(',', $ID);
			$ID = $ID[0];
		}
		$ID = intval($ID);
		if(!$ID) return;
		$ts = $this->prx.'subjects';
		$subj = DB()->get_row("SELECT * from {$ts} WHERE ID={$ID}");
		if($subj){
			$subj->image = get_subject_image($subj->image);
			$subj->color = get_subject_color($subj->color);
			$subj->ancestors = $this->get_subject_ancestors($ID, '<i class="fa fa-arrow-left mx-3"></i>');
		}
		return $subj;
	}

	function map_question($item){
		$definitions = DB()->get_results("SELECT ID, title from {$this->prx}definitions");

		$item->excerpt = $this->excerpt($item->details);

		$details = $this->remove_line_breaks($item->details);
        $details = $this->apply_definitions($details, $definitions);
        $item->details = $details;

        $item->tags = app_tags_translate($item->tags);
        $item->references = app_references_translate($item->references);
        $item->examples = app_repeat_translate($this->remove_line_breaks($item->examples));

        $notes = $this->remove_line_breaks($item->notes);
        $notes = $this->apply_definitions($notes, $definitions);
        $item->notes = app_repeat_translate($notes);

        $item->links = app_repeat_translate($this->remove_line_breaks($item->links));
        $item->votes = intval($item->votes_up) - intval($item->votes_down);
        // $item->cat_ancestors = get_subject_ancestors_array($item->categories);
	    $item->images = get_question_images($item->images);
	    $item->videos = get_question_videos($item->videos);
	    $item->cat = $this->get_question_cat($item->categories);
        return $item;
	}

	function get_question_by_id($id){
		$ID = intval($id);
		if(!$ID) return $this->bad_request();
		$t = $this->prx.'questions';
		$result = DB()->get_row("SELECT * FROM $t WHERE ID={$ID}");
		if($result){
			$result = $this->map_question($result);
			$assets = $this->get_question_assets($ID);
			return $this->send_response(['results' => $result, 'assets' => $assets]);
		} else { return $this->bad_request(); }
	}

	function get_question_assets($question_id){
		$t = $this->prx.'questions';
		$ts = $this->prx.'subjects';
		$tl = $this->prx.'user_likes';
		$tc = $this->prx.'question_comments';
		$tu = $this->prx.'users';
		$tv = $this->prx.'user_votes';
		$user_id = $this->user_id;
		$question_id = intval($question_id);
		if(!$question_id) {
			return $this->bad_request();
			die();
		}
		// INCREMENT VIEWS
		DB()->query("UPDATE {$t} SET views = views + 1 WHERE ID={$question_id}");
		$cat = DB()->get_var("SELECT `categories` FROM {$t} WHERE ID={$question_id}");
		if($cat){
			$cats = explode(',', $cat);
			foreach($cats as $c){
				DB()->query("UPDATE {$ts} SET views = views + 1 WHERE ID={$c}");
				if($user_id) $this->increment_used_subject($user_id, $c);
			}
		}

		$like = 0;
		$voted = false;
		$vote_direction = '';
		$comments = get_question_comments($question_id, $user_id);
		if($user_id){
			$like_check = DB()->get_var("SELECT ID FROM {$tl} WHERE user_id={$user_id} AND question_id={$question_id}");
			$like = $like_check ? 1 : 0;

			$vote_check = DB()->get_row("SELECT ID,direction FROM {$tv} WHERE user_id={$user_id} AND question_id={$question_id}");

			if($vote_check){
				$voted = true;
				$vote_direction = $vote_check->direction ? 'up' : 'down';
			}

		}
		return $this->send_response(['like' => $like, 'comments' => $comments, 'voted' => $voted, 'vote_direction' => $vote_direction]);
	}

	function change_password($password = '', $old_password = ''){
		$t = $this->prx.'users';
		$user_id = $this->user_id;
		if(!user_id || strlen($password) < 6) return $this->bad_request('INVALID INPUT');

		// $pwd = DB()->get_var("SELECT password FROM $t WHERE ID='{$user_id}'");
		// if($pwd != $old_password) return $this->bad_request('WRONG PASSWORD');

		DB()->update($t, AH()->stripslashes([
			'password' => $password,
			'date_edited' => time(),
		]), ['ID' => $user_id]);
		return $this->send_response();
	}

	function change_email($email = '', $old_password = ''){
		$t = $this->prx.'users';
		$user_id = $this->user_id;
		if(!user_id || !is_email($email) || !$old_password) return $this->bad_request('BAD EMAIL OR ID');

		$pwd = DB()->get_var("SELECT password FROM $t WHERE ID='{$user_id}'");
		if($pwd != $old_password) return $this->bad_request('WRONG PASSWORD');

		$check = DB()->get_var("SELECT ID FROM $t WHERE email='{$email}' AND ID<>{$user_id}");
		if($check) return $this->bad_request('EMAIL FOUND IN DATABASE');

		DB()->update($t, AH()->stripslashes([
			'email' => $email,
			'date_edited' => time(),
		]), ['ID' => $user_id]);
		return $this->send_response();
	}

	function edit_profile($args = [], $img = ''){
		$user_id = $this->user_id;
		$image = '';
		$args = extract(wp_parse_args($args, [
			'name' => '',
			'phone' => '',
			'dob' => '',
			'gender' => '',
		]));
		$t = $this->prx.'users';

		if(!empty($img)){
			$uf = $this->upload_folder('pictures/'.date('Y/m/d'));
			$folder = $uf['dir'];
			$size = 256;
			$u = new App_Upload($img);
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
				$image = $uf['url'].$u->file_dst_name;
			}
		}
		
		if($user_id && $name){
			if($dob) $dob = date('Y-m-d', strtotime($dob));
			$tobe_updated = [
				'name' => $name,
				'phone' => $phone,
				'dob' => $dob,
				'gender' => $gender,
				'date_edited' => time(),
			];
			if($image) $tobe_updated['image'] = $image;
			DB()->update($t, AH()->stripslashes($tobe_updated), ['ID' => $user_id]);
			return $this->send_response();
		} else { return $this->bad_request(); }
	}

	function signup($args = []){
		$args = extract(wp_parse_args($args, [
			'email' => '',
			'password' => '',
			'name' => '',
			'image' => '',
			'phone' => '',
			'dob' => '',
			'gender' => '',
		]));
		$t = $this->prx.'users';

		$userObj = [
			'email' => strtolower($email),
			'password' => $password,
			'name' => $name,
			'image' => $image,
			'phone' => $phone,
			'gender' => $gender,
			'dob' => $dob,
			'status' => 0,
		];
		$this->create_user($userObj);
	}

	function get_user($user_id){
		$t = $this->prx.'users';
		$user_id = intval($user_id);
		if(!$user_id) return $this->bad_request('NO ID SUPPLIED');
		$u = DB()->get_row("SELECT * FROM $t WHERE ID='{$user_id}'");
		if(!$u) return $this->bad_request('NO USER FOUND');
		$u = $this->map_user($u);
		return $this->send_response(['results' => $u]);
	}

	function login($args = []){
		$args = extract(wp_parse_args($args, [
			'email' => '',
			'password' => '',
			'name' => '',
			'image' => '',
			'phone' => '',
			'dob' => '',
			'gender' => '',
			'fb_id' => '',
			'google_id' => '',
			'provider' => '',
		]));
		$t = $this->prx.'users';

		$userObj = [
			'email' => $email,
			'name' => $name,
			'image' => $image,
			'phone' => $phone,
			'dob' => $dob,
			'gender' => $gender,
			'fb_id' => $fb_id,
			'google_id' => $google_id,
			'provider' => $provider,
		];

		if ($provider == 'facebook'){
			if($fb_id){
				$u = DB()->get_row("SELECT * FROM $t WHERE fb_id='{$fb_id}' AND status=1");
				if($u){
				    $u = $this->map_user($u);
					DB()->update($t, ['last_login' => time()], ['ID' => $u->ID]);
					$this->set_session($u);
					return $this->send_response(['results'=>$u]);
				} else {
					$this->create_user($userObj);
				}
			}
		} elseif ($provider == 'google'){
			if($google_id){
				$u = DB()->get_row("SELECT * FROM $t WHERE google_id='{$google_id}' AND status=1");
				if($u){
				    $u = $this->map_user($u);
					DB()->update($t, ['last_login' => time()], ['ID' => $u->ID]);
					$this->set_session($u);
					return $this->send_response(['results'=>$u]);
				} else {
					$this->create_user($userObj);
				}
			}
		} elseif ($email && $password){
			$u = DB()->get_row("SELECT * FROM $t WHERE email='{$email}' AND password='{$password}'");
			if($u){
				$u = $this->map_user($u);
				if($u->status == 1){
					DB()->update($t, ['last_login' => time()], ['ID' => $u->ID]);
					$this->set_session($u);
					return $this->send_response(['results' => $u]);
				} else { 
					// return $this->bad_request('Account needs activation'); 
					return $this->bad_request('حسابك بحاجه إلى تفعيل');
				}
			} else { 
				// return $this->bad_request('Invalid email / password credentials'); 
				return $this->bad_request('بيانات اعتماد البريد الكتروني/كلمه المرور غير صالحه');
			}
		} else { 
			// return $this->bad_request('Not valid login method');
			return $this->bad_request('طريقه تسجيل الدخول غير صالحه'); 
		}
	}

	function create_user($args = []){
		$t = $this->prx.'users';
		$key = AH()->generate_password(16, false, 'lud');
		$args = wp_parse_args($args, array(
			'email' => '',
			'name' => '',
			'password' => '',
			'image' => '',
			'phone' => '',
			'dob' => '',
			'gender' => '',
			'fb_id' => '',
			'google_id' => '',
			'provider' => 'native',
			'date_created' => time(),
			'status' => 1,
			'key' => $key
		));
		extract($args);
		if($email && is_email($email) && $name){
			$email = strtolower(trim($email));
			if($dob){
				$dob = date('Y-m-d', strtotime($dob));
			}
			$check = DB()->get_var("SELECT ID FROM $t WHERE email='{$email}'");
			if(!$check){
				$password = strlen($password) > 3 ? $password : AH()->generate_password();
				$args['password'] = $password;
				$args = wp_unslash($args);
				DB()->insert($t, $args);
				$ID = (int) DB()->insert_id;
				$args['ID'] = $ID;

				$subject = 'EasyLaws Activation – Please confirm your email address';
				$link = site_url('activate/?key='.$key);
				$ip = AH()->get_ip();
				$body = "
		            <p>Hey there {$name}, thanks for choosing EasyLaws!</p>
					<p>We'll have you up and running in no time, but first we just need you to confirm your user account by clicking the link below: </p>
					<p>{$link}</p>
					<p>Once you confirm your account you can login right away and start using all the great features that EasyLaws has to offer. Feel free to drop us a line or email us if you have any questions at all - we're here to help! </p>
					<p>This account registration came from the IP: {$ip}</p>
					--
					<p><i>EasyLaws Team.</i></p>
		        ";
				wp_mail($email, $subject, $body);

				if($args['dob'] == '0000-00-00') $args['dob'] = '';
				$args['image'] = $args['image'] ? __CORS($args['image']) : app_avatar__($args['image']);

				// $this->set_session($args); // blocked for user activation
				return $this->send_response(['results'=>$args]);
			} 
			return $this->bad_request('Email already registered');
		} 
		return $this->bad_request('Malformed email or bad name'); 

	}

	function map_user($item){
		if($item->dob == '0000-00-00') $item->dob = '';
		$item->image = $item->image ? __CORS($item->image) : app_avatar__($item->name);
        return $item;
	}

	function forgot($email = ''){
		$t = $this->prx.'users';
		$email = strtolower( $email );
		if($email && is_email($email)){
			$email = strtolower(trim($email));
			$check = DB()->get_row("SELECT `name`, `password` FROM $t WHERE email='{$email}'");
			if($check){
				$subject = 'EasyLaws forgot password request';
				$pwd = $check->password;
				$name = $check->name;
				$body = "
		            <p>Hi {$name}</p>
		            <p>We got a request to send your EasyLaws password.<br>
		            Here below you may find your account password</p>
		            <h3>{$pwd}</h3>
		            <p>P.S: We're always around and love hearing from you.</p>
		            <p><i>EasyLaws Team.</i></p>
		        ";
				wp_mail($email, $subject, $body);
				return $this->send_response(['results'=> 'Password sent']);
			} 
			return $this->bad_request('Email is not registered');
		}  
		return $this->bad_request('Malformed email');
	}

	function change_img($user_id, $data){
		$t = $this->prx.'users';
		$user_id = intval($user_id);
		if(!$user_id) return $this->bad_request('NO ID SUPPLIED');
		if(!$data) return $this->bad_request('NO DATA');
		$check = DB()->get_var("SELECT ID FROM $t WHERE ID='{$user_id}'");
		if(!$check || intval($check) != $user_id) return $this->bad_request('NO ID FOUND');
		$image = $this->base64_image($data);
		if(!$image) return $this->bad_request('COULD NOT SAVE IMAGE');
		DB()->update($t, ['image' => $image], ['ID' => $user_id]);
		return $this->send_response(['results'=> 'Image Changed']);
	}


	function set_session($user, $remember = true){
    	$user = (object) $user;
    	$user = stripslashes_deep($user);
	   	$_SESSION[$this->cookie_email] = $user->email;
	   	$_SESSION[$this->cookie_key] = $user->key;

	   	if($remember){
		  	setcookie($this->cookie_email, $_SESSION[$this->cookie_email], time()+ (14 * DAY_IN_SECONDS), "/");
		  	setcookie($this->cookie_key, $_SESSION[$this->cookie_key], time()+ (14 * DAY_IN_SECONDS), "/");
	   	} else {
	   		setcookie($this->cookie_email, $_SESSION[$this->cookie_email], time()+ (2 * DAY_IN_SECONDS), "/");
	   		setcookie($this->cookie_key, $_SESSION[$this->cookie_key], time()+ (2 * DAY_IN_SECONDS), "/");
	   	}
	}

	function user_id(){
		$u = $this->user();
		return $u ? $u->ID : false;
	}

    function user(){
    	$t = $this->prx.'users';
    	$email = $key = '';
	   	if( !empty($_COOKIE[$this->cookie_email]) && !empty($_COOKIE[$this->cookie_key]) ){
		  	$email = $_COOKIE[$this->cookie_email];
		  	$key = $_COOKIE[$this->cookie_key];
	   	}
	   	if( !empty($_SESSION[$this->cookie_email]) && !empty($_SESSION[$this->cookie_key]) ){
	   		$email = $_SESSION[$this->cookie_email];
		  	$key = $_SESSION[$this->cookie_key];
	   	}
	   	if($email && $key){
	   		$u = DB()->get_row("SELECT * FROM {$t} WHERE `email`='{$email}' AND `key`='{$key}'");
	   		if(!$u){
	   			unset($_SESSION[$this->cookie_email]);
			 	unset($_SESSION[$this->cookie_key]);
			 	return false;
	   		}
	   		return $this->map_user($u);
	   } else {
		  return false;
	   }
	}

	function logout(){
		if(isset($_COOKIE[$this->cookie_email]) && isset($_COOKIE[$this->cookie_key])){
			setcookie($this->cookie_email, "", time()-60*60*24*100, "/");
			setcookie($this->cookie_key, "", time()-60*60*24*100, "/");
		}
		if(isset($_SESSION[$this->cookie_email]) && isset($_SESSION[$this->cookie_key])){
			unset($_SESSION[$this->cookie_email]);
			unset($_SESSION[$this->cookie_key]);
		}
		
		// $_SESSION = array();
		// session_destroy();
	   	return;
	}
}

function wapi() {return APP_WEB_API::instance();}
