<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clContinentGroupDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entContinentGroup' => array(
				'entryId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),				
				'entryGroupKey' => array(
					'type' => 'string',
					'title' => _( 'Group' )
				),
				'entryCountryId' => array(
					'type' => 'integer'
				),
				'entryContinentCode' => array(
					'type' => 'string',
					'min' => 2,
					'max' => 2,
					'title' => _( 'Code' )
				),					
				'entryLocalCountryTitleTextId' => array(
					'type' => 'string'
				),
				'entryStatus' => array(
					'type' => 'array',
					'values' => array(
						'active' => _( 'Active' ),
						'inactive' => _( 'Inactive' )
					),
					'title' => _( 'Status' )
				),
				'entrySort' => array(
					'type' => 'integer'
				),
				'entryCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		
		$this->sPrimaryField = 'entryId';
		$this->sPrimaryEntity = 'entContinentGroup';
		$this->aFieldsDefault = '*';
		
		$this->init();
		
		$this->aHelpers = array(
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'entryLocalCountryTitleTextId'
				),				
				'sTextEntity' => 'entContinentGroupText'				
			) )
		);
	}

	public function readGroup( $aFields, $sContinentGroupKey ) {
		$aParams = array(
			'fields' => $aFields,
			'criterias' => 'entryGroupKey = ' . $this->oDb->escapeStr( $sContinentGroupKey )
		);
		return $this->readData( $aParams );
	}
	
	public function readAllGroups( $aFields = array() ) {
		$aParams = array(
			'fields' => $aFields,
			'groupBy' => 'entryGroupKey'
		);
		return $this->readData( $aParams );
	}
	
	public function deleteByGroupAndContinent( $sContinentGroupKey, $sContinentCode ) {
		$aParams = array(
			'criterias' => 'entryGroupKey = ' . $this->oDb->escapeStr( $sContinentGroupKey ) . ' AND entryContinentCode = ' . $this->oDb->escapeStr( $sContinentCode )
		);
		return $this->deleteData( $aParams );
	}

	public function updateSort( $aPrimaryIds, $sContinentGroupKey ) {				
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );
		
		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'entrySort', array(
			'entities' => 'entContinentGroup',
			'primaryField' => 'entryId',
			'criterias' => 'entryGroupKey = ' . $this->oDb->escapeStr( $sContinentGroupKey )
		) );
	}

}