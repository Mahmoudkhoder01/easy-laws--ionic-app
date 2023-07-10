<?php

if(!class_exists('WP_List_Table')) require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class App_Requests_List_Table extends WP_List_Table {
	public $base_link;

    public function prepare_items($per_page = 20, $base_link = '') {
    	$this->base_link = $base_link;
        $table = DB()->prefix . 'app_requests';
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $paged = (int) $this->get_pagenum();
        $per_page = (int) $per_page;
        $offset = ($paged - 1) * $per_page;
        $join = '';

        $where = 'WHERE 1=1';
        $orderby = 'date_created';
        $order = 'DESC';


        $s = app_rq('s');
        if ($s) $where .= " AND (`details` LIKE '%$s%' OR `user_id` LIKE '%$s%')";

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
        return ['delete' => 'Delete'];
    }

    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'date' => 'Date',
            'request' => 'Request',
            'file' => 'File',
            'user' => 'User',
        );
    }

    protected function get_hidden_columns(){ return array(); }

    protected function get_sortable_columns() {
        return [
            'user' => array('user_id', true),
        ];
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        $filter_status = app_rq('app_filter_status');
        echo '<div class="alignleft actions">';
        echo '</div>';
    }

    public function display_rows() {
        foreach ($this->items as $k => $v) {
            echo "\n\t" . $this->single_row($v);
        }
    }

    public function single_row($row) {
    	$prx = DB()->prefix.'app_';
        $created = ($row->date_created) ? date("d-M-y", $row->date_created) : '---';

        $checkbox = '';
        $link_del = add_query_arg(['action' => 'delete', 'id' => $row->ID], $this->base_link);

        $actions = array();

        $edit = "<b>$created</b>";
        // $actions['edit'] = '<a href="'.$link_edit.'">Edit</a>';
        $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url($link_del, 'bulk-ids') . "' onclick='return confirm(\"Are you sure?\");' >Delete</a>";
        $edit.= $this->row_actions($actions);

        $file = $row->file ? '<wavesurfer data-url="'.$row->file.'" data-height="50"></wavesurfer>' : '';
        $user = app_user_card($row->user_id);

        $checkbox = "<input type='checkbox' name='ids[]' value='{$row->ID}' />";

        $r = "<tr id='user-$row->ID'>";

        list($columns, $hidden) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $class = 'class="'.$column_name.' column-'.$column_name.'"';

            $style = '';
            if (in_array($column_name, $hidden)) $style='style="display:none;"';

            $atts = "$class $style";

            switch ($column_name) {
                case 'cb':$r.= "<th class='check-column'>$checkbox</th>";break;
                case 'date': $r.= "<td $atts>$edit</td>"; break;
                case 'request': $r.= "<td $atts>$row->details</td>"; break;
                case 'user': $r.= "<td $atts>$user</td>"; break;
                case 'file': $r.= "<td $atts>$file</td>"; break;
            }
        }
        $r.= '</tr>';

        return $r;
    }
}
