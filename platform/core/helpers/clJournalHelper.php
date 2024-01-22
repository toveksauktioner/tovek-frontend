<?php

class clJournalHelper {

	public $aParams = array();
	public $oModule;

	public function __construct( clModuleBase $oModule, $aParams = array() ) {
		$this->oModule = $oModule;

		$aParams += array(
			'helperDao' => 'oJournalHelperDao'
		);
		$this->aParams = $aParams;
	}

	public function read( $aFields = array(), $primaryId = null, $aParams = array() ) {
		$this->oModule->oAcl->hasAccess( 'read' . $this->oModule->sModuleName );

		$aParams['fields'] = $aFields;
		$aParams['journalId'] = $primaryId;

		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->read( $aParams );
	}

	public function readAll( $aFields = array(), $primaryId = null ) {
		$this->oModule->oAcl->hasAccess( 'read' . $this->oModule->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'journalId' => $primaryId,
			'status' => null,
			'mode' => null
		);
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->read( $aParams );
	}

}