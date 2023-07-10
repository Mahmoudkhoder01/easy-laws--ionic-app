<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class App_Admin_Page_Search_Extended {

	public function __construct() {
		$this->singular = 'Search Dump';
		$this->plural = 'Search Dump';
		$this->slug = 'app-search-extended';
		$this->table_name  = DB()->prefix.'app_search_dump';
		$this->action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : '';
		$this->link = 'admin.php?page='.$this->slug;

        add_action('app_admin_menu_bottom', array($this, 'menu'), 16);

        add_action('plugins_loaded', function(){
            if(AH()->req('page') == $this->slug && AH()->req('export')){
                $this->export();
            }
        });
	}

	public function menu(){
		$p = add_menu_page($this->plural, $this->plural, 'manage_options', $this->slug, array($this, 'display'), '', '2.0203');
    }

    function year_options($sel = ''){
        if($sel) $sel = explode('-', $sel)[0];
        $o = '';
        $min_year = 2018;
        $year = date('Y');
        for($i = $min_year; $i < ($year+1); $i++){
			$o.= '<option value="'.$i.'" '.selected($i,$sel,false).'>'.$i.'</option>';
        }
        return $o;
    }

    function month_options($sel = ''){
        if($sel) $sel = explode('-', $sel)[1];
        $o = '';
        for( $i = 1; $i < 13; $i++ ) {
            $month = sprintf('%02d',$i);
            $date = date('Y').'-'.$month;
            $month_name = date( 'F', strtotime($date.'-01') );
            $o.= '<option value="'.$month.'" '.selected($month,$sel,false).'>'.$month_name.'</option>';
        }
        return $o;
    }

    function get_user_name($ID){
        $t = DB()->prefix.'app_users';
		return DB()->get_var("SELECT `name` FROM $t WHERE ID={$ID}");
    }

    function export(){
        $y = AH()->req('year');
        $m = AH()->req('month');
        $y = $y ? $y : date('Y');
        $m = $m ? $m : date('m');
        $month = "$y-$m";
        $date = "$y-$m-01";
        $month_name = date('F Y', strtotime($date));

        $start = strtotime($date." 00:00:00");
        $end = strtotime( date('Y-m-t', strtotime($date))." 23:59:59" );

        $vs = DB()->get_results("SELECT * FROM {$this->table_name} WHERE `has_results`=1 AND `date_created`>$start AND `date_created`<$end ORDER BY `date_created` DESC", ARRAY_A);
        $ivs = DB()->get_results("SELECT * FROM {$this->table_name} WHERE `has_results`=0 AND `date_created`>$start AND `date_created`<$end ORDER BY `date_created` DESC", ARRAY_A);

        $isv = AH()->req('valid') == '1' ? true : false;
        $name = $isv ? 'search-dump-valid' : 'search-dump-invalid';
        $name = "$y-$m-$name";
        $arr = $isv ? $vs : $ivs;
        $arr = array_map(function($item){
            $item['userid'] = $item['user_id'];
            $item['user'] = $this->get_user_name($item['user_id']);
            $item['date'] = date('d-m-Y H:i', $item['date_created']);
            unset($item['user_id']);
            unset($item['ID']);
            unset($item['date_created']);
            unset($item['has_results']);
            return $item;
        }, $arr);

        ob_start();
        $out = fopen("php://output", 'w');
        fputcsv($out, ['Keyword', 'Count', 'User ID', 'User', 'date']);
        foreach ($arr as $data) {
            fputcsv($out, $data);
        }
        $string = ob_get_clean();
        fclose($out);
        header('Content-Encoding: UTF-8');
        header("Content-Disposition: attachment; filename=\"$name.csv\"");
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/csv; charset=UTF-8');
        // header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        exit($string);
    }

	public function display(){
        $y = AH()->req('year');
        $m = AH()->req('month');
        $y = $y ? $y : date('Y');
        $m = $m ? $m : date('m');
        $month = "$y-$m";
        $date = "$y-$m-01";
        $month_name = date('F Y', strtotime($date));

        $start = strtotime($date." 00:00:00");
        $end = strtotime( date('Y-m-t', strtotime($date))." 23:59:59" );

        $vs = DB()->get_results("SELECT * FROM {$this->table_name} WHERE `has_results`=1 AND `date_created`>$start AND `date_created`<$end ORDER BY `date_created` DESC");
        $ivs = DB()->get_results("SELECT * FROM {$this->table_name} WHERE `has_results`=0 AND `date_created`>$start AND `date_created`<$end ORDER BY `date_created` DESC");

        $valid = $invalid = '';
        if($vs){
            foreach($vs as $v){
                $user = $v->user_id ? app_user_card($v->user_id) : '---';
                $date = date('j-n-y H:i', $v->date_created);
                $valid .= "<tr><td>{$v->keyword}</td><td>{$v->count}</td><td>{$user}</td><td>{$date}</td><td>{$v->app_version}</td></tr>";
            }
        } else {
            $valid .= "<tr><td colspan='5'>No valid search for this period</td></tr>";
        }

        if($ivs){
            foreach($ivs as $v){
                $user = $v->user_id ? app_user_card($v->user_id) : '---';
                $date = date('j-n-y H:i', $v->date_created);
                $invalid .= "<tr><td>{$v->keyword}</td><td>{$v->count}</td><td>{$user}</td><td>{$date}</td><td>{$v->app_version}</td></tr>";
            }
        } else {
            $invalid .= "<tr><td colspan='5'>No invalid search for this period</td></tr>";
        }

        $url = 'admin.php?'.$_SERVER['QUERY_STRING'];

        echo '
            <div class="wrap">
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-6">
                        <h2>'.$this->plural.' for <b>'.$month_name.'</b></h2>
                    </div>
                    <div class="col-md-6">
                        <form action="" method="GET">
                            <div class="pull-right" style="padding-top: 20px;">
                            <input type="hidden" name="page" value="'.$this->slug.'" />
                            <select name="month">'.$this->month_options($month).'</select>
                            <select name="year">'.$this->year_options($month).'</select>
                            <button type="submit" class="btn btn-primary">Change Date</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            Valid Searches
                            <a class="pull-right" href="'.$url.'&export=1&valid=1">Export</a>
                        </div>
                        <table class="table table-striped">
                            <thead><th>Keyword</th><th>#</th><th>User</th><th>Date</th><th>Version</th></thead>
                            <tbody>'.$valid.'</tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-danger">
                            Invalid Searches
                            <a class="pull-right" href="'.$url.'&export=1&valid=0">Export</a>
                        </div>
                        <table class="table table-striped">
                            <thead><th>Keyword</th><th>#</th><th>User</th><th>Date</th><th>Version</th></thead>
                            <tbody>'.$invalid.'</tbody>
                        </table>
                    </div>
                </div>
            </div>
        ';
	}

}

new App_Admin_Page_Search_Extended;
