<?php

require_once PATH_CORE . '/htmlpurifier/library/HTMLPurifier.auto.php';

class clHtmlPurifier{
	
	public $oConfig;
	
	private $oPurifier;
	
	public function __construct( $aParams = array() ) {
		$this->setConfig( $aParams );
		$this->oPurifier = new HTMLPurifier( $this->oConfig );
	}
	
	public function purify( $html ) {
		if( is_array($html) ) {
			return $this->oPurifier->purifyArray( $html, $this->oConfig );
		} else {
			return $this->oPurifier->purify( $html, $this->oConfig );
		}
	}
	
	public function setConfig( $aParams = array() ) {
		$aParams += array(
			'allowed' => null,
			'allowedAttributes' => null,
			'allowedElements' => null,
			'doctype' => null,
			'encoding' => null
		);
		
		$this->oConfig = HTMLPurifier_Config::createDefault();
		if( $aParams['allowed'] !== null ) $this->oConfig->set( 'HTML.Allowed', $aParams['allowed'] );
		if( $aParams['allowedAttributes'] !== null ) $this->oConfig->set( 'HTML.AllowedAttributes', $aParams['allowedAttributes'] );
		if( $aParams['allowedElements'] !== null ) $this->oConfig->set( 'HTML.AllowedElements', $aParams['allowedElements'] );
		if( $aParams['doctype'] !== null ) $this->oConfig->set( 'HTML.Doctype', $aParams['doctype'] );
		if( $aParams['encoding'] !== null ) $this->oConfig->set( 'Core.Encoding', $aParams['encoding'] );
	}
	
}
