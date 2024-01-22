<?php

require_once PATH_MODULE . '/fortnox/models/clFortnoxBase.php';
require_once PATH_MODULE . '/fortnox/models/clFortnoxDaoBaseRest.php';

class clFortnoxInvoice extends clFortnoxBase {

	public function __construct() {
		$this->sModuleName = 'FortnoxInvoice';
		$this->sModulePrefix = 'fortnoxInvoice';

		$this->sResourceName = 'invoices';
		$this->sPropertyName = 'invoice';

		$this->oDao = new clFortnoxInvoiceDaoRest();

		$this->initBase();
	}

}

class clFortnoxInvoiceDaoRest extends clFortnoxDaoBaseRest {

	public function __construct() {
		/**
		 * https://developer.fortnox.se/documentation/resources/invoices/#Properties
		 */
		$this->aDataDict = array(
			'entFortnoxInvoice' => array(
				'@url' => array(
					'type' => 'string',
					'title' => '@url',
					'appearance' => 'readonly'
				),
				'@urlTaxReductionList' => array(
					'type' => 'string',
					'title' => 'UrlTaxReductionList',
					'appearance' => 'readonly'
				),
				'AccountingMethod' => array(
					'type' => 'array',
					'title' => 'AccountingMethod',
					'values' => array(
						'ACCRUAL',
						'CASH'
					)
				),
				'AdministrationFee' => array(
					'type' => 'float',
					'max' => 12,
					'title' => 'AdministrationFee'
				),
				'AdministrationFeeVAT' => array(
					'type' => 'float',
					'title' => 'AdministrationFeeVAT',
					'appearance' => 'readonly'
				),
				'Address1' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Address1'
				),
				'Address2' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Address2'
				),
				'Balance' => array(
					'type' => 'float',
					'title' => 'Balance',
					'appearance' => 'readonly'
				),
				'BasisTaxReduction' => array(
					'type' => 'float',
					'title' => 'BasisTaxReduction',
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
				'Credit' => array(
					'type' => 'boolean',
					'title' => 'Credit',
					'values' =>  array(
						'true',
						'false'
					),
					'appearance' => 'readonly'
				),
				'CreditInvoiceReference' => array(
					'type' => 'integer',
					'title' => 'CreditInvoiceReference'
				),
				'City' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'City'
				),
				'Comments' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Comments'
				),
				'ContractReference' => array(
					'type' => 'integer',
					'title' => 'ContractReference',
					'appearance' => 'readonly'
				),
				'ContributionPercent' => array(
					'type' => 'float',
					'title' => 'ContributionPercent',
					'appearance' => 'readonly'
				),
				'ContributionValue' => array(
					'type' => 'float',
					'title' => 'ContributionValue',
					'appearance' => 'readonly'
				),
				'Country' => array(
					'type' => 'string',
					'title' => 'Country'
				),
				'CostCenter' => array(
					'type' => 'string',
					'title' => 'CostCenter'
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
				'CustomerName' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'CustomerName'
				),
				'CustomerNumber' => array(
					'type' => 'string',
					'title' => 'CustomerNumber'
				),
				'DeliveryAddress1' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'DeliveryAddress1'
				),
				'DeliveryAddress2' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'DeliveryAddress2'
				),
				'DeliveryCity' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'DeliveryCity'
				),
				'DeliveryCountry' => array(
					'type' => 'string',
					'title' => 'DeliveryCountry'
				),
				'DeliveryDate' => array(
					'type' => 'date',
					'title' => 'DeliveryDate'
				),
				'DeliveryName' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'DeliveryName'
				),
				'DeliveryZipCode' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'DeliveryZipCode'
				),
				'DocumentNumber' => array(
					'type' => 'integer',
					'title' => 'DocumentNumber'
				),
				'DueDate' => array(
					'type' => 'date',
					'title' => 'DueDate'
				),
				//'EDIInformation' => array(
				//	'type' => 'object',
				//	'title' => 'EDIInformation'
				//),
				//'EmailInformation' => array(
				//	'type' => 'object',
				//	'title' => 'EmailInformation'
				//),
				'EUQuarterlyReport' => array(
					'type' => 'boolean',
					'title' => 'EUQuarterlyReport',
					'values' =>  array(
						'true',
						'false'
					)
				),
				'ExternalInvoiceReference1' => array(
					'type' => 'string',
					'max' => 80,
					'title' => 'ExternalInvoiceReference1'
				),
				'ExternalInvoiceReference2' => array(
					'type' => 'string',
					'max' => 80,
					'title' => 'ExternalInvoiceReference2'
				),
				'Freight' => array(
					'type' => 'float',
					'max' => 12,
					'title' => 'Freight'
				),
				'FreightVAT' => array(
					'type' => 'float',
					'title' => 'FreightVAT',
					'appearance' => 'readonly'
				),
				'Gross' => array(
					'type' => 'float',
					'title' => 'Gross',
					'appearance' => 'readonly'
				),
				'HouseWork' => array(
					'type' => 'boolean',
					'title' => 'HouseWork',
					'values' =>  array(
						'true',
						'false'
					),
					'appearance' => 'readonly'
				),
				'InvoiceDate' => array(
					'type' => 'date',
					'title' => 'InvoiceDate'
				),
				'InvoicePeriodStart' => array(
					'type' => 'date',
					'title' => 'InvoicePeriodStart',
					'appearance' => 'readonly'
				),
				'InvoicePeriodEnd' => array(
					'type' => 'date',
					'title' => 'InvoicePeriodEnd',
					'appearance' => 'readonly'
				),
				//'InvoiceRows' => array(
				//	'type' => 'array',
				//	'title' => 'InvoiceRows',
				//	'values' => array()
				//),
				'InvoiceType' => array(
					'type' => 'array',
					'title' => 'InvoiceType',
					'values' => array(
						 'INVOICE',
						 'AGREEMENTINVOICE',
						 'INTRESTINVOICE',
						 'SUMMARYINVOICE',
						 'CASHINVOICE'
					)
				),
				'Labels' => array(
					'type' => 'array',
					'title' => 'Labels',
					'values' => array(
						'Id'
					)
				),
				'Language' => array(
					'type' => 'array',
					'title' => 'Language',
					'values' => array(
						 'SV',
						 'EN'
					)
				),
				'LastRemindDate' => array(
					'type' => 'date',
					'title' => 'LastRemindDate',
					'appearance' => 'readonly'
				),
				'Net' => array(
					'type' => 'float',
					'title' => 'Net',
					'appearance' => 'readonly'
				),
				'NotCompleted' => array(
					'type' => 'boolean',
					'title' => 'NotCompleted',
					'values' =>  array(
						'true',
						'false'
					)
				),
				'NoxFinans' => array(
					'type' => 'boolean',
					'title' => 'NoxFinans',
					'values' =>  array(
						'true',
						'false'
					),
					'appearance' => 'readonly'
				),
				'OCR' => array(
					'type' => 'string',
					'title' => 'OCR'
				),
				'OfferReference' => array(
					'type' => 'integer',
					'title' => 'OfferReference',
					'appearance' => 'readonly'
				),
				'OrderReference' => array(
					'type' => 'integer',
					'title' => 'OrderReference',
					'appearance' => 'readonly'
				),
				'OrganisationNumber' => array(
					'type' => 'string',
					'title' => 'OrganisationNumber',
					'appearance' => 'readonly'
				),
				'OurReference' => array(
					'type' => 'string',
					'max' => 50,
					'title' => 'OurReference'
				),
				'Phone1' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Phone1'
				),
				'Phone2' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Phone2'
				),
				'PriceList' => array(
					'type' => 'string',
					'title' => 'PriceList'
				),
				'PrintTemplate' => array(
					'type' => 'string',
					'title' => 'PrintTemplate'
				),
				'Project' => array(
					'type' => 'string',
					'title' => 'Project'
				),
				'Remarks' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Remarks'
				),
				'Reminders' => array(
					'type' => 'integer',
					'title' => 'Reminders',
					'appearance' => 'readonly'
				),
				'RoundOff' => array(
					'type' => 'float',
					'title' => 'RoundOff',
					'appearance' => 'readonly'
				),
				'Sent' => array(
					'type' => 'boolean',
					'title' => 'Sent',
					'values' =>  array(
						'true',
						'false'
					),
					'appearance' => 'readonly'
				),
				'TaxReduction' => array(
					'type' => 'integer',
					'title' => 'TaxReduction',
					'appearance' => 'readonly'
				),
				'TermsOfDelivery' => array(
					'type' => 'string',
					'title' => 'TermsOfDelivery'
				),
				'TermsOfPayment' => array(
					'type' => 'string',
					'title' => 'TermsOfPayment'
				),
				'Total' => array(
					'type' => 'float',
					'title' => 'Total',
					'appearance' => 'readonly'
				),
				'TotalVAT' => array(
					'type' => 'float',
					'title' => 'TotalVAT',
					'appearance' => 'readonly'
				),
				'VATIncluded' => array(
					'type' => 'boolean',
					'title' => 'VATIncluded',
					'values' =>  array(
						'true',
						'false'
					)
				),
				'VoucherNumber' => array(
					'type' => 'integer',
					'title' => 'VoucherNumber',
					'appearance' => 'readonly'
				),
				'VoucherSeries' => array(
					'type' => 'string',
					'title' => 'VoucherSeries',
					'appearance' => 'readonly'
				),
				'VoucherYear' => array(
					'type' => 'integer',
					'title' => 'VoucherYear',
					'appearance' => 'readonly'
				),
				'WayOfDelivery' => array(
					'type' => 'string',
					'title' => 'WayOfDelivery'
				),
				'YourOrderNumber' => array(
					'type' => 'string',
					'max' => 30,
					'title' => 'YourOrderNumber'
				),
				'YourReference' => array(
					'type' => 'string',
					'max' => 50,
					'title' => 'YourReference'
				),
				'ZipCode' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'ZipCode'
				)
			),
			'entFortnoxInvoiceRow' => array(
				'AccountNumber' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'AccountNumber'
				),
				'ArticleNumber' => array(
					'type' => 'string',
					'max' => 50,
					'title' => 'ArticleNumber'
				),
				'ContributionPercent' => array(
					'type' => 'float',
					'title' => 'ContributionPercent',
					'appearance' => 'readonly'
				),
				'ContributionValue' => array(
					'type' => 'float',
					'title' => 'ContributionValue',
					'appearance' => 'readonly'
				),
				'CostCenter' => array(
					'type' => 'string',
					'title' => 'CostCenter'
				),
				'DeliveredQuantity' => array(
					'type' => 'float',
					'max' => 14,
					'title' => 'DeliveredQuantity'
				),
				'Description' => array(
					'type' => 'string',
					'max' => 50,
					'title' => 'Description'
				),
				'Discount' => array(
					'type' => 'float',
					'max' => 12,
					'title' => 'Discount'
				),
				'DiscountType' => array(
					'type' => 'array',
					'title' => 'DiscountType',
					'values' => array(
						'AMOUNT',
						'PERCENT'
					)
				),
				'HouseWork' => array(
					'type' => 'boolean',
					'title' => 'HouseWork',
					'values' => array(
						'true',
						'false'
					)
				),
				'HouseWorkHoursToReport' => array(
					'type' => 'integer',
					'max' => 5,
					'title' => 'HouseWorkHoursToReport'
				),
				'HouseWorkType' => array(
					'type' => 'array',
					'title' => 'HouseWorkType',
					'values' => array(
						'CONSTRUCTION',
						'ELECTRICITY',
						'GLASSMETALWORK',
						'GROUNDDRAINAGEWORK',
						'MASONRY',
						'PAINTINGWALLPAPERING',
						'MOVINGSERVICES',
						'ITSERVICES',
						'CLEANING',
						'TEXTILECLOTHING',
						'SNOWPLOWING',
						'GARDENING',
						'BABYSITTING',
						'OTHERCARE',
						'OTHERCOSTS'
					)
				),
				'Price' => array(
					'type' => 'float',
					'max' => 12,
					'title' => 'Price'
				),
				'PriceExcludingVAT' => array(
					'type' => 'float',
					'title' => 'PriceExcludingVAT',
					'appearance' => 'readonly'
				),
				'Project' => array(
					'type' => 'string',
					'title' => 'Project'
				),
				'Total' => array(
					'type' => 'float',
					'title' => 'Total',
					'appearance' => 'readonly'
				),
				'TotalExcludingVAT' => array(
					'type' => 'float',
					'title' => 'TotalExcludingVAT',
					'appearance' => 'readonly'
				),
				'Unit' => array(
					'type' => 'string',
					'title' => 'Unit'
				),
				'VAT' => array(
					'type' => 'integer',
					'title' => 'VAT'
				),
			)
		);

		$this->sPrimaryField = 'DocumentNumber';
		$this->sPrimaryEntity = 'entFortnoxInvoice';
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
			'credit', 			 # Creates a credit invoice from the provided invoice. The created credit invoice will be referenced in the property CreditInvoiceReference.
			'email', 			 # Sends an e-mail to the customer with an attached PDF document of the invoice. You can use the properties in the EmailInformation to customize the e-mail message on each invoice.
			'print', 			 # This action returns a PDF document with the current template that is used by the specific document. Note that this action also sets the property Sent as true.
			'printreminder', 	 # This action returns a PDF document with the current reminder template that is used by the specific document. Note that this action also sets the property Sent as true.
			'externalprint', 	 # This action is used to set the field Sent as true from an external system without generating a PDF.
			'preview' 			 # This action returns a PDF document with the current template that is used by the specific document. Apart from the action print, this action doesn’t set the property Sent as true.
		);

		$this->init();
	}

}
