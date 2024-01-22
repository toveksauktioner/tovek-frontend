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

// Settings
$aSetting = array(
	'sort' => array(
		'GREATEST(newsPublishStart, newsCreated)' => 'DESC'
		//'newsPublishStart' => 'DESC',
		//'newsCreated' => 'DESC'
	),
	'entries' => 3,
	'readMore' => 'none', # link | merge | none
	'date' => array(
		'type' => 'digits', # digits | name
		'format' => 'Y-m-d', # 'date-format
		'formatIntl' => 'Y-MM-dd' # ICU-format
	),
	'archiveLayout' => 'guestNewsListAll'
);

// Set sort order and amount of entries
$oNews->oDao->aSorting = $aSetting['sort'];
$oNews->oDao->setEntries( $aSetting['entries'] );

// Get published
$aNews = $oNews->aHelpers['oJournalHelper']->read( 'newsId' );

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

	foreach( $aNews as $key => $entry ) {
		// Format date
		if( $aSetting['date']['type'] == 'digits' ) {
			$entry['newsDate'] = !empty($entry['newsPublishStart']) ? date( $aSetting['date']['format'], strtotime($entry['newsPublishStart']) ) : date( $aSetting['date']['format'], strtotime($entry['newsCreated']) );
		} else {
			$entry['newsDate'] = !empty($entry['newsPublishStart']) ? formatIntlDate( $aSetting['date']['formatIntl'], strtotime($entry['newsPublishStart']) ) : formatIntlDate( $aSetting['date']['formatIntl'], strtotime($entry['newsCreated']) );
		}

		// Image
		$sImage = '';
		if( NEWS_WITH_IMAGE === true ) {
			if( !empty($aNewsImage[$entry['newsId']]) ) {
				$sImagePath = '/images/custom/' . $aNewsImage[$entry['newsId']]['imageParentType'] . '/' . $aNewsImage[$entry['newsId']]['imageId'] . '.' . $aNewsImage[$entry['newsId']]['imageFileExtension'];
				$sImage = '<img src="' . $sImagePath . '" alt="' . htmlspecialchars( $aNewsImage[$entry['newsId']]['imageAlternativeText'] ) . '" />';
			}
			$sImage = '<div class="image">' . $sImage . '</div>'; // Wrap the image. Note: You may, if you want, target a "non uploaded image" with CSS selectors using `.image:empty`
		}

		// Summary fomrat
		$sSummary = '';
		switch( $aSetting['readMore'] ) {
			case 'link':
				$sSummary = '
					<p class="summary content">' . $entry['newsSummaryTextId'] . '</p>
					<a href="' . $entry['routePath'] . '" class="more">' . _( 'Read more' ) . '</a>';
				break;
			case 'merge':
				$sSummary = '
					<a href="' . $entry['routePath'] . '">
						<p class="summary content">' . $entry['newsSummaryTextId'] . '</p>
					</a>';
				break;
			case 'none': default:
				$sSummary = '
					<p class="summary content">' . $entry['newsSummaryTextId'] . '</p>';
				break;
		}

		// Assemble
		$sNewsList .= '
			<li>
				<article>
					<header>
						<a href="' . $entry['routePath'] . '"><h3>' . $entry['newsTitleTextId'] . '</h3></a>
						<time datetime="' . $entry['newsDate'] . '" class="created">' . $entry['newsDate'] . '</time>
					</header>
					<a href="' . $entry['routePath'] . '">' . $sImage . '</a>
					' . $sSummary . '
				</article>
			</li>';
	}

} else {
	$sNewsList = '
			<li>
				<strong>' . _('There are no items to show') . '</strong>
			</li>';
}

echo '
	<div class="view news list">
		<h2><a href="' . $oRouter->getPath( $aSetting['archiveLayout'] ) . '">' . _( 'News' ) . '</a></h2>
		<ul>
			' . $sNewsList . '
		</ul>
		<div class="meta">
			<a href="' . $oRouter->getPath( $aSetting['archiveLayout'] ) . '">' . _( 'News archive' ) . '</a>
		</div>
	</div>';
