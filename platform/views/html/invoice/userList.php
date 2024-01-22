<?php

$sOutput = '';

$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );

$oInvoiceDao = $oInvoiceEngine->getDao( 'Invoice' );
$aInvoiceDataDict = $oInvoiceDao->getDataDict();
$oInvoiceDao->aSorting = [
  'invoiceStatus' => 'ASC',
  'invoiceDate' => 'DESC'
];

$aInvoices = $oInvoiceEngine->readByUser_in_Invoice( $oUser->iId, array(
	'invoiceId',
	'invoiceNo',
	'invoiceUserId',
	'invoiceType',
	'invoiceDate',
	'invoiceDueDate',
	'invoiceStatus',
	'invoiceTotalAmount',
	'invoiceTotalVat',
	'invoiceCreditDays',
	'invoiceLocked',
	'invoiceLockedDate',
	'invoiceLockedByUserId',
	'invoiceAuctionId'
) );

if( !empty($aInvoices) ) {
  	clFactory::loadClassFile( 'clOutputHtmlGridTable' );
  	$oOutputHtmlTableUnpaid = new clOutputHtmlGridTable( $aInvoiceDataDict );
  	$oOutputHtmlTablePaid = new clOutputHtmlGridTable( $aInvoiceDataDict );
		$aTableDataDict = [
      'invoiceId' => [
        'title' => _( 'nr.' )
      ],
	    'invoiceControls' => [
	      'title' => ''
	    ],
      'invoiceDate' => [
        'title' => ''
      ],
  		'invoiceAmount' => [
  			'title' => ''
      ]
  	];
  	$oOutputHtmlTableUnpaid->setTableDataDict( $aTableDataDict );
  	$oOutputHtmlTablePaid->setTableDataDict( $aTableDataDict );

    // Get item information
		$aItems = $oInvoiceEngine->readByInvoice_in_InvoiceLine( arrayToSingle($aInvoices, null, 'invoiceId'), [
      'invoiceLineInvoiceId',
      'invoiceLineItemId'
    ] );

    if( !empty($aItems) ) {
      $aTempItems = [];
      $aItemsToInvoice = [];
      foreach( $aItems as $aItem ) {
        if( !empty($aItem['invoiceLineItemId']) ) {
        	$aTempItems[] = $aItem;
        	$aItemsToInvoice[ $aItem['invoiceLineInvoiceId'] ][] = $aItem['invoiceLineItemId'];
        }
      }
      $aItems = $aTempItems;

			// Get addresses and collect info
			$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
			$oBackEnd->setSource( 'entAuctionItem', 'itemId' );
			$oBackEnd->oDao->setCriterias( array(
				'itemId' => array(
					'fields' => 'itemId',
					'type' => 'in',
					'value' => arrayToSingle( $aItems, null, 'invoiceLineItemId' )
				)
			) );
			$aAddressToItem = arrayToSingle( $oBackEnd->read( [
				'itemId',
				'itemAddressId'
			] ), 'itemId', 'itemAddressId' );
			$oBackEnd->oDao->sCriterias = null;
			$aAddressToItem = array_filter( $aAddressToItem );

			if( !empty($aAddressToItem) ) {
				$oBackEnd->setSource( 'entAuctionAddress', 'addressId' );
				$oBackEnd->oDao->setCriterias( array(
					'addressId' => array(
						'fields' => 'addressId',
						'type' => 'in',
						'value' => $aAddressToItem
					)
				) );
				$aAddresses = valueToKey( 'addressId', $oBackEnd->read([
					'addressId',
					'addressFreightHelp',
					'addressFreightRequestLastDate'
				]) );
				$oBackEnd->oDao->sCriterias = null;
			}
    }

    // Assemble the table
  	$fTotalAmount = 0;
    // $sPayUrl = $oRouter->getPath( 'emptyPaymentEcsterPayCart' );
    $sPayUrl = $oRouter->getPath( 'userInvoicePaySinglePage' );
    $sShowUrl = $oRouter->getPath( 'userInvoiceShow' );
		$sFreightRequestUrl = $oRouter->getPath('userFreightRequest');
  	foreach( $aInvoices as $entry ) {
      $sInfo = $sFreightStatus = $sInvoiceFreightRequest = $sPaymentButton = '';
			$aRowsAttributes = [];

      // All this invoice's payments
  		$aInvoicePaymentData = arrayToSingle( $oInvoiceEngine->readByInvoice_in_InvoicePaymentLog($entry['invoiceId'], array('logAmount')), null, 'logAmount' ) ;
      $fSumRemaining = $entry['invoiceTotalAmount'] - array_sum( $aInvoicePaymentData );
      $fTotalAmount += $fSumRemaining;

      if( ($entry['invoiceType'] != 'credit') ) {
				$sStatus = $aInvoiceDataDict['entInvoice']['invoiceStatus']['values'][ $entry['invoiceStatus'] ];

        if( $fSumRemaining == 0 ) {
          $sPaymentButton = '<span class="paymentStatus button transparent disabled">' . _( 'Betald' ) . '</span>';
        } else {
          $sPaymentButton = '<a href="' . $sPayUrl . '?payInvoice=' . $entry['invoiceId'] . '" class="button transparent">' . _( 'Pay' ) . '</a>';
        }
      } else {
        if( $fSumRemaining == 0 ) {
          $sStatus = _( 'Utbetald' );
          $sPaymentButton = '<span class="paymentStatus button transparent disabled">' . _( 'Utbetald' ) . '</span>';
        } else {
          $sStatus = _( 'Till godo' );
					$sPaymentButton = '<span class="paymentStatus button transparent disabled">' . _( 'Till godo' ) . '</span>';
        }
      }

      // View links and post auction info
      if( !empty($aItemsToInvoice[ $entry['invoiceId'] ]) ) {
        $sInfo .= '
          <a href="?ajax=1&view=auction/itemPostAuctionInfo.php&itemId=' . implode( ',', $aItemsToInvoice[ $entry['invoiceId'] ] ) . '" class="popupLink button  transparent attention"><i class="fas fa-exclamation-circle">&nbsp;</i>' . _( 'Viktig info' ) . '</a>';
      }

      // Payment  before date
      if( !empty($entry['invoiceDueDate']) && ($entry['invoiceDueDate'] != '0000-00-00') ) {
				$sPaymentBeforeDate = $entry['invoiceDueDate'];
      } else {
      	$sPaymentBeforeDate = date( 'Y-m-d', (strtotime($entry['invoiceDate']) + ($entry['invoiceCreditDays']) * 86400) );
			}

      // Freight Request possible?
      $sFreightRequestBtn = '';
      if( !empty($aItemsToInvoice[ $entry['invoiceId'] ]) ) {
      	foreach( $aItemsToInvoice[ $entry['invoiceId'] ] as $iInvoiceItem ) {

      		if( !empty($aAddressToItem[ $iInvoiceItem ]) ) {
      			$aThisAddress = $aAddresses[ $aAddressToItem[$iInvoiceItem] ];

      			if( ($aThisAddress['addressFreightHelp'] != 'no') && (strtotime($aThisAddress['addressFreightRequestLastDate']) >= time() ) ) {
      				$sFreightRequestBtn = '<a href="' . $sFreightRequestUrl . '?invoiceNo=' . $entry['invoiceNo'] . '" class="button transparent"><i class="fas fa-truck-loading">&nbsp;</i>' . _( 'Fraktförfrågan' ) . '</a>';
      			}
      		}

      	}
      }

			// Two rows - info and buttons
      $rows = [
				'first' => [
					'invoiceId' => $entry['invoiceNo'],
					'invoiceControls' => $sInfo,
					'invoiceDate' => $sFreightRequestBtn,
					'invoiceAmount' => $sPaymentButton
				],
				'second' => [
					'invoiceId' => '',
					'invoiceControls' => '
						<a href="' . $sShowUrl . '?invoiceId=' . $entry['invoiceId'] . '" class="popupLink"><i class="fas fa-chevron-circle-right">&nbsp;</i>' . _( 'Visa' ) . '<span class="responsive desktop">&nbsp;' . _( 'faktura' ) . '</span></a>
						<a href="' . $sShowUrl . '?invoiceId=' . $entry['invoiceId'] . '&pdf=1" target="_blank"><i class="fas fa-chevron-circle-right">&nbsp;</i><span class="responsive desktop">' . _( 'Ladda ner' ) . '&nbsp;</span>' . _( 'PDF' ) . '</a>',
					'invoiceDate' => '
						<datetime>
							<date>' . $sPaymentBeforeDate . '</date>
						</datetime>
						<span class="' . $entry['invoiceStatus'] . ' invoiceStatus">' . $sStatus . '</span> |
						' . calculatePrice( $entry['invoiceTotalAmount'], INVOICE_PRICE_FORMAT ) . '
						' . ( !empty($sFreightStatus) ? ' | ' . $sFreightStatus : '' ),
					'invoiceAmount' => calculatePrice( $fSumRemaining, INVOICE_PRICE_FORMAT )
				]
      ];

			if( $entry['invoiceStatus'] == 'paid' ) {
  			$oOutputHtmlTablePaid->addBodyGroup( $rows, $aRowsAttributes );
			} else {
  			$oOutputHtmlTableUnpaid->addBodyGroup( $rows, $aRowsAttributes );
			}
    }

    if( $fTotalAmount > 0 ) {
      $row = array(
        'invoiceId' => '
			    <h1>' . _('Obetalda fakturor') . '</h1>',
				'invoiceControls' => '',
				'invoiceDate' => _( 'Totalt' ),
        'invoiceAmount' => '
					<div class="invoiceRemainingAmount">' . calculatePrice( $fTotalAmount, INVOICE_PRICE_FORMAT ) . '</div>
          <a href="' . $sPayUrl . '" class="button submit">' . _( 'Pay' ) . '</a>'
      );

			$sTitle = '';

  		$oOutputHtmlTableUnpaid->addFooterEntry( $row );
    }

    $sUnpaidTable = $oOutputHtmlTableUnpaid->render();
    $sPaidTable = $oOutputHtmlTablePaid->render();

    if( !empty($sUnpaidTable) ) {
	    $sOutput .= $sUnpaidTable;
	  }
    if( !empty($sPaidTable) ) {
			$sOutput .= '<h1>' . _( 'Betalda fakturor' ) . '</h1>';
	    $sOutput .= $sPaidTable;
	  }
}

$oTemplate->addLink( array(
	'key' => 'invoiceStyle',
	'href' => '/css/index.php?include=views/html/invoice/'
) );

echo '
  <div class="view invoice userList">
    ' . $sOutput . '
  </div>';
