<?php

$aErr = array();

$oNews = clRegistry::get( 'clNews', PATH_MODULE . '/news/models' );
$oRouter = clRegistry::get( 'clRouter' );

$oNews->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

if( NEWS_WITH_IMAGE === true ) {
	/**
	 * Images
	 */
	$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );	
	$oImage->setParams( array(
		'parentType' => $oNews->sModuleName,
		'watermark' => $GLOBALS['newsImageWatermark'],
		'maxWidth' => NEWS_IMAGE_MAX_WIDTH,
		'maxHeight' => NEWS_IMAGE_MAX_HEIGHT,
		'tnMaxWidth' => NEWS_IMAGE_TN_MAX_WIDTH,
		'tnMaxHeight' => NEWS_IMAGE_TN_MAX_HEIGHT,
		'crop' => NEWS_IMAGE_CROP
	) );
}

/**
 * Post
 */
if( !empty($_POST['frmNewsAdd']) ) {	
	/**
	 * Update
	 */
	if( !empty($_GET['newsId']) && ctype_digit($_GET['newsId']) ) {
		$oNews->update( $_GET['newsId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateNews' );
		if( empty($aErr) ) {
			$iNewsId = $_GET['newsId'];
			
			/**
			 * Route
			 */
			if( isset($_POST['routePathUpdate']) && $_POST['routePathUpdate'] == 'yes' ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );
				$sPathByTitle = strToUrl( $oRouter->getPath('guestNews') . '/' . $_POST['newsTitleTextId'] . '/' . $_GET['newsId'] );
				
				if( empty($_POST['routePath']) || $_POST['routePathAuto'] == 'yes' ) {
					$_POST['routePath'] = $sPathByTitle;
				}
				if( $oRouter->updateRouteToObject( $_GET['newsId'], $oNews->sModuleName, $_POST['routePath'], 'guestNews' ) === false ) {
					// Found no route, create one instead
					if( $oRouter->createRouteToObject( $iNewsId, $oNews->sModuleName, $_POST['routePath'], 'guestNews' ) ) {
						
					} else {
						$oNotification = clRegistry::get( 'clNotificationHandler' );
						$oNotification->set( array(
							'dataError' => _( 'Problem with updating route' )
						) );
					}
				}
			}
		}

	/**
	 * Create
	 */
	} else {
		$iNewsId = $oNews->create($_POST);
		$aErr = clErrorHandler::getValidationError( 'createNews' );
		if( empty($aErr) ) {
			/**
			 * Route
			 */
			if( empty($_POST['routePath']) ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );
				$_POST['routePath'] = strToUrl( $oRouter->getPath('guestNews') . '/' . $_POST['newsTitleTextId'] . '/' . $iNewsId );
			}
			if( $oRouter->createRouteToObject( $iNewsId, $oNews->sModuleName, $_POST['routePath'], 'guestNews' ) ) {

			} else {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataError' => _( 'Problem with creating route' )
				) );
			}
		}
	}

	/**
	 * Image
	 */
	if( NEWS_WITH_IMAGE === true ) {
		if( empty($aErr) && !empty($_FILES['newsImage']) && !empty($iNewsId) ) {
			$aErr = $oImage->createWithUpload( array(
				'allowedMime' => array(
					'image/jpeg' => 'jpg',
					'image/pjpeg' => 'jpg',
					'image/gif' => 'gif',
					'image/png' => 'png',
					'image/x-png' => 'png'
				),
				'key' => 'newsImage'
			), $iNewsId );
		}
	}

	if( empty($aErr) && empty($_GET['newsId']) ) {
		$oRouter->redirect( $oRouter->sPath . '?newsId=' . $iNewsId );
	}
}

$sNewsImage = '';

/**
 * Edit
 */
if( !empty($_GET['newsId']) && ctype_digit($_GET['newsId']) ) {
	$aNewsData = current( $oNews->read( array(
		'newsId',
		'newsStatus',
		'newsPublishStart',
		'newsPublishEnd',
		'newsCreated',
		'newsTitleTextId',
		'newsSummaryTextId',
		'newsContentTextId',
		'newsMetaKeywords',
		'newsMetaDescription',
		'routePath'
	), $_GET['newsId']) );

	if( empty($aNewsData) ) {
		// Read none language specific data, for when
		// translating a yet not translated entry.
		$aNewsData = current( $oNews->read( array(
			'newsId',
			'newsStatus',
			'newsPublishStart',
			'newsPublishEnd',
			'newsCreated',
			'newsMetaKeywords',
			'newsMetaDescription'
		), $_GET['newsId']) );
	}

	// Image
	if( NEWS_WITH_IMAGE === true ) {
		$aNewsImage = current( $oImage->readByParent( $_GET['newsId'], array(
			'imageId',
			'imageFileExtension',
			'imageParentId',
			'imageParentType'
		) ) );
		if( !empty($aNewsImage) ) {
			$sNewsImage = '
				<div class="newsImage">
					<img src="/images/custom/' . $aNewsImage['imageParentType'] . '/tn/' . $aNewsImage['imageId'] . '.' . $aNewsImage['imageFileExtension'] . '" alt="" /><br />
					<a href="' . $oRouter->sPath . '?event=deleteImage&amp;deleteImage=' . $aNewsImage['imageId'] . '&amp;' . stripGetStr( array('event', 'imageCreateThumbnail', 'deleteImage') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>
				</div>';
		}
	}

	$sTitle = _( 'Edit news' );
	
/**
 * New
 */
} else {	
	$aNewsData = $_POST;
	$sTitle = _( 'Create news' );
}

/**
 * Form
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oNews->oDao->getDataDict(), array(
	'attributes'	=> array(
		'class'	=> 'marginal'
	),
	'data' => $aNewsData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$aNewsFormDict = array(
	'newsTitleTextId' => array(
		'attributes' => array(
			'class' => 'text charCounter'
		)
	),
	'routePath' => array(
		'type' => 'string',
		'title' => _( 'Path' ),
		'labelSuffixContent' => SITE_DOMAIN,
		'attributes' => array(
			'readonly' => 'readonly',
			'class' => 'text autoFill'
		),
		'fieldAttributes' => array(
			'class' => 'fieldRoutePath'
		),
		'suffixContent' => '<a href="#" id="changeRoutePath">' . _( 'Enter the path manually' ) . '</a>'
	),
	'routePathUpdate' => array(
		'type' => 'boolean',
		'values' => array(
			'yes' => _( 'Yes' ),
			'no' => _( 'No' )			
		),
		'title' => _( 'Update route path' )
	),
	'routePathAuto' => array(
		'type' => 'hidden',
		'value' => 'yes'
	),
	'newsSummaryTextId' => array(
		'type' => 'string',
		'appearance' => 'full'
	),
	'newsContentTextId' => array(
		'type' => 'string',
		'appearance' => 'full',
		'attributes' => array(
			'class' => 'editor'
		)
	),
	'newsMetaKeywords' => array(
		'type' => 'string',
		'title' => _( 'Keywords' ),
		'attributes' => array(
			'class' => 'text charCounter'
		)
	),
	'newsMetaDescription' => array(
		'type' => 'string',
		'title' => _( 'Description' ),
		'attributes' => array(
			'class' => 'text charCounter'
		)
	),
	'newsStatus' => array(),
	'newsPublishStart' => array(
		'attributes' => array(
			'class' => 'datetimepicker text'
		)
	),
	'newsPublishEnd' => array(
		'attributes' => array(
			'class' => 'datetimepicker text'
		)
	),
	'frmNewsAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
);
if( NEWS_WITH_IMAGE === true ) {
	$aNewsFormDict['newsImage'] = array(
		'type' => 'upload',
		'attributes' => array(
			'accept' => 'jpg|jpeg|gif|png',
			'id' => 'newsImageUploader'
		),
		'title' => _( 'Picture' ),
		'suffixContent' => !empty($sNewsImage) ? '<br />' . $sNewsImage : ''
	);
}

$aNewsFormGroupDict = array(
	'layout' => array(
		'title' => _( 'Settings' ),
		'fields' => array(
			'newsTitleTextId',
			'newsSummaryTextId',
			'routePath',
			'routePathUpdate',
			'newsStatus',
			'newsPublishStart',
			'newsPublishEnd'
		)
	),
	'editor' => array(
		'title' => '',
		'fields' => array(
			'newsContentTextId'
		)
	),
	'metadata' => array(
		'title' => 'Metadata',
		'fields' => array(
			'newsMetaKeywords',
			'newsMetaDescription'
		)
	)
);
if( NEWS_WITH_IMAGE === true ) {
	array_push( $aNewsFormGroupDict['layout']['fields'], 'newsImage' );
}

if( empty($_GET['newsId']) ) {
	unset(
		$aNewsFormDict['routePathUpdate'],
		$aNewsFormGroupDict['layout']['fields']['routePathUpdate']
	);
}

$oOutputHtmlForm->setFormDataDict( $aNewsFormDict );
$oOutputHtmlForm->setGroups( $aNewsFormGroupDict );

echo '
	<div class="view news formAdd">
		<h1>' . $sTitle . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $oRouter->getPath('adminNewsAdd')  . '" class="icon iconAdd iconText">' . _( 'Write new news' ) . '</a>
			</div>
		</section>
		' . $oOutputHtmlForm->render() . '
	</div>';

$oNews->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );

$oTemplate->addScript( array(
	'key' => 'jsTinyMce',
	'src' => '/modules/tinymce/tiny_mce.js'
) );
$oTemplate->addScript( array(
	'key' => 'jsTinyMceConfig',
	'src' => '/modules/tinymce/config/basic.js.php'
) );

if( empty($_GET['news']) ) {
	$oTemplate->addBottom( array(
		'key' => 'autoRoutePath1',
		'content' => '
			<script>
				$("#newsTitleTextId").keyup( function() {
					var sValue = $("input#routePath").val();
						if( $("input#routePath").attr("readonly") == "readonly" && sValue != "' . $oRouter->getPath( 'guestNews' ) . '" ) {
						var sContent = $(this).val();
						sContent = strToUrl( "' . $oRouter->getPath( 'guestNews' ) . '/" + sContent );
						$("input#routePath").val(sContent);
						$("input#routePath").trigger("update");
					}
				} );
				$(document).ready( function() {
					if( $("input#routePath").val() == "" ) {
						$("input#routePath").val( "' . $oRouter->getPath( 'guestNews' ) . '/" )
					}
				} );
			</script>'
	) );
}
$oTemplate->addBottom( array(
	'key' => 'autoRoutePath2',
	'content' => '
		<script>
			$("#changeRoutePath").click(function() {
				if( $("input#routePath").attr("readonly") == "readonly" ) {
					$("input#routePath").removeAttr("readonly");

					$("input#routePath").removeClass("autoFill");

					$("#changeRoutePath").html("' . _( 'Auto fill path' ) . '");
					$("input#routePath").trigger("update");
					
					$("#routePathAuto").val("no");

				} else {
					var sContent = strToUrl( "' . $oRouter->getPath('guestNews') . '/" + $("#newsTitleTextId").val().toLowerCase().replace(/ /g,"-") );					
					$("input#routePath").val(sContent);

					$("input#routePath").addClass("autoFill");

					$("input#routePath").attr("readonly", "readonly");
					$("#changeRoutePath").html("' . _( 'Enter the path manually' ) . '");
					$("input#routePath").trigger("update");
					
					$("#routePathAuto").val("yes");
				}
			});
		</script>'
) );








