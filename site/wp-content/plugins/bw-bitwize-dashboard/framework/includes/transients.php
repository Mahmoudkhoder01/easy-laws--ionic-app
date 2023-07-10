<?php

class BW_Transitents {

	protected static $_instance = null;
	public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

	public function init(){
		add_filter( 'cron_schedules', array($this, 'cron_add_weekly') );
		add_action('bw_clear_expired_transients', array($this, 'clearExpired'));
		add_action('bw_clear_shop_transients', array($this, 'clear_woo'));
		add_action('wp_ajax_bw_get_transients_count', array($this, 'ajax_count'));

    	add_action('admin_init', function(){

    		if(!wp_next_scheduled('bw_clear_expired_transients')){
				wp_schedule_event( time(), 'weekly', 'bw_clear_expired_transients' );
			}

			if ( class_exists( 'WooCommerce' ) ){
				if(!wp_next_scheduled('bw_clear_shop_transients')){
					wp_schedule_event( time(), 'threedays', 'bw_clear_shop_transients' );
				}
			}

	    	if(is_admin()){
                if(defined('DOING_AJAX') || defined('DOING_CRON')) return;
                if(!current_user_can('can_bitwize')) return;
	    		if(isset($_GET['bw_transients'])){
	    			if($_GET['bw_transients'] == 'clear_expired'){
	    				$this->clearExpired();
	    			}
	    			if($_GET['bw_transients'] == 'clear_all'){
	    				$this->clearAll();
	    			}
	    			if($_GET['bw_transients'] == 'clear_woo'){
	    				$this->clear_woo();
	    			}
	    		}
	    	}
	    });
    }

    public function cron_add_weekly( $schedules ) {
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __( 'Once Weekly' )
        );
        $schedules['threedays'] = array(
            'interval' => 3 * DAY_IN_SECONDS,
            'display' => __( 'Once every 3 days' )
        );
        return $schedules;
    }

    public function ajax_count(){
    	$all = $this->count();
        $count = "All: {$all->total}, Expired: {$all->expired}, Never Expire: {$all->never_expire}";
        echo $count;
        die();
    }

    public function clear_woo(){
    	if ( class_exists( 'WooCommerce' ) ){
    		if( function_exists('wc_delete_product_transients') ) wc_delete_product_transients();
			if( function_exists('wc_delete_shop_order_transients') ) wc_delete_shop_order_transients();
			if( class_exists('WC_Cache_Helper') ) WC_Cache_Helper::get_transient_version( 'shipping', true );
    	}
    }

	public function count() {
		global $wpdb;
		$threshold = time() - MINUTE_IN_SECONDS;
		$table = $wpdb->prefix . 'options';

		$sql = "
			select count(*) as `total`, count(case when option_value < '$threshold' then 1 end) as `expired`
			from $table
			where (option_name like '\_transient\_timeout\_%' or option_name like '\_site\_transient\_timeout\_%')
		";
		$counts = $wpdb->get_row($sql);

		$sql = "
			select count(*)
			from $table
			where (option_name like '\_transient\_%' or option_name like '\_site\_transient\_%')
			and option_name not like '%\_timeout\_%'
			and autoload = 'yes'
		";
		$counts->never_expire = $wpdb->get_var($sql);

		return $counts;
	}

	public function clearExpired() {
		global $wpdb;
		$threshold = time() - MINUTE_IN_SECONDS;
		$table = $wpdb->prefix . 'options';

		$sql = "
			delete from t1, t2
			using $table t1
			join $table t2 on t2.option_name = replace(t1.option_name, '_timeout', '')
			where (t1.option_name like '\_transient\_timeout\_%' or t1.option_name like '\_site\_transient\_timeout\_%')
			and t1.option_value < '$threshold'
		";
		$wpdb->query($sql);

		$sql = "
			delete from $table
			where (
				   option_name like '\_transient\_timeout\_%'
				or option_name like '\_site\_transient\_timeout\_%'
			)
			and option_value < '$threshold'
		";
		$wpdb->query($sql);
	}

	public function clearAll() {
		global $wpdb;
		$table = $wpdb->prefix . 'options';
		$sql = "
			delete from $table
			where option_name like '\_transient\_%'
			or    option_name like '\_site\_transient\_%'
		";
		$wpdb->query($sql);
	}
}

function BW_Transitents() {return BW_Transitents::instance();}
BW_Transitents()->init();
