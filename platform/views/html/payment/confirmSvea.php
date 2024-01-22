<?php

// This should only be displayed if directed by Svea with the iOI parameter
// Else just return nothing
if( empty($_GET['iOI']) ) return;

$sOutput = '';
$bKeepOnChecking = false;

$oPaymentSvea = clRegistry::get( 'clPaymentSvea', PATH_MODULE . '/payment/models' );
$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
$aInvoiceOrderData = current( $oInvoiceEngine->read_in_InvoiceOrder(null, $_GET['iOI']) );

// If invoice order data is incorrect - do nothing
if( empty($aInvoiceOrderData) ) return;

switch( $aInvoiceOrderData['invoiceOrderStatus'] ) {
	case 'completed':
		$aReturnData = $oPaymentSvea->sveaGetOrder( (int) $aInvoiceOrderData['invoiceOrderCustomId'] );

		if( !empty($aReturnData['response']) ) {
			$sOutput .= $aReturnData['response']['Gui']['Snippet'];
		} else {
			$sOutput .= '
				<p>Orderinformation går inte att nå just nu. Prova att ladda om sidan om en liten stund.</p>';
		}
		break;

	case 'cancelled':
		$sOutput .= '
			<p>Betalningen avbröts. Prova att påbörja en ny betalning.</p>';
		break;

	default:
		$sOutput .= '
			<p>Inväntar respons från Svea Bank.</p>';
		$bKeepOnChecking = true;
}

// If ajax call (made by the script itself) send html as response 
if( !empty($_GET['ajax']) ) {
	echo json_encode( [
		'output' => $sOutput,
		'keepOnChecking' => $bKeepOnChecking
	] );
	exit;
}

?>
	<script>
		function keepChecking() {
    				$.get( '?ajax=1&view=payment/confirmSvea.php', {
    					iOI: <?php echo $_GET['iOI']; ?>
    				}, function(data) {
    					data = JSON.parse( data );
    					if( data.keepOnChecking ) {
    						$("#popupLinkBox .content p:first-child").append( '.' );
    						setTimeout( keepChecking, 1000 );
    					} else {
    						// $("#popupLinkBox .content").html( data.output );
    						location.reload();
    					}
    				} );
		}

		$( function() {
			const keepOnChecking = <?php echo ( $bKeepOnChecking ? 'true' : 'false' ); ?>;

			if( !$('#popupLinkBox').length ) {
				$('body').append( '<div id="popupLinkBox"><div class="wrapper"><nav><a href="#" class="popupClose"><i class="fas fa-times-circle"></i></a></nav><div class="content"></div></div></div>' );
			}

			$("#popupLinkBox .content").html( '<?php echo str_replace( ["\r", "\n", "/"], ['', '', "\/"], addslashes($sOutput) ); ?>' );
    		$('#popupLinkBox').addClass('active').show();

    		if( keepOnChecking ) {
    			setTimeout( keepChecking, 1000 );
    		}
		} );
	</script>