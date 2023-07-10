<?php

if(!class_exists('WP_List_Table')) require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class App_Sponsors_List_Table extends WP_List_Table {
	public $base_link;

    public function prepare_items($per_page = 50, $base_link = '') {
    	$this->base_link = $base_link;
        $table = DB()->prefix . 'app_sponsors';
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
        $orderby = 'name';
        $order = 'ASC';

        if ($search) $where .= " AND (`name` LIKE '%$search%' OR `contact_name` LIKE '%$search%' OR `email` LIKE '%$search%' OR `phone` LIKE '%$search%')";
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
            'sponsor' => 'Sponsor',
            'contact' => 'Contact',
            'campaigns' => 'Campaigns',
            'author' => 'Author',
            'dates' => 'Dates',
        );
    }

    protected function get_hidden_columns(){
        return array();
    }

    protected function get_sortable_columns() {
        return array(
            'sponsor' => array('name', true),
            'contact' => array('contact_name', true),
            'author' => array('author', true),
            'dates' => array('date_created', true),
        );
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        echo '<div class="alignleft actions">';
            // do_action( 'restrict_manage_epay_pcodes' );
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

        $link_edit = add_query_arg(['action' => 'edit','id' => $row->ID], $this->base_link);
        $link_del = add_query_arg(['action' => 'delete', 'id' => $row->ID], $this->base_link);

        $actions = array();

        $edit = "<a href=\"$link_edit\"><strong>$row->name</strong></a>";
        $actions['edit'] = '<a href="'.$link_edit.'">Edit</a>';
        $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url($link_del, 'bulk-ids') . "' onclick='return confirm(\"Are you sure?\");' >Delete</a>";

        $edit.= $this->row_actions($actions);

        $contact = "$row->contact_name<br>$row->email<br>$row->phone";

        $t = PRX.'sponsor_ads';
        $count = DB()->get_var("SELECT COUNT(ID) FROM $t WHERE sponsor_id={$row->ID}");

        $campaigns = $count ? "$count <a href='admin.php?page=app-campaigns&app_filter=$row->ID'>(VIEW)</a>" : '---';

        $checkbox = "<input type='checkbox' name='ids[]' value='{$row->ID}' />";

        $r = "<tr id='user-$row->ID'>";

        list($columns, $hidden) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {

            switch ($column_name) {
                case 'cb':$r.= "<th class='check-column'>$checkbox</th>";break;
                case 'sponsor': $r.= "<td>$edit</td>"; break;
                case 'contact': $r.= "<td>$contact</td>"; break;
                case 'campaigns': $r.= "<td>$campaigns</td>"; break;
                case 'author': $r.= "<td>$author</td>"; break;
                case 'dates': $r.= "<td>$dates</td>"; break;
            }
        }
        $r.= '</tr>';

        return $r;
    }
}
