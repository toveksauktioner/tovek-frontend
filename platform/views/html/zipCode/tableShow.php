<?php

$oZipCode = clRegistry::get( 'clZipCode', PATH_MODULE . '/zipCode/models' );

/**
 * Sync/import data
 */
if( !empty($_GET['syncImport']) ) {
    if( $oZipCode->syncImport() === true ) {
        // Success
        $oNotification->setSessionNotifications( array(
            'dataSaved' => _( 'The data has been synced' )
        ) );
    } else {
        // Not success
        $oNotification->setSessionNotifications( array(
            'dataError' => _( 'There was problems with the sync process' )
        ) );
    }
    
    $oRouter->redirect( $oRouter->sPath );
}

// Sorting
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oZipCode->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('zipId' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'zipId' => array(),
    'zipCode' => array(),
    'zipCity' => array(),
    'zipCountyCode' => array(),
    'zipCounty' => array(),
    'zipMunicipalityCode' => array(),
    'zipMunicipality' => array(),
    'zipARcode' => array(),
    'zipUpdated' => array(),
    'zipCreated' => array()
) );

// Data
$aZipCodes = $oZipCode->read();

$sOutput = '';

if( !empty($aZipCodes) ) {
    clFactory::loadClassFile( 'clOutputHtmlTable' );
    $oOutputHtmlTable = new clOutputHtmlTable( $oZipCode->oDao->getDataDict() );
    $oOutputHtmlTable->setTableDataDict( $oSorting->render() );
    
    foreach( $aZipCodes as $aZipCode ) {
        $oOutputHtmlTable->addBodyEntry( array(
            'zipId' => $aZipCode['zipId'],
            'zipCode' => $aZipCode['zipCode'],
            'zipCity' => $aZipCode['zipCity'],
            'zipCountyCode' => $aZipCode['zipCountyCode'],
            'zipCounty' => $aZipCode['zipCounty'],
            'zipMunicipalityCode' => $aZipCode['zipMunicipalityCode'],
            'zipMunicipality' => $aZipCode['zipMunicipality'],
            'zipARcode' => $aZipCode['zipARcode'],
            'zipUpdated' => $aZipCode['zipUpdated'],
            'zipCreated' => $aZipCode['zipCreated']
        ) );
    }
    
    $sOutput = $oOutputHtmlTable->render();

} else {
    $sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view zipCode tableShow">
		<h1>' . _( 'Zip code' ) . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $oRouter->sPath . '?syncImport=true" class="icon iconText iconDbImport linkConfirm" title="' . _( 'Are your sure?' ) . '">' . _( 'Sync from file' ) . '</a><br />
                <span style="opacity: .45; font-size: .7em;">(' . REC2LK_FILE . ')</span>
			</div>
		</section>
		<section>
			' . $sOutput . '
		</section>
	</div>';