<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_BACK_PLATFORM . '/modules/email/config/cfEmailQueue.php';
require_once PATH_BACK_PLATFORM . '/modules/email/config/cfEmailSendGrid.php';

class clEmailQueue extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'emailQueue';

    $this->oDao = clRegistry::get( 'clEmailQueueDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/email/models' );
		$this->initBase();

    $this->oDao->switchToSecondary();
	}

  public function create( $aData ) {
    if( empty($aData['queueCreated']) ) {
      $aData['queueCreated'] = date( 'Y-m-d H:i:s' );
    }
    return parent::create( $aData );
  }
}
