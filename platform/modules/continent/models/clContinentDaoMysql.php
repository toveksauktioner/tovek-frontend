<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clParentChildHelperDaoSql.php';

class clContinentDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entContinent' => array(
				'continentCode' => array(
					'type' => 'string',
					'primary' => true,
					'required' => true,
					'min' => 2,
					'max' => 2,
					'title' => _( 'Code' )
				),
				'continentName' => array(
					'type' => 'string',
					'max' => 255,
					'required' => true,
					'title' => _( 'Name' )
				)
			),
			'entContinentCountry' => array(
				'countryId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'countryContinentCode' => array(
					'type' => 'string',
					'min' => 2,
					'max' => 2,
					'index' => true,
					'required' => true,
					'title' => _( 'Continent' )
				),
				'countryIsoCode2' => array(
					'type' => 'string',
					'min' => 2,
					'max' => 2,
					'title' => _( 'ISO Code 2' )
				),
				'countryIsoCode3' => array(
					'type' => 'string',
					'min' => 3,
					'max' => 3,
					'title' => _( 'ISO Code 3' )
				),
				'countryNumber' => array(
					'type' => 'string',
					'min' => 3,
					'max' => 3,
					'title' => _( 'Number' )
				),
				'countryName' => array(
					'type' => 'string',
					'max' => 64,
					'title' => _( 'Name' )
				)
			)
		);

		$this->sPrimaryField = 'continentId';
		$this->sPrimaryEntity = 'entContinent';
		$this->aFieldsDefault = '*';

		$this->init();

		$this->aHelpers = array(
			'oParentChildHelperDao' => new clParentChildHelperDaoSql( $this, array(
				'childEntity' => 'entContinentCountry',
				'childPrimaryField' => 'countryId',
				'childParentField' => 'countryContinentCode',
				'parentEntity' => 'entContinent',
				'parentPrimaryField' => 'continentCode',
			) )
		);
	}

}
