
<div class="wrap">
    <h2><?php esc_html_e('Capability Manager - Options', BW_TD); ?></h2>

    <div id="bw_capsman_tabs" style="clear: left;">
        <ul>
            <li><a href="#bw_capsman_tabs-1"><?php esc_html_e('General', BW_TD);?></a></li>

            <li><a href="#bw_capsman_tabs-2"><?php esc_html_e('Additional Modules', BW_TD); ?></a></li>
            <li><a href="#bw_capsman_tabs-3"><?php esc_html_e('Default Roles', BW_TD); ?></a></li>
<?php if ( $this->lib->multisite && (is_super_admin()) ) { ?>
            <li><a href="#bw_capsman_tabs-4"><?php esc_html_e('Multisite', BW_TD); ?></a></li>
<?php } ?>
        </ul>
    <div id="bw_capsman_tabs-1">
    <div id="bw-capsman-settings-form">
        <form method="post" action="<?php echo $link; ?>?page=settings-bw-capsman" >
            <table id="bw_capsman_settings">

                <tr>
                    <td>
                        <input type="checkbox" name="show_admin_role" id="show_admin_role" value="1"
                        <?php echo ($show_admin_role == 1) ? 'checked="checked"' : ''; ?>
                               <?php echo defined('BW_CAPSMAN_SHOW_ADMIN_ROLE') ? 'disabled="disabled" title="Predefined by \'BW_CAPSMAN_SHOW_ADMIN_ROLE\' constant at wp-config.php"' : ''; ?> />
                        <label for="show_admin_role"><?php esc_html_e('Show Administrator role at Capability Manager', BW_TD); ?></label></td>
                    <td>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="caps_readable" id="caps_readable" value="1"
                               <?php echo ($caps_readable == 1) ? 'checked="checked"' : ''; ?> />
                        <label for="caps_readable"><?php esc_html_e('Show capabilities in the human readable form', BW_TD); ?></label></td>
                    <td>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="show_deprecated_caps" id="show_deprecated_caps" value="1"
                               <?php echo ($show_deprecated_caps == 1) ? 'checked="checked"' : ''; ?> />
                        <label for="show_deprecated_caps"><?php esc_html_e('Show deprecated capabilities', BW_TD); ?></label></td>
                    <td>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="edit_user_caps" id="edit_user_caps" value="1"
                               <?php echo ($edit_user_caps == 1) ? 'checked="checked"' : ''; ?> />
                        <label for="edit_user_caps"><?php esc_html_e('Edit user capabilities', BW_TD); ?></label></td>
                    <td>
                    </td>
                </tr>

<?php do_action('bw_capsman_settings_show1'); ?>
            </table>
            <?php wp_nonce_field(BW_TD); ?>
            <input type="hidden" name="bw_capsman_tab_idx" value="0" />
            <p class="submit">
                <input type="submit" class="button-primary" name="bw_capsman_settings_update" value="<?php _e('Save', BW_TD) ?>" />
            </p>

        </form>
    </div>
    </div> <!-- bw_capsman_tabs-1 -->

    <div id="bw_capsman_tabs-2">
        <form name="bw_capsman_additional_modules" method="post" action="<?php echo $link; ?>?page=settings-bw-capsman" >
            <table id="bw_capsman_addons">
<?php if (!$this->lib->multisite) { ?>
                <tr>
                    <td>
                        <input type="checkbox" name="count_users_without_role" id="count_users_without_role" value="1"
                               <?php echo ($count_users_without_role == 1) ? 'checked="checked"' : ''; ?> />
                        <label for="count_users_without_role"><?php esc_html_e('Count users without role', BW_TD); ?></label></td>
                    <td>
                    </td>
                </tr>
<?php
    }

    do_action('bw_capsman_settings_show2');
?>
            </table>
            <?php wp_nonce_field(BW_TD); ?>
            <input type="hidden" name="bw_capsman_tab_idx" value="1" />
            <p class="submit">
                <input type="submit" class="button-primary" name="bw_capsman_addons_settings_update" value="<?php _e('Save', BW_TD) ?>" />

        </form>
    </div>

    <div id="bw_capsman_tabs-3">
        <form name="bw_capsman_default_roles" method="post" action="<?php echo $link; ?>?page=settings-bw-capsman" >
<?php
    if (!$this->lib->multisite) {
        esc_html_e('Primary default role: ', BW_TD);
        echo $this->lib->role_default_html;
?>
        <hr>
<?php
    }
?>
        <?php esc_html_e('Other default roles for new registered user: ', BW_TD); ?>
        <div id="other_default_roles">
            <?php $this->lib->show_other_default_roles(); ?>
        </div>
<?php
    if ($this->lib->multisite) {
        echo '<p>'. esc_html__('Note for multisite environment: take into account that other default roles should exist at the site, in order to be assigned to the new registered users.', BW_TD) .'</p>';
    }
?>
        <hr>
        <?php wp_nonce_field(BW_TD); ?>
            <input type="hidden" name="bw_capsman_tab_idx" value="2" />
            <p class="submit">
                <input type="submit" class="button-primary" name="bw_capsman_default_roles_update" value="<?php _e('Save', BW_TD) ?>" />
            </p>
        </form>
    </div> <!-- bw_capsman_tabs-3 -->

<?php
    if ( $this->lib->multisite && is_super_admin()) {
?>
    <div id="bw_capsman_tabs-4">
        <div id="bw-capsman-settings-form-ms">
            <form name="bw_capsman_settings_ms" method="post" action="<?php echo $link; ?>?page=settings-bw-capsman" >
                <table id="bw_capsman_settings_ms">
<?php
    if (is_super_admin()) {
?>
                    <tr>
                         <td>
                             <input type="checkbox" name="allow_edit_users_to_not_super_admin" id="allow_edit_users_to_not_super_admin" value="1"
                                  <?php echo ($allow_edit_users_to_not_super_admin == 1) ? 'checked="checked"' : ''; ?> />
                             <label for="allow_edit_users_to_not_super_admin"><?php esc_html_e('Allow non super administrators to create, edit, and delete users', BW_TD); ?></label>
                         </td>
                         <td>
                         </td>
                    </tr>
<?php
    }
                    do_action('bw_capsman_settings_ms_show');
?>
                </table>
                <?php wp_nonce_field(BW_TD); ?>
                <input type="hidden" name="bw_capsman_tab_idx" value="3" />
            <p class="submit">
                <input type="submit" class="button-primary" name="bw_capsman_settings_ms_update" value="<?php _e('Save', BW_TD) ?>" />
            </p>
            </form>
        </div>   <!-- bw-capsman-settings-form-ms -->
    </div>  <!-- bw_capsman_tabs-4 -->
<?php
    }
?>
    </div> <!-- bw_capsman_tabs -->
</div>
<script>
    jQuery(document).ready(function() {
        jQuery('#bw_capsman_tabs').tabs();
        <?php if ($bw_capsman_tab_idx>0) { ?>
            jQuery("#bw_capsman_tabs").tabs("option", "active", <?php echo $bw_capsman_tab_idx; ?>);
        <?php } ?>
    });
</script>
