<?php


class BWQM_Output_Headers_Redirects extends BWQM_Output_Headers {

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['trace'] ) ) {
			return;
		}

		header( sprintf( 'X-QM-Redirect-Trace: %s',
			implode( ', ', $data['trace']->get_stack() )
		) );

	}

}

function register_bwqm_output_headers_redirects( array $output, BWQM_Collectors $collectors ) {
	if ( $collector = BWQM_Collectors::get( 'redirects' ) ) {
		$output['redirects'] = new BWQM_Output_Headers_Redirects( $collector );
	}
	return $output;
}

add_filter( 'bwqm/outputter/headers', 'register_bwqm_output_headers_redirects', 140, 2 );
