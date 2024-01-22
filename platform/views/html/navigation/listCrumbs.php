<?php

$aNavigationGroupKeys = array(
	'guest',
	'user'
	//'meta'
);

$aAuctionReadFields = array(
	'auctionId',
	'auctionTitle',
	'auctionShortTitle',
	'partId',
	'partAuctionTitle',
	'partDescription',
	'partAuctionStart',
	'partLocation',
	'routePath'
);

$aItemReadFields = array(
	'itemSortNo',
	'itemTitle',
	'itemAuctionId',
	'itemPartId',
	'routePath'
);


$aPathData = array();
#echo $oRouter->sCurrentLayoutKey;
if( $oRouter->sCurrentLayoutKey == 'guestAuctionItems' ) {
	// Auction list - check auctiondata


	$aPathData[] = array(
		'navigationUrl' => '/',
		'navigationTitle' => _( 'Auktioner' )
	);

} else if( $oRouter->sCurrentLayoutKey == 'classicAuctionItems') {
	// Classic auction item list - check auctiondata

	$aPathData[] = array(
		'navigationUrl' => '/klassiskt',
		'navigationTitle' => _( 'Auktioner' )
	);


} else if( $oRouter->sCurrentLayoutKey == 'guestAuctionItemShow' ) {
	// Auction item - check auction and item data

	$aPathData[] = array(
		'navigationUrl' => '/',
		'navigationTitle' => _( 'Auktioner' )
	);

	$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

	$aUrlParts = array_reverse( explode('/', $oRouter->sPath) );

	$iItemId = null;
	foreach( $aUrlParts as $sUrlPart ) {
		if( !empty($iItemId) ) break;
		if( empty($sUrlPart) ) continue;

		if( ctype_digit($sUrlPart) ) {
			if( empty($iItemId) ) {
				$iItemId = $sUrlPart;
			}
		}
	}

	$aItemData = current( $oAuctionEngine->readAuctionItem( array(
		'itemId' => $iItemId,
		'fields' => $aItemReadFields
	) ) );

	if( !empty($aItemData) ) {
		$aAuctionData = current( $oAuctionEngine->readAuction( array(
			'auctionId' => $aItemData['itemAuctionId'],
			'partId' => $aItemData['itemPartId'],
			'fields' => $aAuctionReadFields
		) ) );

		if( !empty($aAuctionData) ) {
			$sTitle 	= ( !empty($aAuctionData['partAuctionTitle']) ? $aAuctionData['partAuctionTitle'] : $aAuctionData['auctionTitle'] );
			$sTitle .= ( !empty($aAuctionData['partDescription']) ? '<span class="extended"> - ' . $aAuctionData['partDescription'] : '</span>' );

			$aPathData[] = array(
				'navigationUrl' => $aAuctionData['routePath'],
				'navigationTitle' => $sTitle
			);
		}

	}

} else if( $oRouter->sCurrentLayoutKey == 'classicAuctionItemShow' ) {
	// Classic auction item - check auction and item data

	$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

	$iItemId = ( !empty($_GET['itemId']) ? $_GET['itemId'] : null );

	$aItemData = current( $oAuctionEngine->readAuctionItem( array(
		'itemId' => $iItemId,
		'fields' => $aItemReadFields
	) ) );

	if( !empty($aItemData) ) {
		$aAuctionData = current( $oAuctionEngine->readAuction( array(
			'auctionId' => $aItemData['itemAuctionId'],
			'partId' => $aItemData['itemPartId'],
			'fields' => $aAuctionReadFields
		) ) );

		if( !empty($aAuctionData) ) {
			$sTitle 	= ( !empty($aAuctionData['partAuctionTitle']) ? $aAuctionData['partAuctionTitle'] : $aAuctionData['auctionTitle'] );
			$sTitle .= ( !empty($aAuctionData['partDescription']) ? '<span class="extended"> - ' . $aAuctionData['partDescription'] : '</span>' );

			$aPathData[] = array(
				'navigationUrl' => $oRouter->getPath( 'classicAuctionItems' ) . '?auctionId=' . $aItemData['itemAuctionId'] . '&partId=' . $aItemData['itemPartId'],
				'navigationTitle' => $sTitle
			);
		}

	}

} else {
	// Normal page - check navigation for crumbs

	$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );

	foreach( $aNavigationGroupKeys as $sGroup ) {
		$oNavigation->setGroupKey( $sGroup );
		$aPathData = $oNavigation->readWithParentsByUrl( $oRouter->sPath );
		array_pop( $aPathData );
		if( !empty($aPathData) ) break;
	}
}

$aBreadCrumbs = array( '
	<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
		<a itemprop="item" href="/">
			<span itemprop="name"><i class="fa fa-home"></i></span>
		</a>
		<meta itemprop="position" content="1" />
	</li>' );

if( !empty($aPathData) ) {
	foreach( $aPathData as $aCrumbs ) {
		$aBreadCrumbs[] = '
			<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
				<a itemprop="item" href="' . $aCrumbs['navigationUrl'] . '">
					<span itemprop="name">' . $aCrumbs['navigationTitle'] . '</span>
				</a>
				<meta itemprop="position" content="1" />
			</li>';
	}
}

if( $aBreadCrumbs ) {
	echo '
		<nav role="breadcrumb" class="view navigation listCrumbs">
			<ol itemscope itemtype="https://schema.org/BreadcrumbList">
				' . implode( '<li class="separator"><i class="fas fa-angle-right"></i></li>', $aBreadCrumbs ) . '
			</ol>
		</nav>';
}
