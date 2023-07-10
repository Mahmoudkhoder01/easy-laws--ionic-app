<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class App_DYK_Push_Filter {

	public function __construct() {
		$this->singular = 'DYK Push Filter';
		$this->plural = 'DYK Push Filters';
		$this->slug = 'app-dyk-push-filter';
		$this->table_name  = DB()->prefix.'app_subjects';
		$this->action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : '';
		$this->link = 'admin.php?page='.$this->slug;

		add_action('app_admin_menu', array($this, 'menu'), 14);
	}

	public function menu(){
		$p = add_submenu_page('app', $this->plural, $this->plural, 'edit_pages', $this->slug, array($this, 'display'));
    }

    function tree_parse($item, $values = ''){
        $o = array();
		$data = (array) $item['data'];
        $children = (array) $item['children'];
        
        // $count = $this->__count($data['ID']);

		$o['id'] = $o['ID'] = $data['ID'];
        $o['text'] = $data['title'];
        $o['tags'] = [
            '<span class=\"__count\" data-id=\"'.$data['ID'].'\">COUNT</span>',
            'ID: '.$data['ID'],
        ];

        $o['state']['checked'] = $o['state']['selected'] = false;
		if( in_array(trim($data['ID']), array_map('trim', $values))){
			$o['state']['checked'] = $o['state']['selected'] = true;
		}
		if(!empty($children)){
			$o['nodes'] = array_map(function($item) use ($values){
				return $this->tree_parse($item, $values);
			}, $children);
		}
		return $o;
    }
    
    function get_subjects(){
        $values = get_option('app_did_you_know_filter', '');
        $arr = array_map(function($item) use ($values){
			return $this->tree_parse($item, $values);
        }, get_subjects(['compact' => false]) );
        return json_encode($arr);
    }

	public function display(){
        echo $this->header();

        // AH()->print_r( $this->simulate() );

		if(!empty($_GET['message'])) echo '<div class="updated">'.$_GET['message'].'</div>';
		if(!empty($_GET['error'])) echo '<div class="error">'.$_GET['error'].'</div>';

        echo '
            <div class="row" style="margin:20px 0;">
                <div class="col-md-3">
                    <div class="floating">
                        <div class="alert alert-warning text-center">
                            <p><b>Disable DYK</b> push notifications from the selected subjects</p>
                        </div>
                        <div style="margin:10px 0;">
                            <button type="button" class="btn btn-primary btn-block SAVE">
                                <span class="__text">Save</span>
                                <i class="__loader hide fa fa-refresh fa-spin"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div id="TREE"></div>
                </div>
            </div>

			<script>
			jQuery(document).ready(function($){
				var tree = $("#TREE"), data = \''.$this->get_subjects().'\';
                
                tree.treeview({
		          	data: data,
		          	showTags: true,
                    multiSelect: true,
                    selectedBackColor: "#ff4444",
                    selectedColor: "#FFF",
                    levels: 10,
                });

                $(".SAVE").on("click", function(e){
                    e.preventDefault();
                    var ids = [],
                        butt = $(".SAVE"),
                        text = butt.find(".__text"),
                        loader = butt.find(".__loader");

                    var checked = $("#TREE").treeview("getSelected");
                    checked.forEach(function(i){
                        ids.push(i.ID);
                    });
                    butt.attr("disabled", "disabled");
                    text.addClass("hide");
                    loader.removeClass("hide");
                    $.post(ajaxurl, {action: "dyk_filter", ids: ids}, function(data){
                        butt.removeAttr("disabled");
                        text.removeClass("hide");
                        loader.addClass("hide");
                    });
                    console.log(ids);
                });

                $(".__count").on("click", function(e){
                    e.preventDefault();
                    var el = $(this),
                        id = el.data("id");
                    el.closest("li").trigger("click");
                    el.html("<i class=\"fa fa-refresh fa-spin\"></i>");
                    $.post(ajaxurl, {action: "dyk_count_by_cat", ID: id}, function(data){
                        el.html(data);
                    });
                });
			});
			</script>
		';

		echo $this->footer();
	}

    function simulate(){
        $t = DB()->prefix.'app_questions';
        $bad = get_option('app_did_you_know_filter');
		$bad = $bad ? $bad : [];
		$sent = get_option('app_did_you_know_sent');
		$sent = $sent ? $sent : [];
		$sent_ids = implode(',', $sent);

		$query = "SELECT ID,did_you_know,categories from {$t} WHERE status=1 AND CHAR_LENGTH(did_you_know) > 10";
		if($sent_ids) $query .= " AND ID NOT IN ({$sent_ids})";
		if(!empty($bad)){
			foreach($bad as $b){
                $b = intval(trim($b));
                $query .= " AND (FIND_IN_SET({$b}, `categories`)=0)";
            }
		} 
        $query .= " ORDER BY rand()";
        $rows = DB()->get_results($query);
        return $rows;
    }

	public function header(){
		return '<div class="wrap"><h2>'.$this->plural.'</h2>';
	}

	public function footer(){
		return '</div>';
	}

}

new App_DYK_Push_Filter;
