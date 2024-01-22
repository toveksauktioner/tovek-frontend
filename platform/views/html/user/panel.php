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
	$iUnpaidInvoiceTotal = count( $aUserInvoices);
}

if( !empty($_SESSION['userId']) ) {
	echo '
        <ul>
            <li><a href="' . $oRouter->getPath( 'classicGuestStart' ) . '?siteVersion=classic">' . _( 'Classic page' ) . '</a></li>
            <li><a href="' . $oRouter->getPath( 'userHomepage' ) . '" class="myPages">' . _( 'My pages' ) . '</a></li>
		</ul>';
    //echo '
    //    <ul>
    //        <li><a href="' . $oRouter->getPath( 'classicGuestStart' ) . '">' . _( 'Classic page' ) . '</a></li>
    //        <li><a href="' . $oRouter->getPath( 'userHomepage' ) . '">' . _( 'Bid and Favorites' ) . '</a></li>
    //        <li><a href="' . $oRouter->getPath( 'userInvoiceList' ) . '">' . _( 'Invoices' ) . ' <span class="amount">' . $iUnpaidInvoiceTotal . '</span></a></li>
    //        <li><a href="' . $oRouter->getPath( 'userAccount' ) . '">' . _( 'My account' ) . '</a></li>
    //        <li><a href="' . $oRouter->getPath( 'userLogout' ) . '">' . _( 'Logout' ) . '</a></li>
    //    </ul>';

} else {
    echo '
        <ul>
            <li><a href="' . $oRouter->getPath( 'classicGuestStart' ) . '?siteVersion=classic">' . _( 'Classic page' ) . '</a></li>
            <li><a href="' . $oRouter->getPath( 'guestUserSignup' ) . '">' . _( 'Signup' ) . '</a></li>
            <li><a href="' . $oRouter->getPath( 'userLogin' ) . '" id="loginLink">' . _( 'Login' ) . '</a></li>
        </ul>';

}
