<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

abstract class clTextDaoBaseSql extends clDaoBaseSql {

	protected $aEntityAlias = array();
	protected $aSelectedTextFields = array();
	protected $aTextFieldsWithEntities = array();

	public function init() {
    	parent::init();

		$this->aTextFields = (array) $this->aTextFields;
		$iCount = 1;
		foreach( $this->aTextFields as $sField ) {
			$this->aEntityAlias[$sField] = 'text' . $iCount;
			$this->aEntityAlias[$this->sPrimaryEntity . '.' . $sField] = 'text' . $iCount;
			$this->aTextFields[$sField] = $this->sPrimaryEntity . '.' . $sField;
			$this->aTextFieldsWithEntities[$this->sPrimaryEntity . '.' . $sField] = $sField;
			$iCount++;
		}
		if( !empty($this->sTextEntity) ) $this->aDataDict[$this->sTextEntity] = array(
			'textId' => array(
				'type' => 'integer',
				'index' => true,
			),
			'textLangId' => array(
				'type' => 'integer',
				'index' => true,
			),
			'textContent' => array(
				'title' => _( 'Content' ),
				'type' => 'string'
			)
		);
    }

	public function createData( $aData, $aParams = array() ) {

		$aParams += array(
			'entities' => $this->sPrimaryEntity,
			'validated' => false,
			'dataEscape' => true,
			'groupKey' => null
		);

		$this->aEntities = (array) $aParams['entities'];

		$this->setData( $aData );

		foreach( $this->aTextFields as $sField ) {
			$this->aDataDict[$this->sPrimaryEntity][$sField]['type'] = 'string';
		}

		if( !$aParams['validated'] && $aParams['dataEscape'] ) {
			$this->aValidationEntities = $this->aEntities;
			$aValidationParams = array(
				'errGroup' => $aParams['groupKey']
			);
			if( !$this->validateData($aValidationParams) ) return false;
			$aParams['validated'] = true;
		}

		foreach( $this->aTextFields as $sField ) {
			if( array_key_exists($sField, $aData) ) $aData[$sField] = $this->createText( $aData[$sField]);
			$this->aDataDict[$this->sPrimaryEntity][$sField]['type'] = 'integer';
		}

		return parent::createData( $aData, $aParams );
	}

	public function createText( $sText, $iTextId = null ) {
		if( $iTextId === null ) $iTextId = $this->readNextId( 'textId', $this->sTextEntity );
		$aParams = array(
			'entities' => $this->sTextEntity
		);
		$aData = array(
			'textId' => $iTextId,
			'textContent' => $sText,
			'textLangId' => $this->iLangId
		);
		return parent::createData($aData, $aParams) ? $iTextId : false;
	}

	public function deleteData( $aParams = array() ) {
		$aParams['entities'] = array_unique( array_merge((!empty($aParams['entities']) ? (array) $aParams['entities'] : array($this->sPrimaryEntity)), !empty($this->aEntityAlias) ? $this->aEntityAlias : array($this->sTextEntity)) );
		$aParams['entitiesExtended'] = ( !empty($aParams['entitiesExtended']) ? $aParams['entitiesExtended'] : $this->sPrimaryEntity ) . $this->formatEntities();
		$aParams['entitiesToDelete'] = implode( ', ', array_unique($aParams['entities'] + array_unique($this->aEntityAlias)) );

		// Sorting
		if( !empty($aParams['sorting']) ) {
			$aParams['sorting'] = (array) $aParams['sorting'];
			foreach( $aParams['sorting'] as $key => $value ) {
				if( in_array($key, $this->aTextFields) && !empty($this->aEntityAlias[$key]) ) {
					unset( $aParams['sorting'][$key] );
					$aParams['sorting'][$this->aEntityAlias[$key] . '.textContent'] = $value;
				}
			}
		}
		if( !empty($this->aSorting) ) {
			foreach( $this->aSorting as $key => $value ) {
				if( in_array($key, $this->aTextFields) && !empty($this->aEntityAlias[$key]) ) {
					unset( $this->aSorting[$key] );
					$this->aSorting[$this->aEntityAlias[$key] . '.textContent'] = $value;
				}
			}
		}

		return parent::deleteData( $aParams );
	}

	protected function formatEntities( $aFields = array() ) {
		$sSql = '';
		if( empty($aFields) ) $aFields = $this->aTextFields;
		$aFields = array_unique( array_merge($aFields, $this->aSelectedTextFields) );
		foreach( $aFields as $sField ) {
			if( array_key_exists($sField, $this->aTextFieldsWithEntities) ) continue;
			$sSql .= ' LEFT JOIN ' . $this->sTextEntity . ' AS ' . $this->aEntityAlias[$sField] . ' ON ' . $this->sPrimaryEntity . '.' . $sField . ' = ' . $this->aEntityAlias[$sField] . '.textId AND ' . $this->aEntityAlias[$sField] . '.textLangId = ' . $this->iLangId;
		}
		return $sSql;
	}

	public function readData( $aParams = array() ) {
		$aTextFields = array();
		$bTextFields = false;

		$aParams['entities'] = !empty($aParams['entities']) ? (array) $aParams['entities'] : array( $this->sPrimaryEntity );

		// Check and replace fields
		if( (empty($aParams['fields']) && $this->aFieldsDefault === '*') || $aParams['fields'] === '*' ) {
			$aParams['fields'] = array();			
			$aEntities = array_intersect_key($this->aDataDict, array_flip($aParams['entities']));
			if( count($aEntities) > 1 ) {
				// Handle multiple tables
				$aParams['fields'] = array_keys($aEntities[$this->sPrimaryEntity]);
			} else {
				foreach( $aEntities as $aEntity ) {
					$aParams['fields'] = array_merge( $aParams['fields'], array_keys($aEntity) );
				}
			}
			$aParams['fields'] = $this->addEntityToField( $aParams['fields'], $this->aTextFields );
		} elseif( (empty($aParams['fields']) && $this->aFieldsDefault !== '*') ) {
			$aParams['fields'] = $this->addEntityToField( $this->aFieldsDefault, $this->aTextFields );
		} else {
			$aParams['fields'] = (array) $aParams['fields'];
		}

		// Add entity to fields
		foreach( $this->aTextFields as $value ) {
			$sFieldKey = array_search( $value, $aParams['fields'] );
			if( $sFieldKey === false ) continue;

			if( array_key_exists($value, $this->aTextFieldsWithEntities) ) $value = $this->aTextFieldsWithEntities[$value];
			if( $value !== $sFieldKey ) {
				$aParams['fields'][$value] = $aParams['fields'][$sFieldKey];
				unset( $aParams['fields'][$sFieldKey] );
			}
		}

		foreach( $this->aTextFields as $key => $sField ) {
			$sFieldKey = array_search( $sField, $aParams['fields'] );
			if( $sFieldKey !== false ) {
				// Replace text id with text content for this field
				$aTextFields[] = !ctype_digit((string) $key) ? $key : $sField;
				$aParams['fields'][$sFieldKey] = $this->aEntityAlias[$sField] . '.textContent';
				$bTextFields = true;
			}
		}

		if( $bTextFields ) {
			$aParams['entities'] = array_merge( $aParams['entities'], array($this->sTextEntity) );
			$aParams['entitiesExtended'] = ( !empty($aParams['entitiesExtended']) ? $aParams['entitiesExtended'] : $this->sPrimaryEntity ) . $this->formatEntities( $aTextFields );
		}

		// Sorting
		if( !empty($aParams['sorting']) ) {
			$aParams['sorting'] = (array) $aParams['sorting'];
			foreach( $aParams['sorting'] as $key => $value ) {
				if( in_array($key, $this->aTextFields) && !empty($this->aEntityAlias[$key]) ) {
					unset( $aParams['sorting'][$key] );
					$aParams['sorting'][$this->aEntityAlias[$key] . '.textContent'] = $value;
				}
			}
		}
		if( !empty($this->aSorting) ) {
			foreach( $this->aSorting as $key => $value ) {
				if( in_array($key, $this->aTextFields) && !empty($this->aEntityAlias[$key]) ) {
					unset( $this->aSorting[$key] );
					$this->aSorting[$this->aEntityAlias[$key] . '.textContent'] = 'COLLATE ' . $this->sCollation . ' ' . $value;
				}
			}
		}

		return parent::readData( $aParams );
	}

	public function setCriterias( $aCriterias = array(), $sOuterJoinType = 'AND', $sInnerJoinType = 'OR' ) {
		if( empty($aCriterias) ) {
			$this->sCriterias = '';
			return;
		}

		foreach( (array) $aCriterias as $key => $aCriteria ) {
			if( !empty($aCriteria['fields']) ) {
				$aCriteria['fields'] = (array) $aCriteria['fields'];
				foreach( $aCriteria['fields'] as $key2 => $sField ) {
					if( array_key_exists($sField, $this->aEntityAlias) ) {
						$aCriterias[$key]['fields'][$key2] = $this->aEntityAlias[$sField] . '.textContent';
						$this->aSelectedTextFields[$sField] = $sField;
					}
				}
			}

		}
		parent::setCriterias( $aCriterias, $sOuterJoinType, $sInnerJoinType );
	}

	public function updateData( $aData, $aParams = array() ) {
		$aTextFields = array();

		$aParams += array(
			'entities' => $this->sPrimaryEntity,
			'validated' => false,
			'dataEscape' => true,
			'groupKey' => null
		);

		// Check if current language version exists, if not then create
		$aParams2 = $aParams;
		foreach( $this->aTextFields as $sField => $sFieldAlias ) {
			if( is_int($sField) ) continue;
			$aParams2['fields'][] = $sField . ' AS ' . $sField . 'RealId';
			$aParams2['fields'][] = $sField;
		}
		$aData2 = $this->readData( $aParams2 );
		foreach( $aData2 as $entry ) {
			foreach( $this->aTextFields as $sField => $sFieldAlias ) {
				if( is_int($sField) ) continue;

				if( empty($entry[$sField . 'RealId']) ) {
					$entry[$sField . 'RealId'] = $this->createText( '' );
					parent::updateData( array(
						$sField => $entry[$sField . 'RealId']
					), $aParams );
				} elseif( (int) $entry[$sField . 'RealId'] > 0 && $entry[$sField] === null ) {
					$this->createText( '', $entry[$sField . 'RealId'] );
				}
			}
		}

		foreach( $this->aTextFields as $sField ) {
			$this->aDataDict[$this->sPrimaryEntity][$sField]['type'] = 'string';
		}

		$this->aEntities = (array) $aParams['entities'];

		$this->setData( $aData );

		if( !$aParams['validated'] && $aParams['dataEscape'] ) {
			$this->aValidationEntities = $this->aEntities;
			$aValidationParams = array(
				'errGroup' => $aParams['groupKey'],
				'partialDataDict' => true
			);
			if( !$this->validateData($aValidationParams) ) return false;
			$aParams['validated'] = true;
		}

		foreach( $this->aTextFields as $sField ) {
			if( array_key_exists($sField, $aData) ) {
				$aData[$this->aEntityAlias[$sField] . '.textContent'] = $aData[$sField];
				$this->aDataDict[$this->sTextEntity][$this->aEntityAlias[$sField] . '.textContent'] = $this->aDataDict[$this->sTextEntity]['textContent'];
				unset( $aData[$sField] );
				$aTextFields[] = $sField;
			}
			$this->aDataDict[$this->sPrimaryEntity][$sField]['type'] = 'integer';
		}

		$aParams['entities'] = array_merge( (!empty($aParams['entities']) ? (array) $aParams['entities'] : array($this->sPrimaryEntity)), array($this->sTextEntity) );
		$aParams['entitiesExtended'] = ( !empty($aParams['entitiesExtended']) ? $aParams['entitiesExtended'] : $this->sPrimaryEntity ) . $this->formatEntities( $aTextFields );

		$result = parent::updateData( $aData, $aParams );

		return $result;
	}

}
