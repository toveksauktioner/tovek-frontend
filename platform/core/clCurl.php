<?php

/**
 * $Id: clCurl.php 1398 2014-03-31 14:41:09Z mikael $
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author: mikael $
 * @version		Subversion: $Revision: 1398 $, $Date: 2014-03-31 16:41:09 +0200 (må, 31 mar 2014) $
 */

class clCurl {
	
	public $aParams;
	public $aParamsDefault;
	
	// Send
	public $sEndpoint;
	public $aSendHeaders = array();	
	
	// Receive
	public $aLastRespons = array();
	
	public function __construct( $aParams = array() ) {
		// Is cURL installed?
		if( !function_exists('curl_init') ) {
			throw new Exception( 'Sorry cURL is not installed!' );
		}
		
		// Options
		$this->aParamsDefault = $aParams + array(
			// Default option params
			'header' => false,
			'returntransfer' => true,
			'encoding' => 'UTF-8',
			'timeout' => 20
		);
		$this->aParams = $this->aParamsDefault;
	}
	
	public function setEndpoint( $sURL ) {
		$this->sEndpoint = $sURL;
		// Determ port
		$this->aParams['port'] = !empty($this->aParams['port']) ? $this->aParams['port'] : ( strpos($sURL, 'https') !== false ? 443 : 8080 );
		return $this;
	}
	
	public function setParams( $aParams = array(), $bOverride = false ) {		
		$this->aParams = $bOverride === false ? array_merge( $this->aParams, $aParams ) : $aParams;
		return $this;
	}
	
	private function getOptions( $aAdditionalParams = array() ) {
		if( empty($this->aParams['httpheader']) && !empty($this->aSendHeaders) ) {
			$this->aParams['httpheader'] = $this->getSendHeaders();
		}		
		$aOptions = array();
		foreach( array_merge( $this->aParams, $aAdditionalParams ) as $sKey => $sValue ) {
			$sCurlKey = 'CURLOPT_' . strtoupper( $sKey );			
			$aOptions[ constant( $sCurlKey ) ] = $sValue;
		}		
		return $aOptions;
	}
	
	public function addSendHeaders( $aHeaders = array() ) {
		$this->aSendHeaders = array_merge( $this->aSendHeaders, $aHeaders );
		return $this;
	}
	
	public function setSendHeader( $sHeader, $sValue ) {
		$this->aSendHeaders[$sHeader] = $sValue;
		return $this;
	}
	
	private function getSendHeaders( $aAdditionalHeaders = array() ) {
		$aHeaders = array();
		foreach( array_merge( $this->aSendHeaders, $aAdditionalHeaders ) as $sHeader => $sValue ) {
			$aHeaders[] = $sHeader . ': ' . $sValue;
		}
		return $aHeaders;
	}
	
	public function get( $sURL = '' ) {
		$this->setParams( array(
			'url' => !empty($sURL) ? $sURL : $this->sEndpoint
		) );		
		return $this->send();
	}
	
	public function post( $aData, $sURL = '', $sFunction = null ) {
		if( $sFunction !== null ) {
			$mPostfields = call_user_func( $sFunction, $aData );
			$iPost = 1;
		} else {
			$mPostfields = $aData;
			$iPost = count( $aData );
		}
		$this->setParams( array(
			'url' => !empty($sURL) ? $sURL : $this->sEndpoint,
			'post' => $iPost,
			'postfields' => $mPostfields
		) );
		return $this->send();
	}
	
	public function put( $aData, $sURL = '', $sFunction = null ) {
		if( $sFunction === null ) $sFunction = 'json_encode';		
		$this->setParams( array(
			'url' => !empty($sURL) ? $sURL : $this->sEndpoint,
			'post' => 1,
			'postfields' => call_user_func( $sFunction, $aData ),
			'customrequest' => 'PUT'
		) );
		return $this->send();
	}
	
	public function delete( $sURL = '' ) {
		$this->setParams( array(
			'url' => !empty($sURL) ? $sURL : $this->sEndpoint,
			'customrequest' => 'DELETE'
		) );		
		return $this->send();
	}
	
	private function send() {		
		$this->aLastRespons = array();
		
		try {
			$rCurl = curl_init();
			
			// Options
			curl_setopt_array( $rCurl, $this->getOptions() );
			
			// Respons
			$this->aLastRespons = array(
				'data' => array(
					'raw' => curl_exec( $rCurl )
				),
				'error' => array(
					'number' => curl_errno( $rCurl ),
					'message' => curl_error( $rCurl )
				),			
				'info' => curl_getinfo( $rCurl )
			);
			curl_close( $rCurl );
			
			// Reset params
			$this->aParams = $this->aParamsDefault;
			
			/**
			 * Error handling
			 */
			if( $this->aLastRespons['error']['number'] > 0 ) {			
				// Error occurred
				$this->error( $this->aLastRespons );
				return false;
			} else {
				// No error, remove error from respons
				unset( $this->aLastRespons['error'] );
			}
			
			/**
			 * Format data
			 */
			if( !empty($this->aLastRespons['data']['raw']) && $this->aParams['header'] === true ) {
				$aRespons = explode( "\r\n\r\n", $this->aLastRespons['data']['raw'], 2 );
				$this->aLastRespons['data']['headers'] = $this->formatHeaders( $aRespons[0] );			
				$this->aLastRespons['data']['content'] = $aRespons[1];
				
			} elseif( empty($this->aLastRespons['data']['raw']) && $this->aParams['header'] === true ) {
				$this->aLastRespons['data']['headers'] = $this->formatHeaders( $this->aLastRespons['data']['raw'] );
				$this->aLastRespons['data']['content'] = null;
				
			} else {
				$this->aLastRespons['data']['headers'] = null;
				$this->aLastRespons['data']['content'] = $this->aLastRespons['data']['raw'];
			}
			
			/**
			 * Data content type handling 
			 */
			if( !empty($this->aLastRespons['info']['content_type']) && !empty($this->aLastRespons['data']['content']) ) {
				switch( $this->aLastRespons['info']['content_type'] ) {
					case 'application/json':
					case 'application/json; charset=utf-8':
						$this->aLastRespons['data']['content'] = json_decode( trim( $this->aLastRespons['data']['content'] ) );
						break;
					default:
						// Just trim
						$this->aLastRespons['data']['content'] = trim( $this->aLastRespons['data']['content'] );
						break;
				}
			}
			
		} catch( Exception $oException ) {
			$this->aLastRespons['error'] = array(
				'message' => $oException->getMessage()
			);
			
			if( $GLOBALS['logErrors'] ) {			
				// Log
				clFactory::loadClassFile( 'clLogger' );
				clLogger::log( $oException->getMessage(), 'curlException.log' );
			}
			
			if( $GLOBALS['debug'] ) throw new Exception( $oException->getMessage() );
			
		}
		
		// Success
		return $this->aLastRespons;
	}
		
	public function formatHeaders( $sHeaders ) {		
		$aHeaders = array();
		
		foreach( explode("\r\n", $sHeaders) as $iLine => $sLine ) {
			$aLine = explode( ':', $sLine );
			$sHeader = trim( $aLine[0] );
			unset( $aLine[0] );
			$sContent = trim( implode( ':', $aLine ) );
			$aHeaders[ $sHeader ] = $sContent;
		}
		
		return $aHeaders;
	}
	
	public function error( $aRespons ) {		
		if( $GLOBALS['logErrors'] ) {			
			// Log
			clFactory::loadClassFile( 'clLogger' );
			clLogger::log( $aRespons, 'curlError.log' );
		}
		
		if( $GLOBALS['debug'] ) throw new Exception( sprintf( 'Error: (%s) %s', $aRespons['error']['number'], $aRespons['error']['message'] ) );
		else return $this;
	}
	
}