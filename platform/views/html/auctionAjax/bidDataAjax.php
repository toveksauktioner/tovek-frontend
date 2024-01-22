<?php

/*** Check´s if the request is made by ajax ***/
if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
	return; # None ajax call!
}

//if( empty($_GET['itemId']) ) {
//	return; # None item IDs!
//}

require_once PATH_FUNCTION . '/fMoney.php';

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

$aItemIds = explode( ',', $_GET['itemId'] );

// Item data
$aItems = $oAuctionEngine->readAuctionItem( array(
	'itemId' => $aItemIds,
	'status' => '*',
	'fields' => array(
		'itemId',
		'itemSortNo',
		'itemTitle',
		'itemMinBid',
		'itemBidCount',
		'routePath'
	)
) );

//if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == "213.88.134.199" ) {
	/**
	 * Old item?
	 */
	$bOldItem = false;
	if( empty($aItems) ) {
		$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
		$oBackEnd->setSource( 'entAuctionItem', 'itemId' );
		$aItems = $oBackEnd->read( '*', $aItemIds );
		$oBackEnd->oDao->sCriterias = null;

		$bOldItem = true;
	}
//}

// Bid data
$aBidByItem = groupByValue( 'historyBidItemId', $oAuctionEngine->readItemBidHistory( $aItemIds ) );

//if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == "213.88.134.199" ) {
	/**
	 * Old bid data?
	 */
	if( empty($aBidByItem) ) {
		$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
		$oBackEnd->setSource( 'entAuctionBid', 'bidId' );
		$oBackEnd->oDao->aSorting = array( 'bidValue' => 'DESC' );
		$oBackEnd->oDao->setCriterias( array(
			'bidItemId' => array(
				'fields' => 'bidItemId',
				'type' => 'in',
				'value' => $aItemIds
			),
			'bidStatus' => array(
				'fields' => 'bidStatus',
				'value' => 'successful'
			)
		) );
		$aBidData = $oBackEnd->read( '*' );
		$oBackEnd->oDao->sCriterias = null;
		$oBackEnd->oDao->aSorting = array();

		$aBidByItem = array();
		foreach( $aBidData as $aBid ) {
			if( !isset($aBidByItem[ $aBid['bidItemId'] ]) ) {
				$aBidByItem[ $aBid['bidItemId'] ] = array();
			}
			$aBidByItem[ $aBid['bidItemId'] ][] = array(
				'historyBidValue' => $aBid['bidValue'],
				'historyBidUserId' => $aBid['bidUserId'],
				'historyBidPlaced' => $aBid['bidCreated'],
				'historyBidType' => $aBid['bidType']
			);
		}
	}
//}

if( !empty($aItems) ) {
	foreach( $aItems as $iKey => $aItem ) {
		// Check item bid history
		//$oAuctionEngine->updateItemBidHistory( $aItem['itemId'] );

		if( !empty($aBidByItem[ $aItem['itemId'] ]) ) {
			foreach( $aBidByItem[ $aItem['itemId'] ] as $iKey2 => $aBid ) {
				$aBidder = current( $oUser->oDao->read( array(
					'userId' => $aBid['historyBidUserId'],
					'fields' => array( 'username', 'userId', 'infoName' )
				) ) );
				if( empty($aBidder['username']) ) $aBidder['username'] = 'Från skarpa!';

				if( strpos($aBid['historyBidPlaced'], '-') == false ) {
					$iCreated = substr( $aBid['historyBidPlaced'], 0, strrpos( $aBid['historyBidPlaced'], '.') );
				} else {
					$iCreated = strtotime( $aBid['historyBidPlaced'] );
				}
				$sDate = strtolower( formatIntlDate('d MMM Y', $iCreated) );
				$sDate .= ' ' . date( 'H:i', $iCreated );

				$aItems[ $iKey ]['bidBidder'] = '';
				if( !empty($_SESSION['userId']) ) {
					// $aItems[ $iKey ]['bidBidder'] = sprintf( '(%s, %s)', $aBidder['username'], $sDate );
					// $aItems[ $iKey ]['bidBidder'] = sprintf( '(%s)', $aBidder['username'] );
					$aItems[ $iKey ]['bidBidder'] = sprintf( '<span class="bidder">%s</span><span class="bidDate">%s</span>', $aBidder['username'], $sDate );

				}

				$aItems[ $iKey ]['bidValue'] = calculatePrice( $aBid['historyBidValue'], array('profile' => 'human') );
				$aItems[ $iKey ]['itemMinBid'] = calculatePrice( $aItem['itemMinBid'], array('profile' => 'human') );
				$aItems[ $iKey ]['bidPlaced'] = $aBid['historyBidPlaced'];

				// Do we have an over bidder?
				$aItems[ $iKey ]['bidOverBidder'] = 0;
				if( isset($_GET['newBid']) && !empty($aBidByItem[ $aItem['itemId'] ][ $iKey2 + 1 ]) ) {
					if( !empty($_SESSION['userId']) && $_SESSION['userId'] == $aBidByItem[ $aItem['itemId'] ][ $iKey2 + 1 ]['historyBidUserId'] ) {
						$aItems[ $iKey ]['bidOverBidder'] = $aBidByItem[ $aItem['itemId'] ][ $iKey2 + 1 ]['historyBidUserId'];
						$aItems[ $iKey ]['bidOverBidderMsg'] = '
							<a href="' . $aItem['routePath'] . '">
								' . sprintf( _( 'Someone placed an higher bid on item %s %s.' ), $aItem['itemSortNo'], $aItem['itemTitle'] ) . '
								' . _( 'Go to item!' ) . '
							</a>';
					}
				}

				break;
			}
		} else {
			$aItems[ $iKey ]['bidBidder'] = '';
			$aItems[ $iKey ]['bidOverBidder'] = 0;
			$aItems[ $iKey ]['bidValue'] = calculatePrice( $aItem['itemMinBid'], array('profile' => 'human') );
			$aItems[ $iKey ]['itemMinBid'] = calculatePrice( $aItem['itemMinBid'], array('profile' => 'human') );
			$aItems[ $iKey ]['bidPlaced'] = null;
		}
	}
	echo count($aItems) == 1 ? json_encode( current( $aItems ) ) : json_encode( $aItems );

} else {
	echo '';

}
