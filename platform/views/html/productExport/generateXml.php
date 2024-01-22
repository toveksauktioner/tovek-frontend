<?php
// Generate a product export, without attributes

$fStart = microtime(true);

if( !empty($_GET['templateId']) ) $_GET['templateId'] = array_map( 'intval', (array) $_GET['templateId'] );
if( empty($_GET['exclude']) ) $_GET['exclude'] = array();
$_GET['exclude'] += array(
	'categories' => false,
	'images' => false
);

$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
$oProductTemplate = clRegistry::get( 'clProductTemplate', PATH_MODULE . '/product/models' );
$oProductCategory = clRegistry::get( 'clProductCategory', PATH_MODULE . '/product/models' );
//$oProductAttribute = clRegistry::get( 'clProductAttribute', PATH_MODULE . '/product/models' );
//$oProductAttributeSet = clRegistry::get( 'clProductAttributeSet', PATH_MODULE . '/product/models' );
$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
$oImage->oDao->aSorting = array(
	'imageSort' => 'ASC',
	'imageCreated' => 'ASC'
);
$oFile = clRegistry::get( 'clFile', PATH_MODULE . '/file/models' );

require_once( PATH_FUNCTION . '/fData.php' );

function washForXml( $sString ) {
	if( empty($sString) ) return $sString;
	$sString = htmlspecialchars( $sString, ENT_COMPAT, 'UTF-8' );
	return $sString;
}

// Template data
$aTemplates = $oProductTemplate->readAll( array(
	'templateId',
	'templateProductId',
	'templatePageTitleTextId',
	'templatePageDescriptionTextId',
	'templatePageKeywordsTextId',
	'templateVariantSelectionType',
	'templateStatus',
	'templateProductCount',
	'templateSortWeight',
	'templateCreated',
	'templateTitleTextId',
	'templateShortDescriptionTextId',
	'templateDescriptionTextId'
), ( !empty($_GET['templateId']) ? $_GET['templateId'] : null ) );
if( empty($aTemplates) ) {
	echo '<products></products>';
	return;
}

// Variant data
if( !empty($_GET['templateId']) ) {
	$aProducts = $oProduct->readAllByTemplate( $_GET['templateId'], array(
		'productId',
		'productTemplateId',
		'productCustomId',
		'productManufacturer',
		'productManufacturerCustomId',
		'productEan',
		'productQuantity',
		//'productQuantityIncoming',
		//'productQuantityWarningLimit',
		'productDeliveryTime',
		'productWeight',
		'productPrice',
		'productVat',
		'productPriceRecommended',
		'productPriceFreight',
		'productExtraData',
		'productDiscount',
		'productUnpublishDatetime',
		'productStatus',
		'productCreated'
	) );
} else {
	$aProducts = $oProduct->read( array(
		'productId',
		'productTemplateId',
		'productCustomId',
		'productManufacturer',
		'productManufacturerCustomId',
		'productEan',
		'productQuantity',
		//'productQuantityIncoming',
		//'productQuantityWarningLimit',
		'productDeliveryTime',
		'productWeight',
		'productPrice',
		'productVat',
		'productPriceRecommended',
		'productPriceFreight',
		'productExtraData',
		'productDiscount',
		'productUnpublishDatetime',
		'productStatus',
		'productCreated'
	) );
}

$aProductsByTemplateId = array();
foreach( $aProducts as $aProduct ) {
	$aProductsByTemplateId[ $aProduct['productTemplateId'] ][] = $aProduct;
}

// Category data
if( $_GET['exclude']['categories'] == false ) {
	if( !empty($_GET['templateId']) ) {
		$aTemplateCategories = $oProductTemplate->readTemplateCategories($_GET['templateId']);
	} else {
		$aTemplateCategories = $oProductTemplate->readTemplateCategories();
	}
	$aCategoryIds = array();
	$aTemplateToCategory = array();
	foreach( $aTemplateCategories as $entry ) {
		$aCategoryIds[] = $entry['categoryId'];
		$aTemplateToCategory[ $entry['templateId'] ][] = $entry['categoryId'];
	}
	$aCategoryData = $oProductCategory->read( array(
		'categoryId',
		'categoryTitleTextId',
		'categoryDescriptionTextId',
		'categoryPageTitleTextId',
		'categoryPageDescriptionTextId',
		'categoryPageKeywordsTextId',
		'categoryLeft',
		'categoryRight',
		'categoryCreated',
		'routePath'
	), $aCategoryIds );
	$aCategoryById = array();
	foreach( $aCategoryData as $entry ) {
		$aCategoryById[ $entry['categoryId'] ] = $entry;
	}
	unset($aCategoryData);
} else {
	$aCategoryIds = array();
	$aCategoryById = array();
	$aTemplateToCategory = array();
}

// Read template images
if( $_GET['exclude']['images'] == false ) {
	$oImage->setParams( array(
		'parentType' => $oProductTemplate->sModuleName
	) );
	$aTemplateImages = $oImage->readByParent( arrayToSingle($aTemplates, null, 'templateId'), array(
		'imageId',
		'imageFileExtension',
		'imageParentId',
		'imageMD5',
		'imageSort',
		'imageCreated'
	) );
	$aTemplateImagesByTemplateId = array();
	foreach( $aTemplateImages as $entry ) {
		$aTemplateImagesByTemplateId[ $entry['imageParentId'] ][] = $entry;
	}
	unset($aTemplateImages);
	
	// Read variant images
	$oImage->setParams( array(
		'parentType' => $oProduct->sModuleName
	) );
	$aVariantImages = $oImage->readByParent( arrayToSingle($aProducts, null, 'productId'), array(
		'imageId',
		'imageFileExtension',
		'imageParentId',
		'imageMD5',
		'imageSort',
		'imageCreated'
	) );
	$aVariantImagesByProductId = array();
	foreach( $aVariantImages as $entry ) {
		$aVariantImagesByProductId[ $entry['imageParentId'] ][] = $entry;
	}
	unset($aVariantImages);
	$aVariantImage = $aTemplateImagesByTemplateId;
} else {
	$aVariantImage = array();
	$aTemplateImagesByTemplateId = array();
	$aVariantImagesByProductId = array();
}

// Output below
echo '<!-- ', number_format( (microtime(true) - $fStart), 5 ), ' -->
<products>';
foreach( $aTemplates as $iKey => $aTemplate ) {
	echo "\r\n\t", '<product>';
	
	foreach( $aTemplate as $sField => $sData ) {
		echo "\r\n\t\t" . '<' . $sField . '>', washForXml($sData), '</' . $sField . '>' ;
	}
	
	// Template images
	echo "\r\n\t\t", '<templateImages>';
	if( array_key_exists($aTemplate['templateId'], $aTemplateImagesByTemplateId) ) {
		foreach( $aTemplateImagesByTemplateId[ $aTemplate['templateId'] ] as $aTemplateImage ) {
			echo "\r\n\t\t\t" . '<image id="', $aTemplateImage['imageId'],
			'" extension="', $aTemplateImage['imageFileExtension'],
			'" md5="', $aTemplateImage['imageMD5'], '">http://', SITE_DOMAIN,
			'/images/custom/', $oProductTemplate->sModuleName,
			'/', $aTemplateImage['imageId'], '.', $aTemplateImage['imageFileExtension'],'</image>';
		}
	}
	echo "\r\n\t\t", '</templateImages>';
	
	// Variants
	echo "\r\n\t\t", '<variants>';
	if( array_key_exists($aTemplate['templateId'], $aProductsByTemplateId) ) {
		foreach( $aProductsByTemplateId[ $aTemplate['templateId'] ] as $aVariant ) {
			foreach( $aVariant as $sField => $sData ) {
				echo "\r\n\t\t\t" . '<' . $sField . '>', washForXml($sData), '</' . $sField . '>' ;
			}
			
			// Variant images
			echo "\r\n\t\t\t", '<variantImages>';
			if( array_key_exists($aVariant['productId'], $aVariantImagesByProductId) ) {
				foreach( $aVariantImagesByProductId[ $aVariant['productId'] ] as $aVariantImage ) {
					echo "\r\n\t\t\t" . '<image id="', $aVariantImage['imageId'],
					'" extension="', $aVariantImage['imageFileExtension'],
					'" md5="', $aVariantImage['imageMD5'], '">http://', SITE_DOMAIN,
					'/images/custom/', $oProduct->sModuleName,
					'/', $aVariantImage['imageId'], '.', $aVariantImage['imageFileExtension'],'</image>';
				}
			}
			echo "\r\n\t\t\t", '</variantImages>';
		}
	}
	echo "\r\n\t\t", '</variants>';
	
	// Categories
	echo "\r\n\t\t", '<categories>';
	if( array_key_exists( $aTemplate['templateId'], $aTemplateToCategory ) ) {
		foreach( $aTemplateToCategory[$aTemplate['templateId']] as $iCategoryId ) {
			if( array_key_exists( $iCategoryId, $aCategoryById ) ) {
				echo "\r\n\t\t", '<category>';
				foreach( $aCategoryById[ $iCategoryId ] as $sCategoryKey => $sCategoryValue ) {
					echo "\r\n\t\t\t", '<', $sCategoryKey, '>', $sCategoryValue, '</', $sCategoryKey, '>';
				}
				echo "\r\n\t\t", '</category>';
			}
		}
	}
	echo "\r\n\t\t", '</categories>';
	echo "\r\n\t", '</product>';
	//break;
}

echo "\r\n", '</products>';
