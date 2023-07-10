<?php

define( 'APP_DOING_AJAX', true );
define( 'DOING_AJAX', true );

require_once( explode( 'wp-content', __FILE__ )[0] . 'wp-load.php' );

/** Allow for cross-domain requests (from the front end). */
send_origin_headers();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

$request = json_decode(file_get_contents('php://input'));
$action = !empty($request->action) ? $request->action : NULL;
if(is_null($action) || empty($action)) $action = $_REQUEST['action']; //hack to allow jQuery requests

if(is_null($action) || empty($action)) die('---');

if( stripos( untrailingslashit(wp_get_referer()), untrailingslashit(site_url()) ) === FALSE ) {
	if($_REQUEST['__ref'] != '__fromapp'){
		die('Go play with your MOM ;)');
	}
}

@header( 'Content-Type: text/html; charset=UTF-8' );
@header( 'X-Robots-Tag: noindex' );
send_nosniff_header();
nocache_headers();

do_action( 'app_ajax_' . $action );

die();
