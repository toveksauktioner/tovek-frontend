<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clSlideshowImageToLayoutDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entSlideshowImageToLayout' => array(
				'relationId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'slideshowImageId' => array(
					'type' => 'integer',
					'index' => true
				),
				'layoutKey' => array(
					'type' => 'string',
					'index' => true
				)
			)
		);
		
		$this->sPrimaryField = 'relationId';
		$this->sPrimaryEntity = 'entSlideshowImageToLayout';
		$this->aFieldsDefault = '*';
		
		$this->init();
	}

	public function readBySlideshowImageId( $iSlideshowImageId = null ) {
		$aParams['criterias'] = 'slideshowImageId = ' . $this->oDb->escapeStr( $iSlideshowImageId );
		return $this->readData( $aParams );
	}
	
	public function readByLayoutKey( $sLayoutKey = null ) {
		$aParams['criterias'] = 'layoutKey = ' . $this->oDb->escapeStr( $sLayoutKey );
		return $this->readData( $aParams );
	}
	
}