<?php

if(!class_exists('WP_List_Table')) require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class App_Devices_List_Table extends WP_List_Table {
	public $base_link;

    public function prepare_items($per_page = 20, $base_link = '') {
    	$this->base_link = $base_link;
        $table = DB()->prefix . 'app_devices';
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

        $filter_status = app_rq('app_filter_status');
        if ($filter_status) {
            $st = $filter_status == 'yes' ? 1 : 0;
            $where .= " AND (status={$st})";
        }

        $s = app_rq('s');
        if ($s) $where .= " AND (`uuid` LIKE '%$s%' OR `platform` LIKE '%$s%' OR `manufacturer` LIKE '%$s%' OR `model` LIKE '%$s%' OR `version` LIKE '%$s%' OR `user_id` LIKE '%$s%')";

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
        return [
        	'approve' => 'Approve',
        	'unapprove' => 'Un Approve',
        	'delete' => 'Delete'
        ];
    }

    public function get_columns() {
        return array(
            // 'cb' => '<input type="checkbox" />',
            'player' => 'OS ID',
            'user' => 'User',
            'device' => 'Device',
            'dates' => 'Dates',
            // 'status' => 'Valid'
        );
    }

    protected function get_hidden_columns(){ return array(); }

    protected function get_sortable_columns() {
        return array(
            'player' => array('player_id', true),
            'user' => array('user_id', true),
            'dates' => array('date_created', true),
        );
    }

    protected function extra_tablenav( $which ) {
        return;
        if ( 'top' != $which ) return;
        $filter_status = app_rq('app_filter_status');
        echo '<div class="alignleft actions">';
        ?>
        	<select name="app_filter_status" id="app_filter_status">
                <option value="">--Status--</option>
                <option value="yes" <?php selected($filter_status, 'yes');?>>Approved</option>
                <option value="no" <?php selected($filter_status, 'no');?>>Un Approved</option>
            </select>
        <?php
        	submit_button( __( 'Filter' ), 'primary', 'filter_submit', false );

            if($filter_status != ''){
                echo '&nbsp;<a href="admin.php?page=app-devices" class="button" style="display:inline-block;margin-top:0;">Clear</a>';
            }
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
        $edited = ($row->date_edited) ? date("d-M-y", $row->date_edited) : '---';
        $dates = "Created: $created<br>Edited: $edited";

        $checkbox = '';

        $link_approve = add_query_arg(['action' => 'approve', 'id' => $row->ID], $this->base_link);
        $link_unapprove = add_query_arg(['action' => 'unapprove', 'id' => $row->ID], $this->base_link);
        $link_del = add_query_arg(['action' => 'delete', 'id' => $row->ID], $this->base_link);

        $actions = array();

        $edit = "<strong>$row->player_id</strong>";
        // $actions['edit'] = '<a href="'.$link_edit.'">Edit</a>';

        /*if($row->status){
        	$actions['unapprove'] = "<a href='".$link_unapprove."' onclick='return confirm(\"Are you sure?\");' >Un Approve</a>";
        } else {
        	$actions['unapprove'] = "<a href='".$link_approve."' onclick='return confirm(\"Are you sure?\");' >Approve</a>";
        }

        $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url($link_del, 'bulk-ids') . "' onclick='return confirm(\"Are you sure?\");' >Delete</a>";
        $edit.= $this->row_actions($actions);*/

        $device = "
            Platform: {$row->platform}<br>
            Model: {$row->model}<br>
            Manufacturer: {$row->manufacturer}<br>
            Version: {$row->version}<br>
            <!-- Push ID: {$row->push_id} -->
        ";
        $status = AH()->get_active($row->status);
        $user = app_user_card($row->user_id);

        $checkbox = "<input type='checkbox' name='ids[]' value='{$row->ID}' />";

        $r = "<tr id='user-$row->ID'>";

        list($columns, $hidden) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $class = 'class="'.$column_name.' column-'.$column_name.'"';

            $style = '';
            if (in_array($column_name, $hidden)) $style='style="display:none;"';

            if(!$row->status) $style='style="background:#ffa9a0;"';

            $atts = "$class $style";

            switch ($column_name) {
                // case 'cb':$r.= "<th class='check-column' $style>$checkbox</th>";break;
                case 'player': $r.= "<td $atts>$edit</td>"; break;
                case 'device': $r.= "<td $atts>$device</td>"; break;
                case 'user': $r.= "<td $atts>$user</td>"; break;
                case 'dates': $r.= "<td $atts>$dates</td>"; break;
                // case 'status': $r.= "<td $atts>$status</td>"; break;
            }
        }
        $r.= '</tr>';

        return $r;
    }
}
