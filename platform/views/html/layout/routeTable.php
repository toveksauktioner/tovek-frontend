<?php

$aErr = array();
$sOutput = '';

$oRouter = clRegistry::get( 'clRouter' );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

if( !empty($_GET['layoutKey']) ) {

	$oRouter->setAcl( $oUser->oAcl );

	if( !empty($_POST['frmAddRoute']) ) {
		// Update
		if( !empty($_GET['routeId']) ) {
			$oRouter->update( $_GET['routeId'], $_POST );
			$aErr = clErrorHandler::getValidationError( 'updateRoute' );
			if( empty($aErr) ) $oRouter->redirect( $oRouter->getPath('superLayoutAdd') . '?layoutKey=' . $_GET['layoutKey'] );
		// Create
		} else {
			$_POST['routeLayoutKey'] = $_GET['layoutKey'];
			$_POST['routePathLangId'] = $GLOBALS['langId'];
			$iRouteId = $oRouter->create( $_POST );
			$aErr = clErrorHandler::getValidationError( 'createRoute' );
		}
	}

	if( !empty($_GET['deleteRoute']) ) {
		$oRouter->delete( $_GET['deleteRoute'] );
	}
	
	// List
	$aRoutes = $oRouter->readByLayout( $_GET['layoutKey'], array(
		'routeId',
		'routePath',
		'routeCreated',
		'routeUpdated'
	) );
	
	// Edit
	if( !empty($_GET['routeId']) ) {
		$aRouteData = current( $oRouter->read('routePath', $_GET['routeId']) );
		$sTitle = '';
	} else {
		$aRouteData = $_POST;
		
		$oLayout = clRegistry::get( 'clLayoutHtml' );
		$aLayout = $oLayout->readCustom( '*', $_GET['layoutKey'] );
		
		if( $aLayout['layoutDynamicChildrenRoute'] == 'yes' && count($aRoutes) <= 1 ) {
			// You are only supposed to add 1 route at this point
			$sTitle = '<a href="#frmRouteAdd" class="toggleShow icon iconText iconAdd hidden">' . _( 'Add route' ) . '</a>';
		} else {
			$sTitle = '<a href="#frmRouteAdd" class="toggleShow icon iconText iconAdd">' . _( 'Add route' ) . '</a>';
		}		
	}

	$oPagination = clRegistry::get( 'clOutputHtmlPagination', null, $oRouter->oDao, array(
		'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
		'entries' => 10
	) );

	clFactory::loadClassFile( 'clOutputHtmlSorting' );
	$oSorting = new clOutputHtmlSorting( $oRouter->oDao, array(
		'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('routeId' => 'ASC') )
	) );
	$oSorting->setSortingDataDict( array(
		'routeId' => array(),
		'routePath' => array(),
		'routeCreated' => array(),
		'routeUpdated' => array()
	) );
	
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $oRouter->oDao->getDataDict(), array(
		'action' => '',
		'attributes' => array( 'class' => 'inTable' ),
		'data' => $aRouteData,
		'errors' => $aErr,
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Save' )
		),
	) );
	$oOutputHtmlForm->setFormDataDict( array(
		'routeId' => array(),
		'routePath' => array(),
		'frmAddRoute' => array(
			'type' => 'hidden',
			'value' => true
		)
	) );

	$sOutput .= '
		<h2>' . _( 'Routes' ) . '</h2>
		<div id="routeTable">
		' . $oOutputHtmlForm->renderErrors() . '
		' . $sTitle;

	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oRouter->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'routeControls' => array(
			'title' => ''
		)
	) );

	$sUrlRouteEdit = $oRouter->sPath . '?layoutKey=' . $_GET['layoutKey'];

	// Add form
	$aRouteForm = array(
		'routeId' => ( !empty($_GET['routeId']) ? $_GET['routeId'] : '' ),
		'routePath' => $oOutputHtmlForm->renderFields( 'routePath' ),
		'routeCreated' => '',
		'routeUpdated' => '',
		'routeControls' => $oOutputHtmlForm->renderFields( 'frmAddRoute' ) . $oOutputHtmlForm->renderFields( 'routeControls' ) . $oOutputHtmlForm->renderButtons()
	);
	if( empty($_GET['routeId']) ) {
		$oOutputHtmlTable->addBodyEntry( $aRouteForm, array(
			'id' => 'frmRouteAdd'
		) );
	}

	foreach( $aRoutes as $entry ) {
		if( !empty($_GET['routeId']) && $_GET['routeId'] == $entry['routeId'] ) {
			// Add form
			$row = $aRouteForm;
		} else {
			$row = array(
				'routeId' => $entry['routeId'],
				'routePath' => $entry['routePath'],
				'routeCreated' => substr( $entry['routeCreated'], 0, 10 ),
				'routeUpdated' => substr( $entry['routeUpdated'], 0, 10 ),
				'routeControls' => '
				<a href="' . $sUrlRouteEdit . '&amp;routeId=' . $entry['routeId'] . '" class="icon iconEdit">' . _( 'Edit' ) . '</a>&nbsp;
				<a href="' . $sUrlRouteEdit . '&amp;canonicalRouteId=' . $entry['routeId'] . '" class="icon iconRelation">' . _( 'Set as Canonical' ) . '</a>&nbsp;
				<a href="' . $sUrlRouteEdit . '&amp;event=deleteRoute&amp;deleteRoute=' . $entry['routeId'] . '&amp;' . stripGetStr( array('event', 'deleteRoute') ) . '" class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
			);
		}

		$oOutputHtmlTable->addBodyEntry( $row );
	}

	$sOutput .= '
			' . $oOutputHtmlTable->render() . '
			' . ( empty($aRoutes) ? '<strong>' . _('There are no items to show') . '</strong>' : $oPagination->render() ) . '
		</div>';

	echo '
	<div class="view layout routeTable">' . $oOutputHtmlForm->renderForm( $sOutput ) . '</div>';

}

$oRouter->oDao->setLang( $GLOBALS['langId'] );
