<?php

if( empty($_GET['auctionId']) || empty($_GET['partId']) ) {
    $oRouter->redirect( $oRouter->getPath( 'adminAuctions' ) );
}

$oUserManager = clRegistry::get( 'clUserManager' );
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

$oBidDao = $oAuctionEngine->getDao( 'AuctionBid' );
$aDataDict = $oBidDao->getDataDict();

/**
 * Remove bid
 */
if( !empty($_GET['removeBid']) && !empty($_GET['itemId']) ) {
	if( ctype_digit($_GET['removeBid']) && ctype_digit($_GET['itemId']) ) {
		//echo '<pre>';
		//var_dump( "UPDATE entAuctionBid SET bidRemoved = 'yes' WHERE bidId = '" . $_GET['removeBid'] . "'" );
		//var_dump( "DELETE FROM entAuctionBidHistory WHERE historyBidId = '" . $_GET['removeBid'] . "'" );
		//var_dump( "UPDATE entAuctionItem SET itemBidCount = itemBidCount - 1 WHERE itemId = '" . $_GET['itemId'] . "'" );
		//die();

		$oBidDao->oDb->write( "UPDATE entAuctionBid SET bidRemoved = 'yes' WHERE bidId = '" . $_GET['removeBid'] . "'" );
        $oBidDao->oDb->write( "DELETE FROM entAuctionBidHistory WHERE historyBidId = '" . $_GET['removeBid'] . "'" );
		$oBidDao->oDb->write( "UPDATE entAuctionItem SET itemBidCount = itemBidCount - 1 WHERE itemId = '" . $_GET['itemId'] . "'" );

		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataSaved' => sprintf( _( 'Budet (%s) finns inte längre i systemet' ), $_GET['removeBid'] )
		) );
	}
}

/**
 * Auction data
 */
$aAuction = current( $oAuctionEngine->readAuction( array(
	'fields' => '*',
    'auctionId' => $_GET['auctionId'],
    'partId' => $_GET['partId'],
	'auctionStatus' => '*',
	'partStatus' => '*'
) ) );

/**
 * Item data
 */
$aItems = valueToKey( 'itemId', $oAuctionEngine->readAuctionItem( array(
    'fields' => '*',
    'status' => '*',
    'auctionId' => $_GET['auctionId'],
    'partId' => $_GET['partId']
) ) );
$aItemIds = array_keys( $aItems );

if( !empty($aItems) ) {
	//clFactory::loadClassFile( 'clOutputHtmlPagination' );
	//$oPagination = new clOutputHtmlPagination( $oBidDao, array(
	//	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	//	'entries' => 100
	//) );

    /**
     * Bid data
     */
    $aBidData = $oAuctionEngine->readAuctionBid( array(
        'auctionId' => $_GET['auctionId']
        //'partId' => $_GET['partId']
    ) );

	//$sPagination = $oPagination->render();

    $aBidHistory = $oAuctionEngine->readAuctionBidHistory( array(
        'auctionId' => $_GET['auctionId']
        //'partId' => $_GET['partId']
    ) );

    // Sort
    clFactory::loadClassFile( 'clOutputHtmlSorting' );
    $oSorting = new clOutputHtmlSorting( $oBidDao, array(
        'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('bidId' => 'DESC') )
    ) );
    $oSorting->setSortingDataDict( array(
        'bidId' => array(),
        'bidType' => array(),
        'bidValue' => array(),
        'bidItemId' => array(),
        'bidUserId' => array(),
        'bidCreated' => array()
    ) );

    if( !empty($aBidData) ) {
        // User data
        $aUsernames = arrayToSingle( $oUserManager->read( array('userId','username'), arrayToSingle($aBidData, null, 'bidUserId') ), 'userId', 'username' );

        clFactory::loadClassFile( 'clOutputHtmlTable' );
        $oOutputHtmlTable = new clOutputHtmlTable( $aDataDict );
        $oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
            'bidControls' => array(
                'title' => ''
            )
        ) );

        foreach( $aBidData as $aEntry ) {
            if( !in_array($aEntry['bidItemId'], $aItemIds) ) continue;

            // Rewrite date
            $sDate = strtolower( formatIntlDate('d MMM Y', strtotime($aEntry['bidCreated'])) );
            $sDate .= ' kl ' . date( 'H:i:s', strtotime($aEntry['bidCreated']) );

            $aRow = array(
                'bidId' => $aEntry['bidId'],
                'bidType' => sprintf( '<span class="%s">%s</span>', $aEntry['bidType'], _( ucfirst($aEntry['bidType']) ) ),
                'bidValue' => calculatePrice( $aEntry['bidValue'], array(
                    'format' => array(
                        'money' => true
                    )
                ) ),
                'bidItemId' => '<strong>Rop ' . $aItems[ $aEntry['bidItemId'] ]['itemSortNo'] . '.</strong> ' . wordStr( $aItems[ $aEntry['bidItemId'] ]['itemTitle'], 50, ' [...]' ),
                'bidUserId' => $aUsernames[ $aEntry['bidUserId'] ],
                'bidCreated' => $sDate,
                'bidControls' => '
                    <a class="icon iconText iconDelete linkConfirm" href="' . $oRouter->sPath . '?' . stripGetStr() . '&removeBid=' . $aEntry['bidId'] . '&itemId=' . $aEntry['bidItemId'] . '" title="' . _( 'Do you really want to delete this bid?' ) . '">' . _( 'Delete' ) . '</a>'
            );
            $oOutputHtmlTable->addBodyEntry( $aRow );
        }

        $sOutput = $oOutputHtmlTable->render();

    } else {
        $sOutput = '<strong>' . _( 'There are no bids to show' ) . '</strong>';
    }
} else {
    $sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
    <div class="view auction bidTableEdit">
        <h1>' . _( 'Bid history' ) . '</h1>
        <h2>' . $aAuction['auctionTitle'] . ': <span class="part">' . $aAuction['partTitle'] . '</span></h2>
        <section>
            ' . $sOutput . '
			' . /*$sPagination .*/ '
        </section>
    </div>';

$oTemplate->addStyle( array(
    'key' => 'viewCustomStyle',
    'content' => '
        .view > h2 > .part { font-weight: 400; font-style: italic; }
    '
) );
