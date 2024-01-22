<?php

require_once PATH_CORE . '/clModuleBase.php';

class clUserAgreement extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'UserAgreement';
		$this->sModulePrefix = 'userAgreement';
		$this->oDao = clRegistry::get( 'clUserAgreementDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/userAgreement/models' );

		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}

	public function activate( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		return $this->update( $primaryId, array(
			'agreementActivated' => date( 'Y-m-d H:i:s' )
		) );
	}

	public function create( $aData ) {
		if( empty($aData['agreementCreated']) ) {
			$aData['agreementCreated'] = date( 'Y-m-d H:i:s' );
		}

		parent::create( $aData );
	}

	public function readCurrent( $aFields = array(), $sType = null ) {
		$aParams = array(
			'fields' => $aFields,
			'activated' => true,
			'agreementRequired' => $sType
		);
		$aAgreements = $this->oDao->read( $aParams );

		if( !empty($aAgreements) ) {
			return current( $aAgreements );
		}

		return false;
	}

	public function readStatus( $primaryId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => 'agreementActivated',
			'agreementId' => $primaryId
		);
		$aAgreements = $this->oDao->read( $aParams );

		if( !empty($aAgreements) ) {
			return current( current($aAgreements) );
		}

		return false;
	}

	public function update( $primaryId, $aData ) {
		// Prevent update if the agreement is active
		if( empty($this->readStatus($primaryId)) ) {
			return parent::update( $primaryId, $aData );
		}

		return false;
	}

}
