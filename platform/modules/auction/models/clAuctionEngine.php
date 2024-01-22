<?php

require_once PATH_MODULE . '/auction/config/cfAuction.php';
require_once PATH_MODULE . '/auction/config/cfAuctionImage.php';

class clAuctionEngine extends clAuctionEngineBase {
    
    public $aModule = array();
    
    public function __construct() {
		$this->sModuleName = 'AuctionEngine';        
    }
    
    /**
	 * Access method within a module
	 */
	public function __call( $sFunction, $aArguments ) {
		if( strpos($sFunction, '_in_') === false ) {
            throw new Exception( 'Function "' . $sFunction . '" does not exist.' );
        }
        
        $aFunction = explode( '_in_', $sFunction );
        $sMethod = $aFunction[0];
        $sClass = $aFunction[1];
        
		if( !array_key_exists( $sClass, $this->aModule ) ) {
			$this->aModule[ $sClass ] = clRegistry::get( 'cl' . ucfirst($sClass), PATH_MODULE . '/auction/models' );
		}
		
        if( method_exists($this->aModule[ $sClass ], $sMethod) ) {		
            return call_user_func_array( array( $this->aModule[ $sClass ], $sMethod ), $aArguments );
        }
	}
    
    /**
	 * My work here is done...
	 */
	public function __destruct() {
		// Do probably nothing here...
	}
    
    /**
	 * To enable use of module from within engine
	 */
    public function useModule( $sModule ) {
        if( empty($this->aModule[ $sModule ]) ) $this->aModule[ $sModule ] = clRegistry::get( 'cl' . ucfirst($sModule), PATH_MODULE . '/auction/models' );
    }
    
    /**
	 * Get a dao
	 */
	public function getDao( $sModule ) {
        $this->useModule( $sModule );
        return $this->aModule[ $sModule ]->oDao;
	}
    
	/**
	 * Place bid
	 *
	 * @param array $aData
     * @return array
	 */
	public function placeBid( $aData ) {
        $this->useModule( 'auctionBid' );
		return $this->aModule['auctionBid']->placeBid( $aData );
	}
	
	/**
     * Read item bid history
     *
     * @param integer $iItemId
     * @return array
     */
	public function readItemBidHistory( $iItemId, $iEntries = null ) {
		$this->useModule( 'auctionBid' );
		return $this->aModule['auctionBid']->readHistory( array(
			'itemId' => $iItemId,
			'entries' => $iEntries
		) );
	}
	
	/**
	 * Update history by item
	 */
	public function updateItemBidHistory( $aItemId ) {
        $this->useModule( 'auctionBid' );
		return $this->aModule['auctionBid']->updateHistory( $aItemId );
	}
	
	/**
	 * Auction: Function for reading auction
	 */
	public function readAuction( $aParams = array() ) {
		if( !array_key_exists( 'auction', $this->aModule ) ) {
			$this->aModule['auction'] = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );
		}
		
		$aParams += array(
			'fields' => array(),
			'auctionId' => null,
			'partId' => null,			
			'auctionStatus' => null,
			'partStatus' => null
		);
		
		$aStatus = array(
			'auctionStatus' => !empty($aParams['auctionStatus']) ? $aParams['auctionStatus'] : 'active',
			'partStatus' => !empty($aParams['partStatus']) ? $aParams['partStatus'] : 'running'
		);
		
		return $this->aModule['auction']->read( $aParams['fields'], $aParams['auctionId'], $aParams['partId'], $aStatus );
	}
	
	/**
	 * Read auction item
	 * AuctionItem: Global read function for auction items
	 */
	public function readAuctionItem( $aParams = array() ) {
		if( !array_key_exists( 'AuctionItem', $this->aModule ) ) {
			$this->aModule['AuctionItem'] = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
		}
		
		// Fix broken requests to avoid errors
		if( !is_array($aParams) ) $aParams = ( ($aParams == '*') ? array('fields' => '*') : '*' );
		
		$aParams += array(
			'fields' => array(),
			'itemId' => null,
			'sortNo' => null,
			'auctionId' => null,
			'partId' => null,
			'winningUserId' => null,
			'status' => 'active',
			'hot' => null,
			'extendCriteria' => null,
			'sorting' => null,
			'entries' => null,
			'search' => null
		);
		
		$aDataParams = array(
			'fields' => $aParams['fields'],
			'sorting' => $aParams['sorting'],
			'entries' => $aParams['entries']
		);
		
		$aCriterias = array();
		$aEntitiesExtend = array();
		
		/**
		 * Search
		 */
		if( $aParams['search'] !== null ) {
			$this->aModule['AuctionItem']->oDao->setCriterias( array(
				'auctionSearch' => array(
					'type' => 'like',
					'value' => $_SESSION['auctionSearch']['searchQuery'],
					'fields' => array(
						'itemTitle',
						'itemSummary',
						'itemDescription',
						'itemEndTime',
						'itemLocation'
					)
				)
			) );
		}
		
		/**
		 * By item IDs
		 */
		if( $aParams['itemId'] !== null ) {
			if( is_array($aParams['itemId']) ) {
				$aCriterias[] = 'itemId IN(' . implode( ', ', array_map('intval', $aParams['itemId']) ) . ')';
			} else {
				$aCriterias[] = 'itemId = ' . (int) $aParams['itemId'];
			}
		}
		
		/**
		 * By sort NOs
		 */
		if( $aParams['sortNo'] !== null ) {
			if( is_array($aParams['sortNo']) ) {
				$aCriterias[] = 'itemSortNo IN(' . implode( ', ', array_map('intval', $aParams['sortNo']) ) . ')';
			} else {
				$aCriterias[] = 'itemSortNo = ' . (int) $aParams['sortNo'];
			}
		}
		
		/**
		 * By winning user id
		 */
		if( $aParams['winningUserId'] !== null ) {
			if( is_array($aParams['winningUserId']) ) {
				$aCriterias[] = 'itemWinningUserId IN(' . implode( ', ', array_map('intval', $aParams['winningUserId']) ) . ')';
			} else {
				$aCriterias[] = 'itemWinningUserId = ' . (int) $aParams['winningUserId'];
			}
		}
		
		/**
		 * By auction IDs
		 */
		if( $aParams['auctionId'] !== null ) {
			if( is_array($aParams['auctionId']) ) {
				$aCriterias[] = 'itemAuctionId IN(' . implode( ', ', array_map('intval', $aParams['auctionId']) ) . ')';
			} else {
				$aCriterias[] = 'itemAuctionId = ' . (int) $aParams['auctionId'];
			}
		}
		
		/**
		 * By auction IDs
		 */
		if( $aParams['partId'] !== null ) {
			if( is_array($aParams['partId']) ) {
				$aCriterias[] = 'itemPartId IN(' . implode( ', ', array_map('intval', $aParams['partId']) ) . ')';
			} else {
				$aCriterias[] = 'itemPartId = ' . (int) $aParams['partId'];
			}
		}
		
		/**
		 * Item status
		 */
		if( $aParams['status'] !== null && $aParams['status'] != '*' ) {
			if( is_array($aParams['status']) ) {
				$aCriterias[] = 'itemStatus IN("' . implode( '", "', $aParams['status'] ) . '")';
			} else {
				$aCriterias[] = 'itemStatus = ' . $this->aModule['AuctionItem']->oDao->oDb->escapeStr( $aParams['status'] );
			}
		}
		
		/**
		 * Item hot
		 */
		if( $aParams['hot'] !== null ) {
			if( is_array($aParams['hot']) ) {
				$aCriterias[] = 'itemHot IN(' . implode( ', ', array_map(array($this->aModule['AuctionItem']->oDao->oDb, 'escapeStr'),$aParams['hot']) ) . ')';
			} else {
				$aCriterias[] = 'itemHot = ' . $this->aModule['AuctionItem']->oDao->oDb->escapeStr( $aParams['hot'] );
			}
		}
		
		/**
		 * Item time
		 */
		if( $aParams['status'] !== null && $aParams['status'] == 'active' || is_array($aParams['status']) && !in_array('ended', $aParams['status']) ) {
			$aCriterias[] = 'itemEndTime > NOW()';
		}
		
		if( $aParams['fields'] == '*' || (is_array($aParams['fields']) && in_array('auctionId', $aParams['fields'])) ) {
			$aEntitiesExtend[] = 'LEFT JOIN entAuction ON entAuctionItem.itemAuctionId = entAuction.auctionId';
		}
		if( $aParams['fields'] == '*' || (is_array($aParams['fields']) && in_array('partId', $aParams['fields'])) ) {
			$aEntitiesExtend[] = 'LEFT JOIN entAuctionPart ON entAuctionItem.itemPartId = entAuctionPart.partId';
		}
		
		/**
		 * Route data (I would like to try this)
		 */ 
		//if( !empty($aFields) && is_array($aFields) && in_array('routePath', $aFields) ) {
		//	$aEntitiesExtend[] = '
		//		LEFT JOIN entAuctionToRoute ON entAuctionItem.itemId = entAuctionToRoute.parentId
		//		AND entAuctionToRoute.parentType = "auctionItem"
		//		LEFT JOIN entRoute ON entAuctionToRoute.routeId = entRoute.routeId';
		//}
		
		// Entities extended
		if( !empty($aEntitiesExtend) ) $aDataParams['entitiesExtended'] = 'entAuctionItem ' . implode( ' ', $aEntitiesExtend );
		
		// Extend criteria
		if( $aParams['extendCriteria'] !== null ) {
			$aCriterias[] = $aParams['extendCriteria'];
		}
		
		// Criterias
		if( !empty($aCriterias) ) $aDataParams['criterias'] = implode( ' AND ', $aCriterias );
		
		// Group by
		// $aDataParams['groupBy'] = 'entAuctionItem.itemId';
		
		// Read	
		// return $this->aModule['AuctionItem']->oDao->readData( $aDataParams );
		$aResultData = $this->aModule['AuctionItem']->oDao->readData( $aDataParams );

		// Do sorting programatically instead of by sql 
		// By itemSortNo
		if( empty($aParams['sorting']) && (empty($aParams['fields']) || (is_array($aParams['fields']) && in_array('itemSortNo', $aParams['fields']))) ) {
			usort($aResultData, fn($a, $b) => $a['itemSortNo'] <=> $b['itemSortNo']);
		}

		return $aResultData;
	}
	
	/**
	 * Read auction item bid
	 * ItemBid: Global read function for auction items
	 */
	public function readAuctionBid( $aParams = array() ) {
		if( !array_key_exists( 'AuctionBid', $this->aModule ) ) {
			$this->aModule['AuctionBid'] = clRegistry::get( 'clAuctionBid', PATH_MODULE . '/auction/models' );
		}		
		// Read
		return $this->aModule['AuctionBid']->oDao->read( $aParams );
	}
	
	/**
	 * Read auction item bid
	 * ItemBid: Global read function for auction items
	 */
	public function readAuctionBidHistory( $aParams = array() ) {
		if( !array_key_exists( 'AuctionBid', $this->aModule ) ) {
			$this->aModule['AuctionBid'] = clRegistry::get( 'clAuctionBid', PATH_MODULE . '/auction/models' );
		}		
		// Read
		return $this->aModule['AuctionBid']->oDao->readHistory( $aParams );
	}
	
	/**
	 * Read auction auto bid
	 * ItemBid: Global read function for auction items
	 */
	public function readAuctionAutoBid( $aParams = array() ) {
		if( !array_key_exists( 'AuctionAutoBid', $this->aModule ) ) {
			$this->aModule['AuctionAutoBid'] = clRegistry::get( 'clAuctionAutoBid', PATH_MODULE . '/auction/models' );
		}		
		// Read
		return $this->aModule['AuctionAutoBid']->readCustom( $aParams );
	}
	
	/**
	 * Remove auction auto bid
	 * ItemBid: Global read function for auction items
	 */
	public function removeAutoBid( $iAutoId ) {
		if( !array_key_exists( 'AuctionAutoBid', $this->aModule ) ) {
			$this->aModule['AuctionAutoBid'] = clRegistry::get( 'clAuctionAutoBid', PATH_MODULE . '/auction/models' );
		}		
		// Read
		return $this->aModule['AuctionAutoBid']->removeAutoBid( $iAutoId );
	}
	
	/**
	 * Read auction item bid by user
	 */
	public function readBidByUser( $iUserId ) {
		if( !array_key_exists( 'AuctionBid', $this->aModule ) ) {
			$this->aModule['AuctionBid'] = clRegistry::get( 'clAuctionBid', PATH_MODULE . '/auction/models' );
		}		
		// Read
		return $this->aModule['AuctionBid']->readHistory( array(
			'userId' => $iUserId
		) );
	}
	
	/**
	 * Function for increase viewed count
	 */
	public function increaseViewedCount( $sType, $iPrimaryId ) {
		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/auction/models' );
		return call_user_func( array( $this->$sModule, 'increaseViewedCount' ), $iPrimaryId );
	}
	
	/**
	 * AuctionItem: Function to mark/unmark item as favorite
	 */
	public function updateFavoriteItem() {
		if( empty($_GET['favoriteItem']) || empty($_GET['status']) ) return false;
		
		$this->oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
		return $this->oAuctionItem->updateFavoriteItem( $_GET['favoriteItem'], $_SESSION['userId'], $_GET['status'] );
	}
	
}

/**
 * Base class for basic functions (create, createMultiple, read, update, delete).
 * @uses function( 'subclass/type', ... )
 */
abstract class clAuctionEngineBase {

	public $oAcl;

	protected $oDb;

	protected $aEvents = array();
	protected $oEventHandler;

	// For accessing route reading method
	#abstract protected function readRouteRelation( $sParentType, $mParentId );

	protected function initBase() {
		$oUser = clRegistry::get( 'clUser' );
		$this->setAcl( $oUser->oAcl );

		$this->oDb = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );

		$this->oEventHandler = clRegistry::get( 'clEventHandler' );
		$this->oEventHandler->addListener( $this, $this->aEvents );
	}

	public function setAcl( $oAcl ) {
		$this->oAcl = $oAcl;
	}

	public function create( $sType, $aData ) {
		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/auction/models' );
		return call_user_func( array( $this->$sModule, 'create' ), $aData );
	}

	public function createMultiple( $sType, $aData, $aFields ) {
		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/auction/models' );
		$sDao = 'oDao';
		return call_user_func( array( $this->$sModule, 'createMultiple' ), $aData, $aFields );
	}

	public function read( $sType, $aFields = array(), $primaryId = null ) {
		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/auction/models' );

		/**
		 * - Custom hook on for route handling.
		 * 'oDao->sEntitiesExtended' is a new additon in 'clDaoBaseSql.php', that hooks
		 * on it self to $aParams['entitiesExtended'] if populated with additional data.
		 * Goes by 'sPrimaryEntity' if $aParams['entitiesExtended'] appears empty.
		 */
		if( !empty($aFields) && is_array($aFields) && in_array('routePath', $aFields) ) {
			$sEntity = $this->$sModule->oDao->sPrimaryEntity;
			$sField = $this->$sModule->oDao->sPrimaryField;

			$this->$sModule->oDao->sEntitiesExtended = '
				LEFT JOIN entAuctionToRoute ON ' . $sEntity . '.' . $sField . ' = entAuctionToRoute.parentId
				AND entAuctionToRoute.parentType = ' . $this->oDb->escapeStr(lcfirst($sType)) . '
				LEFT JOIN entRoute ON entAuctionToRoute.routeId = entRoute.routeId';
		}

		return call_user_func( array( $this->$sModule, 'read' ), $aFields, $primaryId );
	}

	public function update( $sType, $primaryId, $aData ) {
		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/auction/models' );
		return call_user_func( array( $this->$sModule, 'update' ), $primaryId, $aData );
	}

	public function delete( $sType, $primaryId = null ) {
		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/auction/models' );
		return call_user_func( array( $this->$sModule, 'delete' ), $primaryId );
	}

}