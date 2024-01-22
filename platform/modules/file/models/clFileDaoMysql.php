<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clFileDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entFile' => array(
				'fileId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'filename' => array(
					'type' => 'string'
				),
				'fileExtension' => array(
					'type' => 'string'
				),
				'fileType' => array(
					'type' => 'string'
				),
				'fileParentType' => array(
					'type' => 'string',
					'index' => true,
					'required' => true
				),
				'fileParentId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true
				),
				'fileTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'fileSort' => array(
					'type' => 'integer'
				),
				'fileCreated' => array(
					'type' => 'datetime'
				)
			)
		);
		$this->sPrimaryField = 'fileId';
		$this->sPrimaryEntity = 'entFile';
		$this->aFieldsDefault = array(
			'fileId',
			'filename',
			'fileTitle'
		);
		
		$this->init();
	}
	
	public function read( $aParams = array() ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'parentType' => null,
			'parentId' => null
		);
		$aCriterias = array();

		$aDaoParams = array(
			'fields' => $aParams['fields'],
		);
		
		if( $aParams['parentType'] !== null ) $aCriterias[] = 'fileParentType = ' . $this->oDb->escapeStr( $aParams['parentType'] );
		if( $aParams['parentId'] !== null ) {
			if( is_array($aParams['parentId']) ) {
				$aCriterias[] = 'fileParentId IN(' . implode( ', ', array_map('intval', $aParams['parentId']) ) . ')';
			} else {
				$aCriterias[] = 'fileParentId = ' . (int) $aParams['parentId'];
			}
		}
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData($aDaoParams);
	}

}
