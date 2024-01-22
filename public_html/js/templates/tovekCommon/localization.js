$( document ).on( "click", ".languageSelector", function() {
  var lang = $( this ).data( "lang" );

  $(".lang").hide();
  $(".lang."+lang ).show();

  $(".languageSelector").removeClass( "selected" );
  $( this ).addClass( "selected" );
} );
