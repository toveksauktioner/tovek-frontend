<?php

require_once PATH_MODULE . '/fortnox/models/clFortnoxBase.php';
require_once PATH_MODULE . '/fortnox/models/clFortnoxDaoBaseRest.php';

class clFortnoxSupplierInvoice extends clFortnoxBase {

	public function __construct() {
		$this->sModuleName = 'FortnoxSupplierInvoice';
		$this->sModulePrefix = 'fortnoxSupplierInvoice';

		$this->sResourceName = 'supplierinvoices';
		$this->sPropertyName = 'supplierInvoice';

		$this->oDao = new clFortnoxSupplierInvoiceDaoRest();

		$this->initBase();
	}

}

class clFortnoxSupplierInvoiceDaoRest extends clFortnoxDaoBaseRest {

	public function __construct() {
		/**
		 * https://developer.fortnox.se/documentation/resources/supplier-invoices/#Fields
		 */
		$this->aDataDict = array(
			'entFortnoxSupplierInvoice' => array(
				'@url' => array(
					'type' => 'string',
					'title' => '@url',
					'appearance' => 'readonly'
				),
				'AccountingMethod' => array(
					'type' => 'array',
					'values' => array(
						'ACCRUAL' => _( 'Accrual' ),
						'CASH' => _( 'Cash' )
					),
					'title' => 'AccountingMethod'
				),
				'AdministrationFee' => array(
					'type' => 'float',
					'title' => 'AdministrationFee'
				),
				'Balance' => array(
					'type' => 'string',
					'title' => 'Balance',
					'appearance' => 'readonly'
				),
				'Booked' => array(
					'type' => 'boolean',
					'title' => 'Booked',
					'values' =>  array(
						'true',
						'false'
					),
					'appearance' => 'readonly'
				),
				'Cancelled' => array(
					'type' => 'boolean',
					'title' => 'Cancelled',
					'values' =>  array(
						'true',
						'false'
					),
					'appearance' => 'readonly'
				),
				'Comments' => array(
					'type' => 'string',
					'title' => 'Comments'
				),
				'CostCenter' => array(
					'type' => 'string',
					'title' => 'CostCenter'
				),
				'Credit' => array(
					'type' => 'boolean',
					'title' => 'Credit',
					'values' =>  array(
						'true',
						'false'
					),
					'appearance' => 'readonly'
				),
				'CreditReference' => array(
					'type' => 'integer',
					'title' => 'CreditReference'
				),
				'Currency' => array(
					'type' => 'string',
					'title' => 'Currency'
				),
				'CurrencyRate' => array(
					'type' => 'float',
					'title' => 'CurrencyRate'
				),
				'CurrencyUnit' => array(
					'type' => 'float',
					'title' => 'CurrencyUnit'
				),
				'DisablePaymentFile' => array(
					'type' => 'boolean',
					'title' => 'DisablePaymentFile',
					'values' =>  array(
						'true',
						'false'
					),
					'appearance' => 'readonly'
				),
				'DueDate' => array(
					'type' => 'date',
					'title' => 'DueDate'
				),
				'ExternalInvoiceNumber' => array(
					'type' => 'string',
					'title' => 'ExternalInvoiceNumber'
				),
				'ExternalInvoiceSeries' => array(
					'type' => 'string',
					'title' => 'ExternalInvoiceSeries'
				),
				'Freight' => array(
					'type' => 'float',
					'title' => 'Freight'
				),
				'GivenNumber' => array(
					'type' => 'float',
					'title' => 'GivenNumber'
				),
				'InvoiceNumber' => array(
					'type' => 'string',
					'title' => 'InvoiceNumber'
				),
				'OCR' => array(
					'type' => 'string',
					'title' => 'OCR'
				),
				'OurReference' => array(
					'type' => 'string',
					'title' => 'OurReference'
				),
				'Project' => array(
					'type' => 'string',
					'title' => 'Project'
				),
				'RoundOffValue' => array(
					'type' => 'float',
					'title' => 'RoundOffValue',
					'appearance' => 'readonly'
				),
				'SalesType' => array(
					'type' => 'array',
					'values' => array(
						'STOCK' => _( 'Stock' ),
						'SERVICE' => _( 'Service' )
					),
					'title' => 'SalesType'
				),
				'SupplierNumber' => array(
					'type' => 'integer',
					'title' => 'SupplierNumber'
				),
				'SupplierName' => array(
					'type' => 'string',
					'title' => 'SupplierName'
				),
				'Total' => array(
					'type' => 'float',
					'title' => 'Total',
					'appearance' => 'readonly'
				),
				'VAT' => array(
					'type' => 'float',
					'title' => 'VAT',
					'appearance' => 'readonly'
				),
				'VATType' => array(
					'type' => 'array',
					'values' => array(
						'NORMAL' => _( 'Normal' ),
						'EUINTERNAL' => _( 'EU internal' ),
						'REVERSE' => _( 'Reverse' )
					),
					'title' => 'VATType'
				),
				'YourReference' => array(
					'type' => 'string',
					'title' => 'YourReference'
				)
			),
			'entFortnoxSupplierInvoiceRows' => array(
				'Account' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'Account'
				),
				'ArticleNumber' => array(
					'type' => 'string',
					'title' => 'ArticleNumber'
				),
				'Code' => array(
					'type' => 'string',
					'title' => 'Code'
				),
				'CostCenter' => array(
					'type' => 'string',
					'title' => 'CostCenter'
				),
				'AccountDescription' => array(
					'type' => 'string',
					'title' => 'AccountDescription'
				),
				'ItemDescription' => array(
					'type' => 'string',
					'title' => 'ItemDescription'
				),
				'Debit' => array(
					'type' => 'float',
					'title' => 'Debit'
				),
				'DebitCurrency' => array(
					'type' => 'float',
					'title' => 'DebitCurrency'
				),
				'Credit' => array(
					'type' => 'float',
					'title' => 'Credit'
				),
				'CreditCurrency' => array(
					'type' => 'float',
					'title' => 'CreditCurrency'
				),
				'Project' => array(
					'type' => 'string',
					'title' => 'Project'
				),
				'Price' => array(
					'type' => 'float',
					'title' => 'Price'
				),
				'Quantity' => array(
					'type' => 'float',
					'title' => 'Quantity'
				),
				'Total' => array(
					'type' => 'float',
					'title' => 'Total',
					'appearance' => 'readonly'
				),
				'TransactionInformation' => array(
					'type' => 'string',
					'title' => 'TransactionInformation'
				),
				'Unit' => array(
					'type' => 'string',
					'title' => 'Unit'
				)
			)
		);

		$this->sPrimaryField = 'InvoiceNumber';
		$this->sPrimaryEntity = 'entFortnoxSupplierInvoice';
		$this->aFieldsDefault = '*';

		$this->aDataFilters = array(
			'cancelled',		 # Retrieves all invoices with the status “cancelled”
			'fullypaid',		 # Retrieves all invoices that has been fully paid
			'unpaid',			 # Retrieves all invoices that is unpaid
			'unpaidoverdue',	 # Retrieves all invoices that is unpaid and overdue
			'unbooked'			 # Retrieves all invoices that is unbooked
		);

		$this->aDataActions = array(
			/**
			 * Fortnox have a few functions that is triggered by actions through the API.
			 * These actions is made with either a PUT request or a GET request, in which
			 * the action is provided in the URL.
			 */
			'bookkeep',	 		 # Bookkeeps an invoice
			'cancel', 			 # Cancels an invoice
			'credit', 			 # Creates a credit of the supplier invoice
			'approvalpayment', 	 # Approval of payment of the supplier invoice
			'approvalbookkeep' 			 # 	Approval of bookkeep of the supplier invoice
		);

		$this->init();
	}

}
