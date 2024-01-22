<?php

require_once PATH_CORE . '/clDb.php';

class clDbPdo implements ifDb {

	private $aParams;
	private $iEntryCount;
	private $oConn;
	private $oStatement;

	/**
	 *
	 */
	function __construct( $aParams = array() ) {
		$aParams += array(
			'database' => DB_DATABASE,
			'engine' => DB_ENGINE,
			'host' => DB_HOST,
			'pass' => DB_PASS,
			'port' => DB_PORT,
			'username' => DB_USERNAME
		);
		$this->aParams = $aParams;
		
		$dsn = $this->aParams['engine'] . ':dbname=' . $this->aParams['database'] . ';host=' . $this->aParams['host'] . ';port=' . $this->aParams['port'];
		try {
			$this->oConn = new PDO( $dsn, $this->aParams['username'], $this->aParams['pass'] );
		} catch( Exception $e ) {
			if( $GLOBALS['debug'] ) {
				echo $e->getMessage();
				
				//header( "HTTP/1.1 307 Temporary Redirect" );
				//header( "Location: http://front.tovek.se/maintenance.php" );
				//exit;
				
			} else {
				echo _( 'Fatal Error: A problem occured when connecting to the database' );
			}
			exit;
		}
		//$this->query( 'SET PROFILING = 1' ); // MySQL specific, enable profiling for this session
		$this->query( 'SET names utf8' );
	}

	/**
	 * TODO: Protect database information
	 * This feature was added in PHP 5.6.0. 
	 */
	//function __debugInfo() {
	//	
	//}
	
	/**
	 * Destructor for showing MySQL profiling
	 * Remember to enable SET PROFILING = 1 in constructor
	 * Note: Does not work with COMPRESS_OUTPUT_BUFFER / ob_gzhandler
	 */
	//function __destruct() {
	//	$aProfiles = $this->query( 'SHOW PROFILES' );
	//	if( !empty($aProfiles) ) {
	//		echo '<div id="mysql-profiles">';
	//		
	//		clFactory::loadClassFile( 'clOutputHtmlTable' );
	//		$oOutputHtmlTableProfiles = new clOutputHtmlTable( array(
	//			'Query_ID' => array(),
	//			'Duration' => array(),
	//			'Query' => array(),
	//		), array(
	//			'title' => 'SHOW PROFILES'
	//		) );
	//		$oOutputHtmlTableProfiles->setTableDataDict( array(
	//			'Query_ID' => array( 'title' => 'Query_ID' ),
	//			'Duration' => array( 'title' => 'Duration' ),
	//			'Query' => array( 'title' => 'Query' )
	//		) );
	//		
	//		// Create and sort an array with ascending duration 
	//		$aQueryIdToDuration = array();
	//		foreach( $aProfiles as $aProfile ) {
	//			$aQueryIdToDuration[$aProfile['Query_ID']] = $aProfile['Duration'];
	//		}
	//		arsort( $aQueryIdToDuration, SORT_NUMERIC ); // Slowest query first
	//		
	//		// Show all profiles
	//		$aRanges = array_combine( range(0, 5), range(5, 0) );
	//		foreach( $aProfiles as $aProfile ) {
	//			$iSlowPosition = array_search( $aProfile['Query_ID'], array_keys($aQueryIdToDuration) ); // Lowest value (0) is slowest
	//			if( $iSlowPosition < 5 ) {
	//				// Mark the 5 slowest queries
	//				$aAttributes = array( 'style' => 'background-color: rgba(' . ( $aRanges[$iSlowPosition] * (255 / 5) ) . ', 0, 0, 0.5);');
	//			} else {
	//				$aAttributes = array();
	//			}
	//			
	//			$oOutputHtmlTableProfiles->addBodyEntry( array(
	//				'Query_ID' => $aProfile['Query_ID'],
	//				'Duration' => $aProfile['Duration'],
	//				'Query' => $aProfile['Query'],
	//			), $aAttributes );
	//		}
	//		echo $oOutputHtmlTableProfiles->render();
	//		
	//		// Show some more profile data for the slowest queries
	//		foreach( array_slice($aQueryIdToDuration, 0, 5, true) as $iQueryId => $sDuration ) {
	//			$oOutputHtmlTableSlowestProfiles = new clOutputHtmlTable( array(
	//				'Status' => array(),
	//				'Duration' => array(),
	//				'CPU_user' => array(),
	//				'CPU_system' => array(),
	//				'Context_voluntary' => array(),
	//				'Context_involuntary' => array(),
	//				'Block_ops_in' => array(),
	//				'Block_ops_out' => array(),
	//				'Page_faults_major' => array(),
	//				'Page_faults_minor' => array(),
	//				'Swaps' => array()
	//			), array(
	//				'title' => 'SHOW PROFILE ALL FOR QUERY ' . $iQueryId
	//			) );
	//			$oOutputHtmlTableSlowestProfiles->setTableDataDict( array(
	//				'Status' => array( 'title' => 'Status' ),
	//				'Duration' => array( 'title' => 'Duration' ),
	//				'CPU_user' => array( 'title' => 'CPU_user' ),
	//				'CPU_system' => array( 'title' => 'CPU_system' ),
	//				'Context_voluntary' => array( 'title' => 'Context_voluntary' ),
	//				'Context_involuntary' => array( 'title' => 'Context_involuntary' ),
	//				'Block_ops_in' => array( 'title' => 'Block_ops_in' ),
	//				'Block_ops_out' => array( 'title' => 'Block_ops_out' ),
	//				'Page_faults_major' => array( 'title' => 'Page_faults_major' ),
	//				'Page_faults_minor' => array( 'title' => 'Page_faults_minor' ),
	//				'Swaps' => array( 'title' => 'Swaps' )
	//			) );
	//			$aProfileData = $this->query( 'SHOW PROFILE BLOCK IO, CONTEXT SWITCHES, CPU, MEMORY, PAGE FAULTS, SWAPS FOR QUERY ' . $iQueryId );
	//			if( !empty($aProfileData) ) {
	//				foreach( $aProfileData as $aProfileRow ) {
	//					$oOutputHtmlTableSlowestProfiles->addBodyEntry( $aProfileRow );
	//				}
	//			}
	//			echo $oOutputHtmlTableSlowestProfiles->render();
	//		}
	//		
	//		echo '</div>';
	//	}
	//}
	
	/**
	 * TODO: Protect database information
	 * This feature was added in PHP 5.1.0.
	 */
	//function __set_state() {
	//	
	//}
	
	/**
	 * @see ifDb::affectedEntryCount()
	 */
	public function affectedEntryCount() {
		return $this->oStatement->rowCount();
	}
	
	/**
	 * For PDO transactions
	 */
	public function beginTransaction() {
		return $this->oConn->beginTransaction();
	}

	/**
	 * For PDO transactions
	 */
	public function commit() {
		return $this->oConn->commit();
	}
	
	/**
	 * @see ifDb::entryCount()
	 */
	public function entryCount() {
		return $this->iEntryCount;
	}

	/**
	 * @see ifDb::escapeStr()
	 */
	public function escapeStr( $sStr ) {
		return $this->oConn->quote( $sStr );
	}
	
	/**
	 * For PDO transactions
	 */
	public function execute( $aData ) {
		return $this->oStatement->execute( $aData );
	}
	
	/**
	 * @see ifDb::lastId()
	 */
	public function lastId() {
		return $this->oConn->lastInsertId();
	}

	/**
	 * For PDO transactions
	 */
	public function prepare( $sQueryStatment ) {
		unset($this->oStatement);
		$this->oStatement = $this->oConn->prepare( $sQueryStatment );
		return true;
	}

	/**
	 * @see ifDb::query()
	 */
	public function query( $sQuery ) {
		//if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == "213.88.134.199" ) echo $sQuery . '<br /><br />';
		//clFactory::loadClassFile( 'clLogger' );
		//clLogger::log( $sQuery, 'clDbPdo-query.log' );
		
		unset($this->oStatement);
		$this->oStatement = $this->oConn->query( $sQuery );
		if( $this->oStatement === false ) {
			$aErr = $this->oConn->errorInfo();
			
			if( $GLOBALS['logExceptions'] ) {
				clLogger::logWithTruncation( 'SqlError: ' . $aErr[1] . ' - ' . $aErr[2] . ' Query: ' . $sQuery, 'exceptions.log' );
			}
			if( $GLOBALS['debug'] ) {
				throw new Exception( 'SqlError: ' . $aErr[1] . ' - ' . $aErr[2] . ' Query: ' . $sQuery );
			} else {
				throw new Exception( 'SqlError: ' . $aErr[0] );
			}
			
		}
		
		return $this->oStatement->fetchAll( PDO::FETCH_ASSOC );
	}

	/**
	 * @see ifDb::lastId()
	 */
	public function rollBack() {
		return $this->oConn->rollBack();
	}

	/**
	 * Set attribute
	 */
	public function setAttribute( $sAttribute, $sValue ) {
		return $this->oConn->setAttribute( $sAttribute, $sValue );
	}
	
	/**
	 * @see ifDb::write()
	 */
	public function write( $sQuery, $aParams = array() ) {
		#echo $sQuery . '<br /><br />';
		//clFactory::loadClassFile( 'clLogger' );
		//clLogger::log( $sQuery, 'clDbPdo-write.log' );
		
		$iAffectedRows = $this->oConn->exec( $sQuery );
		if( $iAffectedRows === false ) {
			$aErr = $this->oConn->errorInfo();
			
			if( $GLOBALS['logExceptions'] ) {
				clLogger::logWithTruncation( 'SqlError: ' . $aErr[1] . ' - ' . $aErr[2] . ' Query: ' . $sQuery, 'exceptions.log' );
			}
			if( $GLOBALS['debug'] ) {
				throw new Exception( 'SqlError: ' . $aErr[1] . ' - ' . $aErr[2] . ' Query: ' . $sQuery );
			} else {
				throw new Exception( 'SqlError: ' . $aErr[0] );
			}
			
		}
		return $iAffectedRows;
	}
}
