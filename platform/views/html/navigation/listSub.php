<?php

require_once PATH_FUNCTION . '/fData.php';
$oRouter = clRegistry::get( 'clRouter' );

if( empty($GLOBALS['viewParams']['navigation']['listSub.php']['groupKey']) ) {
	$GLOBALS['viewParams']['navigation']['listSub.php']['groupKey'] = 'guest';
}

$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
$oNavigation->setGroupKey( $GLOBALS['viewParams']['navigation']['listSub.php']['groupKey'] );

$aPath = arrayToSingle( $oNavigation->readWithParentsByUrl($oRouter->sPath), null, 'navigationId' );

$iRootId = current($aPath);

$aTree = $oNavigation->readSubtree( $iRootId, array(
	'navigationId',
	'navigationTitle',
	'navigationUrl',
	'navigationLeft',
	'navigationRight'
) );

// Depth check
$iDepthTotal = 0;
foreach( $aTree as $key => $entry ) $iDepthTotal += $entry['depth'];

$sOutput = '';

if( !empty($aTree) && !empty($aPath) && $iDepthTotal > 0 ) {
	$iPreviousDepth = 0;

	// Find first and last keys
	foreach( $aTree as $key => $entry ) if( $entry['depth'] == 0 ) $iLastKey = $key;
	reset($aTree);
	$iFirstKey = key($aTree);
	
	$sOutput .= '
		<nav class="view listSub">
			<ul class="navSub">';

	$aCount = array();

	foreach( $aTree as $key => $entry ) {
		$iLength = strlen($entry['depth']);
		if( $iLength > 1 ) $entry['depth'] = "0";
		
		// Don't display the root
		if( $entry['navigationId'] == $iRootId ) continue;
		--$entry['depth'];
		
		if( empty($aCount[$entry['depth']]) ) $aCount[$entry['depth']] = 1;
		
		$aClass = array();
		if( $key === key($aTree) ) $aClass[] = 'first';
		
		if( $entry['depth'] > $iPreviousDepth ) {
			$sOutput .= '
			<ul>';
		} elseif(  $entry['depth'] < $iPreviousDepth  ) {
			$aCount[$iPreviousDepth] = 0;
			$sOutput .= str_repeat( '
			</ul>
			</li>', $iPreviousDepth - $entry['depth'] );
		}
		if( !empty($aTree[($key + 1)]) && $aTree[$key + 1]['depth'] > $entry['depth'] ) $aClass[] = 'subTree';
		if( in_array($entry['navigationId'], $aPath) ) $aClass[] = 'selected';
		if( $oRouter->sPath ==  $entry['navigationUrl'] ) $aClass[] = 'active';
		if( $aCount[$entry['depth']] % 2 == 0 ) $aClass[] = 'odd';
		if( empty($aTree[($key + 1)]) ) $aClass[] = 'last';
		
		if( !empty($entry['navigationOpenIn']) && $entry['navigationOpenIn'] == 'blank' ) {
			$sTarget = '_blank';
		} else {
			$sTarget = '_self';
		}
		
		$sOutput .= '
			<li' . ( !empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . '><a href="' . $entry['navigationUrl'] .'" target="' . $sTarget . '">' . $entry['navigationTitle'] . '</a>';
			
		$sOutput .= ($entry['navigationRight'] - $entry['navigationLeft']) === 1 ? '</li>' : '';
		$iPreviousDepth = $entry['depth'];
		
		++$aCount[$entry['depth']];
	}

	$sOutput .= str_repeat( '
				</ul>
				</li>', $iPreviousDepth ) . '
			</ul>
		</nav>';
}

echo $sOutput;