<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clImageAltRouteDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entImageAltRoute' => array(
				'entryId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'entryImageId' => array(
					'type' => 'integer',
					'index' => true
				),
				'entryRouteId' => array(
					'type' => 'integer',
					'index' => true
				),
				'entryImageAlternativeTextTextId' => array(
					'type' => 'string'
				),
				'entryCreated' => array(
					'type' => 'datetime'
				),
				'entryUpdated' => array(
					'type' => 'datetime'
				)
			)
		);
		$this->sPrimaryField = 'entryId';
		$this->sPrimaryEntity = 'entImageAltRoute';
		$this->aFieldsDefault = '*';
		
		$this->init();
		
		$this->aHelpers = array(
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'entryImageAlternativeTextTextId'
				),				
				'sTextEntity' => 'entImageText'				
			) )
		);
	}
	
	public function read( $aParams = array() ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'imageId' => null,
			'routeId' => null
		);
		
		$aDaoParams = array();
		
		/**
		 * Read by image ID
		 */
		if( $aParams['imageId'] !== null ) {
			if( is_array($aParams['imageId']) ) {
				$aCriterias[] = 'entryImageId IN(' . implode( ', ', array_map('intval', $aParams['imageId']) ) . ')';
			} else {
				$aCriterias[] = 'entryImageId = ' . $this->oDb->escapeStr( $aParams['imageId'] );
			}
		}
		
		/**
		 * Read by route ID
		 */
		if( $aParams['routeId'] !== null ) {
			if( is_array($aParams['routeId']) ) {
				$aCriterias[] = 'entryRouteId IN(' . implode( ', ', array_map('intval', $aParams['routeId']) ) . ')';
			} else {
				$aCriterias[] = 'entryRouteId = ' . $this->oDb->escapeStr( $aParams['routeId'] );
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		$aDaoParams += array(
			'fields' => $aParams['fields']
		);
		
		return parent::readData( $aDaoParams );		
	}
	
}