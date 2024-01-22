/**
 *
 * This file handle auction stuff related to clock/time
 *
 */

// Settings
const fastUpdateFreq = 2000;  // milliseconds
const slowUpdateFreq = 60000;  // milliseconds
const slowFastBreakpoint = 3600000; // milliseconds before end of first item on page

var iCheckItemDate = fastUpdateFreq;  // milliseconds
var iDelayEnd = 5000;		// After time has run out - delay milliseconds before closing and removing form
// var iUpdateItemClock = iCheckItemDate; //4000; // milliseconds
// var aBidTimmerCheck = [300,240,180,118,105,90,60,30];  // @seconds (Extra bid check)
var iRedTimmerTime = 120000; // milliseconds
var serverTimeDiff = 0;
var updateInterval = null;


/**
 * Init / on load
 */
$(document).ready( function() {
	updateAllItems();
	getServerTimeDiff();

	updateInterval = setInterval( updateAllItems, iCheckItemDate );
	setInterval( countDownClocks, 1000 );
	setInterval( getServerTimeDiff, 60000 );	// Correct incorrect time every minute
} );

function getServerTimeDiff() {
	// Get server time and calculate diff between computers clock

	$.get( "/getServerTime.php", {
		userId: userId,
		time: Date.now()
	}, function( data ) {
		var serverTime = parseInt( data );
		if( Number.isInteger(serverTime) ) {
			serverTimeDiff = Date.now() - ( serverTime * 1000 );
			// console.log(serverTimeDiff); 
			countDownClocks();
		}
	} );
}


function countDownClocks() {
	$(".endTime").each( function() {
		var currentTimestamp = Date.now() - serverTimeDiff;
		var itemTimestampData = $(this).data( "timestamp" );

		if( typeof itemTimestampData != 'undefined' ) {
			var itemTimestamp = ( itemTimestampData * 1000 ) - currentTimestamp; 
			var timerClass = 'itemTimer';
			var itemId = $( this ).data( "item-id" );

			if( itemTimestamp > 0 ) {
				$(".view.bidFormAdd[data-item-id=" + itemId + "] form input").prop( "disabled", false );
				$(".view.bidFormAdd[data-item-id=" + itemId + "] form button").prop( "disabled", false );

				var formattedTime = formatTime( itemTimestamp, (itemTimestampData * 1000) );

				if( itemTimestamp < 3600000 ) {
					if( itemTimestamp <= iRedTimmerTime ) {
						timerClass += ' red';
					}
				}

			} else if( itemTimestamp > -iDelayEnd ) {
				// Let some time pass before the item dissappers (So the computer timer dont hide when bidding close to 0)
				// iCheckItemDate is the time between updates of items

				$(".view.bidFormAdd[data-item-id=" + itemId + "] form input").prop( "disabled", true );
				$(".view.bidFormAdd[data-item-id=" + itemId + "] form button").prop( "disabled", true );

			} else {
				var formattedTime = "Avslutad";
				var listWrapperObj = $( this ).parents(".listWrapper");
				var itemEntryObj = $( this ).parents(".itemEntry");
				var formObj = $(".view.bidFormAdd[data-item-id=" + itemId + "]");

				if( !listWrapperObj.hasClass('showEnded') && !itemEntryObj.is(":hidden") ) {
					$(itemEntryObj).animate( {
						height: 0
					}, function() {
						$( this ).addClass( "ended" ).css( "height", "auto" );
					} );
					checkVisibleItems();
				}

				formObj.children('form').remove();
				formObj.addClass('isEnded');
				formObj.find(".currentBid .label").html( "Avslutat rop" );
			}

			if( formattedTime ) $(this).html( '<span class="' + timerClass + '">' + formattedTime + '</span>' );
		}

	} );
}


// Update all items on page
// New way of updating
function updateAllItems() {
	updateItemBid( null );
	// $("li.itemEntry, .view.auction.itemShow").each( function() {
	// 	updateItemBid( $(this).data("item-id") );
	// } );
}

/**
 *
 * Update item bid data
 *
 */
function updateItemBid( iItemId ) {
	/**
	 * Item handling
	 */
// console.log("updateItemBid - " + iItemId);
	if( iItemId == null ) {
		var aItems = [];

		$(".items .itemEntry, .view.auction.itemShow").each( function() {
			aItems.push( $(this).data("item-id") );
		} );

	} else {
		aItems = [ iItemId ];
	}
// console.log(aItems);
	// No items listed - ignore
	if( aItems.length == 0 ) return false;

	// Sort ids using function to get natural sort - i.e. 1,2,10 not 1,10,2
	aItems.sort( function(a, b){return a - b} );

	// Get unique ids
	// Function from https://stackoverflow.com/questions/1960473/get-all-unique-values-in-a-javascript-array-remove-duplicates
	aItems = aItems.filter( (v, i, a) => a.indexOf(v) === i );
	var sItemIds = aItems.join( "," );

	// Get timestamp for every second in interval
	var currentTimestamp = Date.now() - serverTimeDiff;
	if( currentTimestamp % iCheckItemDate != 0 ) currentTimestamp -= (currentTimestamp % iCheckItemDate);

	/**
	 * Update displayed bids in list & tables
	 * In success function updates of opened bid in list
	 */
	$.ajax( {
		url: "/?ajax=true&view=auctionAjax/getItemData.php&itemId=" + sItemIds + "&time=" + currentTimestamp,
		type: "GET",
		data: "noCss=true",
		async: true,
		dataType: "html"
	} ).fail( function() {
		// Failed
	} ).done( function( data, textStatus, jqXHR ) {
		returnData = JSON.parse( data );

		if( returnData.result == 'success' ) {
			var lowestTimediff = null; // Variable that will determine if the update interval should be changed

			$.each( returnData.data, function( key, oEntry ) {

				$( ".endTime[data-item-id=" + oEntry.itemId + "]" ).each(function() {
					$(this).data( "timestamp", oEntry.timestamp );
				} );

				if( oEntry.itemBidCount != '0' ) {
					$( "span.itemCurrentBid" + oEntry.itemId ).each(function() {
						$(this).html( " " + oEntry.currentBid + " (" + oEntry.itemBidCount + ') <span class="bidder">' + oEntry.currentBidUser + "</span>" );
					} );

				} else {
					$( "span.itemCurrentBid" + oEntry.itemId ).each(function() {
						$(this).html( " " + oEntry.itemMinBid + " (" + oEntry.itemBidCount + ")" );
					} );
				}

				/**
				 * Update bid forms current bid part if occurring
				 */
				$( ".view.bidFormAdd[data-item-id=" + oEntry.itemId + "] #currentBid" + oEntry.itemId ).html( oEntry.currentBid );
				$( ".view.bidFormAdd[data-item-id=" + oEntry.itemId + "] .currentBidUser .bidder" ).html( oEntry.currentBidUser );
				if( oEntry.currentBidUserId == userId ) {
					$( ".view.bidFormAdd[data-item-id=" + oEntry.itemId + "]" ).addClass( "isWinner isBidder" );
				} else {
					$( ".view.bidFormAdd[data-item-id=" + oEntry.itemId + "]" ).removeClass( "isWinner" );
				}

				/**
				 * Update bid list if occurring 
				 */
				$(".view.bidList[data-item-id=" + oEntry.itemId + "] ul").replaceWith( oEntry.bidHistoryHtml );

				/**
				 * Update bid list all if occurring
				 */
				$(".view.bidListAll[data-item-id=" + oEntry.itemId + "] ul").replaceWith( oEntry.bidHistoryHtml );


				// Find the lowest timestamp
				thisTimediff = (oEntry.timestamp * 1000) - currentTimestamp;
				if( thisTimediff > -iCheckItemDate ) {
					$(".itemEntry.ended[data-item-id=" + oEntry.itemId + "]").removeClass( "ended" );

					if( (lowestTimediff == null) || (lowestTimediff > thisTimediff) ) {
						lowestTimediff = thisTimediff;
					}
				}

			} );

			// Change update interval depending on time left
			if( lowestTimediff != null ) {
				if( lowestTimediff < slowFastBreakpoint) {
					if( iCheckItemDate != fastUpdateFreq ) {
						iCheckItemDate = fastUpdateFreq;
						clearInterval( updateInterval );
						updateInterval = setInterval( updateAllItems, iCheckItemDate );
					}
				} else {
					if( iCheckItemDate != slowUpdateFreq ) {
						iCheckItemDate = slowUpdateFreq;
						clearInterval( updateInterval );
						updateInterval = setInterval( updateAllItems, iCheckItemDate );
					}
				}
			} else {
				if( iCheckItemDate == slowUpdateFreq ) {
					clearInterval( updateInterval );
				} else {
					// Do an extra update with slow update frequency
					iCheckItemDate = slowUpdateFreq;
					clearInterval( updateInterval );
					updateInterval = setInterval( updateAllItems, iCheckItemDate );
				}
			}
console.log(iCheckItemDate);

		} else {
			console.log( returnData.error );
		}

	} );
}

/**
 * Misc functions
 */
function formatTime(time, timeStamp) {

	if( time < 3600000  ) {
		time = time / 10;
		var min = parseInt(time / 6000),
			sec = parseInt(time / 100) - (min * 60),
			hundredths = pad(time - (sec * 100) - (min * 6000), 2);
		return (min > 0 ? pad(min, 2) : "00") + ":" + pad(sec, 2);

	} else {
		const currentDate = new Date( timeStamp );
		const checkDate = new Date();
		checkDate.setHours(23);
		checkDate.setMinutes(59);
		checkDate.setSeconds(59);

		var dayString = '';
		if( timeStamp > checkDate.getTime() ) {
			checkDate.setDate( checkDate.getDate() + 1 );

			if( timeStamp > checkDate.getTime() ) {
				const monthsLiteral = [
					'januari',
					'februari',
					'mars',
					'april',
					'maj',
					'juni',
					'juli',
					'augusti',
					'september',
					'oktober',
					'november',
					'december'
				];
				dayString = currentDate.getDate() + " " + monthsLiteral[ currentDate.getMonth() ];
			} else {
				dayString = "Imorgon";
			}
		} else {
			dayString = "Idag";
		}

		return dayString + " " + pad(currentDate.getHours(), 2) + ":" + pad(currentDate.getMinutes(), 2);
	}

}
function pad(number, length) {
	var str = "" + number;
	while (str.length < length) {
		str = "0" + str;
	}
	return str;
}
