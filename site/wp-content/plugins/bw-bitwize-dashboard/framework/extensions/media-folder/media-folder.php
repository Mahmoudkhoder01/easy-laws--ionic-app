<?php
// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );
if (!defined('WP_MEDIA_FOLDER_PLUGIN_DIR'))
    define('WP_MEDIA_FOLDER_PLUGIN_DIR', plugin_dir_path(__FILE__));

if ( ! defined( 'WPMF_FILE' ) ) {
	define( 'WPMF_FILE', __FILE__ );
}
define( 'WPMF_GALLERY_PREFIX', 'wpmf_gallery_' );
define( '_WPMF_GALLERY_PREFIX', '_wpmf_gallery_' );
define( 'WPMF_GALLERY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
if (is_admin()) {
        require_once( WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/class-media-folder.php' );
        $GLOBALS['wp_media_folder'] = new Wp_Media_Folder;
        require_once( WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-wp-foldel-option.php' );
        new Media_Folder_Option;
        require_once( WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/wpmf-display-own-media.php' );
        new Wpmf_Display_Own_Media;
}
$usegellery = get_option('wpmf_usegellery');
if(isset($usegellery) && $usegellery == 1){
    require_once( WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/wpmf-display-gallery.php' );
    new Wpmf_Display_Gallery;
}