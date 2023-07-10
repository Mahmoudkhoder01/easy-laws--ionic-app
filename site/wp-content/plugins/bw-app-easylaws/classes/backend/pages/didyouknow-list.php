<?php

if(!class_exists('WP_List_Table')) require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class App_Didyouknow_List_Table extends WP_List_Table {
	public $base_link;

    public function prepare_items($per_page = 20, $base_link = '') {
    	$this->base_link = $base_link;
        $this->base_link = 'admin.php?page=app-questions';
        $table = DB()->prefix . 'app_questions';
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $paged = (int) $this->get_pagenum();
        $per_page = (int) $per_page;

        $search = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
        $offset = ($paged - 1) * $per_page;
        $join = '';

        $where = 'WHERE 1=1 AND CHAR_LENGTH(did_you_know) > 10';
        $orderby = 'title';
        $order = 'ASC';

        $filter = app_rq('app_filter');
        if($filter){
            $ss = explode(',', $filter);
            foreach($ss as $s){
                $s = intval(trim($s));
                $where .= " AND (FIND_IN_SET({$s}, `categories`)>0)";
            }
        }

        if ($search) $where .= " AND (`title` LIKE '%$search%' OR `did_you_know` LIKE '%$search%')";
        if (isset($_REQUEST['orderby'])) $orderby = $_REQUEST['orderby'];
        if (isset($_REQUEST['order'])) $order = $_REQUEST['order'];

        $total = (int) DB()->get_var("SELECT COUNT(*) FROM {$table} {$where}");

        $results = DB()->get_results("SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT {$offset},{$per_page}");

        $this->items = $results;

        $this->set_pagination_args(array(
            'total_items' => $total,
            'per_page' => $per_page,
        ));
    }

    public function no_items() { _e('No items found.'); }

    protected function get_bulk_actions() {
        return [];
    }

    public function get_columns() {
        return array(
            // 'cb' => '<input type="checkbox" />',
            'title' => 'Question',
            'status' => '<i class="fa fa-circle-o"></i>',
            'categories' => 'Subjects',
            'did_you_know' => 'Did you know',
            'author' => 'Author',
            'dates' => 'Dates',
        );
    }

    protected function get_hidden_columns(){
        return array();
    }

    protected function get_sortable_columns() {
        return array(
            'title' => array('title', true),
            'categories' => array('categories', true),
            'status' => array('status', true),
            'author' => array('author', true),
            'did_you_know' => array('did_you_know', true),
            'dates' => array('date_created', true),
        );
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        $filter = app_rq('app_filter');
        echo '<div class="alignleft actions">';
        ?>
            <div class="actions_tree">
            <label>Subjects</label>
            <?php
                echo app_input_tree(array(
                    'section' => 'subjects',
                    'name' => 'app_filter',
                    'value' => $filter,
                ));
            ?>
            </div>
        <?php
            submit_button( __( 'Filter' ), 'primary', 'filter_submit', false );

            if($filter){
                echo '&nbsp;<a href="admin.php?page=app-questions" class="button" style="display:inline-block;margin-top:0;">Clear</a>';
            }

        echo '</div>';
    }

    public function display_rows() {
        foreach ($this->items as $k => $v) {
            echo "\n\t" . $this->single_row($v);
        }
    }

    public function single_row($row) {
        $created = ($row->date_created) ? date("d-M-y", $row->date_created) : '---';
        $edited = ($row->date_edited) ? date("d-M-y", $row->date_edited) : '---';
        $logs = '<a href="#" class="modal_link" data-action="app_question_logs" data-id="'.$row->ID.'" data-title="'.$row->title.' Logs">LOGS</a>';
        $dates = "Created: $created<br>Edited: $edited<BR>$logs";

        $status = get_status_icons($row->status);
        $author = $row->author ? get_userdata($row->author)->display_name : '---';

        $cats = subjects_by_field($row->categories);

        $link_edit = add_query_arg(['action' => 'edit', 'id' => $row->ID,], $this->base_link);
        $actions = array();

        $edit = "<a href=\"$link_edit\"><strong>$row->title</strong></a>";
        $actions['edit'] = '<a href="'.$link_edit.'">Edit</a>';
        $edit.= $this->row_actions($actions);

        $checkbox = "<input type='checkbox' name='ids[]' value='{$row->ID}' />";

        $r = "<tr id='user-$row->ID'>";

        list($columns, $hidden) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $class = 'class="'.$column_name.' column-'.$column_name.'"';

            $style = '';
            if (in_array($column_name, $hidden)) $style='style="display:none;"';

            $atts = "$class $style";

            switch ($column_name) {
                // case 'cb':$r.= "<th class='check-column'>$checkbox</th>";break;
                case 'title': $r.= "<td $atts>$edit</td>"; break;
                case 'categories': $r.= "<td $atts>{$cats}</td>"; break;
                case 'status': $r.= "<td $atts>$status</td>"; break;
                case 'did_you_know': $r.= "<td $atts>$row->did_you_know</td>"; break;
                case 'author': $r.= "<td $atts>$author</td>"; break;
                case 'dates': $r.= "<td $atts>$dates</td>"; break;
                default: $r.= "<td $atts>".apply_filters('manage_did_you_know_custom_column', '', $column_name, $row->ID)."</td>";
            }
        }
        $r.= '</tr>';

        return $r;
    }
}
