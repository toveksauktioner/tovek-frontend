<?php

// Ecster Pay javascript is best suited loaded in HEAD so for now this script is not meant for ajax load

// 1. Collect invoices to pay (include credit invoice if total is bigger than 0)
// 2. Create cart in Ecster and get code
// 2b. Check for changes in invoiceOrderId - payment is tried on other location - stop this one

function endScript() {
    echo '
      <script>
        $("#popupLinkBox a.popupClose").click();
      </script>';
    exit;
}

// An user must exist. Otherwise close popup and exit
if( empty($oUser->iId) ) {
  endScript();
}

$sOutput = '';
$sOutputCart = '';

$oPaymentSvea = clRegistry::get( 'clPaymentSvea', PATH_MODULE . '/payment/models' );
$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
$oInvoiceDao = $oInvoiceEngine->getDao( 'Invoice' );
$aInvoiceDataDict = $oInvoiceDao->getDataDict();


$oInvoiceDao->setCriterias( [
  'unpaid' => [
    'type' => 'in',
    'value' => [
      'unpaid',
      'partpaid'
    ],
    'fields' => 'invoiceStatus'
  ],
  'userId' => [
    'type' => '=',
    'value' => $oUser->iId,
    'fields' => 'invoiceUserId'
  ]
] );

$aReadFields = [
	'invoiceId',
	'invoiceNo',
  'invoiceInformation',
	'invoiceStatus',
	'invoiceTotalAmount',
	'invoiceTotalVat',
  'invoiceType',
  'invoiceOrderId'
];

if( !empty($_GET['payInvoice']) ) {
  $aInvoices = valueToKey( 'invoiceId', $oInvoiceEngine->read('Invoice', $aReadFields, $_GET['payInvoice']) );
} else {
  $aInvoices = valueToKey( 'invoiceId', $oInvoiceEngine->read('Invoice', $aReadFields) );
}

if( !empty($aInvoices) ) {
  $fTotalAmount = 0;
  $aOrderInvoices = [];
  $aCancelOrders = [];
  $aCart = [
    'items' => []
  ];

  clFactory::loadClassFile( 'clOutputHtmlGridTable' );
  $oOutputHtmlTable = new clOutputHtmlGridTable( $aInvoiceDataDict );
  $oOutputHtmlTable->setTableDataDict( [
    'invoiceNo' => [
      'title' => _( 'Nr.' )
    ],
    'invoiceRemainingAmount' => [
      'title' => _( 'Att betala' )
    ]
  ] );

  foreach($aInvoices as $iInvoiceId => $aInvoice ) {
    $aInvoicePaymentData = arrayToSingle( $oInvoiceEngine->readByInvoice_in_InvoicePaymentLog($aInvoice['invoiceId'], ['logAmount']), null, 'logAmount' ) ;
    $fSumRemaining = $aInvoice['invoiceTotalAmount'] - array_sum( $aInvoicePaymentData );
    $fTotalAmount += $fSumRemaining;

    $aOrderInvoices[] = $aInvoice['invoiceId'];
    if( !empty($aInvoice['invoiceOrderId']) ) $aCancelOrders[] = $aInvoice['invoiceOrderId'];

    $aCart['items'][] = [
      'articleNumber' => $aInvoice['invoiceId'],
      'name' => ( ($aInvoice['invoiceType'] == 'credit') ? _('Kreditfaktura') : _('Faktura') ) . ' ' . $aInvoice['invoiceNo'],
      'quantity' => 100,
      'unitPrice' => (int)( $fSumRemaining * 100 ),
    ];

    $oOutputHtmlTable->addBodyEntry( [
      'invoiceNo' => ( ($aInvoice['invoiceType'] == 'credit') ? _('Kreditfaktura') : _('Faktura') ) . ' ' . $aInvoices[ $iInvoiceId ]['invoiceNo'],
      'invoiceRemainingAmount' => calculatePrice( $fSumRemaining, INVOICE_PRICE_FORMAT )
    ] );
  }

  // Initialize Svea cart
  if( $fTotalAmount > 0 ) {

    // Create invoice order and connect invoices to it
    $iInvoiceOrderId = $oInvoiceEngine->create( 'InvoiceOrder', [
      'invoiceOrderTotalAmount' => $fTotalAmount,
      'invoiceOrderUserId' => $oUser->iId,
      'invoiceOrderPaymentId' => 7
    ] );

    foreach( $aOrderInvoices as $iInvoiceId ) {
      $oInvoiceEngine->update( 'Invoice', $iInvoiceId, [
          'invoiceOrderId' => $iInvoiceOrderId
      ] );
    }

    // Cancel all orders with overlapping invoices
    if( !empty($aCancelOrders) ) {
      foreach( $aCancelOrders as $iCancelOrderId ) {
        $oInvoiceEngine->setInvoiceOrderCancelled_in_InvoiceOrder( $iCancelOrderId );
      }
    }

    $oPaymentSvea->init( $iInvoiceOrderId );

    $aSveaResponse = $oPaymentSvea->sveaGetCart( $aCart, $oUser->readData('userType') );

    if( !empty($aSveaResponse) ) {
    	if( $aSveaResponse['result'] == 'success' ) {
    		$sOutputCart = $aSveaResponse['response']['Gui']['Snippet'];
    	} else {
    		$sOutputCart = '
    			<p>Om betallösningen inte laddas - maila <a href="mailto:teknik@tovek.se">teknik@tovek.se</a> och ange nedanstående information.</p>
    			<code><strong>Fakturor</strong>: ' . implode( ', ', arrayToSingle($aInvoices, null, 'invoiceNo') ) . '</code>
    			<code><strong>Fel</strong>: ' . $aSveaResponse['error'] . ' - ' . $aSveaResponse['errorDetailed'] . '</code>';
    	}
    } else {
		$sOutputCart = '
			<p>Om betallösningen inte laddas - maila <a href="mailto:teknik@tovek.se">teknik@tovek.se</a> och ange nedanstående information.</p>
			<code><strong>Fakturor</strong>: ' . implode( ', ', arrayToSingle($aInvoices, null, 'invoiceNo') ) . '</code>';
    }

	$oOutputHtmlTable->addFooterEntry( [
      'invoiceNo' => _( 'Totalt' ),
      'invoiceRemainingAmount' => calculatePrice( $fTotalAmount, INVOICE_PRICE_FORMAT )
    ] );
    $sOutput .= $oOutputHtmlTable->render();

  } else {
    endScript();
  }

} else {
  endScript();
}

// Clear notification
if( !empty($oNotification->aNotifications) ) $oNotification->aNotifications = null;

echo '
  <div class="view payment cartSvea" data-order-id="' . $iInvoiceOrderId . '">
    <div class="invoiceList">
      <h1>' . _( 'Betala fakturor' ) . '</h1>
      ' . $sOutput . '
    </div>
    <div id="sveaCart">
      ' . $sOutputCart . '
    </div>
  </div>';
