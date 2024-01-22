<?php

require_once PATH_CONFIG . '/cfError.php';

class clErrorHandler {

	public static $aErr = array();
	public static $aValidationErr = array();

	public static function setError( $iLevel, $sMsg, $sFilename = '', $iLineNr = '' ) {
		if( error_reporting() == 0 ) {
			return;
		} else {
			$sMsg = trim( $sMsg );
			if( $GLOBALS['debug'] ) {
				switch ( $iLevel ) {
					case E_USER_ERROR:
						self::$aErr[] = sprintf( _('Fatal Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
						break;
					case E_USER_WARNING:
						self::$aErr[] = sprintf( _('Warning: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
						break;
					case E_USER_NOTICE:
						self::$aErr[] = sprintf( _('Notice: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
						break;
					default:
						self::$aErr[] = sprintf( _('Unknown Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
						break;
				}
			} else {
				self::$aErr[] = $sMsg;
			}
			
			if( $GLOBALS['logErrors'] ) {
				switch ( $iLevel ) {
					case E_USER_ERROR:
						$sMessage = sprintf( _('Fatal Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
						break;
					case E_USER_WARNING:
						$sMessage = sprintf( _('Warning: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
						break;
					case E_USER_NOTICE:
						$sMessage = sprintf( _('Notice: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
						break;
					default:
						$sMessage = sprintf( _('Unknown Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
						break;
				}
				clLogger::logWithTruncation( $sMessage, 'errors.log' );
			}
		}

		return true;
	}

	public static function setException( $oException ) {
		// For PHP 7+
		if( phpversion() >= 7.0 && $GLOBALS['debug'] === true ) {
			echo 'Uncaught exception: ' . $oException->getMessage() . ' in ' . $oException->getFile() . ' on line ' . $oException->getLine() . "\n";
		}
		
		if( strpos($oException->getMessage(), 'noAccess') !== false ) {
			self::$aErr[] = sprintf( _('Access error: "%s" (You have not this permission)'), $oException->getMessage() );
		} elseif( $GLOBALS['debug'] === true ) {
			self::$aErr[] = sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
		} else {
			self::$aErr[] = $oException->getMessage();
		}
		
		if( $GLOBALS['logExceptions'] ) {
			clLogger::logWithTruncation( sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), 'exceptions.log' );
		}
	}

	public static function setValidationError( $aErr ) {
		self::$aValidationErr += $aErr;

	}

	public static function getValidationError( $errGroup = null ) {
		$aErr = array();

		if( $errGroup === null ) $errGroup = array_keys( self::$aValidationErr );

		foreach( (array) $errGroup as $sErrGroup ) {
			if( !array_key_exists($sErrGroup, self::$aValidationErr) ) continue;

			foreach( self::$aValidationErr[$sErrGroup] as $key => $value ) {
				$sMsg = isset( $GLOBALS['errorMsg']['validation'][$value['type']] ) ? $GLOBALS['errorMsg']['validation'][$value['type']] : $GLOBALS['errorMsg']['validation']['default'];
				$aErr[$key] = sprintf( $sMsg, $value['title'] );
			}
		}

		return $aErr;
	}

}

set_error_handler( 'clErrorHandler::setError' );
set_exception_handler( 'clErrorHandler::setException' );
