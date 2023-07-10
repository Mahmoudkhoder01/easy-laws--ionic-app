<?php
if (!defined('BW_CAPSMAN_PLUGIN_URL')) die();
?>
<div class="has-sidebar-content">
    <div class="postbox" style="float: left; min-width:850px;">
        <h3><?php esc_html_e('Select Role and change its capabilities:', BW_TD); ?> <?php echo $this->role_select_html; ?></h3>
        <div class="inside">
            <?php
            if ($this->caps_readable) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            $caps_access_restrict_for_simple_admin = $this->get_option('caps_access_restrict_for_simple_admin', 0);
            if (is_super_admin() || !$this->multisite || !$caps_access_restrict_for_simple_admin) {
            ?>
            <input type="checkbox" name="bw_capsman_caps_readable" id="bw_capsman_caps_readable" value="1"
            <?php echo $checked; ?> onclick="bw_capsman_turn_caps_readable(0);"/>
            <label for="bw_capsman_caps_readable"><?php esc_html_e('Show capabilities in human readable form', BW_TD); ?></label>&nbsp;&nbsp;
            <?php
                if ($this->show_deprecated_caps) {
                $checked = 'checked="checked"';
                } else {
                $checked = '';
                }
            ?>
            <input type="checkbox" name="bw_capsman_show_deprecated_caps" id="bw_capsman_show_deprecated_caps" value="1"
            <?php echo $checked; ?> onclick="bw_capsman_turn_deprecated_caps(0);"/>
            <label for="bw_capsman_show_deprecated_caps"><?php esc_html_e('Show deprecated capabilities', BW_TD); ?></label>
            <?php
            }
            if ($this->multisite && $this->active_for_network && !is_network_admin() && is_main_site( get_current_blog_id() ) && is_super_admin()) {
            $hint = esc_html__('If checked, then apply action to ALL sites of this Network');
            if ($this->apply_to_all) {
                $checked = 'checked="checked"';
                $fontColor = 'color:#FF0000;';
            } else {
                $checked = '';
                $fontColor = '';
            }
            ?>
            <div style="float: right; margin-left:10px; margin-right: 20px; <?php echo $fontColor;?>" id="bw_capsman_apply_to_all_div">
                <input type="checkbox" name="bw_capsman_apply_to_all" id="bw_capsman_apply_to_all" value="1"
                <?php echo $checked; ?> title="<?php echo $hint;?>" onclick="bw_capsman_applyToAllOnClick(this)"/>
                <label for="bw_capsman_apply_to_all" title="<?php echo $hint;?>"><?php esc_html_e('Apply to All Sites', BW_TD);?></label>
            </div>
            <?php
            }
            ?>
            <br /><br />
            <hr />
            <?php esc_html_e('Core capabilities:', BW_TD); ?>
            <div style="display:table-inline; float: right; margin-right: 12px;">
                <?php esc_html_e('Quick filter:', BW_TD); ?>&nbsp;
                <input type="text" id="quick_filter" name="quick_filter" value="" size="20" onkeyup="bw_capsman_filter_capabilities(this.value);" />
            </div>
            <table class="form-table" style="clear:none;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top;">
                        <?php $this->show_capabilities( true, true ); ?>
                    </td>
                    <td>
                        <?php $this->toolbar(!empty($this->role_delete_html), !empty($this->capability_remove_html));?>
                    </td>
                </tr>
            </table>
            <?php
            $quant = count( $this->full_capabilities ) - count( $this->get_built_in_wp_caps() );
            if ($quant>0) {
            echo '<hr />';
            esc_html_e('Custom capabilities:', BW_TD);
            ?>
            <table class="form-table" style="clear:none;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top;">
                        <?php $this->show_capabilities( false, true );  ?>
                    </td>
                    <td></td>
                </tr>
            </table>
            <?php
            }  // if ($quant>0)
            ?>
            <input type="hidden" name="object" value="role" />
        </div>
    </div>
</div>
