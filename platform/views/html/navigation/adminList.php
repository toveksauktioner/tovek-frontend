<?php

require_once PATH_FUNCTION . '/fData.php';

$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
$oRouter = clRegistry::get( 'clRouter' );

// Get trailing path
$aPath = arrayToSingle( $oNavigation->readWithParentsByUrl($oRouter->sPath), null, 'navigationId' );

$sGroupKey =& $_SESSION['user']['groupKey'];
if( array_key_exists('martin', $_SESSION['user']['groups']) )	{
	$sGroupKey = 'martin';
}

if( $sGroupKey == 'martin' ) {
	$oNavigation->setGroupKey( 'martin' );

	$aTreeData['martin'] = $oNavigation->read( array(
		'navigationId',
		'navigationImageSrc',
		'navigationTitle',
		'navigationUrl',
		'navigationOpenIn',
		'navigationLeft',
		'navigationRight'
	) );

} else {
	$oNavigation->setGroupKey( 'admin' );

	$aTreeData['admin'] = $oNavigation->read( array(
		'navigationId',
		'navigationImageSrc',
		'navigationTitle',
		'navigationUrl',
		'navigationOpenIn',
		'navigationLeft',
		'navigationRight'
	) );
}

if( $sGroupKey == 'super' || ($sGroupKey == 'admin' && array_key_exists('super', $_SESSION['user']['groups'])) ) {
	$oNavigation->setGroupKey( 'super' );

	// $aPath = arrayToSingle( $oNavigation->readWithParentsByUrl($oRouter->sPath), null, 'navigationId' );

	$aTreeData['super'] = $oNavigation->read( array(
		'navigationId',
		'navigationImageSrc',
		'navigationTitle',
		'navigationUrl',
		'navigationOpenIn',
		'navigationLeft',
		'navigationRight'
	) );
}

$sOutput = '';

if( !empty($aTreeData) ) {
	foreach( $aTreeData as $sTreeGroupKey => $aTree ) {
		$iPreviousDepth = 0;

		// Find first and last keys
		foreach( $aTree as $key => $entry ) if( $entry['depth'] == 0 ) $iLastKey = $key;
		reset($aTree);
		$iFirstKey = key($aTree);

		$sOutput .= '
			<ul class="' . $sTreeGroupKey . '">';

		$aCount = array();

		foreach( $aTree as $key => $entry ) {
			if( empty($aCount[$entry['depth']]) ) $aCount[$entry['depth']] = 1;

			$bSubtreeToggle = $bSubtreeTooggleOn = false;

			$aClass = array();
			if( $entry['depth'] > $iPreviousDepth ) {
				$aClass[] = 'subFirst';

				$sOutput .= '<ul>';
			} elseif(  $entry['depth'] < $iPreviousDepth  ) {
				$aCount[$iPreviousDepth] = 0;
				$sOutput .= str_repeat( '</ul></li>', $iPreviousDepth - $entry['depth'] );
			}
			if( !empty($aTree[($key + 1)]) && $aTree[$key + 1]['depth'] > $entry['depth'] ) {
				$aClass[] = 'subTree';
				$bSubtreeToggle = true;
			}
			if( in_array($entry['navigationId'], $aPath) || $entry['navigationId'] == '0' ) {
				$aClass[] = 'selected';
				$bSubtreeTooggleOn = true;
			}
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

			// Compose menu item
			$sMenuItem = '';
			if( $bSubtreeToggle ) {
				$sMenuItem .= '
					<input type="checkbox" style="display: none;" id="navToggle' . $entry['navigationId'] . '"' . ( $bSubtreeTooggleOn ? ' checked="checked"' : '' ) . '>
					<label for="navToggle' . $entry['navigationId'] . '">
						<span class="open"><i class="fas fa-minus"></i></span>
						<span class="closed"><i class="fas fa-plus"></i></span>
					</label>';
			}
			if( !empty($entry['navigationImageSrc']) ) {
				$entry['navigationTitle'] = '<i class="' . $entry['navigationImageSrc'] . '">&nbsp;</i>' . $entry['navigationTitle'];
			}
			$sMenuItem .= '<a href="' . $entry['navigationUrl'] .'" target="' . $sTarget . '" class="ajax">' . $entry['navigationTitle'] . '</a>';

			$sOutput .= '<li' . ( !empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . ' id="nav' . $entry['navigationId'] . '">' . $sMenuItem;

			$sOutput .= ($entry['navigationRight'] - $entry['navigationLeft']) === 1 ? '</li>' : '';
			$iPreviousDepth = $entry['depth'];

			++$aCount[$entry['depth']];
		}

		$sOutput .=
				str_repeat( '</ul></li>', $iPreviousDepth ) . '
			</ul>';
	}
}

echo $sOutput;
