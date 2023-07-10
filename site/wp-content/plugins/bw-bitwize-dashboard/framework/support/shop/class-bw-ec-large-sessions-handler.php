<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class BW_WC_Large_Session_Handler extends WC_Session {
	private $_cookie;
	private $_session_expiring;
	private $_session_expiration;
	private $_has_cookie = false;
	private $_table;

	public function __construct() {
		global $wpdb;
		$this->_cookie = 'wp_woocommerce_session_' . COOKIEHASH;
		$this->_table = $wpdb->prefix . BW_WC_LARGE_SESSIONS_TABLE_NAME;

		if ( $cookie = $this->get_session_cookie() ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];
			$this->_has_cookie         = true;

			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				$this->update_session_timestamp( $this->_customer_id, $this->_session_expiration );
			}
		} else {
			$this->set_session_expiration();
			$this->_customer_id = $this->generate_customer_id();
		}

		$this->_data = $this->get_session_data();

		add_action( 'woocommerce_set_cart_cookies', array( $this, 'set_customer_session_cookie' ), 10 );
		add_action( 'woocommerce_cleanup_sessions', array( $this, 'cleanup_sessions' ), 10 );
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );
		if ( ! is_user_logged_in() ) {
			add_action( 'woocommerce_thankyou', array( $this, 'destroy_session' ) );
			add_filter( 'nonce_user_logged_out', array( $this, 'nonce_user_logged_out' ) );
		}
	}

	public function set_customer_session_cookie( $set ) {
		if ( $set ) {
			$to_hash           = $this->_customer_id . $this->_session_expiration;
			$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
			$cookie_value      = $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;
			$this->_has_cookie = true;

			wc_setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, apply_filters( 'wc_session_use_secure_cookie', false ) );
		}
	}


	public function has_session() {
		return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in();
	}

	public function set_session_expiration() {
		$this->_session_expiring    = time() + intval( apply_filters( 'wc_session_expiring', 60 * 60 * 47 ) );
		$this->_session_expiration  = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 48 ) );
	}

	public function generate_customer_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		} else {
			require_once( ABSPATH . 'wp-includes/class-phpass.php');
			$hasher = new PasswordHash( 8, false );
			return md5( $hasher->get_random_bytes( 32 ) );
		}
	}

	public function get_session_cookie() {
		if ( empty( $_COOKIE[ $this->_cookie ] ) ) {
			return false;
		}

		list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $_COOKIE[ $this->_cookie ] );

		$to_hash = $customer_id . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( $hash != $cookie_hash ) {
			return false;
		}

		return array( $customer_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	public function get_session_data() {
		if ( $this->has_session() ) {
			return (array) $this->get_session( $this->_customer_id, array() );
		}
		return array();
	}

	public function save_data() {
		if ( $this->_dirty && $this->has_session() ) {
			global $wpdb;
			$wpdb->replace(
				$this->_table,
				array(
					'session_key' => $this->_customer_id,
					'session_value' => maybe_serialize( $this->_data ),
					'session_expiry' => $this->_session_expiration
				),
				array(
					'%s',
					'%s',
					'%d'
				)
			);

			wp_cache_delete( $this->_customer_id, BW_WC_LARGE_SESSIONS_CACHE_GROUP );
			$expire = $this->_session_expiration - time();
			wp_cache_add( $this->_customer_id, $this->_data,  BW_WC_LARGE_SESSIONS_CACHE_GROUP, $expire );
			$this->_dirty = false;
		}
	}

	public function destroy_session() {
		wc_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, apply_filters( 'wc_session_use_secure_cookie', false ) );

		$this->delete_session( $this->_customer_id );
		wc_empty_cart();

		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = $this->generate_customer_id();
	}

	public function nonce_user_logged_out( $uid ) {
		return $this->has_session() && $this->_customer_id ? $this->_customer_id : $uid;
	}

	public function cleanup_sessions() {
		global $wpdb;

		if ( ! defined( 'WP_SETUP_CONFIG' ) && ! defined( 'WP_INSTALLING' ) ) {
			$now                = time();
			$expired_sessions   = array();

			$wc_expired_sessions = $wpdb->get_results( $wpdb->prepare( "
				SELECT session_id, session_key FROM $this->_table WHERE session_expiry < %d",
				$now
			) );

			foreach ( $wc_expired_sessions as $session ) {
				wp_cache_delete( $session->session_key, BW_WC_LARGE_SESSIONS_CACHE_GROUP );
				$expired_sessions[] = $session->session_id;  			}

			if ( ! empty( $expired_sessions ) ) {
				$expired_sessions_chunked = array_chunk( $expired_sessions, 100 );
				foreach ( $expired_sessions_chunked as $chunk ) {
					$session_ids = implode( ',', $chunk );
					$wpdb->query( $wpdb->prepare( "DELETE FROM $this->_table WHERE session_id IN ( %s )", $session_ids ) );
				}
			}
		}
	}

	function get_session( $customer_id, $default = false ) {
		global $wpdb;

		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return false;
		}

		$value = wp_cache_get( $customer_id, BW_WC_LARGE_SESSIONS_CACHE_GROUP );

		if ( false === $value ) {
			$value = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT session_value FROM $this->_table WHERE session_key = %s",
					$customer_id
				)
			);

			if ( is_null( $value ) ) {
				$value = $default;
			}

			$expire = $this->_session_expiration - time();
			wp_cache_add( $customer_id, $value, BW_WC_LARGE_SESSIONS_CACHE_GROUP, $expire );
		}

		return maybe_unserialize( $value );
	}

	function delete_session( $customer_id ) {
		global $wpdb;

		wp_cache_delete( $customer_id, BW_WC_LARGE_SESSIONS_CACHE_GROUP );

		$wpdb->delete(
			$this->_table,
			array('session_key' => $customer_id),
			array('%s')
		);
	}

	public function update_session_timestamp( $customer_id, $timestamp ) {
		global $wpdb;
		$wpdb->update(
			$this->_table,
			array('session_expiry' => $timestamp),
			array('session_key' => $customer_id),
			array('%d','%s')
		);
	}
}
