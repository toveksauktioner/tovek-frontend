<?php

class clParentChildHelperDaoSql {

	private $aParams;
	private $oDao;

	public function __construct( clDaoBaseSql $oDao, $aParams = array() ) {
		$this->oDao = $oDao;
		$aParams += array(
			'childEntity' => null,
			'childPrimaryField' => 'childId',
			'childParentField' => 'childParentId',
			'childCreatedField' => 'childCreated',
			'parentEntity' => null,
			'parentPrimaryField' => null,
			'parentChildCountField' => null,
			'parentCreatedField' => null,
		);
		$this->aParams = $aParams;
	}

	public function createChild( $aData, $aParams ) {
		if( $this->aParams['childEntity'] === null ) return false;

		$aParams += array(
			'entities' => $this->aParams['childEntity']
		);
		$aData[$this->aParams['childCreatedField']] = date( 'Y-m-d H:i:s' );

		$result = $this->oDao->createData( $aData, $aParams );
		if( $result ) {
			$iLastId = $this->oDao->oDb->lastId();
			
			if( $this->aParams['childParentField'] !== null && !empty($aData[$this->aParams['childParentField']]) ) {
				$this->updateChildCount( $aData[$this->aParams['childParentField']] );			
			}
			
			return $iLastId;
		}

		return $result;
	}

	public function createParent( $aData, $aParams ) {
		if( $this->aParams['parentEntity'] === null ) return false;

		$aParams += array(
			'entities' => $this->aParams['parentEntity']
		);
		$aData[$this->aParams['parentCreatedField']] = date( 'Y-m-d H:i:s' );

		return $this->oDao->createData( $aData, $aParams );
	}

	public function deleteChild( $childId ) {
		if( $this->aParams['childEntity'] === null ) return false;

		$aParams = array(
			'entities' => $this->aParams['childEntity'],
			'withHelpers' => false
		);

		if( is_array($childId) ) {
			foreach( $childId as $key => $value ) {
				$childId[ $key ] = $this->oDao->oDb->escapeStr( $value );
			}
			$aParams['criterias'] = $this->aParams['childPrimaryField'] . ' IN(' . implode( ', ', $childId ) . ')';
		} else {
			$aParams['criterias'] = $this->aParams['childPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr($childId);
		}

		return $this->oDao->deleteData( $aParams );
	}

	public function deleteChildrenByParent( $parentId ) {
		if( $this->aParams['childEntity'] === null ) return false;

		$aParams = array(
			'entities' => $this->aParams['childEntity'],
			'withHelpers' => false
		);

		if( is_array($parentId) ) {
			foreach( $parentId as $key => $value ) {
				$parentId[ $key ] = $this->oDao->oDb->escapeStr( $value );
			}
			$aParams['criterias'] = $this->aParams['childParentField'] . ' IN(' . implode( ', ', $parentId ) . ')';
		} else {
			$aParams['criterias'] = $this->aParams['childParentField'] . ' = ' . $this->oDao->oDb->escapeStr($parentId);
		}
		
		return $this->oDao->deleteData( $aParams );
	}

	public function deleteParent( $parentId ) {
		if( $this->aParams['parentEntity'] === null ) return false;

		$aParams = array(
			'entities' => $this->aParams['parentEntity'],
			'withHelpers' => false
		);

		if( is_array($parentId) ) {
			foreach( $parentId as $key => $value ) {
				$parentId[ $key ] = $this->oDao->oDb->escapeStr( $value );
			}
			$aParams['criterias'] = $this->aParams['parentPrimaryField'] . ' IN(' . implode( ', ', $parentId ) . ')';
		} else {
			$aParams['criterias'] = $this->aParams['parentPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr($parentId);
		}

		return $this->oDao->deleteData( $aParams );
	}

	public function readChildren( $parentId, $aParams = array() ) {
		if( $this->aParams['childEntity'] === null ) return false;

		$aParams += array(
			'fields' => $this->oDao->aFieldsDefault,
			'childId' => null
		);
		
		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entities' => $this->aParams['childEntity'],
			'entitiesExtended' => isset($aParams['entitiesExtended']) ? $aParams['entitiesExtended'] : null,
			'countField' => $this->aParams['childPrimaryField']
		);
		
		$aCriterias = array();
		
		if( $parentId !== null ) {
			if( is_array($parentId) ) {
				foreach( $parentId as $key => $value ) {
					$parentId[ $key ] = $this->oDao->oDb->escapeStr( $value );
				}
				$aCriterias[] = $this->aParams['childParentField'] . ' IN(' . implode( ', ', $parentId ) . ')';
			} else {
				$aCriterias[] = $this->aParams['childParentField'] . ' = ' . $this->oDao->oDb->escapeStr( $parentId );
			}
		}

		if( $aParams['childId'] !== null ) {
			if( is_array($aParams['childId']) ) {
				foreach( $aParams['childId'] as $key => $value ) {
					$aParams['childId'][ $key ] = $this->oDao->oDb->escapeStr( $value );
				}
				$aCriterias[] = $this->aParams['childPrimaryField'] . ' IN(' . implode( ', ', $aParams['childId'] ) . ')';
			} else {
				$aCriterias[] = $this->aParams['childPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr( $aParams['childId'] );
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->oDao->readData( $aDaoParams );
	}

	public function readParents( $aParams = array() ) {
		if( $this->aParams['parentEntity'] === null ) return false;

		$aParams += array(
			'fields' => $this->oDao->aFieldsDefault,
			'parentId' => null
		);

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entities' => $this->aParams['parentEntity'],
			'entitiesExtended' => isset($aParams['entitiesExtended']) ? $aParams['entitiesExtended'] : null,
			'countField' => $this->aParams['parentPrimaryField']
		);
		$aCriterias = array();

		if( $aParams['parentId'] !== null ) {
			if( is_array($aParams['parentId']) ) {
				foreach( $aParams['parentId'] as $key => $value ) {
					$aParams['parentId'][ $key ] = $this->oDao->oDb->escapeStr( $value );
				}
				$aCriterias[] = $this->aParams['parentPrimaryField'] . ' IN(' . implode( ', ', $aParams['parentId'] ) . ')';
			} else {
				$aCriterias[] = $this->aParams['parentPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr( $aParams['parentId'] );
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->oDao->readData( $aDaoParams );
	}

	public function readRelations() {
		if( $this->aParams['parentEntity'] === null ) return false;

		$aDaoParams = array(
			'fields' => array(
				$this->aParams['childPrimaryField'],
				$this->aParams['childParentField']
			),
			'entities' => array(
				$this->aParams['childEntity']
			),
			'countField' => $this->aParams['parentPrimaryField']
		);
	
		return $this->oDao->readData( $aDaoParams );
	}

	public function updateChild( $childId, $aData, $aParams = array() ) {
		if( $this->aParams['childEntity'] === null ) return false;

		$aParams += array(
			'entities' => $this->aParams['childEntity'],
			'withHelpers' => false
		);
		$aCriterias = array();

		if( is_array($childId) ) {
			foreach( $childId as $key => $value ) {
				$childId[ $key ] = $this->oDao->oDb->escapeStr( $value );
			}
			$aCriterias[] = $this->aParams['childPrimaryField'] . ' IN(' . implode( ', ', $childId ) . ')';
		} else {
			$aCriterias[] = $this->aParams['childPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr( $childId );
		}

		if( !empty($aCriterias) ) $aParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->oDao->updateData( $aData, $aParams );
	}

	public function updateParent( $parentId, $aData, $aParams = array() ) {
		if( $this->aParams['parentEntity'] === null ) return false;

		$aParams += array(
			'entities' => $this->aParams['parentEntity'],
			'withHelpers' => false
		);
		$aCriterias = array();

		if( is_array($parentId) ) {
			foreach( $parentId as $key => $value ) {
				$parentId[ $key ] = $this->oDao->oDb->escapeStr( $value );
			}
			$aCriterias[] = $this->aParams['parentPrimaryField'] . ' IN(' . implode( ', ', $parentId ) . ')';
		} else {
			$aCriterias[] = $this->aParams['parentPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr( $parentId );
		}

		if( !empty($aCriterias) ) $aParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->oDao->updateData( $aData, $aParams );
	}

	public function updateChildCount( $iParentId ) {
		if(
			$this->aParams['childEntity'] === null ||
			$this->aParams['parentEntity'] === null ||
			$this->aParams['parentPrimaryField'] === null ||
			$this->aParams['parentChildCountField'] === null
		) return false;

		$aParams = array(
			'entities' => $this->aParams['parentEntity'],
			'dataEscape' => false,
			'validated' => true,
			'withHelpers' => false,
			'criterias' => $this->aParams['parentPrimaryField'] . ' = ' . $this->oDao->oDb->escapeStr( $iParentId )
		);

		$aData = array(
			$this->aParams['parentChildCountField'] => '(
				SELECT COUNT(' . $this->aParams['childPrimaryField'] . ')
				FROM ' . $this->aParams['childEntity'] . '
				WHERE ' . $this->aParams['childParentField'] . ' = ' . $this->oDao->oDb->escapeStr( $iParentId ) . '
			)'
		);

		return $this->oDao->updateData( $aData, $aParams );
	}

}