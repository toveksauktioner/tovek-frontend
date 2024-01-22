<?php

require_once PATH_FUNCTION . '/fFileSystem.php';

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

/**
 * Post
 */
if( !empty($_POST['frmEditConstant']) ) {
	if( in_array( $_POST['type'], array('boolen','integer') ) ) {
		$sCurrent = "define( '" . $_POST['constant'] . "', " . $_POST['currentValue'] . " );";
		$sReplacement = "define( '" . $_POST['constant'] . "', " . $_POST['newValue'] . " );";
	} else {
		$sCurrent = "define( '" . $_POST['constant'] . "', '" . $_POST['currentValue'] . "' );";
		$sReplacement = "define( '" . $_POST['constant'] . "', '" . $_POST['newValue'] . "' );";
	}
	
	$sFileContent = file_get_contents( $_POST['configFile'] );
	
	$sFileContent = str_replace( $sCurrent, $sReplacement, $sFileContent );
	
	if( !file_put_contents( $_POST['configFile'], $sFileContent ) ) {
		//$oNotification = clRegistry::get( 'clNotificationHandler' );
		//$oNotification->setSessionNotifications( array(
		//	'dataError' => _( 'The data has not been saved' )
		//) );
	} else {
		//$oNotification = clRegistry::get( 'clNotificationHandler' );
		//$oNotification->setSessionNotifications( array(
		//	'dataSaved' => _( 'The data has been saved' )
		//) );
	}
	
	//$oRouter->redirect( $oRouter->sPath );
}

/**
 * Find all config files
 */
$aConfigFiles = regexScanDir( '/(.*?)cf(.*?)+\.php$/i', PATH_CONFIG ) + regexScanDir( '/(.*?)cf(.*?)+\.php$/i', PATH_MODULE );

$aDynamicConfigFiles = array();
$aConstants = array();

foreach( $aConfigFiles as $sConfigFile ) {
	$oFile = @fopen( $sConfigFile, "r" ) or die( 'Failed opening file: error was ' . $php_errormsg );
	
	$sLabel = '';
	$mCurrentValue = '';
	$sConstant = '';
	$aParams = array();
	
	$iLineNo = 0;
	while( !feof($oFile) ) {
		$iLineNo++;
		
		$sLine = trim( fgets($oFile) );
		
		if( $iLineNo == 1 || empty($sLine) ) {
			// Jump over first & empty lines
			continue;
		}
		
		if( strpos($sLine, '#dynamic') !== false ) {
			// File seems to be dynmic
			$aDynamicConfigFiles[] = $sConfigFile;
			
			$aLine = explode( ' ', $sLine );
			$sLabel = $aLine[1];
			continue;
			
		} elseif( substr($sLine, 0, 2) == '//' ) {
			// File is not dynamic, skip to next file
			continue;
			
		} elseif( empty($sLabel) ) {
			// File is not dynamic, skip to next file
			break;
			
		}
		
		/**
		 * Check if row is dynamic
		 */
		$bDynamicRow = false;
		$aTypes = array( '# integer', '# boolen', '# string', '# enum' );
		foreach( $aTypes as $sType ) if( strpos($sLine, $sType) !== false ) $bDynamicRow = true;
		if( $bDynamicRow === false ) continue;
		
		// Get constant from string
		preg_match( '#\((([^()]+|(?R))*)\)#', $sLine, $aMatches );
		if( !empty($aMatches) ) {
			$aConstant = explode( "'", trim($aMatches[2]) );
			$sConstant = $aConstant[1];
			if( !empty($aConstant[3]) ) {
				$mCurrentValue = $aConstant[3];
			} else {
				$mCurrentValue = substr( $aConstant[2], 2 );
			}			
		} else {
			continue;
		}
		
		if( strpos($sLine, '# integer') !== false ) {
			$aLineInfo = explode( ' ', strstr( $sLine, '#' ) );
			unset( $aLineInfo[0], $aLineInfo[1] );
			$aParams[$sConstant] = array(
				'type' => 'integer',
				'title' => implode( ' ', $aLineInfo ),
				'defaultValue' => $mCurrentValue,
				'file' => $sConfigFile
			);
		}
			
		if( strpos($sLine, '# boolen') !== false ) {			
			$aLineInfo = explode( ' ', strstr( $sLine, '#' ) );
			unset( $aLineInfo[0], $aLineInfo[1] );
			$aParams[$sConstant] = array(
				'type' => 'boolen',
				'title' => implode( ' ', $aLineInfo ),
				'defaultValue' => $mCurrentValue,
				'file' => $sConfigFile
			);			
		}
		
		if( strpos($sLine, '# string') !== false ) {
			$aLineInfo = explode( ' ', strstr( $sLine, '#' ) );
			unset( $aLineInfo[0], $aLineInfo[1] );
			$aParams[$sConstant] = array(
				'type' => 'string',
				'title' => implode( ' ', $aLineInfo ),
				'defaultValue' => $mCurrentValue,
				'file' => $sConfigFile
			);
		}
		
		if( strpos($sLine, '# enum') !== false ) {
			$aLineInfo = explode( ' ', strstr( $sLine, '#' ) );
			preg_match( '#\((([^()]+|(?R))*)\)#', $aLineInfo[1], $aMatches );		
			$aValues = explode( ',', $aMatches[2] );
			unset( $aLineInfo[0], $aLineInfo[1] );					
			$aParams[$sConstant] = array(
				'type' => 'array',
				'title' => implode( ' ', $aLineInfo ),
				'values' => array_combine( array_keys(array_flip($aValues)), $aValues ),
				'defaultValue' => $mCurrentValue,
				'file' => $sConfigFile
			);
		}
	}
	
	fclose( $oFile );
	
	if( !empty($sLabel) ) {
		$aConstants[$sLabel] = $aParams;
	}	
}

$aTableDict = array(
	'entConfigContant' => array(
		'constant' => array(
			'title' => _( 'Constant' )
		),
		'value' => array(
			'title' => _( 'Value' )
		)
	)
);

$sOutput = '';

foreach( $aConstants as $sGroupLabel => $aGroup ) {
	/**
	 * Table init
	 */	
	$oOutputHtmlTable = new clOutputHtmlTable( $aTableDict );
	$oOutputHtmlTable->setTableDataDict( current($aTableDict) );
	
	/**
	 * Table rows
	 */
	foreach( $aGroup as $sLabel => $aAttributes ) {		
		$sForm = '';
		
		switch( $aAttributes['type'] ) {
			case 'boolen':
				$sForm = $oOutputHtmlForm->createSelect( 'newValue', $aAttributes['title'], array('true' => 'true', 'false' => 'false'), (string) $aAttributes['defaultValue'] );				
				break;
			case 'array':				
				$sForm = $oOutputHtmlForm->createSelect( 'newValue', $aAttributes['title'], $aAttributes['values'], (string) $aAttributes['defaultValue'] );
				break;
			default:
				$sForm = $oOutputHtmlForm->createInput(  'text', 'newValue', array('value' => $aAttributes['defaultValue']) );
				break;
		}
		
		$sForm .= $oOutputHtmlForm->createInput( 'hidden', 'configFile', array('value' => $aAttributes['file']) );
		$sForm .= $oOutputHtmlForm->createInput( 'hidden', 'constant', array('value' => $sLabel) );
		$sForm .= $oOutputHtmlForm->createInput( 'hidden', 'type', array('value' => $aAttributes['type']) );
		$sForm .= $oOutputHtmlForm->createInput( 'hidden', 'currentValue', array('value' => $aAttributes['defaultValue']) );
		$sForm .= $oOutputHtmlForm->createInput( 'hidden', 'frmEditConstant', array('value' => 1) );
		
		$oOutputHtmlTable->addBodyEntry( array(
			'constant' => $sLabel,
			'value' => $oOutputHtmlForm->createForm( 'post', '', $sForm, array( 'id' => 'frm' . $sLabel ) )
		) );
	}
	
	$sOutput .= '
		<section class="dataOutput">
			<h2>' . _( $sGroupLabel ) . '</h2>
			' . $oOutputHtmlTable->render() . '
		</section>';
}

echo '
	<div class="view config constantTableEdit">
		<h1>' . _( 'Constants' ) . '</h1>
		' . $sOutput . '
	</div>';

if( empty($_GET['ajax']) ) {
	$oTemplate->addBottom( array(
		'key' => 'changeAction',
		'content' => '
			<script>
				$(document).delegate( ".view .dataOutput .dataTable tbody tr td form", "change", function() {
					var sSelectedId = $(this).attr("id");
					
					var jqxhr = $.post( "?ajax=true&view=config/constantTableEdit.php", $(this).serialize(), function() {
						console.log( "success" );
					} )
					.done( function(data) {
						$.ajax(	{
							url: "?ajax=true&view=config/constantTableEdit.php",
						} ).done( function(data) {
							$(".view.config.constantTableEdit").replaceWith(data);
							
							$("select").each( function() {
								$(this).wrap(\'<div class="select input"></div>\');
							} );
							
							var eParentTr = $("#" + sSelectedId).parent("td").parent("tr");
							$(eParentTr).after("<tr id=\"ajaxNotification\"><td colspan=\"2\"><ul class=\"notification\"><li class=\"notification dataSaved\">' . _( 'Update successful' ) . '</li></ul></td></tr>");
							$("#ajaxNotification").delay(1000).slideUp(800);
						} );					
					} )
					.fail( function() {
						console.log( "error" );
					} )
					.always( function() {
						console.log( "finished" );
					} );
				} );
			</script>
		'
	) );
}