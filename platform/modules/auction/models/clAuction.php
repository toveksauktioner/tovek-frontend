<?php

require_once PATH_CORE . '/clModuleBase.php';

class clAuction extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'Auction';
		$this->sModulePrefix = 'auction';
		
		$this->oDao = clRegistry::get( 'clAuctionDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/auction/models' );
		
		$this->initBase();		
	}

	/**
	 * Read
	 */
	public function read( $aFields = array(), $iAuctionId = null, $iPartId = null, $aStatus = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		return $this->oDao->read( array(
			'fields' => $aFields,
			'auctionId' => $iAuctionId,
			'partId' => $iPartId,
			'auctionStatus' => !empty($aStatus['auctionStatus']) ? $aStatus['auctionStatus'] : 'active',
			'partStatus' => !empty($aStatus['partStatus']) ? $aStatus['partStatus'] : 'running'
		) );
	}
	
	/**
	 * Read all
	 */
	public function readAll( $aFields = array(), $iAuctionId = null, $iPartId = null, $aStatus = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		return $this->oDao->read( array(
			'fields' => $aFields,
			'auctionId' => $iAuctionId,
			'partId' => $iPartId,
			'auctionStatus' => '*',
			'partStatus' => '*'
		) );
	}
	
	/**
	 * Delete
	 */
	public function delete( $iAuctionId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		// Delete auction
		$mResult = parent::delete( $iAuctionId );
		
		if( is_int($mResult) ) {			
			/**
			 * Delete route
			 */
			$oRouter = clRegistry::get( 'clRouter' );
			$iRouteId = current(current( $oRouter->readByObject( $iAuctionId, $this->sModuleName, 'entRoute.routeId' ) ));					
			if( !empty($iRouteId) ) {
				// Delete route
				$oRouter->deleteRouteToObjectByRoute( $iRouteId );
				$oRouter->delete( $iRouteId );
			}
			
			/**
			 * Delete auction item
			 */
			$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
			$mResult = $oAuctionItem->deleteByAuction( $iAuctionId );
		}
		
		return $mResult;
	}
	
	public function createPart( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createPart( $aData );
	}

	public function createUserRelation( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createUserRelation( $aData );
	}
	
	public function increaseViewedCount( $iAuctionId ) {
		return $this->oDao->increaseViewedCount( $iAuctionId );
	}
	
	/**
	 *
	 * Address related below
	 * 
	 */
	
	public function createAddress( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createAddress( $aData );
	}
	
	public function createAuctionAddress( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createAuctionAddress( $aData );
	}
	
	public function readAuctionAddress( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readAuctionAddress( array('fields' => $aFields), $primaryId );
	}
	
	public function readAuctionAddressByAuctionPart( $iPartId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readAuctionAddressByAuctionPart( $iPartId );
	}
	
	public function updateAuctionAddress( $iAddressId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->updateAuctionAddress( $iAddressId, $aData );
	}
	
	public function deleteAuctionAddress( $iAddressId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteAuctionAddress( $iAddressId );
	}
	
	public function deleteAuctionAddresses( $iAuctionId = null, $iPartId = null ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteAuctionAddresses( $iAuctionId, $iPartId );
	}

}