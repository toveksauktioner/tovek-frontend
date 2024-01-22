<?php

function arrayToSingle( $aMulti, $key = null, $value = null ) {
	$aSingle = array();

	if( $key === null ) { // Use an indexed array
		if( $value === null ) {
			foreach( $aMulti as &$entry ) {
				$aSingle[] = current( $entry );
			}
		} else {
			foreach( $aMulti as $entryKey => &$entry ) {
				$aSingle[] = $entry[$value];
			}
		}
	} elseif( $key === true ) { // Key is exactly true, which means we'd like to keep the keys
		if( $value === null ) {
			foreach( $aMulti as $entryKey => &$entry ) {
				$aSingle[ $entryKey ] = current( $entry );
			}
		} else {
			foreach( $aMulti as $entryKey => &$entry ) {
				$aSingle[ $entryKey ] = $entry[ $value ];
			}
		}
	} else { // Use a custom key
		if( $value === null ) {
			foreach( $aMulti as $entryKey => &$entry ) {
				$aSingle[ $entry[$key] ] = '';
			}
		} else {
			foreach( $aMulti as $entryKey => &$entry ) {
				$aSingle[ $entry[$key] ] = $entry[ $value ];
			}
		}
	}

	return $aSingle;
}

/**
 * Search in an array
 *
 * @param string $sType = value | key
 * @param string $sMode = indefinite | strict
 */
function arraySearch( $sType, $sString, $aArray, $sMode = 'indefinite' ) {
	if( !isset($GLOBALS['arraySearch']) ) {
		if( !isMultiArray($aArray) ) $aArray = array( $aArray );
		$GLOBALS['arraySearch'] = array();
		$bLoop = false;
	} else {
		$bLoop = true;
	}

	foreach( $aArray as $key => $mValue ) {
		if( is_array($mValue) ) {
			unset( $aArray[$key] );
			arraySearch( $sType, $sString, $mValue, $sMode );
		}
	}

	/**
	 * Format data based on search mode
	 */
	if( $sMode == 'indefinite' ) {
		$sString = strtolower( $sString );
		$aArray = array_map( 'mb_strtolower', $aArray );
		$aArray = array_change_key_case( $aArray, CASE_LOWER );
	}
	if( $sMode == 'strict' ) {
		// Do nothing
	}

	/**
	 * Search based on type
	 */
	if( $sType == 'key' ) {
		$mResult = array_flip( preg_filter( '~' . $sString . '~', '$0', array_flip($aArray) ) );
		if( !empty($mResult) ) {
			$GLOBALS['arraySearch'][] = $mResult;
		}
	}
	if( $sType == 'value' ) {
		$mResult = preg_filter( '~' . $sString . '~', '$0', $aArray );
		if( !empty($mResult) ) {
			$GLOBALS['arraySearch'][] = $mResult;
		}
	}

	if( !$bLoop ) {
		$aResult = $GLOBALS['arraySearch'];
		unset( $GLOBALS['arraySearch'] );
		return $aResult;
	}

	return;
}

function isMultiArray( $aArray ) {
    $aArray2 = array_filter( $aArray,'is_array' );
    if( count($aArray2) > 0 ) return true;
    return false;
}

function valueToKey( $key1, $aArray ) {
	$aNewArray = array();
	foreach( $aArray as $key2 => $entry ) {
		if( !empty($entry[$key1]) ) {
			$aNewArray[$entry[$key1]] = $entry;
		}
	}
	return $aNewArray;
}

function groupByValue( $key1, $aArray ) {
	$aNewArray = array();
	foreach( $aArray as $key2 => $entry ) {
		if( !empty($entry[$key1]) ) {
			if( empty($aNewArray[$entry[$key1]]) ) {
				$aNewArray[$entry[$key1]] = array();
			}
			$aNewArray[$entry[$key1]][] = $entry;
		}
	}
	return $aNewArray;
}

function dataToParentChildArray( $aArray, $key1, $key2 = null ) {
	$aReturn = array();
	foreach( (array) $aArray as $entry ) {
		if( $key2 == null )
			$aReturn[$entry[$key1]][] = $entry;
		else
			$aReturn[$entry[$key1]][] = $entry[$key2];
	}

	return $aReturn;
}

function decimalToPercentage( $fDecimal ) {
	$fDecimal *= 100;
	if( $fDecimal > 100 ) $fDecimal = 100;
	return $fDecimal;
}

function percentageToDecimal( $iPercentage ) {
	if( $iPercentage > 100 ) $iPercentage = 100;
	return $iPercentage / 100;
}

function createArrayCombinations( $a1, $a2 ) {
	$aCombinations = array();
	$iCount = 0;
	foreach( $a1 as $value1 ) {
		#var_dump($value1);
		foreach( $a2 as $value2 ) {
			if( is_array($value1) ) {
				$aCombinations[$iCount] = array_merge( $value1, array($value2) );
			} else {
				$aCombinations[$iCount] = array(
					$value1,
					$value2
				);
			}
			++$iCount;
		}
		#next( $a1 );
	}
	return $aCombinations;
}

function wordStr( $sText, $iLength = 100, $sSuffix = '...', $bPreserveWords = true ) {
	if( mb_strlen($sText) <= $iLength ) return $sText;

	$sText = mb_substr($sText, 0, $iLength );
	return ( $bPreserveWords ? mb_substr($sText, 0, strrpos($sText, ' ')) : $sText ) . $sSuffix;
}

function getRemoteAddr() {
	$sIp = $_SERVER['REMOTE_ADDR'];
	if( ($sIp == '127.0.0.1' || $sIp == $_SERVER['SERVER_ADDR'] || empty($sIp)) && isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
		$aIps = explode( ', ', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$sIp = $aIps[0];
	}
	return $sIp;
}

/**
 * Maps a value from one number range to another
 * @param float $fValue The value to map
 * @param float $fFromLow The lowest value in first number range
 * @param float $fFromHigh The higest value in first number range
 * @param float $fToLow The lowest value in second number range
 * @param float $fToHigh The highest value in second number range
 * @return float
 */
function mapValueFromRangeToRange( $fValue, $fFromLow, $fFromHigh, $fToLow, $fToHigh ) {

	$fFromSpread = $fFromHigh - $fFromLow;
	if ( $fFromSpread <= 0 ) $fFromSpread = 1;

	$fToSpread = $fToHigh - $fToLow;
	if ( $fToSpread < 0 ) $fToSpread = 1;

	$fToStep = $fToSpread / $fFromSpread;

	return ( $fToLow + ( ( $fValue - $fFromLow ) * $fToStep ) );
}

function csvToArray( $sFile, $bIndexLine = true ) {
	$iLineLimit = 0; # 0 = unlimited, for PHP 5.0.4 and greater..
	$aArray = array();
	$aKeys = array();

	if( ($oFile = fopen($sFile, "r")) !== false ) {
		$iCounter = 0;
		while( ($aData = fgetcsv($oFile, $iLineLimit, ';')) !== false ) {
			if( $iCounter == 0 && $bIndexLine === true ) {
				$iDuplicates = 0;
				foreach( $aData as $entry ) {
					if( !in_array($entry, $aKeys) ) {
						$aKeys[] = $entry;
					} else {
						$aKeys[] = $entry . $iDuplicates;
						++$iDuplicates;
					}
				}
				++$iCounter;
				continue;
			} else {
				if( !empty($aKeys) ) {
					foreach( $aData as $key => $entry ) {
						$aArray[$iCounter][$aKeys[$key]] = !empty( $aData[$key] ) ? $aData[$key] : '';
					}
				} else {
					foreach( $aData as $key => $entry ) {
						$aArray[$iCounter][] = !empty( $aData[$key] ) ? $aData[$key] : '';
					}
				}
			}
			++$iCounter;
		}
		fclose($oFile);

		return $aArray;
	} else {
		return false;
	}
}

/*
 * Check if the date is a work day
 * Date can be mixed - if integer it is considerd as time and if string it is considered human readable date Y-m-d
 * Right now just exclude saturday and sunday
 * ToDo: Check for national holidays
 */
function isWorkday( $mDate ) {
	$bIsWorkday = true;

	if( is_string($mDate) ) $mDate = strtotime( $mDate );

	// Check if it is saturday or sunday
	$iDayOfWeek = date( 'w', $mDate );
	if( ($iDayOfWeek == 0) || ($iDayOfWeek == 6) ) {
		$bIsWorkday = false;
	}

	return $bIsWorkday;
}


/*
 * Check the number of workdays from one date to another
 * Dates can be mixed - if integer it is considerd as time and if string it is considered human readable date Y-m-d
 * Logic: number of workdays from date on thursday to tuesday next week is 3
 * So the startdate is not counted, but the enddate is
 * If startdate is null, today is startdate
 * If enddate is null, today is enddate
 */
function numberOfWorkdays( $mEndDate = null, $mStartDate = null ) {
	if( empty($mStartDate) ) 	$mStartDate = time();
	if( empty($mEndDate) ) 		$mEndDate = time();


	// Make dates into time
	if( is_string($mStartDate) ) 	$mStartDate = strtotime( $mStartDate );
	if( is_string($mEndDate) ) 		$mEndDate = strtotime( $mEndDate );

	// Make sure no time is added to dates
	$mStartDate = strtotime( date('Y-m-d', $mStartDate) );
	$mEndDate 	= strtotime( date('Y-m-d', $mEndDate) );

	$iNumberOfWorkdays = 0;

	// Start on next day
	$mStartDate = strtotime( '+1 day', $mStartDate );
	while( $mEndDate >= $mStartDate ) {
		$iNumberOfWorkdays += (int) isWorkday( $mStartDate );
		$mStartDate = strtotime( '+1 day', $mStartDate );
	}

	return $iNumberOfWorkdays;
}

/*
 * Create OCR number
 * Just add the control number last
 */
function createOcrNumber( $value ) {
	return (int) $value . getLuhnNumber( $value );
}

/*
 * Get luhn algorithm control number
 */
function getLuhnNumber( $value ) {
	// https://sv.wikipedia.org/wiki/Luhn-algoritmen

		$aChars = str_split( preg_replace('/[^0-9]/', '', strrev($value)) );
		$iCharSum = 0;
		$iMultiplier = 2;
		foreach( $aChars as $sChar ) {
			$iThisSum = $sChar * $iMultiplier;
			$iCharSum += ( ($iThisSum > 9) ? ($iThisSum - 9) : $iThisSum );
			$iMultiplier = ( ($iMultiplier == 1) ? 2 : 1 );
		}

		return ( 10- ($iCharSum % 10) );
}
