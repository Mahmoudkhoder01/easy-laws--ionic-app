<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class App_Dashboards {

	public function __construct() {
		$this->singular = 'Customized List';
		$this->plural = 'Customized Lists';
		$this->slug = 'app-dashboards';
		$this->table_name  = DB()->prefix.'app_dashboards';
		$this->action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : '';
		$this->link = 'admin.php?page='.$this->slug;
		$this->link_add = add_query_arg(array('action' => 'addnew'), $this->link);
		$this->link_edit = add_query_arg(array('action' => 'edit'), $this->link);

		add_action('app_admin_menu', array($this, 'menu'), 18);
	}

	public function menu(){
		$p = add_submenu_page('app', $this->plural, $this->plural, 'edit_pages', $this->slug, array($this, 'page'));
		add_action('load-'.$p, array($this, 'per_page'));
	}

	public function columns(){
		return array(
			'title' => [
				'type' => 'text',
				'label' => 'List Name'
			],
			'subjects' => [
				'type' => 'tree',
				'label' => 'Subjects',
				'section' => 'subjects',
			],
			'details' => [
				'type' => 'editor',
				'label' => 'Explanation',
			],
		);
	}

	public function display(){
		require_once( dirname(__FILE__) . '/dashboards-list.php' );
		$list_table = new App_Dashboards_List_Table;
		$per_page = 20;

		$screen = get_current_screen();
		$screen_option = $screen->get_option('per_page', 'option');
		$per_page = get_user_meta(get_current_user_id(), $screen_option, true);
		if ( empty ( $per_page) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		$list_table->prepare_items($per_page, $this->link);

		switch ( $list_table->current_action() ) {
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
					DB()->query("DELETE FROM {$this->table_name} WHERE `ID`=$id");
					++$delete_count;
				}
				$redirect = add_query_arg( array('delete_count' => $delete_count, 'update' => $update), $this->link);
				wp_redirect($redirect);
				exit();
		}

		echo $this->header();
		if(!empty($_GET['message'])) echo '<div class="updated">'.$_GET['message'].'</div>';
		if(!empty($_GET['error'])) echo '<div class="error">'.$_GET['error'].'</div>';

			echo '
				<form method="get">
					<input type="hidden" name="page" value="'.$this->slug.'">
			';
			$list_table->search_box( __( 'Search' ), 'user' );
			$list_table->display();
			echo '</form>';

		echo $this->footer();
	}

	public function addnew(){
		echo $this->header('Add New', false, true);

		if(isset($_POST['submit'])){
			$err = array();
			if(empty($_POST['title']) || strlen($_POST['title']) < 2){
				$err[] = 'Title is required';
			} elseif(app_has_duplicate($this->table_name, $_POST['title'])){
				$err[] = 'Duplicate entry';
			}

			if(!empty($err)){
				echo '<div class="error">'.implode(', ', $err).'</div>';
			} else {
				$insert_vals = array();
				foreach ($this->columns() as $name => $type) {
					$insert_vals[$name] = stripslashes_deep($_POST[$name]);
				}
				$insert_vals['date_created'] = time();
				$insert_vals['author'] = get_current_user_id();
				DB()->insert($this->table_name, AH()->stripslashes($insert_vals));

				wp_redirect($this->link.'&message=Added%20Successfully');
				exit();
			}
		}

		echo AF('form_open');
		echo AF('table_open');

		foreach($this->columns() as $k => $v){
			echo AF($v['type'], array_merge([
				'name' => $k,
				'value' => !empty($_POST[$k]) ? $_POST[$k] : '',
			], $v));
		}

		echo AF('table_close');
		echo AF('submit', ['name'=> 'submit', 'value' => 'Add New']);
		echo AF('form_close');

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
			$err = array();
			if(empty($_POST['title']) || strlen($_POST['title']) < 2){
				$err[] = 'Title required';
			} elseif(app_has_duplicate($this->table_name, $_POST['title'], $id)){
				$err[] = 'Duplicate entry';
			}

			if(!empty($err)){
				echo '<div class="error">'.implode(', ', $err).'</div>';
			} else {
				$insert_vals = array();
				foreach ($this->columns() as $name => $type) {
					$insert_vals[$name] = stripslashes_deep($_POST[$name]);
				}
				$insert_vals['date_edited'] = time();
				DB()->update($this->table_name, AH()->stripslashes($insert_vals), array('ID' => $id));

				wp_redirect($this->link.'&message=Updated%20Successfully');
				exit();
			}
		}

		echo AF('form_open');
		echo AF('table_open');

		foreach($this->columns() as $k => $v){
			echo AF($v['type'], array_merge([
				'name' => $k,
				'value' => $row[$k],
			], $v));
		}

		echo AF('table_close');
		echo AF('submit', ['name'=> 'submit', 'value' => 'Update']);
		echo AF('form_close');

		echo $this->footer();
	}

	public function header($title = '', $show_add = true, $show_back = false){
		$n = $show_add ? '<a href="'.$this->link_add.'" class="add-new-h2">Add '.$this->singular.'</a>' : '';
		$b = '';
		// $b = $show_back ? '<div style="margin-bottom:20px;"><a href="'.$this->link.'">&laquo; Back</a></div>' : '';
		return '
			<div class="wrap">
				<h2>'.$title.' '.$this->plural.' '.$n.'</h2>
		';
	}

	public function footer(){
		return '</div>';
	}

	public function page(){
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

new App_Dashboards;
