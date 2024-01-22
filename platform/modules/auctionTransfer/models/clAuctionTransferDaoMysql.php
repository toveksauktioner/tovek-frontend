<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clAuctionTransferDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entAuctionTransfer' => array(
				'transferId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'transferType' => array(
					'type' => 'array',
					'title' => _( 'Type' ),
					'values' => array(
						'import' => _( 'Import' ),
						'export' => _( 'Export' )
					)
				),
				'transferAuctionId' => array(
					'type' => 'integer',
					'title' => _( 'Auction ID' )
				),
				'transferStatus' => array(
					'type' => 'array',
					'title' => _( 'Type' ),
					'values' => array(
						'running' => _( 'Running' ),
						'done' => _( 'Done' )
					)
				),
				'transferCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
                'transferUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);
		
		$this->sPrimaryField = 'transferId';
		$this->sPrimaryEntity = 'entAuctionTransfer';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}
    
}