<?php
class BW_CAPSMAN_Ajax_Processor {
    protected $lib = null;
    public function __construct( $lib ) {
        $this->lib = $lib;
    }

    protected function ajax_check_permissions() {
        if( !wp_verify_nonce( $_REQUEST['wp_nonce'], BW_TD ) ) {
            echo json_encode( array('result' => 'error', 'message' => 'BW Capsman: Wrong or expired request') );
            die;
        }
        $key_capability = $this->lib->get_key_capability();
        if( !current_user_can( $key_capability ) ) {
            echo json_encode( array('result' => 'error', 'message' => 'BW Capsman: Insufficient permissions') );
            die;
        }
    }

    protected function get_users_without_role() {
        global $wp_roles;
        $new_role = filter_input( INPUT_POST, 'new_role', FILTER_SANITIZE_STRING );
        if( empty( $new_role ) ) {
            $answer = array('result' => 'failure', 'message' => 'Provide new role');
            return $answer;
        }
        if( $new_role === 'no_rights' ) {
            $this->lib->create_no_rights_role();
        }
        if( !isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        if( !isset( $wp_roles->roles[$new_role] ) ) {
            $answer = array('result' => 'failure', 'message' => 'Selected new role does not exist');
            return $answer;
        }
        $users = $this->lib->get_users_without_role();
        $answer = array('result' => 'success', 'users' => $users, 'new_role' => $new_role, 'message' => 'success');
        return $answer;
    }

    protected function _dispatch( $action ) {
        switch( $action ){
        case 'get_users_without_role':
            $answer = $this->get_users_without_role();
            break;
        default:
            $answer = array('result' => 'error', 'message' => 'unknown action "' . $action . '"');
        }
        return $answer;
    }

    public function dispatch() {
        $this->ajax_check_permissions();
        $action = filter_input( INPUT_POST, 'sub_action', FILTER_SANITIZE_STRING );
        if( empty( $action ) ) {
            $action = filter_input( INPUT_GET, 'sub_action', FILTER_SANITIZE_STRING );
        }
        $answer = $this->_dispatch( $action );
        $json_answer = json_encode( $answer );
        echo $json_answer;
        die;
    }
}
