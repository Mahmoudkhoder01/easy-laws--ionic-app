<?php
if ( ! defined( 'ABSPATH' ) ) exit;

new App_Admin_Reports;
class App_Admin_Reports {

	public function __construct() {
        AH()->server_limit(); // raise the execution time
		$this->singular = 'Report';
		$this->plural   = 'Reports';
		$this->slug     = 'app-reports';
		$this->table    = PRX.'subjects';
		$this->action   = app_rq('action');
		$this->link     = 'admin.php?page='.$this->slug;

		add_action('app_admin_menu_bottom', [$this, 'menu'], 16);
	}

	public function menu(){
		add_menu_page($this->plural, $this->plural, 'manage_options', $this->slug, [$this, 'page'], '', '2.0206');
    }

    function tree($parent = 0){
        $order="menu_order ASC, title ASC";
        $where = "parent={$parent}";
        $tnotes = PRX.'subject_notes';
        $tq = PRX.'questions';
        $rows = DB()->get_results("SELECT *, 
        ( SELECT note from $tnotes WHERE subject_id = $this->table.ID ) as notes, 
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND status=2 AND CHAR_LENGTH(title) > 1 ) as corrected_AR, 
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND status=0 AND CHAR_LENGTH(title) > 1 ) as pending_AR, 
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND status=1 AND CHAR_LENGTH(title) > 1 ) as active_AR, 
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND CHAR_LENGTH(title) < 1 ) as empty_AR, 

        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND status_en=2 AND CHAR_LENGTH(title_en) > 1 ) as corrected_EN, 
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND status_en=0 AND CHAR_LENGTH(title_en) > 1 ) as pending_EN, 
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND status_en=1 AND CHAR_LENGTH(title_en) > 1 ) as active_EN, 
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND CHAR_LENGTH(title_en) < 1 ) as empty_EN,

        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND status_fr=2 AND CHAR_LENGTH(title_fr) > 1 ) as corrected_FR, 
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND status_fr=0 AND CHAR_LENGTH(title_fr) > 1 ) as pending_FR, 
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND status_fr=1 AND CHAR_LENGTH(title_fr) > 1 ) as active_FR,
        ( SELECT COUNT(ID) FROM $tq WHERE FIND_IN_SET($this->table.ID, `categories`)>0 AND CHAR_LENGTH(title_fr) < 1 ) as empty_FR

        FROM $this->table WHERE $where ORDER BY $order");
        foreach($rows as $row){
			$row->children = $this->tree( $row->ID );
        }
        return $rows;
    }

    function tree_grid(){
        $tree = $this->tree();
        $o = '';
        foreach($tree as $node){
            $o .= $this->parse_node($node);
        }
        return $o;
    }

    function parse_node($node){
        $pclass = $node->parent ? 'treegrid-parent-'.$node->parent : '';
        $img = $this->check_icon($node->image);
        $badges_class = !empty($node->children) ? 'hide' : '';
        $o = "
            <tr class='treegrid-$node->ID $pclass'>
                <td>
                    <a href='admin.php?page=app-subjects&action=edit&id=$node->ID' target='_blank'><i class='fa fa-edit'></i> $node->ID</a>
                </td>
                <td>$node->title</td>
                <td>
                    <a href='admin.php?page=app-questions&app_filter=$node->ID' target='_blank'><i class='fa fa-link'></i> $node->posts_count</a>
                </td>

                <td class='text-center'>$img</td>

                <td><div class='$badges_class'>
                    <span class='badge badge-default'>$node->empty_AR</span><br>
                    <span class='badge badge-danger'>$node->pending_AR</span><br>
                    <span class='badge badge-warning'>$node->corrected_AR</span><br>
                    <span class='badge badge-success'>$node->active_AR</span>
                </div></td>
                <td><div class='$badges_class'>
                    <span class='badge badge-default'>$node->empty_EN</span><br>
                    <span class='badge badge-danger'>$node->pending_EN</span><br>
                    <span class='badge badge-warning'>$node->corrected_EN</span><br>
                    <span class='badge badge-success'>$node->active_EN</span>
                </div></td>
                <td><div class='$badges_class'>
                    <span class='badge badge-default'>$node->empty_FR</span><br>
                    <span class='badge badge-danger'>$node->pending_FR</span><br>
                    <span class='badge badge-warning'>$node->corrected_FR</span><br>
                    <span class='badge badge-success'>$node->active_FR</span>
                </div></td>

                <td>
                    <textarea class='form-control autogrow __note' data-id='$node->ID'>$node->notes</textarea>
                </td>
            </tr>
        ";
        if(!empty($node->children)){
            foreach($node->children as $node){
                $o .= $this->parse_node($node);
            }
        }
        return $o;
    }

    function check_icon($v = 0){
        return $v ? '<i class="fa fa-check __check __check_green"></i>' : '<i class="fa fa-circle __check __check_red"></i>';
    }
    
    function page(){
        echo '
            <div class="wrap" style="overflow-x: scroll;">
                <div>
                    <h3 class="pull-left">
                        Reports
                        <div class="btn-group">
                            <button class="btn btn-default btn-sm __collapse">
                                <i class="fa fa-compress"></i>
                            </button>
                            <button class="btn btn-default btn-sm __expand">
                                <i class="fa fa-expand"></i>
                            </button>
                        </div>
                    </h3>
                    <div class="pull-right text-muted bold" style="margin-top: 20px;">
                        <span class="badge badge-default">#</span> Empty 
                        <span class="badge badge-danger">#</span> Pending 
                        <span class="badge badge-warning">#</span> Corrected 
                        <span class="badge badge-success">#</span> Active
                    </div>
                </div>

                <table class="table treegrid">
                    <thead><tr>
                        <th style="width: 150px;">ID</th>
                        <th>Subject</th>
                        <th>Q#</th>
                        <th>Illustration</th>
                        <th>AR</th>
                        <th>EN</th>
                        <th>FR</th>
                        <th>Notes</th>
                    </tr></thead>
                    <tbody>
                        '.$this->tree_grid().'
                    </tbody>
                </table>
            </div>
            <style>
                .wrap a{color: #525C67;}
                .__note{min-width: 300px;background: transparent; box-shadow: none; border-color: transparent;resize: none;}
                .table th, .table td{white-space: nowrap;}
                /*.__check{font-size: 20px;}*/
                .__check_red{color: #ff0f0f; font-size: 16px;}
                .__check_green{color: #4caf50; font-size: 20px;}
                .hide{disply: none;}
                .table tr:hover td{background: #f5f5f0;}
            </style>
            <script>
            jQuery(document).ready(function($){
                $(".treegrid").treegrid({
                    // initialState: "collapsed"
                });
                $(".__collapse").on("click", function(e){
                    e.preventDefault();
                    $(".treegrid").treegrid("collapseAll");
                });
                $(".__expand").on("click", function(e){
                    e.preventDefault();
                    $(".treegrid").treegrid("expandAll");
                });
                $(".__note").on("keyup", function(e){
                    e.preventDefault();
                    var el = $(this),
                        id = el.data("id");
                    // console.log(id, el.val());
                    $.post(ajaxurl, {action: "app_subject_notes", note: el.val(), ID: id});
                });
            });
            </script>
        ';
    }

}
