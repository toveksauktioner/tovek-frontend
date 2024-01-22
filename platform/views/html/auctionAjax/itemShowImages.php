<?php

if( empty($_GET['itemId']) ) return;

$oLayout = clRegistry::get( 'clLayoutHtml' );
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oObjectStorage = clRegistry::get( 'clObjectStorage', PATH_MODULE . '/objectStorage/models' );

/**
 * Item data
 */
$aItem = current( $oAuctionEngine->readAuctionItem( array(
	'itemId' => $_GET['itemId'],
	'status' => '*',
	'fields' => '*'
) ) );
$bOldItem = false;
if( empty($aItem) ) {
	$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
	$oBackEnd->setSource( 'entAuctionItem', 'itemId' );
	$aItem = current( $oBackEnd->read( '*', $_GET['itemId'] ) );
	$aItem['routePath'] = '#';
	$aItem['auctionType'] = 'net';
	$oBackEnd->oDao->sCriterias = null;

	$bOldItem = true;
}

if( empty($aItem) ) return;

$bCancelled = false;
if( $aItem['itemMinBid'] == 0 && $aItem['itemMarketValue'] == 0 ) {
	$bCancelled = true;
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


if( !empty($aObjectStorageImageData) ) {
  $GLOBALS['imageView'] = [
    'images' => $aObjectStorageImageData,
    'altText' => $aItem['itemTitle'],
    'imageCount' => 1
  ];

} else {
  $GLOBALS['imageView'] = [
    'parentTable' => 'entAuctionItem',
    'parentId' => $aItem['itemId'],
    'altText' => $aItem['itemTitle']
  ];
}
$sImages = $oLayout->renderView( 'global/imageView.php' );


// Media tabs
$aMediaTabs = [
  '<a class="tab selected" data-type="images">
    ' . _( 'Images' ) . '<span class="counter">' . $GLOBALS['imageView']['imageCount'] . '</span>
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

// Description content
if( !empty($aItem['itemDescription']) ) {
  $aMediaTabs[] =  '
    <a class="tab" data-type="description">
      ' . _( 'Beskrivning' ) . '
    </a>';
}

// Vehicle info
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

	$sVehicleInfo = '
		<div class="vehicleInfo">' . $sVehicleInfo . '</div>';
}

echo '
  <div class="view auction ajax itemShowImages">
    <h2>
        <span class="bidNo">' . _( 'Call' ) . ' ' . $aItem['itemSortNo'] . '</span>
        <span class="title">' . $aItem['itemTitle'] . '</span>
    </h2>
    <div class="mediaContainer">
			<div class="tabs colored gray">' . implode( '', $aMediaTabs ) . '</div>
			' . ( !empty($sVideoContent) ? '<div class="tabContainer video">' . $sVideoContent . '</div>' : '' ) . '
			<div class="tabContainer images">' . $sImages . '</div>
			<div class="tabContainer description">' . $aItem['itemDescription'] . $sVehicleInfo . '</div>
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
