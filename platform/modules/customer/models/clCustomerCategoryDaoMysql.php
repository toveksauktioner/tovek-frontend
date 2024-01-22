<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

require_once PATH_HELPER . '/clTreeHelperDaoSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';
require_once PATH_HELPER . '/clRouterHelperDaoSql.php';

require_once PATH_MODULE . '/customer/config/cfCustomer.php';

class clCustomerCategoryDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entCustomerCategory' => array(
				'categoryId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'categoryTitleTextId' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'categoryCanonicalUrlTextId' => array(
					'type' => 'string',
					'title' => _( 'Canonical URL' )
				),
				'categoryDescriptionTextId' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'categoryCustomerBehavior' => array(
					'type' => 'array',
					'title' => _( 'Show customers' ),
					'values' => array(
						'children' => _( 'This, and all it\'s subcategories' ),
						'current' => _( 'Only for this category' ),
						'grouped' => _( 'This, and all it\'s subcategories grouped' )
					)
				),
				'categoryPageTitleTextId' => array(
					'type' => 'string',
					'title' => _( 'Page title' )
				),
				'categoryPageDescriptionTextId' => array(
					'type' => 'string',
					'title' => _( 'Page description' )
				),
				'categoryPageKeywordsTextId' => array(
					'type' => 'string',
					'title' => _( 'Page keywords' )
				),
				'categoryLeft' => array(
					'type' => 'integer'
				),
				'categoryRight' => array(
					'type' => 'integer'
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
		$this->sPrimaryEntity = 'entCustomerCategory';
		$this->aFieldsDefault = '*';
        
        $this->aHelpers = array(
			'oTreeHelperDao' => new clTreeHelperDaoSql( $this, array(
				'categoryEntity' => 'entCustomerCategory'
			) ),
            'oRouterHelper' => new clRouterHelperDaoSql( $this, array(
				'parentEntity' => $this->sPrimaryEntity,
				'parentPrimaryField' => $this->sPrimaryField,
				'parentType' => 'CustomerCategory'
			) ),
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
                    'categoryTitleTextId',
                    'categoryCanonicalUrlTextId',
                    'categoryDescriptionTextId',
                    'categoryPageTitleTextId',
                    'categoryPageDescriptionTextId',
                    'categoryPageKeywordsTextId'
                    
				),
				'sTextEntity' => 'entCustomerText'
			) )
		);
		
		$this->init();
	}
    
}
