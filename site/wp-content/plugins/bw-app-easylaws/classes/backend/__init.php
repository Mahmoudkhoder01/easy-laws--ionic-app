<?php

class App_Admin
{
	private $assets_url, $ajax, $dir;
	public $app_page, $users_page, $accounts_page, $resellers_page, $packages_page, $maintenance_page;

	private static $_instance;
	public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct(){
    	$this->v = APP_VERSION;
    	$this->dir = dirname(__FILE__);
		$this->assets_url = plugins_url('assets', __FILE__);
		$this->ajax = admin_url('admin-ajax.php');
    }

	public function init(){
		require_once $this->dir . '/ajax.php';
		require_once $this->dir . '/hooks.php';
		require_once $this->dir . '/widgets/init.php';

		add_filter( 'admin_menu', array($this, 'admin_menu'));
		add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);

		add_action('admin_enqueue_scripts', array($this, 'add_assets'));
		add_action('admin_footer', array($this, 'footer'));
		$this->includes();
	}

    public function includes(){
    	$pages = array(
    		'questions.php',
    		'didyouknow.php',
    		'dyk-push-filter.php',
    		'subjects.php',
    		'tags.php',
    		'tag-mapper.php',
    		'keywords.php',
    		'definitions.php',
    		'references.php',
    		// 'dashboards.php',

    		'comments.php',
    		'users.php',
    		'requests.php',
    		'devices.php',
    		'push.php',
    		'search-report.php',
    		'search-extended.php',
    		'sponsors.php',
    		'sponsor-ads.php',
    		'reports.php',
    	);
    	foreach($pages as $page){
    		require_once($this->dir.'/pages/'.$page);
    	}
    }

    public function footer(){
    	echo '
    		<img width="50" src="'.$this->assets_url.'/img/drag.png" id="ui-handle-image" />
			<img width="50" src="'.$this->assets_url.'/img/drag-copy.png" id="ui-handle-image-copy" />
			<style>
				@media screen and (max-width: 782px){
					.tablenav.top .actions{ display: initial !important; }
					.tablenav.top, .tablenav .tablenav-pages{ margin: 0 !important; }
					p.search-box {
					    position: relative !important;
				    	margin: 0 !important;
				    	height: initial;
					}
					p.search-box input[name="s"]{ margin: 0; width: 65%; }
					p.search-box input[type=submit]{ margin: 0;}
				}
			</style>
    	';
    }

	public function add_assets(){
		$action = !empty($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
		if($action == 'addnew' || $action == 'edit'){
			wp_enqueue_media();
		}
		wp_enqueue_script( 'jquery-ui-sortable');

		wp_enqueue_style('app-admin-css-plugins', $this->assets_url.'/css/plugins.css?v='.$this->v, '', APP_VERSION);
		wp_enqueue_style('app-admin-css', $this->assets_url.'/css/app.css?v='.$this->v, '', APP_VERSION);

		wp_enqueue_script('app-admin-plugins', $this->assets_url.'/js/plugins.min.js?v='.$this->v, array('jquery'), APP_VERSION, true);

		wp_enqueue_script('app-admin-js', $this->assets_url.'/js/functions.js?v='.$this->v, array('jquery'), APP_VERSION, true);

		wp_localize_script( 'app-admin-js', 'VARS', array(
			'assets_url' => $this->assets_url,
			// 'assets_url' => str_replace('http:', 'https:', $this->assets_url),
			'ajax_url' => $this->ajax,
		) );
	}

	public function admin_menu(){
		$this->app_page = add_menu_page( 'App', 'App', 'read', 'app', null, '', '2.0136' );
		do_action('app_admin_menu');
		remove_submenu_page( 'app', 'app' );
		do_action('app_admin_menu_bottom');
	}

	public function set_screen_option($status, $option, $value) {
		if ( 'per_page' == $option ) return $value;
	}

	public function per_page(){
		$screen = get_current_screen();
		add_screen_option('per_page', array(
			'label' => __('Items per page'),
			'default' => 20,
			'option' => 'app_items_per_page'
		));
	}

}
function app_admin() {
	return App_Admin::instance();
}
$GLOBALS['app_admin'] = app_admin();
app_admin()->init();
