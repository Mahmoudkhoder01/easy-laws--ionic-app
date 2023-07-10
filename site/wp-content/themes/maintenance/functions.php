<?php

show_admin_bar(false);

define('THEME_URL', get_template_directory_uri());
add_action( 'after_setup_theme', function() {
    add_theme_support( 'woocommerce' );
});

include(dirname(__FILE__).'/options.php');

add_action('wp', function(){
    if( !is_admin() && !is_404() && !in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' )) ){
        include(dirname(__FILE__).'/template.php');
        exit();
    }
});
