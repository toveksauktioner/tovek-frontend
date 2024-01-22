<?php

$aErr = array();
$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );

// Image
$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
$oImage->setParams( array(
	'parentType' => $oFreight->sModuleName,
	'maxWidth' => 300,
	'maxHeight' => 300,
	'tnMaxWidth' => 80,
	'tnMaxHeight' => 80,
	'crop' => false
) );

if( !empty($_POST['frmFreightTypeAdd']) ) {
	$aSelectedCountries = $_POST['freightCountries'];
	$_POST = array_intersect_key( $_POST, current($oFreight->oDao->getDataDict('entFreightType')) );

	// Update
	if( !empty($_GET['freightTypeId']) && ctype_digit($_GET['freightTypeId']) ) {
		$iFreightId = $_GET['freightTypeId'];
		
		$oFreight->updateType( $_GET['freightTypeId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateFreightType' );
		if( empty($aErr) ) {
			$oFreight->updateFreightTypeToCountry( $_GET['freightTypeId'], $aSelectedCountries );
		}
		
	// Create
	} else {
		$iFreightId = $oFreight->createType($_POST);
		$aErr = clErrorHandler::getValidationError( 'createFreightType' );
		if( empty($aErr) ) {
			$oFreight->createFreightTypeToCountry( $iFreightId, $aSelectedCountries );
			$oRouter->redirect( $oRouter->sPath . '?freightTypeId=' . $iFreightId );
		}
	}
	
	$oUpload = clRegistry::get( 'clUpload' );
	
	// Logotype
	if( empty($aErr) && !empty($_FILES['freightImage']) && !empty($iFreightId) ) {
		if( !empty($_GET['freightTypeId']) && ctype_digit($_GET['freightTypeId']) ) {
			$oImage->deleteByParent( $_GET['freightTypeId'], $oFreight->sModuleName );
		}
		
		$aErr = $oImage->createWithUpload( array(
			'allowedMime' => array(
				'image/jpeg' => 'jpg',
				'image/pjpeg' => 'jpg',
				'image/gif' => 'gif',
				'image/png' => 'png',
				'image/x-png' => 'png'
			),
			'key' => 'freightImage'
		), $iFreightId );
	}
}

$sImage = '';

// Edit
if( !empty($_GET['freightTypeId']) && ctype_digit($_GET['freightTypeId']) ) {
	$aFreightTypeData = current( $oFreight->readType('*', $_GET['freightTypeId']) );
	$sTitle = _( 'Edit freight type' );

	// Countries
	$aCountryData = $oFreight->readFreightTypeToCountry( $_GET['freightTypeId'] );
	$aFreightTypeData['freightCountries'] = arrayToSingle( $aCountryData, null, 'countryId' );

	// Logotype
	$aImageData = current( $oImage->readByParent( $_GET['freightTypeId'], array(
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
	$aFreightTypeData = $_POST;
	$sTitle = _( 'Add freight type' );
}

// Countries
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName'
) );
$aCountries = arrayToSingle( $aCountries, 'countryId', 'countryName' );

// Add form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oFreight->oDao->getDataDict(), array(
	'attributes' => array('class' => 'marginal'),
	'data' => $aFreightTypeData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'freightTypeTitle' => array(
		'labelAttributes' => array(
			'title' => _( 'Name of this freight type. Will be shown in checkout.' )
		)
	),
	'freightImage' => array(
		'type' => 'upload',
		'attributes' => array(
			'class' => 'multi',
			'accept' => 'jpg|jpeg|gif|png',
			'id' => 'freightImageUploader'
		),
		'title' => _( 'Logotype' ),
		'fieldAttributes' => array(
			'class' => 'imageField'
		),
		'suffixContent' => $sImage
	),
	'freightTypeDescription' => array(
		'type' => 'string',
		'appearance' => 'full',
		'labelAttributes' => array(
			'title' => _( 'This description will be shown in checkout.' )
		)
	),
	'freightTypePrice' => array(
		'labelAttributes' => array(
			'title' => _( 'The cost of the freight type. This will be combined with the general and country fee in the checkout.' )
		)
	),
	'freightTypeSort' => array(
		'labelAttributes' => array(
			'title' => _( 'In which order the freight types will be listet. Integer, lowest value first (ascending order).' )
		)
	),
	'freightTypeAdnlInfo' => array(
		'title' => _( 'Additional information from the customer?' ),
		'values' => array(
			'no' => _( 'No' ),
			'yes' => _( 'Yes' ),
			'required' => _( 'Required' )
		)
	),
	'freightTypeStatus' => array(),
	'freightCountries' => array(
		'type' => 'arraySet',
		'values' => $aCountries,
		'title' => _( 'Countries' ),
		'suffixContent' => ' (' . _( 'Hold down Ctrl when clicking to select multiple' ) . ')',
		'attributes' => array( 'class' => 'freightCountries' )
	),
	'freightAllCountries' => array(
		'type' => 'array',
		'values' => array(
			'no' => _( 'No' ),
			'yes' => _( 'Yes' )
		),
		'title' => _( 'All countries' )
	),
	'frmFreightTypeAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

echo '
	<div class="view freightTypeFormAdd">
		<h3>' . $sTitle . '</h3>
		<section>' . $oOutputHtmlForm->render() . '</section>
	</div>';

$oTemplate->addBottom( array(
	'key' => 'autoSelect',
	'content' => '
	<script type="text/javascript">
		$(document).ready(function() {
			$("#freightAllCountries").change(function() {
				var sValue = $("#freightAllCountries").val();
				if( sValue == "yes" ) {
					$(".freightCountries").each(function(){
						$(".freightCountries option").attr("selected","selected");
					});
				} else {
					$(".freightCountries").each(function(){
						$(".freightCountries option").removeAttr("selected");
					});
				}
			});
		});
	</script>'
) );