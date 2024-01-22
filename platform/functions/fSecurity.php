<?php

/**
 *
 * Encryption handling for ArgoPlatform
 * - Uses openssl encryption with 'AES-256-CBC' method
 * 
 */

/**
 * Encrypt string
 * @return string "base64 encrypt cipher"
 */
function encryptStr( $sString, $sKey = null ) {
	$sEncryptStr = false;
	
	// Secret key
	if( $sKey === null ) {
		// Use 'USER_PASS_SALT' as default
		$sKey1 = unpack('H*', USER_PASS_SALT);
		$sKey2 = unpack('H*', str_replace( ' ', '', preg_replace( '/[^A-Za-z0-9\-]/', '', USER_PASS_SALT ) ));		
		$sKey =  base_convert( decbin($sKey1[1] . $sKey2[1]), 16, 2 );
	}
    
    // IV - encrypt method 'AES-256-CBC' expects 16 bytes, else you will get a warning
    $sIv = substr( hash('sha256', strrev(USER_PASS_SALT)), 0, 16 );
	
	$sEncryptStr = openssl_encrypt( $sString, "AES-256-CBC", $sKey, 0, $sIv );
    
    return base64_encode( $sEncryptStr );
}

/**
 * Decrypt string
 * @return string $sPlainTextDec
 */
function decryptStr( $sString, $sKey = null ) {
	$sEncryptStr = false;
	
	// Secret key
	if( $sKey === null ) {
		// Use 'USER_PASS_SALT' as default
		$sKey1 = unpack('H*', USER_PASS_SALT);
		$sKey2 = unpack('H*', str_replace( ' ', '', preg_replace( '/[^A-Za-z0-9\-]/', '', USER_PASS_SALT ) ));		
		$sKey =  base_convert( decbin($sKey1[1] . $sKey2[1]), 16, 2 );
	}
    
    // IV - encrypt method 'AES-256-CBC' expects 16 bytes, else you will get a warning
    $sIv = substr( hash('sha256', strrev(USER_PASS_SALT)), 0, 16 );

    $sEncryptStr = openssl_decrypt( base64_decode($sString), "AES-256-CBC", $sKey, 0, $sIv );
	
    return $sEncryptStr;
}

/**
 *
 * As of PHP 7.2 deprecated version keept in file below for documentation.
 * 
 */

/**
 * Encrypt string
 * @return string "base64 encrypt cipher"
 */
//function encryptStr( $sString, $sKey = null ) {
//	if( $sKey === null ) {
//		// Use 'USER_PASS_SALT' as default
//		$sKey1 = unpack('H*', USER_PASS_SALT);
//		$sKey2 = unpack('H*', str_replace( ' ', '', preg_replace( '/[^A-Za-z0-9\-]/', '', USER_PASS_SALT ) ));		
//		$sKey =  base_convert( decbin($sKey1[1] . $sKey2[1]), 16, 2 );
//	}
//
//	// Pack data into binary string & get it's length
//	$sEncryptKey = pack( 'H*', $sKey );
//	$iEncryptKeySize =  strlen( $sEncryptKey );
//
//	// Create a random IV to use with CBC encoding
//    $iIvSize = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC );
//    $sIv = mcrypt_create_iv( $iIvSize, MCRYPT_RAND );
//
//	// Creates a cipher text compatible with AES
//	$sCipherText = mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $sEncryptKey, $sString, MCRYPT_MODE_CBC, $sIv );
//
//	// Prepend the IV for it to be available for decryption
//    $sCipherText = $sIv . $sCipherText;
//
//	return base64_encode( $sCipherText );
//}

/**
 * Decrypt string
 * @return string $sPlainTextDec
 */
//function decryptStr( $sString, $sKey = null ) {
//	if( $sKey === null ) {
//		// Use 'USER_PASS_SALT' as default
//		$sKey1 = unpack('H*', USER_PASS_SALT);
//		$sKey2 = unpack('H*', str_replace( ' ', '', preg_replace( '/[^A-Za-z0-9\-]/', '', USER_PASS_SALT ) ));		
//		$sKey =  base_convert( decbin($sKey1[1] . $sKey2[1]), 16, 2 );
//	}
//
//	// Pack data into binary string
//	$sEncryptKey = pack( 'H*', $sKey );
//
//	// Decode string
//	$sCipherTextDec = base64_decode( $sString );
//
//	// Size of the IV belonging to a specific cipher/mode combination
//	$iIvSize = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC );
//
//    // Retrieves the IV, iv_size should be created using mcrypt_get_iv_size()
//    $sIvDec = substr( $sCipherTextDec, 0, $iIvSize );
//
//    // Retrieves the cipher text (everything except the $iv_size in the front)
//    $sCipherTextDec = substr( $sCipherTextDec, $iIvSize );
//
//    // May remove 00h valued characters from end of plain text
//    $sPlainTextDec = mcrypt_decrypt( MCRYPT_RIJNDAEL_128, $sEncryptKey, $sCipherTextDec, MCRYPT_MODE_CBC, $sIvDec );
//
//    return $sPlainTextDec;
//}