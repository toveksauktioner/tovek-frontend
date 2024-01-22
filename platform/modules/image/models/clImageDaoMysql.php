<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clImageDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entImage' => array(
				'imageId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'imageFileExtension' => array(
					'type' => 'string'
				),
				'imageParentType' => array(
					'type' => 'string',
					'index' => true,
					'required' => true
				),
				'imageParentId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true
				),
				'imageAlternativeText' => array(
					'type' => 'string',
				),
				'imageKey' => array(
					'type' => 'string'
				),
				'imageMD5' => array(
					'type' => 'string'
				),
				'imageSort' => array(
					'type' => 'integer'
				),
				'imageCreated' => array(
					'type' => 'datetime'
				)
			)
		);
		$this->sPrimaryField = 'imageId';
		$this->sPrimaryEntity = 'entImage';
		$this->aFieldsDefault = array(
			'imageId',
			'imageFileExtension'
		);

		$this->init();
	}

	public function read( $aParams = array() ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'parentType' => null,
			'parentId' => null,
			'imageKey' => null,
			'md5' => null
		);
		$aCriterias = array();

		$aDaoParams = array(
			'fields' => $aParams['fields'],
		);

		if( $aParams['parentType'] !== null ) $aCriterias[] = 'imageParentType = ' . $this->oDb->escapeStr( $aParams['parentType'] );
		if( $aParams['parentId'] !== null ) {
			if( is_array($aParams['parentId']) ) {
				$aCriterias[] = 'imageParentId IN(' . implode( ', ', array_map('intval', $aParams['parentId']) ) . ')';
			} else {
				$aCriterias[] = 'imageParentId = ' . (int) $aParams['parentId'];
			}
		}
		if( $aParams['imageKey'] !== null ) {
			if( is_array($aParams['imageKey']) ) {
				$aImageKey = array();
				foreach( $aParams['imageKey'] as $imageKey ) {
					$aImageKey[] = 'imageKey = ' . $this->oDb->escapeStr( $imageKey );
				}
				$aCriterias[] = '(' . implode( ' OR ', $aImageKey ) . ')';
			} else {
				$aCriterias[] = 'imageKey = ' . $this->oDb->escapeStr( $aParams['imageKey'] );
			}
		}
		if( $aParams['md5'] !== null ) {
			if( is_array($aParams['md5']) ) {
				$aMd5s = array();
				foreach( $aParams['md5'] as $sMd5 ) {
					$aMd5s[] = 'imageMD5 = ' . $this->oDb->escapeStr( $sMd5 );
				}
				$aCriterias[] = '(' . implode( ' OR ', $aMd5s ) . ')';
			} else {
				$aCriterias[] = 'imageMD5 = ' . $this->oDb->escapeStr( $aParams['md5'] );
			}
		}
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData($aDaoParams);
	}

	public function updateSortOrder( $sParentType, $iParentId, $aImageIds ) {
		$aImageIds = array_map( 'intval', (array) $aImageIds );

		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		return $oDaoMysql->updateSortOrder( $aImageIds, 'imageSort', array(
			'entities' => 'entImage',
			'criterias' => 'imageParentId = ' . (int) $iParentId . ' AND imageParentType = ' . $this->oDb->escapeStr($sParentType),
			'primaryField' => 'imageId'
		) );
	}

}
