<?php

$aErr = array();
$oLayout = clRegistry::get( 'clLayoutHtml' );

$bIsAllowed = false;

if( !empty($_GET['layoutKey']) ) {
	// Does layout exist
	$aData = $oLayout->read( null, $_GET['layoutKey'] );
	
	if( !empty($aData) ) {
		$bIsAllowed = $oLayout->isAllowed( $_GET['layoutKey'] );
	} else {
		$sTitle = 'CSS - Unknown';
		$oNotification->set( array(
			'dataInformation' => _( 'Layout does not exists!' )
		) );
	}
	
	if( $bIsAllowed === true ) {
		// Save
		if( !empty($_POST['frmAddViewCss']) ) {
			$rFile = fopen( PATH_LAYOUT_CSS . '/' . $_GET['layoutKey'] . '.css', 'w' );
			fwrite( $rFile, $_POST['cssContent'] );
			fclose( $rFile );
			
			if( isset($_POST['submitAndGoToList']) ) {
				$oRouter->redirect( $oRouter->getPath( 'superInfoContentPages' ) );
			}
			
			$oNotification->set( array(
				'dataSaved' => _( 'Your data has been saved' )
			) );
		}
		
		// Edit
		if( file_exists( PATH_LAYOUT_CSS . '/' . $_GET['layoutKey'] . '.css' ) ) {
			$sContent = file_get_contents( PATH_LAYOUT_CSS . '/' . $_GET['layoutKey'] . '.css' );
			$sTitle = 'CSS - Edit';
		} else {
			// New
			$sContent = '';
			$sTitle = 'CSS - Create';
		}	
		
		$aDataDict = array(
			'entViewCss' => array(
				'cssContent' => array(
					'type' => 'string',
					'title' => _( 'Content' ),
					'appearance' => 'full'
				),
				'frmAddViewCss' => array(
					'type' => 'hidden',
					'value' => true
				)
			)
		);
		
		$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
		$oOutputHtmlForm->init( $aDataDict, array(
			'action' => '',
			'attributes' => array( 'class' => 'inTable' ),
			'data' => array(
				'cssContent' => $sContent
			),
			'errors' => $aErr,
			'method' => 'post',
			'buttons' => array(
				'submit' => array(
					'content' => _( 'Save' ),
					'attributes' => array(
						'name' => 'submit',
						'type' => 'submit'
					)
				),
				'submitAndGoToList' => array(
					'content' => _( 'Save and go to list' ),
					'attributes' => array(
						'name' => 'submitAndGoToList',
						'type' => 'submit'
					)
				)
			)
		) );
		$sForm = $oOutputHtmlForm->render();
		
	} elseif( !empty($aData) ) {
		$sTitle = 'CSS - Error';
		$oNotification->set( array(
			'dataError' => _( 'No access - ' . $_GET['layoutKey'] )
		) );	
	}
	
	echo '
		<div class="adminLayoutCss view">
			<h1>' . _( $sTitle ) . '</h1>
			' . (!empty($sForm) ? $sForm : null) . '
		</div>';
} else {
	echo '<strong>' . _( 'Missing layout!' ) . '</strong>';
}

//$oTemplate->addScript( array(
//	'key' => 'jqueryTabbyJs',
//	'src' => '/js/jquery.tabby.js'
//) );
//$oTemplate->addBottom( array(
//	'key' => 'viewCssJs',
//	'content' => '
//	<script>
//		$("#cssContent").tabby();
//	</script>'
//) );

/**
 * Codemirror
 */
$oTemplate->addScript( array(
	'key' => 'codemirrorJs',
	'src' => '/modules/tinymce/plugins/codemirror/codemirror-4.8/lib/codemirror.js'
) );
$oTemplate->addScript( array(
	'key' => 'codemirrorJsCss',
	'src' => '/modules/tinymce/plugins/codemirror/codemirror-4.8/mode/css/css.js'
) );
$oTemplate->addLink( array(
	'key' => 'codemirrorCss',
	'href' => '/modules/tinymce/plugins/codemirror/codemirror-4.8/lib/codemirror.css'
) );
$oTemplate->addBottom( array(
	'key' => 'codemirrorInit',
	'content' => '
	<script>
		var editor = CodeMirror.fromTextArea( document.getElementById("cssContent"), {
			lineNumbers: true,
			mode: "text/css"
		} );
  </script>'
) );