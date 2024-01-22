<?php

if( empty($_GET['requestId']) ) return;


$sOutput = '';

$oApi = clRegistry::get( 'clApi', PATH_MODULE . '/api/models' );
$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
$oSystemText = clRegistry::get( 'clSystemText', PATH_MODULE . '/systemText/models' );

// Get data dict via API
$aFreightRequestDataDict = $oApi->call( '/desc/freightRequest', 'GET' );
$aInvoiceDataDict = $oApi->call( '/desc/invoice', 'GET' );

// Get request data
$aResponse = $oApi->call( '/freightRequest/' . $_GET['requestId'], 'GET', [
	'userId' => $_SESSION['userId'],
	'full' => true
] );
$aRequestData = ( !empty($aResponse['data']) ? $aResponse['data']['freightRequest'] : null );
if( !empty($aRequestData) ) {
	$aParcelData = json_decode( $aRequestData['requestParcelProperties'], true );
}
$aConnectedInvoices = ( !empty($aResponse['data']) ? $aResponse['data']['invoices'] : null );

if( !empty($_GET['ajax']) && !empty($_GET['changeStatus']) ) {

	$aReturnData = [
		'result' => 'failure',
		'error' => 'Insufficcient data',
		'indata' => $_GET
	];

	switch( $_GET['changeStatus'] ) {
		case 'accept':
			$aUpdateStatus = $oApi->call( '/freightRequest/' . $_GET['requestId'], 'POST', [
				'data' => json_encode([
					'requestStatus' => 'accepted'
				])
			] );

			if( $aUpdateStatus['result'] == 'success' ) {
				$aAcceptedText = current( $oSystemText->read(null, 'USER_FREIGHT_REQUEST_ACCEPTED_BY_USER') );
				
				if( !empty($aRequestData['requestCost']) ) {
					// Create cost invoice

					$aResponse = $oApi->call( '/invoices/' . $_SESSION['userId'], 'GET', [
						'invoiceId' => $aConnectedInvoices
					] );

					if( !empty($aResponse['data']) ) {
						$aInvoiceIds = arrayToSingle( $aResponse['data'], null, 'invoiceId' );
						$aInvoiceNos = arrayToSingle( $aResponse['data'], null, 'invoiceNo' );
						$aInvoiceData = current( $aResponse['data'] );

						$sFreightStorageInfoTextId = ( ($aRequestData['requestTransporter'] == 'PostNord') ? 'FREIGHT_STORAGE_INFO_POSTNORD' : 'FREIGHT_STORAGE_INFO_SCHENKER' );
						$sFreightStorageInfo = current( current($oSystemText->read('systemTextMessage', $sFreightStorageInfoTextId)) );

						$aParcelInfo = [];
						if( !empty($aParcelData) ) {
							foreach( $aParcelData as $aParcel ) {
								$aParcelInfo[] = $aParcel['copies'] . ' st ' . $aFreightRequestDataDict['definitions']['FREIGHT_UNIFAUN_PACKAGE_CODES'][ $aRequestData['requestTransporter'] ][ $aParcel['packageCode'] ] . ' á ' . $aParcel['weight'] . ' kg';
							}
						}

						$aCostInvoiceData = [
							'invoiceType' 						=> 'invoice',
							'invoiceInformation'			=> _( 'Frakt för faktura nr' ) . ': ' . implode( ', ', $aInvoiceNos ) . $sFreightStorageInfo,
							'invoiceFirstname' 				=> $aInvoiceData['invoiceFirstname'],
							'invoiceSurname' 					=> $aInvoiceData['invoiceSurname'],
							'invoiceCompanyName' 			=> $aInvoiceData['invoiceCompanyName'],
							'invoiceAddress' 					=> $aInvoiceData['invoiceAddress'],
							'invoiceZipCode' 					=> $aInvoiceData['invoiceZipCode'],
							'invoiceCity' 						=> $aInvoiceData['invoiceCity'],
							'invoiceCountryCode' 			=> $aInvoiceData['invoiceCountryCode'],
							'invoiceUserId' 					=> $aInvoiceData['invoiceUserId'],
							'invoiceFee'							=> 0,
							'invoiceLateInterest'			=> $aInvoiceDataDict['definitions']['INVOICE_DEFAULT_LATE_INTEREST'],
							'invoiceCreditDays'				=> $aInvoiceDataDict['definitions']['INVOICE_DEFAULT_CREDIT_DAYS'],
							'invoiceDate'							=> date( 'Y-m-d' ),
							'invoiceFreightRequestId' => $_GET['requestId'],
							'invoiceLines'								=> [
								0 => [
								'invoiceLineTitle' 					=> implode( ' | ', $aParcelInfo ),
								'invoiceLineQuantity'				=> 1,
								'invoiceLinePrice'					=> $aRequestData['requestCost'],
								'invoiceLineVatValue'				=> $aInvoiceDataDict['definitions']['INVOICE_DEFAULT_VAT'],
								'invoiceLineAccountingCode'	=> $aInvoiceDataDict['definitions']['INVOICE_FREIGHT_LINE_ACCOUNTING_CODE'],
								'invoiceLineUserId' 				=> $aInvoiceData['invoiceUserId']
								]
							]
						];

						// Update the parent invoice with info that it should be shipped
						// This is moved to backend so it is done in the final step
						// foreach( $aResponse['data'] as $aInvoice ) {
						// 	$aNoteResponse = $oApi->call( '/invoice/' . $aInvoice['invoiceId'], 'POST', [
						// 		'invoiceNotes' => ( !empty($aInvoice['invoiceNotes']) ? $aInvoice['invoiceNotes'] . ' ' : '' ) . _( 'Ska fraktas' )
						// 	] );
						// }

						// Create invoice and lines
						$aCreateResponse = $oApi->call( '/invoiceQueue/', 'PUT', [
							'data' => json_encode($aCostInvoiceData)
						] );

						if( ($aCreateResponse['result'] == 'success') ) {
							$aReturnData = [
								'result' => 'success',
								'resultHtml' => $aAcceptedText['systemTextMessage'],
								'responeRaw' => $aCreateResponse
							];

						} else {
							$aReturnData['error'] = $aCreateResponse['error'];
						}

					}
				}

			} else {
				$aReturnData = [
					'result' => 'failure',
					'error' => $aUpdateStatus['error']
				];
			}
			break;

		case 'decline':
			$aUpdateStatus = $oApi->call( '/freightRequest/' . $_GET['requestId'], 'POST', [
				'data' => json_encode([
					'requestStatus' => 'declined'
				])
			] );

			if( $aUpdateStatus['result'] == 'success' ) {
  			$aDeclinedText = current( $oSystemText->read(null, 'USER_FREIGHT_REQUEST_DECLINED_BY_USER') );
				
				$aReturnData = [
					'result' => 'success',
					'resultHtml' => $aDeclinedText['systemTextMessage']
				];

			} else {
				$aReturnData = [
					'result' => 'failure',
					'error' => $aUpdateStatus['error']
				];
			}
			break;
	}

	echo json_encode( $aReturnData );
	return;

}


$aReceiverAddresses = [];

// Get addresses
$aResponse = $oApi->call( '/userAddresses/' . $_SESSION['userId'], 'GET' );
$aUserAddress = ( !empty($aResponse['data']) ? $aResponse['data'] : null );
$aUserAddress = valueToKey( 'addressId', $aUserAddress );

if( !empty($aRequestData ) ) {

	if( !empty($aRequestData['requestData']) ) {
		$aData = json_decode( $aRequestData['requestData'], true );

		$aInvoiceLines = $oInvoiceEngine->read( 'InvoiceLine', [
			'invoiceLineTitle',
			'invoiceLineItemId'
		], arrayToSingle($aData, null, 'invoiceLineId') );
	}

	if( !empty($aInvoiceLines) ) {

	  clFactory::loadClassFile( 'clOutputHtmlGridTable' );
	  $oOutputHtmlTable = new clOutputHtmlGridTable( $aFreightRequestDataDict['entFreightRequest'] );
	  $oOutputHtmlTable->setTableDataDict( [
	    'lineInvoiceTitle' => [
	      'title' => _( 'Objekt' )
	    ]
	  ] );

	  foreach( $aInvoiceLines as $aInvoiceLine ) {
	    $row = [
	      'lineInvoiceTitle' => $aInvoiceLine['invoiceLineTitle']
	    ];
	    $oOutputHtmlTable->addBodyEntry( $row );
	  }

	  $sOutput = $oOutputHtmlTable->render();

	}


  // Addresses
  $sSenderAddress = ( !empty($aRequestData['requestSenderId']) ? $aFreightRequestDataDict['definitions']['FREIGHT_UNIFAUN_SENDER_ADDRESSES'][ $aRequestData['requestSenderId'] ] : '' );

  $aReceiverAddress = [];
  if( !empty($aRequestData['requestReceiverAddressLiteral']) ) {
    $aReceiverAddress = json_decode( $aRequestData['requestReceiverAddressLiteral'], true );

  } else if( !empty($aUserAddress[ $aRequestData['requestReceiverAddressId'] ]) ) {

    $aReceiverAddress = $aUserAddress[ $aRequestData['requestReceiverAddressId'] ];
  }

  $sReceiverAddress = '';
  if( !empty($aReceiverAddress) ) {
	  $sReceiverAddress = implode( '<br />', [
	    $aReceiverAddress['addressName'] . ( !empty($aReceiverAddress['addressContactPerson']) ? ' (' . $aReceiverAddress['addressContactPerson'] . ')' : '' ),
	    $aReceiverAddress['addressAddress'],
	    $aReceiverAddress['addressZipCode'] . ' '  . $aReceiverAddress['addressCity'],
	  ] );
	}

	$aParcelInfo = [];
	if( !empty($aRequestData['requestParcelProperties']) ) {
		$aParcelPackageCodes = $aFreightRequestDataDict['definitions']['FREIGHT_UNIFAUN_PACKAGE_CODES'];

		foreach( $aParcelData as $aParcel ) {
			$sParcelType = $aParcelPackageCodes[ $aRequestData['requestTransporter'] ][ $aParcel['packageCode'] ];

			$aParcelInfo[] = '
				<li>' . sprintf( _('%dst %s á %dkg'), $aParcel['copies'], $sParcelType, $aParcel['weight'] )  . '</li>';
		}
	}
	$sParcelInfo = ( !empty($aParcelInfo) ? '<ul>' . implode('', $aParcelInfo) : '</ul>' );

  $sDeliveryDate = ( !empty($aRequestData['requestCalculatedDelivery']) ? $aRequestData['requestCalculatedDelivery'] : '<em>' . _('Ej angivet') . '</em>' );

  switch( $aRequestData['requestStatus'] ) {
  	case 'accepted':
  		$aResultText = current( $oSystemText->read(null, 'USER_FREIGHT_REQUEST_ACCEPTED_BY_USER') );
  		$sRequestResult = $aResultText['systemTextMessage'];
  		break;

  	case 'declined':
  		$aResultText = current( $oSystemText->read(null, 'USER_FREIGHT_REQUEST_DECLINED_BY_USER') );
  		$sRequestResult = $aResultText['systemTextMessage'];
  		break;

  	case 'suggested':
  		$aResultText = current( $oSystemText->read(null, 'USER_FREIGHT_REQUEST_ACCEPT_INFO') );
  		$sRequestResult = '
    		' . $aResultText['systemTextMessage'] . '
    		<div class="buttons">
		    	<button type="button" data-status="decline" class="btnChangeStatus cancel small narrow">' . _( 'Nej, tack' ) . '</button>
		    	<button type="button" data-status="accept" class="btnChangeStatus submit">' . _( 'Jag godkänner förslaget' ) . '</button>
		    </div>';
  		break;

  	default:
  		$sRequestResult = '';
  }
  $aAcceptText = current( $oSystemText->read(null, 'USER_FREIGHT_REQUEST_ACCEPT_INFO') );

  $sOutput .= '
    <div class="requestInfo">
			<div class="receiver">
				<div class="title">' . _( 'Mottagare' ) . '</div>
				<div class="content">' . $sReceiverAddress . '</div>
			</div>
    	<div class="message">
	      	<div class="title">' . _( 'Meddelande' ) . '</div>
	      	<div class="content">' . $aRequestData['requestMessage'] . '</div>
    	</div>
    	<div class="transporter">
	      	<div class="title">' . _( 'Transportör' ) . '</div>
	      	<div class="content">' . $aRequestData['requestTransporter'] . '</div>
    	</div>
    	<div class="parcel">
	      	<div class="title">' . _( 'Kolli' ) . '</div>
	      	<div class="content">' . $sParcelInfo . '</div>
    	</div>
    	<div class="deliveryDate">
	      	<div class="title">' . _( 'Beräknat leveransdatum' ) . '</div>
	      	<div class="content">' . $sDeliveryDate . '</div>
    	</div>
    	<div class="cost">
	      	<div class="title">' . _( 'Kostnad' ) . '</div>
	      	<div class="content">' . calculatePrice( $aRequestData['requestCost'], INVOICE_PRICE_FORMAT ) . '</div>
    	</div>
    	<div class="formStructure framed" id="requestResult">' . $sRequestResult . '</div>
    </div>';




} else {
  $sOutput .= _( 'Ett fel inträffade' );
}



echo '
	<div class="view freightRequest show">
	<h2>' . _( 'Fraktförslag' ) . '</h2>
		' . $sOutput . '
		<center><a href="#" class="popupClose button white small narrow">' . _( 'Stäng fönstret' ) . '</a></center>
	</div>
	<script>
		$( function() {

			$("button.btnChangeStatus").click( function() {
				const changeToStatus = $( this ).data( "status" );

				console.log({
					ajax: 1,
					view: "freightRequest/show.php",
					requestId: ' . $_GET['requestId'] . ',
					changeStatus: changeToStatus
				});

				$.get( "' . $oRouter->sPath . '", {
					ajax: 1,
					view: "freightRequest/show.php",
					requestId: ' . $_GET['requestId'] . ',
					changeStatus: changeToStatus
				}, function(returnData) {
					console.log(returnData);
					returnData = JSON.parse( returnData );

					if( returnData.result == "success" ) {
						$("#requestResult").html( returnData.resultHtml );

						var btnObj = $(".view.freightRequest.tableEdit .gridTable .body .dataRow[data-id=' . $_GET['requestId'] . '] .controls a");

						if( changeToStatus == "decline" ) {
							btnObj.removeClass("submit").addClass("cancel").html( "' . _( 'Avfärdat' ) . '" );

						} else if( changeToStatus == "accept" ) {
							btnObj.removeClass("submit").addClass("disabled").html( "' . _( 'Frakt förbereds' ) . '" );
						}

						// setTimeout(function() {
						//     location.reload();
						// }, 5000);
					} else {
						console.log( returnData.error );
					}
				} );
			} );

		} );
	</script>';