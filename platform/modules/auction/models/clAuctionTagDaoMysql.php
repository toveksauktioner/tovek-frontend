<?php

/* * * *
 * Filename: clAuctionTagDaoMysql.php
 * Created: 18/03/2014 by Mikael
 * Reference: database-overview.mwb
 * Description: See clAuctionTag.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clParentChildHelperDaoSql.php';

class clAuctionTagDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entAuctionTag' => array(
				'tagId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Tag ID' )
				),
				'tagTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'tagDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'tagItemCount' => array(
					'type' => 'integer'
				),			
				'tagCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
				// Foreign key's
				/* none */
			),
			'entAuctionTagToItem' => array(
				'relationId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Relation ID' )
				),
				// Foreign key's
				'relationTagId' => array(
					'type' => 'integer',
					'index' => true
				),
				'relationItemId' => array(
					'type' => 'integer',
					'index' => true
				),
				'relationAuctionId' => array(
					'type' => 'integer',
					'index' => true
				),
				'relationCreated' => array(
					'type' => 'datetime'
				)
			)
		);
		
		$this->sPrimaryEntity = 'entAuctionTag';
		$this->sPrimaryField = 'tagId';		
		$this->aFieldsDefault = array( '*' );		
		
		$this->aHelpers = array(
			'oParentChildHelperDao' => new clParentChildHelperDaoSql( $this, array(
				// Parent
				'parentEntity' => 'entAuctionTag',
				'parentPrimaryField' => 'tagId',
				'parentCreatedField' => 'tagCreated',
				// Child
				'childEntity' => 'entAuctionTagToItem',
				'childParentField' => 'tagId'				
			) )
		);
		
		$this->init();
	}
	
	public function createRelation( $aData ) {
		$aDaoParams = array(
			'entities' => 'entAuctionTagToItem',
			'fields' => array(
				'relationTagId',
				'relationItemId',
				'relationAuctionId',
				'relationCreated'
			)
		);
		return $this->createMultipleData( $aData, $aDaoParams );
	}

	public function deleteRelation( $iRelationId ) {
		$aDaoParams = array(
			'entities' => 'entAuctionTagToItem',
			'criterias' => 'relationId = ' . $this->oDb->escapeStr($iRelationId)
		);	
		return $this->deleteData( $aDaoParams );
	}

	public function deleteRelationByAuctionItem( $iAuctionId, $iItemId ) {
		$aDaoParams = array(
			'entities' => 'entAuctionTagToItem',
			'criterias' => '
				relationItemId = ' . $this->oDb->escapeStr($iItemId)
				. ' AND relationAuctionId = ' . $this->oDb->escapeStr($iAuctionId)
		);	
		return $this->deleteData( $aDaoParams );
	}
	
	public function readRelation( $aParams = array() ) {		
		$aParams += array(
			'relationId' => null,
			'tagId' => null,
			'auctionId' => null,
			'itemId' => null
		);
		
		$aDaoParams = array(
			'entities' => 'entAuctionTagToItem',
			'fields' => $aParams['fields']
		);
		
		$sCriterias = array();
		
		if( $aParams['relationId'] !== null ) {
			if( is_array($aParams['relationId']) ) {
				$aCriterias[] = 'relationId IN(' . implode( ', ', array_map('intval', $aParams['relationId']) ) . ')';
			} else {
				$aCriterias[] = 'relationId = ' . (int) $aParams['relationId'];
			}
		}
		if( $aParams['tagId'] !== null ) {
			if( is_array($aParams['tagId']) ) {
				$aCriterias[] = 'relationTagId IN(' . implode( ', ', array_map('intval', $aParams['tagId']) ) . ')';
			} else {
				$aCriterias[] = 'relationTagId = ' . (int) $aParams['tagId'];
			}
		}
		if( $aParams['auctionId'] !== null ) {
			if( is_array($aParams['auctionId']) ) {
				$aCriterias[] = 'relationAuctionId IN(' . implode( ', ', array_map('intval', $aParams['auctionId']) ) . ')';
			} else {
				$aCriterias[] = 'relationAuctionId = ' . (int) $aParams['auctionId'];
			}
		}
		if( $aParams['itemId'] !== null ) {
			if( is_array($aParams['itemId']) ) {
				$aCriterias[] = 'relationItemId IN(' . implode( ', ', array_map('intval', $aParams['itemId']) ) . ')';
			} else {
				$aCriterias[] = 'relationItemId = ' . (int) $aParams['itemId'];
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->readData( $aDaoParams );
	}

}
