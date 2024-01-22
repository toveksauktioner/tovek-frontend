<?php

$aErr = array();

$oAllaBolag = clRegistry::get( 'clAllaBolag', PATH_MODULE . '/allaBolag/models' );

$sResult = '';

if( !empty($_POST['frmSearch']) ) {
    $aData = $oAllaBolag->find( $_POST['searchString'],  $_POST['searchBy'] );
    
    if( !empty($aData['records']) ) {
        $aTableDict = array(
            'entResult' => array(
                'field' => array(
                    'type' => 'string',
                    'title' => _( 'Field' )
                ),
                'value' => array(
                    'type' => 'string',
                    'title' => _( 'Value' )
                )
            )
        );
        
        $oOutputHtmlTable = clRegistry::get( 'clOutputHtmlTable' );
        $oOutputHtmlTable->init( $aTableDict );
        $oOutputHtmlTable->setTableDataDict( current($aTableDict) );
        
        foreach( $aData['records'] as $sField => $mValue ) {
            $aRow = array(
                'field' => _( ucfirst($sField) ),
                'value' => $mValue
            );
            $oOutputHtmlTable->addBodyEntry( $aRow );
        }
        
        $sResult = $oOutputHtmlTable->render();
    }
}

$aFormDict = array(
    'entSearch' => array(
        'searchString' => array(
            'type' => 'string',
            'title' => _( 'Search string' )
        ),
        'searchBy' => array(
            'type' => 'array',
            'title' => _( 'Search by' ),
            'values' => array(
                'orgnr' => _( 'Org no' )
            )
        ),
        'type' => array(
            'type' => 'array',
            'title' => _( 'Type' ),
            'values' => array(
                'find'
            )
        )
    )
);

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDict, array(
	'attributes'	=> array(
		'class'	=> 'marginal'
	),
	'data' => $_POST,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict( current($aFormDict) + array(
    'frmSearch' => array(
        'type' => 'hidden',
        'value' => true
    )
) );

echo '
	<div class="view allaBolag search">
		<h1>' . _( 'Alla Bolag search' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
        <section class="results">
            ' . $sResult . '
        </section>
	</div>';