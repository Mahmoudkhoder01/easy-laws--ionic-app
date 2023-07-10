<?php
class App_PageTemplater {
		protected $plugin_slug;
        public $templates = array();

        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'init' ) );
        }

        public function init(){
            // $this->templates = array('goodtobebad-template.php' => 'It\'s Good to Be Bad');
            $this->templates = apply_filters('app_custom_page_templates', $this->templates);
        	add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'register_templates' ));
            add_filter( 'wp_insert_post_data', array( $this, 'register_templates' ) );
            add_filter( 'template_include', array( $this, 'view_template'), 11, 2 );
        }

        public function register_templates( $atts ) {
        	$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
        	$templates = wp_get_theme()->get_page_templates();
        	if ( empty( $templates ) ) $templates = array();

        	wp_cache_delete( $cache_key , 'themes');
        	$templates = array_merge( $templates, $this->templates );
        	wp_cache_add( $cache_key, $templates, 'themes', 1800 );
        	return $atts;
        }

        public function view_template( $template ) {
        	global $post;
        	if (!isset($this->templates[get_post_meta( $post->ID, '_wp_page_template', true )] ) ) {
        		return $template;
        	}
            $folder = APP_PLUGIN_DIR.'/frontend/theme/templates/';
        	$file = $folder . get_post_meta( $post->ID, '_wp_page_template', true );
            if( file_exists( $file ) ) $template = $file;
            return $template;
        }
}
$GLOBALS['App_PageTemplater'] = new App_PageTemplater;
