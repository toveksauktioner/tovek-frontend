<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_HELPER . '/clJournalHelper.php';

require_once PATH_MODULE . '/news/config/cfNews.php';
require_once PATH_MODULE . '/news/config/cfNewsImage.php';

class clNews extends clModuleBase {

	public $oJournal;

	public function __construct() {
		$this->sModuleName = 'News';
		$this->sModulePrefix = 'news';
		$this->oDao = clRegistry::get( 'clNewsDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/news/models' );
		
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
	
}