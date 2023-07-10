<?php


class BWQM_Collector_Request extends BWQM_Collector {

	public $id = 'request';

	public function name() {
		return __( 'Request', BW_TD );
	}

	public function process() {

		global $wp, $wp_query, $current_blog, $current_site;

		$qo = get_queried_object();

		if ( is_multisite() ) {
			$this->data['multisite']['current_blog'] = $current_blog;
		}

		if ( BWQM_Util::is_multi_network() ) {
			$this->data['multisite']['current_site'] = $current_site;
		}

		if ( is_admin() ) {
			$this->data['request']['request'] = $_SERVER['REQUEST_URI'];
			foreach ( array( 'query_string' ) as $item ) {
				$this->data['request'][$item] = $wp->$item;
			}
		} else {
			foreach ( array( 'request', 'matched_rule', 'matched_query', 'query_string' ) as $item ) {
				$this->data['request'][$item] = $wp->$item;
			}
		}

		$plugin_qvars = array_flip( apply_filters( 'query_vars', array() ) );
		$qvars        = $wp_query->query_vars;
		$query_vars   = array();

		foreach ( $qvars as $k => $v ) {
			if ( isset( $plugin_qvars[$k] ) ) {
				if ( '' !== $v ) {
					$query_vars[$k] = $v;
				}
			} else {
				if ( !empty( $v ) ) {
					$query_vars[$k] = $v;
				}
			}
		}

		ksort( $query_vars );

		# First add plugin vars to $this->data['qvars']:
		foreach ( $query_vars as $k => $v ) {
			if ( isset( $plugin_qvars[$k] ) ) {
				$this->data['qvars'][$k] = $v;
				$this->data['plugin_qvars'][$k] = $v;
			}
		}

		# Now add all other vars to $this->data['qvars']:
		foreach ( $query_vars as $k => $v ) {
			if ( !isset( $plugin_qvars[$k] ) ) {
				$this->data['qvars'][$k] = $v;
			}
		}

		switch ( true ) {

			case is_null( $qo ):
				// Nada
				break;

			case is_a( $qo, 'WP_Post' ):
				// Single post
				$this->data['queried_object_type']  = 'post';
				$this->data['queried_object_title'] = sprintf( __( 'Single %s: #%d', BW_TD ),
					get_post_type_object( $qo->post_type )->labels->singular_name,
					$qo->ID
				);
				break;

			case is_a( $qo, 'WP_User' ):
				// Author archive
				$this->data['queried_object_type']  = 'user';
				$this->data['queried_object_title'] = sprintf( __( 'Author archive: %s', BW_TD ),
					$qo->user_nicename
				);
				break;

			case property_exists( $qo, 'term_id' ):
				// Term archive
				$this->data['queried_object_type']  = 'term';
				$this->data['queried_object_title'] = sprintf( __( 'Term archive: %s', BW_TD ),
					$qo->slug
				);
				break;

			case property_exists( $qo, 'has_archive' ):
				// Post type archive
				$this->data['queried_object_type']  = 'archive';
				$this->data['queried_object_title'] = sprintf( __( 'Post type archive: %s', BW_TD ),
					$qo->name
				);
				break;

			default:
				// Unknown, but we have a queried object
				$this->data['queried_object_type']  = 'unknown';
				$this->data['queried_object_title'] = __( 'Unknown queried object', BW_TD );
				break;

		}

		$this->data['queried_object'] = $qo;

	}

}

function register_bwqm_collector_request( array $collectors, BW_QueryMonitor $bwqm ) {
	$collectors['request'] = new BWQM_Collector_Request;
	return $collectors;
}

add_filter( 'bwqm/collectors', 'register_bwqm_collector_request', 10, 2 );
