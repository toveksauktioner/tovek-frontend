<?php

$oUserLog = clRegistry::get( 'clUserLog', PATH_MODULE . '/userlog/models' );
$aLogEntries = $oUserLog->oDao->readData( array(
	'fields' => '*',
	'userlogId' => '*',
	'entries' => 5
) );

$aTableDict = current( $oUserLog->oDao->getDataDict() );
unset( $aTableDict['userlogUpdated'] );

$sOutput = "";
if( !empty($aLogEntries) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oUserLog->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $aTableDict );

	foreach( $aLogEntries as $aEntry ) {
		$oOutputHtmlTable->addBodyEntry( $aEntry );
	}
	$sOutput = $oOutputHtmlTable->render();
} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view dashboard listLogs">
		<h3>' . _( 'Logs' ) . '</h3>
		<section>
			<a href="' . $oRouter->getPath( 'adminUserLog' ) . '">' . _( 'View all logs' ) . '</a>
			' . $sOutput . '
		</section>
	</div>';