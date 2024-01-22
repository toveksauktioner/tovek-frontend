<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clRssAuctionDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entRssAuction' => array(
				'rssId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'rssAuctionId' => array(
					'type' => 'integer',
					'title' => _( 'Auction' )
				),
				'rssAuctionStatus' => array(
					'type' => 'array',
					'title' => _( 'Status' ),
					'values' => array(
						'active' => _( 'Active' ),
						'inactive' => _( 'Inactive' )
					)
				),
				'rssAuctionCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);

		$this->sPrimaryField = 'rssId';
		$this->sPrimaryEntity = 'entRssAuction';
		$this->aFieldsDefault = '*';

		$this->init();
	}

}
