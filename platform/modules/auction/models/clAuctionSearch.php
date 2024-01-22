<?php

/**
 * Filename: clAuctionSearch.php
 * Created: 18/03/2014 by Mikael
 * Reference: database-overview.mwb
 * Description:
 */

require_once PATH_CORE . '/clModuleBase.php';

class clAuctionSearch extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'AuctionSearch';
		$this->sModulePrefix = 'auctionSearch';
		
		$this->oDao = clRegistry::get( 'clAuctionSearchDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/auction/models' );
		
		$this->initBase();
	}
	
	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		$aData[$this->sModulePrefix . 'Created'] = date( 'Y-m-d H:i:s' );
		
		if( $this->oDao->createData($aData, $aParams) ) {
			return $this->oDao->oDb->lastId();
		}
		return false;
	}
	
	public function readByUser( $iUserId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		if( $iUserId === null ) $iUserId = $_SESSION['userId'];
		return $this->oDao->readByUser( $iUserId );
	}
	
}
