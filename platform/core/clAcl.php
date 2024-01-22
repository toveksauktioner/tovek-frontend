<?php

class clAcl {

	public $aAcl = array();
	public $aroId;
	public $oDao;

	private $sAroType;
	private $sModuleName;
	private $sModulePrefix;

	public function __construct( $aroId = null, $sAroType = null ) {
		$this->sModulePrefix = 'acl';
		$this->sModuleName = 'Acl';
		$this->oDao = clRegistry::get( 'clAclDao' . DAO_TYPE_DEFAULT_ENGINE );
		if( !empty($aroId) && !empty($sAroType) ) {
			$this->setAro( $aroId, $sAroType );
			$this->readToAclByAro();
		}
	}

	public function create( $aData ) {
		$this->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;

		if( $this->oDao->createData($aData, $aParams) ) return $this->oDao->oDb->lastId();
		return false;
	}

	public function createByAco( $sAcoKey, $sAclType, $aAroIds, $sAroType ) {
		$aData = array();
		foreach( (array) $aAroIds as $sAroId ) {
			$aData[] = array(
				'aclType' => $sAclType,
				'aclAroId' => $sAroId,
				'aclAroType' => $sAroType,
				'aclAcoKey' => $sAcoKey,
				'aclAccess' => 'allow'
			);
		}

		return $this->oDao->createMultipleData( $aData, array(
			'fields' => array(
				'aclType',
				'aclAroId',
				'aclAroType',
				'aclAcoKey',
				'aclAccess'
			)
		) );
	}

	public function delete( $iPrimaryId ) {
		$this->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteDataByPrimary( $iPrimaryId );
	}

	public static function deleteByLayout( $sLayoutKey ) {
		if( empty($sLayoutKey) ) return false;
		$oDao = clRegistry::get( 'clAclDao' . DAO_TYPE_DEFAULT_ENGINE );
		$aParams = array(
			'aclType' => 'layout',
			'acoKey' => $sLayoutKey
		);
		$oDao->delete( $aParams );
	}

	public static function deleteByView( $iViewId ) {
		if( empty($iViewId) ) return false;
		$oDao = clRegistry::get( 'clAclDao' . DAO_TYPE_DEFAULT_ENGINE );
		$aParams = array(
			'aclType' => 'view',
			'acoKey' => $iViewId
		);
		$oDao->delete( $aParams );
	}

	/**
	 *
	 * @param object $sAcoKey
	 */
	public function hasAccess( $sAcoKey, $sAclType = 'dao' ) {
		if(
			substr($sAcoKey, 0, 5) == 'write' &&
			USER_ADMIN_READ_ONLY === true &&
			!array_key_exists('superuser', $_SESSION['user']['aclGroups'])
		) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->setSessionNotifications( array(
				'dataWarning' => _( 'No access: read only' )
			) );
			
			$oRouter = clRegistry::get( 'clRouter' );
			$oRouter->redirect( $oRouter->sPath . (!empty(stripGetStr()) ? '?' . stripGetStr() : '') );
		}
		
		if( !$this->isAllowed($sAcoKey, $sAclType)  )	{
			if( $sAclType == 'layout' ) {
				$oRouter = clRegistry::get( 'clRouter' );
				$_SESSION['returnto'] = array(
					'acoKey' => $sAcoKey,
					'sentTwice' => ( !empty($_SESSION['returnto']) ? true : false )
				);
				
				if( mb_substr($oRouter->sPath, 0, 6 ) == '/admin' && !empty($_SESSION['adminType']) ) {
					$oRouter->redirect( $oRouter->getPath('userLogin') );
					
				} elseif( mb_substr($oRouter->sPath, 0, 6 ) == '/admin' ) {
					$oRouter->redirect( '/' );
					
				} else {
					$oRouter->redirect( $oRouter->getPath('userLogin') );
					
				}
			}
			
			throw new Exception( 'noAccess - ' . $sAcoKey );
		}
	}

	public function isAllowed( $sAcoKey, $sAclType = 'dao' ) {
		if( empty($this->aAcl) ) return false;
		return ( (isset($this->aAcl['superuser'], $_SESSION['user'], $_SESSION['user']['groupKey']) && $this->aAcl['superuser'] == 'allow' && $_SESSION['user']['groupKey'] == 'super') || (isset($this->aAcl[$sAcoKey]) && $this->aAcl[$sAcoKey] == 'allow') );
	}

	public function read( $aFields = array(), $iPrimaryId = null ) {
		$this->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		if( $iPrimaryId !== null ) return current( $this->oDao->readDataByPrimary($iPrimaryId, $aParams) );
		return $this->oDao->readData( $aParams );
	}

	public function readByAco( $sAcoKey, $aFields = array(), $sAclType = 'dao', $sAroType = null ) {
		$aParams = array(
			'fields' => $aFields,
			'acoKey' => $sAcoKey,
			'aclType' => $sAclType,
			'aroType' => $sAroType
		);
		return $this->oDao->read( $aParams );
	}

	public function readToAclByAro( $sAclType = 'dao', $acoKey = null ) {
		if( empty($this->aroId) ) return false;
		$aParams = array(
			'aroId' => $this->aroId,
			'aclType' => $sAclType,
			'acoKey' => $acoKey
		);
		$aResult = $this->oDao->read( $aParams );
		foreach( $aResult as $row ) {
			$this->aAcl[$row['aclAcoKey']] = $row['aclAccess'];
		}
		$this->aroId = null;
	}

	public function setAcl( $aAcl ) {
		$this->aAcl = $aAcl;
	}

	public function setAro( $aroId, $sAroType ) {
		if( !empty($aroId) ) $this->aroId[$sAroType] = $aroId;
	}

	public function update( $iPrimaryId, $aData ) {
		$this->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'update' . $this->sModuleName;
		return $this->oDao->updateDataByPrimary( $iPrimaryId, $aData, $aParams );
	}

	public function updateByAco( $sAcoKey, $sAclType, $aAroIds, $sAroType ) {
		$aData = array();
		$aParams = array(
			'acoKey' => $sAcoKey,
			'aclType' => $sAclType
		);
		$this->oDao->delete( $aParams );

		return $this->createByAco( $sAcoKey, $sAclType, $aAroIds, $sAroType );
	}

}
