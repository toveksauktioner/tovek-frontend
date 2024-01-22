<?php

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );
  $oAuctionItemDao->aSorting = array( 'itemSortNo' => 'ASC' );
$oObjectStorage = clRegistry::get( 'clObjectStorage', PATH_MODULE . '/objectStorage/models' );

// Determe item ID
$iItemId = !empty($_GET['itemId']) ? $_GET['itemId'] : ( ($aData = $oRouter->readObjectByRoute()) ? $aData[0]['objectId'] : null );
if( empty($iItemId) ) return;

// Item data
$aItem = current( $oAuctionEngine->readAuctionItem( array(
    'itemId' => $iItemId,
    'status' => array(
			'active',
			'ended',
			'cancelled'
		),
    'fields' => '*'
) ) );
if( empty($aItem) ) return;

// SEO data
$oTemplate->setTitle( $aItem['itemTitle'] . ' - ' . SITE_DEFAULT_TITLE);
// $oTemplate->setCanonicalUrl( $oRouter->getPath('classicAuctionItemShow') . '?itemId=' . $iItemId );

// Increase viewed count
$oAuctionEngine->increaseViewedCount( 'AuctionItem', $aItem['itemId'] );

if( $aItem['itemSortNo'] == 1 ) $aSortNo = array( 1, 2 );
else $aSortNo = array( ($aItem['itemSortNo']-1), (int) $aItem['itemSortNo'], ($aItem['itemSortNo']+1) );

$aItems = $oAuctionEngine->readAuctionItem( array(
  'sortNo' => $aSortNo,
	'auctionId' => $aItem['itemAuctionId'],
	'partId' => $aItem['itemPartId'],
  'status' => '*',
  'fields' => array(
		'itemId',
		'itemSortNo',
		'routePath'
	),
	'entries' => 3
) );

$aAuction = current( $oAuctionEngine->readAuction( array(
	'auctionId' => $aItem['itemAuctionId'],
  'auctionStatus' => '*',
	'partStatus' => '*',
  'fields' => array(
		'auctionId',
    'auctionTitle',
    'partTitle',
		'partId',
		'partStatus',
		'routePath'
	)
) ) );

// Auction part route
$sPartRoute = '';
if( !empty($aAuction) ) {
  // Set the session variable to this id to open it up on back links
  // $_SESSION['browser']['auction'][ $aAuction['partId'] ]['auctionSelectedItem'] = $iItemId;
  $sCookieSelectedKey = 'auctionSelectedItem-' . $aAuction['partId'];
  if( empty($_COOKIE[$sCookieSelectedKey]) || ($_COOKIE[$sCookieSelectedKey] != $iItemId) ) {
    setcookie( $sCookieSelectedKey, $iItemId, 0, '/' );
  }

	$aPartRouteData = $oRouter->readByObject( $aItem['itemPartId'], 'AuctionPart', 'routePath' );
	if( !empty($aPartRouteData) ) $sPartRoute = current(current( $aPartRouteData ));
}

$sNext = '';
foreach( $aItems as $aNavItem ) {
  if( $aNavItem['itemId'] == $iItemId ) {
    if( !isset($sPrev) ) $sPrev = '';

  } else {
    if( !isset($sPrev) ) {
      $sPrev = $aNavItem['routePath'];
    } else {
      $sNext = $aNavItem['routePath'];
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
$sFinancing = $oLayout->renderView( 'financing/wasakreditUserItemNotice.php' );
$sBidHistory = $oLayout->renderView( 'auction/bidListAll.php' );

$GLOBALS['viewParams']['auction']['auctionShowInfo.php']['item'] = $aItem;
$sInfo = $oLayout->renderView( 'auction/auctionShowInfo.php' );

/**
 * Google maps address
 */
$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
$oBackEnd->setSource( 'entAuctionAddress', 'addressId' );
$oBackEnd->oDao->setCriterias( array(
    'partAuctionId' => array(
        'fields' => 'addressPartId',
        'value' => $aItem['itemPartId']
    )
) );
$aAddress = current( $oBackEnd->read() );
$sMapUrl = '';
if( !empty($aAddress) ) {
	/**
	 * Google maps link
	 */
	$sMapUrl = 'https://www.google.se/maps/place/';
	if( !empty(trim($aAddress['addressAddress'])) ) {
		$sMapUrl .= preg_replace( '/\s+/', '+', $aAddress['addressAddress'] );
	} elseif( !empty(trim($aAddress['addressTitle'])) ) {
		$sMapUrl .= preg_replace( '/\s+/', '+', $aAddress['addressTitle'] );
	}
}

/**
 * Vehicle info
 */
$sVehicleInfo = '';
if( !empty($aItem['itemVehicleDataId']) ) {
	$oVehicleData = clRegistry::get( 'clVehicleData', PATH_MODULE . '/vehicle/models' );
	$aVehicleDataDict = $oVehicleData->oDao->getDataDict();

	$aVehicleData = current( $oVehicleData->read(array(
		'vehicleLicencePlate',
		'vehicleNoKeys',
		'vehicleBrand',
		'vehicleModel',
		'vehicleModelYear',
		'vehicleMileageTime',
		'vehicleMileageDistance',
		'vehicleTransmission',
		'vehicleFuel',
		'vehicleColor',
		'vehicleInspectionDate',
		'vehicleLength',
		'vehicleWidth',
		'vehicleOutput',
		'vehicleCubicCapacity',
		'vehicleOwnerCount',
		'vehicleInspectionApprovedDate',
		'vehicleProhibitedForTraffic',
		'vehicleAnnualTax'
	), $aItem['itemVehicleDataId']) );

	if( !empty($aVehicleData) ) {
		foreach( $aVehicleData as $sKey => $sValue ) {
			if( !empty($sValue) ) {
				$sVehicleInfo .= '
					<div class="title">' . $aVehicleDataDict['entVehicleData'][ $sKey ]['title'] . '</div>
					<div class="value">' . ( !empty($aVehicleDataDict['entVehicleData'][ $sKey ]['values'][ $sValue ]) ? $aVehicleDataDict['entVehicleData'][ $sKey ]['values'][ $sValue ] : $sValue ) . '</div>';
			}
		}
	}
}

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
    ' . _( 'Images' ) . ( !empty($GLOBALS['imageView']['imageCount']) ? '<span class="counter">' . $GLOBALS['imageView']['imageCount'] . '</span>' : '' ) . '
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


$sRegNo = '';
echo '
    <div class="view auction display itemShow" data-item-id="' . $iItemId . '">
      <div class="topbar">
        <h1>
          <a href="' . $sReturnLink . '"><i class="fas fa-backspace"></i></a>
          <small>' . _( 'Call' ) . ' ' . $aItem['itemSortNo'] . '</small>
          ' . $aItem['itemTitle'] . $sRegNo . '
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
  					' . (!empty($sVehicleInfo) ? '<div class="vehicleInfo">' . $sVehicleInfo . '</div>' : '') . '
            <div class="auctionInfo">
                ' . $sInfo . '
            </div>
          </div>
        </div>
        <div class="bidContainer">
          ' . $sBidForm . '
					' . $sFinancing . '
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
