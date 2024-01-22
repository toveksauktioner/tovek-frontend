<?php

require_once PATH_CORE . '/clTextDaoBaseSql.php';

class clInfoContentDaoMysql extends clTextDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entInfoContent' => array(
				'contentId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'contentTextId' => array(
					'title' => _( 'Content' ),
					'type' => 'integer'
				),
				'contentViewId' => array(
					'type' => 'integer'
				),
				'contentKey' => array(
					'type' => 'string',
					'title' => _( 'Key' ),
					'required' => true
				),
				'contentStatus' => array(
					'type' => 'array',
					'title' => _( 'Status' ),
					'values' => array(
						'active' => _( 'Active' ),
						'preview' => _( 'Preview' ),
						'inactive' => _( 'Inactive' )					
					)
				),
				'contentUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				),
				'contentCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			),
			'entInfoContentRevision' => array(
				'revisionId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Revision ID' )
				),
				'contentId' => array(
					'type' => 'integer',
					'required' => true,
					'index' => true
				),
				'revisionLangId' => array(
					'type' => 'integer',
					'required' => true,
					'index' => true
				),
				'userId' => array(
					'title' => _( 'User ID' ),
					'type' => 'integer',
					'index' => true,
					'required' => true
				),
				'username' => array(
					'title' => _( 'Username' ),
					'type' => 'string',
					'required' => true,
					'min' => 3,
					'max' => 50,
				),				
				'revisionContent' => array(
					'title' => _( 'Content' ),
					'type' => 'string'
				),
				'revisionCreated' => array(
					'title' => _( 'Created' ),
					'type' => 'datetime'
				)
			)
		);
		$this->sPrimaryField = 'contentId';
        $this->sPrimaryEntity = 'entInfoContent';
        $this->aFieldsDefault = '*';
		$this->sTextEntity = 'entInfoContentText';
		$this->aTextFields = array(
			'contentTextId'
		);

        $this->init();
    }

	public function createRevision( $aData, $aParams ) {
		$aParams += array(
			'entities' => 'entInfoContentRevision'
		);
		
		return clDaoBaseSql::createData( $aData, $aParams );
	}

	public function delete( $iPrimaryId ) {
		return $this->deleteDataByPrimary(
			$iPrimaryId,
			array(
				'entities' => array(
					'entInfoContent',
					'entInfoContentText',
					'entView'
				),
				'entitiesExtended' => 'entInfoContent LEFT JOIN entInfoContentText ON entInfoContent.contentTextId = entInfoContentText.textId LEFT JOIN entView ON entInfoContent.contentViewId = entView.viewId',
				'entitiesToDelete' => 'entInfoContent, entInfoContentText, entView'
			)
		);
	}

	public function deleteRevision( $iPrimaryId ) {
		return clDaoBaseSql::deleteData(			
			array(
				'entities' => array(
					'entInfoContentRevision'
				),
				'criterias' => 'entInfoContentRevision.revisionId = ' . (int) $iPrimaryId
			)
		);
	}

	public function read( $aParams ) {
		$aParams += array(
			'contentId' => null,
			'viewId' => null,
			'key' => null,
			'fields' => $this->aFieldsDefault,
			'langId' => $this->iLangId
		);

		$aDaoParams = array(
			'fields' => $aParams['fields']
		);
		$aCriterias = array();

		if( $aParams['contentId'] !== null ) {
			if( is_array($aParams['contentId']) ) {
				$aCriterias[] = 'entInfoContent.contentId IN(' . implode( ', ', array_map('intval', $aParams['contentId']) ) . ')';
			} else {
				$aCriterias[] = 'entInfoContent.contentId = ' . (int) $aParams['contentId'];
			}
		}

		if( $aParams['key'] !== null ) {
			if( is_array($aParams['key']) ) {
				$aCriterias[] = 'entInfoContent.contentKey IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['key']) ) . ')';
			} else {
				$aCriterias[] = 'entInfoContent.contentKey = "' . $aParams['key'] . '"';
			}
		}

		if( $aParams['viewId'] !== null ) {
			if( is_array($aParams['viewId']) ) {
				$aCriterias[] = 'entInfoContent.contentViewId IN(' . implode( ', ', array_map('intval', $aParams['viewId']) ) . ')';
			} else {
				$aCriterias[] = 'entInfoContent.contentViewId = ' . (int) $aParams['viewId'];
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData($aDaoParams);
	}
	
	public function readRevision( $aParams ) {
		$aParams += array(
			'revisionId' => null,
			'contentId' => null,
			'fields' => $this->aFieldsDefault,
			'langId' => $this->iLangId
		);

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entities' => 'entInfoContentRevision'
		);
		
		$aCriterias = array();
		if( $aParams['langId'] !== null ) {
			$aCriterias[] = 'entInfoContentRevision.revisionLangId = ' . (int) $aParams['langId'];
		}

		if( $aParams['contentId'] !== null ) {
			if( is_array($aParams['contentId']) ) {
				$aCriterias[] = 'entInfoContentRevision.contentId IN(' . implode( ', ', array_map('intval', $aParams['contentId']) ) . ')';
			} else {
				$aCriterias[] = 'entInfoContentRevision.contentId = ' . (int) $aParams['contentId'];
			}
		}
		
		if( $aParams['revisionId'] !== null ) {
			if( is_array($aParams['revisionId']) ) {
				$aCriterias[] = 'entInfoContentRevision.revisionId IN(' . implode( ', ', array_map('intval', $aParams['revisionId']) ) . ')';
			} else {
				$aCriterias[] = 'entInfoContentRevision.revisionId = ' . (int) $aParams['revisionId'];
			}
		}

		$aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return clDaoBaseSql::readData($aDaoParams);
	}

	public function updateRevision( $iPrimaryId, $aData ) {
		$aParams = array(
			'entities' => 'entInfoContentRevision',
			'criterias' => 'entInfoContentRevision.revisionId = ' . (int) $iPrimaryId
		);
		return clDaoBaseSql::updateData( $aData, $aParams );
	}

}
