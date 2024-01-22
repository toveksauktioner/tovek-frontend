<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/userNotification/config/cfUserNotification.php';

class clUserNotification extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'userNotification';

		$this->oDao = clRegistry::get( 'clUserNotificationDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/userNotification/models' );
		$this->initBase();

        $this->oDao->switchToSecondary();
	}

	public function readByUser( $iUserId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'notificationUserId' => $iUserId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readByStatus( $bRead = null, $bSent = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array();

		if( $bRead !== null ) {
			$aParams = array(
				'notificationRead' => ( $bRead ? 'yes' : 'no' )
			);
		}
		if( $bSent !== null ) {
			$aParams = array(
				'notificationSent' => ( $bSent ? 'yes' : 'no' )
			);
		}

		return $this->oDao->readByForeignKey( $aParams );
	}

	public function searchForDuplicate( $sNotificationTitle, $sNotificationUrl, $iUserId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'notificationTitle' => $sNotificationTitle,
			'notificationUrl' => $sNotificationUrl,
			'notificationUserId' => $iUserId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* *
	 * Notification is not added if defcon level is below max, although this can be overridden based on the type of notification
	 * */
	public function create( $aData, $iOverrideDefconLevel = 5 ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;

		if( empty($aData['notificationType']) ) {
			$aData['notificationType'] = NOTIFICATION_DEFAULT_TYPE;
		}

		//if( DEFCON_LEVEL >= $iOverrideDefconLevel ) {
			$aData += array(
				'notificationCreated' => date( 'Y-m-d H:i:s' ),
				'notificationRead' => 'no',
				'notificationSent' => 'no'
			);

			if( $this->oDao->createData($aData, $aParams) ) {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataSaved' => _( 'The data has been saved' )
				) );

				$iLastId = $this->oDao->oDb->lastId();

				return $iLastId;
			}	
		//}

		return false;
	}

}
