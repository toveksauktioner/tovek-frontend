<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_BACK_PLATFORM . '/modules/objectStorage/config/cfObjectStorage.php';
require_once PATH_PLATFORM . '/composer/vendor/autoload.php';

class clObjectStorage extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'objectStorage';
    $this->oS3Client = new \Aws\S3\S3Client( [
        'profile' => 'backend',
        'endpoint' => OBJECT_STORAGE_ENDPOINT_DEFAULT,
        'region' => OBJECT_STORAGE_REGION,
        'version' => 'latest',
    ] );

    $this->oDao = clRegistry::get( 'clObjectStorageDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/objectStorage/models' );
		$this->initBase();

		$this->oDao->switchToSecondary();
	}


	public function readWithParams( $aParams ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		$aParams += array(
			'objectId' => null,
      'type' => null,
			'parentTable' => null,
      'parentId' => null,
			'access' => 'public',
			'includeConnection' => false,
			'includeVariants' => false,
			'structureVariants' => false,
			'fields' => null
		);

		$aCriterias = [];
		$aExtendedEntities = [];
		$aDaoParams = [
			'fields' => $aParams['fields']
		];

		if( !empty($aParams['objectId']) ) {
			if( is_array($aParams['objectId']) ) {
				$aCriterias[] = 'entObjectStorage.objectId IN (' . implode( ', ', array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['objectId']) ) . ')';
			} else {
				$aCriterias[] = 'entObjectStorage.objectId = ' . $this->oDao->oDb->escapeStr( $aParams['objectId'] );
			}
		}

		if( !empty($aParams['type']) ) {
			if( is_array($aParams['type']) ) {
				$aCriterias[] = "entObjectStorage.objectType IN (" . implode( ", ", array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['type']) ) . ")";
			} else {
				$aCriterias[] = "entObjectStorage.objectType = " . $this->oDao->oDb->escapeStr( $aParams['type'] );
			}
		}

    if( !empty($aParams['parentTable']) ) {
			$aParams['includeConnection'] = true;

      if( is_array($aParams['parentTable']) ) {
        $aCriterias[] = "entObjectStorageToDbObject.parentTable IN (" . implode( ", ", array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['parentTable']) ) . ")";
      } else {
        $aCriterias[] = "entObjectStorageToDbObject.parentTable = " . $this->oDao->oDb->escapeStr( $aParams['parentTable'] );
      }
    }

		if( !empty($aParams['parentId']) ) {
			$aParams['includeConnection'] = true;

			if( is_array($aParams['parentId']) ) {
				$aCriterias[] = 'entObjectStorageToDbObject.parentId IN (' . implode( ', ', array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['parentId']) ) . ')';
			} else {
				$aCriterias[] = 'entObjectStorageToDbObject.parentId = ' . $this->oDao->oDb->escapeStr( $aParams['parentId'] );
			}
		}

		if( !empty($aParams['access']) ) {
			if( is_array($aParams['access']) ) {
				$aCriterias[] = "entObjectStorage.objectAccess IN (" . implode( ", ", array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['access']) ) . ")";
			} else {
				$aCriterias[] = "entObjectStorage.objectAccess = " . $this->oDao->oDb->escapeStr( $aParams['access'] );
			}
		}

		if( $aParams['includeConnection'] === true ) {
			$aExtendedEntities[] = 'LEFT JOIN entObjectStorageToDbObject ON entObjectStorageToDbObject.objectId = entObjectStorage.objectId';
		}

		if( !empty($aParams['includeVariants']) ) {
			$aExtendedEntities[] = 'LEFT JOIN entObjectStorageVariants ON entObjectStorageVariants.objectId = entObjectStorage.objectId';

			if( $aParams['includeVariants'] !== true ) {
				if( is_array($aParams['includeVariants']) ) {
					$aCriterias[] = "entObjectStorageVariants.objectVariant IN (" . implode( ", ", array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['includeVariants']) ) . ")";
				} else {
					$aCriterias[] = "entObjectStorageVariants.objectVariant = " . $this->oDao->oDb->escapeStr( $aParams['includeVariants'] );
				}
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		if( !empty($aExtendedEntities) ) $aDaoParams['entitiesExtended'] = 'entObjectStorage ' . implode( ' ', $aExtendedEntities );

		$aReturnData = $this->oDao->readData( $aDaoParams );

		if( OBJECT_STORAGE_CDN_REPLACE && !empty($aReturnData) ) {
			foreach( $aReturnData as &$aData ) {
				if( empty($aData['objectUrl']) ) continue;
				$aData['objectUrl'] = str_replace( array_keys(OBJECT_STORAGE_CDN_BASE), OBJECT_STORAGE_CDN_BASE, $aData['objectUrl'] );
			}
		}

		if( !empty($aReturnData) && ($aParams['structureVariants'] === true) ) {
			$aTempReturnData = [];
			foreach( $aReturnData as $aObject ) {
				$aTempReturnData[ $aObject['objectId'] ][ $aObject['objectVariant'] ] = $aObject;
			}
			$aReturnData = $aTempReturnData;
		}

		return $aReturnData;
	}

  public function getOriginalUrl( $iObjectId, $aObjectData = null ) {
		if( empty($aObjectData) ) {
			$aObjectData = current( $this->read('*', $iObjectId) );
		}

		if( !empty($aObjectData) ) {
			return OBJECT_STORAGE_PROTOCOL . $aObjectData['objectServer'] . '-' . $aObjectData['objectType'] . '-' . $aObjectData['objectAccess'] . '.' . $aObjectData['objectEndpoint'] . '/' . $aObjectData['objectId'] . '.' . $aObjectData['objectExtension'];
		}
		return false;
	}


	// Connections
	public function readConnection( $sParentTable = null, $iParentId = null, $iObjectId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readConnection( $sParentTable, $iParentId, $iObjectId );
	}

	// Variants
	public function readVariant( $iObjectId, $sVariantName = null ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->readVariant( $iObjectId, $sVariantName );
	}

}
