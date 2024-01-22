<?php
die();
echo date( "Y-m-d H:i:s" );

if( !empty($_COOKIE['userId']) ) {
	echo '<br>' . $_COOKIE['userId'];
}