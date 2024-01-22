<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clRouterHelperDaoSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clFaqQuestionDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entFaqQuestion' => array(
				'questionId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'questionCategoryId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true,
					'title' => _( 'Category' )
				),				
				'questionTitleTextId' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Question' )
				),
				'questionAnswerTextId' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Answer' )
				),				
				// Misc
				'questionStatus' => array(
					'type' => 'array',
					'values' => array(
						'active' => _( 'Active' ),
						'inactive' => _( 'Inactive' )
					),
					'title' => _( 'Status' )
				),
				'questionSort' => array(
					'type' => 'integer',
					'title' => _( 'Sort' )
				),
				'questionCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'questionUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);

		$this->sPrimaryField = 'questionId';
		$this->sPrimaryEntity = 'entFaqQuestion';
		$this->aFieldsDefault = '*';

		$this->init();

		$this->aHelpers = array(
			'oRouterHelper' => new clRouterHelperDaoSql( $this, array(
				'parentEntity' => $this->sPrimaryEntity,
				'parentPrimaryField' => $this->sPrimaryField,
				'parentType' => 'FaqQuestion'
			) ),
			'oTextHelperSql' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'questionTitleTextId',
					'questionAnswerTextId'
				),
				'sTextEntity' => 'entFaqText',
			) )
		);
	}

	public function read( $aParams = array() ) {
        $aParams += array(
			'fields' => $this->aFieldsDefault,
            'questionId' => null,
			'categoryId' => null,
			'status' => null
		);
		
		$aDaoParams = array(
			'fields' => $aParams['fields']
		);
		
		$aCriterias = array();
		
		if( $aParams['questionId'] !== null ) {
			if( is_array($aParams['questionId']) ) {
				$aCriterias[] = 'questionId IN(' . implode( ', ', array_map('intval', $aParams['questionId']) ) . ')';
			} else {
				$aCriterias[] = 'questionId = ' . (int) $aParams['questionId'];
			}
		}
		
		if( $aParams['categoryId'] !== null ) {
			if( is_array($aParams['categoryId']) ) {
				$aCriterias[] = 'questionCategoryId IN(' . implode( ', ', array_map('intval', $aParams['categoryId']) ) . ')';
			} else {
				$aCriterias[] = 'questionCategoryId = ' . (int) $aParams['categoryId'];
			}
		}
		
		if( $aParams['status'] !== null && $aParams['status'] !== '*' ) {
			$aCriterias[] = 'questionStatus = ' . $this->oDb->escapeStr( $aParams['status'] );
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return parent::readData( $aDaoParams );
    }
	
	public function updateSort( $aPrimaryIds, $iCategoryId ) {
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );

		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'questionSort', array(
			'entities' => 'entFaqQuestion',
			'primaryField' => 'questionId',
			'criterais' => 'questionCategoryId = ' . (int) $iCategoryId
		) );
	}

}
