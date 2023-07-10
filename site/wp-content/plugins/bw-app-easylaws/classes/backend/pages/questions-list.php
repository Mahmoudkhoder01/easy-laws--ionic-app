<?php

if(!class_exists('WP_List_Table')) require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class App_Questions_List_Table extends WP_List_Table {
	public $base_link;

    public function prepare_items($per_page = 20, $base_link = '') {
    	$this->base_link = $base_link;
        $table = DB()->prefix . 'app_questions';
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $paged = (int) $this->get_pagenum();
        $per_page = (int) $per_page;

        $search = app_rq('s');
        $filter = app_rq('app_filter');
        $filter_ref = app_rq('app_filter_ref');
        $filter_tag = app_rq('app_filter_tag');
        $filter_status = app_rq('app_filter_status');

        $offset = ($paged - 1) * $per_page;
        $join = '';

        $where = 'WHERE 1=1';
        $orderby = 'menu_order';
        $order = 'ASC';

        if($filter){
            $ss = explode(',', $filter);
            foreach($ss as $s){
                $s = intval(trim($s));
                $where .= " AND (FIND_IN_SET({$s}, `categories`)>0)";
            }
            // $where .= " AND (categories LIKE '%{$filter}%')";
        }

        if($filter_ref){
            $fs = explode(',', $filter_ref);
            foreach($fs as $s){
                $s = intval(trim($s));
                $where .= " AND (FIND_IN_SET({$s}, `references`)>0)";
            }
        }

        if($filter_tag){
            $ts = explode(',', $filter_tag);
            foreach($ts as $s){
                $s = intval(trim($s));
                $where .= " AND (FIND_IN_SET({$s}, `tags`)>0)";
            }
        }

        switch($filter_status){
            case 'active':
                $where .= " AND (status=1)";
            break;
            case 'corrected':
                $where .= " AND (status=2)";
            break;
            case 'pending':
                $where .= " AND (status=0)";
            break;
        }

        if(!current_user_can('edit_pages')){
            $where .= " AND author=".get_current_user_id();
        }

        if ($search) $where .= " AND (`ID` LIKE '%$search%' OR `title` LIKE '%$search%' OR `details` LIKE '%$search%')";
        if (isset($_REQUEST['orderby'])) $orderby = $_REQUEST['orderby'];
        if (isset($_REQUEST['order'])) $order = $_REQUEST['order'];

        if (!empty($_REQUEST['app_filter']) && is_numeric($_REQUEST['app_filter'])) {
            // $orderby = 'menu_order';
            // $order = 'ASC';
        }

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
        // return array('delete' => 'Delete');
        return [
            'delete' => 'Delete',
            'set_active' => 'Set Active',
            'set_corrected' => 'Set Corrected',
            'set_pending' => 'Set Pending',
        ];
    }

    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Question',
            'status' => 'AR',
            'status_en' => 'EN',
            'status_fr' => 'FR',
            'categories' => 'Subjects',
            'dyk' => 'DYK',
            'stats' => 'Stats',
            'interactions' => 'Interactions',
            'media' => 'Media',
            'author' => 'Author',
            'menu_order' => 'Order',
            'created' => 'Created',
            'edited' => 'Edited',
        );
    }

    protected function get_hidden_columns(){
        return array();
    }

    protected function get_sortable_columns() {
        return array(
            'number' => array('ID', true),
            'title' => array('title', true),
            'status' => array('status', true),
            'status_en' => array('status_en', true),
            'status_fr' => array('status_fr', true),
            'dyk' => array('did_you_know', true),
            'author' => array('author', true),
            'menu_order' => array('menu_order', true),
            'created' => array('date_created', true),
            'edited' => array('date_edited', true),
        );
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        $filter = app_rq('app_filter');
        $filter_ref = app_rq('app_filter_ref');
        $filter_tag = app_rq('app_filter_tag');
        $filter_status = app_rq('app_filter_status');
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

            <div class="actions_tree">
            <label>Refs</label>
            <?php
                echo app_input_tree(array(
                    'section' => 'references',
                    'name' => 'app_filter_ref',
                    'value' => $filter_ref,
                ));
            ?>
            </div>

            <select name="app_filter_status" id="app_filter_status">
                <option value="">--Status--</option>
                <option value="active" <?php selected($filter_status, 'active');?>>Active</option>
                <option value="corrected" <?php selected($filter_status, 'corrected');?>>Corrected</option>
                <option value="pending" <?php selected($filter_status, 'pending');?>>Pending</option>
            </select>
        <?php
            submit_button( __( 'Filter' ), 'primary', 'filter_submit', false );

            if($filter || $filter_ref || $filter_tag || $filter_status){
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
        $created = "$created<BR>$logs";

        $status = get_status_icons($row->status);
        $status_en = get_status_icons($row->status_en);
        $status_fr = get_status_icons($row->status_fr);
        $dyk = get_status_icons($row->did_you_know ? 1 : 0);
        $cats = subjects_by_field($row->categories);

        $videos = ($row->videos) ? count(explode(',', $row->videos)) : '0';
        $images = ($row->images) ? count(explode(',', $row->images)) : '0';
        $media = "{$videos} Videos<br>{$images} Pictures";

        $stats = "
            Examples: ".app_repeat_count($row->examples)."<br>
            Notes: ".app_repeat_count($row->notes)."<br>
            Links: ".app_repeat_count($row->links)."<br>
        ";

        $views = number_shorten($row->views, 0);
        $comments_link = $row->comments ? "<a href='admin.php?page=app-comments&s={$row->ID}'><i class='fa fa-link'></i></a>" : '';
        $interactions = "
            UP Votes: {$row->votes_up}<br>
            DOWN Votes: {$row->votes_down}<br>
            Likes: {$row->likes}<br>
            Comments: {$row->comments} {$comments_link}<br>
            Views: {$views}
        ";

        $author = $row->author ? get_userdata($row->author)->display_name : '---';

        $checkbox = '';

        $link_edit = add_query_arg(['action' => 'edit', 'id' => $row->ID,], $this->base_link);
        $link_del = add_query_arg(['action' => 'delete', 'id' => $row->ID], $this->base_link);
        $link_untrash = add_query_arg(['action' => 'untrash', 'id' => $row->ID], $this->base_link);
        $link_forcetrash = add_query_arg(['action' => 'forcetrash', 'id' => $row->ID], $this->base_link);

        $actions = array();

        $edit = "<a href=\"$link_edit\"><strong>$row->title</strong></a>";
        $actions['edit'] = '<a href="'.$link_edit.'">Edit</a>';
        if($row->trashed){
            $actions['untrash'] = "<a href='" . $link_untrash . "'>Un Trash</a>";
            $actions['delete'] = "<a href='" . $link_forcetrash . "' onclick='return confirm(\"Are you sure?\");' >Force Delete</a>";
        } else {
            $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url($link_del, 'bulk-ids') . "' onclick='return confirm(\"Are you sure?\");' >Trash</a>";
        }

        $actions['ID'] = "ID:{$row->ID}";

        $edit.= $this->row_actions($actions);

        $checkbox = "<input type='checkbox' name='ids[]' value='{$row->ID}' />";

        $r = "<tr id='item-$row->ID'>";

        list($columns, $hidden) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $class = 'class="'.$column_name.' column-'.$column_name.'"';

            $style = '';
            if (in_array($column_name, $hidden)) $style='style="display:none;"';
            if($row->trashed) $style='style="background:#ff8f84;"';

            $atts = "$class $style";

            switch ($column_name) {
                case 'cb':$r.= "<th class='check-column'>$checkbox</th>";break;
                case 'title': $r.= "<td $atts>$edit</td>"; break;
                case 'status': $r.= "<td $atts>$status</td>"; break;
                case 'status_en': $r.= "<td $atts>$status_en</td>"; break;
                case 'status_fr': $r.= "<td $atts>$status_fr</td>"; break;
                case 'categories': $r.= "<td $atts>{$cats}</td>"; break;
                case 'dyk': $r.= "<td $atts>$dyk</td>"; break;
                case 'stats': $r.= "<td $atts>$stats</td>"; break;
                case 'interactions': $r.= "<td $atts>$interactions</td>"; break;
                case 'media': $r.= "<td $atts>$media</td>"; break;
                case 'author': $r.= "<td $atts>$author</td>"; break;
                case 'menu_order': $r.= "<td $atts>$row->menu_order</td>"; break;
                case 'created': $r.= "<td $atts>$created</td>"; break;
                case 'edited': $r.= "<td $atts>$edited</td>"; break;
                default: $r.= "<td $atts>".apply_filters('manage_epay_pcodes_custom_column', '', $column_name, $row->ID)."</td>";
            }
        }
        $r.= '</tr>';

        return $r;
    }
}
