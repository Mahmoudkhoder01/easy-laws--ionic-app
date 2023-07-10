<?php
if (!defined('BW_CAPSMAN_PLUGIN_URL')) die();
$edit_user_caps_mode = $this->get_edit_user_caps_mode();
?>
<div class="has-sidebar-content">
    <?php
    if (!is_multisite() || current_user_can('manage_network_users')) {
    $anchor_start = '<a href="' . wp_nonce_url("user-edit.php?user_id={$this->user_to_edit->ID}",
            "bw_capsman_user_{$this->user_to_edit->ID}") .'" >';
    $anchor_end = '</a>';
    } else {
    $anchor_start = '';
    $anchor_end = '';
    }
    $user_info = ' <span style="font-weight: bold;">'.$anchor_start. $this->user_to_edit->user_login;
    if ($this->user_to_edit->display_name!==$this->user_to_edit->user_login) {
        $user_info .= ' ('.$this->user_to_edit->display_name.')';
    }
    $user_info .= $anchor_end.'</span>';
    if (is_multisite() && is_super_admin($this->user_to_edit->ID)) {
    $user_info .= '  <span style="font-weight: bold; color:red;">'. esc_html__('Network Super Admin', BW_TD) .'</span>';
    }
    $this->display_box_start(esc_html__('Change capabilities for user', BW_TD).$user_info, 'min-width:1100px;');
    ?>
    <table cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr>
            <td>&nbsp;</td>
            <td style="padding-left: 10px; padding-bottom: 5px;">
                <?php
                    $caps_access_restrict_for_simple_admin = $this->get_option('caps_access_restrict_for_simple_admin', 0);
                    if (is_super_admin() || !$this->multisite || !$caps_access_restrict_for_simple_admin) {
                        if ($this->caps_readable) {
                            $checked = 'checked="checked"';
                        } else {
                            $checked = '';
                        }
                ?>
                <input type="checkbox" name="bw_capsman_caps_readable" id="bw_capsman_caps_readable" value="1"
                <?php echo $checked; ?> onclick="bw_capsman_turn_caps_readable(<?php echo $this->user_to_edit->ID; ?>);"  />
                <label for="bw_capsman_caps_readable"><?php esc_html_e('Show capabilities in human readable form', BW_TD); ?></label>&nbsp;&nbsp;&nbsp;
                <?php
                    if ($this->show_deprecated_caps) {
                    $checked = 'checked="checked"';
                    } else {
                    $checked = '';
                    }
                ?>
                <input type="checkbox" name="bw_capsman_show_deprecated_caps" id="bw_capsman_show_deprecated_caps" value="1"
                <?php echo $checked; ?> onclick="bw_capsman_turn_deprecated_caps(<?php echo $this->user_to_edit->ID; ?>);"/>
                <label for="bw_capsman_show_deprecated_caps"><?php esc_html_e('Show deprecated capabilities', BW_TD); ?></label>
                <?php
                    }
                ?>
            </td>
        </tr>
        <tr>
            <td class="bw-capsman-user-roles">
                <div style="margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Primary Role:', BW_TD); ?></div>
                <?php
                $show_admin_role = $this->show_admin_role_allowed();
                // output primary role selection dropdown list
                $this->user_primary_role_dropdown_list($this->user_to_edit->roles);
                $values = array_values($this->user_to_edit->roles);
                $primary_role = array_shift($values);  // get 1st element from roles array
                if (function_exists('bbp_filter_blog_editable_roles') ) {  // bbPress plugin is active
                ?>
                <div style="margin-top: 5px;margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('bbPress Role:', BW_TD); ?></div>
                <?php
                // Get the roles
                $dynamic_roles = bbp_get_dynamic_roles();
                $bbp_user_role = bbp_get_user_role($this->user_to_edit->ID);
                if (!empty($bbp_user_role)) {
                echo $dynamic_roles[$bbp_user_role]['name'];
                }
                }
                ?>
                <div style="margin-top: 5px;margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Other Roles:', BW_TD); ?></div>
                <?php
                foreach ($this->roles as $role_id => $role) {
                if ( ($show_admin_role || $role_id!='administrator') && ($role_id!==$primary_role) ) {
                if ( $this->user_can( $role_id ) ) {
                $checked = 'checked="checked"';
                } else {
                $checked = '';
                }
                echo '<label for="wp_role_' . $role_id .'"><input type="checkbox"	id="wp_role_' . $role_id .
                            '" name="wp_role_' . $role_id . '" value="' . $role_id . '"' . $checked .' />&nbsp;' .
                        esc_html__($role['name'], BW_TD) . '</label><br />';
                }
                }
                ?>
            </td>
            <td style="padding-left: 5px; padding-top: 5px; border-top: 1px solid #ccc;">
                <span style="font-weight: bold;"><?php esc_html_e('Core capabilities:', BW_TD); ?></span>
                <div style="display:table-inline; float: right; margin-right: 12px;">
                    <?php esc_html_e('Quick filter:', BW_TD); ?>&nbsp;
                    <input type="text" id="quick_filter" name="quick_filter" value="" size="20" onkeyup="bw_capsman_filter_capabilities(this.value);" />
                </div>
                <table class="form-table" style="clear:none;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top;">
                            <?php $this->show_capabilities( true, false, $edit_user_caps_mode ); ?>
                        </td>
                        <td>
                            <?php $this->toolbar();?>
                        </td>
                    </tr>
                </table>
                <?php
                $quant = count( $this->full_capabilities ) - count( $this->get_built_in_wp_caps() );
                if ($quant>0) {
                    echo '<hr />';
                ?>
                <span style="font-weight: bold;"><?php esc_html_e('Custom capabilities:', BW_TD); ?></span>
                <table class="form-table" style="clear:none;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top;">
                            <?php $this->show_capabilities( false, false, $edit_user_caps_mode ); ?>
                        </td>
                    </tr>
                </table>
                <?php
                }  // if ($quant>0)
                ?>
            </td>
        </tr>
    </table>
    <input type="hidden" name="object" value="user" />
    <input type="hidden" name="user_id" value="<?php echo $this->user_to_edit->ID; ?>" />
</div></div></div>
