<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/userPassRetrieval/config/cfUserPassRetrieval.php';

class clUserPassRetrieval extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'retrieval';
		$this->sModuleName = 'UserPassRetrieval';
		$this->oDao = clRegistry::get( 'clUserPassRetrievalDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/userPassRetrieval/models' );

		$this->initBase();

		$this->oDao->switchToSecondary();
	}

	public function readCustom( $aParams ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( $aParams );
	}

	public function readUserIdByKey( $sKey ) {
		$aData = $this->oDao->read( array(
			'activationKey' => $sKey,
			'fields' => 'retrievalUserId'
		) );

		if( !empty($aData) ) {
			return current( current($aData) );
		}

		return false;
	}

	public function activateByKey( $sKey ) {
		$this->oAcl->hasAccess( 'activate' . $this->sModuleName );
		return $this->oDao->activateByKey( $sKey );
	}

	public function createByUser( $iUserId, $sNewPass, $sUserEmail ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		require_once PATH_FUNCTION . '/fUser.php';

		$aParams['groupKey'] = 'create' . $this->sModuleName;

		// Make sure the user exists and fetch the user e-mail so it's formatted in the same way as in the database
		clFactory::loadClassFile( 'clUser' );

		$oUser	= new clUser();

		// Get user
		$aData = $oUser->oDao->read( array(
			'fields'	=> 'userEmail',
			'userEmail'	=> $sUserEmail,
		) );

		// Abort if user was not found
		if( empty($aData) ) {
			return false;
		}

		// Extract the data
		$aData	= current( $aData );

		// Use this e-mail
		$sUserEmail	= $aData['userEmail'];


		$sActivationKey = md5( uniqid(mt_rand(), true) );
		$aData = array(
			'retrievalActivationKey' =>  $sActivationKey,
			'retrievalPass' => hashUserPass( $sNewPass, $sUserEmail ),
			'retrievalUserId' => (int) $iUserId,
			'retrievalIp' => getUserLongIp(),
			'retrievalCreated' => date( 'Y-m-d H:i:s' )
		);

		if( $this->oDao->createData($aData, $aParams) ) return $sActivationKey;
		return false;
	}

}
