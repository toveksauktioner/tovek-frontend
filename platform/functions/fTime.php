<?php

/**
 * Finds difference in days between dates
 * @param int $start
 * @param int $finish
 * @return int
 */
function daysBetween($start, $finish) {
	if (!is_int($start))	$start = strtotime($start);
	if (!is_int($finish))	$finish = strtotime($finish);

	$diff = $finish - $start;
	$days = $diff / (24 * 3600);

	return round($days);
}

/**
 * Gets the age of an individual
 * @param int $timeStamp
 * @return int
 */
function getAge($timeStamp) {
	$seconds = time() - strtotime($timeStamp);
	$year = 60 * 60 * 24 * 365;

	return floor($seconds / $year);
}

/**
 * Gets a unix timestamp in certain timezones
 * @param int $timeStamp
 * @param int $timeZone
 * @return int
 */
function timeInZone($timeStamp, $timeZone = -8) {
	return strtotime(gmdate('Y-m-d h:i:sa', $timeStamp + (3600 * $timeZone)));
}

/**
 * Checks to see if a timestamp was within a timeframe
 * @param int $check
 * @param int $start
 * @param int $finish
 * @return boolean
 */
function withinTimeframe($check, $start, $finish = '') {
	if (!is_int($check))	$check = strtotime($check);
	if (!is_int($start))	$start = strtotime($start);

	if (empty($finish)) {
		$finish = time();
	} else {
		if (!is_int($finish)) $finish = strtotime($finish);
	}

	if ($check >= $start && $check <= $finish) {
		return true;
	}

	return false;
}

/**
 * Breaksdown a timestamp into an array of days, months, etc since the current time
 * @param int $timeStamp
 * @return array
 */
function timeBreakdown($timeStamp) {
	if (!is_int($timeStamp)) $timeStamp = strtotime($timeStamp);
	$currentTime = time();

	$periods = array(
		'years'         => 31556926,
		'months'        => 2629743,
		'weeks'         => 604800,
		'days'          => 86400,
		'hours'         => 3600,
		'minutes'       => 60,
		'seconds'       => 1
	);

	$durations = array(
		'years'         => 0,
		'months'        => 0,
		'weeks'         => 0,
		'days'          => 0,
		'hours'         => 0,
		'minutes'       => 0,
		'seconds'       => 0
	);

	if ($timeStamp) {
		$seconds = $currentTime - $timeStamp;

		if ($seconds <= 0){
			return $durations;
		}

		foreach ($periods as $period => $seconds_in_period) {
			if ($seconds >= $seconds_in_period) {
				$durations[$period] = floor($seconds / $seconds_in_period);
				$seconds -= $durations[$period] * $seconds_in_period;
			}
		}
	}

	return $durations;
}

function timeToString( $timeStamp ) {
	$aTime = array();
	$aTimeData = timeBreakdown( $timeStamp );

	if( $aTimeData['years'] != 0 ) {
		$aTime[] = $aTimeData['years'] . ' ' . ( $aTimeData['years'] == 1 ? _('year') : _('years') );
	}

	if( $aTimeData['months'] != 0 ) {
		$aTime[] = $aTimeData['months'] . ' ' . ( $aTimeData['months'] == 1 ? _('month') : _('months') );
	}

	if( $aTimeData['days'] != 0 ) {
		$aTime[] = $aTimeData['days'] . ' ' . ( $aTimeData['days'] == 1 ? _('day') : _('days') );
	}

	if( $aTimeData['years'] == 0 && $aTimeData['months'] == 0 && $aTimeData['days'] == 0) {
		$aTime = array();

		if($aTimeData['hours'] != 0) $aTime[] = $aTimeData['hours'] . ' ' . ( $aTimeData['hours'] == 1 ? _('hour') : _('hours') );
		if($aTimeData['minutes'] != 0) $aTime[] = $aTimeData['minutes'] . ' ' . ( $aTimeData['minutes'] == 1 ? _('minute') : _('minutes') );
		if($aTimeData['seconds'] != 0) $aTime[] = $aTimeData['seconds'] . ' ' . ( $aTimeData['seconds'] == 1 ? _('seconds') : _('seconds') );
	}

	return implode(', ', $aTime) . ' ' . _('ago');
}

function datetimeToUtc( $sDateTime ) {
	if( empty($sDateTime) ) return gmmktime();

	$aDateInfo = getdate( strtotime($sDateTime) );

	return gmmktime(
		$aDateInfo['hours'],
		$aDateInfo['minutes'],
		$aDateInfo['seconds'],
		$aDateInfo['mon'],
		$aDateInfo['mday'],
		$aDateInfo['year'],
		+1
	);
}

function firstDateInWeekOfMonth( $iMonth, $sYear = null ) {
	if( $sYear === null ) $sYear = date('Y');

	if( $iMonth <= 9 ) {
		$sMonth = '0' . (string) $iMonth;
	} else {
		$sMonth = (string) $iMonth;
	}

	$sDate = $sYear . '-' . $sMonth . '-01';
	$iDayInWeek = date( "w", strtotime($sDate));
	// Fix for sunday
	if( $iDayInWeek == 0 ) $iDayInWeek = 7;

	if( $iDayInWeek > 1 ) {
		$iDays = $iDayInWeek - 1;
		return date( 'Y-m-d', strtotime(date('Y-m-d', strtotime($sDate)) . ' -' . $iDays . ' day') );
	} else {
		return $sDate;
	}
}

function lastDateInWeekOfMonth( $iMonth, $sYear = null ) {
	if( $sYear === null ) $sYear = date('Y');

	if( $iMonth <= 9 ){
		$sMonth = '0' . (string) $iMonth;
	} else {
		$sMonth = (string) $iMonth;
	}

	$sDate = $sYear . '-' . $sMonth . '-' . date( 'd', strtotime(date('Y-m-d', strtotime(lastDateInMonth( $iMonth )))));
	$iDayInWeek = date( "w", strtotime($sDate));
	// Fix for sunday
	if( $iDayInWeek == 0 ) $iDayInWeek = 7;

	if( $iDayInWeek != 0 && $iDayInWeek < 7 ) {
		$iDays = 7 - $iDayInWeek;
		return date( 'Y-m-d', strtotime(date('Y-m-d', strtotime($sDate)) . ' +' . $iDays . ' day') );
	} else {
		return $sDate;
	}
}

function lastDateInMonth( $iMonth, $sYear = null ) {
	if( $sYear === null ) $sYear = date('Y');
	return date( 'Y-m-t', strtotime($sYear . '-' . $iMonth . '-01') );
}

/**
 * Convert to timestamp from w3c standard format.
 * ex. 'Thu, 10 Apr 2014 08:03:35 GMT'
 */
function rssTimeToTimestamp( $sRssTime, $sTimezone = 'GMT' ) {
	$sDay = substr($sRssTime, 5, 2);
	$sMonth = date( 'm', strtotime(substr($sRssTime, 8, 3) . '1 2011') );
	$sYear = substr($sRssTime, 12, 4);
	$sHour = substr($sRssTime, 17, 2);
	$sMin = substr($sRssTime, 20, 2);
	$sSecond = substr($sRssTime, 23, 2);
	$sTimezone = substr($sRssTime, 26);

	$iTimestamp = mktime( $sHour, $sMin, $sSecond, $sMonth, $sDay, $sYear );

	$sCurrentTimezone = date_default_timezone_get();
	date_default_timezone_set( $sTimezone );

	if( is_numeric( $sTimezone ) ) {
		$sHoursMod = $iMinsMod = 0;
		$sModifier = substr($sTimezone, 0, 1);
		$sHoursMod = (int) substr($sTimezone, 1, 2);
		$iMinsMod = (int) substr($sTimezone, 3, 2);
		$sHourLabel = $sHoursMod > 1 ? 'hours' : 'hour';
		$sStrToTimeArgument = $sModifier . $sHoursMod . ' ' . $sHourLabel;

		if( $iMinsMod ) {
			$sMinsLabel = $iMinsMod > 1 ? 'minutes' : 'minute';
			$sStrToTimeArgument .= ' '.$iMinsMod . ' ' . $sMinsLabel;
		}

		$iTimestamp = strtotime( $sStrToTimeArgument, $iTimestamp );
	}

	// Reset to default timezone
	date_default_timezone_set( $sCurrentTimezone );

	return $iTimestamp;
}


function formatIntlDate( $sFormat, $iTime = null ) {
	// Pattern format: https://unicode-org.github.io/icu/userguide/format_parse/datetime/

	if( $iTime === null ) $iTime = time();

	$oFormatter = new IntlDateFormatter(
    'sv_SE',
    IntlDateFormatter::NONE,
    IntlDateFormatter::NONE,
    null,
    null,
    $sFormat
  );

	return $oFormatter->format( $iTime );
}
