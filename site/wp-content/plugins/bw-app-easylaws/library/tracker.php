<?php

class App_Tracker{

    static $hooks;

    public static function track_hooks(){
        $filter = current_filter();
        if (!empty($GLOBALS['wp_filter'][$filter])) {
            foreach ($GLOBALS['wp_filter'][$filter] as $priority => $tag_hooks) {
                foreach ($tag_hooks as $hook) {
                    if (is_array($hook['function'])) {
                        if (is_object($hook['function'][0])) {
                            $func = get_class($hook['function'][0]) . '->' . $hook['function'][1];
                        } elseif (is_string($hook['function'][0])) {
                            $func = $hook['function'][0] . '::' . $hook['function'][1];
                        }
                    } elseif ($hook['function'] instanceof Closure) {
                        $func = 'a closure';
                    } elseif (is_string($hook['function'])) {
                        $func = $hook['function'];
                    }
                    self::$hooks[] = 'On hook <b>"' . $filter . '"</b> run <b>' . $func . '</b> at priority ' . $priority;
                }
            }
        }
    }
}

add_action('all', array('App_Tracker', 'track_hooks'));

// add_action('shutdown', function () {
//     echo implode('<br />', App_Tracker::$hooks);
// }, 9999);
