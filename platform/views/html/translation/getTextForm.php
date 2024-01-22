<?php

$oLayout = clRegistry::get( 'clLayoutHtml' );
$oLayout->setAcl( $oUser->oAcl );

// Data
if( !empty($_GET['layoutTemplateFile']) ) {
	$oLayout->oDao->setCriterias( array(
		'layoutTemplateFile' => array(
			'type' => '=',				
			'fields' => 'layoutTemplateFile',
			'value' => $_GET['layoutTemplateFile']
		)
	) );
}
$aLayouts = $oLayout->read();

$oLayout->oDao->sCriterias = null;

$aSectionsAndViews = $oLayout->readSectionsAndViews( arrayToSingle($aLayouts, null, 'layoutKey') );

$aStringData = array();
$aStrings = array();
$aViews = array();

foreach( $aSectionsAndViews as $aSectionView ) {
	$sViewFile = $aSectionView['viewModuleKey'] . '/' . $aSectionView['viewFile']; 
	
	if( in_array($sViewFile, $aViews) ) {
		// Duplicate, continue
		continue;
	}
	
	$sFileContent = file_get_contents( PATH_VIEW_HTML . '/' . $sViewFile );
	preg_match_all( '#\_\((.*?)\)#', $sFileContent, $aResult );
	
	$GLOBALS['userRegister']['subject'] = "testaross";
	$varTest = array( "tt" => "Testar" );
	
	if( !empty($aResult) ) {
		foreach( $aResult[1] as $sString ) {
			if( strpos($sString, '$GLOBALS') !== false ) {
				/**
				 * Globals
				 */
				$sString = trim( $sString );
				
				preg_match_all( '/\'([^\']+)\'/', $sString, $aMatches );				
				
				switch( count($aMatches[1]) ) {
					case 1:
						$var1 = $aMatches[1][0];
						$sText = "{$GLOBALS[$var1]}";												
						break;
					case 2:
						$var1 = $aMatches[1][0];
						$var2 = $aMatches[1][1];
						$sText = "{$GLOBALS[$var1][$var2]}";												
						break;
					case 3:
						$var1 = $aMatches[1][0];
						$var2 = $aMatches[1][1];
						$var3 = $aMatches[1][2];
						$sText = "{$GLOBALS[$var1][$var2][$var3]}";												
						break;
				}
				
				if( in_array(strtolower( strip_tags($sText) ), $aStrings) ) {
					continue;
				}
				
				$aStrings[] = strtolower( strip_tags($sText) );
				
				$aStringData[ $sText ] = array(
					'String' => $sText,
					'Translation' => ''
				);
			
			} elseif( strpos($sString, '$') !== false ) {
				/**
				 * Varible
				 */
				continue;
				
			} else {
				/**
				 * Normal
				 */
				$sString = trim( str_replace( "'", '', $sString ) );
				$sString = str_replace( '"', '', $sString );
				
				if( in_array(strtolower( strip_tags($sString) ), $aStrings) ) {
					continue;
				}
				
				$aStrings[] = strtolower( strip_tags($sString) );
				
				$aStringData[ $sString ] = array(
					'String' => $sString,
					'Translation' => ''
				);
			}
		}
	}		
	$aViews[] = $sViewFile;
}

ksort( $aStringData );

if( !empty($_GET['export']) && $_GET['export'] == 'excel' ) {
	$oPhpExcel = clRegistry::get( 'clPhpExcel', PATH_CORE . '/phpExcel' );	
	$oPhpExcel->createFile( $aStringData, 'Strings', array(
		'title' => 'Strings',
		'label' => true,
		'download' => true
	) );
}

if( !empty($_GET['export']) && $_GET['export'] == 'csv' ) {
	echo implode( "\r\n", $aStrings );
	header( 'Content-type: text/csv' );
	header( 'Content-Disposition: attachment; filename=translation.csv' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	exit;
}

$aTemplates = scandir( PATH_TEMPLATE );
unset( $aTemplates[0], $aTemplates[1] );

// Filter form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oLayout->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array(
		'class' => 'inline',
		'style' => 'display: inline-block; margin: 0;'
	),
	'method' => 'get',
	'data' => array(),
	'buttons' => array(
		'submit' => _( 'Filter' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'layoutTemplateFile' => array(
		'type' => 'array',
		'title' => _( 'Template file' ),
		'values' => array(
			'*' => _( 'All template files' )
		) + array_combine( $aTemplates, $aTemplates ),
	),
	'frmFilter' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

$sOutput = '
	<h2>' . _( 'List of strings' ) . '</h2>
	<hr />
		<a href="' . $oRouter->sPath . '?export=excel' . (!empty($_GET['layoutTemplateFile']) ? '&layoutTemplateFile=' . $_GET['layoutTemplateFile'] : '') . '" class="icon iconText iconDbExport">' . _( 'Export in Excel' ) . '</a>
		&nbsp;&nbsp;|&nbsp;&nbsp;
		<a href="' . $oRouter->sPath . '?export=csv' . (!empty($_GET['layoutTemplateFile']) ? '&layoutTemplateFile=' . $_GET['layoutTemplateFile'] : '') . '" class="icon iconText iconDbExport">' . _( 'Export in CSV' ) . '</a>
		&nbsp;&nbsp;|&nbsp;&nbsp;
		' . $oOutputHtmlForm->render() . '
	<hr />	
	<section>
		<h3>' . sprintf( _( 'Found %s strings' ), count($aStrings) ) . ':</h3>
		' . implode( "<br />", $aStrings ) . '
	</section>';

echo '
	<div class="view translation textHelp">
		<h1>' . _( 'Translation: Text helpers' ) . '</h1>
		<div class="ui-tabs ui-widget ui-widget-content ui-corner-all">			
			<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header">
				<li class="ui-state-default ui-corner-top">
					<a href="' . $oRouter->getPath( 'superTranslationOverview' ) . '">' . _( 'Overview' ) . '</a>
				</li>
				<li class="ui-state-default ui-corner-top">
					<a href="' . $oRouter->getPath( 'superTranslationTextHelp' ) . '">' . _( 'Text helpers' ) . '</a>
				</li>
				<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active">
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