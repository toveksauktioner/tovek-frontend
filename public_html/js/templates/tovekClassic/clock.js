/**
 *
 * This scripts handle auction clocks
 *
 */

// Settings
var iCheckItemDate = 10000;  // @miliseconds
var iUpdateItemClock = 4000; // @miliseconds
var aBidTimmerCheck = [30];  // @seconds (Extra bid check)
var iRedTimmerTime = 120000; // @miliseconds

// Dynamic config container
var oTimerConfig = {};

/**
 * Init / on load
 */
$(document).ready( function() {
	$(".itemTimer").each( function() {
		var iItemId = $(this).data("item-id");
		var sDisplayType = $(this).data("display-type");
		// Create timers
		addItemTime( iItemId, sDisplayType, null );
	} );

	// Start continuous timer updater
	updateItemBid( null );
	checkItemDateAjax();
	updateItemClockAjax();
} );

$(document).ajaxComplete( function( event, xhr, settings ) {
	var sAjaxUrl = settings.url;
	if( sAjaxUrl.indexOf("itemList") >= 0 && sAjaxUrl.indexOf("itemId") <= 0 ) {
		reloadTimers();
		return;
	}
	if( sAjaxUrl.indexOf("Item") >= 0 && sAjaxUrl.indexOf("List") >= 0 && sAjaxUrl.indexOf("itemId") <= 0 ) {
		reloadTimers();
		return;
	}
	if( sAjaxUrl.indexOf("itemList") >= 0 && sAjaxUrl.indexOf("itemId") >= 0 ) {
		reloadTimers();
		return;
	}
	if( sAjaxUrl.indexOf("itemShowAjax") > 0 ) {
		var iItemId = getAjaxURLParameter( 'itemId', sAjaxUrl );
		if( $("span.itemTimer[data-item-id=" + iItemId + "][data-display-type='show']").length > 0 ) {
			//var sTimerKey = iItemId + "-show";
			addItemTime( iItemId, "show", null );
		}
	}
} );

function bidTimmerCheck( iTime ) {
	if( aBidTimmerCheck == null ) return false;

	var iSeconds = (iTime / 1000);

	var iCheck = aBidTimmerCheck.indexOf( Math.floor(iSeconds) );
	if( iCheck >= 0 ) return true;
	else return false;
}

function checkItemDateAjax() {
	var refreshTimer = 0;
	function runRefresh() {
		var sItemIds = '';
		$(".itemTimer").each( function() {
			sItemIds += $(this).data("item-id") + ",";
		} );
		sItemIds = sItemIds.substring(0, sItemIds.length - 1);

		if( sItemIds.length != '' ) {
			$.ajax( {
				url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=classic/item_timeAjaxUpdate.php&type=date&itemId=" + sItemIds,
				type: "GET",
				data: "noCss=true",
				async: true,
				dataType: "html",
				beforeSend: function() {}
			} ).fail( function() {
				// Failed

			} ).done( function( data, textStatus, jqXHR ) {
				var object = $.parseJSON(data);
				var aObjects = $.map( object, function(sContent, iItemId) {
					$(".itemTimeWrapper[data-item-id=" + iItemId + "]").each( function() {
						if( $(this).hasClass("date") && $(sContent).hasClass("clock") ) {
							if( $(this).children("span").data("display-type") == 'show' ) {
								sContent = sContent.replace("list", "show");
								var sTimerKey = iItemId + "-show";
							} else {
								var sTimerKey = iItemId + "-list";
							}

							$(this).html( $(sContent).html() );

							$(this).removeClass("date");
							$(this).addClass("clock");

							addItemTime( null, null, sTimerKey );
						}
					} );
				} );
			} );
		}
		refreshTimer = setTimeout( runRefresh, iCheckItemDate );
	}
	runRefresh();
}
function updateItemClockAjax() {
	var refreshTimer = 0;
	function runRefresh() {
		var sItemIds = '';
		$(".itemTimer").each( function() {
			sItemIds += $(this).data("item-id") + ",";
		} );
		sItemIds = sItemIds.substring(0, sItemIds.length - 1);

		if( sItemIds.length != '' ) {
			$.ajax( {
				url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=classic/item_timeAjaxUpdate.php&type=clock&itemId=" + sItemIds,
				type: "GET",
				data: "noCss=true",
				async: true,
				dataType: "html",
				beforeSend: function() {}
			} ).fail( function() {
				// Failed

			} ).done( function( data, textStatus, jqXHR ) {
				var object = $.parseJSON(data);
				var aItemsTimeLeft = $.map( object, function(iItemTimeLeft, iItemId) {
					$.each(oTimerConfig, function( index, value ) {
						if( oTimerConfig[index]["itemId"] == iItemId ) {
							oTimerConfig[index]["update"] = true;
							oTimerConfig[index]["timeleft"] = iItemTimeLeft;
						}
					} );

					//if( iItemTimeLeft < 120000 ) {
					//	// Less then two minutes left
					//	$("span.itemTimer[data-item-id=" + iItemId + "]").addClass( "red" );
					//}
				} );
			} );
			refreshTimer = setTimeout( runRefresh, iUpdateItemClock );
		}
	}
	runRefresh();
}

function reloadTimers() {
	//console.log( "Reload" );

	// Stop all timers
	$.each(oTimerConfig, function( index, value ) {
		oTimerConfig[index]['stop'] = true;
	} );

	//console.log( Object.keys(oTimerConfig).length );

	setTimeout(function() {
		// Re-add timers
		$( function() {
			$(".itemTimer").each( function() {
				var iItemId = $(this).data("item-id");
				var sDisplayType = $(this).data("display-type");
				addItemTime( iItemId, sDisplayType, null );
			} );
		} );
	}, 1000);
}

function addItemTime( iItemId, sDisplayType, sTimerKey ) {
	if( sTimerKey != null ) {
		aTimerKey = sTimerKey.split( '-' );
		iItemId = aTimerKey[0];
		sDisplayType = aTimerKey[1];
	} else {
		sTimerKey = iItemId + "-" + sDisplayType;
	}

	/**
	 * Add new clock controls
	 */
	if( typeof oTimerConfig[sTimerKey] != "undefined" && typeof oTimerConfig[sTimerKey]['timerKey'] != "undefined" ) {
		//( "Clock exists:" );
		//console.log( oTimerConfig[sTimerKey] );
		return;
	}

	/**
	 * Add new clock controls
	 */
	oTimerConfig[sTimerKey] = {
		timerKey: sTimerKey,
		itemId: iItemId,
		stop: false,
		paus: false,
		update: false,
		timeleft: 0,
		ended: false
	};

	createItemTime( sTimerKey );
}

function createItemTime( sTimerKey ) {
	//( "# Create: " + sTimerKey );

	// Identify timer element
	aTimerKey = sTimerKey.split( '-' );
	var eTimer = $("span.itemTimer[data-item-id=" + aTimerKey[0] + "][data-display-type=" + aTimerKey[1] + "]");

	if( eTimer.length <= 0 || $(eTimer).parent(".itemTimeWrapper").hasClass("date") == true ) {
		// Discontinue work
		return;
	}

	/**
	 * Timer object
	 */
	sTimerKey = new ( function() {
		var $countdown;
		var incrementTime = 999;

		var currentTime = $(eTimer).data("time-left");
		var iItemId = $(eTimer).data("item-id");
		var sDisplayType = $(eTimer).data("display-type");
		var sTimerId = iItemId + "-" + sDisplayType;

		$( function() {
			// Setup the timer
			$countdown = eTimer;
			sTimerKey.Timer = $.timer(updateTimer, incrementTime, true);
		} );

		function updateTimer() {
			// Stop if removed but not stopped
			if( typeof oTimerConfig[sTimerId] == "undefined" ) {
				if( typeof sTimerKey.Timer != "undefined" ) {
					this.stop();
				}
			}

			// Removed timer element?
			if( $("span.itemTimer[data-item-id=" + iItemId + "][data-display-type=" + sDisplayType + "]").length <= 0 ) {
				oTimerConfig[sTimerId]["stop"] = true;
				//( "# Removed: " + sTimerId );
			}

			/**
			 * Sync timer with server time
			 */
			if( oTimerConfig[sTimerId]["stop"] == true ) {
				// Stop this timer
				this.stop();

				// Mark timer config as 'stopped'
				oTimerConfig[sTimerId] = "stopped";

				// Log & Return
				//( "# Stopped: " + sTimerId );
				return;

			} else if( oTimerConfig[sTimerId]["update"] == true ) {
				currentTime = oTimerConfig[sTimerId]["timeleft"];
				oTimerConfig[sTimerId]["timeleft"] = 0;
				oTimerConfig[sTimerId]["update"] = false;
			}

			// If timer is completed
			if( currentTime == 0 ) {
				checkItemEndAjax( sTimerId, oTimerConfig[sTimerId]['itemId'] );

				if( oTimerConfig[sTimerId]["ended"] == true ) {
					//( "end check true" );

					// Output timer position
					var timeString = formatTime(currentTime);
					$countdown.html(timeString);

					// Stop this timer
					this.stop();

					// Handle finished item
					stopTimer( iItemId );

					// Mark timer config as 'stopped'
					oTimerConfig[sTimerId] = "stopped";

					// Log & Return
					//( "# Stopped: " + sTimerId );
					return;
				} else {
					//console.log( "end check false" );
				}
			}

			// Red timer upon less then 2 minutes left
			if( currentTime < iRedTimmerTime ) {
				// Less then two minutes left
				if( $("span.itemTimer[data-display-type='list'][data-item-id=" + iItemId + "]").hasClass( "red" ) == false ) {
					$("span.itemTimer[data-display-type='list'][data-item-id=" + iItemId + "]").addClass( "red" );
				}
				if( $("span.itemTimer[data-display-type='show'][data-item-id=" + iItemId + "]").hasClass( "red" ) == false ) {
					$("span.itemTimer[data-display-type='show'][data-item-id=" + iItemId + "]").addClass( "red" );
				}
			}

			if( bidTimmerCheck(currentTime) ) {
				checkItemBidAjax( sTimerId, oTimerConfig[sTimerId]['itemId'] );
			}

			if( currentTime > 0 ) {
				// Output timer position
				var timeString = formatTime(currentTime);
				$countdown.html(timeString);
			}

			// Increment timer position
			currentTime -= incrementTime;
			if (currentTime < 0) currentTime = 0;
		}

		function stopTimer( itemId ) {
			var sDataKey = "data-item-id=" + itemId;

			//console.log( "# Ta bort: " + itemId );

			if( $('.view.bidFormAdd[data-item-id="' + itemId + '"]').length > 0 ) {
				$('.view.bidFormAdd[data-item-id="' + itemId + '"]').remove();

				if( $("li[" + sDataKey + "]").length > 0 ) {
					$("li[" + sDataKey + "]").addClass( "ended" );
				}

				if( $("tr[" + sDataKey + "]").length > 0 ) {
					$("tr[" + sDataKey + "]").addClass( "ended" );
				}

			} else {
				if( $("li[" + sDataKey + "]").length > 0 ) {
					$("li[" + sDataKey + "]").remove();
					$("li.selected[" + sDataKey + "]").remove();
				}

				if( $("tr[" + sDataKey + "]").length > 0 ) {
					$("tr[" + sDataKey + "]").remove();
					$("tr.selected[" + sDataKey + "]").remove();
				}
			}
		}
	} );
}

/**
 * Control function for ended items
 */
function checkItemEndAjax( sTimerId, iItemId ) {
	//console.log( sWebProtocol + "://" + sWebDomain + "?ajax=true&view=classic/item_timeAjaxUpdate.php&type=clock&itemId=" + iItemId );
	
	// Clock
	$.ajax( {
		url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=classic/item_timeAjaxUpdate.php&type=clock&itemId=" + iItemId,
		type: "GET",
		data: "noCss=true",
		async: true,
		dataType: "html",
		beforeSend: function() {}
	} ).fail( function() {
		// Failed

	} ).done( function( data, textStatus, jqXHR ) {
		var object = $.parseJSON(data);
//console.log( object );
		var aItemsTimeLeft = $.map( object, function(iItemTimeLeft, iItemId) {
			if( iItemTimeLeft > 0 ) {
				// Item not ended
				oTimerConfig[sTimerId]["update"] = true;
				oTimerConfig[sTimerId]["timeleft"] = iItemTimeLeft;

				/**
				 * Update bid list if occurring
				 */
				if( $(".view.bidList[data-item-id=" + iItemId + "]").length > 0 ) {
					$.ajax( {
						url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=auction_bidListAjax.php&itemId=" + iItemId,
						type: "GET",
						data: "noCss=true",
						async: true,
						dataType: "html"
					} ).fail( function() {
						// Failed

					} ).done( function( data, textStatus, jqXHR ) {
						// Replace html
						$(".view.bidList[data-item-id=" + iItemId + "] .list").html(data);
					} );
				}

				/**
				 * Update bid list if occurring
				 */
				if( $(".view.bidListAll[data-item-id=" + iItemId + "]").length > 0 ) {
					$.ajax( {
						url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=auction_bidListAllAjax.php&itemId=" + iItemId,
						type: "GET",
						data: "noCss=true",
						async: true,
						dataType: "html"
					} ).fail( function() {
						// Failed

					} ).done( function( data, textStatus, jqXHR ) {
						// Replace html
						$(".view.bidListAll[data-item-id=" + iItemId + "] .list").html(data);
					} );
				}

				/**
				 * Update displayed bids in list & tables
				 */
				$.ajax( {
					url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=classic/bid_dataAjax.php&itemId=" + iItemId,
					type: "GET",
					data: "noCss=true",
					async: true,
					dataType: "html"
				} ).fail( function() {
					// Failed

				} ).done( function( data, textStatus, jqXHR ) {					
					var object = $.parseJSON(data);
					if( typeof object != "undefined" ) {
						if( object.itemBidCount != '0' ) {
							$("span#itemBid" + iItemId).html( " " + object.bidValue + " (" + object.itemBidCount + ")" );
						} else {
							$("span#itemBid" + iItemId).html( " " + object.itemMinBid + " (" + object.itemBidCount + ")" );
						}
					}
				} );

			} else {
				oTimerConfig[sTimerId]["ended"] = true;

			}
		} );

		return;
	} );
}

/**
 * Control function for bids
 */
function checkItemBidAjax( sTimerId, iItemId ) {
	/**
	 * Update bid list if occurring
	 */
	if( $(".view.bidList[data-item-id=" + iItemId + "]").length > 0 ) {
		$.ajax( {
			url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=auction_bidListAjax.php&itemId=" + iItemId,
			type: "GET",
			data: "noCss=true",
			async: true,
			dataType: "html"
		} ).fail( function() {
			// Failed

		} ).done( function( data, textStatus, jqXHR ) {
			// Replace html
			$(".view.bidList[data-item-id=" + iItemId + "] .list").html(data);
		} );
	}

	/**
	 * Update bid list if occurring
	 */
	if( $(".view.bidListAll[data-item-id=" + iItemId + "]").length > 0 ) {
		$.ajax( {
			url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=auction_bidListAllAjax.php&itemId=" + iItemId,
			type: "GET",
			data: "noCss=true",
			async: true,
			dataType: "html"
		} ).fail( function() {
			// Failed

		} ).done( function( data, textStatus, jqXHR ) {
			// Replace html
			$(".view.bidListAll[data-item-id=" + iItemId + "] .list").html(data);
		} );
	}

	/**
	 * Update displayed bids in list & tables
	 */
	$.ajax( {
		url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=classic/bid_dataAjax.php&itemId=" + iItemId,
		type: "GET",
		data: "noCss=true",
		async: true,
		dataType: "html"
	} ).fail( function() {
		// Failed

	} ).done( function( data, textStatus, jqXHR ) {
		var object = $.parseJSON(data);
		if( typeof object != "undefined" ) {
			if( object.itemBidCount != '0' ) {
				$("span#itemBid" + iItemId).html( " " + object.bidValue + " (" + object.itemBidCount + ")" );
			} else {
				$("span#itemBid" + iItemId).html( " " + object.itemMinBid + " (" + object.itemBidCount + ")" );
			}
		}
	} );
}

/**
 *
 * Update item bid data
 * 
 */
function updateItemBid( iItemId ) {
	//console.log( 'test2' );
	
	/**
	 * Item handling
	 */
	if( iItemId == null ) {
        var sItemIds = '';
		$(".itemCurrentBid").each( function() {
			sItemIds += $(this).data("item-id") + ",";
		} );
		sItemIds = sItemIds.substring(0, sItemIds.length - 1);
    } else {
		sItemIds = iItemId;
	}
	
	//console.log( iItemId );
	
	if( iItemId != null ) {
		/**
		 * Update bid list if occurring
		 */
		if( $(".view.bidList[data-item-id=" + iItemId + "]").length > 0 ) {
			$.ajax( {
				url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=auctionAjax/bidListAjax.php&itemId=" + iItemId,
				type: "GET",
				data: "noCss=true",
				async: true,
				dataType: "html"
			} ).fail( function() {
				// Failed		
			} ).done( function( data, textStatus, jqXHR ) {
				$(".itemCurrentBid").each( function() {
					sItemIds += $(this).data("item-id") + ",";
				} );
				// Replace html
				$(".view.bidList[data-item-id=" + iItemId + "] .list").html(data);
			} );
		}
		
		/**
		 * Update bid list if occurring
		 */
		if( $(".view.bidListAll[data-item-id=" + iItemId + "]").length > 0 ) {
			$.ajax( {
				url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=auctionAjax/bidListAllAjax.php&itemId=" + iItemId,
				type: "GET",
				data: "noCss=true",
				async: true,
				dataType: "html"
			} ).fail( function() {
				// Failed		
			} ).done( function( data, textStatus, jqXHR ) {
				// Replace html
				$(".view.bidListAll[data-item-id=" + iItemId + "] .list").html(data);
			} );
		}
	}
	
	/**
	 * Update displayed bids in list & tables
	 */
	$.ajax( {
		url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=classic/bid_dataAjax.php&itemId=" + sItemIds, // ex. 236118
		type: "GET",
		data: "noCss=true",
		async: true,
		dataType: "html"
	} ).fail( function() {
		// Failed
	} ).done( function( data, textStatus, jqXHR ) {
		//console.log( 'test3' );
		
		if( $.trim(data) ) {  
			var oEntries = JSON.parse( $.trim(data) );
			
			if( typeof oEntries.itemId !== "undefined" ) {
				oEntries = [ oEntries ];
			}
			
			$.each( oEntries, function( key, oEntry ) {
				if( oEntry.itemBidCount != '0' ) {			
					$( "span.itemCurrentBid" + oEntry.itemId ).each(function() {
						$(this).html( " " + oEntry.bidValue + " (" + oEntry.itemBidCount + ') <span class="bidder">' + oEntry.bidBidder + "</span>" );
					} );					
					
					if( oEntry.bidPlaced != null ) {
						// Bid palced
						var iTimespamp = parseInt( oEntry.bidPlaced.substring( 0, oEntry.bidPlaced.indexOf('.') ) );
						var oDate = new Date( iTimespamp*1000 );
						
						// Format date
						var sDay = oDate.getDate();
						var sYear = oDate.getFullYear();
						var aMonths = [ "jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec" ];
						var sMonth = aMonths[ oDate.getMonth() ];
						
						$( "span.itemCurrentBidTime" + oEntry.itemId ).each(function() {
							$(this).html( sDay + " " + sMonth + " " + sYear );
						} );
					} else {
						//$( "span.itemCurrentBidTime" + oEntry.itemId ).each(function() {
						//	$(this).html( sDay + " " + sMonth + " " + sYear );
						//} );
					}
					
				} else {
					$( "span.itemCurrentBid" + oEntry.itemId ).each(function() {
						$(this).html( " " + oEntry.itemMinBid + " (" + oEntry.itemBidCount + ")" );
						//console.log( oEntry.bidBidder );
					} );
					$( "span.itemCurrentBidTime" + oEntry.itemId ).each(function() {
						$(this).html( oEntry.bidCreated );
					} );
				}
			} );
		}
	} );
}

// Misc functions
function formatTime(time) {
	time = time / 10;
	var min = parseInt(time / 6000),
		sec = parseInt(time / 100) - (min * 60),
		hundredths = pad(time - (sec * 100) - (min * 6000), 2);
	return (min > 0 ? pad(min, 2) : "00") + ":" + pad(sec, 2);
}
function pad(number, length) {
	var str = "" + number;
	while (str.length < length) {
		str = "0" + str;
	}
	return str;
}
