var iTimeSequence = 5000; // Seconds
var bPageLoad = false;

console.log( "T6" );

function updateAllAuctionItemLists() {
	var timer = 0;

	// Temporary development stuff
	$(document).ready( function() {
		console.log( "AjaxPull service started" );
	} );

	function run() {
		if( bPageLoad == true ) {
			/**
			 * Find all relevant IDs
			 */
			var sItemIds = '';
			$(".itemTimeWrapper").each( function() {
				sItemIds += $(this).data("item-id") + ",";
			} );
			sItemIds = sItemIds.substring(0, sItemIds.length - 1);
			
			// Get timestamp for every second in interval
			var currentTimestamp = Date.now();
			if( currentTimestamp % iCheckItemDate != 0 ) currentTimestamp -= (currentTimestamp % iCheckItemDate);

			/**
			 * Update bid list if occurring
			 */
			if( $(".view.bidList").length > 0 ) {
				var iItemId = $(".view.bidList").data("item-id");
				var sTimerKey = iItemId + "-show";

				$.ajax( {
					url: "/?view=auction/bidListAjax.php&itemId=" + iItemId+ "&time=" + currentTimestamp,
					type: "GET",
					data: "noCss=true",
					async: true,
					dataType: "html"
				} ).fail( function() {
					// Failed
					console.log( "failed!" );

				} ).done( function( data, textStatus, jqXHR ) {
					// Replace html
					$(".view.bidList[data-item-id=" + iItemId + "] .list").html(data);
				} );
			}

			/**
			 * Update bid list if occurring
			 */
			if( $(".view.bidListAll").length > 0 ) {
				var iItemId = $(".view.bidList").data("item-id");
				var sTimerKey = iItemId + "-show";

				$.ajax( {
					url: "/?view=auction/bidListAllAjax.php&itemId=" + iItemId+ "&time=" + currentTimestamp,
					type: "GET",
					data: "noCss=true",
					async: true,
					dataType: "html"
				} ).fail( function() {
					// Failed
					console.log( "failed!" );

				} ).done( function( data, textStatus, jqXHR ) {
					// Replace html
					$(".view.bidListAll[data-item-id=" + iItemId + "] .list").html(data);
				} );
			}

			/**
			 * Update displayed bids in list & tables
			 */
			$.ajax( {
				url: "/?view=auction/bidDataAjax.php&itemId=" + sItemIds+ "&time=" + currentTimestamp,
				type: "GET",
				data: "noCss=true",
				async: true,
				dataType: "html",
				beforeSend: function() {}
			} ).fail( function() {
				// Failed
				console.log( "failed!" );

			} ).done( function( data, textStatus, jqXHR ) {
				if( data != "" ) {
					var objects = $.parseJSON(data);
					var aObjects = $.map( objects, function(oEntry, index) {
						$("span#itemBid" + oEntry.itemId).html( " " + oEntry.bidValue + " (" + oEntry.itemBidCount + ")" );
					} );
				}
			} );

			/**
			 * Update topbar
			 */
			$.ajax( {
				url: "/?ajax=true&view=static/topbar.php?time=" + currentTimestamp,
				type: "GET",
				data: "noCss=true",
				async: true,
				dataType: "html"
			} ).fail( function() {
				// Failed
				console.log( "failed!" );

			} ).done( function( data, textStatus, jqXHR ) {
				$("#topbar .wrapper").html( data );
			} );
		}

		timer = setTimeout( run, iTimeSequence );

		// Prevent running at page load:
		bPageLoad = true;
	}
	run();
}

updateAllAuctionItemLists();
