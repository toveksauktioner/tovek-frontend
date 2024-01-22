/**
 *
 * This file handle auction stuff related to clock/time
 *
 */

// Settings
var iCheckItemDate = 10000;  // @miliseconds
var iUpdateItemClock = 4000; // @miliseconds
var aBidTimmerCheck = [300,240,180,118,105,90,60,30];  // @seconds (Extra bid check)
var iRedTimmerTime = 120000; // @miliseconds
var serverTimeDiff = 0;

// Dynamic config container
var oTimerConfig = {};

/**
 * Init / on load
 */
$(document).ready( function() {
	getServerTimeDiff();
	
	$(".itemTimer").each( function() {
		var iItemId = $(this).data("item-id");
		var sDisplayType = $(this).data("display-type");
		// Create timers
		addItemTime( iItemId, sDisplayType, null );
	} );

	// Start continuous timer updater
	updateItemBid( null );
	checkItemDate();
	updateItemClock();
} );

function getServerTimeDiff() {
	// Get server time and calculate diff between computers clock
	$.ajax( {
		url: "/getServerTime.php",
	} )
	.done(function( data ) {
		var serverTime = parseInt( data );
		if( Number.isInteger(serverTime) ) {
			serverTimeDiff = Date.now() - ( serverTime * 1000 );
			// console.log(serverTimeDiff); 
			// countDownClocks();
		}
	} );
}

/**
 * Actions upon ajax call
 */
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
	if( sAjaxUrl.indexOf("itemShow") > 0 ) {
		var iItemId = getAjaxURLParameter( 'itemId', sAjaxUrl );
		if( $("span.itemTimer[data-item-id=" + iItemId + "][data-display-type='show']").length > 0 ) {
			addItemTime( iItemId, "show", null );
		}
	}
} );

/**
 * Bid timmer check
 */
function bidTimmerCheck( iTime ) {
	if( aBidTimmerCheck == null ) return false;

	var iSeconds = (iTime / 1000);
	var iCheck = aBidTimmerCheck.indexOf( Math.floor(iSeconds) );

	if( iCheck >= 0 ) return true;
	else return false;
}

/**
 * Check item date
 */
function checkItemDate() {
	var refreshTimer = 0;

	function runRefresh() {
		var sItemIds = '';
		$(".itemTimer").each( function() {
			sItemIds += $(this).data("item-id") + ",";
		} );
		sItemIds = sItemIds.substring(0, sItemIds.length - 1);

		if( sItemIds.length != '' ) {
			// Get timestamp for every second in interval
			var currentTimestamp = Date.now();
			if( currentTimestamp % iCheckItemDate != 0 ) currentTimestamp -= (currentTimestamp % iCheckItemDate);

			$.ajax( {
				url: "/?ajax=true&view=auctionAjax/itemTimeAjaxUpdate.php&type=date&itemId=" + sItemIds + "&time=" + currentTimestamp,
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

/**
 * Update item clock
 */
function updateItemClock() {
	var refreshTimer = 0;

	function runRefresh() {
		var sItemIds = '';
		$(".itemTimer").each( function() {
			sItemIds += $(this).data("item-id") + ",";
		} );
		sItemIds = sItemIds.substring(0, sItemIds.length - 1);

		// Get timestamp for every second in interval
		var currentTimestamp = Date.now();
		if( currentTimestamp % iCheckItemDate != 0 ) currentTimestamp -= (currentTimestamp % iCheckItemDate);

		if( sItemIds.length != '' ) {
			$.ajax( {
				url: "/?ajax=true&view=auctionAjax/itemTimeAjaxUpdate.php&type=clock&itemId=" + sItemIds + "&time=" + currentTimestamp,
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
				} );
			} );
			refreshTimer = setTimeout( runRefresh, iUpdateItemClock );
		}
	}
	runRefresh();
}

/**
 *
 * Trigger reload of clock(s)
 * - this function calls on addItemTime()
 *
 */
function reloadTimers() {
	// Stop all timers
	$.each(oTimerConfig, function( index, value ) {
		oTimerConfig[index]['stop'] = true;
	} );

	setTimeout( function() {
		// Re-add timers
		$( function() {
			$(".itemTimer").each( function() {
				var iItemId = $(this).data("item-id");
				var sDisplayType = $(this).data("display-type");
				addItemTime( iItemId, sDisplayType, null );
			} );
		} );
	}, 1000 );
}

/**
 *
 * Clock adding function
 * - this function calls on createItemTime()
 *
 */
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

/**
 *
 * Clock function
 *
 */
function createItemTime( sTimerKey ) {
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
			 * Stop timer or Sync it with server time
			 */
			if( oTimerConfig[sTimerId]["stop"] == true ) {
				// Stop this timer
				this.stop();

				// Mark timer config as 'stopped'
				oTimerConfig[sTimerId] = "stopped";

				return;

			} else if( oTimerConfig[sTimerId]["update"] == true ) {
				currentTime = oTimerConfig[sTimerId]["timeleft"];
				oTimerConfig[sTimerId]["timeleft"] = 0;
				oTimerConfig[sTimerId]["update"] = false;
			}

			/**
			 * If timer is completed
			 */
			if( currentTime == 0 ) {
				itemEndChecking( sTimerId, oTimerConfig[sTimerId]['itemId'] );

				if( oTimerConfig[sTimerId]["ended"] == true ) {
					// Output timer position
					// var timeString = formatTime(currentTime);
					// $countdown.html(timeString);
					$countdown.html("Avslutat");
					$("span.itemTimer[data-display-type='list'][data-item-id=" + iItemId + "]").removeClass( "red" );
					$("span.itemTimer[data-display-type='show'][data-item-id=" + iItemId + "]").removeClass( "red" );

					// Stop this timer
					this.stop();

					// Handle finished item
					stopTimer( iItemId );

					// Mark timer config as 'stopped'
					oTimerConfig[sTimerId] = "stopped";

					return;
				} else {
					//console.log( "end check false" );
				}
			}

			/**
			 * Red timer upon less then 2 minutes left
			 */
			if( (currentTime > 0) && (currentTime < iRedTimmerTime) ) {
				// Less then two minutes left
				$("span.itemTimer[data-display-type='list'][data-item-id=" + iItemId + "]").addClass( "red" );
				$("span.itemTimer[data-display-type='show'][data-item-id=" + iItemId + "]").addClass( "red" );

			}

			if( bidTimmerCheck(currentTime) ) {
				// Timmer needs to be updated
				updateItemBid( oTimerConfig[sTimerId]['itemId'] );
			}

			if( currentTime > 0 ) {
				// Output timer position
				var timeString = formatTime( currentTime );
				$countdown.html( timeString );
			}

			// Increment timer position
			currentTime -= incrementTime;
			if( currentTime < 0 ) currentTime = 0;
		}

		function stopTimer( itemId ) {
			var sDataKey = "data-item-id=" + itemId;
			var sEntryId = "#itemEntry" + itemId;
			var sPopupId = "#infoPopup" + itemId;
			var sPopupContainer = sPopupId + " .container";

			if( $(sEntryId).length > 0 ) {
				if( $(sEntryId).parents(".listWrapper").hasClass('showEnded') ) {
					$( this ).addClass( "ended" );

				} else {
					$(sEntryId).animate( {
						height: 0
					}, function() {
						$( this ).addClass( "ended" );
					} );
					checkVisibleItems();
				}
			}

			if( $(sPopupContainer).html() != "" ) {
				$(sPopupId).children('.container').children('.view.itemShow').children('.bidContainer').children('.view.bidFormAdd').remove();

				// if( $(sEntryId).length > 0 ) {
				// 	$(sEntryId).addClass( "ended" );
				// }

			} else {
				if( $(sEntryId).length > 0 ) {
					// $(sEntryId).fadeOut( 'fast' );
					// $(sEntryId).animate( {
					// 	height: 0
					// }, 'fast', function() {
					// 	$(sEntryId).hide();
					// } );
				}
			}
		}
	} );
}

/**
 *
 * Check function for ended item
 *
 */
function itemEndChecking( sTimerId, iItemId ) {
	// Get timestamp for every second in interval
	var currentTimestamp = Date.now();
	if( currentTimestamp % iCheckItemDate != 0 ) currentTimestamp -= (currentTimestamp % iCheckItemDate);

	$.ajax( {
		url: "/?ajax=true&view=auctionAjax/itemTimeAjaxUpdate.php&type=clock&itemId=" + iItemId + "&time=" + currentTimestamp,
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
			if( iItemTimeLeft > 0 ) {
				// Item not ended
				oTimerConfig[sTimerId]["update"] = true;
				oTimerConfig[sTimerId]["timeleft"] = iItemTimeLeft;

				/**
				 * Update bid list if occurring
				 */
				if( $(".view.bidList[data-item-id=" + iItemId + "]").length > 0 ) {
					$.ajax( {
						url: "/?ajax=true&view=auctionAjax/bidList.php&itemId=" + iItemId + "&time=" + currentTimestamp,
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
						url: "/?ajax=true&view=auctionAjax/bidListAllAjax.php&itemId=" + iItemId + "&time=" + currentTimestamp,
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
					url: "/?ajax=true&view=auctionAjax/bidDataAjax.php&itemId=" + iItemId + "&time=" + currentTimestamp, // ex. 236118
					type: "GET",
					data: "noCss=true",
					async: true,
					dataType: "html"
				} ).fail( function() {
					// Failed
				} ).done( function( data, textStatus, jqXHR ) {
					var oEntries = JSON.parse(data);

					if( typeof oEntries.itemId !== "undefined" ) {
						oEntries = [ oEntries ];
					}

					$.each( oEntries, function( key, oEntry ) {
						if( oEntry.itemBidCount != '0' ) {
							$( "span.itemCurrentBid" + oEntry.itemId ).each( function() {
								$(this).html( " " + oEntry.bidValue + " (" + oEntry.itemBidCount + ")" );
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

								$( "span.itemCurrentBidTime" + oEntry.itemId ).each( function() {
									$(this).html( sDay + " " + sMonth + " " + sYear );
								} );
							} else {
								//$( "span.itemCurrentBidTime" + oEntry.itemId ).each(function() {
								//	$(this).html( sDay + " " + sMonth + " " + sYear );
								//} );
							}

						} else {
							$( "span.itemCurrentBid" + oEntry.itemId ).each( function() {
								$(this).html( " " + oEntry.itemMinBid + " (" + oEntry.itemBidCount + ")" );
							} );
							$( "span.itemCurrentBidTime" + oEntry.itemId ).each( function() {
								$(this).html( oEntry.bidCreated );
							} );
						}
					} );
				} );
			} else {
				oTimerConfig[sTimerId]["ended"] = true;
			}
		} );
		return;
	} );
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

	if( iItemId == null ) {
		var aItems = [];

		$(".items .itemEntry, .itemShow .view.bidFormAdd").each( function() {
			aItems.push( $(this).data("item-id") );
		} );

	} else {
		aItems = [ iItemId ];
	}

	// Sort ids using function to get natural sort - i.e. 1,2,10 not 1,10,2
	aItems.sort( function(a, b){return a - b} );

	// Get unique ids
	// Function from https://stackoverflow.com/questions/1960473/get-all-unique-values-in-a-javascript-array-remove-duplicates
	aItems = aItems.filter( (v, i, a) => a.indexOf(v) === i );
	var sItemIds = aItems.join( "," );

	// Get timestamp for every second in interval
	var currentTimestamp = Date.now();
	if( currentTimestamp % iCheckItemDate != 0 ) currentTimestamp -= (currentTimestamp % iCheckItemDate);

	/**
	 * Update displayed bids in list & tables
	 * In success function updates of opened bid in list
	 */
	$.ajax( {
		url: "/?ajax=true&view=auctionAjax/bidDataAjax.php&itemId=" + sItemIds + "&time=" + currentTimestamp, // ex. 236118
		type: "GET",
		data: "noCss=true",
		async: true,
		dataType: "html"
	} ).fail( function() {
		// Failed
	} ).done( function( data, textStatus, jqXHR ) {
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
					} );
					$( "span.itemCurrentBidTime" + oEntry.itemId ).each(function() {
						$(this).html( oEntry.bidCreated );
					} );
				}

				/**
				 * Update bid form if occurring
				 */
				if( $(".view.bidFormAdd[data-item-id=" + oEntry.itemId + "]").length > 0 ) {
					$.ajax( {
						url: "/?ajax=true&view=auction/bidFormAdd.php&onlyCurrentBidInfo=1&itemId=" + oEntry.itemId + "&time=" + currentTimestamp,
						type: "GET",
						data: "noCss=true",
						async: true,
						dataType: "html"
					} ).fail( function() {
						// Failed
					} ).done( function( data, textStatus, jqXHR ) {
						// Replace html
						// $( ".view.bidFormAdd[data-item-id=" + oEntry.itemId + "]" ).replaceWith(data);
						returnData = JSON.parse( data );
						$( ".view.bidFormAdd[data-item-id=" + oEntry.itemId + "] .currentBid" ).html( returnData.currentBid );
						$( ".view.bidFormAdd[data-item-id=" + oEntry.itemId + "] .currentBidUser" ).html( returnData.currentBidUser );
						if( returnData.currentBidUserId == userId ) {
							$( ".view.bidFormAdd[data-item-id=" + oEntry.itemId + "]" ).addClass( "isWinner isBidder" );
						} else {
							$( ".view.bidFormAdd[data-item-id=" + oEntry.itemId + "]" ).removeClass( "isWinner" );
						}
					} );
				}

				/**
				 * Update bid list if occurring
				 */
				if( $(".view.bidList[data-item-id=" + oEntry.itemId + "]").length > 0 ) {
					$.ajax( {
						url: "/?ajax=true&view=auctionAjax/bidList.php&itemId=" + oEntry.itemId + "&time=" + currentTimestamp,
						type: "GET",
						data: "noCss=true",
						async: true,
						dataType: "html"
					} ).fail( function() {
						// Failed
					} ).done( function( data, textStatus, jqXHR ) {
						// Replace html
						$(".view.bidList[data-item-id=" + oEntry.itemId + "] ul").replaceWith(data);
					} );
				}

				/**
				 * Update bid list all if occurring
				 */
				if( $(".view.bidListAll[data-item-id=" + oEntry.itemId + "]").length > 0 ) {
					$.ajax( {
						url: "/?ajax=true&view=auctionAjax/bidListAllAjax.php&itemId=" + oEntry.itemId + "&time=" + currentTimestamp,
						type: "GET",
						data: "noCss=true",
						async: true,
						dataType: "html"
					} ).fail( function() {
						// Failed
					} ).done( function( data, textStatus, jqXHR ) {
						// Replace html
						$(".view.bidListAll[data-item-id=" + oEntry.itemId + "] ul").replaceWith(data);
					} );
				}

			} );
		}
	} );
}

/**
 * Misc functions
 */
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
