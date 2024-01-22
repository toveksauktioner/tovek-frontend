<?php

// DAO to use
$oLocaleDao = clRegistry::get( 'clLocaleDao' . DAO_TYPE_DEFAULT_ENGINE );

// Load scaffolding
clFactory::loadClassFile( 'clScaffold' );
$oScaffold = new clScaffold( $oLocaleDao );

// Create
if( !empty($_POST['frmAddScaffold']) && $_POST['frmAddScaffold'] == true ) {
	$oScaffold->create( $_POST );
}
// Delete
if( !empty($_GET['deleteScaffold']) && ctype_digit($_GET['deleteScaffold']) ) {
	$oScaffold->delete( $_GET['deleteScaffold'] );
}
// Update
if( !empty($_POST['frmEditScaffold']) && $_POST['frmEditScaffold'] == true ) {
	$oScaffold->update( $_POST[ $oScaffold->oModuleDao->sPrimaryField ], $_POST );
}

$sOutput = '
	<div class="view scaffold locale">
		<h1>' . _( 'Locales' ) . '</h1>
		<section>' . $oScaffold->render( array( 'delete' => false ) ) . '</section>
	</div>';

echo '
	<div class="view translation overview">
		<h1>' . _( 'Translation overview' ) . '</h1>
		<div class="ui-tabs ui-widget ui-widget-content ui-corner-all">			
			<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header">
				<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active">
					<a href="' . $oRouter->getPath( 'superTranslationOverview' ) . '">' . _( 'Overview' ) . '</a>
				</li>
				<li class="ui-state-default ui-corner-top">
					<a href="' . $oRouter->getPath( 'superTranslationTextHelp' ) . '">' . _( 'Text helpers' ) . '</a>
				</li>
				<li class="ui-state-default ui-corner-top">
					<a href="' . $oRouter->getPath( 'superTranslationGetText' ) . '">' . _( 'Get-texts' ) . '</a>
				</li>
				<li class="ui-state-default ui-corner-top">
					<a href="' . $oRouter->getPath( 'superTranslationGoogle' ) . '">' . _( 'Google translate' ) . '</a>
				</li>
			</ul>
			<div id="translationContent" class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-helper-clearfix">
				' . $sOutput . '
			</div>
		</div>
	</div>';
		