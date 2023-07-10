<?php
final class App
{
    public $user, $dev, $v, $base, $S3 = false;
    private static $_instance;
    public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    public function init() {
        $this->html_title = 'Sader App';
        $this->dev = AH()->is_dev();
        // $this->dev = false;

        $this->v = $this->dev ?  AH()->unique_id(8): APP_VERSION;
        $this->base = trailingslashit(wp_make_link_relative(home_url('', null)));

        $this->define();
        $this->init_files();

        // $this->user = AH()->user();

        add_action('template_redirect', array($this, 'force_ssl'));

        add_action('admin_menu', function(){
            if(!current_user_can('can_bitwize')){
                remove_menu_page( 'edit.php?post_type=page' );
            }
        });

        add_image_size( 'subjects-thumb', 500, 240, true );
        add_image_size( 'mobile-thumb', 300, 300, true );
        add_image_size( 'mobile-resized', 1000, 9999, false );

        add_action( 'generate_rewrite_rules', array( $this, 'add_rewrite_rules'));
    }

    public function define(){
        define('APP_PER_PAGE', 50);
        define('APP_URL', plugins_url('/classes/frontend', dirname(__FILE__)) );
        define('APP_DIR', dirname(dirname(__FILE__)).'/app');
        define('APP_AJAX', site_url('api/') );
        // define('APP_UPLOADS_URL', site_url("sys/uploads/accounts/") );
    }

    function add_rewrite_rules($wp_rewrite){
        $new_non_wp_rules['api/(.*)'] = str_ireplace(ABSPATH, '', __DIR__.'/root/api/index.php').'$1';
        
        $new_non_wp_rules['json/.*'] = str_ireplace(ABSPATH, '', dirname(__DIR__).'/json/index.php').'$1';

        $new_non_wp_rules['activate/(.*)'] = str_ireplace(ABSPATH, '', __DIR__.'/root/activate/index.php').'$1';
        $new_non_wp_rules['cron/daily/(.*)'] = str_ireplace(ABSPATH, '', __DIR__.'/root/cron/daily.php').'$1';
        $new_non_wp_rules['cron/worker/(.*)'] = str_ireplace(ABSPATH, '', __DIR__.'/root/cron/worker.php').'$1';
        $new_non_wp_rules['adout/(.*)'] = str_ireplace(ABSPATH, '', __DIR__.'/root/adout/index.php?id=$1');
        $wp_rewrite->non_wp_rules = array_merge($wp_rewrite->non_wp_rules, $new_non_wp_rules);
        return $wp_rewrite;
    }

    public function S3(){
        if(!$this->S3){
            $this->S3 = new S3(S3_ACCESS_KEY, S3_SECRET_KEY);
        }
        return $this->S3;
    }

    public function init_files(){
        // CREATE ROBOTS.TXT
        if ( ! file_exists( $robots = ABSPATH . 'robots.txt' ) ) {
            file_put_contents($robots, "User-agent: *\nAllow: /wp-content/uploads/\nDisallow: /wp-content/plugins/\nDisallow: /wp-admin/\nDisallow: /dashboard/\nDisallow: /wp-includes/");
        }
    }

    public function assets($file, $echo = true){
        global $APP;
        $u = APP_URL;
        if($APP['forcessl'] && !$this->dev){
            $u = str_replace('http:', 'https:', $u);
        }
        $return =  $u.'/assets/'.$file.'?_='.$this->v;
        if ($echo) {
            echo $return;
        } else {
            return $return;
        }
    }

    function force_ssl(){
        global $APP;
        if($APP['forcessl']){
            if ( !bw_is_ssl () && !$this->dev ) {
                wp_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
                exit();
            }
        }
    }

}

function app() {return App::instance();}
$GLOBALS['app'] = app();
app()->init();
