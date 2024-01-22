<?php

require_once PATH_FUNCTION . '/fOutputHtml.php';
require_once PATH_CONFIG . '/cfForm.php';

class clOutputHtmlForm {

	private $aParams = array();

	private $aAttributes = array();
	private $aButtons = array();
	private $aData = array();
	private $aDataDict = array();
	private $aFormDataDict = array();
	private $aGroups = array();
	private $bIncludeQueryStr;
	private $bUsePlaceholder;
	private $sAction;
	private $sMethod;
	private $bUseRecaptcha;

	private $oRecaptcha;

	private static $sWrapperElement;
	private static $bExtraElementWrapper;
	private static $sWrapperClass;

	public $aErr = array();
	public $bDisabled;
	public $bLabelEmbedded;
	public $sLabelSuffix;
	public $sLabelRequiredSuffix;

	/**
	 *
	 */
	public function __construct( $aDataDict = array(), $aParams = array() ) {
		$this->init( $aDataDict, $aParams );
	}

	public function init( $aDataDict, $aParams = array() ) {
		$aParams += array(
			'action' => '',
			'attributes' => array(),
			'buttons' => array(
				'submit' => _( 'Submit' )
			),
			'data' => null,
			'disabled' => false,
			'errors' => array(),
			'groups' => array(),
			'labelEmbedded' => false,
			'labelSuffix' => '',
			'labelRequiredSuffix' => '*',
			'method' => 'get',
			'includeQueryStr' => true,
			'placeholders' => true,
			'wrapperElement' => 'div',
			'extraElementWrapper' => false,
			'wrapperClass' => 'field',
			'recaptcha' => false
		);

		// reCaptcha
		if( $aParams['recaptcha'] === true && RECAPTCHA_ENABLE === false ) {
			$aParams['recaptcha'] = false;
		}

		$this->setParams( $aParams );

		$this->aDataDict = array();
		foreach( $aDataDict as $value ) {
			$this->aDataDict += $value;
		}
		$this->aFormDataDict = $this->aDataDict;
	}

	public function setParams( $aParams ) {
		if( empty($this->aParams) ) {
			$this->aParams = $aParams;
		} else {
			$aParams = array_merge($this->aParams, $aParams);
			$this->aParams = $aParams;
		}

		if( $aParams['data'] === null ) {
			$aParams['data'] = ( $aParams['method'] == 'get' ) ? $_GET : $_POST;
		}
		$this->aData = $aParams['data'];

		$this->setGroups( $aParams['groups'] );

		$this->aAttributes = $aParams['attributes'];
		$this->aButtons = $aParams['buttons'];
		$this->aErr = $aParams['errors'];
		$this->sAction = $aParams['action'];
		$this->bDisabled = $aParams['disabled'];
		$this->bLabelEmbedded = $aParams['labelEmbedded'];
		$this->sLabelSuffix = $aParams['labelSuffix'];
		$this->sLabelRequiredSuffix = $aParams['labelRequiredSuffix'];
		$this->sMethod = $aParams['method'];
		$this->bIncludeQueryStr = $aParams['includeQueryStr'];
		$this->bUsePlaceholder = $aParams['placeholders'];
		$this->bUseRecaptcha = $aParams['recaptcha'];

		self::$sWrapperElement = $aParams['wrapperElement'];
		self::$bExtraElementWrapper = $aParams['extraElementWrapper'];
		self::$sWrapperClass = $aParams['wrapperClass'];

		if( $this->bUseRecaptcha ) {
			$this->oRecaptcha = clRegistry::get( 'clRecaptcha' );
		}
	}

	public function setParamAttributes( $aAttributes ) {
		$this->aAttributes = array_merge( $this->aAttributes, $aAttributes );
	}

	public static function createButton( $sType, $sContent, $aAttributes = array() ) {
		$aAttributes += array(
			'type' => $sType
		);
		return '
				<button' . createAttributes( $aAttributes ) . '>' . $sContent . '</button>';
	}

	public static function createCheckboxSet( $sName, $aValues, $selectedValues = [], $aFieldAttributes = [] ) {
		$sOutput = '';
		if( !empty($selectedValues) ) $selectedValues = array_map( 'strval', (array) $selectedValues );

		foreach( $aValues as $key => $value ) {
			$aAttributes = [
				'name' => $sName . '[]',
				'value' => $key,
				'title' => $value
			];
			if( in_array((string) $key, (array) $selectedValues, true) ) $aAttributes['checked'] = 'checked';

			if( !empty($aFieldAttributes['class']) ) {
				$aFieldAttributes['attributes']['class'] .= ' checkboxSet';
			} else {
				$aFieldAttributes['attributes']['class'] = 'checkboxSet';
			}

			$key = $sName . ucfirst( $key );
			$sOutput .= self::createField( $key, $value, self::createInput('checkbox', $key, $aAttributes), $aFieldAttributes );
		}
		return $sOutput;
	}

	public static function createField( $sName, $sTitle, $sContent, $aParams = array(), $sLabelSuffixContent = '', $aLabelAttributes = array() ) {
		$aParams += array(
			'attributes' => array(),
			'wrapperElement' => self::$sWrapperElement,
			'extraElementWrapper' => self::$bExtraElementWrapper,
			'wrapperClass' => self::$sWrapperClass
		);

		$aParams['attributes']['class'] = empty($aParams['attributes']['class']) ? $aParams['wrapperClass'] : $aParams['attributes']['class'] . ' ' . $aParams['wrapperClass'];

		if( $aParams['extraElementWrapper'] === true ) {
			$sContent = '<' . $aParams['wrapperElement'] . '>' . $sContent . '</' . $aParams['wrapperElement'] . '>';
		}

		if( $aParams['wrapperElement'] === 'label' ) {
			return self::createLabel( $sName, '<span' . createAttributes(array('class' => 'label') + $aLabelAttributes) . '>' . $sTitle . '</span> ' . $sContent, $aParams['attributes'], $sLabelSuffixContent );
		} else {
			return '
			<' . $aParams['wrapperElement'] . createAttributes($aParams['attributes']) . '>' . self::createLabel($sName, $sTitle, $aLabelAttributes, $sLabelSuffixContent) . ' ' . $sContent . '
			</' . $aParams['wrapperElement'] . '>';
		}
	}

	public static function createFieldset( $sTitle, $sContent, $aAttributes = array(), $aLegendAttributes = array() ) {
		if( !empty( $sTitle ) ) {
			$sContent = '<legend' . createAttributes( $aLegendAttributes ) . '><span>' . $sTitle . '</span></legend>' . $sContent;
		}

		return '
			<fieldset' . createAttributes( $aAttributes ) . '>' . $sContent . '
			</fieldset>';
	}

	public static function createForm( $sMethod, $sAction, $sContent = '', $aAttributes = array() ) {
		if( empty($sAction) ) {
			$oRouter = clRegistry::get( 'clRouter' );
			$sAction = htmlspecialchars( $oRouter->sPath ) . ( ($sMethod != 'get' && !empty($_SERVER['QUERY_STRING'])) ? '?' . stripGetStr() : '' );
		}
		$aAttributes += array(
			'action' => $sAction,
			'method' => $sMethod
		);
		return '
		<form' . createAttributes( $aAttributes ) . '>' . $sContent . '
		</form>';
	}

	public static function createInput( $sType, $sName, $aAttributes = array() ) {
		$aAttributes += array(
			'id' => $sName,
			'name' => $sName,
			'type' => $sType,
			'class' => $sType
		);
		return '
				<input' . createAttributes( $aAttributes ) . '>';
	}

	public static function createLabel( $sName, $sContent, $aAttributes = array(), $sLabelSuffixContent = '' ) {
		$aAttributes += array(
			'for' => $sName
		);
		return '
				<label' . createAttributes( $aAttributes ) . '>' . $sContent . '</label>' . (!empty($sLabelSuffixContent) ? '<span class="labelSuffixContent">' . $sLabelSuffixContent . '</span>' : null);
	}

	public static function createRadioSet( $sName, $aValues, $selectedValue = null, $aFieldAttributes = [] ) {
		$sOutput = '';
		foreach( $aValues as $key => $value ) {
			$aAttributes = array(
				'name' => $sName,
				'value' => $key,
				'title' => $value
			);
			if( $key == $selectedValue ) $aAttributes['checked'] = 'checked';

			if( !empty($aFieldAttributes['class']) ) {
				$aFieldAttributes['attributes']['class'] .= ' radioSet';
			} else {
				$aFieldAttributes['attributes']['class'] = 'radioSet';
			}

			$key = $sName . ucfirst( $key );
			$sOutput .= self::createField( $key, $value, self::createInput('radio', $key, $aAttributes), $aFieldAttributes );
		}
		return $sOutput;
	}

	public static function createSelect( $sName, $sTitle, $aValues, $selectedValue = null, $aAttributes = array(), $aOptGroups = array() ) {
		if( isset($aAttributes['multiple']) ) $sName = $sName . '[]';
		$aAttributes += array(
			'id' => $sName,
			'name' => $sName,
			'title' => $sTitle
		);
		$sContent = '';
		if( !empty($selectedValue) ) $selectedValue = array_map( 'strval', (array) $selectedValue );

		// Optgroup
		if( !empty($aOptGroups) ) {
			$bOptGroups = true;
			reset($aOptGroups);
			$sCurrentOptGroupKey = key($aOptGroups);
			$sFirstValueKey = current($aOptGroups[$sCurrentOptGroupKey]['values']);
			$sLastValueKey = end($aOptGroups[$sCurrentOptGroupKey]['values']);
		} else {
			$bOptGroups = false;
		}

		foreach( $aValues as $key => $value ) {
			$aOptAttributes = array(
				'value' => $key
			);

			if( is_array($value) ) {
				if( !empty($value['attributes']) ) $aOptAttributes += $value['attributes'];
				$value = $value['value'];
			}

			if( in_array((string) $key, (array) $selectedValue, true) ) $aOptAttributes['selected'] = 'selected';

			if( $bOptGroups ) {
				// Start new optgroup
				if( $key == $sFirstValueKey ) {
					if( !array_key_exists('attributes', $aOptGroups[$sCurrentOptGroupKey]) ) {
						$aOptGroups[$sCurrentOptGroupKey]['attributes'] = array();
					}
					if( !array_key_exists('label', $aOptGroups[$sCurrentOptGroupKey]['attributes']) ) {
						$aOptGroups[$sCurrentOptGroupKey]['attributes']['label'] = '';
					}

					$sContent .= '
					<optgroup' . createAttributes($aOptGroups[$sCurrentOptGroupKey]['attributes']) . '>';
				}

				$sContent .= '
					' . ( in_array($key, $aOptGroups[$sCurrentOptGroupKey]['values']) ? '	' : '' ) . '<option' . createAttributes( $aOptAttributes ) . '>' . $value . '</option>';
			} else {
				// No optgroup, for preserving tabs
				$sContent .= '
					<option' . createAttributes( $aOptAttributes ) . '>' . $value . '</option>';
			}



			if( $bOptGroups ) {
				// End optgroup
				if( $key == $sLastValueKey ) {

					unset( $aOptGroups[$sCurrentOptGroupKey] );
					reset($aOptGroups);
					if( !empty($aOptGroups) ) {
						$sCurrentOptGroupKey = key($aOptGroups);
						$sFirstValueKey = current($aOptGroups[$sCurrentOptGroupKey]['values']);
						$sLastValueKey = end($aOptGroups[$sCurrentOptGroupKey]['values']);
					}

					$sContent .= '
					</optgroup>';
				}
			}
		}
		return '
				<select' . createAttributes( $aAttributes ) . '>' . $sContent . '
				</select>';
	}

	public static function createTextarea( $sName, $sTitle, $sContent = '',  $aAttributes = array() ) {
		$aAttributes += array(
			'id' => $sName,
			'name' => $sName,
			'title' => $sTitle
		);
		return '
				<textarea' . createAttributes( $aAttributes ) . '>' . $sContent . '</textarea>';
	}

	public function hasError( $name ) {
		return array_key_exists( $name, (array) $this->aErr );
	}

	public function render() {
		$sOutput = '';
		$sOutput .= $this->renderErrors();
		$sOutput .= $this->renderGroups();
		$sOutput .= $this->renderFields();
		$sOutput .= $this->renderButtons();

		// Run invisible method?
		if( $this->bUseRecaptcha === true && defined('RECAPTCHA_INVISIBLE_METHOD') ) {
			switch( RECAPTCHA_INVISIBLE_METHOD ) {
				case 'invokeChallenge':
					$sOutput .= $this->oRecaptcha->invokeChallenge();
					break;

				case 'v3Frontend':
					$sOutput .= $this->oRecaptcha->v3Frontend();
					break;

				default:
					// Do nothing
			}
		}

		return $this->renderForm( $sOutput );
	}

	public function renderButtons( $aButtons = null ) {
		if( empty($this->aButtons) ) return;
		$sOutput = '';

		// Not implemented for now..
		//if( $this->bUseRecaptcha === true && RECAPTCHA_INVISIBLE_METHOD == 'modifyButtons' ) {
		//	$this->aButtons = $this->oRecaptcha->modifyButtons( $this->aButtons );
		//}

		foreach( $this->aButtons as $key => $value ) {
			if( !empty($aButtons) && !in_array($key, (array) $aButtons) ) {
				continue;
			}

			if( is_string($value) ) {
				$value = array(
					'content' => $value
				);
			}

			if( $this->bDisabled === true ) {
				if( is_array($value) ) {
					$value['attributes']['disabled'] = 'disabled';
				} else {
					$value = array(
						'content' => $value,
						'attributes' => array( 'disabled' => 'disabled' )
					);
				}
			}

			if( is_array($value) && !array_key_exists( 'attributes', $value) ) $value['attributes'] = array();

			$sOutput .= is_array($value) ? self::createButton( $key, $value['content'], (array) $value['attributes'] ) : self::createButton( $key, $value );
		}

		return '
			<p class="buttons">' . $sOutput . '
			</p>';
	}

	public function renderErrors() {
		if( empty( $this->aErr ) ) return;

		$sOutput = '';
		foreach( $this->aErr as $key => $aMsg ) {
			foreach( (array) $aMsg as $sMsg) {
				$sOutput .= '
				<li><label for="' . $key . '">' . $sMsg . '</label></li>';
			}
		}
		return '
			<div class="result error">
				<ol>' . $sOutput . '
				</ol>
			</div>';
	}

	public function renderFields( $aFields = null ) {
		$aFormDataDict = ($aFields !== null ) ? array_intersect_key( $this->aFormDataDict, array_flip((array) $aFields) ) : $this->aFormDataDict;
		$sOutput = '';
		$sHidden = '';

		foreach( $aFormDataDict as $key => $field ) {
			$sLabelSuffix = $this->sLabelSuffix;

			$aAttributes = array();
			$aFieldAttributes = array();
			$aLabelAttributes = array();
			$aLegendAttributes = array();

			if( isset($field['attributes']) ) $aAttributes = $field['attributes'];
			if( isset($field['fieldAttributes']) ) $aFieldAttributes = $field['fieldAttributes'];
			if( isset($field['labelAttributes']) ) $aLabelAttributes = $field['labelAttributes'];
			if( isset($field['legendAttributes']) ) $aLegendAttributes = $field['legendAttributes'];

			if( $this->bDisabled === true ) $aAttributes['disabled'] = 'disabled';

			if( $this->hasError($key) ) {
				if( !empty($aFieldAttributes['class']) ) {
					$aFieldAttributes['class'] .= ' errorField';
				} else {
					$aFieldAttributes['class'] = 'errorField';
				}
			}

			if( isset($field['required']) ) {
				if( !empty($aFieldAttributes['class']) ) {
					$aFieldAttributes['class'] .= ' requiredField';
				} else {
					$aFieldAttributes['class'] = 'requiredField';
				}
				$sLabelSuffix = '<span class="requiredSuffix">' . $this->sLabelRequiredSuffix . '</span>' . '<span class="labelSuffix">' . $sLabelSuffix . '</span>';
			}

			if( !isset($field['type']) ) $field['type'] = null;
			if( !isset($field['appearance']) ) $field['appearance'] = null;
			$field['suffixContent'] = isset($field['suffixContent']) ? '<span class="suffixContent">' . $field['suffixContent'] . '</span>' : null;
			$sName = !empty( $field['name'] ) ? $field['name'] : $key;
			$sLabelSuffixContent = isset($field['labelSuffixContent']) ? $field['labelSuffixContent'] : null;

			if( in_array($field['type'], array('array', 'arraySet')) ) {
				if( empty($this->aData) && !empty($field['defaultValue']) )	$sSelected = $field['defaultValue'];
				else if( isset($this->aData[$key]) ) $sSelected = $this->aData[$key];
				else $sSelected = key($field['values']);
			}

			if( !isset( $field['title'] ) && $field['type'] != 'hidden' ) {
				throw new Exception( sprintf( _( 'Field "%s" is missing title' ), $key) );
			}

			// Add a regular expression (without delimiters) in the pattern key to validate regular expressions on both client side and server side
			if( isset( $field['pattern'] ) ) {
				$aAttributes['pattern'] = $field['pattern']; // Pattern attribute be validated by browsers. By passing from dao, we also allow for server side validation in clDataValidation
			}

			if( !empty( $field['required'] ) ) {
				$aAttributes['required'] = 'required';
			}

			if( !empty($aFieldAttributes['class']) ) $aFieldAttributes['class'] .= ' ' . ( !empty($field['type']) ? $field['type'] : 'text' );
			else $aFieldAttributes['class'] = ( !empty($field['type']) ? $field['type'] : 'text' );

			switch( $field['type'] ) {
				case 'array':
					switch( $field['appearance'] ) {
						case 'readonly':
							$aAttributes['disabled'] = 'disabled';
							$sElement = self::createSelect( $sName, $field['title'], $field['values'], $sSelected, $aAttributes );
							$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
							break;
						case 'full':
							if( !empty($aFieldAttributes['class']) ) {
								$aFieldAttributes['class'] .= ' fieldGroup';
							} else {
								$aFieldAttributes['class'] = 'fieldGroup';
							}

							$sElement = self::createRadioSet( $sName, $field['values'], $sSelected );
							$sOutput .= self::createFieldset( $field['title'] . $sLabelSuffix, $sElement, $aFieldAttributes, $aLegendAttributes );
							break;
						case 'group':
							// TODO <optgroup> support
							$sElement = self::createSelect( $sName, $field['title'], $field['values'], $sSelected, $aAttributes, $field['groups'] );
							$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
							break;
						default:
							$sElement = self::createSelect( $sName, $field['title'], $field['values'], $sSelected, $aAttributes );
							$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
							break;
					}
					break;
				case 'arraySet':
					switch( $field['appearance'] ) {
						case 'readonly':
							$aAttributes['disabled'] = 'disabled';
							$aAttributes['multiple'] = 'multiple';
							$sElement = self::createSelect( $sName, $field['title'], $field['values'], $sSelected, $aAttributes );
							$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
							break;
						case 'full':
							if( !empty($aFieldAttributes['class']) ) {
								$aFieldAttributes['class'] .= ' fieldGroup';
							} else {
								$aFieldAttributes['class'] = 'fieldGroup';
							}

							$sElement = self::createCheckboxSet( $sName, $field['values'], $sSelected );
							$sOutput .= self::createFieldset( $field['title'] . $sLabelSuffix, $sElement, $aFieldAttributes, $aLegendAttributes );
							break;
						default:
							$aAttributes['multiple'] = 'multiple';
							$sElement = self::createSelect( $sName, $field['title'], $field['values'], $sSelected, $aAttributes );
							$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
							break;
					}
					break;
				case 'boolean':
					$aAttributes['value'] = key( $field['values'] );
					if( isset($this->aData[$key]) ) $aAttributes['checked'] = 'checked';

					$sElement = self::createInput( 'checkbox', $sName, $aAttributes );
					$sOutput .= self::createField( $sName, $field['title'], $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
					break;
				case 'float':
				case 'integer':
					$aAttributes['title'] = $field['title'];

					if( $this->bUsePlaceholder ) {
						if( empty($aAttributes['placeholder']) ) {
							$aAttributes['placeholder'] = $field['title'] . (isset($field['required']) && !empty($this->sLabelRequiredSuffix) ? ' ' . $this->sLabelRequiredSuffix : '');
						}
					}

					if( empty($aAttributes['maxlength']) ) $aAttributes['maxlength'] = ( isset($field['max']) ) ? $field['max'] : 255;

					switch( $field['appearance'] ) {
						case 'readonly':
							$sElement = isset( $this->aData[$key] ) ? $this->aData[$key] : ( isset($field['value']) ? $field['value'] : '' );
							break;
						case 'full':
							$sElement = self::createTextarea( $sName, $field['title'], (isset($this->aData[$key]) ? $this->aData[$key] : ''), $aAttributes );
							break;
						default:
							if( isset($this->aData[$key]) ) {
								$aAttributes['value'] = $this->aData[$key];
							}
							$sElement = self::createInput( 'text', $sName, $aAttributes );
							break;
					}

					$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
					break;
				case 'hidden':
					$aAttributes['value'] = isset( $field['value'] ) ? $field['value'] : ( isset($this->aData[$key]) ? $this->aData[$key] : '' );
					$sHidden .= self::createInput( 'hidden', $sName, $aAttributes );
					break;
				case 'upload':
					$this->aAttributes['enctype'] = 'multipart/form-data';
					$this->sMethod = 'post';
					if( !isset($aAttributes['class']) ) $aAttributes['class'] = 'upload';
					if( strpos($aAttributes['class'], 'multi') !== false ) $aAttributes['multiple'] = 'multiple';
					$sElement = self::createInput( 'file', $sName, $aAttributes );
					$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
					break;
				case 'date':
					$sElement = self::createInput( 'date', $sName, $aAttributes );
					$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
					break;
				case 'datetime-local':
					$sElement = self::createInput( 'datetime-local', $sName, $aAttributes );
					$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
					break;
				case 'string':
				default:
					if( !isset($aAttributes['title']) ) $aAttributes['title'] = $field['title'];
					if( $this->bUsePlaceholder ) {
						if( empty($aAttributes['placeholder']) ) {
							$aAttributes['placeholder'] = $field['title'] . (isset($field['required']) && !empty($this->sLabelRequiredSuffix) ? ' ' . $this->sLabelRequiredSuffix : '');
						}
						$aLabelAttributes['class'] = 'accessibility';
					}
					switch( $field['appearance'] ) {
						case 'readonly':
							$sElement = isset( $this->aData[$key] ) ? $this->aData[$key] : ( isset($field['value']) ? $field['value'] : '' );
							break;
						case 'full':
							$sElement = self::createTextarea( $sName, $field['title'], (isset($this->aData[$key]) ? $this->aData[$key] : ''), $aAttributes );
							break;
						case 'secret':
							$aAttributes['value'] = isset( $this->aData[$key] ) ? htmlspecialchars( $this->aData[$key] ) : ( isset($field['value']) ? $field['value'] : '' );
							if( !isset($aAttributes['class']) ) $aAttributes['class'] = 'secret';
							if( empty($aAttributes['maxlength']) ) $aAttributes['maxlength'] = ( isset($field['max']) ) ? $field['max'] : 255;
							$sElement = self::createInput( 'password', $sName, $aAttributes );
							break;
						default:
							$aAttributes['value'] = isset( $this->aData[$key] ) ? htmlspecialchars( $this->aData[$key] ) : ( isset($field['value']) ? $field['value'] : '' );
							if( empty($aAttributes['maxlength']) ) $aAttributes['maxlength'] = ( isset($field['max']) ) ? $field['max'] : 255;
							if( $field['type'] == 'string' ) $sElement = self::createInput( 'text', $sName, $aAttributes );
							else $sElement = self::createInput( $field['type'], $sName, $aAttributes );
							break;
					}

					$sOutput .= self::createField( $sName, $field['title'] . $sLabelSuffix, $sElement . $field['suffixContent'], array('attributes' => $aFieldAttributes), $sLabelSuffixContent, $aLabelAttributes );
					break;
			}

			unset( $this->aFormDataDict[$key] );
		}

		if( $this->sMethod === 'get' && $this->bIncludeQueryStr ) {
			$aToStrip = array(
				'ajax',
				'section',
				'view',
				'layout'
			);

			$aToStrip = array_merge( $aToStrip, array_keys( $aFormDataDict ) ); // Also remove existing input keys to prevent overrides
			$aQueries = array_diff_key( $_GET, array_flip($aToStrip) );
			foreach( $aQueries as $key => $value ) {
				if( is_array($value) ) {
					foreach( $value as $entry ) {
						if( !array_key_exists($key, $aFormDataDict) ) {
							$sHidden .= self::createInput( 'hidden', $key . '[]', array('value' => $entry) );
						}
					}
				} else {
					if( !array_key_exists($key, $aFormDataDict) ) {
						$sHidden .= self::createInput( 'hidden', $key, array('value' => $value) );
					}
				}
			}
		}

		if( !empty($sHidden) ) {
			$sOutput .= '
			<div class="hidden">' . $sHidden . '
			</div>';
		}
		return $sOutput;
	}

	public function renderForm( $sContent ) {
		if( $this->bLabelEmbedded === true ) {
			if( empty($this->aAttributes['class']) ) {
				$this->aAttributes['class'] = 'labelEmbedded';
			} else {
				$this->aAttributes['class'] .= ' labelEmbedded';
			}
		}
		return self::createForm( $this->sMethod, $this->sAction, $sContent, $this->aAttributes );
	}

	public function renderGroups( $aGroups = null ) {
		$sOutput = '';
		$bFoldableJs = false;
		$aFormGroups = ($aGroups !== null ) ? array_intersect_key( $this->aGroups, array_flip((array) $aGroups) ) : $this->aGroups;
		foreach( $aFormGroups as $sGroupKey => $aGroup ) {
			$aGroup['attributes']['class'] = 'fieldGroup ' . $sGroupKey . (!empty($aGroup['attributes']['class']) ? ' ' . $aGroup['attributes']['class'] : '');

			$sContent = ( !empty($aGroup['prefixContent']) ? '<div class="prefixContent">' . $aGroup['prefixContent'] . '</div>' : '' );
			$sContent .= $this->renderFields( $aGroup['fields'] );

			$sOutput .= self::createFieldset( $aGroup['title'], $sContent, $aGroup['attributes'] );

			if( !empty($aGroup['suffixContent']) ) $sOutput .= $aGroup['suffixContent'];
			if( !empty($aGroup['attributes']['class']) && stristr($aGroup['attributes']['class'], 'foldable') ) {
				$bFoldableJs = true;
			}
		}

		if( $bFoldableJs === true ) {
			$oTemplate = clRegistry::get( 'clTemplateHtml' );
			$oTemplate->addBottom( array(
				'key' => 'foldableFieldset',
				'content' => '
					<script type="text/javascript">
						$( function() {
							$("fieldset.foldable legend").click( function() {
								var parentObj = $(this).parent();

								if( parentObj.hasClass("folded") ) {
									parentObj.removeClass( "folded" );
								} else {
									parentObj.addClass( "folded" );
								}
							} );
						} );
					</script>
				'
			) );
		}

		return $sOutput;
	}

	public function setData( $aData ) {
		$this->aData = $aData;
	}

	public function setFormDataDict( $aFormDataDict, $aHiddenInputs = array() ) {
		foreach( $this->aDataDict as $key => $value ) {
			if( isset($aFormDataDict[$key]) ) {
				$aFormDataDict[$key] += $this->aDataDict[$key];
			}
		}
		foreach( (array) $aHiddenInputs as $key => $value ) {
			$aFormDataDict[$key] = array(
				'type' => 'hidden',
				'value' => $value
			);
		}
		$this->aFormDataDict = $aFormDataDict;
	}

	public function setGroups( $aGroups ) {
		$this->aGroups = $aGroups;
	}

	public function renderJavascriptValidation() {
		/**
		 * Deprecated function, will be removed soon
		 */
		return;
	}

}
