<?php

class clTextHelperDaoSql {

	public $aParams = array();
	public $oDao;

	protected $aEntityAlias = array();
	protected $aSelectedTextFields = array();
	protected $aTextFields;
	protected $aTextFieldsWithEntities = array();

	public function __construct( clDaoBaseSql $oDao, $aParams = array() ) {
		$this->oDao = $oDao;

		$aParams += array(
			'aTextFields' => array(),
			'sTextEntity' => null,
			'langId' => $this->oDao->iLangId
		);

		$this->aParams = $aParams;

		if( empty($this->aParams['sOriginEntity']) ) {
			$this->aParams['sOriginEntity'] = $this->oDao->sPrimaryEntity;
		}

		$iCount = 1;
		foreach( $this->aParams['aTextFields'] as $sField ) {
			$this->aEntityAlias[$sField] = 'text' . $iCount;
			$this->aEntityAlias[$this->aParams['sOriginEntity'] . '.' . $sField] = 'text' . $iCount;
			$this->aTextFields[$sField] = $this->aParams['sOriginEntity'] . '.' . $sField;
			$this->aTextFieldsWithEntities[$this->aParams['sOriginEntity'] . '.' . $sField] = $sField;
			$iCount++;
		}
		if( !empty($this->aParams['sTextEntity']) ) $this->oDao->aDataDict[$this->aParams['sTextEntity']] = array(
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

	public function changeEntityAlias( $sEntity ) {
		$this->aParams['sOriginEntity'] = $sEntity;
		$iCount = 1;
		foreach( $this->aParams['aTextFields'] as $sField ) {
			$this->aEntityAlias[$sField] = 'text' . $iCount;
			$this->aEntityAlias[$sEntity . '.' . $sField] = 'text' . $iCount;
			$this->aTextFields[$sField] = $sEntity . '.' . $sField;
			$this->aTextFieldsWithEntities[$sEntity . '.' . $sField] = $sField;
			$iCount++;
		}
		return true;
	}

	public function changeTextFields( $aTextFields ) {
		$this->aParams['aTextFields'] = $aTextFields;
		// Reset variables
		$this->aEntityAlias = array();
		$this->aTextFields = array();
		$this->aTextFieldsWithEntities = array();

		$iCount = 1;
		foreach( $this->aParams['aTextFields'] as $sField ) {
			$this->aEntityAlias[$sField] = 'text' . $iCount;
			$this->aEntityAlias[$this->aParams['sOriginEntity'] . '.' . $sField] = 'text' . $iCount;
			$this->aTextFields[$sField] = $this->aParams['sOriginEntity'] . '.' . $sField;
			$this->aTextFieldsWithEntities[$this->aParams['sOriginEntity'] . '.' . $sField] = $sField;
			$iCount++;
		}
	}

	public function readData( $aParams = array() ) {
		$aTextFields = array();
		$bTextFields = false;

		$aParams['entities'] = !empty($aParams['entities']) ? (array) $aParams['entities'] : array( $this->aParams['sOriginEntity'] );

		// Check and replace fields
		if( (empty($aParams['fields']) && $this->oDao->aFieldsDefault === '*') || $aParams['fields'] === '*' || in_array('*', (array) $aParams['fields']) ) {
			$aParams['fields'] = array();
			$aEntities = array_intersect_key($this->oDao->aDataDict, array_flip($aParams['entities']));
			foreach( $aEntities as $aEntity ) {
				$aParams['fields'] = array_merge( $aParams['fields'], array_keys($aEntity) );
			}
			$aParams['fields'] = $this->oDao->addEntityToField( $aParams['fields'], $this->aTextFields, $this->aParams['sOriginEntity'] );
		} elseif( (empty($aParams['fields']) && $this->oDao->aFieldsDefault !== '*') ) {
			$aParams['fields'] = $this->oDao->addEntityToField( $this->oDao->aFieldsDefault, $this->aTextFields, $this->aParams['sOriginEntity'] );
		} else {
			$aParams['fields'] = (array) $aParams['fields'];
		}

		// Add entity to fields
		foreach( $this->aTextFields as $sField => $sFieldWithEntity ) {
			$sFieldKey = array_search( $sField, $aParams['fields'] );
			if( $sFieldKey === false ) continue;

			if( array_key_exists($sFieldWithEntity, $this->aTextFieldsWithEntities) ) $sField = $this->aTextFieldsWithEntities[$sFieldWithEntity];
			if( $sField !== $sFieldKey ) {
				$aParams['fields'][$sField] = $aParams['fields'][$sFieldKey];
				unset( $aParams['fields'][$sFieldKey] );
			}
		}

		foreach( $this->aTextFields as $key => $sField ) {
			$sFieldKey = array_search( $key, $aParams['fields'] );

			if( $sFieldKey !== false ) {
				// Replace text id with text content for this field
				$aTextFields[] = !ctype_digit((string) $key) ? $key : $sField;
				$aParams['fields'][$sFieldKey] = $this->aEntityAlias[$sField] . '.textContent';
				$bTextFields = true;
			} else {
				$sFieldKeyWithEntity = array_search( $sField, $aParams['fields'] );
				if( $sFieldKeyWithEntity !== false ) {
					// Replace text id with text content for this field
					$aTextFields[] = !ctype_digit((string) $key) ? $key : $sField;
					$aParams['fields'][ $key ] = $this->aEntityAlias[$sField] . '.textContent';
					unset($aParams['fields'][$sFieldKeyWithEntity]);
					$bTextFields = true;
				}
			}
		}

		if( $bTextFields ) {
			$aParams['entities'] = array_merge( $aParams['entities'], array($this->aParams['sTextEntity']) );
			$aParams['entitiesExtended'] = ( !empty($aParams['entitiesExtended']) ? $aParams['entitiesExtended'] : $this->aParams['sOriginEntity'] ) . $this->formatEntities( $aTextFields );
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
		if( !empty($this->oDao->aSorting) ) {
			foreach( $this->oDao->aSorting as $key => $value ) {
				if( in_array($key, $this->aTextFields) && !empty($this->aEntityAlias[$key]) ) {
					unset( $this->oDao->aSorting[$key] );
					$this->oDao->aSorting[$this->aEntityAlias[$key] . '.textContent'] = 'COLLATE ' . $this->oDao->sCollation . ' ' . $value;
				}
			}
		}

		return $aParams;
	}

	protected function formatEntities( $aFields = array(), $bLang = true ) {
		$sSql = '';
		if( empty($aFields) ) $aFields = $this->aParams['aTextFields'];
		$aFields = array_unique( array_merge($aFields, $this->aSelectedTextFields) );
		foreach( $aFields as $sField ) {
			if( array_key_exists($sField, $this->aTextFieldsWithEntities) ) continue;
			$sSql .= ' LEFT JOIN ' . $this->aParams['sTextEntity'] . ' AS ' . $this->aEntityAlias[$sField] . ' ON ' . $this->aParams['sOriginEntity'] . '.' . $sField . ' = ' . $this->aEntityAlias[$sField] . '.textId' . ( ($bLang === true) ? ' AND ' . $this->aEntityAlias[$sField] . '.textLangId = ' . $this->oDao->iLangId : '' );
		}
		return $sSql;
	}

	public function createData( $aParams, $aData = array() ) {
		if( empty($aParams) ) $aParams = array();

		if( isset($aParams['entities']) || !empty($this->aParams['sOriginEntity']) ) {
			$sValidationEntity = !empty($aParams['entities']) ? $aParams['entities'] : $this->aParams['sOriginEntity'];

			if( is_array($sValidationEntity) ) {
				// Validate all in array
				$aErr = clDataValidation::validate( $aData, $this->oDao->aDataDict );
			} else {
				// Validate only one entity
				if( array_key_exists($sValidationEntity, $this->oDao->aDataDict) ) {
					$aErr = clDataValidation::validate( $aData, array( $this->oDao->aDataDict[$sValidationEntity]) );
				} else {
					$aErr = clDataValidation::validate( $aData, $this->oDao->aDataDict );
				}
			}
		} else {
			// Validate all
			$aErr = clDataValidation::validate( $aData, $this->oDao->aDataDict );
		}

		if( !empty($aErr) ) {
			clErrorHandler::setValidationError($aErr);
			return false;
		}

		foreach( $this->aTextFields as $sField => $sFieldWithEntity ) {
			$this->oDao->aDataDict[$this->aParams['sOriginEntity']][$sField]['type'] = 'integer';
		}

		foreach( $this->aTextFields as $sField => $sFieldWithEntity  ) {
			if( array_key_exists($sField, $aData) ) $aData[$sField] = $this->createText( $aData[$sField]);
			$this->oDao->aDataDict[$this->aParams['sOriginEntity']][$sField]['type'] = 'integer';
		}

		return array(
			'aData' => $aData,
			'aParams' => $aParams
		);
	}

	public function createText( $sText, $iTextId = null ) {
		if( $iTextId === null ) $iTextId = $this->oDao->readNextId( 'textId', $this->aParams['sTextEntity'] );
		$aParams = array(
			'entities' => $this->aParams['sTextEntity']
		);
		$aData = array(
			'textId' => $iTextId,
			'textContent' => $sText,
			'textLangId' => $this->oDao->iLangId
		);

		return $this->oDao->createData($aData, $aParams) ? $iTextId : false;
	}

	public function deleteData( $aParams = array() ) {
		$aParams['entities'] = array_unique( array_merge((!empty($aParams['entities']) ? (array) $aParams['entities'] : array($this->aParams['sOriginEntity'])), !empty($this->aEntityAlias) ? $this->aEntityAlias : array($this->sTextEntity)) );
		$aParams['entitiesExtended'] = ( !empty($aParams['entitiesExtended']) ? $aParams['entitiesExtended'] : $this->aParams['sOriginEntity'] ) . $this->formatEntities( array(), false );
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
		if( !empty($this->oDao->aSorting) ) {
			foreach( $this->oDao->aSorting as $key => $value ) {
				if( in_array($key, $this->aTextFields) && !empty($this->aEntityAlias[$key]) ) {
					unset( $this->oDao->aSorting[$key] );
					$this->oDao->aSorting[$this->aEntityAlias[$key] . '.textContent'] = $value;
				}
			}
		}

		return $aParams;
	}

	public function updateData( $aData, $aParams = array() ) {
		$aTextFields = array();

		// Check if current language version exists, if not then create
		$aParams2 = $aParams;
		foreach( $this->aTextFields as $sField => $sFieldAlias ) {
			if( is_int($sField) ) continue;
			$aParams2['fields'][] = $sField . ' AS ' . $sField . 'RealId';
			$aParams2['fields'][] = $sField;
		}
		// groupKey is still update, so we need remove it
		unset($aParams2['groupKey']);
		$aData2 = $this->oDao->readData( $aParams2 );

		foreach( $aData2 as $entry ) {
			foreach( $this->aTextFields as $sField => $sFieldAlias ) {
				if( is_int($sField) ) continue;


				if( empty($entry[$sField . 'RealId']) ) {
					$entry[$sField . 'RealId'] = $this->createText( '' );
					$aParams['withHelpers'] = false;
					$this->oDao->updateData( array(
						$sField => $entry[$sField . 'RealId']
					), $aParams );
					$aParams['withHelpers'] = true;
				} elseif( (int) $entry[$sField . 'RealId'] > 0 && $entry[$sField] === null ) {
					$this->createText( '', $entry[$sField . 'RealId'] );
				}
			}
		}

		foreach( $this->aTextFields as $sField => $sFieldAlias ) {
			$this->oDao->aDataDict[$this->aParams['sOriginEntity']][$sField]['type'] = 'string';
		}

		foreach( $this->aTextFields as $sField => $sFieldAlias ) {
			if( array_key_exists($sField, $aData) ) {
				$aData[$this->aEntityAlias[$sField] . '.textContent'] = $aData[$sField];
				$this->oDao->aDataDict[$this->aParams['sTextEntity']][$this->aEntityAlias[$sField] . '.textContent'] = $this->oDao->aDataDict[$this->aParams['sTextEntity']]['textContent'];
				unset( $aData[$sField] );
				$aTextFields[] = $sField;
			}
			$this->oDao->aDataDict[$this->aParams['sOriginEntity']][$sField]['type'] = 'integer';
		}

		$aParams['entities'] = array_merge( (!empty($aParams['entities']) ? (array) $aParams['entities'] : array($this->aParams['sOriginEntity'])), array($this->aParams['sTextEntity']) );
		$aParams['entitiesExtended'] = ( !empty($aParams['entitiesExtended']) ? $aParams['entitiesExtended'] : $this->aParams['sOriginEntity'] ) . $this->formatEntities( $aTextFields );

		return array(
			'aData' => $aData,
			'aParams' => $aParams
		);
	}

}
