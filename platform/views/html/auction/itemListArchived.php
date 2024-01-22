<?php

if( empty($_GET['auctionId']) || empty($_GET['partId']) ) {
	$oRouter->redirect( $oRouter->getPath('guestArchivedAuctions') );
	exit;
}

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
$oOutputHtmlAuction = clRegistry::get( 'clOutputHtmlAuction' );

/**
 * Read auction
 */
$aAuction = current( $oBackEnd->readAuction( '*', $_GET['auctionId'] ) );
$aAuction['auctionType'] = 'net';

if( !empty($aAuction) ) {

	/**
	 * Auction item data
	 */
	$oBackEnd->setSource( 'entAuctionItem', 'itemId' );
	$oBackEnd->oDao->setCriterias( array(
		'itemAuctionId' => array(
			'fields' => 'itemAuctionId',
			'value' => $_GET['auctionId']
		),
		'itemPartId' => array(
			'fields' => 'itemPartId',
			'value' => $_GET['partId']
		)
	) );
	if( !empty($_GET['itemSortNo']) ) {
		$iItemSortNo = (int) $_GET['itemSortNo'];
		$oBackEnd->oDao->setCriterias( array(
			'itemSortNo' => array(
				'type' => 'in',
				'fields' => 'itemSortNo',
				'value' => $iItemSortNo
			)
		) );
	}
	$oBackEnd->oDao->aSorting = array( 'itemSortNo' => 'ASC' );
	$aAuctionItems = $oBackEnd->read( '*' );
	$oBackEnd->oDao->sCriterias = null;
	$oBackEnd->oDao->aSorting = array();

	foreach( $aAuctionItems as $iKey => $aItem ) {
		$aAuctionItems[ $iKey ] += array(
			'routePath' => '',
			'partStatus' => '',
			'auctionType' => 'net'
		);
	}
}

/**
 * Assamble item list
 */
$oOutputHtmlAuctionItems = new clOutputHtmlAuction( array(
	'listKey' => 'itemListArchived',
	'viewFile' => 'auction/itemListArchived.php',
	'title' => _( 'Item list' ),
	'paginationType' => ( !empty($sPaginationType) ? $sPaginationType : 'normal' ),
	'sortType' => !empty($_GET['sortBy']) ? $_GET['sortBy'] : 'itemNo',
	'searchForm' => array( 'itemSortNo' ),
	'listAll' => ( !empty($_GET['listAll']) ? $_GET['listAll'] : false ),
	'showEnded' => true
) );
$oOutputHtmlAuctionItems->addAuctionData( $aAuction );
$oOutputHtmlAuctionItems->addItemData( $aAuctionItems );

$sOutput = $oOutputHtmlAuctionItems->render();

echo '
    <div class="view auction itemList itemListArchived">
		<h1 id="listHeader"><small>' . $aAuction['auctionId'] . '</small> ' . $aAuction['auctionTitle'] . ' <span>' . /*$aAuction['partTitle'] .*/ '</span></h1>
        ' . $sOutput . '
    </div>';
