<?php

require_once PATH_MODULE . '/currency/sources/clCurrencySourceBase.php';

class clCurrencySourceEcbEuropa extends clCurrencySourceBase implements ifCurrencySource {
	
	public $sSourceTitle = 'The European Central Bank';
	public $sFeedSource = 'http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
	public $aAvailableCurrencies = array();
	
	public function getAvailableCurrencies() {
		require_once PATH_FUNCTION . '/fXml.php';
		
		// Curl options
		$aCurlOptions = array(
			CURLOPT_URL 			=>  $this->sFeedSource,
			CURLOPT_PORT 			=>  80,
			CURLOPT_RETURNTRANSFER  =>  1,
			CURLOPT_CONNECTTIMEOUT  =>  45,
			CURLOPT_ENCODING 		=>  'UTF-8'
		);
		
		// Get data
		$rCurlHandle = curl_init();
		curl_setopt_array( $rCurlHandle, $aCurlOptions ); 
		$sContent = curl_exec( $rCurlHandle );
		$iErrNo = curl_errno( $rCurlHandle );
		$sError  = curl_error( $rCurlHandle ) ;
		$aHeader  = curl_getinfo( $rCurlHandle );
		curl_close( $rCurlHandle );
		
		// Convert to array
		$aContent = xml2array($sContent);
		
		// Grab right content
		$aRateData = $aContent['gesmes:Envelope']['_data']['Cube']['_data']['Cube']['_data'];
		$aRateData = current( $aRateData );
		
		// Resort data
		$aRates = array();
		foreach( $aRateData as $entry ){
			$this->aAvailableCurrencies[] = $entry['_attributes']['currency'];			
		}
		
		return $this->aAvailableCurrencies;
	}
	
	public function readRates( $aCurrencyCodes = array() ) {
		require_once PATH_FUNCTION . '/fXml.php';
		
		// Curl options
		$aCurlOptions = array(
			CURLOPT_URL 			=>  $this->sFeedSource,
			CURLOPT_PORT 			=>  80,
			CURLOPT_RETURNTRANSFER  =>  1,
			CURLOPT_CONNECTTIMEOUT  =>  45,
			CURLOPT_ENCODING 		=>  'UTF-8'
		);
		
		// Get data
		$rCurlHandle = curl_init();
		curl_setopt_array( $rCurlHandle, $aCurlOptions ); 
		$sContent = curl_exec( $rCurlHandle );
		$iErrNo = curl_errno( $rCurlHandle );
		$sError  = curl_error( $rCurlHandle ) ;
		$aHeader  = curl_getinfo( $rCurlHandle );
		curl_close( $rCurlHandle );
		
		// Convert to array
		$aContent = xml2array($sContent);
		
		// Grab right content
		$aRateData = $aContent['gesmes:Envelope']['_data']['Cube']['_data']['Cube']['_data'];
		$aRateData = current( $aRateData );
		
		// Resort data
		$aRates = array();
		foreach( $aRateData as $entry ){
			$this->aRates[] = array(
				'code' => $entry['_attributes']['currency'],
				'rate' => $entry['_attributes']['rate']
			);
		}
		
		return $this->aRates;
	}
	
}