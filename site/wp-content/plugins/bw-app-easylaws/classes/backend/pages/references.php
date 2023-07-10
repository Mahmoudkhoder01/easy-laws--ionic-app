<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class App_References {

	public function __construct() {
		$this->singular = 'Reference';
		$this->plural = 'References';
		$this->slug = 'app-references';
		$this->table_name  = DB()->prefix.'app_references';
		$this->action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : '';
		$this->link = 'admin.php?page='.$this->slug;
		$this->link_add = add_query_arg(array('action' => 'addnew'), $this->link);
		$this->link_edit = add_query_arg(array('action' => 'edit'), $this->link);
		$this->link_del = add_query_arg(array('action' => 'delete'), $this->link);

		add_action('app_admin_menu', array($this, 'menu'), 17);
	}

	public function menu(){
		$p = add_submenu_page('app', $this->plural, $this->plural, 'read', $this->slug, array($this, 'page'));
		add_action('load-'.$p, array($this, 'per_page'));
	}

	public function columns(){
		return array(
			'parent' => [
				'type' => 'tree',
				'label' => 'Parent',
				// 'options' => app_references_select(),
				'section' => 'references',
			],
			'tablist' => tabs_langs(),
				'AR' => ['type' => 'tab', 'active' => true],
					'title' => [
						'type' => 'text',
						'label' => 'Title'
					],
					'details' => [
						'type' => 'editor',
						'lang' => 'ar',
						'label' => 'Details'
					],
				'/AR' => ['type' => '/tab'],

				'EN' => ['type' => 'tab'],
					'title_en' => [
						'type' => 'text',
						'label' => 'Title'
					],
					'details_en' => [
						'type' => 'editor',
						'lang' => 'en',
						'label' => 'Details'
					],
				'/EN' => ['type' => '/tab'],

				'FR' => ['type' => 'tab'],
					'title_fr' => [
						'type' => 'text',
						'label' => 'Title'
					],
					'details_fr' => [
						'type' => 'editor',
						'lang' => 'fr',
						'label' => 'Details'
					],
				'/FR' => ['type' => '/tab'],
			'/tablist' => ['type' => '/tablist'],
			
		);
	}

	public function display(){

		switch ( $this->action ) {
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
					<div class="row" style="margin:20px 0;">
						<div class="col-md-3">
						<div class="floating">
							<div style="margin-bottom:5px;">
								<button type="button" class="btn btn-xs btn-default" id="e_all">Expand All</button>
								<button type="button" class="btn btn-xs btn-default" id="c_all">Collapse All</button>
							</div>
							<input type="input" class="form-control" id="TS" placeholder="Search" value="">
							<div style="margin-top:20px; display:none;" id="TA">
								<a class="btn btn-primary EDIT">Edit</a>
								<a class="btn btn-danger DEL" onclick="return confirm(\'Are you sure?\');">Delete</a>
							</div>
						</div>
						</div>
						<div class="col-md-9">
							<div id="TREE"></div>
						</div>
					</div>

					<script>
					jQuery(document).ready(function($){
						var tree = $("#TREE"),
							tree_search = $("#TS"),
							tree_action = $("#TA"),
							e_all = $("#e_all"),
							c_all = $("#c_all"),
							data = \''.app_json_tree('references').'\';

						tree.treeview({
				          	data: data,
				          	showTags: true,
				          	levels: 1,
				        });
						tree.on("nodeSelected", function(e, node){
							tree_action.find("a.EDIT").attr("href", "'.$this->link_edit.'&id="+node.ID);
							tree_action.find("a.DEL").attr("href", "'.$this->link_del.'&id="+node.ID);
							tree_action.slideDown();
							APP.ref_tree_count();
						});
						tree.on("nodeUnselected", function(e, node){
							tree_action.find("a.EDIT").attr("href", "#");
							tree_action.find("a.DEL").attr("href", "#");
							tree_action.slideUp();
							APP.ref_tree_count();
						});
						tree_search.on("keyup", function(){
							var pattern = tree_search.val();
					        var options = {
					            ignoreCase: true,
					            exactMatch: false,
					            revealResults: true
					        };
					        if(pattern.length){
					        	tree.treeview("collapseAll");
					        	var results = tree.treeview("search", [ pattern, options ]);
					        } else {
					        	tree.treeview("clearSearch");
					        }
						});

						e_all.on("click", function(e){
							e.preventDefault();
							tree.treeview("expandAll");
						});
						c_all.on("click", function(e){
							e.preventDefault();
							tree.treeview("collapseAll");
						});
						setTimeout(function(){
							APP.ref_tree_count();
						}, 500);
					});
					</script>
			';

			echo '</form>';

		echo $this->footer();
	}

	public function addnew(){
		echo $this->header('Add New', false, true);

		if(isset($_POST['submit'])){
			$err = array();
			if(empty($_POST['title']) || strlen($_POST['title']) < 2){
				$err[] = 'Title is required';
			}

			if(!empty($err)){
				echo '<div class="error">'.implode(', ', $err).'</div>';
			} else {
				$insert_vals = array();
				foreach ($this->columns() as $name => $type) {
					if(in_array($name, AF_EX())) continue;
					$insert_vals[$name] = stripslashes_deep($_POST[$name]);
				}
				$insert_vals['date_created'] = time();
				$insert_vals['author'] = get_current_user_id();
				DB()->insert($this->table_name, AH()->stripslashes($insert_vals));

				wp_redirect($this->link.'&message=Added%20Successfully');
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
		echo AF('submit', ['name'=> 'submit', 'value' => 'Add New']);
		echo AF('/form');

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
			}

			if(!empty($err)){
				echo '<div class="error">'.implode(', ', $err).'</div>';
			} else {
				$insert_vals = array();
				foreach ($this->columns() as $name => $type) {
					if(in_array($name, AF_EX())) continue;
					$insert_vals[$name] = stripslashes_deep($_POST[$name]);
				}
				$insert_vals['date_edited'] = time();
				DB()->update($this->table_name, AH()->stripslashes($insert_vals), array('ID' => $id));

				wp_redirect($this->link.'&message=Updated%20Successfully');
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
		echo AF('submit', ['name'=> 'submit', 'value' => 'Update']);
		echo AF('/form');

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

new App_References;
