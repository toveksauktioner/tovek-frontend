<?php

if( empty($_GET['itemId']) ) return;

require_once PATH_FUNCTION . '/fMoney.php';

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

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

$sCurrentBid = '';
$sCurrentBidTime = '';

/**
 * Bid form
 */
$sBidForm = '';
$sFinancing = '';
$oLayout = clRegistry::get( 'clLayoutHtml' );
$GLOBALS['viewParams']['auction']['bidFormAdd.php']['item'] = $aItem;
$GLOBALS['viewParams']['auction']['bidList.php']['item'] = $aItem;
if( $bOldItem == false ) {
	$sBidForm = $oLayout->renderView( 'auction/bidFormAdd.php' );
	$sFinancing = $oLayout->renderView( 'financing/wasakreditUserItemNotice.php' );
}
$sBidHistory = $oLayout->renderView( 'auction/bidList.php' );
$sRelatedList = $oLayout->renderView( 'auction/relatedItemList.php' );


if( !empty($_GET['auctionId']) ) {
	$GLOBALS['viewParams']['auction']['auctionShowInfo.php']['auctionId'] = $_GET['auctionId'];
}
if( !empty($_GET['itemId']) ) {
	$GLOBALS['viewParams']['auction']['auctionShowInfo.php']['itemId'] = $_GET['itemId'];
}


echo '
    <div class="view auction ajax itemShow">
			<div class="bidContainer">
				' . $sFinancing . '
				' . $sBidHistory . '
				' . $sBidForm . '
			</div>
    </div>';
