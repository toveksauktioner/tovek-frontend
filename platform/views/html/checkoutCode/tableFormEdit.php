<?php

$aErr = array();

$oCheckoutCode = clRegistry::get( 'clCheckoutCode', PATH_MODULE . '/checkout/models' );
$aDataDict = $oCheckoutCode->oDao->getDataDict( 'entCheckoutCode' );

if( !empty($_POST['frmAddCheckoutCode']) ) {	
	if( !empty($_GET['codeId']) ) {
		// Update
		$oCheckoutCode->update( $_GET['codeId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateCheckoutCode' );
		$iCodeId = $_GET['codeId'];
		
	} else {
		// Create		
		$_POST['codeCreated'] = date( 'Y-m-d H:i:s' );
		$iCodeId = $oCheckoutCode->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createCheckoutCode' );
		
		if( empty($aErr) ) {
			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddCheckoutCode',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddCheckoutCode").show();
					} );				
				</script>'
			) );
		}
	}
}

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oCheckoutCode->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('codeCreated' => 'ASC') )
) );

$oSorting->setSortingDataDict( array(
	//'codeId' => array(),
	'codeSecret' => array(),
	'codeRelationType' => array(),
	'codeRelationId' => array(),
	'codeFreightTypeId' => array(),
	'codePaymentTypeId' => array(),
	'codeCustomerId' => array(),
	'codeExpire' => array(),
	'codeCreated' => array()
) );

$aCodes = valueToKey( 'codeId', $oCheckoutCode->read() );

if( !empty($_GET['linkId']) ) {
	// Edit
	$aData = $aCodes[ $_GET['codeId'] ];
} else {
	// New
	$aData = $_POST;
}

$sOutput = '';
	
if( !empty($aCodes) ) {
	/**
	 * Form init
	 */
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $oCheckoutCode->oDao->getDataDict(), array(
		'action' => '',
		'attributes' => array( 'class' => 'inTable' ),
		'data' => $aData,
		'errors' => $aErr,
		'method' => 'post'
	) );
	unset( $aDataDict['entCheckoutCode']['codeId'] );
	$oOutputHtmlForm->setFormDataDict( $aDataDict['entCheckoutCode'] + array(	
		'frmAddCheckoutCode' => array(
			'type' => 'hidden',
			'value' => true
		)
	) );
	
	/**
	 * Form row
	 */
	$aAddForm = array(
		'codeSecret' => $oOutputHtmlForm->renderFields( 'codeSecret' ) . $oOutputHtmlForm->renderFields( 'codeSecret' ),
		'codeRelationType' => $oOutputHtmlForm->renderFields( 'codeRelationType' ) . $oOutputHtmlForm->renderFields( 'codeRelationType' ),
		'codeRelationId' => $oOutputHtmlForm->renderFields( 'codeRelationId' ) . $oOutputHtmlForm->renderFields( 'codeRelationId' ),
		'codeFreightTypeId' => $oOutputHtmlForm->renderFields( 'codeFreightTypeId' ) . $oOutputHtmlForm->renderFields( 'codeFreightTypeId' ),
		'codePaymentTypeId' => $oOutputHtmlForm->renderFields( 'codePaymentTypeId' ) . $oOutputHtmlForm->renderFields( 'codePaymentTypeId' ),
		'codeCustomerId' => $oOutputHtmlForm->renderFields( 'codeCustomerId' ) . $oOutputHtmlForm->renderFields( 'codeCustomerId' ),
		'codeExpire' => $oOutputHtmlForm->renderFields( 'codeExpire' ) . $oOutputHtmlForm->renderFields( 'codeExpire' ),
		'tableRowControls' => $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
	);
	
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oCheckoutCode->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'tableRowControls' => array(
			'title' => ''
		)
	) );

	$sEditUrl = $oRouter->sPath;

	foreach( $aCodes as $aEntry ) {
		if( !empty($_GET['codeId']) && $aEntry['codeId'] == $_GET['codeId'] ) {
			// Edit
			$aAddForm['tableRowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('codeId', 'event', 'deleteCheckoutCode') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
			$oOutputHtmlTable->addBodyEntry( $aAddForm );
			
		} else {
			// Data row
			$aEntry['codeControls'] = '
				<a href="' . $sEditUrl . '?codeId=' . $aEntry['codeId'] . '" class="ajax icon iconEdit iconText">' . _( 'Edit' ) . '</a>
				<a href="' . $oRouter->sPath . '?event=deleteCheckoutCode&amp;deleteCheckoutCode=' . $aEntry['codeId'] . '" class="icon iconDelete iconText codeConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>';
				
			$oOutputHtmlTable->addBodyEntry( $oOutputHtmlTable->createDataRowByDataKey( $aEntry ) );
		}
	}
	
	if( empty($_GET['codeId']) ) {
		// New
		$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
			'id' => 'frmAddCheckoutCode',
			'style' => 'display: table-row;'
		) );
	}
	
	$sOutput = $oOutputHtmlForm->renderForm(
		$oOutputHtmlForm->renderErrors() .
		$oOutputHtmlTable->render()
	);
	
} else {
	$sOutput = '
		<strong>' . _( 'There are no items to show' ) . '</strong>';
	
}

echo '
	<div class="view checkoutCode tableEdit">
		<h1>' . _( 'Codes' ) . '</h1>
		<p><a href="#frmAddCheckoutCode" class="toggleShow icon iconText iconAdd">' . _( 'Add code' ) . '</a></p>
		<section>
			' . $sOutput . '
		</section>
	</div>';
