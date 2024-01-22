<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clViewDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entView' => array(
				'viewId' => array(
					'type' => 'id',
					'primary' => true,
					'title' => _( 'ID' )
				),
				'viewType' => array(
					'type' => 'array',
					'values' => array(
						'html' => 'html',
						'xml' => 'xml'
					),
					'index' => true,
					'title' => _( 'Type' )
				),
				'viewModuleKey' => array(
					'type' => 'string',
					'title' => _( 'Module key' ),
					'required' => true
				),
				'viewFile' => array(
					'type' => 'string',
					'title' => _( 'File' ),
					'required' => true
				)
			)
		);
		$this->sPrimaryName = 'viewKey';
		$this->sPrimaryField = 'viewId';
		$this->aFieldsDefault = array(
			'viewModuleKey',
			'viewFile'
		);
		$this->init();
	}

	public function read( $aParams = array() ) {
		$aParams += array(
			'fields' => array(),
			'viewId' => null,
			'viewType' => null,
			'viewModuleKey' => null,
			'viewFile' => null
		);
		$aCriterias = array();
		$aDaoParams = array(
			'fields' => $aParams['fields']
		);

		if( $aParams['viewId'] !== null ) {
			if( is_array($aParams['viewId']) ) {
				$aCriterias[] = 'viewId IN(' . implode( ', ', array_map('intval', $aParams['viewId']) ) . ')';
			} else {
				$aCriterias[] = 'viewId = ' . (int) $aParams['viewId'];
			}
		}
		if( $aParams['viewModuleKey'] !== null ) {
			$aCriterias[] = 'viewModuleKey = ' . $this->oDb->escapeStr($aParams['viewModuleKey']);
		}
		if( $aParams['viewFile'] !== null ) {
			$aCriterias[] = 'viewFile = ' . $this->oDb->escapeStr($aParams['viewFile']);
		}
		if( $aParams['viewType'] !== null ) {
			$aCriterias[] = 'viewType = ' . $this->oDb->escapeStr( $aParams['viewType'] );
		}
		if( !empty($aCriterias) ) {
			$aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		}

		return $this->readData($aDaoParams);
	}

}
