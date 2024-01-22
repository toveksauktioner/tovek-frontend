<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clLocaleDaoMysql extends clDaoBaseSql {

	private $sGroupKey;

	public function __construct() {
		$this->aDataDict = array(
			'entLocale' => array(
				'localeId' => array(
					'type' => 'integer',
					'primary' => true
				),
				'localeCode' => array(
					'type' => 'string',
					'title' => _( 'Key' ),
					'required' => true
				),
				'localeTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' ),
					'required' => true
				),
				'localeDefaultMonetary' => array(
					'type' => 'string',
					'title' => _( 'Monetary' ),
					'required' => true
				),
				'localeDefaultCurrency' => array(
					'type' => 'string',
					'title' => _( 'Currency' ),
					'required' => true
				),
				'localeUse' => array(
					'type' => 'array',
					'title' => _( 'Use' ),
					'values' => array(
						'language' => _( 'Language' ),
						'money' => _( 'Money' ),
						'both' => _( 'Both' ),
						'inactive' => _( 'Inactive' )
					),
					'required' => true
				),
				'localeSort' => array(
					'type' => 'integer',
					'title' => _( 'Sort' )
				)
			)
		);
		$this->sPrimaryName = 'entLocale';
		$this->sPrimaryField = 'localeCode';
		$this->aFieldsDefault = '*';
		$this->init();
	}

}
