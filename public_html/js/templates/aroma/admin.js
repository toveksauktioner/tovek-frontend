$(document).ready( function() {

	$('select').each( function() {
		$(this).wrap('<div class="select input"></div>');
	} );

	var $modalOverlay = $('<div id="modal-overlay"></div>').appendTo('body');

	$('a[data-modal]').each( function() {

		var selector	= $(this).attr('data-modal');
		var $modal		= $( selector );

		$(this).on( 'click', function( event ) {
			event.preventDefault();
			$modal.show();
			$modalOverlay.show().addClass('active');
		} )

		$modalOverlay.on( 'click', function( event ) {
			event.preventDefault();
			$modal.hide();
			$modalOverlay.hide().removeClass('active');
		} );
	} );

	// Add label focus detection (good for making CSS aware of focus)
	$('input,select,textarea').on( 'focus', function() {
		$(this).closest('.field').addClass( 'focus' );
	} ).on('blur', function() {
		$(this).closest('.field').removeClass( 'focus' );
	} )

	$('a.linkConfirm').on( 'click', function() {
		if ( confirm(this.title) == false) return false;
		return true;
	} );


	$('#mainMenu a[href="#"]').on( 'click', function( event ) {
		event.preventDefault();
		$("#mainMenu ul li.active").not( $(this).parents('li') ).removeClass('active');
		var $parent = $(this).parent().toggleClass('active');
	} );


	// Old
	var flip = 0;
	$("a.toggleShow").each( function() {
		sTarget = this.href.substring(this.href.indexOf("#"));
		if( !$(sTarget).hasClass("show") ) $(sTarget).hide();
	} ).on( "click", function() {
		sTarget = this.href.substring(this.href.indexOf("#"));
		if( $(sTarget)[0].tagName.toLowerCase() == "tr" ) {
			if( flip++ % 2 == 0 ) {
				$(sTarget).show();
			} else {
				$(sTarget).hide();
				flip = 0;
			}
		} else {
			$(sTarget).slideToggle("fast");
		}
		return false;
	} );

	$.timepicker.regional['sv'] = {
		timeOnlyTitle	: 'Välj tid',
		timeText		: 'Tid',
		hourText		: 'Timma',
		minuteText		: 'Minut',
		secondText		: 'Sekund',
		currentText		: 'Nu',
		closeText		: 'Klar',
		timeFormat		: 'h:m',
		ampm			: false
	};
    $.datepicker.regional['sv'] = {
		closeText: 'Stäng',
        prevText: '&laquo;Förra',
		nextText: 'Nästa&raquo;',
		currentText: 'Idag',
        monthNames: ['Januari','Februari','Mars','April','Maj','Juni',
        'Juli','Augusti','September','Oktober','November','December'],
        monthNamesShort: ['Jan','Feb','Mar','Apr','Maj','Jun',
        'Jul','Aug','Sep','Okt','Nov','Dec'],
		dayNamesShort: ['Sön','Mån','Tis','Ons','Tor','Fre','Lör'],
		dayNames: ['Söndag','Måndag','Tisdag','Onsdag','Torsdag','Fredag','Lördag'],
		dayNamesMin: ['Sö','Må','Ti','On','To','Fr','Lö'],
		weekHeader: 'Ve',
        dateFormat: 'yy-mm-dd',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''
	};
    $.datepicker.setDefaults($.datepicker.regional['sv']);
	$.timepicker.setDefaults($.timepicker.regional['sv']);

	$(".datepicker").datepicker({
		dateFormat	: "yy-mm-dd",
		changeMonth	: true,
		changeYear	: true
	});

	$(".datetimepicker").datetimepicker({
		timeFormat	: 'HH:mm:ss',
		dateFormat	: 'yy-mm-dd',
		showOn		: "button",
		buttonImage	: "/images/icons/calendar.png"
	});

	$("#tabs").tabs();

	// Colorbox
	$(".colorbox").colorbox();

	// Tooltip
	function tooltip(targetItems, name) {
		$(targetItems).each( function(i) {
			var check = this.hasAttribute("title");
			if(check) {
				$("body").append("<div class='"+name+"' id='"+name+i+"'><p>"+$(this).attr('title')+"</p></div>");
				var eTooltip = $("#"+name+i);

				$(this).removeAttr("title").mouseover( function(){
					timer = setTimeout( function() {
						eTooltip.css({opacity:0.8}).show();
					}, 500 );
				} ).mousemove( function(kmouse){
					var screenWidth = window.innerWidth;
					var left = kmouse.pageX + 15;
					var top = kmouse.pageY + 15;
					if( left + 17 + eTooltip.outerWidth() >= screenWidth ) {
						// Stop tooltip at window egde
						left = screenWidth - eTooltip.outerWidth() - 17;
					}
					eTooltip.css({left:left, top:top});
				} ).mouseout( function(){
					clearInterval(timer);
					eTooltip.hide();
				} );
			}
		} );
	}
	tooltip("label", "tooltip");
	tooltip("legend", "tooltip");

	// Preview images
	function imagePreview() {
		xOffset = 10;
		yOffset = 30;
		$("a.preview").hover( function(e) {
			this.t = this.title;
			this.title = "";
			var c = (this.t != "") ? "<br/>" + this.t : "";
			$("body").append("<p id='preview'><img src='"+ this.href +"' alt='Image preview' />"+ c +"</p>");
			$("#preview")
				.css("top",(e.pageY - xOffset) + "px")
				.css("left",(e.pageX + yOffset) + "px")
				.fadeIn("fast");
		},
		function() {
			this.title = this.t;
			$("#preview").remove();
		} );
		$("a.preview").mousemove( function(e) {
			$("#preview")
				.css("top",(e.pageY - xOffset) + "px")
				.css("left",(e.pageX + yOffset) + "px");
		} );
	}
	imagePreview();
} );

/**
 * Admin message
 */
$(document).delegate( ".message .link a", "click", function(event) {
	event.preventDefault();
	var jqxhr = $.get( $(this).attr("href"), function() {
		$("#adminMessage").fadeOut( "normal", function() {
			location.reload();
		} );
	} )
	.done( function() {
		console.log( "second success" );
	} )
	.fail( function() {
		console.log( "error" );
	} )
	.always( function() {
		console.log( "finished" );
	} );
} );

/**
 * Form stuff
 */
$(document).delegate( ".selectSuffix .suffixContent select", "change", function(event) {
	var eInput = $(this).parent(".select").parent(".suffixContent").parent(".selectSuffix").children("input");			
	$(eInput).val( $(this).val() );
	$(this).prop( "selectedIndex", 0 );			
} );

/**
 * Edit images with Moxiemanager
 */
$(document).delegate( ".editableImage", "click", function(event) {
	event.preventDefault();

	var sModuleName = $(this).data( "module-name" );
	var iImageId = $(this).data( "image-id" );
	var sImageExtension = $(this).data( "image-extension" );

	moxman.edit( {
		path: "/Systembilder/" + sModuleName + "/" + iImageId + '.' + sImageExtension,
		skin: 'aroma',
		onsave: function( args ) {
			$.ajax( {
				type: "POST",
				data: {
					imageId: iImageId,
					moduleName: sModuleName,
					editedImage: args.file,
					frmEditImage: "true"
				},
				success: function() {
					$( "main img" ).each(function() {
						var iTime = new Date().getTime();
						var sNewSrc = $(this).attr("src") + '?timestamp=' + iTime;
						$(this).attr( "src", sNewSrc );
					} );
				}
			} );
		}
	} );
} );

/**
 * Upload images with Moxiemanager API
 */
$(document).delegate( ".moxieImageUpload", "click", function(event) {
	event.preventDefault();
	moxman.upload( {
		path: "/Bilder/",
		skin: 'aroma',
		onupload: function(args) {
		   console.log(args.files);
		}
	} );
} );

/**
 * Upload files with Moxiemanager API
 */
$(document).delegate( ".moxieFileUpload", "click", function(event) {
	event.preventDefault();
	moxman.upload( {
		path: "/Filer/",
		skin: 'aroma',
		onupload: function(args) {
		   console.log(args.files);
		}
	} );
} );

/**
 * Browse files with Moxiemanager API
 */
$(document).delegate( ".moxieBrowse", "click", function(event) {
	event.preventDefault();
	moxman.browse( {
		path: "/Bilder/",
		skin: 'aroma',
		onupload: function(args) {
		   console.log(args.files);
		}
	} );				
} );

/**
 * Character countner
 */
$(document).delegate( "input.charCounter, textarea.charCounter", "keyup", function() {
	if( $(this).val().length == 0 ) {
		if( $(this).parent().hasClass("charCounterWrapper") ) {
			$(this).parent().removeClass("charCounterWrapper");
		}
		if( $(this).next().attr('class') == 'charCounterShow' ) {
			$(this).next().remove();
		}
	} else {
		if( !$(this).parent().hasClass("charCounterWrapper") ) {
			$(this).parent().addClass("charCounterWrapper");
		}
		if( $(this).next().attr('class') == 'charCounterShow' ) {
			$(this).next().html( $(this).val().length );
		} else {
			$(this).after('<span class="charCounterShow">' + $(this).val().length + '</span>');
		}
	}
} );

// Function for nice URL:s
function strToUrl( str ) {
	str = str.toLowerCase();

	str = str.replace(/ /g,"-");
	str = str.replace(/\\/g,"-");
	str = str.replace(/_/g,"-");
	str = str.replace(/&/g,"");
	str = str.replace(/\?/g,"");
	str = str.replace(/#/g,"");
	str = str.replace(/%/g,"");
	str = str.replace(/\+/g,"");
	str = str.replace(/$/g,"");
	str = str.replace(/,/g,"");
	str = str.replace(/:/g,"");
	str = str.replace(/;/g,"");
	str = str.replace(/=/g,"");
	str = str.replace(/@/g,"");
	str = str.replace(/&amp;/g,"");
	str = str.replace(/</g,"");
	str = str.replace(/>/g,"");
	str = str.replace(/{/g,"");
	str = str.replace(/}/g,"");
	str = str.replace(/|/g,"");
	str = str.replace(/\^/g,"");
	str = str.replace(/~/g,"");
	str = str.replace(/\[/g,"");
	str = str.replace(/\]/g,"");
	str = str.replace(/`/g,"");
	str = str.replace(/\'/g,"");
	str = str.replace(/"/g,"");
	str = str.replace(/!/g,"");
	str = str.replace(/¨/g,"");

	return str;
}