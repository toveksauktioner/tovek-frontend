<?php

require_once PATH_CORE . '/clModuleBase.php';

require_once PATH_HELPER . '/clTreeHelper.php';

require_once PATH_MODULE . '/customer/config/cfCustomer.php';
require_once PATH_MODULE . '/customer/config/cfCustomerImage.php';

class clCustomerCategory extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'category';
		$this->sModuleName = 'CustomerCategory';
        
		$this->oDao = clRegistry::get( 'clCustomerCategoryDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/customer/models' );
		$this->initBase();
		
		$this->aHelpers = array(
			'oTreeHelper' => new clTreeHelper( $this )
		);
	}

	public function delete( $iCategoryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		// Route
		$oRouter = clRegistry::get( 'clRouter' );
		$aRouteData = $oRouter->readByObject( $iCategoryId, 'CustomerCategory', 'entRoute.routeId' );		
		if( !empty($aRouteData) ) {
			$iRouteId = current(current( $aRouteData ));
			// Delete route
			$oRouter->deleteRouteToObjectByRoute( $iRouteId );
			$oRouter->delete( $iRouteId );
		}
				
		return $this->oDao->aHelpers['oTreeHelperDao']->deleteNode( $iCategoryId );
	}
	
}
