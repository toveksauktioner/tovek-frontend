<?php

$aErr = array();

if( !empty($_GET['viewId']) ) {
	// Get view info
	$oView = clRegistry::get( 'clViewHtml' );
	$aViewData = $oView->read( '*', $_GET['viewId'] );

	$sFilename = pathinfo($aViewData['viewFile'], PATHINFO_FILENAME );
	$sCSSPath = PATH_VIEW_CSS . '/html/' . $aViewData['viewModuleKey'] . '/' . $sFilename . '.css';
	
	if( !empty( $_POST['frmAddViewCss'] ) ) {
		if( !file_exists( dirname( $sCSSPath ) ) ) {
			mkdir( dirname( $sCSSPath ), 0777, true );
		}

		$rFile = fopen( $sCSSPath, 'w' );
		fwrite( $rFile, $_POST['cssContent'] );
		fclose( $rFile );
	}

	$sContent = file_exists( $sCSSPath ) ? file_get_contents( $sCSSPath ) : '';

	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( array(
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
	), array(
		'action' => '',
		'attributes' => array( 'class' => 'inTable' ),
		'data' => array(
			'cssContent' => $sContent
		),
		'errors' => $aErr,
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Save' )
		),
	) );
	echo $oOutputHtmlForm->render();
	
	$oTemplate->addScript( array(
		'key' => 'jqueryTabbyJs',
		'src' => '/js/jquery.tabby.js'
	) );
	$oTemplate->addBottom( array(
		'key' => 'viewCssJs',
		'content' => '
		<script>
			$("#cssContent").tabby();
		</script>'
	) );
}
