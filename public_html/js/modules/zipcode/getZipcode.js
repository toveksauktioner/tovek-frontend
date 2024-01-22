// PAP/API Lite service
// https://papilite.se/

// Requires jQuery to be loaded

var papiLiteUrl = "https://api.papapi.se/lite/";
var papiLiteApiKey = "d27e17bc8948db536afa23a62f3f17b5a9b97d6f";

$( document ).on( "keyup", ".zipcodeLookup", function() {
  var zipcode = $(this).val();
  var target = $(this).data( "target" );

  zipcode = zipcode.replace( /[^0-9]+/g, "" );

  if( zipcode.length == 5 ) {
    $.ajax( {
      url: papiLiteUrl,
      data: {
        query: zipcode,
        format: "json",
        apikey: papiLiteApiKey
      },
      type: 'get',
      error: function(XMLHttpRequest, textStatus, errorThrown) {
        $(target).val( "" );
        console.log('status:' + XMLHttpRequest.status + ', status text: ' + XMLHttpRequest.statusText);
      },
      success: function(data) {
        $(target).val( data.results[0].city );
      }
    } );

  } else {
    zipcode = zipcode.substr( 0, 5 );
  }

  $(this).val( zipcode );
} );
