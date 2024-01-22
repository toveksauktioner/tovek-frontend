<?php

$aErr = array();

$oPuff = clRegistry::get( 'clPuff', PATH_MODULE . '/puff/models' );
$oPuff->oDao->setLang( $GLOBALS['langIdEdit'] );

/**
 * Images
 */
$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
$oImage->oDao->aSorting = array(
	'imageSort' => 'ASC',
	'imageCreated' => 'ASC'
);
$aImageParams = array(
	'parentType' => $oPuff->sModuleName
);

if( !empty($_POST['frmPuffAdd']) ) {
	if( empty($_POST['puffLayoutKey']) ) $_POST['puffLayoutKey'] = PUFF_LAYOUT_DEFAULT;

	// Update
	if( !empty($_GET['puffId']) && ctype_digit($_GET['puffId']) ) {
		$iPuffId = $_GET['puffId'];
		$oPuff->update( $iPuffId, $_POST );
		$aErr = clErrorHandler::getValidationError( 'updatePuff' );

	// Create
	} else {
		$iPuffId = $oPuff->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createPuff' );
	}

	$oUpload = clRegistry::get( 'clUpload' );

	// Image settings
	$sImageLayoutKey = !empty($GLOBALS['puffImageSettings'][ $_POST['puffLayoutKey'] ]) ? $_POST['puffLayoutKey'] : PUFF_LAYOUT_DEFAULT;
	$aImageParams += $GLOBALS['puffImageSettings'][ $sImageLayoutKey ];
	$oImage->setParams( $aImageParams );

	// Images
	if( empty($aErr) && !empty($_FILES['puffImages']) && !empty($iPuffId) ) {
		$aErr = $oImage->createWithUpload( array(
			'additionalThumbnails' => $aImageParams['extraThumbnails'],
			'allowedMime' => array(
				'image/jpeg' => 'jpg',
				'image/pjpeg' => 'jpg',
				'image/gif' => 'gif',
				'image/png' => 'png',
				'image/x-png' => 'png'
			),
			'key' => 'puffImages'
		), $iPuffId );
	}

	if( empty($aErr) && empty($_GET['puffId']) ) {
		$oRouter->redirect( $oRouter->sPath . '?puffId=' . $iPuffId );
	}
}

// Edit
if( !empty($_GET['puffId']) && ctype_digit($_GET['puffId']) ) {
	$sTitle = _( 'Edit puff' );

	// Data
	$aPuffData = current( $oPuff->read( array(
		'puffId',
		'puffLayoutKey',
		'puffTitleTextId',
		'puffClass',
		'puffContentTextId',
		'puffShortContentTextId',
		'puffUrlTextId',
		'puffStatus',
		'puffPublishStart',
		'puffPublishEnd',
		'puffUserType',
		'puffSort'
	), $_GET['puffId']) );

	// Image settings
	$sImageLayoutKey = !empty($GLOBALS['puffImageSettings'][ $aPuffData['puffLayoutKey'] ]) ? $aPuffData['puffLayoutKey'] : PUFF_LAYOUT_DEFAULT;
	$aImageParams += $GLOBALS['puffImageSettings'][ $sImageLayoutKey ];
	$oImage->setParams( $aImageParams );

	$sMainImageWidth = $aImageParams['maxWidth'];
	$sMainImageHeight = $aImageParams['maxHeight'];

	// Images
	$aPuffImages = $oImage->readByParent( $_GET['puffId'], array(
		'imageId',
		'imageFileExtension',
		'imageParentId',
		'imageParentType'
	) );
	if( !empty($aPuffImages) ) {
		$sPuffImages = '
			<h3>' . _( 'Images' ) . '</h3>
			<em>' . _( 'Drag and drop on icon to reorder images' ) . '</em>
			<ul class="puffImages">';

		$iCount = 1;
		foreach( $aPuffImages as $entry ) {
			$sPuffImages .= '
				<li id="sortImage-' . $entry['imageId'] . '"' . ($iCount == 1 ? ' class="first"' : '') . '>
					<a href="/images/custom/' . $entry['imageParentType'] . '/' . $entry['imageId'] . '.' . $entry['imageFileExtension'] . '" class="colorbox">
						<img src="/images/custom/' . $entry['imageParentType'] . '/tn/' . $entry['imageId'] . '.' . $entry['imageFileExtension'] . '" alt="" />
					</a><br />
					<a href="' . $oRouter->sPath . '?event=deleteImage&amp;deleteImage=' . $entry['imageId'] . '&amp;' . stripGetStr( array('event', 'imageCreateThumbnail', 'deleteImage') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>
					<span class="ui-icon ui-icon-arrow-4"></span>
				</li>';

			++$iCount;
		}

		$sPuffImages .= '
			</ul>';
	}

// New, but use template
} elseif( !empty($_GET['useContent']) && $_GET['useContent'] == 'true' && !empty($_GET['usePuffId']) ) {
	// Data as template
	$aPuffData = current( $oPuff->read( array(
		'puffId',
		'puffLayoutKey',
		'puffTitleTextId',
		'puffClass',
		'puffContentTextId',
		'puffShortContentTextId',
		'puffUrlTextId',
		'puffStatus',
		'puffPublishStart',
		'puffPublishEnd',
		'puffUserType',
		'puffSort'
	), $_GET['usePuffId']) );

	if( !empty($aPuffData) ) {
		// Image settings
		$sImageLayoutKey = !empty($GLOBALS['puffImageSettings'][ $aPuffData['puffLayoutKey'] ]) ? $aPuffData['puffLayoutKey'] : PUFF_LAYOUT_DEFAULT;
		$aImageParams += $GLOBALS['puffImageSettings'][ $sImageLayoutKey ];

		// Title
		$sTitle = _( 'Create puff' );

		// Image
		$aPuffItemImage = '';
		$sMainImageWidth = $aImageParams['maxWidth'];
		$sMainImageHeight = $aImageParams['maxHeight'];

		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataInformation' => _( 'Content has been filled as template' )
		) );
	}

// New, blank
} else {
	if( !empty($_GET['layout']) && empty($_POST['layout']) ) {
		$_POST['puffLayoutKey'] = $_GET['layout'];

		// Image settings
		if( !isset( $GLOBALS['puffImageSettings'][ $_POST['puffLayoutKey'] ] ) ) {
			$aImageParams += $GLOBALS['puffImageSettings'][ PUFF_LAYOUT_DEFAULT ];
		} else {
			$sImageLayoutKey = !empty($GLOBALS['puffImageSettings'][ $aPuffData['puffLayoutKey'] ]) ? $_POST['puffLayoutKey'] : PUFF_LAYOUT_DEFAULT;
			$aImageParams += $GLOBALS['puffImageSettings'][ $sImageLayoutKey ];
		}


	} else {
		// Image settings
		$aImageParams += $GLOBALS['puffImageSettings'][ PUFF_LAYOUT_DEFAULT ];
	}

	// Data
	$aPuffData = $_POST;

	// Title
	$sTitle = _( 'Create puff' );

	// Image
	$aPuffItemImage = '';
	$sMainImageWidth = $aImageParams['maxWidth'];
	$sMainImageHeight = $aImageParams['maxHeight'];
}

// Get the puff data dict
$aPuffDataDict = $oPuff->oDao->getDataDict();
$aFormDataDict = array(
	'puffTitleTextId' => array(),
	'puffClass' => array(
		'type' => 'string'
	),
	'puffContentTextId' => array(
		'type' => 'string',
		'appearance' => 'full',
		'title' => _( 'Text' ),
		'attributes' => array(
			'class' => 'editor'
		)
	),
	'puffShortContentTextId' => array(
		'type' => 'string',
		'appearance' => 'full',
		'title' => _( 'Short text' )
	),
	'puffUrlTextId' => array(
		'type' => 'string'
	),
	'puffStatus' => array(),
	'puffPublishStart' => array(
		'attributes' => array(
			'class' => 'datetimepicker text'
		)
	),
	'puffPublishEnd' => array(
		'attributes' => array(
			'class' => 'datetimepicker text'
		)
	),
	'puffImages[]' => array(
		'type' => 'upload',
		'attributes' => array(
			'class' => 'multi',
			'accept' => 'jpg|jpeg|gif|png',
			'id' => 'puffImageUploader'
		),
		'title' => _( 'Pictures' ),
		'suffixContent' => '
			<p><br />' . _( 'Best image resolution is' ) . ' ' . $sMainImageWidth . 'x' . $sMainImageHeight . '.</p>
			' . (!empty($sPuffImages) ? '<br />' . $sPuffImages : '')
	),
	'puffUserType' => array(),
	'puffSort' => array(),
	'frmPuffAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
);

$aFormGroupDataDict = array(
	'information' => array(
		'title' => _( 'Information' ),
		'fields' => array(
			'puffTitleTextId',
			'puffClass',
			'puffContentTextId',
			'puffShortContentTextId',
			'puffUrlTextId'
		)
	),
	'publishing' => array(
		'title' => _( 'Publishing' ),
		'fields' => array(
			'puffLayoutKey',
			'puffUserType',
			'puffStatus',
			'puffPublishStart',
			'puffPublishEnd',
			'puffSort',
		)
	),
	'pictures' => array(
		'title' => _( 'Pictures' ),
		'fields' => array(
			'puffImages[]'
		)
	)
);

/**
 * Activate ability to choose layout if configured to do so and if more options are available
 */
if( PUFF_LAYOUT_EDIT === true && count( $GLOBALS['puffLayout'] ) > 1 ) {
	// Turn puffLayoutKey string into an array of the puffLayouts defined in the config file
	$aPuffDataDict['entPuff']['puffLayoutKey']['type'] = 'array';
	$aPuffDataDict['entPuff']['puffLayoutKey']['values'] = arrayToSingle( $GLOBALS['puffLayout'], true, 'name' );

	// Show in the form
	$aFormDataDict = array('puffLayoutKey' => array() ) + $aFormDataDict; // Add to befinning of form
	$aFormGroupDataDict = array(
		'layout' => array(
			'title' => _( 'Layout' ),
			'fields' => array(
				'puffLayoutKey'
			)
		)
	) + $aFormGroupDataDict;
}

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aPuffDataDict, array(
	'attributes' => array('class' => 'marginal'),
	'data' => $aPuffData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );

/**
 * Remove unused fields
 */
$aLayout = !empty( $aPuffData[ 'puffLayoutKey' ] ) ? $GLOBALS['puffLayout'][ $aPuffData[ 'puffLayoutKey' ] ] : $GLOBALS['puffLayout'][ PUFF_LAYOUT_DEFAULT ]; // Get the currently used layout or use the default if not defined
preg_match_all( "/\[([^\]]*)\]/", $aLayout['template'], $aMatches );
foreach( $aFormDataDict as $sField => $aParams ) {
	if( $sField == 'puffImages[]' ) {
		if( !in_array('image', $aMatches[1]) && !in_array('images', $aMatches[1]) ) {
			unset( $aFormDataDict[$sField], $aFormGroupDataDict['pictures'] );
		}
		continue;
	}
	if( in_array($sField, $aFormGroupDataDict['publishing']['fields']) || !empty($aParams['type']) && $aParams['type'] == 'hidden' ) {
		continue;
	}
	if( $sField == 'puffTitleTextId' ) {
		continue; // puffs should always have a title even if it doesn't exist in the layout
	}
	if( !in_array($sField, $aMatches[1]) ) {
		unset( $aFormDataDict[$sField] );
	}
}

$oOutputHtmlForm->setFormDataDict( $aFormDataDict );
$oOutputHtmlForm->setGroups( $aFormGroupDataDict );

echo '
	<div class="view puffFormAdd">
		<h1>' . $sTitle . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $oRouter->getPath('adminPuffs') . '" class="icon iconText iconGoBack">' . _( 'Go back' ) . '</a>
			</div>
			' . (!empty($_POST) || !empty($_GET['puffId']) ? '
			<div class="tool">
				<a href="' . $oRouter->sPath . '" class="icon iconText iconAdd">' . _( 'New puff' ) . '</a>
			</div>
			' : '') . '
		</section>
		' . $oOutputHtmlForm->render() . '
	</div>';

$oPuff->oDao->setLang( $GLOBALS['langId'] );


if( array_key_exists('puffContentTextId', $aFormDataDict) ) {
	$oTemplate->addScript( array(
		'key' => 'jsTinyMce',
		'src' => '/modules/tinymce/tiny_mce.js'
	) );
	$oTemplate->addScript( array(
		'key' => 'jsTinyMceConfig',
		'src' => '/modules/tinymce/config/basic.js.php'
	) );
}

$oTemplate->addBottom( array(
	'key' => 'jsDatapicker',
	'content' => '
	<script type="text/javascript">
		$(".datepicker").datepicker({
			dateFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true
		});
	</script>'
) );


$oTemplate->addScript( array(
	'key' => 'jqueryMultiFileJs',
	'src' => '/js/jquery.multifile.js'
) );
$oTemplate->addBottom( array(
	'key' => 'jqueryMultiFileInit',
	'content' => '
		<script type="text/javascript">
			$("#puffImageUploader").MultiFile();
		</script>
	'
) );

// Image sorting
if( !empty($_GET['puffId']) ) {
	$oTemplate->addBottom( array(
		'key' => 'jsPuffImageSorting',
		'content' => '
			<script type="text/javascript">
				$("ul.puffImages").sortable( {
					handle: "span",
					update : function () {
						var ulList = $(this);
						$("#ajaxNotification").remove();
						$(ulList).parent().after("<ul id=\"ajaxNotification\" style=\"display: none;\" class=\"notification\"><li class=\"notification dataInformation\">' . _( 'Updating sort order' ) . '</li></div>");
						$("#ajaxNotification").slideDown();

						$.ajax( "' . $oRouter->sPath . '", {
							type: "GET",
							data: "ajax=true&puffId=' . $_GET['puffId'] . '&event=sortImage&sortImage[]=' . $oPuff->sModuleName .'&sortImage[]=' . $_GET['puffId'] . '&" + $("ul.puffImages").sortable("serialize"),
							success: function(data, textStatus, jqXHR) {
								$("#ajaxNotification").replaceWith("<ul id=\"ajaxNotification\" class=\"notification\"><li class=\"notification dataSaved\">' . _( 'Update successful' ) . '</li></div>");
								$("#ajaxNotification").delay(5000).slideUp();
								$(ulList).children("li.first").removeClass("first");

								var iCount = 1;
								$(ulList).children("li").each( function() {
									if( iCount == 1 ) {
										$(this).addClass("first");
									}
									iCount++;
								} );
							},
							error: function(jqXHR, textStatus, errorThrown) {
								$("#ajaxNotification").replaceWith("<ul id=\"ajaxNotification\" class=\"notification\"><li class=\"notification dataError\">' . _( 'Update failed. Please try again.') . '</li></div>");
							}
						} );
					}
				} );
			</script>'
	) );
} else {
	$oTemplate->addBottom( array(
		'key' => 'jsPuffLayoutSwith',
		'content' => '
			<script type="text/javascript">
				$(document).delegate( "#puffLayoutKey", "change", function() {
					console.log( "test" );
					window.location.replace( "' . $oRouter->sPath . '?layout=" + $(this).val() );
				} );
			</script>'
	) );
}
