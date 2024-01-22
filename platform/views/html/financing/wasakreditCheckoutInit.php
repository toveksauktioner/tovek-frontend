<?php

$oFinancing = clRegistry::get( 'clFinancingWasaKredit', PATH_MODULE . '/financing/models' );
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

// The call should contain at least one item reference
if( empty($_REQUEST['itemId']) ) exit;

// Registred vehicles are not handled by checkout - instead a form is presented
// If the service is not enabled the form is also presented
$aItemIsVehicle = current( current($oAuctionEngine->readAuctionItem(array(
  'fields' => 'itemVehicleDataId',
  'itemId' => $_REQUEST['itemId']
))) );
if( !empty($aItemIsVehicle) || !FINANCING_WASAKREDIT_ENABLE ) return;

$iSuggestedFinancingValue = null;
if( !empty($_REQUEST['value']) ) $iSuggestedFinancingValue = $_REQUEST['value'];

if( !empty($_POST['frmLoadCheckout']) ) {
  $aItems = $oAuctionEngine->read( 'AuctionItem', '*', $_REQUEST['itemId'] );

  if( !empty($aItems) ) {
    foreach( $aItems as &$aItem ) {
      $aItem['itemFinancingAmount'] = $iSuggestedFinancingValue;
    }

    $aCartData = array(
      'items' => $aItems
    );

    if( !empty($_SESSION['userId']) ) {
      $aCartData['user'] = current( $oUser->oDao->read( array(
    		'fields' => '*',
    		'userId' => $_SESSION['userId']
    	) ) );
    }

    echo $oFinancing->createCheckout( $aCartData ) . '
      <script>
        window.wasaCheckout.init();
      </script>';
    exit;
  }
}

echo '
  <div class="view payment wasakreditCheckoutInit">
    <div class="requestForm">
      <form id="wasakreditRequestForm" class="newForm oneLiner noLabel">
        <div class="field text bid">
          <input type="number" name="value" id="wasakreditCheckoutAmount" value="' . $iSuggestedFinancingValue . '" />
        </div>
        <div class="hidden">
          <input type="hidden" name="frmLoadCheckout" value="1" />
          <input type="hidden" name="itemId" value="' . $_REQUEST['itemId'] . '" />
        </div>
        <p class="buttons">
          <button type="submit">' . _( 'Ansök' ) . '</button>
        </p>
      </form>
    </div>
    <div class="cancelButton">
      <a href="#" id="wasakreditCancelBtn" class="cancel">' . _( 'Avbryt' ) . '</a>
    </div>
    <div class="checkoutContainer"></div>
  </div>
  <script>
    $("#wasakreditRequestForm").submit( function(ev) {
      ev.preventDefault();
      $.post( "?ajax=1&view=financing/wasakreditCheckoutInit.php", $(this).serialize(), function(data) {
        $("#wasakreditRequestForm").parent().hide();
        $("#wasakreditCancelBtn").parent().show();
        $(".wasakreditCheckoutInit .checkoutContainer").html( data );
      } );
    } );
    $("#wasakreditCancelBtn").click( function(ev) {
      ev.preventDefault();
      $("#popupLinkBox .popupClose").click();
    } );
    $( function() {
      if( $("input#bidValue").val() > $("input#wasakreditCheckoutAmount").val() ) {
        $("input#wasakreditCheckoutAmount").val( $("input#bidValue").val() );
      }
    } );
  </script>';
