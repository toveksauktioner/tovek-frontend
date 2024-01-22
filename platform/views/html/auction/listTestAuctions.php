<?php

if( empty($_SESSION['userId']) || !in_array($_SESSION['userId'], AUCTION_TEST_USERS) ) return;

$sOutput = '';

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oObjectStorage = clRegistry::get( 'clObjectStorage', PATH_MODULE . '/objectStorage/models' );
$oAuctionDao = $oAuctionEngine->getDao( 'Auction' );
$oAuctionDao->aSorting = array( 'partAuctionStart' => 'ASC' );

$aReadFields = array(
	'auctionId',
	'auctionTitle',
	'auctionShortTitle',
	'auctionLocation',
	'partLocation',
	'partId',
	'partTitle',
	'partAuctionTitle',
	'partDescription',
	'partYoutubeLink',
	'partAuctionStart',
	'partStatus',
	'partCreated',
	'routePath'
);

// Read running auctions
$aAllAuctions['running'] = valueToKey( 'partId', $oAuctionEngine->readAuction( array(
	'fields' => $aReadFields,
	'auctionStatus' => 'active',
	'partStatus' => 'running',
	'auctionId' => AUCTION_TEST_AUCTION_ID
) ) );


// Output list
if( !empty($aAllAuctions) ) {
	$aAllPartId = array_keys( $aAllAuctions['running'] );

	/**
	 * Running and upcoming
	 */
  foreach( $aAllAuctions as $sType => $sTypeAuctions ) {
 		$aAuctionList = array();

		foreach( $sTypeAuctions as $iPartId => $aAuction ) {
	    	// Format auction start date
			if( !empty($aAuction['partAuctionStart']) ) {
				$aAuction['partAuctionStart'] = date( 'Y-m-d', strtotime($aAuction['partAuctionStart']) );
			}

			// An alternative title replaces auction title if set
			if( !empty($aAuction['partAuctionTitle']) ) {
				$aAuction['auctionTitle'] = $aAuction['partAuctionTitle'];
			}

			// Read object storage images
			$oObjectStorage->oDao->aSorting = 'parentSort';
			$aObjectStorageImages = $oObjectStorage->readWithParams( [
				'type' => 'image',
				'parentTable' => 'entAuctionPart',
				'parentId' => $iPartId,
				'includeVariants' => true,
				'structureVariants' => true
			] );
			$oObjectStorage->oDao->aSorting = null;

			// Present all images (slideshow with magicscroll)
			$sImage = '';
			if( !empty($aObjectStorageImages) ) {
				foreach( $aObjectStorageImages as $aImage ) {
					$sImagePath = $aImage['small']['objectUrl'];

					$sImage .= '
						<a href="' . $aAuction['routePath'] . '" class="load-background" data-background-image="url(' . $sImagePath . ')"></a>';
				}

				if( count($aObjectStorageImages) > 1 ) {
					$sImage = '
						<div class="slideshow" data-slideshow>' . $sImage . '</div>';
				}

			} else {
				$sImagePath = '/images/templates/tovek/itemEmptyImage.png';
				$sImageBigPath = '/images/templates/tovek/itemEmptyImage.png';

				$sImage .= '
					<a href="' . $aAuction['routePath'] . '" class="load-background" data-background-image="url(' . $sImagePath . ')"></a>';
			}

	    // Auction title
			$sAuctionTitle = !empty($aAuction['auctionShortTitle']) ? $aAuction['auctionShortTitle'] : substr( $aAuction['auctionTitle'], 0, 120 );

			if( empty($aAuction['routePath']) ) {
				//continue;
			}

			$aClass = [ 'auction' ];

	    $aAuctionList[] = '
	        <article class="' . implode( ' ', $aClass ) . '" data-auction-Id="' . $aAuction['auctionId'] . '">
						<div class="image">' . $sImage . '</div>
            <div class="information">
							<div class="meta">
								<div class="location"><i class="fas fa-map-marker-alt">&nbsp;</i>' . ( !empty($aAuction['partLocation']) ? $aAuction['partLocation'] : $aAuction['auctionLocation'] ) . '</div>
								<div class="date"><i class="far fa-calendar">&nbsp;</i>' . $aAuction['partAuctionStart'] . '</div>
							</div>
              <a href="' . $aAuction['routePath'] . '">
								<h2>' . $sAuctionTitle . '</h2>
								' . (!empty($aAuction['partTitle']) ? '<h3>' . $aAuction['partTitle'] . '</h3>' : '') . '
							</a>
            </div>
	        </article>';
		}

	  $sOutput .= implode( '', $aAuctionList );
  }
}

echo $sOutput;
