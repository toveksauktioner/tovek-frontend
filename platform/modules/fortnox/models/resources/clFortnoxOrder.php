<?php

require_once PATH_MODULE . '/fortnox/models/clFortnoxBase.php';
require_once PATH_MODULE . '/fortnox/models/clFortnoxDaoBaseRest.php';

class clFortnoxOrder extends clFortnoxBase {
	
	public function __construct() {
		$this->sModuleName = 'FortnoxOrder';
		$this->sModulePrefix = 'fortnoxOrder';
		
		$this->sResourceName = 'orders';
		$this->sPropertyName = 'order';
		
		$this->oDao = new clFortnoxOrderDaoRest();
		
		$this->initBase();
	}
	
}

class clFortnoxOrderDaoRest extends clFortnoxDaoBaseRest {
	
	public function __construct() {		
		/**
		 * https://developer.fortnox.se/documentation/resources/orders/#Fields
		 */
		$this->aDataDict = array(
			'entFortnoxOrder' => array(
				'@url' => array(
					'type' => 'string',
					'title' => '@url',
					'appearance' => 'readonly'		
				),
				'AdministrationFee' => array(
					'type' => 'float',
					'title' => 'AdministrationFee'
				),
				'AdministrationFeeVAT' => array(
					'type' => 'float',
					'title' => 'AdministrationFeeVAT',
					'appearance' => 'readonly'		
				),
				'Address1' => array(
					'type' => 'string',
					'title' => 'Address1'
				),
				'Address2' => array(
					'type' => 'string',
					'title' => 'Address2'
				),
				'BasisTaxReduction' => array(
					'type' => 'float',
					'title' => 'BasisTaxReduction',
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
				'City' => array(
					'type' => 'string',
					'title' => 'City'
				),
				'Comments' => array(
					'type' => 'string',
					'title' => 'Comments'
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
				'CopyRemarks' => array(
					'type' => 'boolean',
					'title' => 'CopyRemarks',
					'values' =>  array(
						'true',
						'false'
					)
				),
				'Country' => array(
					'type' => 'string',
					'title' => 'Country'
				),
				'CostCenter' => array(
					'type' => 'string',
					'title' => 'CostCenter',
					'searchable' => true
				),
				'Currency' => array(
					'type' => 'string',
					'max' => 3,
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
					'title' => 'CustomerName',
					'searchable' => true,
					'sortable' => true
				),
				'CustomerNumber' => array(
					'type' => 'string',
					'title' => 'CustomerNumber',
					'required' => true,
					'searchable' => true,
					'sortable' => true
				),
				'DeliveryAddress1' => array(
					'type' => 'string',
					'title' => 'DeliveryAddress1'
				),
				'DeliveryAddress2' => array(
					'type' => 'string',
					'title' => 'DeliveryAddress2'
				),
				'DeliveryCity' => array(
					'type' => 'string',
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
					'title' => 'DeliveryName'
				),
				'DeliveryZipCode' => array(
					'type' => 'string',
					'title' => 'DeliveryZipCode'
				),
				'DocumentNumber' => array(
					'type' => 'integer',
					'title' => 'DocumentNumber',
					'searchable' => true,
					'sortable' => true
				),
				//'OrderRows[OrderRow]' => array(
				//	'type' => '',
				//	'title' => 'OrderRows[OrderRow]'
				//),
				'ExternalInvoiceReference1' => array(
					'type' => 'string',
					'title' => 'ExternalInvoiceReference1',
					'searchable' => true
				),
				'ExternalInvoiceReference2' => array(
					'type' => 'string',
					'title' => 'ExternalInvoiceReference2',
					'searchable' => true
				),
				'Freight' => array(
					'type' => 'float',
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
				'InvoiceReference' => array(
					'type' => 'integer',
					'title' => 'InvoiceReference',
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
				'OrderDate' => array(
					'type' => 'date',
					'title' => 'OrderDate',
					'searchable' => true,
					'sortable' => true
				),
				'OfferReference' => array(
					'type' => 'integer',
					'title' => 'OfferReference',
					'appearance' => 'readonly'		
				),
				//'OrderRows[OrderRow]' => array(
				//	'type' => '',
				//	'title' => 'OrderRows[OrderRow]'
				//),
				'OrganisationNumber' => array(
					'type' => 'string',
					'title' => 'OrganisationNumber',
					'appearance' => 'readonly'		
				),
				'OurReference' => array(
					'type' => 'string',
					'title' => 'OurReference',
					'searchable' => true
				),
				'Phone1' => array(
					'type' => 'string',
					'title' => 'Phone1'
				),
				'Phone2' => array(
					'type' => 'string',
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
					'title' => 'Project',
					'searchable' => true
				),
				'Remarks' => array(
					'type' => 'string',
					'title' => 'Remarks'
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
					'appearance' => 'readonly',
					'searchable' => true		
				),
				'TaxReduction' => array(
					'type' => 'float',
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
				'TotalVat' => array(
					'type' => 'float',
					'title' => 'TotalVat',
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
				'WayOfDelivery' => array(
					'type' => 'string',
					'title' => 'WayOfDelivery'
				),
				'YourReference' => array(
					'type' => 'string',
					'title' => 'YourReference',
					'searchable' => true
				),
				'YourOrderNumber' => array(
					'type' => 'string',
					'title' => 'YourOrderNumber'
				),
				'ZipCode' => array(
					'type' => 'string',
					'title' => 'ZipCode'
				)
			),
			'entFortnoxOrderRow' => array(
				'AccountNumber' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'AccountNumber'
				),
				'ArticleNumber' => array(
					'type' => 'string',
					'title' => 'ArticleNumber'
				),
				'ContributionPercent' => array(
					'type' => 'float',
					'title' => 'ContributionPercent'
				),
				'ContributionValue' => array(
					'type' => 'float',
					'title' => 'ContributionValue'
				),
				'CostCenter' => array(
					'type' => 'string',
					'title' => 'CostCenter'
				),
				'DeliveredQuantity' => array(
					'type' => 'float',
					'title' => 'DeliveredQuantity'
				),
				'Description' => array(
					'type' => 'string',
					'title' => 'Description'
				),
				'Discount' => array(
					'type' => 'float',
					'title' => 'Discount'
				),
				'DiscountType' => array(
					'type' => 'string',
					'title' => 'DiscountType'
				),
				'HouseWork' => array(
					'type' => 'boolen',
					'title' => 'HouseWork'
				),
				'HouseWorkHoursToReport' => array(
					'type' => 'integer',
					'title' => 'HouseWorkHoursToReport'
				),
				'HouseWorkType' => array(
					'type' => 'string',
					'title' => 'HouseWorkType'
				),
				'OrderedQuantity' => array(
					'type' => 'float',
					'title' => 'OrderedQuantity'
				),
				'Price' => array(
					'type' => 'float',
					'title' => 'Price'
				),
				'Project' => array(
					'type' => 'string',
					'title' => 'Project'
				),
				'Total' => array(
					'type' => 'float',
					'title' => 'Total'
				),
				'Unit' => array(
					'type' => 'string',
					'title' => 'Unit'
				),
				'VAT' => array(
					'type' => 'float',
					'title' => 'VAT'
				)
			)
		);
		
		$this->sPrimaryField = 'DocumentNumber';
		$this->sPrimaryEntity = 'entFortnoxOrder';
		$this->aFieldsDefault = '*';
		
		$this->aDataFilters = array(
			'cancelled', 		 #Retrieves all orders with the status “cancelled”
			'expired', 			 # Retrieves all orders that has been expired
			'invoicecreated', 	 # Retrieves all offers where an invoice has been created
			'invoicenotcreated'  # Retrieves all orders where an invoice has not been created
		);
		
		$this->aDataActions = array(
			/**
			 * Fortnox have a few functions that is triggered by actions through the API.
			 * These actions is made with either a PUT request or a GET request, in which
			 * the action is provided in the URL.
			 */
			'createinvoice', 	 # Creates an invoice from the order
			'cancel', 			 # Cancels an order
			'email', 			 # Sends an e-mail to the customer with an attached PDF document of the invoice. You can use the field EmailInformation to customize the e-mail message on each invoice.
			'print', 			 # This action returns a PDF document with the current template that is used by the specific document. Note that this action also sets the field Sent as true.
			'externalprint', 	 # This action is used to set the field Sent as true from an external system without generating a PDF.
			'preview' 			 # This action returns a PDF document with the current template that is used by the specific document. Apart from the action print, this action doesn’t set the field Sent as true.
		);
		
		$this->init();
	}
	
}