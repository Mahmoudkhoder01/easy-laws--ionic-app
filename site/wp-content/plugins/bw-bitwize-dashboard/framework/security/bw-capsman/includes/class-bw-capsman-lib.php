<?php

class BW_Capsman_Lib extends BW_Capsman_Main_Lib {

    public $roles = null;
    public $notification = '';
    public $apply_to_all = 0;
    public $user_to_check = array();
    protected $capabilities_to_save = null;
    protected $current_role = '';
    protected $wp_default_role = '';
    protected $current_role_name = '';
    protected $user_to_edit = '';
    protected $show_deprecated_caps = false;
    protected $caps_readable = false;
    protected $full_capabilities = false;
    protected $bw_capsman_object = 'role';
    public $role_default_html = '';
    protected $role_to_copy_html = '';
    protected $role_select_html = '';
    protected $role_delete_html = '';
    protected $capability_remove_html = '';

    public function __construct( $options_id ) {
        parent::__construct( $options_id );
        $this->upgrade();
    }

    protected function upgrade() {
        $bw_capsman_version = $this->get_option( 'bw_capsman_version', '0' );
        if( version_compare( $bw_capsman_version, BW_CAPSMAN_VERSION, '<' ) ) {
            $this->init_bw_capsman_caps();
            $this->put_option( 'bw_capsman_version', BW_CAPSMAN_VERSION, true );
        }
    }

    public function get_bw_capsman_object() {
        return $this->bw_capsman_object;
    }

    protected function get_bw_capsman_caps() {
        $bw_capsman_caps = array(
            'bw_capsman_edit_roles' => 1,
            'bw_capsman_create_roles' => 1,
            'bw_capsman_delete_roles' => 1,
            'bw_capsman_create_capabilities' => 1,
            'bw_capsman_delete_capabilities' => 1,
            'bw_capsman_manage_options' => 1,
            'bw_capsman_reset_roles' => 1
        );
        return $bw_capsman_caps;
    }

    public function init_bw_capsman_caps() {
        global $wp_roles;
        if( !isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        if( !isset( $wp_roles->roles['administrator'] ) ) {
            return;
        }
        $turn_on = !$this->multisite;
        $old_use_db = $wp_roles->use_db;
        $wp_roles->use_db = true;
        $administrator = $wp_roles->role_objects['administrator'];
        $bw_capsman_caps = $this->get_bw_capsman_caps();
        foreach( array_keys( $bw_capsman_caps ) as $cap ) {
            if( !$administrator->has_cap( $cap ) ) {
                $administrator->add_cap( $cap, $turn_on );
            }
        }
        $wp_roles->use_db = $old_use_db;
    }

    protected function init_options( $options_id ) {
        global $wpdb;
        if( $this->multisite ) {
            if( !function_exists( 'is_plugin_active_for_network' ) ) {
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }
            $this->active_for_network = is_plugin_active_for_network( BW_CAPSMAN_PLUGIN_BASE_NAME );
        }
        $current_blog = $wpdb->blogid;
        if( $this->multisite && $current_blog != $this->main_blog_id ) {
            if( $this->active_for_network ) {
                switch_to_blog( $this->main_blog_id );
            }
        }
        $this->options_id = $options_id;
        $this->options = get_option( $options_id );
        if( $this->multisite && $current_blog != $this->main_blog_id ) {
            if( $this->active_for_network ) {
                restore_current_blog();
            }
        }
    }

    public function flush_options() {
        global $wpdb;
        $current_blog = $wpdb->blogid;
        if( $this->multisite && $current_blog !== $this->main_blog_id ) {
            if( $this->active_for_network ) {
                switch_to_blog( $this->main_blog_id );
            }
        }
        update_option( $this->options_id, $this->options );
        if( $this->multisite && $current_blog !== $this->main_blog_id ) {
            if( $this->active_for_network ) {
                restore_current_blog();
            }
        }
    }

    public function get_main_blog_id() {
        return $this->main_blog_id;
    }

    public function get_key_capability() {
        if( !$this->multisite ) {
            $key_capability = BW_CAPSMAN_KEY_CAPABILITY;
        }
        else {
            $enable_simple_admin_for_multisite = $this->get_option( 'enable_simple_admin_for_multisite', 0 );
            if(( defined( 'BW_CAPSMAN_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE' ) && BW_CAPSMAN_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE == 1 ) || $enable_simple_admin_for_multisite ) {
                $key_capability = BW_CAPSMAN_KEY_CAPABILITY;
            }
            else {
                $key_capability = 'manage_network_users';
            }
        }
        return $key_capability;
    }

    public function get_settings_capability() {
        if( !$this->multisite ) {
            // $settings_access = 'bw_capsman_manage_options';
            $settings_access = BW_CAPSMAN_KEY_CAPABILITY;
        }
        else {
            $enable_simple_admin_for_multisite = $this->get_option( 'enable_simple_admin_for_multisite', 0 );
            if(( defined( 'BW_CAPSMAN_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE' ) && BW_CAPSMAN_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE == 1 ) || $enable_simple_admin_for_multisite ) {
                $settings_access = 'bw_capsman_manage_options';
            }
            else {
                $settings_access = $this->get_key_capability();
            }
        }
        return $settings_access;
    }

    public function editor() {
        if( !$this->editor_init0() ) {
            $this->show_message( esc_html__( 'Error: wrong request', BW_TD ) );
            return false;
        }
        $this->process_user_request();
        $this->editor_init1();
        $this->show_editor();
    }

    protected function output_role_edit_dialogs() {
?>
<script language="javascript" type="text/javascript">
  var bw_capsman_current_role = '<?php echo $this->current_role; ?>';
  var bw_capsman_current_role_name  = '<?php echo $this->current_role_name; ?>';
</script>


<div id="bw_capsman_add_role_dialog" class="bw-capsman-modal-dialog" style="padding: 10px;">
  <form id="bw_capsman_add_role_form" name="bw_capsman_add_role_form" method="POST">
    <div class="bw-capsman-label"><?php
        esc_html_e( 'Role name (ID): ', BW_TD ); ?></div>
    <div class="bw-capsman-input"><input type="text" name="user_role_id" id="user_role_id" size="25"/></div>
    <div class="bw-capsman-label"><?php
        esc_html_e( 'Display Role Name: ', BW_TD ); ?></div>
    <div class="bw-capsman-input"><input type="text" name="user_role_name" id="user_role_name" size="25"/></div>
    <div class="bw-capsman-label"><?php
        esc_html_e( 'Make copy of: ', BW_TD ); ?></div>
    <div class="bw-capsman-input"><?php
        echo $this->role_to_copy_html; ?></div>
  </form>
</div>

<div id="bw_capsman_rename_role_dialog" class="bw-capsman-modal-dialog" style="padding: 10px;">
  <form id="bw_capsman_rename_role_form" name="bw_capsman_rename_role_form" method="POST">
    <div class="bw-capsman-label"><?php
        esc_html_e( 'Role name (ID): ', BW_TD ); ?></div>
    <div class="bw-capsman-input"><input type="text" name="ren_user_role_id" id="ren_user_role_id" size="25" disabled /></div>
    <div class="bw-capsman-label"><?php
        esc_html_e( 'Display Role Name: ', BW_TD ); ?></div>
    <div class="bw-capsman-input"><input type="text" name="ren_user_role_name" id="ren_user_role_name" size="25"/></div>
  </form>
</div>

<div id="bw_capsman_delete_role_dialog" class="bw-capsman-modal-dialog">
  <div style="padding:10px;">
    <div class="bw-capsman-label"><?php
        esc_html_e( 'Select Role:', BW_TD ); ?></div>
    <div class="bw-capsman-input"><?php
        echo $this->role_delete_html; ?></div>
  </div>
</div>


<div id="bw_capsman_default_role_dialog" class="bw-capsman-modal-dialog">
  <div style="padding:10px;">
    <?php
        echo $this->role_default_html; ?>
  </div>
</div>


<div id="bw_capsman_delete_capability_dialog" class="bw-capsman-modal-dialog">
  <div style="padding:10px;">
    <div class="bw-capsman-label"><?php
        esc_html_e( 'Delete:', BW_TD ); ?></div>
    <div class="bw-capsman-input"><?php
        echo $this->capability_remove_html; ?></div>
  </div>
</div>

<div id="bw_capsman_add_capability_dialog" class="bw-capsman-modal-dialog">
  <div style="padding:10px;">
    <div class="bw-capsman-label"><?php
        esc_html_e( 'Capability name (ID): ', BW_TD ); ?></div>
    <div class="bw-capsman-input"><input type="text" name="capability_id" id="capability_id" size="25"/></div>
  </div>
</div>

<?php
        do_action( 'bw_capsman_dialogs_html' );
    }

    protected function show_editor() {

        $this->show_message( $this->notification );
?>
<div class="wrap">
          <div id="bw-capsman-icon" class="icon32"><br/></div>
    <h2><?php
        _e( 'Capability Manager', BW_TD ); ?></h2>
    <div id="poststuff">

        <div class="has-sidebar" >
            <form id="bw_capsman_form" method="post" action="<?php
        echo BW_CAPSMAN_WP_ADMIN_URL . BW_CAPSMAN_PARENT . '?page=users-bw-capsman'; ?>" >
                <div id="bw_capsman_form_controls">
<?php
        wp_nonce_field( BW_TD, 'bw_capsman_nonce' );
        if( $this->bw_capsman_object == 'user' ) {
            require_once( BW_CAPSMAN_PLUGIN_DIR . 'includes/bw-capsman-user-edit.php' );
        }
        else {
            $this->set_current_role();
            $this->role_edit_prepare_html();
            require_once( BW_CAPSMAN_PLUGIN_DIR . 'includes/bw-capsman-role-edit.php' );
        }
?>
                    <input type="hidden" name="action" value="update" />
                </div>
            </form>
<?php

        if( $this->bw_capsman_object == 'role' ) {
            $this->output_role_edit_dialogs();
        }
?>
        </div>
    </div>
</div>
<?php
    }

    protected function check_user_to_edit() {

        if( $this->bw_capsman_object == 'user' ) {
            if( !isset( $_REQUEST['user_id'] ) ) {
                return false;
            }
            $user_id = $_REQUEST['user_id'];
            if( !is_numeric( $user_id ) ) {
                return false;
            }
            if( !$user_id ) {
                return false;
            }
            $this->user_to_edit = get_user_to_edit( $user_id );
            if( empty( $this->user_to_edit ) ) {
                return false;
            }
        }

        return true;
    }

    protected function init_current_role_name() {

        if( !isset( $this->roles[$_POST['user_role']] ) ) {
            $mess = esc_html__( 'Error: ', BW_TD ) . esc_html__( 'Role', BW_TD ) . ' <em>' . esc_html( $_POST['user_role'] ) . '</em> ' . esc_html__( 'does not exist', BW_TD );
            $this->current_role = '';
            $this->current_role_name = '';
        }
        else {
            $this->current_role = $_POST['user_role'];
            $this->current_role_name = $this->roles[$this->current_role]['name'];
            $mess = '';
        }

        return $mess;
    }

    protected function prepare_capabilities_to_save() {
        $this->capabilities_to_save = array();
        foreach( $this->full_capabilities as $available_capability ) {
            $cap_id = str_replace( ' ', BW_CAPSMAN_SPACE_REPLACER, $available_capability['inner'] );
            if( isset( $_POST[$cap_id] ) ) {
                $this->capabilities_to_save[$available_capability['inner']] = true;
            }
        }
    }

    protected function permissions_object_update( $mess ) {

        if( $this->bw_capsman_object == 'role' ) {
            if( $this->update_roles() ) {
                if( $mess ) {
                    $mess.= '<br/>';
                }
                if( !$this->apply_to_all ) {
                    $mess = esc_html__( 'Role is updated successfully', BW_TD );
                }
                else {
                    $mess = esc_html__( 'Roles are updated for all network', BW_TD );
                }
            }
            else {
                if( $mess ) {
                    $mess.= '<br/>';
                }
                $mess = esc_html__( 'Error occured during role(s) update', BW_TD );
            }
        }
        else {
            if( $this->update_user( $this->user_to_edit ) ) {
                if( $mess ) {
                    $mess.= '<br/>';
                }
                $mess = esc_html__( 'User capabilities are updated successfully', BW_TD );
            }
            else {
                if( $mess ) {
                    $mess.= '<br/>';
                }
                $mess = esc_html__( 'Error occured during user update', BW_TD );
            }
        }
        return $mess;
    }

    protected function process_user_request() {

        $this->notification = '';
        if( isset( $_POST['action'] ) ) {
            if( empty( $_POST['bw_capsman_nonce'] ) || !wp_verify_nonce( $_POST['bw_capsman_nonce'], BW_TD ) ) {
                echo '<h3>Wrong nonce. Action prohibitied.</h3>';
                exit;
            }

            $action = $_POST['action'];

            if( $action == 'reset' ) {
                $this->reset_user_roles();
                exit;
            }
            else if( $action == 'add-new-role' ) {
                $this->notification = $this->add_new_role();
            }
            else if( $action == 'rename-role' ) {
                $this->notification = $this->rename_role();
            }
            else if( $action == 'delete-role' ) {
                $this->notification = $this->delete_role();
            }
            else if( $action == 'change-default-role' ) {
                $this->notification = $this->change_default_role();
            }
            else if( $action == 'caps-readable' ) {
                if( $this->caps_readable ) {
                    $this->caps_readable = 0;
                }
                else {
                    $this->caps_readable = 1;
                }
                set_site_transient( 'bw_capsman_caps_readable', $this->caps_readable, 600 );
            }
            else if( $action == 'show-deprecated-caps' ) {
                if( $this->show_deprecated_caps ) {
                    $this->show_deprecated_caps = 0;
                }
                else {
                    $this->show_deprecated_caps = 1;
                }
                set_site_transient( 'bw_capsman_show_deprecated_caps', $this->show_deprecated_caps, 600 );
            }
            else if( $action == 'add-new-capability' ) {
                $this->notification = $this->add_new_capability();
            }
            else if( $action == 'delete-user-capability' ) {
                $this->notification = $this->delete_capability();
            }
            else if( $action == 'roles_restore_note' ) {
                $this->notification = esc_html__( 'User Roles are restored to WordPress default values. ', BW_TD );
            }
            else if( $action == 'update' ) {
                $this->roles = $this->get_user_roles();
                $this->init_full_capabilities();
                if( isset( $_POST['user_role'] ) ) {
                    $this->notification = $this->init_current_role_name();
                }
                $this->prepare_capabilities_to_save();
                $this->notification = $this->permissions_object_update( $this->notification );
            }
            else {
                do_action( 'bw_capsman_process_user_request' );
            }
        }
    }

    protected function set_apply_to_all() {
        if( isset( $_POST['bw_capsman_apply_to_all'] ) ) {
            $this->apply_to_all = 1;
        }
        else {
            $this->apply_to_all = 0;
        }
    }

    public function get_default_role() {
        $this->wp_default_role = get_option( 'default_role' );
    }

    protected function editor_init0() {
        $this->caps_readable = get_site_transient( 'bw_capsman_caps_readable' );
        if( false === $this->caps_readable ) {
            $this->caps_readable = $this->get_option( 'bw_capsman_caps_readable' );
            set_site_transient( 'bw_capsman_caps_readable', $this->caps_readable, 600 );
        }
        $this->show_deprecated_caps = get_site_transient( 'bw_capsman_show_deprecated_caps' );
        if( false === $this->show_deprecated_caps ) {
            $this->show_deprecated_caps = $this->get_option( 'bw_capsman_show_deprecated_caps' );
            set_site_transient( 'bw_capsman_caps_readable', $this->caps_readable, 600 );
        }
        $this->get_default_role();

        if( isset( $_REQUEST['object'] ) ) {
            $this->bw_capsman_object = $_REQUEST['object'];
            if( !$this->check_user_to_edit() ) {
                return false;
            }
        }
        else {
            $this->bw_capsman_object = 'role';
        }

        $this->set_apply_to_all();

        return true;
    }

    public function editor_init1() {

        if( !isset( $this->roles ) || !$this->roles ) {
            $this->roles = $this->get_user_roles();
        }

        $this->init_full_capabilities();
    }

    protected function get_last_role_id() {

        $keys = array_keys( $this->roles );
        $last_role_id = array_pop( $keys );

        return $last_role_id;
    }

    public function has_administrator_role( $user_id ) {
        global $wpdb;

        if( empty( $user_id ) || !is_numeric( $user_id ) ) {
            return false;
        }

        $table_name =( !$this->multisite && defined( 'CUSTOM_USER_META_TABLE' ) ) ? CUSTOM_USER_META_TABLE : $wpdb->usermeta;
        $meta_key = $wpdb->prefix . 'capabilities';
        $query = "SELECT count(*)
                FROM $table_name
                WHERE user_id=$user_id AND meta_key='$meta_key' AND meta_value like '%administrator%'";
        $has_admin_role = $wpdb->get_var( $query );
        if( $has_admin_role > 0 ) {
            $result = true;
        }
        else {
            $result = false;
        }
        $this->lib->user_to_check[$user_id] = $result;

        return $result;
    }

    public function user_is_normal_admin($user_id = false){
        global $current_user;
        $bw_capsman_key_capability = 'administrator';
        if( empty( $user_id ) ) {
            $user_id = $current_user->ID;
        }
        $result = user_can( $user_id, $bw_capsman_key_capability );

        return $result;
    }

    public function user_is_admin( $user_id = false ) {
        global $current_user;

        $bw_capsman_key_capability = $this->get_key_capability();
        if( empty( $user_id ) ) {
            $user_id = $current_user->ID;
        }
        $result = user_can( $user_id, $bw_capsman_key_capability );

        return $result;
    }

    public function get_user_roles() {

        global $wp_roles;

        if( !isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        if( function_exists( 'bbp_filter_blog_editable_roles' ) ) {
            $this->roles = bbp_filter_blog_editable_roles( $wp_roles->roles );
            $bbp_full_caps = bbp_get_caps_for_role( bbp_get_keymaster_role() );
            $built_in_wp_caps = $this->get_built_in_wp_caps();
            $bbp_only_caps = array();
            foreach( $bbp_full_caps as $bbp_cap => $val ) {
                if( isset( $built_in_wp_caps[$bbp_cap] ) || substr( $bbp_cap, 0, 15 ) == 'access_s2member' ) {
                    continue;
                }
                $bbp_only_caps[$bbp_cap] = $val;
            }
            $cap_removed = false;
            foreach( $bbp_only_caps as $bbp_cap => $val ) {
                foreach( $this->roles as & $role ) {
                    if( isset( $role['capabilities'][$bbp_cap] ) ) {
                        unset( $role['capabilities'][$bbp_cap] );
                        $cap_removed = true;
                    }
                }
            }
        }
        else {
            $this->roles = $wp_roles->roles;
        }

        if( is_array( $this->roles ) && count( $this->roles ) > 0 ) {
            asort( $this->roles );
        }

        return $this->roles;
    }

    protected function convert_caps_to_readable( $caps_name ) {

        $caps_name = str_replace( '_', ' ', $caps_name );
        $caps_name = ucfirst( $caps_name );

        return $caps_name;
    }

    public function make_roles_backup() {
        global $wpdb;

        $backup_option_name = $wpdb->prefix . 'backup_user_roles';
        $query = "select option_id
              from $wpdb->options
              where option_name='$backup_option_name'
          limit 0, 1";
        $option_id = $wpdb->get_var( $query );
        if( $wpdb->last_error ) {
            $this->log_event( $wpdb->last_error, true );
            return false;
        }
        if( !$option_id ) {
            $roles_option_name = $wpdb->prefix . 'user_roles';
            $query = "select option_value
                        from $wpdb->options
                        where option_name like '$roles_option_name' limit 0,1";
            $serialized_roles = $wpdb->get_var( $query );
            $query = "insert into $wpdb->options
                (option_name, option_value, autoload)
                values ('$backup_option_name', '$serialized_roles', 'no')";
            $record = $wpdb->query( $query );
            if( $wpdb->last_error ) {
                $this->log_event( $wpdb->last_error, true );
                return false;
            }
        }

        return true;
    }

    protected function role_contains_caps_not_allowed_for_simple_admin( $role_id ) {

        $result = false;
        $role = $this->roles[$role_id];
        if( !is_array( $role['capabilities'] ) ) {
            return false;
        }
        foreach( array_keys( $role['capabilities'] ) as $cap ) {
            if( $this->block_cap_for_single_admin( $cap ) ) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    protected function get_roles_can_delete() {
        global $wpdb;

        $table_name =( !$this->multisite && defined( 'CUSTOM_USER_META_TABLE' ) ) ? CUSTOM_USER_META_TABLE : $wpdb->usermeta;
        $meta_key = $wpdb->prefix . 'capabilities';
        $default_role = get_option( 'default_role' );
        // $standard_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        $standard_roles = array('administrator', 'subscriber');
        $roles_can_delete = array();
        foreach( $this->roles as $key => $role ) {
            $can_delete = true;
            if( $key == $default_role ) {
                $can_delete = false;
                continue;
            }
            if( in_array( $key, $standard_roles ) ) {
                continue;
            }
            if( $this->role_contains_caps_not_allowed_for_simple_admin( $key ) ) {
                continue;
            }

            $query = "SELECT DISTINCT meta_value
                FROM $table_name
                WHERE meta_key='$meta_key' AND meta_value like '%$key%'";
            $roles_used = $wpdb->get_results( $query );
            if( $roles_used && count( $roles_used > 0 ) ) {
                foreach( $roles_used as $role_used ) {
                    $role_name = unserialize( $role_used->meta_value );
                    foreach( $role_name as $key1 => $value1 ) {
                        if( $key == $key1 ) {
                            $can_delete = false;
                            break;
                        }
                    }
                    if( !$can_delete ) {
                        break;
                    }
                }
            }
            if( $can_delete ) {
                $roles_can_delete[$key] = $role['name'] . ' (' . $key . ')';
            }
        }

        return $roles_can_delete;
    }

    public function get_built_in_wp_caps() {
        $caps = array();
        $caps['switch_themes'] = 1;
        $caps['edit_themes'] = 1;
        $caps['activate_plugins'] = 1;
        $caps['edit_plugins'] = 1;
        $caps['edit_users'] = 1;
        $caps['edit_files'] = 1;
        $caps['manage_options'] = 1;
        $caps['moderate_comments'] = 1;
        $caps['manage_categories'] = 1;
        $caps['manage_links'] = 1;
        $caps['upload_files'] = 1;
        $caps['import'] = 1;
        $caps['unfiltered_html'] = 1;
        $caps['edit_posts'] = 1;
        $caps['edit_others_posts'] = 1;
        $caps['edit_published_posts'] = 1;
        $caps['publish_posts'] = 1;
        $caps['edit_pages'] = 1;
        $caps['read'] = 1;
        $caps['level_10'] = 1;
        $caps['level_9'] = 1;
        $caps['level_8'] = 1;
        $caps['level_7'] = 1;
        $caps['level_6'] = 1;
        $caps['level_5'] = 1;
        $caps['level_4'] = 1;
        $caps['level_3'] = 1;
        $caps['level_2'] = 1;
        $caps['level_1'] = 1;
        $caps['level_0'] = 1;
        $caps['edit_others_pages'] = 1;
        $caps['edit_published_pages'] = 1;
        $caps['publish_pages'] = 1;
        $caps['delete_pages'] = 1;
        $caps['delete_others_pages'] = 1;
        $caps['delete_published_pages'] = 1;
        $caps['delete_posts'] = 1;
        $caps['delete_others_posts'] = 1;
        $caps['delete_published_posts'] = 1;
        $caps['delete_private_posts'] = 1;
        $caps['edit_private_posts'] = 1;
        $caps['read_private_posts'] = 1;
        $caps['delete_private_pages'] = 1;
        $caps['edit_private_pages'] = 1;
        $caps['read_private_pages'] = 1;
        $caps['unfiltered_upload'] = 1;
        $caps['edit_dashboard'] = 1;
        $caps['update_plugins'] = 1;
        $caps['delete_plugins'] = 1;
        $caps['install_plugins'] = 1;
        $caps['update_themes'] = 1;
        $caps['install_themes'] = 1;
        $caps['update_core'] = 1;
        $caps['list_users'] = 1;
        $caps['remove_users'] = 1;
        $caps['add_users'] = 1;
        $caps['promote_users'] = 1;
        $caps['edit_theme_options'] = 1;
        $caps['delete_themes'] = 1;
        $caps['export'] = 1;
        $caps['delete_users'] = 1;
        $caps['create_users'] = 1;
        if( $this->multisite ) {
            $caps['manage_network'] = 1;
            $caps['manage_sites'] = 1;
            $caps['create_sites'] = 1;
            $caps['manage_network_users'] = 1;
            $caps['manage_network_themes'] = 1;
            $caps['manage_network_plugins'] = 1;
            $caps['manage_network_options'] = 1;
        }

        return $caps;
    }

    protected function get_caps_to_remove() {
        global $wp_roles;

        $full_caps_list = array();
        foreach( $wp_roles->roles as $role ) {
            if( isset( $role['capabilities'] ) && is_array( $role['capabilities'] ) ) {
                foreach( $role['capabilities'] as $capability => $value ) {
                    if( !isset( $full_caps_list[$capability] ) ) {
                        $full_caps_list[$capability] = 1;
                    }
                }
            }
        }

        $caps_to_exclude = $this->get_built_in_wp_caps();
        $bw_capsman_caps = $this->get_bw_capsman_caps();
        $caps_to_exclude = array_merge( $caps_to_exclude, $bw_capsman_caps );

        $caps_to_remove = array();
        foreach( $full_caps_list as $capability => $value ) {
            if( !isset( $caps_to_exclude[$capability] ) ) {
                $cap_in_use = false;
                foreach( $wp_roles->role_objects as $wp_role ) {
                    if( $wp_role->name != 'administrator' ) {
                        if( $wp_role->has_cap( $capability ) ) {
                            $cap_in_use = true;
                            break;
                        }
                    }
                }
                if( !$cap_in_use ) {
                    $caps_to_remove[$capability] = 1;
                }
            }
        }

        return $caps_to_remove;
    }

    protected function get_caps_to_remove_html() {

        $caps_to_remove = $this->get_caps_to_remove();
        if( !empty( $caps_to_remove ) && is_array( $caps_to_remove ) && count( $caps_to_remove ) > 0 ) {
            $html = '<select id="remove_user_capability" name="remove_user_capability" width="200" style="width: 200px">';
            foreach( $caps_to_remove as $key => $value ) {
                $html.= '<option value="' . $key . '">' . $key . '</option>';
            }
            $html.= '</select>';
        }
        else {
            $html = '';
        }

        return $html;
    }

    protected function get_deprecated_caps() {

        $dep_caps = array('level_0' => 0, 'level_1' => 0, 'level_2' => 0, 'level_3' => 0, 'level_4' => 0, 'level_5' => 0, 'level_6' => 0, 'level_7' => 0, 'level_8' => 0, 'level_9' => 0, 'level_10' => 0, 'edit_files' => 0);
        if( $this->multisite ) {
            $dep_caps['unfiltered_html'] = 0;
        }

        return $dep_caps;
    }

    protected function block_cap_for_single_admin( $capability, $ignore_super_admin = false ) {

        if( !$this->multisite ) {
            return false;
        }
        if( !$ignore_super_admin && is_super_admin() ) {
            return false;
        }
        $caps_access_restrict_for_simple_admin = $this->get_option( 'caps_access_restrict_for_simple_admin', 0 );
        if( !$caps_access_restrict_for_simple_admin ) {
            return false;
        }
        $allowed_caps = $this->get_option( 'caps_allowed_for_single_admin', array() );
        if( in_array( $capability, $allowed_caps ) ) {
            $block_this_cap = false;
        }
        else {
            $block_this_cap = true;
        }

        return $block_this_cap;
    }

    protected function show_capabilities( $core = true, $for_role = true, $edit_mode = true ) {

        if( $this->multisite && !is_super_admin() ) {
            $help_links_enabled = $this->get_option( 'enable_help_links_for_simple_admin_ms', 1 );
        }
        else {
            $help_links_enabled = true;
        }

        $onclick_for_admin = '';
        if( !( $this->multisite && is_super_admin() ) ) {
            if( $core && 'administrator' == $this->current_role ) {
                $onclick_for_admin = 'onclick="turn_it_back(this)"';
            }
        }

        if( $core ) {
            $quant = count( $this->get_built_in_wp_caps() );
            $deprecated_caps = $this->get_deprecated_caps();
        }
        else {
            $quant = count( $this->full_capabilities ) - count( $this->get_built_in_wp_caps() );
            $deprecated_caps = array();
        }
        $quant_in_column = (int)$quant / 3;
        $printed_quant = 0;
        foreach( $this->full_capabilities as $capability ) {
            if( $core ) {
                if( !$capability['wp_core'] ) {
                    continue;
                }
            }
            else {
                if( $capability['wp_core'] ) {
                    continue;
                }
            }
            if( !$this->show_deprecated_caps && isset( $deprecated_caps[$capability['inner']] ) ) {
                $hidden_class = 'class="hidden"';
            }
            else {
                $hidden_class = '';
            }
            if( isset( $deprecated_caps[$capability['inner']] ) ) {
                $label_style = 'style="color:#BBBBBB;"';
            }
            else {
                $label_style = '';
            }
            if( $this->multisite && $this->block_cap_for_single_admin( $capability['inner'], true ) ) {
                if( is_super_admin() ) {
                    if( !is_network_admin() ) {
                        $label_style = 'style="color: red;"';
                    }
                }
                else {
                    $hidden_class = 'class="hidden"';
                }
            }
            $checked = '';
            $disabled = '';
            if( $for_role ) {
                if( isset( $this->roles[$this->current_role]['capabilities'][$capability['inner']] ) && !empty( $this->roles[$this->current_role]['capabilities'][$capability['inner']] ) ) {
                    $checked = 'checked="checked"';
                }
            }
            else {
                if( empty( $edit_mode ) ) {
                    $disabled = 'disabled="disabled"';
                }
                else {
                    $disabled = '';
                }
                if( $this->user_can( $capability['inner'] ) ) {
                    $checked = 'checked="checked"';
                    if( !isset( $this->user_to_edit->caps[$capability['inner']] ) ) {
                        $disabled = 'disabled="disabled"';
                    }
                }
            }
            $cap_id = str_replace( ' ', BW_CAPSMAN_SPACE_REPLACER, $capability['inner'] );
            echo '<div id="bw_capsman_div_cap_' . $cap_id . '" ' . $hidden_class . '><input type="checkbox" name="' . $cap_id . '" id="' . $cap_id . '" value="' . $capability['inner'] . '" ' . $checked . ' ' . $disabled . ' ' . $onclick_for_admin . '>';
            if( empty( $hidden_class ) ) {
                if( $this->caps_readable ) {
                    $cap_ind = 'human';
                    $cap_ind_alt = 'inner';
                }
                else {
                    $cap_ind = 'inner';
                    $cap_ind_alt = 'human';
                }
                $help_link = $help_links_enabled ? $this->capability_help_link( $capability['inner'] ) : '';
                echo '<label for="' . $cap_id . '" title="' . $capability[$cap_ind_alt] . '" ' . $label_style . ' > ' . $capability[$cap_ind] . '</label> ' . $help_link . '</div>';
                $printed_quant++;
                if( $printed_quant >= $quant_in_column ) {
                    $printed_quant = 0;
                    echo '</td>
                          <td style="vertical-align:top;">';
                }
            }
            else {
                echo '</div>';
            }
        }
    }

    protected function toolbar( $role_delete = false, $capability_remove = false ) {
        $caps_access_restrict_for_simple_admin = $this->get_option( 'caps_access_restrict_for_simple_admin', 0 );
        if( $caps_access_restrict_for_simple_admin ) {
            $add_del_role_for_simple_admin = $this->get_option( 'add_del_role_for_simple_admin', 1 );
        }
        else {
            $add_del_role_for_simple_admin = 1;
        }
        $super_admin = is_super_admin();
?>
        <div id="bw_capsman_toolbar" >
           <button id="bw_capsman_select_all" class="bw_capsman_toolbar_button">Select All</button>
<?php
        if( 'administrator' != $this->current_role ) {
?>
               <button id="bw_capsman_unselect_all" class="bw_capsman_toolbar_button">Unselect All</button>
               <button id="bw_capsman_reverse_selection" class="bw_capsman_toolbar_button">Reverse</button>
<?php
        }
        if( $this->bw_capsman_object == 'role' ) {
?>
               <hr />
               <div id="bw_capsman_update">
                <button id="bw_capsman_update_role" class="bw_capsman_toolbar_button button-primary" >Update</button>
<?php
            do_action( 'bw_capsman_role_edit_toolbar_update' );
?>
               </div>
<?php
            if( !$this->multisite || $super_admin || $add_del_role_for_simple_admin ) { ?>
               <hr />
<?php
                if( current_user_can( 'bw_capsman_create_roles' ) ) {
?>
               <button id="bw_capsman_add_role" class="bw_capsman_toolbar_button">Add Role</button>
<?php
                }
?>
               <button id="bw_capsman_rename_role" class="bw_capsman_toolbar_button">Rename Role</button>
<?php
            }
            if( !$this->multisite || $super_admin || !$caps_access_restrict_for_simple_admin ) {
                if( current_user_can( 'bw_capsman_create_capabilities' ) ) {
?>
               <button id="bw_capsman_add_capability" class="bw_capsman_toolbar_button">Add Capability</button>
<?php
                }
            }
            if( !$this->multisite || $super_admin || $add_del_role_for_simple_admin ) {
                if( !empty( $role_delete ) && current_user_can( 'bw_capsman_delete_roles' ) ) {
?>
                   <button id="bw_capsman_delete_role" class="bw_capsman_toolbar_button">Delete Role</button>
<?php
                }
            }
            if( !$this->multisite || $super_admin || !$caps_access_restrict_for_simple_admin ) {
                if( $capability_remove && current_user_can( 'bw_capsman_delete_capabilities' ) ) {
?>
                   <button id="bw_capsman_delete_capability" class="bw_capsman_toolbar_button">Delete Capability</button>
<?php
                }
?>
               <hr />
               <button id="bw_capsman_default_role" class="bw_capsman_toolbar_button">Default Role</button>
               <hr />
               <div id="bw_capsman_service_tools">
<?php
                do_action( 'bw_capsman_role_edit_toolbar_service' );
                if( !$this->multisite ||( is_main_site( get_current_blog_id() ) ||( is_network_admin() && is_super_admin() ) ) ) {
                    if( current_user_can( 'bw_capsman_reset_roles' ) ) {
?>
                  <button id="bw_capsman_reset_roles_button" class="bw_capsman_toolbar_button" style="color: red;" title="Reset Roles to its original state">Reset</button>
<?php
                    }
                }
?>
               </div>
            <?php
            }
        }
        else {
?>

               <hr />
                 <div id="bw_capsman_update_user">
                <button id="bw_capsman_update_role" class="bw_capsman_toolbar_button button-primary">Update</button>
<?php
            do_action( 'bw_capsman_user_edit_toolbar_update' );
?>

                 </div>
            <?php
        }
?>

        </div>
        <?php
    }

    protected function capability_help_link( $capability ) {

        if( empty( $capability ) ) {
            return '';
        }

        switch( $capability ){
        case 'activate_plugins':
            $url = 'http://www.shinephp.com/activate_plugins-wordpress-capability/';
            break;

        case 'add_users':
            $url = 'http://www.shinephp.com/add_users-wordpress-user-capability/';
            break;

        case 'create_users':
            $url = 'http://www.shinephp.com/create_users-wordpress-user-capability/';
            break;

        case 'delete_others_pages':
        case 'delete_others_posts':
        case 'delete_pages':
        case 'delete_posts':
        case 'delete_protected_pages':
        case 'delete_protected_posts':
        case 'delete_published_pages':
        case 'delete_published_posts':
            $url = 'http://www.shinephp.com/delete-posts-and-pages-wordpress-user-capabilities-set/';
            break;

        case 'delete_plugins':
            $url = 'http://www.shinephp.com/delete_plugins-wordpress-user-capability/';
            break;

        case 'delete_themes':
            $url = 'http://www.shinephp.com/delete_themes-wordpress-user-capability/';
            break;

        case 'delete_users':
            $url = 'http://www.shinephp.com/delete_users-wordpress-user-capability/';
            break;

        case 'edit_dashboard':
            $url = 'http://www.shinephp.com/edit_dashboard-wordpress-capability/';
            break;

        case 'edit_files':
            $url = 'http://www.shinephp.com/edit_files-wordpress-user-capability/';
            break;

        case 'edit_plugins':
            $url = 'http://www.shinephp.com/edit_plugins-wordpress-user-capability';
            break;

        case 'moderate_comments':
            $url = 'http://www.shinephp.com/moderate_comments-wordpress-user-capability/';
            break;

        case 'read':
            $url = 'http://shinephp.com/wordpress-read-capability/';
            break;

        case 'update_core':
            $url = 'http://www.shinephp.com/update_core-capability-for-wordpress-user/';
            break;

        case 'bw_capsman_edit_roles':
            $url = 'https://www.role-editor.com/bw-capsman-4-18-new-permissions/';
            break;

        default:
            $url = '';
        }
        if( !empty( $url ) ) {
            $link = '<a href="' . $url . '" title="' . esc_html__( 'read about', BW_TD ) . ' ' . $capability . ' ' . esc_html__( 'user capability', BW_TD ) . '" target="new"><img src="' . BW_CAPSMAN_PLUGIN_URL . 'images/help.png" alt="' . esc_html__( 'Help', BW_TD ) . '" /></a>';
        }
        else {
            $link = '';
        }

        return $link;
    }

    protected function validate_user_roles() {

        global $wp_roles;

        $default_role = get_option( 'default_role' );
        if( empty( $default_role ) ) {
            $default_role = 'subscriber';
        }
        $users_query = new WP_User_Query( array('fields' => 'ID') );
        $users = $users_query->get_results();
        foreach( $users as $user_id ) {
            $user = get_user_by( 'id', $user_id );
            if( is_array( $user->roles ) && count( $user->roles ) > 0 ) {
                foreach( $user->roles as $role ) {
                    $user_role = $role;
                    break;
                }
            }
            else {
                $user_role = is_array( $user->roles ) ? '' : $user->roles;
            }
            if( !empty( $user_role ) && !isset( $wp_roles->roles[$user_role] ) ) {
                $user->set_role( $default_role );
                $user_role = '';
            }

            if( empty( $user_role ) ) {
                $cap_removed = true;
                while( count( $user->caps ) > 0 && $cap_removed ) {
                    foreach( $user->caps as $capability => $value ) {
                        if( !isset( $this->full_capabilities[$capability] ) ) {
                            $user->remove_cap( $capability );
                            $cap_removed = true;
                            break;
                        }
                        $cap_removed = false;
                    }
                }
            }
        }
    }

    protected function add_capability_to_full_caps_list( $cap_id ) {
        if( !isset( $this->full_capabilities[$cap_id] ) ) {
            $cap = array();
            $cap['inner'] = $cap_id;
            $cap['human'] = esc_html__( $this->convert_caps_to_readable( $cap_id ), BW_TD );
            if( isset( $this->built_in_wp_caps[$cap_id] ) ) {
                $cap['wp_core'] = true;
            }
            else {
                $cap['wp_core'] = false;
            }

            $this->full_capabilities[$cap_id] = $cap;
        }
    }

    protected function add_roles_caps() {
        foreach( $this->roles as $role ) {
            if( isset( $role['capabilities'] ) && is_array( $role['capabilities'] ) ) {
                foreach( array_keys( $role['capabilities'] ) as $cap ) {
                    $this->add_capability_to_full_caps_list( $cap );
                }
            }
        }
    }

    protected function add_gravity_forms_caps() {

        if( class_exists( 'GFCommon' ) ) {
            $gf_caps = GFCommon::all_caps();
            foreach( $gf_caps as $gf_cap ) {
                $this->add_capability_to_full_caps_list( $gf_cap );
            }
        }
    }

    protected function add_members_caps() {

        $custom_caps = array();
        $custom_caps = apply_filters( 'members_get_capabilities', $custom_caps );
        foreach( $custom_caps as $cap ) {
            $this->add_capability_to_full_caps_list( $cap );
        }
    }

    protected function add_user_caps() {

        if( $this->bw_capsman_object == 'user' ) {
            foreach( array_keys( $this->user_to_edit->caps ) as $cap ) {
                if( !isset( $this->roles[$cap] ) ) {
                    $this->add_capability_to_full_caps_list( $cap );
                }
            }
        }
    }

    protected function add_wordpress_caps() {

        foreach( array_keys( $this->built_in_wp_caps ) as $cap ) {
            $this->add_capability_to_full_caps_list( $cap );
        }
    }

    protected function add_custom_post_type_caps() {

        $capabilities = array('create_posts', 'edit_posts', 'edit_published_posts', 'edit_others_posts', 'edit_private_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts');
        $post_types = get_post_types( array('public' => true, 'show_ui' => true, '_builtin' => false), 'objects' );
        foreach( $post_types as $post_type ) {
            if( $post_type->capability_type == 'post' ) {
                continue;
            }
            if( !isset( $post_type->cap ) ) {
                continue;
            }
            foreach( $capabilities as $capability ) {
                if( isset( $post_type->cap->$capability ) ) {
                    $this->add_capability_to_full_caps_list( $post_type->cap->$capability );
                }
            }
        }
    }

    protected function add_bw_capsman_caps() {

        $bw_capsman_caps = $this->get_bw_capsman_caps();
        foreach( array_keys( $bw_capsman_caps ) as $cap ) {
            $this->add_capability_to_full_caps_list( $cap );
        }
    }

    protected function init_full_capabilities() {

        $this->built_in_wp_caps = $this->get_built_in_wp_caps();
        $this->full_capabilities = array();
        $this->add_roles_caps();
        $this->add_gravity_forms_caps();
        $this->add_members_caps();
        $this->add_user_caps();
        $this->add_wordpress_caps();
        $this->add_custom_post_type_caps();
        $this->add_bw_capsman_caps();

        unset( $this->built_in_wp_caps );
        asort( $this->full_capabilities );
    }

    protected function wp_roles_reinit() {
        global $wp_roles;

        $wp_roles->roles = array();
        $wp_roles->role_objects = array();
        $wp_roles->role_names = array();
        $wp_roles->use_db = true;

        require_once( ABSPATH . '/wp-admin/includes/schema.php' );
        populate_roles();
        $wp_roles->reinit();

        $this->roles = $this->get_user_roles();
    }

    protected function reset_user_roles() {

        if( !current_user_can( 'bw_capsman_reset_roles' ) ) {
            return esc_html__( 'Insufficient permissions to work with Capability Manager', BW_TD );
        }

        $this->wp_roles_reinit();
        $this->init_bw_capsman_caps();
        if( $this->is_full_network_synch() || $this->apply_to_all ) {
            $this->current_role = '';
            $this->direct_network_roles_update();
        }

        $reload_link = wp_get_referer();
        $reload_link = esc_url_raw( remove_query_arg( 'action', $reload_link ) );
?>
            <script type="text/javascript" >
             jQuery.bw_capsman_postGo('<?php
        echo $reload_link; ?>',
                      { action: 'roles_restore_note',
                        bw_capsman_nonce: bw_capsman_data.wp_nonce} );
            </script>
        <?php
    }

    public function is_full_network_synch() {

        $result = defined( 'BW_CAPSMAN_MULTISITE_DIRECT_UPDATE' ) && BW_CAPSMAN_MULTISITE_DIRECT_UPDATE == 1;

        return $result;
    }

    protected function last_check_before_update() {
        if( empty( $this->roles ) || !is_array( $this->roles ) || count( $this->roles ) == 0 ) {
            return false;
        }

        return true;
    }

    protected function save_roles() {
        global $wpdb;

        if( !$this->last_check_before_update() ) {
            return false;
        }
        if( !isset( $this->roles[$this->current_role] ) ) {
            return false;
        }

        $this->capabilities_to_save = $this->remove_caps_not_allowed_for_single_admin( $this->capabilities_to_save );
        $this->roles[$this->current_role]['capabilities'] = $this->capabilities_to_save;
        $option_name = $wpdb->prefix . 'user_roles';

        update_option( $option_name, $this->roles );

        return true;
    }

    function direct_network_roles_update() {
        global $wpdb;

        if( !$this->last_check_before_update() ) {
            return false;
        }
        if( !empty( $this->current_role ) ) {
            if( !isset( $this->roles[$this->current_role] ) ) {
                $this->roles[$this->current_role]['name'] = $this->current_role_name;
            }
            $this->roles[$this->current_role]['capabilities'] = $this->capabilities_to_save;
        }

        $serialized_roles = serialize( $this->roles );
        foreach( $this->blog_ids as $blog_id ) {
            $prefix = $wpdb->get_blog_prefix( $blog_id );
            $options_table_name = $prefix . 'options';
            $option_name = $prefix . 'user_roles';
            $query = "update $options_table_name
                set option_value='$serialized_roles'
                where option_name='$option_name'
                limit 1";
            $wpdb->query( $query );
            if( $wpdb->last_error ) {
                $this->log_event( $wpdb->last_error, true );
                return false;
            }
        }

        return true;
    }

    public function restore_after_blog_switching( $blog_id = 0 ) {

        if( !empty( $blog_id ) ) {
            switch_to_blog( $blog_id );
        }
        $GLOBALS['_wp_switched_stack'] = array();
        $GLOBALS['switched'] = !empty( $GLOBALS['_wp_switched_stack'] );
    }

    protected function wp_api_network_roles_update() {
        global $wpdb;

        $result = true;
        $old_blog = $wpdb->blogid;
        foreach( $this->blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            $this->roles = $this->get_user_roles();
            if( !isset( $this->roles[$this->current_role] ) ) {
                $this->roles[$this->current_role] = array('name' => $this->current_role_name, 'capabilities' => array('read' => true));
            }
            if( !$this->save_roles() ) {
                $result = false;
                break;
            }
        }
        $this->restore_after_blog_switching( $old_blog );
        $this->roles = $this->get_user_roles();

        return $result;
    }

    protected function multisite_update_roles() {

        if( defined( 'BW_CAPSMAN_DEBUG' ) && BW_CAPSMAN_DEBUG ) {
            $time_shot = microtime();
        }

        if( $this->is_full_network_synch() ) {
            $result = $this->direct_network_roles_update();
        }
        else {
            $result = $this->wp_api_network_roles_update();
        }

        if( defined( 'BW_CAPSMAN_DEBUG' ) && BW_CAPSMAN_DEBUG ) {
            echo '<div class="updated fade below-h2">Roles updated for ' .( microtime() - $time_shot ) . ' milliseconds</div>';
        }

        return $result;
    }

    protected function update_roles() {
        global $wpdb;
        if( $this->multisite && is_super_admin() && $this->apply_to_all ) {
            if( !$this->multisite_update_roles() ) {
                return false;
            }
        } else {
            if( !$this->save_roles() ) {
                return false;
            }
        }
        return true;
    }

    protected function log_event( $message, $show_message = false ) {
        global $wp_version;
        $file_name = BW_CAPSMAN_PLUGIN_DIR . 'bw-capsman.log';
        $fh = fopen( $file_name, 'a' );
        $cr = "\n";
        $s = $cr . date( "d-m-Y H:i:s" ) . $cr . 'WordPress version: ' . $wp_version . ', PHP version: ' . phpversion() . ', MySQL version: ' . mysql_get_server_info() . $cr;
        fwrite( $fh, $s );
        fwrite( $fh, $message . $cr );
        fclose( $fh );
        if( $show_message ) {
            $this->show_message( 'Error! ' . esc_html__( 'Error is occur. Please check the log file.', BW_TD ) );
        }
    }

    protected function remove_caps_not_allowed_for_single_admin( $capabilities ) {
        foreach( array_keys( $capabilities ) as $cap ) {
            if( $this->block_cap_for_single_admin( $cap ) ) {
                unset( $capabilities[$cap] );
            }
        }
        return $capabilities;
    }

    protected function add_new_role() {
        global $wp_roles;
        if( !current_user_can( 'bw_capsman_create_roles' ) ) {
            return esc_html__( 'Insufficient permissions to work with Capability Manager', BW_TD );
        }
        $mess = '';
        $this->current_role = '';
        if( isset( $_POST['user_role_id'] ) && $_POST['user_role_id'] ) {
            $user_role_id = utf8_decode( $_POST['user_role_id'] );
            $valid_name = preg_match( '/[A-Za-z0-9_\-]*/', $user_role_id, $match );
            if( !$valid_name ||( $valid_name &&( $match[0] != $user_role_id ) ) ) {
                return esc_html__( 'Error: Role ID must contain latin characters, digits, hyphens or underscore only!', BW_TD );
            }
            $numeric_name = preg_match( '/[0-9]*/', $user_role_id, $match );
            if( $numeric_name &&( $match[0] == $user_role_id ) ) {
                return esc_html__( 'Error: WordPress does not support numeric Role name (ID). Add latin characters to it.', BW_TD );
            }

            if( $user_role_id ) {
                $user_role_name = isset( $_POST['user_role_name'] ) ? $_POST['user_role_name'] : false;
                if( !empty( $user_role_name ) ) {
                    $user_role_name = sanitize_text_field( $user_role_name );
                }
                else {
                    $user_role_name = $user_role_id;
                }

                if( !isset( $wp_roles ) ) {
                    $wp_roles = new WP_Roles();
                }
                if( isset( $wp_roles->roles[$user_role_id] ) ) {
                    return sprintf( 'Error! ' . esc_html__( 'Role %s exists already', BW_TD ), $user_role_id );
                }
                $user_role_id = strtolower( $user_role_id );
                $this->current_role = $user_role_id;

                $user_role_copy_from = isset( $_POST['user_role_copy_from'] ) ? $_POST['user_role_copy_from'] : false;
                if( !empty( $user_role_copy_from ) && $user_role_copy_from != 'none' && $wp_roles->is_role( $user_role_copy_from ) ) {
                    $role = $wp_roles->get_role( $user_role_copy_from );
                    $capabilities = $this->remove_caps_not_allowed_for_single_admin( $role->capabilities );
                }
                else {
                    $capabilities = array('read' => true, 'level_0' => true);
                }
                $result = add_role( $user_role_id, $user_role_name, $capabilities );
                if( !isset( $result ) || empty( $result ) ) {
                    $mess = 'Error! ' . esc_html__( 'Error is encountered during new role create operation', BW_TD );
                }
                else {
                    $mess = sprintf( esc_html__( 'Role %s is created successfully', BW_TD ), $user_role_name );
                }
            }
        }
        return $mess;
    }

    protected function rename_role() {
        global $wp_roles;
        $mess = '';
        $user_role_id = filter_input( INPUT_POST, 'user_role_id', FILTER_SANITIZE_STRING );
        if( empty( $user_role_id ) ) {
            return esc_html__( 'Error: Role ID is empty!', BW_TD );
        }
        $user_role_id = utf8_decode( $user_role_id );
        $match = array();
        $valid_name = preg_match( '/[A-Za-z0-9_\-]*/', $user_role_id, $match );
        if( !$valid_name ||( $valid_name &&( $match[0] != $user_role_id ) ) ) {
            return esc_html__( 'Error: Role ID must contain latin characters, digits, hyphens or underscore only!', BW_TD );
        }
        $numeric_name = preg_match( '/[0-9]*/', $user_role_id, $match );
        if( $numeric_name &&( $match[0] == $user_role_id ) ) {
            return esc_html__( 'Error: WordPress does not support numeric Role name (ID). Add latin characters to it.', BW_TD );
        }

        $new_role_name = filter_input( INPUT_POST, 'user_role_name', FILTER_SANITIZE_STRING );
        if( !empty( $new_role_name ) ) {
            $new_role_name = sanitize_text_field( $new_role_name );
        }
        else {
            return esc_html__( 'Error: Empty role display name is not allowed.', BW_TD );
        }

        if( !isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        if( !isset( $wp_roles->roles[$user_role_id] ) ) {
            return sprintf( 'Error! ' . esc_html__( 'Role %s does not exists', BW_TD ), $user_role_id );
        }
        $this->current_role = $user_role_id;
        $this->current_role_name = $new_role_name;

        $old_role_name = $wp_roles->roles[$user_role_id]['name'];
        $wp_roles->roles[$user_role_id]['name'] = $new_role_name;
        update_option( $wp_roles->role_key, $wp_roles->roles );
        $mess = sprintf( esc_html__( 'Role %s is renamed to %s successfully', BW_TD ), $old_role_name, $new_role_name );
        return $mess;
    }

    protected function delete_wp_roles( $roles_to_del ) {
        global $wp_roles;
        if( !current_user_can( 'bw_capsman_delete_roles' ) ) {
            return esc_html__( 'Insufficient permissions to work with Capability Manager', BW_TD );
        }
        if( !isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        $result = false;
        foreach( $roles_to_del as $role_id ) {
            if( !isset( $wp_roles->roles[$role_id] ) ) {
                $result = false;
                break;
            }
            if( $this->role_contains_caps_not_allowed_for_simple_admin( $role_id ) ) {
                continue;
            }
            unset( $wp_roles->role_objects[$role_id] );
            unset( $wp_roles->role_names[$role_id] );
            unset( $wp_roles->roles[$role_id] );
            $result = true;
        }
        if( $result ) {
            update_option( $wp_roles->role_key, $wp_roles->roles );
        }

        return $result;
    }

    protected function delete_all_unused_roles() {
        $this->roles = $this->get_user_roles();
        $roles_to_del = array_keys( $this->get_roles_can_delete() );
        $result = $this->delete_wp_roles( $roles_to_del );
        $this->roles = null;
        return $result;
    }

    protected function delete_role() {
        if( !current_user_can( 'bw_capsman_delete_roles' ) ) {
            return esc_html__( 'Insufficient permissions to work with Capability Manager', BW_TD );
        }
        $mess = '';
        if( isset( $_POST['user_role_id'] ) && $_POST['user_role_id'] ) {
            $role = $_POST['user_role_id'];
            if( $role == - 1 ) {
                $result = $this->delete_all_unused_roles();
            } else {
                $result = $this->delete_wp_roles( array($role) );
            }
            if( empty( $result ) ) {
                $mess = 'Error! ' . esc_html__( 'Error encountered during role delete operation', BW_TD );
            } elseif( $role == - 1 ) {
                $mess = sprintf( esc_html__( 'Unused roles are deleted successfully', BW_TD ), $role );
            } else {
                $mess = sprintf( esc_html__( 'Role %s is deleted successfully', BW_TD ), $role );
            }
            unset( $_POST['user_role'] );
        }
        return $mess;
    }

    protected function change_default_role() {
        global $wp_roles;
        $mess = '';
        if( !isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        if( !empty( $_POST['user_role_id'] ) ) {
            $user_role_id = $_POST['user_role_id'];
            unset( $_POST['user_role_id'] );
            $errorMessage = 'Error! ' . esc_html__( 'Error encountered during default role change operation', BW_TD );
            if( isset( $wp_roles->role_objects[$user_role_id] ) && $user_role_id !== 'administrator' ) {
                $result = update_option( 'default_role', $user_role_id );
                if( empty( $result ) ) {
                    $mess = $errorMessage;
                } else {
                    $mess = sprintf( esc_html__( 'Default role for new users is set to %s successfully', BW_TD ), $wp_roles->role_names[$user_role_id] );
                }
            } else {
                $mess = $errorMessage;
            }
        }
        return $mess;
    }

    protected function translation_data() {
        if( false ) {
            __( 'Editor', BW_TD );
            __( 'Author', BW_TD );
            __( 'Contributor', BW_TD );
            __( 'Subscriber', BW_TD );
            __( 'Switch themes', BW_TD );
            __( 'Edit themes', BW_TD );
            __( 'Activate plugins', BW_TD );
            __( 'Edit plugins', BW_TD );
            __( 'Edit users', BW_TD );
            __( 'Edit files', BW_TD );
            __( 'Manage options', BW_TD );
            __( 'Moderate comments', BW_TD );
            __( 'Manage categories', BW_TD );
            __( 'Manage links', BW_TD );
            __( 'Upload files', BW_TD );
            __( 'Import', BW_TD );
            __( 'Unfiltered html', BW_TD );
            __( 'Edit posts', BW_TD );
            __( 'Edit others posts', BW_TD );
            __( 'Edit published posts', BW_TD );
            __( 'Publish posts', BW_TD );
            __( 'Edit pages', BW_TD );
            __( 'Read', BW_TD );
            __( 'Level 10', BW_TD );
            __( 'Level 9', BW_TD );
            __( 'Level 8', BW_TD );
            __( 'Level 7', BW_TD );
            __( 'Level 6', BW_TD );
            __( 'Level 5', BW_TD );
            __( 'Level 4', BW_TD );
            __( 'Level 3', BW_TD );
            __( 'Level 2', BW_TD );
            __( 'Level 1', BW_TD );
            __( 'Level 0', BW_TD );
            __( 'Edit others pages', BW_TD );
            __( 'Edit published pages', BW_TD );
            __( 'Publish pages', BW_TD );
            __( 'Delete pages', BW_TD );
            __( 'Delete others pages', BW_TD );
            __( 'Delete published pages', BW_TD );
            __( 'Delete posts', BW_TD );
            __( 'Delete others posts', BW_TD );
            __( 'Delete published posts', BW_TD );
            __( 'Delete private posts', BW_TD );
            __( 'Edit private posts', BW_TD );
            __( 'Read private posts', BW_TD );
            __( 'Delete private pages', BW_TD );
            __( 'Edit private pages', BW_TD );
            __( 'Read private pages', BW_TD );
            __( 'Delete users', BW_TD );
            __( 'Create users', BW_TD );
            __( 'Unfiltered upload', BW_TD );
            __( 'Edit dashboard', BW_TD );
            __( 'Update plugins', BW_TD );
            __( 'Delete plugins', BW_TD );
            __( 'Install plugins', BW_TD );
            __( 'Update themes', BW_TD );
            __( 'Install themes', BW_TD );
            __( 'Update core', BW_TD );
            __( 'List users', BW_TD );
            __( 'Remove users', BW_TD );
            __( 'Add users', BW_TD );
            __( 'Promote users', BW_TD );
            __( 'Edit theme options', BW_TD );
            __( 'Delete themes', BW_TD );
            __( 'Export', BW_TD );
        }
    }

    protected function check_blog_user( $user ) {
        return true;
    }

    protected function network_update_user( $user ) {
        return true;
    }

    protected function update_user( $user ) {
        global $wp_roles;

        if( $this->multisite ) {
            if( !$this->check_blog_user( $user ) ) {
                return false;
            }
        }

        $primary_role = $_POST['primary_role'];
        if( empty( $primary_role ) || !isset( $wp_roles->roles[$primary_role] ) ) {
            $primary_role = '';
        }
        if( function_exists( 'bbp_filter_blog_editable_roles' ) ) {
            $bbp_user_role = bbp_get_user_role( $user->ID );
        }
        else {
            $bbp_user_role = '';
        }

        $edit_user_caps_mode = $this->get_edit_user_caps_mode();
        if( !$edit_user_caps_mode ) {
            $this->capabilities_to_save = $user->caps;
        }

        $user->roles = array();
        $user->remove_all_caps();

        if( !empty( $primary_role ) ) {
            $user->add_role( $primary_role );
        }

        if( !empty( $bbp_user_role ) ) {
            $user->add_role( $bbp_user_role );
        }

        foreach( $_POST as $key => $value ) {
            $result = preg_match( '/^wp_role_(.+)/', $key, $match );
            if( $result === 1 ) {
                $role = $match[1];
                if( isset( $wp_roles->roles[$role] ) ) {
                    $user->add_role( $role );
                    if( !$edit_user_caps_mode && isset( $this->capabilities_to_save[$role] ) ) {
                        unset( $this->capabilities_to_save[$role] );
                    }
                }
            }
        }
        if( count( $this->capabilities_to_save ) > 0 ) {
            foreach( $this->capabilities_to_save as $key => $value ) {
                $user->add_cap( $key );
            }
        }
        $user->update_user_level_from_caps();
        if( $this->apply_to_all ) {
            if( !$this->network_update_user( $user ) ) {
                return false;
            }
        }
        return true;
    }

    protected function add_new_capability() {
        global $wp_roles;
        if( !current_user_can( 'bw_capsman_create_capabilities' ) ) {
            return esc_html__( 'Insufficient permissions to work with Capability Manager', BW_TD );
        }
        $mess = '';
        if( isset( $_POST['capability_id'] ) && $_POST['capability_id'] ) {
            $user_capability = $_POST['capability_id'];
            $valid_name = preg_match( '/[A-Za-z0-9_\-]*/', $user_capability, $match );
            if( !$valid_name ||( $valid_name &&( $match[0] != $user_capability ) ) ) {
                return 'Error! ' . esc_html__( 'Error: Capability name must contain latin characters and digits only!', BW_TD );;
            }
            if( $user_capability ) {
                $user_capability = strtolower( $user_capability );
                if( !isset( $wp_roles ) ) {
                    $wp_roles = new WP_Roles();
                }
                $wp_roles->use_db = true;
                $administrator = $wp_roles->get_role( 'administrator' );
                if( !$administrator->has_cap( $user_capability ) ) {
                    $wp_roles->add_cap( 'administrator', $user_capability );
                    $mess = sprintf( esc_html__( 'Capability %s is added successfully', BW_TD ), $user_capability );
                }
                else {
                    $mess = sprintf( 'Error! ' . esc_html__( 'Capability %s exists already', BW_TD ), $user_capability );
                }
            }
        }
        return $mess;
    }

    protected function delete_capability() {
        global $wpdb, $wp_roles;
        if( !current_user_can( 'bw_capsman_delete_capabilities' ) ) {
            return esc_html__( 'Insufficient permissions to work with Capability Manager', BW_TD );
        }
        $mess = '';
        if( !empty( $_POST['user_capability_id'] ) ) {
            $capability_id = $_POST['user_capability_id'];
            $caps_to_remove = $this->get_caps_to_remove();
            if( !is_array( $caps_to_remove ) || count( $caps_to_remove ) == 0 || !isset( $caps_to_remove[$capability_id] ) ) {
                return sprintf( esc_html__( 'Error! You do not have permission to delete this capability: %s!', BW_TD ), $capability_id );
            }
            $usersId = $wpdb->get_col( "SELECT $wpdb->users.ID FROM $wpdb->users" );
            foreach( $usersId as $user_id ) {
                $user = get_user_to_edit( $user_id );
                if( $user->has_cap( $capability_id ) ) {
                    $user->remove_cap( $capability_id );
                }
            }
            foreach( $wp_roles->role_objects as $wp_role ) {
                if( $wp_role->has_cap( $capability_id ) ) {
                    $wp_role->remove_cap( $capability_id );
                }
            }
            $mess = sprintf( esc_html__( 'Capability %s is removed successfully', BW_TD ), $capability_id );
        }
        return $mess;
    }

    public function other_user_roles( $user ) {
        global $wp_roles;
        if( !is_array( $user->roles ) || count( $user->roles ) <= 1 ) {
            return '';
        }
        if( function_exists( 'bbp_filter_blog_editable_roles' ) ) {
            $bb_press_role = bbp_get_user_role( $user->ID );
        }
        else {
            $bb_press_role = '';
        }
        $roles = array();
        foreach( $user->roles as $key => $value ) {
            if( !empty( $bb_press_role ) && $bb_press_role === $value ) {
                continue;
            }
            $roles[] = $value;
        }
        array_shift( $roles );
        return $roles;
    }

    public function roles_text( $roles ) {
        global $wp_roles;
        if( is_array( $roles ) && count( $roles ) > 0 ) {
            $role_names = array();
            foreach( $roles as $role ) {
                $role_names[] = $wp_roles->roles[$role]['name'];
            }
            $output = implode( ', ', $role_names );
        } else {
            $output = '';
        }
        return $output;
    }

    protected function display_box_start( $title, $style = '' ) {
?>
        <div class="postbox" style="float: left; <?php echo $style; ?>">
            <h3 style="cursor:default;"><span><?php echo $title ?></span></h3>
            <div class="inside">
        <?php
    }

    protected function set_current_role() {
        if( !isset( $this->current_role ) || !$this->current_role ) {
            if( isset( $_REQUEST['user_role'] ) && $_REQUEST['user_role'] && isset( $this->roles[$_REQUEST['user_role']] ) ) {
                $this->current_role = $_REQUEST['user_role'];
            }
            else {
                $this->current_role = $this->get_last_role_id();
            }
            $this->current_role_name = $this->roles[$this->current_role]['name'];
        }
    }

    protected function show_admin_role_allowed() {
        $show_admin_role = $this->get_option( 'show_admin_role', 0 );
        $show_admin_role =(( defined( 'BW_CAPSMAN_SHOW_ADMIN_ROLE' ) && BW_CAPSMAN_SHOW_ADMIN_ROLE == 1 ) || $show_admin_role == 1 ) && $this->user_is_normal_admin();

        return $show_admin_role;
    }

    public function role_edit_prepare_html( $select_width = 200 ) {
        $caps_access_restrict_for_simple_admin = $this->get_option( 'caps_access_restrict_for_simple_admin', 0 );
        $show_admin_role = $this->show_admin_role_allowed();
        if( $select_width > 0 ) {
            $select_style = 'style="width: ' . $select_width . 'px"';
        }
        else {
            $select_style = '';
        }
        $this->role_default_html = '<select id="default_user_role" name="default_user_role" ' . $select_style . '>';
        $this->role_to_copy_html = '<select id="user_role_copy_from" name="user_role_copy_from" style="width: ' . $select_width . 'px">
            <option value="none" selected="selected">' . esc_html__( 'None', BW_TD ) . '</option>';
        $this->role_select_html = '<select id="user_role" name="user_role" onchange="bw_capsman_role_change(this.value);">';
        foreach( $this->roles as $key => $value ) {
            $selected1 = $this->option_selected( $key, $this->current_role );
            $selected2 = $this->option_selected( $key, $this->wp_default_role );
            $disabled =( $key === 'administrator' && $caps_access_restrict_for_simple_admin && !is_super_admin() ) ? 'disabled' : '';
            if( $show_admin_role || $key != 'administrator' ) {
                $translated_name = esc_html__( $value['name'], BW_TD );
                if( $translated_name === $value['name'] ) {
                    $translated_name = translate_user_role( $translated_name );
                }
                $translated_name.= ' (' . $key . ')';
                $this->role_select_html.= '<option value="' . $key . '" ' . $selected1 . ' ' . $disabled . '>' . $translated_name . '</option>';
                $this->role_default_html.= '<option value="' . $key . '" ' . $selected2 . ' ' . $disabled . '>' . $translated_name . '</option>';
                $this->role_to_copy_html.= '<option value="' . $key . '" ' . $disabled . '>' . $translated_name . '</option>';
            }
        }
        $this->role_select_html.= '</select>';
        $this->role_default_html.= '</select>';
        $this->role_to_copy_html.= '</select>';

        $roles_can_delete = $this->get_roles_can_delete();
        if( $roles_can_delete && count( $roles_can_delete ) > 0 ) {
            $this->role_delete_html = '<select id="del_user_role" name="del_user_role" width="200" style="width: 200px">';
            foreach( $roles_can_delete as $key => $value ) {
                $this->role_delete_html.= '<option value="' . $key . '">' . esc_html__( $value, BW_TD ) . '</option>';
            }
            $this->role_delete_html.= '<option value="-1" style="color: red;">' . esc_html__( 'Delete All Unused Roles', BW_TD ) . '</option>';
            $this->role_delete_html.= '</select>';
        }
        else {
            $this->role_delete_html = '';
        }

        $this->capability_remove_html = $this->get_caps_to_remove_html();
    }

    public function user_primary_role_dropdown_list( $user_roles ) {
?>
        <select name="primary_role" id="primary_role">
<?php
        $user_roles = array_intersect( array_values( $user_roles ), array_keys( get_editable_roles() ) );
        $user_primary_role = array_shift( $user_roles );

        wp_dropdown_roles( $user_primary_role );

        $selected =( empty( $user_primary_role ) ) ? 'selected="selected"' : '';
        echo '<option value="" ' . $selected . '>' . esc_html__( '&mdash; No role for this site &mdash;' ) . '</option>';
?>
        </select>
<?php
    }

    protected function user_can( $capability ) {
        if( isset( $this->user_to_edit->caps[$capability] ) ) {
            return true;
        }
        foreach( $this->user_to_edit->roles as $role ) {
            if( $role === $capability ) {
                return true;
            }
            if( !empty( $this->roles[$role]['capabilities'][$capability] ) ) {
                return true;
            }
        }
        return false;
    }

    public function user_has_capability( $user, $cap ) {
        global $wp_roles;
        if( !is_object( $user ) || empty( $user->ID ) ) {
            return false;
        }
        if( is_multisite() && is_super_admin( $user->ID ) ) {
            return true;
        }
        if( isset( $user->caps[$cap] ) ) {
            return true;
        }
        foreach( $user->roles as $role ) {
            if( $role === $cap ) {
                return true;
            }
            if( !empty( $wp_roles->roles[$role]['capabilities'][$cap] ) ) {
                return true;
            }
        }
        return false;
    }

    public function show_other_default_roles() {
        $other_default_roles = $this->get_option( 'other_default_roles', array() );
        foreach( $this->roles as $role_id => $role ) {
            if( $role_id == 'administrator' || $role_id == $this->wp_default_role ) {
                continue;
            }
            if( in_array( $role_id, $other_default_roles ) ) {
                $checked = 'checked="checked"';
            }
            else {
                $checked = '';
            }
            echo '<label for="wp_role_' . $role_id . '"><input type="checkbox"  id="wp_role_' . $role_id . '" name="wp_role_' . $role_id . '" value="' . $role_id . '"' . $checked . ' />&nbsp;' . esc_html__( $role['name'], BW_TD ) . '</label><br />';
        }
    }

    public function create_no_rights_role() {
        global $wp_roles;
        $role_id = 'no_rights';
        $role_name = 'No rights';
        if( !isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        if( isset( $wp_roles->roles[$role_name] ) ) {
            return;
        }
        add_role( $role_id, $role_name, array() );
    }

    public function get_users_without_role() {
        global $wpdb;
        $id = get_current_blog_id();
        $blog_prefix = $wpdb->get_blog_prefix( $id );
        $query = "select ID from {$wpdb->users} users
                where not exists (select user_id from {$wpdb->usermeta}
                where user_id=users.ID and meta_key='{$blog_prefix}capabilities') or
                exists (select user_id from {$wpdb->usermeta}
                where user_id=users.ID and meta_key='{$blog_prefix}capabilities' and meta_value='a:0:{}');";
        $users = $wpdb->get_col( $query );
        return $users;
    }

    public function get_current_role() {
        return $this->current_role;
    }

    protected function get_edit_user_caps_mode() {
        if( $this->multisite && is_super_admin() ) {
            return 1;
        }
        $edit_user_caps = $this->get_option( 'edit_user_caps', 1 );
        return $edit_user_caps;
    }
}
