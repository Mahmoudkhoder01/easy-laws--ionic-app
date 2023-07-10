<?php
	if ( ! defined( 'ABSPATH' ) ) die( '-1' );
	#-----------------------------------------------------------------#
	# Bitwize VC
	#-----------------------------------------------------------------#

	// include_once(dirname(__FILE__).'/vc-equal-height-columns/vc-equal-height-columns.php');
	include_once(dirname(__FILE__).'/row-parallax/row-parallax.php');
	include_once(dirname(__FILE__).'/row-separators/row-separators.php');

	if (!class_exists('BW_VC_Manager')) {
		class BW_VC_Manager{
			function __construct(){
				add_action('init', array($this,'add_bw_to_vc'));
				add_action('admin_enqueue_scripts', array($this,'bw_vc_styles'), 999);

				add_action('do_meta_boxes', array($this,'vc_teaser_remove'));
			}

			function add_bw_to_vc(){
				require_once dirname( __FILE__ ) . '/bw-strip-vc.php';
				if(!bwd_get_option('vc_frontend')){
					vc_disable_frontend();
				}
				// vc_disable_frontend();
				vc_manager()->setIsAsTheme(true);
				vc_manager()->disableUpdater(true);
				if ( vc_mode() === 'page_editable' ){
					add_action('wp_enqueue_scripts', array($this,'bw_vc_fe_styles'), 999);
				}
			}

			function bw_vc_styles() {
				wp_enqueue_style('bw_vc_hook', plugins_url('bw-vc-be.css',__FILE__), array(), BWD_VERSION, 'all');
			}
			function bw_vc_fe_styles() {
				wp_enqueue_style('bw_vc_hook_fe', plugins_url('bw-vc-fe.css',__FILE__), array(), BWD_VERSION, 'all');
			}

			function vc_teaser_remove(){
			    $post_types = get_post_types( '', 'names' );
		        foreach ( $post_types as $post_type ) {
		          remove_meta_box( 'vc_teaser',  $post_type, 'side' );
		        }
			}
		}
	}
	$GLOBALS['BW_VC_Manager'] = new BW_VC_Manager();
