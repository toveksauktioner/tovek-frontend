<?php

require_once PATH_CORE . '/clModuleBase.php';

class clFreight extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'freight';

		$this->oDao = clRegistry::get( 'clFreightDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/freight/models' );
		$this->initBase();
	}

	public function deleteByCountry( $countryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteByCountry( $countryId );
	}

	public function readByCountry( $countryId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readByCountry( $countryId, $aFields );
	}

	public function updateByCountry( $countryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->updateByCountry( $countryId, $aData );
	}
	
	// Types below
	public function createType( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );		
		$aData['freightTypeCreated'] = date( 'Y-m-d H:i:s' );
		return $this->oDao->createType( $aData );
	}	
	
	public function deleteType( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$result = $this->oDao->deleteType( $primaryId );
		if( !empty($result) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'The data has been deleted' )
			) );
		}
		return $result;
	}
	
	public function readType( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readType( $primaryId, $aFields );
	}
	
	public function readTypeByCountry( $iCountryId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readType( null, $aFields, $iCountryId );
	}
	
	public function updateType( $primaryId, $aData ) {		
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		if( empty($primaryId) ) return false;
		
		$result = $this->oDao->updateType( $primaryId, $aData );
		if( $result !== false ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
		}
		return $result;
	}

	public function updateSort() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aPrimaryIds = func_get_args();		
		return $this->oDao->updateSort( $aPrimaryIds );
	}

	// Types to country below
	public function createFreightTypeToCountry( $iFreightTypeId, $aCountryIds ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aData = array();
		foreach( $aCountryIds as $iCountryId ) {
			$aData[] = array(
				'freightTypeId' => $iFreightTypeId,
				'countryId' => $iCountryId
			);
		}
		
		return $this->oDao->createMultipleData( $aData, array(
			'entities' => 'entFreightTypeToCountry',
			'fields' => array(
				'freightTypeId',
				'countryId'
			)
		) );
	}
	
	public function readFreightTypeToCountry( $mFreightTypeId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'entities' => 'entFreightTypeToCountry',
			'fields' => array(
				'freightTypeId',
				'countryId'
			)
		);
		
		if( $mFreightTypeId !== null ) {
			if( is_array($mFreightTypeId) ) {
				$aParams['criterias'] = 'entFreightTypeToCountry.freightTypeId IN(' . implode( ', ', array_map('intval', $mFreightTypeId) ) . ')';
			} else {
				$aParams['criterias'] = 'entFreightTypeToCountry.freightTypeId = ' . (int) $mFreightTypeId;
			}
		}
		
		return $this->oDao->readData( $aParams );
	}
	
	public function deleteFreightTypeToCountry( $iFreightTypeId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteData( array(
			'entities' => 'entFreightTypeToCountry',
			'criterias' => 'freightTypeId = ' . (int) $iFreightTypeId
		) );
	}
	
	public function updateFreightTypeToCountry( $iFreightTypeId, $aCountryIds ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$this->deleteFreightTypeToCountry( $iFreightTypeId );
		$this->createFreightTypeToCountry( $iFreightTypeId, $aCountryIds );
		return true;
	}
	
	// Free freight limit to country below
	public function createFreightFreeLimitToCountry( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createMultipleData( $aData, array(
			'entities' => 'entFreightFreeLimitToCountry',
			'fields' => array(
				'freightFreeLimit',
				'countryId'
			)
		) );
	}
	
	public function readFreightFreeLimitToCountry( $iCountryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readData( array(
			'entities' => 'entFreightFreeLimitToCountry',
			'fields' => array(
				'freightFreeLimit',
				'countryId'
			),
			'criterias' => !empty($iCountryId) ? 'countryId = ' . (int) $iCountryId : null
		) );
	}
	
	public function deleteFreightFreeLimitToCountry() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteData( array(
			'entities' => 'entFreightFreeLimitToCountry'
		) );
	}
	
	public function updateFreightFreeLimitToCountry( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$this->deleteFreightFreeLimitToCountry();
		$this->createFreightFreeLimitToCountry( $aData );
		return true;
	}

}