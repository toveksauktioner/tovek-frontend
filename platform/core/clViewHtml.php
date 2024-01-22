<?php

require_once PATH_CORE . '/clModuleBase.php';

class clViewHtml extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'view';

		$this->oDao = clRegistry::get( 'clViewDao' . DAO_TYPE_DEFAULT_ENGINE );
		$this->initBase();
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;

		$aData['viewType'] = 'html';

		if( $this->oDao->createData($aData, $aParams) ) return $this->oDao->oDb->lastId();
		return false;
	}
	
	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$this->oEventHandler->triggerEvent( array(
			'preDeleteView' => $primaryId
		), 'internal' );
		return $this->oDao->deleteDataByPrimary( $primaryId );
	}

	public function read( $aFields = array(), $iViewId = null ) {
		$aParams = array(
			'fields' => $aFields,
			'viewType' => 'html'
		);
		if( $iViewId !== null ) {
			$aParams['viewId'] = $iViewId;
			return current( $this->oDao->read($aParams) );
		}
		return $this->oDao->read( $aParams );
	}

}
