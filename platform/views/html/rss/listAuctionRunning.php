<?php

$sItems = '';

$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oAuctionDao = $oAuctionEngine->getDao( 'Auction' );
$oObjectStorage = clRegistry::get( 'clObjectStorage', PATH_MODULE . '/objectStorage/models' );

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oAuctionDao, array(
	'currentSort' => ( array('partAuctionStart' => 'ASC') )
) );

$aAuctionData = $oAuctionEngine->readAuction( array(
 'fields' => array(
  'auctionId',
  'auctionTitle',
  'auctionDescription',
	'partId',
  'partAuctionStart',
  'routePath'
 ),
 'auctionStatus' => 'active',
 'partStatus' => 'running'
) );

if( !empty($aAuctionData) ) {
  $aPartIds = arrayToSingle( $aAuctionData, null, 'partId' );

  // Part published is only set in backend
  $oBackEnd->setSource( 'entAuctionPart', 'partId' );
  $oBackEnd->oDao->setCriterias( array(
    'partId' => array(
      'fields' => 'partId',
      'type' => 'in',
      'value' => $aPartIds
    )
  ) );
  $aPartPublished = arrayToSingle( $oBackEnd->read( array(
    'partId',
    'partPublished'
  ) ), 'partId', 'partPublished' );
  $oBackEnd->oDao->sCriterias = null;

	// Read object storage images
	$oObjectStorage->oDao->aSorting = 'parentSort';
	$aObjectStorageImages = groupByValue( 'parentId', $oObjectStorage->readWithParams([
		'type' => 'image',
		'parentTable' => 'entAuctionPart',
		'parentId' => $aPartIds,
		'includeVariants' => ['medium']
	]) );
	$oObjectStorage->oDao->aSorting = null;


	$sAuctionShortUrlPath = $oRouter->getPath( 'emptyAuctionShortUrl' );
	foreach( $aAuctionData as $aAuction ) {
		$sSite = ( (SITE_DEFAULT_PROTOCOL == 'http') ? 'http' : 'https' ) . '://' . SITE_DOMAIN;
		$iPubTime = ( !empty($aPartPublished[ $aAuction['partId'] ]) ? strtotime($aPartPublished[ $aAuction['partId'] ]) : time() );
		$sLink = $sSite . $sAuctionShortUrlPath . '?a=' . $aAuction['auctionId'] . '-' . $aAuction['partId'];

		// Get main image from object storage
		if( !empty($aObjectStorageImages[ $aAuction['partId'] ]) ) {
			$aMainImage = current( $aObjectStorageImages[ $aAuction['partId'] ] );
			$sImagePath = $aMainImage['objectUrl'];

		} else {
			$sImagePath = $sSite . '/images/templates/tovek/itemEmptyImage.png';
		}

		$sItems .= '
			<item>
				<guid isPermaLink="true">' . $sLink . '</guid>
				<link>' . $sLink . '</link>
				<title>' . htmlspecialchars( html_entity_decode($aAuction['auctionTitle']) ) . '</title>
				<description>
					<![CDATA[
						<img src="' . $sImagePath . '" />
						<p><i>' . substr( $aAuction['partAuctionStart'], 0, 16 ) . '</i></p>
						<p>' . htmlspecialchars( html_entity_decode(strip_tags($aAuction['auctionDescription'])) ) . '</p>
					]]>
				</description>
				<pubDate>' . date( 'r', $iPubTime ) . '</pubDate>
			</item>';
	}
}

echo '
	<channel>
		<atom:link href="' . $sSite . $oRouter->sPath . '" rel="self" type="application/rss+xml" />
		<link>' . $sSite . '</link>
	  <title>' . htmlspecialchars( 'Toveks Auktioner & Internetauktioner - Auktion på internet varje vecka' ) . '</title>
	  <description>' . htmlspecialchars( sprintf(_('Pågående auktioner på %s'), SITE_DOMAIN) ) . '</description>
	  ' . $sItems . '
	</channel>';
