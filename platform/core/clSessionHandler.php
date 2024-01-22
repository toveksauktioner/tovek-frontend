<?php

/**
 * $Id: clSessionHandler.php 1000 2018-04-10 08:50:00Z mikael $
 *
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author: mikael $
 * @version		Subversion: $Revision: 1000 $, $Date: 2018-04-10 08:50:00 +0200 (ti, 10 apr 2018) $
 */

/**
 * SessionUpdateTimestampHandlerInterface
 * As of PHP 7.0, you can implement SessionUpdateTimestampHandlerInterface to define your own session
 * id validating method like validate_sid and the timestamp updating method like update_timestamp in
 * the non-OOP prototype of session_set_save_handler().
 */
 
class clSessionHandler implements SessionHandlerInterface { 

	public $aParams;
	
	private $oDb;
	private $sSavePath;
	private $oMemcached;
	
	private $sStorageType;
	private $sSessionName;
	
	public function __construct( $aParams = array() ) {
		/**
		 * Params for this session handler.
		 * Note that 'pdo' as storage selection includes
		 * additional futures such as IP logging.
		 */
		$aParams += array(
			'storage' => 'pdo', # pdo, file, memcached
			'savePath' => PATH_PLATFORM . '/sessions', // default: "/var/lib/php/sessions"
			'debug' => false # true, false
		);
		$this->setParams( $aParams );
		
		$this->init();
	}
	
	/**
	 * Set params
	 */
	private function setParams( $aParams ) {
		$this->sStorageType = $aParams['storage'];
		$this->sSavePath = $aParams['savePath'];		
		return ($this->aParams = $aParams);
	}	
	
	/**
	 * Init
	 */
	private function init() {
		switch( $this->sStorageType ) {
			case 'pdo':
				require_once PATH_CORE . '/clDbPdo.php';
				$this->oDb = new clDbPdo();
				break;
			
			case 'memcached':
				$this->oMemcached = new Memcached();
				$this->oMemcached->addServer( 'localhost', 11211 );				
				break;
			
			case 'file':
			default:
				if( !is_dir($this->sSavePath) ) {
					mkdir( $this->sSavePath, 0777 );
				}
				break;
		}
	}
	
	/**
	 * The open callback works like a constructor in classes and is executed when the session is being opened.
	 * It is the first callback function executed when the session is started automatically or manually with session_start().
	 *
	 * @return boolen true for success, false for failure. 
	 */
	public function open( $sSavePath, $sSessionName ) {
		$this->sSessionName = $sSessionName;
		return true;
    }
	
	/**
	 * The close callback works like a destructor in classes and is executed after the session write callback has been called.
	 * It is also invoked when session_write_close() is called.
	 *
	 * @return boolen true for success, false for failure. 
	 */
	public function close() {
		$sSessionId = session_id();
		// Do we want to do anything here at this point?
        return true;
    }

	/**
	 * The read callback must always return a session encoded (serialized) string, or an empty string if there is no data to read.
	 *
	 * @return string session encoded (serialized).
	 */
    public function read( $sSessionId ) {
		switch( $this->sStorageType ) {
			case 'pdo':
				$sQuery = sprintf( "SELECT sessionData FROM entSessionStorage WHERE sessionId = %s", $this->oDb->escapeStr($sSessionId) );
				$aData = $this->oDb->query( $sQuery );
				return !empty($aData) ? current(current( $this->oDb->query( $sQuery ) )) : '';
				break;
			
			case 'memcached':				
				return $this->oMemcached->get( $sSessionId );
				break;
			
			case 'file':
			default:
				return (string) @file_get_contents( $this->sSavePath . '/sess_' . $sSessionId );
				break;
		}
		
		return false;
    }

	/**
	 * The write callback is called when the session needs to be saved and closed. This callback receives the current session ID a
	 * serialized version the $_SESSION superglobal. The serialization method used internally by PHP is specified in the session.serialize_handler ini setting.
	 * PHP7 will trigger error upon this function returning false.
	 *
	 * @return mixed returns the number of bytes that were written to the file, or false on failure. 
	 */
    public function write( $sSessionId, $mData ) {
		// Remote IP Address
		$sIp = ( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') );		
		$iIpLong = 0;
		if( !empty($sIp) ) $iIpLong = (int) sprintf('%u', ip2long($sIp) );
		
		switch( $this->sStorageType ) {
			case 'pdo':
				if( !empty($_SESSION['userId']) ) {
					$sQuery = sprintf( "INSERT INTO entSessionStorage (sessionId, sessionLastIp, sessionUserAgent, sessionData, sessionUserId, sessionTimestamp) VALUES (%s, %s, %s, %s, %s, %s)", $this->oDb->escapeStr($sSessionId), $this->oDb->escapeStr($iIpLong), $this->oDb->escapeStr($_SERVER['HTTP_USER_AGENT']), $this->oDb->escapeStr($mData), $_SESSION['userId'], time() );
					$sQuery .= sprintf( " ON DUPLICATE KEY UPDATE sessionLastIp = %s, sessionUserAgent = %s, sessionData = %s, sessionUserId = %s, sessionTimestamp = %s", $this->oDb->escapeStr($iIpLong), $this->oDb->escapeStr($_SERVER['HTTP_USER_AGENT']), $this->oDb->escapeStr($mData), $_SESSION['userId'], time() );
				} else {
					$sQuery = sprintf( "INSERT INTO entSessionStorage (sessionId, sessionData, sessionTimestamp) VALUES (%s, %s, %s)", $this->oDb->escapeStr($sSessionId), $this->oDb->escapeStr($mData), time() );
					$sQuery .= sprintf( " ON DUPLICATE KEY UPDATE sessionData = %s", $this->oDb->escapeStr($mData) );
				}
				return $this->oDb->write( $sQuery ) !== false ? true : false;
				break;
			
			case 'memcached':
				$mResult = $this->oMemcached->get( $sSessionId );
				if( !$mResult ) return $this->oMemcached->set( $sSessionId, $mData ); // expiration: ", time() + 300"
				else return $this->oMemcached->replace( $sSessionId, $mData );
				break;
			
			case 'file':
			default:
				return file_put_contents( $this->sSavePath . '/sess_' . $sSessionId, $mData ) === false ? false : true;
				break;
		}
		
		return false;
    }

	/**
	 * This callback is executed when a session is destroyed with session_destroy() or with session_regenerate_id() with the destroy
	 * parameter set to true.
	 *
	 * @return boolen true for success, false for failure. 
	 */
    public function destroy( $sSessionId ) {
		switch( $this->sStorageType ) {
			case 'pdo':
				$sQuery = sprintf( "DELETE FROM entSessionStorage WHERE sessionId = %s", $this->oDb->escapeStr($sSessionId) );
				$this->oDb->query( $sQuery );				
				break;
			
			case 'memcached':
				$this->oMemcached->delete( $sSessionId );
				break;
			
			case 'file':
			default:
				if( file_exists($sFile) ) {
					unlink( $this->sSavePath . '/sess_' . $sSessionId );
				}
				break;
		}
		
        return true;
    }

	/**
	 * Garbage collection
	 * The garbage collector callback is invoked internally by PHP periodically in order to purge old session data. The frequency is
	 * controlled by session.gc_probability and session.gc_divisor. The value of lifetime which is passed to this callback can be set
	 * in session.gc_maxlifetime.
	 *
	 * @return boolen true for success, false for failure. 
	 */
    public function gc( $iMaxLifeTime = null ) {
		$iMaxLifeTime = $iMaxLifeTime != null ? $iMaxLifeTime : USER_TIMEOUT;
		
		switch( $this->sStorageType ) {
			case 'pdo':
				if( $this->aParams['debug'] === true ) {
					// Log this action
					$sLogMsg = sprintf( 'Sessions gc with maxLifeTime: %s (%s)', $iMaxLifeTime, date('Y-m-d H:i:s', $iMaxLifeTime) );
					file_put_contents( dirname(__file__) . '/../logs/sessionHandling.log', date('Y-m-d H:i:s') . "\t" . $sLogMsg . "\r\n", FILE_APPEND );
				}
				
				// Globally clean
				$sQuery = sprintf( "DELETE FROM entSessionStorage WHERE (sessionTimestamp < UNIX_TIMESTAMP() - %s)", $iMaxLifeTime );
				$this->oDb->write( $sQuery );
				
				// Old
				//foreach( $this->oDb->query( "SELECT * FROM entSessionStorage" ) as $aEntry ) {				
				//	if( $aEntry['sessionTimestamp'] + $iMaxLifeTime < time() ) {
				//		$sQuery = sprintf( "DELETE FROM entSessionStorage WHERE sessionId = %s", $this->oDb->escapeStr($aEntry['sessionId']) );
				//		$this->oDb->write( $sQuery );
				//	}
				//}
				
				break;
			
			case 'memcached':
				// Globally clean memcache
				$this->oMemcached->deleteMulti( $this->oMemcached->getAllKeys() );
				// todo: time?
				// $this->oMemcached->getOption(Memcached::OPT_POLL_TIMEOUT)
				break;
			
			case 'file':
			default:
				// Globally clean
				foreach( glob($this->sSavePath . '/sess_*') as $sFile ) {
					if( filemtime($sFile) + $iMaxLifeTime < time() && file_exists($sFile) ) {
						unlink( $sFile );
					}
				}
				break;
		}
		
        return true;
    }
	
	/**
	 * This callback is executed when a new session ID is required. No parameters are provided, and the return value should be a string
	 * that is a valid session ID for your handler. Available since PHP 5.5.1.
	 *
	 * @return string session ID for handler
	 */
	//public function create_sid() {}
	
	/**
	 * implements SessionUpdateTimestampHandlerInterface::validateId() available since PHP 7.0
	 *
	 * @return boolen true for valid, false for not valid.
	 * (if false is returned a new session id will be generated by php internally)
	 */
	//public function validateId( $sSessionId ) {}
	
	/**
	 * implements SessionUpdateTimestampHandlerInterface::validateId() available since PHP 7.0
	 *
	 * @return boolen true for success, false for failure. 
	 */
	//public function updateTimestamp( $sSessionId, $mData ) {}
	
	/**
	 *
	 */
	//public function resetSessionId() {
	//	$old = session_id();
	//	session_regenerate_id();
	//	$new = session_id();
	//	SessionHandler::regenerate_id($old,$new);
	//}

	/**
	 *
	 */
	//public function regenerate_id( $old,$new ) {
	//	$db = mysqli->connect(...);	
	//	$db->query('UPDATE sessions SET session_id = \''.$db->escape_string($new).'\'
	//	WHERE session_id = \''.$db->escape_string($old).'\'');
	//}
	
}