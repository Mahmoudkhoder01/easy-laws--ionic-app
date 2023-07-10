<?php
class App_Push_Hooks
{
	public function __construct(){
		$prx = DB()->prefix.'app_';
		$this->prx = $prx;
		$this->t_questions = $prx.'questions';
		$this->t_subjects = $prx.'subjects';
		$this->t_user_likes = $prx.'user_likes';
		$this->t_devices = $prx.'devices';

		// add_action('app_add_question',  [$this, 'add_question'],  10, 2);
		// add_action('app_edit_question', [$this, 'edit_question'], 10, 2);
		add_action('app_add_subject',   [$this, 'add_subject'],   10, 2);
		add_action('app_edit_subject',  [$this, 'edit_subject'],  10, 2);

		add_action('app_cron_daily', [$this, 'did_you_know']);
		add_action('app_cron_worker', [$this, 'cron_worker']);
	}

	function cron_worker(){
		if(get_option('cats_need_recount') == 'yes'){
			echo '<h2>Recounting Subjects...</h2>';
			app_set_count_cat_posts();
			delete_option('cats_need_recount');
		} else {
			echo '<h2>Subjects are all set.</h2>';
		}
	}

	function add_question($id = null){

		$id = intval($id); if(!$id) return;
		$q = DB()->get_row("SELECT ID,title,categories,status from {$this->t_questions} WHERE ID={$id}");
		if(!$q) return;
		if(!$q->status) return;
		$text = "Added new question: {$q->title}";
		$users = [];
		$subjects = $q->categories;
		if(!$subjects) return;
		$subjects = explode(',', $subjects);
		foreach($subjects as $subject){
			if($subject && is_numeric($subject)){
				$ids = DB()->get_results("SELECT user_id, (SELECT player_id FROM {$t_devices} WHERE user_id = {$t_user_likes}.user_id) AS player_id FROM {$t_user_likes} WHERE subject_id={$subject}");
				foreach($ids as $i){
					if($i->player_id) $users[] = $i->player_id;
				}
			}
		}

		$push = app_push()->send($text, [
			'data' => [
				'section' => 'question',
				'ID' => $q->ID,
			],
			'include_player_ids' => $users,
		]);
	}

	function edit_question($id = null){

		$id = intval($id); if(!$id) return;
		$q = DB()->get_row("SELECT ID,title,categories,status from {$this->t_questions} WHERE ID={$id}");
		if(!$q) return;
		if(!$q->status) return;
		$text = "Edited question: {$q->title}";
		$users = [];
		$subjects = $q->categories;
		if(!$subjects) return;
		$subjects = explode(',', $subjects);
		foreach($subjects as $subject){
			if($subject && is_numeric($subject)){
				$ids = DB()->get_results("SELECT user_id, (SELECT player_id FROM {$t_devices} WHERE user_id = {$t_user_likes}.user_id) AS player_id FROM {$t_user_likes} WHERE subject_id={$subject} OR question_id={$q->ID}");
				foreach($ids as $i){
					if($i->player_id) $users[] = $i->player_id;
				}
			}
		}

		$push = app_push()->send($text, [
			'data' => [
				'section' => 'question',
				'ID' => $q->ID,
			],
			'include_player_ids' => $users,
		]);
	}

	function add_subject($id = null){
		$id = intval($id); if(!$id) return;
		$q = DB()->get_row("SELECT ID,title,parent from {$t_subjects} WHERE ID={$id}");
		if(!$q) return;
		if(!$q->parent) return;
		$text = "Added new subject: {$q->title}";
		$users = [];
		$ids = DB()->get_results("SELECT user_id, (SELECT player_id FROM {$t_devices} WHERE user_id = {$t_user_likes}.user_id) AS player_id FROM {$t_user_likes} WHERE subject_id={$q->parent}");
		foreach($ids as $i){
			if($i->player_id) $users[] = $i->player_id;
		}

		$push = app_push()->send($text, [
			'data' => [
				'section' => 'subject',
				'ID' => $q->ID,
			],
			'include_player_ids' => $users,
		]);
	}

	function edit_subject($id = null){
		$id = intval($id); if(!$id) return;
		$q = DB()->get_row("SELECT ID,title,parent from {$t_subjects} WHERE ID={$id}");
		if(!$q) return;
		// if(!$q->parent) return;
		$text = "Updated subject: {$q->title}";
		$users = [];
		$query = "SELECT user_id, (SELECT player_id FROM {$t_devices} WHERE user_id = {$t_user_likes}.user_id) AS player_id FROM {$t_user_likes} WHERE subject_id={$q->ID}";
		if($q->parent){
			$query .= " OR subject_id={$q->parent}";
		}
		$ids = DB()->get_results($query);
		foreach($ids as $i){
			if($i->player_id) $users[] = $i->player_id;
		}

		$push = app_push()->send($text, [
			'data' => [
				'section' => 'subject',
				'ID' => $q->ID,
			],
			'include_player_ids' => $users,
		]);
	}

	function did_you_know(){
		$bad = get_option('app_did_you_know_filter');
		$bad = $bad ? $bad : [];

		$opt_key = 'app_did_you_know_sent';
		$sent = get_option($opt_key);
		$sent = $sent ? $sent : [];
		$sent_ids = implode(',', $sent);

		$query = "SELECT ID,did_you_know from {$this->t_questions} WHERE status=1 AND CHAR_LENGTH(did_you_know) > 10";
		if($sent_ids) $query .= " AND ID NOT IN ({$sent_ids})";
		if(!empty($bad)){
			foreach($bad as $b){
                $b = intval(trim($b));
                $query .= " AND (FIND_IN_SET({$b}, `categories`)=0)";
            }
		} 
		$query .= " ORDER BY rand() LIMIT 1";

		$q = DB()->get_row($query);
		if(!$q){ // loop
			delete_option($opt_key);
			$this->did_you_know();
			return;
		}
		$sent[] = $q->ID;
		update_option($opt_key, $sent);
		$text = $q->did_you_know;

		$push = app_push()->send($text, [
			'title' => 'هل كنت تعلم؟',
			'data' => [
				'section' => 'question',
				'ID' => $q->ID,
			]
		]);
		echo "<h4>SENDING DID YOU KNOW: $text</h4>";
	}

}
new App_Push_Hooks;
