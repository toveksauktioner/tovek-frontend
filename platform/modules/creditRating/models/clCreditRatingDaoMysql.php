<?php

/* * * *
 * Filename: clCreditRatingDaoMysql.php
 * Created: 15/09/2014 by Markus
 * Reference: database-overview.mwb
 * Description: See clCreditRating.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clCreditRatingDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entCreditRating' => array(
				'ratingId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Credit rating ID' )
				),
				'ratingService' => array(
					'type' => 'string',
					'title' => _( 'Rating service' )
				),
				'ratingServiceFunction' => array(
					'type' => 'string',
					'title' => _( 'Rating service function' )
				),
				'ratingStatus' => array(
					'type' => 'array',
					'values' => array(
						'requested' => _( 'Requested' ),
						'success' => _( 'Success' ),
						'fail' => _( 'Fail' )
					),
					'title' => _( 'Rating status' )
				),
				'ratingSearchPin' => array(
					'type' => 'string',
					'title' => _( 'Search pin' )
				),
				'ratingInData' => array(
					'type' => 'string',
					'title' => _( 'In data' )
				),
				'ratingResultData' => array(
					'type' => 'string',
					'title' => _( 'Result' )
				),
				'ratingCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		$this->sPrimaryEntity = 'entCreditRating';
		$this->sPrimaryField = 'ratingId';		
		$this->aFieldsDefault = array( '*' );
		
		$this->init();
	}
	
}
