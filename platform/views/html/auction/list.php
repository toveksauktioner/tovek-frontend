<?php

// Variables used in trailing views
// Set in loop below
$GLOBALS['auctionNav'] = array();

$sOutput = '';
$sOutputCurrentAuction = '';
$sShowTab = ( isset($_GET['kommande']) ? 'upcoming' : 'running' );
$iBreakpointAsNew = time() - 86400; // Breakpoint for considered new

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
	'partStatus' => 'running'
) ) );

// Read upcomming auctions
// $aAllAuctions['upcoming'] = valueToKey( 'partId', $oAuctionEngine->readAuction( array(
// 	'fields' => $aReadFields,
// 	'auctionStatus' => 'active',
// 	'partStatus' => 'upcomming'
// ) ) );

// Read current auction
$iCurrentPartId = null;
$aRouterObj = current( $oRouter->readObjectByRoute() );
if( !empty($aRouterObj) ) {
	if( $aRouterObj['objectType'] == 'AuctionPart' ) {
		$iCurrentPartId = $aRouterObj['objectId'];

		// If auction not in current auction - check backend if it is ended
		if( empty($aAllAuctions['running'][$iCurrentPartId]) && empty($aAllAuctions['upcoming'][$iCurrentPartId]) ) {
			$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
			$oBackEnd->setSource( 'entAuctionPart', 'partId' );
			$aPartData = current( $oBackEnd->read([
				'partId',
				'partAuctionId',
				'partStatus'
			], $iCurrentPartId) );

			if( !empty($aPartData) && ($aPartData['partStatus'] == 'ended') ) {
				// Redirect with 301
				header( 'HTTP/1.1 301 Moved Permanently' );
				header( 'Location: ' . $oRouter->getPath('guestAuctionItemsArchived') . '?auctionId=' . $aPartData['partAuctionId'] . '&partId=' . $aPartData['partId'] );
				exit();
			}
		}

		// $bUpcoming = array_key_exists( $iCurrentPartId, $aAllAuctions['upcoming'] );

		// if( $bUpcoming ) $sShowTab = 'upcoming';

		$sAuctionTitle = $aAllAuctions[$sShowTab][$iCurrentPartId]['auctionTitle'];
		if( !empty($aAllAuctions[$sShowTab][$iCurrentPartId]['partAuctionTitle']) ) {
			$sAuctionTitle = $aAllAuctions[$sShowTab][$iCurrentPartId]['partAuctionTitle'];
		}
		$oTemplate->setTitle( $sAuctionTitle . ' - ' . $aAllAuctions[$sShowTab][$iCurrentPartId]['partTitle'] . ' - ' . SITE_DEFAULT_TITLE);
	}
}

// Output list
if( !empty($aAllAuctions) ) {
	// $aAllPartId = array_merge( array_keys($aAllAuctions['running']), array_keys($aAllAuctions['upcoming']) );
	$aAllPartId = array_keys( $aAllAuctions['running'] );

	// Read object storage images
	$oObjectStorage->oDao->aSorting = 'parentSort';
	$aObjectStorageImages = groupByValue( 'parentId', $oObjectStorage->readWithParams([
		'type' => 'image',
		'parentTable' => 'entAuctionPart',
		'parentId' => $aAllPartId,
		'includeVariants' => true
	]) );
	$oObjectStorage->oDao->aSorting = null;

	// Structure images 
	if( !empty($aObjectStorageImages) ) {
		$aTempImageData = [];

		foreach( $aObjectStorageImages as $iPartId => $aImages ) {
			foreach( $aImages as $key => $aImage ) {
				$aTempImageData[ $iPartId ][ $aImage['parentSort'] ][ $aImage['objectVariant'] ] = $aImage;
			}
		}

		$aObjectStorageImages = $aTempImageData;
	}

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

			// Present all images (slideshow with magicscroll)
			$sImage = '';
			if( !empty($aObjectStorageImages[$iPartId]) ) {
				foreach( $aObjectStorageImages[$iPartId] as $aImage ) {
					$sImagePath = $aImage['small']['objectUrl'];

					if( ($iPartId == $iCurrentPartId) && empty($sImageBigPath) ) {
						$sImageBigPath = $aImage['large']['objectUrl'];
					}

					$sImage .= '
						<a href="' . $aAuction['routePath'] . '" class="load-background" data-background-image="url(' . $sImagePath . ')"></a>';
				}

				if( count($aObjectStorageImages[$iPartId]) > 1 ) {
					// $sImage = '
					// 	<div class="MagicScroll mcs-bounce" data-options="autoplay:2000; speed:2000; items:1; arrows:off; lazyLoad:true;">' . $sImage . '</div>';
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

			// Set navigation used in trailing views
			$aAuctionNavData = array(
				'text' => $sAuctionTitle . (!empty($aAuction['partTitle']) ? ' - ' . $aAuction['partTitle'] : ''),
				'url' => $aAuction['routePath']
			);
			if( ($iPartId != $iCurrentPartId) && empty($GLOBALS['auctionNav']['current']) ) {
				$GLOBALS['auctionNav']['previous'] = $aAuctionNavData;
			}
			if( !empty($GLOBALS['auctionNav']['current']) && empty($GLOBALS['auctionNav']['next']) ) {
				$GLOBALS['auctionNav']['next'] = $aAuctionNavData;
			}
			if( $iPartId == $iCurrentPartId ) {
				$GLOBALS['auctionNav']['current'] = $aAuctionNavData;
			}

			$aClass = [ 'auction' ];
			if( $iPartId == $iCurrentPartId ) $aClass[] = 'selected';
			if( strtotime($aAuction['partCreated']) > $iBreakpointAsNew ) $aClass[] = 'new';


		// Skip listing test auctions
		if( $aAuction['auctionId'] != AUCTION_TEST_AUCTION_ID ) {
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

			if( $iPartId == $iCurrentPartId ) {
				$sYoutubeContent = '';
				if( !empty($aAuction['partYoutubeLink']) && ($aAuction['partStatus'] == 'upcomming') ) {
					$sYoutubeContent .= '
						<div class="video">
							<h2>' . _( 'Video från auktionsplatsen' ) . '</h2>
							<div class="videoContainer">
				        <iframe src="' . str_replace( 'youtu.be', 'youtube.com/embed', $aAuction['partYoutubeLink'] ) . '?rel=0" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				      </div>
				    </div>';
				}

				$sImageGallery = '';
				if( $aAuction['partStatus'] == 'upcomming' ) {
					if( !empty($aObjectStorageImages[$iPartId]) ) {
						$sImageGallery .= '
							<div class="imageGallery">
								<h2>' . _( 'Bilder från auktionsplatsen' ) . '</h2>
								<ul class="images">';

						foreach( $aObjectStorageImages[$iPartId] as $iObjectId => $aObject ) {
							$sImageGallery .= '
									<li><img src="' . $aObject['small']['objectUrl'] . '"></li>';
						}

						$sImageGallery .= '
								</ul>
							</div>';
					}
				}

				$sOutputCurrentAuction .= '
					<div class="currentContainer">
						<div class="image" style="background-image: url(' . $sImageBigPath . ');"></div>
						<div class="information">
							<h1>
								' . $aAuction['auctionTitle'] . '
								' . (!empty($aAuction['partTitle']) ? '<span>' . $aAuction['partTitle'] . '</span>' : '') . '
							</h1>
							' . (!empty($aAuction['partDescription']) ? $aAuction['partDescription'] : '') . '
							<div class="meta">
								<div class="location"><i class="fas fa-map-marker-alt">&nbsp;</i>' . ( !empty($aAuction['partLocation']) ? $aAuction['partLocation'] : $aAuction['auctionLocation'] ) . '</div>
								<div class="date"><i class="far fa-calendar">&nbsp;</i>' . $aAuction['partAuctionStart'] . '</div>
							</div>
						</div>
						' . $sYoutubeContent . '
						' . $sImageGallery . '
					</div>';

					$oTemplate->addOgTag( 'image', $sImageBigPath );
			}
		}

	  $sOutput .= '<div class="innerContainer ' . $sType . '"' . ( ($sType == $sShowTab) ? '' : ' style="display:none;"' ) . '>' . implode( '', $aAuctionList ) . '</div>';
  }
}

echo '
    <div class="view auction list">
		<!-- Ver 1 -->
			<div class="currentAuction" ' . ( empty($iCurrentPartId) ? 'style="display: none;"' : '' ) . '>
				' . $sOutputCurrentAuction . '
	      <div class="buttons">
	        <button class="toggleAuctions showAuctions small">' . _( 'Visa auktioner' ) . '</button>
	        <button class="toggleAuctions hideAuctions small" style="display: none;"">' . _( 'Göm auktioner' ) . '</button>
	      </div>
			</div>
			<div class="auctions showAll showAuctionsToggle" ' . ( !empty($iCurrentPartId) ? 'style="display: none;"' : '' ) . '>
	      <div class="container">
	        ' . $sOutput . '
				</div>
	    </div>
		</div>
		<script>
			function loadBackgroundImages() {
				$("a.load-background").each( function() {
					if( isVisibleInViewport($(this)) ) {
						$( this ).css("background-image", $(this).data("background-image") );
						$( this ).removeClass("load-background");
					}
				} );
			}

			$(".auction.list .buttons button.toggleAuctions").click( function() {
				$(".auction.list .buttons button.toggleAuctions").toggle();

				if( $(this).hasClass("showAuctions") ) {
					$(".auction.list .showAuctionsToggle").show();
				} else {
					$(".auction.list .showAuctionsToggle").hide();
				}
			} );

			// $(".tabs.auctionType .tab").click( function() {
			// 	$(".auction.list .innerContainer").toggle();
			// 	$(".tabs.auctionType .tab").toggleClass( "selected" );
			// } );

			$( function() {
				$( window ).scroll( function() {
					loadBackgroundImages();
			 	} );
				loadBackgroundImages();
			} );
		</script>';
