<?php

require_once PATH_MODULE . '/fortnox/models/clFortnoxBase.php';
require_once PATH_MODULE . '/fortnox/models/clFortnoxDaoBaseRest.php';

class clFortnoxCustomer extends clFortnoxBase {
	
	public function __construct() {
		$this->sModuleName = 'FortnoxCustomer';
		$this->sModulePrefix = 'fortnoxCustomer';
		
		$this->sResourceName = 'customers';
		$this->sPropertyName = 'customer';
		
		$this->oDao = new clFortnoxCustomerDaoRest();
		
		$this->initBase();
	}
	
}

class clFortnoxCustomerDaoRest extends clFortnoxDaoBaseRest {
	
	public function __construct() {
		/**
		 * https://developer.fortnox.se/documentation/resources/customers/#Properties
		 */
		$this->aDataDict = array(
			'entFortnoxCustomer' => array(
				'@url' => array(
					'type' => 'string',
					'title' => '@url',
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
				'City' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'City'
				),
				'Country' => array(
					'type' => 'string',
					'title' => 'Country',
					'appearance' => 'readonly'	
				),
				'Comments' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Comments'
				),
				'Currency' => array(
					'type' => 'string',
					'max' => 3,
					'title' => 'Currency'
				),
				'CostCenter' => array(
					'type' => 'string',
					'title' => 'CostCenter'
				),
				'CountryCode' => array(
					'type' => 'string',
					'max' => 2,
					'title' => 'CountryCode'
				),
				'CustomerNumber' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'CustomerNumber'
				),
				//'DefaultDeliveryTypes' => array(
				//	'type' => 'object',
				//	'title' => 'DefaultDeliveryTypes'
				//),
				//'DefaultTemplates' => array(
				//	'type' => 'object',
				//	'title' => 'DefaultTemplates'
				//),
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
					'max' => 1024,
					'title' => 'DeliveryCountry',
					'appearance' => 'readonly'	
				),
				'DeliveryCountryCode' => array(
					'type' => 'string',
					'max' => 2,
					'title' => 'DeliveryCountryCode'
				),
				'DeliveryFax' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'DeliveryFax'
				),
				'DeliveryName' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'DeliveryName'
				),
				'DeliveryPhone1' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'DeliveryPhone1'
				),
				'DeliveryPhone2' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'DeliveryPhone2'
				),
				'DeliveryZipCode' => array(
					'type' => 'string',
					'max' => 10,
					'title' => 'DeliveryZipCode'
				),
				'Email' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Email'
				),
				'EmailInvoice' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'EmailInvoice'
				),
				'EmailInvoiceBCC' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'EmailInvoiceBCC'
				),
				'EmailInvoiceCC' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'EmailInvoiceCC'
				),
				'EmailOffer' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'EmailOffer'
				),
				'EmailOfferBCC' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'EmailOfferBCC'
				),
				'EmailOfferCC' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'EmailOfferCC'
				),
				'EmailOrder' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'EmailOrder'
				),
				'EmailOrderBCC' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'EmailOrderBCC'
				),
				'EmailOrderCC' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'EmailOrderCC'
				),
				'Fax' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Fax'
				),
				'InvoiceAdministrationFee' => array(
					'type' => 'float',
					'max' => 11,
					'title' => 'InvoiceAdministrationFee'
				),
				'InvoiceDiscount' => array(
					'type' => 'float',
					'max' => 11,
					'title' => 'InvoiceDiscount'
				),
				'InvoiceFreight' => array(
					'type' => 'float',
					'max' => 11,
					'title' => 'InvoiceFreight'
				),
				'InvoiceRemark' => array(
					'type' => 'string',
					'title' => 'InvoiceRemark'
				),
				'Name' => array(
					'type' => 'string',
					'max' => 1024,
					'title' => 'Name'
				),
				'OrganisationNumber' => array(
					'type' => 'string',
					'max' => 30,
					'title' => 'OrganisationNumber'
				),
				'OurReference' => array(
					'type' => 'string',
					'max' => 50,
					'title' => 'OurReference'
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
				'Project' => array(
					'type' => 'string',
					'title' => 'Project'
				),
				'SalesAccount' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'SalesAccount'
				),
				'ShowPriceVATIncluded' => array(
					'type' => 'boolean',
					'title' => 'ShowPriceVATIncluded',
					'values' =>  array(
						'true',
						'false'
					)
				),
				'TermsOfDelivery' => array(
					'type' => 'string',
					'title' => 'TermsOfDelivery'
				),
				'TermsOfPayment' => array(
					'type' => 'string',
					'title' => 'TermsOfPayment'
				),
				'Type' => array(
					'type' => 'array',
					'title' => 'Type',
					'values' => array(
						'PRIVATE',
						'COMPANY'
					)
				),
				'VATNumber' => array(
					'type' => 'string',
					'title' => 'VATNumber'
				),
				'VATType' => array(
					'type' => 'array', # (string)
					'title' => 'VATType',
					'values' => array(
						'SEVAT',
						'SEREVERSEDVAT',
						'EUREVERSEDVAT',
						'EUVAT',
						'EXPORT'
					)
				),
				'VisitingAddress' => array(
					'type' => 'string',
					'max' => 1028,
					'title' => 'VisitingAddress'
				),
				'VisitingCity' => array(
					'type' => 'string',
					'title' => 'VisitingCity'
				),
				'VisitingCountry' => array(
					'type' => 'string',
					'title' => 'VisitingCountry',
					'appearance' => 'readonly'	
				),
				'VisitingCountryCode' => array(
					'type' => 'string',
					'max' => 2,
					'title' => 'VisitingCountryCode'
				),
				'VisitingZipCode' => array(
					'type' => 'string',
					'max' => 10,
					'title' => 'VisitingZipCode'
				),
				'WWW' => array(
					'type' => 'string',
					'max' => 1028,
					'title' => 'WWW'
				),
				'WayOfDelivery' => array(
					'type' => 'string',
					'title' => 'WayOfDelivery'
				),
				'YourReference' => array(
					'type' => 'string',
					'max' => 50,
					'title' => 'YourReference'
				),
				'ZipCode' => array(
					'type' => 'string',
					'max' => 10,
					'title' => 'ZipCode'
				)
			)
		);		
		
		$this->sPrimaryField = 'CustomerNumber';
		$this->sPrimaryEntity = 'entFortnoxCustomer';
		$this->aFieldsDefault = '*';
	
		$this->init();
	}
	
}