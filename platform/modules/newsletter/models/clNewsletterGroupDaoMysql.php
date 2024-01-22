<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clNewsletterGroupDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entNewsletterGroup' => array(
				'groupId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'groupNameTextId' => array(
					'type' => 'string',
					'title' => _( 'Name' ),
					'required' => true
				),
				'groupDescriptionTextId' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'groupCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' ),
					'required' => true
				)
			)
		);
		
		$this->sPrimaryField = 'groupId';
		$this->sPrimaryEntity = 'entNewsletterGroup';
		$this->aFieldsDefault = '*';
	
		$this->init();
		
		$this->aHelpers = array(
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'groupNameTextId',
					'groupDescriptionTextId'
				),				
				'sTextEntity' => 'entNewsletterText'				
			) )
		);
	}
	
}
