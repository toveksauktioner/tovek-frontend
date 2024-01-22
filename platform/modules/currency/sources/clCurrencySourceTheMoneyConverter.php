<?php

require_once PATH_MODULE . '/currency/sources/clCurrencySourceBase.php';

class clCurrencySourceTheMoneyConverter extends clCurrencySourceBase implements ifCurrencySource{
	public $sFeedSource = 'http://themoneyconverter.com/SEK/rss.xml';

	public function readRates() {
		if( $this->sFeedSource === false ) return false;		

		if( $oXml = simplexml_load_file($this->sFeedSource) ) {
			$iCount = 0;
			foreach( $oXml->xpath('/rss/channel/item/title') as $sTitle ) {
				$sTitle = (string) $sTitle;
				list( $sToCurrency, $sFromCurrency ) = explode( '/', $sTitle );
				$this->aRates[$iCount]['code'] = $sToCurrency;
				++$iCount;
			}
	
	        $iCount = 0;
			foreach( $oXml->xpath('/rss/channel/item/description') as $sDescription ) {
				$sDescription = (string) $sDescription;
				list( $sLeftString, $sRightString ) = explode( '=', $sDescription );
				unset( $sLeftString );
				preg_match( '/[-+]?[0-9]*\.?[0-9]+/', $sRightString, $aMatches );
				$this->aRates[$iCount]['rate'] = 1 / (float) $aMatches[0];
				++$iCount;
			}
		}
		
		return $this->aRates;
	}
}