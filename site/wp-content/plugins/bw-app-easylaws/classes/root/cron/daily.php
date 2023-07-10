<?php
$time_start = microtime(true);

header("Refresh: 15;url=".$_SERVER['REQUEST_URI']);
@ini_set('display_errors', true);

if ( !defined('ABSPATH') ) {
	if ( !defined('DISABLE_WP_CRON') ) define('DISABLE_WP_CRON', true);
	require_once( explode( 'wp-content', __FILE__ )[0] . 'wp-load.php' );
}

if (!defined('APP_VERSION')) wp_die('activate plugin!');

$secret = app_option('cron_secret');
if( (isset($_GET[$secret])) || (isset($_GET['secret']) && $_GET['secret'] == $secret) || (defined('APP_CRON_SECRET') && APP_CRON_SECRET == $secret)){

	echo 'Performing daily cron';
	flush();
	do_action('app_cron_daily');

} else {
	echo ('not allowed');
}
