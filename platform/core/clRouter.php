<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_FUNCTION . '/fRoute.php';

class clRouter extends clModuleBase {

	public $oDao;
	
	public $sPath;
	public $sRefererRoute;
	
	public $iCurrentRouteId;
	public $sCurrentLayoutKey = false;
	public $sCurrentTemplateFile = false;
	public $sCurrentLayoutFile = false;
	public $sCurrentLayoutBodyClass = false;
	
	// Scroll to point after page load
	public $sScrollTo;
	
	public function __construct( $sPath = null ) {
		$this->sModulePrefix = 'route';
		$this->sModuleName = 'Route';

		$this->oDao = clRegistry::get( 'clRouterDao' . DAO_TYPE_DEFAULT_ENGINE );
		$this->setPath( $sPath );

		$this->initBase();
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aData['routePathLangId'] = $this->oDao->iLangId;
		return parent::create( $aData );
	}
	
	public function deleteByLayoutKey( $sLayoutKey ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'criterias' => 'routeLayoutKey = ' . $this->oDao->oDb->escapeStr( $sLayoutKey )
		);
		return $this->oDao->deleteData( $aParams );
	}
	
	public function readByLayout( $sLayoutKey, $aFields = array(), $iLangId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'routeLayoutKey' => $sLayoutKey
		);
		if( $iLangId !== null ) $aParams['langId'] = $iLangId;
		return $this->oDao->read( $aParams );
	}

	public function getPath( $sLayoutKey ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readPathByLayout( $sLayoutKey );
	}
	
	public function route() {
		$this->checkHttpStatus();
		
		if( SITE_RELEASE_MODE === false && mb_substr($this->sPath, 0, 6) != '/admin' ) {
			// Force redirect for installers upon existing
			if( is_dir(PATH_PUBLIC . '/templateInstall') ) $this->redirect( '/templateInstall' );
			if( is_dir(PATH_PUBLIC . '/moduleInstall') ) $this->redirect( '/moduleInstall' );
		}
		
		$aLayout = $this->oDao->readRouteByPath( $this->sPath );
		
		/**
		 * Check for dynamic route children layout
		 */
		if( empty($aLayout) ) {			
			$sSubPath = substr( $this->sPath, 0, strpos( $this->sPath, '/', strpos($this->sPath, '/') + 1 ) );
			$aSubLayout = $this->oDao->readRouteByPath( $sSubPath );
			if( !empty($aSubLayout) && $aSubLayout['layoutDynamicChildrenRoute'] == 'yes' ) {
				$aLayout = $aSubLayout;
			}
		}
		
		$this->iCurrentRouteId = $aLayout['routeId'];
		$this->setCurrentLayout( $aLayout );
		
		if( !empty($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_HOST']) ) {
			$aRefUrl = parse_url( $_SERVER['HTTP_REFERER'] );
			$this->sRefererRoute = $aRefUrl['host'] == $_SERVER['HTTP_HOST'] ? $aRefUrl['path'] : null;
		}
		
		$oLayout = clRegistry::get( 'clLayoutHtml' );
		$oLayout->sLayoutKey = $aLayout['layoutKey'];
		$oLayout->sLayoutFile = $aLayout['layoutFile'];

		$oTemplate = clRegistry::get( 'clTemplateHtml' );
		$oTemplate->setTemplate( $aLayout['layoutTemplateFile'] );		
	}

	public function redirect( $sPath, $iDelay = null ) {
		if( !empty($_GET['ajax']) ) {
			if( $iDelay === null ) {
				echo '<script>window.top.location = "' . ( !empty($_SERVER["HTTP_CDN_HOST"]) ? 'https://' . $_SERVER["HTTP_CDN_HOST"] : '' ) . $sPath . '";</script>';
				exit;
			} else {
				echo '<script>setTimeout(\'window.top.location = "' . ( !empty($_SERVER["HTTP_CDN_HOST"]) ? 'https://' . $_SERVER["HTTP_CDN_HOST"] : '' ) . $sPath . '";\',' . $iDelay . ');</script>';
				exit;
			}			
		} else {
			if( $iDelay === null ) {
				header( 'Location: ' . ( !empty($_SERVER["HTTP_CDN_HOST"]) ? 'https://' . $_SERVER["HTTP_CDN_HOST"] : '' ) . $sPath );
				exit;
			} else {
				header( 'Refresh: ' . $iDelay . '; url=' . ( !empty($_SERVER["HTTP_CDN_HOST"]) ? 'https://' . $_SERVER["HTTP_CDN_HOST"] : '' ) . $sPath );
				exit;
			}
		}
	}
	
	public function setPath( $sPath = null ) {
		if( $sPath === null ) {
			$this->sPath = getRoutePath();
		} else {
			$this->sPath = $sPath;
		}
		return $this;
	}
	
	public function setCurrentLayout( $aLayout ) {
		$this->sCurrentLayoutKey = $aLayout['layoutKey'];
		$this->sCurrentTemplateFile = $aLayout['layoutTemplateFile'];
		$this->sCurrentLayoutFile = $aLayout['layoutFile'];
		$this->sCurrentLayoutBodyClass = $aLayout['layoutBodyClass'];
	}
	
	public function updateByLayout( $sLayoutKey, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aData[$this->sModulePrefix . 'Updated'] = date( 'Y-m-d H:i:s' );
		return $this->oDao->updateByLayout( $sLayoutKey, $aData );
	}
	
	public function setScrollTo( $sId ) {
		$this->sScrollTo = $sId;
	}
	
	public function getScrollTo() {
		return !empty($this->sScrollTo) ? $this->sScrollTo : false;
	}
	
	/**
	 *
	 * Route object
	 *
	 */
	
	public function createRouteToObject( $iObjectId, $sObjectType, $sPath, $sLayoutKey ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aData = array(
			'routeLayoutKey' => $sLayoutKey,
			'routePathLangId' => $this->oDao->iLangId,
			'routePath' => $sPath
		);
		$iRouteId = $this->create( $aData );

		if( $iRouteId === false ) return false;

		$aParams = array(
			'entities' => 'entRouteToObject'
		);
		$aData = array(
			'objectId' => (int) $iObjectId,
			'objectType' => $sObjectType,
			'routeId' => (int) $iRouteId
		);
		return $this->oDao->createData( $aData, $aParams );
	}
	
	public function readByObject( $objectId, $sObjectType, $aFields = array(), $iLangId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readByObject( $objectId, $sObjectType, $aFields, $iLangId );
	}

	public function readObjectByRoute( $iRouteId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$iRouteId = $iRouteId != null ? $iRouteId : $this->iCurrentRouteId;	
		return $this->oDao->readObjectByPath( $iRouteId );
	}
	
	public function updateRouteToObject( $iObjectId, $sObjectType, $sPath, $sLayoutKey ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$iRouteId = $this->readByObject( $iObjectId, $sObjectType, array(
			'entRouteToObject.routeId'
		), $this->oDao->iLangId );

		if( !empty($iRouteId) ) {
			$iRouteId = current( $iRouteId );
			
			return $this->oDao->updateDataByPrimary( $iRouteId, array(
				'routePath' => $sPath,
				'routeLayoutKey' => $sLayoutKey
			) );
		}

		return false;
	}
	
	public function deleteRouteToObjectByRoute( $iRouteId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteRouteToObjectByRoute( $iRouteId );
	}

	public function deleteRouteToObjectByObject( $objectId, $sObjectType ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteRouteToObjectByObject( $objectId, $sObjectType );
	}
	
	/**
	 *
	 * Route http status
	 *
	 */
	
	/**
	 * Read special route HTTP status handling
	 */
	public function checkHttpStatus() {	
		$sURI = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
		
		// Data
		$aStatus = $this->oDao->readHttpStatusByPath( $sURI );
		
		if( $aStatus !== false ) {
			switch( $aStatus['statusCode'] ) {
				// 1xx Informational
				
				// 2xx Success
				case '200': // Use layoutKey and display the normal page
					// TODO
					break;				
				
				// 3xx Redirection
				case '301':					
					header( 'HTTP/1.1 301 Moved Permanently' );
					if( !empty($aStatus['statusAddiditonalHeader']) && !empty($aStatus['statusData']) ) {
						header( sprintf($aStatus['statusAddiditonalHeader'], $aStatus['statusData']) );
					} elseif( !empty($aStatus['statusData']) ) {
						header( "Location: " . $aStatus['statusData'] );
					}
					echo '301 Moved Permanently a href="#"></a>';
					exit;
					break;
				
				// 4xx Client Error
				case '404':
					header( 'HTTP/1.1 404 Not Found' );
					break;
				
				// 5xx Server Error
				
				default:
					// Unhandled status code
					break;
				
			}
			
			if( $aStatus['statusContinueRequest'] == 'no' ) return;
		}
		
		return;
	}
	
	public function createHttpStatus( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'entities' => 'entRouteHttpStatus'
		);
		return $this->oDao->createData( $aData, $aParams );
	}
	
	public function readAllHttpStatus( $aFields = array(), $iLangId = null ) {		
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readAllHttpStatus( $aFields, $iLangId );
	}
	
	public function readHttpStatusByPath( $sPath, $iLangId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readHttpStatusByPath( $sPath, $iLangId );
	}
	
	public function readHttpStatusByLayout( $sLayoutKey, $iLangId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readHttpStatusByLayout( $sLayoutKey, $iLangId );
	}
	
	public function updateHttpStatus( $iStatusId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'entities' => 'entRouteHttpStatus',
			'criterias' => 'statusId = ' . $this->oDao->oDb->escapeStr( $iStatusId )
		);
		return $this->oDao->updateData( $aData, $aParams );
	}
	
	public function deleteHttpStatus( $mStatusId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteHttpStatus( $mStatusId );
	}
	
}