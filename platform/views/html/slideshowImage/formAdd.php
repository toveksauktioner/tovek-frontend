<?php

$aErr = array();
$sImageList = '';

$oLayout = clRegistry::get( 'clLayoutHtml' );
$oSlideshowImage = clRegistry::get( 'clSlideshowImage', PATH_MODULE . '/slideshowImage/models' );
$oSlideshowImage->oDao->setLang( $GLOBALS['langIdEdit'] );
$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );

/**
 * Image params
 */
$oImage->setParams( array(
	'parentType' => $oSlideshowImage->sModuleName,
	'maxWidth' => SLIDESHOW_IMAGE_WIDTH,
	'maxHeight' => SLIDESHOW_IMAGE_HEIGHT,
	'tnMaxWidth' => SLIDESHOW_IMAGE_WIDTH / 2,
	'tnMaxHeight' => SLIDESHOW_IMAGE_HEIGHT / 2,
	'crop' => SLIDESHOW_IMAGE_CROP
) );

/**
 * Edit image with Moxiemanager
 */
if( !empty($_POST['frmEditImage']) ) {
	$oImage->createThumbnailByPrimary( $_POST['imageId'] );
}

if( !empty($_POST['frmSlideshowImageAdd']) ) {
	// Update
	if( !empty($_GET['slideshowImageId']) && ctype_digit($_GET['slideshowImageId']) ) {
		if( $oSlideshowImage->update($_GET['slideshowImageId'], $_POST) !== false ) {
			$iSlideshowImageId = $_GET['slideshowImageId'];
		}
		$aErr = clErrorHandler::getValidationError( 'updateSlideshowImage' );
	// Create
	} else {
		$iSlideshowImageId = $oSlideshowImage->create($_POST);
		$aErr = clErrorHandler::getValidationError( 'createSlideshowImage' );
	}

	$oUpload = clRegistry::get( 'clUpload' );
	
	/**
	 * Image
	 */
	if( !empty($_FILES['slideshowImageUpload']['tmp_name'][0]) && !empty($iSlideshowImageId) ) {
		if( !empty($_GET['slideshowImageId']) ) {
			// Delete previous images
			$oImage->deleteByParent( $_GET['slideshowImageId'], $oSlideshowImage->sModuleName );
			// Reset image params, as deleteByParent removes them
			$oImage->setParams( array(
				'parentType' => $oSlideshowImage->sModuleName,
				'maxWidth' => SLIDESHOW_IMAGE_WIDTH,
				'maxHeight' => SLIDESHOW_IMAGE_HEIGHT,
				'tnMaxWidth' => SLIDESHOW_IMAGE_WIDTH / 2,
				'tnMaxHeight' => SLIDESHOW_IMAGE_HEIGHT / 2,
				'crop' => SLIDESHOW_IMAGE_CROP
			) );
		}
		
		$oImage->createWithUpload( array(
			'allowedMime' => array(
				'image/jpeg' => 'jpg',
				'image/pjpeg' => 'jpg',
				'image/gif' => 'gif',
				'image/png' => 'png'
			),
			'key' => 'slideshowImageUpload'
		), $iSlideshowImageId );
		
	} elseif( !empty($_POST['slideshowImageBrowse']) && !empty($iSlideshowImageId) ) {		
		if( !empty($_GET['slideshowImageId']) ) {
			// Delete previous images
			$oImage->deleteByParent( $_GET['slideshowImageId'], $oSlideshowImage->sModuleName );
		}
		
		require_once PATH_FUNCTION . '/fFileSystem.php';
		
		$sFile = substr($_POST['slideshowImageBrowse'], strrpos($_POST['slideshowImageBrowse'], '/') + 1);
		$sFileExtension = getFileExtension( $sFile );
		$sExistingImage = PATH_PUBLIC . '/images/user/' . $sFile;
		
		$iImageId = $oImage->create( array(
			'imageFileExtension' => $sFileExtension,
			'imageParentType' => $oSlideshowImage->sModuleName,
			'imageParentId' => $iSlideshowImageId
		) );
		
		if( ctype_digit($iImageId) ) {
			$sNewFile = PATH_CUSTOM_IMAGE . '/' . $oSlideshowImage->sModuleName . '/' . $iImageId . '.' . getFileExtension( $sFile );
			
			if( copy($sExistingImage, $sNewFile) ) {
				// Create thumbnail
				$oImage->createThumbnailByPrimary( $iImageId );
			} else {
				$aErr[] = _( 'Problem with copy image' );
			}
		}
	}

	if( empty($aErr) && empty($_GET['slideshowImageId']) ) {
		// Redirect if just created
		$oRouter->redirect( $oRouter->sPath . '?slideshowImageId=' . $iSlideshowImageId );
	}
}

/**
 * Edit
 */
if( !empty($_GET['slideshowImageId']) && ctype_digit($_GET['slideshowImageId']) ) {
	$aSlideshowImageData = current( $oSlideshowImage->read('*', $_GET['slideshowImageId']) );
	$sTitle = _( 'Edit item' );

	// Images
	$oImage->oDao->aSorting = array(
		'imageId' => 'ASC'
	);
	$aImageData = $oImage->readByParent( $_GET['slideshowImageId'], array(
		'imageId',
		'imageFileExtension',
		'imageParentType',
		'imageParentId'
	) );
	$aImages = array();
	foreach( $aImageData as $entry ) {
		if( empty($aImages[$entry['imageParentId']]) ) $aImages[$entry['imageParentId']] = array();
		$aImages[$entry['imageParentId']][] = $entry;
	}
	$sImageList = '';
	if( !empty($aImages[$_GET['slideshowImageId']]) && count($aImages[$_GET['slideshowImageId']]) > 0 ) {
		foreach( $aImages[$_GET['slideshowImageId']] as $aImage ) {
			$sImageList .= '
			<h2>' . _( 'Current image' ) . '</h2>
			<p>
				<a href="/images/custom/' . $aImage['imageParentType'] . '/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'] . '" target="_blank" class="modal">
					<img src="/images/custom/' . $aImage['imageParentType'] . '/' . IMAGE_TN_DIRECTORY . '/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'] . '" alt="" />
				</a>
			</p>
			<a href="/images/custom/' . $aImage['imageParentType'] . '/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'] . '" class="editableImage icon iconText iconEdit" data-module-name="' . $aImage['imageParentType'] . '" data-image-id="' . $aImage['imageId'] . '" data-image-extension="' . $aImage['imageFileExtension'] . '">' . _( 'Edit image' ) . '</a>';
		}
	}

/**
 * New
 */
} else {
	$aSlideshowImageData = $_POST;
	$sTitle = _( 'Add item' );
	$sImageList = '
		<h2>' . _( 'Current image' ) . '</h2>
			<p>' . _( 'No image uploaded yet' ) . '.</p>';
}

/**
 * Form buttons
 */
$aButtons = array(
	'submit' => array(
		'content' => _( 'Save' )
	)
);
if( SLIDESHOW_IMAGE_COLOR_ENABLE === true ) {
	$aButtons['colorReset'] = array(
		'content' => _( 'Reset colors' ),
		'attributes' => array(
			'type' => 'reset',
			'id' => 'btnResetColors'
		)
	);
}

/**
 * Add form
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oSlideshowImage->oDao->getDataDict(), array(
	'attributes' => array('class' => 'marginal'),
	'data' => $aSlideshowImageData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => $aButtons
) );
$aFormDataDict = array(
	'slideshowImageUpload' => array(
		'type' => 'upload',
		'title' => _( 'Image' ),
		'suffixContent' => '
			<p>' . _( 'Best image resolution is' ) . ' ' . SLIDESHOW_IMAGE_WIDTH . 'x' . SLIDESHOW_IMAGE_HEIGHT . '.</p>
			<p>' . _( 'The image will' ) . ' ' . (SLIDESHOW_IMAGE_CROP ? _( 'be cropped if needed' ) : _( 'not be cropped' ) ) . '.</p>
		'
	),
	'slideshowImageBrowse' => array(
		'type' => 'string',
		'title' => _( 'Existing image' ),
		'attributes' => array(
			'placeholder' => _( 'Click here to select' )
		)
	),
	'slideshowImageStatus' => array(),
	'slideshowImageSort' => array(
		'type' => 'hidden'
	),
	'slideshowImageStart' => array(
		'attributes' => array(
			'class' => 'text datetimepicker'
		)
	),
	'slideshowImageEnd' => array(
		'attributes' => array(
			'class' => 'text datetimepicker'
		)
	),
	'slideshowImageUrlTextId' => array(),
	/**
	 * Colors
	 */
	'slideshowImageTextColor' => array(),
	'slideshowImageBackgroundColor' => array(),
	'slideshowImageGradientColor' => array(),
	/**
	 * Transformation
	 */
	'slideshowImageTimeout' => array(
		'defaultValue' => '3000'
	),
	'slideshowImageSpeed' => array(),
	'slideshowImageFx' => array(),
	'frmSlideshowImageAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
);

if( SLIDESHOW_IMAGE_EFFECT_CONTROLS_ENABLE === false ) {
	$aFormDataDict['slideshowImageSpeed'] = array(
		'type' => 'hidden',
		'value' => '500'
	);
	$aFormDataDict['slideshowImageFx'] = array(
		'type' => 'hidden',
		'value' => 'fade'
	);
}

if( SLIDESHOW_IMAGE_COLOR_ENABLE === false ) {
	/**
	 * Colors
	 */
	unset(
		$aFormDataDict['slideshowImageTextColor'],
		$aFormDataDict['slideshowImageBackgroundColor'],
		$aFormDataDict['slideshowImageGradientColor']
	);
}

if( SLIDESHOW_IMAGE_TEXT_ENABLE === true ) {
	$aFormDataDict[ 'slideshowImageContentTextId' ] = array(
		'attributes' => array(
			'class' => SLIDESHOW_IMAGE_TEXT_TINYMCE_ENABLE ? ' editor compact' : ''
		),
		'appearance' => 'full'
	);
}

$oOutputHtmlForm->setFormDataDict( $aFormDataDict );

echo '
	<div class="view slideshowImage formAdd">
		<h1>' . $sTitle . '</h1>
		' . $oOutputHtmlForm->render() . '
		<aside class="preview">
			' . $sImageList . '
		</aside>
	</div>';

$oSlideshowImage->oDao->setLang( $GLOBALS['langId'] );

/**
 *
 * Scripts
 * 
 */

// Use tiny MCE editor
$oTemplate->addScript( array(
	'key' => 'jsTinyMce',
	'src' => '/modules/tinymce/tiny_mce.js'
) );
$oTemplate->addScript( array(
	'key' => 'jsTinyMceConfig',
	'src' => '/modules/tinymce/config/basic.js.php'
) );
 
$oTemplate->addBottom( array(
	'key' => 'moxieBrowseJs',
	'content' => '
		<script type="text/javascript">
			$(document).delegate( "#slideshowImageBrowse", "click", function(event) {
				moxman.browse( {
					path: "/Bilder/",
					onupload: function(args) {
					   console.log(args.files);
					},
					oninsert: function(args) {
						console.log(args.focusedFile);
						$("#slideshowImageBrowse").val( args.focusedFile.path );
					}
				} );				
			} );
		</script>'
) );

$oTemplate->addScript( array(
	'key' => 'jsMoxiemanager',
	'src' => '/modules/tinymce/plugins/moxiemanager/js/moxman.loader.min.js'
) );

if( array_key_exists('slideshowImageTextColor', $aFormDataDict) ) {
	/**
	 * Colorpicker
	 */
	$oTemplate->addBottom( array(
		'key' => 'colorpickerBase',
		'content' => '<script type="text/javascript" src="/js/jquery.colorpicker.min.js"></script>'
	) );
	$oTemplate->addBottom( array(
		'key' => 'colorpickerSelector',
		'content' => '
			<script type="text/javascript">
				$(document).delegate( "#btnResetColors", "click", function(event) {
					event.preventDefault();
					$("#slideshowImageTextColor").val("");
					$("#slideshowImageBackgroundColor").val("");
					$("#slideshowImageGradientColor").val("");
				} );
				$("#slideshowImageTextColor, #slideshowImageBackgroundColor, #slideshowImageGradientColor").colorPicker( {
						animationSpeed: 0, // toggle animation speed,
						doRender: false, // Do not color input,
						renderCallback: function($element, toggled) {
							if( this.color.colors.alpha >= 1 ) $element.val("#" + this.color.colors.HEX.toLowerCase() ); // Make hex if no opacity
				    }
				} );
			</script>'
	) );
}