<?php

$aErr = array();

$oGoogleTranslate = clRegistry::get( 'clGoogleTranslate', PATH_MODULE . '/googleTranslate/models/' );

if( !empty($_POST['frmTranslate']) ) {
	$_POST['textContent'] = strip_tags( $_POST['textContent'] );
	
	$oGoogleTranslate->setLanguages( $_POST['translateFrom'], $_POST['translateTo'] );
	$sTranslateText = $oGoogleTranslate->translate( $_POST['textContent'] );
}

$aDataDict = array(
	'googleTranslate' => array(
		'textContent' => array(
			'type' => 'string',
			'title' => _( 'Text to translated' ),
			'required' => true
		),
		'translateFrom' => array(
			'type' => 'array',
			'title' => _( 'Translate from' ),
			'values' => array(
				'swedish' => _( 'Swedish' ),
				'english' => _( 'English' ),
				'german' => _( 'German' ),
				'italian' => _( 'Italian' )
			),
			'required' => true
		),
		'translateTo' => array(
			'type' => 'array',
			'title' => _( 'Translate to' ),
			'values' => array(				
				'english' => _( 'English' ),
				'swedish' => _( 'Swedish' ),
				'german' => _( 'German' ),
				'italian' => _( 'Italian' )
			),
			'required' => true
		)
	)				   
);

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'attributes' => array('class' => 'vertical'),
	'data' => $_POST,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Translate' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'translateFrom' => array(
		'fieldAttributes' => array(
			'class' => 'translateFrom'					   
		)						 
	),
	'translateTo' => array(
		'fieldAttributes' => array(
			'class' => 'translateTo'					   
		)						 
	),
	'textContent' => array(
		'appearance' => 'full',
		'fieldAttributes' => array(
			'class' => 'textContent'					   
		)
	),
	'frmTranslate' => array(
		'type' => 'hidden',
		'value' => true
	)
) );
$sForm = $oOutputHtmlForm->render();

if( empty($_POST['frmTranslate']) ) {
	$sOutput = '
		<div class="view frmTranslate">
			<h1>Google translate</h1>
			' . $sForm . '
			<h2>' . _( 'Translation' ) . '...</h2>
			<textarea readonly="yes">&nbsp;</textarea>
		</div>';
		
} elseif( !empty($_POST['frmTranslate']) ) {
	$sOutput = '
		<div class="view frmTranslate">
			<h1>Google translate</h1>
			' . $sForm . '
			<h2>' . _( 'Translation' ) . ' from ' . _( $_POST['translateFrom'] ) . ' to ' . _( $_POST['translateTo'] ) . '</h2>
			<textarea readonly="yes">' . $sTranslateText . '</textarea>
			</div>
		</div>';
		
}

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
				<li class="ui-state-default ui-corner-top">
					<a href="' . $oRouter->getPath( 'superTranslationGetText' ) . '">' . _( 'Get-texts' ) . '</a>
				</li>
				<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active">
					<a href="' . $oRouter->getPath( 'superTranslationGoogle' ) . '">' . _( 'Google translate' ) . '</a>
				</li>
			</ul>
			<div id="translationContent" class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-helper-clearfix">
				' . $sOutput . '
			</div>
		</div>
	</div>';