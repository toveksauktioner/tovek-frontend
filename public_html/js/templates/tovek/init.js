// Dependant on JQuery and functions.js

var userId = 0;

function setUserCookie() {
	userId = getCookie( "userId" );
	const username = getCookie( "username" );

	if( username ) {
		$("#userBtn span.extended").html( "&nbsp;" + decodeURI(username) );
		$("#userBtn").show();
		$("#loginBtn").hide();
		$("#userNavLoggedIn").show();
		$("#userNavLoggedOut").hide();
	}

	// Check after an hour (and one second) if the user is still logged in
	if( userId > 0 ) {
		setInterval( function() {
			$.get( '/session.php', function(data) {
				userId = data;
				if( userId == 0 ) {
					location.reload();
				}
			} );
		}, 3601000 );
	}

	// Load form for logged in user (item page)
	if( (userId > 0) && ($('.itemShow .bidFormAdd').length == 1) ) {
		const d = new Date();
		let time = d.getTime();

		$.get( ajaxGlobalUrl, {
			ajax: 1,
			view: 'auction/bidFormAdd.php',
			itemId: $('.itemShow .bidFormAdd').data('item-id'),
			userId: userId,
			time: time
		}, function(data) {
			// console.log( data );
			$('.itemShow .bidFormAdd').replaceWith( data );
		} );
	}

	// Load Wasa Kredit application box
	if( (userId > 0) && ($('.view.financing.wasakreditUserItemNotice .button.loggedout').length == 1) ) {
		const d = new Date();
		let time = d.getTime();
		let buttonObj = $('.view.financing.wasakreditUserItemNotice .button.loggedout');
		let itemId = buttonObj.data( 'item-id' );
		let itemValue = buttonObj.data( 'item-value' );

		$.get( ajaxGlobalUrl, {
			ajax: 1,
			view: 'financing/wasakreditUserItemNotice.php',
			time: time,
			itemId: itemId,
			value: itemValue,
			getApplicationButton: 1
		}, function(data) {
			$('.view.financing.wasakreditUserItemNotice .button.loggedout').replaceWith( data );
		} );
	}

	// Load test auction 
	if( (userId > 0) && ($('.view.auction.list .auctions .container .innerContainer').length == 1) ) {
		const d = new Date();
		let everyMinuteTime = d.getTime();
		if( everyMinuteTime % 60000 != 0 ) everyMinuteTime -= (everyMinuteTime % 60000);

		// console.log("get test auctions");
		$.get( ajaxGlobalUrl, {
			ajax: 1,
			view: 'auction/listTestAuctions.php',
			userId: userId,
			time: everyMinuteTime
		}, function(data) {
			// console.log( '|' + data + '|' );
			$('.view.auction.list .auctions .container .innerContainer').append( data );
		} );
	}
}

$( function() {
	$.get( '/session.php', function(data) {
		userId = data;
		
		if( userId == 0 ) {
			setCookie( "userId", 0, -1 );
			setCookie( "username", 0, -1 );
		}

		setUserCookie();
	} );
} );