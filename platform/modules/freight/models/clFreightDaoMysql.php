<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clFreightDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entFreight' => array(
				'freightId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'freightCountryId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true,
					'title' => _( 'Country' )
				),
				'freightValue' => array(
					'type' => 'float',
					'required' => true,
					'title' => _( 'Freight' )
				),
				'freightCreated' => array(
					'type' => 'datetime'
				)
			),
			'entFreightType' => array(
				'freightTypeId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'freightTypeTitle' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Title' )
				),
				'freightTypeDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'freightTypeAdnlInfo' => array(
					'type' => 'array',
					'title' => _( 'Additional info' ),
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' ),
						'required' => _( 'Required' )
					)
				),
				'freightTypePrice' => array(
					'type' => 'float',
					'title' => _( 'Freight addition' )
				),
				'freightTypeSort' => array(
					'type' => 'integer',
					'title' => _( 'Sort' )
				),
				'freightTypeStatus' => array(
					'type' => 'array',
					'index' => true,
					'values' => array(
						'active' 	=> _( 'Active' ),
						'inactive' 	=> _( 'Inactive' )
					),
					'title' => _( 'Status' )					
				),
				'freightTypeCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			),
			'entFreightTypeToCountry' => array(
				'freightTypeId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'ID' )
				),
				'countryId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'ID' )
				)
			),
			'entFreightFreeLimitToCountry' => array(
				'freightFreeLimit' => array(
					'type' => 'float',
					'index' => true,
					'title' => _( 'Freight free limit' )
				),
				'countryId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'ID' )
				)
			)
		);

		$this->sPrimaryField = 'freightId';
		$this->sPrimaryEntity = 'entFreight';
		$this->aFieldsDefault = array(
			'freightId',
			'freightCountryId',
			'freightValue'
		);

		$this->init();
	}

	public function deleteByCountry( $countryId ) {
		$aParams = array();

		if( is_array($countryId) ) {
			$aParams['criterias'] = "freightCountryId IN (" . implode( ", ", array_map('intval', $countryId) ) . ")";
		} else {
			$aParams['criterias'] = 'freightCountryId = ' . (int) $countryId;
		}

		return $this->deleteData( $aParams );
	}

	public function readByCountry( $countryId, $aFields = array() ) {
		$aParams = array(
			'fields' => $aFields
		);

		if( is_array($countryId) ) {
			$aParams['criterias'] = "freightCountryId IN (" . implode( ", ", array_map('intval', $countryId) ) . ")";
		} else {
			$aParams['criterias'] = 'freightCountryId = ' . (int) $countryId;
		}

		return $this->readData( $aParams );
	}

	public function updateByCountry( $countryId, $aData ) {
		if( is_array($countryId) ) {
			$aParams['criterias'] = "freightCountryId IN (" . implode( ", ", array_map('intval', $countryId) ) . ")";
		} else {
			$aParams['criterias'] = 'freightCountryId = ' . (int) $countryId;
		}

		return $this->updateData( $aData, $aParams );
	}

	// Types below
	public function createType( $aData ) {
		$aParams = array(
			'entities' => 'entFreightType',
			'groupKey' => 'createFreightType'
		);
		return ( $this->createData( $aData, $aParams ) ? $this->lastId() : false );
	}	
	
	public function deleteType( $primaryId ) {
		$aParams = array(
			'entities' => 'entFreightType',
			'criterias' => 'entFreightType.freightTypeId = ' . $this->oDb->escapeStr( $primaryId )	
		);

		return $this->deleteData( $aParams );
	}
	
	public function readType( $primaryId, $aFields = array(), $iCountryId = null ) {
		$aParams = array(
			'fields' => ( !empty($aFields) ? $aFields : array_keys($this->aDataDict['entFreightType']) ),
			'entities' => 'entFreightType'
		);
		
		if( $primaryId !== null ) {
			if( is_array($primaryId) ) {
				$aParams['criterias'] = "freightTypeId IN (" . implode( ", ", array_map('intval', $primaryId) ) . ")";
			} else {
				$aParams['criterias'] = 'freightTypeId = ' . (int) $primaryId;
			}
		}
		
		if( $iCountryId !== null && $primaryId === null ) {
			$aParams['entitiesExtended'] = 'entFreightType LEFT JOIN entFreightTypeToCountry ON entFreightType.freightTypeId = entFreightTypeToCountry.freightTypeId';
			
			foreach( $aParams['fields'] as $key => $entry ) {
				$aParams['fields'][$key] = 'entFreightType.' . $aParams['fields'][$key];
			}
			$aParams['fields'][] = 'entFreightTypeToCountry.countryId';
			
			if( is_array($iCountryId) ) {				
				$aParams['criterias'] = "entFreightTypeToCountry.countryId IN (" . implode( ", ", array_map('intval', $iCountryId) ) . ")";
			} else {
				$aParams['criterias'] = 'entFreightTypeToCountry.countryId = ' . $this->oDb->escapeStr( (int) $iCountryId );
			}
		}
		
		return $this->readData( $aParams );
	}
	
	public function updateType( $primaryId, $aData ) {
		$aParams = array(
			'entities' => 'entFreightType',
			'groupKey' => 'updateFreightType',
			'criterias' => 'entFreightType.freightTypeId = ' . $this->oDb->escapeStr( $primaryId )
		);
		return $this->updateData( $aData, $aParams );
	}

	public function updateSort( $aPrimaryIds ) {				
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );
		
		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'freightTypeSort', array(
			'entities' => 'entFreightType',
			'primaryField' => 'freightTypeId'
		) );
	}

}