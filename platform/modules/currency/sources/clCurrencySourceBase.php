<?php

/*
 * Interface for all currency sources
 */
interface ifCurrencySource {
	
	public function readRates( $aCurrencyCodes = array() );
	
	public function getAvailableCurrencies();
	
}

abstract class clCurrencySourceBase {
	
	public $sSourceTitle;
	public $aRates;
	public $aAvailableCurrencies;

	public function __construct() {
		if( empty($this->sFeedSource) ) $this->sFeedSource = false;
	}
	
}