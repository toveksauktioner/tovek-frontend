<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_HELPER . '/clJournalHelper.php';

class clHelpCategory extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'HelpCategory';
		$this->sModulePrefix = 'helpCategory';

		$this->oDao = clRegistry::get( 'clHelpCategoryDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/help/models' );

		$this->initBase();

		$this->aHelpers = array(
			'oJournalHelper' => new clJournalHelper( $this )
		);
	}

	public function create( $aData ) {
		if( empty($aData['helpCategoryCreated']) ) $aData['helpCategoryCreated'] = date( 'Y-m-d H:i:s' );

		return parent::create( $aData );
	}

	public function update( $iPrimaryId, $aData ) {
		if( empty($aData['helpCategoryUpdated']) ) $aData['helpCategoryUpdated'] = date( 'Y-m-d H:i:s' );

		return parent::update( $iPrimaryId, $aData );
	}

	public function delete( $iPrimaryId ) {
		// Delete help category
		$result = parent::delete( $iPrimaryId );
		if( is_int($result) ) {
			// Route
			$oRouter = clRegistry::get( 'clRouter' );
			$iRouteId = current(current( $oRouter->readByObject( $iPrimaryId, $this->sModuleName, 'entRoute.routeId' ) ));
			if( !empty($iRouteId) ) {
				// Delete route
				$oRouter->deleteRouteToObjectByRoute( $iRouteId );
				$oRouter->delete( $iRouteId );
			}
		}
		return $result;
	}

	public function updateSort() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$aPrimaryIds = func_get_args();
		return $this->oDao->updateSort( $aPrimaryIds );
	}

}
