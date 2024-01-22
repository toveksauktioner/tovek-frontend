/**
 *
 * JS file for functions
 *
 */

function isoDate( sDate ) {
    return sDate.substring(0, 10) + 'T' + sDate.substring(11, sDate.length) + 'Z'
}

function dateToIso( dDate ) {
    var sDate = dDate.toISOString();
    return sDate.substring(0, 10) + ' ' + sDate.substring(11, sDate.length);
}

function dateAdd( dDate, sInterval, iUnits ) {
    var returnDate = new Date( dDate );

    switch( sInterval.toLowerCase() ) {
        case 'year'   :  returnDate.setFullYear( returnDate.getFullYear() + iUnits ); break;
        case 'quarter':  returnDate.setMonth( returnDate.getMonth() + 3*iUnits ); break;
        case 'month'  :  returnDate.setMonth( returnDate.getMonth() + iUnits ); break;
        case 'week'   :  returnDate.setDate( returnDate.getDate() + 7*iUnits ); break;
        case 'day'    :  returnDate.setDate( returnDate.getDate() + iUnits ); break;
        case 'hour'   :  returnDate.setTime( returnDate.getTime() + iUnits*3600000 ); break;
        case 'minute' :  returnDate.setTime( returnDate.getTime() + iUnits*60000 ); break;
        case 'second' :  returnDate.setTime( returnDate.getTime() + iUnits*1000 ); break;
        default       :  returnDate = undefined;  break;
    }

    return returnDate;
}

function initAjaxLinks() {
	// Find and replace all ajax link with
	// ajax given secondary link.
	$( function() {
		$("body a.ajax[data-ajax-href]").each( function() {
			$(this).attr( "href", $(this).data("ajax-href") );
			$(this).removeAttr( "data-ajax-href" );
		} );
	} );
}

function setCookie( sName, sValue, iExpiryDays ) {
    var dDate = new Date();
    dDate.setTime( dDate.getTime() + (iExpiryDays*24*60*60*1000) );
    var sExpires = "expires=" + dDate.toGMTString();

	document.cookie = sName + "=" + sValue + "; " + sExpires + "; path=/";
}

function getCookie( sName ) {
    sName = sName + "=";
    var aCookie = document.cookie.split( ';' );
    for( var i = 0; i < aCookie.length; i++ ) {
        var cookie = aCookie[i];
        while( cookie.charAt(0) == ' ' ) {
			cookie = cookie.substring(1);
		}
        if( cookie.indexOf(sName) != -1 ) {
			return cookie.substring( sName.length, cookie.length );
		}
    }
    return "";
}

function getURLParameter( sParam ) {
	var sPageURL = window.location.search.substring(1);
	var sURLVariables = sPageURL.split( '&' );

	for( var i = 0; i < sURLVariables.length; i++ ) {
		var sParameterName = sURLVariables[i].split( '=' );
		if( sParameterName[0] == sParam ) {
			return sParameterName[1];
		}
	}
	return false;
}

function getAjaxURLParameter( sParam, sPageURL ) {
	var sURLVariables = sPageURL.split( '&' );

	for( var i = 0; i < sURLVariables.length; i++ ) {
		var sParameterName = sURLVariables[i].split( '=' );
		if( sParameterName[0] == sParam ) {
			return sParameterName[1];
		}
	}
	return false;
}

function createSpinner() {
	var options = {
		lines: 11, 				// The number of lines to draw
		length: 24, 			// The length of each line
		width: 12, 				// The line thickness
		radius: 25,		 		// The radius of the inner circle
		corners: 0, 			// Corner roundness (0..1)
		rotate: 0, 				// The rotation offset
		direction: 1, 			// 1: clockwise, -1: counterclockwise
		color: '#fff', 			// #rgb or #rrggbb or array of colors
		speed: 1.4, 			// Rounds per second
		trail: 60, 				// Afterglow percentage
		shadow: true, 			// Whether to render a shadow
		hwaccel: false, 		// Whether to use hardware acceleration
		className: 'spinner', 	// The CSS class to assign to the spinner
		zIndex: 2e9, 			// The z-index (defaults to 2000000000)
		top: '35%', 			// Top position relative to parent in px
		left: '50%' 			// Left position relative to parent in px
	};
	return new Spinner(options);
}

function sendWebSocketMessage( sType, sMsg, mData ) {
	if( typeof oWebsocket !== "undefined" ) {
		oWebsocket.send( JSON.stringify( {
			type: sType,
			message: sMsg,
			data: mData
		} ) );
	}
}


// Visibility handling - find out if an object is visible
function isVisibleInViewport( obj ) {
	var offset = obj.offset();
	var objectTop = offset.top;
	var objectBottom = objectTop + obj.outerHeight();
	var viewportTop = $( window ).scrollTop();
	var viewportBottom = viewportTop + $( window ).outerHeight();

	if( ((objectTop >= viewportTop) && (objectTop <= viewportBottom)) || ((objectTop <= viewportTop) && (objectBottom >= viewportTop)) ) {
		return true;
	} else {
		return false;
	}
}
