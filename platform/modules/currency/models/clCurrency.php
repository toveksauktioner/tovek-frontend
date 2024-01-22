<?php

require_once PATH_CORE . '/clModuleBase.php';

class clCurrency extends clModuleBase {
	private $aCurrency = array();
	
	public function __construct() {
		$this->sModulePrefix = 'currency';
		$this->sModuleName = 'Currency';

		$this->oDao = clRegistry::get( 'clCurrencyDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/currency/models' );
		$this->initBase();
	}
	
	public function readByCurrencyCode( $sCurrency, $aData = '*' ) {
		//$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$a = $this->oDao->readByCurrencyCode( $sCurrency, $aData );
		return $a;
	}
	
	public function updateAllCurrencyCodes( $aCodes = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aCodesInDb = $this->read( 'currencyCode' );
		
		foreach( $aCodesInDb as $entry ) {
			$this->oDao->updateByCurrencyCode( $entry['currencyCode'], array(
				'currencyRate' => (float) number_format( $aCodes[$entry['currencyCode']], 3 ) )
			);
		}
		
		return true;
	}
	
	public function getCurrencyRate($sCurrency) {	
		if( ($sCurrency = strtoupper($sCurrency)) == 'SEK' ) return 1;
		
		if( !array_key_exists( $sCurrency, $this->aCurrency) ) {
			$oCurrency = clRegistry::get( 'clCurrency', PATH_MODULE . '/currency/models' );
			$this->aCurrency[$sCurrency] = array();

			$aCurrency = current( $oCurrency->readByCurrencyCode( $sCurrency, 'currencyRate' ) );
			
			if( empty($aCurrency) ) {
				throw new Exception( sprintf( _( 'If you would like to use %s as currency you have to insert the rate into the currency table.' ), $sCurrency) );
			}
			
			$this->aCurrency[$sCurrency] = (double) $aCurrency['currencyRate'];
		}
		
		return $this->aCurrency[$sCurrency];
	}

}
