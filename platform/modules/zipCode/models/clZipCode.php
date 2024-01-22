<?php

/**
 * Postnummerservice
 * 
 * Art.nr/Filnamn:   rec2LK
 * Filformat:        CSV, Excel, Textfil med fast radlängd
 * Antal rader:      ca 17 500 st
 * Kolumner:         Postnummer, Postort, Länskod, Län, Kommunkod, Kommun, AR-kod
 * Teckenkodning:    ISO-8859-1
 *
 * Reference: http://www.postnummerservice.se/utbud/referensdata/postnummerfiler/postnummerfilen-laen-kommun
 * Source: rec2LK.csv
 */

require_once PATH_MODULE . '/zipCode/config/cfZipCode.php';

require_once PATH_CORE . '/clModuleBase.php';

class clZipCode extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'ZipCode';
		$this->sModulePrefix = 'zipCode';
		
		$this->oDao = clRegistry::get( 'clZipCodeDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/zipCode/models' );
		
		$this->initBase();
	}

	/**
	 * This function sync and import data from file.
	 * It will also create DB table if not exists.
	 */
	public function syncImport( $aParams = array() ) {
		require_once PATH_FUNCTION . '/fDevelopment.php';
		datadictWriteSql( $this->oDao->getDataDict() );
		
		$aParams += array(
			'file' => REC2LK_IMPORT_FILE
		);
		
		if( !file_exists($aParams['file']) ) return false;
		
		$aData = csvToArray( $aParams['file'], false );
		
		if( empty($aData) ) return false;
		
		foreach( $aData as $aEntry ) {
			if( count($aEntry) == 1 && strpos(current($aEntry), ',') == true ) {
				$aEntry = explode( ',', current($aEntry) );
			}
			
			$this->upsertCustom( array(
				'zipCode' 			  => $aEntry[0],
                'zipCity' 			  => $aEntry[1],
                'zipCountyCode' 	  => $aEntry[2],
                'zipCounty' 		  => $aEntry[3],
                'zipMunicipalityCode' => $aEntry[4],
                'zipMunicipality'	  => $aEntry[5],
                'zipARcode' 		  => $aEntry[6]
			) );
		}
		
		return true;
	}	
	
	public function upsertCustom( $aData, $aParams = array() ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aExisting = current( $this->oDao->read( array(
			'zipCode' => $aData['zipCode'],
			'zipMunicipalityCode' => $aData['zipMunicipalityCode']
		) ) );
		
		if( empty($aExisting) ) {
			$aData['zipCreated'] = date( 'Y-m-d H:i:s' ); 
			return $this->create( $aData );
		} else {
			$aData['zipUpdated'] = date( 'Y-m-d H:i:s' ); 
			return $this->update( $aExisting['zipId'], $aData );
		}
	}
	
}