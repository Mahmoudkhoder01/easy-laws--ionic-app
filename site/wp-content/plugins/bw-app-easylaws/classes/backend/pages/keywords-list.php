<?php

if(!class_exists('WP_List_Table')) require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class App_Keywords_List_Table extends WP_List_Table {
	public $base_link;

    public function prepare_items($per_page = 20, $base_link = '') {
    	$this->base_link = $base_link;
        $table = DB()->prefix . 'app_keywords';
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $paged = (int) $this->get_pagenum();
        $per_page = (int) $per_page;

        $search = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
        $offset = ($paged - 1) * $per_page;
        $join = '';

        $where = 'WHERE 1=1';
        $orderby = 'title';
        $order = 'ASC';

        if ($search) $where .= " AND (`title` LIKE '%$search%' OR `details` LIKE '%$search%')";
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
        return array('delete' => 'Delete');
    }

    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Title',
            'keywords' => 'Keywords',
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
            'author' => array('author', true),
            'dates' => array('date_created', true),
        );
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        echo '<div class="alignleft actions">';
            do_action( 'restrict_manage_epay_pcodes' );
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
        $dates = "Created: $created<br>Edited: $edited";

        $author = $row->author ? get_userdata($row->author) ? get_userdata($row->author)->user_nicename : '---' : '---';

        $checkbox = '';

        $link_edit = add_query_arg(array(
        	'action' => 'edit',
        	'id' => $row->ID,
        ), $this->base_link);

        $link_del = add_query_arg(array(
        	'action' => 'delete',
        	'id' => $row->ID,
        ), $this->base_link);

        $actions = array();

        $edit = "<a href=\"$link_edit\"><strong>$row->title</strong></a>";
        $actions['edit'] = '<a href="'.$link_edit.'">Edit</a>';
        $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url($link_del, 'bulk-ids') . "' onclick='return confirm(\"Are you sure?\");' >Delete</a>";

        $edit.= $this->row_actions($actions);

        $keywords = $row->details ? count(explode(',', $row->details)) : 0;
        $keywords = $keywords .' keywords';

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
                case 'title': $r.= "<td $atts>$edit</td>"; break;
                case 'keywords': $r.= "<td $atts>$keywords</td>"; break;
                case 'author': $r.= "<td $atts>$author</td>"; break;
                case 'dates': $r.= "<td $atts>$dates</td>"; break;
                default: $r.= "<td $atts>".apply_filters('manage_keywords_custom_column', '', $column_name, $row->ID)."</td>";
            }
        }
        $r.= '</tr>';

        return $r;
    }
}
