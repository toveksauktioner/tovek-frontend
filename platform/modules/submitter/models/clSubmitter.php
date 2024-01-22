<?php

/* * * *
 * Filename: clSubmitter.php
 * Created: 26/05/2014 by Renfors
 * Reference: database-overview.mwb
 * Description:
 * * * */

require_once PATH_CORE . '/clModuleBase.php';

class clSubmitter extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'Submitter';
		$this->sModulePrefix = 'submitter';

		$this->oDao = clRegistry::get( 'clSubmitterDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/submitter/models' );

		$this->initBase();

		$this->oDao->switchToSecondary();
	}

	public function create( $aData ) {
		$oFortnoxSupplier = clRegistry::get( 'clFortnoxSupplier', PATH_MODULE . '/fortnox/models/resources' );

		$mResult = parent::create( $aData );

		// Fortnox supplier create
		if( !empty($mResult) ) {
			$aData['submitterNo'] = current( current($this->read('submitterNo', $mResult)) );
			$aSupplierFortnoxData = $oFortnoxSupplier->arrangeData( $aData, $GLOBALS['submitter']['fortnox']['structure'] );
			$oFortnoxSupplier->post( $aSupplierFortnoxData );
		}

		return $mResult;
	}

	public function update( $primaryId, $aData ) {
		$oFortnoxSupplier = clRegistry::get( 'clFortnoxSupplier', PATH_MODULE . '/fortnox/models/resources' );

		$mResult = parent::update( $primaryId, $aData );

		// Fortnox supplier update
		if( !empty($mResult) ) {
			$iSubmitterNo = current( current($this->read('submitterNo', $primaryId)) );
			$aSupplierFortnoxData = $oFortnoxSupplier->arrangeData( $aData, $GLOBALS['submitter']['fortnox']['structure'] );
			$oFortnoxSupplier->put( $iSubmitterNo, $aSupplierFortnoxData );
		}

		return $mResult;
	}

	public function delete( $primaryId ) {
		$oFortnoxSupplier = clRegistry::get( 'clFortnoxSupplier', PATH_MODULE . '/fortnox/models/resources' );
		$iSubmitterNo = current( current($this->read('submitterNo', $primaryId)) );

		$mResult = parent::delete( $primaryId );

		// Delete from Fortnox
		if( !empty($mResult) ) {
			$oFortnoxSupplier->delete( $iSubmitterNo );
		}

		return $mResult;
	}

	public function globalSearch( $sSearchString, $aInFields = array(), $aGetFields = array(), $iLimit = 20 ) {
		$this->oDao->sCriterias = '';
		$this->oDao->setEntries( $iLimit );

		if( empty($aInFields) ) {
			$aInFields = array(
				'submitterNo',
				'submitterCustomId',
				'submitterPin',
				'submitterVatNo',
				'submitterCompanyName',
				'submitterFirstname',
				'submitterSurname',
				'submitterAddress',
				'submitterZipCode',
				'submitterCity',
				'submitterPhone',
				'submitterCellPhone',
				'submitterEmail'
			);
		}

		if( empty($aGetFields) ) {
			$aGetFields = array(
				'submitterId AS identifier',
				'submitterNo AS no',
				"CONCAT(submitterCompanyName, ', ', submitterFirstname, ', ', submitterSurname, ' ', submitterEmail) AS name",
			);
		}

		$aSearchCriterias = array();
		$aSearchWords = explode( ' ', $sSearchString );
		foreach( $aSearchWords as $sWord ) {
			if( mb_strlen($sWord) > 3 ) {
				$aSearchCriterias[$sWord] = array(
					'type' => 'like',
					'value' => $sWord,
					'fields' => $aInFields
				);
			}
		}

		$this->oDao->setCriterias( $aSearchCriterias );
		$aSearchResult = $this->read( $aGetFields );

		$this->oDao->sCriterias = '';
		$this->oDao->setEntries( 0 );
		return $aSearchResult;
	}

	public function readFortnoxSubmitter( $primaryId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		$oFortnoxSupplier = clRegistry::get( 'clFortnoxSupplier', PATH_MODULE . '/fortnox/models/resources' );
		$iSubmitterNo = current( current($this->read('submitterNo', $primaryId)) );

		return $oFortnoxSupplier->get( $iSubmitterNo );
	}

	public function createFortnoxSubmitter( $primaryId ) {
		// Fortnox supplier create from existing submitter
		$oFortnoxSupplier = clRegistry::get( 'clFortnoxSupplier', PATH_MODULE . '/fortnox/models/resources' );

		$aData = current( $this->read('*', $primaryId) );

		$aSupplierFortnoxData = $oFortnoxSupplier->arrangeData( $aData, $GLOBALS['submitter']['fortnox']['structure'] );
		return $oFortnoxSupplier->post( $aSupplierFortnoxData );
	}

	public function updateFortnoxSubmitter( $primaryId ) {
		// Fortnox supplier update without updating web data
		$oFortnoxSupplier = clRegistry::get( 'clFortnoxSupplier', PATH_MODULE . '/fortnox/models/resources' );

		$aData = current( $this->read('*', $primaryId) );

		$aSupplierFortnoxData = $oFortnoxSupplier->arrangeData( $aData, $GLOBALS['submitter']['fortnox']['structure'] );
		return $oFortnoxSupplier->put( $aData['submitterNo'], $aSupplierFortnoxData );
	}

	public function readBySubmitterNo( $mSubmitterNo, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submitterNo' => $mSubmitterNo
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readByUser( $mUserId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submitterUserId' => $mUserId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readByPin( $mPin, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submitterPin' => $mPin
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/**
	 * Partner submitter functions
	 */
	public function readSubmitterToPartner( $iSubmitterId = null, $iPartnerUserId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readSubmitterToPartner( $iSubmitterId, $iPartnerUserId );
	}

	public function createSubmitterToPartner( $iSubmitterId, $iPartnerUserId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createSubmitterToPartner( $iSubmitterId, $iPartnerUserId );
	}

	public function deleteSubmitterToPartner( $iSubmitterId, $iPartnerUserId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteSubmitterToPartner( $iSubmitterId, $iPartnerUserId );
	}

}
