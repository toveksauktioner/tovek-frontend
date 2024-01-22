<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_HELPER . '/clJournalHelper.php';

class clFaqCategory extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'FaqCategory';
		$this->sModulePrefix = 'category';
		
		$this->oDao = clRegistry::get( 'clFaqCategoryDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/faq/models' );
		
		$this->initBase();
		
		$this->aHelpers = array(
			'oJournalHelper' => new clJournalHelper( $this )
		);
	}
	
	public function delete( $iPrimaryId ) {
		// Delete news
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