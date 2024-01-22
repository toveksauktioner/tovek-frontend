<?php

require_once PATH_CONFIG . '/cfForm.php';

class clRecaptcha {

	private $oOutputHtmlForm;
	private $oTemplate;
	private $sAction;

	private $aUniqueIds = array();

	function __construct() {
		$this->oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
		$this->oTemplate = clRegistry::get( 'clTemplateHtml' );

		$this->sAction = RECAPTCHA_DEFAULT_ACTION;
	}

	/**
	 * Apply reCaptcha thru invoke challenge method
	 */
	public function invokeChallenge() {
		/**
		 * Get an unique id for the reCapthca to add to the form
		 * (this enable use of multiple reCapthca forms on same page)
		 */
		$sRecaptchaUniqueId = $this->generateUniqueId();

		$this->oOutputHtmlForm->setParamAttributes( array(
			'data-recaptcha-form' => $sRecaptchaUniqueId
		) );

		$this->oTemplate->addBottom( array(
			'key' => 'reCaptchaButtons',
			'content' => '
				<script type="application/javascript" async defer>
					var onloadCallback = function() {
						$(".invisible-recaptcha").each( function() {
							var object = $(this);

							var iWidgetId = grecaptcha.render( object.attr("id"), {
								"sitekey": "' . RECAPTCHA_SITE_KEY . '",
								"callback": function(token) {
									object.parents("form").find(".g-recaptcha-response").val(token);
									object.parents("form").submit();
								}
							} );

							$(object).parents("form").attr("data-widget-id", iWidgetId);
						} );
					};

					$(document).delegate( "form[data-recaptcha-form] .buttons button", "click", function(event) {
						event.preventDefault();
						var iWidgetId = $(this).parents(".buttons").parents("form").data("widget-id");
						grecaptcha.execute( iWidgetId );
					} );
				</script>

				<script src="https://www.google.com/recaptcha/api.js?hl=' . substr($GLOBALS['Locales'][ $GLOBALS['langId'] ], 0, 2) . '&onload=onloadCallback&render=explicit" async defer></script>'
		) );

		return '
			<div id="g-' . $sRecaptchaUniqueId . '" class="g-recaptcha invisible-recaptcha"
				data-sitekey="' . RECAPTCHA_SITE_KEY . '"
				data-callback="onloadCallback"
				data-size="invisible">
			</div>';
	}

	public function modifyButtons( $aButtons ) {
		/**
		 * To be implemented if shown necessary
		 */
	}

	public function setAction( $sAction ) {
		$this->sAction = $sAction;
	}

	/**
	 * ReCaptcha v3 v3Frontend
	 */
	public function v3Frontend() {
		$iFormId = $this->generateUniqueId();

		return '
			<input type="hidden" name="' . RECAPTCHA_TOKEN_KEY . '" id="' . RECAPTCHA_TOKEN_KEY . '-' . $iFormId . '">
			<script src="https://www.google.com/recaptcha/api.js?render=' . RECAPTCHA_SITE_KEY . '"></script>
			<script>
				grecaptcha.ready(function() {
					 grecaptcha.execute("' . RECAPTCHA_SITE_KEY . '", {action: "' . $this->sAction . '"}).then(function(token) {
							$("#' . RECAPTCHA_TOKEN_KEY . '-' . $iFormId . '").val( token );
					 });
				});
			</script>';
	}

	public function validate( $sToken ) {
		$oCurl = clFactory::create( 'clCurl' );

		$aData = array(
			'secret' => RECAPTCHA_SECRET_KEY,
			'response' => $sToken,
			//'remoteip' => getRemoteAddr()
		);

		$mRespons = $oCurl->post( $aData, RECAPTCHA_API_ENDPOINT . '/siteverify' );

		if( !empty($mRespons['data']['content']) ) {
			return $mRespons['data']['content']->success;
		}

		return false;
	}

	/**
	 * Generate a unique ID
	 */
	public function generateUniqueId( $iLength = null, $bNumeric = true, $aCharacters = null ) {
		$iLength = $iLength !== null ? $iLength : 8;

		// If no custom characters is included
		if( $aCharacters === null ) {
			if( $bNumeric === true ) {
				$aCharacters = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
			} else {
				// The characters. Chars like 1li0Oo is not included to avoid confusion
				$aCharacters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'k', 'm', 'n', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '2', '3', '4', '5', '6', '7', '8', '9');
			}
		}

		// Max character index
		$iMaxCharacterIndex	= count( $aCharacters ) - 1;

		// Seed
		srand( (double) microtime() * 1000000 );

		// ID be appended to this string
		$sUniqueId = '';

		for( $iCount = 0; $iCount < $iLength; $iCount++ ) {
			$sUniqueId .= $aCharacters[ rand( 0, $iMaxCharacterIndex ) ];
		}

		if( in_array($sUniqueId, $this->aUniqueIds) ) {
			return $this->generateUniqueId();

		} else {
			$this->aUniqueIds[] = $sUniqueId;
			return $sUniqueId;

		}
	}

}
