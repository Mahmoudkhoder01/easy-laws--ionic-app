<?php


abstract class BWQM_Output_Headers implements BWQM_Output {

	public function __construct( BWQM_Collector $collector ) {
		$this->collector = $collector;
	}

}
