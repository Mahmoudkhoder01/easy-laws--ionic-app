<?php
class BW_Disable_Updates{
	function __construct(){
		self::disable_auto_updates();
		self::disable_translations();

		if(!bwd_get_option('coreupdates')){
			self::disable_core_updates();
		}
		if(bwd_get_option('allupdates')){
			self::disable_theme_updates();
			self::disable_plugin_updates();
		}

		add_action('admin_init', array($this, 'admin_init'));
	}
	static function last_checked() {
		global $wp_version;
		return (object) array(
			'last_checked'    => time(),
			'updates'         => array(),
			'version_checked' => $wp_version,
		);
	}

	function admin_init(){
		// Hide maintenance and update nag
		remove_action( 'admin_notices', 'update_nag', 3 );
		remove_action( 'network_admin_notices', 'update_nag', 3 );
		remove_action( 'admin_notices', 'maintenance_nag' );
		remove_action( 'network_admin_notices', 'maintenance_nag' );
		// CLEAR SCHEDULES
		wp_clear_scheduled_hook( 'wp_version_check' );
		wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
		wp_clear_scheduled_hook( 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'wp_update_themes' );
	}

	static function disable_core_updates() {
		add_action( 'init', create_function( '', 'remove_action( \'init\', \'wp_version_check\' );' ), 2 );
		add_filter( 'pre_option_update_core', '__return_null' );
		remove_action( 'wp_version_check', 'wp_version_check' );
		remove_action( 'admin_init', '_maybe_update_core' );
		add_filter( 'pre_transient_update_core', array( __CLASS__,'last_checked' ) );
		remove_action( 'load-update-core.php', 'wp_update_core' );
		add_filter( 'pre_site_transient_update_core', array( __CLASS__,'last_checked' ) );
		add_action( 'admin_menu', create_function( '', 'remove_action( \'admin_notices\', \'update_nag\', 3 );' ) );
		add_action( 'admin_menu', create_function( '', 'remove_action( \'admin_notices\', \'maintenance_nag\', 10 );' ) );

		add_filter('pre_wp_get_update_data', '__return_true');

	}

	static function disable_plugin_updates() {
		add_filter( 'pre_option_update_plugins', '__return_null' );
		remove_action( 'load-plugins.php', 'wp_update_plugins' );
		remove_action( 'load-update.php', 'wp_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'wp_update_plugins', 'wp_update_plugins' );
		add_filter( 'pre_transient_update_plugins', array( __CLASS__,'last_checked' ) );
		remove_action( 'load-update-core.php', 'wp_update_plugins' );
		add_filter( 'pre_site_transient_update_plugins', array( __CLASS__,'last_checked' ) );

	}

	static function disable_theme_updates() {
		add_filter( 'pre_option_update_themes', '__return_null' );
		remove_action( 'load-themes.php', 'wp_update_themes' );
		remove_action( 'load-update.php', 'wp_update_themes' );
		remove_action( 'admin_init', '_maybe_update_themes' );
		remove_action( 'wp_update_themes', 'wp_update_themes' );
		add_filter( 'pre_transient_update_themes', array( __CLASS__,'last_checked' ) );
		remove_action( 'load-update-core.php', 'wp_update_themes' );
		add_filter( 'pre_site_transient_update_themes', array( __CLASS__,'last_checked' ) );

	}

	static function disable_auto_updates() {
		define( 'AUTOMATIC_UPDATER_DISABLED', TRUE );
		define( 'WP_AUTO_UPDATE_CORE', FALSE );

		remove_action( 'admin_init', '_maybe_update_core' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_themes' );
		remove_action( 'load-plugins.php', 'wp_update_plugins' );
		remove_action( 'load-themes.php', 'wp_update_themes' );
		remove_action( 'init', 'wp_schedule_update_checks' );
		remove_action( 'wp_maybe_auto_update', 'wp_maybe_auto_update' );

		add_filter( 'auto_update_translation', '__return_false' );
		add_filter( 'automatic_updater_disabled', '__return_true' );
		add_filter( 'allow_minor_auto_core_updates', '__return_false' );
		add_filter( 'allow_major_auto_core_updates', '__return_false' );
		add_filter( 'allow_dev_auto_core_updates', '__return_false' );
		add_filter( 'auto_update_core', '__return_false' );
		add_filter( 'wp_auto_update_core', '__return_false' );
		add_filter( 'auto_core_update_send_email', '__return_false' );
		add_filter( 'send_core_update_notification_email', '__return_false' );
		add_filter( 'auto_update_plugin', '__return_false' );
		add_filter( 'auto_update_theme', '__return_false' );
		add_filter( 'automatic_updates_send_debug_email', '__return_false' );
		add_filter( 'automatic_updates_send_debug_email ', '__return_false', 1 );
		add_filter( 'automatic_updates_is_vcs_checkout', '__return_true' );

		remove_action( 'wp_maybe_auto_update', 'wp_maybe_auto_update' );
		remove_action( 'admin_init', 'wp_maybe_auto_update' );
		remove_action( 'admin_init', 'wp_auto_update_core' );
	}

	public function disable_translations() {
		add_filter( 'auto_update_translation', '__return_false' );
		add_filter( 'transient_update_themes', array( __CLASS__, 'remove_translations' ) );
		add_filter( 'site_transient_update_themes', array( __CLASS__, 'remove_translations' ) );
		add_action( 'transient_update_plugins', array( __CLASS__, 'remove_translations' ) );
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'remove_translations' ) );
		add_filter( 'transient_update_core', array( __CLASS__, 'remove_translations' ) );
		add_filter( 'site_transient_update_core', array( __CLASS__, 'remove_translations' ) );
	}

	static function remove_translations( $transient ) {
		if ( is_object( $transient ) && isset( $transient->translations ) ) {
			$transient->translations = array();
		}
		return $transient;
	}
}

new BW_Disable_Updates();
