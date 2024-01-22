<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_FUNCTION . '/fData.php';

class clCurrencyDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entCurrency' => array(
				'currencyId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'currencyCode' => array(
					'type' => 'string',
					'index' => true,
					'required' => true,
					'min' => 3,
					'max' => 3,
					'title' => _( 'Code' )
				),
				'currencyTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'currencyRate' => array(
					'type' => 'float',
					'title' => _( 'Rate' )
				),
				'currencyCreated' => array(
					'type' => 'datetime'
				)
			)
		);
		$this->aDataFilters['input'] = array(
			'currencyCode' => array(
				'sFunction' => 'strtoupper',
				'aParams' => '_self_'
			)
		);
		$this->sPrimaryField = 'currencyId';
		$this->sPrimaryEntity = 'entCurrency';
		$this->aFieldsDefault = array(
			'currencyId',
			'currencyCode',
			'currencyTitle',
			'currencyRate'
		);

		$this->init();
	}
	
	public function readByCurrencyCode( $sCurrencyCode, $aData ) {
		$aParams = array(
			'entities' => 'entCurrency',
			'criterias' => 'currencyCode = ' . $this->oDb->escapeStr( $sCurrencyCode ),
			'fields' => ($aData)
		);
		
		return $this->readData( $aParams );
	}
	
	public function updateByCurrencyCode( $sCurrencyCode, $aData ) {
		return $this->updateData( (array) $aData, array(
			'entities' => 'entCurrency',
			'criterias' => 'currencyCode = ' . $this->oDb->escapeStr( $sCurrencyCode )
		) );
	}

}