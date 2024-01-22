<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/auction/config/cfAuction.php';

class clAuctionBid extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'AuctionBid';
		$this->sModulePrefix = 'auctionBid';

		$this->oDao = clRegistry::get( 'clAuctionBidDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/auction/models' );

		$this->initBase();
	}

    /**
     * Place bid and/or auto bid
     *
     * @param array $aData (bidValue, bidAuctionId, bidPartId, bidItemId, bidType, itemMinBid, itemEndTime)
     * @param array $aParams
     *
     * @return array Result data
     */
    public function placeBid( $aData, $aParams = array() ) {
        $this->oAcl->hasAccess( 'write' . $this->sModuleName );

		// Initialize auto bid module right from start
		$oAutoBid = clRegistry::get( 'clAuctionAutoBid', PATH_MODULE . '/auction/models' );

		// Re-read end time
		$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
		$aItem = current( $oAuctionEngine->readAuctionItem( array(
			'fields' => 'itemEndTime',
			'itemId' => $aData['bidItemId'],
			'status' => '*'
		) ) );

		/**
		 * Param settings
		 */
		$aParams += array(
			'bidTariff' => true,
			'endTime' => $aItem['itemEndTime'],
			'minBid' => $aData['itemMinBid'],
			'type' => $aData['bidType']
		);

		// Clean up data
		$aData = array_intersect_key( $aData, current( $this->oDao->getDataDict() ) );

		$aErr = array(); // Error container

        // User checks
        if( empty($_SESSION['userId']) ) {
			$aErr[] = _( 'No user found' );
        } elseif( empty($aData['bidUserId']) || $aData['bidUserId'] != $_SESSION['userId'] ) {
			$aErr[] = _( 'No user found' );
		}

		// Value type check
		if( strpos($aData['bidValue'], ',') || strpos($aData['bidValue'], '.') ) {
			$aErr[] = _( 'Your bid must be in whole crowns' );
		}

		// End time check
		if( strtotime(date('Y-m-d H:i:s')) > strtotime($aParams['endTime']) ) {
			$aErr[] = _( 'Budgivningen är avslutad' );
		}

        /**
		 * Highest bid & value check
		 */
		$aHighestBid = current( $this->readHistory( array(
			'itemId' => $aData['bidItemId'],
			'entries' => 1
		) ) );
		if( !empty($aHighestBid) ) {
			if( $aData['bidValue'] == $aHighestBid['historyBidValue'] ) {
				$aErr[] = _( 'Value same than current bid' );
			} elseif( $aData['bidValue'] < $aHighestBid['historyBidValue'] ) {
				$aErr[] = _( 'Value lower than current bid' );
			}
			// Highest bid user check (if not auto bid)
			if( $_SESSION['userId'] == $aHighestBid['historyBidUserId'] && $aParams['type'] != 'auto' ) {
				$aErr[] = _( "You can't bid over your self" );
			}
		} else {
			if( $aData['bidValue'] < $aParams['minBid'] ) {
				$aErr[] = _( 'Value lower than min bid' );
			}

			$aCurrentTariff = array();
			foreach( AUCTION_BID_TARIFF as $iKey => $aStep ) {
				if( $aParams['minBid'] >= $aStep['break'] ) $aCurrentTariff = $aStep;
			}

			if( $aData['bidType'] == 'normal' ) {
				if( $aData['bidValue'] > ($aParams['minBid'] + $aCurrentTariff['max']) ) {
					$aErr[] = sprintf( _( 'Bud högre än högsta tillåtna. Högsta är %s' ), ($aParams['minBid'] + $aCurrentTariff['max']) );
				}
			}
		}

		/**
		 * Do not call DB unnecessarily
		 */
		if( empty($aErr) ) {
			// Highest auto bid check
			$aAutoData = $oAutoBid->readItemHighestAutoBid( $aData['bidItemId'] );
			// Removed --------->
			// if( !empty($aAutoData) && $aData['bidValue'] == $aAutoData['autoMaxBid'] ) {
			// 	// Bid value is equal to current highest auto bid
			// 	$aErr[] = _( 'Bid value was same as an existing bid' );
			// }
			// <------- Inserted ------->
			if( !empty($aAutoData) ) {
				$aCurrentTariff = array();
				foreach( AUCTION_BID_TARIFF as $iKey => $aStep ) {
					if( $aAutoData['autoMaxBid'] >= $aStep['break'] ) $aCurrentTariff = $aStep;
				}

				$iMinBidOverAuto = $aAutoData['autoMaxBid'] + $aCurrentTariff['min'];

				if( ($aData['bidValue'] >= $aAutoData['autoMaxBid']) && ($aData['bidValue'] < $iMinBidOverAuto) ) {
					$aErr[] = sprintf( _( 'För att bjuda över existerande bud måste du lägga minst %s' ), $iMinBidOverAuto );
				}
			}
			// <-------
		}

		/**
		 * Bid tariff handling
		 */
		if( empty($aErr) && !empty($aHighestBid) && $aParams['bidTariff'] === true ) {
			$aKeys = array_keys( AUCTION_BID_TARIFF );
			foreach( array_keys($aKeys) as $iKey ) {
				$aCurrentTariff = AUCTION_BID_TARIFF[ $aKeys[$iKey] ];

				if( !empty($aKeys[$iKey+1]) ) {
					$aNextTariff = AUCTION_BID_TARIFF[ $aKeys[$iKey+1] ];

					// Find right breakpoint
					if( $aHighestBid['historyBidValue'] >= $aCurrentTariff['break'] && $aHighestBid['historyBidValue'] < $aNextTariff['break'] ) {
						// Bid span
						$iBidSpan = $aHighestBid['historyBidValue'] > 0 ? $aData['bidValue'] - $aHighestBid['historyBidValue'] : $aData['bidValue'] - $aParams['minBid']; // item min bid????

						if( !empty($aHighestBid) ) {
							$iMinRaise = $aHighestBid['historyBidValue'] + $aCurrentTariff['min'];
							$iMaxRaise = $aHighestBid['historyBidValue'] + $aCurrentTariff['max'];

							// Low/high check
							if( $iBidSpan < $aCurrentTariff['min'] ) $aErr[] = sprintf( _( 'Value lower than min bid. Min bid is %s' ), $iMinRaise );
							if( $aParams['type'] != 'auto' ) {
								if( $iBidSpan > $aCurrentTariff['max'] ) $aErr[] = sprintf( _( 'Bud högre än högsta tillåtna. Högsta är %s' ), $iMaxRaise );
							}
						}

						break;
					}
				}
			}
		}

		if( !empty($aErr) ) {
			/**
			 * Invalid bid, return error report
			 */
			return array(
				'result' => 'error',
				'error' => $aErr
			);
		}

		/**
		 * Register in DB
		 * (error handling below success handling)
		 */
		if( $aParams['type'] == 'auto' ) {
			// Save new auto bid
			$iAutoBidId = $oAutoBid->saveAutoBid( $aData );
		} else {
			// Save normal bid
			$iBidId = $this->oDao->saveBid( $aData );
		}

		/**
		 *
		 * Auto bid part
		 *
		 */
		if(
			( !empty($iBidId) && is_int($iBidId) ) ||
			( !empty($iAutoBidId) && is_int($iAutoBidId) )
		) {
			// Read all auto bids
			if( !empty($aHighestBid) ) {
				// (Reads from highest bid)
				$aAutoBids = $oAutoBid->readByItem( $aData['bidItemId'], $aHighestBid['historyBidValue'] );
			} else {
				// (All auto bids)
				$aAutoBids = $oAutoBid->readByItem( $aData['bidItemId'] );
			}

			if( !empty($aAutoBids) ) {
				// Try to lock item
				$iAffected = $this->oDao->oDb->write( "UPDATE entAuctionItem SET itemAutoBidLocked = 'yes' WHERE itemId = '" . $aData['bidItemId'] . "' AND itemAutoBidLocked = 'no'" );

				/**
				 * This segment will try to lock item for 2 seconds more,
				 * if the first attempt faild.
				 */
				$iRoofBreak = 0;
				while( $iAffected === 0 ) {
					usleep( 250000 ); // Sleep quarter of a secound
					$iAffected = $this->oDao->oDb->write( "UPDATE entAuctionItem SET itemAutoBidLocked = 'yes' WHERE itemId = '" . $aData['bidItemId'] . "' AND itemAutoBidLocked = 'no'" );
					if( $iAffected === 1 ) {
						// Re-read data upon successful locking
						$aAutoBids = $oAutoBid->readByItem( $aData['bidItemId'], $aHighestBid['historyBidValue'] ); // Re-read auto bids
					}
					// Roof break at 2 seconds
					$iRoofBreak++;
					if( $iRoofBreak > 7 ) break;
				}

				if( $iAffected === 1 && !empty($aAutoBids) ) {
					/**
					 *
					 * Item is Auto bid locked!
					 *
					 */

					if( !empty($aHighestBid) ) {
						// Re-read heighest bid
						$aHighestBid = current( $this->readHistory( array(
							'itemId' => $aData['bidItemId'],
							'entries' => 1
						) ) );
					}

					if( empty($aHighestBid) ) {
						/**
						 * First bid
						 * - place bid and return
						 */

						$aHighestBid = array(
							'historyBidValue' => $aParams['minBid']
						);
						$iBidId = $this->oDao->saveBid( array(
							'bidValue' => $aParams['minBid'],
							'bidType' => 'auto',
							'bidAuctionId' => $aData['bidAuctionId'],
							'bidPartId' => $aData['bidPartId'],
							'bidItemId' => $aData['bidItemId'],
							'bidUserId' => $aData['bidUserId']
						) );

						/**
						 * Unlock item again
						 */
						$iAffected = $this->oDao->oDb->write( "UPDATE entAuctionItem SET itemAutoBidLocked = 'no' WHERE itemId = '" . $aData['bidItemId'] . "' AND itemAutoBidLocked = 'yes'" );

						return array(
							'result' => 'success',
							'error' => null
						);
					}

					/**
					 * First and last auto bid
					 */
					$aFirst = current( $aAutoBids );
					foreach( $aAutoBids as $aEntry ) $aLast = $aEntry;

					if( count($aAutoBids) == 1 && $aFirst['autoUserId'] == $aHighestBid['historyBidUserId'] ) {
						/**
						 * Only one auto bid and user is already highest bid..
						 */
					} else  {
						/**
						 * Multiply auto bid to handle..
						 */

						foreach( $aAutoBids as $aEntry ) {
							// Re-read heighest bid
							//$aHighestBid = current( $this->readHistory( array(
							//	'itemId' => $aData['bidItemId'],
							//	'entries' => 1
							//) ) );

							// Reset
							$iHigherBidValue = 0;

							/**
							 * Last auto bid
							 */
							if( $aEntry['autoId'] == $aLast['autoId'] ) {
								/**
								 * Bid tariff
								 */
								$aKeys = array_keys( AUCTION_BID_TARIFF );
								foreach( array_keys($aKeys) as $iKey ) {
									$aCurrentTariff = AUCTION_BID_TARIFF[ $aKeys[$iKey] ];

									if( !empty($aKeys[$iKey+1]) ) {
										$aNextTariff = AUCTION_BID_TARIFF[ $aKeys[$iKey+1] ];

										// Find right breakpoint
										if( $aHighestBid['historyBidValue'] >= $aCurrentTariff['break'] && $aHighestBid['historyBidValue'] < $aNextTariff['break'] ) {
											// Add min heigher
											$iHigherBidValue = $aHighestBid['historyBidValue'] + $aCurrentTariff['min'];
											if( $iHigherBidValue > $aEntry['autoMaxBid'] ) {
												// New value was heigher then auto bid, replace with auto bid
												$iHigherBidValue = $aEntry['autoMaxBid'];
											}
										}
									}
								}

								if( $iHigherBidValue == 0 && $aHighestBid['historyBidValue'] > AUCTION_BID_TARIFF['1000001']['break'] ) {
									$iHigherBidValue = $aHighestBid['historyBidValue'] + AUCTION_BID_TARIFF['1000001']['min'];
									if( $iHigherBidValue > $aEntry['autoMaxBid'] ) {
										// New value was heigher then auto bid, replace with auto bid
										$iHigherBidValue = $aEntry['autoMaxBid'];
									}
								}
							/**
							 * Not last (highest) auto bid, so auto bid value becomes bid value
							 */
							} else {
								$iHigherBidValue = $aEntry['autoMaxBid'];
							}

							if( $iHigherBidValue != 0 ) {
								$iBidId = $this->oDao->saveBid( array(
									'bidValue' => $iHigherBidValue,
									'bidType' => 'auto',
									'bidAuctionId' => $aEntry['autoAuctionId'],
									'bidPartId' => $aEntry['autoPartId'],
									'bidItemId' => $aEntry['autoItemId'],
									'bidUserId' => $aEntry['autoUserId']
								) );
							}

							if( !empty($iBidId) && is_int($iBidId) ) {
								$aHighestBid = array(
									'historyBidId' => $iBidId,
									'historyBidType' => 'auto',
									'historyBidValue' => $iHigherBidValue,
									'historyBidItemId' => $aEntry['autoItemId'],
									'historyBidUserId' => $aEntry['autoUserId']
								);

								continue;

							} else break;
						}
					}
				}

				/**
				 * Unlock item again
				 */
				$iAffected = $this->oDao->oDb->write( "UPDATE entAuctionItem SET itemAutoBidLocked = 'no' WHERE itemId = '" . $aData['bidItemId'] . "' AND itemAutoBidLocked = 'yes'" );
			}
		} else {
			/**
			 * Error while register to DB
			 */
			if( $aParams['type'] == 'auto' ) {
				$aErr = clErrorHandler::getValidationError( 'createAuctionAutoBid' );
			} else {
				$aErr = clErrorHandler::getValidationError( 'createAuctionBid' );
			}
		}

		if( !empty($aErr) ) {
			/**
			 * Invalid bid, return error report
			 */
			return array(
				'result' => 'error',
				'error' => $aErr
			);
		}

		return array(
			'result' => 'success',
			'error' => null
		);
    }

    /**
     * Read item current top bid
     */
    public function highestItemBid( $iItemId ) {
        $this->oAcl->hasAccess( 'read' . $this->sModuleName );
        return current( $this->oDao->read( array(
			'itemId' => $iItemId,
			'sorting' => array( 'bidValue DESC' ),
			'entries' => 1
		) ) );
    }

	/**
     * Read directly by params
     */
	public function readCustom( $aParams = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
        return $this->oDao->read( $aParams );
	}

	/**
     * Read directly by params
     */
	public function readByUser( $iUserId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
        return $this->readCustom( array(
			'userId' => $iUserId
		) );
	}

	/**
     * Normal read function
     */
	public function read( $aFields = array(), $mBidId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		$aParams = array(
			'fields' => $aFields
		);

		if( $mBidId !== null ) {
			$aParams['bidId'] = $mBidId;
		}

		// Read by read-funciton in dao
		return $this->oDao->read( $aParams );
	}

	/**
	 * Read history data
	 */
	public function readHistory( $aParams = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
        return $this->oDao->readHistory( $aParams );
	}

	/**
	 * Update history by item
	 */
	public function updateHistory( $aItemId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
        return $this->oDao->updateHistory( $aItemId );
	}

}
