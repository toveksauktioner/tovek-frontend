<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clFreightCurrencyDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entFreightCurrency' => array(
				'entryId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),				
				'entryFreightTypeId' => array(
					'type' => 'integer',
					'title' => _( 'Freight type ID' )
				),
				'entryCurrencyId' => array(
					'type' => 'integer',
					'title' => _( 'Currency ID' )
				),
				'entryFreightCurrencyAddition' => array(
					'type' => 'float',
					'title' => _( 'Freight addition' )
				),
				// Misc
				'entryStatus' => array(
					'type' => 'array',
					'values' => array(
						'active' => _( 'Active' ),
						'inactive' => _( 'Inactive' )
					),
					'title' => _( 'Status' )
				),
				'entryCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'entryUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);
		
		$this->sPrimaryField = 'entryId';
		$this->sPrimaryEntity = 'entFreightCurrency';
		$this->aFieldsDefault = '*';
		
		$this->init();
		
		$this->aDataFilters['output'] = array(
			'entryUpdated' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			)
		);
	}
	
	public function read( $aParams = array() ) {
        $aParams += array(
			'fields' => $this->aFieldsDefault,
            'entryId' => null,
			'freightTypeId' => null,
			'currency' => null,			
			'status' => 'allowed'
		);
		
		$aDaoParams = array(
			'fields' => $aParams['fields']
		);
		
		$aCriterias = array();
		
		if( $aParams['entryId'] !== null ) {
			if( is_array($aParams['entryId']) ) {
				$aCriterias[] = 'entryId IN(' . implode( ', ', array_map('intval', $aParams['entryId']) ) . ')';
			} else {
				$aCriterias[] = 'entryId = ' . (int) $aParams['entryId'];
			}
		}
		
		if( $aParams['freightTypeId'] !== null ) {
			if( is_array($aParams['freightTypeId']) ) {
				$aCriterias[] = 'entryFreightTypeId IN(' . implode( ', ', array_map('intval', $aParams['freightTypeId']) ) . ')';
			} else {
				$aCriterias[] = 'entryFreightTypeId = ' . (int) $aParams['freightTypeId'];
			}
		}
		
		if( $aParams['currency'] !== null ) {
			if( ctype_digit($aParams['currency']) ) {
				if( is_array($aParams['currency']) ) {
					$aCriterias[] = 'entryCurrencyId IN(' . implode( ', ', array_map('intval', $aParams['currency']) ) . ')';
				} else {
					$aCriterias[] = 'entryCurrencyId = ' . (int) $aParams['currency'];
				}
			} else {
				$aDaoParams['entitiesExtended'] = $this->sPrimaryEntity . ' AS t1 LEFT JOIN entCurrency AS t2 ON t1.entryCurrencyId = t2.currencyId';
				
				if( is_array($aParams['currency']) ) {
					$aCriterias[] = 't2.currencyCode IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['currency']) ) . ')';
				} else {
					$aCriterias[] = 't2.currencyCode = ' . $this->oDb->escapeStr( $aParams['currency'] );
				}
			}
		}
		
		if( $aParams['status'] !== null && $aParams['status'] != '*' ) {
			if( is_array($aParams['status']) ) {
				$aCriterias[] = 'entryStatus IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['status']) ) . ')';
			} else {
				$aCriterias[] = 'entryStatus = ' . $this->oDb->escapeStr( $aParams['status'] );
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return parent::readData( $aDaoParams );
    }
	
}