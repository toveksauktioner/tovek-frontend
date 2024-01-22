// Depends on jQuery


// Variable to check from other scripts to see that this file has been run
var bConnectionCheck = true;

var iShowNoticeEvery = 30000;
var iCheckConnectionEvery = iShowNoticeEvery / 2;
var iCheckConnectionDisconnectedEvery = iShowNoticeEvery / 10;
var sServerTime = '';
var connectionLive = true;

function showConnectionNotice() {
  //console.log( "showConnectionNotice" );
  $("#bottomNotification #connectionNotice").show();
  $("body").addClass( "bottomNoticeActive" );
  connectionLive = false;

  clearInterval( oCheckConnectionInterval );
  oCheckConnectionInterval = setInterval( checkConnection, iCheckConnectionDisconnectedEvery );
}

function hideConnectionNotice() {
  //console.log( "hideConnectionNotice" );
  $("#bottomNotification #connectionNotice").hide();
  $("body").removeClass( "bottomNoticeActive" );

  if( !connectionLive ) {
    if( typeof(oWebsocket) !== "undefined" ) {
      if( (typeof(oWebsocket.readyState) !== "undefined") && (oWebsocket.readyState == 1) ) {
        // $("#development").prepend( "<pre>updateItemBid</pre>" );
        updateItemBid();      // Update items (clock.js)
        connectionLive = true;
      }
    }
  }

  if( connectionLive ) {
    clearInterval( oCheckConnectionInterval );
    oCheckConnectionInterval = setInterval( checkConnection, iCheckConnectionEvery );
  }
}

function checkConnection() {
  //console.log( "checkConnection" );

    if( typeof(oWebsocket) !== "undefined" ) {
      if( (typeof(oWebsocket.readyState) == "undefined") || (oWebsocket.readyState == 3) ) {
        // https://www.tutorialspoint.com/html5/html5_websocket.htm
        // A value of 0 indicates that the connection has not yet been established.
        // A value of 1 indicates that the connection is established and communication is possible.
        // A value of 2 indicates that the connection is going through the closing handshake.
        // A value of 3 indicates that the connection has been closed or could not be opened.
        init();               // WebSocket function (wePushService.js)
      }
    }

    // Get timestamp for every second in interval
    var currentTimestamp = Date.now();
    if( currentTimestamp % iCheckConnectionDisconnectedEvery != 0 ) currentTimestamp -= (currentTimestamp % iCheckConnectionDisconnectedEvery);

    $.ajax( {
      url: "/connectionCheck.php?time=" + currentTimestamp,
      type: "GET",
      error: function(xhr) {
        // $("#development").prepend( "<pre>error</pre>" );
        showConnectionNotice();
      },
      success: function(thisServerTime) {
        // $("#development").prepend( "<pre>" + thisServerTime + " ?= " + sServerTime + "</pre>" );
        if( thisServerTime != sServerTime ) {
          sServerTime = thisServerTime;
          hideConnectionNotice();
        } else {
          showConnectionNotice();
        }
      }
    } );
}

function hideJavascriptNotice() {
  //console.log( "hideJavascriptNotice" );
  $("#bottomNotification #javascriptNotice").hide();
  $("body").removeClass( "bottomNoticeActive" );
}


var oCheckConnectionInterval = setInterval( checkConnection, iCheckConnectionDisconnectedEvery );

$( function() {
  window.onfocus = function() {
    checkConnection();
  };
} );
