<?php

/*
 * Interface for all data access abstraction layer
 */
interface ifDb {

   public function affectedEntryCount();
   public function entryCount();
   public function escapeStr( $sStr );
   public function lastId();
   public function query( $sQuery );
   public function write( $sQuery, $aParams = array() );

}

/*
 * This class contains a factory method to return a abstraction layer for data access, which can use RDBMS, XML database, simple flat file or any other type of data storage.
 */
class clDb {

   public static function create( $aParams = array() ) {
	  $aParams += array(
		 'name' => 'clDb' . DB_DEFAULT_ENGINE,
		 'engine' => DB_DEFAULT_ENGINE,
		 'engineParams' => array()
	  );
	  
	  $sClassName = 'clDb' . $aParams['engine'];
	  clFactory::loadClassFile( $sClassName );
	  $oEngine = new $sClassName( $aParams['engineParams'] );
	  clRegistry::set( $aParams['name'], $oEngine );
	  return $oEngine;
   }

}
