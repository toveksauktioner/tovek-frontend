<?php

$sOutput = '';

if( !empty($_GET['searchQuery']) ) {
	$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
	$oLayout = clRegistry::get( 'clLayoutHtml' );
	$oAcl = clRegistry::get( 'clAcl' );
	
	// Search for info contents
	$oInfoContent->oDao->setCriterias( array(
		'searchInfoContent' => array(
			'fields' => array('contentTextId'),
			'value' => $_GET['searchQuery'],
			'type' => 'like'
		),
		'searchInfoContentWithHtmlEntities' => array(
			'fields' => array('contentTextId'),
			'value' => htmlentities($_GET['searchQuery']),
			'type' => 'like'
		)		
	), 'OR' );
	$aInfoContentData = $oInfoContent->read( array(
		'contentTextId',
		'contentViewId'
	) );
	
	// Search for layouts
	$oLayout->oDao->setCriterias( array(
		'searchLayout' => array(
			'fields' => array(
				'layoutTitleTextId',
				'layoutKeywordsTextId',
				'layoutDescriptionTextId'
			),
			'value' => $_GET['searchQuery'],
			'type' => 'like'
		)
	) );
	$aLayoutSearch = $oLayout->read( array(
		'layoutKey',
		'layoutTitleTextId',
		'layoutKeywordsTextId',
		'layoutDescriptionTextId'
	) );
	$oLayout->oDao->setCriterias( null );
	
	if( !empty($aInfoContentData) || !empty($aLayoutSearch) ) {
		
		require_once( PATH_FUNCTION . '/fData.php' );
		$oRouter = clRegistry::get( 'clRouter' );
		
		$aAllLayouts = array();
		
		// Read layouts by infoContent
		if( !empty($aInfoContentData) ) {

			// Read layouts by coupled views
			$aViewToLayout = arrayToSingle(
				$oLayout->readByViewId( arrayToSingle( $aInfoContentData, null, 'contentViewId' ) ),
				'viewId',
				'sectionLayoutKey'
			);
			
			$aAllLayouts += array_values( $aViewToLayout );
		}
		if( !empty($aLayoutSearch) ) $aAllLayouts = array_merge( $aAllLayouts, arrayToSingle($aLayoutSearch, null, 'layoutKey') );
		
		$aAllowedLayouts = arrayToSingle( $oAcl->readByAco($aAllLayouts, 'aclAcoKey', 'layout'), null, 'aclAcoKey' );
		$aAllLayouts = array_intersect( $aAllLayouts, $aAllowedLayouts );
		
		if( !empty($aAllLayouts) ) { 
			// Read titles
			if( !empty($aViewToLayout) ) {
				// Read layout titles
				$aLayoutData = arrayToSingle(
					$oLayout->read( array(
						'layoutTitleTextId',
						'layoutKey'
					), $aAllLayouts ),
					'layoutKey',
					'layoutTitleTextId'
				);
			}
	
			// Read routes
			$aLayoutToRoute = arrayToSingle(
				$oRouter->getPath( $aAllLayouts ),
				'routeLayoutKey',
				'routePath'
			);
			
			// Merge layout search with infoContent search
			$aCombinedData = array();
			if( !empty($aLayoutSearch) ) {
				foreach( $aLayoutSearch as $entry ) {
					if( array_key_exists($entry['layoutKey'], $aLayoutToRoute) ) {
						
						if( empty($entry['layoutTitleTextId']) ) $entry['layoutTitleTextId'] = _( 'No title' );
						$aCombinedData[ $entry['layoutKey'] ] = $entry + array(
							'routePath' => $aLayoutToRoute[ $entry['layoutKey'] ]
						);
					}
				}
			}
			
			if( !empty($aInfoContentData) ) {
				foreach( $aInfoContentData as $entry ) {
					// Check so this is not just a infoblock
					if( !array_key_exists($entry['contentViewId'], $aViewToLayout) ) continue;
					
					// Check if route exists
					if( !array_key_exists($aViewToLayout[ $entry['contentViewId'] ], $aLayoutToRoute) ) continue;
					
					// Check if layout is allready in result
					if( array_key_exists($aViewToLayout[ $entry['contentViewId'] ], $aCombinedData) ) continue;
					$aCombinedData[ $aViewToLayout[ $entry['contentViewId'] ] ] = $entry + array(
						'layoutTitleTextId' => ( empty($aLayoutData[ $aViewToLayout[ $entry['contentViewId'] ] ]) ? _( 'No title' ) : $aLayoutData[ $aViewToLayout[ $entry['contentViewId'] ] ] ),
						'routePath' => $aLayoutToRoute[ $aViewToLayout[ $entry['contentViewId'] ] ]
					);
				}
			}
			
			$sOutput .= '
			<strong>' . _( 'Found' ) . ' ' . count($aCombinedData)  . ' ' . _( 'matches for' ) . ' "' . htmlentities($_GET['searchQuery'], ENT_QUOTES, 'UTF-8') . '"</strong>
			<ol>';
			
			foreach( $aCombinedData as $entry ) {
				$sOutput .= '
				<li>
					<a href="' . $entry[ 'routePath'] . '" class="ajax">' . $entry['layoutTitleTextId'] . '</a>			
				</li>';
			}
			
			$sOutput .= '
			</ol>';
			
		} else {
			$sOutput .= '
			<strong>' . sprintf( _( 'No matches for "%s" found' ), htmlentities($_GET['searchQuery'], ENT_QUOTES, 'UTF-8') ) . '</strong>';
		}
	} else {
		$sOutput .= '
			<strong>' . sprintf( _( 'No matches for "%s" found' ), htmlentities($_GET['searchQuery'], ENT_QUOTES, 'UTF-8') ) . '</strong>';
	}
	
	// Reset criterias and such
	$oInfoContent->oDao->setCriterias( null );
	$oLayout->oDao->setCriterias( null );
	
} else {
	$sOutput .= '';
}

echo '
<div class="view guestSearchResults">
		<div class="breadcrumbs">
			' . _( 'Search results' ) . '
		</div>
	<h1>' . _( 'Search results' ) . '</h1>
	' . $sOutput . '
</div>';