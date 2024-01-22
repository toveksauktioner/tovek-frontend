<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clVehicleLookupDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entVehicleLookup' => array(
				'lookupId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Vehicle lookup ID' )
				),
				'lookupService' => array(
					'type' => 'string',
					'title' => _( 'Lookup service' )
				),
				'lookupServiceFunction' => array(
					'type' => 'string',
					'title' => _( 'Lookup service function' )
				),
				'lookupStatus' => array(
					'type' => 'array',
					'values' => array(
						'requested' => _( 'Requested' ),
						'success' => _( 'Success' ),
						'fail' => _( 'Fail' )
					),
					'title' => _( 'Lookup status' )
				),
				'lookupSearchParam' => array(
					'type' => 'string',
					'title' => _( 'Search param' )
				),
				'lookupInData' => array(
					'type' => 'string',
					'title' => _( 'In data' )
				),
				'lookupResultData' => array(
					'type' => 'string',
					'title' => _( 'Result' )
				),
				'lookupVehicleDataId' => array(
					'type' => 'int'
				),
				'lookupCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		$this->sPrimaryEntity = 'entVehicleLookup';
		$this->sPrimaryField = 'lookupId';
		$this->aFieldsDefault = array( '*' );

		$this->init();

		$this->aDataFilters['output'] = array(
			'lookupResultData' => array(
				'sFunction' => function($sData) { return ( json_decode($sData, true) ); },
				'aParams' => array( '_self_' )
			)
		);

		$this->aDataFilters['input'] = array(
			'lookupResultData' => array(
				'sFunction' => function($sData) { return ( json_encode($sData) ); },
				'aParams' => array( '_self_' )
			)
		);
	}

}
