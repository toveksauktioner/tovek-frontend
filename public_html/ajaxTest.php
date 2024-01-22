<?php
exit;
$sOutput = '';

if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && (strncmp('37.152.60.', $_SERVER['HTTP_X_FORWARDED_FOR'], 10) == 0) ) {
	// Restricted code
  /*** Check´s if the request is made by ajax ***/
  if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
    $sOutput = "non ajax call";
  } else {
    echo "ajax call";
    return;
  }

} else {
  $sOutput = "restricted";
}

echo '
<html>
<head>
  <script src="/js/jquery/jquery-3.2.1.min.js"></script>
</head>
<body>
  ' . $sOutput . '
  <script>
    $.ajax( {
      url: "https://tovek.se/?ajax=true&view=auctionAjax/bidDataAjax.php&newBid=true&itemId=519221",
      type: "GET",
      data: "noCss=true",
      async: true,
      dataType: "html"
    } ).fail( function() {
     // Failed

    } ).done( function( data, textStatus, jqXHR ) {
      data = JSON.parse(data);
      console.log( data );
    } );
  </script>
</body>
</html>';


return;
