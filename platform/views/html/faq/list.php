<?php

$oFaqCategory = clRegistry::get('clFaqCategory', PATH_MODULE . '/faq/models' );
$oFaqQuestion = clRegistry::get('clFaqQuestion', PATH_MODULE . '/faq/models' );

$aActive = array(
	'category' => null,
	'question' => null
);

/**
 * Entry by route check
 */
$aRouteData = current( $oRouter->readObjectByRoute( $oRouter->iCurrentRouteId ) );
if( !empty($aRouteData) ) {
	if( $aRouteData['objectType'] == 'FaqQuestion' ) {
		// Active question
		$aActive['question'] = $aRouteData['objectId'];
	} else {
		// Active category
		$aActive['category'] = $aRouteData['objectId'];
	}
}

/**
 * Read fields
 */
$aCategoryReadFields = array_keys( current( $oFaqCategory->oDao->getDataDict() ) );
$aQuestionReadFields = array_keys( current( $oFaqQuestion->oDao->getDataDict() ) );
$aCategoryReadFields[] = 'routePath';
$aQuestionReadFields[] = 'routePath';

/**
 * Data
 */
$aCategories = $oFaqCategory->read( $aCategoryReadFields, $aActive['category'] );
if( !empty($aActive['category']) ) {
	$aQuestions = $oFaqQuestion->readByCategory( $aActive['category'], $aQuestionReadFields );
} else {
	$aQuestions = $oFaqQuestion->read( $aQuestionReadFields );
}

$aFaqs = array();
$sCanonicalUrl = null;
$sOutput = '';

if( !empty($aCategories) ) {
	$aQuestionByCategory = groupByValue( 'questionCategoryId', $aQuestions );
	
	foreach( $aCategories as $aCategory ) {
		$sFaq = '';
		$aCategoryClass = array();
		
		if( $aCategory['categoryId'] == $aActive['category'] ) $aCategoryClass[] = 'open';
		
		if( !empty($aQuestionByCategory[ $aCategory['categoryId'] ]) ) {
			$aQuestionList = array();
			
			foreach( $aQuestionByCategory[ $aCategory['categoryId'] ] as $aQuestion ) {
				$aQuestionClass = array();
				
				if( $aQuestion['questionId'] == $aActive['question'] ) {
					$aQuestionClass[] = 'open';
				}
				
				$aQuestionList[] = '
					<li' . (!empty($aQuestionClass)  ? ' class="' . implode(' ', $aQuestionClass) . '"' : '') . '>
						<a href="' . $aQuestion['routePath'] . '" class="ajax">
							' . $aQuestion['questionTitleTextId'] . '
						</a>
						' . (in_array('open', $aQuestionClass) ? '
							<div class="questionAnswerTextId">
								' . nl2br($aQuestion['questionAnswerTextId']) . '
							</div>
						' : '') . '
					</li>';
			}
			
			$sFaq = '
				<h2>' . $aCategory['categoryTitleTextId'] . '</h2>
				<ul>
					' . implode( "\r\n", $aQuestionList ) . '
				</ul>';
			
		} else {
			$sFaq = _( 'No questions' );
		}
		
		
		$aFaqs[] = '
			<article' . (!empty($aCategoryClass)  ? ' class="' . implode(' ', $aCategoryClass) . '"' : '') . '>
				' . $sFaq . '
			</article>';
	}
	
	$sOutput = implode("\r\n", $aFaqs);
	
} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view faq list">
		<h1>' . _( 'FAQ' ) . '</h1>
		' . $sOutput . '
	</div>';

/**
 * JavaScript based version
 */
//$oTemplate->addBottom( array(
//	'key' => 'faqJs',
//	'content' => '
//	<script>
//		$(document).delegate( ".view.faq.list a.ajax", "click", function(event) {
//			event.preventDefault();
//			var sUrl = $(this).attr("href") + "?ajax=true&view=faq/list.php";
//			var jqxhr = $.get( sUrl, function(data) {
//				$(".view.faq.list").replaceWith(data);
//			} )
//			.done( function() {
//				//console.log( "second success" );
//			} )
//			.fail( function() {
//				//console.log( "error" );
//			} )
//			.always( function() {
//				//console.log( "finished" );
//			} );
//		} );
//	</script>'
//) );

/**
 * Canonical URL
 */
if( $sCanonicalUrl !== null ) {
	// Canonical URL
	$oTemplate->addLink( array(
		'key' => 'canonicalURL',
		'href' => 'http://' . SITE_DOMAIN . $sCanonicalUrl,
		'rel' => 'canonical'
	) );
}

if( !empty($aCategories) ) {
	/**
	 * RobotsMeta
	 */
	$aRoutes = array( $oRouter->getPath( $oRouter->sCurrentLayoutKey ) );
	$oRouter2 = clFactory::create( 'clRouter' );
	$aRoutes = array_merge( $aRoutes, arrayToSingle( $oRouter2->readByObject( arrayToSingle($aCategories, null, 'categoryId'), 'faqCategory', array('entRoute.routeId','routePath') ), null, 'routePath' ) );
	if( in_array($oRouter->sPath, $aRoutes) ) {
		$oTemplate->addMeta( array(
			'key' => 'robotsMeta',
			'name' => 'robots',
			'content' => 'noindex, follow'
		) );	
	}
}