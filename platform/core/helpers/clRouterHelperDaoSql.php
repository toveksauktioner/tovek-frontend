<?php

class clRouterHelperDaoSql {

	private $aParams = array();
	private $oDao;

	public function __construct( clDaoBaseSql $oDao, $aParams ) {
		$this->oDao = $oDao;

		$aParams += array(
			'parentEntity'			=> $this->oDao->sPrimaryEntity,
			'parentPrimaryField'	=> $this->oDao->sPrimaryField,
			'parentType'			=> null
		);
		$this->aParams = $aParams;
	}

	public function readData( $aParams ) {
		if( $this->aParams['parentType'] === null ) return $aParams;
		
		$aParams += array(
			'entities'	=> $this->oDao->sPrimaryEntity,
			'fields'	=> $this->oDao->aFieldsDefault
		);
		
		$aParams['entities']	= (array) $aParams['entities'];
		$aParams['fields']		= (array) $aParams['fields'];
		
		if(
			in_array($this->aParams['parentEntity'], $aParams['entities']) &&
			(in_array('routePath', $aParams['fields']) || in_array('*', $aParams['fields']))
		) {
			$aParams['entities'][] = 'entRouteToObject';
			$aParams['entities'][] = 'entRoute';
			
			if( empty($aParams['entitiesExtended']) ) {
				$aParams['entitiesExtended'] = $this->aParams['parentEntity'];
			}
			
			$aParams['entitiesExtended'] .= '
				LEFT JOIN entRouteToObject ON ' . $this->aParams['parentEntity'] . '.' . $this->aParams['parentPrimaryField'] . ' = entRouteToObject.objectId
				AND entRouteToObject.objectType = ' . $this->oDao->oDb->escapeStr( $this->aParams['parentType'] ) . ' LEFT JOIN entRoute ON entRouteToObject.routeId = entRoute.routeId
			';
			
			if( !empty($aParams['criterias']) ) {
				$aParams['criterias'] .= ' AND entRoute.routePathLangId = "' . $this->oDao->iLangId . '"';
			} else {
				$aParams['criterias'] = 'entRoute.routePathLangId = "' . $this->oDao->iLangId . '"';
			}
		}
		return $aParams;
	}
	
}