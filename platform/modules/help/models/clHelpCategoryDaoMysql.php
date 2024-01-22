<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clRouterHelperDaoSql.php';
require_once PATH_HELPER . '/clJournalHelperDaoSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clHelpCategoryDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entHelpCategory' => array(
				'helpCategoryId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Id' )
				),
				'helpCategoryIcon' => array(
					'type' => 'string',
					'title' => _( 'Ikon' )
				),
				'helpCategoryTitleTextId' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Name' )
				),
				'helpCategoryDescriptionTextId' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				// Journal
				'helpCategoryStatus' => array(
					'type' => 'array',
					'values' => array(
						'inactive' => _( 'Inactive' ),
						'active' => _( 'Active' )
					),
					'title' => _( 'Status' )
				),
				'helpCategoryPublishStart' => array(
					'type' => 'datetime',
					'title' => _( 'Start' )
				),
				'helpCategoryPublishEnd' => array(
					'type' => 'datetime',
					'title' => _( 'End' )
				),
				// Misc
				'helpCategorySort' => array(
					'type' => 'integer',
					'title' => _( 'Sort' )
				),
				'helpCategoryCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'helpCategoryUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);

		$this->sPrimaryField = 'helpCategoryId';
		$this->sPrimaryEntity = 'entHelpCategory';
		$this->aFieldsDefault = '*';

		$this->init();

		$this->aHelpers = array(
			'oRouterHelper' => new clRouterHelperDaoSql( $this, array(
				'parentEntity' => $this->sPrimaryEntity,
				'parentPrimaryField' => $this->sPrimaryField,
				'parentType' => 'HelpCategory'
			) ),
			'oJournalHelperDao' => new clJournalHelperDaoSql( $this, array(
				'aJournalFields' => array(
					'status' => 'helpCategoryStatus',
					'publishStart' => 'helpCategoryPublishStart',
					'publishEnd' => 'helpCategoryPublishEnd'
				)
			) ),
			'oTextHelperSql' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'helpCategoryTitleTextId',
					'helpCategoryDescriptionTextId'
				),
				'sTextEntity' => 'entHelpText'
			) )
		);

		$this->aDataFilters['output'] = array(
			'helpCategoryPublishStart' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			),
			'helpCategoryPublishEnd' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			)
		);
	}

	public function updateSort( $aPrimaryIds ) {
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );

		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'helpCategorySort', array(
			'entities' => 'entHelpCategory',
			'primaryField' => 'helpCategoryId'
		) );
	}

}
