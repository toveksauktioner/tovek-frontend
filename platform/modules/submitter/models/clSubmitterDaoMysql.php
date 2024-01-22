<?php

/* * * *
 * Filename: clSubmitterDaoMysql.php
 * Created: 26/05/2014 by Renfors
 * Reference: database-overview.mwb
 * Description: See clSubmitter.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clSubmitterDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entSubmitter' => array(
				'submitterId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Submitter ID' )
				),
				'submitterCustomId' => array(
					'type' => 'string',
					'title' => _( 'Custom ID' )
				),
				'submitterStatus' => array(
					'type' => 'array',
					'values' => array(
						'active' => _( 'Active' ),
						'inactive' => _( 'Inactive' )
					),
					'title' => _( 'Submitter status' )
				),
				'submitterPin' => array(
					'type' => 'string',
					'title' => _( 'PIN/Company ID' ),
					'extraValidation' => array(
						'CompanyPin'
					)
				),
				'submitterVatNo' => array(
					'type' => 'string',
					'title' => _( 'VAT-no' )
				),
				'submitterType' => array(
					'type' => 'array',
					'values' => array(
						'privatePerson' => _( 'Private person' ),
						'company' => _( 'Company' )
					),
					'title' => _( 'Customer type' )
				),
				'submitterCompanyName' => array(
					'type' => 'string',
					'title' => _( 'Name' ),
					'required' => true
				),
				'submitterFirstname' => array(
					'type' => 'string',
					'title' => _( 'Firstname' )
				),
				'submitterSurname' => array(
					'type' => 'string',
					'title' => _( 'Surname' )
				),
				'submitterAddress' => array(
					'type' => 'string',
					'title' => _( 'Address' )
				),
				'submitterZipCode' => array(
					'type' => 'string',
					'title' => _( 'Zip code' )
				),
				'submitterCity' => array(
					'type' => 'string',
					'title' => _( 'City' )
				),
				'submitterCountryCode' => array(
					'type' => 'string',
					'title' => _( 'Country code' )
				),
				'submitterPhone' => array(
					'type' => 'string',
					'title' => _( 'Phone' )
				),
				'submitterCellPhone' => array(
					'type' => 'string',
					'title' => _( 'Cell phone' )
				),
				'submitterEmail' => array(
					'type' => 'string',
					'title' => _( 'Email' )
				),
				'submitterCommissionFee' => array(
					'type' => 'integer',
					'title' => _( 'Commission fee' ) . ' (%)'
				),
				'submitterMarketingFee' => array(
					'type' => 'integer',
					'title' => _( 'Marketing Fee' ) . ' (%)'
				),
				'submitterRecallFee' => array(
					'type' => 'integer',
					'title' => _( 'Recall fee' ) . ' (%)'
				),
				'submitterPaymentDays' => array(
					'type' => 'integer',
					'title' => _( 'Payment days' )
				),
				'submitterPaymentToType' => array(
					'type' => 'array',
					'values' => array(
						'bg' => _( 'Bankgiro' ),
						'pg' => _( 'Plusgiro' ),
						'cash' => _( 'Cash' ),
						'account' => _( 'Account' )
					),
					'title' => _( 'Payment account type' )
				),
				'submitterPaymentToAccount' => array(
					'type' => 'string',
					'title' => _( 'Payment account no' )
				),
				'submitterPaymentPg' => array(
					'type' => 'string',
					'title' => _( 'PG-konto' ),
					'extraValidation' => array(
						'PgAccount'
					),
				),
				'submitterPaymentBg' => array(
					'type' => 'string',
					'title' => _( 'BG-konto' ),
					'extraValidation' => array(
						'BgAccount'
					),
				),
				'submitterPaymentBank' => array(
					'type' => 'string',
					'title' => _( 'Bank (info)' )
				),
				'submitterPaymentBankClearingNo' => array(
					'type' => 'integer',
					'title' => _( 'Clearingnr' )
				),
				'submitterPaymentBankAccountNo' => array(
					'type' => 'string',
          'extraValidation' => array(
            'Digits'
          ),
					'title' => _( 'Kontonummer' )
				),
				'submitterSubmissionType' => array(
					'type' => 'array',
					'values' => array(
						'D' => _( 'D type' ),
						'A' => _( 'A type' )
					),
					'title' => _( 'Submission type' )
				),
				'submitterCreated' => array(
					'type' => 'datetime'
				),
				// Foreign key's
				'submitterUserId' => array(
					'type' => 'integer'
				)
			),
			'entSubmitterToPartnerUser' => array(
				'submitterId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'Submitter ID' )
				),
				'partnerUserId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'Partner ID' )
				),
				'relationCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Relation created' )
				)
			)
		);
		$this->sPrimaryEntity = 'entSubmitter';
		$this->sPrimaryField = 'submitterId';
		$this->aFieldsDefault = array( '*' );

		$this->init();
	}

	/* * *
	 * Combined dao function for reading data
	 * based on foreign key's
	 * * */
	public function readByForeignKey( $aParams ) {
		$aDaoParams = array();
		$sCriterias = array();

		$aParams += array(
			'submitterNo' => null,
			'submitterUserId' => null,
			'submitterPin' => null
		);

		$aDaoParams['fields'] = $aParams['fields'];

		if( $aParams['submitterNo'] !== null ) {
			if( is_array($aParams['submitterNo']) ) {
				$aCriterias[] = 'submitterNo IN(' . implode( ', ', array_map('intval', $aParams['submitterNo']) ) . ')';
			} else {
				$aCriterias[] = 'submitterNo = ' . (int) $aParams['submitterNo'];
			}
		}

		if( $aParams['submitterUserId'] !== null ) {
			if( is_array($aParams['submitterUserId']) ) {
				$aCriterias[] = 'submitterUserId IN(' . implode( ', ', array_map('intval', $aParams['submitterUserId']) ) . ')';
			} else {
				$aCriterias[] = 'submitterUserId = ' . (int) $aParams['submitterUserId'];
			}
		}

		if( $aParams['submitterPin'] !== null ) {
			$aCriterias[] = "REPLACE(submitterPin, '-', '') = " . preg_replace( '/\D/', '', $aParams['submitterPin'] );
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/*
	 * Partner functions (is used for connecting an submitter to a user with "partner" authority. They can view/edit the submitter and it's submissions)
	 */

	public function readSubmitterToPartner( $mSubmitterId = null, $mPartnerUserId = null ) {
		$aDaoParams = array(
			'entities' => 'entSubmitterToPartnerUser'
		);
		$aCriterias = array();

		if( $mSubmitterId !== null ) {
			if( is_array($mSubmitterId) ) {
				$aCriterias[] = 'entSubmitterToPartnerUser.submitterId IN(' . implode( ', ', array_map('intval', $mSubmitterId) ) . ')';
			} else {
				$aCriterias[] = 'entSubmitterToPartnerUser.submitterId = ' . (int) $mSubmitterId;
			}
		}

		if( $mPartnerUserId !== null ) {
			if( is_array($mPartnerUserId) ) {
				$aCriterias[] = 'entSubmitterToPartnerUser.partnerUserId IN(' . implode( ', ', array_map('intval', $mPartnerUserId) ) . ')';
			} else {
				$aCriterias[] = 'entSubmitterToPartnerUser.partnerUserId = ' . (int) $mPartnerUserId;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	public function createSubmitterToPartner( $iSubmitterId, $iPartnerUserId ) {
		$aDaoParams = array(
			'entities' => array(
				'entSubmitterToPartnerUser'
			)
		);
		$aData = array(
			'submitterId' => $iSubmitterId,
			'partnerUserId' => $iPartnerUserId,
			'relationCreated' => date( 'Y-m-d H:i:s' )
		);
		if( $this->createData( $aData, $aDaoParams ) ) {
			return $this->oDb->lastId();
		}
		return false;
	}

	public function deleteSubmitterToPartner( $iSubmitterId, $iPartnerUserId ) {
		$aDaoParams = array(
			'entities' => 'entSubmitterToPartnerUser',
			'criterias' => 'entSubmitterToPartnerUser.submitterId = ' . $this->oDb->escapeStr($iSubmitterId) . ' AND entSubmitterToPartnerUser.partnerUserId = ' . $this->oDb->escapeStr($iPartnerUserId)
		);
		return $this->deleteData( $aDaoParams );
	}

}
