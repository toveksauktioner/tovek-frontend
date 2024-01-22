<?php

require_once PATH_CORE . '/clTextDaoBaseSql.php';

class clLayoutDaoMysql extends clTextDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entLayout' => array(
				'layoutKey' => array(
					'type' => 'string',
					'primary' => true,
					'title' => _( 'Key' ),
					'required' => true
				),
				'layoutFile' => array(
					'type' => 'string',
					'title' => _( 'Layout file' ),
					'required' => true
				),
				'layoutTemplateFile' => array(
					'type' => 'string',
					'title' => _( 'Template file' ),
					'required' => true
				),
				'layoutTitleTextId' => array(
					'type' => 'integer',
					'title' => _( 'Title' )
				),
				'layoutKeywordsTextId' => array(
					'type' => 'integer',
					'title' => _( 'Keywords' )
				),
				'layoutDescriptionTextId' => array(
					'type' => 'integer',
					'title' => _( 'Description' )
				),				
				'layoutCanonicalUrlTextId' => array(
					'type' => 'integer',
					'title' => _( 'Canonical URL' )
				),
				'layoutSuffixContent' => array(
					'type' => 'string',
					'title' => _( 'Suffix content' )
				),
				'layoutBodyClass' => array(
					'type' => 'string',
					'title' => _( 'Body class' )
				),
				'layoutProtected' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Protected' )
				),
				'layoutDynamicChildrenRoute' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Dynamic children route' )
				),
				'layoutCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'layoutUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			),
			'entLayoutSection' => array(
				'sectionId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'sectionKey' => array(
					'type' => 'string',
				),
				'sectionLayoutKey' => array(
					'type' => 'string',
					'required' => true,
					'index' => true
				),
			),
			'entViewToSection' => array(
				'viewId' => array(
					'type' => 'integer',
					'required' => true,
					'index' => true
				),
				'sectionId' => array(
					'type' => 'integer',
					'required' => true,
					'index' => true
				),
				'position' => array(
					'type' => 'integer',
					'required' => true
				)
			)
		);
		$this->sPrimaryField = 'layoutKey';
		$this->sPrimaryEntity = 'entLayout';
		$this->aFieldsDefault = '*';
		
		$this->sTextEntity = 'entLayoutText';
		$this->aTextFields = array(
			'layoutTitleTextId',
			'layoutKeywordsTextId',
			'layoutDescriptionTextId',
			'layoutCanonicalUrlTextId'
		);
		
		$this->init();
	}

	public function createViewToSection( $iViewId, $iSectionId, $iPosition = null ) {
		$aParams = array(
			'entities' => 'entViewToSection',
			'criterias' => 'sectionId = ' . (int) $iSectionId
		);

		if( $iPosition === null ) {
			// Get next position
			$aParams['fields'] = 'MAX(position) as position';
			$iPosition = current( current(clDaoBaseSql::readData($aParams)) ) +  1;
		} else {
			// Update positions
			$aData = array(
				'position' => 'position + 1'
			);
			$aParams['criterias'] .= ' AND position >= ' . (int) $iPosition;
			$aParams['dataEscape'] = false;
			clDaoBaseSql::updateData( $aData, $aParams );
		}

		$aData = array(
			'viewId' => (int) $iViewId,
			'sectionId' => (int) $iSectionId,
			'position' => (int) $iPosition
		);
		$aParams = array(
			'entities' => 'entViewToSection'
		);
		return clDaoBaseSql::createData( $aData, $aParams );
	}

	public function delete( $sLayoutKey ) {

		$this->deleteSectionByLayout( $sLayoutKey );

		$sLayoutKey = $this->oDb->escapeStr( $sLayoutKey );

		// Delete routes
		$aParams = array(
			'entities' => 'entRoute',
			'criterias' => 'routeLayoutKey = ' . $sLayoutKey
		);
		clDaoBaseSql::deleteData( $aParams );

		// Delete layout
		$aParams = array(
			'criterias' => 'layoutKey = ' . $sLayoutKey
		);
		$this->deleteData( $aParams );
	}

	public function deleteViewToSection( $iViewId, $iSectionId ) {
		// Get position
		$aParams = array(
			'fields' => 'position',
			'entities' => 'entViewToSection',
			'criterias' => 'viewId = ' . (int) $iViewId . ' AND sectionId = ' . (int) $iSectionId
		);
		$aPosition = current( clDaoBaseSql::readData($aParams) );
		
		if( empty($aPosition) ) return false;
		
		$iPosition = (int) current( $aPosition );

		// Delete entry
		$aParams = array(
			'entities' => 'entViewToSection',
			'criterias' => 'viewId = ' . (int) $iViewId . ' AND sectionId = ' . (int) $iSectionId
		);
		clDaoBaseSql::deleteData( $aParams );

		// Update positions
		if( !empty($iPosition) ) {
			$aData = array(
				'position' => 'position - 1'
			);
			$aParams = array(
				'entities' => 'entViewToSection',
				'criterias' => 'sectionId = ' . (int) $iSectionId . ' AND position >= ' . (int) $iPosition,
				'dataEscape' => false
			);
			clDaoBaseSql::updateData( $aData, $aParams );
		}
	}

	public function deleteSectionByLayout( $sLayoutKey ) {
		$aParams = array(
			'entitiesToDelete' => 'entViewToSection, entLayoutSection',
			'entities' => array(
				'entViewToSection',
				'entLayoutSection'
			),
			'entitiesExtended' => 'entLayoutSection LEFT JOIN entViewToSection ON entLayoutSection.sectionId = entViewToSection.sectionId',
			'criterias' => 'entLayoutSection.sectionLayoutKey = ' . $this->oDb->escapeStr( $sLayoutKey )
		);
		clDaoBaseSql::deleteData( $aParams );
	}

	public function readCustom( $aParams ) {
		$aParams += array(
			'criterias' => "layoutKey LIKE 'guestInfo-%'"
		);

		return $this->readData( $aParams );
	}
	
	public function readViewModuleKey( $iViewId ) {
		$aParams = array(
			'fields' => 'viewModuleKey',
			'entities' => 'entView',
			'criterias' => 'viewId = ' . (int) $iViewId
		);

		$sViewModuleKey = clDaoBaseSql::readData( $aParams );
		return !empty($sViewModuleKey) ? current( current(clDaoBaseSql::readData($aParams)) ) : false;
	}

	public function readSectionsAndViews( $sLayoutKey, $sSection = null ) {
		$aParams = array(
			'fields' => array(
				't1.sectionId',
				't1.sectionKey',
				't2.viewId',
				't2.position',
				't3.viewModuleKey',
				't3.viewFile'
			),
			'entities' => array(
				'entLayoutSection',
				'entViewToSection',
				'entView'
			),
			'entitiesExtended' => '
				entLayoutSection t1 LEFT JOIN
				entViewToSection t2 ON t1.sectionId = t2.sectionId LEFT JOIN
				entView AS t3 ON t2.viewId = t3.viewId',			
			'sorting' => 't2.position'
		);
		
		$aCriterias = array();
		if( is_array($sLayoutKey) ) {
			$aCriterias[] = 't1.sectionLayoutKey IN("' . implode('", "', $sLayoutKey ) . '")';
		} else {
			$aCriterias[] = 't1.sectionLayoutKey = ' . $this->oDb->escapeStr( $sLayoutKey );
		}
		
		if( $sSection !== null ) {
			$aCriterias[] = 't1.sectionKey = ' . $this->oDb->escapeStr($sSection);
		}
		
		$aParams['criterias'] = implode( ' AND ', $aCriterias);

		return clDaoBaseSql::readData( $aParams );
	}

	public function readSectionId( $sLayoutKey, $sSectionKey ) {
		$aParams = array(
			'fields' => array(
				'sectionId'
			),
			'entities' => array(
				'entLayoutSection'
			),
			'criterias' => 'sectionLayoutKey = ' . $this->oDb->escapeStr( $sLayoutKey ) . ' AND sectionKey = ' . $this->oDb->escapeStr( $sSectionKey ),
			'entries' => '1'
		);

		$aData = current( clDaoBaseSql::readData($aParams) );		
		return !empty($aData) ? current($aData) : false;
	}

	public function readByViewId( $iViewId ) {
		$aParams = array(
			'entities' => array(
				'entLayoutSection',
				'entViewToSection'
			),
			'entitiesExtended' => '
				entLayoutSection LEFT JOIN
				entViewToSection ON entLayoutSection.sectionId = entViewToSection.sectionId'			
		);

		if( is_array($iViewId) ) {
			$aParams += array(
				'fields' => array(
					'viewId',
					'entLayoutSection.sectionLayoutKey'
				),
				'criterias' => 'entViewToSection.viewId IN(' . implode(',', $iViewId) . ')'
			);
			return clDaoBaseSql::readData($aParams);
		} else {
			$aParams += array(
				'fields' => array(				
					'entLayoutSection.sectionLayoutKey'
				),
				'criterias' => 'entViewToSection.viewId = ' . (int) $iViewId,
				'entries' => '1'
			);
			return current( current(clDaoBaseSql::readData($aParams)) );
		}		
	}

	public function update( $sLayoutKey, $aData, $aParams ) {
		// Check if layoutkey is being changed
		if( !empty($aData['layoutKey']) && $aData['layoutKey'] != $sLayoutKey ) {
			// Update routes
			$oRouter = clRegistry::get( 'clRouter' );
			$aData2 = array(
				'routeLayoutKey' => $aData['layoutKey']
			);
			$oRouter->updateByLayout( $sLayoutKey, $aData2 );

			// Update sections
			$aData2 = array(
				'sectionLayoutKey' => $aData['layoutKey']
			);
			$aParams2 = array(
				'entities' => 'entLayoutSection',
				'criterias' => 'sectionLayoutKey = ' . $this->oDb->escapeStr( $sLayoutKey ),
				'groupKey' => $aParams['groupKey']
			);
			clDaoBaseSql::updateData( $aData2, $aParams2 );
		}

		return $this->updateDataByPrimary( $sLayoutKey, $aData, $aParams );
	}

	public function updateLayoutFile( $sLayoutKey, $sLayoutFile ) {
		$this->deleteSectionByLayout( $sLayoutKey );
		$aData = array(
			'layoutFile' => $sLayoutFile
		);
		return $this->updateDataByPrimary( $sLayoutKey, $aData );
	}

	public function updateViewPosition( $iSectionId, $aViewPositions ) {
		foreach( $aViewPositions as $iViewId => $iPosition ) {
			$aParams = array(
				'entities' => 'entViewToSection',
				'criterias' => 'viewId = ' . (int) $iViewId . ' AND sectionId = ' . (int) $iSectionId,
				'validated' => true
			);
			clDaoBaseSql::updateData( array(
				'position' => (int) $iPosition
			), $aParams );
		}

		return true;
	}
}
