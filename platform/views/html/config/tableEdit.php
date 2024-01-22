<?php

$aErr = array();
$iPagination = 15;

$oConfig = clRegistry::get( 'clConfig' );

if( !empty($_POST['frmConfig']) ) {	
	if( $_POST['frmConfig'] == 'update' ) {	
		// Update
		$oConfig->update( $_POST['configKey'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateConfig' );
	} else {
		// Create
		$oConfig->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createConfig' );
	}
	
	if( !empty($aErr) ) {
		$oNotification->set( array(
			'dataError' => _( 'Data was not saved' ) . '!'
		) );
	}
	else {
		$oNotification->set( array(
			'dataSaved' => _( 'Data was saved' ) . '!'
		) );
		$_GET['configKey'] = null;
	}
}

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oConfig->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => $iPagination
) );

// Search
if( !empty($_GET['searchQuery']) ) {
	$oConfig->oDao->setCriterias( array(
		'productSearch' => array(
			'type' => 'like',
			'value' => $_GET['searchQuery'],
			'fields' => array(
				'configKey',
				'configValue',
				'configGroupKey'
			)
		)
	) );
}
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oConfig->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'searchForm' ),
	'data' => $_GET,
	'buttons' => array(
		'submit' => _( 'Search' ),
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'searchQuery' => array(
		'title' => _( 'Search' )
	)
), array_diff_key($_GET, array('searchQuery' => '', 'page' => '')) );
$sSearchForm = $oOutputHtmlForm->render();

// Data
$aData = $oConfig->read();

// Sorting
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oConfig->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('configKey' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'configKey' => array(),
	'configValue' => array(),
	'configGroupKey' => array()
) );

// Table
clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $oConfig->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
	'configControls' => array(
		'title' => ''
	)
) );
if( !empty($_GET['action']) && $_GET['action'] == 'add' ) {
	$row = array(
		'configKey' => '<input type="text" class="text" name="configKey" />',
		'configValue' => '<input type="text" class="text" name="configValue" />',
		'configGroupKey' => '<input type="text" class="text" name="configGroupKey" />',
		'configControls' => '<button type="submite">' . _( 'Save' ) . '</button>'
	);
	$oOutputHtmlTable->addBodyEntry( $row );
}
$iCount = 1;
foreach( $aData as $entry ) {
	if( !empty($_GET['configKey']) && $entry['configKey'] == $_GET['configKey'] ) {
		$row = array(
			'configKey' => $entry['configKey'],
			'configValue' => '<input type="text" class="text" name="configValue" value="' . $entry['configValue'] . '" />',
			'configGroupKey' => '<input type="text" class="text" name="configGroupKey" value="' . $entry['configGroupKey'] . '" />',
			'configControls' => '
				<input type="hidden" name="configKey" value="' . $entry['configKey'] . '" />
				<button type="submite">' . _( 'Save' ) . '</button>'
		);
	} else {
		$row = array(
			'configKey' => '<a href="' . $oRouter->getPath( 'superConfigTableEdit' ) . '?configKey=' . $entry['configKey'] . '">' . $entry['configKey'] . '</a>',
			'configValue' => '<a href="' . $oRouter->getPath( 'superConfigTableEdit' ) . '?configKey=' . $entry['configKey'] . '">' . $entry['configValue'] . '</a>',
			'configGroupKey' => '<a href="' . $oRouter->getPath( 'superConfigTableEdit' ) . '?configKey=' . $entry['configKey'] . '">' . $entry['configGroupKey'] . '</a>',
			'configControls' => '
				<a href="' . $oRouter->getPath( 'superConfigTableEdit' ) . '?configKey=' . $entry['configKey'] . '" class="icon iconEdit iconText">' . _( 'Edit' ) . '</a>
				<a href="' . $oRouter->getPath( 'superConfigTableEdit' ) . '?event=deleteConfig&deleteConfig=' . $entry['configKey'] . '" title="' . _( 'Do you really want to delete this item?' ) . '" class="icon iconDelete iconText linkConfirm">' . _( 'Delete' ) . '</a>'
		);
	}

	if( ($iCount % 2) == 0 ) $oOutputHtmlTable->addBodyEntry( $row, array('class' => 'odd') );
	else $oOutputHtmlTable->addBodyEntry( $row );
	
	++$iCount;
}

// Output
echo '
	<div class="adminConfig view">
		<h1>' . _( 'Config' ) . '</h1>
		' . $sSearchForm . '
		<a href="' . $oRouter->getPath( 'superConfigTableEdit' ) . '?action=add" class="icon iconAdd iconText">' . _( 'Add' ) . '</a>
		<form method="post" action="' . $oRouter->getPath( 'superConfigTableEdit' ) . '">
			' . $oOutputHtmlTable->render() . '
			<input type="hidden" name="frmConfig" value="' . (!empty($_GET['configKey']) ? 'update' : 'create') . '" />
		</form>
		' . $oPagination->render() . '
	</div>';