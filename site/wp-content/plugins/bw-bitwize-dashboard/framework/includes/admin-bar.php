<?php
class BWD_Admin_Bar{

	public function __construct(){
		add_action( 'wp_before_admin_bar_render', array($this, 'remove_admin_bar_links' ));
	    add_filter( 'admin_bar_menu',  array($this, 'replace_howdy'), 9999);
	    add_action( 'admin_bar_menu',  array($this, 'admin_bar'), 10 );
	    add_action( 'init', array($this, 'check_admin_bar_front'));
	}

	public function check_admin_bar_front(){
		if(is_admin_bar_showing() && !is_admin()){
		    add_action('wp_enqueue_scripts',  array( $this, 'add_admin_bar_front'));
		}
	}

	public function add_admin_bar_front(){
    	wp_enqueue_style('bw-adminbar', plugins_url('assets/css/adminbar.css',dirname(__FILE__)), array(), BWD_VERSION, 'all');
    }

    public function replace_howdy( $wp_admin_bar ) {
    	$my_account=$wp_admin_bar->get_node('my-account');
    	$newtitle = str_replace( 'Howdy,', 'Welcome, ', $my_account->title );
    	$wp_admin_bar->add_node( array(
    		'id' => 'my-account',
    		'title' => $newtitle,
    	) );
    }

	public function remove_admin_bar_links() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('wp-logo');
        $wp_admin_bar->remove_menu('about');
        $wp_admin_bar->remove_menu('wporg');
        $wp_admin_bar->remove_menu('documentation');
        $wp_admin_bar->remove_menu('support-forums');
        $wp_admin_bar->remove_menu('feedback');
        //$wp_admin_bar->remove_menu('site-name');
        $wp_admin_bar->remove_menu('view-site');
        $wp_admin_bar->remove_menu('updates');
        $wp_admin_bar->remove_menu('comments');
        $wp_admin_bar->remove_menu('new-content');
        //$wp_admin_bar->remove_menu('w3tc');
        $wp_admin_bar->remove_menu('appearance');
        $wp_admin_bar->remove_menu('my-account');
    }

	public function admin_bar(){
		global $wp_admin_bar, $submenu, $user_identity;

		// USERS
		$user_id = get_current_user_id();
        $avatar = get_avatar($user_id, 30);
        if ( 0 != $user_id ) {
            $wp_admin_bar->add_menu( array(
            	'parent' => 'top-secondary',
            	'id' => 'bw-acc',
            	'title' => $avatar . $user_identity . ' <i class="bw-acc-caret"></i>',
            	'href' => '#'
            ) );
            $wp_admin_bar->add_menu( array(
            	'parent' => 'bw-acc',
            	'id' => 'bw-account',
            	'title' => __( 'My Account' ),
            	'href' => admin_url('profile.php')
            ) );
            $wp_admin_bar->add_menu( array(
            	'parent' => 'bw-acc',
            	'id' => 'bw-logout',
            	'title' => __( 'Logout' ),
            	'href' => wp_logout_url()
            ) );
            // JUMP MENU
            do_action('bw_admin_bar_after_user');
            // LOGO
            if(bwd_get_option('bitwize')){
            	$img = 'logo-bitwize-darkblue.png';
            	$l = 'http://bitwize.com.lb';
            	$s = 'max-height: 20px; margin-top:6px;';
            } else {
	            $img = 'logo-ss-darkblue.png';
	            $l = 'http://sellandsell.com';
	            $s = 'max-height:32px;';
	        }
            $wp_admin_bar->add_menu( array(
            	'id' => 'bw-logo',
            	'title' => __( '<img src="'.plugins_url('assets/images/'.$img, dirname(__FILE__)).'" style="'.$s.'" />' ),
            	'href' => $l
            ) );
        }

        if(current_user_can('manage_options')):
			// SETTINGS
			$wp_admin_bar->add_menu( array(
				'parent' => 'top-secondary',
				'id' => 'bw-settings',
				'title' => '<i class="fa fa-cog"></i>',
				'meta' => array('title' => 'Settings', 'class'=>'show_xs'),
				'href' => admin_url('options-general.php')
			) );
			if(isset($submenu['options-general.php']) && is_array($submenu['options-general.php'])){
				foreach($submenu['options-general.php'] as $k => $v){
					$title = 'bw-tms-'.strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $v[0]));
					if( strpos($v[2], '.php') === false){
						$link = admin_url('admin.php?page='.$v[2]);
					} else {
						$link = admin_url($v[2]);
					}
					$wp_admin_bar->add_menu( array(
						'parent' => 'bw-settings',
						'id' => $title,
						'title' => $v[0],
						'href' => $link
					) );
				}
			}

			// Users
			$wp_admin_bar->add_menu( array(
				'parent' => 'top-secondary',
				'id' => 'bw-users',
				'title' => '<i class="fa fa-user"></i>',
				'meta' => array('title' => 'Users', 'class'=>'show_xs'),
				'href' => admin_url('users.php')
			) );
			if(isset($submenu['users.php']) && is_array($submenu['users.php'])){
				foreach($submenu['users.php'] as $k => $v){
					$title = 'bw-tmusrs-'.strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $v[0]));
					if( strpos($v[2], '.php') === false){
						$link = admin_url('admin.php?page='.$v[2]);
					} else {
						$link = admin_url($v[2]);
					}
					$wp_admin_bar->add_menu( array(
						'parent' => 'bw-users',
						'id' => $title,
						'title' => $v[0],
						'href' => $link
					) );
				}
			}
		endif;

		// LAYOUT OPTIONS
		$wp_admin_bar->add_menu( array(
			'parent' => 'top-secondary',
			'id' => 'bw-right',
			'title' => '<i class="fa fa-crosshairs"></i>',
			'meta' => array('title' => 'Layout Options', 'class'=>'show_xs'),
			'href' => '#'
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'bw-right',
			'id' => 'bw-media',
			'title' => 'Media Library',
			'href' => admin_url('upload.php')
		) );

		if ( current_theme_supports( 'menus' ) || current_theme_supports( 'widgets' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'bw-right',
				'id' => 'bw-navmenu',
				'title' => 'Nav Menus',
				'href' => admin_url('nav-menus.php')
			) );
		}

		if ( current_theme_supports( 'widgets' ) && bwd_get_option('admin_widgets') ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'bw-right',
				'id' => 'bw-widgets',
				'title' => 'Widgets',
				'href' => admin_url('widgets.php')
			) );
		}
		if( bwd_get_option('show_customize') ){
			$wp_admin_bar->add_menu( array(
				'parent' => 'bw-right',
				'id' => 'bw-widgets',
				'title' => 'Customize',
				'href' => add_query_arg( 'return', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'customize.php' )
			) );
		}

		if(current_user_can('manage_options')):
			$wp_admin_bar->add_menu( array(
				'parent' => 'bw-right',
				'id' => 'bw-ccode',
				'title' => 'Custom Code',
				'href' => admin_url('admin.php?page=BWDCCODE')
			) );
		endif;

		if(isset($submenu['themes.php']) && is_array($submenu['themes.php'])){
			foreach($submenu['themes.php'] as $k => $v){
				if($v[2]=='themes.php' || strpos($v[2], 'customize.php') !== false || $v[2]=='nav-menus.php' || $v[2]=='widgets.php')
					continue;

				if( strpos($v[2], '.php') === false){
					$link = admin_url('admin.php?page='.$v[2]);
				} else {
					$link = admin_url($v[2]);
				}

				$title = 'bw-tmr-'.strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $v[0]));

				$wp_admin_bar->add_menu( array(
					'parent' => 'bw-right',
					'id' => $title,
					'title' => $v[0],
					'href' => $link
				) );
			}
		}

		// SYSTEM ADMIN
		if (current_user_can('can_bitwize')){
			$wp_admin_bar->add_menu( array(
				'parent' => 'top-secondary',
				'id' => 'bw-right-admin',
				'title' => '<i class="fa fa-cogs"></i>',
				'meta' => array('title' => 'Settings+', 'class'=>'show_xs'),
				'href' => admin_url('admin.php?page=BWDO')
			) );

			$wp_admin_bar->add_menu( array(
                'parent' => 'bw-right-admin',
                'id' => 'bw-syssettings',
                'title' => 'Settings+',
                'href' => admin_url('admin.php?page=BWDO')
            ) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'bw-right-admin',
				'id' => 'bw-plugins',
				'title' => 'Plugins',
				'href' => admin_url('plugins.php')
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'bw-right-admin',
				'id' => 'bw-themes',
				'title' => 'Themes',
				'href' => admin_url('themes.php')
			) );

			$wp_admin_bar->add_menu( array(
                'parent' => 'bw-right-admin',
                'id' => 'bw-menu-man',
                'title' => 'Menu+',
                'href' => admin_url('admin.php?page=bw-menu-man')
            ) );

            $wp_admin_bar->add_menu( array(
                'parent' => 'bw-right-admin',
                'id' => 'bw-capability',
                'title' => 'Capability+',
                'href' => admin_url('admin.php?page=settings-bw-capsman')
            ) );

            $wp_admin_bar->add_menu( array(
                'parent' => 'bw-right-admin',
                'id' => 'bw-htaccess',
                'title' => 'HTAccess+',
                'href' => admin_url('admin.php?page=bwd_htaccess')
            ) );
		}
	}
}

$GLOBALS['BWD_Admin_Bar'] = new BWD_Admin_Bar;
