<?php

// DAO to use
$oArgoCronDao = clRegistry::get( 'clArgoCronDao' . DAO_TYPE_DEFAULT_ENGINE );

// Load scaffolding
clFactory::loadClassFile( 'clScaffold' );
$oScaffold = new clScaffold( $oArgoCronDao );

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

echo '
	<div class="view scaffoldArgoCron">
		<h1>' . _( 'Argo Cron' ) . '</h1>
		<section>
			' . $oScaffold->render() . '
		</section>
	</div>';