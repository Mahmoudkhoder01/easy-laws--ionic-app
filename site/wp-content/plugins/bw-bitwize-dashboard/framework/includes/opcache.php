<?php
class BWD_OPCache{

	public function __construct(){
		define('BWDCACHEPREFIX', function_exists('opcache_reset') ? 'opcache_' : (function_exists('accelerator_reset') ? 'accelerator_' : ''));

		if( function_exists(BWDCACHEPREFIX . 'get_status') ){
			add_action('admin_menu', array($this, 'menu'));
			add_action( 'admin_bar_menu',  array($this, 'admin_bar'), 100 );
		}
	}

	function url($args = null){
		$base = admin_url('admin.php?page=bwd_opcache');
		if($args){
			return add_query_arg($args, $base);
		} else {
			return $base;
		}
	}

	function menu(){
		add_submenu_page(NULL, 'OPCache', 'OPCache', 'can_bitwize', 'bwd_opcache', array($this, 'page'));
	}

	public function admin_bar(){
		global $wp_admin_bar;
		$wp_admin_bar->add_menu( array(
            'parent' => 'bw-right-admin',
            'id' => 'bw-opcache',
            'title' => 'OPCache',
            'href' => admin_url('admin.php?page=bwd_opcache')
        ) );
	}

	function page(){
		$this->style();
		echo '<div class="wrap" style="text-align: center;">';
			echo '<h2>Opcache Control Panel</h2>';
			echo '
				<div style="padding: 30px 0;">
					<a href="'.$this->url(array('RESET' => 1)).'" class="button" onclick="return confirm(\'RESET cache ?\')">Reset</a>
					<a href="'.$this->url(array('RECHECK' => 1)).'" class="button" onclick="return confirm(\'Recheck all files in the cache ?\')">Recheck</a>
				</div>
			';
			$this->graphs_display();
			$this->perform();
		echo '</div>'; // END WRAP
	}

	function perform(){
		// RESET
		if (!empty($_GET['RESET'])) {
		    if (function_exists(BWDCACHEPREFIX . 'reset')) {call_user_func(BWDCACHEPREFIX . 'reset');}
		    wp_redirect($this->url());
		    exit;
		}
		// RECHECK
		if (!empty($_GET['RECHECK'])) {
		    if (function_exists(BWDCACHEPREFIX . 'invalidate')) {
		        $recheck = trim($_GET['RECHECK']);
		        $files   = call_user_func(BWDCACHEPREFIX . 'get_status');
		        if (!empty($files['scripts'])) {
		            foreach ($files['scripts'] as $file => $value) {
		                if ($recheck === '1' || strpos($file, $recheck) === 0) {
		                    call_user_func(BWDCACHEPREFIX . 'invalidate', $file);
		                }

		            }
		        }
		        wp_redirect($this->url());
		    } else {echo 'Sorry, this feature requires Zend Opcache newer than April 8th 2013';}
		    exit;
		}
	}

	function graphs_display() {
	    $graphs                     = array();
	    $colors                     = array('green', 'brown', 'red');
	    $primes                     = array(223, 463, 983, 1979, 3907, 7963, 16229, 32531, 65407, 130987);
	    $configuration              = call_user_func(BWDCACHEPREFIX . 'get_configuration');
	    $status                     = call_user_func(BWDCACHEPREFIX . 'get_status');
	    $graphs['memory']['total']  = $configuration['directives']['opcache.memory_consumption'];
	    $graphs['memory']['free']   = $status['memory_usage']['free_memory'];
	    $graphs['memory']['used']   = $status['memory_usage']['used_memory'];
	    $graphs['memory']['wasted'] = $status['memory_usage']['wasted_memory'];
	    $graphs['keys']['total']    = $status[BWDCACHEPREFIX . 'statistics']['max_cached_keys'];
	    foreach ($primes as $prime) {
	        if ($prime >= $graphs['keys']['total']) {$graphs['keys']['total'] = $prime;
	            break;}}
	    $graphs['keys']['free']       = $graphs['keys']['total'] - $status[BWDCACHEPREFIX . 'statistics']['num_cached_keys'];
	    $graphs['keys']['scripts']    = $status[BWDCACHEPREFIX . 'statistics']['num_cached_scripts'];
	    $graphs['keys']['wasted']     = $status[BWDCACHEPREFIX . 'statistics']['num_cached_keys'] - $status[BWDCACHEPREFIX . 'statistics']['num_cached_scripts'];
	    $graphs['hits']['total']      = 0;
	    $graphs['hits']['hits']       = $status[BWDCACHEPREFIX . 'statistics']['hits'];
	    $graphs['hits']['misses']     = $status[BWDCACHEPREFIX . 'statistics']['misses'];
	    $graphs['hits']['blacklist']  = $status[BWDCACHEPREFIX . 'statistics']['blacklist_misses'];
	    $graphs['hits']['total']      = array_sum($graphs['hits']);
	    $graphs['restarts']['total']  = 0;
	    $graphs['restarts']['manual'] = $status[BWDCACHEPREFIX . 'statistics']['manual_restarts'];
	    $graphs['restarts']['keys']   = $status[BWDCACHEPREFIX . 'statistics']['hash_restarts'];
	    $graphs['restarts']['memory'] = $status[BWDCACHEPREFIX . 'statistics']['oom_restarts'];
	    $graphs['restarts']['total']  = array_sum($graphs['restarts']);
	    foreach ($graphs as $caption => $graph) {
	        echo '<div class="graph"><div class="h">', $caption, '</div><table border="0" cellpadding="0" cellspacing="0">';
	        foreach ($graph as $label => $value) {
	            if ($label == 'total') {
	                $key          = 0;
	                $total        = $value;
	                $totaldisplay = '<td rowspan="3" class="total"><span>' . ($total > 999999 ? round($total / 1024 / 1024) . 'M' : ($total > 9999 ? round($total / 1024) . 'K' : $total)) . '</span><div></div></td>';
	                continue;}
	            $percent = $total ? floor($value * 100 / $total) : '';
	            $percent = !$percent || $percent > 99 ? '' : $percent . '%';
	            echo '<tr>', $totaldisplay, '<td class="actual">', ($value > 999999 ? round($value / 1024 / 1024) . 'M' : ($value > 9999 ? round($value / 1024) . 'K' : $value)), '</td><td class="bar ', $colors[$key], '" height="', $percent, '">', $percent, '</td><td>', $label, '</td></tr>';
	            $key++;
	            $totaldisplay = '';
	        }
	        echo '</table></div>', "\n";
	    }
	}

	function style(){
		echo '
		<style type="text/css">
			table {border-collapse: collapse; width: 600px; }
			.center {text-align: center;}
			.center table { margin-left: auto; margin-right: auto; text-align: left;}
			.center th { text-align: center !important; }
			.middle {vertical-align:middle;}
			.p {text-align: left;}
			.e {background-color: #ccccff; font-weight: bold; color: #000; width:50%; white-space:nowrap;}
			.h {background-color: #535c69; font-weight: bold; color: #eef2f4; text-transform: capitalize; padding: 5px 0;}
			.v {background-color: #cccccc; color: #000;}
			.vr {background-color: #cccccc; text-align: right; color: #000; white-space: nowrap;}
			.b {font-weight:bold;}
			.white, .white a {color:#fff;}
			.graph {display:inline-block; width:145px; margin:1em 0 1em 1px; border:0; vertical-align:top;}
			.graph table {width:100%; height:150px; border:0; padding:0; margin:5px 0 0 0; position:relative;}
			.graph td {vertical-align:middle; border:0; padding:0 0 0 5px;}
			.graph .bar {width:25px; text-align:right; padding:0 2px; color:#fff;}
			.graph .total {width:34px; text-align:center; padding:0 5px 0 0;}
			.graph .total div {border:1px dashed #888; border-right:0; height:99%; width:12px; position:absolute; bottom:0; left:17px; z-index:-1;}
			.graph .total span {background:#fff; font-weight:bold;}
			.graph .actual {text-align:right; font-weight:bold; padding:0 5px 0 0;}
			.graph .red {background:#ee0000;}
			.graph .green {background:#00cc00;}
			.graph .brown {background:#8B4513;}
		</style>
		';
	}
}
$GLOBALS['BWD_OPCache'] = new BWD_OPCache;
