<?php
if (!defined('ABSPATH')) exit;

class BWD_Tracker
{

    private static $api_url = 'http://tracking.sellandsell.com/v1/';

    public static function init() {
    	if($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1'){
	        add_action('bwd_tracker_send_event', array(__CLASS__, 'send_tracking_data'));
	    }

	    if( !wp_next_scheduled( 'bwd_tracker_send_event' ) ) {
	    	wp_schedule_event( time(), apply_filters( 'bwd_tracker_event_recurrence', 'daily' ), 'bwd_tracker_send_event' );
	    }
    }

    public static function send_tracking_data($override = false) {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        update_option('bwd_tracker_last_send', time());

        $params = self::get_tracking_data();
        $response = wp_safe_remote_post(self::$api_url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => false,
            'headers' => array(
                'user-agent' => 'BitwizeTracker/' . md5(esc_url(home_url('/'))) . ';'
            ) ,
            'body' => $params,
            'cookies' => array()
        ));
    }

    private static function get_last_send_time() {
        return apply_filters('bwd_tracker_last_send_time', get_option('bwd_tracker_last_send', false));
    }

    private static function get_tracking_data() {
        $data = array();

        $data['url'] = home_url();
        $data['email'] = apply_filters('bwd_tracker_admin_email', get_option('admin_email'));
        $data['theme'] = self::get_theme_info();

        $data['wp'] = self::get_wordpress_info();

        $data['server'] = self::get_server_info();

        $all_plugins = self::get_all_plugins();
        $data['active_plugins'] = $all_plugins['active_plugins'];
        $data['inactive_plugins'] = $all_plugins['inactive_plugins'];

        $data['users'] = self::get_user_counts();

        if ( is_woocommerce_active() && function_exists('WC') ):
            $data['shop_installed'] = 'Yes';
            $data['products'] = self::get_product_counts();
            $data['orders'] = self::get_order_counts();
            $data['gateways'] = self::get_active_payment_gateways();
            $data['shipping_methods'] = self::get_active_shipping_methods();
            $data['settings'] = self::get_all_bwd_options_values();
            $data['template_overrides'] = self::get_all_template_overrides();
        else:
            $data['shop_installed'] = 'No';
        endif;
        return apply_filters('bwd_tracker_data', $data);
    }

    public static function let_to_num( $size ) {
        $l   = substr( $size, -1 );
        $ret = substr( $size, 0, -1 );
        switch ( strtoupper( $l ) ) {
            case 'P':
                $ret *= 1024;
            case 'T':
                $ret *= 1024;
            case 'G':
                $ret *= 1024;
            case 'M':
                $ret *= 1024;
            case 'K':
                $ret *= 1024;
        }
        return $ret;
    }

    public static function get_theme_info() {
        $wp_version = get_bloginfo('version');

        if (version_compare($wp_version, '3.4', '<')) {
            $theme_data = get_theme_data(get_stylesheet_directory() . '/style.css');
            $theme_name = $theme_data['Name'];
            $theme_version = $theme_data['Version'];
        }
        else {
            $theme_data = wp_get_theme();
            $theme_name = $theme_data->Name;
            $theme_version = $theme_data->Version;
        }
        $theme_child_theme = is_child_theme() ? 'Yes' : 'No';
        $theme_wc_support = (!current_theme_supports('woocommerce')) ? 'No' : 'Yes';

        return array(
            'name' => $theme_name,
            'version' => $theme_version,
            'child_theme' => $theme_child_theme,
            'wc_support' => $theme_wc_support
        );
    }

    private static function get_wordpress_info() {
        $wp_data = array();

        $memory = self::let_to_num(WP_MEMORY_LIMIT);
        $wp_data['memory_limit'] = size_format($memory);
        $wp_data['debug_mode'] = (defined('WP_DEBUG') && WP_DEBUG) ? 'Yes' : 'No';
        $wp_data['locale'] = get_locale();
        $wp_data['version'] = get_bloginfo('version');
        $wp_data['multisite'] = is_multisite() ? 'Yes' : 'No';

        return $wp_data;
    }

    private static function get_server_info() {
        $server_data = array();

        if (isset($_SERVER['SERVER_SOFTWARE']) && !empty($_SERVER['SERVER_SOFTWARE'])) {
            $server_data['software'] = $_SERVER['SERVER_SOFTWARE'];
        }

        if (function_exists('phpversion')) {
            $server_data['php_version'] = phpversion();
        }

        if (function_exists('ini_get')) {
            $server_data['php_post_max_size'] = size_format(self::let_to_num(ini_get('post_max_size')));
            $server_data['php_time_limt'] = ini_get('max_execution_time');
            $server_data['php_max_input_vars'] = ini_get('max_input_vars');
            $server_data['php_suhosin'] = extension_loaded('suhosin') ? 'Yes' : 'No';
        }

        global $wpdb;
        $server_data['mysql_version'] = $wpdb->db_version();

        $server_data['php_max_upload_size'] = size_format(wp_max_upload_size());
        $server_data['php_default_timezone'] = date_default_timezone_get();
        $server_data['php_soap'] = class_exists('SoapClient') ? 'Yes' : 'No';
        $server_data['php_fsockopen'] = function_exists('fsockopen') ? 'Yes' : 'No';
        $server_data['php_curl'] = function_exists('curl_init') ? 'Yes' : 'No';

        return $server_data;
    }

    private static function get_all_plugins() {
        if (!function_exists('get_plugins')) {
            include ABSPATH . '/wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $active_plugins_keys = get_option('active_plugins', array());
        $active_plugins = array();

        foreach ($plugins as $k => $v) {
            $formatted = array();
            $formatted['name'] = strip_tags($v['Name']);
            if (isset($v['Version'])) {
                $formatted['version'] = strip_tags($v['Version']);
            }
            // if (isset($v['Author'])) {
            //     $formatted['author'] = strip_tags($v['Author']);
            // }
            // if (isset($v['Network'])) {
            //     $formatted['network'] = strip_tags($v['Network']);
            // }
            // if (isset($v['PluginURI'])) {
            //     $formatted['plugin_uri'] = strip_tags($v['PluginURI']);
            // }
            if (in_array($k, $active_plugins_keys)) {
                unset($plugins[$k]);
                $active_plugins[$k] = $formatted;
            }
            else {
                $plugins[$k] = $formatted;
            }
        }

        return array(
            'active_plugins' => $active_plugins,
            'inactive_plugins' => $plugins
        );
    }

    private static function get_user_counts() {
        $user_count = array();
        $user_count_data = count_users();
        $user_count['total'] = $user_count_data['total_users'];

        foreach ($user_count_data['avail_roles'] as $role => $count) {
            $user_count[$role] = $count;
        }

        return $user_count;
    }

    private static function get_product_counts() {
        $product_count = array();
        $product_count_data = wp_count_posts('product');
        $product_count['total'] = $product_count_data->publish;

        $product_statuses = get_terms('product_type', array(
            'hide_empty' => 0
        ));
        foreach ($product_statuses as $product_status) {
            $product_count[$product_status->name] = $product_status->count;
        }
        return $product_count;
    }

    private static function get_order_counts() {
        $order_count = array();
        $order_count_data = wp_count_posts('shop_order');

        foreach (wc_get_order_statuses() as $status_slug => $status_name) {
            $order_count[$status_slug] = $order_count_data->{$status_slug};
        }
        return $order_count;
    }

    private static function get_active_payment_gateways() {
        $active_gateways = array();
        $gateways = WC()->payment_gateways->payment_gateways();
        foreach ($gateways as $id => $gateway) {
            if (isset($gateway->enabled) && $gateway->enabled == 'yes') {
                $active_gateways[$id] = array(
                    'title' => $gateway->title,
                    'supports' => $gateway->supports
                );
            }
        }
        return $active_gateways;
    }

    private static function get_active_shipping_methods() {
        $active_methods = array();
        $shipping_methods = WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $id => $shipping_method) {
            if (isset($shipping_method->enabled) && $shipping_method->enabled == 'yes') {
                $active_methods[$id] = array(
                    'title' => $shipping_method->title,
                    'tax_status' => $shipping_method->tax_status
                );
            }
        }
        return $active_methods;
    }

    private static function get_all_bwd_options_values() {
        return array(
            'version' => WC()->version,
            'currency' => get_woocommerce_currency() ,
            'base_location' => WC()->countries->get_base_country() ,
            'selling_locations' => WC()->countries->get_allowed_countries() ,
            'api_enabled' => get_option('woocommerce_api_enabled') ,
            'weight_unit' => get_option('woocommerce_weight_unit') ,
            'dimension_unit' => get_option('woocommerce_dimension_unit') ,
            'download_method' => get_option('woocommerce_file_download_method') ,
            'download_require_login' => get_option('woocommerce_downloads_require_login') ,
            'calc_taxes' => get_option('woocommerce_calc_taxes') ,
            'coupons_enabled' => get_option('woocommerce_enable_coupons') ,
            'guest_checkout' => get_option('woocommerce_enable_guest_checkout') ,
            'secure_checkout' => get_option('woocommerce_force_ssl_checkout') ,
            'enable_signup_and_login_from_checkout' => get_option('woocommerce_enable_signup_and_login_from_checkout') ,
            'enable_myaccount_registration' => get_option('woocommerce_enable_myaccount_registration') ,
            'registration_generate_username' => get_option('woocommerce_registration_generate_username') ,
            'registration_generate_password' => get_option('woocommerce_registration_generate_password') ,
        );
    }

    private static function get_all_template_overrides() {
        $override_data = array();
        $template_paths = apply_filters('bwd_template_overrides_scan_paths', array(
            'WooCommerce' => WC()->plugin_path() . '/templates/'
        ));
        $scanned_files = array();

        require_once (WC()->plugin_path() . '/includes/admin/class-wc-admin-status.php');

        foreach ($template_paths as $plugin_name => $template_path) {
            $scanned_files[$plugin_name] = WC_Admin_Status::scan_template_files($template_path);
        }

        foreach ($scanned_files as $plugin_name => $files) {
            foreach ($files as $file) {
                if (file_exists(get_stylesheet_directory() . '/' . $file)) {
                    $theme_file = get_stylesheet_directory() . '/' . $file;
                }
                elseif (file_exists(get_stylesheet_directory() . '/woocommerce/' . $file)) {
                    $theme_file = get_stylesheet_directory() . '/woocommerce/' . $file;
                }
                elseif (file_exists(get_template_directory() . '/' . $file)) {
                    $theme_file = get_template_directory() . '/' . $file;
                }
                elseif (file_exists(get_template_directory() . '/woocommerce/' . $file)) {
                    $theme_file = get_template_directory() . '/woocommerce/' . $file;
                }
                else {
                    $theme_file = false;
                }
                if ($theme_file) {
                    $override_data[] = basename($theme_file);
                }
            }
        }
        return $override_data;
    }
}

BWD_Tracker::init();
