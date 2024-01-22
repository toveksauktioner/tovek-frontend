<?php

require_once PATH_FUNCTION . '/fData.php';

$oRouter = clRegistry::get( 'clRouter' );

/**
 * Settings
 */
$bNavigation  		 = true;
$bInfoContent 		 = true;
$bNews 				 = !file_exists(PATH_MODULE . '/news') ? false : true;
$bProducts 			 = !file_exists(PATH_MODULE . '/product') ? false : true;
$bProductCategories  = !file_exists(PATH_MODULE . '/product') ? false : true;
$bFaq  				 = !file_exists(PATH_MODULE . '/faq') ? false : true;
$bDuplicateCheck 	 = true;

$sOutput = '';
$aDuplicates = array();

/**
 * Navigation
 */
if( $bNavigation === true ) {
	$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
	$oNavigation->setGroupKey( 'guest' );

	$aTree = $oNavigation->read( array(
		'navigationId',
		'navigationTitle',
		'navigationUrl',
		'navigationLeft',
		'navigationRight'
	) );

	if( !empty($aTree) ) {

		$sOutput .= '
			<div class="navigation">
				<h2>' . _( 'Navigation' ) . '</h2>';

		$iPreviousDepth = 0;
		$sOutput .= '
				<ul>';
		foreach( $aTree as $key => $entry ) {
			$aClass = array();
			if( $key === key($aTree) ) $aClass[] = 'first';

			if( $entry['depth'] > $iPreviousDepth ) {
				$sOutput .= '
					<ul>';
			} elseif(  $entry['depth'] < $iPreviousDepth  ) {
				echo str_repeat( '
					</ul>
					</li>', $iPreviousDepth - $entry['depth'] );
			}
			if( !empty($aTree[($key + 1)]) && $aTree[$key + 1]['depth'] > $entry['depth'] ) $aClass[] = 'subTree';

			$sOutput .= '
					<li' . ( !empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . '><a href="' . $entry['navigationUrl'] .'" class="ajax" rel="tplMain">' . $entry['navigationTitle'] . '</a>';

			echo ($entry['navigationRight'] - $entry['navigationLeft']) === 1 ? '</li>' : '';
			$iPreviousDepth = $entry['depth'];

			$aDuplicates[] = $entry['navigationUrl'];
		}
		echo str_repeat( '
					</ul>
					</li>', $iPreviousDepth );
		$sOutput .= '
				</ul>
			</div>';
	}
}

/**
 * InfoContent
 */
if( $bInfoContent === true ) {
	$oLayout = clRegistry::get( 'clLayoutHtml' );
	$aCustomLayouts = $oLayout->readCustom( array(
		'layoutKey',
		'layoutTitleTextId'
	) );

	if( !empty($aCustomLayouts) ) {
		$aInfoContentLayouts = arrayToSingle($aCustomLayouts, null, 'layoutKey');

		$aRoutesData = $oRouter->getPath( (array) arrayToSingle( $aCustomLayouts, null, 'layoutKey') );
		$aRoutes = arrayToSingle($aRoutesData, 'routeLayoutKey', 'routePath');

		$aLayoutToLayoutData = array();
		$aViewIds = array();
		foreach( $aInfoContentLayouts as $sLayout) {
			$aTmpLayoutData = $oLayout->readSectionsAndViews($sLayout);
			foreach( $aTmpLayoutData as $entry ) {
				$aLayoutToLayoutData[$sLayout][] = $entry['viewId'];
				$aViewIds[] = $entry['viewId'];
			}
		}

		$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
		$aContentStatus = arrayToSingle( $oInfoContent->readByView( $aViewIds, array(
			'contentViewId',
			'contentStatus'
		) ), 'contentViewId', 'contentStatus' );

		$sOutput .= '
			<div class="infoContent">
				<h2>' . _( 'Pages' ) . '</h2>
				<ul>';

		foreach( $aCustomLayouts as $entry ) {
			if( !isset( $aRoutes[ $entry['layoutKey'] ] ) ) continue; // Skip if not found. May happen if site uses multiple languages
			if( $bDuplicateCheck === true && in_array($aRoutes[ $entry['layoutKey'] ], $aDuplicates) ) continue;

			foreach( $aLayoutToLayoutData[$entry['layoutKey']] as $iViewId ) {
				if( !empty($aContentStatus[$iViewId]) && $aContentStatus[$iViewId] == 'inactive' ) {
					continue 2;
				}
			}

			$sOutput .= '
					<li>
						<a href="' . $aRoutes[ $entry['layoutKey'] ] . '">' . htmlspecialchars($entry['layoutTitleTextId']) . '</a>
					</li>';

			$aDuplicates[] = $aRoutes[ $entry['layoutKey'] ];
		}
		$sOutput .= '
				</ul>
			</div>';
	}
}

/**
 * News
 */
if( $bNews === true ) {
	$oNews = clRegistry::get( 'clNews', PATH_MODULE . '/news/models' );
	
	$aPublishedNews = arrayToSingle( $oNews->aHelpers['oJournalHelper']->read( array('newsId') ), null, 'newsId' );
	
	if( !empty($aPublishedNews) ) {
		$aNews = $oNews->read( array(
			'newsId',
			'newsTitleTextId',
			'newsSummaryTextId',
			'newsPublishStart',
			'newsCreated',
			'routePath'
		), $aPublishedNews );
	}
	
	if( !empty($aNews) ) {
		$sOutput .= '
			<div class="navigation">
				<h2>' . _( 'News' ) . '</h2>
				<ul>';
				
		foreach( $aNews as $aEntry ) {
			$sOutput .= '
					<li>
						<a href="' . $aEntry['routePath'] . '">' . $aEntry['newsTitleTextId'] . '</a>
					</li>';			
		}
		
		$sOutput .= '
				</ul>
			</div>';
	}
}

/**
 * Product and Product categories
 */
if( $bProductCategories === true ) {
	$sCategoryList = '';

	$oCategory = clRegistry::get( 'clProductCategory', PATH_MODULE . '/product/models' );

	if( empty($aCategoriesData) ) {
		$aCategoriesData = $oCategory->read( array(
			'categoryId',
			'categoryTitleTextId' => 'categoryTitleTextId',
			'categoryLeft',
			'categoryRight',
			'routePath'
		) );
	}

	if( !empty($aCategoriesData) ) {
		if( $bProducts === true ) {
			$oProductTemplate = clRegistry::get( 'clProductTemplate', PATH_MODULE . '/product/models' );
		}

		if( $aCategories = $oCategory->readByRoute($oRouter->iCurrentRouteId, 'categoryId') ) {
			$iCategoryId = (int) current( current($aCategories) );
		}

		$iPreviousDepth = 0;
		$sCategoryList .= '
			<ul>';
		foreach( $aCategoriesData as $entry ) {
			$aClass = array();
			if( $entry['depth'] > $iPreviousDepth ) {
				$sCategoryList .= '
				<ul>';
			} elseif(  $entry['depth'] < $iPreviousDepth  ) {
				$sCategoryList .= str_repeat( '
				</ul>
				</li>', $iPreviousDepth - $entry['depth'] );
			}


			$sCategoryList .= '
				<li' . ( !empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . '><a href="' . htmlspecialchars( $entry['routePath'] ) . '" class="ajax' . ( (!empty($_GET['categoryId']) && $_GET['categoryId'] == $entry['categoryId']) ? ' active"' : '' ) . '" rel="tplMain">' . htmlspecialchars( $entry['categoryTitleTextId'] ) . '</a>';

			if( $bProducts === true ) {
				$aProducts = $oProductTemplate->readByCategory( $entry['categoryId'], array(
					'templateTitleTextId',
					'categoryId',
					'routePath'
				) );

				$sCategoryList .= '
				<ul>';
				foreach( $aProducts as $aProduct ) {
					$sCategoryList .= '
					<li>
						<a href="' . $aProduct['routePath'] . '">' . htmlspecialchars($aProduct['templateTitleTextId']) . '</a>
					</li>';
				}
				$sCategoryList .= '
				</ul>';
			}

			$sCategoryList .= ($entry['categoryRight'] - $entry['categoryLeft']) === 1 ? '</li>' : '';
			$iPreviousDepth = $entry['depth'];
		}
		$sCategoryList .= str_repeat( '
				</ul>
				</li>', $iPreviousDepth );
		$sCategoryList .= '
			</ul>';
	} else {
		$sCategoryList .= '
			<strong>' . 'Det finns inga kategorier' . '</strong>';
	}

	$sOutput .= '
		<div class="products">
			<h2>' . ( ( $bProducts === true ) ? _( 'Products' ) : _( 'Product categories' ) ) . '</h2>
			' . $sCategoryList . '
		</div>';
}

/**
 * FAQ
 */
if( $bFaq === true ) {
	$oFaqCategory = clRegistry::get( 'clFaqCategory', PATH_MODULE . '/faq/models' );
	$oFaqQuestion = clRegistry::get( 'clFaqQuestion', PATH_MODULE . '/faq/models' );
	
	/**
	 * Read fields
	 */
	$aCategoryReadFields = array_keys( current( $oFaqCategory->oDao->getDataDict() ) );
	$aQuestionReadFields = array_keys( current( $oFaqQuestion->oDao->getDataDict() ) );
	$aCategoryReadFields[] = 'routePath';
	$aQuestionReadFields[] = 'routePath';
	
	/**
	 * Parent data
	 */
	$oFaqCategory->oDao->aSorting = array(
		'categorySort' => 'ASC'
	);
	$aCategories = $oFaqCategory->read( $aCategoryReadFields );
	
	$sList = '';
	
	if( !empty($aCategories) ) {
		foreach( $aCategories as $aCategory ) {			
			$aQuestions = $oFaqQuestion->readByCategory( $aCategory['categoryId'], $aQuestionReadFields );
			
			$sList .= '
				<li><a href="' . $aCategory['routePath'] . '">' . $aCategory['categoryTitleTextId'] . '</a>';
			
			if( !empty($aQuestions) ) {
				$sList .= '
					<ul>';
				
				foreach( $aQuestions as $aQuestion ) {
					$aRouteData = current( $oRouter->readByObject( $aQuestion['questionId'], 'faqQuestion', array('entRoute.routeId','routePath') ) );			
					if( empty($aRouteData) ) continue;
					
					$sList .= '
						<li><a href="' . $aRouteData['routePath'] . '">' . $aQuestion['questionTitleTextId'] . '</a></li>';
				}
				
				$sList .= '
					</ul>';
			}
			
			$sList .= '
				</li>';	
		}
	}
	
	if( !empty($sList) ) {
		$sOutput .= '
			<div class="faq">
				<h2>' . _( 'FAQ' ) . '</h2>
				<ul>' . $sList . '</ul>
			</div>';
	}
}

/**
 * Output
 */
echo '
	<div class="view sitemap">
		<h1>' . _( 'Sitemap for' ) . ' ' . SITE_DOMAIN . '</h1>
		' . $sOutput . '
		<div class="description">
			<p><strong>Definition: Sitemap / Sajtkarta</strong></p>
			<p>En sitemap/sajtkarta är en karta över alla sidor som finns på webbplatsen. Den hjälper sökmotorerna förstå vilka sidor som finns på en domän och hur hemsidans struktur är uppbyggd. Vid sökmotoroptimering är det viktigt med länkar och en innehållsrik sajtkarta med relevanta sökord ökar möjligheten att placera viktiga sökord högt bland sökresultaten.</p>
			<div class="argonova">
				<p><a href="http://www.argonova.se/">Argonova Systems är en webbyrå som jobbar med webbdesign, webbstrategi och</a> <a href="http://www.argonova.se/marknadsfoering/soekmotorer/optimering/soekmotoroptimering.html">sökmotoroptimering</a>.</p>
			</div>
		</div>
	</div>';
