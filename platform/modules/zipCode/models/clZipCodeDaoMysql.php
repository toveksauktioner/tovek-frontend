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

require_once PATH_CORE . '/clDaoBaseSql.php';

class clZipCodeDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entZipCode' => array(
				'zipId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'zipCode' => array(
					'type' => 'integer',
					'primary' => true,
					'title' => _( 'Zip code' )
				),
                'zipCity' => array(
					'type' => 'string',
					'title' => _( 'City' )
				),
                'zipCountyCode' => array(
					'type' => 'string',
					'title' => _( 'County code' )
				),
                'zipCounty' => array(
					'type' => 'string',
					'title' => _( 'County' )
				),
                'zipMunicipalityCode' => array(
					'type' => 'string',
					'title' => _( 'Municipality code' )
				),
                'zipMunicipality' => array(
					'type' => 'string',
					'title' => _( 'Municipality' )
				),
                'zipARcode' => array(
					'type' => 'string',
					'title' => _( 'AR code' )
				),
                'zipUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				),                
				'zipCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		
		$this->sPrimaryField = 'zipCode';
		$this->sPrimaryEntity = 'entZipCode';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}
	
	public function read( $aParams = array() ) {
        $aParams += array(
			'fields' => $this->aFieldsDefault,
            'zipId' => null,
			'zipCode' => null,
			'zipCity' => null,
			'zipCountyCode' => null,
			'zipCounty' => null,
			'zipMunicipalityCode' => null,
			'zipMunicipality' => null,
			'zipARcode' => null
		);
		
		$aDaoParams = array(
			'fields' => $aParams['fields']
		);
		
		$aCriterias = array();
		
		// ID
		if( $aParams['zipId'] !== null ) {
			if( is_array($aParams['zipId']) ) $aCriterias[] = 'zipId IN(' . implode( ', ', array_map('intval', $aParams['zipId']) ) . ')';
			else $aCriterias[] = 'zipId = ' . (int) $aParams['zipId'];
		}
		
		// ZipCode
		if( $aParams['zipCode'] !== null ) {
			if( is_array($aParams['zipCode']) ) $aCriterias[] = 'zipCode IN(' . implode( ', ', array_map('intval', $aParams['zipCode']) ) . ')';
			else $aCriterias[] = 'zipCode = ' . (int) $aParams['zipCode'];
		}
		
		// ZipCity
		if( $aParams['zipCity'] !== null ) {
			if( is_array($aParams['zipCity']) ) $aCriterias[] = 'zipCity IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['zipCity']) ) . ')';
			else $aCriterias[] = 'zipCity = ' . $this->oDb->escapeStr( $aParams['zipCity'] );
		}
		
		// ZipCountyCode
		if( $aParams['zipCountyCode'] !== null ) {
			if( is_array($aParams['zipCountyCode']) ) $aCriterias[] = 'zipCountyCode IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['zipCountyCode']) ) . ')';
			else $aCriterias[] = 'zipCountyCode = ' . $this->oDb->escapeStr( $aParams['zipCountyCode'] );
		}
		
		// ZipCounty
		if( $aParams['zipCounty'] !== null ) {
			if( is_array($aParams['zipCounty']) ) $aCriterias[] = 'zipCounty IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['zipCounty']) ) . ')';
			else $aCriterias[] = 'zipCounty = ' . $this->oDb->escapeStr( $aParams['zipCounty'] );
		}
		
		// ZipMunicipalityCode
		if( $aParams['zipMunicipalityCode'] !== null ) {
			if( is_array($aParams['zipMunicipalityCode']) ) $aCriterias[] = 'zipMunicipalityCode IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['zipMunicipalityCode']) ) . ')';
			else $aCriterias[] = 'zipMunicipalityCode = ' . $this->oDb->escapeStr( $aParams['zipMunicipalityCode'] );
		}
		
		// ZipMunicipality
		if( $aParams['zipMunicipality'] !== null ) {
			if( is_array($aParams['zipMunicipality']) ) $aCriterias[] = 'zipMunicipality IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['zipMunicipality']) ) . ')';
			else $aCriterias[] = 'zipMunicipality = ' . $this->oDb->escapeStr( $aParams['zipMunicipality'] );
		}
		
		// ZipARcode
		if( $aParams['zipARcode'] !== null ) {
			if( is_array($aParams['zipARcode']) ) $aCriterias[] = 'zipARcode IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['zipARcode']) ) . ')';
			else $aCriterias[] = 'zipARcode = ' . $this->oDb->escapeStr( $aParams['zipARcode'] );
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return parent::readData( $aDaoParams );
    }
	
}