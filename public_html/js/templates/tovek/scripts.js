/**
 *
 * JS file for default template
 *
 */


/**
 * Notification row sticky
 */
window.onscroll = function() { myFunction() };
var header = document.getElementById( 'notificationRow' );
var sticky = header.offsetTop;
function myFunction() {
	if( window.pageYOffset > sticky ) header.classList.add( 'sticky' );
	else header.classList.remove( 'sticky' );
}
$(document).on( 'click', '#notificationRow .notification .close', function(event) {
	$(this).parent('.notification').slideUp( 'fast', function() {
		$(this).remove();
	} );
} );

/**
 * Ajax login
 */
// $(document).delegate("header .guestFormLogin form .buttons button", "click", function(event) {
// 	event.preventDefault();
//
// 	var jqxhr = $.post( "?ajax=true&view=user/popupLogin.php", $(this).parents('.buttons').parents('form').serialize(), function() {} )
// 	.done( function(data) {
// 		var oResult = $.parseJSON(data);
//
//         if( oResult.result == 'success' ) {
// 			$("header .guestFormLogin").slideUp( 'fast', function() {} );
//
// 			$("header > .container > .panel").slideUp( "fast", function() {
// 				var jqxhr = $.get( "?ajax=true", function() {} )
// 				.done( function(data) {
// 					var eHtml = $(data).filter(".layout.main");
// 					$(".layout.main").html( eHtml );
//
// 					setTimeout( function() {
// 						reloadTimers();
// 						updateItemBid( null );
// 					}, 500 );
// 				} ).fail( function() {} );
//
// 				var jqxhr = $.get( "?ajax=true&view=user/panel.php", function() {} )
// 				.done( function(data) {
// 					if( $(window).width() > 880 ) {
// 						$("header > .container > .panel").html( data ).slideDown( "fast" );
// 					} else {
// 						$("header > .container > nav > .container > .panel").html( data );
// 						$("#btnBurger").prop( 'checked', false );
// 					}
//
// 					var jqxhr = $.get( "?ajax=true&view=user/ajaxUserData.php", function() {} )
// 					.done( function(data) {
// 						var oUser = $.parseJSON(data);
//
// 						var sMessage = 'Välkommen ' + oUser.username + '!';
// 						$("#notificationRow").prepend( '<div class="notification">' + sMessage + '</div>' );
// 						$("#notificationRow").slideDown();
//
// 					} ).fail( function() {} );
// 				} ).fail( function() {} );
// 			} );
// 		} else {
//             if( $('.view.guestFormLogin .notification').length > 0 ) {
//                 $('.view.guestFormLogin .notification').remove();
//                 $('.view.guestFormLogin').prepend( '<ul class="notification error" style="display: none;"><li class="notification">' + oResult.message + '</li></ul>' );
//                 $('.view.guestFormLogin .notification').fadeIn( 'fast' );
//             } else {
//                 $('.view.guestFormLogin').prepend( '<ul class="notification error"><li class="notification error">' + oResult.message + '</li></ul>' );
//             }
//         }
// 	} ).fail( function() {} );
// } );

/**
 * Ajax refresh view
 * - add class "ajaxRefreshView"
 * - add data-ajax-view="module/file.php"
 */
$(document).on( "click", "a.ajaxRefreshView", function(event) {
	event.preventDefault();

	if( typeof $(this).data("ajax-view") == 'undefined' ) {
		window.top.location = $(this).attr("href");

	} else {
		var sGET = '';
		if( $(this).attr("href").indexOf("?") >= 0 ) {
			sGET = '&' + $(this).attr("href").split( '?' )[1];
		}

		$.ajax( {
			url: "?ajax=true&view=" + $(this).data("ajaxView") + sGET,
			type: "GET",
			data: "noCss=true",
			async: true,
			dataType: "html"
		} ).fail( function() {
			// Failed

		} ).done( function( data, textStatus, jqXHR ) {
			var oHtml = $($.parseHTML( data ));
			var aViewClass = $(oHtml).closest(".view").attr("class").split(" ");

			var sViewClass = "." + aViewClass.join(".");

			$(sViewClass).replaceWith( data );
		} );
	}
} );

$(document).on( "click", "a.linkConfirm", function(event) {
	event.preventDefault();
	if ( confirm(this.title) == false) {
		return false;
	} else {
		window.location.href = $(this).attr("href");
	}
} );

//$(document).ready( function() {
//	$.timepicker.regional['sv'] = {
//		timeOnlyTitle	: 'Välj tid',
//		timeText		: 'Tid',
//		hourText		: 'Timma',
//		minuteText		: 'Minut',
//		secondText		: 'Sekund',
//		currentText		: 'Nu',
//		closeText		: 'Klar',
//		timeFormat		: 'h:m',
//		ampm			: false
//	};
//    $.datepicker.regional['sv'] = {
//		closeText: 'Stäng',
//        prevText: '&laquo;Förra',
//		nextText: 'Nästa&raquo;',
//		currentText: 'Idag',
//        monthNames: ['Januari','Februari','Mars','April','Maj','Juni',
//        'Juli','Augusti','September','Oktober','November','December'],
//        monthNamesShort: ['Jan','Feb','Mar','Apr','Maj','Jun',
//        'Jul','Aug','Sep','Okt','Nov','Dec'],
//		dayNamesShort: ['Sön','Mån','Tis','Ons','Tor','Fre','Lör'],
//		dayNames: ['Söndag','Måndag','Tisdag','Onsdag','Torsdag','Fredag','Lördag'],
//		dayNamesMin: ['Sö','Må','Ti','On','To','Fr','Lö'],
//		weekHeader: 'Ve',
//        dateFormat: 'yy-mm-dd',
//		firstDay: 1,
//		isRTL: false,
//		showMonthAfterYear: false,
//		yearSuffix: ''
//	};
//    $.datepicker.setDefaults($.datepicker.regional['sv']);
//	$.timepicker.setDefaults($.timepicker.regional['sv']);
//
//	$(".datepicker").datepicker({
//		dateFormat	: "yy-mm-dd",
//		changeMonth	: true,
//		changeYear	: true
//	});
//
//	$(".datetimepicker").datetimepicker({
//		timeFormat	: 'HH:mm:ss',
//		dateFormat	: 'yy-mm-dd',
//		showOn		: "button",
//		buttonImage	: "/images/icons/calendar.png"
//	});
//} );

$(document).ready(function() {
	var flip = 0;
	$("a.toggleShow").each( function() {
		sTarget = this.href.substring(this.href.indexOf("#"));
		if( !$(sTarget).hasClass("show") ) $(sTarget).hide();
	} ).on( "click", function() {
		sTarget = this.href.substring(this.href.indexOf("#"));
		if( $(sTarget)[0].tagName.toLowerCase() == "tr" ) {
			if( flip++ % 2 == 0 ) {
				$(sTarget).show();
			} else {
				$(sTarget).hide();
				flip = 0;
			}
		} else {
			$(sTarget).slideToggle("fast");
		}

		if( $(this).hasClass('autoHide') ) {
			$(this).remove();
		}

		return false;
	} );
} );

/**
 * Login
 */
// $(document).delegate( ".panel .view.guestFormLogin form .buttons button", "click", function(event) {
// 	event.preventDefault();
//
// 	var jqxhr = $.post( "?ajax=true&view=user/popupLogin.php", $(this).parents('.buttons').parents('form').serialize(), function() {} )
// 	.done( function() {
// 		$("header > .container > .panel").slideUp( "fast", function() {
// 			var jqxhr = $.get( "?ajax=true", function() {} )
// 			.done( function(data) {
// 				var eHtml = $(data).filter(".layout.main");
// 				$(".layout.main").html( eHtml );
// 			} ).fail( function() {} );
//
// 			var jqxhr = $.get( "?ajax=true&view=user/panel.php", function() {} )
// 			.done( function(data) {
// 				$("header > .container > .panel").html( data ).slideDown( "fast" );
// 			} ).fail( function() {} );
//
// 			reloadTimers();
// 			updateItemBid( null );
// 		} );
// 	} ).fail( function() {} );
// } );

/**
 * Popup login
 */
//$(document).delegate( ".popupLoginLink", "click", function(event) {
//	event.preventDefault();
//	$(this).slideUp( "fast", function() {
//		$(this).parents().parents().children(".view.user.popupLogin").slideDown();
//	} );
//} );
// $(document).delegate( ".loginLink", "click", function(event) {
// 	event.preventDefault();
// 	$(this).slideUp( "fast", function() {
// 		$(this).parents().parents().children(".view.user.popupLogin").slideDown();
// 	} );
// } );
// $(document).delegate( ".bidPopup .container a.close", "click", function(event) {
// 	event.preventDefault();
// 	$(this).parents('.container').parents('.bidPopup').fadeOut('fast');
// } );
// $(document).on( "click", ".popupLogin form .buttons button", function(event) {
// 	event.preventDefault();
//
// 	$.post( "?ajax=true&view=user/guestFormLogin.php", $(this).parents('.buttons').parents('form').serialize(), function(data) {
// 		var obj = JSON.parse( data );
//
// 		if( obj.result == 'success' ) {
// 			location.reload();
// 		} else {
// 			$(".popupLogin form .result").remove();
// 			$(".popupLogin form").prepend( '<div class="result error"><ol><li>' + obj.message + '</li></ol></div>' );
// 		}
// 	} );
// } );

// $(document).on( "click", "#loginLink", function(event) {
// 	event.preventDefault();
// 	$(".view.guestFormLogin").slideDown();
// } );
