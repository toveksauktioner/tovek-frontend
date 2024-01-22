<?php

/*
 * This registry act both as a Registry with a Factory method and a Service Locator.
 */
class clRegistry {

	public static $aEntries = array();

	public static function set( $sKey, $value ) {
		self::$aEntries[$sKey] = $value;
	}

	public static function get( $sKey, $sClassPath = null ) {
		if ( !array_key_exists($sKey, self::$aEntries) ) {
			$aParams = func_get_args();
			if( !self::$aEntries[$sKey] = call_user_func_array(array('clFactory', 'create'), $aParams) ) return false;
		}
		return self::$aEntries[$sKey];
	}
	
}
