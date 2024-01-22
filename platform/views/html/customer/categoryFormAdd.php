<?php

$aErr = array();

$oCategory = clRegistry::get( 'clCustomerCategory', PATH_MODULE . '/customer/models' );
$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

// Edit language
$oCategory->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

// URL
$sUrlCustomers = $oRouter->getPath( 'guestCustomers' );

// Images
$oImage->setParams( array(
    'parentType' => $oCategory->sModuleName,
    'watermark' => $GLOBALS['customerCategoryImageWatermark'],
    'maxWidth' => CUSTOMER_CATEGORY_IMAGE_MAX_WIDTH,
    'maxHeight' => CUSTOMER_CATEGORY_IMAGE_MAX_HEIGHT,
    'tnMaxWidth' => CUSTOMER_CATEGORY_IMAGE_TN_MAX_WIDTH,
    'tnMaxHeight' => CUSTOMER_CATEGORY_IMAGE_TN_MAX_HEIGHT,
    'crop' => CUSTOMER_CATEGORY_IMAGE_CROP
) );

/**
 * Post
 */
if( !empty($_POST['frmCategoryAdd']) ) {
	if( empty($_POST['routePath']) ) {
		// Route is generated, notify user
		$oNotification->set( array(
			'dataInformation' => _( 'You didn´t enter a route so the system automatically generated one.' )
		) );
	}

	if( $_POST['routePathAuto'] == 'yes' && substr($_POST['routePath'], 0, strlen($sUrlCustomers)) != $sUrlCustomers ) {
		$_POST['routePath'] = $sUrlCustomers . '/' . $_POST['routePath'];
	}
	
	// Sanitize routepath
	$_POST['routePath'] = strToUrl(trim($_POST['routePath']));
	if( !empty($_POST['routePath']) && mb_substr($_POST['routePath'], 0, 1) !== '/' ) $_POST['routePath'] = '/' . $_POST['routePath'];

	// Update
	if( !empty($_GET['categoryId']) ) {
		if( $oCategory->update( $_GET['categoryId'], $_POST ) !== false ) {
			$iCategoryId = $_GET['categoryId'];
			
            $aCategoryTree = array_reverse( arrayToSingle( $oCategory->aHelpers['oTreeHelper']->readWithParents( $iCategoryId, array(
				'categoryTitleTextId'
			) ), null, 'categoryTitleTextId'), true );
			
            // Update route
            $_POST['routePath'] = !empty($_POST['routePath']) ? $_POST['routePath'] : $sUrlCustomers . '/' . implode( '/', array_map('strToUrl', $aCategoryTree) ) . '/' . $iCategoryId;			
            if( !$oRouter->updateRouteToObject( $iCategoryId, $oCategory->sModuleName, $_POST['routePath'], 'guestCustomers' ) ) {
                // Found no route, create one instead
                if( !$oRouter->createRouteToObject( $iCategoryId, $oCategory->sModuleName, $_POST['routePath'], 'guestCustomers' ) ) {
                    $oNotification = clRegistry::get( 'clNotificationHandler' );
                    $oNotification->set( array(
                        'dataError' => _( 'Problem with creating route' )
                    ) );
                }
			}
		}
		$aErr = clErrorHandler::getValidationError( 'updateCategory' );
		
	// Create
	} else {
		if( $iCategoryId = $oCategory->aHelpers['oTreeHelper']->create($_POST) ) {
			$aCategoryTree = array_reverse( arrayToSingle( $oCategory->aHelpers['oTreeHelper']->readWithParents( $iCategoryId, array(
				'categoryTitleTextId'
			) ), null, 'categoryTitleTextId'), true );
			
            // Create route
            $_POST['routePath'] = !empty($_POST['routePath']) ? $_POST['routePath'] : $sUrlCustomers . '/' . implode( '/', array_map('strToUrl', $aCategoryTree) ) . '/' . $iCategoryId;			
            if( !$oRouter->createRouteToObject( $iCategoryId, $oCategory->sModuleName, $_POST['routePath'], 'guestCustomers' ) ) {
                $oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataError' => _( 'Problem with creating route' )
				) );
			}
            
            $_POST = array();
            
		} else {
			$aErr = clErrorHandler::getValidationError( 'createCategory' );
		}
	}

	// Image
    if( empty($aErr) && !empty($_FILES['categoryImage']) && !empty($iCategoryId) ) {
        $aErr = $oImage->createWithUpload( array(
            'allowedMime' => array(
                'image/jpeg' => 'jpg',
                'image/pjpeg' => 'jpg',
                'image/gif' => 'gif',
                'image/png' => 'png',
                'image/x-png' => 'png'
            ),
            'key' => 'categoryImage'
        ), $iCategoryId );
    }
}

/**
 * Move
 */
if( !empty($_GET['categoryId']) && !empty($_POST['frmCategoryMove']) ) {
	$oCategory->aHelpers['oTreeHelper']->move( $_GET['categoryId'], $_POST );
	$oRouter->redirect( $oRouter->getPath('adminCustomerCategories') );
}

/**
 * Data, all categories
 */
$aCategoriesData = $oCategory->aHelpers['oTreeHelper']->readWithChildren( 0, array(
	'categoryId',
	'categoryTitleTextId',
	'categoryDescriptionTextId',
	'categoryLeft',
	'categoryRight'
) );

/**
 * Category list
 */
$aCategories = array(
	'' => '[' . _( 'Root' ) . ']'
);
foreach( $aCategoriesData as $entry ) {
	$aCategories[$entry['categoryId']] = str_repeat('&emsp;', $entry['depth'] + 1) . $entry['categoryTitleTextId'];
}

/**
 * Edit
 */
if( !empty($_GET['categoryId']) && ctype_digit($_GET['categoryId']) ) {
	$sTitle = _( 'Edit category' );
    
    /**
     * Data
     */
    $aCategoryData = current( $oCategory->read(array(
		'categoryId',
		'categoryTitleTextId',
		'routePath',
		'categoryDescriptionTextId',
		'categoryCustomerBehavior',
		'categoryPageTitleTextId',
		'categoryPageDescriptionTextId',
		'categoryPageKeywordsTextId',
		'categoryCanonicalUrlTextId',
	), $_GET['categoryId']) );
	
	if( empty($aCategoryData) && $GLOBALS['langIdEdit'] != $GLOBALS['langId'] ) {
		$aCategoryData = current( $oCategory->read(array(
			'categoryId'
		), $_GET['categoryId']) );
		if( !empty($aCategoryData) ) {
			$aErr[] = _( 'This category is probably not yet translated' );
		}
	}

	/**
     * Image
     */
    $aCategoryImage = current( $oImage->readByParent( $_GET['categoryId'], array(
        'imageId',
        'imageFileExtension',
        'imageParentId',
        'imageParentType'
    ) ) );
    if( !empty($aCategoryImage) ) {
        $sCategoryImage = '
            <div class="categoryImage">
                <img src="/images/custom/' . $aCategoryImage['imageParentType'] . '/tn/' . $aCategoryImage['imageId'] . '.' . $aCategoryImage['imageFileExtension'] . '" alt="" /><br />
                <a href="' . $oRouter->sPath . '?event=deleteImage&amp;deleteImage=' . $aCategoryImage['imageId'] . '&amp;' . stripGetStr( array('event', 'imageCreateThumbnail', 'deleteImage') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>
            </div>';
    }
	
	/**
     * Add form
     */
	$oOutputHtmlForm->init( $oCategory->oDao->getDataDict(), array(
		'attributes' => array('class' => 'vertical'),
		'action' => '',
		'data' => $aCategoryData,
		'errors' => $aErr,
		'labelSuffix' => ':',
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Save' )
		),
	) );
	$aFormDict = array(
		'categoryTitleTextId' => array(),
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
		'routePathAuto' => array(
			'type' => 'hidden',
			'value' => 'yes'
		),
		'categoryDescriptionTextId' => array(
			'type' => 'string',
			'appearance' => 'full',
			'attributes' => array(
				'class' => 'editor compact'
			)
		),
		'categoryCustomerBehavior' => array(),
		'categoryPageTitleTextId' => array(),
		'categoryPageDescriptionTextId' => array(),
		'categoryPageKeywordsTextId' => array(),
		'categoryCanonicalUrlTextId' => array(),
		'frmCategoryAdd' => array(
			'type' => 'hidden',
			'value' => true
		)
	);	
	$aFormGroupDict = array(
		'information' => array(
			'title' => _( 'Information' ),
			'fields' => array(
				'categoryTitleTextId',
				'routePath',
				'categoryDescriptionTextId',
				'categoryCustomerBehavior',
				'categoryPageTitleTextId',
				'categoryPageDescriptionTextId',
				'categoryPageKeywordsTextId',
				'categoryCanonicalUrlTextId',
			)
		)
	);
	
    $aFormDict['categoryImage'] = array(
        'type' => 'upload',
        'attributes' => array(
            'accept' => 'jpg|jpeg|gif|png',
            'id' => 'categoryImageUploader'
        ),
        'title' => _( 'Picture' ),
        'suffixContent' => !empty($sCategoryImage) ? '<br />' . $sCategoryImage : ''
    );
    array_push( $aFormGroupDict['information']['fields'], 'categoryImage' );
	
	$oOutputHtmlForm->setFormDataDict( $aFormDict );
	$oOutputHtmlForm->setGroups( $aFormGroupDict );
	$sCategoryForm = $oOutputHtmlForm->render();
    
    /**
     * Move form
     */
	$oOutputHtmlForm->init( $oCategory->oDao->getDataDict(), array(
		'attributes' => array('class' => 'vertical'),
		'action' => '',
		'data' => $aCategoryData,
		'errors' => $aErr,
		'labelSuffix' => ':',
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Move' )
		),
	) );
	$oOutputHtmlForm->setFormDataDict( array(
		'categoryRelation' => array(
			'title' => _( 'Relation' ),
			'type' => 'array',
			'values' => array(
				'firstChild' => _( 'First beneath target' ),
				'lastChild' => _( 'Last beneath target' ),
				'prevSibling' => _( 'Before target' ),
				'nextSibling' => _( 'After target' )
			)
		),
		'categoryTarget' => array(
			'title' => _( 'Target' ),
			'type' => 'array',
			'values' => $aCategories
		),
		'frmCategoryMove' => array(
			'type' => 'hidden',
			'value' => true
		)
	) );
	$oOutputHtmlForm->setGroups( array(
		'move' => array(
			'title' => _( 'Move' ),
			'fields' => array(
				'categoryRelation',
				'categoryTarget'
			)
		)
	) );
	$sCategoryForm .= '
		<h2>' . _( 'Move' ) . '</h2>
		' . $oOutputHtmlForm->render();

/**
 * New
 */
} else {
	$sTitle = _( 'Add category' );
    
    $aCategoryData = $_POST;
	
	/**
     * Add form
     */
	$oOutputHtmlForm->init( $oCategory->oDao->getDataDict(), array(
		'attributes' => array('class' => 'vertical'),
		'action' => '',
		'data' => $aCategoryData,
		'errors' => $aErr,
		'labelSuffix' => ':',
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Save' )
		),
	) );
	$aFormDict = array(
		'categoryTitleTextId' => array(),
		'routePath' => array(
			'type' => 'string',
			'title' => _( 'Path' ),
			'labelSuffixContent' => SITE_DOMAIN . $sUrlCustomers . '/',
			'attributes' => array(
				'readonly' => 'readonly',
				'class' => 'text autoFill'
			),
			'fieldAttributes' => array(
				'class' => 'fieldRoutePath'
			),
			'suffixContent' => '<a href="#" id="changeRoutePath">' . _( 'Enter the path manually' ) . '</a>'
		),
		'routePathAuto' => array(
			'type' => 'hidden',
			'value' => 'yes'
		),
		'categoryDescriptionTextId' => array(
			'type' => 'string',
			'appearance' => 'full',
			'attributes' => array(
				'class' => 'editor compact'
			)
		),
		'categoryCustomerBehavior' => array(),
		'categoryRelation' => array(
			'title' => _( 'Relation' ),
			'type' => 'array',
			'values' => array(
				'firstChild' => _( 'First beneath target' ),
				'lastChild' => _( 'Last beneath target' ),
				'prevSibling' => _( 'Before target' ),
				'nextSibling' => _( 'After target' )
			)
		),
		'categoryTarget' => array(
			'title' => _( 'Target' ),
			'type' => 'array',
			'values' => $aCategories
		),
		'frmCategoryAdd' => array(
			'type' => 'hidden',
			'value' => true
		)
	);
	$aFormGroupDict = array(
		'information' => array(
			'title' => _( 'Create' ),
			'fields' => array(
				'categoryTitleTextId',
				'routePath',
				'categoryDescriptionTextId',
				'categoryCustomerBehavior',
				'categoryRelation',
				'categoryTarget'
			)
		)
	);
	
    $aFormDict['categoryImage'] = array(
        'type' => 'upload',
        'attributes' => array(
            'accept' => 'jpg|jpeg|gif|png',
            'id' => 'categoryImageUploader'
        ),
        'title' => _( 'Picture' ),
        'suffixContent' => !empty($sCategoryImage) ? '<br />' . $sCategoryImage : ''
    );
    array_push( $aFormGroupDict['information']['fields'], 'categoryImage' );
	
	$oOutputHtmlForm->setFormDataDict( $aFormDict );
	$oOutputHtmlForm->setGroups( $aFormGroupDict );
	$sCategoryForm = $oOutputHtmlForm->render();
}

echo '
	<div class="view categoryFormAdd">
		<h1>' . $sTitle . '</h1>
		' . $sCategoryForm . '
	</div>';

// Language
$oCategory->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );

// Scripts
$oTemplate->addScript( array(
	'key' => 'jsTinyMce',
	'src' => '/modules/tinymce/tiny_mce.js'
) );
$oTemplate->addScript( array(
	'key' => 'jsTinyMceConfig',
	'src' => '/modules/tinymce/config/basic.js.php'
) );

/**
 * Script for routes upon edit
 */
if( !empty($_GET['categoryId']) ) {
	$oTemplate->addBottom( array(
		'key' => 'autoRoutePath2',
		'content' => '
			<script>
				$("#changeRoutePath").click(function() {
					if( $("input#routePath").attr("readonly") == "readonly" ) {
						$("input#routePath").removeAttr("readonly");
						$("#routePathAuto").val("no");
						$("input#routePath").removeClass("autoFill");
	
						$("#changeRoutePath").html("' . _( 'Auto fill path' ) . '");
						$("input#routePath").trigger("update");
	
					} else {
						var sContent = "' . $sUrlCustomers . '/" + $("#categoryTitleTextId").val().toLowerCase().replace(/ /g,"-");
						$("input#routePath").val(sContent);
						$("#routePathAuto").val("yes");
						$("input#routePath").addClass("autoFill");
	
						$("input#routePath").attr("readonly", "readonly");
						$("#changeRoutePath").html("' . _( 'Enter the path manually' ) . '");
						$("input#routePath").trigger("update");
					}
				});
			</script>'
	) );

/**
 * Script for routes upon new
 */
} else {
	$oTemplate->addBottom( array(
		'key' => 'autoRoutePath1',
		'content' => '
			<script>
				$("#categoryTitleTextId").keyup(function() {
					var sValue = $("input#routePath").val();
					if( $("input#routePath").attr("readonly") == "readonly" ) {
						var sContent = $(this).val();
						sContent = strToUrl( sContent );
						$("input#routePath").val(sContent);
						$("input#routePath").trigger("update");
					}
				});
			</script>'
	) );
	
	$oTemplate->addBottom( array(
		'key' => 'autoRoutePath2',
		'content' => '
			<script>
				$("#changeRoutePath").click(function() {
					if( $("input#routePath").attr("readonly") == "readonly" ) {
						$("input#routePath").removeAttr("readonly");
						$("#routePathAuto").val("no");
						$("input#routePath").removeClass("autoFill");
	
						$("#changeRoutePath").html("' . _( 'Auto fill path' ) . '");
						
						$(".fieldRoutePath .labelSuffixContent").html( "' . SITE_DOMAIN . '/" );
						$("input#routePath").val( "' . substr($sUrlCustomers, 1, strlen($sUrlCustomers)) . '/" + $("input#routePath").val() );
						
						$("input#routePath").trigger("update");
	
					} else {
						var sContent = $("#categoryTitleTextId").val().toLowerCase().replace(/ /g,"-");
						$("input#routePath").val(sContent);
						$("#routePathAuto").val("yes");
						$("input#routePath").addClass("autoFill");
	
						$("input#routePath").attr("readonly", "readonly");
						$("#changeRoutePath").html("' . _( 'Enter the path manually' ) . '");
						
						$(".fieldRoutePath .labelSuffixContent").html( "' . SITE_DOMAIN . $sUrlCustomers . '/" );
						$("input#routePath").val( $("input#routePath").val().replace("' . substr($sUrlCustomers, 1, strlen($sUrlCustomers)) . '/", "") );
						
						$("input#routePath").trigger("update");
					}
				});
			</script>'
	) );
}
