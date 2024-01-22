<?php

function deleteDir( $sDir ) {
	$sCurrentDir = opendir( $sDir );
	while( $sName = readdir($sCurrentDir) ) {
		if( is_dir($sDir . '/' . $sName) && ($sName != '.' && $sName != '..') ) {
        	if( !deleteDir($sDir . '/' . $sName) ) return false;
		} elseif( $sName != '.' && $sName != '..' ){
			if( !unlink($sDir . '/' . $sName) ) return false;
		}
	}
	closedir( $sCurrentDir );
	return rmdir( $sDir );
}

/**
 * Copy a file, or recursively copy a folder and its contents
 * Minds symlinks, but fails on Windows LNK-files
 * 
 * @param       string   $sSource    Source path/file
 * @param       string   $sDest      Destination path/file
 * @return      bool     Returns true on success, false on failure
 */
function copyRecursive( $sSource, $sDest ) {
	// Check if source exists
	if( !file_exists($sSource) ) return false;
	
    // Check for symlinks
    if( is_link($sSource) ) {
        return symlink( readlink($sSource), $sDest );
    }
 
    // Simple copy for a file
    if( is_file($sSource) ) {
        return copy( $sSource, $sDest );
    }
 
    // Make destination directory
    if( !is_dir($sDest) ) {
        mkdir($sDest);
    }
 
    // Loop through the folder
    $dir = dir($sSource);
	if( $dir === false ) die("random?");
    while( false !== $entry = $dir->read() ) {
        // Skip pointers
        if( $entry == '.' || $entry == '..' ) {
            continue;
        }
 
        // Deep copy directories
        copyRecursive( $sSource . '/' . $entry, $sDest . '/' . $entry );
    }
 
    // Clean up
    $dir->close();
    return true;
}

function readMimeType( $sFile ) {
	if( function_exists('finfo_file') ) {
		$rFInfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
    	$sMimeType = finfo_file($rFInfo, $sFile) . "\n";
		finfo_close($rFInfo);
		return $sMimeType;
	} elseif( function_exists('mime_content_type') ) {
		return mime_content_type( $sFile );
	}
	return false;
	#return trim( exec('file -bi ' . escapeshellarg( $sFile )) ) ;
}

function cleanFilename( $sFilename ) {
	$aReplaces = array(
		'#' => '-',
		' ' => '-',
		"'" => '',
		'"' => '',
		'__' => '-',
		'&' => '-',
		'/' => '',
		'?' => ''
	);
	return strtolower( stripslashes(str_replace(array_keys($aReplaces), $aReplaces, $sFilename)) );
}

// Returns string file extension, without a dot
function getFileExtension( $sFileName ) {
	if( function_exists('pathinfo') ) {
		return pathinfo( $sFileName, PATHINFO_EXTENSION );
	} else {
		return mb_substr( strrchr( $sFileName,'.' ), 1 );
	}
}

// Returns string without file extension 
function getFileName( $sFileName ) {
	// Do not use pathinfo() cause PATHINFO_FILENAME was added PHP 5.2.0
	//return pathinfo( $sFileName, PATHINFO_FILENAME);
	
	if( function_exists('pathinfo') ) {
		$sFileExt = pathinfo( $sFileName, PATHINFO_EXTENSION );
	} else {
		$sFileExt = substr( strrchr( $sFileName,'.' ), 1 );
	}
		
	return mb_substr( $sFileName, 0, -strlen($sFileExt) - 1 );
}

// Convert bytes to human readable filesize
function bytesToStr( $iBytes ) {
    $aTypes = array( 'B', 'KB', 'MB', 'GB', 'TB' );
    for( $i = 0; $iBytes >= 1024 && $i < ( count( $aTypes ) -1 ); $iBytes /= 1024, $i++ );
    return( round( $iBytes, 2 ) . " " . $aTypes[$i] );
}

/**
 * Scan directory by given regex
 */
function regexScanDir( $sRegex, $sDirectory ) {
	$aReturn = array();
	
	$oDirectory = new RecursiveDirectoryIterator( $sDirectory );
	$oIterator = new RecursiveIteratorIterator( $oDirectory );
	$aHits = new RegexIterator( $oIterator, $sRegex, RecursiveRegexIterator::GET_MATCH );
	
	foreach( $aHits as $aHit ) {
		$aReturn[] = current( $aHit );
	}
	
	return $aReturn;
}