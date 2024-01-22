<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clRouterHelperDaoSql.php';

class clAuctionItemDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entAuctionItem' => array(
				'itemId' => array(
					'type' => 'integer',
					'primary' => true,
					'title' => _( 'Item ID' )
				),
				'itemSortNo' => array(
					'type' => 'integer',
					'title' => _( 'Item' )
				),
				'itemSortLetter' => array(
					'type' => 'string',
					'title' => _( 'Split item letter' )
				),
				'itemTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'itemSummary' => array(
					'type' => 'string',
					'title' => _( 'Summary' )
				),
				'itemDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'itemInformation' => array(
					'type' => 'string',
					'title' => _( 'Information' )
				),
				'itemYoutubeLink' => array(
					'type' => 'string',
					'title' => _( 'Youtube-länk' )
				),
				/**
				 *
				 */
				'itemWinningBidId' => array(
					'type' => 'integer',
					'title' => _( 'Winning bid' )
				),
				'itemWinningBidValue' => array(
					'type' => 'float',
					'title' => _( 'Winning bid value' )
				),
				'itemWinningUserId' => array(
					'type' => 'integer',
					'title' => _( 'Winning user' )
				),
				'itemWinnerMailed' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Winner mailed' )
				),
				'itemMinBid' => array(
					'type' => 'float',
					'title' => _( 'Min bid' )
				),
				'itemBidCount' => array(
					'type' => 'integer',
					'title' => _( 'Bid count' )
				),
				'itemStatus' => array(
					'type' => 'array',
					'values' => array(
						'inactive' => _( 'Inactive' ),
						'active' => _( 'Active' ),
						'ended' => _( 'Ended' ),
						'cancelled' => _( 'Cancelled' )
					),
					'title' => _( 'Status' )
				),
				'itemEndTime' => array( # name change
					'type' => 'datetime',
					'title' => _( 'End time' )
				),
				'itemMarketValue' => array(
					'type' => 'float',
					'title' => _( 'Market value' )
				),
				'itemFeeType' => array(
					'type' => 'array',
					'values' => array(
						'none' => _( 'None' ),
						'percent' => _( 'Percent' ),
						'sek' => _( 'SEK' )
					),
					'title' => _( 'Fee type' )
				),
				'itemFeeValue' => array(
					'type' => 'float',
					'title' => _( 'Fee value' )
				),
				'itemVatValue' => array(
					'type' => 'integer',
					'min' => 0,
					'max' => 100,
					'title' => _( 'VAT (%)' )
				),
				'itemLocation' => array(
					'type' => 'string',
					'title' => _( 'Location' )
				),
				'itemRecalled' => array(
					'type' => 'array',
					'values' => array(
						'yes' => _( 'Yes' ),
						'no' => _( 'No' )
					),
					'title' => _( 'Item is recalled' )
				),
				'itemHot' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Hot item' )
				),
				'itemViewedCount' => array(
					'type' => 'integer',
					'title' => _( 'Viewed count' )
				),
				'itemCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'itemComment' => array(
					'type' => 'string',
					'title' => _( 'Comment' )
				),
				'itemNeedsAttention' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Behöver administreras' )
				),
				// Foreign key's
				'itemCreatedByUserId' => array(
					'type' => 'integer'
				),
				'itemAuctionId' => array(
					'type' => 'integer'
				),
				'itemPartId' => array(
					'type' => 'integer'
				),
				'itemSubmissionId' => array(
					'type' => 'integer',
					'title' => _( 'Submission' )
				),
				'itemSubmissionCustomId' => array(
					'type' => 'string',
					'title' => _( 'Import Submission ID' )
				),
				'itemAddressId' => array(
					'type' => 'integer',
					'title' => _( 'Address' )
				),
				'itemOldItemId' => array(
					'type' => 'integer',
					'title' => _( 'Old item ID' )
				),
				'itemCopiedToItemId' => array(
					'type' => 'integer',
					'title' => _( 'Item copied to ID' )
				),
				'itemVehicleArchiveImageId' => array(
					'type' => 'integer',
					'title' => _( 'Arkivbild' )
				),
				'itemVehicleDataId' => array(
					'type' => 'integer',
					'title' => _( 'Fordons-ID' )
				),
				/**
				 * Auto bid lock
				 */
				'itemAutoBidLocked' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					)
				)
			),
			'entAuctionItemToItem' => array(
				'relationId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'relationFromId' => array(
					'type' => 'integer',
					'index' => true
				),
				'relationFromType' => array(
					'type' => 'string',
				),
				'relationToId' => array(
					'type' => 'integer',
					'index' => true
				),
				'relationToType' => array(
					'type' => 'string'
				),
				'relationCreated' => array(
					'type' => 'datetime'
				)
			),
			'entAuctionItemToUser' => array(
				'itemId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'Item ID' )
				),
				'userId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'User ID' )
				),
				'relationType' => array(
					'type' => 'array',
					'values' => array(
						'favorite' => _( 'Favorite' )
					),
					'title' => _( 'Relation type' )
				)
			)
		);

		$this->sPrimaryField = 'itemId';
		$this->sPrimaryEntity = 'entAuctionItem';
		$this->aFieldsDefault = '*';

		$this->init();

		$this->aHelpers = array(
			'oRouterHelper' => new clRouterHelperDaoSql( $this, array(
				'parentEntity' => $this->sPrimaryEntity,
				'parentPrimaryField' => $this->sPrimaryField,
				'parentType' => 'AuctionItem'
			) )
		);
	}

	/**
	 * Read data
	 */
	public function read( $aParams = array() ) {
        $aParams += array(
			'fields' => $this->aFieldsDefault,
            'itemId' => null,
			'createdByUserId' => null,
			'auctionId' => null,
			'partId' => null,
			'submissionId' => null,
			'submissionCustomId' => null,
			'addressId' => null,
			'sorting' => null,
			'entries' => null
		);

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'sorting' => $aParams['sorting'],
			'entries' => $aParams['entries']
		);

		$aCriterias = array();

		if( $aParams['itemId'] !== null ) {
			if( is_array($aParams['itemId']) ) {
				$aCriterias[] = 'itemId IN(' . implode( ', ', array_map('intval', $aParams['itemId']) ) . ')';
			} else {
				$aCriterias[] = 'itemId = ' . (int) $aParams['itemId'];
			}
		}

		if( $aParams['createdByUserId'] !== null ) {
			if( is_array($aParams['CreatedByUserId']) ) {
				$aCriterias[] = 'itemCreatedByUserId IN(' . implode( ', ', array_map('intval', $aParams['createdByUserId']) ) . ')';
			} else {
				$aCriterias[] = 'itemCreatedByUserId = ' . (int) $aParams['createdByUserId'];
			}
		}

		if( $aParams['auctionId'] !== null ) {
			if( is_array($aParams['AuctionId']) ) {
				$aCriterias[] = 'itemAuctionId IN(' . implode( ', ', array_map('intval', $aParams['auctionId']) ) . ')';
			} else {
				$aCriterias[] = 'itemAuctionId = ' . (int) $aParams['auctionId'];
			}
		}

		if( $aParams['partId'] !== null ) {
			if( is_array($aParams['PartId']) ) {
				$aCriterias[] = 'itemPartId IN(' . implode( ', ', array_map('intval', $aParams['partId']) ) . ')';
			} else {
				$aCriterias[] = 'itemPartId = ' . (int) $aParams['partId'];
			}
		}

		if( $aParams['submissionId'] !== null ) {
			if( is_array($aParams['submissionId']) ) {
				$aCriterias[] = 'itemSubmissionId IN(' . implode( ', ', array_map('intval', $aParams['submissionId']) ) . ')';
			} else {
				$aCriterias[] = 'itemSubmissionId = ' . (int) $aParams['submissionId'];
			}
		}

		if( $aParams['submissionCustomId'] !== null ) {
			if( is_array($aParams['submissionCustomId']) ) {
				$aCriterias[] = 'itemSubmissionCustomId IN("' . implode( '", "', array_map('strval', $aParams['submissionCustomId']) ) . '")';
			} else {
				$aCriterias[] = 'itemSubmissionCustomId = "' . (string) $aParams['submissionCustomId'] . '"';
			}
		}

		if( $aParams['addressId'] !== null ) {
			if( is_array($aParams['addressId']) ) {
				$aCriterias[] = 'itemAddressId IN(' . implode( ', ', array_map('intval', $aParams['addressId']) ) . ')';
			} else {
				$aCriterias[] = 'itemAddressId = ' . (int) $aParams['addressId'];
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return parent::readData( $aDaoParams );
    }

	/**
	 * Read item realtions
	 * $aRelationId is array with two item IDs
	 */
	public function readItemRelation( $iFromRelationId = null ) {
		$aDaoParams = array(
			'entities' => 'entAuctionItemToItem',
			'criterias' =>  $iFromRelationId != null ? ('relationFromId = ' . $this->oDb->escapeStr($iFromRelationId)) : null
		);
		return $this->readData( $aDaoParams );
	}

	/**
	 * Function to mark/unmark item as favorite
	 */
	public function updateFavoriteItem( $iItemId, $iUserId, $bStatus ) {
		$this->deleteData( array(
			'entities' => 'entAuctionItemToUser',
			'criterias' => 'itemId = ' . $this->oDb->escapeStr( $iItemId ) . ' AND userId = ' . $this->oDb->escapeStr( $iUserId )
		) );
		if( $bStatus == 'true' ) {
			$aData = array(
				'itemId' => $iItemId,
				'userId' => $iUserId,
				'relationType' => 'favorite'
			);
			$aDaoParams = array(
				'entities' => 'entAuctionItemToUser',
				'fields' => array(
					'itemId',
					'userId',
					'relationType'
				)
			);
			$this->createData( $aData, $aDaoParams );
		}
		return true;
	}

	/**
	 * Function read favorite item
	 */
	public function readFavoritesByUser( $iUserId ) {
		$aDaoParams = array(
			'entities' => 'entAuctionItemToUser',
			'fields' => array(
				'itemId',
				'userId',
				'relationType'
			),
			'criterias' => 'userId = ' . $this->oDb->escapeStr( $iUserId ) . ' AND relationType = "favorite"'
		);
		return $this->readData( $aDaoParams );
	}

	/**
	 * - JUST FOR DEVELOPMENT TIME! -
	 * Update end time
	 */
	public function updateEndTimeByAuctionPart( $iPartId, $sStartDate, $iInterval ) {
		$sStartDate = date( 'Y-m-d H:i:s', strtotime( '-' . $iInterval . ' minutes', strtotime($sStartDate) ) );

		$this->oDb->write( 'SET @serial := 0;' );
		return $this->oDb->write( '
			UPDATE ' . $this->sPrimaryEntity . '
			SET itemEndTime = ADDDATE( ' . $this->oDb->escapeStr($sStartDate) . ', INTERVAL ' . $iInterval . '*(@serial := @serial + 1) MINUTE )
			WHERE itemPartId = ' . $this->oDb->escapeStr( $iPartId ) . '
			AND itemStatus IN("inactive","active","ended")
			ORDER BY itemSortNo ASC'
		);
	}

	/**
	 * Update item status by auction
	 */
	public function updateStatusByAuctionPart( $iPartId, $sStatus ) {
		return $this->updateData(
			array(
				'itemStatus' => $sStatus
			),
			array(
				'criterias' => 'itemPartId = ' . $this->oDb->escapeStr( $iPartId )
			)
		);
	}

	public function increaseViewedCount( $iItemId ) {
		return $this->oDb->query( "
			UPDATE entAuctionItem
			SET itemViewedCount = itemViewedCount + 1
			WHERE itemId = " . $this->oDb->escapeStr($iItemId) . "
		" );
	}

}
