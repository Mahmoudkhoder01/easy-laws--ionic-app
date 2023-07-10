<?php


if ( ! interface_exists( 'BWQM_Output' ) ) {
interface BWQM_Output {

	public function __construct( BWQM_Collector $collector );

	public function output();

}
}
