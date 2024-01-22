<?php

$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );
$oPayment->oDao->setLang( $GLOBALS['langIdEdit'] );

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oPayment->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('paymentSort' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'paymentTitleTextId' => array(),
	'paymentPrice' => array(),
	'paymentStatus' => array()
) );

$aPayments = $oPayment->readAll( array(
	'paymentId',
	'paymentTitleTextId',
	'paymentPrice',
	'paymentSort',
	'paymentStatus'
) );

$sTools = '';
$sOutput = '';

if( !empty($aPayments) ) {
	$aPaymentDataDict = $oPayment->oDao->getDataDict();

	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $aPaymentDataDict );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'paymentControls' => array(
			'title' => ''
		)
	) );

	$sEditUrl = $oRouter->getPath( 'adminPaymentAdd' );
	$sFreightRelationUrl = $oRouter->getPath( 'adminPaymentToFreightTypeAdd' );
	$sCustomerGroupRelationUrl = $oRouter->getPath( 'adminPaymentToCustomerGroupAdd' );

	$iCount = 1;
	foreach( $aPayments as $entry ) {
		$aParams = array();
		if( $iCount % 2 == 0 ) $aParams['class'] = 'odd';

		$row = array(
			'paymentTitleTextId' => '<a href="' . $sEditUrl . '?paymentId=' . $entry['paymentId'] . '">' . htmlspecialchars( $entry['paymentTitleTextId'] ) . '</a>',
			'paymentPrice' => $entry['paymentPrice'],
			'paymentStatus' => '<span class="' . $entry['paymentStatus'] . '">' . $aPaymentDataDict['entPayment']['paymentStatus']['values'][ $entry['paymentStatus'] ] . '</span>',
			'paymentControls' => '
				<a href="' . $sFreightRelationUrl . '?paymentId=' . $entry['paymentId'] . '" class="icon iconPackageLink iconText">' . _( 'Freight types' ) . '</a>
				<a href="' . $sCustomerGroupRelationUrl . '?paymentId=' . $entry['paymentId'] . '" class="icon iconUser iconText">' . _( 'Customer types' ) . '</a>
				<a href="' . $sEditUrl . '?paymentId=' . $entry['paymentId'] . '" class="icon iconEdit iconText"><span>' . _( 'Edit' ) . '</span></a>
				<a href="?event=deletePayment&amp;deletePayment=' . $entry['paymentId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>'
		);

		$aParams['id'] = 'sortPayment_' . $entry['paymentId'];
		$oOutputHtmlTable->addBodyEntry( $row, $aParams );
		++$iCount;
	}

	if( array_key_exists('super', $_SESSION['user']['groups']) ) {
		$sTools = '
			<section class="tools">
				<div class="tool">
					<a href="' . $oRouter->getPath( 'adminPaymentAdd' ) . '" class="icon iconText iconAdd">' . _( 'Add new payment' ) . '</a>
				</div>
			</section>';
	}
	
	$sOutput = $oOutputHtmlTable->render();
		
} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view paymentTable">
		<h1>' . _( 'Payments') . '</h1>
		' . $sTools . '
		<section>
			' . $sOutput . '
		</section>
	</div>';

$oPayment->oDao->setLang( $GLOBALS['langId'] );

// Sortable
$oTemplate->addBottom( array(
	'key' => 'freightTypeSortable',
	'content' => '
	<script>
		$(".paymentTable table tbody").sortable({
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortPayment&sortPayment=1&" + $(this).sortable("serialize"));
			}
		});
	</script>'
) );
