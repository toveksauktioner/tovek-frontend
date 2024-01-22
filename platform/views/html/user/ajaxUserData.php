<?php

if( !empty($_SESSION['userId']) ) {
    echo json_encode( $oUser->readData( array('userId', 'username', 'infoName') ) );    
}