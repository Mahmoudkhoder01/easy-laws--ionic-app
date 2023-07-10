<?php

if(!class_exists('WP_List_Table')) require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class App_Users_List_Table extends WP_List_Table {
	public $base_link;

    public function prepare_items($per_page = 20, $base_link = '') {
    	$this->base_link = $base_link;
        $table = DB()->prefix . 'app_users';
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

        $filter_admin = app_rq('app_filter_admin');
        if ($filter_admin != '') {
            $st = $filter_admin == 'yes' ? 1 : 0;
            $where .= " AND (is_admin={$st})";
        }

        $s = app_rq('s');
        if ($s) $where .= " AND (`ID` LIKE '%$s%' OR `name` LIKE '%$s%' OR `email` LIKE '%$s%' OR `phone` LIKE '%$s%')";

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
        return [
        	'approve' => 'Approve',
        	'unapprove' => 'Un Approve',
        	'delete' => 'Delete'
        ];
    }

    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'name' => 'Name',
            'id' => 'ID',
            'is_admin' => 'Admin?',
            'email' => 'Email',
            'phone' => 'Phone',
            'dob' => 'DOB',
            'stats' => 'Stats',
            'dates' => 'Dates',
            'date_active' => 'Last Active'
        );
    }

    protected function get_hidden_columns(){ return array(); }

    protected function get_sortable_columns() {
        return array(
            'name' => array('name', true),
            'id' => array('ID', true),
            'question' => array('question_id', true),
            'dob' => array('dob', true),
            'dates' => array('date_created', true),
            'date_active' => array('date_active', true),
        );
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        $filter_status = app_rq('app_filter_status');
        $filter_admin = app_rq('app_filter_admin');
        echo '<div class="alignleft actions">';
        ?>
        	<select name="app_filter_status" id="app_filter_status">
                <option value="">--Status--</option>
                <option value="yes" <?php selected($filter_status, 'yes');?>>Approved</option>
                <option value="no" <?php selected($filter_status, 'no');?>>Un Approved</option>
            </select>
            <select name="app_filter_admin" id="app_filter_admin">
                <option value="">--Is Admin--</option>
                <option value="yes" <?php selected($filter_admin, 'yes');?>>Only Admins</option>
                <option value="no" <?php selected($filter_admin, 'no');?>>Not Admins</option>
            </select>
        <?php
        	submit_button( __( 'Filter' ), 'primary', 'filter_submit', false );

            if($filter_status != '' || $filter_admin != ''){
                echo '&nbsp;<a href="admin.php?page=app-users" class="button" style="display:inline-block;margin-top:0;">Clear</a>';
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
        $last_login = ($row->last_login) ? date("d-M-y", $row->last_login) : '---';
        $dates = "Created: $created<br>Edited: $edited<br>Last Login: $last_login";

        $date_active = ($row->date_active) ? date("d-M-y H:i", $row->date_active) : '---';

        $checkbox = '';

        $link_approve = add_query_arg(['action' => 'approve', 'id' => $row->ID], $this->base_link);
        $link_unapprove = add_query_arg(['action' => 'unapprove', 'id' => $row->ID], $this->base_link);
        $link_admin = add_query_arg(['action' => 'admin', 'id' => $row->ID], $this->base_link);
        $link_unadmin = add_query_arg(['action' => 'unadmin', 'id' => $row->ID], $this->base_link);
        $link_del = add_query_arg(['action' => 'delete', 'id' => $row->ID], $this->base_link);

        $actions = array();

        $icon_gender = (strtolower($row->gender) == 'female') ? 'venus' : 'mars';
        $icon = '<i class="fa fa-'.$icon_gender.'" style="margin-right: 5px;"></i> ';

        $img = $row->image ? __CORS($row->image) : bwd_avatar()->get_img($row->name, 32);
        $img = '<img src="'.$img.'" width="32" height="32" />';

        $edit = "$icon $img <strong>$row->name</strong>";
        // $actions['edit'] = '<a href="'.$link_edit.'">Edit</a>';

        if($row->status){
        	$actions['unapprove'] = "<a href='".$link_unapprove."' onclick='return confirm(\"Are you sure?\");' >Un Approve</a>";
        } else {
        	$actions['unapprove'] = "<a href='".$link_approve."' onclick='return confirm(\"Are you sure?\");' >Approve</a>";
        }

        if($row->is_admin){
            $actions['unadmin'] = "<a href='".$link_unadmin."' onclick='return confirm(\"Are you sure?\");' >Un Admin</a>";
        } else {
            $actions['unadmin'] = "<a href='".$link_admin."' onclick='return confirm(\"Are you sure?\");' >Make Admin</a>";
        }

        $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url($link_del, 'bulk-ids') . "' onclick='return confirm(\"Are you sure?\");' >Delete</a>";
        $edit.= $this->row_actions($actions);

        $votes = DB()->get_var("SELECT COUNT(ID) FROM {$prx}user_votes WHERE user_id={$row->ID}");
        $likes = DB()->get_var("SELECT COUNT(ID) FROM {$prx}user_likes WHERE user_id={$row->ID}");
        // $comments = DB()->get_var("SELECT COUNT(ID) FROM {$prx}question_comments WHERE user_id={$row->ID}");
        $comments_approved = DB()->get_var("SELECT COUNT(ID) FROM {$prx}question_comments WHERE user_id={$row->ID} AND status=1");
        $comments_pending = DB()->get_var("SELECT COUNT(ID) FROM {$prx}question_comments WHERE user_id={$row->ID} AND status=0");

        $stats = "
        	Votes: $votes<br>
        	Likes: $likes<br>
        	Comments:<br>
        	&rarr; $comments_approved Approved<br>
        	&rarr; $comments_pending Pending
        ";

        $dob = ($row->dob && $row->dob != '0000-00-00') ? date('F j, Y', strtotime($row->dob)) : '';
        $age = ($row->dob && $row->dob != '0000-00-00') ? AH()->get_age($row->dob).' years old' : '';
        $admin = AH()->get_active($row->is_admin);

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
                case 'cb':$r.= "<th class='check-column' $style>$checkbox</th>";break;
                case 'name': $r.= "<td $atts>$edit</td>"; break;
                case 'id': $r.= "<td $atts>$row->ID</td>"; break;
                case 'is_admin': $r.= "<td $atts>$admin</td>"; break;
                case 'email': $r.= "<td $atts>$row->email<br>Provider: $row->provider</td>"; break;
                case 'phone': $r.= "<td $atts>$row->phone</td>"; break;
                case 'dob': $r.= "<td $atts>$dob<br>$age</td>"; break;
                case 'stats': $r.= "<td $atts>$stats</td>"; break;
                case 'dates': $r.= "<td $atts>$dates</td>"; break;
                case 'date_active': $r.= "<td $atts>$date_active</td>"; break;
            }
        }
        $r.= '</tr>';

        return $r;
    }
}
