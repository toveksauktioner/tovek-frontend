<?php

/**
 * Sets vatInclusion
 */
function setVatInclusion( $mVatInclusion ) {
	if( ctype_digit($mVatInclusion) ) {
		$_SESSION['vatInclusion'] = $mVatInclusion == 1 ? true : false;

	} elseif( is_string($mVatInclusion) ) {
		$_SESSION['vatInclusion'] = $mVatInclusion == 'true' ? true : false;

	} elseif( is_bool($mVatInclusion) ) {
		$_SESSION['vatInclusion'] = $mVatInclusion;

	} else {
		// Unkown format
		return false;
	}
	return $_SESSION['vatInclusion'];
}

/**
 * return price calculated by given params
 */
function calculatePrice( $fPrice, $aPreParams = array() ) {
	$aPreParams += array(
		'profile' => 'default',
		'additional' => array() # Used to override profile params
	);

	// Add profile params
	$aParams = $_SESSION['money']['profile'][ $aPreParams['profile'] ];

	if( !empty($aPreParams['additional']) ) {
		// Override profile params
		$aParams = array_merge( $aParams, $aPreParams['additional'] );
	}

	// Override VAT param by customer group
	if( !empty($_SESSION['customer']['groupId']) ) {
		$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
		$aGroupData = current( $oCustomer->readCustomerGroup( 'groupVatInclusion', $_SESSION['customer']['groupId'] ) );
		if( !empty($aGroupData['groupVatInclusion']) ) {
			// Override profile VAT param
			$aParams['vatInclude'] = $aGroupData['groupVatInclusion'] == 'yes' ? true : false;
		}
	}

	if( $aParams['vatInclude'] === true && $aParams['vat'] !== null ) {
		// Price as float format upon VAT included
		$fPrice *= 1 + (float) $aParams['vat'];
	}

	if( $aParams['discount'] !== null ) {
		// Select max discount
		$iBestDiscount = max( (array) $aParams['discount'] );
		$fPrice -= $fPrice * ( $iBestDiscount / 100 );
	}

	if( $aParams['decimals'] === null ) {
		// Set decimal param to the number of chars after current languages decimal point
		$aParams['decimals'] = 0;
		$aNumbers = explode( nl_langinfo(RADIXCHAR), $fPrice );
		if( !empty($aNumbers[1]) && $aNumbers[1] > 0 ) {
			$aParams['decimals'] = mb_strlen( $aNumbers[1] );
		}
	}

	if( !empty($aParams['format']['currency']) && !empty($aParams['currencyRate']) ) {
		// Recalculate price with a currency in mind
		$fPrice /= $aParams['currencyRate'];
	}

	// The power of 10 is for rounding of currencies
	$iPower = pow( 10, $aParams['decimals'] );
	$fPrice = round( round( $fPrice * $iPower, $aParams['decimals'] ) ) / $iPower;
	if( $aParams['multiplier'] != 1 ) {
		$fPrice = ceil( $fPrice * (float) $aParams['multiplier'] );
	}

	if( !empty($aParams['format']['money']) ) {
		$oFormatter = new NumberFormatter( $GLOBALS['userLang'], NumberFormatter::CURRENCY );
	} else {
		$oFormatter = new NumberFormatter( $GLOBALS['userLang'], NumberFormatter::DECIMAL );
	}

	$oFormatter->setAttribute( NumberFormatter::FRACTION_DIGITS, $aParams['decimals'] );

	if( $aParams['currencyFormat'] == 'i' ) {
		$oFormatter->setSymbol( NumberFormatter::CURRENCY_SYMBOL, "SEK" );
	}

	$fPrice = $oFormatter->format( $fPrice );

	if( !empty( $aParams['format']['vatLabel']) ) {
		// VAT label suffix
		$fPrice .= ' (' . ( $aParams['vatInclude'] ? _( 'incl. VAT' ) : _( 'excl. VAT' ) ) . ')';
	}

	return $fPrice;
}

/**
 * return price without vat
 *
 * @param float $fPriceWithVat Price with VAT
 * @param mixed $mVatProcentage VAT procentage as float or int ( e.g 0.25 or 25 )
 * @return float Price without VAT
 */
function removeVat( $fPriceWithVat, $mVatProcentage = 0.25 ) {
	if( empty($fPriceWithVat) || empty($mVatProcentage) ) return false;

	// Try to fix VAT arg if it's not formated properly
	$mVatProcentage = str_replace( ',', '.', $mVatProcentage );

	if( mb_strpos($mVatProcentage, '.') === false ) {
		// Convert int VAT to float type
		$mVatProcentage /= 100;
	}

	$fPriceCalulated = $fPriceWithVat * ( 100 / ( 100+(100 * $mVatProcentage) ) );

	return (float) $fPriceCalulated;
}

/**
 * return discount price without discount
 *
 * @param float $fPrice Price with discount
 * @param mixed $mDiscount float or int ( e.g 0.25 or 25 )
 * @param integer $iDecimals
 * @return string price without discount
 *
 * note: This functions has a potential margin error
 * diferencial of 1 decimal
 */
function invertedDiscount( $fPrice, $mDiscount, $iDecimals = 2 ) {
	return number_format( round( $fPrice / ( $mDiscount > 1 ? ( 1 - $mDiscount / 100 ) : $mDiscount ), ($iDecimals - 1) ), $iDecimals );
}
