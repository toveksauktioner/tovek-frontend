<?php

require_once PATH_FUNCTION . '/fData.php';
$oRouter = clRegistry::get( 'clRouter' );

if( empty($GLOBALS['viewParams']['navigation']['list.php']['groupKey']) ) {
	$GLOBALS['viewParams']['navigation']['list.php']['groupKey'] = 'guest';
}

$sGroupKey = $GLOBALS['viewParams']['navigation']['list.php']['groupKey'];

$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
$oNavigation->setGroupKey( $sGroupKey );

$aPath = arrayToSingle( $oNavigation->readWithParentsByUrl($oRouter->sPath), null, 'navigationId' );

$aTree = $oNavigation->read( array(
	'navigationId',
	'navigationTitle',
	'navigationUrl',
	'navigationOpenIn',
	'navigationImageSrc',
	'navigationLeft',
	'navigationRight'
) );

$sOutput = '';

if( !empty($aTree) ) {
	$iPreviousDepth = 0;

	// Find first and last keys
	foreach( $aTree as $key => $entry ) if( $entry['depth'] == 0 ) $iLastKey = $key;
	reset($aTree);
	$iFirstKey = key($aTree);

	$sOutput .= '
		<ul class="navMain ' . $sGroupKey . '"' . ( ($sGroupKey == 'user') ? ' id="userNavLoggedIn" style="display: none;"' : '' ) . '>';

	$aCount = array();

	foreach( $aTree as $key => $entry ) {
		if( empty($aCount[$entry['depth']]) ) $aCount[$entry['depth']] = 1;

		$aClass = array();
		if( $entry['depth'] > $iPreviousDepth ) {
			$aClass[] = 'subFirst';

			$sOutput .= '<ul>';
		} elseif(  $entry['depth'] < $iPreviousDepth  ) {
			$aCount[$iPreviousDepth] = 0;
			$sOutput .= str_repeat( '</ul></li>', $iPreviousDepth - $entry['depth'] );
		}
		if( !empty($aTree[($key + 1)]) && $aTree[$key + 1]['depth'] > $entry['depth'] ) $aClass[] = 'subTree';
		if( in_array($entry['navigationId'], $aPath) ) $aClass[] = 'selected';
		if( $oRouter->sPath === $entry['navigationUrl'] ) $aClass[] = 'active';
		if( $aCount[$entry['depth']] % 2 != 0 ) $aClass[] = 'odd';
		if( $key === $iFirstKey ) $aClass[] = 'first';
		if( $key === $iLastKey ) $aClass[] = 'last';

		if( ( !empty($aTree[($key + 1)]) && $aTree[$key + 1]['depth'] < $entry['depth'] )
		   || ( $entry['depth'] > 0 && empty($aTree[($key + 1)]) ) ) $aClass[] = 'subLast';

		if( !empty($entry['navigationOpenIn']) && $entry['navigationOpenIn'] == 'blank' ) {
			$sTarget = '_blank';
		} else {
			$sTarget = '_self';
		}

		if( substr($entry['navigationUrl'], 0, 1) == '#' ) {
			$aClass[] = substr( $entry['navigationUrl'], 1, (strlen($entry['navigationUrl'])-1) );
		}

		if( $entry['navigationId'] == 150 ) {
			if( $oRouter->sCurrentTemplateFile == 'tovekClassic.php' ) {
				$entry['navigationUrl'] = '/klassiskt';
			}
		}

		$sNavigationImage = '';
		if( !empty($entry['navigationImageSrc']) ) {
			$sNavigationImage = '<i class="' . $entry['navigationImageSrc'] . '"></i>';
		}

		$sOutput .= '<li' . ( !empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . '><a href="' . $entry['navigationUrl'] .'" target="' . $sTarget . '" class="ajax">' . $sNavigationImage . $entry['navigationTitle'] . '</a>';

		$sOutput .= ($entry['navigationRight'] - $entry['navigationLeft']) === 1 ? '</li>' : '';
		$iPreviousDepth = $entry['depth'];

		++$aCount[$entry['depth']];
	}

	$sOutput .=
			str_repeat( '</ul></li>', $iPreviousDepth ) . '
		</ul>';
}

echo $sOutput;
