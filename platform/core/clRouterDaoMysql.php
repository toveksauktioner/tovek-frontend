<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clRouterDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entRoute' => array(
				'routeId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'routeLayoutKey' => array(
					'type' => 'string',
					'index' => true,
					'required' => true,
					'title' => _( 'Layout' )
				),
				'routePathLangId' => array(
					'type' => 'integer',
					'required' => true,
					'title' => _( 'Language' )
				),
				'routePath' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Path' )
				),
				'routeCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'routeUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			),
			'entRouteToObject' => array(
				'routeId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true
				),
				'objectId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true
				),
				'objectType' => array(
					'type' => 'string',
					'index' => true,
					'min' => 1,
					'max' => 255,
					'required' => true
				),
			),
			'entRouteHttpStatus' => array(
				'statusId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'statusLayoutKey' => array(
					'type' => 'string',
					'index' => true,
					'title' => _( 'Layout key' )
				),
				'statusLangId' => array(
					'type' => 'integer',
					'required' => true,
					'title' => _( 'Language' )
				),
				'statusDomain' => array(
					'type' => 'string',
					'title' => _( 'Domain' )
				),
				'statusRoutePath' => array(
					'type' => 'string',
					'index' => true,
					'required' => true,
					'title' => _( 'Path' )
				),
				'statusCode' => array(
					'type' => 'array',
					'values' => array (
						100 => _( 'Continue' ),
						101 => _( 'Switching Protocols' ),
						102 => _( 'Processing' ),
						200 => _( 'OK' ),
						201 => _( 'Created' ),
						202 => _( 'Accepted' ),
						203 => _( 'Non-Authoritative Information' ),
						204 => _( 'No Content' ),
						205 => _( 'Reset Content' ),
						206 => _( 'Partial Content' ),
						207 => _( 'Multi-Status' ),
						300 => _( 'Multiple Choices' ),
						301 => _( 'Moved Permanently' ),
						302 => _( 'Found' ),
						303 => _( 'See Other' ),
						304 => _( 'Not Modified' ),
						305 => _( 'Use Proxy' ),
						306 => _( 'Switch Proxy' ),
						307 => _( 'Temporary Redirect' ),
						400 => _( 'Bad Request' ),
						401 => _( 'Unauthorized' ),
						402 => _( 'Payment Required' ),
						403 => _( 'Forbidden' ),
						404 => _( 'Not Found' ),
						405 => _( 'Method Not Allowed' ),
						406 => _( 'Not Acceptable' ),
						407 => _( 'Proxy Authentication Required' ),
						408 => _( 'Request Timeout' ),
						409 => _( 'Conflict' ),
						410 => _( 'Gone' ),
						411 => _( 'Length Required' ),
						412 => _( 'Precondition Failed' ),
						413 => _( 'Request Entity Too Large' ),
						414 => _( 'Request-URI Too Long' ),
						415 => _( 'Unsupported Media Type' ),
						416 => _( 'Requested Range Not Satisfiable' ),
						417 => _( 'Expectation Failed' ),
						418 => _( 'I\'m a teapot' ),
						422 => _( 'Unprocessable Entity' ),
						423 => _( 'Locked' ),
						424 => _( 'Failed Dependency' ),
						425 => _( 'Unordered Collection' ),
						426 => _( 'Upgrade Required' ),
						449 => _( 'Retry With' ),
						450 => _( 'Blocked by Windows Parental Controls' ),
						451 => _( 'Unavailable For Legal Reasons' ),
						500 => _( 'Internal Server Error' ),
						501 => _( 'Not Implemented' ),
						502 => _( 'Bad Gateway' ),
						503 => _( 'Service Unavailable' ),
						504 => _( 'Gateway Timeout' ),
						505 => _( 'HTTP Version Not Supported' ),
						506 => _( 'Variant Also Negotiates' ),
						507 => _( 'Insufficient Storage' ),
						509 => _( 'Bandwidth Limit Exceeded' ),
						510 => _( 'Not Extended' )
					),
					'required' => true,
					'title' => _( 'HTTP Status' )
				),
				/* For instance a location, or more instructions for handling */
				'statusData' => array(
					'type' => 'string',
					'title' => _( 'Data' )
				),
				/* For instance "Location: %s", which then uses statusData as %s */
				'statusAddiditonalHeader' => array(
					'type' => 'string',
					'title' => _( 'Additional header format' )
				),
				'statusContinueRequest' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Continue request' )
				)
			)
		);
		$this->sPrimaryField = 'routeId';
		$this->aFieldsDefault = array(
			'routeId',
			'routeLayoutKey'
		);
		$this->init();
	}

	public function read( $aParams = array() ) {
		$aParams += array(
			'fields' => array(),
			'routeId' => null,
			'routeLayoutKey' => null,
			'langId' => $this->iLangId
		);
		$aCriterias = array();

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entities' => array( 'entRoute' ),
		);

		if( $aParams['routeId'] !== null ) {
			if( is_array($aParams['routeId']) ) {
				$aCriterias[] = "routeId IN (" . implode( ", ", array_map('intval', $aParams['routeId']) ) . ")";
			} else {
				$aCriterias[] = 'routeId = ' . (int) $aParams['routeId'];
			}
		}
		if( $aParams['routeLayoutKey'] !== null ) {
			if( is_array($aParams['routeLayoutKey']) ) {
				$aCriterias[] = "routeLayoutKey IN (" . implode( ", ", array_map(array($this->oDb, 'escapeStr'), $aParams['routeLayoutKey']) ) . ")";
			} else {
				$aCriterias[] = 'routeLayoutKey = ' . $this->oDb->escapeStr( $aParams['routeLayoutKey'] );
			}
		}
		if( $aParams['langId'] !== null ) $aCriterias[] = 'routePathLangId = ' . (int) $aParams['langId'];
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData($aDaoParams);
	}

	public function readPathByLayout( $layoutKey, $iLangId = null ) {
		if( $iLangId === null ) $iLangId = $this->iLangId;

		$aParams = array(
			'fields' => array(
				'routePath'
			),
			'criterias' => 'routePathLangId = ' . (int) $iLangId . ' AND ',
			'sorting' => array( 'routeId' => 'ASC' )
		);

		if( is_array($layoutKey) ) {
			$aParams['criterias'] .= 'routeLayoutKey IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $layoutKey) ) . ')';
			$aParams['fields'][] = 'routeLayoutKey';
		} else {
			$aParams['criterias'] .= 'routeLayoutKey = ' . $this->oDb->escapeStr( $layoutKey );
		}

		$aPaths = $this->readData($aParams);
		if( !empty($aPaths) && !is_array($layoutKey) ) $aPaths = current( current($aPaths) );
		return $aPaths;
	}

	public function readRouteByPath( $sPath, $iLangId = null ) {
		if( $iLangId === null ) $iLangId = $this->iLangId;
		$sPath = $this->oDb->escapeStr( (string) $sPath );

		$aParams = array(
			'fields' => array(
				'routeId',
				'layoutKey',
				'layoutFile',
				'layoutTemplateFile',
				'layoutDynamicChildrenRoute',
				'layoutBodyClass',
				'routePathLangId'				
			),
			'entities' => array(
				'entRoute',
				'entLayout'
			),
			'entitiesExtended' => 'entRoute LEFT JOIN entLayout ON entRoute.routeLayoutKey = entLayout.layoutKey',
			'criterias' => 'routePath = ' . $sPath . ' AND routePathLangId = ' . (int) $iLangId,
			'entries' => 1
		);

		// Find route with correct language ID
		$aData = $this->readData( $aParams );
		if ( !empty($aData) ) return current( $aData );

		// Find route with URI
		$aParams['criterias'] = 'routePath = ' . $sPath;
		$aData = $this->readData( $aParams );
		if ( $aData !== false ) {
			if( !empty($aData) ) {
				$aDataTmp = current( $aData );
				$GLOBALS['langId'] = $aDataTmp['routePathLangId'];
				$oLocale = clRegistry::get( 'clLocale' );
				$GLOBALS['userLang'] = $GLOBALS['Locales'][ $GLOBALS['langId'] ];
				$oLocale->setLocale( $GLOBALS['userLang'] );
			}
			
			return current( $aData );
		}

		// No route found
		return false;
	}

	public function updateByLayout( $sLayoutKey, $aData ) {
		$mExisting = $this->readPathByLayout( $sLayoutKey );
		if( empty($mExisting) || ( is_array($mExisting) && count($mExisting) == 0 ) ) {
			$aData['routeLayoutKey'] = $sLayoutKey;
			$aData['routePathLangId'] = $this->iLangId;
			return $this->createData( $aData );
		} else {
			$aParams = array(
				'criterias' => 'routeLayoutKey = ' . $this->oDb->escapeStr( $sLayoutKey ) . ' AND routePathLangId = ' . (int) $this->iLangId
			);
			return $this->updateData( $aData, $aParams );
		}
	}

	/**
	 *
	 * Route object
	 *
	 */
	
	public function readByObject( $objectId, $sObjectType, $aFields = array(), $iLangId = null ) {
		$aParams = array(
			'entities' => array(
				'entRoute',
				'entRouteToObject'
			),
			'fields' => $aFields,
			'entitiesExtended' => 'entRouteToObject LEFT JOIN entRoute ON entRouteToObject.routeId = entRoute.routeId',
		);
		$aCriterias = array(
			'objectType = ' . $this->oDb->escapeStr( $sObjectType ) . ( $iLangId !== null ? ' AND entRoute.routePathLangId = ' . (int) $iLangId : '' )
		);

		if( is_array($objectId) ) {
			$aCriterias[] = "entRouteToObject.objectId IN (" . implode( ", ", array_map('intval', $objectId) ) . ")";
		} else {
			$aCriterias[] = 'entRouteToObject.objectId = ' . (int) $objectId;
		}
		if( !empty($aCriterias) ) $aParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aParams );
	}
	
	public function readObjectByPath( $routeId ) {
		$aParams = array(
			'fields' => array(
				'objectId',
				'objectType'
			),
			'entities' => 'entRouteToObject'
		);

		if( is_array($routeId) ) {
			$aParams['criterias'] = "routeId IN (" . implode( ", ", array_map('intval', $routeId) ) . ")";
		} else {
			$aParams['criterias'] = 'routeId = ' . (int) $routeId;
		}

		return $this->readData( $aParams );
	}
	
	public function readObjectByType( $mType ) {
		$aParams = array(
			'fields' => array(
				'routeId',
				'objectId',
				'objectType'
			),
			'entities' => 'entRouteToObject'
		);
		
		if( is_array($mType) ) {
			$aParams['criterias'] = "objectType IN (" . implode( ", ", $mType ) . ")";
		} else {
			$aParams['criterias'] = 'objectType = ' . $this->oDb->escapeStr( $mType );
		}
		
		return $this->readData( $aParams );
	}
	
	public function deleteRouteToObjectByObject( $objectId, $sObjectType ) {
		$aParams = array(
			'entities' => 'entRouteToObject'
		);
		$aCriterias = array(
			'objectType' => $this->oDb->escapeStr( $sObjectType )
		);

		if( is_array($objectId) ) {
			$aCriterias[] = "objectId IN (" . implode( ", ", array_map('intval', $objectId) ) . ")";
		} else {
			$aCriterias[] = 'objectId = ' . (int) $objectId;
		}

		if( !empty($aCriterias) ) $aParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->deleteData( $aParams );
	}
	
	public function deleteRouteToObjectByRoute( $routeId ) {
		$aParams = array(
			'entities' => 'entRouteToObject'
		);
		$aCriterias = array();

		if( is_array($routeId) ) {
			$aCriterias[] = "routeId IN (" . implode( ", ", array_map('intval', $routeId) ) . ")";
		} else {
			$aCriterias[] = 'routeId = ' . (int) $routeId;
		}

		if( !empty($aCriterias) ) $aParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->deleteData( $aParams );
	}
	
	/**
	 *
	 * Route http status
	 *
	 */
	
	public function readAllHttpStatus( $aFields, $iLangId = null ) {
		if( $iLangId === null ) $iLangId = $this->iLangId;
		
		$aParams = array(
			'fields' => array(
				'*'				
			),
			'entities' => array(
				'entRouteHttpStatus'
			),			
			'criterias' => 'statusLangId = ' . (int) $iLangId
		);
		
		return $this->readData( $aParams );
	}
	
	public function readHttpStatusByPath( $sPath, $iLangId = null ) {
		if( $iLangId === null ) $iLangId = $this->iLangId;

		$aParams = array(
			'fields' => array(
				'*'				
			),
			'entities' => array(
				'entRouteHttpStatus'
			),			
			'criterias' => 'statusRoutePath = ' . $this->oDb->escapeStr( (string) $sPath ) . ' AND statusLangId = ' . (int) $iLangId,
			'entries' => 1
		);
		
		// Find URI
		$aData = $this->readData( $aParams );
		if ( !empty($aData) ) return current( $aData );
		
		// No route found
		return false;
	}
	
	public function readHttpStatusByLayout( $sLayoutKey, $iLangId = null ) {
		if( $iLangId === null ) $iLangId = $this->iLangId;

		$aParams = array(
			'fields' => array(
				'*'				
			),
			'entities' => array(
				'entRouteHttpStatus'
			),			
			'criterias' => 'statusLayoutKey = ' . $this->oDb->escapeStr( (string) $sLayoutKey ) . ' AND statusLangId = ' . (int) $iLangId			
		);
		
		$aData = $this->readData( $aParams );
		if ( !empty($aData) ) return $aData;
		
		// No route found
		return false;
	}
	
	public function deleteHttpStatus( $mStatusId ) {
		$aParams = array(
			'entities' => 'entRouteHttpStatus'
		);
		
		if( is_array($mStatusId) ) {
			$aCriterias[] = "statusId IN (" . implode( ", ", array_map('intval', $mStatusId) ) . ")";
		} else {
			$aCriterias[] = 'statusId = ' . (int) $mStatusId;
		}
		
		if( !empty($aCriterias) ) $aParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->deleteData( $aParams );
	}
	
}
