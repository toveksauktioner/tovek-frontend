<?php

/**
 * Filename: clAuctionTag.php
 * Created: 18/03/2014 by Mikael
 * Reference: database-overview.mwb
 * Description:
 */

require_once PATH_CORE . '/clModuleBase.php';

class clAuctionTag extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'AuctionTag';
		$this->sModulePrefix = 'auctionTag';
		
		$this->oDao = clRegistry::get( 'clAuctionTagDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/auction/models' );
		
		$this->initBase();
	}
	
	public function read( $aFields = array(), $primaryId = null ) {
		
		$aFields = (array) $aFields;
	
		// Handle routePath
		if( in_array('routePath', $aFields) || in_array('*', $aFields) ) {
			$oRouter = clRegistry::get( 'clRouter' );
			
			// Route
			$aTagRoutes = arrayToSingle( $oRouter->readByObject($primaryId, 'AuctionTag', array(
				'entRouteToObject.objectId',
				'entRoute.routePath'
			)), 'objectId', 'routePath' );
		
			if( !in_array('*', $aFields) ) {
				$aTempFields = array( 'tagId' );
				foreach( $aFields as $sField ) {
					if( $sField != 'routePath' ) {
						$aTempFields[] = $sField;
					}
				}
				$aFields = $aTempFields;
			}
		}
		
		$aData = parent::read( $aFields, $primaryId );
		
		// Handle routePath
		if( in_array('routePath', $aFields) || in_array('*', $aFields) ) {
			
			$aTempData = array();
			foreach( $aData as $entry ) {
				if( isset($aTagRoutes[$entry['tagId']]) ) {
					$entry['routePath'] = $aTagRoutes[$entry['tagId']];
				}
				else {
					$entry['routePath'] = '/';
				}
				
				$aTempData[] = $entry;
			}
			
			$aData = $aTempData;
		}
		
		return $aData;
	}
	
	public function createRelation( $iAuctionId, $iItemId, $mTagId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );		
		$aData = array();		
		foreach( (array) $mTagId as $iTagId ) {
			$aData[] = array(
				'relationTagId' => $iTagId,
				'relationItemId' => $iItemId,
				'relationAuctionId' => $iAuctionId,
				'relationCreated' => date( 'Y-m-d H:i:s' )
			);
		}		
		return $this->oDao->createRelation( $aData );
	}
	
	public function delete( $iPrimaryId ) {
		$mResult = parent::delete( $iPrimaryId );
		
		if( $mResult ) {
			// Delete route connected to the tag	
			$oRouter = clRegistry::get( 'clRouter' );
		
			$iRouteId = current( current($oRouter->readByObject($iPrimaryId, $this->sModuleName, 'entRoute.routeId')) );
			
			if( $iRouteId ) {
				$oRouter->delete( $iRouteId );
				$oRouter->deleteRouteToObjectByObject( $iPrimaryId, $this->sModuleName );
			}
		}
		
		return $mResult;
	}

	public function deleteRelation( $iRelationId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteRelation( $iRelationId );		
	}

	public function deleteRelationByAuctionItem( $iAuctionId, $iItemId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteRelationByAuctionItem( $iAuctionId, $iItemId );		
	}
	
	public function updateRelation( $iAuctionId, $iItemId, $mTagId ) {
		$this->deleteRelationByAuctionItem( $iAuctionId, $iItemId );
		return $this->createRelation( $iAuctionId, $iItemId, $mTagId );
	}
	
	public function readRelation( $aFields = array(), $iRelationId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'relationId' => $iRelationId
		);
		return $this->oDao->readRelation( $aParams );
	}
	
	public function readRelationByAuction( $iAuctionId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'auctionId' => $iAuctionId
		);
		return $this->oDao->readRelation( $aParams );
	}
	
	public function readRelationByItem( $iItemId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'itemId' => $iItemId
		);
		return $this->oDao->readRelation( $aParams );
	}
	
	public function readRelationByTag( $iTagId, $aFields = array(), $aParams = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams += array(
			'fields' => $aFields,
			'tagId' => $iTagId
		);
		return $this->oDao->readRelation( $aParams );
	}
	
}
