<?php

if( !empty($_GET['parentTable']) ) $GLOBALS['imageView']['parentTable'] = $_GET['parentTable'];
if( !empty($_GET['parentId']) ) $GLOBALS['imageView']['parentId'] = $_GET['parentId'];
if( !empty($_GET['objectId']) ) $GLOBALS['imageView']['objectId'] = $_GET['objectId'];
if( !empty($_GET['altText']) ) $GLOBALS['imageView']['altText'] = $_GET['altText'];

$sImageList = $sMainImage = '';

$oObjectStorageImage = clRegistry::get( 'clObjectStorage', PATH_MODULE . '/objectStorage/models' );
	$oObjectStorageImage->oDao->aSorting = [ 'parentSort' => 'ASC' ];

if( !empty($GLOBALS['imageView']['images']) ) {
  // Images already selected - do nothing

} else if( !empty($GLOBALS['imageView']['parentTable']) && !empty($GLOBALS['imageView']['parentId']) && ctype_digit($GLOBALS['imageView']['parentId'])  ) {
  // Image parent table and id is set get from database
  $GLOBALS['imageView']['images'] = $oObjectStorageImage->readWithParams( [
   'parentTable' => $GLOBALS['imageView']['parentTable'],
   'parentId' => $GLOBALS['imageView']['parentId'],
   'includeVariants' => true,
   'structureVariants' => true,
   'access' => 'public',
   'type' => 'image'
  ] );

} else {
  // Invalid parmeters - end view
  return;
}

if( !empty($GLOBALS['imageView']['images']) ) {
	$iMainImageId = ( !empty($GLOBALS['imageView']['objectId']) ? $GLOBALS['imageView']['objectId'] : null );

  // Count items as a form of feedback
  $GLOBALS['imageView']['imageCount'] = count( $GLOBALS['imageView']['images'] );

	foreach( $GLOBALS['imageView']['images'] as $iObjectId => $aImageData ) {

    if( $GLOBALS['imageView']['imageCount'] > 1 ) {
      // No reason to display gallery if only one image
  		$sImageList .= '
        <a data-zoom-id="imageViewMainImage" href="' . $aImageData['large']['objectUrl'] . '" data-image="' . $aImageData['medium']['objectUrl'] . '">
        	<img src="' . $aImageData['tn']['objectUrl'] . '" alt="" />
        </a>';
     }

		if( empty($iMainImageId) || ($iMainImageId == $iObjectId) ) {
			$sMainImage = '
        <a href="' . $aImageData['large']['objectUrl'] . '" class="MagicZoom" id="imageViewMainImage" data-options="zoomOn:click; zoomPosition:inner;  variableZoom:true;  cssClass:mz-show-arrows; transitionEffect:false; textClickZoomHint:Klicka för att zooma;" data-mobile-options="textClickZoomHint:Dubbelklicka eller nyp för att zooma;">
          <img src="' . $aImageData['medium']['objectUrl'] . '">
        </a>';

			$oTemplate->addOgTag( 'image', $aImageData['small']['objectUrl'] );

      $iMainImageId = $iObjectId;
		}

 	}

} else {
  $sMainImage = '
    <a href="/images/templates/tovek/itemEmptyImage.png" class="MagicZoom" data-options="zoomMode:off;">
      <img src="/images/templates/tovek/itemEmptyImage.png" alt="no-image" />
    </a>';
	$GLOBALS['imageView']['imageCount'] = 0;
}

$sScrollHtml = '';
if( !empty($sImageList) ) {
  $sScrollHtml = '
    <div class="imageList MagicScroll" data-options="loop:off; items:4;">' . $sImageList . '</div>';
}

echo '
  <div class="view global imageView">
		<div class="mainImage">' . $sMainImage . '</div>
    ' . $sScrollHtml . '
  </div>
	<script>
		MagicZoom.start();
		MagicScroll.start();
	</script>';
