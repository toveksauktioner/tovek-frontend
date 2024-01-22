<?php

require_once PATH_CORE . '/clModuleBase.php';

class clSlideshowImageToLayout extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'SlideshowImageToLayout';
		
		$this->oDao = clRegistry::get( 'clSlideshowImageToLayoutDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/slideshowImage/models' );
		$this->initBase();
	}
	
	public function readBySlideshowImageId( $iSlideshowImageId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );		
		return $this->oDao->readBySlideshowImageId( $iSlideshowImageId );
	}
	
	public function readByLayoutKey( $sLayoutKey = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );		
		return $this->oDao->readByLayoutKey( $sLayoutKey );
	}
	
}