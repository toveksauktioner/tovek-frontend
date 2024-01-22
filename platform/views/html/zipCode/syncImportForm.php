<?php

$aErr = array();

$oZipCode = clRegistry::get( 'clZipCode', PATH_MODULE . '/zipCode/models' );

/**
 * Post
 */
if( !empty($_POST['frmRun']) ) {
    if( $oZipCode->syncImport() === true ) {
        // Success
        $oNotification->set( array(
            'dataSaved' => _( 'The data has been synced' )
        ) );
    } else {
        // Not success
        $oNotification->set( array(
            'dataError' => _( 'There was problems with the sync process' )
        ) );
    }
}

/**
 * Form
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( array(
    'entForm' => array(
        'frmRun' => array(
            'type' => 'hidden',
            'value' => true
        )
    )
), array(
	'errors' => $aErr,
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Sync' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
    'frmRun' => array()
) );

echo '
    <div class="view zipCode syncImportForm">
        <h1>' . _( 'Sync data' ) . '</h1>
        ' . $oOutputHtmlForm->render() . '
    </div>';