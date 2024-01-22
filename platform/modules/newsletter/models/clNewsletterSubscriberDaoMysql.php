<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clNewsletterSubscriberDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entNewsletterSubscriber' => array(
				'subscriberId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'subscriberName' => array(
					'type' => 'string',
					'title' => _( 'Name' )
				),
				'subscriberEmail' => array(
					'type' => 'string',
					'title' => _( 'Email' ),
					'min' => 6,
					'max' => 320,
					'extraValidation' => array(
						'email'
					),
					'required' => true
				),
				'subscriberStatus' => array(
					'type' => 'array',
					'title' => _( 'Status' ),
					'values' => array(
						'active' => _( 'Active' ),
						'inactive' => _( 'Inactive' ),
						'bounce' => _( 'Bounce' )
					),
					'required' => true
				),
				'subscriberUnsubscribe' => array(
					'type' => 'array',
					'title' => _( 'User unsubscribed' ),
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					)
				),
				'subscriberCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			),
			'entNewsletterSubscriberToGroup' => array(
				'subscriberId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'Subscriber ID' ),
					'required' => true
				),
				'groupId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'Group ID' ),
					'required' => true
				)
			)
		);
		
		$this->sPrimaryField = 'subscriberId';
		$this->sPrimaryEntity = 'entNewsletterSubscriber';
		$this->aFieldsDefault = '*';
	
		$this->init();
	}
	
}
