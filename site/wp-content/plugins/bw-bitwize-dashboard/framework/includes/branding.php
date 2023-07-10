<?php
class BW_Branding
{
	private function define($name, $value) {
        if (!defined($name)) define($name, $value);
    }

	public function __construct(){
		global $pagenow;
		if(bwd_get_option('sysdash')) require_once(dirname(__FILE__) . '/widget-sysinfo.php');
		add_action( 'wp_head', array( $this, 'generator' ));
		// MASTER SLIDER SUPPORT
		add_filter( 'masterslider_disable_auto_update', '__return_true' );
		add_action('admin_head', function(){ echo '<style>#msp-header{display:none;}</style>'; });
		// Admin Menu Hooks
		add_action( 'admin_enqueue_scripts', array( &$this, 'faster_menu') , 3 );
		add_action( 'admin_footer', array( &$this, 'faster_remove_menu' ) );

		if (function_exists( 'add_theme_support' )){
		    add_filter('manage_posts_columns',  array( $this, 'posts_columns'), 5);
		    add_action('manage_posts_custom_column',  array( $this, 'posts_custom_columns'), 5, 2);
		    add_filter('manage_edit-product_columns',  array( $this, 'posts_columns'), 5);
		}
		add_action('admin_enqueue_scripts',  array( $this, 'enqueue_styles_admin'), PHP_INT_MAX);
		add_action('admin_init',  array( $this, 'admin_scripts'));
		add_action('login_head',  array( $this, 'add_favicon'));
    	add_action('admin_head',  array( $this, 'add_favicon'));
    	add_action('login_head',  array( $this, 'custom_login') );
    	add_action('login_enqueue_scripts', array($this, 'login_scripts'));
    	add_action('login_footer',  array( $this, 'custom_login_footer') );
		// add_filter( 'tiny_mce_before_init',  array( $this, 'formatTinyMCE' ) );

		// TEXT REPLACE
		if(is_admin()){
	        add_filter('gettext', array($this, 'rename_admin_menu_items'));
	        add_filter('ngettext', array($this, 'rename_admin_menu_items'));
	    }
	    // BRANDING
	    add_filter( 'admin_footer_text',  array($this, 'remove_footer_admin'), 9999);
	    add_filter( 'update_footer',  array($this, 'change_footer_version'), 9999);
	    add_action( 'admin_menu' ,  array($this, 'branding_remove_custom_fields_metaboxes' ));
	    // Color Scheme
	    remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
	    add_action('admin_init', array( $this, 'set_default_admin_color') );
	    //Dashboard
	    add_action( 'admin_init', array( $this, 'remove_dashboard_meta' ) );
		add_action('admin_menu', array($this, 'disable_man_links'), 102);
		add_action( 'admin_footer', array($this, 'remove_settings_links'));
	}

	public function remove_settings_links(){
		global $pagenow;
		$p = isset($_GET['page']) ? $_GET['page'] : '';
		if ( $pagenow == 'options-general.php' && $p == '') {
			echo '<style>#wpbody-content .wrap p {display:none;}#wpbody-content .wrap p.submit {display:block;}</style>';
		}
	}

	public function generator(){
		$c = bwd_get_option('bitwize') ? 'Bitwize - http://bitwize.com.lb' : 'SellandSell - http://sellandsell.com';
		$c = apply_filters('bw_generator', $c . ' - ' . esc_attr( BWD_VERSION ));
		echo "\n" . '<meta name="generator" content="'.$c.'" />' . "\n";
	}

	public function faster_menu(){
		global $wp_scripts;
		$custom_script_url = plugins_url('/assets/js/faster-nav-menu.js',dirname(__FILE__));
		$wp_scripts->registered['nav-menu']->src = $custom_script_url;
		return;
	}

	public function faster_remove_menu() {
		if ( is_admin() ) {
			global $pagenow;
		    if ( !is_super_admin()) return;
		    if ( 'nav-menus.php' != $pagenow ) return;
			wp_enqueue_script( 'bw-faster-remove-menu', plugins_url('/assets/js/faster-remove-menu.js', dirname(__FILE__)), array('jquery') );
		}
	}

	public function posts_columns($columns){
		global $current_screen;
		$new = array();
		foreach($columns as $key => $title) {
			if( 'post' == $current_screen->post_type ){
			    if ($key=='title'){
			    	$new['post_id'] = '';
			    	$new['post_thumbs'] = '';
			    }
			}
			if( 'product' == $current_screen->post_type ){
			    if ($key=='thumb') $new['post_id'] = '';
			}
		    $new[$key] = $title;
		}
		return $new;
	}

	public function posts_custom_columns($column_name, $id){
		if($column_name === 'post_id') echo get_the_ID();
	    if($column_name === 'post_thumbs') echo the_post_thumbnail( array(60,120) );
	}

	public function enqueue_styles_admin(){
		wp_dequeue_style('font-awesome');
		wp_enqueue_style('font-awesome-bw', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), BWD_VERSION, 'all');
		wp_enqueue_style('bw-admin-css', plugins_url('assets/css/admin.css',dirname(__FILE__)), array(), BWD_VERSION, 'all');
	}

	public function admin_scripts() {
	    wp_enqueue_script('bwadmin-functions', plugins_url('assets/js/bw-admin.js',dirname(__FILE__)), array('jquery'), BWD_VERSION, TRUE);
	    wp_localize_script( 'bwadmin-functions', 'bitwize', array(
			'ajax_url' => admin_url( "admin-ajax.php" )
		) );
	}
	/* ADMIN & LOGIN FAVICON
    ================================================== */
    public function add_favicon() {
    	$f = bwd_get_option('bitwize') ? 'bitwize-favicon.png' : 'ss-favicon.png';
        $url = plugins_url('assets/images/'.$f,dirname(__FILE__));
        echo '<link rel="shortcut icon" href="' . $url . '" />';
    }
    /* ADMIN LOGIN SUPPORT
    ================================================== */
    public function custom_login() {
        $files = '<link rel="stylesheet" href="'.plugins_url('assets/css/login.css?v='.BWD_VERSION,dirname(__FILE__)).'" />';
        if(bwd_get_option('bitwize')){
        	$files .= '<style>body.login h1 a{background-image: url('.plugins_url('assets/images/login/logo.png',dirname(__FILE__)).') !important;background-size: 150px 38px; width: 150px; height: 38px;}</style>';
        }
        echo $files;
    }

    public function login_scripts(){
    	wp_enqueue_script('jquery');
    }

    public function custom_login_footer() {
    	if(bwd_get_option('bitwize')){
    		$l = 'http://bitwize.com.lb'; $t = 'By Bitwize';
    	} else{
    		$l = 'http://sellandsell.com'; $t = 'By SellandSell.com';
    	}
		echo '<script>(function($){var el = jQuery("div#login h1 a");el.attr("href","'.$l.'");el.attr("title","'.$t.'");})(jQuery);</script>';
    }

	/* Editor style
	================================================== */
	public function formatTinyMCE( $in ) {
		$in['wordpress_adv_hidden'] = FALSE;
		return $in;
	}

	// TEXT REPLACE
	public function rename_admin_menu_items( $menu ) {
		if(bwd_get_option('bitwize')){
	        $menu = str_ireplace(
	        	array('Screen Options','wp ', ' wp','wordpress ', ' wordpress', 'edit with visual composer'),
	        	array('','BW ', ' BW','Bitwize ', ' Bitwize', 'Edit in Frontend'),
	        	$menu
	        );
		}else{
	        $menu = str_ireplace(
	        	array('Screen Options','wp ', ' wp','wordpress ', ' wordpress', 'edit with visual composer'),
	        	array('','S&S ', ' S&S','Sell&Sell ', ' Sell&Sell', 'Edit in Frontend'),
	        	$menu
	        );
	    }
        return $menu;
    }

    // BRANDING
    public function remove_footer_admin () { echo ' '; }

    public function change_footer_version() {
    	$l = bwd_get_option('bitwize') ? 'http://bitwize.com.lb' : 'http://sellandsell.com';
    	return '<a href="'.$l.'" target="_blank" style="text-decoration:none;"><i style="color:#535C69;" class="fa fa-heart"></i></a>';
    }

    public function branding_remove_custom_fields_metaboxes() {
        $post_types = get_post_types( '', 'names' );
        foreach ( $post_types  as $post_type ) {
            remove_meta_box( 'postcustom' , $post_type , 'normal' ); //removes custom fields
            // remove_meta_box( 'commentstatusdiv' , $post_type , 'normal' ); //removes comments status
            // remove_meta_box( 'commentsdiv' , $post_type , 'normal' ); //removes comments
            // remove_meta_box( 'authordiv' , $post_type , 'normal' ); //removes author
        }
    }

    // COLOR SCHEME
    public function set_default_admin_color() {
        if (is_user_logged_in()) {
            // $current_user = wp_get_current_user();
            // $user_id = $current_user->ID;
            $user_id = get_current_user_id();
            $color_scheme = get_user_option( 'admin_color', $user_id );

            $args = array( 'ID' => $user_id, 'admin_color' => 'light' );
            if($color_scheme != 'light') wp_update_user( array(
            	'ID' => $user_id,
            	'admin_color' => 'light'
            ) );
        }
    }

    // DASHBOARD
    public function remove_dashboard_meta() {
        remove_action('welcome_panel', 'wp_welcome_panel');
        if(bwd_get_option('disable_blog')){
        	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');
        }
        remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
        // remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
        // remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
        // remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
	}

	public function disable_man_links() {
		global $pagenow, $menu, $submenu;

		add_filter('contextual_help', function($contextual_help){
			ob_start();
    		return $contextual_help;
		});
    	add_action('admin_notices', function(){
    		echo preg_replace('#<div id="contextual-help-link-wrap".*>.*</div>#Us','',ob_get_clean());
    	});
		add_filter('gettext', function($translation, $text, $domain){
			if ($text=='Need help? Use the Help tab in the upper right of your screen.') {
		    	$translation = '';
			}
			return $translation;
		},10,4);
		add_action('admin_notices', function(){
			ob_start();
		});
    	add_action('dbx_post_sidebar', function(){
    		$html = str_replace('<p>Need help? Use the Help tab in the upper right of your screen.</p>','',ob_get_clean());
    		echo str_replace('Excerpts are optional hand-crafted summaries of your content that can be used in your theme. <a href="http://codex.wordpress.org/Excerpt" target="_blank">Learn more about manual excerpts.</a>','',$html);
    	});

		// Remove media from sidebar
		remove_menu_page('upload.php');
		remove_submenu_page('upload.php', 'media-new.php');

		// Remove all submenus from Pages
		remove_submenu_page('edit.php?post_type=page', 'edit.php?post_type=page');
		remove_submenu_page('edit.php?post_type=page', 'post-new.php?post_type=page');

		// Remove plugins from sidebar
		remove_menu_page('plugins.php');
		remove_submenu_page('plugins.php', 'plugin-install.php');

		remove_menu_page('themes.php');
		remove_submenu_page('themes.php', 'nav-menus.php' );
		remove_submenu_page('themes.php', 'themes.php' );
		remove_submenu_page('themes.php', 'widgets.php' );
		remove_submenu_page('themes.php', 'customize.php' );
		remove_submenu_page('themes.php', 'theme-editor.php');

		if( isset($submenu['tools.php']) && count( $submenu['tools.php'] ) < 4 ){
			remove_menu_page('tools.php');
		}
		remove_submenu_page('tools.php', 'tools.php');
		remove_submenu_page('tools.php', 'import.php');
		remove_submenu_page('tools.php', 'export.php');

		remove_menu_page('users.php');
		remove_menu_page('options-general.php');

		remove_submenu_page('index.php', 'update-core.php' );

		if(!current_user_can('can_bitwize')){
			// add_filter('screen_options_show_screen', '__return_false');
			remove_submenu_page('index.php', 'update-core.php' );

			// remove_submenu_page('options-general.php', 'options-general.php' );
			remove_submenu_page('options-general.php', 'options-writing.php' );
			remove_submenu_page('options-general.php', 'options-reading.php' );
			remove_submenu_page('options-general.php', 'options-discussion.php' );
			remove_submenu_page('options-general.php', 'options-media.php' );
			remove_submenu_page('options-general.php', 'options-permalink.php' );

			if (
				$pagenow === 'themes.php'
				// $pagenow === 'customize.php'
				// || $pagenow === 'widgets.php'
				// || $pagenow === 'nav-menus.php'
				|| $pagenow === 'theme-editor.php'
				|| $pagenow === 'plugins.php'
				|| $pagenow === 'plugin-install.php'
				|| $pagenow === 'tools.php'
				|| $pagenow === 'import.php'
				|| $pagenow === 'export.php'

				// || $pagenow === 'options-general.php'
				|| $pagenow === 'options-writing.php'
				|| $pagenow === 'options-reading.php'
				|| $pagenow === 'options-discussion.php'
				|| $pagenow === 'options-media.php'
				|| $pagenow === 'options-permalink.php'
			) {
				wp_redirect(admin_url()); exit;
			}
		}

		if(!current_user_can('can_manage_theme')){
			if ($pagenow === 'themes.php' && $_REQUEST['page'] !== '_options'){
				wp_redirect(admin_url()); exit;
			}
		}
	}
}

$GLOBALS['BW_Branding'] = new BW_Branding;
