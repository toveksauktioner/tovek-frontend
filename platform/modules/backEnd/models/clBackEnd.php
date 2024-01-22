<?php

require_once PATH_CORE . '/clModuleBase.php';

class clBackEnd extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'BackEnd';
		$this->sModulePrefix = 'backEnd';

		$this->oDao = clRegistry::get( 'clBackEndDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/backEnd/models' );

		$this->initBase();
	}

    /**
     * Set source
     */
    public function setSource( $sEntity, $sField ) {
        $this->oDao->setSource( $sEntity, $sField );
    }

    /**
     * Set auction
     */
    public function setSourceToAuction() {
        $this->setSource( 'entAuction', 'auctionId' );
    }

	/**
     * Set auction part
     */
    public function setSourceToAuctionPart() {
        $this->setSource( 'entAuctionPart', 'partId' );
    }

    /**
     * Set auction
     */
    public function setSourceToAuctionItem() {
        $this->setSource( 'entAuctionItem', 'itemId' );
    }

    /**
     * Read auction
     */
    public function readAuction( $aFields = array(), $primaryId = null ) {
		$this->setSourceToAuction();
        return parent::read( $aFields, $primaryId );
	}

    /**
     * Read active auction
     */
    public function readActiveAuction( $aFields = array(), $primaryId = null ) {
		$this->setSourceToAuction();
        $this->oDao->setCriterias( array(
			'auctionStatus' => array(
				'fields' => 'auctionStatus',
				'value' => 'active'
			)
		) );
        $aData = parent::read( $aFields, $primaryId );
        $this->oDao->sCriterias = null;
        return $aData;
	}

	/**
     * Read active auction
     */
    public function readActiveAuctionPart( $aFields = array(), $primaryId = null, $aAuctionIds = array() ) {
		$this->setSourceToAuctionPart();
		$aCriterias = array();

		$aCriterias['partStatus'] = array(
			'fields' => 'partStatus',
			'type' => 'in',
			'value' => array(
				'running',
				'upcomming'
			)
		);
		if( !empty($aAuctionIds) ) {
			$aCriterias['partAuctionId'] = array(
				'fields' => 'partAuctionId',
				'type' => 'in',
				'value' => $aAuctionIds
			);
		}

		$this->oDao->setCriterias( $aCriterias );
        $aData = parent::read( $aFields, $primaryId );
        $this->oDao->sCriterias = null;
        return $aData;
	}

	/**
     * Read active auction
     */
    public function readAuctionPart( $aFields = array(), $primaryId = null, $aAuctionIds = array() ) {
		$this->setSourceToAuctionPart();
		$aCriterias = array();

		$aCriterias = array();
		if( !empty($aAuctionIds) ) {
			$aCriterias['partAuctionId'] = array(
				'fields' => 'partAuctionId',
				'type' => 'in',
				'value' => $aAuctionIds
			);
		}

		$this->oDao->setCriterias( $aCriterias );
        $aData = parent::read( $aFields, $primaryId );
        $this->oDao->sCriterias = null;
        return $aData;
	}

	/**
	 * Read auction item
	 */
	public function readAuctionItem( $aFields = array(), $primaryId = null ) {
		$this->setSourceToAuctionItem();
		return parent::read( $aFields, $primaryId );
	}

}
