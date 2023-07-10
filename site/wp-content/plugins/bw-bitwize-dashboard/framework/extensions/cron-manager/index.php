<?php
defined( 'ABSPATH' ) or die();

class BW_Crontrol
{
    protected function __construct() {
        add_action( 'init', array($this, 'action_handle_posts') );
        add_action( 'admin_menu', array($this, 'action_admin_menu') );
        register_activation_hook( BITWIZE_CORE_PLUGIN_FILE, array($this, 'action_activate') );
        add_filter( 'cron_schedules', array($this, 'filter_cron_schedules') );
        add_action( 'bwcron_cron_job', array($this, 'action_php_cron_event') );
    }

    function action_php_cron_event( $code ) {
        eval( $code );
    }

    function action_handle_posts() {
        if( isset( $_POST['new_cron'] ) ) {
            if( !current_user_can( 'manage_options' ) )die( __( 'You are not allowed to add new cron events.', BW_TD ) );
            check_admin_referer( "new-cron" );
            extract( $_POST, EXTR_PREFIX_ALL, 'in' );
            $in_args = json_decode( stripslashes( $in_args ), true );
            $this->add_cron( $in_next_run, $in_schedule, $in_hookname, $in_args );
            wp_redirect( "tools.php?page=bwcron_admin_manage_page&bwcron_message=5&bwcron_name={$in_hookname}" );
        }
        else if( isset( $_POST['new_php_cron'] ) ) {
            if( !current_user_can( 'manage_options' ) )die( __( 'You are not allowed to add new cron events.', BW_TD ) );
            check_admin_referer( "new-cron" );
            extract( $_POST, EXTR_PREFIX_ALL, 'in' );
            $args = array('code' => stripslashes( $in_hookcode ));
            $this->add_cron( $in_next_run, $in_schedule, 'bwcron_cron_job', $args );
            wp_redirect( "tools.php?page=bwcron_admin_manage_page&bwcron_message=5&bwcron_name={$in_hookname}" );
        }
        else if( isset( $_POST['edit_cron'] ) ) {
            if( !current_user_can( 'manage_options' ) )die( __( 'You are not allowed to edit cron events.', BW_TD ) );

            extract( $_POST, EXTR_PREFIX_ALL, 'in' );
            check_admin_referer( "edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run}" );
            $in_args = json_decode( stripslashes( $in_args ), true );
            $i = $this->delete_cron( $in_original_hookname, $in_original_sig, $in_original_next_run );
            $i = $this->add_cron( $in_next_run, $in_schedule, $in_hookname, $in_args );
            wp_redirect( "tools.php?page=bwcron_admin_manage_page&bwcron_message=4&bwcron_name={$in_hookname}" );
        }
        else if( isset( $_POST['edit_php_cron'] ) ) {
            if( !current_user_can( 'manage_options' ) )die( __( 'You are not allowed to edit cron events.', BW_TD ) );

            extract( $_POST, EXTR_PREFIX_ALL, 'in' );
            check_admin_referer( "edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run}" );
            $args['code'] = stripslashes( $in_hookcode );
            $args = array('code' => stripslashes( $in_hookcode ));
            $i = $this->delete_cron( $in_original_hookname, $in_original_sig, $in_original_next_run );
            $i = $this->add_cron( $in_next_run, $in_schedule, 'bwcron_cron_job', $args );
            wp_redirect( "tools.php?page=bwcron_admin_manage_page&bwcron_message=4&bwcron_name={$in_hookname}" );
        }
        else if( isset( $_POST['new_schedule'] ) ) {
            if( !current_user_can( 'manage_options' ) )die( __( 'You are not allowed to add new cron schedules.', BW_TD ) );
            check_admin_referer( "new-sched" );
            $name = $_POST['internal_name'];
            $interval = $_POST['interval'];
            $display = $_POST['display_name'];

            if( !is_numeric( $interval ) ) {
                $now = time();
                $future = strtotime( $interval, $now );
                if( $future === FALSE || $future == - 1 || $now > $future ) {
                    wp_redirect( "tools.php?page=bwcron_admin_options_page&bwcron_message=7&bwcron_name=" . urlencode( $interval ) );
                    return;
                }
                $interval = $future - $now;
            }
            else if( $interval <= 0 ) {
                wp_redirect( "tools.php?page=bwcron_admin_options_page&bwcron_message=7&bwcron_name=" . urlencode( $interval ) );
                return;
            }

            $this->add_schedule( $name, $interval, $display );
            wp_redirect( "tools.php?page=bwcron_admin_options_page&bwcron_message=3&bwcron_name=$name" );
        }
        else if( isset( $_GET['action'] ) && $_GET['action'] == 'delete-sched' ) {
            if( !current_user_can( 'manage_options' ) )die( __( 'You are not allowed to delete cron schedules.', BW_TD ) );
            $id = $_GET['id'];
            check_admin_referer( "delete-sched_{$id}" );
            $this->delete_schedule( $id );
            wp_redirect( "tools.php?page=bwcron_admin_options_page&bwcron_message=2&bwcron_name=$id" );
        }
        else if( isset( $_GET['action'] ) && $_GET['action'] == 'delete-cron' ) {
            if( !current_user_can( 'manage_options' ) )die( __( 'You are not allowed to delete cron events.', BW_TD ) );
            $id = $_GET['id'];
            $sig = $_GET['sig'];
            $next_run = $_GET['next_run'];
            check_admin_referer( "delete-cron_{$id}_{$sig}_{$next_run}" );
            if( $this->delete_cron( $id, $sig, $next_run ) ) {
                wp_redirect( "tools.php?page=bwcron_admin_manage_page&bwcron_message=6&bwcron_name=$id" );
            }
            else {
                wp_redirect( "tools.php?page=bwcron_admin_manage_page&bwcron_message=7&bwcron_name=$id" );
            };
        }
        else if( isset( $_GET['action'] ) && $_GET['action'] == 'run-cron' ) {
            if( !current_user_can( 'manage_options' ) )die( __( 'You are not allowed to run cron events.', BW_TD ) );
            $id = $_GET['id'];
            $sig = $_GET['sig'];
            check_admin_referer( "run-cron_{$id}_{$sig}" );
            if( $this->run_cron( $id, $sig ) ) {
                wp_redirect( "tools.php?page=bwcron_admin_manage_page&bwcron_message=1&bwcron_name=$id" );
            }
            else {
                wp_redirect( "tools.php?page=bwcron_admin_manage_page&bwcron_message=8&bwcron_name=$id" );
            }
        }
    }

    function run_cron( $hookname, $sig ) {
        $crons = _get_cron_array();
        foreach( $crons as $time => $cron ) {
            if( isset( $cron[$hookname][$sig] ) ) {
                $args = $cron[$hookname][$sig]['args'];
                delete_transient( 'doing_cron' );
                wp_schedule_single_event( time() - 1, $hookname, $args );
                spawn_cron();
                return true;
            }
        }
        return false;
    }

    function add_cron( $next_run, $schedule, $hookname, $args ) {
        $next_run = strtotime( $next_run );
        if( $next_run === FALSE || $next_run == - 1 )$next_run = time();
        if( !is_array( $args ) )$args = array();
        if( $schedule == '_oneoff' ) {
            return wp_schedule_single_event( $next_run, $hookname, $args ) === NULL;
        }
        else {
            return wp_schedule_event( $next_run, $schedule, $hookname, $args ) === NULL;
        }
    }

    function delete_cron( $to_delete, $sig, $next_run ) {
        $crons = _get_cron_array();
        if( isset( $crons[$next_run][$to_delete][$sig] ) ) {
            $args = $crons[$next_run][$to_delete][$sig]['args'];
            wp_unschedule_event( $next_run, $to_delete, $args );
            return true;
        }
        return false;
    }

    function add_schedule( $name, $interval, $display ) {
        $old_scheds = get_option( 'bwcron_schedules', array() );
        $old_scheds[$name] = array('interval' => $interval, 'display' => $display);
        update_option( 'bwcron_schedules', $old_scheds );
    }

    function delete_schedule( $name ) {
        $scheds = get_option( 'bwcron_schedules', array() );
        unset( $scheds[$name] );
        update_option( 'bwcron_schedules', $scheds );
    }

    function action_activate() {
        $extra_scheds = array('twicedaily' => array('interval' => 43200, 'display' => __( 'Twice Daily', BW_TD )));
        add_option( 'bwcron_schedules', $extra_scheds );

        if( _get_cron_array() === FALSE ) {
            _set_cron_array( array() );
        }
    }

    function action_admin_menu() {
        $page = add_management_page( 'Cron Schedules', 'Cron Schedules', 'can_bitwize', 'bwcron_admin_options_page', array($this, 'admin_options_page') );
        $page = add_management_page( 'Cron Manager', 'Cron Manager', 'can_bitwize', 'bwcron_admin_manage_page', array($this, 'admin_manage_page') );
    }

    function filter_cron_schedules( $scheds ) {
        $new_scheds = get_option( 'bwcron_schedules', array() );
        return array_merge( $new_scheds, $scheds );
    }

    function admin_options_page() {
        $schedules = $this->get_schedules();
        $custom_schedules = get_option( 'bwcron_schedules', array() );
        $custom_keys = array_keys( $custom_schedules );

        if( isset( $_GET['bwcron_message'] ) ) {
            $messages = array('2' => __( 'Successfully deleted the cron schedule %s', BW_TD ), '3' => __( 'Successfully added the cron schedule %s', BW_TD ), '7' => __( 'Cron schedule not added because there was a problem parsing %s', BW_TD ));
            $hook = stripslashes( $_GET['bwcron_name'] );
            $msg = sprintf( $messages[$_GET['bwcron_message']], '<strong>' . esc_html( $hook ) . '</strong>' );

            echo "<div id=\"message\" class=\"updated fade\"><p>$msg</p></div>";
        }
?>
        <div class="wrap">
    <?php
        screen_icon(); ?>
        <h2><?php
        _e( "Cron Schedules", "bwcron" ); ?></h2>
        <p><?php
        _e( 'Cron schedules are the time intervals that are available to Sell&Sell and it\'s extensions to schedule events. You can only delete cron schedules that you have created with Cron Manager.', BW_TD ); ?></p>
        <div id="ajax-response"></div>
        <table class="widefat">
        <thead>
            <tr>
                <th><?php
        _e( 'Name', BW_TD ); ?></th>
                <th><?php
        _e( 'Interval', BW_TD ); ?></th>
                <th><?php
        _e( 'Display Name', BW_TD ); ?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if( empty( $schedules ) ) {
?>
            <tr colspan="4"><td><?php
            _e( 'You currently have no cron schedules. Add one below!', BW_TD ) ?></td></tr>
            <?php
        }
        else {
            $class = "";
            foreach( $schedules as $name => $data ) {
                echo "<tr id=\"sched-$name\" class=\"$class\">";
                echo "<td>$name</td>";
                echo "<td>{$data['interval']} (" . $this->interval( $data['interval'] ) . ")</td>";
                echo "<td>{$data['display']}</td>";
                if( in_array( $name, $custom_keys ) ) {
                    echo "<td><a href='" . wp_nonce_url( "tools.php?page=bwcron_admin_options_page&amp;action=delete-sched&amp;id=$name", 'delete-sched_' . $name ) . "' class='delete'>" . __( 'Delete' ) . "</a></td>";
                }
                else {
                    echo "<td>&nbsp;</td>\n";
                }
                echo "</tr>";
                $class = empty( $class ) ? "alternate" : "";
            }
        }
?>
        </tbody>
        </table>
        </div>
        <div class="wrap narrow">
      <?php
        screen_icon(); ?>
            <h2><?php
        _e( 'Add new cron schedule', BW_TD ); ?></h2>
            <p><?php
        _e( 'Adding a new cron schedule will allow you to schedule events that re-occur at the given interval.', BW_TD ); ?></p>
            <form method="post" action="tools.php?page=bwcron_admin_options_page">
                <table width="100%" cellspacing="2" cellpadding="5" class="editform form-table">
                <tbody>
                <tr>
                  <th width="33%" valign="top" scope="row"><label for="cron_internal_name"><?php
        _e( 'Internal name', BW_TD ); ?>:</label></th>
                  <td width="67%"><input type="text" size="40" value="" id="cron_internal_name" name="internal_name"/></td>
                </tr>
                <tr>
                  <th width="33%" valign="top" scope="row"><label for="cron_interval"><?php
        _e( 'Interval (seconds)', BW_TD ); ?>:</label></th>
                  <td width="67%"><input type="text" size="40" value="" id="cron_interval" name="interval"/></td>
                </tr>
                <tr>
                  <th width="33%" valign="top" scope="row"><label for="cron_display_name"><?php
        _e( 'Display name', BW_TD ); ?>:</label></th>
                  <td width="67%"><input type="text" size="40" value="" id="cron_display_name" name="display_name"/></td>
                </tr>
              </tbody></table>
                <p class="submit"><input id="schedadd-submit" type="submit" class="button-primary" value="<?php
        _e( 'Add Cron Schedule &raquo;', BW_TD ); ?>" name="new_schedule"/></p>
                <?php
        wp_nonce_field( 'new-sched' ) ?>
            </form>
        </div>
        <?php
    }

    function get_schedules() {
        $schedules = wp_get_schedules();
        uasort( $schedules, create_function( '$a,$b', 'return $a["interval"]-$b["interval"];' ) );
        return $schedules;
    }

    function schedules_dropdown( $current = false ) {
        $schedules = $this->get_schedules();
?>
        <select class="postform" name="schedule">
        <option <?php
        selected( $current, '_oneoff' ) ?> value="_oneoff"><?php
        _e( 'Non-repeating', BW_TD ) ?></option>
        <?php
        foreach( $schedules as $sched_name => $sched_data ) { ?>
            <option <?php
            selected( $current, $sched_name ) ?> value="<?php
            echo $sched_name
?>">
                <?php
            echo $sched_data['display'] ?> (<?php
            echo $this->interval( $sched_data['interval'] ) ?>)
            </option>
        <?php
        } ?>
        </select>
        <?php
    }

    function test_cron_spawn( $cache = true ) {

        if( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON )return true;

        $cached_status = get_transient( 'wp-cron-test-ok' );

        if( $cache and $cached_status )return true;

        $doing_wp_cron = sprintf( '%.22F', microtime( true ) );

        $cron_request = apply_filters( 'cron_request', array('url' => site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron ), 'key' => $doing_wp_cron, 'args' => array('timeout' => 3, 'blocking' => true, 'sslverify' => apply_filters( 'https_local_ssl_verify', true ))) );

        $cron_request['args']['blocking'] = true;

        $result = wp_remote_post( $cron_request['url'], $cron_request['args'] );

        if( is_wp_error( $result ) ) {
            return $result;
        }
        else {
            set_transient( 'wp-cron-test-ok', 1, 3600 );
            return true;
        }
    }

    function show_cron_status() {

        $status = $this->test_cron_spawn();

        if( is_wp_error( $status ) ) {
?>
      <div id="cron-status-error" class="error">
        <p><?php
            printf( __( 'There was a problem spawning a call to the Cron system on your site. This means Cron jobs on your site may not work. The problem was: %s', BW_TD ), '<br><strong>' . esc_html( $status->get_error_message() ) . '</strong>' ); ?></p>
      </div>
      <?php
        }
    }

    function show_cron_form( $is_php, $existing ) {
        if( $is_php ) {
            $helper_text = sprintf( __( 'Cron events trigger actions in your code. Using the form below, you can enter the schedule of the action, as well as the PHP code for the action itself. Alternatively, the schedule can be specified from  the code for the action in a file on on your server using <a href="%1$s">this form</a>.', BW_TD ), admin_url( 'tools.php?page=bwcron_admin_manage_page&action=new-cron#bwcron_form' ) );
            $link = ' (<a href="tools.php?page=bwcron_admin_manage_page#bwcron_form">' . __( 'Add new event', BW_TD ) . '</a>)';
        }
        else {
            $helper_text = sprintf( __( 'Cron events trigger actions in your code. A cron event added using the form below needs a corresponding action hook somewhere in code, perhaps the %1$s file in your theme. It is also possible to create your action hook using <a href="%2$s">this form</a>.', BW_TD ), '<code>functions.php</code>', admin_url( 'tools.php?page=bwcron_admin_manage_page&action=new-php-cron#bwcron_form' ) );
            $link = ' (<a href="tools.php?page=bwcron_admin_manage_page&amp;action=new-php-cron#bwcron_form">' . __( 'Add new PHP event', BW_TD ) . '</a>)';
        }
        if( is_array( $existing ) ) {
            $other_fields = wp_nonce_field( "edit-cron_{$existing['hookname']}_{$existing['sig']}_{$existing['next_run']}", "_wpnonce", true, false );
            $other_fields.= '<input name="original_hookname" type="hidden" value="' . $existing['hookname'] . '" />';
            $other_fields.= '<input name="original_sig" type="hidden" value="' . $existing['sig'] . '" />';
            $other_fields.= '<input name="original_next_run" type="hidden" value="' . $existing['next_run'] . '" />';
            $existing['args'] = $is_php ? $existing['args']['code'] : json_encode( $existing['args'] );
            $existing['next_run'] = date( 'Y-m-d H:i:s', $existing['next_run'] );
            $action = $is_php ? 'edit_php_cron' : 'edit_cron';
            $button = $is_php ? __( 'Modify PHP Cron Event', BW_TD ) : __( 'Modify Cron Event', BW_TD );
            $link = false;
        }
        else {
            $other_fields = wp_nonce_field( "new-cron", "_wpnonce", true, false );
            $existing = array('hookname' => '', 'hookcode' => '', 'args' => '', 'next_run' => 'now', 'schedule' => false);
            $action = $is_php ? 'new_php_cron' : 'new_cron';
            $button = $is_php ? __( 'Add PHP Cron Event', BW_TD ) : __( 'Add Cron Event', BW_TD );
        }
?>
        <div id="bwcron_form" class="wrap narrow">
      <?php
        screen_icon(); ?>
            <h2><?php
        echo $button;
        if( $link )echo '<span style="font-size:xx-small">' . $link . '</span>'; ?></h2>
            <p><?php
        echo $helper_text
?></p>
            <form method="post">
                <?php
        echo $other_fields
?>
                <table width="100%" cellspacing="2" cellpadding="5" class="editform form-table"><tbody>
                    <?php
        if( $is_php ): ?>
                    <tr>
                      <th width="33%" valign="top" scope="row"><label for="hookcode"><?php
            _e( 'Hook code', BW_TD ); ?>:</label></th>
                      <td width="67%"><textarea style="width:95%" class="code" rows="5" name="hookcode"><?php
            echo esc_textarea( $existing['args'] ); ?></textarea></td>
                    </tr>
                <?php
        else: ?>
                    <tr>
                      <th width="33%" valign="top" scope="row"><label for="hookname"><?php
            _e( 'Hook name', BW_TD ); ?>:</label></th>
                      <td width="67%"><input type="text" size="40" id="hookname" name="hookname" value="<?php
            echo esc_attr( $existing['hookname'] ); ?>"/></td>
                    </tr>
                    <tr>
                      <th width="33%" valign="top" scope="row"><label for="args"><?php
            _e( 'Arguments', BW_TD ); ?>:</label><br /><span style="font-size:xx-small"><?php
            _e( 'e.g., [], [25], ["asdf"], or ["i","want",25,"cakes"]', BW_TD ) ?></span></th>
                      <td width="67%"><input type="text" size="40" id="args" name="args" value="<?php
            echo esc_textarea( $existing['args'] ); ?>"/></td>
                    </tr>
                <?php
        endif; ?>
                <tr>
                  <th width="33%" valign="top" scope="row"><label for="next_run"><?php
        _e( 'Next run (UTC)', BW_TD ); ?>:</label><br /><span style="font-size:xx-small"><?php
        _e( 'e.g., "now", "tomorrow", "+2 days", or "25-02-2014 15:27:09"', BW_TD ) ?></th>
                  <td width="67%"><input type="text" size="40" id="next_run" name="next_run" value="<?php
        echo esc_attr( $existing['next_run'] ); ?>"/></td>
                </tr><tr>
                  <th valign="top" scope="row"><label for="schedule"><?php
        _e( 'Event schedule', BW_TD ); ?>:</label></th>
                  <td>
                      <?php
        $this->schedules_dropdown( $existing['schedule'] ) ?>
                    </td>
                </tr>
              </tbody></table>
                <p class="submit"><input type="submit" class="button-primary" value="<?php
        echo $button
?> &raquo;" name="<?php
        echo $action
?>"/></p>
            </form>
        </div>
        <?php
    }

    function get_cron_events() {

        $crons = _get_cron_array();
        $events = array();

        if( empty( $crons ) ) {
            return new WP_Error( 'no_events', __( 'You currently have no scheduled cron events.', BW_TD ) );
        }

        foreach( $crons as $time => $cron ) {
            foreach( $cron as $hook => $dings ) {
                foreach( $dings as $sig => $data ) {
                    $events["$hook-$sig"] = (object)array('hook' => $hook, 'time' => $time, 'sig' => $sig, 'args' => $data['args'], 'schedule' => $data['schedule'], 'interval' => isset( $data['interval'] ) ? $data['interval'] : null,);
                }
            }
        }

        return $events;
    }

    function admin_manage_page() {
        if( isset( $_GET['bwcron_message'] ) ) {
            $messages = array('1' => __( 'Successfully executed the cron event %s', BW_TD ), '4' => __( 'Successfully edited the cron event %s', BW_TD ), '5' => __( 'Successfully created the cron event %s', BW_TD ), '6' => __( 'Successfully deleted the cron event %s', BW_TD ), '7' => __( 'Failed to the delete the cron event %s', BW_TD ), '8' => __( 'Failed to the execute the cron event %s', 'bwcron ' ));
            $hook = stripslashes( $_GET['bwcron_name'] );
            $msg = sprintf( $messages[$_GET['bwcron_message']], '<strong>' . esc_html( $hook ) . '</strong>' );

            echo "<div id=\"message\" class=\"updated fade\"><p>$msg</p></div>";
        }
        $events = $this->get_cron_events();
        $doing_edit =( isset( $_GET['action'] ) && $_GET['action'] == 'edit-cron' ) ? $_GET['id'] : false;
        $time_format = 'Y-m-d H:i:s';

        $tzstring = get_option( 'timezone_string' );
        $current_offset = get_option( 'gmt_offset' );

        if( $current_offset >= 0 )$current_offset = '+' . $current_offset;

        if( '' === $tzstring )$tz = sprintf( 'UTC%s', $current_offset );
        else $tz = sprintf( '%s (UTC%s)', str_replace( '_', ' ', $tzstring ), $current_offset );

        $this->show_cron_status();
?>
        <div class="wrap">
    <?php
        screen_icon(); ?>
        <h2><?php
        _e( 'Cron Events', BW_TD ); ?></h2>
        <p></p>
        <table class="widefat">
        <thead>
            <tr>
                <th><?php
        _e( 'Hook Name', BW_TD ); ?></th>
                <th><?php
        _e( 'Arguments', BW_TD ); ?></th>
                <th><?php
        _e( 'Next Run', BW_TD ); ?></th>
                <th><?php
        _e( 'Recurrence', BW_TD ); ?></th>
                <th colspan="3">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if( is_wp_error( $events ) ) {
?>
            <tr><td colspan="7"><?php
            echo $events->get_error_message(); ?></td></tr>
            <?php
        }
        else {
            $class = "";
            foreach( $events as $id => $event ) {

                if( $doing_edit && $doing_edit == $event->hook && $event->time == $_GET['next_run'] && $event->sig == $_GET['sig'] ) {
                    $doing_edit = array('hookname' => $event->hook, 'next_run' => $event->time, 'schedule' =>( $event->schedule ? $event->schedule : '_oneoff' ), 'sig' => $event->sig, 'args' => $event->args);
                }

                $args = empty( $event->args ) ? '<em>' . __( 'None', BW_TD ) . '</em>' : json_encode( $event->args );

                echo "<tr id=\"cron-{$id}\" class=\"{$class}\">";
                echo "<td>" .( $event->hook == 'bwcron_cron_job' ? '<em>' . __( 'PHP Cron', BW_TD ) . '</em>' : $event->hook ) . "</td>";
                echo "<td>" .( $event->hook == 'bwcron_cron_job' ? '<em>' . __( 'PHP Code', BW_TD ) . '</em>' : $args ) . "</td>";
                echo "<td>" . get_date_from_gmt( date( 'Y-m-d H:i:s', $event->time ), $time_format ) . " (" . $this->time_since( time(), $event->time ) . ")</td>";
                echo "<td>" .( $event->schedule ? $this->interval( $event->interval ) : __( 'Non-repeating', BW_TD ) ) . "</td>";
                echo "<td><a class='view' href='tools.php?page=bwcron_admin_manage_page&amp;action=edit-cron&amp;id={$event->hook}&amp;sig={$event->sig}&amp;next_run={$event->time}#bwcron_form'>" . __( 'Edit', BW_TD ) . "</a></td>";
                echo "<td><a class='view' href='" . wp_nonce_url( "tools.php?page=bwcron_admin_manage_page&amp;action=run-cron&amp;id={$event->hook}&amp;sig={$event->sig}", "run-cron_{$event->hook}_{$event->sig}" ) . "'>" . __( 'Run Now', BW_TD ) . "</a></td>";
                echo "<td><a class='delete' href='" . wp_nonce_url( "tools.php?page=bwcron_admin_manage_page&amp;action=delete-cron&amp;id={$event->hook}&amp;sig={$event->sig}&amp;next_run={$event->time}", "delete-cron_{$event->hook}_{$event->sig}_{$event->time}" ) . "'>" . __( 'Delete', BW_TD ) . "</a></td>";
                echo "</tr>";
                $class = empty( $class ) ? "alternate" : "";
            }
        }
?>
        </tbody>
        </table>

        <div class="tablenav">
          <p class="description">
            <?php
        printf( __( 'Local timezone is %s', BW_TD ), '<code>' . $tz . '</code>' ); ?>
            <span id="utc-time"><?php
        printf( __( 'UTC time is %s', BW_TD ), '<code>' . date_i18n( $time_format, false, true ) . '</code>' ); ?></span>
            <span id="local-time"><?php
        printf( __( 'Local time is %s', BW_TD ), '<code>' . date_i18n( $time_format ) . '</code>' ); ?></span>
          </p>
        </div>

        </div>
        <?php
        if( is_array( $doing_edit ) ) {
            $this->show_cron_form( $doing_edit['hookname'] == 'bwcron_cron_job', $doing_edit );
        }
        else {
            $this->show_cron_form(( isset( $_GET['action'] ) and $_GET['action'] == 'new-php-cron' ), false );
        }
    }

    function time_since( $older_date, $newer_date ) {
        return $this->interval( $newer_date - $older_date );
    }

    function interval( $since ) {

        $chunks = array(array(60 * 60 * 24 * 365, _n_noop( '%s year', '%s years', BW_TD )), array(60 * 60 * 24 * 30, _n_noop( '%s month', '%s months', BW_TD )), array(60 * 60 * 24 * 7, _n_noop( '%s week', '%s weeks', BW_TD )), array(60 * 60 * 24, _n_noop( '%s day', '%s days', BW_TD )), array(60 * 60, _n_noop( '%s hour', '%s hours', BW_TD )), array(60, _n_noop( '%s minute', '%s minutes', BW_TD )), array(1, _n_noop( '%s second', '%s seconds', BW_TD )),);

        if( $since <= 0 ) {
            return __( 'now', BW_TD );
        }

        for( $i = 0, $j = count( $chunks ); $i < $j; $i++ ) {
            $seconds = $chunks[$i][0];
            $name = $chunks[$i][1];

            if(( $count = floor( $since / $seconds ) ) != 0 ) {
                break;
            }
        }

        $output = sprintf( _n( $name[0], $name[1], $count, BW_TD ), $count );

        if( $i + 1 < $j ) {
            $seconds2 = $chunks[$i + 1][0];
            $name2 = $chunks[$i + 1][1];

            if(( $count2 = floor(( $since -( $seconds * $count ) ) / $seconds2 ) ) != 0 ) {

                $output.= ' ' . sprintf( _n( $name2[0], $name2[1], $count2, BW_TD ), $count2 );
            }
        }

        return $output;
    }

    public static function init() {

        static $instance = null;

        if( !$instance )$instance = new BW_Crontrol;

        return $instance;
    }
}

if( defined( 'WP_CLI' ) and WP_CLI and is_readable( $wp_cli = dirname( __FILE__ ) . '/class-wp-cli.php' ) )include_once $wp_cli;

BW_Crontrol::init();
