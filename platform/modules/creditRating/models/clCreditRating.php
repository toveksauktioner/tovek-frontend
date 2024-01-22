<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 15/09/2014 by Renfors
 * Description:
 * Main file for managing credit rating checkup
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */


class clCreditRating extends clModuleBase {
	
	public $iRatingId;
	public $oResultXML;

	public function __construct() {
		$this->sModuleName = 'CreditRating';
		$this->sModulePrefix = 'creditRating';
		
		$this->oDao = clRegistry::get( 'clCreditRatingDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/creditRating/models' );
		
		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}
	
	/**
	 * Function to be called upon before the credit rating service is called.
	 *
	 * Will set info about the credit rating call to be accessed for followup
	 */
	public function initCheckup( $aParams ) {
		$aParams += array(
			'ratingService' => null,
			'ratingServiceFunction' => null,
			'ratingStatus' => 'requested',
			'ratingInData' => null,
			'ratingCreated' => date( 'Y-m-d H:i:s' ),
			'ratingUserId' => null
		);
		
		$this->iRatingId =  $this->create( $aParams );
	}
	
	/**
	 * Function to be called upon to validate the return data against other values (i.e. user provided info)
	 *
	 * Validation process to validate data.
	 *
	 * INDATA:
	 * aData is a formatted array with which values in oResultXML to check
	 * Example:
	 * * $aData = array(
	 * * 	'NewDataSet' => array(
	 * * 		'GETDATA_RESPONSE' => array(
	 * * 			'FIRST_NAME' => array(
	 * * 				'type' => 'IN',								IN or EQUALS
	 * * 				'value' => 'someValue',				
	 * * 				'onFail' => 'deny'						Different levels of fail. Can be "deny" or "warning" or any other key
	 * * 			)
	 * * 		)
	 * * 	);
	 *
	 * The example aData will check if $oResultXML->NewDataSet->GETDATA_RESPONSE->FIRST_NAME contains the string "someValue".
	 * On fail it will return an array with the key "deny" set to true
	 * 
	 * RETURN VALUES:
	 * Will return true if all is OK.
	 * Will return array with onFail types set to true if invalid.
	 * Will return false if there is no result xml.
	 */
	public function validateData( $aData, $oResultXML = null ) {

		if( empty($oResultXML) ) $oResultXML = $this->oResultXML;
		
		if( !empty($oResultXML) ) {
			$mValid = false;
			
			foreach( $aData as $key => $entry ) {
				if( is_array($entry) ) {
					if( !isset($entry['type']) ) {
						$mSubResult = $this->validateData( $entry, $oResultXML->$key );

						if( is_array($mSubResult) ) {
							if( is_array($mValid) ) {
								$mValid += $mSubResult;
							} else {
								$mValid = $mSubResult;
							}
						}
					} else {
						$aSearch 	= array( '-',	' ' );
						$aReplace = array( '',	''	);
						
						$sResultData = mb_strtolower( str_replace($aSearch, $aReplace, $oResultXML->$key) );
						$sValidateData = mb_strtolower( str_replace($aSearch, $aReplace, $entry['value']) );
						
						switch( $entry['type'] ) {
							case 'IN':
								if( !strstr($sResultData, $sValidateData) ) $mValid[$entry['onFail']] = true;
								#echo $sValidateData . " IN " . $sResultData . "</br>";
								break;
							
							case 'EQUALS':
							default:
								if( $sResultData != $sValidateData ) $mValid[$entry['onFail']] = true;
								#echo $sValidateData . " = " . $sResultData . "</br>";								
						}
						
					}
				}
			}
			
			if( is_array($mValid) ) {
				return $mValid;
			} else {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Function to be called upon after the credit rating service is called.
	 *
	 * Will set the followup info about the checkup
	 */
	public function finishCheckup( $iRatingId, $aParams ) {
		$aParams += array(
			'ratingStatus' => 'success',
			'ratingResultData' => null
		);
		
		return $this->update( $iRatingId, $aParams );
	}
	
}
