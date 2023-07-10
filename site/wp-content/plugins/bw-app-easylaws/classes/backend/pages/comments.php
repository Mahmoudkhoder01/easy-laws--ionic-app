<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class App_Comments {

	public function __construct() {
		$this->singular = 'Comment';
		$this->plural = 'Comments';
		$this->slug = 'app-comments';
		$this->table_name  = DB()->prefix.'app_question_comments';
		$this->table_q  = DB()->prefix.'app_questions';
		$this->action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : '';
		$this->link = 'admin.php?page='.$this->slug;

		add_action('app_admin_menu_bottom', array($this, 'menu'), 14);
	}

	public function menu(){
		$p = add_menu_page($this->plural, $this->plural, 'edit_pages', $this->slug, array($this, 'display'), '', '2.0200');
		add_action('load-'.$p, array($this, 'per_page'));
	}

	public function display(){
		require_once( dirname(__FILE__) . '/comments-list.php' );
		$list_table = new App_Comments_List_Table;
		$per_page = 20;

		$screen = get_current_screen();
		$screen_option = $screen->get_option('per_page', 'option');
		$per_page = get_user_meta(get_current_user_id(), $screen_option, true);
		if ( empty ( $per_page) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		$list_table->prepare_items($per_page, $this->link);

		switch ( $list_table->current_action() ) {
			case 'approve':
				if ( empty($_REQUEST['ids']) && empty($_REQUEST['id']) ) {
					wp_redirect($this->link); exit();
				}
				if ( empty($_REQUEST['ids']) ){
					$_ids = array( intval( $_REQUEST['id'] ) );
				} else{
					$_ids = array_map( 'intval', (array) $_REQUEST['ids'] );
				}
				$update = 'approve';
				$count = 0;
				$time = time();
				foreach ( $_ids as $id ) {
					DB()->query("UPDATE {$this->table_name} SET status=1, date_approved={$time} WHERE `ID`=$id");
					$question_id = DB()->get_var("SELECT question_id FROM {$this->table_name} WHERE ID={$id}");
					DB()->query("UPDATE {$this->table_q} SET comments = comments + 1 WHERE ID={$question_id}");
					++$count;
				}
				$redirect = add_query_arg(['count' => $count, 'update' => $update], $this->link);
				wp_redirect($redirect);
				exit();
			case 'unapprove':
				if ( empty($_REQUEST['ids']) && empty($_REQUEST['id']) ) {
					wp_redirect($this->link); exit();
				}
				if ( empty($_REQUEST['ids']) ){
					$_ids = array( intval( $_REQUEST['id'] ) );
				} else{
					$_ids = array_map( 'intval', (array) $_REQUEST['ids'] );
				}
				$update = 'unapprove';
				$count = 0;
				foreach ( $_ids as $id ) {
					DB()->query("UPDATE {$this->table_name} SET status=0, date_approved=0 WHERE `ID`=$id");
					$question_id = DB()->get_var("SELECT question_id FROM {$this->table_name} WHERE ID={$id}");
					DB()->query("UPDATE {$this->table_q} SET comments = comments - 1 WHERE ID={$question_id}");
					++$count;
				}
				$redirect = add_query_arg(['count' => $count, 'update' => $update], $this->link);
				wp_redirect($redirect);
				exit();
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


	public function header($title = ''){
		return '
			<div class="wrap">
				<h2>'.$title.' '.$this->plural.'</h2>
		';
	}

	public function footer(){
		return '</div>';
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

new App_Comments;
