<?php

require_once PATH_MODULE . '/fortnox/models/clFortnoxBase.php';
require_once PATH_MODULE . '/fortnox/models/clFortnoxDaoBaseRest.php';

class clFortnoxSupplier extends clFortnoxBase {

	public function __construct() {
		$this->sModuleName = 'FortnoxSupplier';
		$this->sModulePrefix = 'fortnoxSupplier';

		$this->sResourceName = 'suppliers';
		$this->sPropertyName = 'supplier';

		$this->oDao = new clFortnoxSupplierDaoRest();

		$this->initBase();
	}

}

class clFortnoxSupplierDaoRest extends clFortnoxDaoBaseRest {

	public function __construct() {
		/**
		 * https://developer.fortnox.se/documentation/resources/suppliers/#Fields
		 */
		$this->aDataDict = array(
			'entFortnoxSupplier' => array(
				'Active' => array(
					'type' => 'boolean',
					'values' => array(
						true => _( 'Yes' ),
						false => _( 'No' )
					),
					'title' => 'Active'
				),
				'Address1' => array(
					'type' => 'string',
					'title' => 'Address1'
				),
				'Address2' => array(
					'type' => 'string',
					'title' => 'Address2'
				),
				'Bank' => array(
					'type' => 'string',
					'title' => 'Bank'
				),
				'BankAccountNumber' => array(
					'type' => 'integer',
					'title' => 'BankAccountNumber'
				),
				'BG' => array(
					'type' => 'string',
					'title' => 'BG',
          'extraValidation' => array(
            'BgAccount'
          )
				),
				'BIC' => array(
					'type' => 'string',
					'title' => 'BIC'
				),
				'BranchCode' => array(
					'type' => 'string',
					'title' => 'BranchCode'
				),
				'City' => array(
					'type' => 'string',
					'title' => 'City'
				),
				'ClearingNumber' => array(
					'type' => 'integer',
					'title' => 'ClearingNumber'
				),
				'Comments' => array(
					'type' => 'string',
					'title' => 'Comments'
				),
				'CostCenter' => array(
					'type' => 'string',
					'title' => 'CostCenter'
				),
				'Country' => array(
					'type' => 'string',
					'title' => 'Country'
				),
				'CountryCode' => array(
					'type' => 'string',
          'min' => 2,
          'max' => 2,
					'title' => 'CountryCode'
				),
				'Currency' => array(
					'type' => 'string',
					'min' => 3,
					'max' => 3,
					'title' => 'Currency'
				),
				'DisablePaymentFile' => array(
					'type' => 'boolean',
					'values' => array(
						true => _( 'Yes' ),
						false => _( 'No' )
					),
					'title' => 'DisablePaymentFile'
				),
				'Email' => array(
					'type' => 'string',
					'title' => 'Email'
				),
				'Fax' => array(
					'type' => 'string',
					'title' => 'Fax'
				),
				'IBAN' => array(
					'type' => 'string',
					'title' => 'IBAN'
				),
				'Name' => array(
					'type' => 'string',
					'title' => 'Name',
          'required' => true
				),
				'OrganisationNumber' => array(
					'type' => 'string',
					'title' => 'OrganisationNumber',
          'extraValidation' => array(
            'CompanyPin'
          )
				),
				'OurReference' => array(
					'type' => 'string',
					'title' => 'OurReference'
				),
				'OurCustomNumber' => array(
					'type' => 'string',
					'title' => 'OurCustomNumber'
				),
				'PG' => array(
					'type' => 'string',
					'title' => 'PG',
          'extraValidation' => array(
            'BgAccount'
          )
				),
				'Phone1' => array(
					'type' => 'string',
					'title' => 'Phone1',
          'extraValidation' => array(
						'PhoneNumber'
          )
				),
				'Phone2' => array(
					'type' => 'string',
					'title' => 'Phone2',
          'extraValidation' => array(
            'PhoneNumber'
          )
				),
				'PreDefinedAccount' => array(
					'type' => 'string',
					'min' => 4,
					'max' => 4,
					'title' => 'PreDefinedAccount'
				),
				'Project' => array(
					'type' => 'string',
					'title' => 'Project'
				),
				'SupplierNumber' => array(
					'type' => 'string',
					'title' => 'SupplierNumber'
				),
				'TermsOfPayment' => array(
					'type' => 'string',
					'title' => 'TermsOfPayment'
				),
				'VATNumber' => array(
					'type' => 'string',
					'title' => 'VATNumber'
				),
				'VATType' => array(
					'type' => 'array', # (string)
					'title' => 'VATType',
					'values' => array(
						'REVERSE',
						'NORMAL',
						'UIF'
					)
				),
				'VisitingAddress' => array(
					'type' => 'string',
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
					'title' => 'VisitingCountryCode'
				),
				'VisitingZipCode' => array(
					'type' => 'string',
					'title' => 'VisitingZipCode'
				),
				'WorkPlace' => array(
					'type' => 'string',
					'title' => 'WorkPlace'
				),
				'WWW' => array(
					'type' => 'string',
					'title' => 'WWW'
				),
				'WayOfDelivery' => array(
					'type' => 'string',
					'title' => 'WayOfDelivery'
				),
				'YourReference' => array(
					'type' => 'string',
					'title' => 'YourReference'
				),
				'ZipCode' => array(
					'type' => 'string',
					'title' => 'ZipCode'
				)
			)
		);

		$this->sPrimaryField = 'SupplierNumber';
		$this->sPrimaryEntity = 'entFortnoxSupplier';
		$this->aFieldsDefault = '*';

		$this->init();
	}

}
