<?php

require_once PATH_CORE . '/clModuleBase.php';

class clFreightRelation extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'FreightRelation';
		$this->sModulePrefix = 'freightRelation';
		
		$this->oDao = clRegistry::get( 'clFreightRelationDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/freight/models' );
		$this->initBase();
	}
	
	/**
	 *
	 * @array aData (
	 * 		'relationProductId',
	 *		'relationFreightPriceType',
	 *		'relationElevatedPrice',
	 *		'relationFreightTypeIds' => array()
	 * )
	 * 
	 */
	public function createRelation( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		// All active freight types
		$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );
		$oFreight->oDao->setCriterias( array(
			'getActiveFreights' => array(
				'type' => '=',
				'value' => 'active',
				'fields' => array( 'entFreightType.freightTypeStatus' )
			)
		) );		
		$aFreightTypes = arrayToSingle( $oFreight->readType( array(
			'freightTypeId'
		) ), null, 'freightTypeId' );
		// Reset criterias
		$oFreight->oDao->sCriterias = null;
		
		return $this->oDao->createRelation( $aData, $aFreightTypes );
	}
	
	public function readByProduct( $aProductIds ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readByProduct( $aProductIds );
	}
	
	public function deleteByProduct( $iProductId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'criterias' => 'relationProductId = ' . (int) $iProductId
		);
		return $this->oDao->deleteData( $aParams );
	}
	
	public function deleteByProducts() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );		
		$aParams = array(
			'criterias' => '
				relationProductId NOT IN (
				SELECT productId AS relationProductId
				FROM entProduct
			)'
		);		
		return $this->oDao->deleteData( $aParams );		
	}
	
	public function deleteByFreightType( $mFreightTypeId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		if( is_array($mFreightTypeId) ) {
			$aParams = array(
				'criterias' => 'relationFreightTypeId IN("' . implode('","', $mFreightTypeId) . '")'
			);
		} else {
			$aParams = array(
				'criterias' => 'relationFreightTypeId = ' . (int) $mFreightTypeId
			);
		}
		return $this->oDao->deleteData( $aParams );
	}
	
	public function updateRelation( $aData, $iProductId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );		
		$this->deleteByProduct( $iProductId );
		return $this->createRelation( $aData );
	}
	
	/* * *
	 * Shortcut way to remove all relations at once
	 */
	public function deleteAllRelations() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->oDb->query( "TRUNCATE TABLE entFreightRelation" );
	}
	
	/* * *
	 * This function fetch all products and send it
	 * to function 'resetRelationToProduct' in Dao.
	 */
	public function resetRelationToAllProducts() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );
		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
		
		// Clear all relations
		$this->deleteAllRelations();
		
		// Freight types		
		$oFreight->oDao->setCriterias( array(
			'getActiveFreights' => array(
				'type' => '=',
				'value' => 'active',
				'fields' => array( 'entFreightType.freightTypeStatus' )
			)
		) );	
		$aFreightTypes = $oFreight->readType( array(
			'freightTypeId'
		) );
		$oFreight->oDao->sCriterias = null;
		$aFreightTypes = arrayToSingle( $aFreightTypes, null, 'freightTypeId' );
		
		// All products		
		$aProductIds = $oProduct->read( array( 'productId' ) );
		$aProductIds = arrayToSingle( $aProductIds, null, 'productId' );
		
		// Create new relations
		if( $this->oDao->resetRelationToProduct( $aFreightTypes, $aProductIds ) ) {
			// Notify user
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'All freight relations has been reset to default values' )
			) );
			return true;
		}
		return false;
	}
	
	public function resetFreightTypeRelationToAllProducts( $mFreightTypeId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
		
		// Freight types
		if( !is_array($mFreightTypeId) ) {
			$aFreightTypes = (array) $mFreightTypeId;
		} else {
			$aFreightTypes = $mFreightTypeId;
		}
		
		// Delete existing
		$this->deleteByFreightType( $aFreightTypes );
		
		// All products		
		$aProductIds = $oProduct->read( array( 'productId' ) );
		$aProductIds = arrayToSingle( $aProductIds, null, 'productId' );
		
		// Create new relations
		if( $this->oDao->resetRelationToProduct( $aFreightTypes, $aProductIds ) ) {
			// Notify user
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'All freight relations has been reset to default values' )
			) );
			return true;
		}
		return false;
	}
	
}