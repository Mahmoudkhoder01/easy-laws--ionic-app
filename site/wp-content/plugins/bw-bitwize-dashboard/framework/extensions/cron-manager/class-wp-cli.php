<?php

class BW_Crontrol_Command extends WP_CLI_Command
{

    protected $bwcron = null;

    public function __construct() {

        $this->bwcron = BW_Crontrol::init();
    }

    public function list_events( $args, $assoc_args ) {

        $defaults = array('format' => 'table',);
        $values = wp_parse_args( $assoc_args, $defaults );

        $events = $this->bwcron->get_cron_events();

        if( is_wp_error( $events ) ) {
            WP_CLI::line( WP_CLI::error_to_string( $events ) );
            die();
        }

        $events = array_map( array($this, '_map_event'), $events );

        $fields = array('hook', 'next_run', 'recurrence');

        \WP_CLI\Utils\format_items( $values['format'], $events, $fields );
    }

    public function run_event( $args, $assoc_args ) {

        $hook = $args[0];
        $result = false;
        $events = $this->bwcron->get_cron_events();

        if( is_wp_error( $events ) )WP_CLI::error( $events );

        foreach( $events as $id => $event ) {
            if( $event->hook == $hook ) {
                $result = $this->bwcron->run_cron( $event->hook, $event->sig );
                break;
            }
        }

        if( $result )WP_CLI::success( sprintf( __( 'Successfully executed the cron event %s', BW_TD ), "'" . $hook . "'" ) );
        else WP_CLI::error( sprintf( __( 'Failed to the execute the cron event %s', BW_TD ), "'" . $hook . "'" ) );
    }

    public function delete_event( $args, $assoc_args ) {

        $hook = $args[0];
        $result = false;
        $events = $this->bwcron->get_cron_events();

        if( is_wp_error( $events ) )WP_CLI::error( $events );

        foreach( $events as $id => $event ) {
            if( $event->hook == $hook ) {
                $result = $this->bwcron->delete_cron( $event->hook, $event->sig, $event->time );
                break;
            }
        }

        if( $result )WP_CLI::success( sprintf( __( 'Successfully deleted the cron event %s', BW_TD ), "'" . $hook . "'" ) );
        else WP_CLI::error( sprintf( __( 'Failed to the delete the cron event %s', BW_TD ), "'" . $hook . "'" ) );
    }

    public function list_schedules( $args, $assoc_args ) {

        $defaults = array('format' => 'table',);
        $values = wp_parse_args( $assoc_args, $defaults );

        $schedules = $this->bwcron->get_schedules();
        $schedules = array_map( array($this, '_map_schedule'), $schedules, array_keys( $schedules ) );

        $fields = array('name', 'display', 'interval');

        \WP_CLI\Utils\format_items( $values['format'], $schedules, $fields );
    }

    public function test() {

        $status = $this->bwcron->test_cron_spawn( false );

        if( is_wp_error( $status ) )WP_CLI::error( $status );
        else WP_CLI::success( __( 'WP-Cron is working as expected.', BW_TD ) );
    }

    protected function _map_event( $event ) {
        $time_format = 'Y/m/d H:i:s';
        $event->next_run = get_date_from_gmt( date( 'Y-m-d H:i:s', $event->time ), $time_format ) . " (" . $this->bwcron->time_since( time(), $event->time ) . ")";
        $event->recurrence =( $event->schedule ? $this->bwcron->interval( $event->interval ) : __( 'Non-repeating', BW_TD ) );
        return $event;
    }

    protected function _map_schedule( $schedule, $name ) {
        $schedule = (object)$schedule;
        $schedule->name = $name;
        return $schedule;
    }
}

WP_CLI::add_command( BW_TD, 'BW_Crontrol_Command' );
