<?php

if( empty($_SERVER['HTTP_X_FORWARDED_FOR']) || $_SERVER['HTTP_X_FORWARDED_FOR'] != "213.88.134.199" ) {
	header( "HTTP/1.1 404 Not Found" );
	exit;
}
die();

/**
 * 15 * * * * /usr/bin/php -f /var/www/platform/cronjob/cjAuctionItemEnd.php >/dev/null
 * ('crontab -e' on server to edit)
 */

try {
    // Bootstrap platform
	require_once( dirname(dirname(__FILE__)) . '/platform/core/bootstrap.php' );
	$_SERVER['REQUEST_URI'] = ''; // Cronjob fix for router

	ini_set( 'error_reporting', E_ALL );
	ini_set( 'display_errors', true );
	ini_set( 'memory_limit', '1G' );
	set_time_limit( 0 );

	/**
     * Cronjob error handling
     */
	function cronjobErrorHandler( $iLevel, $sMsg, $sFilename = '', $iLineNr = '' ) {
		switch ( $iLevel ) {
			case E_USER_ERROR:
				$sError = sprintf( _('Fatal Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
			case E_USER_WARNING:
				$sError = sprintf( _('Warning: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
			case E_USER_NOTICE:
				$sError = sprintf( _('Notice: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
			default:
				$sError = sprintf( _('Unknown Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
		}
		echo $sError;
        
		file_put_contents( dirname(dirname(__FILE__)) . '/logs/endAuctionItemErrors.log', date('Y-m-d H:i:s') . ' ' . $sError . "\n", FILE_APPEND );
		return true;
	}
	set_error_handler( 'cronjobErrorHandler' );
	function setException( $oException ) {
		echo sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
		file_put_contents( dirname(dirname(__FILE__)) . '/logs/endAuctionItemErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
		return true;
	}
	set_exception_handler( 'setException' );

	// Error (not exception) variable
	$aError = array();

	// Dependency files
	require_once( PATH_FUNCTION . '/fData.php' );
    require_once( PATH_MODULE . '/auction/config/cfAuction.php' );

	// Database object
	$oDbFront = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );
    $oDbBackend = clRegistry::get( 'clDbPdoSecondary' );    

	// Logger
	clFactory::loadClassFile( 'clLogger' );
	//clLogger::log( '- Cronjob started @ ' . date( 'Y-m-d H:i:s' ) . ' -', 'endAuctionItem.log' );

	// Start a timer
	$fStartTime = microtime(true);

	// Counters
	$iUpdatedItemCount = 0;

	/**
     * Get ended auction items, that still is active
     */
	$aFields = array(
		'itemId',
		'itemSortNo',
		'itemTitle',
		'itemWinningBidId',
		'itemLocation',
		'itemStatus',
		'itemEndTime',
		'itemCreated',
		'itemAddressId',
		'partId',
		'partStatus',
		'partAuctionId'
	);
	$sItemQuery = "
		SELECT " . implode(',', $aFields) . "
		FROM entAuctionItem
		LEFT JOIN entAuctionPart ON entAuctionItem.itemAuctionId = entAuctionPart.partAuctionId
		WHERE itemEndTime < '" . date( 'Y-m-d H:i:s', strtotime('- 15 seconds') ) . "'
		AND itemEndTime > '" . date('Y-m-d') . " 00:00:00'
		AND itemStatus = 'active'
		AND partStatus = 'running'
		GROUP BY entAuctionItem.itemId
	";
	$aAuctionItems = $oDbFront->query( $sItemQuery );
//echo '<pre>';
//var_dump( $aAuctionItems );
//die;    
	if( !empty($aAuctionItems) ) {
		// Address data for all items
		$aAuctionAddresses = valueToKey( 'addressId', $oDbBackend->query("
			SELECT addressId, addressTitle, addressAddress, addressCollectSpecial, addressCollectStart, addressCollectEnd, addressFreightHelp, addressCollectInfo
			FROM entAuctionAddress
			WHERE addressId IN(" . implode( ', ', array_map('intval', arrayToSingle($aAuctionItems, null, 'itemAddressId')) ) . ")
		") );
		// Address data for auction parts
		$aPartAddresses = valueToKey( 'addressId', $oDbBackend->query("
			SELECT addressId, addressTitle, addressAddress, addressCollectSpecial, addressCollectStart, addressCollectEnd, addressFreightHelp, addressCollectInfo
			FROM entAuctionAddress
			WHERE addressPartId IN(" . implode( ', ', array_map('intval', arrayToSingle($aAuctionItems, null, 'partId')) ) . ")
		") );
        
        /**
         * Handling each item, which ended in time but still has status 'active'
         */
		foreach( $aAuctionItems as $aItem ) {
			/**
             * Get winning bid
             */
			$aAuctionItemBid = $oDbFront->query( '
				SELECT *
				FROM entAuctionBidHistory
				WHERE historyBidItemId = "' . $aItem['itemId'] . '"
				ORDER BY historyBidValue DESC
				LIMIT 1
			' );
            
			if( !empty($aAuctionItemBid) ) {
                // Winning bid
				$aAuctionItemBid = current( $aAuctionItemBid );
				$aUser = current( $oDbBackend->query( '
					SELECT userId,userEmail
					FROM entUser
					WHERE userId = "' . $aAuctionItemBid['historyBidUserId'] . '"
				' ) );
                
			} else {
				// No winning bid
                $aAuctionItemBid = 0;
				$aUser = array();
                
			}
            
            if( !empty($aAuctionItemBid) ) {
                /**
                 * Update item with winning data
                 */
				$sQuery = '
                    UPDATE entAuctionItem
                    SET itemStatus = "ended",
                        itemWinningBidId = "' . $aAuctionItemBid['historyBidId'] . '",
                        itemWinningBidValue = "' . $aAuctionItemBid['historyBidValue'] . '",
                        itemWinningUserId = "' . $aAuctionItemBid['historyBidUserId'] . '"
                    WHERE itemId = "' . $aItem['itemId'] . '"
                ';
                $mResult = $oDbFront->write( $sQuery );
            }
            
            /**
             * Continue with sending winning mail
             */
			if( !empty($mResult) && !empty($aUser['userEmail']) ) {             
                // Mail content
                $sContent = $GLOBALS['auction']['winnersMessage']['bodyHtml'];
                
                /**
                 * Make a presentation of the addresses
                 */
                if( !empty($aItem['itemAddressId']) && isset($aAuctionAddresses[ $aItem['itemAddressId'] ]) ) {
                    $aAddress = $aAuctionAddresses[ $aItem['itemAddressId'] ];
                } elseif( count($aPartAddresses) == 1 ) {
                    $aAddress = current( $aPartAddresses );
                } else {
                    $aAddress = null;
                }
                
                /**
                 * Item address
                 */
                $sItemAddress = '';
                if( !empty($aAddress) ) {
                    $sCollectInfo = '
                        <strong>' . $aAddress['addressTitle'] . ':</strong> ' . $aAddress['addressAddress'];
                        
                    if( !empty($aAddress['addressCollectStart']) && ($aAddress['addressCollectStart'] != '0000-00-00 00:00:00') ) {
                        $iCollectStartTime = strtotime( $aAddress['addressCollectStart'] );
                        $iCollectEndTime = strtotime( $aAddress['addressCollectEnd'] );
                        $sCollectInfo .= '
                            , <strong>' . strftime( '%A', $iCollectStartTime ) . 'en den ' . strftime( '%e %b', $iCollectStartTime ) . ' mellan kl. ' . strftime( '%H:%M', $iCollectStartTime ) . '-' . strftime( '%H:%M', $iCollectEndTime ) . '</strong>.';
                            
                        if( !empty($aAddress['addressCollectSpecial']) ) {
                            $sCollectInfo .= '
                                <br><strong>' . _( 'För mer info' ) . ':</strong> ' . $aAddress['addressCollectSpecial'] . '</strong>.';
                        }
                    } else {
                        if( !empty($aAddress['addressCollectSpecial']) ) {
                            $sCollectInfo .= '
                                <br><strong>' . _( 'Tid enligt överenskommelse på telefon' ) . ':</strong> ' . $aAddress['addressCollectSpecial'] . '</strong>.';
                        }
                    }
                    
                    if( !empty($aAddress['addressCollectInfo']) ) {
                        $sCollectInfo .= '
                            <br>' . $aAddress['addressCollectInfo'];
                    }
                    
                    if( $aAddress['addressFreightHelp'] == 'yes' ) {
                        $sCollectInfo .= '
                            Trucklasthjälp samt frakthjälp finns mot betalning. Frakthjälp skall beställas senast 2 arbetsdagar innan avhämtningen.';
                    }
                    
                    $sItemAddress = '
                        <br /><hr /><br />
                        <h3 style="margin: 0; padding: 0; font-size: 20px;">Tid och plats för avhämtning av detta objekt</h3>
                        <p>' . $sCollectInfo . '</p>';
                }
                
                /**
                 * Add information
                 */
                $sContent = str_replace( '{auctionItemSortNo}', $aItem['itemSortNo'], $sContent );
                $sContent = str_replace( '{auctionItemTitle}', $aItem['itemTitle'], $sContent );
                $sContent = str_replace( '{mapForPrint}', '<a href="#">Karta och auktionskatalog för utskrift</a>', $sContent );
                $sContent = str_replace( '{sPaymentDate}', substr($aItem['itemCreated'], 0, 10), $sContent );
                $sContent = str_replace( '{collectList}', $aItem['itemLocation'], $sContent );
                $sContent = str_replace( '{itemAddress}', $sItemAddress, $sContent );
                //$sContent = str_replace( '{noCollectText}', $aItem['itemLocation'], $sContent );
                
                /**
                 * Send mail
                 */
                $oMailHandler = clFactory::create( 'clMailHandler' );
                $aMailParams = array(
                    'from' => 'Toveks auktioner <' . SITE_MAIL_FROM . '>',
                	'to' => $aUser['userEmail'],
					'bcc' => array( 'mikael@argonova.se', 'bjorn@tovek.se' ),
                	'title' => _( 'Grattis till er vunna auktion' ),
                	'content' => $sContent
                );
                $oMailHandler->prepare( $aMailParams );
                if( $oMailHandler->send() ) {
                    $mResult = $oDbFront->write( '
                        UPDATE entAuctionItem
                        SET itemWinnerMailed = "yes"
                        WHERE itemId = "' . $aItem['itemId'] . '"
                    ' );
                }
                    
				++$iUpdatedItemCount;
				
				echo '<pre>1: ';
				var_dump( $aItem['itemId'] );				
				var_dump( $aUser['userId'] );
				var_dump( $aUser['userEmail'] );
				var_dump( $aAuctionItemBid );
				die();
			}			
		}
        
	} else {
		//clLogger::log( 'No items found', 'endAuctionItem.log' );
        
	}

	// Log
	//clLogger::log( 'Updated <' . $iUpdatedItemCount . '> auction items.', 'endAuctionItem.log' );
	//clLogger::log( 'Cronjob finished @ ' . number_format( microtime(true) - $fStartTime, 4 ) . 's.', 'endAuctionItem.log' );
	//clLogger::logRotate( 'endAuctionItem.log', '8M' );
    
} catch( Throwable $oThrowable ) {
    // Exception logging
	file_put_contents( dirname(dirname(__FILE__)) . '/logs/endAuctionItemErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
    
} catch( Exception $oException ) {
	// Exception logging
	file_put_contents( dirname(dirname(__FILE__)) . '/logs/endAuctionItemErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );

}