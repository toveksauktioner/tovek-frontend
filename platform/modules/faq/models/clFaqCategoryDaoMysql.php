<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clRouterHelperDaoSql.php';
require_once PATH_HELPER . '/clJournalHelperDaoSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clFaqCategoryDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entFaqCategory' => array(
				'categoryId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Id' )
				),
				'categoryTitleTextId' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Name' )
				),
				'categoryDescriptionTextId' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Description' )
				),
				// Journal
				'categoryStatus' => array(
					'type' => 'array',
					'values' => array(
						'inactive' => _( 'Inactive' ),
						'active' => _( 'Active' )
					),
					'title' => _( 'Status' )
				),
				'categoryPublishStart' => array(
					'type' => 'datetime',
					'title' => _( 'Start' )
				),
				'categoryPublishEnd' => array(
					'type' => 'datetime',
					'title' => _( 'End' )
				),
				// Misc
				'categorySort' => array(
					'type' => 'integer',
					'title' => _( 'Sort' )
				),
				'categoryCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'categoryUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);

		$this->sPrimaryField = 'categoryId';
		$this->sPrimaryEntity = 'entFaqCategory';
		$this->aFieldsDefault = '*';

		$this->init();

		$this->aHelpers = array(
			'oRouterHelper' => new clRouterHelperDaoSql( $this, array(
				'parentEntity' => $this->sPrimaryEntity,
				'parentPrimaryField' => $this->sPrimaryField,
				'parentType' => 'FaqCategory'
			) ),
			'oJournalHelperDao' => new clJournalHelperDaoSql( $this, array(
				'aJournalFields' => array(
					'status' => 'categoryStatus',
					'publishStart' => 'categoryPublishStart',
					'publishEnd' => 'categoryPublishEnd'
				)
			) ),
			'oTextHelperSql' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'categoryTitleTextId',
					'categoryDescriptionTextId'
				),
				'sTextEntity' => 'entFaqText'
			) )
		);
		
		$this->aDataFilters['output'] = array(
			'categoryPublishStart' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			),
			'categoryPublishEnd' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			)
		);
	}

	public function updateSort( $aPrimaryIds ) {
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );
		
		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'categorySort', array(
			'entities' => 'entFaqCategory',
			'primaryField' => 'categoryId'
		) );
	}

}
