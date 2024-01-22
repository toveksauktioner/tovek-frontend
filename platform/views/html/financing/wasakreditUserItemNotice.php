<?php

$sFinancingUrl = $oRouter->getPath( 'guestWasaKreditFinancing' );

if( !empty($_GET['ajax']) && !empty($_GET['getApplicationButton']) ) {
	echo '
		<a class="popupLink button loggedin" href="' . $sFinancingUrl . '?ajax=1&itemId=' . $_GET['itemId'] . '&value=' . $_GET['value'] . '" data-size="full">' . _( 'Ansök' ) . '</a>';
	exit;
}

// This view is dependent on bidForm being loaded previous

$sOutput = '';

if( !empty($GLOBALS['viewParams']['financing']['wasakreditUserItemNotice.php']['item']) ) {
  $aItem = $GLOBALS['viewParams']['financing']['wasakreditUserItemNotice.php']['item'];
} else {
  return;
}

if( !empty($GLOBALS['viewParams']['financing']['wasakreditUserItemNotice.php']['suggestedFinancingValue']) ) {
  $iSuggestedFinancingValue = $GLOBALS['viewParams']['financing']['wasakreditUserItemNotice.php']['suggestedFinancingValue'];
} else {
  return;
}

$iFinancingThreshold = null;
$oConfig = clFactory::create( 'clConfig' );
$aConfigData = $oConfig->oDao->readData( array(
	'fields' => array( 'configValue' ),
	'criterias' => "configKey = 'itemFinancingThreshold'"
) );

if( !empty($aConfigData) ) {
	$iFinancingThreshold = (int) current(current( $aConfigData ));

	if( $aItem['itemMinBid'] >= $iFinancingThreshold ) {
		// Item min bid must be over threshold set in config

		$oFinancing = clRegistry::get( 'clFinancingWasaKredit', PATH_MODULE . '/financing/models' );
		$aFinancingDataDict = $oFinancing->oDao->aDataDict;

		if( !empty($_SESSION['userId']) ) {
			$aItemFinanced = current( $oFinancing->readFinancingToItem( array(
				'itemId' => $aItem['itemId'],
				'userId' => $_SESSION['userId']
			) ) );
		}

		if( !empty($aItemFinanced) ) {
			// There is a financing for this user and item
			$aFinancing = current( $oFinancing->read(null, $aItemFinanced['financingId']) );

			$sOutput = '
				<h3>' . _( 'Finansiering' ) . '</h3>
				<div class="container">
					<div class="info">
						<img src="/images/logos/lf-wasa-kredit-logo_left_rgb.png" />
					</div>
					<div class="financingText">' . _( 'Det finns en ansökan om finansiering av detta objekt' ) . '</div>
					<div class="financingInfo">
						<h4>' . _( 'Värde' ) . '</h4>
						<div class="value">' . $aItemFinanced['requestedValue'] . '</div>
						<h4>' . _( 'Status' ) . '</h4>
						<div class="status ' . $aFinancing['financingStatus'] . '">' . $aFinancingDataDict['entFinancing']['financingStatus']['values'][ $aFinancing['financingStatus'] ] . '</div>
					</div>
				</div>';

		} else {
			// Display financing application form

      // User must be logged in - otherwise display login button
      if( !empty($_SESSION['userId']) ) {
        $sApplicationButton = '
	          <a class="popupLink button loggedin" href="' . $sFinancingUrl . '?ajax=1&itemId=' . $aItem['itemId'] . '&value=' . $iSuggestedFinancingValue . '" data-size="full" data-item-id="' . $aItem['itemId'] . '" data-item-value="' . $iSuggestedFinancingValue . '">' . _( 'Ansök' ) . '</a>';
        $sApplicationScript = '$(".wasakreditUserItemNotice .applicationButton a.button").attr( "href", "' . $sFinancingUrl . '?ajax=1&itemId=' . $aItem['itemId'] . '&value=" + amount );';

      } else {
        $sApplicationButton = '
	          <a class="popupLink button loggedout" href="/logga-in" data-item-id="' . $aItem['itemId'] . '" data-item-value="' . $iSuggestedFinancingValue . '">' . _( 'Logga in' ) . '</a>';
        $sApplicationScript = '';
      }
      
			$sOutput = '
				<h3>' . _( 'Finansiering' ) . '</h3>
				<div class="container">
					<div class="info">
						<img src="/images/logos/lf-wasa-kredit-logo_left_rgb.png" />
					</div>
					<div class="wasakreditMonthlyCost" data-amount="' . $iSuggestedFinancingValue . '"></div>
          <div class="applicationButton">' . $sApplicationButton . '</div>
				</div>
				<script>
					function financingMonthlyCost( amount ) {
						if( amount == null) amount = $(".wasakreditMonthlyCost").data( "amount" );
						if( typeof amount != "undefined" ) {
							$(".wasakreditMonthlyCost").load( "?ajax=1&view=financing/wasakreditMonthlyCostWidget.php&amount=" + amount );
              ' . $sApplicationScript . '
						}
					}
					$("input#bidValue").on( "keyup change", function() {
						financingMonthlyCost( $(this).val() );
					} );
					financingMonthlyCost( null );
				</script>';
		}

	}
}

echo '
    <div class="view financing wasakreditUserItemNotice">
			' . $sOutput . '
    </div>';
