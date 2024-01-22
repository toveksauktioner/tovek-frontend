<?php

$sOutput = '';
$sCategoriesOutput = '';
$sFormOutput = '';
$iOpenTopicWhenMax = 3;
$iMinCharForSearch = 3;

$oHelpTopic = clRegistry::get('clHelpTopic', PATH_MODULE . '/help/models' );
$oHelpCategory = clRegistry::get('clHelpCategory', PATH_MODULE . '/help/models' );

$oHelpTopic->oDao->setLang( $GLOBALS['langIdEdit'] );
$oHelpCategory->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

// Read all categories
$aCategoryList = $oHelpCategory->read();

// Fetch category or topic ids by route
$aRouteData = current( $oRouter->readObjectByRoute($oRouter->iCurrentRouteId) );
if( !empty($aRouteData) ) {
	if( $aRouteData['objectType'] == 'HelpCategory' ) {
		$_GET['c'] = $aRouteData['objectId'];
	}
	if( $aRouteData['objectType'] == 'HelpTopic' ) {
		$_GET['t'] = $aRouteData['objectId'];
	}
}

// Limit to category
if( !empty($_GET['c']) && ctype_digit($_GET['c']) ) {
	$oHelpTopic->oDao->setCriterias( array(
		'category' => array(
			'type' => '=',
			'value' => $_GET['c'],
			'fields' => 'entHelpTopicToCategory.helpCategoryId'
		)
	) );

} else if( !empty($_GET['t']) && ctype_digit($_GET['t']) ) {
	$oHelpTopic->oDao->setCriterias( array(
		'search' => array(
			'type' => '=',
			'value' => $_GET['t'],
			'fields' => 'entHelpTopic.helpTopicId'
		)
	) );
}

// Search

if( !empty($_GET['q']) && (strlen($_GET['q']) >= $iMinCharForSearch) ) {
	$oHelpTopic->oDao->setCriterias( array(
		'search' => array(
			'type' => 'like',
			'value' => $_GET['q'],
			'fields' => array(
				'text1.textContent',
				'text2.textContent'
			)
		)
	) );
}

// If there are criterias for limitation of questions - read data
if( !empty($oHelpTopic->oDao->sCriterias) ) {
	$aTopics = valueToKey( 'helpTopicId', $oHelpTopic->readWithCategory() );

	if( !empty($aTopics) ) {
		$iCount = count( $aTopics );

		foreach( $aTopics as $aTopic ) {
			$aClass = array();
			if( ($iCount <= $iOpenTopicWhenMax) || ( !empty($_GET['t']) && ($_GET['t'] == $aTopic['helpTopicId'])) ) {
				$aClass[] = 'open';
			}
			$sOutput .= '
				<article' . ( !empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . '>
					<a href="?t=' . $aTopic['helpTopicId'] . '">
						<i class="fas fa-chevron-right closed"></i>
						<i class="fas fa-chevron-down open"></i>
						' . $aTopic['helpTopicTitleTextId'] . '
					</a>
					<div class="description">
						' . $aTopic['helpTopicDescriptionTextId'] . '
					</div>
				</article>';
		}

	} else {
		$sOutput .= _( 'Det finns inga artiklar att visa. Sökningar måste innehålla minst tre tecken.' );
	}
} else if( isset($_GET['q']) ) {
	$sOutput .= _( 'Sökningar måste innehålla minst tre tecken.' );
} else {
	$sOutput .= _( 'Sök i formuläret ovan eller välj en kategori.' );
}


// List Categories
if( !empty($aCategoryList) ) {
	$sCategoriesOutput .= '
		<a href="#" class="header">
			<i class="fas fa-chevron-right closed"></i>
			<i class="fas fa-chevron-down open"></i>
			' . _( 'Kategorier' ) . '
		</a>';

	foreach( $aCategoryList as $aCategory ) {
		$aClass = array();

		if( !empty($_GET['c']) && ($_GET['c'] == $aCategory['helpCategoryId']) ) $aClass[] = 'selected';

		$sCategoriesOutput .= '
			<a href="?c=' . $aCategory['helpCategoryId'] . '" ' . ( !empty($aClass) ? 'class="' . implode(' ', $aClass) . '"' : '' ) . '>' . ( !empty($aCategory['helpCategoryIcon']) ? '<i class="' . $aCategory['helpCategoryIcon'] . '"></i>' : '' ) . $aCategory['helpCategoryTitleTextId'] . '</a>';
	}
}


// Search Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oHelpTopic->oDao->aDataDict, array(
	'attributes' => array('class' => 'newForm searchForm'),
	'data' => $_GET,
	'labelSuffix' => '',
  'placeholders' => false,
	'method' => 'get',
	'buttons' => array(
		'submit' => _( 'Sök' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'q' => array(
		'type' => 'string',
		'title' => _( 'Sök ämne' ),
		'fieldAttributes' => array(
			'class' => 'search'
		)
	),
	'ajaxSearch' => array(
		'type' => 'hidden',
		'value' => '1'
	)
) );
$sFormOutput = $oOutputHtmlForm->render();



if( !empty($_GET['ajaxSearch']) && (isset($_GET['c']) || isset($_GET['q'])) ) {
	// End ajax requests for categories or searches (not for regular popup request)
	echo $sOutput;
	exit;
}

echo '
	<div class="view help list">
		<div class="form">
			' . $sFormOutput . '
			<a href="' . /*$oRouter->getPath( 'guestHelpContact' ) .*/ '/om-våra-auktioner" class="button white" id="helpContactButton" data-size="full">' . _( 'Kontakta oss' ) . '</a>
		</div>
		<div class="categories ' . ( (empty($_GET['c']) && empty($_GET['q'])) ? 'open' : 'closed' ) . '">
			' . $sCategoriesOutput . '
		</div>
		<div class="topics">
			<div class="topicsContainer">
				' . $sOutput . '
			</div>
		</div>
	</div>
	<script>
		function searchForm() {
			var formObj = $(".view.help.list .searchForm");
			var url = formObj.attr( "action" );

			$(".view.help.list .topics .topicsContainer").load( url, formObj.serialize(), function() {
				$(".view.help.list .categories a").removeClass( "selected" );
				$(".view.help.list .categories").removeClass("open").addClass("closed");
			} );
		}

		$( document ).on( "click", ".view.help.list .categories > a", function(ev) {
			ev.preventDefault();

			var thisObj = $( this );

			if( thisObj.hasClass("header") ) {
				$(".view.help.list .categories").toggleClass("open closed");

			} else {
				var url = thisObj.attr( "href" ) + "&ajax=1&ajaxSearch=1&view=help/list.php";

				$(".view.help.list .topics .topicsContainer").load( url, function() {
					$(".view.help.list .categories a").removeClass( "selected" );
					thisObj.addClass( "selected" );
					$(".view.help.list .categories").removeClass("open").addClass("closed");
				} );
			}
		} );

		$( document ).on( "click", ".view.help.list .topics article > a", function(ev) {
			ev.preventDefault();

			$(".view.help.list .topics article").removeClass( "open" );
			$( this ).parent().addClass( "open" );
		} );

		$( document ).on( "submit", ".view.help.list .searchForm", function(ev) {
			ev.preventDefault();

			searchForm();
		} );
		$( document ).on( "keyup", ".view.help.list .searchForm input#q", function(ev) {
			searchForm();
		} );
	</script>';
