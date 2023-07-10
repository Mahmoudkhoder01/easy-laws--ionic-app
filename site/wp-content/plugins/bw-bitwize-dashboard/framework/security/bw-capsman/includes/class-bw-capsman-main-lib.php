<?php

class BW_Capsman_Main_Lib {

    private static $instance = null;
    protected $options_id = '';
    protected $options = array();
    public $multisite = false;
    public $active_for_network = false;
    public $blog_ids = null;
    protected $main_blog_id = 0;
    public $log_to_file = false;
    private $log_file_name = '';

    public function __construct( $options_id ) {

        $this->multisite = function_exists( 'is_multisite' ) && is_multisite();
        if( $this->multisite ) {
            $this->blog_ids = $this->get_blog_ids();
            $this->main_blog_id = $this->blog_ids[0][0];
        }

        $this->init_options( $options_id );

        add_action( 'admin_notices', array(&$this, 'show_message') );
    }

    protected function get_blog_ids() {
        global $wpdb;

        $blog_ids = $wpdb->get_col( "select blog_id from $wpdb->blogs order by blog_id asc" );

        return $blog_ids;
    }

    protected function init_options( $options_id ) {
        $this->options_id = $options_id;
        $this->options = get_option( $options_id );
    }

    public function show_message( $message, $error_style = false ) {

        if( $message ) {
            if( $error_style ) {
                echo '<div id="message" class="error" >';
            }
            else {
                echo '<div id="message" class="updated fade">';
            }
            echo $message . '</div>';
        }
    }

    public function get_request_var( $var_name, $request_type = 'request', $var_type = 'string' ) {

        $result = 0;
        if( $request_type == 'get' ) {
            if( isset( $_GET[$var_name] ) ) {
                $result = $_GET[$var_name];
            }
        }
        else if( $request_type == 'post' ) {
            if( isset( $_POST[$var_name] ) ) {
                if( $var_type != 'checkbox' ) {
                    $result = $_POST[$var_name];
                }
                else {
                    $result = 1;
                }
            }
        }
        else {
            if( isset( $_REQUEST[$var_name] ) ) {
                $result = $_REQUEST[$var_name];
            }
        }

        if( $result ) {
            if( $var_type == 'int' && !is_numeric( $result ) ) {
                $result = 0;
            }
            if( $var_type != 'int' ) {
                $result = esc_attr( $result );
            }
        }

        return $result;
    }

    public function get_option( $option_name, $default = false ) {

        if( isset( $this->options[$option_name] ) ) {
            return $this->options[$option_name];
        }
        else {
            return $default;
        }
    }

    public function put_option( $option_name, $option_value, $flush_options = false ) {

        $this->options[$option_name] = $option_value;
        if( $flush_options ) {
            $this->flush_options();
        }
    }

    public function delete_option( $option_name, $flush_options = false ) {
        if( array_key_exists( $option_name, $this->options ) ) {
            unset( $this->options[$option_name] );
            if( $flush_options ) {
                $this->flush_options();
            }
        }
    }

    public function flush_options() {

        update_option( $this->options_id, $this->options );
    }

    public static function check_version( $must_have_version, $version_to_check, $error_message, $plugin_file_name ) {

        if( version_compare( $must_have_version, $version_to_check, '<' ) ) {
            if( is_admin() &&( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
                deactivate_plugins( $plugin_file_name );
                wp_die( $error_message );
            }
            else {
                return;
            }
        }
    }

    public function option_selected( $value, $etalon ) {
        $selected = '';
        if( strcasecmp( $value, $etalon ) == 0 ) {
            $selected = 'selected="selected"';
        }

        return $selected;
    }

    public function get_current_url() {
        global $wp;
        $current_url = esc_url_raw( add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );

        return $current_url;
    }
}
