<?php

// Check if user logged out by session and still have a cookie set. If so terminate cookie
session_start();

if( empty($_SESSION['userId']) && !empty($_COOKIE['userId']) ) {
	$iCookieExpire = time() - 3600;
	setcookie( 'username', '', $iCookieExpire, '/' );
	setcookie( 'userId', '', $iCookieExpire, '/' );
}

if( empty($_SESSION['userId']) ) {
	echo 'offline';
}