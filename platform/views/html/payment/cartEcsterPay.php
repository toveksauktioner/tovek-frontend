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

$oPaymentEcsterPay = clRegistry::get( 'clPaymentEcsterPay', PATH_MODULE . '/payment/models' );
$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
$oInvoiceDao = $oInvoiceEngine->getDao( 'Invoice' );
$aInvoiceDataDict = $oInvoiceDao->getDataDict();


// Check order request
if( !empty($_GET['checkOrderId']) ) {
  $aReturnData = [];
  $aReturnData['status'] = $oInvoiceEngine->getInvoiceOrderStatus_in_InvoiceOrder( $_GET['checkOrderId'] );

  switch( $aReturnData['status'] ) {
    case 'cancelled':
      $aReturnData['html'] = '
        <div class="message cancelled">
          <h2>' . _( 'Betalning har inletts i annat fönster/enhet' ) . '</h2>
          <p>Du kommer strax skickas tillbaka till FAKTUROR.</p>
        </div>';
      break;

    case 'completed':
      $aReturnData['html'] = '
        <div class="message completed">
          <h2>' . _( 'Betalning genomförd' ) . '</h2>
          <p>Du kommer strax skickas tillbaka till FAKTUROR.</p>
        </div>';
      break;

    default:
      // Do nothing
  }

  echo json_encode( $aReturnData );
  exit;
}


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
    'currency' => 'SEK',
    'rows' => []
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

    $aCart['rows'][] = [
      'partNumber' => $aInvoice['invoiceId'],
      'name' => ( ($aInvoice['invoiceType'] == 'credit') ? _('Kreditfaktura') : _('Faktura') ) . ' ' . $aInvoice['invoiceNo'],
      'quantity' => 1,
      'unitAmount' => (int)( $fSumRemaining * 100 ),
    ];

    $oOutputHtmlTable->addBodyEntry( [
      'invoiceNo' => ( ($aInvoice['invoiceType'] == 'credit') ? _('Kreditfaktura') : _('Faktura') ) . ' ' . $aInvoices[ $iInvoiceId ]['invoiceNo'],
      'invoiceRemainingAmount' => calculatePrice( $fSumRemaining, INVOICE_PRICE_FORMAT )
    ] );
  }

  // Initialize Ecster Pay cart
  if( $fTotalAmount > 0 ) {
    $aCart['amount'] = (int)( $fTotalAmount * 100 );

    // Create invoice order and connect invoices to it
    $iInvoiceOrderId = $oInvoiceEngine->create( 'InvoiceOrder', [
      'invoiceOrderTotalAmount' => $fTotalAmount,
      'invoiceOrderUserId' => $oUser->iId,
      'invoiceOrderPaymentId' => 6
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

    $oPaymentEcsterPay->init( $iInvoiceOrderId );
    $aEcsterPayResponse = $oPaymentEcsterPay->ecsterGetCart( $aCart, $oUser->readData('userType') );

    if( !empty($aEcsterPayResponse['checkoutCart']['key']) ) $sEcsterPayCartKey = $aEcsterPayResponse['checkoutCart']['key'];

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
  <div class="view payment cartEcsterPay" data-order-id="' . $iInvoiceOrderId . '">
    <div class="invoiceList">
      <h1>' . _( 'Betala fakturor' ) . '</h1>
      ' . $sOutput . '
    </div>
    <div id="ecster-pay-ctr">
      <div id="EcsterPayLoading" style="text-align: center;">
        Om betallösningen inte laddas - maila <a href="mailto:teknik@tovek.se">teknik@tovek.se</a> vilka fakturor det gäller.
      </div>
    </div>
  </div>';

if( !empty($iInvoiceOrderId) ) {
  // Check order id for payment initialized in another window
  echo '
    <script>
      function checkOrderStatus() {
        var currentWindowOrderId = $(".view.payment.cartEcsterPay").data( "order-id" );

        $.get( "", {
          ajax: 1,
          view: "payment/cartEcsterPay.php",
          checkOrderId: ' . $iInvoiceOrderId . '
        }, function(returnData) {
          returnData = JSON.parse( returnData );

          if( currentWindowOrderId == ' . $iInvoiceOrderId . ' ) {
            // Make sure the current window loaded is for this order.

            switch( returnData.status ) {
              case "cancelled":
              case "completed":
                clearInterval( checkStatusInterval );
                $("#ecster-pay-ctr").remove();
                $(".view.payment.cartEcsterPay").append( returnData.html );
                setTimeout( function() {
                  location.href = "' . $oRouter->getPath('userInvoiceList') . '";
                }, 5000 );
                break;

              default:
                // Do nothing
            }

          } else {
            // This order is not the current window.
            clearInterval( checkStatusInterval );
          }

        } );
      }

      var checkStatusInterval = setInterval( checkOrderStatus, 5000 );
      $( document ).on( "click", "#popupLinkBox a.popupClose", function(ev) {
        clearInterval( checkStatusInterval );
      } );
    </script>';
}

if( !empty($sEcsterPayCartKey) ) {
  $oTemplate->addScript( [
  	'key' => 'jsEcsterPayLibrary',
  	'src' => ECSTER_JS_LIBRARY
  ] );

  echo '
    <script>
      function ecsterPayInit() {
        EcsterPay.start( {
          cartKey: "' . $sEcsterPayCartKey . '",
          shopTermsUrl: "' . ECSTER_TOVEK_TERMS_URL . '",
          showPaymentResult: false,
          onPaymentSuccess: function(ecsterData) {
            $.post( "' . ECSTER_CALLBACK_URL . '", {
              functionCalled: "onPaymentSuccess",
              ecsterData: ecsterData
            }, function() {
              checkOrderStatus();
            } );
          },
          onPaymentFailure: function(ecsterData) {
            $.post( "' . ECSTER_CALLBACK_URL . '", {
              functionCalled: "onPaymentFailure",
              ecsterFailureData: ecsterData
            }, function() {
              // Do nothing
            } );
          },
          onPaymentDenied: function(data) {
            $.post( "' . ECSTER_CALLBACK_URL . '", {
              functionCalled: "onPaymentDenied",
              ecsterDeniedData: ecsterData
            }, function() {
              // Do nothing
            } );
          },
          onCheckoutStartSuccess: function(data) {
            $("#EcsterPayLoading").remove();
          }
        } );
      }

      $( function() {
        ecsterPayInit();
      } );
    </script>';
}
