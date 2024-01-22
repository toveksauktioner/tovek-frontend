<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clConfigBackendDaoMysql extends clDaoBaseSql {
	
	private $sGroupKey;
	
	public function __construct() {
		$this->aDataDict = array(
			'entConfig' => array(
				'configKey' => array(
					'type' => 'string',
					'primary' => true,
					'required' => true,
					'title' => _( 'Key' )
				),
				'configValue' => array(
					'type' => 'string',
					'title' => _( 'Value' ),
					'required' => true
				),
				'configGroupKey' => array(
					'type' => 'string',
					'title' => _( 'Group key' )
				),
			)
		);
		$this->sPrimaryName = 'entConfig';
		$this->sPrimaryField = 'configKey';
		$this->aFieldsDefault = '*';
		$this->init();
	}
	
	public function readByGroupKey( $sGroupKey ) {
		$aParams = array(
			'criterias' => 'configGroupKey = ' . $this->oDb->escapeStr( $sGroupKey )
		);
		return $this->readData( $aParams );
	}
	
}
