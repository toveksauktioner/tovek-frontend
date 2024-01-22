<?php

//ini_set( 'max_execution_time', 180 );
//ini_set( 'memory_limit', '1GB' );
//echo '<pre>';
//var_dump( $_SESSION['routerCheck'] );
//die();

$aErr = array();

$oAuctionTransfer = clRegistry::get( 'clAuctionTransfer', PATH_MODULE . '/auctionTransfer/models' );
$oAuction = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );
$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );

if( !empty($_GET['error']) ) {
	$oNotification->setSessionNotifications( array(
		'dataError' => _( 'Something must have went wrong' )
	) );
}

/**
 * Clear route data
 */
if( !empty($_GET['clearRoute']) ) {
	$oRouter->oDao->deleteData( array(
		'criterias' => 'routeLayoutKey IN ("guestAuctions","guestAuctionItems","guestAuctionItemShow") AND routeId NOT IN (440,441,442)'
	) );
	$oRouter->oDao->deleteData( array(
		'entities' => 'entRouteToObject',
		'criterias' => 'objectType IN ("Auction","AuctionItem")'
	) );
	$oNotification->setSessionNotifications( array(
		'dataSaved' => _( 'Route data has been cleaned' )
	) );
	unset( $_SESSION['routerCheck'] );
	$oRouter->redirect( $oRouter->sPath );
}

/**
 * Post
 */
if( !empty($_GET['updateAuctionRoutes']) && $_GET['updateAuctionRoutes'] == 'true' ) {
	unset( $_SESSION['routerCheck'] );
	$oRouter->redirect( $oRouter->sPath . '?checkAuctionRoute=true' . ( !empty($_GET['auctionId']) ? '&auctionId=' . $_GET['auctionId'] : '' ) );
}
if( !empty($_GET['checkAuctionRoute']) && $_GET['checkAuctionRoute'] == 'true' ) {	
    if( !isset($_SESSION['routerCheck']) ) $_SESSION['routerCheck'] = array(
        'auction' => array( 1 ),
        'item' => array(),
		'counter' => 0
    );
	
    /**
     * Auction data
     */
	if( !empty($_GET['auctionId']) ) {
		$aAuctions = $oAuction->oDao->readData( array(
			'fields' => array(),
			'criterias' => 'auctionId IN (' . implode( ', ', array_map('intval', explode(',', $_GET['auctionId']))) . ')'
		) );
	} else {
		$aAuctions = $oAuction->oDao->readData( array(
			'fields' => array(
				'auctionId', 'auctionTitle', 'auctionStatus' //'routePath'
			),
			//'entitiesExtended' => 'entAuction LEFT JOIN entRouteToObject ON entAuction.auctionId = entRouteToObject.objectId AND entRouteToObject.objectType = "Auction" LEFT JOIN entRoute ON entRouteToObject.routeId = entRoute.routeId',
			//'criterias' => !empty($_SESSION['routerCheck']['auction']) ? 'auctionId NOT IN (' . implode( ', ', array_map('intval', $_SESSION['routerCheck']['auction']) ) . ')' : array()
			'criterias' => 'auctionId NOT IN (' . implode( ', ', array_map('intval', $_SESSION['routerCheck']['auction']) ) . ')'
		) );
	}
	
    if( !empty($aAuctions) ) {
        foreach( $aAuctions as $aAuction ) {
			// Auction item holder
            if( !isset($_SESSION['routerCheck']['item'][ $aAuction['auctionId'] ]) ) {
				$_SESSION['routerCheck']['item'][ $aAuction['auctionId'] ] = array();
			}
			
			/**
             * Auction part data
             */
			$aAuctionParts = $oAuction->oDao->readData( array(
				'fields' => array(),
				'entities' => 'entAuctionPart',
				'criterias' => 'partAuctionId = "' . $aAuction['auctionId'] . '"'
			) );
			
			/**
             * Auction & auction part routes
             */
			if( !empty($aAuctionParts) ) {			
				/**
				 * Auction route
				 */
				$aRouteData = $oRouter->readByObject( $aAuction['auctionId'], 'Auction', array(
					'entRouteToObject.routeId',
					'objectId',
					'objectType'
				) );				
				if( empty($aRouteData) ) {
					/**
					 * Create auction route
					 */
					$sPath = strToUrl( $oRouter->getPath( 'guestAuctions' ) . '/' . $aAuction['auctionTitle'] . '/' . $aAuction['auctionId'] );				
					if( $oRouter->createRouteToObject( $aAuction['auctionId'], 'Auction', $sPath, 'guestAuctions' ) ) {
						// Successful
						$_SESSION['routerCheck']['counter']++;
						
						/**
						 * Auction part
						 */
						if( !empty($aAuctionParts) ) {
							foreach( $aAuctionParts as $aPart ) {
								$sPath .= '/' . strToUrl( $aPart['partTitle'] . '/' . $aPart['partId'] );
								if( $oRouter->createRouteToObject( $aPart['partId'], 'AuctionPart', $sPath, 'guestAuctionItems' ) ) {
									// Successful
									$_SESSION['routerCheck']['counter']++;
									
								} else {
									// Unsuccessful
									
								}
							}
						}
						
					} else {
						// Unsuccessful
						
					}
				}
			} else {
				// Auciton without part, do not continue
				$_SESSION['routerCheck']['auction'][] = $aAuction['auctionId'];
				continue;
			}
			
			/**
             * Auction item data
             */
			$aCriterias = array( 'itemAuctionId = "' .  $aAuction['auctionId'] . '"' );
			if( !empty($_SESSION['routerCheck']['item'][ $aAuction['auctionId'] ]) ) {
				$aCriterias[] = 'itemId NOT IN (' . implode( ', ', array_map('intval', $_SESSION['routerCheck']['item'][ $aAuction['auctionId'] ]) ) . ')';
			}
            $aAuctionItems = $oAuctionItem->oDao->readData( array(
                'fields' => array(),
                'criterias' => implode( ' AND ', $aCriterias )
            ) );
			
            if( !empty($aAuctionItems) ) {
				$aItemToRoute = arrayToSingle( $oRouter->readByObject( arrayToSingle($aAuctionItems, null, 'itemId'), 'AuctionItem', array(
					'entRouteToObject.routeId',
					'objectId',
					'objectType'
				) ), 'objectId', 'routeId' );
				
				if( count($aItemToRoute) < count($aAuctionItems) ) {
					/**
					 * Auction item routes
					 */
					foreach( $aAuctionItems as $aItem ) {
						if( array_key_exists($aItem['itemId'], $aItemToRoute) ) {
							continue; // Route already exists
						}
						
						/**
						 * Create item route
						 */
						$sPath = strToUrl( $oRouter->getPath( 'guestAuctionItems' ) . '/' . $aItem['itemTitle'] . '/' . $aItem['itemId'] );				
						if( $oRouter->createRouteToObject( $aItem['itemId'], 'AuctionItem', $sPath, 'guestAuctionItemShow' ) ) {
							// Successful
							$_SESSION['routerCheck']['item'][ $aAuction['auctionId'] ][] = $aItem['itemId'];
							$_SESSION['routerCheck']['counter']++;
							
							if( $_SESSION['routerCheck']['counter'] % 300 == 0 ) {								
								/**
								 * Redirect on every 100 entry
								 */
								$oTemplate->addBottom( array(
									'key' => 'jsRedirect',
									'content' => '
										<script>
											$(document).ready( function() {
												setTimeout( function() { window.top.location="' . $oRouter->sPath . '?checkAuctionRoute=true' . (!empty($_GET['auctionId']) ? '&auctionId=' . $_GET['auctionId'] : '') . '" } , 3000 );   
											} );
										</script>'
								) );
								break 2;
							}
							
							continue;
							
						} else {
							// Unsuccessful
						}					
					}
					
				} else {				
					/**
					 * All routes for current auction exists
					 */
					$_SESSION['routerCheck']['auction'][] = $aAuction['auctionId'];
					$oTemplate->addBottom( array(
						'key' => 'jsRedirect',
						'content' => '
							<script>
								$(document).ready( function() {
									setTimeout( function() { window.top.location="' . $oRouter->sPath . '?checkAuctionRoute=true" } , 3000 );   
								} );
							</script>'
					) );
					break;
				}	
            } else {
				/**
				 * No items, continue..
				 */
				$_SESSION['routerCheck']['auction'][] = $aAuction['auctionId'];
				continue;
			}
        }
    } else {		
		/**
		 * No more auctions to process
		 */		
		unset( $_SESSION['routerCheck'] );		
		//$oNotification->setSessionNotifications( array(
		//	'dataSaved' => _( 'The data has been saved' )
		//) );
		//$oRouter->redirect( $oRouter->sPath );
		$oTemplate->addBottom( array(
			'key' => 'jsRedirect',
			'content' => '
				<script>					
					$(document).ready( function() {
						$("#loaderWrapper").fadeOut( "fast", function() {
							//$("#loaderWrapper").remove();
						} );
						setTimeout( function() { window.top.location="' . $oRouter->sPath . '" } , 3000 );   
					} );
				</script>'
		) );
	}
} else {
	unset( $_SESSION['routerCheck'] );
}

/**
 * Sorting
 */
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oRouter->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('routeCreated' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'routeId' => array(),
	'routeLayoutKey' => array(),
	'routePath' => array(),
    'routeCreated' => array()
) );

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oRouter->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 30
) );

// Data
$aRoutes = $oRouter->oDao->read( array(
    'routeLayoutKey' => array(
        'guestAuctions',
        'guestAuctionItems',
        'guestAuctionItemShow'
    ),
    'fields' => array(
        'routeId',
        'routeLayoutKey',
        'routePath',
        'routeCreated'
    )
) );

// Pagination
$sPagination = $oPagination->render();

if( !empty($aRoutes) ) {
    // Table init
    clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oRouter->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'controls' => array(
			'title' => ''
		)
	) );
    
    foreach( $aRoutes as $aRoute ) {
        $aRow = array(
            'routeId' => $aRoute['routeId'],
            'routeLayoutKey' => $aRoute['routeLayoutKey'],
            'routePath' => $aRoute['routePath'],
            'routeCreated' => substr( $aRoute['routeCreated'], 0, 10 ),
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
			<p>Bearbetar (' . (!empty($_SESSION['routerCheck']['counter']) ? $_SESSION['routerCheck']['counter'] : 0) . ' created), vänligen vänta...</p>
		</div>
		<div class="background"></div>
	</div>';
	
echo '
	<div class="view auctionTransfer routeTableEdit">
		<h1>' . _( 'Routes' ) . '</h1>
        <section class="tools">
            <div class="tool">
				<a href="' . $oRouter->getPath( 'adminAuctions' ) . '" class="icon iconText iconGoBack">' . _( 'Go back' ) . '</a>
			</div>
			<div class="tool">
				<a href="?updateAuctionRoutes=true" class="icon iconText iconDbImport" id="checkAuctionRouteLink">' . _( 'Update route paths' ) . '</a>,
				<label for="auctionId">' . _( 'Only ID' ) . ':</label> <input type="text" name="auctionId" placeholder="' . _( 'Auction ID' ) . '" style="width: 6em;" />
			</div>
			<div class="tool">
				<a href="?clearRoute=true" class="icon iconText iconDelete linkConfirm" title="' .  _( 'Are you sure?' ) . '">' . _( 'Clear routes' ) . '</a>
			</div>
		</section>
		<section>
			' . $sPagination . '
			' . $sOutput . '
			' . $sPagination . '
		</section>
	</div>';

/**
 * Loader
 */
if( !empty($_GET['checkAuctionRoute']) && empty($_GET['error']) ) {
	$oTemplate->addBottom( array(
		'key' => 'viewScriptJs',
		'content' => '
			<script>
				//$("#loaderWrapper").fadeIn( "slow" );
				$("#loaderWrapper").show();
			</script>
		'
	) );
}

$oTemplate->addBottom( array(
	'key' => 'checkAuctionRouteLinkJs',
	'content' => '
		<script>
			$(document).delegate( "#checkAuctionRouteLink", "click", function(event) {
				event.preventDefault();
				if( $(\'input[name="auctionId"]\').val() != "" ) {
					window.top.location = "' . $oRouter->sPath . '?updateAuctionRoutes=true&auctionId=" + $(\'input[name="auctionId"]\').val();
				} else {
					window.top.location = "' . $oRouter->sPath . '?updateAuctionRoutes=true";
				}
			} );
		</script>
	'
) );

if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
	return;
}