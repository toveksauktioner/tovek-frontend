<?php

require_once( PATH_FUNCTION . '/fData.php' );

$oNews = clRegistry::get( 'clNews', PATH_MODULE . '/news/models' );

// Images
if( NEWS_WITH_IMAGE === true ) {
	$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
	$oImage->setParams( array(
		'parentType' => $oNews->sModuleName,
	) );
}

// Sorting
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oNews->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array(
		'GREATEST(newsPublishStart, newsCreated)' => 'DESC'
	) )
) );
$oSorting->setSortingDataDict( array(
	'newsTitleTextId' => array(),
	'newsPublishStart' => array(
		'title' => _( 'Published' )
	)
) );

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oNews->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 5
) );

// Settings
$oNews->oDao->aSorting = array(
	'GREATEST(newsPublishStart, newsCreated)' => 'DESC'
);
$sDateFormat = 'Y-m-d';

// Get published
$aNews = $oNews->aHelpers['oJournalHelper']->read( 'newsId' );

$sPagination = $oPagination->render();

$sNewsList = '';

if( !empty($aNews) ) {
	// Data
	$aNews = $oNews->read( array(
		'newsId',
		'newsTitleTextId',
		'newsSummaryTextId',
		'newsPublishStart',
		'newsCreated',
		'routePath'
	), arrayToSingle($aNews, null, 'newsId') );

	// Images
	if( NEWS_WITH_IMAGE === true ) {
		$aNewsImage = valueToKey( 'imageParentId', $oImage->readByParent( arrayToSingle($aNews, null, 'newsId'), array(
			'imageId',
			'imageFileExtension',
			'imageAlternativeText',
			'imageParentId',
			'imageParentType'
		) ) );
	}

	$iCount = 0;
	$iFirstKey = key($aNews);
	foreach( $aNews as $key => $entry ) {
		$sClass = '';
		if( $key == $iFirstKey ) $sClass = 'first';
		if( $iCount % 2 ) $sClass = 'odd';
		if( empty($aNews[$key+1]) ) $sClass = 'last';

		$entry['newsDate'] = !empty($entry['newsPublishStart']) ? date( $sDateFormat, strtotime($entry['newsPublishStart']) ) : date( $sDateFormat, strtotime($entry['newsCreated']) );

		$sImage = '';
		if( NEWS_WITH_IMAGE === true ) {
			if( !empty($aNewsImage[$entry['newsId']]) ) {
				$sImagePath = '/images/custom/' . $aNewsImage[$entry['newsId']]['imageParentType'] . '/' . $aNewsImage[$entry['newsId']]['imageId'] . '.' . $aNewsImage[$entry['newsId']]['imageFileExtension'];
				$sImage = '<img src="' . $sImagePath . '" alt="' . htmlspecialchars( $aNewsImage[$entry['newsId']]['imageAlternativeText'] ) . '" />';
			}
			$sImage = '<div class="image">' . $sImage . '</div>'; // Wrap the image. Note: You may, if you want, target a "non uploaded image" with CSS selectors using `.image:empty`
		}

		$sNewsList .= '
			<li' . (!empty($sClass) ? ' class="' . $sClass . '"' : '') . '>
				<article>
					<header>
						<a href="' . $entry['routePath'] . (!empty($_GET['page']) ? '?page=' . $_GET['page'] : '') . '"><h3>' . $entry['newsTitleTextId'] . '</h3></a>
						<time datetime="' . $entry['newsDate'] . '" class="created">' . $entry['newsDate'] . '</time>
					</header>
					<a href="' . $entry['routePath'] . (!empty($_GET['page']) ? '?page=' . $_GET['page'] : '') . '">' . $sImage . '</a>
					<p class="summary content">' . $entry['newsSummaryTextId'] . '</p>
					<a href="' . $entry['routePath'] . (!empty($_GET['page']) ? '?page=' . $_GET['page'] : '') . '" class="more">' . _( 'Read more' ) . '</a>
				</article>
			</li>
		';

		++$iCount;
	}

} else {
	$sNewsList = '
		<li>
			<strong>' . _('There are no items to show') . '</strong>
		</li>
	';
}

// Routes
$sNewsShowPath = $oRouter->getPath( 'guestNewsListAll' );

echo '
	<div class="view news listAll">
		<h2>' . _( 'News archive' ) . '</h2>
		<ul>
			' . $sNewsList . '
		</ul>
		' . $sPagination . '
	</div>
';
