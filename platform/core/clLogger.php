<?php

require_once PATH_CORE . '/clModuleBase.php';

class clLogger extends clModuleBase {

	public $oDao;

	public function __construct() {
		$this->sModuleName = 'Logger';
		$this->sModulePrefix = 'log';
		
		$this->oDao = clRegistry::get( 'clLoggerDao' . DAO_TYPE_DEFAULT_ENGINE );
		
		$this->initBase();
	}

	/**
	 * Log message to file or database
	 *	 
	 * The format is as following: "Datetime" "tab character" "message" "newline character", with no spaces in between.
	 * If the message is an array it is converted to a string with var_export().
	 * If the message is an object it is serialized
	 * The message is appended at the bottom of the file
	 *
	 * File:
	 * @param mixed $sMsg Message to write
	 * @param string $sFilename Filename to write to
	 * @return mixed Number of bytes that were written to the file, or FALSE on failure.
	 *
	 * Database:
	 * @param mixed $sMsg Message to write
	 * @param string $sFilename logLabel as log type / identifier
	 * @return mixed Number of inserted id, or FALSE on failure.
	 */
	public static function log( $mMsg = '', $sFilename = 'general.log' ) {
		if( is_array($mMsg) ) {
			$sMsg = var_export( $mMsg, true );
		} elseif( is_object($mMsg) ) {
			$sMsg = serialize($mMsg);
		} else {
			$sMsg = $mMsg;
		}
			
		if( $GLOBALS['logEngine'] == 'file' ) {			
			return file_put_contents( PATH_LOG . '/' . $sFilename, date('Y-m-d H:i:s') . "\t" . $sMsg . "\r\n", FILE_APPEND );
		}
		if( $GLOBALS['logEngine'] == 'database' ) {			
			if( strpos($sFilename, '.log') ) {
				$sFilename = substr( $sFilename, 0, (strlen($sFilename) - 4) );
			}
			$oDao = clRegistry::get( 'clLoggerDao' . DAO_TYPE_DEFAULT_ENGINE );
			return $oDao->create( array(
				'logLabel' => $sFilename,
				'logData' => $sMsg,
				'logCreated' => date( 'Y-m-d H:i:s' )
			) );
		}
		return false;
	}
	
	/**
	 * Rotate a logfile by size
	 *
	 * Moves a file and appends a unix timestamp right before the file extension
	 * 
	 * @param string $sFilename File to rotate
	 * @param string $sFileSize Filesize in bytes, or php shorthands "8M", "8K" and so on.
	 * @return bool Returns true if success, and false on no need to rotate or failure
	 */
	public static function logRotate( $sFilename = 'general.log', $sFileSize = '32M' )  {		
		// Skip this function upon 'database' as logEngine
		if( $GLOBALS['logEngine'] == 'database' ) return true;
		
		if( !file_exists( PATH_LOG . '/' . $sFilename ) ) return false; // Logfile does not exist
		
		if( !is_numeric($sFileSize) ) {
			// Parse string for size
			
			$aFilesizesToMultiplier = array(
				'b' => 1, 'byte' => 1, 'bytes' => 1,
				'k' => 1024, 'kilobyte' => 1024, 'kb' => 1024, 'kilobytes' => 1024,
				'm' => 1048576, 'megabyte' => 1048576, 'mb' => 1048576, 'megabytes' => 1048576,
				'g' => 1073741824, 'gigabyte' => 1073741824, 'gb' => 1073741824, 'gigabytes' => 1073741824				
			);
			
			// Try washing the variable a little
			$sFilesizeUnit = str_replace( array(
				' ',
				'.',
				','
			), '', $sFileSize );
			
			// Remove digits to get size unit, and make characters lowercase
			$sFilesizeUnit = mb_strtolower( preg_replace('[\d]', '', $sFilesizeUnit) );
			
			if( !array_key_exists($sFilesizeUnit, $aFilesizesToMultiplier) ) return false; // Not know filesize format
			$iSize = preg_replace('[\D]', '', $sFileSize);
			
			$iSize = $iSize * $aFilesizesToMultiplier[ $sFilesizeUnit ];				
		} else {
			// This is bytes
			$iSize = (int) $sFileSize;
		}
		
		if( filesize( PATH_LOG . '/' . $sFilename ) >= $iSize ) {
			// Rotate
			$iPositionOfDot = mb_strlen( strrchr( $sFilename, '.' ));
			$sNewFilename = mb_substr( $sFilename, 0, $iPositionOfDot * -1 ) . '.' . time() . mb_substr( $sFilename, $iPositionOfDot * -1 );

			return rename( PATH_LOG . '/' . $sFilename, PATH_LOG . '/' . $sNewFilename );
		}

		return false;
	}

	/**
	 * Log message to file, and if the file has to many lines then truncate those lines
	 * 
	 * The format is as following: "Datetime" "tab character" "message" "newline character", with no spaces in between.
	 * If the message is an array it is converted to a string with var_export().
	 * The message is appended at the bottom of the file.
	 * If the logfile has more rows than the max argument, truncate the file.
	 * WARNING: This function does not lock the file, data may be lost if truncation takes a long time
	 * WARNING: Very large files will take a long time to truncate. This function does try to check
	 * 			if truncation will take more memory than available, but do not rely on this (php overhead etc)
	 * 
	 * @param mixed $sMsg Message to write
	 * @param string $sFilename Filename to write to
	 * @param integer $iMaxLines The number of lines before truncating. Defaults to 10.000
	 * @return mixed Number of bytes that were written to the file, or FALSE on failure.
	 **/
	public static function logWithTruncation( $mMsg = '', $sFilename = 'general.log', $iMaxLines = 10000 ) {
		// Skip this function upon 'database' as logEngine
		if( $GLOBALS['logEngine'] == 'database' ) return true;
		
		if( !is_numeric($iMaxLines) ) return false;
		
		if( is_array($mMsg) ) {
			$sMsg = var_export( $mMsg, true );
		} else {
			$sMsg = $mMsg;
		}
		
		$rHandle = fopen( PATH_LOG . '/' . $sFilename, 'c+' );		
		if( $rHandle ) {
			// Check number of lines
			$iLineCount = 0;
			while( !feof($rHandle) ) {
				fgets($rHandle);
				++$iLineCount;
			}
			
			// Add the new row at end of file
			if( !fputs( $rHandle, date('Y-m-d H:i:s') . "\t" . $sMsg . "\r\n" ) ) {
				throw new Exception( sprintf( _( 'Could not write to file %s' ), PATH_LOG . '/' . $sFilename ) );
			}
			
			// Truncate lines
			if( $iLineCount > $iMaxLines ) {
				
				// Check if we have enough memory to truncate
				// This may or may not work, don't rely on it to much (due to php overhead etc)
				$iMemoryLimit = ini_get( 'memory_limit') ;
				if( !is_int($iMemoryLimit) ) {
					// Handle PHP shorthand byte values
					switch( strtolower( mb_substr( $iMemoryLimit, -1 ) ) ) {
						 case 'g': // The 'G' modifier is available since PHP 5.1.0
							$iMemoryLimit = mb_substr( $iMemoryLimit, 0, -1 ) * 1073741824;
							break;							
						 case 'm':
							$iMemoryLimit = mb_substr( $iMemoryLimit, 0, -1 ) * 1048576;
							break;							
						 case 'k':
							$iMemoryLimit = mb_substr( $iMemoryLimit, 0, -1 ) * 1024;
							break;						
						default:
							$iMemoryLimit = 134217728; // Unknown, assume default 128M
							break;
					}
				}
				if( $iMemoryLimit != -1 && filesize(PATH_LOG . '/' . $sFilename) >= ( $iMemoryLimit - memory_get_usage() ) ) {
					throw new Exception( sprintf( _( 'Logfile %s is to big to truncate' ), PATH_LOG . '/' . $sFilename ) );
				}
				
				$iLinesToTruncate = $iLineCount - $iMaxLines;
				
				rewind( $rHandle );
				// Store complete file in a string as it uses much less memory than arrays
				$sCompleteFile = '';
				$iSkipLines = 1;
				while( !feof($rHandle) ) {
					if( $iSkipLines < $iLinesToTruncate ) {
						fgets( $rHandle );
						++$iSkipLines;
						continue;
					}
					$sCompleteFile .= fgets( $rHandle );					
					++$iSkipLines;
				}
				
				ftruncate( $rHandle, 0 );
				rewind( $rHandle );
				fputs( $rHandle, $sCompleteFile );
			}
			fclose($rHandle);
			return true;
		} else {
			throw new Exception( sprintf( _( 'Could not open file %s' ), PATH_LOG . '/' . $sFilename ) );
		}
		
	}
	
	/**
	 * Read log from database based on label
	 **/
	public function readByLabel( $sLabel ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readByLabel( $sLabel );
	}

}