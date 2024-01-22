<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clInvoiceAccountingDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entInvoiceAccounting' => array(
				'invoiceAccountingCode' => array(
					'type' => 'string',
					'min' => 4,
					'max' => 4,
					'primary' => true,
					'title' => _( 'Accounting code' )
				),
				'invoiceAccountingTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				)
			)
		);
		$this->sPrimaryEntity = 'entInvoiceAccounting';
		$this->sPrimaryField = 'invoiceAccountingId';		
		$this->aFieldsDefault = array( '*' );
		
		$this->init();
	}
}
