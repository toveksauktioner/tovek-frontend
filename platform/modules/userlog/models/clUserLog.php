<?php

class clUserLog {
	
	private $aParams = array();

	private $sModuleName = "", $sModulePrefix = "";
	public $oDao;

	public function __construct() {
		$this->sModuleName = 'UserLog';
		$this->sModulePrefix = 'userlog';
		
		$this->oDao = clRegistry::get( 'clUserLogDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/userlog/models' );
	}
	
	public function setParams( $aParams = array() ) {
		if( empty($aParams['username']) ) {
			$oUser = clRegistry::get( 'clUser' );
			$sUsername = !empty($_SESSION['userId']) ? current(current( $oUser->oDao->read( array(
				'userId' => $_SESSION['userId'],
				'fields' => 'username'
			) ) )) : 'guest';
			$aParams += array(
				'username' => $sUsername,
			);
		}
		$aParams += array(
			'parentType' => null,
			'parentId' => 0,
			'event' => 'null'
		);
		$this->aParams = $aParams;
		return true;
	}

	public function create( $aData = array() ) {
		if( empty($this->aParams) ) return false;

		$aData = array(
			'username' => $this->aParams['username'],
			'userlogParentType' => $this->aParams['parentType'],
			'userlogParentId' => $this->aParams['parentId'],
			'userlogEvent' => $this->aParams['event'],
			'userlogCreated' => date( "Y-m-d H:i:s" )
		);
		return $this->oDao->createData( $aData );
	}

}