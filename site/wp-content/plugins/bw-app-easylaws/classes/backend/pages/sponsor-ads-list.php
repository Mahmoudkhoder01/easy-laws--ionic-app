<?php

if(!class_exists('WP_List_Table')) require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class App_Sponsor_Ads_List_Table extends WP_List_Table {
	public $base_link;

    public function prepare_items($per_page = 50, $base_link = '') {
    	$this->base_link = $base_link;
        $table = DB()->prefix . 'app_sponsor_ads';
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $paged = (int) $this->get_pagenum();
        $per_page = (int) $per_page;

        $search = app_rq('s');
        $filter = app_rq('app_filter');
        $offset = ($paged - 1) * $per_page;
        $join = '';

        $where = 'WHERE 1=1';
        $orderby = 'start';
        $order = 'DESC';

        if($filter){
            $filter = intval(trim($filter));
            $where .= " AND (sponsor_id={$filter})";
            // $where .= " AND (sponsor_id LIKE '%{$filter}%')";
        }

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
            'ad' => 'Ad',
            'sponsor' => 'Sponsor',
            'sections' => 'Sections',
            'questions' => 'Questions',
            'subjects' => 'Subjects',
            'screens' => 'Screens',
            'duration' => 'Duration',
            '__status' => 'Status',
            'stats' => 'Stats',
            'dates' => 'Dates',
        );
    }

    protected function get_hidden_columns(){
        return array();
    }

    protected function get_sortable_columns() {
        return array(
            'ad' => array('title', true),
            'duration' => array('start', true),
            'sponsor' => array('sponsor_id', true),
            'dates' => array('date_created', true),
        );
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;

        $filter = app_rq('app_filter');

        $prx = DB()->prefix.'app_';

        $sponsors = DB()->get_results("SELECT ID,name FROM {$prx}sponsors ORDER BY name ASC");
        $opts = '<option value="">--Sponsor--</option>';
        foreach($sponsors as $s){
            $opts .= '<option value="'.$s->ID.'" '.selected($filter, $s->ID, false).'>'.$s->name.'</option>';
        }

        echo '<div class="alignleft actions">';
            echo '<select name="app_filter">'.$opts.'</select>';
            
            submit_button( 'Filter', 'primary', 'filter_submit', false );

            if($filter){
                echo '&nbsp;<a href="admin.php?page=app-campaigns" class="button" style="display:inline-block;margin-top:0;">Clear</a>';
            }
        echo '</div>';
    }

    public function display_rows() {
        foreach ($this->items as $k => $v) {
            echo "\n\t" . $this->single_row($v);
        }
    }

    function campaign_status($row){
        $time = time();
        if($row->active){
            if($time >= $row->start && $time <= $row->end){
                return 'Active';
            } elseif($time > $row->end){
                return 'Expired';
            } elseif($time < $row->start){
                return 'Not Started';
            }
        }
        return 'Not Active';
    }

    public function single_row($row) {
        $prx = DB()->prefix.'app_';
        $created = ($row->date_created) ? date("d-M-y", $row->date_created) : '---';
        $edited = ($row->date_edited) ? date("d-M-y", $row->date_edited) : '---';
        $dates = "Created:<br>$created<br>Edited:<br>$edited";

        $checkbox = '';

        $link_edit = add_query_arg(['action' => 'edit','id' => $row->ID], $this->base_link);
        $link_del = add_query_arg(['action' => 'delete', 'id' => $row->ID], $this->base_link);

        $img = $row->image ? wp_get_attachment_image_url($row->image, 'full') : '';
        $img = $img ? "<a href='$img' target='_blank'><img src='$img' width='100' /></a>" : '';

        $link = $row->link ? '<br><br><a href="'.$row->link.'" target="_blank">(Preview Link)</a>' : '';

        $actions = [];
        $edit = "
            <a href=\"$link_edit\"><strong>$row->title</strong></a><br>
            $img $link
        ";
        $actions['edit'] = '<a href="'.$link_edit.'">Edit</a>';
        $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url($link_del, 'bulk-ids') . "' onclick='return confirm(\"Are you sure?\");' >Delete</a>";
        $edit.= $this->row_actions($actions);

        $sponsor = $row->sponsor_id ? DB()->get_var("SELECT `name` FROM {$prx}sponsors WHERE ID={$row->sponsor_id}") : '---';

        $start = date('d M y', $row->start);
        $end = date('d M y', $row->end);
        $duration = "$start <br><i class='fa fa-arrow-down' style='margin-left: 20px;'></i><br> $end";

        $status = $this->campaign_status($row);
        $stats = "Imp.: $row->impressions<br>Clicks: $row->clicks";
        $sections = $row->sections ? implode('<br>', explode(',', $row->sections)) : '---';
        $screens = $row->screens ? implode('<br>', explode(',', $row->screens)) : '---';
        $questions = $row->questions ? implode('<br>', explode(',', $row->questions)) : '---';

        $__subjects = $row->subjects ? explode(',', $row->subjects) : [];
        $subjects = array_map(function($item){
            $t = DB()->prefix.'app_subjects';
            return DB()->get_var("SELECT title FROM $t WHERE ID=$item");
        }, $__subjects);
        $subjects = $subjects ? implode('<br>', $subjects) : '---';

        $checkbox = "<input type='checkbox' name='ids[]' value='{$row->ID}' />";

        $r = "<tr id='user-$row->ID'>";

        list($columns, $hidden) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {

            switch ($column_name) {
                case 'cb':$r.= "<th class='check-column'>$checkbox</th>";break;
                case 'ad': $r.= "<td>$edit</td>"; break;
                case 'sponsor': $r.= "<td>$sponsor</td>"; break;
                case 'sections': $r.= "<td>$sections</td>"; break;
                case 'questions': $r.= "<td>$questions</td>"; break;
                case 'subjects': $r.= "<td>$subjects</td>"; break;
                case 'screens': $r.= "<td>$screens</td>"; break;

                case 'duration': $r.= "<td>$duration</td>"; break;
                case '__status': $r.= "<td>$status</td>"; break;
                case 'stats': $r.= "<td>$stats</td>"; break;

                case 'dates': $r.= "<td>$dates</td>"; break;
            }
        }
        $r.= '</tr>';

        return $r;
    }
}
