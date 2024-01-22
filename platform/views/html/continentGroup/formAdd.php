<?php

$aErr = array();

$oContinentGroup = clRegistry::get( 'clContinentGroup', PATH_MODULE . '/continentGroup/models' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );

// Set current groupKey
if( !empty($_GET['continentGroupKey']) ) {
	$_SESSION['continentGroupKey'] = $_GET['continentGroupKey'];
}

// Add
if( !empty($_POST['frmAddCountries']) && !empty($_SESSION['continentGroupKey']) ) {	
	// Data
	$aData = array();
	foreach( $_POST['chkCountry'] as $key => $entry ) {
		$aData[] = array(
			'entryGroupKey' => $_SESSION['continentGroupKey'],			
			'entryCountryId' => $key,
			'entryContinentCode' => $_GET['continentCode'],
			'entryLocalCountryTitleTextId' => $_POST['entryLocalCountryTitleTextId'][$key],
			'entryStatus' => $_POST['countryStatus'][$key],
			'entryCreated' => date( 'Y-m-d H:i:s' )
		);
	}
	
	// Delete
	$oContinentGroup->deleteByGroupAndContinent( $_SESSION['continentGroupKey'], $_GET['continentCode'] );
	$aErr = clErrorHandler::getValidationError( 'deleteContinentGroup' );
	
	// Create
	foreach( $aData as $entry ) {
		if( !$oContinentGroup->create( $entry ) ) {
			$aErr = clErrorHandler::getValidationError( 'createContinentGroup' );
		}
	}
	
	// Don't work! Probably because textHelper
	// ---------------------------------------
	//$oContinentGroup->oDao->createMultipleData( $aData, array(
	//	'entities' => 'entContinentGroup',
	//	'fields' => array(
	//		'entryGroupKey',			
	//		'entryCountryId',
	//		'entryContinentCode',
	//		'entryLocalCountryTitleTextId',
	//		'entryStatus',
	//		'entryCreated'
	//	),
	//	'groupKey' => 'createContinentGroup'
	//) );
	//$aErr = clErrorHandler::getValidationError( 'createContinentGroup' );
	// ---------------------------------------
	
	// Notify
	if( empty($aErr) ) {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataSaved' => _( 'The data has been saved' )
		) );
	}
}

// Edit
if( !empty($_SESSION['continentGroupKey']) ) {
	$aGroupData = $oContinentGroup->readGroup( '*', $_SESSION['continentGroupKey'] );
	$aData = array();
	foreach( $aGroupData as $entry ) {
		$aData[$entry['entryCountryId']] = array(
			'entryLocalCountryTitleTextId' => $entry['entryLocalCountryTitleTextId'],
			'entryStatus' => $entry['entryStatus']
		);
	}
	
	$sTitle = _( 'Edit continentgroup' );

// New
} else {
	$aGroupData = $_POST;
	$sTitle = _( 'Add continentgroup' );
}

// Continent list
$aContinents = $oContinent->read();
$aContinentList = array();
$aCurrentContinent = null;
foreach( $aContinents as $key => $continent ) {
	$aContinentList[$continent['continentCode']] = $continent['continentName'];
	// Active
	if( !empty($_GET['continentCode']) && $continent['continentCode'] == $_GET['continentCode'] ) {
		$aCurrentContinent = array(
			'code' => $continent['continentCode'],
			'name' => $continent['continentName']
		);
	}
}

// Country table
if( !empty($_GET['continentCode']) ) {
	$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( $_GET['continentCode'] );
	
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oContinent->oDao->getDataDict( 'entContinentCountry' ) );
	$oOutputHtmlTable->setTableDataDict( array(
		'checkbox' => array(
			'title' => _( 'Select' )
		),
		'countryName' => array(
			'title' => _( 'Name' ) . ' (English)'
		),
		'entryLocalCountryTitleTextId' => array(
			'title' => _( 'Local name' )
		),
		'countryContinentCode' => array(),
		'countryIsoCode2' => array(),
		'countryIsoCode3' => array(),
		'countryNumber' => array(),
		'countryStatus' => array(
			'title' => _( 'Status' )
		)
	) );

	// Continent row
	$row = array(
		'checkbox' => '<input type="checkbox" value="all" name="chkCountry[]" id="selectAllCountries" />',
		'countryName' => _( 'All countries in' ) . ' <strong>' . $aCurrentContinent['name'] . '</strong>',
		'entryLocalCountryTitleTextId' => '',
		'countryContinentCode' => '<strong>' . $aCurrentContinent['code'] . '</strong>',
		'countryIsoCode2' => '',
		'countryIsoCode3' => '',
		'countryNumber' => '',
		'countryStatus' => '
			<select title="Status" name="allCountriesStatus" id="allCountriesStatus">
				<option value="null">' . _( 'Select' ) . '</option>
				<option value="active">' . _( 'Active' ) . '</option>
				<option value="inactive">' . _( 'Inactive' ) . '</option>
			</select>'
	);
	$oOutputHtmlTable->addBodyEntry( $row, array( 'style' => 'background: #e2e2e2; height: 26px;' ) );

	foreach( $aCountries as $country ) {
		if( array_key_exists($country['countryId'], $aData) ) {
			$sCheckbox = '<input type="checkbox" checked="checked" name="chkCountry[' . $country['countryId'] . ']" />';
			$sSelect = '
				<select title="Status" name="countryStatus[' . $country['countryId'] . ']" />
					<option value="active"' . ($aData[$country['countryId']]['entryStatus'] == 'active' ? ' selected="selected"' : null) . '>' . _( 'Active' ) . '</option>
					<option value="inactive"' . ($aData[$country['countryId']]['entryStatus'] == 'inactive' ? ' selected="selected"' : null) . '>' . _( 'Inactive' ) . '</option>
				</select>';
			$sName = '<input type="text" value="' . $aData[$country['countryId']]['entryLocalCountryTitleTextId'] . '" name="entryLocalCountryTitleTextId[' . $country['countryId'] . ']" class="text" />';
		} else {
			$sCheckbox = '<input type="checkbox" name="chkCountry[' . $country['countryId'] . ']" />';
			$sSelect = '
				<select title="Status" name="countryStatus[' . $country['countryId'] . ']" />
					<option value="active">' . _( 'Active' ) . '</option>
					<option value="inactive">' . _( 'Inactive' ) . '</option>
				</select>';
			$sName = '<input type="text" value="" name="entryLocalCountryTitleTextId[' . $country['countryId'] . ']" class="text" />';
		}
		
		$row = array(
			'checkbox' => $sCheckbox,
			'countryName' => $country['countryName'],
			'entryLocalCountryTitleTextId' => $sName,
			'countryContinentCode' => $country['countryContinentCode'],
			'countryIsoCode2' => $country['countryIsoCode2'],
			'countryIsoCode3' => $country['countryIsoCode3'],
			'countryNumber' => $country['countryNumber'],
			'countryStatus' => $sSelect
		);
		
		$oOutputHtmlTable->addBodyEntry( $row );
	}

	$sCountryFormTable = '
		<form method="post" id="frmCountries">
			<p class="buttons">
				<button type="submit">' . _( 'Save list' ) . '</button>
			</p>
			' . $oOutputHtmlTable->render() . '
			<input id="frmAddCountries" class="hidden" type="hidden" name="frmAddCountries" value="1" />
			<p class="buttons">
				<button type="submit">' . _( 'Save list' ) . '</button>
			</p>
		</form>';
}

// Group form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oContinentGroup->oDao->getDataDict(), array(
	'attributes' => array( 'class' => 'inline' ),
	'data' => $aGroupData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'labelRequiredSuffix' => '',
	'method' => 'get',
	'buttons' => array(
		'submit' => _( 'Create' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'continentGroupKey' => array(
		'type' => 'string',
		'title' => _( 'Group name' ),
		'value' => !empty($_GET['continentGroupKey']) ? $_GET['continentGroupKey'] : null
	)
) );
$sGroupForm = $oOutputHtmlForm->render();

// Continent form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oContinentGroup->oDao->getDataDict(), array(
	'attributes' => array( 'class' => 'inline' ),
	'data' => null,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'labelRequiredSuffix' => '',
	'method' => 'get',
	'buttons' => array(
		'submit' => _( 'Select' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'continentCode' => array(
		'type' => 'array',
		'values' => $aContinentList,
		'title' => _( 'Continent' )
	),
	'selectContinent' => array(
		'type' => 'hidden',
		'value' => true
	)
) );
$sContinentForm = $oOutputHtmlForm->render();

echo '
	<div class="view continentGroupFormAdd">
		<h1>' . $sTitle . '</h1>
		<div class="groupFormAdd">
			' . $sGroupForm . '
		</div>
		<div class="continentSelect">
			' . (!empty($_GET['continentGroupKey']) ? $sContinentForm : null) . '
		</div>
		<div class="countrySelect">
			' . (!empty($_GET['continentCode']) ? $sCountryFormTable : null) . '
		</div>
	</div>';
	

// Check all countries
$oTemplate->addBottom( array(
	'key' => 'changeAllFreight',
	'content' => '
	<script>
		$("input#selectAllCountries").change(function() {
			$("form#frmCountries").find(":checkbox").attr("checked", this.checked);
		});
	</script>'
) );