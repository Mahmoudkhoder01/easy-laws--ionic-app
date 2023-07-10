<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class App_Questions {

	public function __construct() {
		$this->singular = 'Question';
		$this->plural = 'Questions';
		$this->slug = 'app-questions';
		$this->table_name  = DB()->prefix.'app_questions';
		$this->table_logs  = DB()->prefix.'app_question_logs';
		$this->action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : '';
		$this->link = 'admin.php?page='.$this->slug;
		$this->link_add = add_query_arg(array('action' => 'addnew'), $this->link);
		$this->link_edit = add_query_arg(array('action' => 'edit'), $this->link);

		add_action('app_admin_menu', array($this, 'menu'), 10);
	}

	public function menu(){
		$p = add_submenu_page('app', 'Q & A', 'Q & A', 'read', $this->slug, array($this, 'page'));
		add_action('load-'.$p, array($this, 'per_page'));
	}

	public function columns(){
		$o = array(
			
			'categories' => [
				'type' => 'tree',
				'label' => 'Subjects',
				'section' => 'subjects',
			],

			'tablist' => tabs_langs(),
				'AR' => ['type' => 'tab', 'active' => true],
					'status' => [
						'type' => 'select',
						'label' => 'Status',
						'options' => app_status_select(),
					],
					'title' => [
						'type' => 'text',
						'label' => 'Question'
					],
					'details' => [
						'type' => 'editor',
						'lang' => 'ar',
						'label' => 'Explanation'
					],
					'notes' => [
						'type' => 'textarea_repeater',
						'lang' => 'ar',
						'label' => 'Notes'
					],
					'examples' => [
						'type' => 'textarea_repeater',
						'lang' => 'ar',
						'label' => 'Examples'
					],
					'did_you_know' => [
						'type' => 'editor',
						'lang' => 'ar',
						'label' => 'Did you know?'
					],
				'/AR' => ['type' => '/tab'],

				'EN' => ['type' => 'tab'],
					'status_en' => [
						'type' => 'select',
						'label' => 'Status',
						'options' => app_status_select(),
					],
					'title_en' => [
						'type' => 'text',
						'label' => 'Question'
					],
					'details_en' => [
						'type' => 'editor',
						'lang' => 'en',
						'label' => 'Explanation'
					],
					'notes_en' => [
						'type' => 'textarea_repeater',
						'lang' => 'en',
						'label' => 'Notes'
					],
					'examples_en' => [
						'type' => 'textarea_repeater',
						'lang' => 'en',
						'label' => 'Examples'
					],
					'did_you_know_en' => [
						'type' => 'editor',
						'lang' => 'en',
						'label' => 'Did you know?'
					],
				'/EN' => ['type' => '/tab'],

				'FR' => ['type' => 'tab'],
					'status_fr' => [
						'type' => 'select',
						'label' => 'Status',
						'options' => app_status_select(),
					],
					'title_fr' => [
						'type' => 'text',
						'label' => 'Question'
					],
					'details_fr' => [
						'type' => 'editor',
						'lang' => 'fr',
						'label' => 'Explanation'
					],
					'notes_fr' => [
						'type' => 'textarea_repeater',
						'lang' => 'fr',
						'label' => 'Notes'
					],
					'examples_fr' => [
						'type' => 'textarea_repeater',
						'lang' => 'fr',
						'label' => 'Examples'
					],
					'did_you_know_fr' => [
						'type' => 'editor',
						'lang' => 'fr',
						'label' => 'Did you know?'
					],
				'/FR' => ['type' => '/tab'],
			'/tablist' => ['type' => '/tablist'],

			'videos' => [
				'type' => 'media',
				'label' => 'Videos',
				'media_type' => 'video',
			],
			'images' => [
				'type' => 'media',
				'label' => 'Pictures',
				'media_type' => 'image',
			],
			'links' => [
				'type' => 'text_repeater',
				'label' => 'Links'
			],
			'references' => [
				'type' => 'tree',
				'label' => 'References',
				'section' => 'references',
			],
			'tags' => [
				'type' => 'select',
				'label' => 'Tags',
				'options' => app_tags_select(),
				'multiple' => true,
				'can_sort' => 'yes',
			],
			'keywords' => [
				'type' => 'select',
				'label' => 'Keywords',
				'options' => app_keywords_select(),
				'multiple' => true,
				'can_add' => 'yes',
				'can_sort' => 'yes',
			],
			// 'author' => [
			// 	'type' => 'select',
			// 	'label' => 'Author',
			// 	'options' => app_get_authors(),
			// 	'default' => get_current_user_id(),
			// ],
			'menu_order' => [
				'type' => 'number',
				'label' => 'Menu Order',
			],
		);

		if(current_user_can('edit_pages')){
			$o['author'] = [
				'type' => 'select',
				'label' => 'Author',
				'options' => app_get_authors(),
				'default' => get_current_user_id(),
			];
		}
		return $o;
	}

	public function set_log($id, $operation){
		$author = wp_get_current_user()->display_name;
		$details = "$operation BY: $author";
		DB()->insert($this->table_logs, [
			'question_id' => intval($id),
			'details' => $details,
			'date_created' => time(),
		]);
	}

	public function display(){
		require_once( dirname(__FILE__) . '/questions-list.php' );
		$list_table = new App_Questions_List_Table;
		$per_page = 150;

		// $screen = get_current_screen();
		// $screen_option = $screen->get_option('per_page', 'option');
		// $per_page = get_user_meta(get_current_user_id(), $screen_option, true);
		// if ( empty ( $per_page) || $per_page < 1 ) {
		// 	$per_page = $screen->get_option( 'per_page', 'default' );
		// }

		$list_table->prepare_items($per_page, $this->link);
		
		$_ref = app_rq('_ref') ? app_rq('_ref') : app_referer();

		switch ( $list_table->current_action() ) {
			case 'forcetrash':
				if ( empty($_REQUEST['id']) ) {
					wp_redirect($this->link);
					exit();
				}
				$id = intval($_REQUEST['id']);
				DB()->query("DELETE FROM {$this->table_name} WHERE `ID`=$id");
				$this->set_log($id, 'Deleted');
				update_option('cats_need_recount', 'yes');
				wp_redirect($_ref);
				exit();
			break;
			case 'untrash':
				if ( empty($_REQUEST['id']) ) {
					wp_redirect($this->link);
					exit();
				}
				$id = intval($_REQUEST['id']);
				DB()->query("UPDATE {$this->table_name} SET trashed=0 WHERE `ID`=$id");
				$this->set_log($id, 'UN-Trashed');
				update_option('cats_need_recount', 'yes');
				wp_redirect($_ref);
				exit();
			break;
			case 'delete':
				if ( empty($_REQUEST['ids']) && empty($_REQUEST['id']) ) {
					wp_redirect($this->link); exit();
				}
				if ( empty($_REQUEST['ids']) ){
					$delids = array( intval( $_REQUEST['id'] ) );
				} else{
					$delids = array_map( 'intval', (array) $_REQUEST['ids'] );
				}
				$update = 'del';
				$delete_count = 0;
				foreach ( $delids as $id ) {
					// DB()->query("DELETE FROM {$this->table_name} WHERE `ID`=$id");
					DB()->query("UPDATE {$this->table_name} SET trashed=1 WHERE `ID`=$id");
					$this->set_log($id, 'Trashed');
					++$delete_count;
				}
				update_option('cats_need_recount', 'yes');
				$redirect = add_query_arg( array('delete_count' => $delete_count, 'update' => $update), $_ref );
				// $redirect = add_query_arg( array('delete_count' => $delete_count, 'update' => $update), $this->link);
				wp_redirect($redirect);
				exit();
			break;

			case 'set_active':
				if ( empty($_REQUEST['ids']) && empty($_REQUEST['id']) ) {
					wp_redirect($this->link); exit();
				}
				if ( empty($_REQUEST['ids']) ){
					$delids = array( intval( $_REQUEST['id'] ) );
				} else{
					$delids = array_map( 'intval', (array) $_REQUEST['ids'] );
				}
				$update = 'set_active';
				$count = 0;
				foreach ( $delids as $id ) {
					DB()->query("UPDATE {$this->table_name} SET `status`=1 WHERE `ID`=$id");
					$this->set_log($id, 'SET Active');
					++$count;
				}
				update_option('cats_need_recount', 'yes');
				$redirect = add_query_arg(['count'=>$count,'update'=>$update], $_ref);
				wp_redirect($redirect);
				exit();
			break;
			case 'set_pending':
				if ( empty($_REQUEST['ids']) && empty($_REQUEST['id']) ) {
					wp_redirect($this->link); exit();
				}
				if ( empty($_REQUEST['ids']) ){
					$delids = array( intval( $_REQUEST['id'] ) );
				} else{
					$delids = array_map( 'intval', (array) $_REQUEST['ids'] );
				}
				$update = 'set_pending';
				$count = 0;
				foreach ( $delids as $id ) {
					DB()->query("UPDATE {$this->table_name} SET `status`=0 WHERE `ID`=$id");
					$this->set_log($id, 'SET Pending');
					++$count;
				}
				update_option('cats_need_recount', 'yes');
				$redirect = add_query_arg(['count'=>$count,'update'=>$update], $_ref);
				wp_redirect($redirect);
				exit();
			break;
			case 'set_corrected':
				if ( empty($_REQUEST['ids']) && empty($_REQUEST['id']) ) {
					wp_redirect($this->link); exit();
				}
				if ( empty($_REQUEST['ids']) ){
					$delids = array( intval( $_REQUEST['id'] ) );
				} else{
					$delids = array_map( 'intval', (array) $_REQUEST['ids'] );
				}
				$update = 'set_corrected';
				$count = 0;
				foreach ( $delids as $id ) {
					DB()->query("UPDATE {$this->table_name} SET `status`=2 WHERE `ID`=$id");
					$this->set_log($id, 'SET Corrected');
					++$count;
				}
				update_option('cats_need_recount', 'yes');
				$redirect = add_query_arg(['count'=>$count,'update'=>$update], $_ref);
				wp_redirect($redirect);
				exit();
			break;
		}

		echo $this->header();
		if(!empty($_GET['message'])) echo '<div class="updated">'.$_GET['message'].'</div>';
		if(!empty($_GET['error'])) echo '<div class="error">'.$_GET['error'].'</div>';

		if(!empty($_GET['app_filter_tag'])) {
			echo '<div class="m-t"><div class="label label-info">Filtering by TAG: '.tag_by_id($_GET['app_filter_tag']).'</div></div>';
		}

		if(!empty($_GET['app_filter']) && is_numeric($_GET['app_filter'])) {
			$subid = intval($_GET['app_filter']);
			echo '<div class="m-t"><a class="btn btn-primary subject-document-creator m-r" data-id="'.$subid.'"><i class="fa fa-file-word-o"></i> <span>Generate Document</span></a></div>';
		}

			echo '
				<form method="get">
					<input type="hidden" name="page" value="'.$this->slug.'"/>
			';
			$list_table->search_box( __( 'Search' ), 'item' );
			$list_table->display();
			echo '</form>';

		echo $this->footer();
	}

	public function addnew(){
		echo $this->header('Add New', false, true);

		if(isset($_POST['submit'])){
			$err = array();
			if(empty($_POST['title']) || strlen($_POST['title']) < 2){
				$err[] = 'Title Required';
			}
			if(empty($_POST['details']) || strlen($_POST['details']) < 2){
				$err[] = 'Explanation Required';
			}
			if(empty($_POST['categories']) || strlen($_POST['categories']) < 2){
				$err[] = 'Subject(s) Required';
			}

			if(!empty($err)){
				echo '<div class="error">'.implode(', ', $err).'</div>';
			} else {
				// AH()->print_r($_POST); exit();
				$insert_vals = array();
				foreach ($this->columns() as $name => $type) {
					if(in_array($name, AF_EX())) continue;
					$insert_vals[$name] = stripslashes_deep($_POST[$name]);
				}

				$insert_vals['examples'] = app_convert_from_repeater($insert_vals['examples']);
				$insert_vals['examples_en'] = app_convert_from_repeater($insert_vals['examples_en']);
				$insert_vals['examples_fr'] = app_convert_from_repeater($insert_vals['examples_fr']);

				$insert_vals['notes'] = app_convert_from_repeater($insert_vals['notes']);
				$insert_vals['notes_en'] = app_convert_from_repeater($insert_vals['notes_en']);
				$insert_vals['notes_fr'] = app_convert_from_repeater($insert_vals['notes_fr']);

				$insert_vals['links'] = app_convert_from_repeater($insert_vals['links']);

				// $insert_vals['categories'] = $insert_vals['categories'] ? implode(',', $insert_vals['categories']) : '';
				// $insert_vals['references'] = $insert_vals['references'] ? implode(',', $insert_vals['references']) : '';
				$insert_vals['tags'] = $insert_vals['tags'] ? implode(',', $insert_vals['tags']) : '';
				$insert_vals['keywords'] = $insert_vals['keywords'] ? implode(',', $insert_vals['keywords']) : '';

				$insert_vals['author'] = $insert_vals['author'] ? $insert_vals['author'] : get_current_user_id();
				$insert_vals['date_created'] = time();
				DB()->insert($this->table_name, AH()->stripslashes($insert_vals));
				$id = DB()->insert_id;
				// app_map_keywords($this->table_name, $id);
				do_action('app_add_question', $id);

				$this->set_log($id, 'Created');
				$_ref = app_rq('_ref');
				if($_ref){
					wp_redirect($_ref.'&message=Added%20Successfully');
				} else {
					wp_redirect($this->link.'&message=Added%20Successfully');
				}
				exit();
			}
		}

		echo AF('form');
		foreach($this->columns() as $k => $v){
			echo AF($v['type'], array_merge([
				'name' => $k,
				'value' => AH()->post($k),
			], $v));
		}
		echo AF('hidden', ['name' => '_ref', 'value' => app_referer()]);
		echo AF('submit', ['name'=> 'submit', 'value' => 'Add New']);
		echo AF('/form');

		$this->validate();
		
		echo '<div style="height: 200px"></div>';

		echo $this->footer();
	}

	public function edit(){
		echo $this->header('Edit', true, true);

		$id = intval($_GET['id']);
		if(!$id) {
			wp_redirect($this->link.'&error=No%20ID%20supplied'); exit();
		}

		$row = DB()->get_row("SELECT * FROM {$this->table_name} WHERE ID={$id}", ARRAY_A);
		if(!$row) {
			wp_redirect($this->link.'&error=Could%20not%20locate%20record'); exit();
		}

		if(isset($_POST['submit'])){
			// AH()->print_r($_POST); exit();
			$err = array();
			if(empty($_POST['title']) || strlen($_POST['title']) < 2){
				$err[] = 'Title Required';
			}
			if(empty($_POST['details']) || strlen($_POST['details']) < 2){
				$err[] = 'Explanation Required';
			}
			if(empty($_POST['categories']) || strlen($_POST['categories']) < 2){
				$err[] = 'Subject(s) Required';
			}

			if(!empty($err)){
				echo '<div class="error">'.implode(', ', $err).'</div>';
			} else {
				$insert_vals = array();
				foreach ($this->columns() as $name => $type) {
					if(in_array($name, AF_EX())) continue;
					$insert_vals[$name] = stripslashes_deep($_POST[$name]);
				}

				$insert_vals['examples'] = app_convert_from_repeater($insert_vals['examples']);
				$insert_vals['examples_en'] = app_convert_from_repeater($insert_vals['examples_en']);
				$insert_vals['examples_fr'] = app_convert_from_repeater($insert_vals['examples_fr']);
				$insert_vals['notes'] = app_convert_from_repeater($insert_vals['notes']);
				$insert_vals['notes_en'] = app_convert_from_repeater($insert_vals['notes_en']);
				$insert_vals['notes_fr'] = app_convert_from_repeater($insert_vals['notes_fr']);

				$insert_vals['links'] = app_convert_from_repeater($insert_vals['links']);

				// $insert_vals['categories'] = $insert_vals['categories'] ? implode(',', $insert_vals['categories']) : '';
				// $insert_vals['references'] = $insert_vals['references'] ? implode(',', $insert_vals['references']) : '';
				$insert_vals['tags'] = $insert_vals['tags'] ? implode(',', $insert_vals['tags']) : '';
				$insert_vals['keywords'] = $insert_vals['keywords'] ? implode(',', $insert_vals['keywords']) : '';

				$insert_vals['author'] = $insert_vals['author'] ? $insert_vals['author'] : get_current_user_id();
				$insert_vals['date_edited'] = time();
				DB()->update($this->table_name, AH()->stripslashes($insert_vals), array('ID' => $id));

				// app_map_keywords($this->table_name, $id);
				do_action('app_edit_question', $id);

				$this->set_log($id, 'Edited');

				$_ref = app_rq('_ref');
				if($_ref){
					wp_redirect($_ref.'&message=Updated%20Successfully');
				} else {
					wp_redirect($this->link.'&message=Updated%20Successfully');
				}
				exit();
			}
		}

		echo AF('form');
		foreach($this->columns() as $k => $v){
			$value = isset($row[$k]) ? $row[$k] : '';
			echo AF($v['type'], array_merge([
				'name' => $k,
				'value' => $value,
			], $v));
		}
		echo AF('hidden', ['name' => '_ref', 'value' => app_referer()]);
		echo AF('submit', ['name'=> 'submit', 'value' => 'Update']);
		echo AF('/form');

		$this->validate();
		
		echo '<div style="height: 200px"></div>';

		echo $this->footer();
	}

	function validate(){
		echo '
		<script>
			jQuery(document).ready(function($){
				$(".__FORM").on("submit", function(e){
					// e.preventDefault();
					var el = $(this),
						categories = el.find("input[name=\'categories\']"),
						title = el.find("input[name=\'title\']"),
						details = el.find("input[name=\'details\']");

					if(!categories.val()){
						alert("Subject is required");
						$("html, body").animate({ scrollTop: 0 }, 300);
						return false;
					}
					if(!title.val()){
						alert("title is required");
						title[0].focus();
						$("html, body").animate({ scrollTop: 0 }, 300);
						return false;
					}
					// if(!details.val()){
					// 	alert("Explanation is required");
					// 	$("html, body").animate({ scrollTop: 0 }, 300);
					// 	return false;
					// }

					// el.submit();
				})
			});
		</script>
		';
	}

	public function header($title = '', $show_add = true, $show_back = false){
		$n = $show_add ? '<a href="'.$this->link_add.'" class="add-new-h2">Add '.$this->singular.'</a>' : '';
		$b = '';
		// $b = $show_back ? '<div style="margin-bottom:20px;"><a href="'.$this->link.'">&laquo; Back</a></div>' : '';
		$att = '';
		if(!empty($_REQUEST['app_filter']) && is_numeric($_REQUEST['app_filter'])){
			$att = 'id="app-table-sortable"';
		}
		return '
			<div class="wrap" '.$att.'>
				<h2>'.$title.' '.$this->plural.' '.$n.'</h2>'.$b.'
		';
	}

	public function footer(){
		return '</div>';
	}

	public function page(){
		// app_map_keywords($this->table_name);
		switch ($this->action){
			case 'addnew': $this->addnew(); break;
			case 'edit': $this->edit(); break;
			default: $this->display(); break;
		}
	}

	public function per_page(){
		$screen = get_current_screen();
		add_screen_option('per_page', array(
			'label' => __('Items per page'),
			'default' => 20,
			'option' => 'app_items_per_page'
		));
	}

}

new App_Questions;
