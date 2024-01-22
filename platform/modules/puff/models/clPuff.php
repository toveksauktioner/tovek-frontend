<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_HELPER . '/clJournalHelper.php';
require_once PATH_MODULE . '/puff/config/cfPuffImage.php';
require_once PATH_MODULE . '/puff/config/cfPuffLayout.php';

class clPuff extends clModuleBase {

	public $oJournal;

	public function __construct() {
		$this->sModuleName = 'Puff';
		$this->sModulePrefix = 'puff';

		$this->oDao = clRegistry::get( 'clPuffDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/puff/models' );
		$this->initBase();

		$this->aHelpers = array(
			'oJournalHelper' => new clJournalHelper( $this )
		);
	}

	public function readByRoute( $iRouteId, $aFields = array(), $primaryId = null, $aCriterias = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aDaoParams = array(
			'fields' => $aFields,
			'entitiesExtended' => 'entPuff LEFT JOIN entRouteToObject ON entPuff.puffId = entRouteToObject.objectId'
		);

		$aCriterias = array_merge( $aCriterias, array(
			'entRouteToObject.routeId = ' . $this->oDao->oDb->escapeStr( $iRouteId ),
			'entRouteToObject.objectType = ' . $this->oDao->oDb->escapeStr( 'puff' )
		) );

		if( $primaryId !== null ) {
			if( is_array($primaryId) ) {
				$aCriterias[] = 'entPuff.puffId IN(' . implode( ', ', array_map('intval', $primaryId) ) . ')';
			} else {
				$aCriterias[] = 'entPuff.puffId = ' . (int) $primaryId;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->oDao->readData( $aDaoParams );
	}

	public function read( $aFields = array(), $primaryId = null, $aCriterias = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		if( !empty($aCriterias) ) $aParams['criterias'] = implode( ' AND ', $aCriterias );

		if( $primaryId !== null ) return $this->oDao->readDataByPrimary($primaryId, $aParams);
		return $this->oDao->readData( $aParams );
	}

	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$result = $this->oDao->deleteDataByPrimary( $primaryId );
		if( !empty($result) ) {
			// Delete routeToObejct
			$oRouter = clRegistry::get( 'clRouter' );
			$oRouter->deleteRouteToObjectByObject( $primaryId, $this->sModuleName );

			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'The data has been deleted' )
			) );
		}
		return $result;
	}

	public function updateSort() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$aPrimaryIds = func_get_args();
		return $this->oDao->updateSort( $aPrimaryIds );
	}

}
