<?php

echo '
	<div id="languageSelector">
		<div class="availableLang">
			<div class="lang"><i class="fas fa-globe-americas"></i><span class="desktop tablet">Language</div>
		</div>	
		<div id="google_translate_element" class="foldable availableLang"></div>

		<script type="text/javascript">
			function googleTranslateElementInit() {
			  new google.translate.TranslateElement( {
			  	pageLanguage: "sv"
			  }, "google_translate_element" );
			}
			$("#languageSelector .availableLang:first-child").click( function() {
				$("#languageSelector .foldable").toggle();
				$("#languageSelector .desktop.tablet").toggleClass("mobile");
			} );
		</script>
		<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
	</div>';
