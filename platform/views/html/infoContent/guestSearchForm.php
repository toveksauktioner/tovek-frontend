<?php

$aDataDict = array(
	'infoContentSearch' => array(
		'searchQuery' => array(
			'title' => _( 'Keywords' ),
			'type' => 'string'
		),	
		'frmSearch' => array(
			'type' => 'hidden',
			'value' => 'true'
		)
	)
);

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'action' => $oRouter->getPath( 'guestSearchResults' ),
	'attributes' => array(
		'class' => 'marginal'
	),
	'includeQueryStr' => false,
	'method' => 'get',
	'buttons' => array(
		'submit' => _( 'Search' )
	)
) );

echo '
<div class="view guestSearchForm">
	<h2>' . _( 'Search' ) . '</h2>
	' . $oOutputHtmlForm->render() . '
</div>';