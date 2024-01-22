<?php

$oSlideshowImage = clRegistry::get( 'clSlideshowImage', PATH_MODULE . '/slideshowImage/models' );
$oSlideshowImage->oDao->aSorting = array(
	'slideshowImageSort' => 'ASC',
	'slideshowImageId' => 'ASC'
);
$aSlides = $oSlideshowImage->aHelpers['oJournalHelper']->read();

/**
 * Exclude slides based on current layout,
 * if slideshowImageToLayout has been used.
 * (Will otherwise not effect this module)
 */
if( !empty($aSlides) ) {
	// Check for relations to layouts
	$oSlideshowImageToLayout = clRegistry::get( 'clSlideshowImageToLayout', PATH_MODULE . '/slideshowImage/models' );
	$aSlideshowImageToLayout = $oSlideshowImageToLayout->read();
	if( !empty($aSlideshowImageToLayout) ) {
		// Found relations
		$aSlideshowImageToLayout = $oSlideshowImageToLayout->read();
		foreach( $aSlides as $key => $entry ) {
			$bExists = false;
			foreach( $aSlideshowImageToLayout as $entry2 ) {
				if( $entry2['slideshowImageId'] == $entry['slideshowImageId'] ) {
					if( $oRouter->sCurrentLayoutKey == $entry2['layoutKey'] ) {
						// Match current layout
						$bExists = true;
					}
				}
			}
			if( $bExists === false ) {
				// Removing image
				unset( $aSlides[$key] );
			}
		}
	}
}

if( !empty($aSlides) ) {
	require_once( PATH_FUNCTION . '/fData.php' );

	/**
	 * Image data
	 */
	$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
	$oImage->setParams( array(
		'parentType' => $oSlideshowImage->sModuleName
	) );
	$oImage->oDao->setEntries( null ); // Make sure no other module mess upp the entry limit because of clRegistry
	$aImageData = valueToKey( 'imageParentId', $oImage->readByParent( arrayToSingle( $aSlides, null, 'slideshowImageId'), array(
		'imageId',
		'imageFileExtension',
		'imageAlternativeText',
		'imageParentId',
		'imageParentType'
	) ) );
	
	/**
	 * Determ mode
	 */
	$sMode = 'multi';
	if( count($aSlides) <= 1 ) {
		$sMode = 'single';
		$aSlides = array_merge( $aSlides, $aSlides, $aSlides );
	}
	
	$sOutput = '';
	$aImageLoader = array();
	
	/**
	 * Assamble slide content
	 */
	$iCount = 0;
	foreach( $aSlides as $aSlide ) {
		if( array_key_exists( $aSlide['slideshowImageId'], $aImageData ) ) {
			$aImage = $aImageData[ $aSlide['slideshowImageId'] ];
			
			// Path & resolution
			$sImagePath = '/images/custom/' . $aImage['imageParentType'] . '/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'];
			$aResolution = @getimagesize( PATH_CUSTOM_IMAGE . '/' . $aImage['imageParentType'] . '/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'] );
			
			if( !empty($aSlide['slideshowImageTextColor']) ) {
				$aDataParams[] = ' color: ' . $aSlide['slideshowImageTextColor'] . ';"';
			}
			
			$sImage = '<img data-u="image" src="' . $sImagePath . '" alt="' . htmlspecialchars( $aImage['imageAlternativeText'] ) . '" />';
			$sImage = !empty( $aSlide['slideshowImageUrlTextId'] ) ? '<a href="' . $aSlide['slideshowImageUrlTextId'] . '" class="image">' . $sImage . '</a>' : $sImage;

			if( SLIDESHOW_IMAGE_TEXT_ENABLE === true && !empty( $aSlide['slideshowImageContentTextId'] ) ) {
				$aSlide['slideshowImageTextColor'] = !empty($aSlide['slideshowImageTextColor']) ? $aSlide['slideshowImageTextColor'] : '#ffffff';			
				$sDescription = '<div class="description" style="color: ' . $aSlide['slideshowImageTextColor'] . ';">' . $aSlide['slideshowImageContentTextId'] . '</div>';
			} else {
				$sDescription = '';
			}

			$sOutput .= '<div class="slide" id="slide-' . $iCount . '" style="display: none;' . (!empty($aDataParams) ? ' ' . implode(' ', $aDataParams) : '') . '">' . $sImage . $sDescription . '</div>';

			// Add image for pre-loading
			$aImageLoader[] = 'slideshowImages.push( "' . $sImagePath . '" );';
			
			$iCount++;
		}
	}
	
	/**
	 * Output
	 */
	echo '
		<div class="view slideshow">
			<div id="jssor-slideshow" style="position: relative; margin: 0 auto; top: 0px; left: 0px; width: 1663px; height: ' . (SLIDESHOW_IMAGE_HEIGHT + 35) . 'px;">
				<div class="slides" data-u="slides" style="cursor: default; position: relative; overflow: hidden; left: 0px; top: 0px; width: 1663px; height: ' . SLIDESHOW_IMAGE_HEIGHT . 'px; overflow: hidden;">
					' . $sOutput . '
				</div>
				<div class="pager" data-u="navigator" style="position: absolute; bottom: 0;">
					<div u="prototype"></div>
				</div>
			</div>
		</div>
		
		<script>
			/**
			 * Pre-load images in slideshow
			 */
			$(document).ready(function() {
				// Preload all images in slideshow in this array
				var slideshowImages = [];
				' . implode(' ', $aImageLoader) . '
				var imageSlideshowObjects = [];
				$.each( slideshowImages , function(index, value) {
					imageSlideshowObjects[index] = new Image();
					imageSlideshowObjects[index].src = value;
				} );
			});
		</script>';
	
	/**
	 * Jssor
	 */
	$oTemplate->addScript( array(
		'key' => 'jsJssor',
		'src' => '/js/jquery.jssor.slider.min.js'
	) );
	$oTemplate->addBottom( array(
		'key' => 'jsJssorInit',
		'content' => '
			<script>
				$(document).ready( function() {				
					if( $(window).width() < ' . SLIDESHOW_IMAGE_WIDTH . ' ) {
						var oOptions = {
							//$SlideWidth: ' . SLIDESHOW_IMAGE_WIDTH . ',
							$Cols: 1,
							//$Align: ((' . SLIDESHOW_IMAGE_WIDTH . ' * 1.5) - ' . SLIDESHOW_IMAGE_WIDTH . ') / 2,
							$BulletNavigatorOptions: {
								$Class: $JssorBulletNavigator$,
								$ChanceToShow: 2,
								$AutoCenter: 1,
								$SpacingX: 20
							}
						};
					
					} else {
						var oOptions = {
							$SlideWidth: ' . SLIDESHOW_IMAGE_WIDTH . ',
							$Cols: 2,
							$Align: ((' . SLIDESHOW_IMAGE_WIDTH . ' * 1.5) - ' . SLIDESHOW_IMAGE_WIDTH . ') / 2,
							$BulletNavigatorOptions: {
								$Class: $JssorBulletNavigator$,
								$ChanceToShow: 2,
								$AutoCenter: 1,
								$SpacingX: 20
							}
						};
					}					
					
					oOptions.$AutoPlay = ' . ($sMode == 'multi' ? 'true' : 'false') . ';
					oOptions.$StartIndex = ' . ($sMode == 'multi' ? '0' : '1') . ';
					
					var jssorSlideshow = new $JssorSlider$( "jssor-slideshow", oOptions );
					
					/**
					 * Responsive code
					 */
					function scaleSlider() {
						var refSize = jssorSlideshow.$Elmt.parentNode.clientWidth;
						
						if( refSize ) {
							refSize = Math.min( refSize, (' . SLIDESHOW_IMAGE_WIDTH . ' * 2) );
							jssorSlideshow.$ScaleWidth( refSize );
							
						} else {
							window.setTimeout( scaleSlider, 30 );
						}
					}
					// Scale slider while window ready, load, resize & orientationchange
					scaleSlider();
					$(window).bind( "load", scaleSlider );
					$(window).bind( "resize", scaleSlider );
					$(window).bind( "orientationchange", scaleSlider );
					
					/**
					 * Transfer action
					 */
					function SlideParkEventHandler(slideIndex, fromIndex) {
						if( fromIndex >= 0 ) {
							$("#slide-" + fromIndex).removeClass( "active" );
						}
						$("#slide-" + slideIndex).addClass( "active" );
					}				
					jssorSlideshow.$On( $JssorSlider$.$EVT_PARK, SlideParkEventHandler );
				} );
			</script>
		'
	) );
	
	/**
	 * Jssor related css
	 */
	$oTemplate->addStyle( array(
		'key' => 'jsJssorCss',
		'content' => '
			.view.slideshow {
				position: relative;
				height: auto;
				width: 100%;
				margin: 0 auto;
			}
			
			.view.slideshow .slide {
				opacity: .5;
				transition: opacity 1s;
			}
			.view.slideshow .slide.active {
				transition: 1;
				opacity: 1 !important;
			}
			
			.view.slideshow .pager div, .view.slideshow .pager div:hover {
				width: 8px;
				height: 8px;
				background: #c0c0c0;
				border: 1px solid #c0c0c0;
				border-radius: 4px;
				-webkit-border-radius: 4px;
				-moz-border-radius: 4px;
				overflow: hidden;
				cursor: pointer;
				display: ' . ($sMode == 'multi' ? 'block' : 'none') . ';
			}
			.view.slideshow .pager div.av, .view.slideshow .pager div.av:hover {
				background: #238891;
				border-color: #238891;
				display: ' . ($sMode == 'multi' ? 'block' : 'block !important') . ';
			}
		'
	) );
}
