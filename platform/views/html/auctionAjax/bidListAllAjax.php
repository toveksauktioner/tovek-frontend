<?php

/*** Check´s if the request is made by ajax ***/
if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
	return;
} elseif( empty($_GET['itemId']) ) {
	echo 'noItem';
	return;
}

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

// Data
//$_GET['entries'] = !empty($_GET['entries']) ? $_GET['entries'] : 3; # Default to three
$aBidHistory = $oAuctionEngine->readItemBidHistory( $_GET['itemId'] );

if( !empty($aBidHistory) ) {
    $aList = array();

	$iFirstKey = key( $aBidHistory );

    foreach( $aBidHistory as $iKey => $aBid ) {
        //$sMicro = sprintf( "%06d",( $aBid['bidPlaced'] - floor($aBid['bidPlaced']) ) * 1000000 );
        //$oDate = new DateTime( date( 'Y-m-d H:i:s.' . $sMicro, $aBid['bidPlaced'] ) );
        //$sTime = $oDate->format("Y-m-d H:i:s.u");

        $aUsername = current( $oUser->oDao->read( array('userId' => $aBid['historyBidUserId'], 'fields' => 'username') ) );

        // Rewrite date
        if( strpos($aBid['historyBidPlaced'], '-') == false ) {
            $iCreated = substr( $aBid['historyBidPlaced'], 0, strrpos( $aBid['historyBidPlaced'], '.') );
            $sDate = date( 'Y-m-d', $iCreated );
            $sTime = date( 'H:i:s', $iCreated );
        } else {
            $sDate = date( 'Y-m-d', strtotime($aBid['historyBidPlaced']) );
            $sTime = date( 'H:i:s', strtotime($aBid['historyBidPlaced']) );
        }

        $aClass = array();
        if( $iKey == $iFirstKey ) $aClass[] = 'first';
        if( !empty($aBid['historyBidType']) ) $aClass[] = $aBid['historyBidType'];

        $aList[] = '
            <li' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . '>
							<div class="label">&nbsp;</div>
							<div class="user">' . $aUsername['username'] . '</div>
              <div class="value">' . $aBid['historyBidValue'] . ' kr</div>
              <div class="time"><datetime><date>' . $sDate . '</date><time>' . $sTime . '</time></datetime></div>
            </li>';
    }

    $sOutput = '<ul>' . implode( '', $aList ) . '</ul>';

} else {
    $sOutput = '<ul><li>' . _( 'No bids' ) . '</li></ul>';

}

echo $sOutput;
