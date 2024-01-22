<?php

function createAttributes( $aAttributes = array() ) {
	$sOutput = '';
	foreach( $aAttributes as $key => $value ) {
		if( $value === null || is_array($value) ) continue;
		$sOutput .= ' ' . $key . '="' . $value . '"';
	}
	return $sOutput;
}

function strToUrl( $sStr ) {
	$sStr = str_replace( array(' ', '\\', '_', '–', '—', '‒'), '-', $sStr );
	$sStr = str_replace( array('&', '?', '#', '%', '+', '$', ',', ':', ';', '=', '@', '&amp;', '<', '>', '{', '}', '|', '^', '~', '[', ']', '`', "'", '"', '!', '¨', '¤'), '', $sStr );

	return mb_strtolower( $sStr );
}

function stripGetStr( $aToStrip = array(), $bUrlEncode = true ) {
	$aToStrip[] = 'ajax';
	$aToStrip[] = 'section';
	$aToStrip[] = 'view';
	$aToStrip[] = 'layout';
	$aQuery = array_diff_key( $_GET, array_flip($aToStrip) );
	$aNewQuery = array();
	foreach( $aQuery as $key => $value ) {
		if( is_array($value) ) {
			foreach( $value as $entry ) {
				$aNewQuery[] = $key . '[]=' . $entry;
			}
		} else {
			$aNewQuery[] = $key . '=' . $value;
		}
	}
	return implode( ($bUrlEncode ? '&amp;' : '&'), $aNewQuery );
}

function multiArrayAsList( $aArray ) {
	$sContent .= '
		<ul>';
	
	foreach( $aArray as $key => $value ) {
		if( is_array($value) ) {
			$sContent .= '
				<li>' . $key . '
					'. multiArrayAsList( $value ) . '
				</li>';
		} else {
			$sContent .= '
				<li>' . $value . '</li>';
		}
	}
	
	$sContent .= '
		</ul>';
	
	return $sContent;
}

/***
 *  Compares two urls
 *  @param string $sSubject The url to check for a match
 *  @param string $sToMatch What to match with
 *  @return bool Returns true if urls match, otherwise returns false. Also returns
 *  false if failed to parse subject url
 */
function isSameDomain( $sSubject, $sToMatch ) {
    $sUrlSubject = parse_url($sSubject, PHP_URL_HOST);
    $sUrlToMatch = parse_url($sToMatch, PHP_URL_HOST);
	
    if($sUrlSubject == $sUrlToMatch || empty($sUrlSubject)) {
		// internal link
		return true;
    } else {
		// external link
		return false;
    }
}

function shortenString( $sString, $iLength ) {
	return preg_replace('/\s+?(\S+)?$/', '', substr($sString, 0, $iLength));
}