<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clCustomerServiceDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entCustomerService' => array(
				'serviceId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),                
                'serviceTitleTextId' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),		
                'serviceDescriptionTextId' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'serviceCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),				
				'serviceUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			),
            'entCustomerToService' => array(
                'customerId' => array(
					'type' => 'integer',
					'title' => _( 'Customer ID' )
				),
                'serviceId' => array(
					'type' => 'integer',
					'title' => _( 'Service ID' )
				)
            )
		);
		
		$this->sPrimaryField = 'serviceId';
		$this->sPrimaryEntity = 'entCustomerService';
		$this->aFieldsDefault = '*';
		
		$this->init();
        
        $this->aHelpers = array(
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'serviceTitleTextId',
					'serviceDescriptionTextId'
				),				
				'sTextEntity' => 'entCustomerText'				
			) )
		);
	}	
}