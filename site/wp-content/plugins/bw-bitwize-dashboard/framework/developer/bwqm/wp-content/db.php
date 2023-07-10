<?php
/*
Plugin Name: BW Query Monitor Database Class
*********************************************************************
Ensure this file is symlinked to your wp-content directory to provide
additional database query information in Query Monitor's output.
*********************************************************************
*/

defined( 'ABSPATH' ) or die();
if ( defined( 'BWQM_DISABLED' ) and BWQM_DISABLED ) return;
if ( defined( 'WP_CLI' ) and WP_CLI ) return;
$bwqm_dir = dirname( dirname( __FILE__ ) );
if ( ! is_readable( $backtrace = "{$bwqm_dir}/classes/Backtrace.php" ) ) return;
require_once $backtrace;
if ( !defined( 'SAVEQUERIES' ) ) define( 'SAVEQUERIES', true );
class BWQM_DB extends wpdb {
	public $bwqm_php_vars = array(
		'max_execution_time'  => null,
		'memory_limit'        => null,
		'upload_max_filesize' => null,
		'post_max_size'       => null,
		'display_errors'      => null,
		'log_errors'          => null,
	);
	function __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {
		foreach ( $this->bwqm_php_vars as $setting => &$val ) {
			$val = ini_get( $setting );
		}
		parent::__construct( $dbuser, $dbpassword, $dbname, $dbhost );
	}
	function query( $query ) {
		if ( ! $this->ready ) return false;
		if ( $this->show_errors ) $this->hide_errors();
		$result = parent::query( $query );
		if ( ! SAVEQUERIES ) return $result;
		$i = $this->num_queries - 1;
		$this->queries[$i]['trace'] = new BWQM_Backtrace( array(
			'ignore_items' => 1,
		) );
		if ( $this->last_error ) {
			$this->queries[$i]['result'] = new WP_Error( 'qmdb', $this->last_error );
		} else {
			$this->queries[$i]['result'] = $result;
		}
		return $result;
	}
}
$wpdb = new BWQM_DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
