<?php

require_once PATH_CORE . '/clModuleBase.php';

class clAuctionTransfer extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'AuctionTransfer';
		$this->sModulePrefix = 'auctionTransfer';
		
		$this->oDao = clRegistry::get( 'clAuctionTransferDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/auctionTransfer/models' );
		
		$this->initBase();		
	}

	/**
	 * Import
	 */
	public function import() {
		
	}
	
	/**
	 * Export
	 */
	public function export() {
		
	}
	
	/**
	 * Export bids
	 */
	public function exportBidHistory( $mAuctionId ) {
		die( 'Stop' );
		
		$aAuctionIds = is_array($mAuctionId) ? $mAuctionId : (array) $mAuctionId;
		
		$oAuctionBid = clRegistry::get( 'clAuctionBid', PATH_MODULE . '/auction/models' );
		$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
		
		$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );		
		$oBackEnd->setSource( 'entAuctionBid', 'bidId' );
		
		$oBackEnd->oDao->aDataDict = array(
			'entAuctionBid' => array(
				'bidId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Bid ID' )
				),
				'bidType' => array(
					'type' => 'array',
					'values' => array(
						'manually' => _( 'Manually' ),
						'auto' => _( 'Auto' )
					),
					'title' => _( 'Type' )
				),
				'bidStatus' => array(
					'type' => 'array',
					'values' => array(
						'processing' => _( 'Processing' ),
						'successful' => _( 'Successful' ),
						'unsuccessful' => _( 'Unsuccessful' )
					),
					'title' => _( 'Status' )
				),
				'bidValue' => array(
					'type' => 'float',
					'title' => _( 'Value' )
				),
				'bidTransactionId' => array(
					'type' => 'string',
					'title' => _( 'Transaction ID' )
				),
				'bidError' => array(
					'type' => 'text',
					'title' => _( 'Error' )
				),
				'bidCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				// Foreign key's
				'bidAuctionId' => array(
					'type' => 'integer',
					'title' => _( 'Auction' )
				),
				'bidItemId' => array(
					'type' => 'integer',
					'title' => _( 'Item' )
				),
				'bidUserId' => array(
					'type' => 'integer',
					'title' => _( 'User' )
				),
				'bidOldUsername' => array(
					'type' => 'string',
					'title' => _( 'Old username' )
				),
				'bidRemoved' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Removed' )
				)
			)
		);
		
		foreach( $aAuctionIds as $iAuctionId ) {
			$aItemIds = arrayToSingle( $oAuctionItem->readByAuction( $iAuctionId, null, 'itemId' ), null, 'itemId' );
			
			foreach( $aItemIds as $aItemId ) {
				// Bid data
				$aBids = $oAuctionBid->readHistory( array(
					'itemId' => $aItemId,
					'exported' => 'no',
					'sorting' => array( 'historyBidValue' => 'ASC' )
				) );
				
				if( empty($aBids) ) continue;
				
				$aInsertedHistoryIds = array();
				foreach( $aBids as $aBid ) {				
					$oBackEnd->oDao->createData( array(
						'bidType' => $aBid['historyBidType'],
						'bidStatus' => 'successful',
						'bidValue' => $aBid['historyBidValue'],
						'bidTransactionId' => md5( $aBid['historyBidAuctionId'] . $aBid['historyBidItemId'] . $aBid['historyBidUserId'] ),
						//'bidError' => '',
						'bidCreated' => date( 'Y-m-d H:i:s', substr( $aBid['historyBidPlaced'], 0, strrpos( $aBid['historyBidPlaced'], '.') ) ),
						'bidAuctionId' => $aBid['historyBidAuctionId'],
						'bidItemId' => $aBid['historyBidItemId'],
						'bidUserId' => $aBid['historyBidUserId'],
						//'bidOldUsername' => '',
						'bidRemoved' => 'no'
					) );
					$aInsertedHistoryIds[] = $aBid['historyId'];
				}
				
				if( !empty($aInsertedHistoryIds) ) {
					$oAuctionBid->oDao->updateData( array(
						'historyExported' => 'yes'
					), array(
						'entities' => 'entAuctionBidHistory',
						'criterias' => 'historyId IN(' . implode( ', ', array_map('intval', $aInsertedHistoryIds) ) . ')'
					) );
				}
			}
		}
		
		echo '<pre>';
		var_dump( $aInsertedHistoryIds );
		die;
	}
	
}