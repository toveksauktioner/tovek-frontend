function snackbar( message, level = "info", autoClose = true, onCloseFn ) {
	$("body").append( '<div id="snackbar" class="' + level + '">' + message + '<span class="close">&times;</span></div>' );
	var snackbarElement = $("#snackbar");
	snackbarElement.addClass("show");
	if( autoClose ) {
		setTimeout( function() {
			snackbarElement.removeClass("show");
			snackbarElement.remove();
		}, 3000 );
	} else {
		var closeBtn = $("#snackbar > .close");
		closeBtn.bind("click", function() {
			if( typeof onCloseFn !== 'undefined' ) {
				onCloseFn();
            }
            setTimeout( function() {
                snackbarElement.removeClass("show");
                snackbarElement.remove();
            }, 3000 );
		} );
	}	
}

function modal( content, reload = false, onCloseFn, width="80%", height="auto" ) {
	$("body").append( '<div id="modalBox" class="modalBox">' +
	'<div class="modal-content">' + 
		'<span class="close">&times;</span>' +
		content + 
	'</div>' );
	var modalElement = $("#modal.modal");
	$(".modal-content").css("width", width);
	$(".modal-content").css("height", height);
	modalElement.addClass("show");
	var closeBtn = $("#modalBox.modalBox > .modal-content > .close");
	closeBtn.bind("click", function() {
		if( typeof onCloseFn !== 'undefined' ) {
			onCloseFn();
		}
		// modalElement.removeClass("show");
		modalElement.addClass("hide");
		setTimeout( function() {
			modalElement.remove();
			modalElement.removeClass("show");
		}, 450 );
		if( reload == true ) {
			location.reload();
		}
	} );
}