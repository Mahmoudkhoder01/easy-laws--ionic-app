<?php
if ( ! defined( 'ABSPATH' ) ) exit;

new App_Admin_Page_Sponsor_Ads;
class App_Admin_Page_Sponsor_Ads {

	public function __construct() {
		$this->singular = 'Campaign';
		$this->plural = 'Campaigns';
		$this->slug = 'app-campaigns';
		$this->table_name  = DB()->prefix.'app_sponsor_ads';
		$this->action = app_rq('action');
		$this->link = 'admin.php?page='.$this->slug;
		$this->link_add = add_query_arg(array('action' => 'addnew'), $this->link);
		$this->link_edit = add_query_arg(array('action' => 'edit'), $this->link);

		add_action('app_admin_menu_bottom', array($this, 'menu'), 16);
	}

	public function menu(){
		$p = add_submenu_page('sponsors', $this->plural, $this->plural, 'manage_options', $this->slug, array($this, 'page'), '', '2.0205');
        add_action('load-'.$p, array($this, 'per_page'));
    }
    
    function sponsors(){
        $t = DB()->prefix.'app_sponsors';
        $ss = DB()->get_results("SELECT * FROM $t order by `name` desc");
        $o = [];
        $o[''] = '-- Select Sponsor --';
        foreach($ss as $s){
            $o[$s->ID] = $s->name;
        }
        return $o;
    }

	public function columns(){
		return array(
            'sponsor_id' => [
				'type' => 'select',
                'label' => 'Sponsor',
                'options' => $this->sponsors()
            ],
            'active' => [
                'type' => 'switch',
                'label' => 'Active',
                'default' => 0
            ],
			'title' => [
				'type' => 'text',
                'label' => 'Title',
                'desc' => 'Campaign title (internal reference)',
            ],
			'image' => [
				'type' => 'media',
				'label' => 'Image',
                'media_type' => 'image',
                'desc' => '320px X 50px'
			],
            'link' => [
				'type' => 'text',
                'label' => 'Link',
                'desc' => 'include http:// or https://'
            ],
            'start' => [
                'type' => 'date',
                'label' => 'Start Date',
                // 'default' => date('Y-m-d', strtotime('+1 day')),
            ],
            'end' => [
                'type' => 'date',
                'label' => 'End Date',
                // 'default' => date('Y-m-d', strtotime('+1 month')),
            ],
            'sections' => [
                'type' => 'select',
                'label' => 'Show On',
                'options' => [
                    'questions' => 'Question (choose ID(s))',
                    'subjects' => 'Subjects (choose below)',
                    'screens' => 'Screens',
                ],
                'multiple' => true,
            ],
            'questions' => [
				'type' => 'text',
                'label' => 'Question(s)',
                'desc' => 'Qustions IDs (separate by comma)'
            ],
            'subjects' => [
				'type' => 'tree',
				'label' => 'Subject(s)',
				'section' => 'subjects',
			],
            'screens' => [
                'type' => 'select',
                'label' => 'Screens',
                'options' => [
                    'dashboard' => 'Dashboard',
                    'subjects' => 'Subjects',
                    'search' => 'Search',
                    'notifications' => 'Notifications',
                    'more' => 'More',
                    'settings' => 'Settings',
                    'askquestion' => 'Ask Question',
                    'favorites' => 'Favorites',
                    'history' => 'History',
                ],
                'multiple' => true,
            ],
		);
    }
    
    function form_scripts(){
        return '<script>
        jQuery(document).ready(function($){
            var select = $("#form-item-show-on select"),
                questions = $("#form-item-questions"),
                subjects = $("#form-item-subjects"),
                screens = $("#form-item-screens");

            var __hide = function(){
                questions.hide();
                subjects.hide();
                screens.hide();
            }
            __hide();
            select.on("change", function(){
                var selected = select.find("option:selected");
                var __s = [];
                selected.each(function(){
                    var v = $(this).val();
                    __s.push(v);
                });
                console.log(__s);
                __hide();
                if($.inArray("questions", __s) !== -1) questions.show();
                if($.inArray("subjects", __s) !== -1) subjects.show();
                if($.inArray("screens", __s) !== -1) screens.show();
            }).trigger("change");
        });
        </script>';
    }

	public function display(){
		require_once( dirname(__FILE__) . '/sponsor-ads-list.php' );
		$list_table = new App_Sponsor_Ads_List_Table;
		$per_page = 50;

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
            if(empty($_POST['sponsor_id'])) $err[] = 'Sponsor is required';
            if(empty($_POST['title'])) $err[] = 'title is required';
            if(empty($_POST['start'])) $err[] = 'Start Date is required';
            if(empty($_POST['end'])) $err[] = 'End Date is required';

			if(!empty($err)){
				echo '<div class="error">'.implode(', ', $err).'</div>';
			} else {
				$insert_vals = array();
				foreach ($this->columns() as $name => $type) {
					$insert_vals[$name] = stripslashes_deep(__fix_spaces($_POST[$name]));
                }

                $insert_vals['active'] = isset($insert_vals['active']) ? 1 : 0;
                $insert_vals['sections'] = $insert_vals['sections'] ? implode(',', $insert_vals['sections']) : '';
                $insert_vals['screens'] = $insert_vals['screens'] ? implode(',', $insert_vals['screens']) : '';
                $insert_vals['start'] = strtotime($insert_vals['start']);
                $insert_vals['end'] = strtotime($insert_vals['end']);

				$insert_vals['date_created'] = time();
				// $insert_vals['author'] = get_current_user_id();
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
        echo '<div style="height: 200px;"></div>';
        echo $this->form_scripts();

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
            if(empty($_POST['sponsor_id'])) $err[] = 'Sponsor is required';
            if(empty($_POST['title'])) $err[] = 'title is required';
            if(empty($_POST['start'])) $err[] = 'Start Date is required';
            if(empty($_POST['end'])) $err[] = 'End Date is required';

			if(!empty($err)){
				echo '<div class="error">'.implode(', ', $err).'</div>';
			} else {
				$insert_vals = array();
				foreach ($this->columns() as $name => $type) {
					$insert_vals[$name] = stripslashes_deep(__fix_spaces($_POST[$name]));
                }
                
                $insert_vals['active'] = isset($insert_vals['active']) ? 1 : 0;
                $insert_vals['sections'] = $insert_vals['sections'] ? implode(',', $insert_vals['sections']) : '';
                $insert_vals['screens'] = $insert_vals['screens'] ? implode(',', $insert_vals['screens']) : '';
                $insert_vals['start'] = strtotime($insert_vals['start']);
                $insert_vals['end'] = strtotime($insert_vals['end']);

				$insert_vals['date_edited'] = time();
				DB()->update($this->table_name, AH()->stripslashes($insert_vals), array('ID' => $id));

				wp_redirect($this->link.'&message=Updated%20Successfully');
				exit();
			}
		}

		echo AF('form_open');
		echo AF('table_open');

		foreach($this->columns() as $k => $v){
            $value = $row[$k];
            if($v['type'] == 'date'){
                $value = $value ? date('Y-m-d', $value) : '';
            }
			echo AF($v['type'], array_merge([
				'name' => $k,
				'value' => $value,
			], $v));
		}

		echo AF('table_close');
		echo AF('submit', ['name'=> 'submit', 'value' => 'Update']);
        echo AF('form_close');
        echo '<div style="height: 200px;"></div>';
        echo $this->form_scripts();

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
