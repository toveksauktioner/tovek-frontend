<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clContactDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entContact' => array(
				'contactId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Contact form ID' )
				),
				'contactButtonTextId' => array(
					'type' => 'string',
					'title' => _( 'Submit button' ),
					'required' => true,
				),
				'contactSubmitMessageTextId' => array(
					'type' => 'string',
					'title' => _( 'Submit message' ),
					'required' => true,
				)
			)
		);

		$this->sPrimaryField = 'contactId';
		$this->sPrimaryEntity = 'entContact';
		$this->aFieldsDefault = '*';

		$this->init();

		$this->aHelpers = array(
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'contactButtonTextId',
					'contactSubmitMessageTextId',
				),
				'sTextEntity' => 'entContactText'
			) )
		);
	}
}
