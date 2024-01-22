<?php
  /*** Include config ***/
  require_once realpath(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/platform/core/bootstrap.php';
?>
/**
 *
 *
 * Main file for javascript related to Tovek2014 template
 *
 *
 */

// Set domain of use for this file
var sDomain = "<?php echo SITE_DEFAULT_PROTOCOL; ?>://<?php echo SITE_DOMAIN; ?>/";

/**
 * Script for topBar scrolling
 */
$(document).on( 'scroll', function() {
	if( $(document).scrollTop() > 53 ) {
		$("#topbar").addClass( "scroll" );
	} else {
		$("#topbar").removeClass( "scroll" );
	}
} );
// ...and related cookie infoBar
$(document).delegate("#cookiesInfo .link a", 'click', function(event) {
	event.preventDefault();

	var dDate = new Date();
    dDate.setTime( dDate.getTime() + (7*24*60*60*1000) );
    var sExpires = "expires=" + dDate.toGMTString();

	document.cookie = 'cookieInformation' + "=" + 'accepted' + "; " + sExpires + "; path=/";

	$("#cookiesInfo").slideUp();
} );

/**
 * Global handling of ajax target links
 */
$(document).delegate( 'a.ajax[data-ajax-targetClass]', 'click', function(event) {
    event.preventDefault();

	// Set target to class
	var sTarget = $(this).data( 'ajax-targetClass' );
	if( typeof($(this).attr( 'data-ajax-targetid' )) !== 'undefined' ) {
		// Change targe to id
		var sTarget = $(this).data( 'ajax-targetid' );
	}

	//console.log( $(this).attr("href") );

	$.ajax( {
		url: $(this).attr("href"),
		type: "GET",
		data: "noCss=true",
		async: true,
		dataType: "html",
		beforeSend: function() {}
	} ).fail( function() {
		// Failed

	} ).done( function( data, textStatus, jqXHR ) {
		// Done
		$(sTarget).html(data).fadeIn( 400, function() {
			$(sTarget + " a.ajax[data-ajax-href]").each( function() {
				$(this).attr( "href", $(this).data("ajax-href") );
				$(this).removeAttr( "data-ajax-href" );
			} );
		} );

        setTimeout( function() {
			reloadTimers();
			updateItemBid( null );
		}, 500 );
	} );
} );

/**
 * Deligated events block ----------------------------------------------
 */
$(document).delegate("#autoscroll", 'click', function(event) {
	if( $('#autoscroll').prop('checked') === true ) {
		// On
		setCookie( "autoscroll", "on", "5" );
	} else {
		// Off
		setCookie( "autoscroll", "off", "5" );
	}
} );
$(document).delegate("a.directLink", 'click', function(event) {
	event.preventDefault();

	if( $(".directLinkContainer").hasClass("show") ) {
		$(".directLinkContainer").removeClass("show");
	} else {
		$(".directLinkContainer").addClass("show");
	}
} );
$(document).delegate(".view.itemShow a.tellFriend", 'click', function(event) {
	event.preventDefault();

	if( $(".tellFriendContainer").hasClass("show") ) {
		$(".tellFriendContainer").removeClass("show");
	} else {
		$(".tellFriendContainer").addClass("show");
	}
} );
/**
 * End of deligated event block ---------------------------------------
 */

/**
 * Page load related jQuery
 */
$(document).ready( function() {

	$("img#spotlight-arrow").animate( {
		left: "-205px"
	}, 1000, function() {
		// Animation complete.
	} );

	$("#spotlight div#spotlight-content").animate( {
		right: "165px"
	}, 1000, function() {
		// Animation complete.
	} );

	/**
	 * Function for global ajax target links
	 */
	initAjaxLinks();

	var sAutoscrollStatus = getCookie( "autoscroll" );
	if( sAutoscrollStatus == "on" ) {
		$("#autoscroll").attr( 'checked', 'checked' );
	}

	var sCookieStatus = getCookie( "cookieInformation" );
	if( typeof(sCookieStatus) !== "undefined" && sCookieStatus == "accepted" ) {
		$("#cookiesInfo").hide();
	}

} );

$( function() {
	$(".images ul li a.colorbox").colorbox( {
		rel: "itemImages",
		current: "Bild {current} av {total}",
		scalePhotos: true,
		width: "95%",
		height: "95%"
	} );
} );

$(document).delegate("a.ajaxPopup", 'click', function(event) {
	event.preventDefault();

	var eLink = $(this);
	var sReturnUrl = window.location.href;

	var sTemplate = "<div id='overlay'>" +
			"<div class='container'>" +
				"<a href='#' class='close'>Stäng</a>" +
				"[content]" +
			"</div>" +
			"<div class='background'></div>" +
		"</div>";

	$.ajax( {
		url: $(eLink).attr("href"),
		type: "GET",
		data: "noCss=true",
		async: true,
		dataType: "html",
		beforeSend: function() {}
	} ).fail( function() {
		// Failed

	} ).done( function( data, textStatus, jqXHR ) {
		// Done
		sTemplate = sTemplate.replace( "[content]", data );
		$("body").prepend( sTemplate );
		$("#overlay").animate( {
			opacity: 1.0
		}, 200, function() {
			// Animation complete.
		} );
	} );
} );

$(document).delegate("#overlay .container a.close", 'click', function(event) {
	event.preventDefault();

	$("#overlay .container").fadeOut( "fast", function() {
		$("#overlay").fadeOut( "fast", function() {
			$("#overlay").remove();
		} );
	} );
} );

$(document).delegate("a#nextAuctionBtn", 'click', function(event) {
	event.preventDefault();

	var currentAuctionPartId = $(this).data("auction-part-id");

	$.get( "?ajax=true&view=classic/auction_list.php&getNextPart=" + currentAuctionPartId, function(data) {
		if( data != '' ) {
			location.href = data;
		}
	} );
} );

$(document).delegate( ".message .loginLink", "click", function(event) {
	event.preventDefault();
    $(this).parent().slideUp();
	$(".view.user.popupLogin").slideDown();
} );
$(document).delegate( ".popupLogin form .buttons button", "click", function(event) {
	event.preventDefault();

	$.post( "?ajax=true&view=user/popupLogin.php", $(this).parents('.buttons').parents('form').serialize(), function(data) {
    var obj = JSON.parse( data );

		if( obj.result == 'success' ) {
			location.reload();
		} else {
			$(".popupLogin form .result").remove();
			$(".popupLogin form").prepend( '<div class="result error"><ol><li>' + obj.message + '</li></ol></div>' );
		}
	} );
} );

/**
 * Login
 */
$(document).delegate( ".view.guestFormLogin form .buttons button", "click", function(event) {
	event.preventDefault();

	var jqxhr = $.post( "?ajax=true&view=user/popupLogin.php", $(this).parents('.buttons').parents('form').serialize(), function() {} )
	.done( function(data) {
        var oResult = $.parseJSON(data);

        if( oResult.result == 'success' ) {
            $("#overlay").fadeOut( "fast", function() {
                $("#overlay").remove();

                //var jqxhr = $.get( "?ajax=true", function() {} )
                //.done( function(data) {
                //	var eHtml = $(data).filter(".layout.main");
                //	$(".layout.main").html( eHtml );
                //} ).fail( function() {} );

                var jqxhr = $.get( "?ajax=true&view=classic/composed_topbar.php", function() {} )
                .done( function(data) {
                    $("#topbar .wrapper").html( data );
                } ).fail( function() {} );

                var jqxhr = $.get( "?ajax=true&view=classic/composed_userPanel.php", function() {} )
                .done( function(data) {
                    $("#mobileMenu ul").replaceWith( data );
                } ).fail( function() {} );

                document.getElementById("navToggle").checked = false;

            } );
        } else {
            if( $('.view.guestFormLogin .notification').length > 0 ) {
                $('.view.guestFormLogin .notification').remove();
                $('.view.guestFormLogin').prepend( '<ul class="notification error" style="display: none;"><li class="notification">' + oResult.message + '</li></ul>' );
                $('.view.guestFormLogin .notification').fadeIn( 'fast' );
            } else {
                $('.view.guestFormLogin').prepend( '<ul class="notification error"><li class="notification error">' + oResult.message + '</li></ul>' );
            }
        }

	} ).fail( function() {} );
} );

$(document).delegate( "#logoutLink", "click", function(event) {
	event.preventDefault();

	var jqxhr = $.get( "?ajax=true&view=user/logout.php", function() {} )
	.done( function() {
        var jqxhr = $.get( "?ajax=true&view=classic/composed_topbar.php", function() {} )
        .done( function(data) {
            $("#topbar .wrapper").html( data );
        } ).fail( function() {} );
    } ).fail( function() {} );
} );
