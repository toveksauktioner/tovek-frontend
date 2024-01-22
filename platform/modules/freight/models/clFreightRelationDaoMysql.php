<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clFreightRelationDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entFreightRelation' => array(
				'relationId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'relationFreightTypeId' => array(
					'type' => 'integer'
				),
				'relationProductId' => array(
					'type' => 'integer'
				),
				'relationFreightPriceType' => array(
					'type' => 'array',
					'values' => array(
						'fixed' => _( 'Fixed' ),
						'elevated' => _( 'Elevated' )
					)
				),
				'relationElevatedPrice' => array(
					'type' => 'float'
				),
				'relationStatus' => array(
					'type' => 'array',
					'values' => array(
						'valid' => _( 'Valid' ),
						'invalid' => _( 'Invalid' )
					)
				)
			)
		);
		
		$this->sPrimaryField = 'relationId';
		$this->sPrimaryEntity = 'entFreightRelation';
		$this->aFieldsDefault = '*';
		
		$this->init();
	}
	
	public function createRelation( $aData, $aFreightTypes ) {
		$aDaoData = array();
		foreach( $aFreightTypes as $entry ) {
			// Determine if current freight type is a valid type			
			if( !in_array($entry, (array) $aData['relationFreightTypeIds']) ) {
				// Not a valid type for selected product
				$sStatus = 'invalid';
			} else {
				// Valid type for selected product
				$sStatus = 'valid';
			}
			
			$aDaoData[] = array(
				'relationFreightTypeId' => $entry,			
				'relationProductId' => $aData['relationProductId'],
				'relationFreightPriceType' => $aData['relationFreightPriceType'],
				'relationElevatedPrice' => $aData['relationElevatedPrice'],
				'relationStatus' => $sStatus
			);
		}
		
		// Create relations
		return $this->createMultipleData( $aDaoData, array(
			'entities' => 'entFreightRelation',
			'fields' => array(
				'relationFreightTypeId',			
				'relationProductId',
				'relationFreightPriceType',
				'relationElevatedPrice',
				'relationStatus'
			),
			'groupKey' => 'createFreightRelation'
		) );
	}

	public function resetRelationToProduct( $aFreightTypes, $aProductIds ) {
		if( !is_array($aProductIds) ) $aProductIds = (array) $aProductIds;
		
		$iProductCount = 0;
		$aData = array();		
		foreach( $aProductIds as $key => $product ) {
			foreach( $aFreightTypes as $freight ) {
				$aData[] = array(
					'relationFreightTypeId' => $freight,			
					'relationProductId' => $product,
					'relationFreightPriceType' => 'fixed',
					'relationElevatedPrice' => 0,
					'relationStatus' => 'valid'
				);
			}
			++$iProductCount;
			
			if( $iProductCount == 50 || empty($aProductIds[$key+1]) ) {
				// Create relations
				$this->createMultipleData( $aData, array(
					'entities' => 'entFreightRelation',
					'fields' => array(
						'relationFreightTypeId',			
						'relationProductId',
						'relationFreightPriceType',
						'relationElevatedPrice',
						'relationStatus'
					),
					'groupKey' => 'createFreightRelation'
				) );
				
				// Reset
				$iProductCount = 0;
				$aData = array();
			}
		}		
		return true;
	}

	public function readByProduct( $aProductIds ) {
		$aParams = array();
		
		if( is_array($aProductIds) ) {
			$aParams['criterias'] = 'relationProductId IN(' . implode( ', ', $aProductIds ) . ')';
		} else {
			$aParams['criterias'] = 'relationProductId = ' . $aProductIds;
		}
		
		return $this->readData( $aParams );
	}

}