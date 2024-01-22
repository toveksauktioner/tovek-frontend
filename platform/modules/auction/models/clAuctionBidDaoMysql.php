<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clRouterHelperDaoSql.php';

class clAuctionBidDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entAuctionBid' => array(
                'bidId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
                'bidType' => array(
                    'type' => 'array',
					'title' => _( 'Type' ),
                    'values' => array(
                        'normal' => _( 'Normal' ),
                        'auto' => _( 'Auto' )
                    )
                ),
                'bidValue' => array(
                    'type' => 'integer',
					'title' => _( 'Value' )
                ),
                'bidTransactionId' => array(
					'type' => 'string',
					'title' => _( 'Transaction ID' )
				),
                'bidPlaced' => array(
					'type' => 'float', # decimal 10,4 in DB (microtime)
					'title' => _( 'Placed' )
				),
				'bidCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'bidRemoved' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Removed' )
				),
				/**
				 * Foreign key's
				 */
				'bidAuctionId' => array(
                    'type' => 'integer',
					'title' => _( 'Auction ID' )
                ),
                'bidPartId' => array(
                    'type' => 'integer',
					'title' => _( 'Part ID' )
                ),
                'bidItemId' => array(
                    'type' => 'integer',
					'title' => _( 'Item ID' )
                ),
                'bidUserId' => array(
					'type' => 'integer',
					'title' => _( 'User' )
				)
            ),
			'entAuctionBidHistory' => array(
                'historyId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'History ID' )
				),
				'historyBidId' => array(
					'type' => 'integer',
					'title' => _( 'Bid ID' )
				),
				'historyBidType' => array(
					'type' => 'string',
					'title' => _( 'Bid type' )
				),
				'historyBidValue' => array(
					'type' => 'integer',
					'title' => _( 'Bid value' )
				),
				'historyBidItemId' => array(
					'type' => 'integer',
					'title' => _( 'Item ID' )
				),
				'historyBidUserId' => array(
					'type' => 'integer',
					'title' => _( 'User ID' )
				),
				'historyBidAuctionId' => array(
					'type' => 'integer',
					'title' => _( 'Auction ID' )
				),
				'historyBidPartId' => array(
                    'type' => 'integer',
					'title' => _( 'Part ID' )
                ),
				'historyBidPlaced' => array(
					'type' => 'float', # decimal 10,4 in DB (microtime)
					'title' => _( 'Placed' )
				),
				'historyCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'historyExported' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Exported' )
				)
			),
			'entAuctionBidTariff' => array(
				'breakValue' => array(
					'type' => 'integer',
					'index' => true
				),
				'minBidValue' => array(
					'type' => 'integer',
					'index' => true
				),
				'maxBidValue' => array(
					'type' => 'integer',
					'index' => true
				),
                /**
				 * Misc
				 */
                'breakCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
                'breakUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
        );

        $this->sPrimaryField = 'bidId';
		$this->sPrimaryEntity = 'entAuctionBid';
		$this->aFieldsDefault = '*';

		$this->init();

    }

	/**
     * Save bid in DB
     *
     * @param array $aData (bidValue, bidAuctionId, bidPartId, bidItemId, bidType)
     * @return mixed
     */
    public function saveBid( $aData ) {
        $aData['bidCreated'] = date( 'Y-m-d H:i:s' );
        $aData['bidPlaced'] = microtime( true ); // 1547624295,4706
        return parent::createData( $aData );
    }

    /**
	 * Read data
	 */
	public function read( $aParams = array() ) {
        $aParams += array(
			'fields' => $this->aFieldsDefault,
            'bidId' => null,
			'itemId' => null,
			'userId' => null,
			'minBid' => null,
			'removed' => 'no',
			'customSort' => null,
			'entries' => null
		);

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entries' => $aParams['entries']
		);

		$aCriterias = array(
			sprintf( "bidRemoved = '%s'", $aParams['removed'] )
		);

		if( $aParams['bidId'] !== null ) {
			if( is_array($aParams['bidId']) ) {
				$aCriterias[] = 'bidId IN(' . implode( ', ', array_map('intval', $aParams['bidId']) ) . ')';
			} else {
				$aCriterias[] = 'bidId = ' . (int) $aParams['bidId'];
			}
		}

		if( $aParams['itemId'] !== null ) {
			if( is_array($aParams['itemId']) ) {
				$aCriterias[] = 'bidItemId IN(' . implode( ', ', array_map('intval', $aParams['itemId']) ) . ')';
			} else {
				$aCriterias[] = 'bidItemId = ' . (int) $aParams['itemId'];
			}
		}

		if( $aParams['userId'] !== null ) {
			if( is_array($aParams['userId']) ) {
				$aCriterias[] = 'bidUserId IN(' . implode( ', ', array_map('intval', $aParams['userId']) ) . ')';
			} else {
				$aCriterias[] = 'bidUserId = ' . (int) $aParams['userId'];
			}
		}

		if( $aParams['minBid'] !== null ) {
			$aCriterias[] = 'bidValue > ' . $this->oDb->escapeStr( $aParams['minBid'] );
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		/**
		 * Sorting is very important
		 * - customSort is named to not be set by mistake
		 */
		$aDaoParams['sorting'] = !empty($aParams['customSort']) ? $aParams['customSort'] : array( 'bidPlaced DESC' );

		return parent::readData( $aDaoParams );
    }

	/**
	 * Read history data
	 */
	public function readHistory( $aParams = array() ) {
		if( !empty($aParams['itemId']) ) $this->updateHistory( $aParams['itemId'] );

		$aParams += array(
			'entities' => 'entAuctionBidHistory',
			'fields' => array_keys( $this->aDataDict['entAuctionBidHistory'] ),
            'bidId' => null,
			'itemId' => null,
			'userId' => null,
			'auctionId' => null,
			'partId' => null,
			'sorting' => array( 'historyBidValue' => 'DESC' ),
			'entries' => null,
			'exported' => null
		);

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entities' => $aParams['entities'],
			'sorting' => $aParams['sorting'],
			'entries' => $aParams['entries'],
			'groupBy' => 'historyBidId'
		);

		$aCriterias = array();

		if( $aParams['bidId'] !== null ) {
			if( is_array($aParams['bidId']) ) {
				$aCriterias[] = 'historyBidId IN(' . implode( ', ', array_map('intval', $aParams['bidId']) ) . ')';
			} else {
				$aCriterias[] = 'historyBidId = ' . (int) $aParams['bidId'];
			}
		}

		if( $aParams['itemId'] !== null ) {
			if( is_array($aParams['itemId']) ) {
				$aCriterias[] = 'historyBidItemId IN(' . implode( ', ', array_map('intval', $aParams['itemId']) ) . ')';
			} else {
				$aCriterias[] = 'historyBidItemId = ' . (int) $aParams['itemId'];
			}
		}

		if( $aParams['userId'] !== null ) {
			if( is_array($aParams['userId']) ) {
				$aCriterias[] = 'historyBidUserId IN(' . implode( ', ', array_map('intval', $aParams['userId']) ) . ')';
			} else {
				$aCriterias[] = 'historyBidUserId = ' . (int) $aParams['userId'];
			}
		}

		if( $aParams['auctionId'] !== null ) {
			if( is_array($aParams['auctionId']) ) {
				$aCriterias[] = 'historyBidAuctionId IN(' . implode( ', ', array_map('intval', $aParams['auctionId']) ) . ')';
			} else {
				$aCriterias[] = 'historyBidAuctionId = ' . (int) $aParams['auctionId'];
			}
		}

		if( $aParams['partId'] !== null ) {
			if( is_array($aParams['partId']) ) {
				$aCriterias[] = 'historyBidPartId IN(' . implode( ', ', array_map('intval', $aParams['partId']) ) . ')';
			} else {
				$aCriterias[] = 'historyBidPartId = ' . (int) $aParams['partId'];
			}
		}

		if( $aParams['exported'] !== null ) {
			if( is_array($aParams['exported']) ) {
				$aCriterias[] = 'historyExported IN("' . implode( '", "', $aParams['exported'] ) . '")';
			} else {
				$aCriterias[] = 'historyExported = ' . $this->oDb->escapeStr( $aParams['exported'] );
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return parent::readData( $aDaoParams );
	}

	/**
	 * Update history data
	 */
	public function updateHistory( $iItemId ) {
		$this->oDb->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$this->oDb->setAttribute( PDO::ATTR_TIMEOUT, 20 );

		try {
			// Read all bids
			$aBids = valueToKey( 'bidId', parent::readData( array(
				'sorting' => array( 'bidPlaced ASC' ),
				'criterias' => '(bidItemId = "' . (int) $iItemId . '" AND bidRemoved = "no")'
				//'criterias' => 'bidItemId = ' . (int) $iItemId
			) ) );
			if( empty($aBids) ) return true;

			// Read all history
			$aBidHistory = valueToKey( 'historyBidId', parent::readData( array(
				'entities' => 'entAuctionBidHistory',
				'sorting' => array( 'historyBidPlaced ASC' ),
				'criterias' => 'historyBidId IN(' . implode(',', arrayToSingle($aBids, null, 'bidId')) . ')'
			) ) );

			if( !empty($aBidHistory) ) {
				foreach( $aBids as $iBidId => $aBid ) {
					if( array_key_exists($iBidId, $aBidHistory) ) {
						unset( $aBids[ $iBidId ] );
					}
				}
			}
			//if( !empty($aBidHistory) ) $aBids = array_diff_key( $aBids, $aBidHistory );

			$aData = array();
			$aPrev = array();
			foreach( $aBids as $aBid ) {
				// First bid
				if( empty($aData) && empty($aBidHistory) ) {
					$aData[] = array(
						'historyBidId' => $aBid['bidId'],
						'historyBidType' => $aBid['bidType'],
						'historyBidValue' => $aBid['bidValue'],
						'historyBidItemId' => $aBid['bidItemId'],
						'historyBidUserId' => $aBid['bidUserId'],
						'historyBidUserId' => $aBid['bidUserId'],
						'historyBidAuctionId' => $aBid['bidAuctionId'],
						'historyBidPartId' => $aBid['bidPartId'],
						'historyBidPlaced' => $aBid['bidPlaced'],
						'historyCreated' => date( 'Y-m-d H:i:s' )
					);
					$aPrev = $aBid;
					continue;

				} elseif( empty($aData) && !empty($aBidHistory) ) {
					$aPrevHistory = current( array_reverse($aBidHistory, true) );
					$aPrev = array(
						 'bidId' => $aPrevHistory['historyBidId'],
						 'bidType' => $aPrevHistory['historyBidType'],
						 'bidValue' => $aPrevHistory['historyBidValue'],
						 'bidItemId' => $aPrevHistory['historyBidItemId'],
						 'bidUserId' => $aPrevHistory['historyBidUserId'],
						 'bidPlaced' => $aPrevHistory['historyBidPlaced'],
						 'bidCreated' => $aPrevHistory['historyCreated']
					);
				}

				// Breakpoint
				$aBreakpoint = array();
				foreach( AUCTION_BID_TARIFF as $iKey => $aTariff ) {
					if( $aBid['bidValue'] > $aTariff['break'] ) $aBreakpoint = $aTariff;
				}

				if( $aBid['bidType'] == 'auto' ) {
					$aData[] = array(
						'historyBidId' => $aBid['bidId'],
						'historyBidType' => $aBid['bidType'],
						'historyBidValue' => $aBid['bidValue'],
						'historyBidItemId' => $aBid['bidItemId'],
						'historyBidUserId' => $aBid['bidUserId'],
						'historyBidAuctionId' => $aBid['bidAuctionId'],
						'historyBidPartId' => $aBid['bidPartId'],
						'historyBidPlaced' => $aBid['bidPlaced'],
						'historyCreated' => date( 'Y-m-d H:i:s' )
					);
					$aPrev = $aBid;
				} else {
					if(
						( !empty($aPrev) && ($aBid['bidValue'] - $aPrev['bidValue']) >= $aBreakpoint['min'] ) ||
						( empty($aPrev) && $aBid['bidValue'] >= $aBreakpoint['min'] )
					) {
						$aData[] = array(
							'historyBidId' => $aBid['bidId'],
							'historyBidType' => $aBid['bidType'],
							'historyBidValue' => $aBid['bidValue'],
							'historyBidItemId' => $aBid['bidItemId'],
							'historyBidUserId' => $aBid['bidUserId'],
							'historyBidAuctionId' => $aBid['bidAuctionId'],
							'historyBidPartId' => $aBid['bidPartId'],
							'historyBidPlaced' => $aBid['bidPlaced'],
							'historyCreated' => date( 'Y-m-d H:i:s' )
						);
						$aPrev = $aBid;
					}
				}
			}

			if( empty($aData) ) return true; // No acceptable bids..

			// Old way!
			//$var = parent::createMultipleData( $aData, array(
			//	'entities' => 'entAuctionBidHistory',
			//	'fields' => array(
			//		'historyBidId',
			//		'historyBidType',
			//		'historyBidValue',
			//		'historyBidItemId',
			//		'historyBidUserId',
			//		'historyBidPlaced',
			//		'historyCreated'
			//	)
			//) );

			// Array of column names
			$aDataKeys = array_keys( current($aData) );

			// Removed ---->
			// $sQuery = "
			// 	INSERT INTO entAuctionBidHistory(" . implode(',', $aDataKeys) . ")
			// 	SELECT :" . implode(',:', $aDataKeys) . "
			// 	FROM (SELECT 1) entTemporaryTable
			// 	WHERE NOT EXISTS(
			// 		SELECT * FROM entAuctionBidHistory
			// 		WHERE historyBidId = :historyBidId
			// 		LIMIT 1
			// 	)"; 
			// <----- Inserted ---->
			$sQuery = "
				INSERT INTO entAuctionBidHistory(" . implode(',', $aDataKeys) . ")
				SELECT :" . implode(',:', $aDataKeys) . "
				FROM (SELECT 1) entTemporaryTable
				WHERE NOT EXISTS(
					SELECT * FROM entAuctionBidHistory
					WHERE
						historyBidId = :historyBidId
						OR (
							historyBidItemId = :historyBidItemId
							AND historyBidUserId = :historyBidUserId
							AND historyBidValue = :historyBidValue
						)
						OR (
							historyBidItemId = :historyBidItemId
							AND historyBidValue >= :historyBidValue
						)
					LIMIT 1
				)";
			// <-----

			//$sQuery = "
			//	INSERT INTO entAuctionBidHistory(" . implode(',', $aDataKeys) . ")
			//	SELECT :" . implode(',:', $aDataKeys) . "
			//	FROM (SELECT 1) entTemporaryTable
			//	WHERE NOT EXISTS(
			//		SELECT * FROM entAuctionBidHistory
			//		WHERE historyBidId = :historyBidId
			//	) <=> NULL LOCK IN SHARE MODE";

			// Prepare transaction query
			$this->oDb->prepare( $sQuery );

			// Turns off autocommit mode and begin transaction
			$this->oDb->beginTransaction();

			foreach( $aData as $aEntry ) {
				// Bind data
				//$aEntry = array_combine( array_map( function($key) { return ":" . $key; }, array_keys($aEntry) ), $aEntry );

				// Write bid to db
				$this->oDb->execute( array_combine(
					array_map(
						function($key) { return ":" . $key; },
						array_keys($aEntry)
					),
				$aEntry ) );

				// Fetch bid ID
				$iLastInsertId = (int) $this->oDb->lastId();

				if( $iLastInsertId == 0 ) {
					/**
					 *	- Unsuccessful -
					 *	Placing bid was unsuccessful
					 */

				} else {
					/**
					 *	- Successful -
					 *	Placing bid was successful
					 */

					// Update item bid count
					$this->oDb->write( "
						UPDATE entAuctionItem
						SET itemBidCount = itemBidCount + 1
						WHERE itemId = " . $this->oDb->escapeStr( $aEntry['historyBidItemId'] )
					);

					// End time check
					$this->oDb->write( "
						UPDATE entAuctionItem
						SET itemEndTime = DATE_ADD(NOW(), INTERVAL 120 SECOND)
						WHERE itemId = " . $this->oDb->escapeStr( $aEntry['historyBidItemId'] ) . "
						AND DATE_SUB(itemEndTime, INTERVAL 120 SECOND) <= NOW()"
					);
				}
			}

			// Finish transaction
			$this->oDb->commit();

		} catch( PDOException $oError ) {
			// Bid could not be placed
			$this->oDb->rollBack();

			//echo '<pre>';
			//var_dump( $oError );
			//die;

			// Return error
			return $oError;
		}
	}

}
