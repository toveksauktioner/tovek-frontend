<?php

require_once PATH_MODULE . '/vehicle/models/clVehicleLookup.php';
require_once PATH_MODULE . '/vehicle/config/cfBilvision.php';

class clVehicleLookupBilvision extends clVehicleLookup {

	protected $sUserID 		= BILVISION_USERID;
	protected $sPassword 		= BILVISION_PASSWORD;
	protected $sWebservice 	= ( BILVISION_CERTIFIED_MODE ? BILVISION_CERTIFIED_WEBSERVICE : BILVISION_UNCERTIFIED_WEBSERVICE );
	protected $sNameSpace 	= ( BILVISION_CERTIFIED_MODE ? BILVISION_CERTIFIED_NAMESPACE : BILVISION_UNCERTIFIED_NAMESPACE );
	protected $oSoapClient;

	public function initSoap( $sWSDL, $aOptions = array() ) {
		/**
		 * Run the SoapClient constructor
		 */

		$this->oSoapClient = new SoapClient( $sWSDL, $aOptions + array(
			'soap_version'	=> BILVISION_SOAP_VERSION,
			'trace'					=> BILVISION_SOAP_TRANCE
		) );
	}

	public function callBilvisionFunction( $sName, $xmlVar, $sSearchParam = null ) {
		/**
		 * Calls a method in the bilvision api.
		 * All bilvision method takes a object as param so
		 * this functions converts an XML string to an object and passes it
		 */
		try {
			$this->initCheckup( array(
				'lookupService' => 'Bilvision',
				'lookupServiceFunction' => $sName,
				'lookupStatus' => 'requested',
				'lookupSearchParam' => $sSearchParam,
				'lookupInData' => $xmlVar
			) );

			$soapReturn = $this->oSoapClient->__soapCall( $sName, array(
				new SoapVar($xmlVar, XSD_ANYXML)
			) );

			return $soapReturn;
		}
		catch (Exception $e) {
			echo $e;
		 	return false;
		}
	}

	public function generateAccountXml( $aAccount = array() ) {
		/**
		 * aAccount shall contain account data to bilvision.
		 * 	Required fields are:
		 *		UserID
		 *		Password
		 * The function will take the default values from the config
		 * if the required fields doesn´t exist in the aAccount-array
		 */

		$aAccount += array(
			'UserID' => $this->sUserID,
			'Password' => $this->sPassword
		);

		return $this->generateDataXml( $aAccount );
	}

	public function getBilvisionFunctions() {
		/**
		 * Returns the available methods from the bilvision api
		 */

		return $this->oSoapClient->__getFunctions();
	}

	public function getLastRequest() {
		/**
		 * Returns the last request sent to bilvision
		 */

		return $this->oSoapClient->__getLastRequest();
	}

	public function generateDataXml( $aData = array() ) {
		$sXml = '';
		foreach( (array) $aData as $key => $entry ) {
			if( is_string($entry) ) {
				$sXml .= '<' . $key . '>' . $entry . '</' . $key . '>';
			} else {
				$sXml .= '<' . $key . '>' . $this->generateDataXml($entry) . '</' . $key . '>';
			}
		}

		return $sXml;
	}

	public function errorHandler( $sRejectedCode ) {
		if( empty($sRejectedCode) ) return true;

		if( array_key_exists($sRejectedCode, $GLOBALS['BILVISION_ERROR_CODES']) ) {
			return $GLOBALS['BILVISION_ERROR_CODES'][ $sRejectedCode ];
		} else {
			return _( 'Unknown error' );
		}
	}


	// GetData functions

	public function getBilvisionData( $sName, $aParams, $aAccount = array() ) {
		/**
		 * aAccount shall contain account data to bilvision.
		 * 	Required fields are:
		 *		UserID
		 *		Password
		 * The function will take the default values from the config
		 * (setLoginData overrides the default values)
		 * if the required fields doesn´t exist in the aAccount-array
		 *
		 * $sName is the request type
		 * aParams contains params required by the request type
		 */

		$this->initSoap( $this->sWebservice . '?WSDL&op=' . $sName );

		$sXml =
		'<' . $sName . ' xmlns="' . $this->sNameSpace . '">
			' . $this->generateAccountXml( $aAccount ) . '
			' . $this->generateDataXml( $aParams ) . '
		</' . $sName . '>';

		// Documentation indicates functions with other than reg-nr as search value
		// Functions below are the ones available according to Documentation ver 3.06
		switch( $sName ) {
			case 'Besiktningsfraga':
			case 'Dispensfraga':
			case 'ForegaendeAgarefraga':
			case 'Grundfraga':
			case 'TeknikfragaV2':
				$sSearchParam = str_replace( array(
					' ',
					'-'
				), array(
					'',
					''
				), mb_strtoupper($aParams['Regnr']) );
				break;

			default:
				$sSearchParam = null;
		}

		$oSoapReturn = $this->callBilvisionFunction( $sName, $sXml, $sSearchParam );
		$sResultName = $sName . 'Result';

		if( isset($oSoapReturn->$sResultName->Err) ) {
			$this->finishCheckup( $this->iLookupId, array(
				'lookupStatus' => 'fail',
				'lookupResultData' => $oSoapReturn->$sResultName->Err
			) );

			return false;
		} else {
			$this->finishCheckup( $this->iLookupId, array(
				'lookupStatus' => 'success',
				'lookupResultData' => $oSoapReturn->$sResultName
			) );

			return $oSoapReturn->$sResultName;
		}

	}

}
