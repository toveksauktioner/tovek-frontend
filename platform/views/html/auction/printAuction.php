<?php

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );

/**
 * Function just for creating custom 'h3' as breakpoint.
 */
 if( !function_exists('title') ) {
	function title( $sTitle ) {
		return '
			<h3>
				<div class="toplistTitleLeftBorder">&nbsp;</div><span>' . $sTitle . '</span><div class="toplistTitleRightBorder">&nbsp;</div>
			</h3>';
	}
}

if( !empty($_GET['auctionId']) && !empty($_GET['partId']) ) {
	// Auction data
	$aAuction = current( $oAuctionEngine->readAuction( array(
		'fields' => '*',
		'partId' => (int) $_GET['partId'],
		'auctionStatus' => '*',
		'partStatus' => '*'
	) ) );

	if( !empty($aAuction) ) {
		// Auction item data
		$oAuctionItemDao->aSorting = array( 'itemSortNo' );
		$aItems = valueToKey( 'itemId', $oAuctionEngine->readAuctionItem(array(
			'fields' => '*',
			'status' => '*',
			'partId' => $_GET['partId']
		)) );

		/**
		 * Group auction items by location
		 */
		$aItemByLocation = array();
		foreach( $aItems as $aItem ) {
			if( empty($aItemByLocation[$aItem['itemLocation']]) ) {
				$aItemByLocation[$aItem['itemLocation']] = array();
			} else {
				$aItemByLocation[$aItem['itemLocation']][] = $aItem['itemId'];
			}
		}
		// ..and determ active location
		$sActiveLocation = current(array_keys($aItemByLocation));
		if( !empty($_GET['location']) ) {
			$sActiveLocation = $_GET['location'];
		}

		/**
		 * Auciton item controls
		 */
		$sTableControls = '
			<ul>
				<li>Visa rop:</li>
				<li class="location">
					<a href="' . $oRouter->sPath . '?auctionId=' . $_GET['auctionId'] . '&partId=' . $_GET['partId'] . '#itemWrapper" class="' . (empty($_GET['location']) ? 'active' : '') . '">
						' . _( 'All' ) . '
					</a>
				</li>';
		foreach( array_keys($aItemByLocation) as $sLocation ) {
			$sRoutePath = $oRouter->sPath . '?auctionId=' . $_GET['auctionId'] . '&partId=' . $_GET['partId'] . '&location=' . $sLocation . '#itemWrapper';

			$aClass = array();
			if( !empty($_GET['location']) && $sLocation == $_GET['location'] ) {
				$aClass[] = 'active';
			}

			$sTableControls .= '
				<li class="location">
					<a href="' . $sRoutePath . '" class="' . (!empty($aClass) ? implode(' ', $aClass) : '') . '">
						' . $sLocation . '
					</a>
				</li>';
		}
		$sTableControls .= '
				<li>' . str_repeat( '&nbsp;', 10 ) . '</li>
				<li><a href="#itemContainer" class="toggleShow">Visa/dölj rop</a></li>
			</ul>';


		/**
		 * Auction item table
		 */
		$sAuctionItemTable = '';
		clFactory::loadClassFile( 'clOutputHtmlTable' );
		$oOutputHtmlTable = new clOutputHtmlTable( $oAuctionItemDao->getDataDict() );
		$oOutputHtmlTable->setTableDataDict( array(
			'itemSortNo' => array(),
			'itemTitle' => array(
				'title' => _( 'Description' )
			),
			'itemLocation' => array(
				'title' => _( 'Place of storage' )
			),
			'itemVat' => array(
				'title' => ''
			)
		) );
		$iCount = 1;
		foreach( $aItems as $aItem ) {
			if( !empty($_GET['location']) && $_GET['location'] != $aItem['itemLocation'] ) {
				continue;
			}

			$row = array(
				'itemSortNo' => $aItem['itemSortNo'],
				'itemTitle' => $aItem['itemTitle'],
				'itemLocation' => $aItem['itemLocation'],
				'itemVat' => ( ($aItem['itemVatValue'] == 0) ? 'Moms utgår ej' : '' )
			);

			if( $iCount % 2 == 0 ) {
				$oOutputHtmlTable->addBodyEntry( $row, array( 'class' => 'odd' ) );
			} else {
				$oOutputHtmlTable->addBodyEntry( $row );
			}

			++$iCount;
		}
		$sAuctionItemTable = '
			<div id="itemWrapper">
				<div id="itemControls">
					' . $sTableControls . '
				</div>
				<div id="itemContainer" class="show">
					<p>( <strong>Notera att,</strong> 25% moms tillkommer på <strong>samtliga</strong> utrop, <strong>utom vissa</strong> vilka är märkta med "Moms utgår ej". )</p>
					' . $oOutputHtmlTable->render() . '
				</div>
			</div>';

		$oLayout = clRegistry::get( 'clLayoutHtml' );
		echo $oLayout->renderView( 'auction/auctionShowInfo.php' ) . '
			<br class="break" />
			', title( 'Auktionsrop' ), '
			', $sAuctionItemTable;

	} else {
		echo '
		<p style="text-align: center;">
			<span style="font-size: 28px; color: #c4c4c4; font-weight: 300;">
				- ' . _( 'Something went wrong' ) . ' -
			</span>
		</p>';
	}
} else {
	echo '
		<p style="text-align: center;">
			<span style="font-size: 28px; color: #c4c4c4; font-weight: 300;">
				- ' . _( 'No auction to show' ) . ' -
			</span>
		</p>';
}
