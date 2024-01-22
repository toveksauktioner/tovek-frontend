<?php

// The call should contain at least one item reference
if( empty($_REQUEST['itemId']) ) exit;


$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

// Registred vehicles are handled by this form - otherwise the checkout is used
// If the API service is not enabled the form is presented
$aItemIsVehicle = current( current($oAuctionEngine->readAuctionItem(array(
  'fields' => 'itemVehicleDataId',
  'itemId' => $_REQUEST['itemId']
))) );
if( empty($aItemIsVehicle) && FINANCING_WASAKREDIT_ENABLE ) return;


$iSuggestedFinancingValue = null;
if( !empty($_REQUEST['value']) ) $iSuggestedFinancingValue = $_REQUEST['value'];


$sOutput = '';
$sItemRoute = '';
$sItemTitle = '';

// Item data
$aItemData = $oAuctionEngine->readAuctionItem( array(
	'fields' => array(
		'itemId',
		'itemTitle',
		'itemEndTime',
		'routePath'
	),
	'itemId' => $_REQUEST['itemId'],
	'status' => array(
		'active',
		'inactive',
		'ended'
	)
) );

if( !empty($aItemData[0]['routePath']) ) {
	$sItemRoute = $aItemData[0]['routePath'];
} else {
	// Fallback
	$sItemRoute = '/rop?itemId=' . $_GET['itemId'];
}

if( !empty($aItemData[0]['itemTitle']) ) {
	$sItemTitle = $aItemData[0]['itemTitle'];
}


$aErr = array();

$aDataDict = array(
	'entFinancing' => array(
		'financingCompanyPin' => array(
			'title' => _( 'Organisationsnummer' ),
			'type' => 'string',
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'number'
			)
		),
		'financingContactPerson' => array(
			'title' => _( 'Kontaktperson' ),
			'type' => 'string',
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'name'
			)
		),
		'financingPhone' => array(
			'title' => _( 'Telefonnummer' ),
			'type' => 'string',
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'phone'
			)
		),
		'financingEmail' => array(
			'title' => _( 'E-post' ),
			'type' => 'string',
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'email'
			)
		),
		'financingAmount' => array(
			'title' => _( 'Tänkt maxbud' ),
			'type' => 'string',
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'bid'
			),
			'value' => $iSuggestedFinancingValue
		),
		'financingUrl' => array(
			'type' => 'hidden',
			'value' => $sItemRoute
		),
		'financingTitle' => array(
			'type' => 'hidden',
			'value' => $sItemTitle
		),
		'itemId' => array(
			'type' => 'hidden',
			'value' => $_REQUEST['itemId']
		),
		'frmFinancing' => array(
			'type' => 'hidden',
			'value' => 'true'
		)
	)
);

// Post
if( !empty($_POST['frmFinancing']) && $_POST['frmFinancing'] == 'true' ) {
	$_GET['ajax'] = false;

	$_POST = array_intersect_key( $_POST, $aDataDict['entFinancing']  );

	$_POST = array_intersect_key( $_POST, $aDataDict['entFinancing']  );
	$aErr = clDataValidation::validate( $_POST, $aDataDict );

	if( empty($aErr) ) {
		$sSubject = _( 'Finanseringsförfrågan från' ) . ' ' . SITE_DOMAIN;

		$sBodyHtml = '';
		foreach( $_POST as $key => $value ) {
			if( $aDataDict['entFinancing'][$key]['type'] == 'hidden' ) {
				if( $key == 'financingUrl' ) {
					$sUrl = (SITE_DEFAULT_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . $value;
					$sBodyHtml .= '<strong>' . _( 'Länk till objekt' ) . ':</strong> <a href="' . $sUrl . '">' . $sUrl . '</a><br>';
				} if( $key == 'financingTitle' ) {
					$sBodyHtml .= '<strong>' . _( 'Objekt' ) . ': ' . $value . '<br>';
				} else {
					continue; // Skip hidden fields
				}
			} else {
				$sBodyHtml .= '<strong>' . ( array_key_exists( 'title', $aDataDict['entFinancing'][$key] ) ? $aDataDict['entFinancing'][$key]['title'] : $key ) . ':</strong> ' . $value . '<br>';
			}
		}

		$oMailHandler = clRegistry::get( 'clMailHandler' );
		$oMailHandler->prepare( array(
			'from' => 'Toveks auktioner <' . SITE_MAIL_FROM . '>',
			'to' => array(
        // 'markus@tovek.se',
        // 'mats.cederfalk@wasakredit.se',
        // 'johan.agren@wasakredit.se',
        'affarssupport@wasakredit.se'
      ),
			'title' => $sSubject,
			'content' => $sBodyHtml
		) );

		if( $oMailHandler->send() ) {
			// Form is sent -  exit without output
			exit;

		} else {
			$aErr[] = _( 'Förfrågan kunde inte skickas' );
		}
	} else {
		clErrorHandler::setValidationError( $aErr );
		$aErr = clErrorHandler::getValidationError( 'entFinancing' );
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'action' => '',
	'errors' => $aErr,
	'attributes' => array(
		'class' => 'newForm framed'
	),
	'method' => 'post',
	'placeholders' => false,
	'buttons' => array(
		'submit' => _( 'Ansök om finansiering' )
	),
	'labelSuffix' => '',
	'labelRequiredSuffix' => '',
  'recaptcha' => true
) );


// Stop form if to close to end
if( !empty($aItemData[0]['itemEndTime']) && (time() > (strtotime($aItemData[0]['itemEndTime']) - (24*60*60))) ) {
	$sOutput = '
		<h1>Ansökan är inte möjlig när det är mindre än 24 timmar till avslut.</h1>';

} else {
	$sOutput = '
		' . $oOutputHtmlForm->render() . '
		<script>
			$( document ).on( "submit", ".itemFinancingForm form", function(ev) {
				ev.preventDefault();

				$(".itemFinancingForm").load( "' . $oRouter->getPath('emptyAuctionItemFinancing') . '#itemFinancingFormContent", $(this).serializeArray(), function(data) {
					if( data == "" ) {
						$("#popupLinkBox .popupClose").click();
						alert("' . _('Förfrågan har skickats!') . '");
					} else {
						$(".itemFinancingFormContent").html( data);
					}
				} );
			} );
		</script>';
}

echo '
	<div class="view auction itemFinancingForm">
		<h3>' . _( 'Ansökningar för registrerade fordon måste hanteras manuellt' ) . '</h3>
		<p>' . _( 'Ansökan kan därför ta lite tid. All kommunikation kommer ske direkt mellan dig och WasaKredit.' ) . '</p>
		<div id="itemFinancingFormContent">
			' . $sOutput . '
		</div>
	</div>';
