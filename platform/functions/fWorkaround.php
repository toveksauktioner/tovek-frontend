<?php

function workaroundCheck() {
   checkMbstring();
   checkMagicQuotesGpc();
}

/**
 * mbstring
 */
function checkMbstring() {
   if( !extension_loaded('mbstring') ) {
	  // mbstring not compiled in php, workaround this
	  require_once( dirname( __FILE__ ) . '/../core/Multibyte.php' );
   } else {
	  mb_internal_encoding( 'UTF-8' );
   }
}

/**
 * magic_quotes_gpc
 */
function checkMagicQuotesGpc() {
   if( (phpversion() < 7.4) && get_magic_quotes_gpc() ) {
	  function stripslashesRecursive( &$mixed ) {
		  foreach( $mixed as $key => $value ){
			  if( is_array($value) ) {
				  stripslashesRecursive( $mixed[$key] );
			  } else {
				  $mixed[$key] = stripslashes( $value );
			  }
		  }
	  }
	  if( !empty($_POST) )		stripslashesRecursive( $_POST );
	  if( !empty($_GET) ) 		stripslashesRecursive( $_GET );
	  if( !empty($_COOKIE) ) 	stripslashesRecursive( $_COOKIE );
   }
}

if( phpversion() < 5.3 ) {
   function lcfirst( $sString ) {
	  $sString[0] = strtolower( $sString[0] );
	  return $sString;
   }
}
