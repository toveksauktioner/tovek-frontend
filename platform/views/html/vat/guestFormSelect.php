<?php

$aFormDataDict = array(
	'entVatSelect' => array(
		'changeVatInclusion' => array(
			'type' => 'array',
			'values' => array(
				'true' => _( 'Show prices incl. VAT' )
				'false' => _( 'Show prices excl. VAT' )
			),
			'title' => _( 'Select vat' ),
			'attributes' => array(
				'onchange' => 'this.form.submit();'
			)
		)
	)
);

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'labelSuffix' => ':',
	'data' => array(
		'changeVatInclusion' => $_SESSION['vatInclusion']
	),
	'buttons' => array()
) );

echo $oOutputHtmlForm->render();

