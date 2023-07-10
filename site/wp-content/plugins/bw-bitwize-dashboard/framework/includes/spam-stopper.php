<?php
if ( !defined( 'ABSPATH' ) ) die;

class BW_Stop_Spammers
{
	function __construct() {
		add_action( 'pre_comment_on_post', array( &$this, 'stopforumspam_check_query' ), 1 );
		add_action( 'woocommerce_new_customer_data', array( &$this, 'stop_forum_spam_check' ), 9999, 1 );
	}

	function sfs_get_real_ip_address() {
		$ip = null;
		if (!empty($_SERVER[ 'HTTP_CLIENT_IP' ] ) ) {
			$ip = $_SERVER[ 'HTTP_CLIENT_IP' ];
		} elseif (!empty($_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
			$ip = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
		} elseif (!empty($_SERVER[ 'HTTP_FORWARDED_FOR' ] ) ) {
			$ip = $_SERVER[ 'HTTP_FORWARDED_FOR' ];
		} else if ( !empty( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
			$ip = $_SERVER[ 'REMOTE_ADDR' ];
		} else {
			$ip = $_SERVER[ 'REMOTE_ADDR' ];
		}
		return $ip;
	}

	function stopforumspam_check_query( $post_id = null ) {
		global $current_user;

		if ( empty( $post_id ) )
			return;

		if ( is_user_logged_in() ) {
			if ( empty( $current_user ) )
				$current_user = get_currentuserinfo();
			$data['user_email'] = $current_user->data->user_email;
		} else {
			$data['user_email'] = isset( $_POST['email'] ) ? $_POST['email'] : '';
		}

		if ( empty( $data ) )
			return;

		$res = $this->stop_forum_spam_check( $data );
	}


	function stop_forum_spam_check( $data = null ) {
		if ( function_exists( 'is_checkout' ) )
			if ( is_checkout() )
				return $data;

		if ( defined ( 'DOING_AJAX' ) && isset( $_REQUEST['action'] ) && ( 'woocommerce-checkout' == $_REQUEST['action'] || 'wc-checkout' == $_REQUEST['action'] ) )
			return $data;

		if ( empty( $data['user_email'] ) )
			return;

		$user_ip = $this->sfs_get_real_ip_address();
		$link = 'http://www.stopforumspam.com/api?email=' . urlencode( $data['user_email'] ) . '&f=json&ip=' . urlencode( $user_ip );
		$response = wp_remote_get( $link, array( 'timeout' => 10, 'ssl_verify' => false ) );

		if ( is_wp_error( $response ) || empty( $response['body'] ) )
			return $data;

		try {
			$json = json_decode( $response['body'] );
		} catch( Exception $ex ) {
			return $data;
		}

		$json_email = $json->email->appears;
		$confidence = floatval( $json->email->confidence );
		$json_ip = $json->ip->appears;
		// known spammer? empty the array
		if ( ( 1 == $json_email ) || ( 1 == $json_ip ) || $confidence > 50 ) {
			return array();
		}
		return $data;
	}
}

new BW_Stop_Spammers();
