<?php

header( 'Content-Type: text/xml' );

/**
 *
 * Generate a sitemap according to sitemaps.org protocol
 *
 */

require_once PATH_FUNCTION . '/fData.php';

$oRouter = clRegistry::get( 'clRouter' );

/**
 * Settings
 */
$bNavigation  		 	= true;
$bInfoContent 		 	= true;
$bNews 				 			= !file_exists(PATH_MODULE . '/news') ? false : true;
$bAuctions 			 		= !file_exists(PATH_MODULE . '/auction') ? false : true;
$bDuplicateCheck 	 = true;

$aDuplicates = array();

/**
 * URL function
 */
function outputUrl( $aParams = array() ) {
	$aParams += array(
		'loc' => null,
		'lastmod' => null,
		'changefreq' => 'daily',
		'priority' => null,
		'escape' => true
	);
	if($aParams['loc'] === null) return false;

	$sUrl = "\n\t<url>\n\t\t<loc>" . ( $aParams['escape'] === null ? htmlspecialchars($aParams['loc']) : $aParams['loc'] ) . "</loc>";

	if($aParams['lastmod'] !== null) $sUrl .= "\n\t\t<lastmod>" . htmlspecialchars($aParams['lastmod']) . "</lastmod>";
	if($aParams['changefreq'] !== null) $sUrl .= "\n\t\t<changefreq>" . htmlspecialchars($aParams['changefreq']) . "</changefreq>";
	if($aParams['priority'] !== null) $sUrl .= "\n\t\t<priority>" . htmlspecialchars($aParams['priority']) . "</priority>";

	$sUrl .= "\n\t</url>";

	return $sUrl;
}

echo '
<?xml version="1.0" encoding="UTF-8" ?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
         xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Navigation
if( $bNavigation === true ) {
	$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
	$oNavigation->setGroupKey( 'guest' );

	$aTree = $oNavigation->read( array(
		'navigationUrl'
	) );

	if( !empty($aTree) ) {
		foreach( $aTree as $key => $entry ) {
			echo outputUrl( array(
				'loc' => 'http://' . SITE_DOMAIN . $entry['navigationUrl'],
				'lastmod' => null,
				'changefreq' => 'monthly',
				'priority' => null
			) );

			$aDuplicates[] = $entry['navigationUrl'];
		}
	}
}

// InfoContent
if( $bInfoContent === true ) {
	$oLayout = clRegistry::get( 'clLayoutHtml' );
	$aCustomLayouts = $oLayout->readCustom( array(
		'layoutKey',
		'layoutTitleTextId'
	) );

	if( !empty($aCustomLayouts) ) {
		$aInfoContentLayouts = arrayToSingle($aCustomLayouts, null, 'layoutKey');

		$aRoutesData = $oRouter->getPath($aInfoContentLayouts);
		$aRoutes = arrayToSingle($aRoutesData, 'routeLayoutKey', 'routePath');

		$aLayoutToLayoutData = array();
		$aViewIds = array();
		foreach( $aInfoContentLayouts as $sLayout) {
			$aTmpLayoutData = $oLayout->readSectionsAndViews($sLayout);
			foreach( $aTmpLayoutData as $entry ) {
				$aLayoutToLayoutData[$sLayout][] = $entry['viewId'];
				$aViewIds[] = $entry['viewId'];
			}
		}

		$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
		$aContentStatus = arrayToSingle( $oInfoContent->readByView( $aViewIds, array(
			'contentViewId',
			'contentStatus'
		) ), 'contentViewId', 'contentStatus' );

		foreach( $aCustomLayouts as $entry ) {
			if( !array_key_exists($entry['layoutKey'], $aRoutes) || in_array($aRoutes[ $entry['layoutKey'] ], $aDuplicates) ) continue;

			foreach( $aLayoutToLayoutData[$entry['layoutKey']] as $iViewId ) {
				if( array_key_exists($iViewId,$aContentStatus) && $aContentStatus[$iViewId] == 'inactive' ) {
					continue 2;
				}
			}

			echo outputUrl( array(
				'loc' => 'http://' . SITE_DOMAIN . $aRoutes[ $entry['layoutKey'] ],
				'lastmod' => null,
				'changefreq' => 'monthly',
				'priority' => null,
				'escape' => false
			) );

			$aDuplicates[] = $aRoutes[ $entry['layoutKey'] ];
		}

	}
}

/**
 * News
 */
if( $bNews === true ) {
	$oNews = clRegistry::get( 'clNews', PATH_MODULE . '/news/models' );

	$aPublishedNews = arrayToSingle( $oNews->aHelpers['oJournalHelper']->read( array('newsId') ), null, 'newsId' );

	if( !empty($aPublishedNews) ) {
		$aNews = $oNews->read( array(
			'newsId',
			'newsTitleTextId',
			'newsSummaryTextId',
			'newsPublishStart',
			'newsCreated',
			'routePath'
		), $aPublishedNews );

		foreach( $aNews as $aEntry ) {
			echo outputUrl( array(
				'loc' => 'http://' . SITE_DOMAIN . $aEntry['routePath'],
				'lastmod' => null,
				'changefreq' => 'monthly',
				'priority' => null,
				'escape' => false
			) );
		}
	}
}

// Auctions
if( $bAuctions === true ) {
	$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

	$aAuctions = $oAuctionEngine->readAuction( array(
		'fields' => array(
			'partId',
			'routePath'
		),
		'auctionStatus' => 'active',
		'partStatus' => array(
			'running',
			'upcomming'
		)
	) );

	foreach( $aAuctions as $aAuction ) {
		if( !in_array($aAuction['routePath'], $aDuplicates) ) {
			echo outputUrl( array(
				'loc' => 'http://' . SITE_DOMAIN . $aAuction['routePath'],
				'lastmod' => null,
				'changefreq' => 'daily',
				'priority' => null,
				'escape' => false
			) );
			$aDuplicates[] = $aAuction['routePath'];
		}
	}
}

echo '
</urlset>';
