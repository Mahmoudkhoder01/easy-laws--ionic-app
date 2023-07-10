<?php

class BW_Capsman {
    protected $lib = null;

    protected $setting_page_hook = null;
    public $key_capability = 'not allowed';

    protected $bw_capsman_hook_suffixes = null;

    function __construct( $library ) {

        $this->lib = $library;
        $this->bw_capsman_hook_suffixes = array('settings_page_settings-bw-capsman', 'users_page_users-bw-capsman');

        register_activation_hook( BITWIZE_CORE_PLUGIN_FILE, array($this, 'setup') );
        register_deactivation_hook( BITWIZE_CORE_PLUGIN_FILE, array($this, 'cleanup') );

        $this->key_capability = $this->lib->get_key_capability();

        if( $this->lib->multisite ) {
            add_action( 'wpmu_new_blog', array($this, 'duplicate_roles_for_new_blog'), 10, 2 );
        }

        if( !is_admin() ) {
            return;
        }

        add_action( 'admin_init', array($this, 'plugin_init'), 1 );

        add_action( 'admin_menu', array($this, 'plugin_menu') );

        if( $this->lib->multisite ) {
            add_action( 'network_admin_menu', array($this, 'network_plugin_menu') );
        }
    }

    public function hide_users_count($hook){
        if($hook != 'users.php') return;
        echo '<style>.subsubsub a .count, .subsubsub a.current .count{display:none !important;}</style>';
    }

    public function plugin_init() {

        global $current_user, $pagenow;

        if( !empty( $current_user->ID ) ) {
            $user_id = $current_user->ID;
        }
        else {
            $user_id = 0;
        }

        $supress_protection = apply_filters( 'bw_capsman_supress_administrators_protection', false );
        if( !$supress_protection && !$this->lib->user_is_admin( $user_id ) ) {
            // add_filter( 'editable_roles', array($this, 'exclude_admin_role') );
            // add_filter( 'user_has_cap', array($this, 'not_edit_admin'), 10, 3 );
            add_action( 'pre_user_query', array($this, 'exclude_administrators') );
            // add_filter( 'views_users', array($this, 'exclude_admins_view') );

            add_action('admin_enqueue_scripts', array($this, 'hide_users_count'));
        }

        if( !$supress_protection && !$this->lib->user_is_normal_admin( $user_id ) ) {
            add_filter( 'editable_roles', array($this, 'exclude_admin_role') );
            add_filter( 'user_has_cap', array($this, 'not_edit_admin'), 10, 3 );
            add_filter( 'views_users', array($this, 'exclude_admins_view') );
        }

        add_action( 'admin_enqueue_scripts', array($this, 'admin_load_js') );
        add_action( 'user_row_actions', array($this, 'user_row'), 10, 2 );
        add_action( 'edit_user_profile', array($this, 'edit_user_profile'), 10, 2 );
        add_filter( 'manage_users_columns', array($this, 'user_role_column'), 10, 1 );
        add_filter( 'manage_users_custom_column', array($this, 'user_role_row'), 10, 3 );
        add_action( 'profile_update', array($this, 'user_profile_update'), 10 );
        add_filter( 'all_plugins', array($this, 'exclude_from_plugins_list') );

        add_action( 'admin_enqueue_scripts', array($this, 'add_js_to_settings_page') );

        if( $this->lib->multisite ) {
            add_action( 'wpmu_activate_user', array($this, 'add_other_default_roles'), 10, 1 );

            $allow_edit_users_to_not_super_admin = $this->lib->get_option( 'allow_edit_users_to_not_super_admin', 0 );
            if( $allow_edit_users_to_not_super_admin ) {
                add_filter( 'map_meta_cap', array($this, 'restore_users_edit_caps'), 1, 4 );
                remove_all_filters( 'enable_edit_any_user_configuration' );
                add_filter( 'enable_edit_any_user_configuration', '__return_true' );
                add_filter( 'admin_head', array($this, 'edit_user_permission_check'), 1, 4 );
                if( $pagenow == 'user-new.php' ) {
                    add_filter( 'site_option_site_admins', array($this, 'allow_add_user_as_superadmin') );
                }
            }
        }
        else {
            add_action( 'user_register', array($this, 'add_other_default_roles'), 10, 1 );
            $count_users_without_role = $this->lib->get_option( 'count_users_without_role', 0 );
            if( $count_users_without_role ) {
                add_action( 'restrict_manage_users', array($this, 'move_users_from_no_role_button') );
                add_action( 'admin_init', array($this, 'add_css_to_users_page') );
                add_action( 'admin_footer', array($this, 'add_js_to_users_page') );
            }
        }

        add_action( 'wp_ajax_bw_capsman_ajax', array($this, 'bw_capsman_ajax') );
    }

    public function allow_add_user_as_superadmin( $site_admins ) {

        global $pagenow, $current_user;

        if( $pagenow !== 'user-new.php' ) {
            return $site_admins;
        }

        remove_filter( 'site_option_site_admins', array($this, 'allow_add_user_as_superadmin') );
        $can_add_user = current_user_can( 'create_users' ) && current_user_can( 'promote_users' );
        add_filter( 'site_option_site_admins', array($this, 'allow_add_user_as_superadmin') );

        if( !$can_add_user ) {
            return $site_admins;
        }

        if( !in_array( $current_user->user_login, $site_admins ) ) {
            $site_admins[] = $current_user->user_login;
        }

        return $site_admins;
    }

    public function move_users_from_no_role_button() {

        global $wpdb;

        if( stripos( $_SERVER['REQUEST_URI'], 'wp-admin/users.php' ) === false ) {
            return;
        }

        $id = get_current_blog_id();
        $blog_prefix = $wpdb->get_blog_prefix( $id );
        $query = "select count(ID) from {$wpdb->users} users
                    where not exists (select user_id from {$wpdb->usermeta}
                                          where user_id=users.ID and meta_key='{$blog_prefix}capabilities') or
                          exists (select user_id from {$wpdb->usermeta}
                                    where user_id=users.ID and meta_key='{$blog_prefix}capabilities' and meta_value='a:0:{}')                ;";
        $users_count = $wpdb->get_var( $query );
        if( $users_count > 0 ) {
?>
        &nbsp;&nbsp;<input type="button" name="move_from_no_role" id="move_from_no_role" class="button"
                        value="Without role (<?php
            echo $users_count; ?>)" onclick="bw_capsman_move_users_from_no_role_dialog()">
        <div id="move_from_no_role_dialog" class="bw-capsman-dialog">
            <div id="move_from_no_role_content" style="padding: 10px;">
                To: <select name="bw_capsman_new_role" id="bw_capsman_new_role">
                    <option value="no_rights">No rights</option>
                </select><br>
            </div>
        </div>
<?php
        }
    }

    public function add_css_to_users_page() {
        if( stripos( $_SERVER['REQUEST_URI'], '/users.php' ) === false ) {
            return;
        }
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_style( 'bw-capsman-admin-css', BW_CAPSMAN_PLUGIN_URL . 'css/bw-capsman-admin.css', array(), false, 'screen' );
    }

    public function add_js_to_users_page() {

        if( stripos( $_SERVER['REQUEST_URI'], '/users.php' ) === false ) {
            return;
        }

        wp_enqueue_script( 'jquery-ui-dialog', false, array('jquery-ui-core', 'jquery-ui-button', 'jquery') );
        wp_register_script( 'bw-capsman-users-js', plugins_url( '/js/bw-capsman-users.js', BW_CAPSMAN_PLUGIN_FULL_PATH ) );
        wp_enqueue_script( 'bw-capsman-users-js' );
        wp_localize_script( 'bw-capsman-users-js', 'bw_capsman_users_data', array(
            'wp_nonce' => wp_create_nonce( BW_TD ),
            'move_from_no_role_title' => esc_html__( 'Change role for users without role', BW_TD ),
            'no_rights_caption' => esc_html__( 'No rights', BW_TD ),
            'provide_new_role_caption' => esc_html__( 'Provide new role', BW_TD )
        ));
    }

    public function add_js_to_settings_page(){
        if(isset($_REQUEST['page']) && $_REQUEST['page'] === 'settings-bw-capsman'){
            wp_enqueue_script( 'jquery-ui-tabs',false );
        }
    }

    public function add_other_default_roles( $user_id ) {

        if( empty( $user_id ) ) {
            return;
        }
        $user = get_user_by( 'id', $user_id );
        if( empty( $user->ID ) ) {
            return;
        }
        $other_default_roles = $this->lib->get_option( 'other_default_roles', array() );
        if( count( $other_default_roles ) == 0 ) {
            return;
        }
        foreach( $other_default_roles as $role ) {
            $user->add_role( $role );
        }
    }

    public function restore_users_edit_caps( $caps, $cap, $user_id, $args ) {

        foreach( $caps as $key => $capability ) {

            if( $capability != 'do_not_allow' )continue;

            switch( $cap ){
            case 'edit_user':
            case 'edit_users':
                $caps[$key] = 'edit_users';
                break;

            case 'delete_user':
            case 'delete_users':
                $caps[$key] = 'delete_users';
                break;

            case 'create_users':
                $caps[$key] = $cap;
                break;
            }
        }

        return $caps;
    }

    function edit_user_permission_check() {
        global $current_user, $profileuser;

        if( is_super_admin() ) {
            return;
        }

        $screen = get_current_screen();

        get_currentuserinfo();

        if( $screen->base == 'user-edit' || $screen->base == 'user-edit-network' ) {
            if( !is_super_admin( $current_user->ID ) && is_super_admin( $profileuser->ID ) ) {
                wp_die( esc_html__( 'You do not have permission to edit this user.' ) );
            }
            elseif( !( is_user_member_of_blog( $profileuser->ID, get_current_blog_id() ) && is_user_member_of_blog( $current_user->ID, get_current_blog_id() ) ) ) {
                wp_die( esc_html__( 'You do not have permission to edit this user.' ) );
            }
        }
    }

    public function exclude_admin_role( $roles ) {

        if( isset( $roles['administrator'] ) ) {
            // unset( $roles['administrator'] );
        }

        return $roles;
    }

    public function not_edit_admin( $allcaps, $caps, $name ) {

        $user_keys = array('user_id', 'user');
        foreach( $user_keys as $user_key ) {
            $access_deny = false;
            $user_id = $this->lib->get_request_var( $user_key, 'get' );
            if( !empty( $user_id ) ) {
                if( $user_id == 1 ) {
                    $access_deny = true;
                }
                else {
                    if( !isset( $this->lib->user_to_check[$user_id] ) ) {
                        $access_deny = $this->lib->has_administrator_role( $user_id );
                    }
                    else {
                        $access_deny = $this->lib->user_to_check[$user_id];
                    }
                }
                if( $access_deny ) {
                    unset( $allcaps['edit_users'] );
                }
                break;
            }
        }

        return $allcaps;
    }

    public function exclude_administrators( $user_query ) {

        global $wpdb;

        $result = false;
        $links_to_block = array('profile.php', 'users.php');
        foreach( $links_to_block as $key => $value ) {
            $result = stripos( $_SERVER['REQUEST_URI'], $value );
            if( $result !== false ) {
                break;
            }
        }

        if( $result === false ) {
            return;
        }

        $cur_id = get_current_user_id();

        $tableName =( !$this->lib->multisite && defined( 'CUSTOM_USER_META_TABLE' ) ) ? CUSTOM_USER_META_TABLE : $wpdb->usermeta;
        $meta_key = $wpdb->prefix . 'capabilities';
        $admin_role_key = '%"administrator"%';
        $query = "select user_id from $tableName where meta_key='$meta_key' and meta_value like '$admin_role_key'";
        $ids_arr = $wpdb->get_col( $query );

        foreach($ids_arr as $k=>$v){
            if(!user_can($v, $this->key_capability)){
                unset($ids_arr[$k]);
            }
            if($v == $cur_id){
                unset($ids_arr[$k]); // ensure current user shown
            }
        }

        if( is_array( $ids_arr ) && count( $ids_arr ) > 0 ) {
            $ids = implode( ',', $ids_arr );
            $user_query->query_where.= " AND ( $wpdb->users.ID NOT IN ( $ids ) )";
        }
    }

    public function exclude_admins_view( $views ) {

        unset( $views['administrator'] );

        return $views;
    }

    public function user_row( $actions, $user ) {

        global $pagenow, $current_user;

        if( $pagenow == 'users.php' ) {
            if( $current_user->has_cap( $this->key_capability ) ) {
                $actions['capabilities'] = '<a href="' . wp_nonce_url( "users.php?page=users-bw-capsman&object=user&amp;user_id={$user->ID}", "bw_capsman_user_{$user->ID}" ) . '">' . esc_html__( 'Capabilities', BW_TD ) . '</a>';
            }
        }

        return $actions;
    }

    public function duplicate_roles_for_new_blog( $blog_id ) {

        global $wpdb, $wp_roles;

        $main_blog_id = $this->lib->get_main_blog_id();
        if( empty( $main_blog_id ) ) {
            return;
        }
        $current_blog = $wpdb->blogid;
        switch_to_blog( $main_blog_id );
        $main_roles = new WP_Roles();
        $default_role = get_option( 'default_role' );
        switch_to_blog( $blog_id );
        $main_roles->use_db = false;
        $main_roles->add_cap( 'administrator', 'dummy_123456' );
        $main_roles->role_key = $wp_roles->role_key;
        $main_roles->use_db = true;
        $main_roles->remove_cap( 'administrator', 'dummy_123456' );
        update_option( 'default_role', $default_role );
        switch_to_blog( $current_blog );
    }

    public function exclude_from_plugins_list( $plugins ) {

        if( $this->lib->multisite ) {
            if( is_super_admin() || $this->lib->user_is_admin() ) {
                return $plugins;
            }
        }
        else {
            if( current_user_can( 'administrator' ) || $this->lib->user_is_admin() ) {
                return $plugins;
            }
        }

        foreach( $plugins as $key => $value ) {
            if( $key == 'bw-capsman/' . BW_CAPSMAN_PLUGIN_FILE ) {
                unset( $plugins[$key] );
                break;
            }
        }

        return $plugins;
    }

    public function plugin_menu() {

        $translated_title = esc_html__( 'Capability Manager', BW_TD );
        if( function_exists( 'add_submenu_page' ) ) {
            $bw_capsman_page = add_submenu_page( 'users.php', $translated_title, $translated_title, $this->key_capability, 'users-bw-capsman', array($this, 'edit_roles') );
            add_action( "admin_print_styles-$bw_capsman_page", array($this, 'admin_css_action') );
        }

        if( !$this->lib->multisite ||( $this->lib->multisite && !$this->lib->active_for_network ) ) {
            $settings_capability = $this->lib->get_settings_capability();
            $this->settings_page_hook = add_submenu_page( NULL, $translated_title, $translated_title, $settings_capability, 'settings-bw-capsman', array($this, 'settings') );
            // $this->settings_page_hook = add_options_page( $translated_title, $translated_title, $settings_capability, 'settings-bw-capsman', array($this, 'settings') );
            add_action( "admin_print_styles-{$this->settings_page_hook}", array($this, 'admin_css_action') );
        }
    }

    public function network_plugin_menu() {
        if( is_multisite() ) {
            $translated_title = esc_html__( 'Capability Manager', BW_TD );
            $this->settings_page_hook = add_submenu_page( 'settings.php', $translated_title, $translated_title, $this->key_capability, 'settings-bw-capsman', array(&$this, 'settings') );
            add_action( "admin_print_styles-{$this->settings_page_hook}", array($this, 'admin_css_action') );
        }
    }

    protected function get_settings_action() {

        $action = 'show';
        $update_buttons = array('bw_capsman_settings_update', 'bw_capsman_addons_settings_update', 'bw_capsman_settings_ms_update', 'bw_capsman_default_roles_update');
        foreach( $update_buttons as $update_button ) {
            if( !isset( $_POST[$update_button] ) ) {
                continue;
            }
            if( !wp_verify_nonce( $_POST['_wpnonce'], BW_TD ) ) {
                wp_die( 'Security check failed' );
            }
            $action = $update_button;
            break;
        }

        return $action;
    }

    protected function update_general_options() {
        if( defined( 'BW_CAPSMAN_SHOW_ADMIN_ROLE' ) &&( BW_CAPSMAN_SHOW_ADMIN_ROLE == 1 ) ) {
            $show_admin_role = 1;
        }
        else {
            $show_admin_role = $this->lib->get_request_var( 'show_admin_role', 'checkbox' );
        }
        $this->lib->put_option( 'show_admin_role', $show_admin_role );

        $caps_readable = $this->lib->get_request_var( 'caps_readable', 'checkbox' );
        $this->lib->put_option( 'bw_capsman_caps_readable', $caps_readable );

        $show_deprecated_caps = $this->lib->get_request_var( 'show_deprecated_caps', 'checkbox' );
        $this->lib->put_option( 'bw_capsman_show_deprecated_caps', $show_deprecated_caps );

        $edit_user_caps = $this->lib->get_request_var( 'edit_user_caps', 'checkbox' );
        $this->lib->put_option( 'edit_user_caps', $edit_user_caps );

        do_action( 'bw_capsman_settings_update1' );

        $this->lib->flush_options();
        $this->lib->show_message( esc_html__( 'Capability Manager options are updated', BW_TD ) );
    }

    protected function update_addons_options() {

        if( !$this->lib->multisite ) {
            $count_users_without_role = $this->lib->get_request_var( 'count_users_without_role', 'checkbox' );
            $this->lib->put_option( 'count_users_without_role', $count_users_without_role );
        }
        do_action( 'bw_capsman_settings_update2' );

        $this->lib->flush_options();
        $this->lib->show_message( esc_html__( 'Capability Manager options are updated', BW_TD ) );
    }

    protected function update_default_roles() {
        global $wp_roles;

        $primary_default_role = $this->lib->get_request_var( 'default_user_role', 'post' );
        if( !empty( $primary_default_role ) && isset( $wp_roles->role_objects[$primary_default_role] ) && $primary_default_role !== 'administrator' ) {
            update_option( 'default_role', $primary_default_role );
        }

        $other_default_roles = array();
        foreach( $_POST as $key => $value ) {
            $prefix = substr( $key, 0, 8 );
            if( $prefix !== 'wp_role_' ) {
                continue;
            }
            $role_id = substr( $key, 8 );
            if( $role_id !== 'administrator' && isset( $wp_roles->role_objects[$role_id] ) ) {
                $other_default_roles[] = $role_id;
            }
        }
        $this->lib->put_option( 'other_default_roles', $other_default_roles, true );

        $this->lib->show_message( esc_html__( 'Default Roles are updated', BW_TD ) );
    }

    protected function update_multisite_options() {
        if( !$this->lib->multisite ) {
            return;
        }

        $allow_edit_users_to_not_super_admin = $this->lib->get_request_var( 'allow_edit_users_to_not_super_admin', 'checkbox' );
        $this->lib->put_option( 'allow_edit_users_to_not_super_admin', $allow_edit_users_to_not_super_admin );

        do_action( 'bw_capsman_settings_ms_update' );

        $this->lib->flush_options();
        $this->lib->show_message( esc_html__( 'Capability Manager options are updated', BW_TD ) );
    }

    public function settings() {
        $settings_capability = $this->lib->get_settings_capability();
        if( !current_user_can( $settings_capability ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to manage options for Capability Manager.', BW_TD ) );
        }
        $action = $this->get_settings_action();
        switch( $action ){
        case 'bw_capsman_settings_update':
            $this->update_general_options();
            break;

        case 'bw_capsman_addons_settings_update':
            $this->update_addons_options();
            break;

        case 'bw_capsman_settings_ms_update':
            $this->update_multisite_options();
            break;

        case 'bw_capsman_default_roles_update':
            $this->update_default_roles();
        case 'show':
        default:;
        }
        if( defined( 'BW_CAPSMAN_SHOW_ADMIN_ROLE' ) &&( BW_CAPSMAN_SHOW_ADMIN_ROLE == 1 ) ) {
            $show_admin_role = 1;
        }
        else {
            $show_admin_role = $this->lib->get_option( 'show_admin_role', 0 );
        }
        $caps_readable = $this->lib->get_option( 'bw_capsman_caps_readable', 0 );
        $show_deprecated_caps = $this->lib->get_option( 'bw_capsman_show_deprecated_caps', 0 );
        $edit_user_caps = $this->lib->get_option( 'edit_user_caps', 1 );

        if( $this->lib->multisite ) {
            $allow_edit_users_to_not_super_admin = $this->lib->get_option( 'allow_edit_users_to_not_super_admin', 0 );
        }
        else {
            $count_users_without_role = $this->lib->get_option( 'count_users_without_role', 0 );
        }

        $this->lib->get_default_role();
        $this->lib->editor_init1();
        $this->lib->role_edit_prepare_html( 0 );

        $bw_capsman_tab_idx = $this->lib->get_request_var( 'bw_capsman_tab_idx', 'int' );

        do_action( 'bw_capsman_settings_load' );

        if( $this->lib->multisite && is_network_admin() ) {
            $link = 'settings.php';
        }
        else {
            // $link = 'options-general.php';
            $link = 'admin.php';
        }

        $license_key_only = $this->lib->multisite && is_network_admin() && !$this->lib->active_for_network;

        require_once( BW_CAPSMAN_PLUGIN_DIR . 'includes/settings-template.php' );
    }

    public function admin_css_action() {

        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        if( stripos( $_SERVER['REQUEST_URI'], 'settings-bw-capsman' ) !== false ) {
            // wp_enqueue_style( 'bw-capsman-jquery-ui-tabs', BW_CAPSMAN_PLUGIN_URL . 'css/jquery-ui-1.10.4.custom.min.css', array(), false, 'screen' );
        }
        wp_enqueue_style( 'bw-capsman-admin-css', BW_CAPSMAN_PLUGIN_URL . 'css/bw-capsman-admin.css', array(), false, 'screen' );
    }

    public function edit_roles() {

        if( !current_user_can( $this->key_capability ) ) {
            wp_die( esc_html__( 'Insufficient permissions to work with Capability Manager', BW_TD ) );
        }

        $this->lib->editor();
    }

    private function convert_option( $option_name ) {

        $option_value = get_option( $option_name, 0 );
        delete_option( $option_name );
        $this->lib->put_option( $option_name, $option_value );
    }

    function setup() {

        $this->convert_option( 'bw_capsman_caps_readable' );
        $this->convert_option( 'bw_capsman_show_deprecated_caps' );
        $this->lib->flush_options();

        $this->lib->make_roles_backup();
        $this->lib->init_bw_capsman_caps();

        do_action( 'bw_capsman_activation' );
    }

    protected function unload_conflict_plugins_css( $hook_suffix ) {
        global $wp_styles;

        if( !in_array( $hook_suffix, $this->bw_capsman_hook_suffixes ) && !in_array( $hook_suffix, array('users.php', 'profile.php') ) ) {
            return;
        }

        if( isset( $wp_styles->registered['admin-page-css'] ) ) {
            wp_deregister_style( 'admin-page-css' );
        }
    }

    public function admin_load_js( $hook_suffix ) {

        $this->unload_conflict_plugins_css( $hook_suffix );

        if( in_array( $hook_suffix, $this->bw_capsman_hook_suffixes ) ) {
            wp_enqueue_script( 'jquery-ui-dialog', false, array('jquery-ui-core', 'jquery-ui-button', 'jquery') );
            wp_enqueue_script( 'jquery-ui-tabs', false, array('jquery-ui-core', 'jquery') );
            wp_register_script( 'bw-capsman-js', plugins_url( '/js/bw-capsman-js.js', BW_CAPSMAN_PLUGIN_FULL_PATH ) );
            wp_enqueue_script( 'bw-capsman-js' );
            wp_localize_script( 'bw-capsman-js', 'bw_capsman_data', array(
                'wp_nonce' => wp_create_nonce( BW_TD ),
                'page_url' => BW_CAPSMAN_WP_ADMIN_URL . BW_CAPSMAN_PARENT . '?page=users-bw-capsman',
                'is_multisite' => is_multisite() ? 1 : 0,
                'select_all' => esc_html__( 'Select All', BW_TD ),
                'unselect_all' => esc_html__( 'Unselect All', BW_TD ),
                'reverse' => esc_html__( 'Reverse', BW_TD ),
                'update' => esc_html__( 'Update', BW_TD ),
                'confirm_submit' => esc_html__( 'Please confirm permissions update', BW_TD ),
                'add_new_role_title' => esc_html__( 'Add New Role', BW_TD ),
                'rename_role_title' => esc_html__( 'Rename Role', BW_TD ),
                'role_name_required' => esc_html__( ' Role name (ID) can not be empty!', BW_TD ),
                'role_name_valid_chars' => esc_html__( ' Role name (ID) must contain latin characters, digits, hyphens or underscore only!', BW_TD ),
                'numeric_role_name_prohibited' => esc_html__( ' WordPress does not support numeric Role name (ID). Add latin characters to it.', BW_TD ),
                'add_role' => esc_html__( 'Add Role', BW_TD ),
                'rename_role' => esc_html__( 'Rename Role', BW_TD ),
                'delete_role' => esc_html__( 'Delete Role', BW_TD ),
                'cancel' => esc_html__( 'Cancel', BW_TD ),
                'add_capability' => esc_html__( 'Add Capability', BW_TD ),
                'delete_capability' => esc_html__( 'Delete Capability', BW_TD ),
                'reset' => esc_html__( 'Reset', BW_TD ),
                'reset_warning' => esc_html__( 'DANGER! Resetting will restore default settings from WordPress Core.', BW_TD ) . "\n\n" . esc_html__( 'If any plugins have changed capabilities in any way upon installation (such as S2Member, WooCommerce, and many more), those capabilities will be DELETED!', BW_TD ) ."\n\n". esc_html__( 'Continue?', BW_TD ),
                'default_role' => esc_html__( 'Default Role', BW_TD ),
                'set_new_default_role' => esc_html__( 'Set New Default Role', BW_TD ),
                'delete_capability' => esc_html__( 'Delete Capability', BW_TD ),
                'delete_capability_warning' => esc_html__( 'Warning! Be careful - removing critical capability could crash some plugin or other custom code', BW_TD ),
                'capability_name_required' => esc_html__( ' Capability name (ID) can not be empty!', BW_TD ),
                'capability_name_valid_chars' => esc_html__( ' Capability name (ID) must contain latin characters, digits, hyphens or underscore only!', BW_TD ),
            ) );
            do_action( 'bw_capsman_load_js' );
        }
    }

    protected function is_user_profile_extention_allowed() {
        $result = stripos( $_SERVER['REQUEST_URI'], 'network/user-edit.php' ) == false;

        return $result;
    }

    public function edit_user_profile( $user ) {

        global $current_user;

        if( !$this->is_user_profile_extention_allowed() ) {
            return;
        }

        if( !$this->lib->user_is_admin( $current_user->ID ) ){
            return;
        }
?>
        <h3><?php
        _e( 'Capability Manager', BW_TD ); ?></h3>
        <table class="form-table">
            <tr>
              <th scope="row"><?php
        _e( 'Other Roles', BW_TD ); ?></th>
              <td>
        <?php
        $roles = $this->lib->other_user_roles( $user );
        if( is_array( $roles ) && count( $roles ) > 0 ) {
            foreach( $roles as $role ) {
                echo '<input type="hidden" name="bw_capsman_other_roles[]" value="' . $role . '" />';
            }
        }

        $output = $this->lib->roles_text( $roles );
        echo $output;
        if( $this->lib->user_is_admin( $current_user->ID ) ) {
            echo '&nbsp;&nbsp;&gt;&gt;&nbsp;<a href="' . wp_nonce_url( "users.php?page=users-bw-capsman&object=user&amp;user_id={$user->ID}", "bw_capsman_user_{$user->ID}" ) . '">' . esc_html__( 'Edit', BW_TD ) . '</a>';
        }
?>
              </td>
            </tr>
        </table>
        <?php
    }

    public function user_role_column( $columns = array() ) {
        global $current_user;
        if( $this->lib->user_is_admin( $current_user->ID ) ) {
            $columns['bw_capsman_roles'] = esc_html__( 'Other Roles', BW_TD );
        }

        return $columns;
    }

    public function user_role_row( $retval = '', $column_name = '', $user_id = 0 ) {

        if( 'bw_capsman_roles' == $column_name ) {
            $user = get_userdata( $user_id );
            $roles = $this->lib->other_user_roles( $user );
            $retval = $this->lib->roles_text( $roles );
        }

        return $retval;
    }

    public function user_profile_update( $user_id ) {

        if( !current_user_can( 'edit_user', $user_id ) ) {
            return;
        }
        $user = get_userdata( $user_id );

        if( isset( $_POST['bw_capsman_other_roles'] ) ) {
            $new_roles = array_intersect( $user->roles, $_POST['bw_capsman_other_roles'] );
            $skip_roles = array();
            foreach( $new_roles as $role ) {
                $skip_roles['$role'] = 1;
            }
            unset( $new_roles );
            foreach( $_POST['bw_capsman_other_roles'] as $role ) {
                if( !isset( $skip_roles[$role] ) ) {
                    $user->add_role( $role );
                }
            }
        }
    }

    public function bw_capsman_ajax() {

        require_once( BW_CAPSMAN_PLUGIN_DIR . 'includes/class-ajax-processor.php' );
        $ajax_processor = new BW_CAPSMAN_Ajax_Processor( $this->lib );
        $ajax_processor->dispatch();
    }

    function cleanup() {
    }
}
