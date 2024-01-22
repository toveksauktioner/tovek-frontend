<?php

/* * * *
 * Filename: invoiceList.php
 * Created: 21/05/2014 by Renfors
 * Reference:
 * Description: View file for showing users unpaid invoices.
 * * * */

$aPriceFormat = array(
 'additional' => array(
	 'format' => array(
		 'money' => true
	 ),
	 'currencyFormat' => 'i'
 )
);

$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );

/**
 * Replace load back-end database
 */
$oInvoice = clRegistry::get( 'clInvoice', PATH_MODULE . '/invoice/models' );
$oInvoiceLine = clRegistry::get( 'clInvoiceLine', PATH_MODULE . '/invoice/models' );
$oInvoicePaymentLog = clRegistry::get( 'clInvoicePaymentLog', PATH_MODULE . '/invoice/models' );
$oFreightRequest = clRegistry::get( 'clFreightRequest', PATH_MODULE . '/freightRequest/models' );
$oInvoice->oDao->oDb = clRegistry::get( 'clDbPdoSecondary' );
$oInvoiceLine->oDao->oDb = clRegistry::get( 'clDbPdoSecondary' );
$oInvoicePaymentLog->oDao->oDb = clRegistry::get( 'clDbPdoSecondary' );
$oFreightRequest->oDao->oDb = clRegistry::get( 'clDbPdoSecondary' );
/**
 * End of replace load back-end database
 */

$oInvoiceDao = $oInvoiceEngine->getDao( 'Invoice' );
$aDataDict = $oInvoiceDao->getDataDict();

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

$oFreightRequest = clRegistry::get( 'clFreightRequest', PATH_MODULE . '/freightRequest/models' );

$oUserManager = clRegistry::get( 'clUserManager' );

/**
 * Selected payment method
 */
if( !isset($_SESSION['paymentMethod']) ) {
	$_SESSION['paymentMethod'] = 5;
}
if( !empty($_GET['paymentMethodSelection']) ) {
	$_SESSION['paymentMethod'] = $_GET['paymentMethodSelection'];
}

// Cancel the invoice payment
if( !empty($_POST['orderid']) && !empty($_POST['md5key2']) ) {
  $oInvoiceEngine->setInvoiceOrderCancelled_in_InvoiceOrder( $_SESSION['invoiceOrderId'] );
}
if( !empty($_GET['cancelPaymentInvoiceOrderId']) ) {
  $aInvoiceOrderData = current( $oInvoiceEngine->read('InvoiceOrder', null, $_GET['cancelPaymentInvoiceOrderId']) );
  if( !empty($aInvoiceOrderData) && ($aInvoiceOrderData['invoiceOrderUserId'] == $oUser->iId) ) {
  	$oInvoiceEngine->setInvoiceOrderCancelled_in_InvoiceOrder( $_GET['cancelPaymentInvoiceOrderId'] );
  }
}

// Sorting
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oInvoiceDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('invoiceId' => 'DESC') )
) );
$oSorting->setSortingDataDict( array(
	'invoiceId' => array(
		'title' => _( 'Nr.' )
	),
	'invoiceDate' => array(
		'title' => _( 'Date' )
	),
	'invoiceRemainingAmount' => array(
		'title' => ''
	)
) );

// Fetch user invoices
$oInvoiceDao->sCriterias = null;
$oInvoiceDao->setCriterias( array(
	'unpaid' => array(
		'fields' => 'invoiceStatus',
		'type' => 'in',
		'value' => array(
			'unpaid',
			'partpaid'
		)
	)
) );
$aInvoices = $oInvoiceEngine->readByUser_in_Invoice( $oUser->iId, array(
	'invoiceId',
	'invoiceNo',
	'invoiceUserId',
	'invoiceType',
	'invoiceDate',
	'invoiceStatus',
	'invoiceTotalAmount',
	'invoiceTotalVat',
	'invoiceCreditDays',
	'invoiceLocked',
	'invoiceLockedDate',
	'invoiceLockedByUserId',
	'invoiceAuctionId',
	'invoiceFreightRequestId'
) );

// Initiate the invoice payment
if( isset($_GET['payInvoice']) ) {
	#if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($_SERVER['HTTP_X_FORWARDED_FOR'], array("213.88.134.199","194.18.61.14")) ) {
		# Temporary limitation

		$_SESSION['orderPaymentType'] = $_SESSION['paymentMethod']; # 5:'clPaymentDibsHosted', 6:'clPaymentSwish'

		// Create the invoice order
		$iInvoiceOrderId = $oInvoiceEngine->createInvoiceOrder( explode(',', $_GET['payInvoice']), $_SESSION['orderPaymentType'] );

		if( $iInvoiceOrderId !== false ) {
			$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );

			// Initiate payment of the order
			$_SESSION['invoiceOrderId'] = $iInvoiceOrderId;

			$sPaymentMethodClass = current( current($oPayment->read('paymentClass', $_SESSION['orderPaymentType'])) );
			$oPaymentMethod = clRegistry::get( $sPaymentMethodClass, PATH_MODULE . '/payment/models' );
			$oPaymentMethod->initInvoiceOrder( $iInvoiceOrderId );
		}
	 #} else {
	 #	 $sNotification = '<ul class="notification"><li class="error">Betalning är inte tillgänglig för tillfället</li></ul>';
	 #}
}

if( !empty($aInvoices) ) {
	clFactory::loadClassFile( 'clOutputHtmlGridTable' );
	$oOutputHtmlTable = new clOutputHtmlGridTable( $aDataDict );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'invoicePay' => array(
			'title' => ''
		)
	) );

	$sShowUrl = $oRouter->getPath( 'userInvoiceShow' );
	$sPayUrl = '';

	$fTotalAmount = 0;
	foreach( $aInvoices as $entry ) {

		// All this invoice's payments
		$aInvoicePaymentData = arrayToSingle( $oInvoiceEngine->readByInvoice_in_InvoicePaymentLog($entry['invoiceId'], array('logAmount')), null, 'logAmount' ) ;
		$fSumRemaining = $entry['invoiceTotalAmount'] - array_sum( $aInvoicePaymentData );

		// Check if the invoice is elegible for freight request
		$bFreightRequestAllowed = false;
		$aItems = arrayToSingle( $oInvoiceEngine->readByInvoice_in_InvoiceLine($entry['invoiceId'], 'invoiceLineItemId'), null, 'invoiceLineItemId' );

    if( !empty($aItems) ) {
      // Clean up items
      $aTempItems = array();
      foreach( $aItems as $iItemId ) {
        if( !empty($iItemId) ) $aTempItems[] = $iItemId;
      }
      $aItems = $aTempItems;
    }

    if( !empty($aItems) ) {
			// Get addresses and collect info
			$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
			$oBackEnd->setSource( 'entAuctionItem', 'itemId' );
			$oBackEnd->oDao->setCriterias( array(
				'itemId' => array(
					'fields' => 'itemId',
					'type' => 'in',
					'value' => $aItems
				)
			) );
			$aAddressIds = arrayToSingle( $oBackEnd->read( 'itemAddressId' ), 'itemAddressId', 'itemAddressId' );
			$oBackEnd->oDao->sCriterias = null;
			if( !empty($aAddressIds) ) {
				$oBackEnd->setSource( 'entAuctionAddress', 'addressId' );
				$oBackEnd->oDao->setCriterias( array(
					'addressId' => array(
						'fields' => 'addressId',
						'type' => 'in',
						'value' => $aAddressIds
					)
				) );
				$aAddresses = $oBackEnd->read( array(
					'addressId',
					'addressFreightHelp',
					'addressFreightRequestLastDate'
				) );
				$oBackEnd->oDao->sCriterias = null;
			}

			// The last time to submit freigt requests are the date set on the address
			// If not all of the addresses hav freight help available then the freight is not possible
			if( !empty($aAddresses) ) {
				$bFreightHelpAvailable = true;
				$iStopTime = false;
				foreach( $aAddresses as $aAddress ) {
					if( !empty($aAddress['addressFreightRequestLastDate']) ) {
						$iThisCollectDay = strtotime( $aAddress['addressFreightRequestLastDate'] . ' 23:59:59' );
						if( !$iStopTime || ($iStopTime > $iThisCollectDay) ) {
							$iStopTime = $iThisCollectDay;
						}
					}

					if( $aAddress['addressFreightHelp'] == 'no' ) {
						$bFreightHelpAvailable = false;
						break;
					}
				}

				if( ($bFreightHelpAvailable === true) && ($iStopTime !== false) && (time() < $iStopTime) ) {
					$bFreightRequestAllowed = true;
					}

			}
		}

		//if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == "213.88.134.199" ) {
		//	$bFreightRequestAllowed = true;
		//}
		// Freight request (only "auction" invoices)
		$sInvoiceFreightRequest = '';
		if( !empty($entry['invoiceAuctionId']) ) {

      $sInvoiceFreightRequest = '<span class="freightRequestBtn unavailable" data-status="requested" data-invoice-id="' . $entry['invoiceId'] . '"><span class="fas fa-times-circle">&nbsp;</span>' . _( 'Not available' ) . '</span>';

			if( $bFreightRequestAllowed === true ) {
				$sInvoiceFreightRequest = '<a class="freightRequestBtn request" data-status="none" data-invoice-id="' . $entry['invoiceId'] . '"><span class="fas fa-plus-circle">&nbsp;</span><strong>' . _( 'Request suggestion' ) . '</strong></a>';
			}

			$aFreightRequest = ( !empty($entry['invoiceFreightRequestId']) ? $oFreightRequest->read('requestStatus', $entry['invoiceFreightRequestId']) : array() );
			if( !empty($aFreightRequest) ) {
				$aFreightRequest = current( $aFreightRequest );
				$sFreightRequestStatus = $aFreightRequest['requestStatus'];

				switch( $sFreightRequestStatus ) {
					case 'requested':
						if( $bFreightRequestAllowed === true ) {
							$sInvoiceFreightRequest = '<span class="freightRequestBtn requested" data-status="requested" data-invoice-id="' . $entry['invoiceId'] . '"><span class="fas fa-info-circle">&nbsp;</span><em>' . _( 'Your request is handled' ) . '</em></span>';
						}
						break;

					case 'suggested':
						if( $bFreightRequestAllowed === true ) {
							$sInvoiceFreightRequest = '<a class="freightRequestBtn suggested" data-status="suggested" data-invoice-id="' . $entry['invoiceId'] . '"><span class="fas fa-exclamation-circle">&nbsp;</span><strong>' . _( 'You have received a suggestion' ) . '</strong></a>';
						}
						break;

					case 'accepted':
						$sInvoiceFreightRequest = '<span class="freightRequestBtn accepted" data-status="accepted" data-invoice-id="' . $entry['invoiceId'] . '"><span class="fas fa-check-circle">&nbsp;</span>' . _( 'You have accepted the suggestion' ) . '</span>';
						break;

					case 'declined':
						$sInvoiceFreightRequest = '<span class="freightRequestBtn declined" data-status="declined" data-invoice-id="' . $entry['invoiceId'] . '"><span class="fas fa-times-circle">&nbsp;</span>' . _( 'Declined' ) . '</span>';
						break;

					case 'shipped':
						if( !empty($sFreightRequestUnifaunOrderId) ) {
							$oUnifaun = clRegistry::get( 'clUnifaun', PATH_MODULE . '/unifaun/models' );
							#$sUnifaunParcelNo = current( current($oUnifaun->read('unifaunParcelNo', $sFreightRequestUnifaunOrderId)) );
							$sUnifaunParcelNo = current( current($oUnifaun->readByInvoice($entry['invoiceId'], 'unifaunParcelNo')) );

							#$sInvoiceFreightRequest = '<a href="' . UNIFAUN_POSTNORD_TRACKING_URL . $sUnifaunParcelNo . '" class="icon iconAccept iconText freightRequestBtn" data-status="shipped" data-invoice-id="' . $entry['invoiceId'] . '" target="_blank">' . _( 'Spåra leveransen' ) . '</a>';
							$sInvoiceFreightRequest = _( 'Försändelse-id' ) . ': ' . $sUnifaunParcelNo . ' (<a href="' . UNIFAUN_POSTNORD_TRACKING_URL . '" target="_blank">' . _( 'PostNord' ) . '</a>)';
						} else {
							$sInvoiceFreightRequest = '<span class="icon iconAccept iconText freightRequestBtn" data-status="shipped" data-invoice-id="' . $entry['invoiceId'] . '" target="_blank">' . _( 'Skickad (går ej att spåra)' ) . '</span>';
						}
						break;
				}
			}
		}

		$fTotalAmount += $fSumRemaining;

    $sFreight = '';
    if( !empty($sInvoiceFreightRequest) ) {
      $sFreight .= '
        <h6>' . _( 'Frakt' ) . '</h6>
        <p>' . $sInvoiceFreightRequest . '</p>';
    }

    $sInfo = '';
    if( !empty($aItems) ) {
      $sInfo .= '
      <h6>' . _( 'Info' ) . '</h6>
      <p><a href="?ajax=1&view=auction/itemPostAuctionInfo.php&itemId=' . implode( ',', $aItems ) . '" class="popupLink">' . _( 'Avhämtning och fordon' ) . '</a></p>';
    }

    // Two rows - first main data - second details
    $rows = array(
      'first' => array(
  			'invoiceId' => $entry['invoiceNo'],
  			'invoiceDate' => substr( $entry['invoiceDate'], 0, 10 ),
  			'invoiceRemainingAmount' =>  calculatePrice( $fSumRemaining, $aPriceFormat ),
        'invoicePay' => ( ($entry['invoiceType'] != 'credit') ? '<a href="' . $sPayUrl . '?payInvoice=' . $entry['invoiceId'] . '" class="button small">' . _( 'Pay' ) . '</a>' : '<span class="paymentStatus">' . _('To receive') . '</span> ' )
      ),
      'second' => array(
        'invoiceId' => '
          <div class="info">
            <h6>' . _( 'Faktura' ) . '</h6>
            ' . $aDataDict['entInvoice']['invoiceType']['values'][$entry['invoiceType']] . '
            <i class="fas fa-caret-right"></i>
            ' . calculatePrice( $entry['invoiceTotalAmount'], $aPriceFormat ) . '
            <i class="fas fa-caret-right"></i>
            <span class="' . $entry['invoiceStatus'] . '">' . _( $aDataDict['entInvoice']['invoiceStatus']['values'][$entry['invoiceStatus']] ) . '</span>
          </div>
          <div class="freight">
            ' . $sFreight . '
          </div>
          <div class="postAuction">
            ' . $sInfo . '
          </div>',
        'invoicePay' => '
          <a href="' . $sShowUrl . '?invoiceId=' . $entry['invoiceId'] . '" class="popupLink" data-show-element="section.invoice"><i class="fas fa-file-invoice">&nbsp;</i>' . _( 'Show' ) . '</a>
          <a href="' . $sShowUrl . '?invoiceId=' . $entry['invoiceId'] . '&pdf=1" target="_blank"><i class="fas fa-file-pdf">&nbsp;</i>' . _( 'PDF' ) . '</a>'
      )
    );

    $oOutputHtmlTable->addBodyGroup( $rows );
	}

	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$aPaymentMethods = array(
		5 => _( 'Dibs' ),
		//6 => _( 'Swish' )
	);

	$row = array(
		'invoiceId' => '',
		'invoiceDate' => '',
		'invoiceRemainingAmount' => '',
		'invoicePay' => ''
	);
	$oOutputHtmlTable->addFooterEntry( $row, array('class' => 'paymentMethodRow') );

	$aInvoiceIds = arrayToSingle( $aInvoices, null, 'invoiceId' );
	$row = array(
		'invoiceId' => '',
		'invoiceDate' => '',
		'invoiceRemainingAmount' => calculatePrice( $fTotalAmount, $aPriceFormat ),
		'invoicePay' => ( ($fTotalAmount > 0) ? '<a href="' . $sPayUrl . '?payInvoice=' . implode(',', $aInvoiceIds) . '" class="button small submit">' . _( 'Pay' ) . '</a>' : '<span class="paymentStatus">' . _('To receive') . '<span>' )
	);
	$oOutputHtmlTable->addFooterEntry( $row );

	$sOutput = $oOutputHtmlTable->render();
} else {

	$sOutput = '<strong>' . _('There are no items to show') . '</strong>';
}

echo '
	<div class="view invoiceList invoiceListUnpaid">
		<h1>' . _( 'Invoices to pay/receive' ) . '</h1>
		' . ( !empty($sNotification) ? $sNotification : '' ) . '
		' . $sOutput . '
	</div>
	<div class="view" id="showInvoice"></div>
	<div class="view" id="freightRequest">
		<div id="freightRequestContent" style="overflow-y: scroll; max-height: 80vh;"></div>
	</div>';


$oTemplate->addLink( array(
	'key' => 'invoiceStyle',
	'href' => '/css/index.php?include=views/html/invoice/'
) );

$oTemplate->addScript( array(
'key' => 'popup',
'src' => '/js/templates/tovekCommon/popup.js'
) );

$oTemplate->addBottom( array(
	'key' => 'jsAjaxLinks',
	'content' => '
	<script>
		$( function() {

			$("#showInvoice").click( function() {
				$( this ).hide();
			} );

			$(".freightRequestBtn").click( function() {
				var invoiceId = $( this ).data( "invoice-id" );
				var status = $( this ).data( "status" );
				var thisOffset = $( this ).offset();

				switch( status ) {
					case "none":
					case "suggested":
						$.post( "' . $oRouter->getPath('ajaxFreightRequestFormAdd') . '", {
							invoiceId: invoiceId
						}, function(data) {
							var boxTop = thisOffset.top;
							console.log( boxTop );
							$("#freightRequestContent").html( data );
							//$("#freightRequest").css( "top", boxTop + "px" );
							$("#freightRequest").show();
						} );
						break;
				}

			} );
		} );

		$(document).delegate( ".ajaxPost", "change", function() {
			var sTargetUrl = window.location;
			var sAction = "get";
			var bRefreshView = false;

			if( typeof $(this).data("target-url") !== "undefined" ) sTargetUrl = $(this).data("target-url");
			if( typeof $(this).data("action") !== "undefined" ) sAction = $(this).data("action");
			if( typeof $(this).data("refreshView") !== "undefined" ) bRefreshView = true;

			var eParentView = $(this).closest( ".view" );

			var eParentForm = null;
			if( !$(this).hasClass("single") ) {
				eParentForm = $(this).closest( "form" );
			}

			if( sAction == "get" ) {
				if( $(this).hasClass("single") ) {
					sTargetUrl = sTargetUrl + "?" + $(this).attr("id") + "=" + $(this).val();
				} else if( eParentForm !== null ) {
					sTargetUrl = sTargetUrl + "?" + encodeURIComponent( $( eParentForm ).serialize() );
				}

				var jqxhr = $.get( sTargetUrl, function() {} )
				.done( function(data) {
					if( bRefreshView == true ) {
						$( eParentView ).replaceWith( data );
					}
				} )
				.fail( function() {} )
				.always( function() {} );

			} else if( sAction == "post" ) {
				if( $(this).hasClass("single") ) {
					sData = "{" + $(this).attr("id") + ":" + $(this).val() + "}";
				} else if( eParentForm !== null ) {
					sData = $( eParentForm ).serializeArray();
				}

				var jqxhr = $.post( sTargetUrl, sData, function(data) {} )
				.done( function(data) {
					if( bRefreshView == true ) {
						$( eParentView ).replaceWith( data );
					}
				} )
				.fail( function() {} )
				.always( function() {} );

			} else {
				console.log( "Error: bad action type" );
				return false;

			}
		} );
	</script>'
) );
