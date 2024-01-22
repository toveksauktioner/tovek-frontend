<?php

class clVehicleLookup extends clModuleBase {

	public $iLookupId;
	public $oResultXML;

	public function __construct() {
		$this->sModuleName = 'VehicleLookup';
		$this->sModulePrefix = 'vehicleLookup';

		$this->oDao = clRegistry::get( 'clVehicleLookupDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/vehicle/models' );

		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}

	/**
	 * Function to be called upon before the vehicle lookup service is called.
	 *
	 * Will set info about the vehicle lookup call to be accessed for followup
	 */
	public function initCheckup( $aParams ) {
		$aParams += array(
			'lookupService' => null,
			'lookupServiceFunction' => null,
			'lookupStatus' => 'requested',
			'lookupInData' => null,
			'lookupCreated' => date( 'Y-m-d H:i:s' ),
			'lookupUserId' => null
		);

		$this->iLookupId =  $this->create( $aParams );
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
	 * Function to be called upon after the vehicle lookup service is called.
	 *
	 * Will set the followup info about the checkup
	 */
	public function finishCheckup( $iLookupId, $aParams ) {
		$aParams += array(
			'lookupStatus' => 'success',
			'lookupResultData' => null
		);

		return $this->update( $iLookupId, $aParams );
	}

}
