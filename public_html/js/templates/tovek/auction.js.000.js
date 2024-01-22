/**
 *
 * This file handle auction stuff in general
 *
 */

var iGlobalCurrentItemId = 0;

$(document).on( "click", ".favLinkContainer", function(event) {
	event.stopPropagation();

	const favObj = $( this );
	const itemId = favObj.data( "item-id" );
	const selected = ( favObj.hasClass("selected") ? 1 : 0 );

	if( (typeof userId == "string") && (parseInt(userId) > 0) ) {
		$.get( ajaxGlobalUrl, {
			ajax: 1,
			view: "auction/userFavItems.php",
			userId: userId,
			itemId: itemId,
			selected: selected,
			frmSetFav: 1
		}, function(data) {
			data = JSON.parse( data );

			if( data.status == "selected" ) {
				favObj.addClass( "selected" );
			} else {
				favObj.removeClass( "selected" );
			}
		} );

	} else {
		$("#loginBtn").click();
	}
} );
$( function() {
	if( (typeof userId == "string") && (parseInt(userId) > 0) ) {
		$.get( ajaxGlobalUrl, {
			ajax: 1,
			view: "auction/userFavItems.php",
			userId: userId,
			frmGetFav: 1,
			time: Date.now()
		}, function(data) {
			data = JSON.parse( data );

			for( favorite of data.favorites ) {
				$(".favLinkContainer[data-item-id=" + favorite + "]").addClass( "selected" );
			}
		} );

	}
} );

/**
 * Info popup: OPEN
 */
function infoPopupOpen( iItemId ) {
	if( iItemId == "" ) return;

	$('.infoPopup').each( function() {
		if( ($(this).parent().data('item-id') != iItemId) && ($(this).children('.container').children('.view').length > 0) ) {
			$(this).removeClass('show').children('.container').children('.view').remove();
			$(this).parent().removeClass("show");
			// console.log( 'Removed 2' );
    }
	} );

  var eThis = $('#itemEntry' + iItemId);
  var eInfoPopup = $('#infoPopup' + iItemId);

  if( eInfoPopup.hasClass('show') ) {
    return;
  }

	// Archived auction items just link to item page
	if( eThis.parents(".itemList").hasClass('itemListArchived') ) {
		location.href = eThis.find(".itemTitle a").attr( "href" );
		return;
	}

	// Store selected item in session
	$.get( ajaxGlobalUrl, {
		ajax: 1,
		view: "ajax/storeSessionData.php",
		key: 'auctionSelectedItem',
		value: iItemId,
		referrer: location.href
	}, function(data) {
		// console.log(data);
	} );

  $('.infoPopup').each( function() {
    if( $(this).hasClass('show') ) {
      $(this).removeClass('show');
    }
  }, function() {} );

	// Get timestamp for every second in interval
	var currentTimestamp = Date.now() - serverTimeDiff;
	if( currentTimestamp % iCheckItemDate != 0 ) currentTimestamp -= (currentTimestamp % iCheckItemDate);

  $.ajax( {
    url: ajaxGlobalUrl + '?ajax=true&view=auctionAjax/itemShow.php&itemId=' + iItemId + "&time=" + currentTimestamp,
    type: 'GET',
    data: 'noCss=true',
    async: true,
    dataType: "html"
	} ).fail( function() {
    // Failed
    alert( textStatus + " " + errorThrown );

  } ).done( function( data, textStatus, jqXHR ) {
    eInfoPopup.children('.container').html( data );
	eInfoPopup.removeClass( 'load' ).addClass( 'show' );
	eThis.addClass("show");
	eInfoPopup.find(".bidForm input").focus();

	itemPos = eThis.position();

	if( typeof itemPos != "undefined" ) {
		$("html, body").animate( {
	      scrollTop: itemPos.top
	    }, 500 );
	}
  } );
}
$(document).on( "click", ".links .moreLink a.button.info, .view.auction.itemList li.itemEntry .listType", function(event) {
	event.preventDefault();

	const iItemId = $( this ).data('item-id');
	const partId = $(".auction.itemList").data( "part-id" );
	// console.log(partId);

	setCookie( "auctionSelectedItem-" + partId, iItemId, (1/24) );

	if( !$(this).hasClass('popupLink') ) {
		infoPopupOpen( iItemId );
	}
} );
$(document).on( "click", ".view.auction.itemList li.itemEntry .listType a", function(event) {
	event.stopPropagation();
} );
$( function() {
	const partId = $(".auction.itemList").data( "part-id" );

	if( typeof partId != "undefined" ) {
		const selectedItem = getCookie( "auctionSelectedItem-" + partId );

		if( typeof selectedItem != "undefined" ) {
			infoPopupOpen( selectedItem );
		}
	}
} );

/**
 * Info popup: CLOSE
 */
$(document).on( "click", ".itemEntry.popup .closeLink a", function(event) {
	event.preventDefault();
	$(this).parent('.closeLink').parent('.itemEntry').removeClass( 'open' );
} );
$(document).on( "click", ".infoPopup .topbar .closeLink .close", function(event) {
    event.preventDefault();
    $(this).parent('.closeLink').parent('.topbar').parent('.infoPopup').animate( {opacity: 0, top: '0'}, 200, function() {
       //$(this).parent('.closeLink').parent('.topbar').parent('.infoPopup').delay(200).removeClass('show');
       $(this).delay(200).removeClass('show').removeAttr('style');
	   $(this).children('.container').children('.view').remove();
    } );
} );

/**
 * Info popup: TOOGLE NAV
 */
// $(document).on( "click", ".infoPopup .topbar .itemNav span", function(event) {
//     event.preventDefault();
//
//     var eParent = $(this).parent('.itemNav').parent('.topbar').parent('.infoPopup');
//     var eEntry = $(eParent).parent('.itemEntry');
//     var iCurrentScroll = $(window).scrollTop();
//
//     if( $(this).attr('class') == 'up' ) {
//         $('html, body').animate( { scrollTop: (iCurrentScroll - 210) }, 50 );
//
//         $(eParent).animate( {opacity: 0, top: '-50%'}, 275, function() {
//             $(eParent).removeClass('show');
//         } );
//
//         var eEntryPrev = $(eEntry).prev('li');
//         var iItemId = $(eEntryPrev).data('item-id');
//
//         $.ajax( {
//             url: '?ajax=true&view=auctionAjax/itemShow.php&itemId=' + iItemId,
//             type: 'GET',
//             data: 'noCss=true',
//             async: true,
//             dataType: "html"
//
//         } ).fail( function() {
//             // Failed
//             alert( textStatus + " " + errorThrown );
//
//         } ).done( function( data, textStatus, jqXHR ) {
//             //console.log( "#infoPopup" + iItemId );
//             //console.log( data );
//
//             $("#infoPopup" + iItemId).css( "top", "0" );
//             $("#infoPopup" + iItemId).children('.container').html( data );
//             $("#infoPopup" + iItemId).addClass( 'show' );
//             $("#infoPopup" + iItemId).animate( {opacity: 1, top: '-50%'}, 250 );
//         } );
//
//     } else {
//         $('html, body').animate( { scrollTop: (iCurrentScroll + 210) }, 50 );
//
//         $(eParent).animate( {opacity: 0, top: '0'}, 275, function() {
//             $(eParent).removeClass('show');
//         } );
//
//         var eEntryNext = $(eEntry).next('li');
//         var iItemId = $(eEntryNext).data('item-id');
//
//         $.ajax( {
//             url: '?ajax=true&view=auctionAjax/itemShow.php&itemId=' + iItemId,
//             type: 'GET',
//             data: 'noCss=true',
//             async: true,
//             dataType: "html"
//
//         } ).fail( function() {
//             // Failed
//             alert( textStatus + " " + errorThrown );
//
//         } ).done( function( data, textStatus, jqXHR ) {
//             //console.log( "#infoPopup" + iItemId );
//             //console.log( data );
//
//             $("#infoPopup" + iItemId).css( "top", "-50%" );
//             $("#infoPopup" + iItemId).children('.container').html( data );
//             $("#infoPopup" + iItemId).addClass( 'show' );
//             $("#infoPopup" + iItemId).animate( {opacity: 1, top: '-50%'}, 250 );
//         } );
//     }
// } );

// $(document).on( 'click', "a#nextAuctionBtn", function(event) {
// 	event.preventDefault();
//
// 	var currentAuctionPartId = $(this).data("auction-part-id");
//
// 	$.get( "?ajax=true&view=auction/list.php&getNextPart=" + currentAuctionPartId, function(data) {
// 		if( data != '' ) {
// 			location.href = data + "#listTop";
// 		}
// 	} );
// } );

/**
 * Info popup: TOOGLE NAV
 */
$(document).on( 'keypress', "input#bidValue", function(event) {
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
$(document).on( "click", ".bidForm .buttons button[name=submitHelp]", function(event) {
	event.preventDefault();
	var itemId = $(this).data("item-id");
	$(".bidHelp" + itemId).toggle();
} );
$(document).on( "click", ".bidForm .buttons button[name=submitPost], .bidForm .buttons button[name=submitMaxBid]", function(event) {
    event.preventDefault();

    var eForm = $(this).closest(".bidForm");
    var eView = $(eForm).parent(".view");
    var itemId = $(eForm).children(".hidden").children("#bidItemId").val();
    var iAuctionId = $(eForm).children(".hidden").children("#bidAuctionId").val();
	  var fBidValue = $(eForm).find("#bidValue").val();

	if( $(this).attr('name') == 'submitPost' ) {
    // $(eForm).append('<input type="hidden" name="submitPost" value="1" />');
		$(eForm).find("#bidType").val( "normal" );
		$.post( "/global/log-data", {
			logLabel: "bidButton",
			logData: "Item-ID: " + itemId + "; Button: normal; Value: " + fBidValue + ";"
		}, function(data) {
			// console.log(data);
		} );
  } else {
		// $(eForm).append('<input type="hidden" name="submitMaxBid" value="1" />');
		$(eForm).find("#bidType").val( "auto" );
		$.post( "/global/log-data", {
			logLabel: "bidButton",
			logData: "Item-ID: " + itemId + "; Button: auto; Value: " + fBidValue + ";"
		}, function(data) {
			// console.log(data);
		} );
	}

    var jqxhr = $.post( "?ajax=true&view=auctionAjax/bidForm.php", $(eForm).serializeArray(), function(data) {
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
                url: "?ajax=true&view=auction/bidFormAdd.php&itemId=" + itemId + "&userId=" + userId,
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



$(document).on( "click", ".removeAutoBid", function(event) {
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


// $(document).on( 'click', ".moreButton a", function(event) {
// 	event.preventDefault();

// 	var jqxhr = $.get( $(this).attr("href") + "&ajax=true", function() {} )
// 	.done( function(data) {
// 		var eHtml = $(data).filter(".layout.main");
// 		$(".layout.main").html( eHtml );

// 		setTimeout( function() {
// 			reloadTimers();
// 			updateItemBid( null );
// 		}, 500 );
// 	} ).fail( function() {} );
// } );


$(document).on( 'click', '.toolbar .listTypeSelect', function() {
	var listType = $( this ).data( 'list-type' );

	var partId = $(".view.auction.itemList").data( "part-id" );
	if( typeof partId == "undefined" ) partId = "userList";

	// Store selection in session
	// $.get( '/', {
	// 	ajax: 1,
	// 	view: "ajax/storeSessionData.php",
	// 	key: 'auctionListView',
	// 	value: listType
	// } );


	setCookie( "auctionListType-" + partId, listType, (1/24) );

	if( listType == 'compact' ) {
		$('.toolbar .listTypeSelect.normal').removeClass( 'selected' );
		$('.toolbar .listTypeSelect.compact').addClass( 'selected' );

		$('.listWrapper').removeClass( 'normal' ).addClass( 'compact' );

	} else {
		$('.toolbar .listTypeSelect.compact').removeClass( 'selected' );
		$('.toolbar .listTypeSelect.normal').addClass( 'selected' );

		$('.listWrapper').removeClass( 'compact' ).addClass( 'normal' );
	}

	checkVisibleItems();
} );
$( function() {
	if( $(".view.auction.itemList").length > 0 ) {
		var partId = $(".view.auction.itemList").data( "part-id" );
		if( typeof partId == "undefined" ) partId = "userList";

		const listType = getCookie( "auctionListType-" + partId );
		// console.log(listType);
		if( listType == 'compact' ) {
			$('.toolbar .listTypeSelect.normal').removeClass( 'selected' );
			$('.toolbar .listTypeSelect.compact').addClass( 'selected' );

			$('.listWrapper').removeClass( 'normal' ).addClass( 'compact' );

		} else {
			$('.toolbar .listTypeSelect.compact').removeClass( 'selected' );
			$('.toolbar .listTypeSelect.normal').addClass( 'selected' );

			$('.listWrapper').removeClass( 'compact' ).addClass( 'normal' );
		}
	}
} );


// Visibility handling - which items should be updated and
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

// Load item list images if they are visible - triggered on document ready and when page is scrolled
function checkVisibleItems() {
	$(".view.auction.itemList .itemEntry").each( function() {
		if( isVisibleInViewport( $(this)) ) {
			$( this ).addClass( "visible" );

			if( $(this).find("picture.loadImages").length > 0 ) {
				// load images
				$( this ).find("picture.loadImages source").each( function() {
					$( this ).prop( "srcset", $(this).data("srcset") );
				} );
				$( this ).find("picture.loadImages img").each( function() {
					$( this ).prop( "src", $(this).data("src") );
				} );

				// Stop loadin of more images
				$( this ).find("picture.loadImages").removeClass( "loadImages" );
			}

			// Activate timers

		} else {
			$( this ).removeClass( "visible" );
		}
	} );
}
$(document).on( 'scroll', function() {
	checkVisibleItems();
} );
$( function() {
	checkVisibleItems();
} );


// Change list type
$(document).on( 'change', '#listEndedItems', function() {
	$(".view.auction.itemList .listWrapper").toggleClass( "showEnded" );
	const partShowEnded = $(".view.auction.itemList .listWrapper").hasClass( "showEnded" );
	var partId = $(".view.auction.itemList").data( "part-id" );
	if( typeof partId == "undefined" ) partId = "userList";

	setCookie( "auctionShowEnded-" + partId, partShowEnded, (1/24) );
} );
$( function() {
	if( $(".view.auction.itemList").length > 0 ) {
		var partId = $(".view.auction.itemList").data( "part-id" );
		if( typeof partId == "undefined" ) partId = "userList";

		const showEnded = getCookie( "auctionShowEnded-" + partId );
		
		if( (showEnded == "true") || $("#listEndedItems").is(":disabled") ) {
			$(".view.auction.itemList .listWrapper").addClass( "showEnded" );
			$("#listEndedItems").prop( 'checked', true );
		}
	}
} );

// Change additional filters
$(document).on( 'change', '.toolbar .additionalFilter input[type="checkbox"]', function() {
	var thisGroup = $( this ).attr( 'name' );
	var values = [];
	var inputSelector = '.toolbar .additionalFilter input[name="' + thisGroup + '"]:checked';

	$( inputSelector ).each( function() {
		values.push( $(this).val() );
	} );

	location.href = '?' + thisGroup + '=' + values;
} );
