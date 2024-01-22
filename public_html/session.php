<?php
if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ) {
    // Only ajax calls are accepted
    session_start();
    echo ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 );
}
die();