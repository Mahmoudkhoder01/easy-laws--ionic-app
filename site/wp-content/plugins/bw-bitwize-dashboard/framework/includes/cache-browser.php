<?php

class BW_Browser_Cache_HTAccess{

    function __construct(){
    	// register_activation_hook(BITWIZE_CORE_PLUGIN_FILE, array($this, 'activate'));
    	// register_deactivation_hook(BITWIZE_CORE_PLUGIN_FILE, array($this, 'deactivate'));
    	add_action('admin_init', array($this, 'admin_init'));
    }

    function admin_init(){
        if(defined('DOING_AJAX') || defined('DOING_CRON')) return;
        if(!current_user_can('can_bitwize')) return;
        if(isset($_GET['bw_cache'])){
            if($_GET['bw_cache'] == 'activate_browser'){
                $this->activate();
                wp_redirect( admin_url('options-general.php?page=BWDO') );
                exit;
            }
            if($_GET['bw_cache'] == 'deactivate_browser'){
                $this->deactivate();
                wp_redirect( admin_url('options-general.php?page=BWDO') );
                exit;
            }
        }
    }

    function activate(){
        update_option('bw_browser_cache_status', '1');
    	$this->flush();
    }

    function deactivate(){
        update_option('bw_browser_cache_status', '0');
    	$this->flush(true);
    }

    function flush($remove = false){
        if (!$GLOBALS['is_apache']) {
            return;
        }

        $rules = array();
        $htaccess_file = ABSPATH . '.htaccess';

        if ($remove === false) {
            $rules = explode( "\n", $this->marker() );
        }

        return insert_with_markers( $htaccess_file, 'BrowserCache', $rules );
    }

    function marker(){
        $marker = '';
        $marker.= $this->charset();
        $marker.= $this->etag();
        $marker.= $this->web_fonts_access();
        $marker.= $this->files_match();
        $marker.= $this->mod_expires();
        $marker.= $this->mod_deflate();
        $marker = str_replace("\n\n", "\n", $marker);
        return $marker;
    }

    function old_flush($force = false) {
        if (!$GLOBALS['is_apache']) {
            return;
        }

        $rules = '';
        $htaccess_file = ABSPATH . '.htaccess';

        if (is_writable($htaccess_file)) {
            $ftmp = file_get_contents($htaccess_file);
            $ftmp = preg_replace('/# BEGIN BWBROWSERCACHE(.*)# END BWBROWSERCACHE/isU', '', $ftmp);
            $ftmp = str_replace("\n\n", "\n", $ftmp);
            if ($force === false) {
                $rules = $this->marker();
            }
            file_put_contents($htaccess_file, $rules . $ftmp);
        }
    }

    function old_marker() {
        $marker = '# BEGIN BWBROWSERCACHE'."\n";
        $marker.= $this->charset();
        $marker.= $this->etag();
        $marker.= $this->web_fonts_access();
        $marker.= $this->files_match();
        $marker.= $this->mod_expires();
        $marker.= $this->mod_deflate();
        $marker.= '# END BWBROWSERCACHE'."\n"."\n";

        return $marker;
    }

    function mod_deflate() {
        $rules = '<IfModule mod_deflate.c>'."\n";
        $rules.= 'SetOutputFilter DEFLATE'."\n";
        $rules.= '<IfModule mod_setenvif.c>'."\n";
        $rules.= '<IfModule mod_headers.c>'."\n";
        $rules.= 'SetEnvIfNoCase ^(Accept-EncodXng|X-cept-Encoding|X{15}|~{15}|-{15})$ ^((gzip|deflate)\s*,?\s*)+|[X~-]{4,13}$ HAVE_Accept-Encoding'."\n";
        $rules.= 'RequestHeader append Accept-Encoding "gzip,deflate" env=HAVE_Accept-Encoding'."\n";
        $rules.= 'SetEnvIfNoCase Request_URI \\'."\n";
        $rules.= '\\.(?:gif|jpe?g|png|rar|zip|exe|flv|mov|wma|mp3|avi|swf|mp?g)$ no-gzip dont-vary'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= '<IfModule mod_filter.c>'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE application/atom+xml'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE application/javascript'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE application/json'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE application/rss+xml'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE application/vnd.ms-fontobject'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE application/x-font-ttf'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE application/xhtml+xml'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE application/xml'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE font/opentype'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE image/svg+xml'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE image/x-icon'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE text/css'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE text/html'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE text/plain'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE text/x-component'."\n";
        $rules.= 'AddOutputFilterByType DEFLATE text/xml'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= '<IfModule mod_headers.c>'."\n";
        $rules.= 'Header append Vary User-Agent env=!dont-vary'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= '</IfModule>'."\n";
        return $rules;
    }

    function mod_expires() {
        $rules = '<IfModule mod_expires.c>'."\n";
        $rules.= 'ExpiresActive on'."\n";
        $rules.= 'ExpiresDefault                          "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType text/cache-manifest       "access plus 0 seconds"'."\n";
        $rules.= 'ExpiresByType text/html                 "access plus 0 seconds"'."\n";
        $rules.= 'ExpiresByType text/xml                  "access plus 0 seconds"'."\n";
        $rules.= 'ExpiresByType application/xml           "access plus 0 seconds"'."\n";
        $rules.= 'ExpiresByType application/json          "access plus 0 seconds"'."\n";
        $rules.= 'ExpiresByType application/rss+xml       "access plus 1 hour"'."\n";
        $rules.= 'ExpiresByType application/atom+xml      "access plus 1 hour"'."\n";
        $rules.= 'ExpiresByType image/x-icon              "access plus 1 week"'."\n";
        $rules.= 'ExpiresByType image/gif                 "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType image/png                 "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType image/jpeg                "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType video/ogg                 "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType audio/ogg                 "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType video/mp4                 "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType video/webm                "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType text/x-component          "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType application/x-font-ttf    "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType font/opentype             "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType application/x-font-woff   "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType image/svg+xml             "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType application/vnd.ms-fontobject "access plus 1 month"'."\n";
        $rules.= 'ExpiresByType text/css                  "access plus 1 year"'."\n";
        $rules.= 'ExpiresByType application/javascript    "access plus 1 year"'."\n";
        $rules.= '</IfModule>'."\n";
        return $rules;
    }

    function charset() {
        $charset = preg_replace('/[^a-zA-Z0-9_\-\.:]+/', '', get_bloginfo('charset', 'display'));
        $rules = "AddDefaultCharset $charset"."\n";
        $rules.= "<IfModule mod_mime.c>"."\n";
        $rules.= "AddCharset $charset .atom .css .js .json .rss .vtt .xml"."\n";
        $rules.= "</IfModule>"."\n";
        return $rules;
    }

    function files_match() {
        $rules = '<IfModule mod_alias.c>'."\n";
        $rules.= '<FilesMatch "\.(html|htm|rtf|rtx|svg|svgz|txt|xsd|xsl|xml)$">'."\n";
        $rules.= '<IfModule mod_headers.c>'."\n";
        $rules.= 'Header set X-Powered-By "BW Cache Engine"'."\n";
        $rules.= 'Header unset Pragma'."\n";
        $rules.= 'Header append Cache-Control "public"'."\n";
        $rules.= 'Header unset Last-Modified'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= '</FilesMatch>'."\n";
        $rules.= '<FilesMatch "\.(css|htc|js|asf|asx|wax|wmv|wmx|avi|bmp|class|divx|doc|docx|eot|exe|gif|gz|gzip|ico|jpg|jpeg|jpe|json|mdb|mid|midi|mov|qt|mp3|m4a|mp4|m4v|mpeg|mpg|mpe|mpp|otf|odb|odc|odf|odg|odp|ods|odt|ogg|pdf|png|pot|pps|ppt|pptx|ra|ram|svg|svgz|swf|tar|tif|tiff|ttf|ttc|wav|wma|wri|xla|xls|xlsx|xlt|xlw|zip)$">'."\n";
        $rules.= '<IfModule mod_headers.c>'."\n";
        $rules.= 'Header unset Pragma'."\n";
        $rules.= 'Header append Cache-Control "public"'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= '</FilesMatch>'."\n";
        $rules.= '</IfModule>'."\n";
        return $rules;
    }

    function etag() {
        $rules = '<IfModule mod_headers.c>'."\n";
        $rules.= 'Header unset ETag'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= 'FileETag None'."\n";
        return $rules;
    }

    function web_fonts_access() {
        $rules = '<IfModule mod_setenvif.c>'."\n";
        $rules.= '<IfModule mod_headers.c>'."\n";
        $rules.= '<FilesMatch "\.(gif|png|jpe?g|svg|svgz|ico|webp)$">'."\n";
        $rules.= 'SetEnvIf Origin ":" IS_CORS'."\n";
        $rules.= 'Header set Access-Control-Allow-Origin "*" env=IS_CORS'."\n";
        $rules.= '</FilesMatch>'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= '<FilesMatch "\.(eot|otf|tt[cf]|woff)$">'."\n";
        $rules.= '<IfModule mod_headers.c>'."\n";
        $rules.= 'Header set Access-Control-Allow-Origin "*"'."\n";
        $rules.= '</IfModule>'."\n";
        $rules.= '</FilesMatch>'."\n";
        return $rules;
    }
}

new BW_Browser_Cache_HTAccess;
