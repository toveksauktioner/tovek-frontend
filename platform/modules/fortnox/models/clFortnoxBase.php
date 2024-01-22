<?php

require_once PATH_MODULE . '/fortnox/config/cfFortnox.php';
require_once PATH_CORE . '/clCurl.php';

abstract class clFortnoxBase {

	public $oAcl;

	public $sModuleName;
	public $sModulePrefix;
	public $sResourceName;
	public $sPropertyName;

	public $oDao;

	protected $oCurl;
	protected $sAccessToken;

	protected $aEvents = array();
	protected $oEventHandler;

	protected $aNotifications = array();
	protected $oNotification;

	protected $oDataValidation;
	protected $oErrorHandler;
	protected $oLogger;

	protected function initBase() {
		/**
		 * Extenden security
		 */
		$oUser = clRegistry::get( 'clUser' );
		$aAllowedAccesses = array_intersect( array('super','admin'), array_keys($oUser->aGroups) );
		if( empty($aAllowedAccesses) ) throw new Exception( _( 'No access - Fortnox' ) );
		$this->setAcl( $oUser->oAcl );

		/**
		 * Event, Notification & Logger
		 */
		$this->oEventHandler = clRegistry::get( 'clEventHandler' );
		$this->oEventHandler->addListener( $this, $this->aEvents );
		$this->oNotification = clRegistry::get( 'clNotificationHandler' );
		$this->oDataValidation = clRegistry::get( 'clDataValidation' );
		$this->oErrorHandler = clRegistry::get( 'clErrorHandler' );
		$this->oLogger = clRegistry::get( 'clLogger' );

		/**
		 * cUrl handler
		 */
		$this->oCurl = clRegistry::get( 'clCurl', null, array(
			'ssl_verifypeer' => false,
			'ssl_verifyhost' => false
		) );
		$this->oCurl->setEndpoint( FORTNOX_ENDPOINT );

		if( empty($this->sModuleName) ) $this->sModuleName = 'Fortnox';
		if( empty($this->sModulePrefix) ) $this->sModulePrefix = 'fortnox';

		$this->sAccessToken = FORTNOX_ACCESS_TOKEN;
	}

	public function setAcl( $oAcl ) {
		$this->oAcl = $oAcl;
	}
	
	public function log( $mData, $sFilename = null ) {
		$sFilename = $sFilename != null ? $sFilename : 'fortnox.log';
		return $this->oLogger->log( $mData, $sFilename );
	}
	
	/**
	 * Main api call function
	 */
	protected function apiCall( $sRequestMethod, $sResource, $aBody = null ) {
		if( empty($this->sAccessToken) ) {
			if( !$this->getAccessToken() ) {
				throw new Exception( _( 'Authentication problems' ) );
			}
		}

		try {
			// Complement endpoint
			$this->oCurl->setEndpoint( FORTNOX_ENDPOINT . $sResource );

			$aOptions = array(
				'Access-Token' => $this->sAccessToken,
				'Client-Secret' => FORTNOX_CLIENT_SECRET,
				'Content-Type' => FORTNOX_CONTENT_TYPE,
				'Accept' => FORTNOX_ACCEPTS
			);
			$this->oCurl->addSendHeaders( $aOptions );

			$mReturn = null;
			switch( $sRequestMethod ) {
				case 'GET': 	$mReturn = $this->oCurl->get(); break;
				case 'POST': 	$mReturn = $this->oCurl->post( $aBody ); break;
				case 'PUT': 	$mReturn = $this->oCurl->put( $aBody ); break;
				case 'DELETE': 	$mReturn = $this->oCurl->delete(); break;
			}

			if( !empty($mReturn['data']['content']->ErrorInformation) ) {
		 		$this->errorHandling( $mReturn['data']['content']->ErrorInformation );

				$sErrorCode = _( 'No code' );
				$sErrorMessage = _( 'No message' );
				// Error response can have varied form of varible names.
				// Reported to Fortnox but no change right now
				if( !empty($mReturn['data']['content']->ErrorInformation->code) ) {
					$sErrorCode = $mReturn['data']['content']->ErrorInformation->code;
				} else if( !empty($mReturn['data']['content']->ErrorInformation->Code) ) {
					$sErrorCode = $mReturn['data']['content']->ErrorInformation->Code;
				}
				if( !empty($mReturn['data']['content']->ErrorInformation->message) ) {
					$sErrorMessage = $mReturn['data']['content']->ErrorInformation->message;
				} else if( !empty($mReturn['data']['content']->ErrorInformation->Message) ) {
					$sErrorMessage = $mReturn['data']['content']->ErrorInformation->Message;
				}

				$this->oNotification->setError( $sErrorMessage . ' (' . _( 'Error code' ) . ': ' . $sErrorCode . ')' );

			} else if( !empty($mReturn['info']['http_code']) ) {
				if( ($mReturn['info']['http_code'] == '200') || ($mReturn['info']['http_code'] == '201') ) {
					/**
					 * Successful
					 * 200 - OK - A resource has been returned
					 * 201 - Created - A resource has been created
					 */
					if( isset($mReturn['data']['content']->{ucfirst($this->sPropertyName)}) ) {
					 $aData = &$mReturn['data']['content']->{ucfirst($this->sPropertyName)};
					} else {
					 $aData = &$mReturn['data']['content']->{ucfirst($this->sResourceName)};
				 	}

					$aMeta = &$mReturn['data']['content']->MetaInformation;

					return json_decode( json_encode( $aData ), true );

				} else if( $mReturn['info']['http_code'] == '204' ) {
					/**
					 * Successful
					 * 204 - Success - A resource has been removed
					 */
					 return true;
				} elseif( ($mReturn['info']['http_code'] == '400') || ($mReturn['info']['http_code'] == '403') || ($mReturn['info']['http_code'] == '404') ) {
					/**
					 * Unsuccessful
					 * 400 - Bad Request - The request cannot be fulfilled due to bad syntax
					 * 403 - Forbidden - Failed authentication
					 * 404 - Not Found - The requested resource could not be found
					 */
					$this->errorHandling( $mReturn['data']['content']->ErrorInformation );
				} else if( $mReturn['info']['http_code'] == '500' ) {
					/**
					 * Unsuccessful
					 * 500 - 	Internal Server Error - An error occured at our end
					 */
					 $this->oNotification->setError( _('Fortnox service unavailable') );
				}
			}

			return false;

		} catch( Exception $oException ) {
			$this->exceptionHandling( $oException );
		}
	}

	/*
	* arrangeData takes data in the structure used by Aroma and arranges it in the structure used by Fortnox resources
	* The $aAromaData parameter is the raw data from Aroma (i.e. from a form or database readout)
	* The $aStructure is an array with the Aroma data fields as keys and data in an array with the following fields:
	* - 'field' - (mandatory) - The value should be the Fortnox field Name
	* - 'values' - (optional) - An array with the possible Aroma values as keys and the Fortnox values as value
	*/
	public function arrangeData( $aAromaData, $aStructure ) {
		$aArrangedData = array();
		
		foreach( $aStructure as $sAromaKey => $aField ) {
			if( !empty($aAromaData[$sAromaKey]) ) {
				
				if( !empty($aField['values']) && isset($aField['values'][ $aAromaData[$sAromaKey] ]) ) {
					$aArrangedData[ $aField['field'] ] = $aField['values'][ $aAromaData[$sAromaKey] ];
				} else {
					$aArrangedData[ $aField['field'] ] = $aAromaData[$sAromaKey];
				}
				
			}
		}
		
		return $aArrangedData;
	}
	
	/*
	* Converts data keys
	* @param aData The data whose keys should be converted
	* @param aDataDict Fortnox resource dataDict
	* @param sEntity Entity in Fortnox resource dataDict
	* @param sDirection(e.g. 'toFortnox', 'fromFortnox') 
	*/
	public function convertDataKeys( $aData, $aDataDict, $sEntity = null, $sDirection = 'toFortnox' ) {
		$aConvertedData = array();
		
		// DataDict by entity
		$aReferenceDict = $sEntity !== null ? $aDataDict[ $sEntity ] : current($aDataDict);
		
		// Reference array
		$aReference = array();
		foreach( $aReferenceDict as $sField => $aParams ) {
			if( empty($aParams['alias']) ) continue;
			if( $sDirection == 'toFortnox' ) $aReference[ $sField ] = $aParams['alias'];
			else $aReference[ $aParams['alias'] ] = $sField;
		}
		
		foreach( $aData as $sKey => $mValue ) {
			if( in_array($sKey, $aReference) ) $aConvertedData[ array_search($sKey, $aReference) ] = $mValue;
			elseif( array_key_exists($sKey, $aReference) ) $aConvertedData[ $aReference[$sKey] ] = $mValue;
		}
		
		return $aConvertedData;
	}
	
	/**
	 * This function is only called once per auth code
	 */
	protected function getAccessToken() {
		if( !empty($this->sAccessToken) ) return true;
		if( FORTNOX_AUTH_CODE == '' ) return false;

		try {
			$aOptions = array(
				'Authorization-Code' => FORTNOX_AUTH_CODE,
				'Client-Secret' => FORTNOX_CLIENT_SECRET,
				'Content-Type' => FORTNOX_CONTENT_TYPE,
				'Accept' => FORTNOX_ACCEPTS
			);
			$this->oCurl->addSendHeaders( $aOptions );
			$this->oCurl->get( FORTNOX_ENDPOINT );
			
			// Log here to ensure no data loss
			$this->log( $this->oCurl->aLastRespons, 'fortnoxCurl.log' );
			
			if( in_array($this->oCurl->aLastRespons['info']['http_code'], array('200', '201')) ) {
				$sAccessToken = $this->aLastRespons['data']['content']->Authorization->AccessToken;

				$sFileContent = file_get_contents( PATH_MODULE . '/fortnox/config/cfFortnox.php' );

				$sCurrent = "define( 'FORTNOX_ACCESS_TOKEN', '' );";
				$sReplacement = "define( 'FORTNOX_ACCESS_TOKEN', '" . $sAccessToken . "' );";
				$sFileContent = str_replace( $sCurrent, $sReplacement, $sFileContent );

				$sCurrent = "define( 'FORTNOX_AUTH_CODE', '" . FORTNOX_AUTH_CODE . "' );";
				$sReplacement = "define( 'FORTNOX_AUTH_CODE', '' );";;
				$sFileContent = str_replace( $sCurrent, $sReplacement, $sFileContent );

				if( !file_put_contents( PATH_MODULE . '/fortnox/config/cfFortnox.php', $sFileContent ) ) {
					return false;
				}
				return true;
			}

		} catch( Exception $oException ) {
			$this->exceptionHandling( $oException );
		}
		return false;
	}

	/*
	* GET is the read function.
	* If $mPrimaryId is given the Fortnox object with that primary id is fetched. Otherwise a list of object limited count paramaters
	* $sAction can be given in some cases and is specified by the Fortnox documentation on each resource
	*/
	public function get( $mPrimaryId = null, $sAction = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		if( !empty($sAction) && !empty($mPrimaryId) ) {
			/**
			 * Fortnox have a few functions that is triggered by actions through the API.
			 * These actions is made with either a PUT request or a GET request, in which
			 * the action is provided in the URL.
			 */
	 		return $this->apiCall( 'GET', $this->sResourceName . '/' . $mPrimaryId . '/' . $sAction );
		}

		if( !empty($mPrimaryId) ) return $this->apiCall( 'GET', $this->sResourceName . '/' . $mPrimaryId );

		return $this->apiCall( 'GET', $this->sResourceName );
	}

		/*
		* POST is the insert function.
		* $aData is validated against the oDao->aDataDict
		*/
	public function post( $aData ) {
		$aErr = array();

		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$aErr = $this->oDataValidation->validate( $aData, $this->oDao->aDataDict, array(
			'errGroup' => 'post' . $this->sModuleName
		) );

		if( empty($aErr) ) {
			if( !empty($aData['useResource']) ) unset( $aData['useResource'] );

			foreach( $aData as $key => $value ) if( empty($value) ) unset( $aData[ $key ] );
			$aFortnoxData[ ucfirst($this->sPropertyName) ] = $aData;

			return $this->apiCall( 'POST', $this->sResourceName, $aFortnoxData );
		}

		$this->oErrorHandler->setValidationError( $aErr );
		$this->oNotification->setError( clErrorHandler::getValidationError('post' . $this->sModuleName) );
		return false;
	}

	/*
	* PUT is the update function.
	* $aData is validated against the oDao->aDataDict
	* $sAction can be given (ignored if $aData is given) and is defined by Fortnox documnentation for each resource
	*/
	public function put( $mPrimaryId, $aData = null, $sAction = null ) {
		$aErr = array();

		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		if( !empty($aData) ) {
	 		$aErr = $this->oDataValidation->validate( $aData, $this->oDao->aDataDict, array(
				'errGroup' => 'post' . $this->sModuleName
			) );

			if( empty($aErr) ) {
				if( !empty($aData['useResource']) ) unset( $aData['useResource'] );

				foreach( $aData as $key => $value ) if( empty($value) ) unset( $aData[ $key ] );
				$aFortnoxData[ ucfirst($this->sPropertyName) ] = $aData;

				if( !empty($sAction) ) {
					return $this->apiCall( 'PUT', $this->sResourceName . '/' . $mPrimaryId . '/' . $sAction, $aFortnoxData );
				}
				return $this->apiCall( 'PUT', $this->sResourceName . '/' . $mPrimaryId, $aFortnoxData );
			}
		}

		if( !empty($sAction) ) {
			/**
			 * Fortnox have a few functions that is triggered by actions through the API.
			 * These actions is made with either a PUT request or a GET request, in which
			 * the action is provided in the URL.
			 */
			return $this->apiCall( 'PUT', $this->sResourceName . '/' . $mPrimaryId . '/' . $sAction );
		}

		if( !empty($aErr) ) {
			$this->oErrorHandler->setValidationError( $aErr );
			$this->oNotification->setError( clErrorHandler::getValidationError('post' . $this->sModuleName) );
		} else {
			$this->oNotification->addError( _( 'No data or action was given' ) );
		}

		return false;
	}

	public function delete( $mPrimaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->apiCall( 'DELETE', $this->sResourceName . '/' . $mPrimaryId );
	}

	private function errorHandling( $oError ) {
		$sErrorCode = _( 'No code' );
		$sErrorMessage = _( 'No message' );
		
		// Error response can have varied form of varible names.
		// Reported to Fortnox but no change right now
		if( !empty($oError->code) ) {
			$sErrorCode = $oError->code;
		} else if( !empty($oError->Code) ) {
			$sErrorCode = $oError->Code;
		}
		if( !empty($oError->message) ) {
			$sErrorMessage = $oError->message;
		} else if( !empty($oError->Message) ) {
			$sErrorMessage = $oError->Message;
		}

		if( FORTNOX_LOGGING === true ) {
			// Log error
			$this->log( sprintf( '(%s) %s', $sErrorCode, $sErrorMessage ), 'fortnoxError.log' );
		}
		if( FORTNOX_ERROR_NOTIFY === true ) {
			// Notify developer by mail
			@mail( FORTNOX_ERROR_EMAIL, 'FortnoxError',  sprintf( '(%s) %s', $sErrorCode, $sErrorMessage ) ); // TODO remove after watch period
		}
		if( FORTNOX_DEBUG === true ) {
			// Notification			
			$this->oNotification->setSessionNotifications( array(
				'dataError' => sprintf( '(%s) %s', $sErrorCode, $sErrorMessage )
			) );
			
			// Throw error
			//throw new Exception( sprintf( '%s in %s on %s', $oError->getMessage(), $oError->getFile(), $oError->getLine() ) );
		}
	}

	private function exceptionHandling( $oException ) {
		if( FORTNOX_LOGGING === true ) {
			// Log exception
			$this->log( sprintf( 'Exception: %s in %s at line %s', $oException->getMessage(), $oException->getFile(), $oException->getLine() ), 'fortnoxException.log' );

			if( FORTNOX_DEBUG === true ) {
				$this->log( 'status: ' . $this->oCheckout['status'], 'fortnoxException.log' );
			}
		}
		if( FORTNOX_ERROR_NOTIFY === true ) {
			// Notify developer by mail
			@mail( FORTNOX_ERROR_EMAIL, 'FortnoxError',  sprintf( 'Exception: %s in %s at line %s', $oException->getMessage(), $oException->getFile(), $oException->getLine() ) ); // TODO remove after watch period
		}
		if( FORTNOX_DEBUG === true ) {
			// Throw error
			//throw new Exception( sprintf( '%s in %s on %s', $oException->getMessage(), $oException->getFile(), $oException->getLine() ) );
			echo sprintf( '%s in %s on %s', $oException->getMessage(), $oException->getFile(), $oException->getLine() );
			#die();
		}
	}

}
