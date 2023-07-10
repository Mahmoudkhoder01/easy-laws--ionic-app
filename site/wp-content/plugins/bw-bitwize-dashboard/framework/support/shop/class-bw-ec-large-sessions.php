<?php

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BW_WC_LARGE_SESSIONS_CACHE_GROUP', 'bw_ec_session_id' );
define( 'BW_WC_LARGE_SESSIONS_TABLE_NAME', 'shop_sessions' );

Class BW_WC_Large_Sessions {

	const VERSION = '1.0.0';
	protected static $instance = null;

	public function __construct() {
		add_filter( 'woocommerce_session_handler', array( $this, 'set_woocommerce_session_class' ), 10, 1 );
		if(get_option('bw_wc_large_sessions_installed') !== 'yes'){
			$this->install();
		}
	}

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function set_woocommerce_session_class( $class ) {
		require_once( dirname(__FILE__).'/class-bw-ec-large-sessions-handler.php' );
		$class = 'BW_WC_Large_Session_Handler';
		return $class;
	}

	public function install() {
		global $wpdb;
		$db_version = '1.0.0';

		$table_name = $wpdb->prefix . BW_WC_LARGE_SESSIONS_TABLE_NAME;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				session_id bigint(20) NOT NULL AUTO_INCREMENT,
				session_key char(32) NOT NULL,
				session_value longtext NOT NULL,
				session_expiry bigint(20) NOT NULL,
				UNIQUE KEY  session_id (session_id),
				PRIMARY KEY  session_key (session_key)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'wc_large_sessions_db_version', $db_version );
		update_option('bw_wc_large_sessions_installed', 'yes');

		// Clear previous cleanup sessions and schedule it to run every hour
		wp_clear_scheduled_hook( 'woocommerce_cleanup_sessions' );
		wp_schedule_event( time(), 'hourly', 'woocommerce_cleanup_sessions' );
	}
}

add_action( 'plugins_loaded', array( 'BW_WC_Large_Sessions', 'get_instance' ), 0 );
