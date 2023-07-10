<?php
class App_Pages {
    private static $_instance;
    public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self(); return self::$_instance;
    }

    public function __construct(){
        // delete_option('app_pages');
        add_action('init', array($this, 'setup_pages'));
        add_shortcode('app', array($this, 'app_new'));

        add_filter( 'query_vars', array($this, 'query_vars' ));
        add_action( 'init', array($this, 'rules'));
    }

    public function query_vars($query_vars){
        foreach($this->__rules()['tags'] as $tag){
            $query_vars[] = $tag;
        }
        return $query_vars;
    }

    function __rules(){
        $sitemaps_path = str_ireplace(ABSPATH, '', dirname(__DIR__) . '/frontend/sitemap');
        $rules = [
            'q/([^/]*)/?' => 'index.php?pagename=app&action=question&ID=$matches[1]',
            'question/([^/]*)/?' => 'index.php?pagename=app&action=question&ID=$matches[1]',
            'subject/([^/]*)/?' => 'index.php?pagename=app&action=subject&ID=$matches[1]',
            'tag/([^/]*)/?' => 'index.php?pagename=app&action=tag&ID=$matches[1]',
            'favorites/([^/]*)/?' => 'index.php?pagename=app&action=favorites&ID=$matches[1]',

            'search/?' => 'index.php?pagename=app&action=search',
            'subjects/?' => 'index.php?pagename=app&action=subjects',
            'tags/?' => 'index.php?pagename=app&action=tags',
            'profile/?' => 'index.php?pagename=app&action=profile',
            'logout/?' => 'index.php?pagename=app&action=logout',
            'contact/?' => 'index.php?pagename=app&action=contact',
            'request/?' => 'index.php?pagename=app&action=request',
            'history/?' => 'index.php?pagename=app&action=history',
            'favorites/?' => 'index.php?pagename=app&action=favorites&ID=subjects',
            
            'home/?' => 'index.php?pagename=app&action=home',

            'main-sitemap.xsl' => $sitemaps_path.'/template.php',
            'sitemap.xml' => $sitemaps_path.'/index.php',
            'questions-sitemap.xml' => $sitemaps_path.'/questions.php',
            'subjects-sitemap.xml' => $sitemaps_path.'/subjects.php',
            'pages-sitemap.xml' => $sitemaps_path.'/pages.php',
        ];
        $tags = [ 'ID', 'action' ];
        return [ 'rules' => $rules, 'tags' => $tags ];
    }

    public function rules(){
        foreach($this->__rules()['rules'] as $k => $v){
            add_rewrite_rule($k, $v, 'top');
        }
        foreach($this->__rules()['tags'] as $tag){
            add_rewrite_tag($tag,'([^&]+)');
        }
        
    }

    public function app( $args=array(), $content=null ) {
        ob_start();
        do_action('the_app_before');
        include APP_PLUGIN_DIR . '/classes/frontend/index.php';
        do_action('the_app_after');
        $output = ob_get_clean();
        return $output;
    }

    public function app_new( $args=array(), $content=null ) {
        // include dirname(__DIR__) . '/frontend/__init.php';
        ob_start();
        
        if( !get_query_var('action') ) set_query_var('action', 'home');
        do_action('__app_header');
            do_action('__app');
        do_action('__app_footer');        
        
		$output = ob_get_clean();
		return $output;
	}

    public function setup_pages(){
        if(defined('DOING_AJAX') && DOING_AJAX) return;
        if(is_admin()) $this->create_pages('app_pages');
    }

    public function create_pages($slug){
    	$pages = get_option($slug, false);
        if (!$pages) {
            $app_page = array(
				'post_title'  		=> 'App',
                'post_content' 		=> '[app]',
				'post_name'			=> 'app',
				'comment_status' 		=> 'closed',
				'post_type'     		=> 'page',
				'post_status'   		=> 'publish',
				'post_author'   		=> 1,
			);
			$app_page = wp_insert_post( $app_page );
			$pages['app'] = $app_page;
            update_option($slug, $pages);
        }
    }
}

App_Pages::instance();
