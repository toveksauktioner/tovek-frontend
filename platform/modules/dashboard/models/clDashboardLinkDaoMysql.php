<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clDashboardLinkDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entDashboardLink' => array(
				'linkId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'linkTextSwedish' => array(
					'type' => 'string',
					'title' => _( 'Title in swedish' )
				),
				'linkTextEnglish' => array(
					'type' => 'string',
					'title' => _( 'Title in english' )
				),
				'linkUrl' => array(
					'type' => 'string',
					'title' => _( 'URL' )
				),
				'linkDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'linkType' => array(
					'type' => 'array',
					'title' => _( 'Type' ),
					'values' => array(
						'internal' => _( 'Internal' ),
						'external' => _( 'External' )
					)
				),
				'linkSort' => array(
					'type' => 'integer',
					'title' => _( 'Sort flag' )
				),
				'linkCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),				
				'linkUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);
		
		$this->sPrimaryField = 'linkId';		
		$this->sPrimaryEntity = 'entDashboardLink';
		$this->sSortField = 'linkSort';
		$this->aFieldsDefault = '*';
		
		$this->init();
		
		$this->aDataFilters['output'] = array(
			'linkCreated' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			),
			'linkUpdated' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			)
		);
	}
	
	public function updateSort( $aPrimaryIds ) {
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );
		
		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		
		return $oDaoMysql->updateSortOrder( $aPrimaryIds, $this->sSortField, array(
			'entities' => $this->sPrimaryEntity,
			'primaryField' => $this->sPrimaryField
		) );
	}
	
}