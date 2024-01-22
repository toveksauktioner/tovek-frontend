<?php
// TODO logging and correct exception handling
require_once PATH_CORE . '/clDb.php';


class clDbMysql implements ifDb {

	private $aParams;
	private $iEntryCount;
	private $rStatement;

	/**
	 *
	 */
	function __construct( $aParams = array() ) {
		$this->aParams = $aParams += array(
			'database' => DB_DATABASE,
			'engine' => DB_ENGINE,
			'host' => DB_HOST,
			'pass' => DB_PASS,
			'port' => DB_PORT,
			'username' => DB_USERNAME
		);
		
		$rLink = mysql_connect( $aParams['host'] . ':' . $aParams['port'], $aParams['username'], $aParams['pass'] );
		mysql_select_db( $aParams['database'], $rLink );
	}

	/**
	 * @see ifDb::affectedEntryCount()
	 */
	public function affectedEntryCount() {
		return mysql_affected_rows();
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
		return "'" . mysql_real_escape_string( $sStr ) . "'";
	}

	/**
	 * @see ifDb::lastId()
	 */
	public function lastId() {
		return mysql_insert_id();
	}

	/**
	 * @see ifDb::query()
	 * TODO logging and correct exception handling
	 */
	public function query( $sQuery ) {
		#echo $sQuery . '<br /><br />';
		unset($this->rStatement);
		$this->rStatement = mysql_query( $sQuery );
		if( $this->rStatement === false ) {
			die('clDbMysql query error: ' . mysql_error());
		}
		
		$aResult = array();
		while( $aRow = mysql_fetch_array($this->rStatement) ) {
			$aResult[] = $aRow;
		}
		
		return $aResult;
	}

	/**
	 * @see ifDb::write()
	 * TODO logging and correct exception handling
	 */
	public function write( $sQuery, $aParams = array() ) {
		#echo $sQuery . '<br /><br />';
		unset($this->rStatement);
		$this->rStatement = mysql_query( $sQuery );
		if( $this->rStatement === false ) {
			die('clDbMysql query error: ' . mysql_error());
		}
		
		return mysql_affected_rows();
	}
}
