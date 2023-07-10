<?php
class APP_API extends APP_API_BASE{
	public $prx;

	public function __construct(){
		$this->prx = DB()->prefix.'app_';

		$this->assign('get_subject_by_id');
		$this->assign('get_dashboard');
		$this->assign('did_you_know');
		$this->assign('get_firstrun_subjects');
		$this->assign('set_firstrun_subjects');

		$this->assign('get_firstrun_subjects_new');
		$this->assign('set_firstrun_subjects_new');

		$this->assign('get_used_subjects');

		$this->assign('get_subjects');
		$this->assign('get_questions');
		$this->assign('get_question_by_id');
		$this->assign('get_question_assets');
		$this->assign('get_definition');
		$this->assign('get_reference');
		$this->assign('get_tags');
		$this->assign('get_likes');

		$this->assign('comment_vote');
		$this->assign('vote');
		$this->assign('like');
		$this->assign('like_delete');
		$this->assign('comment');
		$this->assign('set_search_history');
		$this->assign('set_browsing_history');

		$this->assign('set_request');
		$this->assign('set_request_m4a');

		$this->assign('change_password');
		$this->assign('change_email');
		$this->assign('edit_profile');
		$this->assign('signup');
		$this->assign('get_user');
		$this->assign('login');
		$this->assign('forgot');
		$this->assign('change_img');

		$this->assign('record_device');

		$this->assign('mark_notifications');
		$this->assign('delete_notifications');
		$this->assign('get_notifications');
		$this->assign('get_unread_notifications');

		$this->assign('__subj');

		$this->assign('allow_requests');

		$this->assign('get_ads');
	}

	function get_ads(){
		$t = PRX.'sponsor_ads';
		$id = intval($this->rq('id'));
		$sect = $this->rq('sect');
		$screen = $this->rq('screen');
		if(!$sect) $this->bad_request('Invalid command');
		$now = time();
		$where = "active=1 AND start <= $now AND end >= $now";
		$results = DB()->get_results("SELECT * FROM $t WHERE $where");
		$results = array_map(function($item){
			$item->image = $item->image ? wp_get_attachment_image_url($item->image, 'full') : '';
			$item->link = site_url('/adout/'.$item->ID);
			return $item;
		}, $results);
		$this->send_response(['results' => $results]);
	}

	function allow_requests(){
		$this->send_response(['result' => (int) app_option('allow_requests') ? true : false]);
	}

	function __subj(){
		$this->send_response(['results' => get_subjects_new(['search' => 'company companies', 'hide_empty' => 0])]);
	}

	function mark_notifications(){
		$t = $this->prx.'notifications';
		$user_id = intval($this->rq('user_id'));
		if(!$user_id) $this->bad_request('No User');

		$as = $this->rq('as');
		if($as == 'read'){
			$read = 1;
		} else if($as == 'unread'){
			$read = 0;
		} else {
			$this->bad_request('Invalid command');
		}

		$ids = $this->rq('ids');
		$_key = $this->rq('_key');
		if($ids){
			if(!is_array($ids)) $ids = [ $ids ];
			$ids = array_map('intval', $ids);
			if(empty($ids)) $this->bad_request('No IDs supplied for the delete command');
			foreach($ids as $id){
				DB()->update($t, ['is_read' => $read], ['user_id' => $user_id, 'ID' => $id]);
			}
		} elseif ($_key) {
			DB()->update($t, ['is_read' => $read], ['user_id' => $user_id, '_key' => $_key]);
		} else {
			DB()->update($t, ['is_read' => $read], ['user_id' => $user_id]);
		}
		$this->send_response(['updated' => true]);
	}

	function delete_notifications(){
		$t = $this->prx.'notifications';
		$user_id = intval($this->rq('user_id'));
		if(!$user_id) $this->bad_request('No User');
		$ids = $this->rq('ids');
		if(!is_array($ids)) $ids = [ $ids ];
		$ids = array_map('intval', $ids);
		if(empty($ids)) $this->bad_request('No IDs supplied for the delete command');
		foreach($ids as $id){
			DB()->delete($t, ['user_id' => $user_id, 'ID' => $id]);
		}
		$this->send_response(['deleted' => true]);
	}

	function get_unread_notifications(){
		$t = $this->prx.'notifications';
		$user_id = intval($this->rq('user_id'));
		if(!$user_id) $this->bad_request('No User');
		$cnt = DB()->get_var("SELECT COUNT(ID) FROM $t WHERE user_id=$user_id AND is_read=0");
		$this->send_response(['count' => $cnt]);
	}

	function get_notifications(){
		$t = $this->prx.'notifications';
		$user_id = intval($this->rq('user_id'));
		if(!$user_id) $this->bad_request('No User');
		$rows = DB()->get_results("SELECT * FROM $t WHERE user_id=$user_id ORDER BY date_created DESC");

		$rows = array_map(function($item) {
			$item->details = nl2br($item->details);
			return $item;
		}, $rows);

		$this->send_response(['results' => $rows]);
	}

	function get_subject_by_id(){
		$id = intval($this->rq('id'));
		if(!$id) $this->bad_request('No ID');
		$t = $this->prx.'subjects';
		$row = DB()->get_row("SELECT * FROM {$t} WHERE ID={$id}");
		if(!$row) $this->bad_request('No Results');
		$row->image = get_subject_image($row->image);
		$row->color = get_subject_color($row->color);
		$this->send_response(['subject' => $row]);
	}

	function set_firstrun_subjects(){
		$t = $this->prx.'user_likes';
		$ts = $this->prx.'subjects';
		$user_id = intval($this->rq('user_id'));
		$subjects = $this->rq('subjects');

		if(!$user_id) $this->bad_request('No User');
		if(!$subjects) $this->bad_request('No Subjects provided');

		$subjects = explode(',', $subjects);
		if(empty($subjects)) $this->bad_request('No Subjects detected');

		$liked = DB()->get_col("SELECT subject_id FROM {$t} WHERE user_id={$user_id} AND `type`=1");
		foreach($liked as $l_subj){
			DB()->query("UPDATE $ts SET likes = likes - 1 WHERE ID={$l_subj}");
		}
		DB()->query("DELETE FROM {$t} WHERE user_id={$user_id} AND `type`=1");

		$cnt = 0;
		foreach($subjects as $subject_id){
			$subject_id = intval($subject_id);
			if(!$subject_id) continue;
			$check = DB()->get_var("SELECT ID FROM {$t} WHERE user_id={$user_id} AND subject_id={$subject_id}");
			if($check) continue;

			DB()->query("UPDATE $ts SET likes = likes + 1 WHERE ID={$subject_id}");
			DB()->insert($t, [
				'user_id' => $user_id,
				'subject_id' => $subject_id,
				'type' => 1,
				'date_created' => time(),
			]);
			$cnt++;
		}
		$this->send_response(['count' => $cnt, 'subjects' => $subjects]);
	}

	function get_firstrun_subjects(){
		$t = $this->prx.'user_likes';
		$ts = $this->prx.'subjects';
		$tq = $this->prx.'questions';
		$user_id = intval($this->rq('user_id'));

		$liked = [];
		if($user_id){
			$liked = DB()->get_col("SELECT subject_id FROM {$t} WHERE user_id={$user_id} AND `type`=1");
		}

		$subjects = DB()->get_results("SELECT * FROM {$ts} WHERE ID IN (SELECT categories FROM {$tq} WHERE status=1 AND trashed=0) ORDER BY views DESC, parent ASC");
		$subjects = array_map(function($item) use ($liked) {
			$is_liked = in_array($item->ID, $liked) ? 1 : 0;
			$item->is_liked = $is_liked;
			$item->image = get_subject_image($item->image);
			$item->color = get_subject_color($item->color);
			return $item;
		}, $subjects);

		$this->send_response([
			'subjects' => $subjects,
			'liked' => $liked,
		]);
	}


	function set_firstrun_subjects_new(){
		$t = $this->prx.'user_likes';
		$ts = $this->prx.'subjects';
		$user_id = intval($this->rq('user_id'));
		$subjects = $this->rq('subjects');

		if(!$user_id) $this->bad_request('No User');
		if(!$subjects) $this->bad_request('No Subjects provided');

		$subjects = explode(',', $subjects);
		if(empty($subjects)) $this->bad_request('No Subjects detected');

		$liked = DB()->get_col("SELECT subject_id FROM {$t} WHERE user_id={$user_id} AND `type`=1");
		foreach($liked as $l_subj){
			DB()->query("UPDATE $ts SET likes = likes - 1 WHERE ID={$l_subj}");
		}
		DB()->query("DELETE FROM {$t} WHERE user_id={$user_id} AND `type`=1");

		$cnt = 0;
		foreach($subjects as $subject_id){
			$subject_id = intval($subject_id);
			if(!$subject_id) continue;
			$check = DB()->get_var("SELECT ID FROM {$t} WHERE user_id={$user_id} AND subject_id={$subject_id}");
			if($check) continue;

			DB()->query("UPDATE $ts SET likes = likes + 1 WHERE ID={$subject_id}");
			DB()->insert($t, [
				'user_id' => $user_id,
				'subject_id' => $subject_id,
				'type' => 1,
				'date_created' => time(),
			]);
			$cnt++;
		}
		$this->send_response(['count' => $cnt, 'subjects' => $subjects]);
	}

	function get_firstrun_subjects_new(){
		$t = $this->prx.'user_likes';
		$ts = $this->prx.'subjects';
		$tq = $this->prx.'questions';
		$user_id = intval($this->rq('user_id'));

		$liked = [];
		if($user_id){
			$liked = DB()->get_col("SELECT subject_id FROM {$t} WHERE user_id={$user_id} AND `type`=1");
		}

		$subjects = DB()->get_results("SELECT * FROM {$ts} WHERE parent=0 AND posts_count > 0 ORDER BY views DESC, title ASC");

		$subjects = array_map(function($item) use ($liked) {
			$is_liked = in_array($item->ID, $liked) ? 1 : 0;
			$item->is_liked = $is_liked;
			$item->image = get_subject_image($item->image);
			$item->color = get_subject_color($item->color);
			return $item;
		}, $subjects);

		$this->send_response([
			'subjects' => $subjects,
			'liked' => $liked,
		]);
	}



	function get_used_subjects(){
		$t = $this->prx.'user_used_subjects';
		$ts = $this->prx.'subjects';
		$user_id = intval($this->rq('user_id'));
		if(!$user_id) $this->bad_request('No User');

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
		$this->send_response([
			'results' => $subjects,
		]);
	}

	function did_you_know(){
		$t = $this->prx.'questions';
		$q = DB()->get_results("SELECT ID,did_you_know from {$t} WHERE status=1 AND trashed=0 AND CHAR_LENGTH(did_you_know) > 10 ORDER BY rand() LIMIT 20");
		$this->send_response([ 'results' => $q ]);
	}

	function get_dashboard(){
		$ts = $this->prx.'subjects';
		$tus = $this->prx.'user_used_subjects';
		$tl = $this->prx.'user_likes';
		$tq = $this->prx.'questions';
		$user_id = intval($this->rq('user_id'));

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

		$did_you_know = DB()->get_results("SELECT ID,did_you_know from {$tq} WHERE status=1 AND trashed=0 AND CHAR_LENGTH(did_you_know) > 10 ORDER BY rand() LIMIT 20");

		$this->send_response([
			'subjects' => $subjects,
			'used_subjects' => $used_subjects,
			'liked_subjects' => $liked_subjects,
			'liked_questions' => $liked_qn,
			'did_you_know' => $did_you_know,
		]);
	}

	function set_request(){
		$t = $this->prx.'requests';
		$user_id = intval($this->rq('user_id'));
		$details = $this->rq('details');
		$wav = $this->rq('wav');
		if($user_id){
			$file = '';
			$this->server_limit();
			if($wav) $file = $this->base64_audio($wav);
			DB()->insert($t, [
				'user_id' => $user_id,
				'details' => $details,
				'file' => $file,
				'date_created' => time(),
			]);
			$this->send_response();
		} else { $this->bad_request('Invalid Input'); }
	}

	function set_request_m4a(){
		$t = $this->prx.'requests';
		$user_id = intval($this->rq('user_id'));
		$details = $this->rq('details');
		$wav = $this->rq('wav');
		if($user_id){
			$file = ['url' => '', 'dir' => ''];
			$this->server_limit();
			if($wav) $file = $this->base64_m4a($wav);
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
			app_notify_admins($subject, $message, $att);

			$this->send_response();
		} else { $this->bad_request('Invalid Input'); }
	}

	function set_search_history(){
		$t = $this->prx.'user_search_history';
		$user_id = intval($this->rq('user_id'));
		$keyword = $this->rq('keyword');
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
			$this->send_response();
		} else { $this->bad_request('Invalid Input'); }
	}

	function set_browsing_history(){
		$t = $this->prx.'user_browsing_history';
		$user_id = intval($this->rq('user_id'));
		$question_id = intval($this->rq('question_id'));
		$title = $this->rq('title');
		if($user_id && $question_id && $title){
			DB()->insert($t, [
				'user_id' => $user_id,
				'question_id' => $question_id,
				'title' => $title,
				'dat' => date('Y-m-d'),
				'date_created' => time(),
			]);
			$this->send_response();
		} else { $this->bad_request('Invalid Input'); }
	}

	function comment_vote(){
		$t = $this->prx.'user_comment_votes';
		$tq = $this->prx.'question_comments';
		$user_id = intval($this->rq('user_id'));
		$comment_id = intval($this->rq('comment_id'));
		$direction = $this->rq('direction');
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
				$this->send_response(['results' => $direction]);
			} else { $this->bad_request('Already Voted'); }
		} else { $this->bad_request('Invalid Input'); }
	}

	function vote(){
		$t = $this->prx.'user_votes';
		$tq = $this->prx.'questions';
		$user_id = intval($this->rq('user_id'));
		$question_id = intval($this->rq('question_id'));
		$direction = $this->rq('direction');
		if($user_id && $question_id && $direction){
			$direction = $direction == 'down' ? 0 : 1;
			$user_check = DB()->get_var("SELECT status FROM {$this->prx}users WHERE ID={$user_id}");
			if(!$user_check) $this->bad_request('User not found');
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
				$this->send_response(['results' => $direction]);
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
				$this->send_response(['results' => $direction]);
			} else { $this->bad_request('Already Voted'); }
		} else { $this->bad_request('Invalid Input'); }
	}

	function like_delete(){
		$t = $this->prx.'user_likes';
		$tq = $this->prx.'questions';
		$ts = $this->prx.'subjects';
		$user_id = intval($this->rq('user_id'));
		$id = intval($this->rq('id'));
		$type = intval($this->rq('type')) == 1 ? 1 : 0;

		if(!$user_id) $this->bad_request('No User supplied');
		$user_check = DB()->get_var("SELECT status FROM {$this->prx}users WHERE ID={$user_id}");
		if(!$user_check) $this->bad_request('User Not Allowed');
		if(!$id) $this->bad_request('Invalid Input');

		if($type){ // subject
			DB()->query("UPDATE {$ts} SET likes = likes - 1 WHERE ID={$id}");
			$deleted = 'subject';
			DB()->delete($t, ['user_id' => $user_id, 'subject_id' => $id]);
		} else { //question
			DB()->query("UPDATE {$tq} SET likes = likes - 1 WHERE ID={$id}");
			$deleted = 'Question';
			DB()->delete($t, ['user_id' => $user_id, 'question_id' => $id]);
		}
		$this->send_response(['deleted' => $deleted]);
	}

	function like(){
		$t = $this->prx.'user_likes';
		$tq = $this->prx.'questions';
		$ts = $this->prx.'subjects';
		$user_id = intval($this->rq('user_id'));
		$question_id = intval($this->rq('question_id'));
		$subject_id = intval($this->rq('subject_id'));
		$type = intval($this->rq('type')) == 1 ? 1 : 0;

		if(!$user_id) $this->bad_request('No User supplied');
		$user_check = DB()->get_var("SELECT status FROM {$this->prx}users WHERE ID={$user_id}");
		if(!$user_check) $this->bad_request('User Not Allowed');

		if($type){ // subject
			if(!$subject_id) $this->bad_request('Invalid Input');
			$like_check = DB()->get_var("SELECT ID FROM {$t} WHERE user_id={$user_id} AND subject_id={$subject_id}");
			if($like_check){
				$like = 0;
				DB()->query("UPDATE {$ts} SET likes = likes - 1 WHERE ID={$subject_id}");
				DB()->delete($t, ['user_id' => $user_id, 'subject_id' => $subject_id]);
			} else {
				$like = 1;
				DB()->query("UPDATE {$ts} SET likes = likes + 1 WHERE ID={$subject_id}");
				DB()->insert($t, [
					'user_id' => $user_id,
					'subject_id' => $subject_id,
					'type' => $type,
					'date_created' => time(),
				]);
			}
			$this->send_response(['results' => $like]);
		} else { //question
			if(!$question_id) $this->bad_request('Invalid Input');
			$like_check = DB()->get_var("SELECT ID FROM {$t} WHERE user_id={$user_id} AND question_id={$question_id}");
			if($like_check){
				$like = 0;
				DB()->query("UPDATE {$tq} SET likes = likes - 1 WHERE ID={$question_id}");
				DB()->delete($t, ['user_id' => $user_id, 'question_id' => $question_id]);
			} else {
				$like = 1;
				DB()->query("UPDATE {$tq} SET likes = likes + 1 WHERE ID={$question_id}");
				DB()->insert($t, [
					'user_id' => $user_id,
					'question_id' => $question_id,
					'type' => $type,
					'date_created' => time(),
				]);
			}
			$this->send_response(['results' => $like]);
		}
	}

	function get_likes(){
		$t = $this->prx.'user_likes';
		$tq = $this->prx.'questions';
		$ts = $this->prx.'subjects';

		$user_id = intval($this->rq('user_id'));
		if(!$user_id) $this->bad_request();
		$type = intval($this->rq('type')) == 1 ? 1 : 0;

		$per_page = 20;
		$page = intval($this->rq('page'));
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
		$this->send_response( [
			'page' => $page,
			'total_pages' => $total_pages,
			'total' => $total,
			'per_page' => $per_page,
			'results' => $results
		] );
	}

	function comment(){
		$t = $this->prx.'question_comments';
		$tq = $this->prx.'questions';
		$tu = $this->prx.'users';
		$user_id = intval($this->rq('user_id'));
		$question_id = intval($this->rq('question_id'));
		$details = wp_unslash($this->rq('details'));

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
				app_notify_admins($subject, $message);

				$this->send_response();
			} else { $this->bad_request('User Not Allowed'); }
		} else { $this->bad_request('Invalid Input'); }
	}

	function get_definition(){
		$t = $this->prx.'definitions';
		$id = intval($this->rq('id'));
		if($id){
			$result = DB()->get_row("SELECT * FROM $t WHERE ID={$id}");
			if($result){
				$result->details = $this->remove_line_breaks($result->details);
				$result->tags = app_tags_translate($result->tags);
				$result->examples = app_repeat_translate($this->remove_line_breaks($result->examples));
        		$result->notes = app_repeat_translate($this->remove_line_breaks($result->notes));
				$this->send_response( ['results' => $result ] );
			} else { $this->bad_request(); }
		} else { $this->bad_request(); }
	}

	function get_reference(){
		$t = $this->prx.'references';
		$id = intval($this->rq('id'));
		if($id){
			$result = DB()->get_row("SELECT * FROM $t WHERE ID={$id}");
			if($result){
				$result->parent = $result->parent ? reference_by_id($result->parent) : '';
				$this->send_response( ['results' => $result ] );
			} else { $this->bad_request(); }
		} else { $this->bad_request(); }
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
			$this->send_response( ['results' => $result ] );
		} else { $this->bad_request(); }
	}

	function get_subjects(){
		$t = $this->prx.'subjects';
		$ID = intval($this->rq('ID'));
		$parent = intval($this->rq('parent'));
		$args = ['hide_empty' => true];
		if($ID) $args['ID'] = $ID;
		if($parent) $args['parent'] = $parent;
		$subjects = get_subjects_new($args);
		$this->send_response( ['results' => $subjects ] );
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

	function get_questions(){
		$t = $this->prx.'questions';
		$ts = $this->prx.'subjects';
		$tl = $this->prx.'user_likes';
		$t_tags = $this->prx.'tags';
		$t_keywords = $this->prx.'keywords';
		$per_page = 30;
		$app_version = $this->rq('app_version') ? $this->rq('app_version') : '---';

		$user_id = intval($this->rq('user_id'));
		$cat = intval($this->rq('cat'));
		$tag = intval($this->rq('tag'));
		$s = $this->rq('s');
		$orderby = $this->rq('order_by');
		$orderby = $orderby ? 'T.'.$orderby : 'T.date_created';
		$order = $this->rq('order');
		$order = $order ? $order : 'DESC';

		$order = "$orderby $order";

		$page = intval($this->rq('page'));
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

		$definitions = [];
		// $definitions = DB()->get_results("SELECT ID, title from {$this->prx}definitions");

        $results = array_map(function($item) use ($definitions){
        	$item->excerpt = $this->excerpt($item->details);
        	$details = $this->remove_line_breaks($item->details);
	        $details = $this->apply_definitions($details, $definitions);
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
		
		$this->send_response( [
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
		] );
	}

	function get_question_cat($ID){
	    if(strpos(',', $ID) !== false){
			$ID = explode(',', $ID);
			$ID = $ID[0];
		}
		$ID = intval($ID);
		if(!$ID) return;
		$ts = $this->prx.'subjects';
		$subj = DB()->get_row("SELECT title,image,color from {$ts} WHERE ID={$ID}");
		if($subj){
			$subj->image = get_subject_image($subj->image);
			$subj->color = get_subject_color($subj->color);
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
        $item->cat_ancestors = get_subject_ancestors_array($item->categories);
	    $item->images = get_question_images($item->images);
	    $item->videos = get_question_videos($item->videos);
	    $item->cat = $this->get_question_cat($item->categories);
        return $item;
	}

	function get_question_by_id(){
		$ID = intval($this->rq('question_id'));
		if(!$ID) $this->bad_request();
		$t = $this->prx.'questions';
		$result = DB()->get_row("SELECT * FROM $t WHERE ID={$ID}");
		if($result){
			$result = $this->map_question($result);
			$this->send_response(['results' => $result]);
		} else { $this->bad_request(); }
	}

	function get_question_assets(){
		$t = $this->prx.'questions';
		$ts = $this->prx.'subjects';
		$tl = $this->prx.'user_likes';
		$tc = $this->prx.'question_comments';
		$tu = $this->prx.'users';
		$tv = $this->prx.'user_votes';
		$user_id = intval($this->rq('user_id'));
		$question_id = intval($this->rq('question_id'));
		if(!question_id) {
			$this->bad_request();
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
		$this->send_response(['like' => $like, 'comments' => $comments, 'voted' => $voted, 'vote_direction' => $vote_direction]);
	}

	function change_password(){
		$t = $this->prx.'users';
		$user_id = intval($this->rq('user_id'));
		$password = $this->rq('password');
		$old_password = $this->rq('old_password');
		if(!user_id || strlen($password) < 6) $this->bad_request('INVALID INPUT');

		// $pwd = DB()->get_var("SELECT password FROM $t WHERE ID='{$user_id}'");
		// if($pwd != $old_password) $this->bad_request('WRONG PASSWORD');

		DB()->update($t, AH()->stripslashes([
			'password' => $password,
			'date_edited' => time(),
		]), ['ID' => $user_id]);
		$this->send_response();
	}

	function change_email(){
		$t = $this->prx.'users';
		$user_id = intval($this->rq('user_id'));
		$email = $this->rq('email');
		$old_password = $this->rq('old_password');
		if(!user_id || !is_email($email) || !$old_password) $this->bad_request('BAD EMAIL OR ID');

		$pwd = DB()->get_var("SELECT password FROM $t WHERE ID='{$user_id}'");
		if($pwd != $old_password) $this->bad_request('WRONG PASSWORD');

		$check = DB()->get_var("SELECT ID FROM $t WHERE email='{$email}' AND ID<>{$user_id}");
		if($check) $this->bad_request('EMAIL FOUND IN DATABASE');

		DB()->update($t, AH()->stripslashes([
			'email' => $email,
			'date_edited' => time(),
		]), ['ID' => $user_id]);
		$this->send_response();
	}

	function edit_profile(){
		$t = $this->prx.'users';
		if($this->rq('user_id') && $this->rq('name')){
			$dob = $this->rq('dob');
			if($dob) $dob = date('Y-m-d', strtotime($dob));
			DB()->update($t, AH()->stripslashes([
				'name' => $this->rq('name'),
				'phone' => $this->rq('phone'),
				'dob' => $dob,
				'gender' => $this->rq('gender'),
				'date_edited' => time(),
			]), ['ID' => $this->rq('user_id')]);
			$this->send_response();
		} else { $this->bad_request(); }
	}

	function signup(){
		$t = $this->prx.'users';
		$email = strtolower( $this->rq('email') );
		$password = $this->rq('password');
		$name = $this->rq('name');
		$image = $this->rq('image');
		$phone = $this->rq('phone');
		$gender = $this->rq('gender');
		$dob = $this->rq('dob');

		$userObj = [
			'email' => $email,
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

	function get_user(){
		$t = $this->prx.'users';
		$user_id = intval($this->rq('user_id'));
		if(!$user_id) $this->bad_request('NO ID SUPPLIED');
		$u = DB()->get_row("SELECT * FROM $t WHERE ID='{$user_id}'");
		if(!$u) $this->bad_request('NO USER FOUND');
		$u = $this->map_user($u);
		$this->send_response(['results' => $u]);
	}

	function login(){
		$t = $this->prx.'users';
		$email = strtolower( $this->rq('email') );
		$password = $this->rq('password');
		$name = $this->rq('name');
		$image = $this->rq('image');
		$phone = $this->rq('phone');
		$dob = $this->rq('dob');
		$gender = $this->rq('gender');

		$fb_id = $this->rq('fb_id');
		$google_id = $this->rq('google_id');
		$provider = $this->rq('provider');

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
					$this->send_response(['results'=>$u]);
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
					$this->send_response(['results'=>$u]);
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
					$this->send_response(['results' => $u]);
				} else { $this->bad_request('Account needs activation'); }
			} else { $this->bad_request('Invalid email / password credentials'); }
		} else { $this->bad_request('Not valid login method'); }
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

				$this->send_response(['results'=>$args]);
			} $this->bad_request('Email already registered');
		} else { $this->bad_request('Malformed email or bad name'); }

	}

	function map_user($item){
		if($item->dob == '0000-00-00') $item->dob = '';
		$item->image = $item->image ? __CORS($item->image) : app_avatar__($item->name);
        return $item;
	}

	function forgot(){
		$t = $this->prx.'users';
		$email = strtolower( $this->rq('email') );
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
				$this->send_response(['results'=> 'Password sent']);
			} $this->bad_request('Email is not registered');
		} else { $this->bad_request('Malformed email'); }
	}

	function change_img(){
		$t = $this->prx.'users';
		$user_id = intval($this->rq('user_id'));
		$data = $this->rq('data');
		if(!$user_id) $this->bad_request('NO ID SUPPLIED');
		if(!$data) $this->bad_request('NO DATA');
		$check = DB()->get_var("SELECT ID FROM $t WHERE ID='{$user_id}'");
		if(!$check || intval($check) != $user_id) $this->bad_request('NO ID FOUND');
		$image = $this->base64_image($data);
		if(!$image) $this->bad_request('COULD NOT SAVE IMAGE');
		DB()->update($t, ['image' => $image], ['ID' => $user_id]);
		$this->send_response(['results'=> 'Image Changed']);
	}

	function record_device(){
		$t = $this->prx.'devices';
		$user_id = intval($this->rq('user_id'));
		$player_id = $this->rq('player_id');
		if($player_id){
			$check = DB()->get_var("SELECT ID FROM $t WHERE player_id='{$player_id}'");
			if($check){
				$args = ['date_edited' => time()];
				if($user_id) $args['user_id'] = $user_id;
				if($this->rq('push_id')) $args['push_id'] = $this->rq('push_id');
				DB()->update($t,  AH()->stripslashes($args), ['ID' => $check]);
			} else {
				DB()->insert($t, AH()->stripslashes([
					'user_id' => $user_id,
					'player_id' => $player_id,
					'push_id' => $this->rq('push_id'),
					'uuid' => $this->rq('uuid'),
					'platform' => $this->rq('platform'),
					'model' => $this->rq('model'),
					'manufacturer' => $this->rq('manufacturer'),
					'version' => $this->rq('version'),
					'serial' => $this->rq('serial'),
					'date_created' => time(),
				]));
			}
			$this->send_json(['valid' => 'YES']);
		} else { $this->bad_request(); }
	}
}

new APP_API;
