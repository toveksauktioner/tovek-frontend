<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_CORE . '/clTreeDaoBaseSql.php';

class clNavigationDaoMysql extends clDaoBaseSql {

	public $oTreeDao;
	public $sGroupKey;

	public function __construct() {
		$this->aDataDict = array(
			'entNavigation' => array(
				'navigationId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'navigationLangId' => array(
					'type' => 'integer',
					'index' => true
				),
				'navigationGroupKey' => array(
					'type' => 'string',
					'required' => true,
					'index' => true
				),
				'navigationUrl' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Path' )
				),
				'navigationTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'navigationImageSrc' => array(
					'type' => 'string',
					'title' => _( 'Image source' )
				),
				'navigationOpenIn' => array(
					'type' => 'array',
					'values' => array(
						'self' => _( 'Same (default)' ),
						'blank' => _( 'New window / tab' )
					),
					'title' => _( 'Open in' )
				),
				'navigationLeft' => array(
					'type' => 'integer',
					'index' => true
				),
				'navigationRight' => array(
					'type' => 'integer'
				)
			)
		);
		$this->sPrimaryField = 'navigationId';
		$this->sPrimaryEntity = 'entNavigation';
		$this->aFieldsDefault = array(
			'navigationId',
			'navigationUrl',
			'navigationTitle'
		);
		$this->sLeftColName = 'navigationLeft';
		$this->sRightColName = 'navigationRight';
		$this->init();

		$this->oTreeDao = new clTreeDaoBaseSql( $this );
	}

	public function delete( $iPrimaryId ) {
		$aParams = array(
			'criterias' => 'navigationGroupKey = ' . $this->sGroupKey . ' AND entNavigation.navigationLangId = ' . (int) $GLOBALS['langIdEdit']
		);
		return $this->oTreeDao->deleteNode( $iPrimaryId, $aParams );
	}

	public function move( $iPrimaryId, $aData ) {
		$aParams = array(
			'criterias' => 'navigationGroupKey = ' . $this->sGroupKey . ' AND entNavigation.navigationLangId = ' . (int) $this->iLangId
		);
		$targetNode = !empty( $aData['navigationTarget'] ) ? (int) $aData['navigationTarget'] : null;
		if( !empty($aData['navigationRelation']) ) $aParams['relation'] = $aData['navigationRelation'];

		$iPosition = $this->oTreeDao->readNodePosition( $targetNode, $aParams );
		$this->oTreeDao->updateNodePosition( $iPrimaryId, $iPosition, $aParams );

		# Update all other language versions to this new position
		return $this->oDb->write( 'UPDATE entNavigation t1 LEFT JOIN entNavigation t2 ON t1.navigationId = t2.navigationId SET t2.navigationLeft = t1.navigationLeft, t2.navigationRight = t1.navigationRight WHERE t1.navigationLangId = ' . (int) $this->iLangId . ' AND t2.navigationLangId <> ' . (int) $this->iLangId );
	}

	public function read( $aParams ) {
		$aParams += array(
			'fields' => array(),
			'navigationId' => null,
			'rootId' => null,
			'maxDepth' => null
		);
		$aCriterias = array();
		$aDaoParams = array(
			'fields' => $aParams['fields']
		);

		if( $aParams['navigationId'] !== null ) {
			if( is_array($aParams['navigationId']) ) {
				$aCriterias[] = "entNavigation.navigationId IN ('" . implode("', '", $aParams['navigationId']) . "')";
			} else {
				$aCriterias[] = 'entNavigation.navigationId = ' . (int) $aParams['navigationId'];
			}
		}
		$aCriterias[] = 'entNavigation.navigationLangId = ' . (int) $this->iLangId . ' AND parent.navigationLangId = ' . (int) $this->iLangId;
		$aDaoParams['entitiesExtendedJoinField'] = array(
			'navigationGroupKey' => $this->sGroupKey
		);
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		if( $aParams['maxDepth'] !== null ) $aDaoParams['maxDepth'] = $aParams['maxDepth'];
		return $this->oTreeDao->readWithChildren( $aParams['rootId'], $aDaoParams );
	}

	public function readByUrl( $aParams ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'criterias' => 'navigationUrl = ' . $this->oDb->escapeStr($aParams['navigationUrl']) . ' AND entNavigation.navigationLangId = ' . (int) $this->iLangId . ' AND navigationGroupKey = ' . $this->sGroupKey
		);
		
		return $this->readData( $aParams );
	}

	public function readGroup() {
		$aParams = array(
			'fields' => 'navigationGroupKey',
			'groupBy' => 'navigationGroupKey'
		);
		return $this->readData( $aParams );
	}

	public function readTreeByNode( $aParams = array() ) {
		
		$aParams += array(
			'fields' => $this->aFieldsDefault
		);
		
		$aRootNodeData = current($this->oTreeDao->readRootNodeByNode($aParams['navigationId'], array(
			$this->sPrimaryField
		), array(
			'criterias' => 'entNavigation.navigationLangId = ' . (int) $this->iLangId . ' AND navigationGroupKey = ' . $this->sGroupKey
		)));
		if( empty($aRootNodeData) ) return false;
		
		$aTreeDaoParams = array(
			'fields' => $aParams['fields'],
			'criterias' => 'entNavigation.navigationLangId = ' . (int) $this->iLangId . ' AND parent.navigationLangId = ' . (int) $this->iLangId,
			'entitiesExtendedJoinField' => array(
				'navigationGroupKey' => $this->sGroupKey
			)
		);
		
		// Read tree
		return $this->oTreeDao->readWithChildren( $aRootNodeData[$this->sPrimaryField], $aTreeDaoParams );
	}

	public function readWithParentsByUrl( $sUrl, $aParams = array() ) {
		$aParams['entitiesExtendedJoinField'] = array(
			'navigationGroupKey' => $this->sGroupKey
		);
		if( empty($aParams['fields']) ) $aParams['fields'] = $this->aFieldsDefault;

		foreach( $aParams['fields'] as $key => $value ) {
			if( !array_key_exists($value, $this->aDataDict[$this->sPrimaryEntity]) ) continue;
			$aParams['fields'][$key] = 'parent.' . $value;
		}

		$aParams['entitiesExtended'] = $this->sPrimaryEntity . ' JOIN ' . $this->sPrimaryEntity . ' AS parent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? ' ' . $this->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = parent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->sPrimaryEntity . '.' . $this->sLeftColName . ' BETWEEN parent.' . $this->sLeftColName . ' AND parent.' . $this->sRightColName . ( !empty($aParams['entitiesExtended']) ? ' LEFT JOIN ' . $aParams['entitiesExtended'] : '' );
		$aParams['criterias'] = $this->sPrimaryEntity . '.navigationLangId = ' . (int) $this->iLangId . ' AND parent.navigationLangId = ' . (int) $this->iLangId . ' AND ' . $this->sPrimaryEntity . '.navigationUrl = ' . $this->oDb->escapeStr( $sUrl ) . ( !empty($aParams['entitiesExtendedJoinField']) ? ' AND ' . $this->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . reset($aParams['entitiesExtendedJoinField']) : '' ) . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '');
		$aParams['groupBy'] = 'parent.navigationId';
		$aParams['sorting'] = 'parent.' . $this->sLeftColName;
		return $this->readData( $aParams );
	}
	
	public function readSiblings( $aParams = array() ) {
		$aDaoParams = array(
			'fields' => ( !empty($aParams['fields']) ? $aParams['fields'] : $this->aFieldsDefault ),
			'entitiesExtendedJoinField' => array(
				'navigationGroupKey' => $this->sGroupKey,
				'navigationLangId' => (int) $this->iLangId
			)			
		);
		
		return $this->oTreeDao->readNodeSiblings( $aParams['nodeId'], $aDaoParams );
	}

	public function setGroupKey( $sGroupKey ) {
		$this->sGroupKey = $this->oDb->escapeStr( $sGroupKey );
	}

	public function update( $primaryId, $aData, $aParams = array() ) {
		if( is_array($primaryId) ) {
			$aParams['criterias'] = $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' IN (' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $primaryId) ) . ')';
		} else {
			$aParams['criterias'] = $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' = ' . $this->oDb->escapeStr( $primaryId );
		}
		$aParams['criterias'] .= ' AND navigationLangId = ' . (int) $this->iLangId;

		return $this->updateData( $aData, $aParams );
	}

}
