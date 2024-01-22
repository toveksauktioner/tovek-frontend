<?php

// Generic unsubscribe for all email registy
// TO DO: Make it generic and for email registry. Now only newsletter subscribers

// Input data ($_GET):
// e = email address
// c = checksum (md5 of combined data from subscriber)

$sOutput = '';

$oSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );

if( !empty($_GET['e']) ) {
  $aSubscriber = current( $oSubscriber->readByEmail($_GET['e'], '*') );

  if( !empty($aSubscriber) ) {
    $sCheckSum = md5( $aSubscriber['subscriberEmail'] . $aSubscriber['subscriberUnsubscribe'] . $aSubscriber['subscriberCreated'] );

    if( $sCheckSum == $_GET['c'] ) {
      $oSubscriber->oDao->updateDataByPrimary( $aSubscriber['subscriberId'], array(
        'subscriberUnsubscribe' => 'yes'
      ) );
      $sOutput = '<strong>' . $_GET['e'] . '</strong> ' . _( 'har avanmälts från vårt nyhetsbrev.' );
    }
  }
}

if( empty($sOutput) ) {
  $sOutput = _( 'Felaktiga uppgifter.' );
}

echo '
  <div class="view email unsubscribe">
    <p>' . $sOutput . '</p>
  </div>';
