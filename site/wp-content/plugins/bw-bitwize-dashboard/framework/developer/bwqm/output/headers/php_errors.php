<?php


class BWQM_Output_Headers_PHP_Errors extends BWQM_Output_Headers {

	public function output() {

		if ( ! BWQM_Util::is_ajax() ) {
			return;
		}

		$data = $this->collector->get_data();

		if ( empty( $data['errors'] ) ) {
			return;
		}

		$count = 0;

		foreach ( $data['errors'] as $type => $errors ) {

			foreach ( $errors as $key => $error ) {

				$count++;

				# @TODO we should calculate the component during process() so we don't need to do it
				# separately in each output.
				$component = $error->trace->get_component();
				$output_error = array(
					'type'      => $error->type,
					'message'   => wp_strip_all_tags( $error->message ),
					'file'      => $error->file,
					'line'      => $error->line,
					'stack'     => $error->trace->get_stack(),
					'component' => $component->name,
				);

				header( sprintf( 'X-QM-Error-%d: %s',
					$count,
					json_encode( $output_error )
				) );

			}

		}

		header( sprintf( 'X-QM-Errors: %d',
			$count
		) );

	}

}

function register_bwqm_output_headers_php_errors( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'php_errors' ) ) {
		$output['php_errors'] = new BWQM_Output_Headers_PHP_Errors( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/headers', 'register_bwqm_output_headers_php_errors', 110, 2 );
