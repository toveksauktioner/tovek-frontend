<?php

$aFormDataDict = array(
	'formSearch' => array(
		'searchQuery' => array(
			'title' => _( 'Vad söker du?' ),
			'fieldAttributes' => array(
				'class' => 'search'
			)
		),
		'searchArchive' => array(
			'title' => _( 'Avslutade auktioner' ),
			'type' => 'boolean',
			'values' => array(
				1 => '1'
			),
			'fieldAttributes' => array(
				'class' => 'afterButtons checkbox'
			)
		),
		'frmSearch' => array(
			'type' => 'hidden',
			'value' => true
		)
	)
);

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'method' => 'get',
	'action' => $oRouter->getPath( 'guestAuctionSearch' ),
	'attributes' => array( 'class' => 'searchForm newForm' ),
	'placeholders' => false,
	'data' => $_GET,
	'buttons' => array(
		'submit' => _( 'Search' )
	)
) );

echo '
	<div class="view auction searchForm">
		' . $oOutputHtmlForm->render() . '
	</div>';
