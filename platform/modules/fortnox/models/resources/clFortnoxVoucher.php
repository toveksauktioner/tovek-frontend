<?php

require_once PATH_MODULE . '/fortnox/models/clFortnoxBase.php';
require_once PATH_MODULE . '/fortnox/models/clFortnoxDaoBaseRest.php';

class clFortnoxVoucher extends clFortnoxBase {

	public function __construct() {
		$this->sModuleName = 'FortnoxVoucher';
		$this->sModulePrefix = 'fortnoxVoucher';

		$this->sResourceName = 'vouchers';
		$this->sPropertyName = 'voucher';

		$this->oDao = new clFortnoxVoucherDaoRest();

		$this->initBase();
	}

}

class clFortnoxVoucherDaoRest extends clFortnoxDaoBaseRest {

	public function __construct() {
		/**
		 * https://developer.fortnox.se/documentation/resources/vouchers/#Properties
		 */
		$this->aDataDict = array(
			'entFortnoxVoucher' => array(
				'@url' => array(
					'type' => 'boolean',
					'values' => array(
						true => _( 'Yes' ),
						false => _( 'No' )
					),
					'title' => 'Url',
					'appearance' => 'readonly'
				),
				'Comments' => array(
					'type' => 'string',
					'min' => 0,
					'max' => 1000,
					'title' => 'Comments'
				),
				'CostCenter' => array(
					'type' => 'string',
					'title' => 'CostCenter'
				),
				'Description' => array(
					'type' => 'string',
					'min' => 0,
					'max' => 200,
					'title' => 'Description',
					'required' => true
				),
				'Project' => array(
					'type' => 'string',
					'title' => 'Project'
				),
				'ReferenceNumber' => array(
					'type' => 'string',
					'title' => 'ReferenceNumber',
					'appearance' => 'readonly'
				),
				'ReferenceType' => array(
					'type' => 'array',
					'values' => array(
						'INVOICE',
						'SUPPLIERINVOICE',
						'INVOICEPAYMENT',
						'SUPPLIERPAYMENT',
						'MANUAL',
						'CASHINVOICE',
						'ACCRUAL'
					),
					'title' => 'ReferenceType'
				),
				'TransactionDate' => array(
					'type' => 'date',
					'title' => 'TransactionDate'
				),
				'VoucherNumber' => array(
					'type' => 'integer',
					'title' => 'VoucherNumber',
					'appearance' => 'readonly'
				),
				'VoucherRows' => array(
					'type' => 'object',
					'title' => 'VoucherRows',
					'objectDataDict' => array(
						'entFortnoxVoucherRow' => array(
							'Account' => array(
								'type' => 'integer',
								'min' => 1000,
								'max' => 9999,
								'title' => 'Account'
							),
							'CostCenter' => array(
								'type' => 'string',
								'title' => 'CostCenter'
							),
							'Credit' => array(
								'type' => 'float',
								'min' => 0,
								'max' => 999999999999.99,			// Maximum 14 digits (incl. decimals)
								'title' => 'Credit'
							),
							'Description' => array(
								'type' => 'string',
								'title' => 'Description',
								'appearance' => 'readonly'
							),
							'Debit' => array(
								'type' => 'float',
								'min' => 0,
								'max' => 999999999999.99,			// Maximum 14 digits (incl. decimals)
								'title' => 'Debit'
							),
							'Project' => array(
								'type' => 'string',
								'title' => 'Project'
							),
							'Removed' => array(
								'type' => 'boolean',
								'title' => 'Removed',
								'appearance' => 'readonly'
							),
							'TransactionInformation' => array(
								'type' => 'string',
								'min' => 0,
								'max' => 100,
								'title' => 'TransactionInformation'
							)
						)
					)
				),
				'VouceherSeries' => array(
					'type' => 'array',
					'values' => array(
						'A',	// Redovisning
						'B',	// Kundfakturor
						'C',	// Inbetalningar från kunder
						'D',	// Leverantörsfakturor
						'E',	// Utbetalningar till leverantörer
						'F',	// Kassa
						'G',	// Avskrivning
						'H',	// Periodisering
						'I',	// Bokslut
						'J',	// Revisor
						'K',	// Lön
						'L',	// Kontantfaktura
						'M'		// Momsrapport
					),
					'title' => 'VouceherSeries'
				),
				'Year' => array(
					'type' => 'integer',
					'title' => 'Year'
				)
			)
		);

		$this->sPrimaryField = 'VoucherNumber';
		$this->sPrimaryEntity = 'entFortnoxVoucher';
		$this->aFieldsDefault = '*';

		$this->init();
	}

}
