<?php

if( !empty($_SESSION['userId']) ) {
	clFactory::loadClassFile( 'clUser' );
	$oUser = new clUser( $_SESSION['userId'] );
	$oUser->logout();
}

if( !empty($_COOKIE['userId']) ) {
	$iCookieExpire = time() - 3600;
	setcookie( 'username', '', $iCookieExpire, '/' );
	setcookie( 'userId', '', $iCookieExpire, '/' );
}

echo '
	<script>
		window.location.href = "/";
	</script>'; 
