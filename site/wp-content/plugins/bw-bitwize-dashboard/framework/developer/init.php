<?php

include(dirname( __FILE__ ) . '/redux/framework.php');

if(!bwd_get_option('bwquerymon')){
	if ( ! file_exists( $db = WP_CONTENT_DIR . '/db.php' ) and function_exists( 'symlink' ) ) {
		@symlink( dirname(__FILE__) . '/bwqm/wp-content/db.php' , $db );
	}
	include(dirname( __FILE__ ) . '/bwqm/bwqm.php');
} else {
	if(file_exists(WP_CONTENT_DIR . '/db.php')){
		if ( class_exists( 'BWQM_DB' ) ) {
			unlink( WP_CONTENT_DIR . '/db.php' );
		}
	}
}

if(bwd_get_option('metabox'))
	include(dirname( __FILE__ ) . '/meta-box/meta-box.php');

