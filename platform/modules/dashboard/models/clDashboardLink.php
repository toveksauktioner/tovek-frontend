<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/dashboard/config/cfDashboard.php';

class clDashboardLink extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'DashboardLink';
		$this->sModulePrefix = 'dashboardLink';
		
		$this->oDao = clRegistry::get( 'clDashboardLinkDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/dashboard/models' );
		
		$this->initBase();		
	}

	public function sync() {		
		// Curl options
		$aCurlOptions = array(
			CURLOPT_URL 			=>  DASHBOARD_LINK_IMPORT_SOURCE_URL,
			CURLOPT_PORT 			=>  80,
			CURLOPT_RETURNTRANSFER  =>  1,
			CURLOPT_CONNECTTIMEOUT  =>  10,
			CURLOPT_ENCODING 		=>  'UTF-8'
		);
		
		// Get data
		$rCurlHandle = curl_init();
		curl_setopt_array( $rCurlHandle, $aCurlOptions ); 
		$aExternalData = curl_exec( $rCurlHandle );
		$iErrNo = curl_errno( $rCurlHandle );
		$sError  = curl_error( $rCurlHandle ) ;
		$aHeader  = curl_getinfo( $rCurlHandle );
		curl_close( $rCurlHandle );
		
		if( $aHeader['http_code'] != 200 ) {
			// Problem with reading external source, end sync
			return false;
		}
		
		// Decrypt
		require_once PATH_FUNCTION . '/fSecurity.php';
		$aExternalData = decryptStr( $aExternalData );
		
		$sEncoding = 'UTF-8';
		
		if( !empty($aExternalData) ) {
			foreach( json_decode( rtrim($aExternalData, "\0") ) as $oEntry ) {				
				$aExternalEntry = array(
					'linkTextSwedish' => htmlspecialchars( $oEntry->linkTextSwedish, ENT_QUOTES, $sEncoding ),
					'linkTextEnglish' => htmlspecialchars( $oEntry->linkTextEnglish, ENT_QUOTES, $sEncoding ),
					'linkUrl' => htmlspecialchars( $oEntry->linkUrl, ENT_QUOTES, $sEncoding ),
					'linkDescription' => htmlspecialchars( $oEntry->linkDescription, ENT_QUOTES, $sEncoding ),
					'linkType' => htmlspecialchars( $oEntry->linkType, ENT_QUOTES, $sEncoding ),
					'linkSort' => htmlspecialchars( $oEntry->linkSort, ENT_QUOTES, $sEncoding ),
					'linkCreated' => htmlspecialchars( $oEntry->linkCreated, ENT_QUOTES, $sEncoding ),
					'linkUpdated' => htmlspecialchars( $oEntry->linkUpdated, ENT_QUOTES, $sEncoding )
				);
				
				$aData = current( $this->readByUrl( $oEntry->linkUrl ) );
				
				if( !empty($aData) ) {
					// Update
					$this->update( $aData['linkId'], $aExternalEntry );
					
				} else {
					// Create
					$this->create( $aExternalEntry );
				}
			}			
		}
		return true;
	}
	
	public function export() {
		return $this->oDao->readData( array(
			'criterias' => 'linkType = "external"'
		) );
	}
	
	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		
		// Highest sort no plus one
		$aData['linkSort'] = !empty($aData['linkSort']) ? $aData['linkSort'] : current(current( $this->read('MAX(linkSort)') )) + 1;
		
		if( $this->oDao->createData($aData, $aParams) ) {
			return $this->oDao->oDb->lastId();
		}
		return false;
	}
	
	public function readByUrl( $sUrl ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readData( array(
			'criterias' => 'linkUrl = ' . $this->oDao->oDb->escapeStr( $sUrl )
		) );
	}
	
	public function update( $primaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'update' . $this->sModuleName;		
		return $this->oDao->updateDataByPrimary( $primaryId, $aData, $aParams );		
	}
	
	public function updateSort() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aPrimaryIds = func_get_args();
		return $this->oDao->updateSort( $aPrimaryIds );
	}
	
}