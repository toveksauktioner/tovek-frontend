<?php

require_once PATH_CORE . '/geoIp2/src/autoload.php';
use GeoIp2\Database\Reader;

class clGeoIP2 {

	public function __construct() {
		$this->sModuleName = 'GeoIP2';
		$this->sModulePrefix = 'geoIP2';		
	}
	
	public function getCity( $sIpAddress = null ) {
		if( $sIpAddress === null ) {
			$sIpAddress = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		}
		
		$oReader = new GeoIp2\Database\Reader(PATH_CORE . '/geoIp2/db/GeoLite2-City.mmdb');		
		$oRecord = $oReader->city( $sIpAddress );
		
		return !empty($oRecord->city->name) ? $oRecord->city->name : false;
	}
	
	public function getSubdivision( $sIpAddress = null ) {
		if( $sIpAddress === null ) {
			$sIpAddress = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		}
		
		$oReader = new GeoIp2\Database\Reader(PATH_CORE . '/GeoIp2/db/geoLite2-City.mmdb');		
		$oRecord = $oReader->city( $sIpAddress );
		
		return !empty($oRecord->mostSpecificSubdivision->name) ? $oRecord->mostSpecificSubdivision->name : false;
	}
	
	public function getInformation( $sIpAddress = null ) {
		if( $sIpAddress === null ) {
			$sIpAddress = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		}
		
		$oReader = new GeoIp2\Database\Reader(PATH_CORE . '/geoIp2/db/GeoLite2-City.mmdb');		
		$oRecord = $oReader->city( $sIpAddress );
		
		return !empty($oRecord->mostSpecificSubdivision->name) ? array(
			'subdivision' => $oRecord->country->name,
			'mostSpecificSubdivision' => $oRecord->mostSpecificSubdivision->name,
			'cityName' => $oRecord->city->name,
			'postalCode' => $oRecord->postal->code,
			'isoCode' => $oRecord->country->isoCode  
		) : array();
	}
	
}