<?php

require_once PATH_MODULE . '/api/config/cfApi.php';

class clApi {
	function call( $sService, $sMethod = 'GET', $aData = null ) {
		$sUrl = API_RESOURCE . $sService;

		// POST and PUT needs data to send - otherwise stop processing
		if( in_array($sMethod, ['POST', 'PUT']) && empty($aData) ) return false;

		// Make data into query string
		if( !empty($aData) ) $sHttpQuery = http_build_query( $aData );

		// Curl init and basic params
		$oCurl = curl_init();
		curl_setopt( $oCurl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $oCurl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
        curl_setopt( $oCurl, CURLOPT_USERPWD, API_USER . ":" . API_PASSKEY );
        curl_setopt( $oCurl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $oCurl, CURLOPT_TIMEOUT, 30 );
 
 		// Handle methods
        switch( $sMethod ) {
        	case 'POST':
        		curl_setopt( $oCurl, CURLOPT_POST, 1 );
				curl_setopt( $oCurl, CURLOPT_POSTFIELDS, $sHttpQuery );
				break;

			case 'PUT':
				curl_setopt( $oCurl, CURLOPT_CUSTOMREQUEST, "PUT" );
				curl_setopt( $oCurl, CURLOPT_POSTFIELDS, $sHttpQuery );
				break;

			case 'DELETE':
				curl_setopt( $oCurl, CURLOPT_CUSTOMREQUEST, "DELETE" );
				break;

        	case 'GET':
        	default:
        		if( !empty($aData) ) $sUrl .= '?' . $sHttpQuery;
        }

        // Set url (might have changed accordingly to method)
    	curl_setopt( $oCurl, CURLOPT_URL, $sUrl );

    	// Debug options
    	$fp = fopen( PATH_LOG . '/clApiCurl.txt', 'w' );
		curl_setopt( $oCurl, CURLOPT_VERBOSE, 1 );
   		curl_setopt( $oCurl, CURLOPT_STDERR, $fp );

    	// Get respons and return it
		$oResponse = curl_exec( $oCurl );

		// Post debug
		// $info = curl_getinfo( $oCurl );
		// return $info;

		curl_close( $oCurl );
		fclose( $fp );

		return json_decode( $oResponse, true );
	}
}