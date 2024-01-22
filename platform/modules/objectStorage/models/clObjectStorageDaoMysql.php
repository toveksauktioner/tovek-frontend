<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clObjectStorageDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = OBJECT_STORAGE_DATADICT;
		$this->sPrimaryEntity = 'entObjectStorage';
		$this->sPrimaryField = 'objectId';
		$this->aFieldsDefault = '*';

		$this->init();
	}

	// Connection
	public function readConnection( $sParentTable = null, $iParentId = null, $iObjectId = null ) {
		$aDaoParams = array(
			'entities' => 'entObjectStorageToDbObject'
		);
		$aCriterias = [];

		if( $sParentTable !== null ) 	$aCriterias[] = 'entObjectStorageToDbObject.parentTable = ' . $this->oDb->escapeStr( $sParentTable );
		if( $iParentId !== null ) 		$aCriterias[] = 'entObjectStorageToDbObject.parentId = ' . $this->oDb->escapeStr( $iParentId );
		if( $iObjectId !== null ) 		$aCriterias[] = 'entObjectStorageToDbObject.objectId = ' . $this->oDb->escapeStr( $iObjectId );

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		return $this->readData( $aDaoParams );
	}

	// Variant
	public function readVariant( $iObjectId, $sVariantName = null ) {
		$aDaoParams = array(
			'entities' => 'entObjectStorageVariants'
		);
		$aCriterias = [
			'entObjectStorageVariants.objectId = ' . $this->oDb->escapeStr( $iObjectId ),
		];

		if( $sVariantName !== null ) $aCriterias[] = 'entObjectStorageVariants.objectVariant = ' . $this->oDb->escapeStr( $sVariantName );

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		return $this->readData( $aDaoParams );
	}
}
