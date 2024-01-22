<?php

$sRedirectTo = '';

if( !empty($_SESSION['HTTP_AUCTION_ITEM']) ) {
	$sRedirectTo = $_SESSION['HTTP_AUCTION_ITEM'];

} elseif( !empty($_SESSION['HTTP_PREVIOUS']) ) {
	$sRedirectTo = $_SESSION['HTTP_PREVIOUS'];

} else {
	$sRedirectTo = $oRouter->getPath( 'userHomepage' );
}

echo '
	<div class="view user newUserStopover">
		<h1>Välkommen som kund hos tovek.se</h1>
		Omdirigerar...
	</div>
	<script>
		$( function() {
			window.location.href = "' . $sRedirectTo . '";
		} );
	</script>';
