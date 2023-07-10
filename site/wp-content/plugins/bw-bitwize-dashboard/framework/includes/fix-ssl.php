<?php
if(!class_exists('BW_Fix_SSL')):
class BW_Fix_SSL{

    public static function run() {

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
            $_SERVER['HTTPS'] = 'on';
        if (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false)
            $_SERVER['HTTPS'] = 'on';
        if(self::is_ssl()){
            add_filter('script_loader_src', array(__CLASS__, 'fixURL'));
            add_filter('style_loader_src', array(__CLASS__, 'fixURL'));
            add_filter('upload_dir', array(__CLASS__, 'uploadDir'));

            if (!is_admin()) {
                add_filter('wp_get_attachment_url', array(__CLASS__, 'fixURL'), 100);
            }
            add_filter( 'template_directory_uri', array(__CLASS__, 'fixURL'), 100);
    		add_filter( 'clean_url', array(__CLASS__, 'fixURL'), 100);

            add_action('after_setup_theme',array(__CLASS__, 'ob_starter') , 0);
        }

        if(bwd_get_option('force_secure_dashboard')){
            self::force_secure_dashboard();
        }
    }

    public static function is_ssl(){
        $ssl = (@$_SERVER["HTTPS"] == "on" || @$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 1 : 0;
        return $ssl;
    }

    public static function fixURL($url) {
        $url = str_replace('http:', '', $url);
        return $url;
    }

    public static function uploadDir($uploads) {
        $uploads['url'] = self::fixURL($uploads['url']);
        $uploads['baseurl'] = self::fixURL($uploads['baseurl']);
        return $uploads;
    }

    public static function ob_starter(){
        return ob_start(array(__CLASS__, 'html_filter')) ;
    }

    public static function html_filter($buffer){
        $buffer = str_replace(array('src="http://', "src='http://"), array('src="//', "src='//"), $buffer);
        return $buffer;
    }

    public static function force_secure_dashboard(){
        if(  strpos($_SERVER['REQUEST_URI'],'ss-login') !== false || ( strpos($_SERVER['REQUEST_URI'],'dashboard/') !== false && strpos($_SERVER['REQUEST_URI'],'dashboard/ajax') === false )){
            if(!self::is_ssl()){
                header("HTTP/1.1 301 Moved Permanently", true, 301);
                header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                exit();
            }
        }
    }
}

endif;

BW_Fix_SSL::run();

function bw_is_ssl(){
    return BW_Fix_SSL::is_ssl();
}
