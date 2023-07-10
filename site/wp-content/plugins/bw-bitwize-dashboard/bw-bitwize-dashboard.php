<?php
/*
Plugin Name: BW Bitwize Dashboard
Plugin URI: http://bitwize.com.lb/
Description: Discover, install and update all Bitwize plugins from a single place
Version: 8.9.4.11
Author: Bitwize &trade;
Author URI: http://bitwize.com.lb/
Category: Plugins
Changelog: ss-api;JSON API;Redux Repeater;Block Known Bots & Bad referrals;Orders Auto-Complete;SMTP;Woo sales price fix;Avatar uses holder.js;Permalinks filters;Fix spam errors;Enhanced Security
*/

if ( ! defined( 'ABSPATH' ) ) exit;

final class Bitwize_Core
{
    protected static $_instance = null;
    public $name = 'Bitwize Dashboard';
    public $slug = 'bw-bitwize-dashboard';
    public $td = 'BW_TD';

	public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

	public function __clone() {_doing_it_wrong(__FUNCTION__, 'No Monkey Business Here O_o', $this->version);}
    public function __sleep() {_doing_it_wrong(__FUNCTION__, 'No Monkey Business Here O_o', $this->version);}
    public function __wakeup() {_doing_it_wrong(__FUNCTION__, 'No Monkey Business Here O_o', $this->version);}
    public function __construct() {}

    private function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	public function init(){
        do_action('before_bitwize_init');
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        do_action('bitwize_init');
    }

    private function init_hooks() {
    	register_activation_hook(__FILE__, array($this, 'activate'));
    	register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    	add_action('init', array($this, 'load_dashboard'));
    	add_action('init', array($this, 'load_plugin_textdomain'), 0);
    }

    public function load_dashboard(){
    	define('BWD_END_POINT', apply_filters('bwd_end_point', 'http://api.sellandsell.com'));
		if ( $this->is_request('admin') && current_user_can('can_bitwize') ) {
			include dirname(__FILE__).'/framework/dashboard/class-bwd-dashboard.php' ;
			$GLOBALS['BWD_Dashboard'] = new BWD_Dashboard( __FILE__ );
		}
		// include dirname(__FILE__).'/framework/dashboard/class-bwd-tracker.php' ;
    }

    public function activate(){
    	flush_rewrite_rules();
    }

    public function deactivate(){
    	flush_rewrite_rules();
		wp_clear_scheduled_hook( 'bwd_tracker_send_event' );
    }

    private function define_constants() {
        $version = get_file_data( __FILE__, array('Version') );
        $this->define('BITWIZE_CORE_PLUGIN_FILE', __FILE__);
        $this->define('BITWIZE_CORE_PLUGIN_DIR', dirname(__FILE__) );
    	$this->define('BW_TD', 'BW_TD');
    	$this->define('BWD_VERSION', $version[0]);
		$this->define('BWD_URL', plugins_url('',__FILE__));
    }

	private function define($name, $value) {
        if (!defined($name)) define($name, $value);
    }

    public function includes() {
    	$files = array(
            'functions',
            'includes/__init',
            'settings/init',
            'developer/init',
    		'security/init',
    		'extensions/init',
    		'support/init',
    	);
    	foreach($files as $file){
    		require_once dirname(__FILE__).'/framework/'.$file.'.php';
    	}
    }

    public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'BW_TD' );
		load_textdomain( 'BW_TD', WP_LANG_DIR . '/'.$this->slug.'/'.$this->slug.'-' . $locale . '.mo' );
		load_plugin_textdomain( 'BW_TD', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
	}

    public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

    public function debug($a = null){
        if (!is_null($a)) {
            if(is_array($a) || is_object($a)){
                echo '<pre>'; var_dump($a); echo '</pre>';
            }else{
                echo '<h2>DEBUG: '.$a.'</h2>';
            }
        }
    }
}

function BW_Core() {return Bitwize_Core::instance();}
$GLOBALS['bitwize_core'] = BW_Core();
BW_Core()->init();
