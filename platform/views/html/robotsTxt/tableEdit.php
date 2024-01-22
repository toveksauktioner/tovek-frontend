<?php

$aErr = array();
$sOutput = '';
$bFocus = false;

$oRobotsTxt = clRegistry::get( 'clRobotsTxt', PATH_MODULE . '/robotsTxt/models' );

// Sort
$oRobotsTxt->oDao->aSorting = array(
	'ruleSort' => 'ASC'
);

// Post
if( !empty($_POST['frmAddRule']) ) {
	// Update
	if( !empty($_GET['ruleId']) && ctype_digit($_GET['ruleId']) ) {
		$oRobotsTxt->update( $_GET['ruleId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateRobotsTxt' );
		if( empty($aErr) ) unset( $_GET['ruleId'] );

	// Create
	} else {
		$bFocus = true;
		$_POST['ruleCreated'] = date( 'Y-m-d H:i:s' );
		$iRobotsTxtId = $oRobotsTxt->create($_POST);
		$aErr = clErrorHandler::getValidationError( 'createRobotsTxt' );
	}
}

// All rules
$aRulesList = $oRobotsTxt->read();

// Edit
if( !empty($_GET['ruleId']) && ctype_digit($_GET['ruleId']) ) {
	$aRobotsTxtData = current( $oRobotsTxt->read( '*', $_GET['ruleId']) );
	$sTitle = _( 'Edit robotsTxt' );
} else {
	// New
	$aRobotsTxtData = $_POST;
	$sTitle = _( 'Robots txt' );
}

$aRobotsDataDict = $oRobotsTxt->oDao->getDataDict();

// Table
clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $aRobotsDataDict );
$oOutputHtmlTable->setTableDataDict( array(
	'ruleSort' => array(),
	'ruleType' => array(),
	'ruleVariable' => array(),
	'ruleActivation' => array(),
	'ruleControls' => array(
		'title' => ''
	)
) );

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aRobotsDataDict, array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aRobotsTxtData,
	'errors' => $aErr,
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	),
) );
$oOutputHtmlForm->setFormDataDict( array(
	'ruleSort' => array(),
	'ruleType' => array(),
	'ruleVariable' => array(),
	'ruleActivation' => array(),
	'frmAddRule' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// URL
$sEditUrl = $oRouter->sPath;

// Add form
$aRuleForm = array(
	'ruleSort' => null,
	'ruleType' => $oOutputHtmlForm->renderFields( 'ruleType' ),
	'ruleVariable' => $oOutputHtmlForm->renderFields( 'ruleVariable' ),
	'ruleActivation' => $oOutputHtmlForm->renderFields( 'ruleActivation' ),
	'ruleControls' => $oOutputHtmlForm->renderFields( 'frmAddRule' ) . $oOutputHtmlForm->renderFields( 'ruleControls' ) . $oOutputHtmlForm->renderButtons()
);
if( empty($_GET['ruleId']) ) {
	$oOutputHtmlTable->addBodyEntry( $aRuleForm, array(
		'id' => 'frmRuleAdd'
	) );
}

if( !empty($aRulesList) ) {

	// So that we can print readable values
	$aRuleTypes =& $aRobotsDataDict['entRobotsTxt']['ruleType']['values'];
	$aRuleActivations =& $aRobotsDataDict['entRobotsTxt']['ruleActivation']['values'];

	foreach( $aRulesList as $entry ) {
		if( !empty($_GET['ruleId']) && $_GET['ruleId'] == $entry['ruleId'] ) {
			// Add form
			$row = $aRuleForm;
		} else {
			// Existing rules
			$row = array(
				'ruleSort' => $entry['ruleSort'],
				'ruleType' => $aRuleTypes[ $entry['ruleType'] ],
				'ruleVariable' => $entry['ruleVariable'],
				'ruleActivation' => $aRuleActivations[ $entry['ruleActivation'] ],
				'ruleControls' => '
					<a href="' . $sEditUrl . '?ruleId=' . $entry['ruleId'] . '" class="ajax icon iconEdit iconText">' . _( 'Edit' ) . '</a>
					<a href="' . $oRouter->sPath . '?event=deleteRobotsTxtRule&amp;deleteRobotsTxtRule=' . $entry['ruleId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
			);
		}
		// Add row
		$oOutputHtmlTable->addBodyEntry( $row, array('id' => 'sortRobotsTxtRule_' . $entry['ruleId']) );
	}
}

// Sort script
$oTemplate->addBottom( array(
	'key' => 'ruleSortable',
	'content' => '
	<script>
		$(".robotsTxtTable table tbody").sortable({
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortRobotsTxtRule&sortRobotsTxtRule=1&" + $(this).sortable("serialize"));
			}
		});
	</script>'
) );

if( $bFocus === true ) {
	$oTemplate->addBottom( array(
		'key' => 'ruleAddFocus',
		'content' => '
		<script>
			$(document).ready( function() {
				$("#frmRuleAdd").show();
				$("#frmRuleAdd input").focus();
			} );
		</script>'
	) );
}

echo '
	<div class="view robotsTxtTable">
		<h1>' . $sTitle . '</h1>
		<div class="addControl">
			<a href="#frmRuleAdd" class="toggleShow icon iconText iconAdd">' . _( 'Add rule' ) . '</a>
		</div>
		' . $oOutputHtmlForm->renderErrors() . '
		' . $oOutputHtmlForm->renderForm( $oOutputHtmlTable->render() ) . '
		' . ( empty($aRulesList) ? '<strong>' . _('There are no items to show') . '</strong>' : '' ) . '
	</div>
';