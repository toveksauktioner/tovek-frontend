<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clRouterHelperDaoSql.php';
require_once PATH_HELPER . '/clJournalHelperDaoSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clNewsDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entNews' => array(
				'newsId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'newsTitleTextId' => array(
					'type' => 'string',
					'title' => _( 'Headline' ),
					'required' => true
				),
				'newsSummaryTextId' => array(
					'type' => 'string',
					'title' => _( 'Summary' )
				),
				'newsContentTextId' => array(
					'type' => 'string',
					'title' => _( 'Content' )
				),				
				'newsMetaKeywords' => array(
					'type' => 'string',
					'title' => _( 'Keywords' )
				),
				'newsMetaDescription' => array(
					'type' => 'string',
					'title' => _( 'Descriptions' )
				),
				// JournalHelper
				'newsStatus' => array(
					'type' => 'array',
					'values' => array(
						'inactive' => _('Inactive'),
						'active' => _('Active')
					),
					'title' => _( 'Status' )
				),
				'newsPublishStart' => array(
					'type' => 'datetime',
					'title' => _( 'Start' )
				),
				'newsPublishEnd' => array(
					'type' => 'datetime',
					'title' => _( 'End' )
				),
				// Misc
				'newsCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'newsUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);
		
		$this->sPrimaryField = 'newsId';
		$this->sPrimaryEntity = 'entNews';
		$this->aFieldsDefault = '*';
	
		$this->init();
		
		$this->aHelpers = array(
			'oRouterHelper' => new clRouterHelperDaoSql( $this, array(
				'parentEntity' => $this->sPrimaryEntity,
				'parentPrimaryField' => $this->sPrimaryField,
				'parentType' => 'News'
			) ),
			'oJournalHelperDao' => new clJournalHelperDaoSql( $this, array(
				'aJournalFields' => array(
					'status' => 'newsStatus',
					'publishStart' => 'newsPublishStart',
					'publishEnd' => 'newsPublishEnd'
				)
			) ),
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'newsTitleTextId',
					'newsSummaryTextId',
					'newsContentTextId',
					'newsMetaKeywords',
					'newsMetaDescription'
				),				
				'sTextEntity' => 'entNewsText'				
			) )
		);
		
		$this->aDataFilters['output'] = array(
			'newsPublishStart' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			),
			'newsPublishEnd' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			)
		);
	}	
}
