<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class App_Admin_Page_Search_Report {

	public function __construct() {
		$this->singular = 'Search Report';
		$this->plural = 'Search Report';
		$this->slug = 'app-search-report';
		$this->table_name  = DB()->prefix.'app_search_log';
		$this->action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : '';
		$this->link = 'admin.php?page='.$this->slug;

		add_action('app_admin_menu_bottom', array($this, 'menu'), 16);
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

	public function display(){
        $y = AH()->req('year');
        $m = AH()->req('month');
        $month = ($y && $m) ? "$y-$m" : date('Y-m');
        $month_name = date('F Y', strtotime($month.'-01'));
        $vs = DB()->get_results("SELECT * FROM {$this->table_name} WHERE `has_results`=1 AND `month`='{$month}' ORDER BY `count` DESC");
        $ivs = DB()->get_results("SELECT * FROM {$this->table_name} WHERE `has_results`=0 AND `month`='{$month}' ORDER BY `count` DESC");

        $valid = $invalid = '';
        if($vs){
            foreach($vs as $v){
                $valid .= "<tr><td>{$v->count}</td><td>{$v->keyword}</td></tr>";
            }
        } else {
            $valid .= "<tr><td colspan='2'>No valid search for this period</td></tr>";
        }

        if($ivs){
            foreach($ivs as $v){
                $invalid .= "<tr><td>{$v->count}</td><td>{$v->keyword}</td></tr>";
            }
        } else {
            $invalid .= "<tr><td colspan='2'>No invalid search for this period</td></tr>";
        }

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
                        <div class="alert alert-success">Valid Searches</div>
                        <table class="table table-striped">
                            <thead><th>#</th><th>Keyword</th></thead>
                            <tbody>'.$valid.'</tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-danger">Invalid Searches</div>
                        <table class="table table-striped">
                            <thead><th>#</th><th>Keyword</th></thead>
                            <tbody>'.$invalid.'</tbody>
                        </table>
                    </div>
                </div>
            </div>
        ';
	}

}

new App_Admin_Page_Search_Report;
