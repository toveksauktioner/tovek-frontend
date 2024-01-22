<?php

$oDb = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );

$aTables = arrayToSingle( $oDb->query( "select * from information_schema.tables where TABLE_SCHEMA = '" . DB_DATABASE . "'" ), null, 'TABLE_NAME' );

foreach( $aTables as $key => $sTable ) {	
	if( substr($sTable, 0, 3) != 'ent' ) {
		unset( $aTables[ $key ] );
	}
	if( substr($sTable, (strlen($sTable) - 4), 4) != 'Text' ) {
		unset( $aTables[ $key ] );
	}
}

$aRows = array();
$aStringData = array();
$aTexts = array();

foreach( $aTables as $key => $sTable ) {	
	$aRows[] = '<table>' . $sTable . '</table>';
	
	$aData = $oDb->query( "select * from " . $sTable . " where textLangId = " . (int) $GLOBALS['langIdEdit'] );
	
	if( !empty($aData) ) {
		foreach( $aData as $aEntry ) {
			if( empty($aEntry['textContent']) ) {
				continue;
			}
			
			// Remove HTML from text
			$sText = strip_tags( trim($aEntry['textContent']) );
			
			// Replace line breaks
			$sText = str_replace( array("\r", "\n"), " ", $sText );
			preg_replace( "/\r|\n/", " ", $sText );
			
			// Replace more stuff
			$sText = str_replace( '&nbsp;', ' ', $sText );
			
			$sText = str_replace( '&aring;', 'å', $sText );
			$sText = str_replace( '&auml;', 'ä', $sText );
			$sText = str_replace( '&ouml;', 'ö', $sText );
			$sText = str_replace( '&Aring;', 'Å', $sText );
			$sText = str_replace( '&Auml;', 'Ä', $sText );
			$sText = str_replace( '&Ouml;', 'Ö', $sText );
			
			$sText = str_replace( '&copy;', '©', $sText );
			$sText = str_replace( '&eacute;', 'é', $sText );
			
			if( in_array(trim($sText), $aTexts) ) {
				// Duplicate
				continue;
			}
			
			// Data as rows
			$aRows[] = '<textId>' . $aEntry['textId'] . '</textId>';
			$aRows[] = '<textLangId>' . $aEntry['textLangId'] . '</textLangId>';
			$aRows[] = '<textContent>' . $aEntry['textContent'] . '</textContent>';
			$aRows[] = "<translation-start>\r\n\r\nType here!\r\n\r\n</translation-stop>";
			
			// Data as struct array
			$aStringData[] = array(
				'entity' => $sTable,
				'primaryId' => $aEntry['textId'],
				'langId' => $aEntry['textLangId'],
				'Text' => trim( $sText ),
				'Translation' => ''
			);
			
			$aTexts[] = trim( $sText );
		}
	} else {
		$aRows[] = 'empty';
	}
	
	$aRows[] = "";
}

if( !empty($_GET['export']) && $_GET['export'] == 'excel' ) {
	$oPhpExcel = clRegistry::get( 'clPhpExcel', PATH_CORE . '/phpExcel' );	
	$oPhpExcel->createFile( $aStringData, 'Strings', array(
		'title' => 'Strings',
		'label' => true,
		'download' => true
	) );
}

if( !empty($_GET['export']) && $_GET['export'] == 'csv' ) {
	echo implode( "\r\n", $aRows );
	header( 'Content-type: text/csv' );
	header( 'Content-Disposition: attachment; filename=translation.csv' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	exit;
}

$sOutput = '
	<h2>' . _( 'List of texts' ) . '</h2>
	<hr />
		<a href="' . $oRouter->sPath . '?export=excel" class="icon iconText iconDbExport">' . _( 'Export in Excel' ) . '</a>
		&nbsp;&nbsp;|&nbsp;&nbsp;
		<a href="' . $oRouter->sPath . '?export=csv" class="icon iconText iconDbExport">' . _( 'Export in CSV' ) . '</a>
	<hr />
	<section>
		<h3>' . sprintf( _( 'Found %s texts' ), count($aStringData) ) . ':</h3>
		- ' . implode( "<br />- ", $aTexts ) . '
	</section>';

echo '
	<div class="view translation textHelp">
		<h1>' . _( 'Translation: Text helpers' ) . '</h1>
		<div class="ui-tabs ui-widget ui-widget-content ui-corner-all">			
			<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header">
				<li class="ui-state-default ui-corner-top">
					<a href="' . $oRouter->getPath( 'superTranslationOverview' ) . '">' . _( 'Overview' ) . '</a>
				</li>
				<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active">
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



