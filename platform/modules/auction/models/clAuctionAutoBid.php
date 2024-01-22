<?php

require_once PATH_CORE . '/clModuleBase.php';

class clAuctionAutoBid extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'AuctionAutoBid';
		$this->sModulePrefix = 'auctionAutoBid';
		
		$this->oDao = clRegistry::get( 'clAuctionAutoBidDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/auction/models' );
		
		$this->initBase();
	}
	
    /**
     * Save auto bid (auto bid can't be "placed")
     *
     * @param array $aData
     * @return array
     */
    public function saveAutoBid( $aData ) { 
        $this->oAcl->hasAccess( 'write' . $this->sModuleName );
        
        $aAutoData = array(
            'autoMaxBid' => $aData['bidValue'],
            'autoAuctionId' => $aData['bidAuctionId'],
            'autoItemId' => $aData['bidItemId'],
            'autoUserId' => $aData['bidUserId']
        );
        
		$this->updateByUserItem( $aData['bidUserId'], $aData['bidItemId'], array(
			'autoRemoved' => 'yes'
		) );
		
        /**
         * Save auto bid to DB
         */
        return $this->oDao->saveAutoBid( $aAutoData );
    }
	
    /**
     * Read by item
     * 
     * @param integer $iItemId
     * @param integer $iMinBid Minimum value to read from.
     *
     * @return array
     */
    public function readByItem( $iItemId, $iMinBid = 0 ) {
        $this->oAcl->hasAccess( 'write' . $this->sModuleName );
        // Fetch entries in DESC order and group data by user
        $aGrouped = groupByValue( 'autoUserId', $this->oDao->read( array(
			'itemId' => $iItemId,
			'minBid' => $iMinBid,
			'sorting' => array( 'autoMaxBid DESC' )
		) ) );
        $aData = array();
        foreach( $aGrouped as $iUserId => $aEntries ) {
            // Cos DESC order we can pick first entry
            $aData[ $iUserId ] = current( $aEntries );
        }
        // Reverse order to get low to heigh
        return array_reverse( $aData, true );
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
     * Read item highest auto bid
     */
	public function readItemHighestAutoBid( $iItemId ) {
		$aDaoParams = array(
			'itemId' => $iItemId,
			'fields' => array(
				'autoId',
				'autoItemId',				
				'autoUserId',
				'autoAuctionId',
				'autoMaxBid'
			),
			'sorting' => array( 'autoMaxBid' => 'DESC' ),
			'entries' => '1'			
		);
		return current( $this->oDao->read( $aDaoParams ) );
	}
	
	/**
	 * Update by user
	 */
	public function updateByUser( $iUserId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->update( $aData, array(
			'userId' => $iUserId
		) );
	}
	
	/**
	 * Update by user item
	 */
	public function updateByUserItem( $iUserId, $iItemId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->update( $aData, array(
			'userId' => $iUserId,
			'itemId' => $iItemId
		) );
	}
	
	/**
     * Remove auto bid
     */
	public function removeAutoBid( $iAutoId ) {
		return $this->update( $iAutoId, array(
			'autoRemoved' => 'yes'
		) );
	}
	
}
