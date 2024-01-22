<?php

/**
 * Filename: clAuctionSearchDaoMysql.php
 * Created: 25/05/2014 by Mikael
 * Reference: database-overview.mwb
 * Description: clAuctionSearch.php
 */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clAuctionSearchDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entAuctionSearch' => array(
				'searchId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'searchString' => array(
					'type' => 'string',
					'title' => _( 'Search string' )
				),
				'searchUserId' => array(
					'type' => 'integer'
				),				
				'searchCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)				
			)
		);
		$this->sPrimaryEntity = 'entAuctionSearch';
		$this->sPrimaryField = 'searchId';		
		$this->aFieldsDefault = array( '*' );
		
		$this->init();
	}

	/**
	 * Combined dao function for reading data
	 * based on foreign key's
	 */
	public function readByForeignKey( $aParams ) {
		$aDaoParams = array();
		$sCriterias = array();
		
		$aParams += array(
			'searchUserId' => null
		);
		
		$aDaoParams['fields'] = $aParams['fields'];
		
		if( $aParams['searchUserId'] !== null ) {
			if( is_array($aParams['searchUserId']) ) {
				$aCriterias[] = 'searchUserId IN(' . implode( ', ', array_map('intval', $aParams['searchUserId']) ) . ')';
			} else {
				$aCriterias[] = 'searchUserId = ' . (int) $aParams['searchUserId'];
			}
		}	
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->readData( $aDaoParams );
	}
	
	public function readByUser( $iUserId ) {
		$aDaoParams = array(
			'groupBy' => 'searchString',
			'criterias' => 'searchUserId = ' . (int) $iUserId
		);
		return $this->readData( $aDaoParams );
	}
	
}
