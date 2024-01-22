var oWebsocket;
var iReadyState;

function init() {
	try {
		//console.log( sPushProtocol + "://" + sPushDomain + ":" + sPushPort + '/' );
		//console.log( sWebProtocol + "://" + sWebDomain );
		
		// Create a new WebSocket object
		oWebsocket = new WebSocket( sPushProtocol + "://" + sPushDomain + ":" + sPushPort + '/' );
		
		/**
		 * Open new connection
		 */
		oWebsocket.addEventListener( 'open', function( event ) {
			// Connection is open
			if( bDebug === true ) console.log( "Connected to server in " + event.timeStamp + " ms" );
			
			if( bGeoiplookup === true ) {
                // Callback with GEO IP Lookup
				$.getJSON( 'https://json.geoiplookup.io', function(data) {
					oWebsocket.send( JSON.stringify( {
						type: "newConnection",
						message: "A new connection!",
						data: JSON.stringify( data, null, 2 )
					} ) );
				} );
            } else {
				// Normal callback
				oWebsocket.send( JSON.stringify( {
					type: "newConnection",
					message: "A new connection!",
					data: ""
				} ) );
			}
		} );
		
		/**
		 * Message from server
		 */
		oWebsocket.addEventListener( 'message', function( event ) {			
			var rawData = JSON.parse( event.data );			
			var sType = rawData.type;
			var sMessage = rawData.message;
			var sData = rawData.data;
			
			/**
			 * Message type
			 */
			switch( sType ) {
				case 'system':
					if( bDebug === true ) console.log( 'Received system message: ' + sMessage );
					break;
				
                case 'newConnection':
					//if( bDebug === true ) console.log( 'A new connection!' );
					if( bGeoiplookup === true ) console.log( sData );
					// New other connection..
					break;
					
				case 'update':
					/**
					 * New bid
					 */
					if( sMessage == 'New bid accepted' ) {
                        var aData = sData.split(';');
						var iItemId = aData[0];
						
						/**
						 * Update bid list if occurring
						 */
						if( $(".view.bidList[data-item-id=" + iItemId + "]").length > 0 ) {
							var sTimerKey = iItemId + "-show";
							
							$.ajax( {
								url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=auctionAjax/bidList.php&itemId=" + iItemId,
								type: "GET",
								data: "noCss=true",
								async: true,
								dataType: "html"
							} ).fail( function() {
								// Failed
								
							} ).done( function( data, textStatus, jqXHR ) {
								// Replace html
								$(".view.bidList[data-item-id=" + iItemId + "] ul").replaceWith(data);
								
								/**
								 * Bid slider
								 */
								//bidSlider( iItemId );
							} );
						}
						
						/**
						 * Update bid form if occurring
						 */
						if( $(".view.bidFormAdd[data-item-id=" + iItemId + "]").length > 0 ) {
							var sTimerKey = iItemId + "-show";
							
							$.ajax( {
								url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=auction/bidFormAdd.php&itemId=" + iItemId,
								type: "GET",
								data: "noCss=true",
								async: true,
								dataType: "html"
							} ).fail( function() {
								// Failed
								
							} ).done( function( data, textStatus, jqXHR ) {
								// Replace html
								$(".view.bidFormAdd[data-item-id=" + iItemId + "]").replaceWith(data);
								
								/**
								 * Bid slider
								 */
								bidSlider( iItemId );
							} );
						}
						
						/**
						 * Update bid list if occurring
						 */
						if( $(".view.bidListAll[data-item-id=" + iItemId + "]").length > 0 ) {
							var sTimerKey = iItemId + "-show";
							
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
								$(".view.bidListAll[data-item-id=" + iItemId + "] ul").replaceWith(data);
								
								/**
								 * Bid slider
								 */
								//bidSlider( iItemId );
							} );
						}
						
						/**
						 * Update displayed bids in list & tables
						 */
						$.ajax( {
							url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=auctionAjax/bidDataAjax.php&newBid=true&itemId=" + iItemId,
							type: "GET",
							data: "noCss=true",
							async: true,
							dataType: "html"
						} ).fail( function() {
							// Failed
							
						} ).done( function( data, textStatus, jqXHR ) {
							var oEntry = JSON.parse(data);
							
							if( oEntry.itemBidCount != '0' ) {			
								//$( "span.itemCurrentBid" + oEntry.itemId ).each(function() {
								//	$(this).html( " " + oEntry.bidValue + " (" + oEntry.itemBidCount + ') <span class="bidder">' + oEntry.bidBidder + "</span>" );
								//} );
                                if( $("#itemBid" + iItemId).length > 0 ) {
                                    $("#itemBid" + iItemId).html( " " + oEntry.bidValue + " (" + oEntry.itemBidCount + ') <span class="bidder">' + oEntry.bidBidder + "</span>" );
                                }
                                
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
									// $(this).html( oEntry.bidPlaced );
								} );
								
								if( $("#currentBid" + iItemId).length > 0 ) {
									$("#currentBid" + iItemId).html( oEntry.bidValue );
								}
								if( $("#currentBidUser" + iItemId).length > 0 ) {
									$("#currentBidUser" + iItemId).html( oEntry.bidBidder );
								}
								
							} else {
								//$( "span.itemCurrentBid" + oEntry.itemId ).each(function() {
								//	$(this).html( " " + oEntry.itemMinBid + " (" + oEntry.itemBidCount + ")" );
								//} );
                                if( $("#itemBid" + iItemId).length > 0 ) {
                                    $("#itemBid" + iItemId).html( " " + oEntry.itemMinBid + " (" + oEntry.itemBidCount + ")" );
                                }
                                
								$( "span.itemCurrentBidTime" + oEntry.itemId ).each(function() {
									$(this).html( oEntry.bidCreated );
									// $(this).html( oEntry.bidPlaced );
								} );
								
								if( $("#currentBid" + iItemId).length > 0 ) {
									$("#currentBid" + iItemId).html( oEntry.itemMinBid );
								}
								if( $("#currentBidUser" + iItemId).length > 0 ) {
									$("#currentBidUser" + iItemId).html( oEntry.bidBidder );
								}
							}
							
							if( oEntry.bidOverBidder != 0 && $("#notificationRow").length != 0 ) {
                                $("#notificationRow").append( '<div class="notification">' + oEntry.bidOverBidderMsg + ' <span class="close">X</span></div>' );
								
								if( $('#notificationRow .notification').length > 3 ) {
                                    $('#notificationRow .notification:first-child').slideUp( 400, function() {
										$(this).remove();
									} );
                                }
								
								if( !$("#notificationRow").is(':visible') ) {
                                    $("#notificationRow").slideDown( 400, function() {
										var eThis = $(this);
										setTimeout( function() {
											eThis.slideUp( 400, function() {
												eThis.remove();
											} );
										}, 5000 );
									} );
                                }								
                            }
						} );
						
						/**
						 * Update bid list if occurring (MAY NOT BE USED!!)
						 */
						//if( $(".view.bidListAll[data-item-id=" + iItemId + "]").length > 0 ) {
						//	var sTimerKey = iItemId + "-show";
						//	
						//	$.ajax( {
						//		url: sWebProtocol + "://" + sWebDomain + "?view=auction/bidListAllAjax.php&itemId=" + iItemId,
						//		type: "GET",
						//		data: "noCss=true",
						//		async: true,
						//		dataType: "html"
						//	} ).fail( function() {
						//		// Failed
						//		
						//	} ).done( function( data, textStatus, jqXHR ) {
						//		// Replace html
						//		$(".view.bidListAll[data-item-id=" + iItemId + "] .list").html(data);
						//	} );
						//}
						
						/**
						 * Update topbar (KEEP??)
						 */
						//$.ajax( {
						//	url: sWebProtocol + "://" + sWebDomain + "?ajax=true&view=static/topbar.php",
						//	type: "GET",
						//	data: "noCss=true",
						//	async: true,
						//	dataType: "html"
						//} ).fail( function() {
						//	// Failed
						//	
						//} ).done( function( data, textStatus, jqXHR ) {
						//	$("#topbar .wrapper").html( data );
						//} );
                    }
					break;
					
				default:
					if( bDebug === true ) console.log( 'Unkown error: ' + sMessage );
					// Nothing to do..
					break;
            }			
		} );
		
		/**
		 * Error
		 */
		oWebsocket.addEventListener( 'error', function( event ) {
			if( bDebug === true ) console.log( 'error: ', event );
		} );
		
		/**
		 * Connection is close
		 */
		oWebsocket.addEventListener( 'close', function( event ) {
			if( bDebug === true ) {
				console.log( 'Disconnected (' + event.code + ')' );
				console.log( 'Reason: ' + event.reason );
				console.log( 'Was clean: ' + event.wasClean );
			}
		} );
		
	} catch( error ) {
		//if( bDebug === true ) console.error( error );
	}
}

/**
 * Connect to server
 */
$(document).ready( function() {
	init();
} );
