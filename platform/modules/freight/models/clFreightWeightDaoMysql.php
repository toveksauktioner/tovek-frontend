<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clFreightWeightDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entFreightWeight' => array(
				'freightWeightId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'freightTypeId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true
				),
				'freightWeightFrom' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'Weight from' )
				),
				'freightWeightTo' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true,
					'title' => _( 'Weight to' )
				),
				'freightWeightAddition' => array(
					'type' => 'float',
					'title' => _( 'Freight addition' )
				),				
				'freightWeightStatus' => array(
					'type' => 'array',
					'values' => array(
						'allowed' => _( 'Allowed' ),
						'denied' => _( 'Denied' )
					),
					'title' => _( 'Status' )
				),
				'freightWeightUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				),
				'freightWeightCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)			
		);

		$this->sPrimaryField = 'freightWeightId';
		$this->sPrimaryEntity = 'entFreightWeight';
		$this->aFieldsDefault = array(
			'*'
		);

		$this->init();
	}

	public function read( $aParams = array() ) {
		$aParams += array(
			'freightTypeId' => null,
			'weight' => null,
			'fields' => $this->aFieldsDefault,
		);
		$aCriterias = array();

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entities' => array( 'entFreightWeight' ),
		);

		if( $aParams['freightTypeId'] !== null ) {
			if( is_array($aParams['freightTypeId']) ) {
				$aCriterias[] = 'freightTypeId IN(' . implode( ', ', array_map('intval', $aParams['freightTypeId']) ) . ')';
			} else {
				$aCriterias[] = 'freightTypeId = ' . (int) $aParams['freightTypeId'];
			}
		}
		if( $aParams['weight'] !== null ) {			
			$aCriterias[] = 'freightWeightFrom <= ' . (int) $aParams['weight'] . ' AND freightWeightTo >= ' . (int) $aParams['weight'];
		}
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	
	
}