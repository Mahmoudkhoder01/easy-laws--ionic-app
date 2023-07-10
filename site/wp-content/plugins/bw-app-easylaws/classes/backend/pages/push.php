<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class App_Admin_Push {

	public function __construct() {
		$this->singular = 'Push Notification';
		$this->plural = 'Push Notifications';
		$this->slug = 'app-push-notifications';
		$this->table_name  = DB()->prefix.'app_devices';
		$this->action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : '';
		$this->link = 'admin.php?page='.$this->slug;

		add_action('app_admin_menu_bottom', array($this, 'menu'), 15);
	}

	public function menu(){
		$p = add_menu_page($this->plural, $this->plural, 'manage_options', $this->slug, array($this, 'display'), '', '2.0203');
	}

	public function columns(){
		return [
			'users' => [
				'type' => 'text',
				'label' => 'User IDs',
				'desc' => 'Send only to these user IDs (separate by comma)',
			],
			'subjects' => [
				'type' => 'tree',
				'label' => 'Subjects',
				'section' => 'subjects',
				'desc' => 'Send only to users who favorite these subjects',
			],
			'title' => [
				'type' => 'text',
				'label' => 'Push Title',
				'desc' => 'Optional',
			],
			'text' => [
				'type' => 'textarea',
				'label' => 'Push Text',
				// 'desc' => '140 characters max',
			],
			'click_section' => [
				'type' => 'select',
				'raw_select' => true,
				'label' => 'Click Action',
				'options' => [
					'__NONE__' => '-- NONE --',
					'question' => 'Navigate to Question',
					'subject' => 'Navigate to Subject',
					'subject_list' => 'Show Subject List',
					'notification' => 'Show notifications page',
					'app_store' => 'Open App Store / Play Store',
					'rate_app' => 'Open Rate App (in app/play stores)',
					'link' => 'Open External Link',
				],
				// 'desc' => 'Make sure to enter the ID / Link below in case of Question / Subject / Subject List / External Link',
			],
			'click_ID' => [
				'type' => 'text',
				'label' => 'Question / Subject ID',
				'desc' => '',
			],
		];
	}

	function add_subject($id = null){
		$id = intval($id); if(!$id) return;
		$t_devices = DB()->prefix . 'app_devices';
		$t_subjects  = DB()->prefix.'app_subjects';
		$t_user_likes  = DB()->prefix.'app_user_likes';
		$q = DB()->get_row("SELECT ID,title,parent from {$t_subjects} WHERE ID={$id}");
		if(!$q) return;
		// if(!$q->parent) return;
		$text = "Added new subject: {$q->title}";
		$ids = $player_ids = [];
		$ids = DB()->get_col("SELECT user_id FROM {$t_user_likes} WHERE subject_id={$q->parent} OR subject_id={$q->ID}");
		foreach($ids as $i){
			$pids = DB()->get_col("SELECT player_id FROM $t_devices WHERE user_id=$i");
			if($pids){
				foreach($pids as $pid){
					$player_ids[] = $pid;
				}
			}
		}
		return ['ids' => $ids, 'player_ids' => $player_ids];
	}

	function get_user($id = null){
		$id = intval($id); if(!$id) return;
		$t = DB()->prefix . 'app_devices';
		return DB()->get_var("SELECT player_id FROM $t WHERE user_id={$id}");
	}

	public function display(){
		echo $this->header('Add New');

		if(AH()->post('submit')){
			$err = array();
			$push = '';

			$title = AH()->post('title');
			$text = AH()->post('text');
			$click_section = AH()->post('click_section');
			$click_ID = AH()->post('click_ID');
			$users = AH()->post('users');
			$subjects = AH()->post('subjects');
			$_key = uniqid().time();

			if(!$text || strlen($text) < 2){
				$err[] = 'Push Text is required';
			}

			if($click_section && ($click_section == 'question' || $click_section == 'subject' || $click_section == 'subject_list' || $click_section == 'link') && !$click_ID){
				if($click_section == 'question'){
					$err[] = 'Qusetion ID is required';
				} elseif($click_section == 'subject' || $click_section == 'subject_list'){
					$err[] = 'Subject ID is required';
				} elseif($click_section == 'link'){
					$err[] = 'Link is required, include (http(s)://)';
				}
			}

			if(!empty($err)){
				echo '<div class="error">'.implode(', ', $err).'</div>';
			} else {
				$args = [];
				$msg = '';

				if($title){
					$args['title'] = $title;
				}

				if($click_section && $click_section !== '__NONE__'){
					$args['data'] = [
						'section' => $click_section,
						'ID' => $click_ID ? intval($click_ID) : '',
						'_key' => $_key,
					];
				}

				$__USERS = '';

				if($users){

					$us = explode(',', $users);
					foreach($us as $u){
						$args['filters']['user_id'][] = trim($u);
					}
					$push = app_push()->send(AH()->post('text'), $args);
					$__USERS = $us;

				} elseif ($subjects){

					$subjects = explode(',', $subjects);
					$player_ids = $ids= [];
					foreach($subjects as $subject){
						$_n = $this->add_subject($subject);
						$player_ids = array_merge($player_ids, $_n['player_ids']);
						$ids = array_merge($ids, $_n['ids']);
					}
                    
					$player_ids = array_unique($player_ids);
					// AH()->print_r($player_ids);
                    if($player_ids){
					    $args['include_player_ids'] = $player_ids;
					    $push = app_push()->send(AH()->post('text'), $args);
					    $count = count($player_ids);
					    $msg = "PUSH Sent to $count Devices";
                    } else {
                    	$msg = "NO Devices";
					}
					
					$ids = array_unique($ids);
					// AH()->print_r($ids);
					$__USERS = $ids;
					
				} else {

				    $push = app_push()->send(AH()->post('text'), $args);
					$msg="Push Sent Successfully";
					$__USERS = '__ALL__';
					
				}

				if($__USERS){
					insert_notification($__USERS, [
						'title' => $title,
						'details' => $text,
						'action' => $click_section,
						'action_id' => $click_ID,
						'_key' => $_key,
					]);
				}

				if(!empty($push)){
					$perrors = isset($push['errors']) ? implode(', ', $push['errors']) : '';
					echo "
					<div class='updated success' style='padding: 10px;'>
					OneSignal Respone<br>
					ID: {$push['id']}<br>
					recipients: {$push['recipients']}<br>
					errors: {$perrors}<br>
					</div>";
				}
				// echo "<div class='updated success'>$msg</div>";
				foreach($_POST as $k => $v){
					unset($_POST[$k]);
				}
			}
		}

		echo AF('form_open');
		// echo AF('table_open');

		foreach($this->columns() as $k => $v){
			echo AF($v['type'], array_merge([
				'name' => $k,
				'value' => !empty($_POST[$k]) ? $_POST[$k] : '',
			], $v));
		}

		// echo AF('table_close');
		echo AF('submit', ['name'=> 'submit', 'value' => 'Send Push']);
		echo AF('form_close');

		echo $this->scripts();
		echo $this->footer();
	}

	function scripts(){
		return "
			<script>jQuery(document).ready(function($){
				$('#form-item-click-action select').on('change', function(){
					var el = $(this),
						v = el.val(),
						text = '';
					if (v == 'question' || v == 'subject' || v == 'subject_list'  || v == 'link'){
						if(v == 'question'){
							text = 'Question ID';
						} else if(v == 'subject' || v == 'subject_list'){
							text = 'Subject ID';
						} else if(v == 'link'){
							text = 'Link';
						}
						$('#form-item-question-subject-id th').text(text);
						$('#form-item-question-subject-id').show();
					} else {
						$('#form-item-question-subject-id').hide();
					}
				}).trigger('change');
			});</script>
		";
	}

	public function header($title = ''){
		return '
			<div class="wrap">
				<h2>'.$title.' '.$this->plural.'</h2>
		';
	}

	public function footer(){
		return '</div>';
	}

}

new App_Admin_Push;
