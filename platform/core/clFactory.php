<?php

class clFactory {

	// NOTE: This autoloader adopts the PSR-0 standard, which means prefixed (cl) classes will not autoload
	// Autoload (require class file if not already loaded) based on the requested class name
	// Example: When activated in the configuration, it will automatically load the file /platform/modules/PressImage/Category.php when calling the class PressImage_Category
	public static function autoload( $sClassName ) {

		// FUTURE: Add support for multiple directories by encoding and decoding json-arrays if we'd like to configure with constants
		// Search in these directories
		$aDirectories	= array(
			AUTOLOADER_SEARCH_PATH
		);

		// Replace each path delimiter to a directory delimiter. strtr is faster than str_replace
		$sPath	= '/' . strtr( $sClassName, AUTOLOADER_CLASS_DELIMITER, '/' ) . '.php';

		// Look in each directory
		foreach( $aDirectories as $sDirectory ) {

			// Is readable?
			if( is_readable( $sDirectory . $sPath ) ) {

				// Require the file
				require_once $sDirectory . $sPath;
				return true;
			}

			// For debugging
			//echo '<pre>File not found: ' . $sDirectory . $sPath . '</pre>';
		}

		// No file was found. This also means the class was not found.
		return false;
	}
	
	/*
	 * @return object
	 */
	public static function create( $sClassName, $sClassPath = null ) {
		if( !class_exists($sClassName) ) {
			self::loadClassFile( $sClassName, $sClassPath );
		}
		if( func_num_args() > 2 ) {
			$aParams = func_get_args();
			unset( $aParams[0], $aParams[1] );
			$oReflection = new ReflectionClass( $sClassName );
			return $oReflection->newInstanceArgs( $aParams );
		}
		return new $sClassName;
	}

	/*
	 * @return boolean
	 */	
	public static function classExists( $sClassName, $sClassPath = null ) {
		if ( class_exists($sClassName) ) return true;
		
		$sClassFile = $sClassPath . '/' . $sClassName . '.php';
		if ( !file_exists($sClassFile) ) return false;
		require_once $sClassFile;
		if ( class_exists($sClassName) ) return true;
		
		return false;
	}
	
	/*
	 * @return null
	 */
	public static function loadClassFile( $sClassName, $sClassPath = null ) {
		if( empty($sClassPath) ) $sClassPath = PATH_CORE;
		$sClassFile = $sClassPath . '/' . $sClassName . '.php';
		if( !file_exists($sClassFile) ) throw new Exception( 'fileNotFound - ' . $sClassName );
		require_once $sClassFile;
		return;
	}
	
}
