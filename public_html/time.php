<?php
if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && ($_SERVER['HTTP_X_FORWARDED_FOR'] == '70.34.217.241') ) {
	// Continue
	#phpinfo();
} else {
	header( "HTTP/1.1 404 Not Found" );
	exit;
}

//echo '
//	<script>
//		updateItemBid( null );
//
//		function updateItemBid( iItemId ) {
//			console.log( "Hello" );
//		}
//	</script>';

/**
 * Check server & DB time:
 */
require_once( dirname(dirname(__FILE__)) . '/platform/core/bootstrap.php' );
$oDbFront = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );
$aData = $oDbFront->query( 'SELECT NOW(); ' );
echo '<pre>';
var_dump( $aData );
var_dump( date( 'Y-m-d H:i:s', time() ) );
die();

/*
if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == "213.88.134.199" ) { ?>
		<script>
			var iTestId = '429761';
			var sMsg = '<a href="/auktion/rop/vertikal-borrmaskin-/429761">Någon har lagt ett högre bud på Rop 2 Vertikal borrmaskin. Gå till ropet!</a>';

			$("#notificationRow").append( '<div class="notification">' + sMsg + ' <span class="close">X</span></div>' );

			if( $('#notificationRow .notification').length > 3 ) {
				$('#notificationRow .notification:first-child').slideUp( 400, function() {
					$(this).remove();
				} );
			}

			if( !$("#notificationRow").is(':visible') ) {
				$("#notificationRow").slideDown( 400, function() {
					var eThis = $(this);
					setTimeout( function() {
						eThis.slideUp( 400, function() {
							eThis.remove();
						} );
					}, 5000 );
				} );
			}
		</script>
	}
*/
