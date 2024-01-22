<?php

class clTreeDaoBaseSql {

	private $oDao;

	public function __construct( $oDao = null ) {
    	if( $oDao !== null ) $this->setDao( $oDao );
    }

	public function createNode( $aData, $aParams = array() ) {
		$aParams += array(
			'position' => 0
		);

		$this->oDao->oDb->query( 'LOCK TABLE ' . $this->oDao->sPrimaryEntity . ' WRITE' );
		$aParams['criterias'] = $this->oDao->sRightColName . ' > ' . $aParams['position'] . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$this->oDao->oDb->write( 'UPDATE ' . $this->oDao->sPrimaryEntity . ' SET ' . $this->oDao->sRightColName . ' = ' . $this->oDao->sRightColName . ' + 2' . $this->oDao->formatCriterias($aParams['criterias']) );

		$aParams['criterias'] = $this->oDao->sLeftColName . ' > ' . $aParams['position'] . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$this->oDao->oDb->write( 'UPDATE ' . $this->oDao->sPrimaryEntity . ' SET ' . $this->oDao->sLeftColName . ' = ' . $this->oDao->sLeftColName . ' + 2' . $this->oDao->formatCriterias($aParams['criterias']) );
		$this->oDao->oDb->query( 'UNLOCK TABLES' );

		$aData[$this->oDao->sLeftColName] = $aParams['position'] + 1;
		$aData[$this->oDao->sRightColName] = $aParams['position'] + 2;
		$aResult = $this->oDao->createData( $aData, $aParams );
		return $aResult;
	}

	public function deleteNode( $node, $aParams = array() ) {
		$aResult = current( $this->oDao->readDataByPrimary( $node, array(
			'fields' => array($this->oDao->sLeftColName, $this->oDao->sRightColName )
		)) );
		if( empty($aResult) ) return false;

		$iLeft = $aResult[$this->oDao->sLeftColName];
		$iRight = $aResult[$this->oDao->sRightColName];
		$iWidth = $iRight - $iLeft + 1;
		$iResult = 0;

		$aParams['criterias'] = $this->oDao->sLeftColName . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$iResult = $this->oDao->deleteData( $aParams );

		$this->oDao->oDb->query( 'LOCK TABLE ' . $this->oDao->sPrimaryEntity . ' WRITE' );
		$aParams['criterias'] = $this->oDao->sRightColName . ' > ' . $iRight . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$this->oDao->oDb->write( 'UPDATE ' . $this->oDao->sPrimaryEntity . ' SET ' . $this->oDao->sRightColName . ' = ' . $this->oDao->sRightColName . ' - ' . $iWidth . $this->oDao->formatCriterias($aParams['criterias']) );

		$aParams['criterias'] = $this->oDao->sLeftColName . ' > ' . $iRight . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$this->oDao->oDb->write( 'UPDATE ' . $this->oDao->sPrimaryEntity . ' SET ' . $this->oDao->sLeftColName . ' = ' . $this->oDao->sLeftColName . ' - ' . $iWidth . $this->oDao->formatCriterias($aParams['criterias']) );
		$this->oDao->oDb->query( 'UNLOCK TABLES' );

		return $iResult;
	}

	/***
	 *  Returns "leafs", which are nodes without any children
	 *  
	 *  @param array $aParams
	 *  @return array Returns array with data
	 */
	public function readLeafNodes( $aParams = array() ) {
		$aParams['criterias'] = $this->oDao->sRightColName . ' = ' . $this->oDao->sLeftColName . ' + 1' . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		return $this->oDao->readData( $aParams );
	}

	// TODO Read node count from subtree
	public function readNodeCount( $node = null ) {
		$aResult = $this->oDao->readData( array(
			'fields' => 'MAX(' . $this->oDao->sRightColName . ') / 2'
		) );
		return (int) current( current($aResult) );
	}
	
	/***
	 *  Reads the parent of a node
	 *
	 *  @param mixed $node Node ID
	 *  @param array $aParams
	 *  @return Mixed Returns array with data, or false if node is a root node or non existence
	 */
	public function readNodeParent( $node, $aParams = array() ) {		
		if( isset($aParams['fields']) ) {
			if( is_array($aParams['fields']) ) {
				foreach( $aParams['fields'] as &$sField ) {
					$sField = 'parent.' . $sField;
				}
			} elseif( $aParams['fields'] == '*' )  {
				$aParams['fields'] = 'parent.*';
			} else {
				$aParams['fields'] = 'parent.' . $aParams['fields'];
			}
		} else {
			$aParams['fields'] = 'parent.' . $this->oDao->sPrimaryField;
		}

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entitiesExtended' => $this->oDao->sPrimaryEntity . ' AS node, ' . $this->oDao->sPrimaryEntity . ' AS parent',
			'criterias' => 'parent.' . $this->oDao->sLeftColName . ' < node.' . $this->oDao->sLeftColName . ' AND parent.' . $this->oDao->sRightColName . ' > node.' . $this->oDao->sRightColName . ' AND node.' . $this->oDao->sPrimaryField . ' = ' . $this->oDao->oDb->escapeStr( $node ),
			'sorting' => '( parent.' . $this->oDao->sRightColName . ' - parent.' . $this->oDao->sLeftColName . ' ) ASC',
			'entries' => '1'
		);
		
		if( !empty($aParams['entitiesExtendedJoinField']) ) {
			foreach( $aParams['entitiesExtendedJoinField'] as $sCriteriaField => $sCriteraValue ) {
				$aDaoParams['criterias'] .= ' AND parent.' . $sCriteriaField . ' = ' . $sCriteraValue . ' AND node.' . $sCriteriaField . ' = ' . $sCriteraValue;
			}
		}
		
		return current($this->oDao->readData( $aDaoParams ));
	}

	public function readNodePosition( $node, $aParams = array() ) {
		$aParams += array(
			'relation' => null,
			'criterias' => null
		);

		$iPosition = 0;
		if( empty($node) ) {
			if( $aParams['relation'] == 'lastChild' ){
				$aResult = $this->oDao->readData( array(
					'fields' => 'MAX(' . $this->oDao->sRightColName . ')',
					'criterias' => $aParams['criterias']
				) );
				$iPosition = (int) current( current($aResult) );
			}
		} else {
			switch( $aParams['relation'] ) {
				case 'prevSibling':
					$sFields = $this->oDao->sLeftColName . ' - 1';
					break;
				case 'nextSibling':
					$sFields = $this->oDao->sRightColName;
					break;
				case 'lastChild':
					$sFields = $this->oDao->sRightColName . ' - 1';
					break;
				case 'firstChild':
				default:
					$sFields = $this->oDao->sLeftColName;
					break;
			}

			$aResult = $this->oDao->readDataByPrimary( $node, array(
				'fields' => $sFields
			) + $aParams );
			$iPosition = (int) current( current($aResult) );
		}

		return $iPosition;
	}
	
	/**
	 * Reads the siblings of a node
	 * @param mixed $node Node ID
	 * @param array $aParams
	 * 
	 * @return array Returns array with data, or false on failure
	 */	
	public function readNodeSiblings( $node, $aParams = array() ) {
		$iParentId = current($this->readNodeParent( $node, array(
			'fields' => $this->oDao->sPrimaryField
		) + $aParams ));
		if( $iParentId === false ) {
			// Missing node or depth = 0
			return false;
		}

		$aParams += array(
			'fields' => $this->oDao->aFieldsDefault,
		);
		$aParams['fields'] = (array) $aParams['fields'];

		$sFieldCount = '(COUNT(parent.' . $this->oDao->sPrimaryField . ') - 1)';

		$aParams['entitiesExtended'] = $this->oDao->sPrimaryEntity . ' JOIN ' . $this->oDao->sPrimaryEntity . ' AS parent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = parent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName . ' BETWEEN parent.' . $this->oDao->sLeftColName . ' AND parent.' . $this->oDao->sRightColName . ( !empty($aParams['entitiesExtended']) ? ' LEFT JOIN ' . $aParams['entitiesExtended'] : '' );
		$aParams['groupBy'] = $this->oDao->sPrimaryEntity . '.' . $this->oDao->sPrimaryField;
		$aParams['sorting'] = $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName;
		
		$sFieldCount = '(COUNT(parent.' . $this->oDao->sPrimaryField . ')-1)';
		$aParams['entitiesExtended'] = $this->oDao->sPrimaryEntity . '
		JOIN ' . $this->oDao->sPrimaryEntity . ' AS parent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = parent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName . ' BETWEEN parent.' . $this->oDao->sLeftColName . ' AND parent.' . $this->oDao->sRightColName . '
		JOIN ' . $this->oDao->sPrimaryEntity . ' AS subParent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = subParent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName . ' BETWEEN subParent.' . $this->oDao->sLeftColName . ' AND subParent.' . $this->oDao->sRightColName . '
		JOIN (
			SELECT ' . $sFieldCount . ' AS depth, ' . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sPrimaryField . ( !empty($aParams['entitiesExtendedJoinField']) ? ', ' . $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) : '' ) . '
			FROM ' . $aParams['entitiesExtended'] . '
			WHERE ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . reset($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sPrimaryField . ' = ' . $this->oDao->oDb->escapeStr( $iParentId ) . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '') . '
			GROUP BY ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ', ' : '' ) . $aParams['groupBy'] . '
			ORDER BY ' . $aParams['sorting'] . '
		) AS subTree ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? 'subParent.' . key($aParams['entitiesExtendedJoinField']) . ' = subTree.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . 'subParent.' . $this->oDao->sPrimaryField . ' = subTree.' . $this->oDao->sPrimaryField;
		$sFieldCount = '(COUNT(parent.' . $this->oDao->sPrimaryField . ') - (subTree.depth) - 1)';

		foreach( $aParams['fields'] as $key => $value ) {
			if( !array_key_exists($value, $this->oDao->aDataDict[$this->oDao->sPrimaryEntity]) ) continue;
			$aParams['fields'][$key] = $this->oDao->sPrimaryEntity . '.' . $value;
		}

		$aParams['groupBy'] .= ' HAVING depth = 1';
		$aParams['fields'][] = $sFieldCount . ' AS depth';
		
		$sExtraCriterias = '';
		if( !empty($aParams['entitiesExtendedJoinField']) ) {
			foreach( $aParams['entitiesExtendedJoinField'] as $sCriteriaKey => $sCriteraValue ) {
				$sExtraCriterias .= ' AND tree.' .$sCriteriaKey . ' = ' . $sCriteraValue;
			}
			
		}
		
		$aParams['fields'][] = '(SELECT ' . $this->oDao->sPrimaryField . ' FROM ' . $this->oDao->sPrimaryEntity . ' AS tree
		WHERE tree.' . $this->oDao->sLeftColName . ' < ' . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName . ' AND tree.' . $this->oDao->sRightColName . ' > ' . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sRightColName . $sExtraCriterias . ' ORDER BY tree.' . $this->oDao->sRightColName . ' - ' . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sRightColName . ' ASC LIMIT 1) AS parentId';		
		return $this->oDao->readData( $aParams );
	}
	
	/***
	 *  Reads the root node, from a node somewhere in the path
	 *
	 *  @param integer $node
	 *  @param array $aParams
	 *  @return array Returns array with data
	 */
	public function readRootNodeByNode( $node, $aFields = array()  ) {
		
		// Read node left and right values
		$aNodePostionData = current($this->oDao->readDataByPrimary( $node, array(
			'fields' => array(
				$this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName,
				$this->oDao->sPrimaryEntity . '.' . $this->oDao->sRightColName
			)
		) ));
		if( empty($aNodePostionData) ) return false;
		
		// Read the root node
		$aDaoParams = array(
			'fields' => $aFields,
			'criterias' => $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName . ' <= ' . $aNodePostionData[$this->oDao->sLeftColName] .
						' AND ' . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sRightColName . ' >= ' . $aNodePostionData[$this->oDao->sRightColName],
			'sorting' => array( $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName => 'ASC' ),
			'entries' => 1
		);
		
		return $this->oDao->readData( $aDaoParams );
	}
	
	/***
	 *  Read whole path, with help of a node somewhere in the path
	 *  @param integer $node
	 *  @param array $aParams
	 *  @return array Returns array with data
	 */
	public function readPathByNode( $node, $aParams = array() ) {
		
		$aRootNodeData = current($this->readRootNodeByNode($node, array(
			$this->oDao->sPrimaryField
		)));
		if( empty($aRootNodeData) ) return false;

		// Read tree		
		return $this->readWithChildren( $aRootNodeData[$this->oDao->sPrimaryField], $aParams );
	}

	/* To do:
	 * get 'realDepth' when reading depth from nod
	 **/
	public function readWithChildren( $node, $aParams = array() ) {
		$aParams += array(
			'fields' => $this->oDao->aFieldsDefault,
			'maxDepth' => null,
			'minDepth' => null
		);
		$aParams['fields'] = (array) $aParams['fields'];

		$sFieldCount = '(COUNT(parent.' . $this->oDao->sPrimaryField . ') - 1)';

		$aParams['entitiesExtended'] = $this->oDao->sPrimaryEntity . ' JOIN ' . $this->oDao->sPrimaryEntity . ' AS parent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = parent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName . ' BETWEEN parent.' . $this->oDao->sLeftColName . ' AND parent.' . $this->oDao->sRightColName . ( !empty($aParams['entitiesExtended']) ? ' LEFT JOIN ' . $aParams['entitiesExtended'] : '' );
		$aParams['groupBy'] = $this->oDao->sPrimaryEntity . '.' . $this->oDao->sPrimaryField;
		$aParams['sorting'] = $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName;

		if( empty($node) ) {
			if( !empty($aParams['entitiesExtendedJoinField']) ) {
				$aParams['criterias'] = $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . reset($aParams['entitiesExtendedJoinField']) . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '');
			}
		} else {
			$sFieldCount = '(COUNT(parent.' . $this->oDao->sPrimaryField . ')-1)';
			$aParams['entitiesExtended'] = $this->oDao->sPrimaryEntity . '
			JOIN ' . $this->oDao->sPrimaryEntity . ' AS parent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = parent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName . ' BETWEEN parent.' . $this->oDao->sLeftColName . ' AND parent.' . $this->oDao->sRightColName . '
			JOIN ' . $this->oDao->sPrimaryEntity . ' AS subParent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = subParent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName . ' BETWEEN subParent.' . $this->oDao->sLeftColName . ' AND subParent.' . $this->oDao->sRightColName . '
			JOIN (
				SELECT ' . $sFieldCount . ' AS depth, ' . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sPrimaryField . ( !empty($aParams['entitiesExtendedJoinField']) ? ', ' . $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) : '' ) . '
				FROM ' . $aParams['entitiesExtended'] . '
				WHERE ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . reset($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sPrimaryField . ' = ' . $this->oDao->oDb->escapeStr( $node ) . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '') . '
				GROUP BY ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ', ' : '' ) . $aParams['groupBy'] . '
				ORDER BY ' . $aParams['sorting'] . '
			) AS subTree ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? 'subParent.' . key($aParams['entitiesExtendedJoinField']) . ' = subTree.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . 'subParent.' . $this->oDao->sPrimaryField . ' = subTree.' . $this->oDao->sPrimaryField;
			$sFieldCount = '(COUNT(parent.' . $this->oDao->sPrimaryField . ') - (subTree.depth) - 1)';
		}

		foreach( $aParams['fields'] as $key => $value ) {
			if( !array_key_exists($value, $this->oDao->aDataDict[$this->oDao->sPrimaryEntity]) ) continue;
			$aParams['fields'][$key] = $this->oDao->sPrimaryEntity . '.' . $value;
		}

		if( $aParams['maxDepth'] !== null ) {
			$aParams['groupBy'] .= ' HAVING ' . $sFieldCount . ' <= ' . $aParams['maxDepth'];
			if( $aParams['minDepth'] !== null ) {
				$aParams['groupBy'] .= ' AND ' . $sFieldCount . ' >= ' . $aParams['minDepth'];
			}
		} elseif( $aParams['minDepth'] !== null ) {
			$aParams['groupBy'] .= ' HAVING ' . $sFieldCount . ' >= ' . $aParams['minDepth'];
		}

		$aParams['fields'][] = $sFieldCount . ' AS depth';
		return $this->oDao->readData( $aParams );
	}

	public function readWithParents( $node, $aParams = array() ) {
		$aParams += array(
			'fields' => $this->oDao->aFieldsDefault
		);
		$aParams['fields'] = (array) $aParams['fields'];

		foreach( $aParams['fields'] as $key => $value ) {
			if( !array_key_exists($value, $this->oDao->aDataDict[$this->oDao->sPrimaryEntity]) ) continue;
			$aParams['fields'][$key] = $this->oDao->sPrimaryEntity . '.' . $value;
		}

		$aParams['entitiesExtended'] = $this->oDao->sPrimaryEntity . ' AS node JOIN ' . $this->oDao->sPrimaryEntity . ' ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? ' ' . 'node.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . $this->oDao->sPrimaryEntity . '.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . 'node.' . $this->oDao->sLeftColName . ' BETWEEN ' . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName . ' AND ' . $this->oDao->sPrimaryEntity . '.' . $this->oDao->sRightColName . ( !empty($aParams['entitiesExtended']) ? ' LEFT JOIN ' . $aParams['entitiesExtended'] : '' );
		$aParams['criterias'] = 'node.' . $this->oDao->sPrimaryField . ' = ' . $this->oDao->oDb->escapeStr( $node ) . ( !empty($aParams['entitiesExtendedJoinField']) ? ' AND ' . 'node.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . reset($aParams['entitiesExtendedJoinField']) : '' ) . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$aParams['sorting'] = $this->oDao->sPrimaryEntity . '.' . $this->oDao->sLeftColName;
		return $this->oDao->readData( $aParams );
	}

	public function setDao( $oDao ) {
		if( is_subclass_of($oDao, 'clDaoBaseSql') ) $this->oDao = $oDao;
	}

	public function updateNodePosition( $node, $iPosition, $aParams = array() ) {
		$iPosition++;
		$aResult = current( $this->oDao->readDataByPrimary($node, array(
			'fields' => array($this->oDao->sLeftColName, $this->oDao->sRightColName )
		)) );
		if( empty($aResult) ) return false;

		$iLeft = $aResult[$this->oDao->sLeftColName];
		$iRight = $aResult[$this->oDao->sRightColName];
		if( empty($aParams['criterias']) ) $aParams['criterias'] = '';
		$iResult = 0;

		$this->oDao->oDb->query( 'LOCK TABLE ' . $this->oDao->sPrimaryEntity . ' WRITE' );
		$iResult = $this->oDao->oDb->write( '
		UPDATE ' . $this->oDao->sPrimaryEntity . '
			SET ' . $this->oDao->sLeftColName . ' = ' . $this->oDao->sLeftColName . ' + CASE
				WHEN ' . $iPosition . ' < ' . $iLeft . '
					THEN CASE
						WHEN ' . $this->oDao->sLeftColName . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . '
							THEN ' . $iPosition . ' - ' . $iLeft . '
						WHEN ' . $this->oDao->sLeftColName . ' BETWEEN ' . $iPosition . ' AND ' . $iLeft . ' - 1
							THEN ' . $iRight . ' - ' . $iLeft . ' + 1
						ELSE 0
					END
				WHEN ' . $iPosition . ' > ' . $iRight . '
					THEN CASE
						WHEN ' . $this->oDao->sLeftColName . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . '
							THEN ' . $iPosition . ' - ' . $iRight . ' - 1
						WHEN ' . $this->oDao->sLeftColName . ' BETWEEN ' . $iRight . ' + 1 AND ' . $iPosition . ' - 1
							THEN ' . $iLeft . ' - ' . $iRight . ' - 1
						ELSE 0
					END
				ELSE 0
			END,
			' . $this->oDao->sRightColName . ' = ' . $this->oDao->sRightColName . ' + CASE
				WHEN ' . $iPosition . ' < ' . $iLeft . '
					THEN CASE
						WHEN ' . $this->oDao->sRightColName . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . '
							THEN ' . $iPosition . ' - ' . $iLeft . '
						WHEN ' . $this->oDao->sRightColName . ' BETWEEN ' . $iPosition . ' AND ' . $iLeft . ' - 1
							THEN ' . $iRight . ' - ' . $iLeft . ' + 1
						ELSE 0
					END
				WHEN ' . $iPosition . ' > ' . $iRight . '
					THEN CASE
						WHEN ' . $this->oDao->sRightColName . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . '
							THEN ' . $iPosition . ' - ' . $iRight . ' - 1
						WHEN ' . $this->oDao->sRightColName . ' BETWEEN ' . $iRight . ' + 1 AND ' . $iPosition . ' - 1
							THEN ' . $iLeft . ' - ' . $iRight . ' - 1
						ELSE 0
					END
				ELSE 0
			END' . $this->oDao->formatCriterias($aParams['criterias']) );
		$this->oDao->oDb->query( 'UNLOCK TABLES' );
		return $iResult;
	}

}
