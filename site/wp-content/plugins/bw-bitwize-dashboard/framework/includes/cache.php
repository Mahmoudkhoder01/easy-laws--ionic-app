<?php
	class BW_Cache {
		private $slug = "bw_cache";
		private $WP_con_DIR = "";
		private $System_Message = "";
		private $Options = array('Status' => true, 'NewPost' => true, 'TimeOut' => false); //hourly|twicedaily|daily
		private $Cronjob_Settings;
		private $Start_Time;
		private $Block_Cache = false;

		protected static $_instance = null;
		public static function instance() {
	        if (is_null(self::$_instance)) self::$_instance = new self();
	        return self::$_instance;
	    }

		public function init(){

			$this->Set_WP_Con_DIR();
			$this->Set_WP_Blog_Path();
			$this->Set_WP_Domain();

			if($this->enabled()){
				$this->Detect_New_Post();
				$this->Check_Cron_Time();
				if(is_admin()){
					$this->Set_Cronjob_Settings();
					add_action( '_core_updated_successfully', array( $this, 'deleteCache' ) );
			        add_action( 'switch_theme', array( $this, 'deleteCache' ) );
			        add_action( 'wp_trash_post', array( $this, 'deleteCache' ) );
					add_action( 'admin_bar_menu',  array($this, 'admin_bar_add'), 100 );
				} else {
					// $this->startCache();
					add_action('template_redirect', array($this, 'startCache'), 999);
				}

				add_action('admin_notices', array($this, 'notices'));
				add_action( 'add_meta_boxes', array($this, 'meta_box_add' ));
				add_action( 'save_post', array($this, 'meta_box_save' ));
				add_filter( 'the_content', array($this, 'add_no_cache'), 20);
			}

			add_action('admin_init', array($this, 'admin_init'));
		}

		public function admin_init(){
			if(is_admin()){
	            if(defined('DOING_AJAX') || defined('DOING_CRON')) return;
               	if(!current_user_can('can_bitwize')) return;
			    if(isset($_GET['bw_cache'])){
			    	if($_GET['bw_cache'] == 'activate'){
			    		$this->activate();
			    		wp_redirect( admin_url('options-general.php?page=BWDO') );
			    		exit;
			    	}
			    	if($_GET['bw_cache'] == 'deactivate'){
			    		$this->deactivate();
			    		wp_redirect( admin_url('options-general.php?page=BWDO') );
			    		exit;
			    	}
			    	if($_GET['bw_cache'] == 'delete') $this->deleteCache();
			    }
			}
		}

		public function meta_box_add(){
			$types = get_post_types(array('public'   => true));
			foreach($types as $type){
				add_meta_box( 'bwchace_meta_box', 'Cache Options', array($this, 'meta_box_callback'), $type, 'side', 'high' );
			}
		}

		public function meta_box_callback(){
			global $post;
		    $values = get_post_custom( $post->ID );
		    $cache_check = isset($values['bwcache_meta_box_check']) ? esc_attr($values['bwcache_meta_box_check'][0]) : 'off';
		    echo '
		    <p>
		        <input type="checkbox" id="bwcache_meta_box_check" name="bwcache_meta_box_check" '.checked( $cache_check, 'on', false ).' />
		        <label for="bwcache_meta_box_check">Don\'t Cache this Post</label>
		    </p>
		    ';
		    wp_nonce_field( 'bwcache_meta_box_check', 'bwcache_meta_box_nonce' );
		}

		public function meta_box_save($post_id){
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
			if( !isset( $_POST['bwcache_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['bwcache_meta_box_nonce'], 'bwcache_meta_box_check' ) )
				return;
			if( !current_user_can( 'edit_post' ) ) return;
			$cache_check = isset($_POST['bwcache_meta_box_check']) ? 'on' : 'off';
    		update_post_meta( $post_id, 'bwcache_meta_box_check', $cache_check );
		}

		public function add_no_cache($content){
			global $post;
			$values = get_post_custom( $post->ID );
		    $cache_check = isset($values['bwcache_meta_box_check']) ? esc_attr($values['bwcache_meta_box_check'][0]) : 'off';
		    if($cache_check == 'on'){
		    	$content = $content.'[NoCache]';
		    }
		    return $content;
		}

		public function notices(){
			if($this->System_Message){
				echo '<div class="updated '.$this->System_Message[1].'" id="message"><p>'.$this->System_Message[0].'</p></div>';
			}
		}

		public function enabled(){
			return (get_option('bw_cache_status') == 1) ? true : false;
		}

		public function admin_bar_add(){
			global $wp_admin_bar;
			if($this->enabled()){
				$wp_admin_bar->add_menu( array(
					'id' => 'bw-cache-int',
					'parent' => 'top-secondary',
					'title' => '<i class="fa fa-trash-o"></i> Cache',
					'href' => add_query_arg('bw_cache', 'delete'),
					'meta'   => array('title' => 'Delete Cache'),
				) );
			}

		}

		public function checkShortCode($content){
			preg_match("/\[NoCache\]/", $content, $NoCache);
			if(count($NoCache) > 0){
				if(is_single() || is_page()){
					$this->Block_Cache = true;
				}
				$content = str_replace("[NoCache]", "", $content);
			}
			return $content;
		}

		public function activate(){
			update_option('bw_cache_status', '1');
			$this->addCacheTimeout();
			$this->System_Message = $this->modifyHtaccess();
		}

		public function deactivate(){
			update_option('bw_cache_status', '0');
			$this->modifyHtaccess(true);
			wp_clear_scheduled_hook($this->slug);
			$this->deleteCache();
		}

		public function Set_WP_Con_DIR(){
			$this->WP_con_DIR = ABSPATH."wp-content";
		}

		public function Set_WP_Blog_Path(){
			if( is_multisite() == true ) {
				global $current_blog;
				if( is_subdomain_install() == true ) {
					$blog_path = $current_blog->domain;
				} else {
					$blog_path = $current_blog->path;
				}
				if( $blog_path == '/') {
					$blog_path = 'root';
				}
			} else {
				$blog_path = 'all';
			}
			$this->WP_blog_Path = $blog_path;
		}

		public function Set_WP_Domain(){
			if( is_multisite() == true ) {
				global $current_blog;
				if( is_subdomain_install() == true ) {
					$blog_url = $current_blog->domain;
				} else {
					$blog_url = $current_blog->domain.$current_blog->path;
				}
			} else {
				$blog_url =  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);
			}
			$this->WP_blog_Url = $blog_url;
		}

		public function Check_Cron_Time(){
			add_action($this->slug,  array($this, 'setSchedule'));
		}

		public function Detect_New_Post(){
			if($this->Options['NewPost'] && $this->Options['Status']){
				add_filter( 'publish_post',          array( $this, 'deleteCache' ) );
				add_filter( 'delete_post',           array( $this, 'deleteCache' ) );
				add_filter( 'publish_page',          array( $this, 'deleteCache' ) );
				add_filter( 'delete_page',           array( $this, 'deleteCache' ) );
				add_filter( 'switch_theme',          array( $this, 'deleteCache' ) );
				add_filter( 'wp_create_nav_menu',    array( $this, 'deleteCache' ) );
				add_filter( 'wp_update_nav_menu',    array( $this, 'deleteCache' ) );
				add_filter( 'wp_delete_nav_menu',    array( $this, 'deleteCache' ) );
				add_filter( 'save_post',             array( $this, 'deleteCache' ) );
				add_filter( 'trackback_post',        array( $this, 'deleteCache' ) );
				add_filter( 'pingback_post',         array( $this, 'deleteCache' ) );
				add_filter( 'comment_post',          array( $this, 'deleteCache' ) );
				add_filter( 'edit_comment',          array( $this, 'deleteCache' ) );
				add_filter( 'delete_comment',        array( $this, 'deleteCache' ) );
				add_filter( 'wp_set_comment_status', array( $this, 'deleteCache' ) );
				add_filter( 'create_term',           array( $this, 'deleteCache' ) );
				add_filter( 'edit_terms',            array( $this, 'deleteCache' ) );
				add_filter( 'delete_term',           array( $this, 'deleteCache' ) );
				add_filter( 'add_link',              array( $this, 'deleteCache' ) );
				add_filter( 'edit_link',             array( $this, 'deleteCache' ) );
				add_filter( 'delete_link',           array( $this, 'deleteCache' ) );
			}
		}

		public function deleteCache(){
			if(is_dir($this->WP_con_DIR."/cache/".$this->WP_blog_Path)){
				$this->rm_folder_recursively($this->WP_con_DIR."/cache/".$this->WP_blog_Path);
				$this->System_Message = array("All cache files have been deleted","success");
			}else{
				$this->System_Message = array( __( 'Cache deleted', BW_TD ),"success");
			}
		}

		public function addCacheTimeout(){
			if($this->Options["TimeOut"]){
				if($this->Options["TimeOut"]){
					wp_clear_scheduled_hook($this->slug);
					wp_schedule_event(time() + 120, $this->Options["TimeOut"], $this->slug);
				}else{
					wp_clear_scheduled_hook($this->slug);
				}
			}
		}

		public function setSchedule(){
			$this->deleteCache();
		}

		public function Set_Cronjob_Settings(){
			if(wp_next_scheduled($this->slug)){
				$this->Cronjob_Settings["period"] = wp_get_schedule($this->slug);
				$this->Cronjob_Settings["time"] = wp_next_scheduled($this->slug);
			}
		}

		public function rm_folder_recursively($dir, $i = 1) {
		    foreach(scandir($dir) as $file) {
		    	if($i > 500){
		    		return true;
		    	}else{
		    		$i++;
		    	}
		        if ('.' === $file || '..' === $file) continue;
		        if (is_dir("$dir/$file")) $this->rm_folder_recursively("$dir/$file", $i);
		        else unlink("$dir/$file");
		    }

		    rmdir($dir);
		    return true;
		}

		public function remove_marker( $filename, $marker ) {
			if (!file_exists( $filename ) || is_writeable( $filename ) ) {
				if (!file_exists( $filename ) ) {
					return '';
				} else {
					$markerdata = explode( "\n", implode( '', file( $filename ) ) );
				}

				$f = fopen( $filename, 'w' );
				$foundit = false;
				if ( $markerdata ) {
					$state = true;
					foreach ( $markerdata as $n => $markerline ) {
						if (strpos($markerline, '# BEGIN ' . $marker) !== false)
							$state = false;
						if ( $state ) {
							if ( $n + 1 < count( $markerdata ) )
								fwrite( $f, "{$markerline}\n" );
							else
								fwrite( $f, "{$markerline}" );
						}
						if (strpos($markerline, '# END ' . $marker) !== false) {
							$state = true;
						}
					}
				}
				return true;
			} else {
				return false;
			}
		}

		public function modifyHtaccess($remove = false){
			if($this->Options["Status"]){

				$rules = array();
		        $htaccess_file = ABSPATH . '.htaccess';

		        if ($remove === false) {
		            $rules = explode( "\n", $this->getHtaccess() );
		            $wprules = implode( "\n", extract_from_markers( $htaccess_file, 'WordPress' ) );
		            $this->remove_marker( $htaccess_file, 'WordPress' ); // remove original WP rules so our rules go on top

		            if(
		            	insert_with_markers( $htaccess_file, 'BWCache', $rules )  &&
		            	insert_with_markers( $htaccess_file, 'WordPress', explode( "\n", $wprules ) )
		            ) {
			        	return array( __( "Cache Activated", BW_TD ), "success");
			        } else {
			        	return array( __( ".htacces is not writable", BW_TD ), "error");
			        }

		        } else {
		        	if( insert_with_markers( $htaccess_file, 'BWCache', array() ) ) {
		        		return array( __( "Cache Deactivated", BW_TD ), "success");
		        	} else {
		        		return array( __( ".htacces is not writable", BW_TD ), "error");
		        	}
		        }
			}else{
				//disable
				$this->deleteCache();
				return array( __( "Options saved", BW_TD ), "success");
			}
		}

		public function getHtaccess(){
			$data = "<IfModule mod_rewrite.c>"."\n".
					"RewriteEngine On"."\n".
					"RewriteBase /"."\n".

					"RewriteCond %{HTTPS} !on"."\n".
					"RewriteCond %{HTTP:X-Forwarded-Proto} !https"."\n".
					"RewriteCond %{HTTP_USER_AGENT} !(facebookexternalhit|WhatsApp|Mediatoolkitbot)"."\n".
					"RewriteCond %{REQUEST_METHOD} !POST"."\n".
					"RewriteCond %{QUERY_STRING} !.+"."\n".
					"RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|app_logged_in|wp_woocommerce_session).*$"."\n".
					'RewriteCond %{HTTP:Profile} !^[a-z0-9\"]+ [NC]'."\n".
					"RewriteCond ".ABSPATH."wp-content/cache/".$this->WP_blog_Path."%{REQUEST_URI}index.html -f"."\n".
					'RewriteRule ^(.*) '.ABSPATH.'wp-content/cache/'.$this->WP_blog_Path.'%{REQUEST_URI}index.html [L]'."\n".

					"RewriteCond %{HTTPS} on [OR]"."\n".
					"RewriteCond %{HTTP:X-Forwarded-Proto} https"."\n".
					"RewriteCond %{HTTP_USER_AGENT} !(facebookexternalhit|WhatsApp|Mediatoolkitbot)"."\n".
					"RewriteCond %{REQUEST_METHOD} !POST"."\n".
					"RewriteCond %{QUERY_STRING} !.+"."\n".
					"RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|app_logged_in|wp_woocommerce_session).*$"."\n".
					'RewriteCond %{HTTP:Profile} !^[a-z0-9\"]+ [NC]'."\n".
					"RewriteCond ".ABSPATH."wp-content/cache/".$this->WP_blog_Path."%{REQUEST_URI}__ssl/index.html -f"."\n".
					'RewriteRule ^(.*) '.ABSPATH.'wp-content/cache/'.$this->WP_blog_Path.'%{REQUEST_URI}__ssl/index.html [L]'."\n".

					"</IfModule>";
			return $data;
		}

		public function getRewriteBase(){
			$tmp = str_replace($_SERVER['DOCUMENT_ROOT']."/", "", ABSPATH);
			$tmp = $tmp ? trailingslashit($tmp) : "";
			return $tmp;
		}

		public function startCache(){
			if($this->Options['Status']){
				$this->Start_Time = microtime(true);
				ob_start(array($this, "callback"));
			}
		}

		public function ignored(){
			$ignored = array("robots.txt", "wp-login.php", "ss-login", "wp-cron.php", "wp-content", "/sys/", "wp-admin", "/dashboard/", "wp-includes", "/cart/", "/checkout/");
			$ignored = apply_filters('bw_cache_ignored', $ignored);
			foreach ($ignored as $key => $value) {
				if (strpos($_SERVER["REQUEST_URI"], $value) !== false) {
					return true;
				}
			}
			return false;
		}

		public function callback($buffer){
			$buffer = $this->checkShortCode($buffer);
			$bypass_cache = apply_filters('bw_bypass_cache', false);
			if(defined('DONOTCACHEPAGE')){ // for Wordfence: not to cache 503 pages
				return $buffer;
			}else if(is_404()){
				return $buffer;
			}else if($this->ignored()){
				return $buffer;
			}else if($bypass_cache === true){
				return $buffer."<!-- not cached -->";
			}else if($this->Block_Cache === true){
				return $buffer."<!-- not cached -->";
			}else if(isset($_GET["preview"])){
				return $buffer."<!-- not cached -->";
			}else if($this->checkHtml($buffer)){
				return $buffer;
			}else{
				$cachFilePath = $this->WP_con_DIR."/cache/".$this->WP_blog_Path.$_SERVER["REQUEST_URI"];
				$content = $this->cacheDate($buffer);
				$this->createFolder($cachFilePath, $content);
				return $buffer;
			}
		}

		public function checkHtml($buffer){
			preg_match('/<\/html>/', $buffer, $htmlTag);
			preg_match('/<\/body>/', $buffer, $bodyTag);
			if(count($htmlTag) > 0 && count($bodyTag) > 0){
				return 0;
			}else{
				return 1;
			}
		}

		public function cacheDate($buffer){
			return $buffer."<!-- Cache generated in ".$this->creationTime()." seconds, on ".date("m-d-y G:i:s")." -->";
		}

		public function creationTime(){
			return microtime(true) - $this->Start_Time;
		}

		public function isCommenter(){
			$commenter = wp_get_current_commenter();
			return isset($commenter["comment_author_email"]) && $commenter["comment_author_email"] ? false : true;
		}

		public function createFolder($cachFilePath, $buffer, $extension = "html"){
			if(bw_is_ssl()) $cachFilePath = $cachFilePath.'/__ssl';

			if($buffer && strlen($buffer) > 100){
				if (!is_user_logged_in() && $this->isCommenter()){
					wp_mkdir_p($cachFilePath);
					if(is_dir($cachFilePath) && is_writeable($cachFilePath)){
						file_put_contents($cachFilePath."/index.".$extension, $buffer);
					}
				}
			}
		}
	}

	function BW_Cache() {return BW_Cache::instance();}
	$GLOBALS['bitwize_cache'] = BW_Cache();
	BW_Cache()->init();
