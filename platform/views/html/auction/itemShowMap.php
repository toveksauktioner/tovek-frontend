<?php

$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );

$oBackEnd->setSource( 'entAuctionAddress', 'addressId' );

$oBackEnd->oDao->setCriterias( array(
    'partAuctionId' => array(		
        'fields' => 'addressPartId',
        'value' => $_GET['partId']
    )
) );

$aAddress = $oBackEnd->read();

//echo '<pre>';
//var_dump( $aAddress );
//die;

//$sUrl = urlencode( 'https://www.google.com/maps/dir/?api=1&address=H%C3%A4stskov%C3%A4gen+10%2C+813+33+Hofors' );
$sUrl = urlencode( 'Hästskovägen 10, 813 33 Hofors' );

//https://www.google.se/maps/place/Hästskovägen+10,+813+33+Hofors

echo '<pre>';
var_dump( $sUrl );
die;

echo '<h1>' . _( 'Karta' ) . '</h1><p></p><p>Här ska en karta finnas..</p>';
