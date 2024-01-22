<?php

/**
 * Filename: clAuctionDaoMysql.php
 * Created: 18/03/2014 by Mikael
 * Reference: database-overview.mwb
 * Description: See clAuction.php
 */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clAuctionBackendDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entAuction' => array(
				'auctionId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Auction ID' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionType' => array(
					'type' => 'array',
					'title' => _( 'Auction type' ),
					'values' => array(
						'net' => _( 'Net auktion' ),
						'live' => _( 'Live auktion' )
					),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionInternalName' => array(
					'type' => 'string',
					'title' => _( 'Internal name' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionInternalProject' => array(
					'type' => 'string',
					'title' => _( 'PR' )
				),
				'auctionTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionShortTitle' => array(
					'type' => 'string',
					'max' => 120,
					'title' => _( 'Short title' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionSummary' => array(
					'type' => 'string',
					'title' => _( 'Summary' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionContactDescription' => array(
					'type' => 'string',
					'title' => _( 'Contact description' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionLocation' => array(
					'type' => 'string',
					'title' => _( 'Location' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionLastPayDate' => array(
					'type' => 'date',
					'title' => _( 'Last pay date' )
				),
				'auctionArchiveStatus' => array(
					'type' => 'array',
					'title' => _( 'View in archive afterwards?' ),
					'values' => array(
						'active' => _( 'Visible' ),
						'inactive' => _( 'Hidden' )
					)
				),
				/* New field to add to database. (Renfors 2014-04-15)
				 *
				 *'auctionPartItemNumbering' => array(
					'type' => 'array',
					'title' => _( 'Numbering method for parts' ),
					'values' => array(
						'continous' => _( 'Continous' ),
						'interval' => _( 'Interval' )
					)
				),*/
				'auctionStatus' => array(
					'type' => 'array',
					'title' => _( 'Status' ),
					'values' => array(
						'inactive' => _( 'Inactive' ),
						'active' => _( 'Active' )
					),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionViewedCount' => array(
					'type' => 'integer',
					'title' => _( 'Viewed count' )
				),
				'auctionCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'auctionOldAuctionId' => array(
					'type' => 'integer',
					'title' => _( 'Old auction ID' )
				)
				// Foreign key's
				/* none */
			),
			'entAuctionPart' => array(
				'partId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Part ID' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'partTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'partAuctionTitle' => array(
					'type' => 'string',
					'title' => _( 'Alternativ auktionsrubrik' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'partDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'partYoutubeLink' => array(
					'type' => 'string',
					'title' => _( 'Youtube-länk' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'partLocation' => array(
					'type' => 'string',
					'title' => _( 'Location' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'partPreBidding' => array(
					'type' => 'array',
					'title' => _( 'Pre bidding' ),
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					)
				),
				'partAuctionStart' => array(
					'type' => 'datetime',
					'title' => _( 'Auction starts' )
				),
				'partStatus' => array(
					'type' => 'array',
					'title' => _( 'Status' ),
					'values' => array(
						'inactive' => _( 'Inactive' ),
						'upcomming' => _( 'Upcomming' ),
						'running' => _( 'Running' ),
						'halted' => _( 'Halted' ),
						'ending' => _( 'Ending' ),
						'ended' => _( 'Ended' )
					),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				),
				'partHaltedTime' => array(
					'type' => 'datetime',
					'title' => _( 'Halted' )
				),
				'partCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'partPublished' => array(
					'type' => 'datetime',
					'title' => _( 'Published' )
				),
				'partReviewValue' => array(
					'type' => 'int',
					'min' => 1,
					'max' => 5,
					'title' => _( 'Review value' )
				),
				'partReviewComment' => array(
					'type' => 'string',
					'title' => _( 'Kommentar' )
				),
				// Foreign key's
				'partAuctionId' => array(
					'type' => 'string',
					'title' => _( 'Auction ID' ),
					'api' => array(
						'accessGroup' => array(
							'guest'
						)
					)
				)
			),
			'entAuctionAddress' => array(
				'addressId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Address ID' )
				),
				'addressTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'addressAddress' => array(
					'type' => 'string',
					'title' => _( 'Address' )
				),
				'addressAddressDescription' => array(
					'type' => 'string',
					'title' => _( 'Vägbeskrivning' )
				),
				'addressShowingSpecial' => array(
					'type' => 'string',
					'title' => _( 'According to agreement on no' )
				),
				'addressShowingStart' => array(
					'type' => 'datetime',
					'title' => _( 'Showing start' )
				),
				'addressShowingEnd' => array(
					'type' => 'datetime',
					'title' => _( 'Showing end' )
				),
				'addressShowingInfo' => array(
					'type' => 'string',
					'title' => _( 'Showing info' )
				),
				'addressCollectSpecial' => array(
					'type' => 'string',
					'title' => _( 'According to agreement on no' )
				),
				'addressCollectStart' => array(
					'type' => 'datetime',
					'title' => _( 'Collect start' )
				),
				'addressCollectEnd' => array(
					'type' => 'datetime',
					'title' => _( 'Collect end' )
				),
				'addressCollectInfo' => array(
					'type' => 'string',
					'title' => _( 'Collect info' )
				),
				'addressFreightHelp' => array(
					'type' => 'array',
					'title' => _( 'Erbjuds frakt?' ),
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' ),
						'custom' => _( 'Anpassad' )
					)
				),
				'addressFreightInfo' => array(
					'type' => 'string',
					'title' => _( 'Anpassad text för frakt' )
				),
				'addressFreightRequestLastDate' => array(
					'type' => 'date',
					'title' => _( 'Fraktbeställning - sista dag' )
				),
				'addressForkliftHelp' => array(
					'type' => 'array',
					'title' => _( 'Erbjuds lasthjälp?' ),
					'values' => array(
						'yes' => _( 'Yes' ),
						'no' => _( 'No' ),
						'custom' => _( 'Anpassad' )
					)
				),
				'addressLoadingInfo' => array(
					'type' => 'string',
					'title' => _( 'Erbjuds lasthjälp?' )
				),
				// Foreign key's
				'addressPartId' => array(
					'type' => 'string',
					'title' => _( 'Part ID' )
				)
			),
			'entAuctionToUser' => array(
				'auctionId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'Auction ID' )
				),
				'userId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'User ID' )
				),
				'relationCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Relation created' )
				)
			)
		);
		$this->sPrimaryEntity = 'entAuction';
		$this->sPrimaryField = 'auctionId';
		$this->aFieldsDefault = array( '*' );

		$this->init();

		$this->aDataFilters['output'] = array(
			'auctionLastPayDate' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			),
			'partAuctionStart' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			),
			'partHaltedTime' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			)
		);
	}

	public function read( $aParams ) {
		$aDaoParams = array();

		$aParams += array(
			'fields' => array(),
			'auctionId' => null,
			'partId' => null,
			'auctionStatus' => 'active',
			'partStatus' => 'running'
		);

		$aCriterias = array();
		$aEntitiesExtend = array();

		// Fields
		$aDaoParams['fields'] = $aParams['fields'];

		/**
		 * Add auciton part data
		 */
		$aEntitiesExtend[] = '
			LEFT JOIN entAuctionPart ON entAuction.auctionId = entAuctionPart.partAuctionId';

		/**
		 * Route handling
		 */
		if( !empty($aParams['fields']) && is_array($aParams['fields']) && in_array('routePath', $aParams['fields']) ) {
			/**
			 * Add route data
			 */
			$sRouteType = 'auction';
			if( $aParams['fields'] == '*' || is_array($aParams['fields']) && in_array('partId', $aParams['fields']) ) {
				$sRouteType = 'auctionPart';
			}

			$aEntitiesExtend[] = '
				LEFT JOIN entAuctionToRoute ON entAuctionPart.partId = entAuctionToRoute.parentId
				AND entAuctionToRoute.parentType = "' . $sRouteType . '"
				LEFT JOIN entRoute ON entAuctionToRoute.routeId = entRoute.routeId';
		}

		/**
		 * Read by auction ID
		 */
		if( $aParams['auctionId'] !== null ) {
			if( is_array($aParams['auctionId']) ) {
				$aCriterias[] = 'auctionId IN(' . implode( ', ', array_map('intval', $aParams['auctionId']) ) . ')';
			} else {
				$aCriterias[] = 'auctionId = ' . (int) $aParams['auctionId'];
			}
		}

		/**
		 * Read by part ID
		 */
		if( $aParams['partId'] !== null ) {
			if( is_array($aParams['partId']) ) {
				$aCriterias[] = 'partId IN(' . implode( ', ', array_map('intval', $aParams['partId']) ) . ')';
			} else {
				$aCriterias[] = 'partId = ' . (int) $aParams['partId'];
			}
		}

		/**
		 * Handle auction status
		 */
		if( $aParams['auctionStatus'] !== null && $aParams['auctionStatus'] != '*' ) {
			if( is_array($aParams['auctionStatus']) ) {
				$aCriterias[] = "auctionStatus IN('" . implode( "', '", $aParams['auctionStatus'] ) . "')";
			} else {
				$aCriterias[] = 'auctionStatus = ' . $this->oDb->escapeStr( $aParams['auctionStatus'] );
			}
		} elseif( $aParams['auctionStatus'] === null ) {
			// Fallback security, never read other than running if not specified
			$aCriterias[] = 'auctionStatus = "active"';
		} else {
			// Read all
		}

		/**
		 * Handle auction part status
		 */
		if( $aParams['partStatus'] !== null && $aParams['partStatus'] != '*' ) {
			if( is_array($aParams['partStatus']) ) {
				$aCriterias[] = "partStatus IN('" . implode( "', '", $aParams['partStatus'] ) . "')";
			} else {
				$aCriterias[] = 'partStatus = ' . $this->oDb->escapeStr( $aParams['partStatus'] );
			}
		} elseif( $aParams['partStatus'] === null ) {
			// Fallback security, never read other than running if not specified
			$aCriterias[] = 'partStatus = "running"';
		} else {
			// Read all
		}

		/**
		 * Assemble and return data
		 */
		if( !empty($aEntitiesExtend) ) $aDaoParams['entitiesExtended'] = 'entAuction ' . implode( ' ', $aEntitiesExtend );
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/*
	 * Auction part functions
	 */

 	public function readAuctionPart( $iPartId, $aFields = array() ) {
 		$aDaoParams = array(
			'fields' => $aFields,
 			'entities' => 'entAuctionPart',
 			'criterias' => 'entAuctionPart.partId = ' . $this->oDb->escapeStr($iPartId)
 		);
 		return $this->readData( $aDaoParams );
 	}

	public function createAuctionPart( $aData ) {
		$aDaoParams = array(
			'entities' => array(
				'entAuctionPart'
			)
		);
		if( $this->createData( $aData, $aDaoParams ) ) {
			return $this->oDb->lastId();
		}
		return false;
	}

	public function updateAuctionPart( $iPartId, $aData ) {
		$aDaoParams = array(
			'entities' => 'entAuctionPart',
			'criterias' => 'entAuctionPart.partId = ' . $this->oDb->escapeStr($iPartId)
		);

		return $this->updateData( $aData, $aDaoParams );
	}

	public function readAuctionPartByAuction( $iAuctionId ) {
		$aDaoParams = array(
			'entities' => 'entAuctionPart',
			'criterias' => 'entAuctionPart.partAuctionId = ' . $this->oDb->escapeStr($iAuctionId)
		);
		return $this->readData( $aDaoParams );
	}

	public function deleteAuctionPart( $iPartId ) {
		// Delete addresses for this part
		$this->deleteAuctionAddresses( null, $iPartId );

		$aDaoParams = array(
			'entities' => 'entAuctionPart',
			'criterias' => 'entAuctionPart.partId = ' . $this->oDb->escapeStr($iPartId)
		);

		return $this->deleteData( $aDaoParams );
	}

	public function deleteAuctionParts( $iAuctionId ) {
		$aDaoParams = array(
			'entities' => 'entAuctionPart',
			'criterias' => 'entAuctionPart.partAuctionId = ' . $this->oDb->escapeStr($iAuctionId)
		);

		return $this->deleteData( $aDaoParams );
	}

	/*
	 * Address functions
	 */

	public function readAuctionAddress( $aDaoParams, $primaryId = null ) {
		$aDaoParams += array(
			'entities' => array(
				'entAuctionAddress'
			)
		);

		if( !empty($primaryId) ) {
			if( is_array($primaryId) ) {
				$aDaoParams['criterias'] = 'entAuctionAddress.addressId' . ' IN (' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $primaryId) ) . ')';
			} else {
				$aDaoParams['criterias'] = 'entAuctionAddress.addressId' . ' = ' . $this->oDb->escapeStr( $primaryId );
			}
		}

		return $this->readData( $aDaoParams );
	}

	public function createAuctionAddress( $aData ) {
		$aDaoParams = array(
			'entities' => array(
				'entAuctionAddress'
			)
		);
		if( $this->createData( $aData, $aDaoParams ) ) {
			return $this->oDb->lastId();
		}
		return false;
	}

	public function updateAuctionAddress( $iAddressId, $aData ) {
		$aDaoParams = array(
			'entities' => 'entAuctionAddress',
			'criterias' => 'entAuctionAddress.addressId = ' . $this->oDb->escapeStr($iAddressId)
		);
		return $this->updateData( $aData, $aDaoParams );
	}

	public function readAuctionAddressByAuctionPart( $iPartId ) {
		if( is_array($iPartId) ) {
			$aDaoParams = array(
				'entities' => 'entAuctionAddress',
				'criterias' => 'entAuctionAddress.addressPartId IN(' . implode( ', ', array_map('intval', $iPartId) ) . ')'
			);
		} else {
			$aDaoParams = array(
				'entities' => 'entAuctionAddress',
				'criterias' => 'entAuctionAddress.addressPartId = ' . $this->oDb->escapeStr($iPartId)
			);
		}

		return $this->readData( $aDaoParams );
	}

	public function deleteAuctionAddress( $iAddressId ) {

		$aDaoParams = array(
			'entities' => 'entAuctionAddress',
			'criterias' => 'entAuctionAddress.addressId = ' . $this->oDb->escapeStr($iAddressId)
		);

		if( $mResult = $this->deleteData($aDaoParams) ) {

			// Remove connections to items
			$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
			$oAuctionItem->updateByAddress( $iAddressId, array(
				'itemAddressId' => null
			) );

		}

		return $mResult;
	}

	public function deleteAuctionAddresses( $iAuctionId = null, $iPartId = null ) {
		if( !empty($iAuctionId) ) {
			$aParts = arrayToSingle( $this->readAuctionPartByAuction($iAuctionId), null, 'partId' );
		}
		else {
			$aParts = (array) $iPartId;
		}

		foreach( $aParts as $iPartId ) {
			$aDaoParams = array(
				'entities' => 'entAuctionAddress',
				'criterias' => 'entAuctionAddress.addressPartId = ' . $this->oDb->escapeStr($iPartId)
			);
			if( $this->deleteData($aDaoParams) ) {

				// Remove connections to items
				$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
				$oAuctionItem->updateByPart( $iPartId, array(
					'itemAddressId' => null
				) );

			}
		}
	}

	/*
	 * User functions (is used for connecting an auction to a user with "partner" authority. They can edit the connected auctions)
	 */

	public function readAuctionToUser( $iAuctionId = null, $iUserId = null ) {
		if( !empty($iAuctionId) ) {
			if( is_array($iAuctionId) ) {
				$aDaoParams = array(
					'entities' => 'entAuctionToUser',
					'criterias' => 'entAuctionToUser.auctionId IN(' . implode( ', ', array_map('intval', $iAuctionId) ) . ')'
				);
			} else {
				$aDaoParams = array(
					'entities' => 'entAuctionToUser',
					'criterias' => 'entAuctionToUser.auctionId = ' . $this->oDb->escapeStr($iAuctionId)
				);
			}
		}

		if( !empty($iUserId) ) {
			if( is_array($iUserId) ) {
				$aDaoParams = array(
					'entities' => 'entAuctionToUser',
					'criterias' => 'entAuctionToUser.userId IN(' . implode( ', ', array_map('intval', $iUserId) ) . ')'
				);
			} else {
				$aDaoParams = array(
					'entities' => 'entAuctionToUser',
					'criterias' => 'entAuctionToUser.userId = ' . $this->oDb->escapeStr($iUserId)
				);
			}
		}
		return $this->readData( $aDaoParams );
	}

	public function createAuctionToUser( $iAuctionId, $iUserId ) {
		$aDaoParams = array(
			'entities' => array(
				'entAuctionToUser'
			)
		);
		$aData = array(
			'auctionId' => $iAuctionId,
			'userId' => $iUserId,
			'relationCreated' => date( 'Y-m-d H:i:s' )
		);
		if( $this->createData( $aData, $aDaoParams ) ) {
			return $this->oDb->lastId();
		}
		return false;
	}

	public function deleteAuctionToUser( $iAuctionId, $iUserId ) {
		$aDaoParams = array(
			'entities' => 'entAuctionToUser',
			'criterias' => 'entAuctionToUser.auctionId = ' . $this->oDb->escapeStr($iAuctionId) . ' AND entAuctionToUser.userId = ' . $this->oDb->escapeStr($iUserId)
		);
		return $this->deleteData( $aDaoParams );
	}


	public function increaseViewedCount( $iAuctionId ) {
		return $this->oDb->query( "
			UPDATE entAuction
			SET auctionViewedCount = auctionViewedCount + 1
			WHERE auctionId = " . $this->oDb->escapeStr($iAuctionId) . "
		" );
	}

	/**
	 * $param array $aParams
	 */
	public function readData( $aParams = array() ) {

		$aParams += array(
			'withHelpers' => true
		);

		if( !empty($this->aHelpers) && $aParams['withHelpers'] !== false ) {
			$aParams = $this->executeHelpers( 'readData', $aParams );
		}

		$aParams += array(
			'count' => $this->bEntriesTotal,
			'countField' => null,
			'criterias' => null,
			'entities' => $this->sPrimaryEntity,
			'entitiesExtended' => null,
			'entries' => null,
			'fields' => array(),
			'groupBy' => null,
			'sorting' => null
		);

		/**
		 * Addition for Tovek
		 */
		if( !empty($this->sEntitiesExtended) ) {
			if( empty($aParams['entitiesExtended']) ) {
				$aParams['entitiesExtended'] = $this->sPrimaryEntity . ' ' . $this->sEntitiesExtended;
			} else {
				$aParams['entitiesExtended'] .= ' ' . $this->sEntitiesExtended;
			}
		}

		$this->aFields = $aParams['fields'];
		$this->setEntities( $aParams['entities'] );
		$sEntities = $aParams['entitiesExtended'] !== null ? $aParams['entitiesExtended'] : implode( ', ', $this->aEntities );

		if( $aParams['count'] ) $this->iLastEntriesTotal = $this->readEntriesTotal( $aParams );

		$this->preReadData();
		$this->result = $this->oDb->querySecondaryDb(
			'SELECT ' . $this->formatFields() . ' FROM ' . $sEntities .
			$this->formatCriterias( $aParams['criterias'] ) .
			( $aParams['groupBy'] !== null ? ' GROUP BY ' . $aParams['groupBy'] : '' ) .
			$this->formatSorting( $aParams['sorting'] ) .
			( $aParams['entries'] !== null ? ' LIMIT ' . $aParams['entries'] : $this->formatEntries() )
		);
		$this->postReadData();

		if( !empty($this->aDataFilters['output']) ) $this->filterOutputData( $this->aDataFilters['output'] );

		return $this->result;
	}

}
