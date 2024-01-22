<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clAcoDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entAco' => array(
				'acoKey' => array(
					'type' => 'string',
					'primary' => true,
					'required' => true,
					'title' => _( 'Key' )
				),
				'acoType' => array(
					'type' => 'array',
					'values' => array(
						'dao' => _( 'DAO' ),
						'view' => _( 'Views' ),
						'layout' => _( 'Layouts' )
					),
					'title' => _( 'Type' )
				),
				'acoGroup' => array(
					'type' => 'string',
					'title' => _( 'Group' )
				)
			)
		);
		$this->sPrimaryField = 'acoKey';
		$this->sPrimaryEntity = 'entAco';
		$this->aFieldsDefault = '*';
		$this->init();
	}
	
}

?>