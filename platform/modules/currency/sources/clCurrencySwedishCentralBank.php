<?php

require_once PATH_MODULE . '/currency/sources/clCurrencySourceBase.php';

class clCurrencySwedishCentralBank extends clCurrencySourceBase implements ifCurrencySource {
	
	public $sSourceTitle = 'Sveriges riksbank';
	public $sFeedSource = 'https://swea.riksbank.se/sweaWS/wsdl/sweaWS_ssl.wsdl';
	public $aAvailableCurrencies = array();
	
	protected $rClient;
	
	public function __construct() {		
		$sWsdl =& $this->sFeedSource;
		
		$aParams = array(			
			'trace' => true,
			'exceptions' => true,
			'use' => SOAP_LITERAL,
			'style' => SOAP_DOCUMENT,
			'connection_timeout' => 15
		);
		if( strpos($_SERVER["HTTP_HOST"], 'uapp') > 0 ) {
			// Client having proxy
			$aParams += array(
				'proxy_host' => "isa4",
				'proxy_port' => 8080
			);
		}
		
		try {
			$this->rClient = new SoapClient( $sWsdl, $aParams );
		} catch( SoapFault $oFault ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataError' => _( 'Could not access the external resource service' )
			) );
		}		
	}
	
	/* *
	 *	Returns data about codes for data gathering
	 */
	public function getInterestAndExchangeNames( $iGroupId, $sLanguage ) {
		if( $this->rClient === null ) return false;
		
		return $this->rClient->getInterestAndExchangeNames( array(
			'groupid' => $iGroupId,
			'languageid' => $sLanguage
		) );
	}
	
	/* *
	 *	Returns exchange rate between given currencies
	 */
	public function getLatestInterestAndExchangeRates( $sSeriesId, $sLanguage ) {
		if( $this->rClient === null ) return false;
		
		return $this->rClient->getLatestInterestAndExchangeRates( array(
			'languageid' => $sLanguage,
			'seriesid' => $sSeriesId
		) );
	}
	
	/* *
	 * id(11) is the id of the group of codes for currencies
	 */
	public function getAvailableExchangeNames() {
		if( $this->rClient === null ) return false;
		
		$oResult = $this->getInterestAndExchangeNames( '11', 'en' );
		$aExchangeNames = array();
		foreach( $oResult->return as $oEntry ) {
			$aExchangeNames[$oEntry->shortdescription] = $oEntry->seriesid;	
		}
		return $aExchangeNames;
	}
	
	/* *
	 * id(11) is the id of the group of codes for currencies
	 */
	public function getAvailableCurrencies() {
		if( $this->rClient === null ) return false;
		
		$oResult = $this->getInterestAndExchangeNames( '11', 'en' );
		$aExchangeCodes = array();
		foreach( $oResult->return as $oEntry ) {
			$this->aAvailableCurrencies[] = $oEntry->shortdescription;	
		}
		return $this->aAvailableCurrencies;
	}
	
	public function readRates( $aCurrencyCodes = array() ) {
		if( $this->rClient === null ) return false;		
		if( $this->sFeedSource === false ) return false;
			
		$aExchangeNames = $this->getAvailableExchangeNames();
		
		foreach( $aCurrencyCodes as $sCurrencyCode ) {
			if( array_key_exists($sCurrencyCode, $aExchangeNames) ) {
				$oResult = $this->getLatestInterestAndExchangeRates( $aExchangeNames[$sCurrencyCode], 'en' );				
				$this->aRates[] = array(
					'code' => $sCurrencyCode,
					'rate' => $oResult->return->groups->series->resultrows->value / $oResult->return->groups->series->unit
				);
			}
		}
		
		return $this->aRates;
	}
	
}