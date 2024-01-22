<?php

/**
 * User invoices
 */
$iUnpaidInvoiceTotal = '';
if( !empty($_SESSION['userId']) ) {
	$oBackEnd = clFactory::create( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
	$oBackEnd->setSource( 'entInvoice', 'invoiceId' );
	$oBackEnd->oDao->sCriterias = '';
	$oBackEnd->oDao->setCriterias( array(
		'unpaid' => array(
			'fields' => 'invoiceStatus',
			'type' => 'in',
			'value' => array(
				'unpaid',
				'partpaid'
			)
		),
		'user' => array(
			'fields' => 'invoiceUserId',
			'value' => $_SESSION['userId']
		)
	) );
	$aUserInvoices = $oBackEnd->read();
	$oBackEnd->oDao->sCriterias = '';
	$iUnpaidInvoiceTotal = count( $aUserInvoices);
}

$aLinks = array(
	'classic' => $oRouter->getPath( 'classicGuestStart' ),
	'bidAndFav' => $oRouter->getPath( 'userHomepage' ),
	'invoices' => $oRouter->getPath( 'userInvoiceList' ),
	'account' => $oRouter->getPath( 'userAccount' ),
	'logout' => $oRouter->getPath( 'userLogout' )
);

echo '
	<div class="view user panelLinks">
		<ul class="tabs">
			<li class="tab' . ( ($aLinks['classic'] == $oRouter->sPath) ? ' selected' : '' ) . '"><a href="' . $aLinks['classic'] . '">' . _( 'Classic page' ) . '</a></li>
			<li class="tab' . ( ($aLinks['bidAndFav'] == $oRouter->sPath) ? ' selected' : '' ) . '"><a href="' . $aLinks['bidAndFav'] . '">' . _( 'Bid and Favorites' ) . '</a></li>
			<li class="tab' . ( ($aLinks['invoices'] == $oRouter->sPath) ? ' selected' : '' ) . '"><a href="' . $aLinks['invoices'] . '">' . _( 'Invoices' ) . /*<span class="amount">' . $iUnpaidInvoiceTotal . '</span>*/ '</a></li>
			<li class="tab' . ( ($aLinks['account'] == $oRouter->sPath) ? ' selected' : '' ) . '"><a href="' . $aLinks['account'] . '">' . _( 'My account' ) . '</a></li>
			<li class="tab' . ( ($aLinks['logout'] == $oRouter->sPath) ? ' selected' : '' ) . '"><a href="' . $aLinks['logout'] . '">' . _( 'Logout' ) . '</a></li>
		</ul>
	</div>';
