<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 15/09/2014 by Renfors
 * Description:
 * File for managing credit rating with Creditsafe
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */

require_once PATH_MODULE . '/creditRating/models/clCreditRating.php';
require_once PATH_MODULE . '/creditRating/config/cfCreditsafe.php';

class clCreditRatingCreditsafe extends clCreditRating {

	protected $sUsername 		= CREDITSAFE_USERNAME;
	protected $sPassword 		= CREDITSAFE_PASSWORD;
	protected $sLanguage 		= CREDITSAFE_LANGUAGE;
	protected $sWebservice 	= CREDITSAFE_WEBSERVICE;
	protected $oSoapClient;
	
	public function initSoap( $sWSDL, $aOptions = array() ) {
		/**
		 * Run the SoapClient constructor
		 */
		
		$this->oSoapClient = new SoapClient( $sWSDL, $aOptions + array(
			'soap_version'	=> CREDITSAFE_SOAP_VERSION,
			'trace'					=> CREDITSAFE_SOAP_TRANCE
		) );
	}
	
	public function callCreditsafeFunction( $sName, $xmlVar, $sSearchNr = null ) {
		/**
		 * Calls a method in the creditsafe api.
		 * All creditsafe method takes a object as param so
		 * this functions converts an XML string to an object and passes it
		 */
		try {
			$this->initCheckup( array(
				'ratingService' => 'Creditsafe',
				'ratingServiceFunction' => $sName,
				'ratingStatus' => 'requested',
				'ratingSearchPin' => $sSearchNr,
				'ratingInData' => $xmlVar
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
		 * aAccount shall contain account data to creditsafe. 
		 * 	Required fields are: 
		 *		Username
		 *		Password
		 *		Language
		 *	Optional fields are:
		 *		TransactionId
		 * The function will take the default values from the config
		 * if the required fields doesn´t exist in the aAccount-array
		 */
		
		$aAccount += array(
			'UserName' => $this->sUsername,
			'Password' => $this->sPassword,
			'Language' => $this->sLanguage
		);
		
		return '<account>' . $this->generateDataXml( $aAccount ). '</account>';
	}
	
	public function getCreditsafeFunctions() {
		/**
		 * Returns the available methods from the creditsafe api
		 */

		return $this->oSoapClient->__getFunctions();
	}
	
	public function getLastRequest() {
		/**
		 * Returns the last request sent to creditsafe
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
		
		switch( $sRejectedCode ) {
			case '6':
				return _( 'Incorrect info XML code' );
			case '7':
				return _( 'No access to this product' );
			case '8':
				return _( 'No more reports/points' );
			case '9':
				return _( 'Wrong username or password' );
			case '11':
				return _( 'Wrong block' );
			case '12':
				return _( 'Wrong template' );
			case '13':
				return _( 'Security validation not ok' );
			case '14':
				return _( 'An error occurred, please try again later' );
			case '15':
				return /*_(*/ 'No match record' /*)*/;
			case '16':
				return _( 'Your account does not allow information access to this company' );
			case '17':
				return _( 'No access to this company type' );
			case 'C30':
				return _( 'Bankruptcy closed' );
			case 'C31':
				return _( 'Liquidation concluded' );
			case 'C32':
				return _( 'Company is removed' );
			case 'C33':
				return _( 'Fusion is closed' );
			case 'C34':
				return _( 'Company is removed' );
			case 'C35':
				return _( 'Removed' );
			case 'P6':
				return _( 'Dead' );
			case 'P7':
				return _( 'Emigrated' );
			case 'P8':
				return _( 'Protected' );
			case 'P9':
				return _( 'Blocked (by Creditsafe)' );
			case 'P38':
				return _( 'Blocked (by SPAR)' );
			case 'P39':
				return _( 'Social security number change' );
			default:
				return _( 'Unknown error' );
		}
	}
	
	
	// GetData functions
	
	public function getDataBySecure($aData, $aAccount = array() ) {
		/**
		 * aAccount shall contain account data to creditsafe. 
		 * 	Required fields are: 
		 *		Username
		 *		Password
		 *		Language
		 *	Optional fields are:
		 *		TransactionId
		 * The function will take the default values from the config
		 * (setLoginData overrides the default values)
		 * if the required fields doesn´t exist in the aAccount-array
		 *
		 * aData contains array with following values
		 * 'Block_Name' is the blockname provided from creditsafe.
		 * 'SearchNumber' is either a personal safety code or a ORG nr
		 */
		
		try {
			$this->initSoap( CREDITSAFE_GETDATA_WDSL );
			
			$sXml = 
			'<GetDataBySecure xmlns="' . $this->sWebservice . '/getdata/">
				<GetData_Request>
					' . $this->generateAccountXml( $aAccount ) . '
					' . $this->generateDataXml( $aData ) . '
				</GetData_Request>
			</GetDataBySecure>';
	
			$oSoapReturn = $this->callCreditsafeFunction( 'GetDataBySecure', $sXml, $aData['SearchNumber'] );
				
			if( isset($oSoapReturn->GetDataBySecureResult->Error) ) {			
				$this->finishCheckup( $this->iRatingId, array(
					'ratingStatus' => 'fail',
					'ratingResultData' => $oSoapReturn->GetDataBySecureResult->Error->Reject_text
				) );
				
				return false;
			} else {
				$oReturnXML = simplexml_load_string( $oSoapReturn->GetDataBySecureResult->Parameters->any );
	
				$this->finishCheckup( $this->iRatingId, array(
					'ratingStatus' => 'success',
					'ratingResultData' => $oReturnXML->asXML()
				) );
				
				$this->oResultXML = $oReturnXML;
				return true;
			}
		} catch( Throwable $oThrowable ) {
			echo '<pre>';
			var_dump( $oThrowable );
			die();
			
		} catch( Exception $oException ) {
			echo '<pre>';
			var_dump( $oException );
			die();
			
		}
	}
	
	
	// CAS functions
	
	public function getTemplateList( $sReportType = 'NotSet', $aAccount = array() ) {
		/**
		 * aAccount shall contain account data to creditsafe. 
		 * 	Required fields are: 
		 *		Username
		 *		Password
		 *		Language
		 *	Optional fields are:
		 *		TransactionId
		 * The function will take the default values from the config
		 * (setLoginData overrides the default values)
		 * if the required fields doesn´t exist in the aAccount-array
		 *
		 * sReportType could either contain 'NotSet', 'Company' or 'Consumer'
		 */
		
		$this->initSoap( CREDITSAFE_CAS_WDSL );
		
		$sXml = 
		'<GetTemplateList xmlns="' . $this->sWebservice . '/CAS/">
			<cas_service>
				' . $this->generateAccountXml( $aAccount ) . '
				<ReportType>' . $sReportType . '</ReportType>
			</cas_service>
		</GetTemplateList>';	
		
		return $this->callCreditsafeFunction( 'GetTemplateList', $sXml );
	}
	
	public function casPersonService( $aData = array(), $aAccount = array() ) {
		/**
		 * aAccount shall contain account data to creditsafe. 
		 * 	Required fields are: 
		 *		Username
		 *		Password
		 *		Language
		 *	Optional fields are:
		 *		TransactionId
		 * The function will take the default values from the config
		 * (setLoginData overrides the default values)
		 * if the required fields doesn´t exist in the aAccount-array
		 * 
		 * aData shall contain data about the person.
		 *	Required fields are:
		 *		SearchNumber
		 *		Templates
		 *	Optional fields are:
		 *		FirstName
		 *		LastName
		 *		Address1
		 *		ZIP
		 *		Town
		 */
		
		$this->initSoap( CREDITSAFE_CAS_WDSL );
		
		$sXml = '
			<CasPersonService xmlns="' . $this->sWebservice . '/CAS/">
				<cas_person>
					' . $this->generateAccountXml( $aAccount ) . '
					' . $this->generateDataXml( $aData ) . '
				</cas_person>
			</CasPersonService>';

		$oSoapReturn = $this->callCreditsafeFunction( 'casPersonService', $sXml, $aData['SearchNumber'] );
		
		if( isset($oSoapReturn->CasPersonServiceResult->Status) && ($oSoapReturn->CasPersonServiceResult->Status != '1') ) { 	# 1 = godkänd av Kreditmall			
			$this->finishCheckup( $this->iRatingId, array(
				'ratingStatus' => 'fail',
				'ratingResultData' => $oSoapReturn->CasPersonServiceResult->ErrorList->ERROR->Reject_text
			) );
			
			return false;
		} else {		
			$oReturnXML = $this->generateDataXml( $oSoapReturn );
			
			$this->finishCheckup( $this->iRatingId, array(
				'ratingStatus' => 'success',
				'ratingResultData' => $oReturnXML
			) );
			
			$this->oResultXML = $oReturnXML;
			return true;
		}
	}
	
	public function casCompanyService( $aData = array(), $aAccount = array() ) {
		/**
		 * aAccount shall contain account data to creditsafe. 
		 * 	Required fields are: 
		 *		Username
		 *		Password
		 *		Language
		 *	Optional fields are:
		 *		TransactionId
		 * The function will take the default values from the config
		 * (setLoginData overrides the default values)
		 * if the required fields doesn´t exist in the aAccount-array
		 * 
		 * aData shall contain data about the person.
		 *	Required fields are:
		 *		SearchNumber
		 *		Templates
		 *	Optional fields are:
		 *		FirstName
		 *		LastName
		 *		Address1
		 *		ZIP
		 *		Town
		 */
		
		$this->initSoap( CREDITSAFE_CAS_WDSL );

		$sXml = '
			<CasCompanyService xmlns="' . $this->sWebservice . '/CAS/">
				<cas_company>
					' . $this->generateAccountXml( $aAccount ) . '
					' . $this->generateDataXml( $aData ) . '
				</cas_company>
			</CasCompanyService>';	

		$oSoapReturn = $this->callCreditsafeFunction( 'casCompanyService', $sXml, $aData['SearchNumber'] );
		
		if( isset($oSoapReturn->CasCompanyServiceResult->Status) && ($oSoapReturn->CasCompanyServiceResult->Status != '1') ) { 	# 1 = godkänd av Kreditmall			
			$this->finishCheckup( $this->iRatingId, array(
				'ratingStatus' => 'fail',
				'ratingResultData' => $oSoapReturn->CasCompanyServiceResult->ErrorList->ERROR->Reject_text
			) );
			
			return false;
		} else {		
			$oReturnXML = $this->generateDataXml( $oSoapReturn );
			
			$this->finishCheckup( $this->iRatingId, array(
				'ratingStatus' => 'success',
				'ratingResultData' => $oReturnXML
			) );
			
			$this->oResultXML = $oReturnXML;
			return true;
		}
		
	}
	

}