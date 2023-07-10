<?php

class BWD_Mapping {
	public function __construct(){
		if(is_admin()) return;
		remove_filter('template_redirect','redirect_canonical');
		add_action( 'wp_loaded', [$this, 'ob_starter'], 11 );
	}

	function replace($url){
		$old = parse_url( get_option( 'siteurl' ) , PHP_URL_HOST);
		$new = $_SERVER['HTTP_HOST'];
		return str_replace($old, $new, $url);
	}

	function ob_starter(){
		ob_start( [$this, "ob_starter_filter"] ) ;
	}

	function ob_starter_filter($buffer){
		return $this->replace($buffer);
	}
}

if( !bwd_get_option('use_mappings') ) {
	new BWD_Mapping;
}
