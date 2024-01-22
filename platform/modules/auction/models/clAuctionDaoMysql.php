<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
//require_once PATH_HELPER . '/clRouterHelperDaoSql.php';

class clAuctionDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entAuction' => array(
				'auctionId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'auctionType' => array(
					'type' => 'array',
					'title' => _( 'Type' ),
					'values' => array(
						'net' => _( 'Net auktion' ),
						'live' => _( 'Live auktion' )
					)
				),
				'auctionInternalName' => array(
					'type' => 'string',
					'title' => _( 'Internal name' )
				),
				'auctionInternalProject' => array(
					'type' => 'string',
					'title' => _( 'PR' )
				),
				'auctionTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'auctionShortTitle' => array(
					'type' => 'string',
					'max' => 120,
					'title' => _( 'Short title' )
				),
				'auctionSummary' => array(
					'type' => 'string',
					'title' => _( 'Summary' )
				),
				'auctionDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'auctionContactDescription' => array(
					'type' => 'string',
					'title' => _( 'Contact description' )
				),
				'auctionLocation' => array(
					'type' => 'string',
					'title' => _( 'Location' )
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
				'auctionStatus' => array(
					'type' => 'array',
					'title' => _( 'Status' ),
					'values' => array(
						'inactive' => _( 'Inactive' ),
						'active' => _( 'Active' )
					)
				),
				'auctionViewedCount' => array(
					'type' => 'integer',
					'title' => _( 'Viewed count' )
				),
				'auctionCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
                'auctionUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			),
			'entAuctionPart' => array(
				'partId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Part ID' )
				),
				'partTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'partAuctionTitle' => array(
					'type' => 'string',
					'title' => _( 'Alternativ auktionsrubrik' )
				),
				'partDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'partYoutubeLink' => array(
					'type' => 'string',
					'title' => _( 'Youtube-länk' )
				),
				'partLocation' => array(
					'type' => 'string',
					'title' => _( 'Location' ),
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
					'title' => _( 'Auction ID' )
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
					'type' => 'string',
					'title' => _( 'Erbjuds frakt?' )
				),
				'addressFreightInfo' => array(
					'type' => 'string',
					'title' => _( 'Anpassad text för frakt' )
				),
				'addressFreightRequestLastDate' => array(
					'type' => 'date',
					'title' => _( 'Fraktbeställning - sista dag' )
				),
				'addressFreightSenderId' => array(
					'type' => 'int',
					'title' => _( 'Avsändaradress' )
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
				'addressHidden' => array(
					'type' => 'array',
					'title' => _( 'Dold adress' ),
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					)
				),
				'addressPreRegistration' => array(
					'type' => 'string',
					'title' => _( 'Föranmälan krävs' )
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

		$this->sPrimaryField = 'auctionId';
		$this->sPrimaryEntity = 'entAuction';
		$this->aFieldsDefault = '*';

		$this->init();

		//$this->aHelpers = array(
		//	'oRouterHelper' => new clRouterHelperDaoSql( $this, array(
		//		'parentEntity' => $this->sPrimaryEntity,
		//		'parentPrimaryField' => $this->sPrimaryField,
		//		'parentType' => 'Auction'
		//	) )
		//);

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

		$this->aDataFilters['input'] = array(
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

	/**
	 * Read data
	 */
	public function read( $aParams ) {
		$aParams += array(
			'fields' => array(),
			'auctionId' => null,
			'partId' => null,
			'auctionStatus' => 'active',
			'partStatus' => 'running'
		);

		$aCriterias = array();
		$aEntitiesExtend = array();

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'groupBy' => 'entAuctionPart.partId'
		);

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
				LEFT JOIN entRouteToObject ON entAuctionPart.partId = entRouteToObject.objectId
				AND entRouteToObject.objectType = "' . ucfirst($sRouteType) . '"
				LEFT JOIN entRoute ON entRouteToObject.routeId = entRoute.routeId';

			$aCriterias[] = 'routePath != ""';
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

		// Test
		if( $aParams['auctionStatus'] == 'active' && $aParams['partStatus'] == 'running' ) {
			$aCriterias[] = 'partAuctionStart > "' . date( 'Y-m-d' ) . ' 00:00:01"';
		}

		/**
		 * Assemble and return data
		 */
		if( !empty($aEntitiesExtend) ) $aDaoParams['entitiesExtended'] = 'entAuction ' . implode( ' ', $aEntitiesExtend );
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/**
	 * Delete data
	 */
	public function delete( $aParams ) {
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

		return $this->deleteData( $aDaoParams );
	}


	public function createPart( $aData ) {
		$aDaoParams = array(
			'entities' => 'entAuctionPart',
			'fields' => array_keys( $aData ),
			'groupKey' => 'createAuctionPart'
		);
		return $this->createData( $aData, $aDaoParams );
	}

	public function createAddress( $aData ) {
		$aDaoParams = array(
			'entities' => 'entAuctionAddress',
			'fields' => array_keys( $aData )
		);
		return $this->createData( $aData, $aDaoParams );
	}

	public function createUserRelation( $aData ) {
		$aDaoParams = array(
			'entities' => 'entAuctionToUser',
			'fields' => array_keys( $aData )
		);
		return $this->createData( $aData, $aDaoParams );
	}

	public function increaseViewedCount( $iAuctionId ) {
		return $this->oDb->query( "
			UPDATE entAuction
			SET auctionViewedCount = auctionViewedCount + 1
			WHERE auctionId = " . $this->oDb->escapeStr($iAuctionId) . "
		" );
	}

	/**
	 *
	 * Address related below
	 *
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

}
