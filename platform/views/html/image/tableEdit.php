<?php

clFactory::loadClassFile( 'clOutputHtmlPagination' );
clFactory::loadClassFile( 'clOutputHtmlSorting' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oImage	= clRegistry::get( 'clImage', PATH_MODULE . '/image/models/' );

$aImageDataDict = $oImage->oDao->getDataDict();

// Parent types
$aImageParentTypes = arrayToSingle( $oImage->read( array(
	'DISTINCT(imageParentType) AS imageParentType'
) ), null, 'imageParentType');

$oPagination = new clOutputHtmlPagination( $oImage->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null )
) );

$oImageOutputDataDict = array(
	'imageId' => array(
		'title' => _( 'ID' )
	),
	'imageFileExtension' => array(
		'title' => _( 'File Extension' )
	),
	'imageAlternativeText' => array(
		'title' => _( 'Alternative text' )
	),
	'imageParentType' => array(
		'title' => _( 'Parent Type' )
	),
	'imageParentId' => array(
		'title' => _( 'Parent ID' )
	),
	'imageSort' => array(
		'title' => _( 'Sort' )
	),
	'imageCreated' => array(
		'title' => _( 'Created' )
	),
	'imageControls' => array(
		'title' => ''
	)
);

$oSorting = new clOutputHtmlSorting( $oImage->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('imageCreated' => 'DESC') )
) );
$oSorting->setSortingDataDict( $oImageOutputDataDict );

/**
 * Search form
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aImageDataDict, array(
	'attributes' => array( 'class' => 'searchForm' ),
	'data' => $_GET,
	'buttons' => array(
		'submit' => _( 'Search' ),
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'searchQuery' => array(
		'title' => _( 'Search' )
	),
	'page' => array(
		'type' => 'hidden',
		'value' => 0
	)
) );
$sSearch = $oOutputHtmlForm->render();

if( empty( $_GET['parentType'] ) ) {
	$_GET['parentType'] = 'all';
}

$aImageFields = array(
	'imageId',
	'imageFileExtension',
	'imageAlternativeText',
	'imageParentType',
	'imageParentId',
	'imageSort',
	'imageCreated'
);

/**
 * Search
 */
if( !empty($_GET['searchQuery']) ) {
	$aSearchCriterias = array(
		'imageSearch' => array(
			'type' => 'like',
			'value' => $_GET['searchQuery'],
			'fields' => $aImageFields
		)
	);
	$oImage->oDao->setCriterias( $aSearchCriterias );
}

// Image groupKeys
$sImageParentTypes = '';
if( !empty( $aImageParentTypes ) ) {
	foreach( $aImageParentTypes as $value ) {
		$sImageParentTypes .= '<li class="ui-state-default ui-corner-top' . ( $_GET['parentType'] == $value ? ' ui-tabs-selected ui-state-active' : '' ) . '"><a href="' . $oRouter->sPath . '?parentType=' . $value . '&amp;' . stripGetStr( array('parentType') ) . '">' . $value . '</a></li>';
	}
	$sImageParentTypes = '<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header">' . $sImageParentTypes . '</ul>';
}

try {
	if( !empty( $_POST['updateImages'] ) && !empty( $_POST['image'] ) ) {
		$aErrors = array();
		foreach ( $_POST['image'] as $iId => &$aImage ) {
			try {
				$oImage->update( $iId, $aImage );
			} catch( Exception $e ) {
				$aErrors[] = sprintf( _( 'Image update error: %s' ), $e->getMessage() );
			}
		}
		if( !empty( $aErrors ) ) throw new Exception( implode( ', ', $aErrors ) );
	}
} catch( Exception $e ) {
	echo $e->getMessage();
}

try {
	$oImage->setParams( array(
		'parentType' => $_GET['parentType']
	) );
	$aImages = $_GET['parentType'] == 'all' ? $oImage->read( $aImageFields ) : $oImage->readByParent( null, $aImageFields );
	if( empty($aImages) ) throw new Exception( _( 'There are no items to show' ) );

	$oOutputHtmlTable = new clOutputHtmlTable( $aImageDataDict );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'imageControls' => array(
			'title' => ''
		)
	) );

	foreach( $aImages as &$aImage ) {
		$sPath	= '/images/custom/' . $aImage['imageParentType'] . '/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'];
		
		$aRow = array(		
			'imageId' => '<a href="' . $sPath . '" target="_blank" rel="' . $sPath . '" class="imgPreview">' . $aImage['imageId'] . '</a>',
			'imageFileExtension' => $aImage['imageFileExtension'],
			'imageAlternativeText' => '<input type="text" name="image[' . $aImage['imageId'] . '][imageAlternativeText]" value="' . htmlspecialchars( $aImage['imageAlternativeText'] ) . '" class="text" />',
			'imageParentType' => $aImage['imageParentType'],
			'imageParentId'	=> $aImage['imageParentId'],
			'imageSort'	=> $aImage['imageSort'],
			'imageCreated' => $aImage['imageCreated'],
			'imageControls' => '
				<label title="' . htmlspecialchars( '<img src="' . $sPath . '" />' ) . '" target="_blank" class="imgPreview icon iconImage"><span>' . _( 'Preview' ) . '</span></label>
				<a href="' . $oRouter->getPath( 'superImageAltRoute' ) . '?imageId=' . $aImage['imageId'] . '" class="icon iconRelation" title="' . _( 'Alt route relations' ) . '"><span>' . _( 'Alt route relations' ) . '</span></a>
				<a href="' . $oRouter->sPath . '?event=deleteImage&amp;deleteImage=' . $aImage['imageId'] . '&amp;' . stripGetStr( array('event', 'deleteImage') ) . '" class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>'
		);
		
		$oOutputHtmlTable->addBodyEntry( $aRow );
	}
	
	$sImages = '
		<form method="post">
			' . $oOutputHtmlTable->render() . '
			<div class="hidden">
				<input type="hidden" name="updateImages" value="1" />
			</div>
			<p class="buttons">
				<button>' . sprintf( _( 'Save %s' ), _( 'images' ) ) . '</button>
			</p>
			<hr />
			' . $oPagination->render() . '
		</form>';
		
} catch( Exception $e ) {
	$sImages = '<strong>' . $e->getMessage() . '</strong>';
}

$sImages = '<div class="imageList ui-tabs-panel ui-widget-content ui-corner-bottom ui-helper-clearfix">' . $sImages . '</div>';

echo '
	<div class="view image tableEdit">
		<h1>' . _( 'Images' ) . '</h1>		
		<section class="tools">
			<div class="tool">
				' . $sSearch . '
			</div>
		</section>
		<div class="ui-tabs ui-widget ui-widget-content ui-corner-all">
			' . $sImageParentTypes . '
			' . $sImages . '
		</div>
	</div>';