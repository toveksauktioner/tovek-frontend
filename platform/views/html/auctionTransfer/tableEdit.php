<?php
return;
$aErr = array();

$oAuctionTransfer = clRegistry::get( 'clAuctionTransfer', PATH_MODULE . '/auctionTransfer/models' );
$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
$oAuction = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );
$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );

$iMaxSequence = 80; // 5 auctions per page load
$iAuctionCreated = 0;

///**
// * Clear data
// */
//if( !empty($_GET['cleanData']) ) {
//	$oAuctionTransfer = clRegistry::get( 'clAuctionTransfer', PATH_MODULE . '/auctionTransfer/models' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionTransfer' );
//	
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuction' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionPart' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionAddress' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionToUser' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionSearch' );
//	
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionItem' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionItemToItem' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionItemToUser' );
//	
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionTag' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionTagToItem' );
//	
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionBid' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionBidHistory' );
//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionAutoBid' );
//	
//	$oNotification->setSessionNotifications( array(
//		'dataSaved' => _( 'All bud data har rensats bort' )
//	) );
//	$oRouter->redirect( $oRouter->sPath );
//}

/**
 * Post
 */
if( !empty($_GET['fetchAuction']) ) {
	sleep( 1 );
	
	// Read all auctions from front DB	
	$aExistingAuctionIds = arrayToSingle( $oAuction->readAll( 'auctionId' ), null, 'auctionId' );
	
	// Read all active auctions from back DB
	$aActiveAuctions = $oBackEnd->readActiveAuction( '*' );
	
	// Read all auctions from back DB		
	$aActiveAuctionParts = $oBackEnd->readActiveAuctionPart( '*', null, arrayToSingle($aActiveAuctions, null, 'auctionId') );

	if( !empty($aActiveAuctionParts) ) {
		// Read all auctions from back DB		
		$aActiveAuctions = valueToKey( 'auctionId', $oBackEnd->readActiveAuction( '*', arrayToSingle($aActiveAuctionParts, null, 'partAuctionId') ) );
		
		/**
		 * Test stuff
		 */
		//$aActiveAuctions = array_slice( $aActiveAuctions, 0, 1 );			
		
		if( !empty($aActiveAuctions) ) {
			foreach( $aActiveAuctions as $aAuction ) {				
				if( in_array($aAuction['auctionId'], $aExistingAuctionIds) ) {
					continue; // Already exists
				}
				
				// Check max break point
				if( $iAuctionCreated >= $iMaxSequence ) break;
				
				/**
				 * Auction items
				 */
				$oBackEnd->setSource( 'entAuctionItem', 'itemId' );
				$oBackEnd->oDao->setCriterias( array(
					'itemAuctionId' => array(		
						'fields' => 'itemAuctionId',
						'value' => $aAuction['auctionId']
					)
				) );			
				$aAuctionItems = $oBackEnd->read();							
				$oBackEnd->oDao->sCriterias = null;
				//if( empty($aAuctionItems) ) {					
				//	continue; // No items yet, skip!
				//}
				
				/**
				 * Auction item to user
				 */
				$oBackEnd->setSource( 'entAuctionItemToUser', 'itemId' );
				$oBackEnd->oDao->setCriterias( array(
					'itemId' => array(		
						'fields' => 'itemId',
						'type' => 'in',
						'value' => arrayToSingle( $aAuctionItems, null, 'itemId' )
					)
				) );			
				$aItemToUser = $oBackEnd->read();							
				$oBackEnd->oDao->sCriterias = null;
				
				/**
				 * Create transfer entry
				 */
				$iTransferId = $oAuctionTransfer->create( array(
					'transferStatus' => 'running',
					'transferType' => 'import',
					'transferAuctionId' => $aAuction['auctionId'],
					'transferCreated' => date( 'Y-m-d H:i:s' )
				) );
				$aErr = clErrorHandler::getValidationError( 'createAuctionTransfer' );
				
				/**
				 * Create auction
				 */
				$oAuction->create( $aAuction );
				$aErr = clErrorHandler::getValidationError( 'createAuction' );
				if( empty($aErr) ) {
					$aDataDict = $oAuction->oDao->getDataDict();
					unset( $aDataDict['entAuction'] );
					
					/**
					 * Additional entities
					 */
					foreach( $aDataDict as $sEntity => $aFields ) {
						switch( $sEntity ) {
							/**
							 * Auction part
							 */
							case 'entAuctionPart':														
								$oBackEnd->setSource( 'entAuctionPart', 'partId' );
								
								$oBackEnd->oDao->setCriterias( array(
									'partAuctionId' => array(		
										'fields' => 'partAuctionId',
										'value' => $aAuction['auctionId']
									)
								) );
								
								$aAuctionParts = $oBackEnd->read();								
								$oBackEnd->oDao->sCriterias = null;								
								if( empty($aAuctionParts) ) {
									$oAuction->delete( $aAuction['auctionId'] );
									continue 2; // Skip auction without part(s)
								}
								
								foreach( $aAuctionParts as $aPart ) {
									foreach( $aPart as $key => $value ) {
										if( $value == '0000-00-00 00:00:00' ) $aPart[ $key ] = '';
									}
									
									// Create auction part
									$oAuction->createPart( $aPart );
									$aErr = clErrorHandler::getValidationError( 'createAuctionPart' );
								}
								
								break;
							
							/**
							 * Auction address
							 */
							case 'entAuctionAddress':
								$oBackEnd->setSource( 'entAuctionAddress', 'addressId' );
								
								$oBackEnd->oDao->setCriterias( array(
									'addressPartId' => array(
										'type' => 'in',
										'fields' => 'addressPartId',
										'values' => arrayToSingle( $aAuctionParts, null, 'partId' )
									)
								) );
								
								$aAddresses = $oBackEnd->read();						
								$oBackEnd->oDao->sCriterias = null;
								
								foreach( $aAddresses as $aAddress ) {
									// Create auction part
									$oAuction->createAddress( $aAddress );
									$aErr = clErrorHandler::getValidationError( 'entAuctionAddress' );
								}
								
								break;
							
							/**
							 * Auction to user
							 */
							case 'entAuctionToUser':
								$oBackEnd->setSource( 'entAuctionToUser', 'auctionId' );
								
								$oBackEnd->oDao->setCriterias( array(
									'auctionId' => array(
										'fields' => 'auctionId',
										'value' => $aAuction['auctionId']
									)
								) );
								
								$aRelations = $oBackEnd->read();						
								$oBackEnd->oDao->sCriterias = null;
								
								foreach( $aRelations as $aRelation ) {
									// Create auction part
									$oAuction->createUserRelation( $aRelation );
									$aErr = clErrorHandler::getValidationError( 'entAuctionToUser' );
								}
								
								break;
							
							default:
								// Error??
								break;
						}
					}
					
					/**
					 * Auction items
					 */
					if( !empty($aAuctionItems) ) {
						foreach( $aAuctionItems as $aAuctionItem ) {
							// Create auction part
							$oAuctionItem->create( $aAuctionItem );
							$aErr = clErrorHandler::getValidationError( 'entAuctionItem' );
							//$aImageParentIds[] = $aAuctionItem['itemId'];
						}
					}
					
					/**
					 * Auction item to user
					 */
					if( !empty($aItemToUser) ) {
						foreach( $aItemToUser as $aEntry ) {
							// Create auction part
							$oAuctionItem->updateFavoriteItem( $aEntry['itemId'], $aEntry['userId'], 'true' );
							$aErr = clErrorHandler::getValidationError( 'entAuctionItemToUser' );
						}
					}
				}
				
				// Finish
				$oAuctionTransfer->update( $iTransferId, array(
					'transferStatus' => 'done'
				) );
				
				$iAuctionCreated++;
			}
		}
		
		if( empty($aErr) ) {
			if( $iAuctionCreated === 0 ) {
				$oRouter->redirect( $oRouter->sPath );
				
			} else {
				//$oRouter->redirect( $oRouter->sPath . '?fetchAuction=true' );
				$oTemplate->addTop( array(
					'key' => 'redirectJs',
					'content' => '
						<script>setTimeout(\'window.top.location = "http://front.tovek.se/admin/auctions?fetchAuction=true";\',\'2\');</script>'
				) );
				
			}
		}
	}
}

/**
 * Sorting
 */
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oAuctionTransfer->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('transferCreated' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'transferId' => array(),
	'transferType' => array(),
	'transferAuctionId' => array(),
	'transferCreated' => array(),
    'transferUpdated' => array()
) );

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oAuctionTransfer->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 30
) );

// Data
$aTransfers = $oAuctionTransfer->read();

// Pagination
$sPagination = $oPagination->render();

if( !empty($aTransfers) ) {
    // Table init
    clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oAuctionTransfer->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'controls' => array(
			'title' => ''
		)
	) );
    
    foreach( $aTransfers as $aTransfer ) {
        $aRow = array(
            'transferId' => $aTransfer['transferId'],
            'transferType' => _( ucfirst($aTransfer['transferType']) ),
            'transferAuctionId' => $aTransfer['transferAuctionId'],
            'transferCreated' => $aTransfer['transferCreated'],
            'transferUpdated' => $aTransfer['transferUpdated'],
            'controls' => ''
        );
        $oOutputHtmlTable->addBodyEntry( $aRow );
    }
    
    $sOutput = $oOutputHtmlTable->render();
    
} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div id="loaderWrapper">
		<div class="container">
			<div class="loader"></div>
			<p>Importerar, vänligen vänta...</p>
		</div>
		<div class="background"></div>
	</div>';

echo '
	<div class="view auctionTransfer tableEdit">
		<h1>' . _( 'Transfers' ) . '</h1>
        <section class="tools">
			<div class="tool">
				<a href="?fetchAuction=true" class="icon iconText iconDbImport">' . _( 'Check for fetchable auctions' ) . '</a>
			</div>
		</section>
		<section>
			' . $sPagination . '
			' . $sOutput . '
			' . $sPagination . '
		</section>
	</div>';

if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
	return;
}

/**
 * 
 */
$oTemplate->addBottom( array(
	'key' => 'viewScript',
	'content' => '
		<script>
			$(document).delegate( ".iconDbImport", "click", function(event) {
				event.preventDefault();
				
				$("#loaderWrapper").fadeIn( "slow" );
				
				var jqxhr = $.get( "?ajax=true&view=auctionTransfer/tableEdit.php&fetchAuction=true", function() {} )
				.done( function() {
					$("#loaderWrapper").fadeOut( "slow", function() {
						$("#loaderWrapper").remove();
					} );	
				} )
				.fail( function() {} )
				.always( function() {} );
			} );
		</script>
	'
) );