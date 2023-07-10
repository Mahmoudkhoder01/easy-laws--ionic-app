<?php
class BWD_Core_Bootstrap{

	public function __construct(){
		// DEFINE
		$this->define();
		add_filter('widget_text', 'do_shortcode');
		// BLOCK WP
		if(!bwd_get_option('allow_wp')){
			add_filter('pre_http_request', array($this, 'block_wp'), 100, 3);
		}
		// New Relic Flush
		remove_action('shutdown', 'wp_ob_end_flush_all', 1);
		add_action( 'shutdown', array($this, 'fix_flush'), 1, 0);
		// DEREGISTER HEARTBEAT
		add_action( 'init', array($this, 'heart_beat'), 1);
		// DISABLE JSON API
		if(!bwd_get_option('enable_rest')){
			add_action( 'after_setup_theme', array($this, 'disable_json_api'));
		}
		add_filter('rest_url_prefix', function(){
			return '__api';
		});
		add_action( 'init', array( $this, 'add_api_endpoint' ), 0 );

		// DISABLE ATTACHEMENT PAGES
		add_action('template_redirect', array($this, 'disable_attachement_pages'), 1);
		// DISABLE UNWANTED CRONS
		add_action('init', array($this, 'disable_unwanted_crons'));
		// DISABLE EMOJI in 4.2+
		add_action( 'init', array($this, 'disable_emoji'));
		// INITIAL SETUP
		add_action('after_setup_theme',  array( $this, 'initial_theme_setup'));

		// MAIL Tweaks
		add_filter('wp_mail_from', array($this, 'mail_from'));
		add_filter('wp_mail_from_name', array($this, 'mail_from_name'));
		add_filter('wp_mail_content_type', array($this, 'mail_type'));
		// ASSIGN MASTER
		add_action( 'admin_init', array($this, 'assign_master'));
		add_action( 'Bitwize_core_event',  array($this, 'force_assign') );
		//Admin in English
	    add_filter( 'locale', array( $this, 'admin_in_english_locale' ) );
		add_filter( 'plugin_locale', array( $this, 'admin_in_english_locale' ) );
	}

	public static function add_api_endpoint() {
		// REST API
		add_rewrite_rule( '^ss-api/v([1-3]{1})/?$', 'index.php?wc-api-version=$matches[1]&wc-api-route=/', 'top' );
		add_rewrite_rule( '^ss-api/v([1-3]{1})(.*)?', 'index.php?wc-api-version=$matches[1]&wc-api-route=$matches[2]', 'top' );
	}

	function block_wp($val, $request, $url){
		$b = apply_filters('blocked_hosts', array());
		if(!is_array($b)) $b = array($b);
		$b[] = 'wordpress.org';
		foreach($b as $h){
			if(stripos($url, $h)) return true;
		}
		return $val;
	}

	function fix_flush(){
		$levels = ob_get_level();
		for ( $i = 0; $i < $levels - 1; $i++ )
			ob_end_flush();
	}

	function define(){
		if(!bwd_get_option('cronjobs')){
			if(!defined('DISABLE_WP_CRON')) define( 'DISABLE_WP_CRON', true );
		}
		if(!defined('WP_CRON_LOCK_TIMEOUT')) define( 'WP_CRON_LOCK_TIMEOUT', 30 );
		if(!defined('EMPTY_TRASH_DAYS')) define( 'EMPTY_TRASH_DAYS', 30 ); // 30 days
		if(!defined('DISALLOW_FILE_EDIT')) define( 'DISALLOW_FILE_EDIT', true );
		if(!defined('AUTOSAVE_INTERVAL')) define( 'AUTOSAVE_INTERVAL',    3600 );     // autosave 1x per hour
		if(!defined('WP_POST_REVISIONS')) define( 'WP_POST_REVISIONS',    false );    // no revisions

		if(!bwd_get_option('xmlrpc')){
			add_filter( 'xmlrpc_enabled', '__return_false');
			add_filter( 'xmlrpc_methods', function( $methods ) {
			   unset( $methods['pingback.ping'] );
			   unset( $methods['pingback.extensions.getPingbacks'] );
			   unset( $methods['system.multicall'] );
			   return $methods;
			});
			add_action( 'plugins_loaded', function(){
				if( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
					die('XMLRPC Request Rejected');
				}
			}, 1);
		}
		add_filter('pings_open', '__return_false', 20, 2);
	}

	public function initial_theme_setup() {
		show_admin_bar(false);
		add_theme_support( 'title-tag' );
	    add_filter( 'widget_text', 'do_shortcode' );
	    add_filter( 'the_excerpt', 'do_shortcode' );
	    remove_action( 'wp_head', 'wp_generator' );
	    remove_action( 'wp_head', 'rsd_link' );
	    remove_action( 'wp_head', 'wlwmanifest_link' );
	    remove_action( 'wp_head', 'start_post_rel_link' );
	    remove_action( 'wp_head', 'index_rel_link' );
	    remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	}

	function heart_beat(){
		global $pagenow;
		if ( 'post.php' != $pagenow && 'post-new.php' != $pagenow && 'edit.php' != $pagenow )
			wp_deregister_script('heartbeat');
	}

	function disable_json_api(){
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	    remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
	    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
	    add_filter( 'embed_oembed_discover', '__return_false' );
	    remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
	    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter('json_enabled', '__return_false');
	  	add_filter('json_jsonp_enabled', '__return_false');
	  	add_filter('rest_enabled', '__return_false');
	  	add_filter('rest_jsonp_enabled', '__return_false');
	}

	function disable_attachement_pages(){
		global $post;
		if ( is_attachment() && isset($post->post_parent) && is_numeric($post->post_parent) && ($post->post_parent != 0) ) {
			wp_redirect(get_permalink($post->post_parent), 301);
			exit;
		} elseif ( is_attachment() && isset($post->post_parent) && is_numeric($post->post_parent) && ($post->post_parent < 1) ) {
			wp_redirect(get_bloginfo('wpurl'), 302);
			exit;
    	}
	}

	function disable_unwanted_crons(){
		wp_clear_scheduled_hook('wp_version_check');
		wp_clear_scheduled_hook('wp_maybe_auto_update');
	}

	function disable_emoji(){
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', function($plugins){
			if ( is_array( $plugins ) ) {
				return array_diff( $plugins, array( 'wpemoji' ) );
			} else {
				return array();
			}
		});
	}

	function mail_from(){
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) $sitename = substr( $sitename, 4 );
		return 'system@' . $sitename;
	}
	function mail_from_name(){ return get_option('blogname'); }
	function mail_type(){ return 'text/html'; }

	public function assign_master(){
		if( get_option('bw_master_installed') != 1 ){
			$this->force_assign();
		}
		if(!wp_next_scheduled('Bitwize_core_event')){
			wp_schedule_event( time(), 'daily', 'Bitwize_core_event' );
		}
	}

	public function force_assign(){
		global $wp_roles;
		// $bitwizer = new WP_User(1);
		$bitwizer = get_user_by('login','bitwizer');
		$bitwizer->add_cap('can_bitwize');
		$bitwizer->add_cap('can_manage_plugins');
		$bitwizer->add_cap('can_manage_theme');
		$adm = $wp_roles->get_role('administrator');
		$adm->add_cap('can_manage_plugins',false);
		$adm->add_cap('can_manage_theme',false);
		update_option('bw_master_installed', 1);
	}

	public function admin_in_english_locale( $locale ) {
		if (
			(is_admin() || (false !== strpos( $_SERVER['REQUEST_URI'], '/wp-includes/js/tinymce/') ) ||
			(false !== strpos( $_SERVER['REQUEST_URI'], '/wp-login.php' ) && false !== strpos( $_SERVER['REQUEST_URI'], '/sys-login' )) ||
			(false !== strpos( $_SERVER['REQUEST_URI'], '/customize.php' )))
			&& !(defined( 'DOING_AJAX' ) && DOING_AJAX && false === strpos( wp_get_referer(), '/wp-admin/' ) && false === strpos( wp_get_referer(), '/dashboard/' ))
		) {
			return 'en_US';
		}
		return $locale;
	}

}

$GLOBALS['BWD_Core_Bootstrap'] = new BWD_Core_Bootstrap;
