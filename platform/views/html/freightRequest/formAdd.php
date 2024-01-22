<?php

$sOutput = '';
$aErr = [];

$oApi = clRegistry::get( 'clApi', PATH_MODULE . '/api/models' );
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
  $oAuctionDao = $oAuctionEngine->getDao( 'Auction' );
$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
  $oInvoiceDao = $oInvoiceEngine->getDao( 'Invoice' );
  $aInvoiceDataDict = $oInvoiceDao->aDataDict;
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );

// Get data dict via API
$aFreightRequestDataDict = $oApi->call( '/desc/freightRequest', 'GET' );

// Get current addresses
$oAuctionDao->setCriterias( [
  'addressFreightHelp' => [
    'type' => 'not',
    'value' => 'no',
    'fields' => 'addressFreightHelp'
  ],
  'addressFreightRequestLastDate' => [
    'type' => '>=',
    'value' => date( 'Y-m-d' ),
    'fields' => 'addressFreightRequestLastDate'
  ]
] );
$aAddresses = $oAuctionEngine->readAuctionAddress_in_Auction();

// Get users invoice line items
if( !empty($aAddresses) ) {
  $aItems = $oAuctionEngine->readByAuction_in_AuctionItem( null, arrayToSingle($aAddresses, null, 'addressPartId'), [
    'itemId',
    'itemSortNo',
    'itemTitle',
    'itemAddressId',
    'itemLocation'
  ] );
  $aItems = valueToKey( 'itemId', $aItems );

  // Get invoice lines that are connected to the items and user
  $aInvoiceLines = $oInvoiceEngine->readByParams_in_InvoiceLine( [
    'invoiceLineItemId' => array_keys( $aItems )
  ] );

  if( !empty($aInvoiceLines) ) {
    $aInvoiceIds = arrayToSingle( $aInvoiceLines, null, 'invoiceLineInvoiceId' );
    $aInvoiceLines =  valueToKey( 'invoiceLineId', $aInvoiceLines );

    $oInvoiceDao->setCriterias( [
      'user' => [
        'type' => '=',
        'value' => $_SESSION['userId'],
        'fields' => 'invoiceUserId'
      ]
    ] );
    $aInvoices = valueToKey( 'invoiceId', $oInvoiceEngine->read('Invoice', null, $aInvoiceIds) );

    // Assemble item list grouped by location
    $aLinesSelectable = []; 
    if( !empty($aInvoiceLines) ) {
      foreach( $aInvoiceLines as $key => $aInvoiceLine ) {
        // Remove invoice line if it not on users invoice
        if( !array_key_exists($aInvoiceLine['invoiceLineInvoiceId'], $aInvoices) ) {
          unset( $aInvoiceLines[$key] );
          continue;
        }

        $aItem = $aItems[ $aInvoiceLine['invoiceLineItemId'] ];

        $aLinesSelectable[ $aItem['itemLocation'] ][ $aInvoiceLine['invoiceLineId'] ] = [
          'lineId' => $aInvoiceLine['invoiceLineId'],
          'invoiceLineItemId' => $aInvoiceLine['invoiceLineItemId'],
          'invoiceId' => $aInvoiceLine['invoiceLineInvoiceId'],
          'title' => $aInvoiceLine['invoiceLineTitle']
        ];
      }
    }
  }
}

if( !empty($aInvoices) ) {

  clFactory::loadClassFile( 'clOutputHtmlGridTable' );
  $oOutputHtmlTable = new clOutputHtmlGridTable( $aInvoiceDataDict, [
    'rowGridStyle' => '2em 4em auto 4em',
    'attributes' => [
      'class' => 'transparent nonSticky'
    ]
  ] );
  $oOutputHtmlTable->setTableDataDict( [
    'invoiceSelected' => [
      'title' => '' //'<input type="checkbox" class="checkAllAuto" data-check-class="invoiceSelectedCheckbox" checked="checked">'
    ],
    'invoiceNo' => [
      'title' => _( 'Faktura' )
    ],
  	'invoiceInformation' => [
      'title' => _( 'Information' )
    ],
  	'invoiceStatus' => [
      'title' => _( 'Betald' )
    ]
  ] );

  foreach( $aInvoices as $key => $aInvoice ) {
    $aFreightRequestByInvoice = $oApi->call( '/freightRequestInvoice/', 'GET', [
      'invoiceId' => $aInvoice['invoiceId']
    ] );
    if( ($aFreightRequestByInvoice['result'] == 'success') && !empty($aFreightRequestByInvoice['data']) ) {
      unset( $aInvoices[$key] );
      continue;
    } 

    $row = [
      'invoiceSelected' => '<input type="checkbox" value="' . $aInvoice['invoiceNo'] . '"  name="invoiceNo[' . $aInvoice['invoiceId'] . ']" class="invoiceSelectedCheckbox invoice' . $aInvoice['invoiceNo'] . '">',
      'invoiceNo' => $aInvoice['invoiceNo'],
      'invoiceInformation' => strip_tags( $aInvoice['invoiceInformation'] ),
      'invoiceStatus' => $aInvoiceDataDict['entInvoice']['invoiceStatus']['icons'][ $aInvoice['invoiceStatus'] ]
    ];
    $oOutputHtmlTable->addBodyEntry( $row, [
      'data-invoice-id' => $aInvoice['invoiceId'],
      // 'class' => implode( ' ', $aRowClass )
    ] );

  }

  $sOutput = $oOutputHtmlTable->render();

  // Add line selectors
  $aLineSelectorPerLocation = [];
  if( !empty($aLinesSelectable) ) {
    foreach( $aLinesSelectable as $sLocation => $aLines ) {
      $sThisLocationOutput = '';

      foreach( $aLines as $aLine ) {
        if( empty($aInvoices[ $aLine['invoiceId'] ]) ) continue;

        $aThisInvoice = $aInvoices[ $aLine['invoiceId'] ];
        $sThisLocationOutput .= '
          <li class="invoiceLine' . $aThisInvoice['invoiceNo'] . '">
            <input type="checkbox" name="invoiceLineId[' . $sLocation . '][' . $aLine['lineId'] . ']" id="invoiceLineId' . $aLine['lineId'] . '" class="invoiceLineSelectedCheckbox" value="' . $aLine['lineId'] . '" data-invoice-no="' . $aThisInvoice['invoiceNo'] . '">
            <label for="invoiceLineId' . $aLine['lineId'] . '">' . $aLine['title'] . '</label>
          </li>';
      }

      if( !empty($sThisLocationOutput) ) {
        $aLineSelectorPerLocation[] = '
          <ul>
            <small>' . $sLocation . '</small>
            ' . $sThisLocationOutput . '
          </ul>';
      } 
    }

    $sOutput .= '
      <div class="invoiceLineSelector">
        ' . implode( '', $aLineSelectorPerLocation ) . '
        <br class="clear">
      </div>';
  }

}

// Get addresses
$aResponse = $oApi->call( '/userAddresses/' . $_SESSION['userId'], 'GET' );
$aAddresses = ( !empty($aResponse['data']) ? $aResponse['data'] : null );

if( !empty($aAddresses) ) {
  foreach( $aAddresses as $entry ) {
    $aAddressData = [];
    $aAddressData[] = $entry['addressName'];
    if( !empty($entry['addressContactPerson']) ) $aAddressData[] = $entry['addressContactPerson'];
    $aAddressData[] = $entry['addressAddress'] . ', ' . $entry['addressZipCode'] . ' ' . $entry['addressCity'];

    $aAddressSelect[ $entry['addressId'] ] = implode( ', ', $aAddressData );
  }
}

// Form handling
if( !empty($_POST['frmFreightRequestAdd']) ) {
  $aRequests = [];

  if( !empty($_POST['invoiceLineId']) ) {
    foreach( $_POST['invoiceLineId'] as $sLocation => $aLines ) {
      $aRequestData = [];

      foreach( $aLines as $iLineId ) {
        $aThisLine = $aLinesSelectable[ $sLocation ][ $iLineId ];

        $aRequestData[] = [
          'invoiceLineId' => $iLineId,
          'invoiceId' => $aThisLine['invoiceId'],
          'itemId' => $aThisLine['invoiceLineItemId']
        ];
      }

      $aRequests[ $sLocation ] = [
        'requestMessage' => $_POST['requestMessage'],
        'requestData' => json_encode( $aRequestData ),
        'requestStatus' => 'requested',
        'requestUserId' => $_SESSION['userId'],
        'requestReceiverAddressId' => $_POST['requestReceiverAddressId']
      ];
    }
  }

  if( !empty($aRequests) ) {
    foreach( $aRequests as $aRequest ) {
      $aResponse = $oApi->call( '/freightRequest/', 'PUT', [
        'data' => json_encode( $aRequest )
      ] );
      echo '<pre>' . print_r( $aResponse, true ) . '</pre>';
    }
  }

}

// Form
$oOutputHtmlForm->init( $aFreightRequestDataDict, [
  'attributes' => [
    'id' => 'frmFreightRequestAdd',
    'class' => 'newForm framed'
  ],
  'errors' => $aErr,
  'method' => 'post',
  'buttons' => [
    'reset' => [
      'content' => _( 'Ångra' ),
      'attributes' => [
        'class' => 'btnReset'
      ]
    ],
    'submit' => [
      'content' => _( 'Skicka' ),
      'attributes' => [
        'class' => 'btnSubmit'
      ]
    ],
    'button' => [
      'content' => _( 'Be om fraktförslag' ),
      'attributes' => [
        'class' => 'btnToggle'
      ]
    ]
  ]
] );

$oOutputHtmlForm->setFormDataDict( [
  'requestReceiverAddressId' => [
    'type' => 'array',
    'appearance' => 'full',
    'values' => $aAddressSelect
  ],
  'requestMessage' => [
    'appearance' => 'full'
  ],
  // 'requestReferral' => [
  //   'type' => 'hidden'
  // ],
  'frmFreightRequestAdd' => [
    'type' => 'hidden',
    'value' => true
  ]
] );

// Put the invoice list in the form
$sFormOutput = $oOutputHtmlForm->renderForm(
  $oOutputHtmlForm->renderErrors() .
  $sOutput .
  $oOutputHtmlForm->renderGroups() .
  $oOutputHtmlForm->renderFields() .
  $oOutputHtmlForm->renderButtons()
);

// Output
if( !empty($sOutput) && !empty($aInvoices) ) {
  echo '
    <div class="view freightRequest formAdd">
      <h1>' . _( 'Be om fraktförslag' ) . '</h1>
      ' . $sFormOutput . '
    </div>
    <script>

      $(".freightRequest.formAdd .gridTable .body .dataRow").click( function() {
        $( this ).find(".invoiceSelectedCheckbox").trigger( "click" );
      } );
      $(".freightRequest.formAdd .gridTable .body .dataRow .invoiceSelectedCheckbox").click( function(ev) {
        ev.stopPropagation();
      } );

      $( function() {

        $(".invoiceSelectedCheckbox").click( function() {
          let invoiceNo = $( this ).val();
          $(".invoiceLine" + invoiceNo + " .invoiceLineSelectedCheckbox").prop( "checked", $(this).prop("checked") );
        } );

        $(".invoiceLineSelectedCheckbox").click( function() {
          let invoiceNo = $( this ).data( "invoice-no" );
          if( $(this).is(":checked") ) {
            $(".invoiceSelectedCheckbox.invoice" + invoiceNo).prop( "checked", true );
          }
        } );

        $("#frmFreightRequestAdd button.btnToggle").click( function() {
          $("#frmFreightRequestAdd button.btnSubmit").show();
          $("#frmFreightRequestAdd button.btnReset").show();
          $("#frmFreightRequestAdd .field, #frmFreightRequestAdd fieldset, #frmFreightRequestAdd .invoiceLineSelector").show();
          $( this ).hide();
        } );

        $("#frmFreightRequestAdd button.btnReset").click( function() {
          $("#frmFreightRequestAdd button.btnSubmit").hide();
          $("#frmFreightRequestAdd .field, #frmFreightRequestAdd fieldset, #frmFreightRequestAdd .invoiceLineSelector").hide();
          $( this ).hide();
          $("#frmFreightRequestAdd button.btnToggle").show();
        } );

        $("#frmFreightRequestAdd button.btnSubmit").hide();
        $("#frmFreightRequestAdd button.btnReset").hide();
        $("#frmFreightRequestAdd .field, #frmFreightRequestAdd fieldset, #frmFreightRequestAdd .invoiceLineSelector").hide();
      } );


    </script>';
}

// Pre select invoice 
if( !empty($_GET['invoiceNo']) ) {
  echo '
    <script>
      $( function() {
        console.log(' . $_GET['invoiceNo'] . ');
        var somethingSelected = false;

        $(".invoiceSelectedCheckbox").each( function() {
          if( $(this).prop("value") == ' . $_GET['invoiceNo'] . ' ) {
            somethingSelected = true;
            $( this ).trigger( "click" );
          }
        } );

        if( somethingSelected ) {
          $("#frmFreightRequestAdd button.btnToggle").click();
        }
      } );
    </script>';
}
