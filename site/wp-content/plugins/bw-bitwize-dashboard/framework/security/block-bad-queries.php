<?php

if (!defined('ABSPATH')) die();

add_action('plugins_loaded', function(){

	$request_uri_array  = apply_filters('bbq_request_uri_items',  array('eval\(', 'UNION(.*)SELECT', '\(null\)', 'base64_', '\/localhost', '\%2Flocalhost', '\/pingserver', '\/config\.', '\/wwwroot', '\/makefile', 'crossdomain\.', 'proc\/self\/environ', 'etc\/passwd', '\/https\:', '\/http\:', '\/ftp\:', '\/cgi\/', '\.cgi', '\.exe', '\.sql', '\.ini', '\.dll', '\.asp', '\.jsp', '\/\.bash', '\/\.git', '\/\.svn', '\/\.tar', ' ', '\<', '\>', '\/\=', '\.\.\.', '\+\+\+', '\:\/\/', '\/&&', '\/Nt\.', '\;Nt\.', '\=Nt\.', '\,Nt\.', '\.exec\(', '\)\.html\(', '\{x\.html\(', '\(function\(', '\.php\([0-9]+\)'));
	$query_string_array = apply_filters('bbq_query_string_items', array('\.\.\/', '127\.0\.0\.1', 'localhost', 'loopback', '\%0A', '\%0D', '\%00', '\%2e\%2e', 'input_file', 'execute', 'mosconfig', 'path\=\.', 'mod\=\.', 'wp-config\.php'));
	$user_agent_array   = apply_filters('bbq_user_agent_items',   array('acapbot', 'binlar', 'casper', 'cmswor', 'diavol', 'dotbot', 'finder', 'flicky', 'morfeus', 'nutch', 'planet', 'purebot', 'pycurl', 'semalt', 'skygrid', 'snoopy', 'sucker', 'turnit', 'vikspi', 'zmeu'));

	$request_uri_string  = false;
	$query_string_string = false;
	$user_agent_string   = false;

	if (isset($_SERVER['REQUEST_URI'])     && !empty($_SERVER['REQUEST_URI']))     $request_uri_string  = $_SERVER['REQUEST_URI'];
	if (isset($_SERVER['QUERY_STRING'])    && !empty($_SERVER['QUERY_STRING']))    $query_string_string = $_SERVER['QUERY_STRING'];
	if (isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])) $user_agent_string   = $_SERVER['HTTP_USER_AGENT'];

	if ($request_uri_string || $query_string_string || $user_agent_string) {
		if (
			// strlen( $_SERVER['REQUEST_URI'] ) > 255 || // optional
			preg_match( '/' . implode( '|', $request_uri_array )  . '/i', $request_uri_string ) ||
			preg_match( '/' . implode( '|', $query_string_array ) . '/i', $query_string_string ) ||
			preg_match( '/' . implode( '|', $user_agent_array )   . '/i', $user_agent_string )
		) {
			header('HTTP/1.1 403 Forbidden');
			header('Status: 403 Forbidden');
			header('Connection: Close');
			exit;
		}
	}
});
