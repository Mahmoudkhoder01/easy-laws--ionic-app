<?php
if(!class_exists('BWD_Security')):
class BWD_Security{
	private $sub_folder;
	private $login_query;
	private $admin_key;
	private $remove_other_meta;
	private $new_admin_path;
	private $replace_admin_ajax;
	private $hide_wp_login;
	private $hide_other_wp_files;
	private $disable_directory_listing;
	private $avoid_direct_access;
	private $remove_ver_scripts;
	private $hide_wp_admin;
	private $new_include_path;
	private $new_content_path;
	private $WP_CONTENT_URL;
	private $WP_PLUGIN_URL;

    private $replace_old=array();
    private $replace_new=array();
    private $preg_replace_old=array();
    private $preg_replace_new=array();
    private $admin_replace_old=array();
    private $admin_replace_new=array();

    private $auth_cookie_expired;
    private $login_slug;
    private $register_slug;

	function __construct() {
        $this->login_slug = apply_filters('bwd_slug_login', 'sys-login');
        $this->register_slug = apply_filters('bwd_slug_register', 'sys-register');

		$this->remove_other_meta = true;
		$this->login_query = apply_filters('bwd_slug_trusted', 'sys-trusted');
		$this->admin_key = md5(home_url());

		$this->new_include_path = apply_filters('bwd_slug_include', 'lib');
		$this->new_content_path = apply_filters('bwd_slug_content', 'sys');
		$this->new_admin_path = apply_filters('bwd_slug_admin', 'dashboard');
		$this->replace_admin_ajax = apply_filters('bwd_slug_ajax', 'ajax');
		$this->hide_wp_login = true;

		$this->remove_ver_scripts = true;
		$this->hide_wp_admin = true;

		$this->hide_other_wp_files = false;
		$this->disable_directory_listing = false;
		$this->avoid_direct_access = false;

        if(isset($_GET['bwd_flush_rules'])){
            add_action('admin_init', array(&$this, 'flush_rules'));
			add_action('init', array(&$this, 'flush_rules'));
        }

		if (is_ssl()){
		    $this->WP_CONTENT_URL = str_replace ('http:','https:', WP_CONTENT_URL);
		    $this->WP_PLUGIN_URL = str_replace ('http:','https:', WP_PLUGIN_URL);
		}else {
		    $this->WP_CONTENT_URL = WP_CONTENT_URL;
		    $this->WP_PLUGIN_URL = WP_PLUGIN_URL;
		}

		$sub_installation= trim(str_replace (home_url(),'',site_url()),'/');
        if ($sub_installation && substr($sub_installation, 0, 4)!='http')
            $this->sub_folder= $sub_installation . '/' ;

        $this->is_subdir_mu= false;
        if (is_multisite())
            $this->is_subdir_mu= true;

        if ((defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) || (defined('VHOST') && VHOST == 'yes'))
            $this->is_subdir_mu= false;

        if (is_multisite() && !$this->sub_folder && $this->is_subdir_mu)
            $this->sub_folder = ltrim(parse_url( trim( get_blog_option(BLOG_ID_CURRENT_SITE, 'home' ),'/').'/', PHP_URL_PATH ), '/');

        if ( ! defined( 'ADMIN_COOKIE_PATH' ) || ADMIN_COOKIE_PATH == SITECOOKIEPATH.'wp-admin' ) {
			add_action('after_setup_theme', array(&$this, 'set_admin_config_define'));
		}

        add_action( 'after_setup_theme', array( &$this, 'block_wp_admin' ));
        add_action( 'init', array( &$this, 'init' ), 1);
        add_action( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules'));
        add_filter( 'admin_url', array (&$this, 'replace_admin_url'), 100, 3);
        add_action( 'wp_loaded', array(&$this, 'ob_starter') );
        // add_action( 'shutdown', create_function('', 'return ob_end_flush();'));
        add_filter('plugins_url', array(&$this, 'plugins_url'), 1000, 1);

    	if ($this->hide_wp_login){

                add_action('auth_cookie_expired', array($this, 'auth_cookie_expired'));
                add_action('init', array($this, 'execute_hide_backend'), 1000);
                add_action('login_init', array($this, 'execute_hide_backend_login'));
                add_action('plugins_loaded', array($this, 'plugins_loaded'), 11);

                add_filter('body_class', array($this, 'remove_admin_bar'));
                add_filter('loginout', array($this, 'filter_loginout'));
                add_filter('login_url', array($this, 'filter_login_url'));
                add_filter('wp_redirect', array($this, 'filter_login_url'), 10, 2);
                add_filter('lostpassword_url', array($this, 'filter_login_url'), 10, 2);
                add_filter('site_url', array($this, 'filter_login_url'), 10, 2);
                add_filter('retrieve_password_message', array($this, 'retrieve_password_message'));
                add_filter('comment_moderation_text', array($this, 'comment_moderation_text'));

                remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
	    }
	}

    // LOGIN

    function get_home_root() {
        $home_root = parse_url( site_url() );
        if ( isset( $home_root['path'] ) ) {
            $home_root = trailingslashit( $home_root['path'] );
        } else {
            $home_root = '/';
        }
        return $home_root;
    }

    function auth_cookie_expired() {
        $this->auth_cookie_expired = true;
        wp_clear_auth_cookie();
    }

    function comment_moderation_text($notify_message) {
        preg_match_all("#(https?:\/\/((.*)wp-admin(.*)))#", $notify_message, $urls);
        if (isset($urls) && is_array($urls) && isset($urls[0])) {
            foreach ($urls[0] as $url) {
                $notify_message = str_replace(trim($url), wp_login_url(trim($url)), $notify_message);
            }
        }
        return $notify_message;
    }

    function execute_hide_backend() {

        if (get_site_option('users_can_register') == 1 && isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] == $this->get_home_root() . $this->register_slug) {

            wp_redirect(wp_login_url() . '?action=register');
            exit;
        }

        if (((get_site_option('users_can_register') == false && (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-register.php') || isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-signup.php'))) || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-login.php') && is_user_logged_in() !== true) || (is_admin() && is_user_logged_in() !== true) || ($this->register_slug != 'wp-register.php' && strpos($_SERVER['REQUEST_URI'], 'wp-register.php') !== false || strpos($_SERVER['REQUEST_URI'], 'wp-signup.php') !== false || (isset($_REQUEST['redirect_to']) && strpos($_REQUEST['redirect_to'], 'wp-admin/customize.php') !== false))) && strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') === false && $this->auth_cookie_expired === false) {

            global $itsec_is_old_admin;
            $itsec_is_old_admin = true;

            add_action('wp_loaded', array($this, 'block_access'));
        }

        $url_info = parse_url($_SERVER['REQUEST_URI']);
        $login_path = site_url($this->login_slug, 'relative');
        $login_path_trailing_slash = site_url($this->login_slug . '/', 'relative');

        if ($url_info['path'] === $login_path || $url_info['path'] === $login_path_trailing_slash) {

            if (!is_user_logged_in()) {

                error_reporting(0);
                @ini_set('display_errors', 0);

                status_header(200);

                if (defined('DOMAIN_MAPPING') && DOMAIN_MAPPING == 1) {
                    remove_action('login_head', 'redirect_login_to_orig');
                }

                if (!function_exists('login_header')) {

                    include (ABSPATH . 'wp-login.php');
                    exit;
                }
            } elseif (!isset($_GET['action']) || (sanitize_text_field($_GET['action']) != 'logout' && sanitize_text_field($_GET['action']) != 'postpass' )) {

                if ($this->auth_cookie_expired === false) {

                    wp_redirect(get_admin_url());
                    exit();
                }
            } elseif (isset($_GET['action']) && (sanitize_text_field($_GET['action']) == 'postpass')) {

                error_reporting(0);
                @ini_set('display_errors', 0);

                status_header(200);
                if (!function_exists('login_header')) {
                    include (ABSPATH . '/wp-login.php');
                    exit;
                }

                if (isset($_SERVER['HTTP_REFERRER'])) {
                    wp_redirect(sanitize_text_field($_SERVER['HTTP_REFERRER']));
                    exit();
                }
            }
        }
    }

    function execute_hide_backend_login() {
        if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php')) {
            global $itsec_is_old_admin;
            $itsec_is_old_admin = true;
            $this->block_access();
        }
    }

    function filter_login_url($url) {
        $t = str_replace('wp-login.php', $this->login_slug, $url);
        return str_replace('wp-login.php', $this->login_slug, $url);
    }

    function filter_loginout($link) {
        return str_replace('wp-login.php', $this->login_slug, $link);
    }

    function plugins_loaded() {
        if (is_user_logged_in() && isset($_GET['action']) && sanitize_text_field($_GET['action']) == 'logout') {
            check_admin_referer('log-out');
            wp_logout();
            $redirect_to = !empty($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : 'wp-login.php?loggedout=true';
            wp_safe_redirect($redirect_to);
            exit();
        }
    }

    function remove_admin_bar($classes) {
        if (is_admin() && is_user_logged_in() !== true) {
            foreach ($classes as $key => $value) {
                if ($value == 'admin-bar') {
                    unset($classes[$key]);
                }
            }
        }
        return $classes;
    }

    function retrieve_password_message($message) {
        return str_replace('wp-login.php', $this->login_slug, $message);
        return $message;
    }

    function set_404() {

        global $wp_query;
        status_header( 404 );
        if ( function_exists( 'nocache_headers' ) ) {
            nocache_headers();
        }
        $wp_query->set_404();
        $page_404 = get_404_template();
        if ( strlen( $page_404 ) > 1 ) {
            include( $page_404 );
        } else {
            include( get_query_template( 'index' ) );
        }
        die();
    }

    // END LOGIN

    function flush_rules(){
        flush_rewrite_rules();
    }

    function block_wp_admin(){
        if ($this->hide_wp_admin)  {
            if ( $this->str_contains($_SERVER['PHP_SELF'], '/wp-admin') && trim($this->new_admin_path,' /')!='wp-admin' && !$this->str_contains($_SERVER['REQUEST_URI'], $this->new_admin_path) && !current_user_can('manage_options') ) {
                if (!$this->ends_with($_SERVER['PHP_SELF'], '/admin-ajax.php')) {
                    $this->block_access();
                }
            }
        }
    }

	function set_admin_config_define(){
		if( defined( 'ADMIN_COOKIE_PATH' ) && ADMIN_COOKIE_PATH != SITECOOKIEPATH.'wp-admin' ) {
			return;
		}

		$config_file_path = $this->find_wpconfig_path();
	    if ( ! $config_file_path ) {
			return;
	    }

		$config_file = file( $config_file_path );

		$is_exist = false;

		$new_admin = preg_replace('|https?://[^/]+|i', '', get_option('siteurl') . '/' ) . $this->new_admin_path;

		$constant = "define('ADMIN_COOKIE_PATH', '".$new_admin."'); // Added by System Security". "\r\n";

		foreach ( $config_file as &$line ) {
			if ( ! preg_match( '/^define\(\'([A-Z_]+)\',([ ]+)/', $line, $match ) ) {
				continue;
			}

			if ( $match[1] == 'ADMIN_COOKIE_PATH' ) {
				$is_exist = true;
				$line = $constant;
			}
		}
		unset( $line );

		// If the constant does not exist, create it
		if ( ! $is_exist ) {
			array_shift( $config_file );
			array_unshift( $config_file, "<?php\r\n", $constant);
		}

		// Insert the constant in wp-config.php file
		$handle = @fopen( $config_file_path, 'w' );
		foreach( $config_file as $line ) {
			@fwrite( $handle, $line );
		}

		@fclose( $handle );

		// Update the writing permissions of wp-config.php file
		$chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
		@chmod( $config_file_path, $chmod );
	}

	function find_wpconfig_path(){
		$config_file = $this->get_home_path() . 'wp-config.php';
		$config_file_alt = dirname( $this->get_home_path() ) . '/wp-config.php';

		if ( file_exists( $config_file ) && is_writable( $config_file ) ) {
			return $config_file;
		} elseif ( @file_exists( $config_file_alt ) && is_writable( $config_file_alt ) && !file_exists( dirname( $this->get_home_path() ) . '/wp-settings.php' ) ) {
			return $config_file_alt;
		}

		// No writable file found
		return false;
	}

	function get_home_path() {
		$home    = set_url_scheme( get_option( 'home' ), 'http' );
		$siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );
		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
			$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
			$pos = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
			$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
			$home_path = trailingslashit( $home_path );
		} else {
			$home_path = ABSPATH;
		}

		return str_replace( '\\', '/', $home_path );
	}

	function init(){
		$is_trusted=false;
        if (current_user_can('manage_options') || (isset($_GET[$this->login_query]) && $_GET[$this->login_query]==$this->admin_key) )
            $is_trusted=true;

        $new_admin_path = (trim($this->new_admin_path, '/')) ? trim($this->new_admin_path, '/') : 'wp-admin';

        if (trim($this->new_admin_path, '/') && trim($this->new_admin_path,'/') != 'wp-admin') {
            $_SERVER['REQUEST_URI'] = $this->replace_admin_url($_SERVER['REQUEST_URI']);
            add_filter( 'admin_url', array (&$this, 'replace_admin_url'), 100, 3);
        }

        if ($this->remove_ver_scripts) {
            add_filter( 'style_loader_src', array( &$this, 'remove_ver_scripts'), 9999 );
            add_filter( 'script_loader_src', array( &$this, 'remove_ver_scripts'), 9999 );
        }

    	if ($this->hide_wp_login && !$is_trusted)  {
            if ($this->ends_with($_SERVER['PHP_SELF'], '/wp-login.php') || $this->ends_with($_SERVER['PHP_SELF'], '/wp-login.php/')) {
                $this->block_access();
            }
        }

        if ($this->remove_other_meta){
            add_filter('the_generator', create_function('', 'return "";'));
            remove_action('wp_head', 'wp_generator' );
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'index_rel_link');
            remove_action('wp_head', 'parent_post_rel_link', 10, 0);
            remove_action('wp_head', 'start_post_rel_link', 10, 0);
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
            remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);

            $this->replace_old[]='<link rel="profile" href="http://gmpg.org/xfn/11" />';
            $this->replace_new[]='';

            $this->replace_old[]='<link rel="pingback" href="'. get_bloginfo( 'pingback_url' ).'" />';
            $this->replace_new[]='';

            $this->replace_old[]='<link rel="profile" href="http://gmpg.org/xfn/11">';
            $this->replace_new[]='';

            $this->replace_old[]='<link rel="pingback" href="'. get_bloginfo( 'pingback_url' ).'">';
            $this->replace_new[]='';
        }
        if ($this->new_include_path && $this->is_permalink()){
            $rel_include_path = $this->sub_folder . 'wp-includes';
            $new_include_path = trim($this->new_include_path, '/ ') ;

            if (is_multisite()){
                $new_include_path = '/'.$new_include_path;
                $rel_include_path = $this->blog_path .str_replace($this->sub_folder,'',$rel_include_path);
            }

            $this->replace_old[]=$rel_include_path.'/';
            $this->replace_new[]=$new_include_path.'/';
        }

        if ($this->new_content_path && $this->is_permalink()){
            $rel_content_path = $this->sub_folder . 'wp-content';
            $new_content_path = trim($this->new_content_path, '/ ') ;

            if (is_multisite()){
                $new_content_path = '/'.$new_content_path;
                $rel_content_path = $this->blog_path .str_replace($this->sub_folder,'',$rel_content_path);
            }

            $this->replace_old[]=$rel_content_path.'/';
            $this->replace_new[]=$new_content_path.'/';
        }
    }

	function add_rewrite_rules($wp_rewrite){
        if($this->login_slug){
            $new_non_wp_rules[$this->login_slug . '/(.*)'] = $this->sub_folder . 'wp-login\.php$';
        }
        if($this->register_slug){
            $new_non_wp_rules[$this->register_slug . '/(.*)'] = $this->sub_folder . 'wp-login\.php?action=register$';
        }
		if ($this->new_admin_path && trim($this->new_admin_path, '/')!='wp-admin' && $this->is_permalink() ){
            $rel_admin_path = $this->sub_folder . 'wp-admin';
            $new_admin_path = trim($this->new_admin_path, '/') ;

            $new_non_wp_rules[$new_admin_path.'/(.*)'] = $rel_admin_path.'/$1';

            if (is_multisite()){
                $new_admin_path = '/'.$new_admin_path;
                $rel_admin_path = $this->blog_path .str_replace($this->sub_folder,'', $rel_admin_path);
            }
            $this->admin_replace_old[]=$rel_admin_path.'/';
            $this->admin_replace_new[]=$new_admin_path.'/';
        }

        if ($this->new_include_path && $this->is_permalink()){
            $rel_include_path = $this->sub_folder . 'wp-includes';
            $new_include_path = trim($this->new_include_path, '/ ') ;

            $new_non_wp_rules[$new_include_path.'/(.*)'] = $rel_include_path.'/$1';
        }

        if ($this->new_content_path && $this->is_permalink()){
            $rel_content_path = $this->sub_folder . 'wp-content';
            $new_content_path = trim($this->new_content_path, '/ ') ;

            $new_non_wp_rules[$new_content_path.'/(.*)'] = $rel_content_path.'/$1';
        }

        if ($this->replace_admin_ajax && trim($this->replace_admin_ajax, '/ ')!='admin-ajax.php' && trim($this->replace_admin_ajax )!='wp-admin/admin-ajax.php' && $this->is_permalink())  {
            $rel_admin_ajax = $this->sub_folder . 'wp-admin/admin-ajax.php';
            $new_admin_ajax = trim($this->replace_admin_ajax, '/ ');

            $admin_ajax = str_replace('.','\\.', $new_admin_ajax);

            $new_non_wp_rules[$admin_ajax] = $rel_admin_ajax;

            if (is_multisite()){
                $rel_admin_ajax =  str_replace($this->sub_folder,'',$rel_admin_ajax);
                $new_admin_ajax =  $new_admin_ajax;
            }

            $this->replace_old[]= $rel_admin_ajax;
            $this->replace_new[]= $new_admin_ajax;

            $this->replace_old[]= str_replace('/', '\/', $rel_admin_ajax);
            $this->replace_new[]= str_replace('/', '\/', $new_admin_ajax);
        }

        if ($this->hide_other_wp_files && $this->is_permalink()){
            $rel_content_path = $this->sub_folder . trim(str_replace(site_url(),'', $this->WP_CONTENT_URL), '/');
            $rel_plugin_path = $this->sub_folder . trim(str_replace(site_url(),'', $this->WP_PLUGIN_URL), '/');
            $rel_include_path = $this->sub_folder .trim(WPINC);
            $style_path_reg='';

            $new_non_wp_rules[$this->sub_folder .'readme\.html|'.$this->sub_folder .'license\.txt|'.$rel_content_path.'/debug\.log'.$style_path_reg.'|'.$rel_include_path.'/$'] = 'nothing_404_404';
        }

        if ($this->disable_directory_listing && $this->is_permalink()) {
            $rel_content_path = $this->sub_folder . trim(str_replace(site_url(),'', $this->WP_CONTENT_URL), '/');
            $rel_include_path = $this->sub_folder .trim(WPINC);

            $new_non_wp_rules['((('.$rel_content_path.'|'.$rel_include_path.')/([A-Za-z1-9-_/]*))|(wp-admin/(?!network/)([A-Za-z1-9-_/]+)))(\.txt|/)$'] = 'nothing_404_404';
        }

        if ($this->avoid_direct_access )  {

            $white_list= array();
            $white_list[]='wp-login.php';
            $white_list[]='index.php';
            $white_list[]='wp-admin/';

            $block = true;
            $white_regex = '';
            foreach ($white_list as $white_file) {
                 $white_regex.= $this->sub_folder . str_replace(array('.', ' '), array('\.',''), $white_file ).'|';  //make \. remove spaces
            }
            $white_regex=substr($white_regex, 0 ,strlen($white_regex)-1); //remove last |
            $white_regex = str_replace(array("\n", "\r\n", "\r"), '', $white_regex);

            $new_non_wp_rules['('.$white_regex.')(.*)'] = '$1$2';
            $new_non_wp_rules[$this->sub_folder . '(.*)\.php$'] = 'nothing_404_404';

            add_filter('mod_rewrite_rules', array(&$this, 'mod_rewrite_rules'),10, 1);
        }

        if (isset($new_non_wp_rules) && $this->is_permalink())
            $wp_rewrite->non_wp_rules = array_merge($wp_rewrite->non_wp_rules, $new_non_wp_rules);
        // print_r($wp_rewrite);
        return $wp_rewrite;
	}

	function mod_rewrite_rules($rules){
        $home_root = parse_url(home_url());
        if ( isset( $home_root['path'] ) )
            $home_root = trailingslashit($home_root['path']);
        else
            $home_root = '/';

        $rules=str_replace('(.*) '.$home_root.'$1$2 ', '(.*) $1$2 ', $rules);

        return $rules;
    }

    function remove_ver_scripts($src){
    	if(is_admin()) return $src;

        if ( strpos( $src, 'ver=' ) )
            $src = remove_query_arg( 'ver', $src );
        return $src;
    }

	function replace_admin_url($url, $path = '', $scheme='admin'){
        if (trim( $this->new_admin_path ,'/ ') && trim( $this->new_admin_path ,'/ ') != 'wp-admin' ){
            $url = str_replace( 'wp-admin/', trim( $this->new_admin_path ,'/ ').'/', $url);

            if ($this->replace_admin_ajax && trim($this->replace_admin_ajax, '/ ')!='admin-ajax.php' && trim($this->replace_admin_ajax )!='wp-admin/admin-ajax.php' && $this->is_permalink())  {
                $url = str_replace(
                    trim($this->new_admin_path ,'/ ').'/admin-ajax.php',
                    trim( $this->replace_admin_ajax ,'/ ').'/',
                    $url
                );
            }
        }
        return $url;
    }

    function ob_starter(){
    	// print_r($this->replace_old);
        ob_start(array(&$this, "global_html_filter")) ;
    }

    function global_html_filter( $buffer){

        if (is_admin() && $this->admin_replace_old ) {
            $buffer = str_replace($this->admin_replace_old, $this->admin_replace_new, $buffer);
            return $buffer;
        }

        if (is_admin()) return $buffer;

        if ($this->replace_old)
            $buffer = str_replace($this->replace_old, $this->replace_new, $buffer);

        if ($this->preg_replace_old)
            $buffer = preg_replace($this->preg_replace_old, $this->preg_replace_new, $buffer);

        return $buffer;
    }

    function plugins_url($content){

        if ($this->new_content_path){
            $rel_content_path = $this->sub_folder . 'wp-content';
            $new_content_path = trim($this->new_content_path, '/ ') ;

            if (is_multisite()){
                $new_content_path = '/'.$new_content_path;
                $rel_content_path = $this->blog_path .str_replace($this->sub_folder,'',$rel_content_path);
            }

            $replace_old = $rel_content_path;
            $replace_new = $new_content_path;

            $content = str_replace($replace_old, $replace_new, $content);
        }

        return $content;
    }

    function is_permalink(){
        global $wp_rewrite;
        if (!isset($wp_rewrite) || !is_object($wp_rewrite) || !$wp_rewrite->using_permalinks())
            return false;
        return true;
    }

    function block_access(){
        global $wp_query;

        $url=esc_url('http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI']);

        status_header( 404 );
        nocache_headers();

        $headers = array('X-Pingback' => get_bloginfo('pingback_url'));
        $headers['Content-Type'] = get_option('html_type') . '; charset=' . get_option('blog_charset');
        foreach( (array) $headers as $name => $field_value )
            @header("{$name}: {$field_value}");

        if ( $this->str_contains($_SERVER['PHP_SELF'], '/wp-admin/') || $this->ends_with($_SERVER['PHP_SELF'], '.php')) {

            $response = @wp_remote_get( home_url('/nothing_404_404') );
            if ( ! is_wp_error($response) )
            	echo $response['body'];
            else
            	wp_redirect( home_url('/404_Not_Found')) ;

        }else{
            require_once( get_404_template() );
        }

        die();
    }

    function str_contains($string, $find, $case_sensitive=true) {
        if (empty($string) || empty($find))
        	return false;

        if ($case_sensitive)
        	$pos = strpos($string, $find);
        else
        	$pos = stripos($string, $find);

        if ($pos === false)
        	return false;
        else
        	return true;
    }

    function starts_with($string, $find, $case_sensitive=true){
    	if ($case_sensitive)
    		return strpos($string, $find) === 0 ;
    	return stripos($string, $find) === 0;
    }

    function ends_with($string, $find, $case_sensitive=true){
    	$expectedPosition = strlen($string) - strlen($find);
    	if($case_sensitive)
    		return strrpos($string, $find, 0) === $expectedPosition;
    	return strripos($string, $find, 0) === $expectedPosition;
    }
}


new BWD_Security();

endif;
