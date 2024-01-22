<?php

require_once PATH_CONFIG . '/cfForm.php';

class clDataValidation {

	public static function validate( $aData, $aDataDict, $aParams = array() ) {
		$aParams += array(
			'errGroup' => null,
			'partialDataDict' => false
		);
		$aErr = array();
		$sErrGroup = $aParams['errGroup'];

		foreach( $aDataDict as $sEntity => $aEntityDataDict ) {

			if( $aParams['errGroup'] === null ) $sErrGroup = $sEntity;

			foreach( $aEntityDataDict as $key => $value ) {
				if( $aParams['partialDataDict'] && !isset($aData[$key]) ) continue;
				if( empty($value['title']) ) $value['title'] = $key;

				if( empty($aData[$key]) ) {
					if( !empty($value['required']) ) {
						$aErr[$sErrGroup][$key] = array(
							'title' => $value['title'],
							'type' => 'required'
						);
					}
					continue;
				}

				$aValidation = array();
				switch( $value['type'] ) {
					case 'array':
						$aValidation['inArray'] = array_keys( $value['values'] );
						break;
					case 'boolean':
						$aValidation[] = 'boolean';
						break;
					case 'date':
						$aValidation[] = 'date';
						break;
					case 'datetime': // YYYY-MM-DD HH:MM:SS
						$aDateParts = explode( ' ', $aData[$key] );
						if( count($aDateParts) != 2 ) {
							$aErr[$sErrGroup][$key] = array(
								'title' => $value['title'],
								'type' => 'datetime'
							);
							continue 2;
						}
						list( $sDate, $sTime ) = $aDateParts;
						if( !self::isDate($sDate) || !self::isTime($sTime) ) {
							$aErr[$sErrGroup][$key] = array(
								'title' => $value['title'],
								'type' => 'datetime'
							);
							continue 2;
						}
						break;
					case 'integer':
						$aValidation[] = 'int';
						if( isset($value['min']) ) $aValidation['greaterThan'] = $value['min'] - 1;
						if( isset($value['max']) ) $aValidation['lessThan'] = $value['max'] + 1;
						break;
					case 'float':
						$aValidation[] = 'float';
						if( isset($value['min']) ) $aValidation['greaterThan'] = $value['min'] - 1;
						if( isset($value['max']) ) $aValidation['lessThan'] = $value['max'] + 1;
						break;
					case 'string':
						if( isset($value['min']) || isset($value['max']) ) {
							if( !isset($value['max']) ) $value['max'] = null;
							$aValidation['stringLength'] = array( (int) $value['min'], $value['max'] );
						}
						break;
				}

				if( isset($value['extraValidation']) ) {
					$aValidation = $value['extraValidation'] + $aValidation;
				}

				foreach( $aValidation as $validationKey => $validationValue ) {
					if( is_int($validationKey) ) {
						$type = $validationValue;
						$method = 'is' . ucfirst( $type );
						if( self::$method($aData[$key]) ) {
							continue;
						}
					} else {
						$type = $validationKey;
						$method = 'is' . ucfirst( $type );
						if( self::$method($aData[$key], $validationValue) ) {
							continue;
						}
					}

					$aErr[$sErrGroup][$key] = array(
						'title' => $value['title'],
						'type' => $type
					);
					break;
				}

				// Regular expression pattern validation if value exists
				if( isset( $value['pattern'] ) && strlen( $aData[$key] ) > 0 ) {
					if( !self::isRegex( $aData[$key], '/' . $value['pattern'] . '/' ) ) {
						$aErr[$sErrGroup][$key] = array(
							'title' => $value['title'],
							'type' => 'pattern'
						);
					}
				}
			}
		}

		return $aErr;
	}

	public static function validateRecaptcha( $sErrGroup ) {
		$aErr = array();


		if( (RECAPTCHA_ENABLE === true) && (RECAPTCHA_INVISIBLE === false) ) {
			if( !isset( $_POST['g-recaptcha-response'] ) ) {
				$aErr[$sErrGroup]['g-recaptcha'] = array(
					'title' => _( '"I\'m not a robot"' ),
					'type' => 'string'
				);
			} else {
				$sCaptchaResponse = file_get_contents( 'https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $_POST['g-recaptcha-response'] . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
				$sCaptchaResponse = json_decode( $sCaptchaResponse, true );

				if( !isset( $sCaptchaResponse['success'] ) || $sCaptchaResponse['success'] !== true ) {
					$aErr[$sErrGroup]['g-recaptcha'] = array(
						'title' => _( '"I\'m not a robot"' ),
					'type' => 'string'
					);
				}
			}
		}

		return $aErr;
	}

	public static function isSwedishBankAccount( $value ) {
		return self::isRegex( $value, '/^[0-9]{4,5}[-]{0,1}[0-9]{4,10}$/ix' );
	}

	public static function isAlnum( $value ) {
		return ctype_alnum( $value );
	}

	public static function isAlpha( $value ) {
		return ctype_alpha( $value );
	}

	public static function isBarcode( $value ) {}

	public static function isBetween( $value, $values ) {
		list( $iMin, $iMaximum ) = $values;

		if( $value < $iMin ) {
			return false;
		}

		if( $value > $iMaximum ) {
			return false;
		}

		return true;
	}

	public static function isBgAccount( $value ) {
		$bResult = self::isRegex( $value, '/^[0-9]{3,4}-[0-9]{4}$/ix' );

		if( $bResult ) {
			return self::isLuhn( $value );
		}
		return false;
	}

	public static function isBoolean( $value ) {
		return is_bool( $value );
	}

	public static function isCcNum( $value ) {}

	public static function isDate( $value, $sFormat = null ) {

		// Default format YYYY-MM-DD
		if( null === $sFormat ) {
			if( strlen($value) !== 10 ) {
				return false;
			}

			$iYearStart = 0;
			$iYearLen = 4;
			$iMonthStart = 5;
			$iMonthLen = 2;
			$iDayStart = 8;
			$iDayLen = 2;
		} else {
			$iFormatLen = strlen( $sFormat );

			if( strlen($value) !== $iFormatLen ) {
				return false;
			}

			$iYearStart = null;
			$iYearLen = 0;
			$iMonthStart = null;
			$iMonthLen = 0;
			$iDayStart = null;
			$iDayLen = 0;

			for( $i = 0; $i < $iFormatLen; $i++ ) {
				switch($sFormat[$i]) {
					case 'y':
					case 'Y':
						if( null === $iYearStart ) $iYearStart = $i;
						$iYearLen++;
						break;
					case 'm':
					case 'M':
						if( null === $iMonthStart ) $iMonthStart = $i;
						$iMonthLen++;
						break;
					case 'd':
					case 'D':
						if( null === $iDayStart ) $iDayStart = $i;
						$iDayLen++;
						break;
				}
			}
		}

		$iYear = substr( $value, $iYearStart, $iYearLen );
		$iMonth = substr( $value, $iMonthStart, $iMonthLen );
		$iDay = substr( $value, $iDayStart, $iDayLen );

		return checkdate( $iMonth, $iDay, $iYear );
	}

	public static function isDigits( $value ) {
		return ctype_digit( $value );
	}

	public static function isEmail( $value ) {
		if( function_exists('filter_var') ) {
			return filter_var( $value, FILTER_VALIDATE_EMAIL ) !== false;
		} else {
			return self::isRegex( $value, '/^[^@\s]+@([-a-z\d]+\.)+([a-z]{2}|com|net|edu|org|gov|mil|int|biz|pro|info|arpa|aero|coop|name|museum)$/ix' );
		}
	}

	public static function isFloat( $value ) {
		/*
		$temp = $value;
		setType( $value, 'float' );
		return ( (string) $value === (string) $temp );
		*/
		if( is_float($value) === true ) return true;
		return filter_var( $value, FILTER_VALIDATE_FLOAT ) !== false;
	}

	public static function isGreaterThan( $value, $iMin ) {
		return ( $value > $iMin );
	}

	public static function isHex( $value ) {
		return ctype_xdigit( $value );
	}

	public static function isHostname( $value ) {
		return self::isIp( gethostbyname($value) );
	}

	public static function isInArray( $value, $array ) {
		return in_array( $value, $array );
	}

	public static function isInt( $value ) {
		if( is_int($value) ) return true;
		$temp = (int) $value;
		if( (string) $value === (string) $temp ) return true;
		return ctype_digit( $value );
	}

	public static function isIp( $value ) {
		/*
		if( ($iLongIp = ip2long($value)) === false ) {
			return false;
		}

		return ( $value == long2ip($iLongIp) );
		*/
		return filter_var( $value, FILTER_VALIDATE_IP ) !== false;
	}

	public static function isLessThan( $value, $iMax ) {
		return ( $value < $iMax );
	}

	public static function isNotEmpty( $value ) {
		return !empty( $value );
	}

	public static function isCompanyPin( $value ) {
		$value = str_replace( array('-', '+'), array('', ''), $value );
		if( strlen($value) != 10 ) return false;
		if( !is_numeric($value) ) return false;

		$iChecksum = $value[9];
		$sDigits = '';
		$iSum = 0;

		$iLen = strlen( $value ) - 1;
		for( $i = 0; $i < $iLen; $i++ ) {
			if( $i % 2 ) {
				$sDigits .= $value[$i] * 1;
			} else {
				$sDigits .= $value[$i] * 2;
			}
		}

		$iLen = strlen( $sDigits );
		for( $i = 0; $i < $iLen; $i++ ) {
			$iSum += $sDigits[$i];
		}
		return (($iSum + $iChecksum) % 10) === 0;
	}

	public static function isLuhn( $value ) {
		// Check with Luhn algorithm
		// https://sv.wikipedia.org/wiki/Luhn-algoritmen

			$aChars = str_split( preg_replace('/[^0-9]/', '', strrev($value)) );
			$iCharSum = 0;
			$iMultiplier = 1;
			foreach( $aChars as $sChar ) {
				$iThisSum = $sChar * $iMultiplier;
				$iCharSum += ( ($iThisSum > 9) ? ($iThisSum - 9) : $iThisSum );
				$iMultiplier = ( ($iMultiplier == 1) ? 2 : 1 );
			}

			if( ($iCharSum == 0) || (($iCharSum % 10) != 0) ) {
				return false;
			} else {
				return true;
			}
	}

	public static function isOcrNumber( $value ) {
		$bResult = self::isRegex( $value, '/^[0-9]*$/ix' );

		if( $bResult ) {
			return self::isLuhn( $value );
		}
		return false;
	}

	public static function isPgAccount( $value ) {
		$bResult = self::isRegex( $value, '/^[0-9]*-[0-9]$/ix' );

		if( $bResult ) {
			return self::isLuhn( $value );
		}
		return false;
	}

	public static function isPhoneNumber( $value ) {
		return self::isRegex( $value, '/^\(?\+?[0-9]{0,2}\)?[0-9\- ]*$/ix' );
	}

	public static function isPin( $value ) {
		$value = str_replace( array('-', '+'), array('', ''), $value );
		if( strlen($value) != 10 ) return false;
		if( !is_numeric($value) ) return false;

		// Check validity of the date
		$iMonth = (int)substr( $value, 2, 2 );
		$iDay = (int)substr( $value, 4, 2 );
		$iYear = (int)substr($value, 0, 2);
		$iYear += ( ($iYear < date('y')) ? 2000 : 1900 );
		if( !checkdate($iMonth, $iDay, $iYear) ) return false;

		$iSex = $value[8];
		$iChecksum = $value[9];
		$sDigits = '';
		$iSum = 0;

		$iLen = strlen( $value ) - 1;
		for( $i = 0; $i < $iLen; $i++ ) {
			if( $i % 2 ) {
				$sDigits .= $value[$i] * 1;
			} else {
				$sDigits .= $value[$i] * 2;
			}
		}

		$iLen = strlen( $sDigits );
		for( $i = 0; $i < $iLen; $i++ ) {
			$iSum += $sDigits[$i];
		}
		return (($iSum + $iChecksum) % 10) === 0;
	}

	public static function isRegex( $value, $pattern ) {
		return preg_match( $pattern, $value );
	}

	public static function isStringLength( $value, $values ) {
		list( $iMin, $iMax ) = $values;
		$iLength = strlen( $value );

		if( $iLength < $iMin ) {
			return false;
		}

		if( ($iLength > $iMax) && (null !== $iMax) ) {
			return false;
		}

		return true;
	}

	public static function isTime( $value, $sFormat = null ) {

		// Default format HH:MM:SS
		if( null === $sFormat ) {
			if( strlen($value) !== 8 ) {
				return false;
			}

			$iHourStart = 0;
			$iHourLen = 2;
			$iMinuteStart = 3;
			$iMinuteLen = 2;
			$iSecondStart = 6;
			$iSecondLen = 2;
		} else {
			$iFormatLen = strlen( $sFormat );

			if( strlen($value) !== $iFormatLen ) {
				return false;
			}

			$iHourStart = null;
			$iHourLen = 0;
			$iMinuteStart = null;
			$iMinuteLen = 0;
			$iSecondStart = null;
			$iSecondLen = 0;

			for( $i = 0; $i < $iFormatLen; $i++ ) {
				switch($sFormat[$i]) {
					case 'h':
					case 'H':
						if( null === $iHourStart ) $iHourStart = $i;
						$iHourLen++;
						break;
					case 'm':
					case 'M':
						if( null === $iMinuteStart ) $iMinuteStart = $i;
						$iMinuteLen++;
						break;
					case 's':
					case 'S':
						if( null === $iSecondStart ) $iSecondStart = $i;
						$iSecondLen++;
						break;
				}
			}
		}

		$iHour = substr( $value, $iHourStart, $iHourLen );
		$iMinute = substr( $value, $iMinuteStart, $iMinuteLen );
		$iSecond = substr( $value, $iSecondStart, $iSecondLen );

		if( $iHour > 23 || $iHour < 0 ) return false;
		if( $iMinute > 59 || $iMinute < 0 ) return false;
		if( $iSecond > 59 || $iSecond < 0 ) return false;

		return true;
	}

	public static function isUrl( $value ) {
		return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
	}

}
