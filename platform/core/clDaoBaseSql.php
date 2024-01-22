<?php

require_once PATH_CORE . '/ifDaoBase.php';

abstract class clDaoBaseSql implements ifDaoBase {

	public $sModuleName;
	public $aDataDict = array();
	public $aDataFilters = array();
	public $aCombinedDataDict = array();
	public $aCurrentDataDict = array();
	public $aFieldsAmbiguous = array();
	public $aFieldsDefault = array();
	public $aHelpers = array();
	public $aSorting = array();
	public $bEntriesTotal = false;
	public $iLangId;
	public $iLastEntriesTotal;
	public $oDb;
	public $sCollation;
	public $sCriterias;
	public $sPrimaryEntity;
	public $sPrimaryField;

	protected $aData = array();
	protected $aEntities = array();
	protected $aValidationEntities = array();
	protected $aFields = array();
	protected $iEntries;
	protected $iOffset;
	protected $result;

	protected function init() {
		if( empty($this->sPrimaryEntity) ) $this->sPrimaryEntity = key( $this->aDataDict );
		if( empty($this->aEntities) ) $this->aEntities = array_keys( $this->aDataDict );
		if( !isset($this->aDataFilters['input']) ) $this->aDataFilters['input'] = array();
		if( !isset($this->aDataFilters['output']) ) $this->aDataFilters['output'] = array();
		
		$this->oDb = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );
		if( !$this->oDb ) throw new Exception( _('No database object was found') );
		clFactory::loadClassFile( 'clDataValidation' );
		
		$this->sCollation = $GLOBALS['mysqlCollation'];
		$this->setLang( $GLOBALS['langId'] );
	}
	
	public function switchToPrimary() {
		$this->oDb = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );
	}
	
	public function switchToSecondary() {
		$this->oDb = clRegistry::get( 'clDbPdoSecondary' );
	}
	
	public function addEntityToField( $aFields, $aExcludedFields, $sEntity = null ) {
		if( $sEntity === null ) $sEntity = $this->sPrimaryEntity;
		$aFields = (array) $aFields;
		foreach( $aFields as $key => $sField ) {
			if( !in_array($sField, $aExcludedFields) ) $aFields[$key] = $sEntity . '.' . $sField;
		}
		return $aFields;
	}

	public function combineDataDict() {
		if( !empty($this->aCombinedDataDict) ) return $this->aCombinedDataDict;
		foreach( array_intersect_key($this->aDataDict, array_flip($this->aEntities)) as $sEntity => $value ) {
			$this->aCombinedDataDict += $value;
		}
		return $this->aCombinedDataDict;
	}

	/**
	 * @return int Number of inserted rows
	 */
	public function createData( $aData, $aParams = array() ) {
		if( empty($aData) ) return;

		$aParams += array(
			'withHelpers' => true
		);

		if( !empty($this->aHelpers) && $aParams['withHelpers'] !== false ) {
			$aResult = $this->executeHelpers( 'createData', $aParams, $aData );
			if( !empty($aResult['aData']) ) $aData = $aResult['aData'];
			if( !empty($aResult['aParams']) ) $aParams = $aResult['aParams'];
			unset( $aResult );
		}

		$aParams += array(
			'entities' => $this->sPrimaryEntity,
			'entitiesExtended' => null,
			'validated' => false,
			'dataEscape' => true,
			'groupKey' => null
		);

		$this->setEntities( $aParams['entities'] );
		$sEntities = $aParams['entitiesExtended'] !== null ? $aParams['entitiesExtended'] : implode( ', ', $this->aEntities );

		$this->setData( $aData );

		if( !$aParams['validated'] && $aParams['dataEscape'] ) {
			$this->aValidationEntities = $this->aEntities;
			$aValidationParams = array(
				'errGroup' => $aParams['groupKey']
			);
			if( !$this->validateData($aValidationParams) ) return false;
		}

		$this->preCreateData();
		$this->result = $this->oDb->write( 'INSERT INTO ' . $sEntities . ' SET ' . $this->formatData( $aParams['dataEscape'] ) );
		$this->postCreateData();

		return $this->result;
	}

	/**
	 *
	 * @return
	 * @param object $aData
	 * @param object $aEntities
	 * array(
	 * 		'entUser' => '',
	 *		'entUserInfo' => array(
	 *			'fromEntity' => 'entUser',
	 *			'field' => 'infoUserId'
	 *		)
	 * )
	 */
	public function createDataTransaction( $aData, $aEntities, $aParams = array() ) {
		if( empty($aData) ) return;
		$aParams += array(
			'groupKey' => null
		);

		$this->aEntities = array_keys( $aEntities );
		$this->aValidationEntities = $this->aEntities;
		$this->setData( $aData );
		$aLastIds = array();

		$aValidationParams = array(
			'errGroup' => $aParams['groupKey']
		);
		if( !$this->validateData($aValidationParams) ) return false;

		foreach( $this->aValidationEntities as $sEntity ) {
			$aParams = array(
				'entities' => $sEntity,
				'validated' => true
			);

			if( isset($aEntities[$sEntity]['fromEntity']) && isset($aLastIds[$aEntities[$sEntity]['fromEntity']]) ) {
				$aData[$aEntities[$sEntity]['field']] = $aLastIds[$aEntities[$sEntity]['fromEntity']];
			}

			if( !$this->createData($aData, $aParams) ) return false;
			$aLastIds[$sEntity] = $this->readLastId();
		}
		return $aLastIds;
	}

	/**
	 *
	 * @return
	 * @param object $aData
	 * array(
	 * 		$aDataEntry01,
	 *		$aDataEntry02
	 * )
	 * @param object $aParams[optional]
	 */
	public function createMultipleData( $aData, $aParams = array() ) {
		if( empty($aData) ) return;
		
		$aParams += array(
			'withHelpers' => true
		);

		if( !empty($this->aHelpers) && $aParams['withHelpers'] !== false ) {
			$aResult = $this->executeHelpers( 'createData', $aParams, $aData );
			if( !empty($aResult['aData']) ) $aData = $aResult['aData'];
			if( !empty($aResult['aParams']) ) $aParams = $aResult['aParams'];
			unset( $aResult );
		}

		$aParams += array(
			'entities' => $this->sPrimaryEntity,
			'entitiesExtended' => null,
			'fields' => array(),
			'validated' => false,
			'dataEscape' => true,
			'groupKey' => null
		);

		$this->aFields = $aParams['fields'];
		$this->setEntities( $aParams['entities'] );
		$sEntities = $aParams['entitiesExtended'] !== null ? $aParams['entitiesExtended'] : implode( ', ', $this->aEntities );

		// Validate data
		$aDataTmp = array();
		foreach( $aData as $aDataEntry ) {
			$this->setData( array_combine($aParams['fields'], $aDataEntry) );
			if( !$aParams['validated'] && $aParams['dataEscape'] ) {
				$this->aValidationEntities = $this->aEntities;
				$aValidationParams = array(
					'errGroup' => $aParams['groupKey']
				);
				if( !$this->validateData($aValidationParams) ) return false;
			}

			if( $aParams['dataEscape'] ) $aDataEntry = array_map( array($this->oDb, 'escapeStr'), $this->aData );
			$aDataTmp[] = '(' . implode( ', ', $aDataEntry ) . ')';
		}

		return $this->oDb->write( 'INSERT INTO ' . $sEntities . ' (' . $this->formatFields() . ') VALUES ' . implode(', ', $aDataTmp) );
	}
	
	public function decrementEntity( $sEntity, $mPrimary, $iValue = 1 ) {
		$aData = array(
			$sEntity => '(' . $sEntity . '-1)'
		);
		
		$aParams = array(
			'dataEscape' => false
		);
		
		if( is_array($mPrimary) ) {
			$aParams['criterias'] = $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' IN (' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $mPrimary) ) . ')';
		} else {
			$aParams['criterias'] = $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' = ' . $this->oDb->escapeStr( $mPrimary );
		}
		
		return $this->updateData( $aData, $aParams );
	}

	/**
	 * @return Boolean
	 */
	public function deleteData( $aParams = array() ) {

		$aParams += array(
			'withHelpers' => true
		);

		if( !empty($this->aHelpers) && $aParams['withHelpers'] !== false ) {
			$aParams = $this->executeHelpers( 'deleteData', $aParams );
		}

		$aParams += array(
			'criterias' => null,
			'entities' => $this->sPrimaryEntity,
			'entitiesExtended' => null,
			'entitiesToDelete' => null,
			'entries' => null,
			'sorting' => null
		);

		$this->setEntities( $aParams['entities'] );
		$sEntities = $aParams['entitiesExtended'] !== null ? $aParams['entitiesExtended'] : implode( ', ', $this->aEntities );
		
		$this->preDeleteData();
		$this->result = $this->oDb->write(
			'DELETE' . ( $aParams['entitiesToDelete'] !== null ? ' ' . $aParams['entitiesToDelete'] : '' ) . ' FROM ' . $sEntities .
			$this->formatCriterias( $aParams['criterias'] ) .
			$this->formatSorting( $aParams['sorting'] ) .
			( $aParams['entries'] !== null ? ' LIMIT ' . $aParams['entries'] : $this->formatEntries() )
		);
		$this->postDeleteData();

		return $this->result;
	}

	/**
	 * @return Boolean
	 */
	public function deleteDataByPrimary( $primary, $aParams = array() ) {
		
		$aParams += array(
			'withHelpers' => true
		);

		if( !empty($this->aHelpers) && $aParams['withHelpers'] !== false ) {
			$aParams = $this->executeHelpers( 'deleteDataByPrimary', $aParams, null, $primary );
		}
		
		if( !empty($aParams['criterias']) ) {
			$aParams['criterias'] .= ' AND ';
		} else {
			$aParams['criterias'] = '';
		}
		if( is_array($primary) ) {
			$aParams['criterias'] .= $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' IN (' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $primary) ) . ')';
		} else {
			$aParams['criterias'] .= $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' = ' . $this->oDb->escapeStr( $primary );
		}

		return $this->deleteData( $aParams );
	}

	public function executeHelpers( $sMethod, $aParams, $aData = array(), $mPrimaryId = null ) {

		foreach( $this->aHelpers as $sClass => $oObject ) {
			if( method_exists($oObject, $sMethod) ) {
				$aResult = $oObject->$sMethod( $aParams, $aData, $mPrimaryId );
				if( !empty($aResult) )$aParams = $aResult;
			}
		}
		
		if( empty($aResult) ) {
			if( !empty($aData) ) {
				if( $sMethod == 'updateData' ) {
					// Args is reversed in updateData
					$aResult = array(
						'aParams' => $aData,
						'aData' => $aParams
					);
				} else {
					$aResult = array(
						'aParams' => $aParams,
						'aData' => $aData
					);
				}
			} else {
				$aResult = $aParams;
			}
			
		}
		return $aResult;
	}

	public function filterInputData( $aDataFilters ) {
		if( empty($this->aData) ) return;

		$aDataFilters = array_intersect_key( $aDataFilters, $this->aData );
		foreach( $aDataFilters as $key => $value ) {
			$value['aParams'] = (array) $value['aParams'];
			$aSelfKey = array_search( '_self_', $value['aParams'] );
			if( $aSelfKey !== false ) $value['aParams'][$aSelfKey] = $this->aData[$key];
			if( !empty($value['sClass']) ) {
				$this->aData[$key] = call_user_func_array( array($value['sClass'], $value['sFunction']), $value['aParams'] );
			} else {
				$this->aData[$key] = call_user_func_array( $value['sFunction'], $value['aParams'] );
			}
		}
		reset( $this->aData );
	}

	public function filterOutputData( $aDataFilters ) {
		if( empty($this->result) ) return;
		$aDataFilters = array_intersect_key( $aDataFilters, current($this->result) );
		foreach( $this->result as $key => $entry ) {
			foreach( $aDataFilters as $sFilterKey => $filterData ) {
				if( array_key_exists($sFilterKey, $entry) ) {
					$filterData['aParams'] = (array) $filterData['aParams'];
					$aSelfKey = array_search( '_self_', $filterData['aParams'] );
					if( $aSelfKey !== false ) $filterData['aParams'][$aSelfKey] = $this->result[$key][$sFilterKey];					
					if( !empty($filterData['sClass']) ) {
						$this->result[$key][$sFilterKey] = call_user_func_array( array($filterData['sClass'], $filterData['sFunction']), $filterData['aParams'] );
					} else {
						$this->result[$key][$sFilterKey] = call_user_func_array( $filterData['sFunction'], $filterData['aParams'] );
					}
				}
			}
		}
		reset($this->result);
	}

	public function formatCriterias( $sCriterias = '' ) {
		if( empty($sCriterias) && empty($this->sCriterias) ) return;
		return ' WHERE ' . $sCriterias . ( (!empty($sCriterias) && !empty($this->sCriterias)) ? ' AND ' . $this->sCriterias : $this->sCriterias );
	}

	/**
	 * @param array $aData An array with keys and values
	 */
	protected function formatData( $bEscape = true ) {
		$aDataTmp = array();

		foreach( $this->aData as $key => $value ) {
			$aDataTmp[$key] = $key . ' = ' . ( $bEscape ? $this->oDb->escapeStr($value) : $value );
		}

		return implode( ', ', $aDataTmp );
	}

	protected function formatDataFloat() {
		if( nl_langinfo(RADIXCHAR) !== '.' ) {
			$aDataDict = $this->combineDataDict();
			foreach( $this->aData as $key => $value ) {
				if( array_key_exists($key, $aDataDict) && $aDataDict[$key]['type'] === 'float' ) {
					$this->aData[$key] = str_replace( ',', '.', $value );
				}
			}
		}
	}

	protected function formatEntries() {
		return !empty($this->iEntries) ? ' LIMIT ' . ( !empty($this->iOffset) ? $this->iOffset . ', ' : '' ) . $this->iEntries : '';
	}

	protected function formatFields() {
		if( empty($this->aFields) ) {
			$this->aFields = !empty( $this->aFieldsDefault ) ? $this->aFieldsDefault : '*';
		}
		$this->aFields = (array) $this->aFields;
		foreach( $this->aFields as $key => $value ) {
			if( !ctype_digit((string) $key) ) $this->aFields[$key] = $value . ' AS ' . $key;
		}
		return implode( ', ', $this->aFields );
	}

	protected function formatSorting( $aSorting = array() ) {
		$aSorting = (array) $aSorting + (array) $this->aSorting;
		foreach( $aSorting as $sField => $value ) {
			if( is_array($value) ) {
				$aSorting[$sField] = $sField . ' ' . ( !empty($value['collation']) ? 'COLLATE ' . $value['collation'] . ' ' : '' ) . ( !empty($value['direction']) ? $value['direction'] : '' );
			} else {
				$aSorting[$sField] = ( !is_numeric($sField) ? $sField . ' ' : '' ) . $value;
			}
		}
		return !empty( $aSorting ) ? ' ORDER BY ' . implode( ', ', $aSorting ) : '';
	}

	public function getDataDict( $entity = null ) {
		if( $entity === null ) return $this->aDataDict;

		return array_intersect_key( $this->aDataDict, array_flip((array) $entity) );
	}

	public function lastId() {
		return $this->oDb->lastId();
	}
	
	public function incrementEntity( $sEntity, $mPrimary, $iValue = 1 ) {
		$aData = array(
			$sEntity => '(' . $sEntity . '+1)'
		);
		
		$aParams = array(
			'dataEscape' => false
		);
		
		if( is_array($mPrimary) ) {
			$aParams['criterias'] = $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' IN (' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $mPrimary) ) . ')';
		} else {
			$aParams['criterias'] = $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' = ' . $this->oDb->escapeStr( $mPrimary );
		}
		
		return $this->updateData( $aData, $aParams );
	}

	/**
	 * Add filter function
	 */
	protected function postCreateData() {}
	protected function postDeleteData() {}
	protected function postReadData() {}
	protected function postUpdateData() {}

	protected function preCreateData() {}
	protected function preDeleteData() {}
	protected function preReadData() {}
	protected function preUpdateData() {}
	
	/**
	 * $param array $aParams
	 */
	public function readData( $aParams = array() ) {
		
		$aParams += array(
			'withHelpers' => true
		);

		if( !empty($this->aHelpers) && $aParams['withHelpers'] !== false ) {
			$aParams = $this->executeHelpers( 'readData', $aParams );
		}

		$aParams += array(
			'count' => $this->bEntriesTotal,
			'countField' => null,
			'criterias' => null,
			'entities' => $this->sPrimaryEntity,
			'entitiesExtended' => null,
			'entries' => null,
			'fields' => array(),
			'groupBy' => null,
			'sorting' => null
		);
		
		$this->aFields = $aParams['fields'];
		$this->setEntities( $aParams['entities'] );
		$sEntities = $aParams['entitiesExtended'] !== null ? $aParams['entitiesExtended'] : implode( ', ', $this->aEntities );

		if( $aParams['count'] ) $this->iLastEntriesTotal = $this->readEntriesTotal( $aParams );

		$this->preReadData();
		$this->result = $this->oDb->query(
			'SELECT ' . $this->formatFields() . ' FROM ' . $sEntities .
			$this->formatCriterias( $aParams['criterias'] ) .
			( $aParams['groupBy'] !== null ? ' GROUP BY ' . $aParams['groupBy'] : '' ) .
			$this->formatSorting( $aParams['sorting'] ) .
			( $aParams['entries'] !== null ? ' LIMIT ' . $aParams['entries'] : $this->formatEntries() )
		);
		$this->postReadData();

		if( !empty($this->aDataFilters['output']) ) $this->filterOutputData( $this->aDataFilters['output'] );

		return $this->result;
	}

	public function readDataByPrimary( $primary, $aParams = array() ) {
		if( !empty($aParams['criterias']) ) {
			$aParams['criterias'] .= ' AND ';
		} else {
			$aParams['criterias'] = '';
		}
		if( is_array($primary) ) {
			$aParams['criterias'] .= $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' IN (' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $primary) ) . ')';
		} else {
			$aParams['criterias'] .= $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' = ' . $this->oDb->escapeStr( $primary );
		}

		return $this->readData( $aParams );
	}

	public function readLastId() {
		return $this->oDb->lastId();
	}

	public function readHighestId( $sField = null, $sEntity = null ) {
		if( $sField === null ) $sField = $this->sPrimaryField;
		if( $sEntity === null ) $sEntity = $this->sPrimaryEntity;
		$iCurrentHighestId = current( current($this->oDb->query('SELECT MAX(' . $sField . ') FROM ' . $sEntity)) );
		if( empty($iCurrentHighestId) ) $iCurrentHighestId = 0;
		return $iCurrentHighestId;
	}

	public function readNextId( $sField = null, $sEntity = null ) {
		if( $sField === null ) $sField = $this->sPrimaryField;
		if( $sEntity === null ) $sEntity = $this->sPrimaryEntity;
		$iNextId = current( current($this->oDb->query('SELECT MAX(' . $sField . ') + 1 FROM ' . $sEntity)) );
		if( empty($iNextId) ) $iNextId = 1;
		return $iNextId;
	}

	public function readEntriesTotal( $aParams = array() ) {
		
		$aParams += array(
			'withHelpers' => true
		);

		if( !empty($this->aHelpers) && $aParams['withHelpers'] !== false ) {
			$aParams = $this->executeHelpers( 'readEntriesTotal', $aParams );
		}

		$aParams += array(
			'countField' => null,
			'criterias' => null,
			'entities' => $this->sPrimaryEntity,
			'entitiesExtended' => null,
			'entries' => null,
			'groupBy' => null
		);

		$aParams['fields'] = '*';

		$aParams['entities'] = (array) $aParams['entities'];
		$sEntities = $aParams['entitiesExtended'] !== null ? $aParams['entitiesExtended'] : implode( ', ', $aParams['entities'] );
		if( $aParams['countField'] !== null ) {
			$aParams['fields'] = 'COUNT(' . $aParams['countField'] . ')';
		} else {
			if( $aParams['criterias'] !== null ) $aParams['fields'] = $this->sPrimaryEntity . '.' . $this->sPrimaryField;
			$aParams['fields'] = ( $aParams['groupBy'] !== null && $aParams['fields'] != '*' ) ? 'COUNT(DISTINCT(' . $aParams['fields'] . '))' : 'COUNT(' . $aParams['fields'] . ')';
		}

		return (int) current( current($this->oDb->query(
			'SELECT ' . $aParams['fields'] . ' FROM ' . $sEntities .
			$this->formatCriterias( $aParams['criterias'] )
			//. ( $aParams['groupBy'] !== null ? ' GROUP BY ' . $aParams['groupBy'] : '' )
		)) );
	}

	public function setCriterias( $aCriterias = array(), $sOuterJoinType = 'AND', $sInnerJoinType = 'OR' ) {
		if( empty($aCriterias) ) {
			$this->sCriterias = '';
			return;
		}
		
		// To be more tested before permanent implemented (uncommented)
		//if( !empty($this->aHelpers) ) {
		//	$aCriterias = $this->executeHelpers( 'setCriterias', $aCriterias );
		//}
		
		$aDaoCriterias = array();
		foreach( (array) $aCriterias as $aCriteria ) {
			$aCriteria['fields'] = (array) $aCriteria['fields'];
			if( !isset($aCriteria['type']) ) $aCriteria['type'] = '';
			$sJoinType = !empty($aCriteria['joinType']) ? ' ' . $aCriteria['joinType'] . '' : ' ' . $sInnerJoinType . ' ';
			
			switch( $aCriteria['type'] ) {
				case 'between':
					foreach( $aCriteria['fields'] as $key => $sField ) {
						$aCriteria['fields'][$key] = $sField . ' BETWEEN ' . (int) $aCriteria['value'] . ' AND ' . (int) $aCriteria['value2'];
					}					
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'fulltext':
					$aDaoCriterias[] = 'MATCH(' . implode( ', ', $aCriteria['fields'] ) . ') AGAINST (' . $this->oDb->escapeStr( $aCriteria['value'] ) . ' IN BOOLEAN MODE)';
					break;
				case 'like':
					$aCriteria['value'] = $this->oDb->escapeStr( '%' . $aCriteria['value'] . '%' );
					foreach( $aCriteria['fields'] as $key => $sField ) {
						$aCriteria['fields'][$key] = $sField . ' LIKE ' . $aCriteria['value'];
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'notLike':
					$aCriteria['value'] = $this->oDb->escapeStr( '%' . $aCriteria['value'] . '%' );
					foreach( $aCriteria['fields'] as $key => $sField ) {
						$aCriteria['fields'][$key] = $sField . ' NOT LIKE ' . $aCriteria['value'];
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'in':
					foreach( $aCriteria['fields'] as $key => $sField ) {
						if( is_array($aCriteria['value']) ) {
							$aCriteria['fields'][$key] = $sField . ' IN ("' . implode( '", "', $aCriteria['value'] ) . '")';
						} else {
							$aCriteria['fields'][$key] = $sField . ' IN ("' . $aCriteria['value'] . '")';
						}
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'notIn':
					foreach( $aCriteria['fields'] as $key => $sField ) {
						if( is_array($aCriteria['value']) ) {
							$aCriteria['fields'][$key] = $sField . ' NOT IN ("' . implode( '", "', $aCriteria['value'] ) . '")';
						} else {
							$aCriteria['fields'][$key] = $sField . ' NOT IN ("' . $aCriteria['value'] . '")';
						}
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'not':
					$aCriteria['value'] = $this->oDb->escapeStr( $aCriteria['value'] );
					foreach( $aCriteria['fields'] as $key => $sField ) {
						$aCriteria['fields'][$key] = $sField . ' != ' . $aCriteria['value'];
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'isNull':
					foreach( $aCriteria['fields'] as $key => $sField ) {
						$aCriteria['fields'][$key] = $sField . ' IS NULL';
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'isNotNull':
					foreach( $aCriteria['fields'] as $key => $sField ) {
						$aCriteria['fields'][$key] = $sField . ' IS NOT NULL';
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'equalsOrGreaterThan':
				case '>=':
					foreach( $aCriteria['fields'] as $key => $sField ) {
						if( is_array($aCriteria['value']) ) {
							$aCriteria['fields'][$key] = $sField . ' >= ("' . implode( '", "', $aCriteria['value'] ) . '")';
						} else {
							$aCriteria['fields'][$key] = $sField . ' >= ("' . $aCriteria['value'] . '")';
						}
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'equalsOrLessThan':
				case '<=':
					foreach( $aCriteria['fields'] as $key => $sField ) {
						if( is_array($aCriteria['value']) ) {
							$aCriteria['fields'][$key] = $sField . ' <= ("' . implode( '", "', $aCriteria['value'] ) . '")';
						} else {
							$aCriteria['fields'][$key] = $sField . ' <= ("' . $aCriteria['value'] . '")';
						}
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
					break;
				case 'equals':
				case '=':
				default:
					$aCriteria['value'] = $this->oDb->escapeStr( $aCriteria['value'] );
					foreach( $aCriteria['fields'] as $key => $sField ) {
						$aCriteria['fields'][$key] = $sField . ' = ' . $aCriteria['value'];
					}
					$aDaoCriterias[] = '(' . implode( $sJoinType, $aCriteria['fields'] ) . ')';
			}
		}
		$this->sCriterias = ( !empty($this->sCriterias) ? $this->sCriterias . ' AND ' : '' ) . '(' . implode( ' ' . $sOuterJoinType . ' ', $aDaoCriterias ) . ')';
	}

	public function setData( $aData ) {
		$aDataDict = array();
		foreach( $this->aEntities as $sEntity ) {
			if( !empty($this->aDataDict[$sEntity]) ) $aDataDict += $this->aDataDict[$sEntity];
		}
		$aData = array_intersect_key( (array) $aData, $aDataDict );

		// Process HTML
		$aHtmlDataKeys = array();
		foreach( $aData as $key => $value ) {
			if( empty($aDataDict[$key]['type']) || $aDataDict[$key]['type'] !== 'string' ) continue;

			if( empty($aDataDict[$key]['allowHtml']) ) {
				//$aData[$key] = strip_tags( $value );
			} else {
				$aHtmlDataKeys[] = $key;
			}
		}
		if( !empty($aHtmlDataKeys) ) {
			$oHtmlPurifier = clRegistry::get( 'clHtmlPurifier', PATH_CORE . '/htmlpurifier' );

			foreach( $aHtmlDataKeys as $sKey) {
				$aParams = array();
				if( !empty($aDataDict[$sKey]['allowHtml']['allowed']) ) $aParams['allowed'] = $aDataDict[$sKey]['allowHtml']['allowed'];
				$oHtmlPurifier->setConfig( $aParams );
				$aData[$sKey] = $oHtmlPurifier->purify( $aData[$sKey] );
			}
		}

		$this->aData = $aData;

		if( !empty($this->aDataFilters['input']) ) $this->filterInputData( $this->aDataFilters['input'] );
		$this->formatDataFloat();
	}

	public function setEntities( $aEntities ) {
		$this->aEntities = (array) $aEntities;
		$this->aCombinedDataDict = array();
	}

	public function setEntries( $iEntries, $iOffset = 0 ) {
		$this->iEntries = (int) $iEntries;
		$this->iOffset = (int) $iOffset;
	}

	public function setLang( $iLangId ) {
		$this->iLangId = (int) $iLangId;
	}

	/**
	 * @return Boolean
	 */
	public function updateData( $aData, $aParams = array() ) {
		if( empty($aData) ) return;

		$aParams += array(
			'withHelpers' => true
		);

		if( !empty($this->aHelpers) && $aParams['withHelpers'] !== false ) {
			$aResult = $this->executeHelpers( 'updateData', $aData, $aParams );
			if( !empty($aResult['aData']) ) $aData = $aResult['aData'];
			if( !empty($aResult['aParams']) ) $aParams = $aResult['aParams'];
			unset( $aResult );
		}

		$aParams += array(
			'criterias' => null,
			'entities' => $this->sPrimaryEntity,
			'entitiesExtended' => null,
			'validated' => null,
			'dataEscape' => true,
			'groupKey' => null
		);
		
		$this->setEntities( $aParams['entities'] );
		$sEntities = $aParams['entitiesExtended'] !== null ? $aParams['entitiesExtended'] : implode( ', ', $this->aEntities );

		$this->setData( $aData );
		if( !$aParams['validated'] && $aParams['dataEscape'] ) {
			$this->aValidationEntities = $this->aEntities;
			$aValidationParams = array(
				'errGroup' => $aParams['groupKey'],
				'partialDataDict' => true
			);
			if( !$this->validateData($aValidationParams) ) return false;
		}

		$this->preUpdateData();
		$this->result = $this->oDb->write( 'UPDATE ' . $sEntities . ' SET ' . $this->formatData($aParams['dataEscape']) .
			$this->formatCriterias( $aParams['criterias'] )
		);
		$this->postUpdateData();

		return $this->result;
	}

	public function updateDataByPrimary( $primary, $aData, $aParams = array() ) {
		if( empty($aData) ) return;
		if( !empty($aParams['criterias']) ) {
			$aParams['criterias'] .= ' AND ';
		} else {
			$aParams['criterias'] = '';
		}
		if( is_array($primary) ) {
			$aParams['criterias'] .= $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' IN (' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $primary) ) . ')';
		} else {
			$aParams['criterias'] .= $this->sPrimaryEntity . '.' . $this->sPrimaryField . ' = ' . $this->oDb->escapeStr( $primary );
		}

		return $this->updateData( $aData, $aParams );
	}

	public function upsert( $primary, $aData, $aParams = array() ) {
		$aResult = $this->readDataByPrimary( $primary, $aParams );
		if( empty($aResult) ) return $this->createData( $aData, $aParams );
		return $this->updateDataByPrimary( $primary, $aData, $aParams );
	}

	/**
	 * @return Boolean
	 */
	public function validateData( $aParams = array() ) {
		$aErr = array();

		$aDataDict = array_intersect_key( $this->aDataDict, array_flip($this->aValidationEntities) );
		$aErr = clDataValidation::validate( $this->aData, $aDataDict, $aParams );

		if( !empty($aErr) ) {
			clErrorHandler::setValidationError( $aErr );
			return false;
		}
		return true;
	}
	
	/**
	 * @return Array
	 */
	public function getTableInfo( $sEntity ) {
		$this->result = $this->oDb->query( '
			SELECT * 
			FROM information_schema.tables
			WHERE table_schema = ' . $this->oDb->escapeStr( DB_DATABASE ) . ' 
			AND table_name = ' . $this->oDb->escapeStr( $sEntity ) . '
			LIMIT 1;' );
		return $this->result;
	}
	
}
