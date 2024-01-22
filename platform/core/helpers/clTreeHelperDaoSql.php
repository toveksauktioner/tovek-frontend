<?php

class clTreeHelperDaoSql {

	private $oDao;
	private $aParams = array();

	public function __construct( clDaoBaseSql $oDao, $aParams = array() ) {
    	$this->oDao = $oDao;

    	$aParams += array(
    		'categoryEntity' => null,
    		'categoryPrimaryField' => 'categoryId',
    		'categoryLeftField' => 'categoryLeft',
    		'categoryRightField' => 'categoryRight',
    		'categoryDefaultFields' => '*'
    	);

    	$this->aParams = $aParams;
    }

	public function createNode( $aData, $aParams = array() ) {
		if( empty($this->aParams['categoryEntity']) ) return false;
		
		$aParams += array(
			'entities' => $this->aParams['categoryEntity'],
			'position' => 0
		);
		
		$this->oDao->oDb->query( 'LOCK TABLE ' . $this->aParams['categoryEntity'] . ' WRITE' );
		$aParams['criterias'] = $this->aParams['categoryRightField'] . ' > ' . $aParams['position'] . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$sCriterias = !empty($aParams['dividerCriterias']) ? $aParams['criterias'] . ' AND ' . $aParams['dividerCriterias'] : $aParams['criterias'];
		$this->oDao->oDb->write( 'UPDATE ' . $this->aParams['categoryEntity'] . ' SET ' . $this->aParams['categoryRightField'] . ' = ' . $this->aParams['categoryRightField'] . ' + 2' . $this->oDao->formatCriterias($sCriterias) );
		
		$aParams['criterias'] = $this->aParams['categoryLeftField'] . ' > ' . $aParams['position'] . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$sCriterias = !empty($aParams['dividerCriterias']) ? $aParams['criterias'] . ' AND ' . $aParams['dividerCriterias'] : $aParams['criterias'];
		$this->oDao->oDb->write( 'UPDATE ' . $this->aParams['categoryEntity'] . ' SET ' . $this->aParams['categoryLeftField'] . ' = ' . $this->aParams['categoryLeftField'] . ' + 2' . $this->oDao->formatCriterias($sCriterias) );
		$this->oDao->oDb->query( 'UNLOCK TABLES' );
		
		$aData[$this->aParams['categoryLeftField']] = $aParams['position'] + 1;
		$aData[$this->aParams['categoryRightField']] = $aParams['position'] + 2;
		
		$aResult = $this->oDao->createData( $aData, $aParams );
		return $aResult;
	}

	public function deleteNode( $node, $aParams = array() ) {
		if( empty($this->aParams['categoryEntity']) ) return false;

		$aParams = array(
			'entities' => $this->aParams['categoryEntity']
		);

		$aResult = current( $this->readDataByPrimary( $node, array(
			'fields' => array($this->aParams['categoryLeftField'], $this->aParams['categoryRightField'] )
		)) );
		if( empty($aResult) ) return false;

		$iLeft = $aResult[$this->aParams['categoryLeftField']];
		$iRight = $aResult[$this->aParams['categoryRightField']];
		$iWidth = $iRight - $iLeft + 1;
		$iResult = 0;

		$aParams['criterias'] = $this->aParams['categoryLeftField'] . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$iResult = $this->oDao->deleteData( $aParams );

		$this->oDao->oDb->query( 'LOCK TABLE ' . $this->aParams['categoryEntity'] . ' WRITE' );
		$aParams['criterias'] = $this->aParams['categoryRightField'] . ' > ' . $iRight . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$this->oDao->oDb->write( 'UPDATE ' . $this->aParams['categoryEntity'] . ' SET ' . $this->aParams['categoryRightField'] . ' = ' . $this->aParams['categoryRightField'] . ' - ' . $iWidth . $this->oDao->formatCriterias($aParams['criterias']) );

		$aParams['criterias'] = $this->aParams['categoryLeftField'] . ' > ' . $iRight . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$this->oDao->oDb->write( 'UPDATE ' . $this->aParams['categoryEntity'] . ' SET ' . $this->aParams['categoryLeftField'] . ' = ' . $this->aParams['categoryLeftField'] . ' - ' . $iWidth . $this->oDao->formatCriterias($aParams['criterias']) );
		$this->oDao->oDb->query( 'UNLOCK TABLES' );

		return $iResult;
	}

	public function move( $categoryId, $aData ) {
		$targetNode = !empty( $aData['categoryTarget'] ) ? (int) $aData['categoryTarget'] : null;
		if( !empty($aData['categoryRelation']) ) $aParams['relation'] = $aData['categoryRelation'];

		$iPosition = $this->readNodePosition( $targetNode, $aParams );
		return $this->updateNodePosition( $categoryId, $iPosition, $aParams );
	}

	public function readDataByPrimary( $primary, $aParams = array() ) {
		if( empty($this->aParams['categoryEntity']) ) return false;

		$aParams += array(
			'entities' => $this->aParams['categoryEntity'],
		);
		if( !empty($aParams['criterias']) ) {
			$aParams['criterias'] .= ' AND ';
		} else {
			$aParams['criterias'] = '';
		}
		if( is_array($primary) ) {
			$aParams['criterias'] .= $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryPrimaryField'] . ' IN (' . implode( ', ', array_map(array($this->oDao->oDb, 'escapeStr'), $primary) ) . ')';
		} else {
			$aParams['criterias'] .= $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr( $primary );
		}

		return $this->oDao->readData( $aParams );
	}

	public function readLeafNodes( $aParams = array() ) {
		$aParams = array(
			'entities' => $this->aParams['categoryEntity'],
			'criterias' => $this->aParams['categoryRightField'] . ' = ' . $this->aParams['categoryLeftField'] . ' + 1' . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' )
		);
		return $this->oDao->readData( $aParams );
	}

	// TODO Read node count from subtree
	public function readNodeCount( $node = null ) {
		$aResult = $this->oDao->readData( array(
			'entities' => $this->aParams['categoryEntity'],
			'fields' => 'MAX(' . $this->aParams['categoryRightField'] . ') / 2'
		) );
		return (int) current( current($aResult) );
	}

	public function readNodePosition( $node, $aParams = array() ) {
		$aParams += array(
			'entities' => $this->aParams['categoryEntity'],
			'relation' => null
		);

		$iPosition = 0;
		if( empty($node) ) {
			if( $aParams['relation'] == 'lastChild' ){
				$aResult = $this->oDao->readData( array(
					'entities' => $this->aParams['categoryEntity'],
					'fields' => 'MAX(' . $this->aParams['categoryRightField'] . ')'
				) );
				$iPosition = (int) current( current($aResult) );
			}
		} else {
			switch( $aParams['relation'] ) {
				case 'prevSibling':
					$sFields = $this->aParams['categoryLeftField'] . ' - 1';
					break;
				case 'nextSibling':
					$sFields = $this->aParams['categoryRightField'];
					break;
				case 'lastChild':
					$sFields = $this->aParams['categoryRightField'] . ' - 1';
					break;
				case 'firstChild':
				default:
					$sFields = $this->aParams['categoryLeftField'];
					break;
			}

			$aResult = $this->readDataByPrimary( $node, array(
				'fields' => $sFields
			) + $aParams );
			$iPosition = (int) current( current($aResult) );
		}

		return $iPosition;
	}

	public function readWithChildren( $node, $aParams = array() ) {
		$aParams += array(
			'entities' => $this->aParams['categoryEntity'],
			'fields' => $this->aParams['categoryDefaultFields'],
			'maxDepth' => null,
			'minDepth' => null
		);
		$aParams['fields'] = (array) $aParams['fields'];

		$sFieldCount = '(COUNT(parent.' . $this->aParams['categoryPrimaryField'] . ') - 1)';

		$aParams['entitiesExtended'] = $this->aParams['categoryEntity'] . ' JOIN ' . $this->aParams['categoryEntity'] . ' AS parent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? ' ' . $this->aParams['categoryEntity'] . '.' . key($aParams['entitiesExtendedJoinField']) . ' = parent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryLeftField'] . ' BETWEEN parent.' . $this->aParams['categoryLeftField'] . ' AND parent.' . $this->aParams['categoryRightField'] . ( !empty($aParams['entitiesExtended']) ? ' LEFT JOIN ' . $aParams['entitiesExtended'] : '' );
		$aParams['groupBy'] = $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryPrimaryField'];
		$aParams['sorting'] = $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryLeftField'];

		if( empty($node) ) {
			if( !empty($aParams['entitiesExtendedJoinField']) ) {
				$aParams['criterias'] = $this->aParams['categoryEntity'] . '.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . reset($aParams['entitiesExtendedJoinField']) . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '');
			}
		} else {
			$aParams['entitiesExtended'] = $this->aParams['categoryEntity'] . '
			JOIN ' . $this->aParams['categoryEntity'] . ' AS parent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->aParams['categoryEntity'] . '.' . key($aParams['entitiesExtendedJoinField']) . ' = parent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryLeftField'] . ' BETWEEN parent.' . $this->aParams['categoryLeftField'] . ' AND parent.' . $this->aParams['categoryRightField'] . '
			JOIN ' . $this->aParams['categoryEntity'] . ' AS subParent ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->aParams['categoryEntity'] . '.' . key($aParams['entitiesExtendedJoinField']) . ' = subParent.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryLeftField'] . ' BETWEEN subParent.' . $this->aParams['categoryLeftField'] . ' AND subParent.' . $this->aParams['categoryRightField'] . '
			JOIN (
				SELECT ' . $sFieldCount . ' AS depth, ' . $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryPrimaryField'] . ( !empty($aParams['entitiesExtendedJoinField']) ? ', ' . $this->aParams['categoryEntity'] . '.' . key($aParams['entitiesExtendedJoinField']) : '' ) . '
				FROM ' . $aParams['entitiesExtended'] . '
				WHERE ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->aParams['categoryEntity'] . '.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . reset($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr( $node ) . '
				GROUP BY ' . ( !empty($aParams['entitiesExtendedJoinField']) ? $this->aParams['categoryEntity'] . '.' . key($aParams['entitiesExtendedJoinField']) . ', ' : '' ) . $aParams['groupBy'] . '
				ORDER BY ' . $aParams['sorting'] . '
			) AS subTree ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? 'subParent.' . key($aParams['entitiesExtendedJoinField']) . ' = subTree.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . 'subParent.' . $this->aParams['categoryPrimaryField'] . ' = subTree.' . $this->aParams['categoryPrimaryField'];
			$sFieldCount = '(COUNT(parent.' . $this->aParams['categoryPrimaryField'] . ') - (MAX(subTree.depth) + 1))';
		}

		foreach( $aParams['fields'] as $key => $value ) {
			if( !array_key_exists($value, $this->oDao->aDataDict[$this->aParams['categoryEntity']]) ) continue;
			$aParams['fields'][$key] = $this->aParams['categoryEntity'] . '.' . $value;
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
			'entities' => $this->aParams['categoryEntity'],
			'fields' => $this->aParams['categoryDefaultFields']
		);
		$aParams['fields'] = (array) $aParams['fields'];

		foreach( $aParams['fields'] as $key => $value ) {
			if( !array_key_exists($value, $this->oDao->aDataDict[$this->aParams['categoryEntity']]) ) continue;
			$aParams['fields'][$key] = $this->aParams['categoryEntity'] . '.' . $value;
		}

		$aParams['entitiesExtended'] = $this->aParams['categoryEntity'] . ' AS node JOIN ' . $this->aParams['categoryEntity'] . ' ON ' . ( !empty($aParams['entitiesExtendedJoinField']) ? ' ' . 'node.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . $this->aParams['categoryEntity'] . '.' . key($aParams['entitiesExtendedJoinField']) . ' AND ' : '' ) . 'node.' . $this->aParams['categoryLeftField'] . ' BETWEEN ' . $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryLeftField'] . ' AND ' . $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryRightField'] . ( !empty($aParams['entitiesExtended']) ? ' LEFT JOIN ' . $aParams['entitiesExtended'] : '' );
		$aParams['criterias'] = 'node.' . $this->aParams['categoryPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr( $node ) . ( !empty($aParams['entitiesExtendedJoinField']) ? ' AND ' . 'node.' . key($aParams['entitiesExtendedJoinField']) . ' = ' . reset($aParams['entitiesExtendedJoinField']) : '' ) . ( !empty($aParams['criterias']) ? ' AND ' . $aParams['criterias'] : '' );
		$aParams['sorting'] = $this->aParams['categoryEntity'] . '.' . $this->aParams['categoryLeftField'];
		return $this->oDao->readData( $aParams );
	}

	public function updateNodePosition( $node, $iPosition, $aParams = array() ) {
		$iPosition++;
		$aResult = current( $this->readDataByPrimary($node, array(
			'fields' => array($this->aParams['categoryLeftField'], $this->aParams['categoryRightField'] )
		)) );
		if( empty($aResult) ) return false;

		$iLeft = $aResult[$this->aParams['categoryLeftField']];
		$iRight = $aResult[$this->aParams['categoryRightField']];
		if( empty($aParams['criterias']) ) $aParams['criterias'] = '';
		$iResult = 0;

		$this->oDao->oDb->query( 'LOCK TABLE ' . $this->aParams['categoryEntity'] . ' WRITE' );
		$iResult = $this->oDao->oDb->write( '
		UPDATE ' . $this->aParams['categoryEntity'] . '
			SET ' . $this->aParams['categoryLeftField'] . ' = ' . $this->aParams['categoryLeftField'] . ' + CASE
				WHEN ' . $iPosition . ' < ' . $iLeft . '
					THEN CASE
						WHEN ' . $this->aParams['categoryLeftField'] . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . '
							THEN ' . $iPosition . ' - ' . $iLeft . '
						WHEN ' . $this->aParams['categoryLeftField'] . ' BETWEEN ' . $iPosition . ' AND ' . $iLeft . ' - 1
							THEN ' . $iRight . ' - ' . $iLeft . ' + 1
						ELSE 0
					END
				WHEN ' . $iPosition . ' > ' . $iRight . '
					THEN CASE
						WHEN ' . $this->aParams['categoryLeftField'] . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . '
							THEN ' . $iPosition . ' - ' . $iRight . ' - 1
						WHEN ' . $this->aParams['categoryLeftField'] . ' BETWEEN ' . $iRight . ' + 1 AND ' . $iPosition . ' - 1
							THEN ' . $iLeft . ' - ' . $iRight . ' - 1
						ELSE 0
					END
				ELSE 0
			END,
			' . $this->aParams['categoryRightField'] . ' = ' . $this->aParams['categoryRightField'] . ' + CASE
				WHEN ' . $iPosition . ' < ' . $iLeft . '
					THEN CASE
						WHEN ' . $this->aParams['categoryRightField'] . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . '
							THEN ' . $iPosition . ' - ' . $iLeft . '
						WHEN ' . $this->aParams['categoryRightField'] . ' BETWEEN ' . $iPosition . ' AND ' . $iLeft . ' - 1
							THEN ' . $iRight . ' - ' . $iLeft . ' + 1
						ELSE 0
					END
				WHEN ' . $iPosition . ' > ' . $iRight . '
					THEN CASE
						WHEN ' . $this->aParams['categoryRightField'] . ' BETWEEN ' . $iLeft . ' AND ' . $iRight . '
							THEN ' . $iPosition . ' - ' . $iRight . ' - 1
						WHEN ' . $this->aParams['categoryRightField'] . ' BETWEEN ' . $iRight . ' + 1 AND ' . $iPosition . ' - 1
							THEN ' . $iLeft . ' - ' . $iRight . ' - 1
						ELSE 0
					END
				ELSE 0
			END' . $this->oDao->formatCriterias($aParams['criterias']) );
		$this->oDao->oDb->query( 'UNLOCK TABLES' );
		return $iResult;
	}

}
