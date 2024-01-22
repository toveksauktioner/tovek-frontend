<?php

$aErr = array();

$oRouter = clRegistry::get( 'clRouter' );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );
$oAco = clRegistry::get( 'clAco' );
$aAcoDataDict = $oAco->oDao->getDataDict();

if( !empty($_POST['frmAddAco']) ) {
	// Update
	if( !empty($_GET['acoKey']) ) {
		$oAco->update( $_GET['acoKey'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateAcl' );
		if( empty($aErr) ) $oRouter->redirect( $oRouter->sPath );
		
	// Create
	} else {
		$aData = $oAco->oDao->readData( array('criterias' => 'acoKey = "' . $_POST['acoKey'] . '"') );
		if( empty( $aData ) ) {
			$oAco->create( $_POST );
			$aErr = clErrorHandler::getValidationError( 'createAcl' );
		} else {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataError' => _( 'ACO key already exists!' )
			) );
		}
		
	}
}

// Edit
if( !empty($_GET['acoKey']) ) {
	$aAcoData = current( $oAco->read('*', $_GET['acoKey']) );

// New
} else {
	$aAcoData = $_POST;	
}

$oPagination = clRegistry::get( 'clOutputHtmlPagination', null, $oAco->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 150
) );

// Sort
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oAco->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('acoGroup' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'acoKey' => array(),
	'acoType' => array(),
	'acoGroup' => array()
) );

// Data
$aAco = $oAco->read();

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oAco->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aAcoData,
	'errors' => $aErr,
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	),
) );
$oOutputHtmlForm->setFormDataDict( array(
	'acoKey' => array(),
	'acoType' => array(),
	'acoGroup' => array(),
	'frmAddAco' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $oAco->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
	'acoControls' => array(
		'title' => ''
	)
) );

// Add form
$aAcoForm = array(
	'acoKey' => $oOutputHtmlForm->renderFields( 'acoKey' ),
	'acoType' => $oOutputHtmlForm->renderFields( 'acoType' ),
	'acoGroup' => $oOutputHtmlForm->renderFields( 'acoGroup' ),
	'acoControls' => $oOutputHtmlForm->renderFields( 'frmAddAco' ) . $oOutputHtmlForm->renderFields( 'acoControls' ) . $oOutputHtmlForm->renderButtons()
);
if( empty($_GET['acoKey']) ) {
	$oOutputHtmlTable->addBodyEntry( $aAcoForm, array(
		'id' => 'frmAcoAdd'
	) );
}

$sUrlAclAcoAdd = $oRouter->getPath( 'superAclAcoAdd' );

if( !empty($aAco) ) {
	foreach( $aAco as $entry ) {
		if( !empty($_GET['acoKey']) && $_GET['acoKey'] == $entry['acoKey'] ) {
			// Add form
			$row = $aAcoForm;
		} else {
			$row = array(
				'acoKey' => $entry['acoKey'],
				'acoType' => $aAcoDataDict['entAco']['acoType']['values'][$entry['acoType']],
				'acoGroup' => $entry['acoGroup'],
				'acoControls' => '
				<a href="' . $sUrlAclAcoAdd . '?aclType=dao&amp;acoKey=' . $entry['acoKey'] . '" class="icon iconLock"><span>' . _( 'ACL' ) . '</span></a>
				<a href="' . $oRouter->sPath . '?acoKey=' . $entry['acoKey'] . '" class="icon iconEdit"><span>' . _( 'Edit' ) . '</span></a>
				<a href="' . $oRouter->sPath . '?event=deleteAco&amp;deleteAco=' . $entry['acoKey'] . '&amp;' . stripGetStr( array('event', 'deleteAco') ) . '" class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>'
			);
		}
		
		$oOutputHtmlTable->addBodyEntry( $row );
	}
	
	$sOutput = $oOutputHtmlForm->renderForm(
		$oOutputHtmlForm->renderErrors() .
		$oOutputHtmlTable->render() .
		$oPagination->render()
	);
	
} else {
	$sOutput = $oOutputHtmlForm->renderForm(
		$oOutputHtmlForm->renderErrors() .
		$oOutputHtmlTable->render() .
		'<strong>' . _('There are no items to show') . '</strong>' .
		$oPagination->render()
	);
}

echo '
	<div class="view acl table">
		<h1>' . _( 'ACO keys' ) . '</h1>
		' . (empty($_GET['acoKey']) ? '<a href="#frmAcoAdd" class="toggleShow icon iconText iconAdd">' . _( 'Add ACO' ) . '</a>' : '') . '		
		<section>
			' . $sOutput . '
		</section>
	</div>';
$oRouter->oDao->setLang( $GLOBALS['langId'] );