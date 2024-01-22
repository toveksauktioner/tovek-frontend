
<?php

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
$oObjectStorage = clRegistry::get( 'clObjectStorage', PATH_MODULE . '/objectStorage/models' );

// Determe item ID
$iItemId = !empty($_GET['itemId']) ? $_GET['itemId'] : ( ($aData = $oRouter->readObjectByRoute()) ? $aData[0]['objectId'] : null );
if( empty($iItemId) ) return;

// Item data
$oBackEnd->oDao->setCriterias( array(
  'itemStatus' => array(
    'type' => 'in',
    'value' => array(
			'active',
			'ended',
			'cancelled'
		),
    'fields' => 'itemStatus'
  )
) );
$aItem = current( $oBackEnd->readAuctionItem('*', $iItemId) );
if( empty($aItem) ) return;

// Formatted variables
$sCallNo = _( 'Call' ) . ' ' . $aItem['itemSortNo'];
$sItemTitle = $aItem['itemTitle'];


// SEO data
$oTemplate->setTitle( $aItem['itemTitle'] . ' - ' . SITE_DEFAULT_TITLE);
// $oTemplate->setCanonicalUrl( $oRouter->getPath('classicAuctionItemShow') . '?itemId=' . $iItemId );


// Read next and previous
if( $aItem['itemSortNo'] == 1 ) $aSortNo = array( 1, 2 );
else $aSortNo = array( ($aItem['itemSortNo']-1), (int) $aItem['itemSortNo'], ($aItem['itemSortNo']+1) );

$oBackEnd->oDao->sCriterias = null;
$oBackEnd->oDao->setCriterias( array(
  'sortNo' => array(
    'type' => 'in',
    'value' => $aSortNo,
    'fields' => 'itemSortNo'
  ),
  'auctionId' => array(
    'type' => '=',
    'value' => $aItem['itemAuctionId'],
    'fields' => 'itemAuctionId'
  ),
  'partId' => array(
    'type' => '=',
    'value' => $aItem['itemPartId'],
    'fields' => 'itemPartId'
  )
) );
$oBackEnd->oDao->aSorting = array( 'itemSortNo' => 'ASC' );
$aItems = $oBackEnd->readAuctionItem( array(
  'itemId',
  'itemSortNo',
  'itemAuctionId',
  'itemPartId',
  'itemTitle',
  'itemVehicleDataId',
  'itemVehicleArchiveImageId'
) );
$oBackEnd->oDao->aSorting = null;

// Read auction data
$oBackEnd->oDao->sCriterias = null;
$aAuction = current( $oBackEnd->readAuction(array(
  'auctionId',
  'auctionTitle'
), $aItem['itemAuctionId']) );

$aAuction += current( $oBackEnd->readAuctionPart(array(
  'partTitle',
  'partId',
  'partStatus'
), $aItem['itemPartId']) );


// Auction part route
$sPartRoute = '';
if( !empty($aAuction) ) {
  // Set the session variable to this id to open it up on back links
  $_SESSION['browser']['auction'][ $aAuction['partId'] ]['auctionSelectedItem'] = $iItemId;
  $sPartRoute = $oRouter->getPath( 'guestAuctionItemsArchived' ) . '?auctionId=' . $aAuction['auctionId'] . '&partId=' . $aAuction['partId'];
}


$sNext = '';
foreach( $aItems as $aNavItem ) {
  if( $aNavItem['itemId'] == $iItemId ) {
    if( !isset($sPrev) ) $sPrev = '';

  } else {
    if( !isset($sPrev) ) {
      $sPrev = $oRouter->getPath( 'guestAuctionItemShowArchived' ) . '?itemId=' . $aNavItem['itemId'];
    } else {
      $sNext = $oRouter->getPath( 'guestAuctionItemShowArchived' ) . '?itemId=' . $aNavItem['itemId'];
    }
  }
}

// The end time difference can be used in several functions below
$iTimeSinceEnded = time() - strtotime( $aItem['itemEndTime'] );


// Check if vehicle have image (archived)
if( !empty($aItem['itemVehicleDataId']) && ($iTimeSinceEnded > 2592000) ) {
  $aObjectStorageImageData = $oObjectStorage->readWithParams( [
  	'type' => 'image',
  	'parentTable' => 'entVehicleData',
  	'parentId' => $aItem['itemVehicleDataId'],
  	'includeVariants' => true,
    'structureVariants' => true
  	]
  );
}

/**
 * Bid form
 */
$oLayout = clRegistry::get( 'clLayoutHtml' );
$GLOBALS['viewParams']['auction']['bidFormAdd.php']['item'] = $aItem;
$GLOBALS['viewParams']['auction']['bidListAll.php']['item'] = $aItem;
$sBidForm = $oLayout->renderView( 'auction/bidFormAdd.php' );
$sBidHistory = $oLayout->renderView( 'auction/bidListAll.php' );

$GLOBALS['viewParams']['auction']['auctionShowInfo.php']['item'] = $aItem;
$sInfo = $oLayout->renderView( 'auction/auctionShowInfo.php' );

if( $aItem['itemMinBid'] == 0 ) {
  // Item cancelled - no images
  $sImages = '';

} else {
  if( !empty($aObjectStorageImageData) ) {
    $GLOBALS['imageView'] = [
      'images' => $aObjectStorageImageData,
      'altText' => $aItem['itemTitle'],
      'imageCount' => 1
    ];

  } else {
    $GLOBALS['imageView'] = [
      'parentTable' => 'entAuctionItem',
      'parentId' => $iItemId,
      'altText' => $aItem['itemTitle']
    ];
  }
  $sImages = $oLayout->renderView( 'global/imageView.php' );
}

// More info content
if( !empty($aItem['itemDescription']) ) {
	$sMoreInfo = '
		<div class="moreInfo">
			<h3>' . _( 'Mer information om ropet' ) . '</h3>
			' . $aItem['itemDescription'] . '
		</div>';
}

// Media tabs
$aMediaTabs = [
  '<a class="tab selected" data-type="images">
    ' . _( 'Images' ) . '<span class="counter">' . ( !empty($GLOBALS['imageView']['imageCount']) ? $GLOBALS['imageView']['imageCount'] : '' ) . '</span>
  </a>'
];

// Video content
if( !empty($aItem['itemYoutubeLink']) ) {
  $sVideoContent = '
    <div class="videoContainer">
      <iframe src="' . str_replace( 'youtu.be', 'youtube.com/embed', $aItem['itemYoutubeLink'] ) . '?rel=0" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>';

    $aMediaTabs[] =  '
      <a class="tab" data-type="video">
        ' . _( 'Video' ) . '<span class="counter">1</span>
      </a>';
}

// Return link
if( !empty($sPartRoute) ) $sReturnLink = $sPartRoute;
else $sReturnLink = !empty($oRouter->sRefererRoute) ? $oRouter->sRefererRoute : $aAuction['routePath'];


echo '
    <div class="view auction display itemShow itemShowArchived">
      <div class="topbar">
        <h1>
          <a href="' . $sReturnLink . '"><i class="fas fa-backspace"></i></a>
          <small>' . $sCallNo . '</small>
          ' . $sItemTitle . '
        </h1>
        <div class="itemNav">
          <a href="' . $sPrev . '" class="button small ' . ( empty($sPrev) ? 'disabled' : '' ) . '"><i class="fas fa-angle-left"></i></a>
          <span>' . _( 'Call' ) . ' ' . $aItem['itemSortNo'] . '</span>
          <a href="' . $sNext . '" class="button small ' . ( empty($sNext) ? 'disabled' : '' ) . '"><i class="fas fa-angle-right"></i></a>
        </div>
      </div>
      <div class="container">
        <div class="itemInformation">
          <div class="mediaContainer">
            <div class="tabs colored gray">' . implode( '', $aMediaTabs ) . '</div>
            ' . ( !empty($sVideoContent) ? '<div class="tabContainer video">' . $sVideoContent . '</div>' : '' ) . '
            <div class="tabContainer images">' . $sImages . '</div>
          </div>
          <div class="information">
            ' . ( !empty($sMoreInfo) ? $sMoreInfo : '' ) . '
            <div class="auctionInfo">
                ' . $sInfo . '
            </div>
        	</div>
        </div>
        <div class="bidContainer">
          ' . $sBidForm . '
          ' . $sBidHistory . '
        </div>
      </div>
    </div>
    <script>
      $(".mediaContainer .tabs .tab").click( function() {
        let tabType = $( this ).data( "type" );
        let containerClass = ".mediaContainer .tabContainer." + tabType;

        $(".mediaContainer .tabs .tab").removeClass( "selected" );
        $( this ).addClass( "selected" );

        $(".mediaContainer .tabContainer").hide();
        $( containerClass ).show();
      } );
    </script>';
