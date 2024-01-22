// Function for handling vehicle data 

$( document ).on( "click", "#searchVehicleBtn", function(ev) {
	ev.preventDefault();

	var searchQuery = $("#vehicleLicencePlate").val();

	$.get( "?ajax=1&view=vehicle/vehicleFormAdd.php", {
		licencePlateSearchQuery: searchQuery
	}, function(data) {
		data = JSON.parse( data );

		$("#bilvisionButton").html( data.bilvisionButton );
		$("#vehicleFormContainer").html( data.vehicleFormContainer );
	} );

} );

$( document ).on( "click", "#lookupBilvision", function(ev) {
	ev.preventDefault();

	var searchQuery = $("#vehicleLicencePlate").val();

	$.get( "?ajax=1&view=vehicle/lookupServiceBilvision.php", {
		vehicleLicencePlate: searchQuery
	}, function(data) {
		data = JSON.parse( data );

		if( data.result == "success" ) {
			$.get( "?ajax=1&view=vehicle/vehicleFormAdd.php", {
				vehicleDataId: data.vehicleDataId,
				onlyForm: 1
			}, function(formData) {
				$("#vehicleFormContainer").html( formData.vehicleFormContainer );
				$("#bilvisionButton").html( "" );
			} );
		}
	} );

} );

$( function() {

	$(".addVehicle").click( function(ev) {
		ev.preventDefault();

		if( $(this).hasClass("selected") ) {
			$(".popupTrigger").removeClass( "selected" );
			$(".popup").hide();

		} else {
			var thisObj = $( this );

			$.get( "?ajax=1&view=vehicle/vehicleFormAdd.php", function(data) {
				$("#vehicleFormContent").html( data );
				resizeAndMovePopup( thisObj, "vehicleForm", 800 );

				// Focus on textarea
				$("#vehicleForm form input.text:first-child").focus();
			} );

		}

	} );

	$(".editVehicle").click( function(ev) {
		ev.preventDefault();

		if( $(this).hasClass("selected") ) {
			$(".popupTrigger").removeClass( "selected" );
			$(".popup").hide();

		} else {
			var thisObj = $( this );
			var vehicleDataId = thisObj.data( "vehicle-data-id" );

			$.get( "?ajax=1&view=vehicle/vehicleFormAdd.php", {
				vehicleDataId: vehicleDataId
			}, function(data) {
				$("#vehicleFormContent").html( data );
				resizeAndMovePopup( thisObj, "vehicleForm", 800 );

				// Focus on textarea
				$("#vehicleForm form input.text:first-child").focus();
			} );

		}

	} );

} );
