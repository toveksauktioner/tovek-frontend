<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 18/03/2014 by Mikael
 * Description:
 * See clSubmission.php
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clSubmissionDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entSubmission' => array(
				'submissionId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Submission ID' )
				),
				'submissionTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'submissionReferral' => array(
					'type' => 'string',
					'title' => _( 'Submitters referral' )
				),
				'submissionFirstname' => array(
					'type' => 'string',
					'title' => _( 'Firstname' )
				),
				'submissionSurname' => array(
					'type' => 'string',
					'title' => _( 'Surname' )
				),
				'submissionCompanyName' => array(
					'type' => 'string',
					'title' => _( 'Company name' )
				),
				'submissionAddress' => array(
					'type' => 'string',
					'title' => _( 'Address' )
				),
				'submissionZipCode' => array(
					'type' => 'string',
					'title' => _( 'Zip Code' )
				),
				'submissionCity' => array(
					'type' => 'string',
					'title' => _( 'City' )
				),
				'submissionCountryCode' => array(
					'type' => 'string',
					'title' => _( 'Country code' )
				),
				'submissionCommissionFee' => array(
					'type' => 'float',
					'title' => _( 'Commission fee (%)' )
				),
				'submissionMarketingFee' => array(
					'type' => 'float',
					'title' => _( 'Marknadsföring (%)' )
				),
				'submissionMarketingFeeSum' => array(
					'type' => 'float',
					'title' => _( 'Marknadsföring (kr)' )
				),
				'submissionRecallFee' => array(
					'type' => 'float',
					'title' => _( 'Recalled fee (%)' )
				),
				'submissionBillingFee' => array(
					'type' => 'float',
					'title' => _( 'Billing note fee (cash payment)' )
				),
				'submissionPaymentDays' => array(
					'type' => 'integer',
					'title' => _( 'Payment days' )
				),
				'submissionPaymentToType' => array(
					'type' => 'array',
					'values' => array(
						'bg' => _( 'Bankgiro' ),
						'pg' => _( 'Plusgiro' ),
						'cash' => _( 'Cash' ),
						'account' => _( 'Account' )
					),
					/*'attributes' => array(
						'disabled' => true
					),*/
					'title' => _( 'Payment account type' )
				),
				'submissionPaymentToAccount' => array(
					'type' => 'string',
					'title' => _( 'Payment account no' ),
					/*'attributes' => array(
						'disabled' => true
					),*/
				),
				'submissionBillingLocked' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Billing locked?' )
				),
				'submissionBillingLockedDate' => array(
					'type' => 'datetime'
				),
				'submissionMoreGoodsAvailable' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Finns mer gods till försäljning' )
				),
				'submissionSettlementDate' => array(
					'type' => 'date',
					'title' => _( 'Set-off' )
				),
				'submissionStatus' => array(
					'type' => 'array',
					'values' => array(
						'new' => _( 'Ny' ),
						'populated' => _( 'Avslutad auktion' ),
						'sent' => _( 'Skickad' ),
						'editing' => _( 'Redigering' ),
						'approved' => _( 'Godkänd' )
					),
					'title' => _( 'Status' )
				),
				'submissionPartnerStatus' => array(
					'type' => 'array',
					'values' => array(
						'none' => _( 'Ingen' ),
						'created' => _( 'Skapad' ),
						'ready' => _( 'Klar' ),
						'approved' => _( 'Godkänd' )
					),
					'title' => _( 'Partner status' )
				),
				'submissionSigned' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Signerad' )
				),
				'submissionSignedDate' => array(
					'type' => 'date'
				),
				'submissionSentToUser' => array(
					'type' => 'datetime'
				),
				'submissionNotes' => array(
					'type' => 'string',
					'title' => _( 'Noteringar' )
				),
				'submissionCreated' => array(
					'type' => 'datetime'
				),
				// Foreign key's
				'submissionSubmitterId' => array(
					'type' => 'integer'
				),
				'submissionBillingLockedByUserId' => array(
					'type' => 'integer'
				),
				'submissionSentToUserId' => array(
					'type' => 'integer'
				),
				'submissionAuctionId' => array(
					'type' => 'integer'
				),
				'submissionAuctionPartId' => array(
					'type' => 'integer'
				),
				'submissionImportCustomId' => array(
					'type' => 'string',
					'title' => _( 'Import Submission ID' )
				),
				'submissionVismaOrderId' => array(
					'type' => 'integer'
				),
				'submissionCreatedByUserId' => array(
					'type' => 'integer'
				),
				'submissionCopyFromSubmissionId' => array(
					'type' => 'integer'
				)
			),
			'entSubmissionLine' => array(
				'lineId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Line ID' )
				),
				'lineTitle' => array(
					'type' => 'string',
					'title' => _( 'Line title' )
				),
				'lineSortNo' => array(
					'type' => 'integer',
					'title' => _( 'Line sort no' )
				),
				'lineSortLetter' => array(
					'type' => 'string',
					'title' => _( 'Line sort letter' )
				),
				'lineValue' => array(
					'type' => 'float',
					'title' => _( 'Line value' )
				),
				'lineRememberValue' => array(
					'type' => 'float',
					'title' => _( 'Remember value' )
				),
				'lineRememberFeeValue' => array(
					'type' => 'float',
					'title' => _( 'Fee remember value' )
				),
				'lineRememberSource' => array(
					'type' => 'array',
					'values' => array(
						'' => '',
						'zeroed' => _( 'Nolla rop' ),
						'auction' => _( 'Auktion' ),
						'manual' => _( 'Manuellt' )
					),
					'title' => _( 'Remember source' )
				),
				'lineRememberSourceType' => array(
					'type' => 'array',
					'values' => array(
						'' => '',
						'credit' => _( 'Kredit' )
					),
					'title' => _( 'Remember source type' )
				),
				'lineVatValue' => array(
					'type' => 'integer',
					'title' => _( 'Line VAT (%)' )
				),
				'lineRecalled' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Line recalled' )
				),
				// Foreign key's
				'lineSubmissionId' => array(
					'type' => 'integer',
					'index' => true
				),
				'lineAuctionItemId' => array(
					'type' => 'integer',
					'index' => true
				),
				'lineReportId' => array(
					'type' => 'integer'
				),
				'lineAuctionReportId' => array(
					'type' => 'integer'
				)
			),
			'entSubmissionBillingLine' => array(
				'billingLineId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Billing line ID' )
				),
				'billingLineTitle' => array(
					'type' => 'string',
					'title' => _( 'Line title' )
				),
				'billingLineType' => array(
					'type' => 'array',
					'values' => array(
						'preVat' => _( 'Before VAT' ),
						'postVat' => _( 'After VAT' )
					),
					'title' => _( 'Line type' )
				),
				'billingLineValue' => array(
					'type' => 'float',
					'title' => _( 'Line value' )
				),
				'billingLineSubmitterFeeMailPdf' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Bifoga PDF' )
				),
				// Foreign key's
				'billingLineSubmissionId' => array(
					'type' => 'integer',
					'index' => true
				),
				'billingLineSubmissionReportId' => array(
					'type' => 'integer',
					'index' => true
				),
				'billingLineSubmissionAuctionReportId' => array(
					'type' => 'integer',
					'index' => true
				),
				'billingLineSubmitterFeeId' => array(
					'type' => 'integer'
				)
			),
			'entSubmissionReport' => array(
				'submissionReportId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Submission report ID' )
				),
				'submissionNo' => array(
					'type' => 'integer',
					'title' => _( 'Submission no.' )
				),
				'submissionReportCommissionFee' => array(
					'type' => 'float',
					'title' => _( 'Commission fee (%)' )
				),
				'submissionReportCommissionMax' => array(
					'type' => 'integer',
					'title' => _( 'Max-provision (kr)' )
				),
				'submissionReportMarketingFeeType' => array(
					'type' => 'array',
					'values' => array(
						'sek' => _( 'kr' ),
						'percent' => '%'
					),
					'title' => _( 'Marknadsföring (typ)' )
				),
				'submissionReportMarketingFeeValue' => array(
					'type' => 'float',
					'title' => _( 'Marknadsföring' )
				),
				'submissionReportProject' => array(
					'type' => 'string',
					'title' => _( 'PR' )
				),
				'submissionReportPaymentDate' => array(
					'type' => 'date',
					'title' => _( 'Utbetalningsdag' )
				),
				'submissionReportPayedDate' => array(
					'type' => 'date',
					'title' => _( 'Betald (datum)' )
				),
				'submissionReportCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Submission report created' )
				),
				'submissionReportSentToFortnoxTime' => array(
					'type' => 'datetime'
				),
				// Foreign key's
				'reportSubmissionId' => array(
					'type' => 'integer',
					'title' => _( 'Submission ID' )
				),
				'reportAuctionId' => array(
					'type' => 'integer',
					'title' => _( 'Auction ID' )
				),
			),
			'entSubmissionAuctionReport' => array(
				'submissionAuctionReportId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Submission auction report ID' )
				),
				'submissionAuctionReportCommissionFee' => array(
					'type' => 'float',
					'title' => _( 'Commission fee (%)' )
				),
				'submissionAuctionReportCommissionMax' => array(
					'type' => 'integer',
					'title' => _( 'Max-provision (kr)' )
				),
				'submissionAuctionReportMarketingFeeType' => array(
					'type' => 'array',
					'values' => array(
						'sek' => _( 'kr' ),
						'percent' => '%'
					),
					'title' => _( 'Marknadsföring (typ)' )
				),
				'submissionAuctionReportMarketingFeeValue' => array(
					'type' => 'float',
					'title' => _( 'Marknadsföring' )
				),
				'submissionAuctionReportProject' => array(
					'type' => 'string',
					'title' => _( 'PR' )
				),
				'submissionAuctionReportPaymentDate' => array(
					'type' => 'date',
					'title' => _( 'Utbetalningsdag' )
				),
				'submissionAuctionReportPayedDate' => array(
					'type' => 'date',
					'title' => _( 'Betald (datum)' )
				),
				'submissionAuctionReportCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Submission auction report created' )
				),
				'submissionAuctionReportSentToFortnoxTime' => array(
					'type' => 'datetime'
				),
				// Foreign key's
				'reportSubmissionId' => array(
					'type' => 'integer',
					'title' => _( 'Submission ID' )
				),
				'reportAuctionId' => array(
					'type' => 'integer',
					'title' => _( 'Auction ID' )
				),
			)
		);
		$this->sPrimaryEntity = 'entSubmission';
		$this->sPrimaryField = 'submissionId';
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
			'submissionNo' => null,
			'submissionSubmitterId' => null,
			'submissionBillingLockedByUserId' => null,
			'submissionSentToUserId' => null,
			'submissionAuctionId' => null,
			'submissionAuctionPartId' => null,
			'submissionImportCustomId' => null,
			'submissionVismaOrderId' => null,
			'submissionCopyFromSubmissionId' => null
		);

		$aDaoParams['fields'] = $aParams['fields'];

		if( $aParams['submissionNo'] !== null ) {
			if( is_array($aParams['submissionNo']) ) {
				$aCriterias[] = 'submissionNo IN(' . implode( ', ', array_map('intval', $aParams['submissionNo']) ) . ')';
			} else {
				$aCriterias[] = 'submissionNo = ' . (int) $aParams['submissionNo'];
			}
		}

		if( $aParams['submissionSubmitterId'] !== null ) {
			if( is_array($aParams['submissionSubmitterId']) ) {
				$aCriterias[] = 'submissionSubmitterId IN(' . implode( ', ', array_map('intval', $aParams['submissionSubmitterId']) ) . ')';
			} else {
				$aCriterias[] = 'submissionSubmitterId = ' . (int) $aParams['submissionSubmitterId'];
			}
		}

		if( $aParams['submissionBillingLockedByUserId'] !== null ) {
			if( is_array($aParams['submissionBillingLockedByUserId']) ) {
				$aCriterias[] = 'submissionBillingLockedByUserId IN(' . implode( ', ', array_map('intval', $aParams['submissionBillingLockedByUserId']) ) . ')';
			} else {
				$aCriterias[] = 'submissionBillingLockedByUserId = ' . (int) $aParams['submissionBillingLockedByUserId'];
			}
		}

		if( $aParams['submissionSentToUserId'] !== null ) {
			if( is_array($aParams['submissionSentToUserId']) ) {
				$aCriterias[] = 'submissionSentToUserId IN(' . implode( ', ', array_map('intval', $aParams['submissionSentToUserId']) ) . ')';
			} else {
				$aCriterias[] = 'submissionSentToUserId = ' . (int) $aParams['submissionSentToUserId'];
			}
		}

		if( $aParams['submissionAuctionId'] !== null ) {
			if( is_array($aParams['submissionAuctionId']) ) {
				$aCriterias[] = 'submissionAuctionId IN(' . implode( ', ', array_map('intval', $aParams['submissionAuctionId']) ) . ')';
			} else {
				$aCriterias[] = 'submissionAuctionId = ' . (int) $aParams['submissionAuctionId'];
			}
		}

		if( $aParams['submissionAuctionPartId'] !== null ) {
			if( is_array($aParams['submissionAuctionPartId']) ) {
				$aCriterias[] = 'submissionAuctionPartId IN(' . implode( ', ', array_map('intval', $aParams['submissionAuctionPartId']) ) . ')';
			} else {
				$aCriterias[] = 'submissionAuctionPartId = ' . (int) $aParams['submissionAuctionPartId'];
			}
		}

		if( $aParams['submissionImportCustomId'] !== null ) {
			if( is_array($aParams['submissionImportCustomId']) ) {
				$aCriterias[] = 'submissionImportCustomId IN("' . implode( '", "', array_map('strval', $aParams['submissionImportCustomId']) ) . '")';
			} else {
				$aCriterias[] = 'submissionImportCustomId = "' . (string) $aParams['submissionImportCustomId'] . '"';
			}
		}

		if( $aParams['submissionVismaOrderId'] !== null ) {
			if( is_array($aParams['submissionVismaOrderId']) ) {
				$aCriterias[] = 'submissionVismaOrderId IN(' . implode( ', ', array_map('intval', $aParams['submissionVismaOrderId']) ) . ')';
			} else {
				$aCriterias[] = 'submissionVismaOrderId = ' . (int) $aParams['submissionVismaOrderId'];
			}
		}

		if( $aParams['submissionCopyFromSubmissionId'] !== null ) {
			if( is_array($aParams['submissionCopyFromSubmissionId']) ) {
				$aCriterias[] = 'submissionCopyFromSubmissionId IN(' . implode( ', ', array_map('intval', $aParams['submissionCopyFromSubmissionId']) ) . ')';
			} else {
				$aCriterias[] = 'submissionCopyFromSubmissionId = ' . (int) $aParams['submissionCopyFromSubmissionId'];
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/* * *
	 * Function for reading combinded data from both entSubmission and entSubmissionReport
	 * * */
	public function readExtendedData( $aParams, $sJoinType = 'LEFT' ) {
		$sCriterias = array();

		$aEntitiesExtend = array(
			$sJoinType . ' JOIN entSubmissionReport ON entSubmissionReport.reportSubmissionId = entSubmission.submissionId',
			$sJoinType . ' JOIN entSubmissionAuctionReport ON entSubmissionAuctionReport.reportSubmissionId = entSubmission.submissionId'
		);

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entitiesExtended' => 'entSubmission ' . implode( ' ', $aEntitiesExtend )
		);

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/* * *
	 * LINES
	 * Create a line for use in the submission
	 * * */
	public function createLine( $aData ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionLine',
			'fields' => array(
				'lineTitle',
				'lineSortNo',
				'lineSortLetter',
				'lineValue',
				'lineVatValue',
				'lineRecalled',
				'lineSubmissionId',
				'lineAuctionItemId'
			)
		);
		$this->createData( $aData, $aDaoParams );

		return $this->oDb->lastId();
	}

	/* * *
	 * Delete a line connected to the submission
	 * * */
	public function deleteLine( $iLineId ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionLine',
			'criterias' => 'lineId = ' . $this->oDb->escapeStr($iLineId)
		);
		return $this->deleteData( $aDaoParams );
	}

	/* * *
	 * Read line by primary
	 * * */
	public function readLine( $aFields, $iLineId = null ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionLine',
			'fields' => $aFields
		);

		$sCriterias = array();

		if( $iLineId !== null ) {
			if( is_array($iLineId) ) {
				$aCriterias[] = 'lineId IN(' . implode( ', ', array_map('intval', $iLineId) ) . ')';
			} else {
				$aCriterias[] = 'lineId = ' . (int) $iLineId;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/* * *
	 * Combined dao function for reading lines
	 * * */
	public function readLines( $aParams = array() ) {
		$aParams += array(
			'lineSubmissionId' => null,
			'lineId' => null,
			'lineAuctionItemId' => null,
			'lineReportId' => null,
			'lineAuctionReportId' => null,
			'fields' => null
		);

		$aDaoParams = array(
			'entities' => 'entSubmissionLine',
			'fields' => $aParams['fields'],
			'count' => false,
			'sorting' => array(
				'lineSortNo' => 'ASC',
				'lineSortLetter' => 'ASC'
			)
		);

		$sCriterias = array();

		if( $aParams['lineSubmissionId'] !== null ) {
			if( is_array($aParams['lineSubmissionId']) ) {
				$aCriterias[] = 'lineSubmissionId IN(' . implode( ', ', array_map('intval', $aParams['lineSubmissionId']) ) . ')';
			} else {
				$aCriterias[] = 'lineSubmissionId = ' . (int) $aParams['lineSubmissionId'];
			}
		}
		if( $aParams['lineId'] !== null ) {
			if( is_array($aParams['lineId']) ) {
				$aCriterias[] = 'lineId IN(' . implode( ', ', array_map('intval', $aParams['lineId']) ) . ')';
			} else {
				$aCriterias[] = 'lineId = ' . (int) $aParams['lineId'];
			}
		}
		if( $aParams['lineAuctionItemId'] !== null ) {
			if( is_array($aParams['lineAuctionItemId']) ) {
				$aCriterias[] = 'lineAuctionItemId IN(' . implode( ', ', array_map('intval', $aParams['lineAuctionItemId']) ) . ')';
			} else {
				$aCriterias[] = 'lineAuctionItemId = ' . $this->oDb->escapeStr( $aParams['lineAuctionItemId'] );
			}
		}
		if( $aParams['lineReportId'] !== null ) {
			if( is_array($aParams['lineReportId']) ) {
				$aCriterias[] = 'lineReportId IN(' . implode( ', ', array_map('intval', $aParams['lineReportId']) ) . ')';
			} else {
				$aCriterias[] = 'lineReportId = ' . $this->oDb->escapeStr( $aParams['lineReportId'] );
			}
		}
		if( $aParams['lineAuctionReportId'] !== null ) {
			if( is_array($aParams['lineAuctionReportId']) ) {
				$aCriterias[] = 'lineAuctionReportId IN(' . implode( ', ', array_map('intval', $aParams['lineAuctionReportId']) ) . ')';
			} else {
				$aCriterias[] = 'lineAuctionReportId = ' . $this->oDb->escapeStr( $aParams['lineAuctionReportId'] );
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/* * *
	 * Create a line for use in the submission
	 * * */
	public function updateLine( $iLineId, $aData ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionLine',
			'criterias' => 'lineId = ' . $this->oDb->escapeStr( $iLineId )
		);

		unset( $aData['lineId'] );

		return $this->updateData( $aData, $aDaoParams );
	}

	/* * *
	 * BILLING LINES
	 * Create a billing line for use in the billing of the submission
	 * * */
	public function createBillingLine( $aData ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionBillingLine',
			'fields' => array(
				'billingLineTitle',
				'billingLineType',
				'billingLineValue',
				'billingLineSubmissionId'
			)
		);
		$this->createData( $aData, $aDaoParams );

		return $this->oDb->lastId();
	}

	/* * *
	 * Delete a billing line connected to the submission
	 * * */
	public function deleteBillingLine( $iBillingLineId ) {

		$aDaoParams = array(
			'entities' => 'entSubmissionBillingLine',
			'criterias' => 'billingLineId = ' . $this->oDb->escapeStr($iBillingLineId)
		);
		return $this->deleteData( $aDaoParams );
	}

	/* * *
	 * Read billing line by primary
	 * * */
	public function readBillingLine( $aFields, $iBillingLineId = null ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionBillingLine',
			'count' => false,
			'fields' => $aFields
		);

		$sCriterias = array();

		if( $iBillingLineId !== null ) {
			if( is_array($iBillingLineId) ) {
				$aCriterias[] = 'billingLineId IN(' . implode( ', ', array_map('intval', $iBillingLineId) ) . ')';
			} else {
				$aCriterias[] = 'billingLineId = ' . (int) $iBillingLineId;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/* * *
	 * Combined dao function for reading billing lines
	 * * */
	public function readBillingLines( $aParams = array() ) {
		$aParams += array(
			'billingLineSubmissionId' => null,
			'billingLineSubmissionReportId' => null,
			'billingLineSubmissionAuctionReportId' => null,
			'billingLineId' => null,
			'billingLineType' => null
		);

		$aDaoParams = array(
			'entities' => 'entSubmissionBillingLine',
			'fields' => $aParams['fields'],
			'count' => false
		);

		$sCriterias = array();

		if( $aParams['billingLineSubmissionId'] !== null ) {
			if( is_array($aParams['billingLineSubmissionId']) ) {
				$aCriterias[] = 'billingLineSubmissionId IN(' . implode( ', ', array_map('intval', $aParams['billingLineSubmissionId']) ) . ')';
			} else {
				$aCriterias[] = 'billingLineSubmissionId = ' . (int) $aParams['billingLineSubmissionId'];
			}
		}
		if( $aParams['billingLineSubmissionReportId'] !== null ) {
			if( is_array($aParams['billingLineSubmissionReportId']) ) {
				$aCriterias[] = 'billingLineSubmissionReportId IN(' . implode( ', ', array_map('intval', $aParams['billingLineSubmissionReportId']) ) . ')';
			} else {
				$aCriterias[] = 'billingLineSubmissionReportId = ' . (int) $aParams['billingLineSubmissionReportId'];
			}
		}
		if( $aParams['billingLineSubmissionAuctionReportId'] !== null ) {
			if( is_array($aParams['billingLineSubmissionAuctionReportId']) ) {
				$aCriterias[] = 'billingLineSubmissionAuctionReportId IN(' . implode( ', ', array_map('intval', $aParams['billingLineSubmissionAuctionReportId']) ) . ')';
			} else {
				$aCriterias[] = 'billingLineSubmissionAuctionReportId = ' . (int) $aParams['billingLineSubmissionAuctionReportId'];
			}
		}
		if( $aParams['billingLineId'] !== null ) {
			if( is_array($aParams['billingLineId']) ) {
				$aCriterias[] = 'billingLineId IN(' . implode( ', ', array_map('intval', $aParams['billingLineId']) ) . ')';
			} else {
				$aCriterias[] = 'billingLineId = ' . (int) $aParams['billingLineId'];
			}
		}
		if( $aParams['billingLineType'] !== null ) {
			if( is_array($aParams['billingLineType']) ) {
				$aCriterias[] = 'billingLineType IN(' . implode( ', ', array_map('intval', $aParams['billingLineType']) ) . ')';
			} else {
				$aCriterias[] = 'billingLineType = ' . $this->oDb->escapeStr( $aParams['billingLineType'] );
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/* * *
	 * Update a billing line for use in the billing of the submission
	 * * */
	public function updateBillingLine( $iBillingLine, $aData ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionBillingLine',
			'criterias' => 'billingLineId = ' . $this->oDb->escapeStr( $iBillingLine )
		);

		unset( $aData['billingLineId'] );

		return $this->updateData( $aData, $aDaoParams );
	}


	/* * *
	 * SUBMISSION REPORT
	 * Create a submission report for an submission
	 * * */
	public function createReport( $aData ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionReport',
			'fields' => array(
				'reportSubmissionId',
				'reportAuctionId',
			)
		);
		$this->createData( $aData, $aDaoParams );

		return $this->oDb->lastId();
	}

	/* * *
	 * Delete a submission report
	 * * */
	public function deleteReport( $iReportId ) {

		$aDaoParams = array(
			'entities' => 'entSubmissionReport',
			'criterias' => 'submissionReportId = ' . $this->oDb->escapeStr($iReportId)
		);
		return $this->deleteData( $aDaoParams );
	}

	/* * *
	 * Read submission report by primary
	 * * */
	public function readReport( $aFields, $iReportId = null, $iSubmissionId = null ) {
		$aDaoParams = array(
			'entitiesExtended' => 'entSubmissionReport LEFT JOIN entSubmission ON entSubmission.submissionId = entSubmissionReport.reportSubmissionId',
			'fields' => $aFields,
			'count' => false
		);

		if( $iReportId !== null ) {
			if( is_array($iReportId) ) {
				$aCriterias[] = 'submissionReportId IN(' . implode( ', ', array_map('intval', $iReportId) ) . ')';
			} else {
				$aCriterias[] = 'submissionReportId = ' . (int) $iReportId;
			}
		}

		if( $iSubmissionId !== null ) {
			if( is_array($iSubmissionId) ) {
				$aCriterias[] = 'reportSubmissionId IN(' . implode( ', ', array_map('intval', $iSubmissionId) ) . ')';
			} else {
				$aCriterias[] = 'reportSubmissionId = ' . (int) $iSubmissionId;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/* * *
	 * Read submission report by report no
	 * * */
	public function readReportByNo( $iReportNo, $aFields ) {
		$aDaoParams = array(
			'entitiesExtended' => 'entSubmissionReport LEFT JOIN entSubmission ON entSubmission.submissionId = entSubmissionReport.reportSubmissionId',
			'fields' => $aFields,
			'count' => false
		);

		if( $iReportNo !== null ) {
			if( is_array($iReportNo) ) {
				$aCriterias[] = 'submissionReportNo IN(' . implode( ', ', array_map('intval', $iReportNo) ) . ')';
			} else {
				$aCriterias[] = 'submissionReportNo = ' . (int) $iReportNo;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/* * *
	 * Update a submission report
	 * * */
	public function updateReport( $iReportId, $aData ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionReport'
		);
		$aCriterias = array();

		if( $iReportId !== null ) {
			if( is_array($iReportId) ) {
				$aCriterias[] = 'submissionReportId IN(' . implode( ', ', array_map('intval', $iReportId) ) . ')';
			} else {
				$aCriterias[] = 'submissionReportId = ' . (int) $iReportId;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		unset( $aData['submissionReportId'] );

		return $this->updateData( $aData, $aDaoParams );
	}


	/* * *
	 * SUBMISSION AUCTION REPORT
	 * Create a submission auction report for an submission
	 * * */
	public function createAuctionReport( $aData ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionAuctionReport',
			'fields' => array(
				'reportSubmissionId',
				'reportAuctionId',
			)
		);
		$this->createData( $aData, $aDaoParams );

		return $this->oDb->lastId();
	}

	/* * *
	 * Delete a submission auction report
	 * * */
	public function deleteAuctionReport( $iReportId ) {

		$aDaoParams = array(
			'entities' => 'entSubmissionAuctionReport',
			'criterias' => 'submissionAuctionReportId = ' . $this->oDb->escapeStr($iReportId)
		);
		return $this->deleteData( $aDaoParams );
	}

	/* * *
	 * Read submission auction report by primary
	 * * */
	public function readAuctionReport( $aFields, $iReportId = null, $iSubmissionId = null ) {
		$aDaoParams = array(
			'entitiesExtended' => 'entSubmissionAuctionReport LEFT JOIN entSubmission ON entSubmission.submissionId = entSubmissionAuctionReport.reportSubmissionId',
			'fields' => $aFields,
			'count' => false
		);

		if( $iReportId !== null ) {
			if( is_array($iReportId) ) {
				$aCriterias[] = 'submissionAuctionReportId IN(' . implode( ', ', array_map('intval', $iReportId) ) . ')';
			} else {
				$aCriterias[] = 'submissionAuctionReportId = ' . (int) $iReportId;
			}
		}

		if( $iSubmissionId !== null ) {
			if( is_array($iSubmissionId) ) {
				$aCriterias[] = 'reportSubmissionId IN(' . implode( ', ', array_map('intval', $iSubmissionId) ) . ')';
			} else {
				$aCriterias[] = 'reportSubmissionId = ' . (int) $iSubmissionId;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

	/* * *
	 * Update a submission auction report
	 * * */
	public function updateAuctionReport( $iReportId, $aData ) {
		$aDaoParams = array(
			'entities' => 'entSubmissionAuctionReport'
		);
		$aCriterias = array();

		if( $iReportId !== null ) {
			if( is_array($iReportId) ) {
				$aCriterias[] = 'submissionAuctionReportId IN(' . implode( ', ', array_map('intval', $iReportId) ) . ')';
			} else {
				$aCriterias[] = 'submissionAuctionReportId = ' . (int) $iReportId;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		unset( $aData['submissionAuctionReportId'] );

		return $this->updateData( $aData, $aDaoParams );
	}

}
