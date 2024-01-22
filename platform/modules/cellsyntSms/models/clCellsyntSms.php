<?php

require_once PATH_MODULE . '/cellsyntSms/config/cfCellsyntSms.php';

class clCellsyntSms {

	public function __construct() {
		$this->sModuleName = 'CellsyntSms';
		$this->sModulePrefix = 'cellsyntSms';
	}

	/**
	 * Example of use:
	 * $oCellsyntSms = clRegistry::get( 'clCellsyntSms', PATH_MODULE . '/cellsyntSms/models' );
	 * $oCellsyntSms->send( '0046730860481', 'Det här är ett test..' );
	 */
	public function send( $mPhoneNo, $sMessage ) {
		$sPhoneNo = '';
		if( is_array($mPhoneNo) ) {
			foreach( $mPhoneNo as $key => $value ) {
				$mPhoneNo[$key] = $this->convertToInternational( $value );
			}
			$sPhoneNo = implode( ',', $mPhoneNo );
		} else {
			$sPhoneNo = $this->convertToInternational( $mPhoneNo );
		}

		$aData = array(
			'username' => CELLSYNT_USERNAME,
			'password' => CELLSYNT_PASSWORD,
			'destination' => $sPhoneNo,
			'originatortype' => CELLSYNT_ORIGINATOR_TYPE,
			'originator' => CELLSYNT_ORIGINATOR,
			'charset' => 'UTF-8',
			'type' => 'text',
			'text' => $sMessage,
			'allowconcat' => CELLSYNT_ALLOW_CONCAT
		);

		// Test mode: Just log the data - then return true
		if( CELLSYNT_TEST_MODE === true ) {
			clFactory::loadClassFile( 'clLogger' );
			$sLogMessage = 'Sending SMS via Cellsynt SMS: ' . print_r( $aData, true );
			clLogger::log( $sLogMessage, 'clCellsyntSms_send.log' );

			return true;
		}

		// Assemble full URL
		$sUrl = CELLSYNT_SERVICE_PONT . '?' . http_build_query($aData, '', '&');

		// Set cUrl options
		$aCurlOptions = array(
			CURLOPT_URL 			=>  $sUrl,
			CURLOPT_PORT 			=>  80,
			//CURLOPT_SSL_VERIFYPEER	=>  false,
			//CURLOPT_SSL_VERIFYHOST  =>  false,
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

		//echo '<pre>';
		//var_dump( $sContent );
		//var_dump( $iErrNo );
		//var_dump( $sError );
		//var_dump( $aHeader );
		//die;

		if( $iErrNo != 0 ) {
			// Error occurred
			return $sError . ' (' . $iErrNo . ')';
		}

		if( substr($sContent, 0, 2) == 'OK' ) {
			// Success
			$sTrackingId = substr($sContent, 5, (strlen($sContent) - 5));
			return $sTrackingId;
		} else {
			// Error occurred
			echo $sContent;
			return false;
		}

		return false;
	}

	/**
	 * This function converts a phone number
	 * to internationall format.
	 */
	public function convertToInternational( $sNumber ) {
		// First, remove all spaces
		$sNumber = preg_replace( '/\s+/', '', $sNumber );

		// International beginning?
		if( substr($sNumber, 0, 2) == '00' ) {
			// Return number, without any special characters.
			return preg_replace( '/[^0-9]/', '', $sNumber );
		}

		// Special char beginning?
		if( substr($sNumber, 0, 1) == '+' ) {
			// Return number with plus char replaced
			// and any other specail char removed.
			return preg_replace( '/[^0-9]/', '', str_replace('+', '00', $sNumber) );
		}

		// Pre-remove any special characters
		$sNumber = preg_replace( '/[^0-9]/', '', $sNumber );

		// Format to international, depending on length
		switch( strlen($sNumber) ) {
			case 9:
				$sNumber = '0046' . substr( $sNumber, 1, 8 );
				break;
			case 10:
				$sNumber = '0046' . substr( $sNumber, 1, 9 );
				break;
			case 11:
				$sNumber = '0046' . substr( $sNumber, 1, 10 );
				break;
			default:
				$sNumber = '';
				break;
		}

		// Return number
		return $sNumber;
	}

	/**
	 * Checking the phone number so it is probably a cellphone number
	 * Basically only works for swedish numbers
	 */
	public function validatePhoneNo( $sNumber ) {
		if( (mb_substr($sNumber, 0, 2) == '07') || (mb_substr($sNumber, 0, 4) == '+467') ) {
			return $this->convertToInternational( $sNumber );
		}
		return false;
	}

}
