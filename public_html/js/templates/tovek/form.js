$( document ).on( "submit", "form.ajaxForm", function(ev) {
  ev.preventDefault();

  var thisForm = $( this );

  var url = $( this ).attr( "action" );
  if( typeof url === "undefined" ) return;

  var method = $( this ).attr( "method" );
  if( typeof method === "undefined" ) {
    method = 'get';
  }
  method = method.toLowerCase();

  var onSuccess = $( this ).data( 'on-success' );
  var onSuccessTarget = $( this ).data( 'on-success-target' );

  if( method == "post" ) {
    $.post( url, $(this).serialize(), function(data) {
      data = JSON.parse( data );

      if( data.result == "success" ) {
        // console.log(onSuccess);
        // console.log(onSuccessTarget);

        if( onSuccess == "close") {
          $( onSuccessTarget ).hide();

        } else  if( onSuccess == "click") {
          $( onSuccessTarget ).trigger( 'click' );

        } else  if( onSuccess == "reload") {
          location.reload();

        }

      } else {
        thisForm.find(".field.error").removeClass( 'error' );
        thisForm.find(".errMsg").remove();

        // Error display
        jQuery.each( data.error, function(key, value) {
          var selector = "#" + key;
          thisForm.find( selector ).parents( ".field" ).addClass( "error" ).append( '<div class="errMsg">' + value + '</div>' );
        } );
      }

    } );

  } else {

  }
} );
