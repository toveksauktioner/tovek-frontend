<?php

require_once PATH_CORE . '/clModuleBase.php';

class clFinancing extends clModuleBase {
	public $iFinancingId;

	public function __construct() {
		$this->sModulePrefix = 'financing';

		$this->oDao = clRegistry::get( 'clFinancingDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/financing/models' );
		$this->initBase();
	}

	public function setFinancingId( $iFinancingId ) {
    $this->iFinancingId = $iFinancingId;
	}

	public function read( $aFields = array(), $primaryId = null ) {
		// Default function requires user id to be the current logged in
		$aParams = array(
			'fields' => $aFields,
			'criterias' =>  'entFinancing.financingUserId = ' . $this->oDao->oDb->escapeStr( $_SESSION['userId'] )
		);

		if( $primaryId !== null ) return $this->oDao->readDataByPrimary( $primaryId, $aParams );

		return $this->oDao->readData( $aParams );
	}

  public function readFinancingByExternalId( $iId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		$aDaoParams = array(
			'fields' => $aFields
		);

		if( is_array($iId) ) {
			$aDaoParams['criterias'] = 'entFinancing.financingExternalOrderId IN (' . implode( ', ', array_map(array($this->oDao->oDb, 'escapeStr'), $iId) ) . ')';
		} else {
			$aDaoParams['criterias'] = 'entFinancing.financingExternalOrderId = ' . $this->oDao->oDb->escapeStr( $iId );
		}

		return $this->oDao->readData( $aDaoParams );
  }

  public function createFinancing( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

    if( empty($aData['financingCreated']) ) $aData['financingCreated'] = date( 'Y-m-d H:i:s' );

		if( $this->oDao->createData($aData) ) {
			$iLastId = $this->oDao->oDb->lastId();

			return $iLastId;
		}
		return false;
  }

  public function updateFinancing( $primaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

    if( empty($aData['financingUpdated']) ) $aData['financingUpdated'] = date( 'Y-m-d H:i:s' );
    return $this->oDao->updateDataByPrimary( $primaryId, $aData );
  }

  public function createServiceRequest( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

    if( empty($aData['requestQueryCreated']) && !empty($aData['requestQuery']) ) $aData['requestQueryCreated'] = date( 'Y-m-d H:i:s' );
    if( empty($aData['requestResponseCreated']) &&  !empty($aData['requestResponse']) ) $aData['requestResponseCreated'] = date( 'Y-m-d H:i:s' );

		$aDaoParams = array(
			'entities' => 'entFinancingServiceRequest'
		);

		if( $this->oDao->createData($aData, $aDaoParams) ) {
			$iLastId = $this->oDao->oDb->lastId();

			return $iLastId;
		}
		return false;
  }

  public function readServiceRequest( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		$aDaoParams = array(
			'entities' => 'entFinancingServiceRequest',
			'fields' => $aFields
		);

		if( $primaryId !== null ) return $this->oDao->readDataByPrimary($primaryId, $aParams);
		return $this->oDao->readData( $aParams );
  }

  public function updateServiceRequest( $primaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

    if( empty($aData['requestQueryCreated']) && !empty($aData['requestQuery']) ) $aData['requestQueryCreated'] = date( 'Y-m-d H:i:s' );
    if( empty($aData['requestResponseCreated']) &&  !empty($aData['requestResponse']) ) $aData['requestResponseCreated'] = date( 'Y-m-d H:i:s' );

		$aDaoParams = array(
			'entities' => 'entFinancingServiceRequest'
		);

		if( is_array($primaryId) ) {
			$aDaoParams['criterias'] = 'entFinancingServiceRequest.requestId IN (' . implode( ', ', array_map(array($this->oDao->oDb, 'escapeStr'), $primaryId) ) . ')';
		} else {
			$aDaoParams['criterias'] = 'entFinancingServiceRequest.requestId = ' . $this->oDao->oDb->escapeStr( $primaryId );
		}

		return $this->oDao->updateData( $aData, $aDaoParams );
  }

  public function createFinancingToItem( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

    $aDaoParams = array(
			'entities' => 'entFinancingToItem'
		);

		if( $this->oDao->createData($aData, $aDaoParams) ) {
			$iLastId = $this->oDao->oDb->lastId();

			return $iLastId;
		}
		return false;
  }

  public function updateFinancingToItem( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

    $aDaoParams = array(
			'entities' => 'entFinancingToItem',
		);

		$aCriterias = array(
			'entFinancingToItem.financingId = ' . $this->oDao->oDb->escapeStr( $aData['financingId'] ),
			'entFinancingToItem.itemId = ' . $this->oDao->oDb->escapeStr( $aData['itemId'] )
		);
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		unset(
			$aData['financingId'],
			$aData['itemId']
		);

		return $this->oDao->updateData( $aData, $aDaoParams );
  }

	public function readFinancingToItem( $aParams ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		$aParams += array(
			'financingId' => null,
			'itemId' => null,
			'userId' => null,
			'fields' => null,
			'includeFinancingData' => false
		);

		$aDaoParams = array(
			'entities' => array( 'entFinancingToItem' ),
			'fields' => $aParams['fields']
		);

		$aCriterias = array();

		if( !empty($aParams['financingId']) ) {
			if( is_array($aParams['financingId']) ) {
				$aCriterias[] = 'entFinancingToItem.financingId IN (' . implode( ', ', array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['financingId']) ) . ')';
			} else {
				$aCriterias[] = 'entFinancingToItem.financingId = ' . $this->oDao->oDb->escapeStr( $aParams['financingId'] );
			}
		}

		if( !empty($aParams['itemId']) ) {
			if( is_array($aParams['itemId']) ) {
				$aCriterias[] = 'entFinancingToItem.itemId IN (' . implode( ', ', array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['itemId']) ) . ')';
			} else {
				$aCriterias[] = 'entFinancingToItem.itemId = ' . $this->oDao->oDb->escapeStr( $aParams['itemId'] );
			}
		}

		if( !empty($aParams['userId']) ) {
			if( is_array($aParams['userId']) ) {
				$aCriterias[] = 'entFinancingToItem.userId IN (' . implode( ', ', array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['userId']) ) . ')';
			} else {
				$aCriterias[] = 'entFinancingToItem.userId = ' . $this->oDao->oDb->escapeStr( $aParams['userId'] );
			}
		}

		if( $aParams['includeFinancingData'] === true ) {
			$aDaoParams['entities'][] = 'entFinancing';
			$aCriterias[] = 'entFinancingToItem.financingId = entFinancing.financingId';
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->oDao->readData( $aDaoParams );
	}

}
