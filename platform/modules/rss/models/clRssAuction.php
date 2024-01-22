<?php

require_once PATH_CORE . '/clModuleBase.php';

class clRssAuction extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'RssAuction';
		$this->sModulePrefix = 'rssAuction';

		$this->oDao = clRegistry::get( 'clRssAuctionDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/rss/models' );

		$this->initBase();

		$this->oDao->switchToSecondary();
	}

	public function readActive( $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'criterias' => 'rssAuctionStatus = "active"'
		);
		return $this->oDao->readData( $aParams );
	}

}
