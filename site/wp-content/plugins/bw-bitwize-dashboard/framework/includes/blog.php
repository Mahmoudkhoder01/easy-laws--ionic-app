<?php

class BWD_Blog_Bootstrap{
	public function __construct(){
		global $pagenow;
		// Disable Comments
		if(bwd_get_option('disable_comments') && bwd_get_option('disable_reviews')){
			add_action('admin_init', array($this, 'disable_comments_post_types_support'));
			add_filter('wp_count_comments', array($this, 'skip_comment_count_query'), 10, 2);
			add_filter('comments_open', array($this, 'comments_open'), 20, 2);
			// add_filter('pings_open', array($this, 'comments_open'), 20, 2);
			add_filter('comments_array', array($this, 'disable_comments_hide_existing_comments'), 10, 2);
			add_action('admin_menu', array($this, 'disable_comments_admin_menu'));
			add_action('admin_init', array($this, 'disable_comments_admin_menu_redirect'));
			add_action('admin_init', array($this, 'disable_comments_dashboard'));
			add_action('wp_before_admin_bar_render', array($this, 'remove_comments_admin_bar_links'));
		}
		// Disable Posts
		if(bwd_get_option('disable_blog')){
			add_action( 'admin_menu', array( $this, 'remove_post_type_post' ) );
			add_action( 'admin_bar_menu', array($this, 'remove_wp_nodes'), 999 );
			if ( !is_admin() && ($pagenow != 'wp-login.php') ) {
				add_action( 'posts_results', array( $this, 'check_post_type' ) );
			}
		} else {
			add_action( 'admin_menu', array($this, 'change_post_label' ));
			add_action( 'init', array($this, 'change_post_object' ));
		}
		// DISABLE REVIEWS
		if(bwd_get_option('disable_reviews')){
			add_filter( 'comments_open', array($this, 'reviews_open'), 10, 2 );
			add_action( 'admin_init', array($this, 'disable_reviews_post_types_support'));
		}
	}

	public function skip_comment_count_query( $count, $post_id ) {
        // if ( 0 === $post_id ) {
                $stats = array(
                        'approved'       => 0,
                        'moderated'      => 0,
                        'spam'           => 0,
                        'trash'          => 0,
                        'post-trashed'   => 0,
                        'total_comments' => 0,
                        'all'            => 0,
                );
                return (object) $stats;
        // }
	}

	public function disable_comments_post_types_support() {
		$post_types = get_post_types();
		foreach ($post_types as $post_type) {
			if(post_type_supports($post_type, 'comments')) {
				if ($post_type != 'product' ) remove_post_type_support($post_type, 'comments');
				remove_post_type_support($post_type, 'trackbacks');
			}
		}
	}

	public function comments_open( $open, $post_id ) {
		// $post = get_post( $post_id );
		if ( 'product' != get_post_type($post_id) )
			$open = false;
		return $open;
	}

	public function disable_comments_hide_existing_comments($comments, $post_id) {
		$post = get_post( $post_id );
		if ( 'product' != $post->post_type )
			$comments = array();
		return $comments;
	}

	public function disable_comments_admin_menu() {
		remove_menu_page('edit-comments.php');
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	public function disable_comments_admin_menu_redirect() {
		global $pagenow;
		if ($pagenow === 'edit-comments.php' || $pagenow === 'options-discussion.php') {
			wp_redirect(admin_url()); exit;
		}
	}

	public function disable_comments_dashboard() {
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
	}

	public function remove_comments_admin_bar_links() {
		remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('comments');
	}

	public static function disallow_post_type_post(){
		global $pagenow, $wp;
		switch( $pagenow ) {
			case 'edit.php':
			case 'edit-tags.php':
			case 'post-new.php':
				if ( !array_key_exists('post_type', $_GET) && !array_key_exists('taxonomy', $_GET) ) {
					wp_safe_redirect( get_admin_url(), 301 );
					exit;
				}
				break;
		}
	}

	public static function remove_post_type_post(){
		global $menu, $submenu;
		/*
			edit.php
			post-new.php
			edit-tags.php?taxonomy=category
			edit-tags.php?taxonomy=post_tag
		 */
		$done = false;
		foreach( $menu as $k => $v ) {
			foreach($v as $key => $val) {
				switch($val) {
					case 'Posts':
						unset($menu[$k]);
						$done = true;
						break;
				}
			}

			/* bail out as soon as we are done */
			if ( $done ) {
				break;
			}
		}

		$done = false;
		foreach( $submenu as $k => $v ) {
			switch($k) {
				case 'edit.php':
					unset($submenu[$k]);
					$done = true;
					break;
			}

			/* bail out as soon as we are done */
			if ( $done ) {
				break;
			}
		}
	}

	public static function check_post_type( $posts = array() ){
		global $wp_query;

		$look_for = "wp_posts.post_type = 'post'";
		$instance = strpos( $wp_query->request, $look_for );
		if ( $instance !== false ) {
			$posts = array(); // we are querying for post type `post`
		}

		return $posts;
	}

	public static function remove_from_search_filter( $query ){
		$post_types = get_post_types();

		if ( array_key_exists('post', $post_types) ) {
			/* exclude post_type `post` from the query results */
			unset( $post_types['post'] );
		}
		$query->set( 'post_type', array_values($post_types) );

		return $query;
	}

	public static function remove_wp_nodes() {
	    global $wp_admin_bar;
	    $wp_admin_bar->remove_node( 'new-post' );
	    $wp_admin_bar->remove_node( 'new-link' );
	    // $wp_admin_bar->remove_node( 'new-media' );
	}

	// RENAME Posts
	public function change_post_label() {
	    global $menu;
	    global $submenu;
	    $menu[5][0] = __('Blog', BW_TD);
	    $submenu['edit.php'][5][0] = __('Blog', BW_TD);
	    $submenu['edit.php'][10][0] = __('Add Blog Post', BW_TD);
	    $submenu['edit.php'][16][0] = __('Blog Tags', BW_TD);
	    echo '';
	}

	public function change_post_object() {
	    global $wp_post_types;
	    $labels = &$wp_post_types['post']->labels;
	    $labels->name = __('Blog Posts', BW_TD);
	    $labels->singular_name = __('Blog Post', BW_TD);
	    $labels->add_new = __('Add Blog Post', BW_TD);
	    $labels->add_new_item = __('Add Blog Post', BW_TD);
	    $labels->edit_item = __('Edit Blog Post', BW_TD);
	    $labels->new_item = __('Blog Post', BW_TD);
	    $labels->view_item = __('View Blog Post', BW_TD);
	    $labels->search_items = __('Search Blog Posts', BW_TD);
	    $labels->not_found = __('No Blog Posts found', BW_TD);
	    $labels->not_found_in_trash = __('No Blog Posts found in Trash', BW_TD);
	    $labels->all_items = __('All Blog Posts', BW_TD);
	    $labels->menu_name = __('Blog Posts', BW_TD);
	    $labels->name_admin_bar = __('Blog Posts', BW_TD);
	}

	// DISABLE REVIEWS
	public function reviews_open( $open, $post_id ) {
		// $post = get_post( $post_id );
		if ( 'product' == get_post_type($post_id) )
			$open = false;
		return $open;
	}

	public function disable_reviews_post_types_support() {
		$post_types = get_post_types();
		foreach ($post_types as $post_type) {
			if(post_type_supports($post_type, 'comments')) {
				if ($post_type == 'product' ) remove_post_type_support($post_type, 'comments');
			}
		}
	}
}

$GLOBALS['BWD_Blog_Bootstrap'] = new BWD_Blog_Bootstrap;
