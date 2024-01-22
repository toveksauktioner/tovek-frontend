<?php

// Functions useful when developing applications. E.g. pretty var_dump()'s or unit tests, whatever you like.

/***
 *  Formats a argoPlatform dataDict into a array or string with SQL create table commands
 *  @param array $aDataDict The dataDict to use
 *  @param bool $bReturnString If true returns a string, if false return an array with commands
 *  @return mixed Returns array or string. String is default.
 */
function datadict2sql( $aDataDict = array(), $bReturnString = true ) {
	$aSqls = array();
	$iCount = 0;
	foreach( $aDataDict as $sTable => $aValues ) {
		$aSqls[ $iCount ] = 'CREATE TABLE IF NOT EXISTS `' . $sTable . '` ( ' . "\n";

		$aIndexes = array();
		$sPrimary = '';

		foreach( $aValues as $sFieldName => $aFieldData ) {
			if( isset($aFieldData['index']) && $aFieldData['index'] == true ) $aIndexes[] = $sFieldName;

			// Primary
			if( isset($aFieldData['primary']) && $aFieldData['primary'] == true ) $sPrimary = $sFieldName;

			// Field name
			$sFieldData = "\t" . '`' . $sFieldName . '` ';

			// Field data type
			switch( $aFieldData['type'] ) {
				case 'array':
				case 'arraySet':
					$sFieldData .= 'ENUM("' . implode( '", "', array_keys($aFieldData['values'] ) ) . '") ';
					break;

				case 'date':
					$sFieldData .= 'date ';
					break;

				case 'datetime':
					$sFieldData .= 'datetime ';
					break;

				case 'float':
					$sFieldData .= 'float ';
					if( isset($aFieldData['min']) && $aFieldData['min'] >= 0 ) $aSqls[ $iCount ] .= 'unsigned ';
					break;

				case 'integer':
					$sFieldData .= 'int(10) ';
					if( isset($aFieldData['min']) && $aFieldData['min'] >= 0 ) $aSqls[ $iCount ] .= 'unsigned ';
					break;

				case 'string':
				default:
					$sFieldData .= 'varchar(' . ( !empty($aFieldData['max']) ? $aFieldData['max'] : 255 ) . ') ';
					break;
			}

			// NOT NULL?
			$sFieldData .= 'NOT NULL ';

			// Auto increment
			if( !empty($aFieldData['autoincrement']) ) $sFieldData .= 'auto_increment ';

			$sFieldData = "\t" .  trim($sFieldData); // Remove trailing space

			$aSqls[ $iCount ] .= $sFieldData . ",\n";
		}

		// Primary key
		if( !empty($sPrimary) ) {
			$aSqls[ $iCount ] .= "\t" . 'PRIMARY KEY (`' . $sPrimary . '`)';
		} else {
			$aSqls[ $iCount ] .= "\t" . 'PRIMARY KEY (`' . key($aValues) . '`)';
		}

		if( !empty($aIndexes) ) {
			// Add a comma if needed
			$aSqls[ $iCount ] .= ",\n";
		} else {
			$aSqls[ $iCount ] .= "\n";
		}

		// Keys/indexes
		if( !empty($aIndexes) ) {
			$iIndexes = count( $aIndexes );
			$iIndexCount = 1;
			foreach( $aIndexes as $sIndex) {
				$aSqls[ $iCount ] .= "\t" . 'KEY `' . $sIndex . '` (`' . $sIndex . '`)' . ( $iIndexCount == $iIndexes ? '' : ',' ) . '' . "\n";
				++$iIndexCount;
			}
		}

		$aSqls[ $iCount ] .= ') ENGINE=MyISAM DEFAULT CHARSET=utf8;';
		++$iCount;
	}

	return ( $bReturnString === true ? implode("\n\n", $aSqls ) : $aSqls);
}

/***
 *  Fetches MySQL information_schema.columns data and parses into a argoPlatform dataDict. Uses echo() for output.
 *  @param string $sTableName Table name
 *  @param clDbPdo $oDb A argoPlatform clDbPdo object
 *  @return boolean Returns TRUE on success or FALSE on failure.
 */
function mysqlTableToDataDict( $sTableName, clDbPdo $oDb ) {
	if( empty($sTableName) ) return false;
	$aColumnsData = $oDb->query( 'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_KEY, EXTRA FROM information_schema.columns WHERE TABLE_NAME = ' . $oDb->escapeStr($sTableName) . ' ORDER BY ORDINAL_POSITION ASC' );

	if( !empty($aColumnsData) ) {
		$aOutput = array();
		$iColumnCount = 0;
		foreach( $aColumnsData as $aColumnData) {
			$aOutput[ $iColumnCount ] = "\t'" . $aColumnData['COLUMN_NAME'] . "' => array(\n\t\t";
			$aFieldData = array();

			// Type
			switch( strtolower($aColumnData['DATA_TYPE']) ) {
				case 'int':
					$aFieldData[] = "'type' => 'integer'";
					break;
				case 'char':
				case 'varchar':
				case 'text':
					$aFieldData[] = "'type' => 'string'";
					break;
				case 'float':
					$aFieldData[] = "'type' => 'float'";
					break;
				case 'date':
					$aFieldData[] = "'type' => 'date'";
					break;
				case 'datetime':
					$aFieldData[] = "'type' => 'datetime'";
					break;
				case 'enum':
					$aFieldData[] = "'type' => 'array'";
					$aEnumValues = explode( ',', mb_substr( $aColumnData['COLUMN_TYPE'], 5, -1 ) );
					foreach( $aEnumValues as &$sEnum ) {
						$sEnum = str_replace( array( '\'', '"' ), '', $sEnum );

						$sEnum = "'" . $sEnum . "' => _( '" . ucfirst($sEnum) . "' )";

					}
					$aFieldData[] = "'values' => array(\n\t\t\t" . implode( ",\n\t\t\t", $aEnumValues ) . "\n\t\t)";

					break;


				default:
					die( 'Add <strong>' . $aColumnData['DATA_TYPE'] . '</strong> to function!' );
					break;
			}

			// Title
			$aFieldData[] = "'title' => _( '" . ucfirst($aColumnData['COLUMN_NAME']) . "' )";

			// Keys
			switch( strtolower($aColumnData['COLUMN_KEY']) ) {
				case 'pri':
					$aFieldData[] = "'primary' => true";
					break;

				case '':
					break;

				default:
					die("Unknown key <strong>" . $aColumnData['COLUMN_KEY'] . "</strong>, please add");
					break;
			}

			// Auto increment
			if( !empty($aColumnData['EXTRA']) && strpos($aColumnData['EXTRA'], 'auto_increment') !== false ) {
				$aFieldData[] = "'autoincrement' => true";
			}

			$aOutput[ $iColumnCount ] .= implode(",\n\t\t", $aFieldData) . "\n\t)";
			++$iColumnCount;
		}

		echo "<pre>
'" . $sTableName . "' => array(
" . implode( ",\n", $aOutput) . "
)
</pre>";

		return true;
	}
	return false;
}

function hex_dump($data, $newline="\n") {
	static $from = '';
	static $to = '';

	static $width = 16; # number of bytes per line

	static $pad = '.'; # padding for non-visible characters

	if ($from==='') {
		for ($i=0; $i<=0xFF; $i++) {
			$from .= chr($i);
			$to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
		}
	}

	$hex = str_split(bin2hex($data), $width*2);
	$chars = str_split(strtr($data, $from, $to), $width);

	$offset = 0;
	foreach ($hex as $i => $line) {
		echo sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']' . $newline;
		$offset += $width;
	}
}

/***
 *  Outputs the last part of file(s)
 *  @param string $sDirectoryOrFile Directory or file
 *  @param integer $iLines Output the last X lines. Defaults to 10 if omitted.
 *  @return mixed Returns false on failiure, else string with output
 */
function tail( $sDirectoryOrFile = null, $iLines = 10 ) {
	if( $sDirectoryOrFile === null || !is_readable($sDirectoryOrFile) ) {
		return false;
	}
	if( !is_int($iLines) || $iLines < 0 ) {
		$iLines = 10;
	}

	$sOutput = '';
	if( is_dir($sDirectoryOrFile) ) {
		if( mb_substr($sDirectoryOrFile, -1, 1) == '/') {
			// Just remove trailing slash
			$sDirectoryOrFile = mb_substr($sDirectoryOrFile, 0, mb_strlen($sDirectoryOrFile) - 1 );
		}

		$aFiles = scandir( $sDirectoryOrFile );
		foreach( $aFiles as $sFile ) {
			if( $sFile == '.' || $sFile == '..' || is_dir($sDirectoryOrFile . '/' . $sFile) ) {
				continue;
			}

			$sOutput .= '==&gt; ' . $sDirectoryOrFile . '/' . $sFile . ' &lt;==' . PHP_EOL;
			$aLastLines = array_slice( file($sDirectoryOrFile . '/' . $sFile), -$iLines );
			$sOutput .= implode( '', $aLastLines ) . PHP_EOL . PHP_EOL;

		}
	} elseif( is_file($sDirectoryOrFile) ) {
		$sOutput .= '==&gt; ' . $sDirectoryOrFile . ' &lt;==' . PHP_EOL;
		$aLastLines = array_slice( file($sDirectoryOrFile), -$iLines );
		$sOutput .= implode( '', $aLastLines ) . PHP_EOL . PHP_EOL;
	} else {
		// Symlink or unknown file type?
		return false;
	}

	return '<pre>' . $sOutput . '</pre>';
}

/***
 *  Create a argoPlatform dataDict entity in default db engine
 *  @param array $aDataDict The dataDict to use
 *  @return boolean Returns true or false
 */
function datadictWriteSql( $aDataDict = array() ) {
	$aTableSql = datadict2sql( $aDataDict, false );

	// Database object
	$oDb = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );

	foreach( $aTableSql as $sSqlQuery ) {
		$mResult = $oDb->write( $sSqlQuery );
		if( $mResult === 0 ) continue;
		else return false;
	}

	return true;
}
