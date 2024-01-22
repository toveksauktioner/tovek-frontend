//console.log( "# T10" );
/**
 *
 *
 * Main file for javascript related to auction handling
 *
 *
 */

/**
 * Script for viewmode switching
 */
$(document).delegate("ul.viewmodes li a.ajax", 'click', function(event) {
	event.preventDefault();

	$("ul.viewmodes li.active").removeClass( 'active' );
	$(this).parent('li').addClass( 'active' );

	var eActiveViewmode = $(this).parent("li");
	$(".sorting .optionsContainer ul.options li").each( function() {
		var sNewViewmodeType = getAjaxURLParameter( 'viewmode', $(eActiveViewmode).children('a').attr("href") );
		var sOldViewmodeType = getAjaxURLParameter( 'viewmode', $(this).children('a').attr("href") );
		var sNewHref = $(this).children('a').attr("href").replace(sOldViewmodeType, sNewViewmodeType);
		$(this).children('a').attr("href", sNewHref);
	} );
} );

/**
 * Script for sorting switching
 */
$(document).delegate(".sorting ul.options li a", 'click', function(event) {
	event.preventDefault();

	$("ul.options > li").removeClass("active");
	$(this).parent("li").addClass("active");
	$(".sorting.selector").html( $(this).html() );
	$(this).parent("li").parent("ul").parent(".optionsContainer").parent(".sorting").children(".selector").html( $(this).html() );

	var eActiveSorting = $(this).parent("li");
	$("ul.viewmodes li").each( function() {
		var sNewSortType = getAjaxURLParameter( 'sortBy', $(eActiveSorting).children('a').attr("href") );
		var sOldSortType = getAjaxURLParameter( 'sortBy', $(this).children('a').attr("href") );
		var sNewHref = $(this).children('a').attr("href").replace(sOldSortType, sNewSortType);
		$(this).children('a').attr("href", sNewHref);
	} );
} );

/**
 * ------------------------------
 */

/**
 * Info popup: TOOGLE NAV
 */
$(document).delegate("input#bidValue", 'keypress', function(event) {
	if( event.keyCode == '13' ) {
		event.preventDefault();
		//console.log( $(this).parent('.field').children('.buttons').html() );
		$(this).parent('.field').parent('.bidForm').children('.buttons').children( 'button[name="submitMaxBid"]' ).trigger( "click" );
	}
} );
$(document).on('keyup', "input#bidValue", function(event) {
 // Clear value of other than numbers
 var str = $( this ).val();
 var res = str.replace( /[^0-9]+/g, "" );
 $( this ).val( res );
} );
$(document).delegate( ".bidForm .buttons button[name=submitHelp]", "click", function(event) {
	event.preventDefault();
	var itemId = $(this).data("item-id");
	$(".bidHelp" + itemId).toggle();
} );
$(document).delegate( ".bidForm .buttons button[name=submitPost], .bidForm .buttons button[name=submitMaxBid]", "click", function(event) {
    event.preventDefault();

    var eForm = $(this).closest(".bidForm");
    var eView = $(eForm).parent(".view");
    var itemId = $(eForm).children(".hidden").children("#bidItemId").val();
    var iAuctionId = $(eForm).children(".hidden").children("#bidAuctionId").val();
    var fBidValue = $(eForm).children(".freeBid").children(".container").children("#bidValue").val();

	if( $(this).attr('name') == 'submitPost' ) {
        $(eForm).append('<input type="hidden" name="submitPost" value="1" />');
    } else {
		$(eForm).append('<input type="hidden" name="submitMaxBid" value="1" />');
	}

    var jqxhr = $.post( sHost + "?ajax=true&view=auctionAjax/bidForm.php", $(eForm).serializeArray(), function(data) {
        // console.log( data );
    } )
    .done( function(data) {
        var object = $.parseJSON(data);

        if( object.result != 'error' ) {
            // Notify push service
            oWebsocket.send( JSON.stringify( {
                type: "auctionBid",
                message: "a new bid has been accepted",
                data: itemId + ";" + fBidValue
            } ) );

            $.ajax( {
                url: sHost + "?ajax=true&view=auction/bidFormAdd.php&itemId=" + itemId,
                type: "GET",
                data: "noCss=true",
                async: true,
                dataType: "html"
            } ).fail( function(error) {
                // Failed
                console.log( error );

            } ).done( function( data, textStatus, jqXHR ) {
                // Done
                $(eView).replaceWith( data );

				updateItemBid( itemId );
            } );

			$.each( $('.bidPopup .notification'), function( iError, sErrorMessage ) {
				$(this).remove();
			} );

            $.each( object.error, function( iError, sErrorMessage ) {
                $(eForm).prepend( '<div class="notification success">' + sErrorMessage + '</div>' );
            } );

        } else {
			$.each( $('.bidFormAdd .notification'), function( iError, sErrorMessage ) {
				$(this).remove();
			} );

            $.each( object.error, function( iError, sErrorMessage ) {
                $(eForm).prepend( '<div class="notification error">' + sErrorMessage + '</div>' );
            } );
        }
    } )
    .fail( function(error) {
        console.log( error );
    } )
    .always( function() {
        //console.log( "finished" );
    } );
} );

$(document).delegate( ".removeAutoBid", "click", function(event) {
    event.preventDefault();

	var iItemId = $(this).data( 'item-id' );

	$.ajax( {
		url: $(this).attr('href'),
		type: "GET",
		data: "noCss=true",
		async: true,
		dataType: "html"
	} ).fail( function(error) {
		// Failed
		console.log( error );

	} ).done( function( data, textStatus, jqXHR ) {
		// Done
		$('.view.auction.bidFormAdd[data-item-id="' + iItemId + '"]').replaceWith( data );
	} );
} );

/**
 * ------------------------------
 */

///**
// * Script for stopping autoBid without reload
// */
//$(document).delegate("a[data-stopAutoBid]", 'click', function(event) {
//	event.preventDefault();
//
//	var eTargetLink = $(this);
//	var iItemId = $(this).data("stopAutoBid");
//
//	$.ajax( {
//		url: $(this).attr('href'),
//		type: 'GET',
//		data: 'noCss=true',
//		async: true,
//		dataType: "html"
//
//	} ).fail( function() {
//		// Failed
//		alert( textStatus + " " + errorThrown );
//
//	} ).done( function( data, textStatus, jqXHR ) {
//		// Done
//
//		var sUrl = sDomain + "?ajax=true&view=classic/auction_bidFormAdd.php&ajax-event=true";
//		sUrl += "&auctionId=" + $(eTargetLink).data("auction-id");
//		sUrl += "&itemId=" + $(eTargetLink).data("stopautobid");
//		sUrl += "&itemStatus=" + $(eTargetLink).data("item-status");
//
//		$.ajax( {
//			url: sUrl,
//			type: "GET",
//			data: "noCss=true",
//			async: true,
//			dataType: "html"
//		} ).fail( function() {
//			// Failed
//
//		} ).done( function( data, textStatus, jqXHR ) {
//			// Done
//			$(".view.bidForm").html(data);
//		} );
//	} );
//} );

///**
// * Script for stopping autoBid without reload
// */
//$(document).delegate("a[data-removePreBid]", 'click', function(event) {
//	event.preventDefault();
//
//	var eTargetLink = $(this);
//	var iItemId = $(this).data("removeprebid");
//
//	$.ajax( {
//		url: $(this).attr('href'),
//		type: 'GET',
//		data: 'noCss=true',
//		async: true,
//		dataType: "html"
//
//	} ).fail( function() {
//		// Failed
//		alert( textStatus + " " + errorThrown );
//
//	} ).done( function( data, textStatus, jqXHR ) {
//		// Done
//
//		var sUrl = sDomain + "?ajax=true&view=classic/auction_bidPreBidFormAdd.php&ajax-event=true";
//		sUrl += "&auctionId=" + $(eTargetLink).data("auction-id");
//		sUrl += "&itemId=" + iItemId;
//		sUrl += "&itemStatus=" + $(eTargetLink).data("item-status");
//
//		$.ajax( {
//			url: sUrl,
//			type: "GET",
//			data: "noCss=true",
//			async: true,
//			dataType: "html"
//		} ).fail( function() {
//			// Failed
//
//		} ).done( function( data, textStatus, jqXHR ) {
//			// Done
//			$(".view.preBidForm").html(data);
//		} );
//	} );
//} );

///**
// * Ajax handling of auction bidding
// */
//var sBidType;
//$(document).delegate("button[name=submitPost]", 'click', function(event) {
//	sBidType = "submitPost";
//} );
//$(document).delegate("button[name=submitMaxBid]", 'click', function(event) {
//	sBidType = "submitMaxBid";
//} );
//$(document).delegate("button[name=submitPreBid]", 'click', function(event) {
//	sBidType = "submitPreBid";
//} );
//$(document).delegate("button[name=submitHelp]", 'click', function(event) {
//	sBidType = "submitHelp";
//} );
//$(document).delegate("input#bidValue", 'keypress', function(event) {
//	if( event.keyCode == '13' ) {
//		sBidType = "submitMaxBid";
//		event.preventDefault();
//		$( "#bidForm" ).submit();
//	}
//} );
//$(document).delegate("#bidForm", 'submit', function(event) {
//	event.preventDefault();
//
//	if( $("#bidValue").val() == "" ) {
//		$("#previousSubmit").val( "submitMaxBid" );
//		$("#bidValue").focus();
//		return;
//
//	} else if( $("#bidValue").val() != "" && $("#previousSubmit").val() != "none" ) {
//		sBidType = $("#previousSubmit").val();
//		$("#previousSubmit").val( "none" );
//
//	}
//
//	if( sBidType != "submitHelp" ) {
//		var itemId = $("#itemId").val();
//		var iAuctionId = $("#auctionId").val();
//		var fBidValue = $("#bidValue").val();
//
//		var jqxhr = $.post( sDomain + "?ajax=true&view=classic/auction_bidFormAdd.php&type=" + sBidType, $(this).serializeArray(), function(data) {
//			// console.log( data );
//		} )
//		.done( function() {
//			// Notify push service
//			oWebsocket.send( JSON.stringify( {
//				type: "auctionBid",
//				message: "a new bid has been accepted",
//				data: itemId + ";" + fBidValue
//			} ) );
//
//			$.ajax( {
//				url: sDomain + "?view=classic/auction_bidFormAdd.php&ajax-event=true&itemId=" + itemId + "&auctionId=" + iAuctionId + "&itemStatus=active",
//				type: "GET",
//				data: "noCss=true",
//				async: true,
//				dataType: "html"
//			} ).fail( function() {
//				// Failed
//
//			} ).done( function( data, textStatus, jqXHR ) {
//				// Done
//				$(".view.bidForm[data-item-id=" + itemId + "]").html(data);
//
//				// Autofocus
//				$("input#bidValue").focus();
//
//				// Notify push service
//				//oWebsocket.send( JSON.stringify( {
//				//	type: "auctionBid",
//				//	message: "a new bid has been accepted",
//				//	data: itemId + ";" + fBidValue
//				//} ) );
//			} );
//		} )
//		.fail( function() {
//			//alert( "error" );
//		} )
//		.always( function() {
//			console.log( "finished" );
//		} );
//
//	} else {
//		alert( "Max bud fungerar så här:" );
//	}
//} );
//$(document).delegate("#preBidForm", 'submit', function(event) {
//	event.preventDefault();
//
//	if( $("#bidValue").val() == "" ) {
//		return;
//	}
//
//	if( sBidType != "submitHelp" ) {
//		var itemId = $("#itemId").val();
//		var fBidValue = $("#bidValue").val();
//
//		var jqxhr = $.post( sDomain + "?ajax=true&view=classic/auction_bidPreBidFormAdd.php&type=" + sBidType, $(this).serializeArray(), function(data) {
//			// console.log( data );
//		} )
//		.done( function() {
//			$.ajax( {
//				url: sDomain + "?view=auction/itemShowAjax.php&itemId=" + itemId,
//				type: "GET",
//				data: "noCss=true",
//				async: true,
//				dataType: "html"
//			} ).fail( function() {
//				// Failed
//
//			} ).done( function( data, textStatus, jqXHR ) {
//				// Done
//				$("tr#ajaxItemShow[data-item-shown=" + itemId + "] td").html(data);
//				$("div#ajaxItemShow[data-item-shown=" + itemId + "]").html(data);
//
//				// Autofocus
//				$("input#bidValue").focus();
//			} );
//		} )
//		.fail( function() {
//			//alert( "error" );
//		} )
//		.always( function() {
//			console.log( "finished" );
//		} );
//
//	} else {
//		alert( "Max bud fungerar så här:" );
//	}
//} );

/**
 * Check if WebSocket connection is established
 */
$(document).delegate( "input#bidValue", 'focus', function(event) {
	if( Modernizr.websockets ) {
		if( typeof oWebsocket == "undefined" || oWebsocket.readyState != 1 ) {
			init();
		}
	}
} );

/**
 * Handling of auction item list
 */
$(document).delegate("ul.items li a.ajax", 'click', function(event) {
	event.preventDefault();

	if( $(this).hasClass("favLink") == true ) {
		// Stop if fav-link
		return false;
	}

	// Show all hidden clocks
	$("span.itemTimeWrapper.clock:hidden").each( function() {
		$(this).show();
	} );

	// Selected item element
	var oSelectionLi = $(this).parents( 'li' );
	var oSelectionUl = $(this).parents( 'li' ).parents( 'ul' );

	// Selected item ID
	var iItemId = $(this).data( "item-id" );

	// Only display halted items
	if( $(this).parent( "li" ).hasClass( "halted" ) ) {
		// Stop upon halted item
		return;
	}

	// Remove visable item
	if( $("ul.items div#ajaxItemShow").length != 0 ) {
		// Remove
		$("ul.items div#ajaxItemShow").remove();
	}

	// Grab new active item
	if( oSelectionLi.hasClass( 'selected' ) ) {
		// Quit on same as active
		$(oSelectionLi).removeClass( 'selected' );
		return;
	} else {
		// Remove selected from old selection
		$("ul.items li.selected").removeClass( 'selected' );
	}

	// Determ some varibles
	var iSelectionIndex = oSelectionLi.prevAll("li:not(#ajaxItemShow)").length;
	var iNumberOfItems = $(oSelectionUl).children( 'li:not(#ajaxItemShow)' ).length;

	// Determ how many items per row
	var lisInRow = 0;
	$(oSelectionUl).children( 'li:not(#ajaxItemShow)' ).each( function() {
		if( $(this).prevAll("li").length > 0 ) {
			if( $(this).position().top != $(this).prevAll("li").first().position().top ) {
				return false;
			}
			lisInRow++;
		} else {
			lisInRow++;
		}
	} );

	var rowIndex = (iSelectionIndex + 1) % lisInRow;
	var insertIndex;
	if( rowIndex == 0 ) {
		insertIndex = iSelectionIndex;
	} else {
		insertIndex = lisInRow - rowIndex + iSelectionIndex;
	}

	if( insertIndex > (iNumberOfItems - 1) ) {
		insertIndex	= iNumberOfItems - 1;
	}

	$('html, body').animate({
		scrollTop: oSelectionLi.offset().top
	}, 400);

	// Total
	var iNumberOfItems = $(oSelectionUl).children( 'li:not(#ajaxItemShowHiding)' ).length;

	// Loader
	var oSpinner = createSpinner();

	$.ajax( {
		url: $(this).attr('href'),
		type: 'GET',
		data: 'noCss=true',
		async: true,
		dataType: "html",
		beforeSend: function() {
			if( $("ul.items div#ajaxItemShow").length > 0 ) {
				// Remove
				$("ul.items div#ajaxItemShow").stop().remove();
			}

			//$('<div id="ajaxItemShow" class="itemShow" data-item-shown="' + iItemId + '" style="display:none;">&nbsp;</div>').insertAfter( "ul.items li:eq(" + insertIndex + ")").data("item-id", iItemId);
			$('<div id="ajaxItemShow" class="itemShow" data-item-shown="' + iItemId + '" style="display:none;">&nbsp;</div>').insertAfter( $(oSelectionUl).children( "li:eq(" + insertIndex + ")" ) ).data("item-id", iItemId);

			oSelectionLi.addClass("loading").append('<div id="spinner">&nbsp;</div>');
			oSpinner.spin(document.getElementById('spinner'));
		}

	} ).fail( function() {
		// Failed
		alert( textStatus + " " + errorThrown );

	} ).done( function( data, textStatus, jqXHR ) {
		// Done
		oSelectionLi.removeClass("loading");
		oSpinner.spin(false);
		$('#spinner', oSelectionLi).remove();
		$("ul.items div#ajaxItemShow").html(data).show(400, function() {
			oSelectionLi.addClass("selected");
			var iScrollTo = $("#ajaxItemShow").children(".view").offset().top - 36;
			$('html, body').animate({
				scrollTop: iScrollTo
			}, 400);
		} );

		// Hide clock in list
		$("ul.items > li > .information > a > p.metadata > span.itemTimeWrapper.clock[data-item-id=" + iItemId + "]").hide();
		$("ul.items > li > .info > a > .metadata > p.endTime > span.itemTimeWrapper.clock[data-item-id=" + iItemId + "]").hide();

		// Notify push service
		sendWebSocketMessage( 'content', 'A auction item was viewed', 'null' );
	} );
} );
// ...and close link:
$(document).delegate("ul.items div.itemShow .view div a.close", 'click', function(event) {
	event.preventDefault();

	// Show all hidden clocks
	$("span.itemTimeWrapper.clock:hidden").each( function() {
		$(this).show();
	} );

	$("#ajaxItemShow").slideUp( 400, function() {
		// Remove
		$(this).remove();
	} );

	$('html, body').animate( {
		scrollTop: $("ul.items li.selected").offset().top - 40
	}, 400);

	$("ul.items li.selected").removeClass("selected");
} );

/**
 * Handling of auction item table
 */
$(document).delegate("table.items tbody tr", 'click', function(event) {
	if( $(this).hasClass( 'itemShow' ) ) {
		// Quit on open ajax tr
		return;
	}

	// Show all hidden clocks
	$("span.itemTimeWrapper.clock:hidden").each( function() {
		$(this).show();
	} );

	// Selected item element
	var oSelectionTr = $(this);
	var oSelectionTable = $(this).parents("table");

	// Selected item ID
	var iItemId = $(this).data( "item-id" );

	// Only display halted items
	if( $(this).hasClass( "halted" ) ) {
		// Stop upon halted item
		return;
	}

	// Remove visable item
	if( $("table.items tr#ajaxItemShow").length != 0 ) {
		// Remove
		$("table.items tr#ajaxItemShow").remove();
	}

	// Grab new active item
	if( oSelectionTr.hasClass( 'selected' ) ) {
		// Quit on same as active
		$(oSelectionTr).removeClass( 'selected' );
		return;
	} else {
		// Remove selected from old selection
		$("table.items tr.selected").removeClass( 'selected' );
	}

	$('html, body').animate( {
		scrollTop: oSelectionTr.offset().top
	}, 400);

	// Loader
	var oSpinner = createSpinner();

	$.ajax( {
		url: $(oSelectionTr).data( "ajax-href" ),
		type: 'GET',
		data: 'noCss=true',
		async: true,
		dataType: "html",
		beforeSend: function() {
			if( $("table.items tr#ajaxItemShow").length > 0 ) {
				// Remove
				$("table.items tr#ajaxItemShow").stop().remove();
			}

			$('<tr id="ajaxItemShow" class="itemShow" data-item-shown="' + iItemId + '"><td colspan="8" style="display:none;">&nbsp;</td></tr>').insertAfter( oSelectionTr ).data("item-id", iItemId);

			oSelectionTr.addClass("loading").append('<div id="spinner">&nbsp;</div>');
			oSpinner.spin(document.getElementById('spinner'));
		}

	} ).fail( function() {
		// Failed
		alert( textStatus + " " + errorThrown );

	} ).done( function( data, textStatus, jqXHR ) {
		// Done
		oSelectionTr.removeClass("loading");
		oSpinner.spin(false);
		$('#spinner', oSelectionTr).remove();
		$("table.items tr#ajaxItemShow td").html(data).show(400, function() {
			oSelectionTr.addClass("selected");
			var iScrollTo = $("tr#ajaxItemShow td .view").offset().top - 36;
			$('html, body').animate({
				scrollTop: iScrollTo
			}, 400);
		} );

		// Hide clock in list
		$("table.items > tbody > tr > td.itemEndTime > span.itemTimeWrapper.clock[data-item-id=" + iItemId + "]").hide();

		// Notify push service
		sendWebSocketMessage( 'content', 'A auction item was viewed', 'null' );
	} );
} );

// ...and close link:
$(document).delegate("table.items tr.itemShow .view div a.close", 'click', function(event) {
	event.preventDefault();

	// Show all hidden clocks
	$("span.itemTimeWrapper.clock:hidden").each( function() {
		$(this).show();
	} );

	$(".itemShow").slideUp( 400, function() {
		// Remove
		$(this).remove();
	} );

	$('html, body').animate( {
		scrollTop: $("table.items tr.selected").offset().top - 40
	}, 400);

	$("table.items tr.selected").removeClass("selected");
} );

/**
 * Favorite link in tables
 */
$(document).delegate("a.ajax.favLink", 'click', function(event) {
	event.preventDefault();

	var eTargetLink = $(this);
	var sStatus = $(this).attr("data-status");

	$.ajax( {
		url: $(this).attr('href'),
		type: 'GET',
		data: 'noCss=true',
		async: true,
		dataType: "html"
	} ).fail( function() {
		// Failed

	} ).done( function( data, textStatus, jqXHR ) {
		// Done
		if( sStatus == "true" ) {
			$(eTargetLink).children("img").attr( "src", "/images/templates/tovekClassic/icon-fav-grey-list.png" );
			$(eTargetLink).attr( "data-status", "false" );
		} else {
			$(eTargetLink).children("img").attr( "src", "/images/templates/tovekClassic/icon-fav-list.png" );
			$(eTargetLink).attr( "data-status", "true" );
		}
	} );

	return false;
} );

/**
 * Autoload auction list upon scroll
 */
$( function() {
	var iPreviousDocumentHeight = 0;
	$(window).scroll( function() {
		var sAutoscrollStatus = getCookie( "autoscroll" );
		if( sAutoscrollStatus == "on" ) {
			if( $(window).scrollTop() + $(window).height() >= $(document).height() - 350 ) {
				if( $(window).scrollTop() + $(window).height() == iPreviousDocumentHeight ) {
					iPreviousDocumentHeight = 0;
					return;
				} else {
					iPreviousDocumentHeight = $(document).height();
				}

				var oAuctionItemLists = $.parseJSON( decodeURIComponent( getCookie( "AuctionItemLists" ) ) );
				var aAuctionItemLists = $.map( oAuctionItemLists, function(listData, listKey) {
					if( $("#" + listKey).length == 0 ) {
						return false;
					}

					if( (parseInt(listData['entries']) * (listData['entriesSequence'] - 1)) >= listData['entriesTotal'] ) {
						return false;
					}

					if( listData['entriesSequence'] === null ) {
						var sEntriesSequence = '2';
					} else {
						var sEntriesSequence = listData['entriesSequence'];
					}

					// Route check
					var sUrl = window.location.pathname;

					if( sUrl == '/min-sida' ) {
						return false;
					}

					var aUrl = sUrl.split( '/' );
					var sCurrUrl = aUrl[aUrl.length - 2] + aUrl[aUrl.length - 1];

					var aUrl2 = listData['routePath'].split( '/' );
					var sCheckUrl = aUrl2[aUrl.length - 2] + aUrl2[aUrl.length - 1];

					//console.log( listData['routePath'] + " = " + sUrl );
					if( sCheckUrl != sCurrUrl ) {
						return false;
					}

					/**
					 * Assamble the ajax url
					 */
					var sUrl = sDomain + listData['routePath'] + "?ajax=true";
					sUrl = sUrl + "&view=" + listData['viewFile'];
					sUrl = sUrl + "&listKey=" + listData['listKey'];
					sUrl = sUrl + "&entriesSequence=" + (parseInt(sEntriesSequence) + 1);
					sUrl = sUrl + "&viewmode=" + listData['viewmode'];
					sUrl = sUrl + "&routePath=" + listData['routePath'];
					if( typeof(getURLParameter("searchQuery")) !== 'undefined' ) {
						sUrl = sUrl + "&searchQuery=" + getURLParameter("searchQuery");
					}

					$.ajax( {
						url: sUrl,
						type: "GET",
						data: "noCss=true",
						async: true,
						dataType: "html",
						beforeSend: function() {}
					} ).fail( function() {
						// Failed

					} ).done( function( data, textStatus, jqXHR ) {
						// Done
						$("#" + listData['listKey']).html(data).fadeIn( 400, function() {
							$("#" + listData['listKey'] + " a.ajax[data-ajax-href]").each( function() {
								$(this).attr( "href", $(this).data("ajax-href") );
								$(this).removeAttr( "data-ajax-href" );
							} );
						} );
					} );

					return true;
				} );
			}
		}
		return;
	} );
} );

/**
 * Toggle bidform on mobile devices
 */
$(document).ready(function(){
	$('.toggleBiddingForm').click(function(){
		$('.itemBidding').toggleClass('show');
		$('.toggleBiddingForm').toggle();
	})
});
