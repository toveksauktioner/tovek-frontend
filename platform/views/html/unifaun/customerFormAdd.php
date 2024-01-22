<?php

// Requests are found via invoice
if( empty($_POST['invoiceId']) || !ctype_digit($_POST['invoiceId']) ) {
	return;
}

$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
$oFreightRequest = clRegistry::get( 'clFreightRequest', PATH_MODULE . '/freightRequest/models' );
	$aRequestDataDict = $oFreightRequest->oDao->aDataDict;
$oUnifaun = clRegistry::get( 'clUnifaun', PATH_MODULE . '/unifaun/models' );
clFactory::loadClassFile( 'clOutputHtmlTable' );


// Read parent invoice
$aInvoiceData = current( $oInvoiceEngine->read('Invoice', '*', $_POST['invoiceId']) );

// Read freight request
if( !empty($aInvoiceData['invoiceFreightRequestId']) ) {
	$aFreightRequestData = current( $oFreightRequest->read('requestId', $aInvoiceData['invoiceFreightRequestId']) );
	if( !empty($aFreightRequestData) ) {
		$iRequestId = $aFreightRequestData['requestId'];
	}
}

if( empty($iRequestId) ) {
	return;
}

// Get user data
$aUserData = $oUser->readData( array(
	'infoName',
	'infoAddress',
	'infoZipCode',
	'infoCity',
	'infoCountryCode',
	'infoContactPerson',
	'infoPhone',
	'infoCellPhone',
	'userEmail',
	'userType'
) );

require_once( PATH_FUNCTION . '/fArray.php' );

$aFreightRequestData = current($oFreightRequest->read( array('*'), $iRequestId ));
if( empty($aFreightRequestData) || ($aFreightRequestData['requestStatus'] != 'suggested') || !empty($aFreightRequestData['requestInvoiceQueueId']) ) {
	$oRouter->redirect( $oRouter->getPath('userInvoiceList') );
	exit;
}

// Get the available notifications for the services
$aAvailableNotifications = array();
foreach( $GLOBALS['unifaun']['notification'] as $key => $value ) {
	if( in_array($key, array_keys($GLOBALS['unifaun']['serviceToNotification'][ $aFreightRequestData['requestTransportService'] ])) ) {
		$aAvailableNotifications[ $key ] = $value;
	}
}

// Fetch invoices data
$aInvoices = $oInvoiceEngine->readByFreightRequest_in_Invoice( $iRequestId, '*' );
$aInvoiceIds = arrayToSingle( $aInvoices, null, 'invoiceId' );
$aInvoiceNos = arrayToSingle( $aInvoices, 'invoiceId', 'invoiceNo' );

foreach( $aInvoiceIds as $iInvoiceId ) {
	$aInvoiceLines[$iInvoiceId] = $oInvoiceEngine->readByInvoice_in_InvoiceLine( $iInvoiceId );
}


if( !empty($_POST['acceptFreightRequest']) && $_POST['acceptFreightRequest'] ) {

	$oNotification = clRegistry::get( 'clNotificationHandler' );

	$aUnifaunOrderData = $aFreightRequestData + $_POST;

	$aFormErr = array();
	if( empty($aUnifaunOrderData['notification']) ) {
		$aFormErr['notification'] = _( 'Du måste välja notifieringsalternativ' );
	} else {
		$bPhoneIsMobile = false;
		if( substr($aUnifaunOrderData['phone'], 0, 2) == '07' ) $bPhoneIsMobile = true;
		if( substr($aUnifaunOrderData['phone'], 0, 4) == '+467' ) $bPhoneIsMobile = true;

		if( ($aUnifaunOrderData['notification'] == 'sms') && empty($aUnifaunOrderData['phone']) && ($bPhoneIsMobile === true) ) {
			$aFormErr['phone'] = _('Du måste ange ett mobilnummer när SMS är notifieringsalternativ');
		}
		if( ($aUnifaunOrderData['notification'] == 'enot') && empty($aUnifaunOrderData['email']) ) {
			$aFormErr['email'] = _( 'Du måste ange epost-adress när E-post är notifieringsalternativ' );
		}
	}

	if( empty($aFormErr) ) {

		if( ($aFreightRequestData['requestTransportService'] == 'P52') && ($_POST['privatePerson'] == 'yes') ) {
			// For pallets there is a private person option

			if( $aUnifaunOrderData['notification'] == 'sms' ) {
				$sAddonKey = 'text3';
				$sAddonValue = $aUnifaunOrderData['phone'];
			} else {
				$sAddonKey = 'text4';
				$sAddonValue = $aUnifaunOrderData['email'];
			}

			$aUnifaunOrderData['addon']['HOMEDLV'] = array(
				$sAddonKey => $sAddonValue
			);
		}


		$bCreateInvoice = false;
		$bGenerateDeliveryInfoFile = false;
		if( $aFreightRequestData['requestTransportService'] == UNIFAUN_SCHENKER_SERVICE ) {
			// Schenker freight dont create a Pacsoft order but generates a file with delivery info
			$bCreateInvoice = true;
			$bGenerateDeliveryInfoFile = true;
		} else {
			$mUnifaunXml = $oUnifaun->createOrderXml( $aUnifaunOrderData, $aInvoices[0] );	// Use the first invoice for freight order

			if( $mUnifaunXml === false ) {
				// Error
				$oNotification->set( array('dataError' => 'Orderfilen har inte sparats' ) );
			} else {
				// Submitted to Unifaun
				$oNotification->set( array('dataSaved' => 'Orderfil har skapats' ) );
				$bCreateInvoice = true;
			}
		}

		if( $bCreateInvoice === true ) {
			$aData = array(
				'requestStatus' => 'accepted'
			);

			$oFreightRequest->update( $iRequestId, $aData );
			$aErr = clErrorHandler::getValidationError( 'updateFreightRequest' );

			if( empty($aErr) ) {
				// Save the original invoice for printing
				$aInvoicePdfParams = array(
					'pdf' => true,
					'stamp' => 'freight' . ( ($aFreightRequestData['requestTransportService'] == UNIFAUN_SCHENKER_SERVICE) ? '-schenker' : '' )
				);

				// Create dir for PDF files if it doesn't exist
				$sUnifaunExportPdfDir = UNIFAUN_EXPORT_PATH . '/PDF';
				if( !is_dir($sUnifaunExportPdfDir) ) {
					if( !mkdir($sUnifaunExportPdfDir, 0755) ) throw new Exception( sprintf(_('Could not create directory %s'), $sUnifaunExportPdfDir) );
				}

				// Autoload for mPDF
				require_once PATH_PLATFORM . '/composer/vendor/autoload.php';

				$sInvoiceFooter = $oInvoiceEngine->getInvoiceFooter_in_Invoice();

				foreach( $aInvoiceIds as $iInvoiceId ) {
					$sFileName = $sUnifaunExportPdfDir . '/' . $aInvoiceNos[$iInvoiceId] . '.pdf';

					$sInvoiceContent = $oInvoiceEngine->generateInvoiceHtml_in_Invoice( $iInvoiceId, true, $aInvoicePdfParams );
					$sCss = file_get_contents( PATH_CSS . '/views/html/invoice/380.css' );

					$oMPdf = new \Mpdf\Mpdf( INVOICE_PDF_PARAMS );
					$oMPdf->SetHTMLFooter( $sInvoiceFooter );
					$oMPdf->SetHTMLHeader( $oInvoiceEngine->getInvoiceHeader_in_Invoice($aInvoicePdfParams, $iInvoiceId) );
					$oMPdf->WriteHTML( $sCss, \Mpdf\HTMLParserMode::HEADER_CSS );
					$oMPdf->WriteHTML( $sInvoiceContent, \Mpdf\HTMLParserMode::HTML_BODY );
					// $oMPdf->WriteHTML( $sInvoiceContent );
					$oMPdf->Output( $sFileName, 'F' );
					unset( $oMPdf );
				}

				// Bypass the ajax setting to avoid empty result
				$bAjaxSetting = ( !empty($_GET['ajax']) ? $_GET['ajax'] : false );
				unset( $_GET['ajax'] );

				// Retunr the ajax setting
				if( $bAjaxSetting == 1 ) $_GET['ajax'] = 1;

				if( $bGenerateDeliveryInfoFile === true ) {
					$sDeliveryInfo = _( 'Freight for invoice no' ) . ': ' . implode( ', ', $aInvoiceNos );
					foreach( $aUnifaunOrderData as $key => $value ) {
						$sDeliveryInfo .= "\n" . $key . ': ' . $value;
					}

					$sTxtFileName = $sUnifaunExportPdfDir . '/' . implode( '_', $aInvoiceNos ) . '.txt';
					$oTxtFile = fopen( $sTxtFileName, 'w' );
					fwrite( $oTxtFile, $sDeliveryInfo );
					fclose( $oTxtFile );
				}

				// Info regarding freight storage
				$sFreightStorageInfo = ( ($aFreightRequestData['requestTransportService'] == UNIFAUN_SCHENKER_SERVICE) ? $GLOBALS['freightStorageText']['schenker'] : $GLOBALS['freightStorageText']['postnord'] );

				// Create new invoice with the freight cost
				$aData = array(
					'invoiceType' 						=> 'invoice',
					'invoiceInformation'			=> _( 'Freight for invoice no' ) . ': ' . implode( ', ', $aInvoiceNos ) . $sFreightStorageInfo,
					'invoiceFirstname' 				=> $aInvoiceData['invoiceFirstname'],
					'invoiceSurname' 					=> $aInvoiceData['invoiceSurname'],
					'invoiceCompanyName' 			=> $aInvoiceData['invoiceCompanyName'],
					'invoiceAddress' 					=> $aInvoiceData['invoiceAddress'],
					'invoiceZipCode' 					=> $aInvoiceData['invoiceZipCode'],
					'invoiceCity' 						=> $aInvoiceData['invoiceCity'],
					'invoiceCountryCode' 			=> $aInvoiceData['invoiceCountryCode'],
					'invoiceUserId' 					=> $aInvoiceData['invoiceUserId'],
					'invoiceFee'							=> 0,
					'invoiceLateInterest'			=> INVOICE_DEFAULT_LATE_INTEREST,
					'invoiceCreditDays'				=> INVOICE_DEFAULT_CREDIT_DAYS,
					'invoiceDate'							=> date( 'Y-m-d' )
				);

				// Update the parent invoice with info that it should be shipped
				$oInvoiceEngine->update( 'Invoice', $aInvoiceIds, array(
					'invoiceNotes' => ( !empty($aInvoiceData['invoiceNotes']) ? $aInvoiceData['invoiceNotes'] . ' ' : '' ) . _( 'Ska fraktas' )
				) );

				if( $aFreightRequestData['requestCost'] > 0 ) {
					$iInvoiceQueueId = $oInvoiceEngine->create( 'InvoiceQueue', $aData );
					$aErr = clErrorHandler::getValidationError( 'createInvoiceQueue' );

					if( empty($aErr) ) {
						// Set invoice queue id to avoid duplicates
						$oFreightRequest->update( $iRequestId, array('requestInvoiceQueueId' => $iInvoiceQueueId) );

						$aLineData = array(
							'invoiceLineTitle' 						=> $aFreightRequestData['requestParcelCount'] . ' st ' . $aRequestDataDict['entFreightRequest']['requestParcelSize']['values'][ $aFreightRequestData['requestParcelSize'] ] . ' á ' . $aFreightRequestData['requestParcelWeight'] . ' kg',
							'invoiceLineQuantity'					=> 1,
							'invoiceLinePrice'						=> $aFreightRequestData['requestCost'],
							'invoiceLineVatValue'					=> INVOICE_DEFAULT_VAT,
							'invoiceLineAccountingCode'		=> INVOICE_FREIGHT_LINE_ACCOUNTING_CODE,
							'invoiceLineInvoiceQueueId' 	=> $iInvoiceQueueId,
							'invoiceLineUserId' 					=> $aInvoiceData['invoiceUserId']
						);

						$iInvoiceQueueLineId = $oInvoiceEngine->create( 'InvoiceQueueLine', $aLineData );
						$aErr = clErrorHandler::getValidationError( 'createInvoiceQueueLine' );

						if( empty($aErr) ) {
							#$oInvoiceEngine->setTotalAmount_in_Invoice( $iInvoiceId );
						}
					}
				}

				if( ($aUnifaunOrderData['notification'] == 'letter') ) {
					// Add fee for notification via letter
					$aLineData = array(
						'invoiceLineTitle' 						=> _( 'Avgift för brevavisering' ),
						'invoiceLineQuantity'					=> 1,
						'invoiceLinePrice'						=> UNIFAUN_NOTLTR_FEE,
						'invoiceLineVatValue'					=> INVOICE_DEFAULT_VAT,
						'invoiceLineInvoiceQueueId' 	=> $iInvoiceQueueId,
						'invoiceLineUserId' 					=> $aInvoiceData['invoiceUserId']
					);

					$iInvoiceQueueLineId = $oInvoiceEngine->create( 'InvoiceQueueLine', $aLineData );
				}
			}

			// The form is sent by jQuery so the form output is not necessary
			exit;
		}
	}
}

$aFormDataDict = array(
	'unifaun' => array(
		'name' => array(
			'type' => 'string',
			'title' => _( 'Name' ),
			'attributes' => array(
				'placeholder' => _( 'Name' )
			),
			'value' => $aUserData['infoName']
		),
		'address1' => array(
			'type' => 'string',
			'title' => _( 'Address' ),
			'attributes' => array(
				'placeholder' => _( 'Address' )
			),
			'value' => $aUserData['infoAddress']
		),
		'zipcode' => array(
			'type' => 'string',
			'title' => _( 'Zip Code' ),
			'attributes' => array(
				'placeholder' => _( 'Zip Code' )
			),
			'value' => $aUserData['infoZipCode']
		),
		'city' => array(
			'type' => 'string',
			'title' => _( 'City' ),
			'attributes' => array(
				'placeholder' => _( 'City' )
			),
			'value' => $aUserData['infoCity']
		),
		'country' => array(
			'type' => 'hidden',
			'value' => $aUserData['infoCountryCode']
		),
		'contact' => array(
			'type' => 'string',
			'title' => _( 'Contact' ),
			'attributes' => array(
				'placeholder' => _( 'Contact' )
			),
			'value' => $aUserData['infoContactPerson']
		),
		'phone' => array(
			'type' => 'string',
			'title' => _( 'Mobiltelefon' ),
			'attributes' => array(
				'placeholder' => _( 'Mobiltelefon' )
			),
			'value' => ( !empty($aUserData['infoPhone']) ? $aUserData['infoPhone'] : $aUserData['infoCellPhone'] ),
			'required' => true
		),
		'email' => array(
			'type' => 'string',
			'title' => _( 'Email' ),
			'attributes' => array(
				'placeholder' => _( 'Email' )
			),
			'value' => $aUserData['userEmail']
		),
		'notification' => array(
			'type' => 'array',
			'values' => $aAvailableNotifications,
			'title' => _( 'Avisering' )
		),
	)
);

if( $aFreightRequestData['requestTransportService'] == 'P52' ) {
	$aFormDataDict['unifaun']['privatePerson'] = array(
		'type' => 'array',
		'title' => _( 'Privatperson' ),
		'values' => array(
			'no' => _( 'No' ),
			'yes' => _( 'Yes' )
		)
	);
}

if( empty($_POST['privatePerson']) ) {
	$_POST['privatePerson'] = ( ($aUserData['userType'] == 'privatePerson') ? 'yes' : 'no' );
}

$aErr = array();
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'attributes'	=> array(
		'class' => 'newForm framed'
	),
	'labelSuffix'	=> ':',
	'placeholders' => false,
	'data'			=> $_POST,
	'errors' 		=> $aErr,
	'method'		=> 'post',
	'buttons'		=> array(
		'submit' => array(
			'content' => _( 'Accept' ),
			'attributes' => array(
				'name' => 'frmSubmit',
				'value' => true
			)
		)
	)
) );

//$oOutputHtmlForm->setGroups( array(
//	'misc' => array(
//		'title' => _( 'Misc' ),
//		'fields' => array(
//			//'autoprint',
//			'enot',
//			'sms'
//		)
//	)
//) );

echo '
	<div class="view unifaun customerFormAdd">
		<h1>Unifaun</h1>
		' . $oOutputHtmlForm->render() . '
		<div id="unifaunCommunication"></div>
	</div>
	<script>
		$( function() {

			function checkPhone() {
				var phoneObj = $("#phone");
				var phone = phoneObj.val();

				if( phone.match( /^(\+)?\d{2}\d{4}\d{4,5}/ ) == null ) {
					phoneObj.parent().addClass( "error" );
					return false;
				} else {
					//Match found
					phoneObj.parent().addClass( "approved" );
					return true
				}
			}

			$("#phone").blur( function() {
				checkPhone();
			} );

			$(".view.unifaun.customerFormAdd form").submit( function(event) {
				event.preventDefault();

				var phoneObj = $( this ).find("#phone");
				var emailObj = $( this ).find("#email");
				var phone = phoneObj.val();
				var email = emailObj.val();
				var notification = $( this ).find("#notification").val();

				if( ((phone == "") || !checkPhone()) && (notification == "sms") ) {
					alert( "' . _('Du måste ange ett mobilnummer när SMS är notifieringsalternativ') . '" );
					phoneObj.focus();

				} else if( (email == "") && (notification == "enot") ) {
					alert( "' . _('Du måste ange epost-adress när E-post är notifieringsalternativ') . '" );
					emailObj.focus();

				} else {
					var postData = $(this).serializeArray();
					postData[postData.length] = { name: "invoiceId", value: "' . $_POST['invoiceId'] . '" };
					postData[postData.length] = { name: "acceptFreightRequest", value: "1" };

					$.post( "' . $oRouter->getPath('emptyUnifaunFormAdd') . '", postData, function(data) {
						if( data == "" ) {
							window.location.reload();
						} else {
							$("#unifaunCommunication").html( data );
						}
					} );

				}

			} );

			// Check phone on init
			checkPhone();
		} );
	</script>';
