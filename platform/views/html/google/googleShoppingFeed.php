<?php

/**
 * Google shopping feed
 * - Exports products into XML-feed
 *
 * Documentation:
 * https://support.google.com/merchants/answer/7052112?hl=sv
 * http://www.google.com/basepages/producttype/taxonomy-with-ids.sv-SE.txt
 */

// Settings
define( 'GOOGLE_PRODUCT_CATEGORY', '5182' );

$oConfig = clRegistry::get( 'clConfig' );
$oLocale = clRegistry::get( 'clLocale' );

$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
$oProductTemplate = clRegistry::get( 'clProductTemplate', PATH_MODULE . '/product/models' );
$oProductAttributeSet = clRegistry::get( 'clProductAttributeSet', PATH_MODULE . '/product/models/' );
$oProductAttribute = clRegistry::get( 'clProductAttribute', PATH_MODULE . '/product/models/' );

$oCurrency = clRegistry::get( 'clCurrency', PATH_MODULE . '/currency/models' );
$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );

$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );
$oFreightRelation = clRegistry::get( 'clFreightRelation', PATH_MODULE . '/freight/models' );
//$oFreightWeight = clRegistry::get( 'clFreightWeight', PATH_MODULE . '/freight/models' );

// Set monetary
$oLocale->setMonetary( $oLocale->getLandCodeByCurrencyCode( $GLOBALS['currency'] ) );
$fCurrency = $oCurrency->getCurrencyRate( $GLOBALS['currency'] );

// Country data
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$aCountries = valueToKey( 'countryId', $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName',
	'countryIsoCode2'
) ) );

/**
 * Freights
 */
$fFreight = 0;
// Get freight options
$aConfigs = $oConfig->read( array(
  'configKey',
  'configValue'
), array('generalFreightFee', 'freeFreightLimit' ));
foreach( $aConfigs as $oEntry ) {
	switch( $oEntry['configKey'] ) {
		case 'generalFreightFee':
			$fGeneralFreight = $oEntry['configValue'];
			break;
		
		case 'freeFreightLimit':
			$fFreeFreightLimit = calculatePrice( $oEntry['configValue'], array(
				'additional' => array(
					'format' => array(
						'currency' => true
					),
					'currencyRate' => $fCurrency
				)
			) );		
			break;
	}
}
$fFreight += $fGeneralFreight;

/**
 * Country fright addition
 */
$iCountryId = 210;
$fCountryFreightAddition = $oFreight->readByCountry($iCountryId, array(
	'freightValue'
) );
if( !empty($fCountryFreightAddition) ) {
	$fCountryFreightAddition = current( current( $fCountryFreightAddition ) );
	$fFreight += $fCountryFreightAddition;
}

/**
 * Country's free limit
 */
$aCountryFreightFreeLimit = array();
if( method_exists( $oFreight, 'readFreightFreeLimitToCountry' ) ) {
	$aCountryFreightFreeLimit = current( $oFreight->readFreightFreeLimitToCountry($iCountryId) );
	if( !empty( $aCountryFreightFreeLimit ) ) {
		$fCountryFreightFreeLimit = calculatePrice( (float) $aCountryFreightFreeLimit['freightFreeLimit'], array(
			'additional' => array(
				'format' => array(
				  'currency' => true
				),
				'currencyRate' => $fCurrency
			)
		) );
	}
}

/**
 * Freight Type selection
 */
$oFreight->oDao->aSorting = array(
	'freightTypeSort' => 'ASC'
);
$oFreight->oDao->setCriterias( array(
	'freightTypeStatus' => array(
		'fields' => 'freightTypeStatus',
		'value' => 'active'
	)
) );

/**
 * Read freight types
 */
if( method_exists( $oFreight, 'readTypeByCountry' ) ) {
	$aFreightTypes = $oFreight->readTypeByCountry( $iCountryId );
} elseif( method_exists( $oFreight, 'readType' ) ) {
	$aFreightTypes = $oFreight->readType();
} else {
	$aFreightTypes = array();
}

// Reset freight
$oFreight->oDao->sCriterias = null;
$oFreight->oDao->aSorting = array();

/**
 * Assamble some data
 */
$sTitle = SITE_TITLE;
$sTopDomain = 'http://' . SITE_DOMAIN; // Remember to make each route unique for each language
$sUpdated = date("Y-m-d\TH:i:sP"); // I think this is right. RFC3339 for Atom, right?

/**
 * Read template products
 */
$aProducts = $oProductTemplate->read( array(
	// Template fields
	'templateId',
	'templateTitleTextId',
	'templateDescriptionTextId',	
	// Product fields
	'productId',
	'productQuantity',
	'productQuantityWarningLimit',
	'productQuantityIncoming',
	'productPrice',
	'productDiscount',	
	'productVat',
	// Route
	'routePath'
) );
$aTemplateIds = arrayToSingle( $aProducts, null, 'templateId' );
$aTemplateIdToProductId = arrayToSingle( $aProducts, 'templateId', 'productId' );

/**
 * All product template images
 */
$aTemplateImages = array();
$oImage->setParams( array(
	'parentType' => $oProductTemplate->sModuleName
) );
$oImage->oDao->aSorting = array(
	'imageSort' => 'ASC',
	'imageCreated' => 'ASC'
);
$aTemplateImageData = $oImage->readByParent( $aTemplateIds, array(
	'imageId',
	'imageFileExtension',
	'imageParentId'
) );
if( !empty($aTemplateImageData) ) {	
	foreach( $aTemplateImageData as $oEntry ) {
		if( array_key_exists($oEntry['imageParentId'], $aTemplateImages) ) {
			// Do not add more images if one already is present
			continue; 
		}
		$aTemplateImages[$oEntry['imageParentId']] = array(
			'filename' => $oEntry['imageId'] . '.' . $oEntry['imageFileExtension'],
			'parentType' => 'ProductTemplate'
		);
	}
}

/**
 * Read variant images if no template image
 */
$aTemplateIdsMissingImage = array_diff_key( array_flip($aTemplateIds), $aTemplateImages );
if( !empty($aTemplateIdsMissingImage) ) {
	$aTemplateIdsToReadImages = array();
	foreach( $aTemplateIdsMissingImage as $iMissingTemplateId => $key ) {
		if( array_key_exists($iMissingTemplateId, $aTemplateIdToProductId) ) $aTemplateIdsToReadImages[] = $aTemplateIdToProductId[ $iMissingTemplateId ];
	}
  
	$oImage->setParams( array(
		'parentType' => $oProduct->sModuleName
	) );
	$oImage->oDao->aSorting = array(
		'imageSort' => 'ASC',
		'imageCreated' => 'ASC'
	);
	$aTemplateVariantImageData = $oImage->readByParent( $aTemplateIdsToReadImages, array(
		'imageId',
		'imageFileExtension',
		'imageParentId'
	) );
	
	if( !empty($aTemplateVariantImageData) ) {
		// Integrate with templateId into image array
		foreach( $aTemplateVariantImageData as $oEntry ) {
			if( array_key_exists($aTemplateIdToTemplateId[ $oEntry['imageParentId'] ], $aTemplateImages) ) {
				// Do not add more images if one already is present
				continue; 
			}
			$aTemplateImages[ $aTemplateIdToTemplateId[ $oEntry['imageParentId'] ] ] = array(
				'filename' => $oEntry['imageId'] . '.' . $oEntry['imageFileExtension'],
				'parentType' => 'Product'
			);
		}
	}
}

/**
 * Common functions
 */
function feedFriendlyOutput( $sString, $iLimit ) {
	return htmlspecialchars( substr( trim( strip_tags( $sString ) ), 0, $iLimit ) );
}
function feedFriendlyHTML( $sString, $iLimit ) {
	return ( substr( trim( strip_tags( $sString ) ), 0, $iLimit ) );
}

/**
 * Create the XML-feed
 */
$oXml = new SimpleXMLElement( '<feed/>' );
$oXml->addAttribute( 'xmlns','http://www.w3.org/2005/Atom' );
$oXml->addAttribute( 'xmlns:xmlns:g','http://base.google.com/ns/1.0' );

$oXml->addChild( 'title', $sTitle );

$oLink = $oXml->addChild( 'link' );
$oLink->addAttribute( 'rel','self' );
$oLink->addAttribute( 'href', $sTopDomain );

$oXml->addChild( 'updated', $sUpdated );

if( !empty($aProducts) ) {
	foreach( $aProducts as $iKey => &$aProduct ) {
		$aEntry = array(
			'id' => feedFriendlyOutput( 'IOS-PT-' . $aProduct['templateId'], 150 ), # Unique product template ID
			'title' => feedFriendlyOutput( $aProduct['templateTitleTextId'], 150 ),
			'description' => feedFriendlyOutput( $aProduct['templateDescriptionTextId'], 5000 ),
			'link' => feedFriendlyOutput( $sTopDomain . $aProduct['routePath'], 2000 ),
			'condition' => 'new'
		);
		
		// Category based on Google ID
		$aEntry['google_product_category'] = GOOGLE_PRODUCT_CATEGORY;
	  
		/**
		 * Price calculation
		 */
		$fPrice = calculatePrice( $aProduct['productPrice'], array(
			'profile' => 'human',
			'additional' => array(
				'vat' => $aProduct['productVat'],
				'discount' => array( $aProduct['productDiscount'] ),
				'decimals' => PRODUCT_PRICE_DECIMALS,
				'format' => array(
					'money' => true,
					'vatLabel' => PRODUCT_SHOW_VAT,
					'currency' => true
				),
				'currencyRate' => $fCurrency
			)
		) );
		$aEntry['price'] = $fPrice;
		
		/**
		 * Product availability
		 */ 
		if( (int) $aProduct['productQuantity'] <= 0 ) {
			$aEntry['availability'] = 'out of stock';
			
		} elseif( (int) $aProduct['productQuantity'] <= (int) $aProduct['productQuantityWarningLimit'] ) {
			$aEntry['availability'] = 'in stock'; # few in stock
		
		} elseif( (int) $aProduct['productQuantityIncoming'] > 0 ) {
			$aEntry['availability'] = 'preorder';
			
		} else {
			$aEntry['availability'] = 'in stock';
			
		}
		
		// Image
		if( array_key_exists( $aProduct['templateId'], $aTemplateImages ) ) {
			$sImagePath = '/images/custom/' . $aTemplateImages[ $aProduct['templateId'] ]['parentType'] . '/' . $aTemplateImages[ $aProduct['templateId'] ]['filename'];
			if( file_exists( PATH_PUBLIC . $sImagePath ) ) {
				$aEntry['image_link'] = feedFriendlyOutput( $sTopDomain . $sImagePath, 2000 );
			}
		}
		
		// Add product
		$oEntry = $oXml->AddChild( 'entry' );
		foreach( $aEntry as $sLabel => $sValue ) {
			$oEntry->addChild( 'xmlns:g:' . $sLabel, $sValue );
		}
		
		/**
		 * Shipping
		 */
		if( !empty($aFreightTypes) ) {
			foreach( $aFreightTypes as $key => &$aFreightType ) {				
				if( $aFreightType['freightTypeStatus'] != 'active' ) {
					// Skip if not active
					continue; 
				}
				
				/**
				 * Price calculation
				 */
				$sShippingPrice = calculatePrice( $aFreightType['freightTypePrice'], array(
					'profile' => 'human',
					'additional' => array(
						//'vat' => $aTemplate['productVat'],
						'discount' => array( $aProduct['productDiscount'] ),
						'decimals' => PRODUCT_PRICE_DECIMALS,
						'format' => array(
							'money' => true,
							'vatLabel' => PRODUCT_SHOW_VAT,
							'currency' => true
						),
						'currencyRate' => $fCurrency
					)
				) );
				$sShippingPrice = str_replace( ',', '.', $sShippingPrice);
				
				/**
				 * Add shippment
				 */
				$oShipping = $oEntry->addChild( 'xmlns:g:shipping' );
				if( !empty($aFreightType['countryId']) ) {
					$oShipping->addChild( 'xmlns:g:country', strtolower( $aCountries[ $aFreightType['countryId'] ]['countryIsoCode2'] ) );
				}
				$oShipping->addChild( 'xmlns:g:service', $aFreightType['freightTypeTitle'] );
				$oShipping->addChild( 'xmlns:g:price', $sShippingPrice );
			}
		}
	}
}

/**
 * Output
 */
header( 'Content-type: text/xml' );
echo $oXml->asXML();