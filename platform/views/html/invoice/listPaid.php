<?php

/* * * *
 * Filename: invoiceListPaid.php
 * Created: 21/05/2014 by Renfors
 * Reference:
 * Description: View file for showing users paid invoices.
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
$oInvoiceDao = $oInvoiceEngine->getDao( 'Invoice' );
$aDataDict = $oInvoiceDao->getDataDict();

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

$oFreightRequest = clRegistry::get( 'clFreightRequest', PATH_MODULE . '/freightRequest/models' );

$oUserManager = clRegistry::get( 'clUserManager' );

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
	'invoiceTotalAmount' => array(
    'title' => ''
  ),
  'invoiceStatus' => array(
    'title' => ''
  )
) );

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oInvoiceDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 10
) );

// Fetch user invoices
$oInvoiceDao->sCriterias = null;
$oInvoiceDao->setCriterias( array(
	'unpaid' => array(
		'fields' => 'invoiceStatus',
		'value' => 'paid'
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
	'invoiceCreditDays',
	'invoiceLocked',
	'invoiceLockedDate',
	'invoiceLockedByUserId',
	'invoiceAuctionId',
	'invoiceFreightRequestId'
) );

// Pagination
$sPagination = $oPagination->render();

if( !empty($aInvoices) ) {
	clFactory::loadClassFile( 'clOutputHtmlGridTable' );
	$oOutputHtmlTable = new clOutputHtmlGridTable( $aDataDict );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() );

	$sShowUrl = $oRouter->getPath( 'userInvoiceShow' );
	$sPayUrl = '';

	foreach( $aInvoices as $entry ) {

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


    // Two rows - first main data - second details
    $row = array(
      'invoiceId' => $entry['invoiceNo'],
      'invoiceDate' => substr( $entry['invoiceDate'], 0, 10 ),
      'invoiceTotalAmount' =>  calculatePrice( $entry['invoiceTotalAmount'], $aPriceFormat ),
      'invoiceStatus' => '
				<span class="paymentStatus ' . $entry['invoiceStatus'] . '">
					' . _( $aDataDict['entInvoice']['invoiceStatus']['values'][$entry['invoiceStatus']] ) . '
				</span>'
    );
    $oOutputHtmlTable->addBodyEntry( $row, array(
      'class' => 'first'
    ) );

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

		$row = array(
			'invoiceId' => '
        <div class="info">
          <h6>' . _( 'Faktura' ) . '</h6>
          ' . $aDataDict['entInvoice']['invoiceType']['values'][$entry['invoiceType']] . '
        </div>
        <div class="freight">
          ' . $sFreight . '
        </div>
        <div class="postAuction">
          ' . $sInfo . '
        </div>',
			'invoiceStatus' => '
				<a href="' . $sShowUrl . '?invoiceId=' . $entry['invoiceId'] . '" class="popupLink" data-show-element="section.invoice"><i class="fas fa-file-invoice">&nbsp;</i>' . _( 'Show' ) . '</a>
				<a href="' . $sShowUrl . '?invoiceId=' . $entry['invoiceId'] . '&pdf=1" target="_blank"><i class="fas fa-file-pdf">&nbsp;</i>' . _( 'PDF' ) . '</a>'
		);
		$oOutputHtmlTable->addBodyEntry( $row, array(
      'class' => 'second'
    ) );
	}

  $oOutputHtmlTable->addPagination( $sPagination );

	$sOutput = $oOutputHtmlTable->render();
} else {

	$sOutput = '<strong>' . _('There are no items to show') . '</strong>';
}

echo '
	<div class="view invoiceList invoiceListPaid">
		<h1>', _( 'Invoice archive' ), '</h1>
		' . $sOutput . '
	</div>';
