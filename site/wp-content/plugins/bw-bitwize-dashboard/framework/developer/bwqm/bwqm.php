<?php

defined( 'ABSPATH' ) or die();
if ( defined( 'BWQM_DISABLED' ) and BWQM_DISABLED ) return;
if ( defined( 'WP_CLI' ) and WP_CLI ) return;

$bwqm_dir = dirname( __FILE__ );
foreach ( array( 'Backtrace', 'Collectors', 'Collector', 'Plugin', 'Util', 'Dispatchers', 'Dispatcher', 'Output' ) as $bwqm_class ) {
	require_once "{$bwqm_dir}/classes/{$bwqm_class}.php";
}

class BW_QueryMonitor extends BWQM_Plugin {

	protected function __construct( $file ) {

		# Actions
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
		add_action( 'init',           array( $this, 'action_init' ) );
		add_action( 'shutdown',       array( $this, 'action_shutdown' ), 0 );

		# Filters
		add_filter( 'pre_update_option_active_plugins',               array( $this, 'filter_active_plugins' ) );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );

		# [Dea|A]ctivation
		register_activation_hook(   BITWIZE_CORE_PLUGIN_FILE, array( $this, 'activate' ) );
		add_action('admin_init', array( $this, 'activate' ) );
		register_deactivation_hook( BITWIZE_CORE_PLUGIN_FILE, array( $this, 'deactivate' ) );

		# Parent setup:
		parent::__construct( $file );

		# Load and register built-in collectors:
		BWQM_Util::include_files( $this->plugin_path( 'collectors' ) );

	}

	public function action_plugins_loaded() {

		# Register additional collectors:
		foreach ( apply_filters( 'bwqm/collectors', array(), $this ) as $collector ) {
			BWQM_Collectors::add( $collector );
		}

		# Dispatchers:
		BWQM_Util::include_files( $this->plugin_path( 'dispatchers' ) );

		# Register built-in and additional dispatchers:
		foreach ( apply_filters( 'bwqm/dispatchers', array(), $this ) as $dispatcher ) {
			BWQM_Dispatchers::add( $dispatcher );
		}

	}

	public function activate( $sitewide = false ) {

		if ( ! file_exists( $db = WP_CONTENT_DIR . '/db.php' ) and function_exists( 'symlink' ) ) {
			@symlink( $this->plugin_path( 'wp-content/db.php' ), $db );
		}

		/*
		if ( $sitewide ) {
			update_site_option( 'active_sitewide_plugins', get_site_option( 'active_sitewide_plugins'  ) );
		} else {
			update_option( 'active_plugins', get_option( 'active_plugins'  ) );
		}
		*/

	}

	public function deactivate() {

		# Only delete db.php if it belongs to Query Monitor
		if ( class_exists( 'BWQM_DB' ) ) {
			unlink( WP_CONTENT_DIR . '/db.php' );
		}

	}

	public function should_process() {

		# @TODO this decision should be moved to each dispatcher

		# Don't process if the minimum required actions haven't fired:

		if ( is_admin() ) {

			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}

		} else {

			if ( ! ( did_action( 'wp' ) or did_action( 'login_init' ) ) ) {
				return false;
			}

		}

		$e = error_get_last();

		# Don't process if a fatal has occurred:
		if ( ! empty( $e ) and ( $e['type'] & ( E_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR ) ) ) {
			return false;
		}

		# Allow users to disable the processing and output
		if ( ! apply_filters( 'bwqm/process', true, is_admin_bar_showing() ) ) {
			return false;
		}

		$dispatchers = BWQM_Dispatchers::init();

		foreach ( $dispatchers as $dispatcher ) {

			# At least one dispatcher is active, so we need to process:
			if ( $dispatcher->is_active() ) {
				return true;
			}

		}

		return false;

	}

	public function action_shutdown() {

		# @TODO this should move to each dispatcher so it can decide when it wants to do its output
		# eg. the JSON dispatcher needs to output inside the 'json_post_dispatch' filter, not on shutdown

		if ( ! $this->should_process() ) {
			return;
		}
		if(!is_admin()){
			if(!bwd_get_option('bwqm_fe')) return;
		}

		$collectors  = BWQM_Collectors::init();
		$dispatchers = BWQM_Dispatchers::init();

		foreach ( $collectors as $collector ) {
			$collector->tear_down();
			$collector->process();
		}

		foreach ( $dispatchers as $dispatcher ) {

			if ( ! $dispatcher->is_active() ) {
				continue;
			}

			$dispatcher->before_output();

			$outputters = apply_filters( "bwqm/outputter/{$dispatcher->id}", array(), $collectors );

			foreach ( $outputters as $outputter ) {
				$outputter->output();
			}

			$dispatcher->after_output();

		}

	}

	public function action_init() {

		$dispatchers = BWQM_Dispatchers::init();

		foreach ( $dispatchers as $dispatcher ) {
			$dispatcher->init();
		}

	}

	public function filter_active_plugins( $plugins ) {

		if ( empty( $plugins ) ) {
			return $plugins;
		}

		$f = preg_quote( basename( $this->plugin_base() ) );

		return array_merge(
			preg_grep( '/' . $f . '$/', $plugins ),
			preg_grep( '/' . $f . '$/', $plugins, PREG_GREP_INVERT )
		);

	}

	public function filter_active_sitewide_plugins( $plugins ) {

		if ( empty( $plugins ) ) {
			return $plugins;
		}

		$f = $this->plugin_base();

		if ( isset( $plugins[$f] ) ) {

			unset( $plugins[$f] );

			return array_merge( array(
				$f => time(),
			), $plugins );

		} else {
			return $plugins;
		}

	}

	public static function symlink_warning() {
		$db = WP_CONTENT_DIR . '/db.php';
		// trigger_error( sprintf( __( 'The symlink at %s is no longer pointing to the correct location. Please remove the symlink, then deactivate and reactivate Query Monitor.', BW_TD ), "<code>{$db}</code>" ), E_USER_WARNING );
	}

	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new BW_QueryMonitor( $file );
		}

		return $instance;

	}

}

BW_QueryMonitor::init( __FILE__ );
