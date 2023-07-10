<?php


# E_DEPRECATED and E_USER_DEPRECATED were introduced in PHP 5.3 so we need to use back-compat constants that work on 5.2.
if ( defined( 'E_DEPRECATED' ) ) {
	define( 'BWQM_E_DEPRECATED', E_DEPRECATED );
} else {
	define( 'BWQM_E_DEPRECATED', 0 );
}

if ( defined( 'E_USER_DEPRECATED' ) ) {
	define( 'BWQM_E_USER_DEPRECATED', E_USER_DEPRECATED );
} else {
	define( 'BWQM_E_USER_DEPRECATED', 0 );
}

class BWQM_Collector_PHP_Errors extends BWQM_Collector {

	public $id = 'php_errors';
	private $display_errors = null;

	public function name() {
		return __( 'PHP Errors', BW_TD );
	}

	public function __construct() {

		parent::__construct();
		set_error_handler( array( $this, 'error_handler' ) );
		register_shutdown_function( array( $this, 'shutdown_handler' ) );

		$this->display_errors = ini_get( 'display_errors' );
		@ini_set( 'display_errors', 0 );

	}

	public function error_handler( $errno, $message, $file = null, $line = null ) {

		#if ( !( error_reporting() & $errno ) )
		#	return false;

		switch ( $errno ) {

			case E_WARNING:
			case E_USER_WARNING:
				$type = 'warning';
				break;

			case E_NOTICE:
			case E_USER_NOTICE:
				$type = 'notice';
				break;

			case E_STRICT:
				$type = 'strict';
				break;

			case BWQM_E_DEPRECATED:
			case BWQM_E_USER_DEPRECATED:
				$type = 'deprecated';
				break;

			default:
				return false;
				break;

		}

		if ( error_reporting() > 0 ) {

			if ( ! class_exists( 'BWQM_Backtrace' ) ) {
				return false;
			}

			$trace  = new BWQM_Backtrace;
			$caller = $trace->get_caller();
			$key    = md5( $message . $file . $line . $caller['id'] );

			$filename = BWQM_Util::standard_dir( $file, '' );

			if ( isset( $this->data['errors'][$type][$key] ) ) {
				$this->data['errors'][$type][$key]->calls++;
			} else {
				$this->data['errors'][$type][$key] = (object) array(
					'errno'    => $errno,
					'type'     => $type,
					'message'  => $message,
					'file'     => $file,
					'filename' => $filename,
					'line'     => $line,
					'trace'    => $trace,
					'calls'    => 1
				);
			}

		}

		return apply_filters( 'bwqm/collect/php_errors_return_value', false );

	}

	public function shutdown_handler() {

		$e = error_get_last();

		if ( empty( $this->display_errors ) ) {
			return;
		}

		if ( empty( $e ) or ! ( $e['type'] & ( E_ERROR | E_PARSE | E_COMPILE_ERROR | E_COMPILE_WARNING | E_USER_ERROR | E_RECOVERABLE_ERROR ) ) ) {
			return;
		}

		if ( $e['type'] & E_RECOVERABLE_ERROR ) {
			$error = 'Catchable fatal error';
		} else if ( $e['type'] & E_COMPILE_WARNING ) {
			$error = 'Warning';
		} else {
			$error = 'Fatal error';
		}

		if ( function_exists( 'xdebug_print_function_stack' ) ) {

			xdebug_print_function_stack( sprintf( '%1$s: %2$s in %3$s on line %4$d. Output triggered ',
				$error,
				$e['message'],
				$e['file'],
				$e['line']
			) );

		} else {

			printf( '<br /><b>%1$s</b>: %2$s in <b>%3$s</b> on line <b>%4$d</b><br />',
				htmlentities( $error ),
				htmlentities( $e['message'] ),
				htmlentities( $e['file'] ),
				intval( $e['line'] )
			);

		}

	}

	public function tear_down() {
		parent::tear_down();
		ini_set( 'display_errors', $this->display_errors );
		restore_error_handler();
	}

}

# Load early to catch early errors
BWQM_Collectors::add( new BWQM_Collector_PHP_Errors );
