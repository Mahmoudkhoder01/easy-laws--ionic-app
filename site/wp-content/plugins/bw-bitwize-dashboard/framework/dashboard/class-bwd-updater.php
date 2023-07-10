<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class BWD_Updater_Skin extends WP_Upgrader_Skin {

	var $response;
	var $error;
	var $options;

	function __construct($args = array()) {
		parent::__construct($args);
		$this->response = array();
		$this->error = '';
	}

	function header() {
		if ( $this->done_header )
			return;
		$this->done_header = true;
	}

	function footer() {
		//echo json_encode($this->response);
	}

	function get_error() {
		return is_wp_error($this->error) ? $this->error : false;
	}

	function error($error) {
		if ( is_string($error) && isset( $this->upgrader->strings[$error] ) )
			$this->error = new WP_Error($error, $this->upgrader->strings[$error]);

		if ( is_wp_error($error) ) {
			$this->error = $error;
		}
	}

	function feedback($string) {
		if ( isset( $this->upgrader->strings[$string] ) )
			$string = $this->upgrader->strings[$string];

		if ( strpos($string, '%') !== false ) {
			$args = func_get_args();
			$args = array_splice($args, 1);
			if ( !empty($args) )
				$string = vsprintf($string, $args);
		}
		if ( empty($string) )
			return;
		$this->response[] = $string;
	}
	function before() {}
	function after() {}
}


class BWD_Updater {

	function __construct() {
   	}

   	public function stringify_error( $errors ) {
   		$result = array();
   		if ( is_wp_error($errors) && $errors->get_error_code() ) {
			foreach ( $errors->get_error_messages() as $message ) {
				if ( $errors->get_error_data() )
					$result[] = $message . ' ' . $errors->get_error_data();
				else
					$result[] = $message;
			}
		} else if ( false == $errors ) {
			$result[] = __("Operation failed.");
		}
		return implode( "\n", $result );
   	}

   	public function check_fsconnect( ) {
   		set_current_screen( 'bwd_dashboard' );
   		ob_clean();
   		ob_start();

   		$wp_upgrader = new WP_Upgrader( null );
   		$wp_upgrader->init();
        $connect_result = $wp_upgrader->fs_connect( array( WP_CONTENT_DIR, WP_PLUGIN_DIR ) );
        if (false == $connect_result || is_wp_error($connect_result)) {
        	ob_flush();
        	die();
        }
        ob_end_clean();
   	}

   	public function install($from) {
   		$response = array();
   		$response['result'] = false;

   		if ( ! current_user_can('install_plugins') )
			$response['msg'] = __( 'You do not have sufficient permissions to install plugins on this site.' );

		if (!empty($from)) {
			$skin = new BWD_Updater_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result = $upgrader->install($from);
			if ($skin->get_error()) {
				$result = $skin->get_error();
			}
			if ($result == null) {
				$response['msg'] = __( 'Plugin is already installed.' );
			}
			else if ( ! $result || is_wp_error($result) ) {
				$response['msg'] = $this->stringify_error( $result );
			} else {
				// Everything went well
				$response['result'] = true;
				$response['msg'] = __( 'Plugin installed successfully.' );
			}
		} else {
			$response['msg'] = __( 'Please select a plugin to install.' ) ;
		}
		return $response;
   	}

   	public function force_upgrade( $plugin_path ) {
		$plugin_transient = get_site_transient( 'update_plugins' );
		list( $plugin_folder, $plugin_file ) = explode( '/', $plugin_path );
		if ( $plugin_file ) {
			if ( ! function_exists( 'plugins_api' ) ) {
				include( ABSPATH . '/wp-admin/includes/plugin-install.php' );
			}
			$plugin_api = plugins_api( 'plugin_information', array( 'slug' => $plugin_folder, 'fields' => array( 'sections' => false, 'compatibility' => false, 'tags' => false ) ) );

			$temp_array = array(
				'slug'        => $plugin_folder,
				'new_version' => $plugin_api->version,
				// 'package'     => $plugin_api->download_link
				'package'     => untrailingslashit( BWD_END_POINT ) . $plugin_api->download_url
			);
			$temp_object = (object) $temp_array;
			$plugin_transient->response[ $plugin_path ] = $temp_object;
		}
		set_site_transient( 'update_plugins', $plugin_transient );
	}

   	public function upgrade($plugin) {
   		$response = array();
   		$response['result'] = false;

   		if ( ! current_user_can('update_plugins') )
			$response['msg'] = __( 'You do not have sufficient permissions to update plugins for this site.' );

		if (!empty($plugin)) {
			// FORCE UPDATE
			$this->force_upgrade($plugin);

			deactivate_plugins($plugin, false, ( is_multisite() && is_super_admin() ));

			$skin = new BWD_Updater_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result = $upgrader->upgrade($plugin);
			if ($skin->get_error()) {
				$result = $skin->get_error();
			}
			if ( is_wp_error($result) ) {
				$response['msg'] = $this->stringify_error( $result );
			} else if ($result === false) {
				$response['result'] = false;
				$response['msg'] = __( 'The plugin could not be upgraded.' );
			} else {
				$response['result'] = true;
				$response['msg'] = __( 'Plugin updated successfully.' );
			}
		} else {
			$response['msg'] = __( 'Please select a plugin to update.' ) ;
		}
		return $response;
   	}

   	public function activate($plugin) {
   		$response = array();
   		$response['result'] = false;

   		if ( ! current_user_can('activate_plugins') )
			$response['msg'] = __( 'You do not have sufficient permissions to manage plugins for this site.' );
		else {
	   		$result = activate_plugin($plugin, '', ( is_multisite() && is_super_admin() ));
			if ( is_wp_error($result) ) {
				if ( 'unexpected_output' == $result->get_error_code() ) {
					$response['result'] = true;
					$response['msg'] = __( 'Plugin activated successfully. But generated unexpected output. '.$result->get_error_data() );
				} else {
					$response['msg'] = $this->stringify_error( $result );
				}
			} else {
				if ( ! ( is_multisite() && is_super_admin() ) ) {
					$recent = (array) get_option( 'recently_activated' );
					unset( $recent[ $plugin ] );
					update_option( 'recently_activated', $recent );
				}
				$response['result'] = true;
				$response['msg'] = __( 'Plugin activated successfully.' );
			}
		}
		return $response;
   	}

   	public function deactivate($plugin) {
   		$response = array();
   		$response['result'] = false;

   		if($plugin == 'bw-bitwize-dashboard/bw-bitwize-dashboard.php'){
   			$response['msg'] = 'Unable to deactivate "Bitwize Dashboard" Plugin';
   			return $response;
   		}

   		if ( ! current_user_can('activate_plugins') ) {
			$response['msg'] = __( 'You do not have sufficient permissions to deactivate plugins for this site.' );
   		}
		else {
	   		deactivate_plugins($plugin, false, ( is_multisite() && is_super_admin() ));
			if ( ! ( is_multisite() && is_super_admin() ) ) {
				if (is_array($plugin)) {
					$deactivated = array();
					foreach ( $plugin as $slug ) {
						$deactivated[ $slug ] = time();
					}
				} else {
					$deactivated = array( $plugin => time() );
				}
				update_option( 'recently_activated', $deactivated + (array) get_option( 'recently_activated' ) );
			}
			$response['result'] = true;
			$response['msg'] = __( 'Plugin deactivated successfully.' );
		}
		return $response;
   	}

   	public function delete($plugin) {
   		$response = array();
   		$response['result'] = false;

   		if($plugin == 'bw-bitwize-dashboard/bw-bitwize-dashboard.php'){
   			$response['msg'] = 'Unable to delete "Bitwize Dashboard" Plugin';
   			return $response;
   		}

   		if ( ! current_user_can('delete_plugins') ) {
			$response['msg'] = __( 'You do not have sufficient permissions to delete plugins for this site.' );
   		}
		else {
			deactivate_plugins($plugin, false, ( is_multisite() && is_super_admin() ));
			$del_res = delete_plugins(array($plugin));
	   		if( $del_res == true){
				$response['result'] = true;
				$response['msg'] = __( 'Plugin deactivated successfully.' );
			}else{
				$response['msg'] = $del_res;
			}
		}
		return $response;
   	}
}