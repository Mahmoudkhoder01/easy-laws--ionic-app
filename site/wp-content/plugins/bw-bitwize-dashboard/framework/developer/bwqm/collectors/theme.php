<?php


class BWQM_Collector_Theme extends BWQM_Collector {

	public $id = 'theme';

	public function name() {
		return __( 'Theme', BW_TD );
	}

	public function __construct() {
		parent::__construct();
		add_filter( 'body_class', array( $this, 'filter_body_class' ), 99 );
	}

	public function filter_body_class( $class ) {
		$this->data['body_class'] = $class;
		return $class;
	}

	public function process() {

		global $template;

		$template_path        = BWQM_Util::standard_dir( $template );
		$stylesheet_directory = BWQM_Util::standard_dir( get_stylesheet_directory() );
		$template_directory   = BWQM_Util::standard_dir( get_template_directory() );
		$theme_directory      = BWQM_Util::standard_dir( get_theme_root() );

		$template_file  = str_replace( array( $stylesheet_directory, $template_directory ), '', $template_path );
		$template_file  = ltrim( $template_file, '/' );
		$theme_template = str_replace( $theme_directory, '', $template_path );
		$theme_template = ltrim( $theme_template, '/' );

		$this->data['template_path']  = $template_path;
		$this->data['template_file']  = $template_file;
		$this->data['theme_template'] = $theme_template;
		$this->data['stylesheet']     = get_stylesheet();
		$this->data['template']       = get_template();

		if ( isset( $this->data['body_class'] ) ) {
			asort( $this->data['body_class'] );
		}

	}

}

function register_bwqm_collector_theme( array $collectors, BW_QueryMonitor $bwqm ) {
	$collectors['theme'] = new BWQM_Collector_Theme;
	return $collectors;
}

if ( !is_admin() ) {
	add_filter( 'bwqm/collectors', 'register_bwqm_collector_theme', 10, 2 );
}
