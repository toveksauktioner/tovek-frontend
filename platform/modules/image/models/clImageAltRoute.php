<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/image/config/cfImage.php';

class clImageAltRoute extends clModuleBase {
	
	public function __construct() {
		if( !empty($aParams) ) $this->setParams($aParams);
		
		$this->sModulePrefix = 'imageAltRoute';

		$this->oDao = clRegistry::get( 'clImageAltRouteDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/image/models' );
		
		$this->initBase();
	}
	
	public function readByImage( $mImageId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'imageId' => $mImageId
		) );
	}
	
	public function readByImageRoute( $mImageId, $iRouteId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'imageId' => $mImageId,
			'routeId' => $iRouteId
		) );
	}
	
}