<?php

// Read all auctions from front DB	
//$oAuction = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );
//$aExistingAuctionIds = arrayToSingle( $oAuction->readAll( 'auctionId' ), null, 'auctionId' );
//echo '<pre>';
//var_dump( implode( ',', $aExistingAuctionIds ) );
//die();

$sOutput = '';

/**
 * Import Bids
 */
if( !empty($_GET['importBid']) ) {
    try {
        
        $oAuctionBid = clRegistry::get( 'clAuctionBid', PATH_MODULE . '/auction/models' );
        $oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
        $oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
        $oBackEnd->setSource( 'entAuctionBid', 'bidId' );
        
        // Read all item IDs
        $aItemIds = arrayToSingle( $oAuctionItem->read( 'itemId' ), null, 'itemId' );
        
        /**
         * Read bid data
         */
        $oBackEnd->oDao->setCriterias( array(
            'bidItemId' => array(		
                'fields' => 'bidItemId',
                'type' => 'in',
                'value' => $aItemIds
            )
        ) );	
        $aBidByItem = groupByValue( 'bidItemId', $oBackEnd->read() );
        
        $iTotalItem = 0;
        $iTotalBid = 0;
        $iTotalCreated = 0;
        
        if( !empty($aBidByItem) ) {
            foreach( $aBidByItem as $iItemId => $aBids ) {
                $iTotalItem++;
                
                $iItemBidCount = 0;
                foreach( $aBids as $aBid ) {
                    $iTotalBid++;
                    $aBidData = array(
                        'bidType' => ( $aBid['bidType'] == 'manually' ? 'normal' : 'auto' ),
                        'bidValue' => $aBid['bidValue'],
                        'bidTransactionId' => $aBid['bidTransactionId'],
                        'bidPlaced' => strtotime($aBid['bidCreated']) . ',0000',
                        'bidCreated' => $aBid['bidCreated'],
                        'bidRemoved' => $aBid['bidRemoved'],
                        'bidAuctionId' => $aBid['bidAuctionId'],
                        'bidPartId' => !empty($aBid['bidPartId']) ? $aBid['bidPartId'] : '',
                        'bidItemId' => $aBid['bidItemId'],
                        'bidUserId' => $aBid['bidUserId']
                    );
                    //die( 'stop 2' );
                    
                    // Create bid
                    $oAuctionBid->oDao->createData( $aBidData, array(
                        'groupKey' => 'createAuctionBid'
                    ) );
                    $aErr = clErrorHandler::getValidationError( 'createAuctionBid' );
                    
                    if( empty($aErr) ) {
                        $iItemBidCount++;
                        $iTotalCreated++;
                    } else {
                        echo '<pre>Bid error: ';
                        var_dump( $aErr );
                        die;
                    }
                }
                
                // Update item bid count
                $oAuctionItem->update( $iItemId, array( 'itemBidCount' => $iItemBidCount ) );
                $aErr = clErrorHandler::getValidationError( 'updateAuctionItem' );
                
                if( empty($aErr) ) {
                    // success
                    
                    // Update history
                    $oAuctionBid->updateHistory( $iItemId );
                    
                } else {
                    echo '<pre>Item error: ';
                    var_dump( $aErr );
                    die;
                }
            }
        }
        
        $sOutput = '
            <div class="container">
                <p><strong>Total items:</strong> <span>' . $iTotalItem . '</span></p>
                <p><strong>Total bids:</strong> <span>' . $iTotalBid . '</span></p>
                <p><strong>Total created:</strong> <span>' . $iTotalCreated . '</span></p>
            </div>';
    
    } catch( Throwable $oThrowable ) {
        echo '<pre>';
        var_dump( $oThrowable );
        die;
        
    } catch( Exception $oException ) {
        echo '<pre>';
        var_dump( $oException );
        die;
        
    }
}

/**
 * Import auto bids
 */
if( !empty($_GET['importAutoBid']) ) {
    try {
        
        $oAuctionAutoBid = clRegistry::get( 'clAuctionAutoBid', PATH_MODULE . '/auction/models' );
        $oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
        $oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
        $oBackEnd->setSource( 'entAuctionAutoBid', 'autoId' );
        
        // Read all item IDs
        $aItemIds = arrayToSingle( $oAuctionItem->read( 'itemId' ), null, 'itemId' );
        
        /**
         * Read bid data
         */
        $oBackEnd->oDao->setCriterias( array(
            'bidItemId' => array(		
                'fields' => 'autoItemId',
                'type' => 'in',
                'value' => $aItemIds
            )
        ) );	
        $aAutoBidByItem = groupByValue( 'autoItemId', $oBackEnd->read() );
        
        $iTotalItem = 0;
        $iTotalBid = 0;
        $iTotalCreated = 0;
        
        if( !empty($aAutoBidByItem) ) {
            foreach( $aAutoBidByItem as $iItemId => $aAutoBids ) {
                $iTotalItem++;
                
                $iItemBidCount = 0;
                foreach( $aAutoBids as $aAutoBid ) {
                    $iTotalBid++;
                    $aAutoBidData = array(
                        'autoMaxBid' => $aAutoBid['autoMaxBid'],
                        'autoPlaced' => strtotime($aAutoBid['autoCreated']) . ',0000',
                        'autoCreated' => $aAutoBid['autoCreated'],
                        'autoRemoved' => 'no',
                        'autoAuctionId' => $aAutoBid['autoAuctionId'],
                        'autoPartId' => 0,
                        'autoItemId' => $aAutoBid['autoItemId'],
                        'autoUserId' => $aAutoBid['autoUserId']
                    );                   
                    //die( 'stop 2' );
                    
                    // Create auto bid
                    $oAuctionAutoBid->oDao->createData( $aAutoBidData, array(
                        'groupKey' => 'createAuctionAutoBid'
                    ) );
                    $aErr = clErrorHandler::getValidationError( 'createAuctionAutoBid' );
                    
                    if( empty($aErr) ) {
                        $iItemBidCount++;
                        $iTotalCreated++;
                    } else {
                        echo '<pre>Bid error: ';
                        var_dump( $aErr );
                        die;
                    }
                }                
            }
        }
        
        $sOutput = '
            <div class="container">
                <p><strong>Total items:</strong> <span>' . $iTotalItem . '</span></p>
                <p><strong>Total bids:</strong> <span>' . $iTotalBid . '</span></p>
                <p><strong>Total created:</strong> <span>' . $iTotalCreated . '</span></p>
            </div>';
    
    } catch( Throwable $oThrowable ) {
        echo '<pre>';
        var_dump( $oThrowable );
        die;
        
    } catch( Exception $oException ) {
        echo '<pre>';
        var_dump( $oException );
        die;
        
    }
}

echo '
    <div class="view auctionTransfer bidImport">
        <h1>' . _( 'Bid import 007' ) . '</h1>
        <p><a href="?importBid=true">' . _( 'Import bids' ) . '</a></p>
        <p>&nbsp;</p>
        <p><a href="?importAutoBid=true">' . _( 'Import auto bids' ) . '</a></p>
        <p>&nbsp;</p>
        ' . ( !empty($sOutput) ? '<hr />' . $sOutput : '' ) . '
    </div>';