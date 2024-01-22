<?php

$oAuctionTransfer = clRegistry::get( 'clAuctionTransfer', PATH_MODULE . '/auctionTransfer/models' );

$sOutput = '';

if( !empty($_GET['exportBid']) && !empty($_GET['auctionId']) ) {
	echo '<pre>';
	var_dump( $oAuctionTransfer->exportBidHistory( $_GET['auctionId'] ) );
	die;
}
	
echo '
    <div class="view auctionTransfer bidExport">
        <h1>' . _( 'Bid export' ) . '</h1>
        <p><a href="?exportBid=true">' . _( 'Export bids' ) . '</a></p>
        <p>&nbsp;</p>
        ' . ( !empty($sOutput) ? '<hr />' . $sOutput : '' ) . '
    </div>';