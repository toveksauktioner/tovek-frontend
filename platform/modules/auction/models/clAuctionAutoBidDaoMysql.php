<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clAuctionAutoBidDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entAuctionAutoBid' => array(
				'autoId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Item ID' )
				),
				'autoMaxBid' => array(
					'type' => 'float'
				),
                'autoPlaced' => array(
					'type' => 'float', # decimal 10,4 in DB (microtime)
					'title' => _( 'Placed' )
				),
				'autoCreated' => array(
					'type' => 'datetime'
				),
				'autoRemoved' => array(
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
				'autoAuctionId' => array(
					'type' => 'integer'
				),
                'autoPartId' => array(
					'type' => 'integer'
				),
				'autoItemId' => array(
					'type' => 'integer'
				),
				'autoUserId' => array(
					'type' => 'integer'
				)
			)
		);
		$this->sPrimaryEntity = 'entAuctionAutoBid';
		$this->sPrimaryField = 'autoId';		
		$this->aFieldsDefault = array( '*' );
		
		$this->init();
	}
    
    /**
     * Save auto bid in DB
     *
     * @param array $aData (bidValue, bidAuctionId, bidPartId, bidItemId, bidType)
     * @return array
     */
    public function saveAutoBid( $aData ) {        
        // Save bid to DB
        $aData['autoCreated'] = date( 'Y-m-d H:i:s' );
        $aData['autoPlaced'] = microtime( true ); // 1547624295,4706
        return parent::createData( $aData );        
    }
    
	/**
	 * Read
	 */
	public function read( $aParams ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'autoId' => null,
			'auctionId' => null,
			'itemId' => null,
			'userId' => null,
			'minBid' => null,
			'removed' => 'no',
			'sorting' => array( 'autoMaxBid' => 'DESC' ),
			'entries' => null
		);
		
		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'sorting' => $aParams['sorting'],
			'entries' => $aParams['entries']
		);
		
		$aCriterias = array(
			sprintf( "autoRemoved = '%s'", $aParams['removed'] )
		);
		
		if( $aParams['autoId'] !== null ) {
			if( is_array($aParams['autoId']) ) {
				$aCriterias[] = 'autoId IN(' . implode( ', ', array_map('intval', $aParams['autoId']) ) . ')';
			} else {
				$aCriterias[] = 'autoId = ' . (int) $aParams['autoId'];
			}
		}
		
		if( $aParams['auctionId'] !== null ) {
			if( is_array($aParams['auctionId']) ) {
				$aCriterias[] = 'autoAuctionId IN(' . implode( ', ', array_map('intval', $aParams['auctionId']) ) . ')';
			} else {
				$aCriterias[] = 'autoAuctionId = ' . (int) $aParams['auctionId'];
			}
		}
		
		if( $aParams['itemId'] !== null ) {
			if( is_array($aParams['itemId']) ) {
				$aCriterias[] = 'autoItemId IN(' . implode( ', ', array_map('intval', $aParams['itemId']) ) . ')';
			} else {
				$aCriterias[] = 'autoItemId = ' . (int) $aParams['itemId'];
			}
		}
		
		if( $aParams['userId'] !== null ) {
			if( is_array($aParams['userId']) ) {
				$aCriterias[] = 'autoUserId IN(' . implode( ', ', array_map('intval', $aParams['userId']) ) . ')';
			} else {
				$aCriterias[] = 'autoUserId = ' . (int) $aParams['userId'];
			}
		}
		
		if( $aParams['minBid'] !== null ) {
			$aCriterias[] = 'autoMaxBid > ' . $this->oDb->escapeStr( $aParams['minBid'] );
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->readData( $aDaoParams );
	}
	
	/**
	 * Update
	 */
	public function update( $aData, $aParams = array() ) {		
		$aParams += array(
			'userId' => null
		);
		
		$aDaoParams = array();
		$aCriterias = array();
		
		if( $aParams['userId'] !== null ) {
			if( is_array($aParams['userId']) ) {
				$aCriterias[] = 'autoUserId IN(' . implode( ', ', array_map('intval', $aParams['userId']) ) . ')';
			} else {
				$aCriterias[] = 'autoUserId = ' . (int) $aParams['userId'];
			}
		}
		
		if( $aParams['itemId'] !== null ) {
			if( is_array($aParams['itemId']) ) {
				$aCriterias[] = 'autoItemId IN(' . implode( ', ', array_map('intval', $aParams['itemId']) ) . ')';
			} else {
				$aCriterias[] = 'autoItemId = ' . (int) $aParams['itemId'];
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->updateData( $aData, $aDaoParams );
	}
	
}
