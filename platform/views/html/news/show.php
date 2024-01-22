<?php

$oNews = clRegistry::get( 'clNews', PATH_MODULE . '/news/models' );
$oRouter = clRegistry::get( 'clRouter' );

$sDateFormat = 'Y-m-d';

if( empty($_GET['newsId']) ) {
	$aObjectData = $oRouter->readObjectByRoute( $oRouter->iCurrentRouteId );
	if( !empty($aObjectData) ) {
		$_GET['newsId'] = $aObjectData[0]['objectId'];
	}
}

if( empty($_GET['newsId']) ) {
	clFactory::loadClassFile( 'clOutputHtmlSorting' );
	$oSorting = new clOutputHtmlSorting( $oNews->oDao, array(
		'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('newsPublishStart' => 'DESC', 'newsCreated' => 'DESC') )
	) );
	$oNews->oDao->setEntries( 1 );
	$aObjectData = current( $oNews->aHelpers['oJournalHelper']->read( 'newsId' ) );

	if( !empty($aObjectData) ) {
		$_GET['newsId'] = $aObjectData['newsId'];
	}
}

if( !empty($_GET['newsId']) ) {
	$aNews = $oNews->aHelpers['oJournalHelper']->read( 'newsId', $_GET['newsId'] );

	if( !empty($aNews) ) {
		$aNews = current( $oNews->read( '*', arrayToSingle($aNews, null, 'newsId') ) );

		$oTemplate->setKeywords( $aNews['newsMetaKeywords'] );
		$oTemplate->setDescription( $aNews['newsMetaDescription'] );

		$aNews['newsDate'] = !empty($aNews['newsPublishStart']) ? date( $sDateFormat, strtotime($aNews['newsPublishStart']) ) : date( $sDateFormat, strtotime($aNews['newsCreated']) );

		// Set page title to the news title if set
		if(!empty($aNews['newsTitleTextId'])) {
			$oTemplate = clRegistry::get( 'clTemplateHtml' );
			$oTemplate->setTitle( $aNews['newsTitleTextId'] );
		}

		// Images
		$sImage = '';
		if( NEWS_WITH_IMAGE === true ) {
			$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
			$oImage->setParams( array(
				'parentType' => $oNews->sModuleName,
			) );

			$aNewsImage = valueToKey( 'imageParentId', $oImage->readByParent( $aNews['newsId'], array(
				'imageId',
				'imageFileExtension',
				'imageAlternativeText',
				'imageParentId',
				'imageParentType'
			) ) );

			if( !empty($aNewsImage[$aNews['newsId']]) ) {
				$sImagePath = '/images/custom/' . $aNewsImage[$aNews['newsId']]['imageParentType'] . '/' . $aNewsImage[$aNews['newsId']]['imageId'] . '.' . $aNewsImage[$aNews['newsId']]['imageFileExtension'];
				$sImage = '<img src="' . $sImagePath . '" alt="' . htmlspecialchars( $aNewsImage[$aNews['newsId']]['imageAlternativeText'] ) . '" />';
			}
			$sImage = '<div class="image">' . $sImage . '</div>'; // Wrap the image. Note: You may, if you want, target a "non uploaded image" with CSS selectors using `.image:empty`
		}

		echo '
			<article class="view news show">
				<header>
					' . $sImage . '
					<div class="title"><h1>' . $aNews['newsTitleTextId'] . '</h1>
					<time datetime="' . $aNews['newsDate'] . '" class="created">' . $aNews['newsDate'] . '</time>
				</header>
				<div class="summary">' . $aNews['newsSummaryTextId'] . '</div>
				<div class="content">' . $aNews['newsContentTextId'] . '</div>
			</article>';

	} else {
		$oRouter->redirect( '/' );
	}
}
