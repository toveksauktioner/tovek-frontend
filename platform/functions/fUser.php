<?php

function generateRandomPass( $iPassLength, array $aCharacters = null ) {
	// If no custom characters is included
	if( $aCharacters === null ) {
		// The characters. Chars like 1li0Oo is not included to avoid confusion
		$aCharacters	= array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'k', 'm', 'n', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '2', '3', '4', '5', '6', '7', '8', '9');
	}

	// Password will be appended to this string
	$sPass = '';

	// Max character index
	$iMaxCharacterIndex	= count( $aCharacters ) - 1;

	// Seed
	srand( (double) microtime() * 1000000 );

	for( $i = 0; $i < $iPassLength; $i++ ) {
		$sPass .= $aCharacters[ rand( 0, $iMaxCharacterIndex ) ];
	}

	return $sPass;
}

function getUserIp() {
	if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	return $_SERVER['REMOTE_ADDR'];
}

function getUserLongIp() {
	return (int) sprintf('%u', ip2long(getUserIp()) );
}

function hashUserPass( $sPassword, $sSalt ) {
	return hash( 'sha512', md5($sSalt) . $sPassword . USER_PASS_SALT );
}
