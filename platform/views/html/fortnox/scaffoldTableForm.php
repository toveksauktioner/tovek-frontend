<?php

/**
 * Resource select form
 */
$aAllResources = array();
require_once PATH_FUNCTION . '/fFileSystem.php';
$aResourceFiles = regexScanDir( '/cl(.*?)+\.php$/i', PATH_MODULE . '/fortnox/models/resources' );
if( !empty($aResourceFiles) ) {
	foreach( $aResourceFiles as $sFile ) {
		$sClassName = str_replace( '.php', '', $sFile );
		$aAllResources[$sClassName] = $sClassName;
	}	
}

$_REQUEST['useResource'] = !empty($_REQUEST['useResource']) ? $_REQUEST['useResource'] : key( $aAllResources );

$oFortnoxResource = clRegistry::get( $_REQUEST['useResource'], PATH_MODULE . '/fortnox/models/resources' );
$oFortnoxScaffold = clRegistry::get( 'clFortnoxScaffold', PATH_MODULE . '/fortnox/models' );

// Create
if( !empty($_POST['frmAddFortnoxScaffold']) ) {
	unset( $_POST['frmAddFortnoxScaffold'] );
	$oFortnoxResource->post( $_POST );
	$oRouter->redirect( $oRouter->sPath );
}

// Update
if( !empty($_POST['frmEditFortnoxScaffold']) ) {
	unset( $_POST['frmEditFortnoxScaffold'] );
	$oFortnoxResource->put( $_POST[ $oFortnoxArticle->sPrimaryField ], $_POST );
	$oRouter->redirect( $oRouter->sPath );
}

// Delete
if( !empty($_GET['deleteFortnoxScaffold']) ) {	
	$oFortnoxResource->delete( $_GET['deleteFortnoxScaffold'] );
	$oRouter->redirect( $oRouter->sPath );
}

$oFortnoxScaffold->init( $oFortnoxResource );

/**
 * Resource select form
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$sResourceForm = $oOutputHtmlForm->createField( 'useResource', _( 'Resource' ) . ':', $oOutputHtmlForm->createSelect( 'useResource', _( 'Resource' ), $aAllResources, $_REQUEST['useResource'], array(
	'onchange' => 'this.form.submit();'
) ) );
$sResourceForm = $oOutputHtmlForm->createForm( 'get', '', $sResourceForm, array('class' => 'inline') );

echo '
	<div class="view fortnox">
		<h1>' . _( 'Fortnox scaffold' ) . '</h1>
		<section class="tools">
			<div class="tool">
				' . $sResourceForm . '
			</div>
		</section>
		<section>
			' . $oFortnoxScaffold->render() . '
		</section>
	</div>';



