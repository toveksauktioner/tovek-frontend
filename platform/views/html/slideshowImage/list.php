<?php

$oSlideshowImage = clRegistry::get( 'clSlideshowImage', PATH_MODULE . '/slideshowImage/models' );

// Sort
$oSlideshowImage->oDao->aSorting = array(
	'slideshowImageSort' => 'ASC',
	'slideshowImageId' => 'ASC'
);

// Data
$aSlides = $oSlideshowImage->aHelpers['oJournalHelper']->read();

if( empty($aSlides) ) {
	// No output at empty
	return;
}
	
/**
 * Exclude slides based on current layout,
 * if slideshowImageToLayout has been used.
 * (Will otherwise not effect this module)
 *
 * Check for relations to layouts
 */
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

if( empty($aSlides) ) {
	// No slides left, no output
	return;
}

$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
$oImage->setParams( array(
	'parentType' => $oSlideshowImage->sModuleName
) );
$oImage->oDao->setEntries( null ); // Make sure no other module mess upp the entry limit because of clRegistry

// Image data
$aImageDataRaw = $oImage->readByParent( arrayToSingle( $aSlides, null, 'slideshowImageId'), array(
	'imageId',
	'imageFileExtension',
	'imageAlternativeText',
	'imageParentId'
) );
$aImageData = array();
foreach( $aImageDataRaw as $entry ) {
	$aImageData[ $entry['imageParentId'] ] = array(
		'filename' => $entry['imageId'] . '.' . $entry['imageFileExtension'],
		'alternativeText' => $entry['imageAlternativeText']
	);
}
unset( $aImageDataRaw );

$aImageLoader = array();

$sOutput = '';

foreach( $aSlides as $aSlide ) {
	if( !array_key_exists( $aSlide['slideshowImageId'], $aImageData ) ) {
		// Found no image, continue to next entry
		continue;
	}
	
	$sImagePath = '/images/custom/' . $oSlideshowImage->sModuleName . '/' . $aImageData[ $aSlide['slideshowImageId'] ]['filename'];

	// Slide params
	$aParams = array(
		'timeout'  =>  $aSlide['slideshowImageTimeout'],
		'speed'    =>  $aSlide['slideshowImageSpeed'],
		'fx'	   =>  $aSlide['slideshowImageFx']
	);

	$aDataParams = array();
	foreach( $aParams as $key => $value ) {
		$aDataParams[] = 'data-cycle-' . $key . '="' . $value . '"';
	}
	
	if( !empty($aSlide['slideshowImageTextColor']) ) {
		$aDataParams[] = 'style="color: ' . $aSlide['slideshowImageTextColor'] . ';"';
	}
	
	$sImage = '<img src="' . $sImagePath . '" alt="' . htmlspecialchars( $aImageData[ $aSlide['slideshowImageId'] ]['alternativeText'] ) . '" />';
	$sImage = !empty( $aSlide['slideshowImageUrlTextId'] ) ? '<a href="' . $aSlide['slideshowImageUrlTextId'] . '" class="image">' . $sImage . '</a>' : '<div class="image">' . $sImage . '</div>';
	
	// Description
	$sDescription = '';
	if( SLIDESHOW_IMAGE_TEXT_ENABLE === true && !empty( $aSlide['slideshowImageContentTextId'] ) ) {
		$aSlide['slideshowImageTextColor'] = !empty($aSlide['slideshowImageTextColor']) ? $aSlide['slideshowImageTextColor'] : '#ffffff';			
		$sDescription = '<div class="description" style="color: ' . $aSlide['slideshowImageTextColor'] . ';">' . $aSlide['slideshowImageContentTextId'] . '</div>';
	}

	$sOutput .= '<li' . (!empty($aDataParams) ? ' ' . implode(' ', $aDataParams) : '') . '>' . $sImage . $sDescription . '</li>';

	// Add image for pre-loading
	$aImageLoader[] = 'slideshowImages.push( "' . $sImagePath . '" );';
}

/**
 * Slideshow params
 */
$aParams = array(
	'slides'   =>  'li',
	'log' 	   =>  'false',
	//'delay'  =>  '',
	'swipe'    =>  'true',
	'loader'   =>  'wait',
	// Caption
	#'caption' 			  => 'cycle-caption',
	#'caption-template'   => '{{slideNum}} / {{slideCount}}',
	// Pager
	'pager'    			  =>  '.cycle-pager',
	'pager-active-class'  =>  'cycle-pager-active',
	'pager-template' 	  =>  '<span>&bull;</span>',
);

$aDataParams = array();
foreach( $aParams as $key => $value ) {
	$aDataParams[] = 'data-cycle-' . $key . '="' . $value . '"';
}

echo '
	<div class="view slideshow">
		<div class="container">
			<ul class="cycle-slideshow"' . (!empty($aDataParams) ? ' ' . implode(' ', $aDataParams) : '') . '>
				' . $sOutput . '
			</ul>
		</div>
		' . (count($aImageLoader) > 1 ? '
		<div class="cycle-pager"></div>
		' : '') . '
	</div>';

/**
 * Load jQuery plugin manually
 */
echo '
	<script src="/js/jquery.cycle2.min.js"></script>
	<script src="/js/jquery.cycle2.swipe.min.js"></script>';

/**
 * Pre-load images
 */
echo '
	<script>
		$(document).ready( function() {
			// Preload all images in slideshow in this array
			var slideshowImages = [];
			' . implode(' ', $aImageLoader) . '
			var imageSlideshowObjects = [];
			$.each( slideshowImages , function(index, value) {
				imageSlideshowObjects[index] = new Image();
				imageSlideshowObjects[index].src = value;
			} );
		} );
	</script>';