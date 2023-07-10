<?php

if(!defined('ABSPATH')) return;

class App_User {
	public $data;
	public $ID = 0;
	var $filter = null;

	public function __construct( $id = 0, $name = '' ) {
		if ( $id instanceof App_User ) {
			$this->init( $id->data );
			return;
		} elseif ( is_object( $id ) ) {
			$this->init( $id );
			return;
		}

		if ( ! empty( $id ) && ! is_numeric( $id ) ) {
			$name = $id;
			$id = 0;
		}

		if ( $id ) {
			$data = self::get_data_by( 'id', $id );
		} else {
			$data = self::get_data_by( 'login', $name );
		}

		if ( $data ) {
			$this->init( $data );
		} else {
			$this->data = new stdClass;
		}
	}

	public function init( $data, $blog_id = '' ) {
		$this->data = $data;
		$this->ID = (int) $data->ID;
	}

	public function exists() {
		return ! empty( $this->ID );
	}

	public function to_array() {
		return get_object_vars( $this->data );
	}

	public function get( $key ) {
		return $this->__get( $key );
	}

	public function has_prop( $key ) {
		return $this->__isset( $key );
	}


	public static function get_data_by( $field, $value ) {

		if ( 'id' == $field ) {
			if ( ! is_numeric( $value ) )
				return false;
			$value = intval( $value );
			if ( $value < 1 )
				return false;
		} else {
			$value = trim( $value );
		}

		if ( !$value )
			return false;

		switch ( $field ) {
			case 'id':
				$user_id = $value;
				$db_field = 'ID';
				break;
			// case '_code':
			// 	$user_id = wp_cache_get($value, 'app_userlocalcode');
			// 	$db_field = '_code';
			// 	break;
			case 'email':
				$user_id = wp_cache_get($value, 'app_useremail');
				$db_field = 'user_email';
				break;
			case 'login':
				$value = sanitize_user( $value );
				$user_id = wp_cache_get($value, 'app_userlogins');
				$db_field = 'user_login';
				break;
			default:
				return false;
		}

		if ( false !== $user_id ) {
			if ( $user = wp_cache_get( $user_id, 'app_users' ) )
				return $user;
		}

		if ( !$user = DB()->get_row( DB()->prepare(
			"SELECT * FROM ".DB()->prefix."app_users WHERE $db_field = %s", $value
		) ) )
			return false;

		$user->account = DB()->get_row( DB()->prepare(
			"SELECT * FROM ".DB()->prefix."app_user_accounts WHERE ID = %d", $user->account_id
		) );

		if(!empty($user->account->package)){
			$user->package = DB()->get_row( DB()->prepare(
				"SELECT * FROM ".DB()->prefix."app_packages WHERE ID = %d", $user->account->package
			) );
		} else {
			$user->package = '';
		}

		self::update_user_caches( $user );

		return $user;
	}

	public static function update_user_caches($user) {
		wp_cache_add($user->ID, $user, 'app_users');
		// wp_cache_add($user->_code, $user, 'app_userlocalcode');
		wp_cache_add($user->user_login, $user->ID, 'app_userlogins');
		wp_cache_add($user->user_email, $user->ID, 'app_useremail');
	}

	public static function clean_user_cache( $user ) {
		if ( is_numeric( $user ) )
			$user = new App_User( $user );

		if ( ! $user->exists() )
			return;

		wp_cache_delete( $user->ID, 'app_users' );
		// wp_cache_delete( $user->_id, $user, 'app_userlocalid');
		// wp_cache_delete( $user->_code, $user, 'app_userlocalcode');
		wp_cache_delete( $user->user_login, 'app_userlogins' );
		wp_cache_delete( $user->user_email, 'app_useremail' );
		wp_cache_delete( $user->user_mobile, 'app_usermobile' );
	}

	public function __isset( $key ) {
		if ( 'id' == $key ) {
			$key = 'ID';
		}

		if ( isset( $this->data->$key ) )
			return true;

		return metadata_exists( 'user', $this->ID, $key );
	}

	public function __get( $key ) {
		if ( 'id' == $key ) {
			return $this->ID;
		}

		if ( isset( $this->data->$key ) ) {
			$value = $this->data->$key;
		} else {
			$value = AU()->get_user_meta( $this->ID, $key, true );
		}

		if ( $this->filter ) {
			$value = sanitize_user_field( $key, $value, $this->ID, $this->filter );
		}

		return $value;
	}

	public function __set( $key, $value ) {
		if ( 'id' == $key ) {
			$this->ID = $value;
			return;
		}

		$this->data->$key = $value;
	}
}

class App_User_Query {

	public $query_vars = array();
	private $results;
	private $total_users = 0;
	public $meta_query = false;
	private $compat_fields = array( 'results', 'total_users' );

	// SQL clauses
	public $table;
	public $query_fields;
	public $query_from;
	public $query_where;
	public $query_join;
	public $query_orderby;
	public $query_limit;

	public function __construct( $query = null ) {
		if ( ! empty( $query ) ) {
			$this->prepare_query( $query );
			$this->query();
		}
	}

	public function prepare_query( $query = array() ) {
		$this->table = DB()->prefix.'app_users';

		if ( ! empty( $query ) ) {
			$this->query_limit = null;
			$this->query_vars = wp_parse_args( $query, array(
				'role' => '',
				'account_id' => '',
				'meta_key' => '',
				'meta_value' => '',
				'meta_compare' => '',
				'include' => array(),
				'exclude' => array(),
				'orderby' => 'user_login',
				'order' => 'ASC',
				'offset' => '',
				'number' => '',
				'where' => '',
				'join' => '',
				'has_birthday' => '',
				'count_total' => false,
				'fields' => 'all',
			) );
		}

		$qv =& $this->query_vars;

		if ( is_array( $qv['fields'] ) ) {
			$qv['fields'] = array_unique( $qv['fields'] );

			$this->query_fields = array();
			foreach ( $qv['fields'] as $field ) {
				$field = 'ID' === $field ? 'ID' : sanitize_key( $field );
				$this->query_fields[] = "$field";
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} elseif ( 'all' == $qv['fields'] ) {
			$this->query_fields = "*";
		} else {
			$this->query_fields = "ID";
		}

		$this->query_from = "FROM $this->table";
		$this->query_where = "WHERE 1=1";

		if ( ! empty( $qv['include'] )) {
			$ids = implode( ',', wp_parse_id_list( $qv['include'] ) );
			$this->query_where .= " AND ID IN ($ids)";
		} elseif ( ! empty( $qv['exclude'] ) ) {
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ID NOT IN ($ids)";
		}

		if ( isset( $qv['has_birthday'] ) && $qv['has_birthday'] == 1) {
			$this->query_where .= " AND (date_format(user_dob, '%m-%d') = date_format(now(), '%m-%d'))";
		}

		$role = '';
		if ( isset( $qv['role'] ) ) {
			$role = trim( $qv['role'] );
			$this->query_where .= " AND user_role='$role'";
		}

		if ( isset( $qv['account_id'] ) ) {
			$account_id = absint( trim( $qv['account_id'] ) );
			$this->query_where .= " AND account_id=$account_id";
		}

		if ( ! empty( $qv['where'] )) {
			$where = $qv['where'];
			$this->query_where .= " AND ($where)";
		}

		$this->query_join = '';
		if( ! empty( $qv['join'])){
			$this->query_join = $qv['join'];
		}

		// sorting
		$qv['order'] = isset( $qv['order'] ) ? strtoupper( $qv['order'] ) : '';

		if ( empty( $qv['orderby'] ) ) {
			$ordersby = array( 'user_login' => $order );
		} else if ( is_array( $qv['orderby'] ) ) {
			$ordersby = $qv['orderby'];
		} else {
			// 'orderby' values may be a comma- or space-separated list.
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}

		$orderby_array = array();
		foreach ( $ordersby as $_key => $_value ) {
			if ( ! $_value ) {
				continue;
			}

			if ( is_int( $_key ) ) {
				$_orderby = $_value;
				$_order = $qv['order'];
			} else {
				$_orderby = $_key;
				$_order = $_value;
			}

			$orderby_array[] = $_orderby . ' ' . $_order;
		}

		// If no valid clauses were found, order by user_login.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = "user_login $order";
		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		// limit
		if ( isset( $qv['number'] ) && $qv['number'] ) {
			if ( isset( $qv['offset'] ) && $qv['offset'] )
				$this->query_limit = DB()->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			else
				$this->query_limit = DB()->prepare("LIMIT %d", $qv['number']);
		}

		do_action_ref_array( 'pre_app_user_query', array( &$this ) );
	}

	public function query() {

		$qv =& $this->query_vars;

		$query = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_join $this->query_orderby $this->query_limit";

		if ( is_array( $qv['fields'] ) || 'all' == $qv['fields'] ) {
			$this->results = DB()->get_results( $query );
		} else {
			$this->results = DB()->get_col( $query );
		}

		if ( isset( $qv['count_total'] ) && $qv['count_total'] )
			// $this->total_users = DB()->get_var( apply_filters( 'found_users_query', 'SELECT FOUND_ROWS()' ) );
			$this->total_users = DB()->get_var("SELECT COUNT(ID) $this->query_from $this->query_where");

		if ( !$this->results )
			return;

		if ( 'all' == $qv['fields'] ) {
			foreach ( $this->results as $key => $user ) {
				$this->results[ $key ] = new App_User( $user, '' );
			}
		}
	}

	public function get( $query_var ) {
		if ( isset( $this->query_vars[$query_var] ) )
			return $this->query_vars[$query_var];

		return null;
	}

	public function set( $query_var, $value ) {
		$this->query_vars[$query_var] = $value;
	}

	protected function get_search_sql( $string, $cols, $wild = false ) {

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		$like = $leading_wild . DB()->esc_like( $string ) . $trailing_wild;

		foreach ( $cols as $col ) {
			if ( 'ID' == $col ) {
				$searches[] = DB()->prepare( "$col = %s", $string );
			} else {
				$searches[] = DB()->prepare( "$col LIKE %s", $like );
			}
		}

		return ' AND (' . implode(' OR ', $searches) . ')';
	}

	public function get_results() {
		return $this->results;
	}

	public function get_total() {
		return $this->total_users;
	}

	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	public function __get( $name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return $this->$name;
		}
	}

	public function __set( $name, $value ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return $this->$name = $value;
		}
	}

	public function __isset( $name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return isset( $this->$name );
		}
	}

	public function __unset( $name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			unset( $this->$name );
		}
	}

	public function __call( $name, $arguments ) {
		if ( 'get_search_sql' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}
}

class API_Users {

	public $debug = true;
	public $table;

    protected static $_instance = null;
	public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct(){
    	$this->table = DB()->prefix.'app_users';

    	// if(!function_exists('wp_hash')) require( ABSPATH . WPINC . '/pluggable.php' );

    	$this->setup_cookie_constants();

    	add_filter( 'app_authenticate', array($this, 'authenticate_username_password'),  20, 3 );
		// add_filter( 'app_authenticate', array($this, 'authenticate_spam_check'), 99 );
		add_filter( 'app_determine_current_user', array($this, 'validate_auth_cookie' ));
		add_filter( 'app_determine_current_user', array($this, 'validate_logged_in_cookie'), 20 );
    }

	function set_current_user($id, $name = '') {
		global $app_current_user;

		if ( isset( $app_current_user ) && ( $app_current_user instanceof App_User ) && ( $id == $app_current_user->ID ) )
			return $app_current_user;

		$app_current_user = new App_User( $id, $name );

		do_action( 'app_set_current_user' );

		return $app_current_user;
	}

	function get_current_user() {
		global $app_current_user;
		$this->get_currentuserinfo();
		return $app_current_user;
	}

	function get_currentuserinfo() {
		global $app_current_user;

		if ( ! empty( $app_current_user ) ) {
			if ( $app_current_user instanceof App_User )
				return;

			if ( is_object( $app_current_user ) && isset( $app_current_user->ID ) ) {
				$cur_id = $app_current_user->ID;
				$app_current_user = null;
				$this->set_current_user( $cur_id );
				return;
			}

			$app_current_user = null;
			$this->set_current_user( 0 );
			return false;
		}

		if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
			$this->set_current_user( 0 );
			return false;
		}

		$user_id = apply_filters( 'app_determine_current_user', false );
		if ( ! $user_id ) {
			$this->set_current_user( 0 );
			return false;
		}

		$this->set_current_user( $user_id );
	}

    function get_current_user_id() {
		$user = $this->get_current_user();
		return ( isset( $user->ID ) ? (int) $user->ID : 0 );
	}

    function get_user_by( $field, $value ) {
		$userdata = App_User::get_data_by( $field, $value );

		if ( !$userdata )
			return false;

		$user = new App_User;
		$user->init( $userdata );

		return $user;
	}

	function get_users( $args = array() ) {

		$args = wp_parse_args( $args );
		$args['count_total'] = false;

		$user_search = new App_User_Query($args);

		return (array) $user_search->get_results();
	}

	function get_userdata( $user_id ) {
		return $this->get_user_by( 'id', $user_id );
	}

	public function core_user_keys(){
		$o = array();
		$q = DB()->get_results("SHOW COLUMNS FROM {$this->table}");
		if(!empty($q)){
			foreach($q as $k){
				if($k->Field == 'ID') continue;
				$o[] = $k->Field;
			}
		}
		return $o;

    }

	function insert_user( $userdata ) {

		if ( $userdata instanceof stdClass ) {
			$userdata = get_object_vars( $userdata );
		} elseif ( $userdata instanceof App_User ) {
			$userdata = $userdata->to_array();
		}

		// Hash the password
		$user_pass = $this->hash_password( $userdata['user_pass'] );
		$sanitized_user_login = sanitize_user( $userdata['user_login'], true );
		$pre_user_login = apply_filters( 'pre_user_login', $sanitized_user_login );
		$user_login = trim( $pre_user_login );
		if ( empty( $user_login ) ) {
			return new WP_Error('empty_user_login', __('Cannot create a user with an empty login name.') );
		}
		if ( $this->username_exists( $user_login ) ) {
			return new WP_Error( 'existing_user_login', __( 'Sorry, that username already exists!' ) );
		}
		if ( ! empty( $userdata['user_nicename'] ) ) {
			$user_nicename = sanitize_user( $userdata['user_nicename'], true );
		} else {
			$user_nicename = $user_login;
		}
		$user_nicename = sanitize_title( $user_nicename );
		$raw_user_email = empty( $userdata['user_email'] ) ? '' : $userdata['user_email'];
		$user_email = apply_filters( 'pre_user_email', $raw_user_email );
		if ( ( ( ! empty( $old_user_data ) && $user_email !== $old_user_data->user_email ) )
			&& ! defined( 'WP_IMPORTING' )
			&& $this->email_exists( $user_email )
		) {
			return new WP_Error( 'existing_user_email', __( 'Sorry, that email address is already used!' ) );
		}
		if ( empty( $userdata['display_name'] ) ) {
			$display_name = trim( sprintf( '%1$s %2$s', $first_name, $last_name ) );
		} else {
			$display_name = $userdata['display_name'];
		}

		$user_role = empty( $userdata['user_role'] ) ? apply_filters('app_default_role', 'user') : $userdata['user_role'];

		$userdata['user_role'] = $user_role;
		$userdata['user_pass'] = $user_pass;
		$userdata['user_login'] = $user_login;
		$userdata['user_nicename'] = $user_nicename;
		$userdata['user_email'] = $user_email;
		$userdata['display_name'] = $display_name;
		$userdata['user_activation_key'] = uniqid();
		$userdata['user_created'] = gmdate( 'Y-m-d H:i:s' );
		$userdata['user_edited'] = gmdate( 'Y-m-d H:i:s' );

		foreach($userdata as $k => $v){
			if ( isset($k) && !in_array($k, $this->core_user_keys() ) ) {
				unset($userdata[$k]);
			}
		}
		$userdata = wp_unslash($userdata);

		DB()->insert( $this->table, $userdata );
		$user_id = (int) DB()->insert_id;

		$user = new App_User( $user_id );

		wp_cache_delete( $user_id, 'app_users' );
		wp_cache_delete( $user_login, 'app_userlogins' );

		do_action( 'app_user_register', $user_id );

		return $user_id;
	}

	function update_user($userdata) {

		if ( $userdata instanceof stdClass ) {
			$userdata = get_object_vars( $userdata );
		} elseif ( $userdata instanceof App_User ) {
			$userdata = $userdata->to_array();
		}

		$ID = isset( $userdata['ID'] ) ? (int) $userdata['ID'] : 0;
		if ( ! $ID ) {
			return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
		}

		// First, get all of the original fields
		$user_obj = $this->get_userdata( $ID );
		if ( ! $user_obj ) {
			return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
		}

		$user = $user_obj->to_array();
		unset($user['account']);
		unset($user['package']);
		// Escape data pulled from DB.
		$user = add_magic_quotes( $user );

		// If password is changing, hash it now.
		if ( ! empty($userdata['user_pass']) ) {
			$plaintext_pass = $userdata['user_pass'];
			$userdata['user_pass'] = $this->hash_password($userdata['user_pass']);
		}

		wp_cache_delete($user[ 'user_email' ], 'app_useremail');

		// Merge old and new fields with new fields overwriting old ones.
		$userdata['user_edited'] = gmdate( 'Y-m-d H:i:s' );
		$userdata = array_merge($user, $userdata);

		unset($userdata['account']);
		unset($userdata['package']);
		// AH()->print_r($userdata);

		$result = DB()->update($this->table, $userdata, array('ID' => $ID));
		if( is_wp_error( $result ) ) {
			return $result->get_error_message();
		}

		wp_cache_delete( $ID, 'app_users' );
		wp_cache_delete( $user_obj->user_login, 'app_userlogins' );

		do_action( 'app_profile_update', $ID, $user_obj );
		// $user_id = $this->insert_user($userdata);

		// Update the cookies if the password changed.
		$app_current_user = $this->get_current_user();
		if ( $app_current_user->ID == $ID ) {
			if ( isset($plaintext_pass) ) {
				$this->clear_auth_cookie();
				$logged_in_cookie    = $this->parse_auth_cookie( '', 'logged_in' );

				$default_cookie_life = apply_filters( 'auth_cookie_expiration', ( 2 * DAY_IN_SECONDS ), $ID, false );
				$remember            = ( ( $logged_in_cookie['expiration'] - time() ) > $default_cookie_life );

				$this->set_auth_cookie( $ID, $remember );
			}
		}

		return $ID;
	}

	function delete_user($id){
		if ( ! $id ) {
			return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
		}
		DB()->query("DELETE FROM $this->table WHERE ID=$id");
		DB()->query("DELETE FROM ".DB()->prefix."app_users_meta WHERE `user_id`=$id");
		return $id;
	}

	function get_user_field($id, $field) {
		if ( ! $id ) {
			return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
		}

		$r = DB()->get_var("SELECT $field FROM {$this->table} WHERE ID=$id");
		if($r){
			return $r;
		}
		return false;
	}

	function update_user_field($id, $field, $val) {
		if ( ! $id ) {
			return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
		}

		DB()->update($this->table, array($field => $val), array('ID' => $id));
		return $id;
	}

    function validate_username( $username ) {
		$sanitized = sanitize_user( $username, true );
		$valid = ( $sanitized == $username );
		return apply_filters( 'app_validate_username', $valid, $username );
	}

    function username_exists( $username ) {
		if ( $user = $this->get_user_by( 'login', $username ) ) {
			return $user->ID;
		}
		return false;
	}

    function email_exists( $email ) {
		if ( $user = $this->get_user_by( 'email', $email) ) {
			return $user->ID;
		}
		return false;
	}

	function is_user_logged_in() {
		$user = $this->get_current_user();

		if ( ! $user->exists() )
			return false;

		return true;
	}

	function authenticate($username, $password) {
		$username = sanitize_user($username);
		$password = trim($password);
		$user = apply_filters( 'app_authenticate', null, $username, $password );

		if ( $user == null ) {
			$user = new WP_Error('app_authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.'));
		}

		$ignore_codes = array('empty_username', 'empty_password');

		if (is_wp_error($user) && !in_array($user->get_error_code(), $ignore_codes) ) {
			do_action( 'app_login_failed', $username );
		}

		return $user;
	}

	function hash_password($password) {
		global $hasher;
		if ( empty($hasher) ) {
			require_once( ABSPATH . WPINC . '/class-phpass.php');
			$hasher = new PasswordHash(8, true);
		}
		return $hasher->HashPassword( trim( $password ) );
	}

	function check_password($password, $hash, $user_id = '') {
		global $hasher;

		if ( strlen($hash) <= 32 ) {
			$check = hash_equals( $hash, md5( $password ) );
			if ( $check && $user_id ) {
				// Rehash using new hash.
				$this->set_password($password, $user_id);
				$hash = $this->hash_password($password);
			}

			return apply_filters( 'app_check_password', $check, $password, $hash, $user_id );
		}

		if ( empty($hasher) ) {
			require_once( ABSPATH . WPINC . '/class-phpass.php');
			$hasher = new PasswordHash(8, true);
		}

		$check = $hasher->CheckPassword($password, $hash);

		return apply_filters( 'app_check_password', $check, $password, $hash, $user_id );
	}

	function set_password( $password, $user_id ) {

		$hash = $this->hash_password( $password );
		DB()->update($this->table, array('user_pass' => $hash, 'user_activation_key' => ''), array('ID' => $user_id) );

		wp_cache_delete($user_id, 'app_users');
	}

	function logout() {
		$this->destroy_current_session();
		$this->clear_auth_cookie();
		do_action( 'app_logout' );
	}

	function signon( $credentials = array(), $secure_cookie = '' ) {
		if ( empty($credentials) ) {
			if ( ! empty($_POST['log']) )
				$credentials['user_login'] = $_POST['log'];
			if ( ! empty($_POST['pwd']) )
				$credentials['user_password'] = $_POST['pwd'];
			if ( ! empty($_POST['rememberme']) )
				$credentials['remember'] = $_POST['rememberme'];
		}

		if ( !empty($credentials['remember']) )
			$credentials['remember'] = true;
		else
			$credentials['remember'] = false;

		do_action_ref_array( 'app_do_authenticate', array( &$credentials['user_login'], &$credentials['user_password'] ) );

		if ( '' === $secure_cookie )
			$secure_cookie = is_ssl();

		$secure_cookie = apply_filters( 'app_secure_signon_cookie', $secure_cookie, $credentials );

		global $app_auth_secure_cookie; // XXX ugly hack to pass this to wp_authenticate_cookie
		$app_auth_secure_cookie = $secure_cookie;

		add_filter('app_authenticate', array($this, 'authenticate_cookie'), 30, 3);

		$user = $this->authenticate($credentials['user_login'], $credentials['user_password']);

		if ( is_wp_error($user) ) {
			if ( $user->get_error_codes() == array('empty_username', 'empty_password') ) {
				$user = new WP_Error('', '');
			}

			return $user;
		}

		$this->set_auth_cookie($user->ID, $credentials['remember'], $secure_cookie);

		do_action( 'app_login', $user->user_login, $user );
		return $user;
	}

	function authenticate_username_password($user, $username, $password) {
		if ( $user instanceof App_User ) {
			return $user;
		}

		if ( empty($username) || empty($password) ) {
			if ( is_wp_error( $user ) )
				return $user;

			$error = new WP_Error();

			if ( empty($username) )
				$error->add('empty_username', __('<strong>ERROR</strong>: The username field is empty.'));

			if ( empty($password) )
				$error->add('empty_password', __('<strong>ERROR</strong>: The password field is empty.'));

			return $error;
		}

		$user = $this->get_user_by('login', $username);

		if ( !$user )
			return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Invalid username.' ) );

		$user = apply_filters( 'app_authenticate_user', $user, $password );
		if ( is_wp_error($user) )
			return $user;

		if ( !$this->check_password($password, $user->user_pass, $user->ID) )
			return new WP_Error( 'incorrect_password', sprintf( __( '<strong>ERROR</strong>: The password you entered for the username <strong>%1$s</strong> is incorrect' ),
			$username ) );

		return $user;
	}

	function validate_logged_in_cookie( $user_id ) {
		if ( $user_id ) {
			return $user_id;
		}
		if(isset($_COOKIE[APP_LOGGED_IN_COOKIE])){
			return $this->validate_auth_cookie( $_COOKIE[APP_LOGGED_IN_COOKIE], 'logged_in' );
		}
		return false;
	}

	function authenticate_cookie($user, $username, $password) {
		if ( $user instanceof App_User ) {
			return $user;
		}

		if ( empty($username) && empty($password) ) {
			$user_id = $this->validate_auth_cookie();
			if ( $user_id )
				return new App_User($user_id);

			global $app_auth_secure_cookie;

			if ( $app_auth_secure_cookie )
				$auth_cookie = APP_SECURE_AUTH_COOKIE;
			else
				$auth_cookie = APP_AUTH_COOKIE;

			if ( !empty($_COOKIE[$auth_cookie]) )
				return new WP_Error('expired_session', __('Please log in again.'));
		}

		return $user;
	}

	function nonce_tick() {
		$nonce_life = apply_filters( 'app_nonce_life', DAY_IN_SECONDS );
		return ceil(time() / ( $nonce_life / 2 ));
	}

	function create_nonce($action = -1) {

		$user = $this->get_current_user();
		$uid = (int) $user->ID;
		if ( ! $uid ) {
			$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
		}

		$token = $this->get_session_token();
		$i = $this->nonce_tick();

		return substr( $this->hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
	}

	function check_password_reset_key($key, $login) {
		global $hasher;

		$key = preg_replace('/[^a-z0-9]/i', '', $key);

		if ( empty( $key ) || !is_string( $key ) )
			return new WP_Error('invalid_key', __('Invalid key'));

		if ( empty($login) || !is_string($login) )
			return new WP_Error('invalid_key', __('Invalid key'));

		$row = DB()->get_row( DB()->prepare( "SELECT ID, user_activation_key FROM $this->table WHERE user_login = %s", $login ) );
		if ( ! $row )
			return new WP_Error('invalid_key', __('Invalid key'));

		if ( empty( $hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$hasher = new PasswordHash( 8, true );
		}

		if ( $hasher->CheckPassword( $key, $row->user_activation_key ) )
			return $this->get_userdata( $row->ID );

		if ( $key === $row->user_activation_key ) {
			$return = new WP_Error( 'expired_key', __( 'Invalid key' ) );
			$user_id = $row->ID;
			return apply_filters( 'password_reset_key_expired', $return, $user_id );
		}

		return new WP_Error( 'invalid_key', __( 'Invalid key' ) );
	}

	function reset_password( $user, $new_pass ) {
		do_action( 'app_password_reset', $user, $new_pass );
		$this->set_password( $new_pass, $user->ID );
		$this->password_change_notification( $user );
	}

	function verify_nonce( $nonce, $action = -1 ) {

		$nonce = (string) $nonce;
		$user = $this->get_current_user();
		$uid = (int) $user->ID;
		if ( ! $uid ) {
			$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
		}

		if ( empty( $nonce ) ) {
			return false;
		}

		$token = $this->get_session_token();
		$i = $this->nonce_tick();

		// Nonce generated 0-12 hours ago
		$expected = substr( $this->hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce'), -12, 10 );
		if ( hash_equals( $expected, $nonce ) ) {
			return 1;
		}

		// Nonce generated 12-24 hours ago
		$expected = substr( $this->hash( ( $i - 1 ) . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
		if ( hash_equals( $expected, $nonce ) ) {
			return 2;
		}

		// Invalid nonce
		return false;
	}

	function get_session_token() {
		$cookie = $this->parse_auth_cookie( '', 'logged_in' );
		return ! empty( $cookie['token'] ) ? $cookie['token'] : '';
	}

	function get_all_sessions() {
		$manager = WP_Session_Tokens::get_instance( $this->get_current_user_id() );
		return $manager->get_all();
	}

	function destroy_current_session() {
		$token = $this->get_session_token();
		if ( $token ) {
			$manager = WP_Session_Tokens::get_instance( $this->get_current_user_id() );
			$manager->destroy( $token );
		}
	}

	function destroy_other_sessions() {
		$token = $this->get_session_token();
		if ( $token ) {
			$manager = WP_Session_Tokens::get_instance( $this->get_current_user_id() );
			$manager->destroy_others( $token );
		}
	}
	function destroy_all_sessions() {
		$manager = WP_Session_Tokens::get_instance( $this->get_current_user_id() );
		$manager->destroy_all();
	}

	function validate_auth_cookie($cookie = '', $scheme = '') {

		if ( ! $cookie_elements = $this->parse_auth_cookie($cookie, $scheme) ) {
			do_action( 'app_auth_cookie_malformed', $cookie, $scheme );
			return false;
		}

		$scheme = $cookie_elements['scheme'];
		$username = $cookie_elements['username'];
		$hmac = $cookie_elements['hmac'];
		$token = $cookie_elements['token'];
		$expired = $expiration = $cookie_elements['expiration'];

		if ( defined('DOING_AJAX') || 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$expired += HOUR_IN_SECONDS;
		}

		if ( $expired < time() ) {
			do_action( 'app_auth_cookie_expired', $cookie_elements );
			return false;
		}

		$user = $this->get_user_by('login', $username);
		if ( ! $user ) {
			do_action( 'app_auth_cookie_bad_username', $cookie_elements );
			return false;
		}

		$pass_frag = substr($user->user_pass, 8, 4);

		$key = $this->hash( $username . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );

		// If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
		$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
		$hash = hash_hmac( $algo, $username . '|' . $expiration . '|' . $token, $key );

		if ( ! hash_equals( $hash, $hmac ) ) {
			do_action( 'app_auth_cookie_bad_hash', $cookie_elements );
			return false;
		}

		$manager = WP_Session_Tokens::get_instance( $user->ID );
		if ( ! $manager->verify( $token ) ) {
			do_action( 'app_auth_cookie_bad_session_token', $cookie_elements );
			return false;
		}

		if ( $expiration < time() ) {
			$GLOBALS['login_grace_period'] = 1;
		}

		do_action( 'app_auth_cookie_valid', $cookie_elements, $user );

		return $user->ID;
	}

	function generate_auth_cookie( $user_id, $expiration, $scheme = 'auth', $token = '' ) {

		$user = $this->get_userdata($user_id);
		if ( ! $user ) {
			return '';
		}

		if ( ! $token ) {
			$manager = WP_Session_Tokens::get_instance( $user_id );
			$token = $manager->create( $expiration );
		}

		$pass_frag = substr($user->user_pass, 8, 4);

		$key = $this->hash( $user->user_login . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );

		$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
		$hash = hash_hmac( $algo, $user->user_login . '|' . $expiration . '|' . $token, $key );

		$cookie = $user->user_login . '|' . $expiration . '|' . $token . '|' . $hash;

		return apply_filters( 'auth_cookie', $cookie, $user_id, $expiration, $scheme, $token );
	}

	function parse_auth_cookie($cookie = '', $scheme = '') {
		if ( empty($cookie) ) {
			switch ($scheme){
				case 'auth':
					$cookie_name = APP_AUTH_COOKIE;
					break;
				case 'secure_auth':
					$cookie_name = APP_SECURE_AUTH_COOKIE;
					break;
				case "logged_in":
					$cookie_name = APP_LOGGED_IN_COOKIE;
					break;
				default:
					if ( is_ssl() ) {
						$cookie_name = APP_SECURE_AUTH_COOKIE;
						$scheme = 'secure_auth';
					} else {
						$cookie_name = APP_AUTH_COOKIE;
						$scheme = 'auth';
					}
		    }

			if ( empty($_COOKIE[$cookie_name]) )
				return false;
			$cookie = $_COOKIE[$cookie_name];
		}

		$cookie_elements = explode('|', $cookie);
		if ( count( $cookie_elements ) !== 4 ) {
			return false;
		}

		list( $username, $expiration, $token, $hmac ) = $cookie_elements;

		return compact( 'username', 'expiration', 'token', 'hmac', 'scheme' );
	}

	function set_auth_cookie($user_id, $remember = false, $secure = '') {
		if ( $remember ) {
			$expiration = time() + apply_filters( 'auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, $remember );

			$expire = $expiration + ( 12 * HOUR_IN_SECONDS );
		} else {
			$expiration = time() + apply_filters( 'auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, $remember );
			$expire = 0;
		}

		if ( '' === $secure ) {
			$secure = is_ssl();
		}

		// Frontend cookie is secure when the auth cookie is secure and the site's home URL is forced HTTPS.
		$secure_logged_in_cookie = $secure && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );

		$secure = apply_filters( 'app_secure_auth_cookie', $secure, $user_id );

		$secure_logged_in_cookie = apply_filters( 'app_secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure );

		if ( $secure ) {
			$auth_cookie_name = APP_SECURE_AUTH_COOKIE;
			$scheme = 'secure_auth';
		} else {
			$auth_cookie_name = APP_AUTH_COOKIE;
			$scheme = 'auth';
		}

		$manager = WP_Session_Tokens::get_instance( $user_id );
		$token = $manager->create( $expiration );

		$auth_cookie = $this->generate_auth_cookie( $user_id, $expiration, $scheme, $token );
		$logged_in_cookie = $this->generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );

		do_action( 'app_set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme );

		do_action( 'app_set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in' );

		setcookie($auth_cookie_name, $auth_cookie, $expire, APP_PLUGINS_COOKIE_PATH, APP_COOKIE_DOMAIN, $secure, true);
		setcookie($auth_cookie_name, $auth_cookie, $expire, APP_ADMIN_COOKIE_PATH, APP_COOKIE_DOMAIN, $secure, true);
		setcookie(APP_LOGGED_IN_COOKIE, $logged_in_cookie, $expire, APP_COOKIEPATH, APP_COOKIE_DOMAIN, $secure_logged_in_cookie, true);
		if ( APP_COOKIEPATH != APP_SITECOOKIEPATH )
			setcookie(APP_LOGGED_IN_COOKIE, $logged_in_cookie, $expire, APP_SITECOOKIEPATH, APP_COOKIE_DOMAIN, $secure_logged_in_cookie, true);
	}

	function clear_auth_cookie() {
		do_action( 'app_clear_auth_cookie' );

		setcookie( APP_AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, APP_ADMIN_COOKIE_PATH,   APP_COOKIE_DOMAIN );
		setcookie( APP_SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, APP_ADMIN_COOKIE_PATH,   APP_COOKIE_DOMAIN );
		setcookie( APP_AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, APP_PLUGINS_COOKIE_PATH, APP_COOKIE_DOMAIN );
		setcookie( APP_SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, APP_PLUGINS_COOKIE_PATH, APP_COOKIE_DOMAIN );
		setcookie( APP_LOGGED_IN_COOKIE,   ' ', time() - YEAR_IN_SECONDS, APP_COOKIEPATH,          APP_COOKIE_DOMAIN );
		setcookie( APP_LOGGED_IN_COOKIE,   ' ', time() - YEAR_IN_SECONDS, APP_SITECOOKIEPATH,      APP_COOKIE_DOMAIN );

		// Old cookies
		setcookie( APP_AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, APP_COOKIEPATH,     APP_COOKIE_DOMAIN );
		setcookie( APP_AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, APP_SITECOOKIEPATH, APP_COOKIE_DOMAIN );
		setcookie( APP_SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, APP_COOKIEPATH,     APP_COOKIE_DOMAIN );
		setcookie( APP_SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, APP_SITECOOKIEPATH, APP_COOKIE_DOMAIN );

		// Even older cookies
		setcookie( APP_USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, APP_COOKIEPATH,     APP_COOKIE_DOMAIN );
		setcookie( APP_PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, APP_COOKIEPATH,     APP_COOKIE_DOMAIN );
	}

	function add_user_meta($object_id, $meta_key, $meta_value) {

		if (! is_numeric( $object_id ) ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}

		$table = DB()->prefix.'app_users_meta';

		$meta_key = wp_unslash($meta_key);
		$meta_value = wp_unslash($meta_value);

		$_meta_value = $meta_value;
		$meta_value = maybe_serialize( $meta_value );

		do_action( "add_app_user_meta", $object_id, $meta_key, $_meta_value );

		$result = DB()->insert( $table, array(
			'user_id' => $object_id,
			'meta_key' => $meta_key,
			'meta_value' => $meta_value
		) );

		if ( ! $result )
			return false;

		$mid = (int) DB()->insert_id;

		wp_cache_delete($object_id, 'app_user_meta');

		return $mid;
	}

	function update_user_meta($object_id, $meta_key, $meta_value, $prev_value = '') {

		if ( ! $meta_key || ! is_numeric( $object_id ) ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}

		$table = DB()->prefix.'app_users_meta';

		$column = 'user_id';
		$id_column = 'umeta_id' ;

		// expected_slashed ($meta_key)
		$meta_key = wp_unslash($meta_key);
		$passed_value = $meta_value;
		$meta_value = wp_unslash($meta_value);

		// Compare existing value to new value if no prev value given and the key exists only once.
		if ( empty($prev_value) ) {
			$old_value = $this->get_user_meta($object_id, $meta_key);
			if ( count($old_value) == 1 ) {
				if ( $old_value[0] === $meta_value )
					return false;
			}
		}

		$meta_ids = DB()->get_col( DB()->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id ) );
		if ( empty( $meta_ids ) ) {
			return $this->add_user_meta($object_id, $meta_key, $passed_value);
		}

		$_meta_value = $meta_value;
		$meta_value = maybe_serialize( $meta_value );

		$data  = compact( 'meta_value' );
		$where = array( $column => $object_id, 'meta_key' => $meta_key );

		if ( !empty( $prev_value ) ) {
			$prev_value = maybe_serialize($prev_value);
			$where['meta_value'] = $prev_value;
		}

		$result = DB()->update( $table, $data, $where );
		if ( ! $result )
			return false;

		wp_cache_delete($object_id, 'app_user_meta');

		return true;
	}

	function delete_user_meta($object_id, $meta_key, $meta_value = '', $delete_all = false) {

		if ( ! $meta_key || ! is_numeric( $object_id ) && ! $delete_all ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id && ! $delete_all ) {
			return false;
		}

		$table = DB()->prefix.'app_users_meta';

		$type_column = 'user_id';
		$id_column = 'umeta_id' ;

		// expected_slashed ($meta_key)
		$meta_key = wp_unslash($meta_key);
		$meta_value = wp_unslash($meta_value);


		$_meta_value = $meta_value;
		$meta_value = maybe_serialize( $meta_value );

		$query = DB()->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s", $meta_key );

		if ( !$delete_all )
			$query .= DB()->prepare(" AND $type_column = %d", $object_id );

		if ( $meta_value )
			$query .= DB()->prepare(" AND meta_value = %s", $meta_value );

		$meta_ids = DB()->get_col( $query );
		if ( !count( $meta_ids ) )
			return false;

		if ( $delete_all )
			$object_ids = DB()->get_col( DB()->prepare( "SELECT $type_column FROM $table WHERE meta_key = %s", $meta_key ) );

		$query = "DELETE FROM $table WHERE $id_column IN( " . implode( ',', $meta_ids ) . " )";

		$count = DB()->query($query);

		if ( !$count )
			return false;

		if ( $delete_all ) {
			foreach ( (array) $object_ids as $o_id ) {
				wp_cache_delete($o_id, 'app_user_meta');
			}
		} else {
			wp_cache_delete($object_id, 'app_user_meta');
		}

		return true;
	}

	function get_user_meta($object_id, $meta_key = '', $single = false) {
		if ( ! is_numeric( $object_id ) ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}

		$meta_cache = wp_cache_get($object_id, 'app_user_meta');

		if ( !$meta_cache ) {
			$meta_cache = $this->update_meta_cache( 'app_user_meta', array( $object_id ) );
			$meta_cache = $meta_cache[$object_id];
		}

		if ( ! $meta_key ) {
			return $meta_cache;
		}

		if ( isset($meta_cache[$meta_key]) ) {
			if ( $single )
				return maybe_unserialize( $meta_cache[$meta_key][0] );
			else
				return array_map('maybe_unserialize', $meta_cache[$meta_key]);
		}

		if ($single)
			return '';
		else
			return array();
	}

	function update_meta_cache($meta_type, $object_ids) {

		if ( ! $meta_type || ! $object_ids ) {
			return false;
		}

		$table = DB()->prefix.'app_users_meta';

		$column = 'user_id';

		if ( !is_array($object_ids) ) {
			$object_ids = preg_replace('|[^0-9,]|', '', $object_ids);
			$object_ids = explode(',', $object_ids);
		}

		$object_ids = array_map('intval', $object_ids);

		$cache_key = 'app_user_meta';
		$ids = array();
		$cache = array();
		foreach ( $object_ids as $id ) {
			$cached_object = wp_cache_get( $id, $cache_key );
			if ( false === $cached_object )
				$ids[] = $id;
			else
				$cache[$id] = $cached_object;
		}

		if ( empty( $ids ) )
			return $cache;

		// Get meta info
		$id_list = join( ',', $ids );
		$id_column = 'umeta_id';
		$meta_list = DB()->get_results( "SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list) ORDER BY $id_column ASC", ARRAY_A );

		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow) {
				$mpid = intval($metarow[$column]);
				$mkey = $metarow['meta_key'];
				$mval = $metarow['meta_value'];

				// Force subkeys to be array type:
				if ( !isset($cache[$mpid]) || !is_array($cache[$mpid]) )
					$cache[$mpid] = array();
				if ( !isset($cache[$mpid][$mkey]) || !is_array($cache[$mpid][$mkey]) )
					$cache[$mpid][$mkey] = array();

				// Add a value to the current pid/key:
				$cache[$mpid][$mkey][] = $mval;
			}
		}

		foreach ( $ids as $id ) {
			if ( ! isset($cache[$id]) )
				$cache[$id] = array();
			wp_cache_add( $id, $cache[$id], $cache_key );
		}

		return $cache;
	}

	function setup_cookie_constants() {
		if ( !defined( 'APP_COOKIEHASH' ) ) {
			$siteurl = get_site_option( 'siteurl' );
			if ( $siteurl )
				define( 'APP_COOKIEHASH', md5( $siteurl ) );
			else
				define( 'APP_COOKIEHASH', '' );
		}

		if ( !defined('APP_USER_COOKIE') ) define('APP_USER_COOKIE', 'appuser_' . APP_COOKIEHASH);
		if ( !defined('APP_PASS_COOKIE') ) define('APP_PASS_COOKIE', 'apppass_' . APP_COOKIEHASH);
		if ( !defined('APP_AUTH_COOKIE') ) define('APP_AUTH_COOKIE', 'app_' . APP_COOKIEHASH);
		if ( !defined('APP_SECURE_AUTH_COOKIE') ) define('APP_SECURE_AUTH_COOKIE', 'app_sec_' . APP_COOKIEHASH);
		if ( !defined('APP_LOGGED_IN_COOKIE') ) define('APP_LOGGED_IN_COOKIE', 'app_logged_in_' . APP_COOKIEHASH);
		if ( !defined('APP_TEST_COOKIE') ) define('APP_TEST_COOKIE', 'app_test_cookie');
		if ( !defined('APP_COOKIEPATH') ) define('APP_COOKIEPATH', preg_replace('|https?://[^/]+|i', '', get_option('home') . '/' ) );
		if ( !defined('APP_SITECOOKIEPATH') ) define('APP_SITECOOKIEPATH', preg_replace('|https?://[^/]+|i', '', get_option('siteurl') . '/' ) );
		if ( !defined('APP_ADMIN_COOKIE_PATH') )
		define( 'APP_ADMIN_COOKIE_PATH', APP_SITECOOKIEPATH );
		if ( !defined('APP_PLUGINS_COOKIE_PATH') ) define( 'APP_PLUGINS_COOKIE_PATH', preg_replace('|https?://[^/]+|i', '', WP_PLUGIN_URL)  );
		if ( !defined('APP_COOKIE_DOMAIN') ) define('APP_COOKIE_DOMAIN', false);
	}

	function hash($data, $scheme = 'auth') {
		$salt = $this->salt($scheme);
		return hash_hmac('md5', $data, $salt);
	}

	function salt( $scheme = 'auth' ) {
		static $cached_salts = array();
		if ( isset( $cached_salts[ $scheme ] ) ) {
			return apply_filters( 'salt', $cached_salts[ $scheme ], $scheme );
		}

		static $duplicated_keys;
		if ( null === $duplicated_keys ) {
			$duplicated_keys = array( 'put your unique phrase here' => true );
			foreach ( array( 'AUTH', 'SECURE_AUTH', 'LOGGED_IN', 'NONCE', 'SECRET' ) as $first ) {
				foreach ( array( 'KEY', 'SALT' ) as $second ) {
					if ( ! defined( "{$first}_{$second}" ) ) {
						continue;
					}
					$value = constant( "{$first}_{$second}" );
					$duplicated_keys[ $value ] = isset( $duplicated_keys[ $value ] );
				}
			}
		}

		$values = array(
			'key' => '',
			'salt' => ''
		);
		if ( defined( 'SECRET_KEY' ) && SECRET_KEY && empty( $duplicated_keys[ SECRET_KEY ] ) ) {
			$values['key'] = SECRET_KEY;
		}
		if ( 'auth' == $scheme && defined( 'SECRET_SALT' ) && SECRET_SALT && empty( $duplicated_keys[ SECRET_SALT ] ) ) {
			$values['salt'] = SECRET_SALT;
		}

		if ( in_array( $scheme, array( 'auth', 'secure_auth', 'logged_in', 'nonce' ) ) ) {
			foreach ( array( 'key', 'salt' ) as $type ) {
				$const = strtoupper( "{$scheme}_{$type}" );
				if ( defined( $const ) && constant( $const ) && empty( $duplicated_keys[ constant( $const ) ] ) ) {
					$values[ $type ] = constant( $const );
				} elseif ( ! $values[ $type ] ) {
					$values[ $type ] = get_site_option( "{$scheme}_{$type}" );
					if ( ! $values[ $type ] ) {
						$values[ $type ] = $this->generate_password( 64, true, true );
						update_site_option( "{$scheme}_{$type}", $values[ $type ] );
					}
				}
			}
		} else {
			if ( ! $values['key'] ) {
				$values['key'] = get_site_option( 'secret_key' );
				if ( ! $values['key'] ) {
					$values['key'] = $this->generate_password( 64, true, true );
					update_site_option( 'secret_key', $values['key'] );
				}
			}
			$values['salt'] = hash_hmac( 'md5', $scheme, $values['key'] );
		}

		$cached_salts[ $scheme ] = $values['key'] . $values['salt'];

		/** This filter is documented in wp-includes/pluggable.php */
		return apply_filters( 'salt', $cached_salts[ $scheme ], $scheme );
	}

	function generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ( $special_chars )
			$chars .= '!@#$%^&*()';
		if ( $extra_special_chars )
			$chars .= '-_ []{}<>~`+=,.;:/?|';

		$password = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$password .= substr($chars, $this->get_rand(0, strlen($chars) - 1), 1);
		}
		return apply_filters( 'random_password', $password );
	}

	function get_rand( $min = 0, $max = 0 ) {
		global $rnd_value;

		// Reset $rnd_value after 14 uses
		// 32(md5) + 40(sha1) + 40(sha1) / 8 = 14 random numbers from $rnd_value
		if ( strlen($rnd_value) < 8 ) {
			if ( defined( 'WP_SETUP_CONFIG' ) )
				static $seed = '';
			else
				$seed = get_transient('random_seed');

			$rnd_value = md5( uniqid(microtime() . mt_rand(), true ) . $seed );
			$rnd_value .= sha1($rnd_value);
			$rnd_value .= sha1($rnd_value . $seed);
			$seed = md5($seed . $rnd_value);
			if ( ! defined( 'WP_SETUP_CONFIG' ) )
				set_transient('random_seed', $seed);
		}

		// Take the first 8 digits for our value
		$value = substr($rnd_value, 0, 8);

		// Strip the first eight, leaving the remainder for the next call to wp_rand().
		$rnd_value = substr($rnd_value, 8);

		$value = abs(hexdec($value));

		// Some misconfigured 32bit environments (Entropy PHP, for example) truncate integers larger than PHP_INT_MAX to PHP_INT_MAX rather than overflowing them to floats.
		$max_random_number = 3000000000 === 2147483647 ? (float) "4294967295" : 4294967295; // 4294967295 = 0xffffffff

		// Reduce the value to be within the min - max range
		if ( $max != 0 )
			$value = $min + ( $max - $min + 1 ) * $value / ( $max_random_number + 1 );

		return abs(intval($value));
	}

}

function AU() {return API_Users::instance();}
$GLOBALS['AU'] = AU();
