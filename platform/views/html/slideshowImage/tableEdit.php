<?php

$oSlideshowImage = clRegistry::get( 'clSlideshowImage', PATH_MODULE . '/slideshowImage/models' );
$aSlideshowImageDataDict = $oSlideshowImage->oDao->getDataDict();

/**
 * Sorting
 */
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oSlideshowImage->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('slideshowImageSort' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	//'slideshowImageId' => array(),
	'slideshowImageSort' => array(),
	'slideshowImageStatus' => array(),
	'slideshowImageStart' => array(),
	'slideshowImageEnd' => array(),
	'slideshowImageCreated' => array(),
	'slideshowImageUpdated' => array()
) );

if( !empty($_GET['slideshowImageId']) ) {
	$aSlides = $oSlideshowImage->read( '*', $_GET['slideshowImageId'] );
} else {
	// Read all
	$aSlides = $oSlideshowImage->read( '*' );
}

$sEditUrl = $oRouter->getPath( 'adminSlideshowImageAdd' );

$sOutput = '';

if( !empty($aSlides) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oSlideshowImage->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( array(
		'slideshowImageThumbnail' => array(
			'title' => _( 'Thumbnail' )
		) ) + $oSorting->render() + array(
		'slideshowImageControls' => array(
			'title' => ''
		)
	) );

	$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
	$oImage->setParams( array(
		'parentType' => $oSlideshowImage->sModuleName
	) );
	$aImageDataRaw = $oImage->readByParent( arrayToSingle( $aSlides, null, 'slideshowImageId'), array(
		'imageId',
		'imageFileExtension',
		'imageParentId'
	) );
	$aSlidesImageData = array();
	foreach( $aImageDataRaw as $aEntry ) {
		$aSlidesImageData[ $aEntry['imageParentId'] ] = $aEntry['imageId'] . '.' . $aEntry['imageFileExtension'];
	}

	foreach( $aSlides as $aSlide ) {
		$aRow = array(
			'slideshowImageThumbnail' => '<img src="/images/custom/' . $oSlideshowImage->sModuleName . '/tn/' . $aSlidesImageData[$aSlide['slideshowImageId']] . '" alt="" width="45" />',
			//'slideshowImageId' => '<a href="' . $sEditUrl . '?slideshowImageId=' . $aSlide['slideshowImageId'] . '">' . $aSlide['slideshowImageId'] . '</a>',
			'slideshowImageSort' => $aSlide['slideshowImageSort'],
			'slideshowImageStatus' => '<span class="' . $aSlide['slideshowImageStatus'] . '">' . $aSlideshowImageDataDict['entSlideshowImage']['slideshowImageStatus']['values'][$aSlide['slideshowImageStatus']] . '</span>',
			'slideshowImageStart' => $aSlide['slideshowImageStart'],
			'slideshowImageEnd' => $aSlide['slideshowImageEnd'],
			'slideshowImageCreated' => substr( $aSlide['slideshowImageCreated'], 0, 16 ),
			'slideshowImageUpdated' => substr( $aSlide['slideshowImageUpdated'], 0, 16 ),
			'slideshowImageControls' => '
				<a href="' . $sEditUrl . (strpos($sEditUrl,'?') ? '&' : '?') . 'slideshowImageId=' . $aSlide['slideshowImageId'] . '" class="icon iconEdit iconText">' . _( 'Edit' ) . '</a>
				&nbsp;|&nbsp;
				<a href="?event=deleteSlideshowImage&amp;deleteSlideshowImage=' . $aSlide['slideshowImageId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		);
		
		$oOutputHtmlTable->addBodyEntry( $aRow, array('id' => 'sortSlideshowImage_' . $aSlide['slideshowImageId']) );
	}

	$oTemplate->addBottom( array(
		'key' => 'attributeSortable',
		'content' => '
		<script>
			$(".view.slideshow.tableEdit table tbody").sortable({
				update : function () {
					$.get("' . $oRouter->sPath . '", "ajax=true&event=sortSlideshowImage&sortSlideshowImage=1&" + $(this).sortable("serialize"));
				}
			});
		</script>'
	) );

	$sOutput = $oOutputHtmlTable->render();
	
} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view slideshowImage tableEdit">
		<h1>' . _( 'Slideshow' ) . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $sEditUrl. '" class="icon iconText iconAdd">' . _( 'Add image' ) . '</a>
			</div>
		</section>
		<section>
			' . $sOutput . '
		</section>
	</div>';
