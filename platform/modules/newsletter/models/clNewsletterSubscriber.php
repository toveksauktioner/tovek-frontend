<?php

require_once PATH_CORE . '/clModuleBase.php';

class clNewsletterSubscriber extends clModuleBase {

	// Additional objects
	public $oGroupDao;

	public function __construct() {
		$this->sModuleName = 'NewsletterSubscriber';
		$this->sModulePrefix = 'newsletterSubscriber';
		
		$this->oDao = clRegistry::get( 'clNewsletterSubscriberDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/newsletter/models' );
		$this->oGroupDao = clRegistry::get( 'clNewsletterGroupDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/newsletter/models' );

		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		// Check if subscriber exists
		$aSubscriber = $this->readByEmail( $aData['subscriberEmail'], 'subscriberId' );
		if( empty($aSubscriber) ) {
			$aParams['groupKey'] = 'create' . $this->sModuleName;
			
			$aData += array(
				'subscriberCreated' => date( 'Y-m-d H:i:s' ),
				'subscriberUnsubscribe' => 'no'
			);
			$aGroups = array();
			if( !empty($aData['subscriberGroup']) ) {
				foreach( (array) $aData['subscriberGroup'] as $iGroupId ) {
					$aGroups[] = $iGroupId;
				}
			}
			
			if( $this->oDao->createData($aData, $aParams) ) {
				$iPrimaryId = $this->oDao->oDb->lastId();
				// Groups			
				if( !empty($aGroups) ) {
					$this->createSubscriberToGroup( $iPrimaryId, $aGroups );
				}
				return $iPrimaryId;
			}
		}
		return false;
	}

	public function readByEmail( $sEmail, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'criterias' => 'subscriberEmail = ' . $this->oDao->oDb->escapeStr( $sEmail )
		);
		return $this->oDao->readData( $aParams );
	}
	
	public function update( $primaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'update' . $this->sModuleName;
		$aGroups =& $aData['subscriberGroup'];
		
		$result = $this->oDao->updateDataByPrimary( $primaryId, $aData, $aParams );
		// Groups
		if( $result !== false ) {			
			$iPrimaryId = $this->oDao->oDb->lastId();
			// Delete
			$this->deleteSubscriberToGroup( $primaryId );
			// Create
			$this->createSubscriberToGroup( $primaryId, $aGroups );
		}
		return $result;
	}

	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$result = $this->oDao->deleteDataByPrimary( $primaryId );
		if( !empty($result) ) {
			// Group
			$this->deleteSubscriberToGroup( $primaryId );
		}
		return $result;
	}

	// Subscriber to group
	public function createSubscriberToGroup( $iPrimaryId, $aGroups = array() ) {
		if( empty($aGroups) ) return false;
		$this->oDao->sPrimaryEntity = 'entNewsletterSubscriberToGroup';
		$aData = array();
		foreach( $aGroups as $iGroupId ) {
			$aData[] = array(
				'subscriberId' => $iPrimaryId,
				'groupId' => $iGroupId
			);
		}
		$aParams['fields'] = array(
			'subscriberId',
			'groupId'
		);
		$this->oDao->createMultipleData($aData, $aParams);
		$this->oDao->sPrimaryEntity = 'entNewsletterSubscriber';
		return true;
	}
	
	public function readSubscriberToGroup( $iSubscriberId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$this->oDao->sPrimaryEntity = 'entNewsletterSubscriberToGroup';
		if( !empty($iSubscriberId) ) {
			$aParams = array(
				'criterias' => 'subscriberId = ' . (int) $iSubscriberId
			);
		}
		$aData = $this->oDao->readData( $aParams );
		$this->oDao->sPrimaryEntity = 'entNewsletterSubscriber';
		return $aData;
	}
	
	public function deleteSubscriberToGroup( $iSubscriberId ) {
		$this->oDao->sPrimaryEntity = 'entNewsletterSubscriberToGroup';
		$this->oDao->deleteData( array(
			'criterias' => 'subscriberId = ' . (int) $iSubscriberId
		) );
		$this->oDao->sPrimaryEntity = 'entNewsletterSubscriber';
	}

	// Group functions
	public function createGroup( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		
		if( $this->oGroupDao->createData($aData, $aParams) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
			return $this->oGroupDao->oDb->lastId();
		}
		return false;
	}

	public function readGroup( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		if( $primaryId !== null ) return $this->oGroupDao->readDataByPrimary($primaryId, $aParams);
		return $this->oGroupDao->readData( $aParams );
	}

	public function readSubscribersByGroup( $iGroupId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => 'subscriberEmail',
			'entitiesExtended' => '
				entNewsletterSubscriber
				LEFT JOIN entNewsletterSubscriberToGroup
				ON entNewsletterSubscriber.subscriberId = entNewsletterSubscriberToGroup.subscriberId',
			'groupBy' => 'subscriberEmail'
		);	
		if( $iGroupId != '*' ) {
			$aParams['criterias'] = 'entNewsletterSubscriberToGroup.groupId = ' . (int) $iGroupId;
		}
		return $this->oDao->readData( $aParams );
	}

	public function updateGroup( $primaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$result = $this->oGroupDao->updateDataByPrimary( $primaryId, $aData );
		if( $result !== false ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
		}
		return $result;
	}

	public function deleteGroup( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$result = $this->oGroupDao->deleteDataByPrimary( $primaryId );
		if( !empty($result) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'The data has been deleted' )
			) );
		}
		return $result;
	}

	// Import
	public function importSubscribers() {
		require_once PATH_FUNCTION . '/fData.php';
		$sImportType = current( func_get_args() );	
		// Platform users		
		if( $sImportType == 'users' ) {		
			// Users
			$oUserManager = clRegistry::get( 'clUserManager' );
			$aUsers = $oUserManager->oDao->read( array(
				'fields' => array(
					'userId',
					'infoName',
					'userEmail'
				)
			) );
			foreach( $aUsers as $key => $aUser ) {
				$aUserGroups = $oUserManager->oDao->readUserGroup( $aUser['userId'] );			
				$aUserGroups = arrayToSingle($aUserGroups, 'groupKey', 'groupTitle');
				// Remove superUsers from this import
				if( array_key_exists('super', $aUserGroups) ) {
					unset($aUsers[$key]);
				}			
			}
			
			// Subscribers
			$aSubscribers = $this->read( array('subscriberEmail','subscriberName') );			
			$aSubscribers = arrayToSingle($aSubscribers, 'subscriberEmail', 'subscriberName');
			
			// Create
			$aEmails = array(); # For dublicate entries
			foreach( $aUsers as $user ) {				
				if( !array_key_exists($user['userEmail'], $aSubscribers) && !in_array($user['userEmail'], $aEmails) ) {
					$this->create( array(
						'subscriberName' => $user['infoName'],
						'subscriberEmail' => $user['userEmail'],
						'subscriberStatus' => 'active'
					) );
					$aEmails[] = $user['userEmail'];
				}
			}		
			return true;
		}
	}

}