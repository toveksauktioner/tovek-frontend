<?php

require_once PATH_MODULE . '/slideshowImage/config/cfSlideshowImage.php';
require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_HELPER . '/clJournalHelper.php';

class clSlideshowImage extends clModuleBase {
	
	public function __construct() {
        $this->sModulePrefix = 'slideshowImage';
		$this->sModuleName = 'SlideshowImage';

        $this->oDao = clRegistry::get( 'clSlideshowImageDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/slideshowImage/models' );
		$this->initBase();
		
		$this->aHelpers = array(
			'oJournalHelper' => new clJournalHelper( $this )
		);
    }
	
	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );		
		$result = $this->oDao->deleteDataByPrimary( $primaryId );
		if( !empty($result) ) {
			// delete image
			$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
			$oImage->deleteByParent( $primaryId, $this->sModuleName );
			
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