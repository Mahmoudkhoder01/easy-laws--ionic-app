<?php
/**
 * Plugin Name: BW APP Easylaws
 * Description: Easy Laws Application
 * Author: Bitwize
 * Author URI: http://bitwize.com.lb
 * Version: 0.0.17
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function DB() { global $wpdb; return $wpdb; }

final class Base_App
{
	private static $_instance;
	public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

	public function init(){
		date_default_timezone_set('Asia/Beirut');
		@ini_set('max_input_vars', 9000);
		register_activation_hook(__FILE__, array($this, 'install'));
		$this->setup_constants();
		$this->includes();
		// add_action('plugins_loaded', array($this, 'load_textdomain'));
		add_action('admin_init', array($this, 'upgrade_check'));
	}

	public function __clone() {_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' );}
	public function __wakeup() {_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' );}

	private function setup_constants() {
		$version = get_file_data( __FILE__, array('Version') );
		$this->define( 'APP_VERSION', $version[0] );
		$this->define( 'APP_DB_VERSION', '0.31' );
		$this->define( 'APP_PLUGIN_DIR', dirname( __FILE__ ) );
		$this->define( 'APP_PLUGIN_URL', plugins_url( '', __FILE__ ) );
		$this->define( 'APP_PLUGIN_FILE', __FILE__ );
		$this->define( 'PRX', DB()->prefix.'app_' );
	}

	private function define($k, $v){
		if(!defined($k)) define($k, $v);
	}

	private function includes() {
		require_once APP_PLUGIN_DIR . '/library/__init.php';
		require_once APP_PLUGIN_DIR . '/classes/__init.php';
		require_once APP_PLUGIN_DIR . '/api/__init.php';
		require_once APP_PLUGIN_DIR . '/frontend/__init.php';
	}

	public function install(){
		/*
		$roles = array('author', 'contributor', 'editor', 'subscriber');
		foreach($roles as $role){
			remove_role($role);
		}
		*/
		flush_rewrite_rules();
	}

	public function upgrade_check(){
		$old = get_option('APP_VERSION', 0);
		if($old != APP_VERSION){
			$this->upgrade();
		}
	}

	public function upgrade(){
		// RESET OPCACHE
		if(function_exists('opcache_reset')){
			opcache_reset();
		}

		$this->clear_transients();

		do_action('app_upgrade');
		update_option('APP_VERSION', APP_VERSION);
	}

	public function clear_transients() {
		$t = DB()->prefix . 'options';
		DB()->query("DELETE FROM {$t} where option_name like '\_transient\_%' or option_name like '\_site\_transient\_%'");
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'ATD', false, plugin_basename( dirname( __FILE__ ) ) . "/langs" );
	}
}

function app_option($option = '', $default = ''){
    $APP = get_option('APP');
    if( ! empty($APP) && isset($APP[$option])) return $APP[$option];
    return $default;
}

function base_app() {return Base_App::instance();}
$GLOBALS['base_app'] = base_app();
base_app()->init();
