<?php

$aErr = array();

$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );
$oPayment->oDao->setLang( $GLOBALS['langIdEdit'] );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );

// Images
$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
$oImage->setParams( array(
	'parentType' => $oPayment->sModuleName,
	'maxWidth' => 300,
	'maxHeight' => 300,
	'tnMaxWidth' => 80,
	'tnMaxHeight' => 80,
	'crop' => false
) );

// All countries
$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName'
) );
$aCountries = arrayToSingle( $aCountries, 'countryId', 'countryName' );

if( !empty($_POST['frmPaymentAdd']) ) {
	// Update
	if( !empty($_GET['paymentId']) && ctype_digit($_GET['paymentId']) ) {
		$oPayment->update( $_GET['paymentId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updatePayment' );
		if( empty($aErr) && !empty($_POST['paymentCountries']) ) {
			$oPayment->updatePaymentToCountry( $_GET['paymentId'], $_POST['paymentCountries'] );
		}
		$iPaymentId = $_GET['paymentId'];
		
	// Create
	} else {
		$iPaymentId = $oPayment->create($_POST);
		$aErr = clErrorHandler::getValidationError( 'createPayment' );		
		if( empty($aErr) && !empty($_POST['paymentCountries']) ) {
			$oPayment->createPaymentToCountry( $iPaymentId, $_POST['paymentCountries'] );
			$oRouter->redirect( $oRouter->sPath . '?paymentId=' . $iPaymentId );
		}
	}
	
	$oUpload = clRegistry::get( 'clUpload' );
	
	// Logotype
	if( empty($aErr) && !empty($_FILES['paymentImage']) && !empty($iPaymentId) ) {
		if( !empty($_GET['paymentId']) && ctype_digit($_GET['paymentId']) ) {
			$oImage->deleteByParent( $_GET['paymentId'], $oPayment->sModuleName );
		}
		
		$aErr = $oImage->createWithUpload( array(
			'allowedMime' => array(
				'image/jpeg' => 'jpg',
				'image/pjpeg' => 'jpg',
				'image/gif' => 'gif',
				'image/png' => 'png',
				'image/x-png' => 'png'
			),
			'key' => 'paymentImage'
		), $iPaymentId );
	}
	
	if( !empty($_POST['paymentToOrderField']) ) {
		$aData = array();
		foreach( $_POST['paymentToOrderField'] as $sField ) {
			$aData[] = array(
				'paymentId' => $iPaymentId, 
				'orderField' => $sField
			);			
		}
		
		$oPayment->deletePaymentToOrderField( $iPaymentId );
		$oPayment->createPaymentToOrderField( $aData );
		$aErr = clErrorHandler::getValidationError( 'createPaymentToOrderField' );
		if( empty($aErr) ) {}		
	}
}

$sImage = '';

// Edit
if( !empty($_GET['paymentId']) && ctype_digit($_GET['paymentId']) ) {
	$aPaymentData = current( $oPayment->readAll('*', $_GET['paymentId']) );
	$sTitle = _( 'Edit payment' );
	
	// Countries
	$aCountryData = $oPayment->readPaymentToCountry( $_GET['paymentId'] );
	$aActiveCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
		'countryId',
		'countryName'
	), arrayToSingle($aCountryData, null, 'countryId') );
	$aPaymentData['paymentCountries'] = arrayToSingle( $aCountryData, null, 'countryId' );
	
	if( count($aActiveCountries) != count($aCountries) ) {
		// Active countries list
		$sActiveCountries = '
			<hr />
			<h3>' . _( 'Selected countries' ) . '</h3>
			<ul>';
		foreach( $aActiveCountries as $aCountry ) {
			$sActiveCountries .= '<li>' . $aCountry['countryName'] . '</li>';
		}
		$sActiveCountries .= '<ul>';
	} else {
		// Active countries list
		$sActiveCountries = '
			<hr />
			<h3>' . _( 'Selected countries' ) . '</h3>
			<em>' . _( 'All is currently selected' ) . '</em>';
	}
	
	// Order fields
	$aOrderFieldData = array();	
	
	// Logotype
	$aImageData = current( $oImage->readByParent( $_GET['paymentId'], array(
		'imageId',
		'imageFileExtension',
		'imageParentId',
		'imageParentType'
	) ) );
	if( !empty($aImageData) ) {
		$sImage = '
			<div>
				<a href="/images/custom/' . $aImageData['imageParentType'] . '/' . $aImageData['imageId'] . '.' . $aImageData['imageFileExtension'] . '" class="colorbox"><img src="/images/custom/' . $aImageData['imageParentType'] . '/' . IMAGE_TN_DIRECTORY . '/' . $aImageData['imageId'] . '.' . $aImageData['imageFileExtension'] . '" alt="" height="50px" /></a>
				<p>
					<a href="' . $oRouter->sPath . '?event=imageCreateThumbnail&amp;imageCreateThumbnail=' . $aImageData['imageId'] . '&amp;' . stripGetStr( array('event', 'imageCreateThumbnail', 'deleteImage') ) . '" class="icon iconThumbnail linkConfirm" title="' . _( 'Do you really want to recreate the thumbnail?' ) . '">' . _( 'Recreate thumbnail' ) . '</a>
					<a href="' . $oRouter->sPath . '?event=deleteImage&amp;deleteImage=' . $aImageData['imageId'] . '&amp;' . stripGetStr( array('event', 'imageCreateThumbnail', 'deleteImage') ) . '" class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>
				</p>
			</div>';
	}
	
} else {
	$aPaymentData = $_POST;
	$sTitle = _( 'Add payment' );
	$sActiveCountries = '';
}

// Add form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oPayment->oDao->getDataDict(), array(
	'attributes' => array('class' => 'marginal'),
	'data' => $aPaymentData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$aFormDataDict = array(
	'paymentTitleTextId' => array(
		'labelAttributes' => array(
			'title' => _( 'Name of this payment method. Will be shown in checkout.' )
		)
	),
	'paymentImage' => array(
		'type' => 'upload',
		'attributes' => array(
			'class' => 'multi',
			'accept' => 'jpg|jpeg|gif|png',
			'id' => 'paymentImageUploader'
		),
		'title' => _( 'Logotype' ),
		'fieldAttributes' => array(
			'class' => 'imageField'
		),
		'suffixContent' => $sImage
	),
	'paymentDescriptionTextId' => array(
		'type' => 'string',
		'appearance' => 'full',
		'labelAttributes' => array(
			'title' => _( 'This description will be shown in checkout.' )
		)
	),
	'paymentPrice' => array(
		'labelAttributes' => array(
			'title' => _( 'The fee for this payment method.' )
		)
	),
	'paymentSort' => array(
		'labelAttributes' => array(
			'title' => _( 'In which order the payment method will be listet. Integer, lowest value first (ascending order).' )
		)
	),
	'paymentStatus' => array(),
	'paymentCountries' => array(
		'type' => 'arraySet',		
		'values' => $aCountries,
		'title' => _( 'Countries' ),
		'suffixContent' => ' (' . _( 'Hold down Ctrl when clicking to select multiple' ) . ')',
		'attributes' => array( 'class' => 'paymentCountries' )
	),
	'paymentAllCountries' => array(
		'type' => 'array',		
		'values' => array(
			'no' => _( 'No' ),
			'yes' => _( 'Yes' )			
		),
		'title' => _( 'All countries' ),
		'suffixContent' => $sActiveCountries,
		'attributes' => array( 'class' => 'paymentAllCountries' )
	),
	'frmPaymentAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
);

if( !empty($aPaymentData['paymentPriceAllowed']) && $aPaymentData['paymentPriceAllowed'] == 'no' ) {
	$aFormDataDict['paymentPrice']['attributes'] = array(
		'disabled' => 'disabled'
	);
}

$aFormGroups = array(
	'information' => array(
		'title' => _( 'Information' ),
		'fields' => array(
			'paymentTitleTextId',
			'paymentImage',
			'paymentDescriptionTextId',
			'paymentPrice',
			'paymentStatus',
			'paymentSort',
			'frmPaymentAdd'
		)
	),
	'countries' => array(
		'title' => _( 'Countries' ),
		'fields' => array(
			'paymentCountries',
			'paymentAllCountries'
		)
	)
);

/**
 * For payment class file selection
 */
if( $_SESSION['user']['groupKey'] == 'super' ) {
	// None usable fils
	$aRemoveFiles = array(
		'.',
		'..',
		'clPayment.php',
		'clPaymentBase.php'
	);
	
	$aClasses = scandir( PATH_MODULE . '/payment/models' );
	$aAvailableClasses = array();
	foreach( $aClasses as $key => $sClassFile ) {
		if( in_array($sClassFile, $aRemoveFiles) ) {
			continue;
		} elseif( strpos($sClassFile, 'Dao') ) {
			continue;
		} else {
			$aClassFile = explode( '.', $sClassFile );
			$aAvailableClasses[$aClassFile[0]] = $aClassFile[0];
		}
	}
	
	$aFormDefaultFields = array_keys($aFormDataDict);
	
	// Order fields
	$aSelectableFields = array();	
	
	$aFormDataDict += array(
		'paymentClass' => array(
			'type' => 'array',
			'values' => $aAvailableClasses,
			'title' => _( 'Payment class' )
		),
		'paymentType' => array(),
		'paymentPriceAllowed' => array(),
		'paymentToOrderField' => array(
			'type' => 'arraySet',
			'values' => $aSelectableFields,
			'title' => _( 'Order fields' ),
			'attributes' => array( 'class' => 'paymentToOrderField' )
		)
	);
	
	$aFormGroups['superUser'] = array(
		'title' => _( 'For superuser' ),
		'fields' => array(
			'paymentClass',
			'paymentType',
			'paymentPriceAllowed',
			'paymentToOrderField'
		)
	);
}

$oOutputHtmlForm->setGroups( $aFormGroups );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );

echo '
	<div class="view paymentFormAdd">
		<h3>' . $sTitle . '</h3>
		' . $oOutputHtmlForm->render() . '
	</div>';
	
$oPayment->oDao->setLang( $GLOBALS['langId'] );

$oTemplate->addBottom( array(
	'key' => 'autoSelect',
	'content' => '
	<script type="text/javascript">
		$(document).ready(function() {
			$("#paymentAllCountries").change(function() {				
				var sValue = $("#paymentAllCountries").val();
				if( sValue == "yes" ) {
					$(".paymentCountries").each(function(){
						$(".paymentCountries option").attr("selected","selected");
					});
				} else {
					$(".paymentCountries").each(function(){
						$(".paymentCountries option").removeAttr("selected");
					});
				}
			});		 
		});
	</script>'
) );

$oTemplate->addStyle( array(
	'key' => 'customViewCss',
	'content' => '
		select.paymentCountries,
		select.paymentToOrderField
		{ height: 18.2em; }
	'
) );