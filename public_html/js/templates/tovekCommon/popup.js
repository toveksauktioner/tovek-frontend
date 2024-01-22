var popupLinkBoxHistory;

$( document ).on( 'click', 'a.popupLink', function(ev) {
  ev.preventDefault();

  var triggerObj = $( this );
  var url = triggerObj.attr( 'href' );
  var html = triggerObj.html();
  var title = html.replace( /(<([^>]+)>)/gi, "" );
  var showElement = triggerObj.data( 'show-element' );
  var prependInfo = triggerObj.data( 'prepend-info' );
  var appendInfo = triggerObj.data( 'append-info' );
  var position = triggerObj.data( 'position' );
  var size = triggerObj.data( 'size' );
  var history = triggerObj.data( 'history' );
  var backText = triggerObj.data( 'back-text' );
  var backLink = '';

  // History handling
  if( (typeof history != 'undefined') ) {
    // History navigation in use

  } else if( $(this).parents('#popupLinkBox').length > 0 ) {
    // There are history items - add one
    popupLinkBoxHistory.push( {
      url: url,
      size: size,
      title: title
    } );
    history = popupLinkBoxHistory.length - 1;

  } else {
    // First item loaded - new history object
    popupLinkBoxHistory = [ {
      url: url,
      size: size,
      title: title
    } ];
    history = 0;
  }
// console.log(popupLinkBoxHistory);

  if( showElement != null ) url += ' ' + showElement;

  if( (typeof backText != 'undefined') && (backText != '') ) {
    backLink = '<a href="#" class="popupClose backLink"><i class="fas fa-backspace"></i>' + backText + '</a>';
  }

  if( !$('#popupLinkBox').length ) {
    $('body').append( '<div id="popupLinkBox"><div class="wrapper"><nav>' + backLink + '<a href="#" class="popupClose"><i class="fas fa-times-circle"></i></a></nav><div class="content"></div></div></div>' );
  }

  $('#popupLinkBox .content').load( url, function(data) {
    // Reset values
    $('#popupLinkBox .wrapper').css( 'top', '50%' ).css( 'bottom', 'initial' ).css( 'left', '50%' ).css( 'right', 'initial' ).removeClass( "relPosVertical relPosHorizontal" );

    if( typeof position != 'undefined' ) {
      var aPos = position.split( ':' );
      var windowWidth = $( document ).width();
      var windowHeight = $( document ).height();
      var triggerPosition = triggerObj.offset();
      var triggerHeight = triggerObj.height();
      var triggerWidth = triggerObj.width();

      for( i=0; i<aPos.length; i++ ) {
        if( aPos[i] == 'bottom' ) {
          var positionTop = triggerPosition.top + triggerHeight;
          $('#popupLinkBox .wrapper').css( 'top', positionTop ).addClass( "relPosVertical" );
        }
        if( aPos[i] == 'top' ) {
          var positionTop = windowHeight - triggerPosition.top;
          $('#popupLinkBox .wrapper').css( 'bottom', positionTop ).addClass( "relPosVertical" );
        }
        if( aPos[i] == 'left' ) {
          var positionLeft = triggerPosition.left;
          $('#popupLinkBox .wrapper').css( 'left', positionLeft ).addClass( "relPosHorizontal" );
        }
        if( aPos[i] == 'right' ) {
          var positionRight = windowWidth - triggerPosition.left - triggerWidth;
          $('#popupLinkBox .wrapper').css( 'right', positionRight ).addClass( "relPosHorizontal" );
        }
      }
    }

    // Remove previous navigation and add new
    // Prepending so order will be correct if next is added before prev
    $("#popupLinkBox .wrapper nav .navigation").remove();
    if( history < (popupLinkBoxHistory.length - 1) ) {
      nextId = history + 1;
      nextHistory = popupLinkBoxHistory[ nextId ];
      $("#popupLinkBox .wrapper nav").prepend( '<a href="' + nextHistory.url + '" class="popupLink navigation next" data-history="' + nextId +'" data-size="' + nextHistory.size + '"><i class="fas fa-arrow-circle-right"></i><span>&nbsp;' + nextHistory.title + '</span></a>' );
    }
    if( history > 0 ) {
      prevId = history - 1;
      prevHistory = popupLinkBoxHistory[ prevId ];
      if( history == (popupLinkBoxHistory.length - 1) ) prevTitle = '<span>&nbsp;' + prevHistory.title + '</span>';
      $("#popupLinkBox .wrapper nav").prepend( '<a href="' + prevHistory.url + '" class="popupLink navigation prev" data-history="' + prevId +'" data-size="' + prevHistory.size + '"><i class="fas fa-arrow-circle-left"></i><span>&nbsp' + prevHistory.title + '</span></a>' );

    } else if( popupLinkBoxHistory.length > 1 ) {
      $("#popupLinkBox .wrapper nav").prepend( '<span class="navigation prev inactive" ><i class="fas fa-arrow-circle-left"></i><span>&nbsp' + prevHistory.title + '</span></span>' );

    }

    // Reset previous size and set new
    $('#popupLinkBox .wrapper').removeClass( "full" );
    if( typeof size != 'undefined' ) $('#popupLinkBox .wrapper').addClass( size );

    if( typeof prependInfo != 'undefined' ) $( this ).prepend( prependInfo );
    if( typeof appendInfo != 'undefined' ) $( this ).append( appendInfo );

    $('#popupLinkBox').addClass('active').show();
    $("#popupLinkBox form :input:visible:enabled:first").focus();
  } );
} );

$( document ).on( 'click', '#popupLinkBox a.popupClose', function(ev) {
  ev.preventDefault();
  // $('#popupLinkBox').removeClass('active').hide();
  // Note: entire popupBox should be removed - problems with it in invoice cart
  $('#popupLinkBox').remove();
} );

$( document ).on( 'keyup', function(ev) {
  if( ev.keyCode === 27 ) $("#popupLinkBox a.popupClose").click();               // 27 = escape
} );

$( document ).on( 'click', '#popupLinkBox', function() {
  $("#popupLinkBox a.popupClose").click();
} );
$( document ).on( 'click', '#popupLinkBox .wrapper', function(event) {
  event.stopPropagation();
} );
