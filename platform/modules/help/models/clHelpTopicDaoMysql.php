<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clRouterHelperDaoSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clHelpTopicDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entHelpTopic' => array(
				'helpTopicId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'helpTopicTitleTextId' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Topic' )
				),
				'helpTopicDescriptionTextId' => array(
					'type' => 'string',
					'title' => _( 'Answer' )
				),
				// Misc
				'helpTopicStatus' => array(
					'type' => 'array',
					'values' => array(
						'active' => _( 'Active' ),
						'inactive' => _( 'Inactive' )
					),
					'title' => _( 'Status' )
				),
				'helpTopicPublishStart' => array(
					'type' => 'datetime',
					'title' => _( 'Start' )
				),
				'helpTopicPublishEnd' => array(
					'type' => 'datetime',
					'title' => _( 'End' )
				),
				'helpTopicCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'helpTopicUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			),
			'entHelpTopicToCategory' => array(
				'helpTopicId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true
				),
				'helpCategoryId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true
				)
			),
			'entHelpTopicRelation' => array(
				'helpTopicId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true
				),
				'helpTopicRelationId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true
				)
			)
		);

		$this->sPrimaryField = 'helpTopicId';
		$this->sPrimaryEntity = 'entHelpTopic';
		$this->aFieldsDefault = '*';

		$this->init();

		$this->aHelpers = array(
			'oRouterHelper' => new clRouterHelperDaoSql( $this, array(
				'parentEntity' => $this->sPrimaryEntity,
				'parentPrimaryField' => $this->sPrimaryField,
				'parentType' => 'HelpTopic'
			) ),
			'oTextHelperSql' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'helpTopicTitleTextId',
					'helpTopicDescriptionTextId'
				),
				'sTextEntity' => 'entHelpText',
			) )
		);
	}

	public function read( $aParams = array() ) {
    $aParams += array(
			'fields' => $this->aFieldsDefault,
      'topicId' => null,
			'categoryId' => null,
			'withCategory' => null,
			'status' => null
		);

		$aDaoParams = array(
			'fields' => $aParams['fields']
		);

		$aCriterias = array();
		$aEntitiesExtend = array();

		if( $aParams['topicId'] !== null ) {
			if( is_array($aParams['topicId']) ) {
				$aCriterias[] = 'entHelpTopic.helpTopicId IN(' . implode( ', ', array_map('intval', $aParams['topicId']) ) . ')';
			} else {
				$aCriterias[] = 'entHelpTopic.helpTopicId = ' . (int) $aParams['topicId'];
			}
		}

		if( $aParams['categoryId'] !== null ) {
			$aParams['withCategory'] = true;

			if( is_array($aParams['categoryId']) ) {
				$aCriterias[] = 'entHelpTopicToCategory.helpCategoryId IN(' . implode( ', ', array_map('intval', $aParams['categoryId']) ) . ')';
			} else {
				$aCriterias[] = 'entHelpTopicToCategory.helpCategoryId = ' . (int) $aParams['categoryId'];
			}
		}

		if( !empty($aParams['withCategory']) ) {
			$aEntitiesExtend[] = 'LEFT JOIN entHelpTopicToCategory ON entHelpTopicToCategory.helpTopicId = entHelpTopic.helpTopicId';
		}

		if( $aParams['status'] !== null && $aParams['status'] !== '*' ) {
			$aCriterias[] = 'helpTopicStatus = ' . $this->oDb->escapeStr( $aParams['status'] );
		}

		if( !empty($aEntitiesExtend) ) {
			$aDaoParams += array(
				'entitiesExtended' => 'entHelpTopic ' . implode( ' ', $aEntitiesExtend )
			);
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return parent::readData( $aDaoParams );
  }

	// public function updateSort( $aPrimaryIds, $iCategoryId ) {
	// 	$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );
	//
	// 	$oDaoMysql = clRegistry::get( 'clDaoMysql' );
	// 	$oDaoMysql->setDao( $this );
	// 	return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'helpTopicSort', array(
	// 		'entities' => 'entHelpTopic',
	// 		'primaryField' => 'helpTopicId',
	// 		'criterais' => 'helpTopicCategoryId = ' . (int) $iCategoryId
	// 	) );
	// }

	// TOPIC TO CATEGORY functions
	public function readTopicToCategory( $mTopicId ) {
		$aDaoParams = array(
			'fields' => 'helpCategoryId',
			'entities' => 'entHelpTopicToCategory',
			'withHelpers' => false
		);

		if( is_array($mTopicId) ) {
			$aDaoParams['criterias'] = 'helpTopicId IN(' . implode( ', ', array_map('intval', $mTopicId) ) . ')';
		} else {
			$aDaoParams['criterias'] = 'helpTopicId = ' . (int) $mTopicId;
		}

		return parent::readData( $aDaoParams );
	}
	public function createTopicToCategory( $mTopicId, $mCategoryId ) {
		$aDaoParams = array(
			'entities' => 'entHelpTopicToCategory',
			'withHelpers' => false,
			'fields' => array(
				'helpTopicId',
				'helpCategoryId'
			)
		);

		$mTopicId = (array) $mTopicId;
		$mCategoryId = (array) $mCategoryId;

		$aData = array();
		foreach( $mTopicId as $iTopicId ) {
			foreach( $mCategoryId as $iCategoryId ) {
				$aData[] = array(
					'helpTopicId' => $iTopicId,
					'helpCategoryId' => $iCategoryId
				);
			}
		}

		return parent::createMultipleData( $aData, $aDaoParams );
	}
	public function deleteTopicToCategory( $mTopicId, $mCategoryId ) {
		$aCriterias = array();
		$aDaoParams = array(
			'entities' => 'entHelpTopicToCategory',
			'withHelpers' => false
		);

		if( is_array($mTopicId) ) {
			$aCriterias[] = 'helpTopicId IN(' . implode( ', ', array_map('intval', $mTopicId) ) . ')';
		} else {
			$aCriterias[] = 'helpTopicId = ' . (int) $mTopicId;
		}

		if( !empty($mCategoryId) ) {
			if( is_array($mTopicId) ) {
				$aCriterias[] = 'helpCategoryId IN(' . implode( ', ', array_map('intval', $mCategoryId) ) . ')';
			} else {
				$aCriterias[] = 'helpCategoryId = ' . (int) $mCategoryId;
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return parent::deleteData( $aDaoParams );
	}

	// TOPIC RELATION functions
	public function readTopicRelation( $mTopicId ) {
		$aDaoParams = array(
			'fields' => 'helpTopicRelationId',
			'entities' => 'entHelpTopicRelation',
			'withHelpers' => false
		);

		if( is_array($mTopicId) ) {
			$aDaoParams['criterias'] = 'helpTopicId IN(' . implode( ', ', array_map('intval', $mTopicId) ) . ')';
			$aDaoParams['criterias'] .= 'OR helpTopicRelationId IN(' . implode( ', ', array_map('intval', $mTopicId) ) . ')';
		} else {
			$aDaoParams['criterias'] = 'helpTopicId = ' . (int) $mTopicId;
			$aDaoParams['criterias'] .= 'OR helpTopicRelationId = ' . (int) $mTopicId;
		}

		return parent::readData( $aDaoParams );
	}
	public function createTopicRelation( $mTopicId, $mRelationId ) {
		$aDaoParams = array(
			'entities' => 'entHelpTopicRelation',
			'withHelpers' => false,
			'fields' => array(
				'helpTopicId',
				'helpTopicRelationId'
			)
		);

		$mTopicId = (array) $mTopicId;
		$mRelationId = (array) $mRelationId;

		$aData = array();
		foreach( $mTopicId as $iTopicId ) {
			foreach( $mRelationId as $iRelationId ) {
				$aData[] = array(
					'helpTopicId' => $iTopicId,
					'helpTopicRelationId' => $iRelationId
				);
			}
		}

		return parent::createMultipleData( $aData, $aDaoParams );
	}
	public function deleteTopicRelation( $iTopicId, $iRelationId ) {
		$aDaoParams = array(
			'entities' => 'entHelpTopicRelation',
			'withHelpers' => false,
			'criterias' => 'helpTopicId = ' . (int) $mTopicId . ' AND helpTopicRelationId = ' . (int) $iRelationId
		);

		return parent::deleteData( $aDaoParams );
	}

}
