<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class App_TagMapper {

	public function __construct() {
		$this->singular = 'Tag Mapper';
		$this->plural = 'Tag Mapper';
		$this->slug = 'app-tag-mapper';
		$this->table  = DB()->prefix.'app_questions';
		$this->action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : '';
		$this->link = 'admin.php?page='.$this->slug;

		add_action('app_admin_menu', array($this, 'menu'), 14);
	}

	public function menu(){
		$p = add_submenu_page('app', $this->plural, $this->plural, 'read', $this->slug, array($this, 'page'));
	}

	public function page(){
		$error = $success = '';
		if(isset($_POST['submit'])){
			$subject = isset($_POST['subject']) ? $_POST['subject'] : '';
			
			$tagslist = isset($_POST['tagslist']) ? implode(',', $_POST['tagslist']) : '';
			$force = isset($_POST['force']) ? true : false;
			if($subject){
				$subject = explode(',', $subject);
				$subject = $subject[0];
				// echo '<h1>Subject: '.$subject.'</h1>';
				$subject_name = subject_by_id($subject);
				$qs = DB()->get_results("SELECT ID,tags FROM {$this->table} WHERE FIND_IN_SET({$subject}, `categories`)>0");
				if($qs){
					$i = 0;
					foreach($qs as $q){
						$i++;
						if($force){
							DB()->update($this->table, ['tags' => $tagslist], ['ID' => $q->ID]);
						} else {
							$cur = $q->tags;
							$new = $tagslist;
							$cur_arr = $cur ? explode(',', $cur) : [];
							$new_arr = $new ? explode(',', $new) : [];
							$upd_arr = array_merge($cur_arr, $new_arr);
							$upd = implode(',', $upd_arr);
							DB()->update($this->table, ['tags' => $upd], ['ID' => $q->ID]);
						}
					}
					$ts = count($_POST['tagslist']);
					$success = '<div class="updated success">Mapped '.$ts.' Tags to '.$i.' questions under "'.$subject_name.'"</div>';
				} else {
					$error = '<div class="updated error">No questions found under "'.$subject_name.'", nothing was changed</div>';
				}
			} else {
				$error = '<div class="updated error">No Subject selected</div>';
			}
		}
		echo '
			<div class="wrap" style="min-height: 600px;">
				<h2>'.$this->plural.'</h2>
				'.$success.$error.'
				<p>Use this form to apply TAGS to all questions of desired subject</p>
				'.AF('form_open').AF('table_open').'

				'.AF('tree', [
					'name' => 'subject',
					'value' => '',
					'type' => 'subjects',
					'label' => 'Subject',
					'section' => 'subjects'
				]).'

				'.AF('select', [
					'label' => 'Tags',
					'options' => app_tags_select(),
					'multiple' => true,
					'can_sort' => 'yes',
					'name' => 'tagslist',
					'value' => '',
				]).'

				'.AF('checkbox_one', [
					'label' => 'Force Full Mapping',
					'name' => 'force',
					'value' => 'yes',
					'desc' => 'Forcing will delete previous questions tags and will apply only the newly selected tags to each question.'
				]).'

				'.AF('table_close').'
				'.AF('submit', ['name'=> 'submit', 'value' => 'Map Tags']).'
				'.AF('form_close').'
			</div>
		';
	}

}

new App_TagMapper;
