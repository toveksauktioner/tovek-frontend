<?php

require_once PATH_FUNCTION . '/fOutputHtml.php';

define( 'PRODUCT_LIST_LAYOUT', 'defaultList' );
$GLOBALS['productLayout'] = array(
	'defaultList' => array(
		'name'		=> _( 'Default listing' ),
		'template'	=> '
			<div class="product">
				<div class="container">
					<a href="[routePath]">
						<img itemprop="image" src="[image]" alt="" />
					</a>
					<a href="[routePath]">
						<h3>[templateTitleTextId]</h3>
					</a>
					<p class="descripton">
						<span itemprop="description">[templateShortDescriptionTextId]</span>
					</p>
					<p class="price">
						<span itemprop="price" content="[dataPrice]">[humanPrice]</span>
					</p>
				</div>
			</div>
		'
	)
);

class clOutputHtmlProduct {
    
    public $aProducts = array();
    
    public $aImages = array(
        'template' => array(),
        'variant' => array()
    );
    
    public $aParams = array();
    
	public function __construct( $aParams = array() ) {
		$this->setParams( $aParams );
	}
    
    public function setParams( $aParams ) {
        $this->aParams = $aParams;
    }
    
	public function addProduct( $iProductId, $aProduct ) {
        // Add product by cross reference product ID
        $this->aProducts[ $iProductId ] = $aProduct;
    }
	
    public function addProducts( $aProducts ) {
        // Add product(s) by cross reference product ID
        $this->aProducts = array_merge( $this->aProducts, valueToKey( 'productId', $aProducts ) );
    }
    
    public function addTemplateImages( $aImages ) {
        // Add image(s) by cross reference imageParentId
        $this->aImages['template'] = ( $this->aImages['template'] + valueToKey( 'imageParentId', $aImages ) );
    }
    
    public function addVariantImages( $aImages ) {
        // Add image(s) by cross reference imageParentId
        $this->aImages['variant'] = ( $this->aImages['variant'] + valueToKey( 'imageParentId', $aImages ) );
    }
    
    public function render() {
        if( empty($this->aProducts) ) return false;
        
        $aList = array();        
        foreach( $this->aProducts as $aProduct ) {
            $aProduct += array(
                'image' => '',
                'dataPrice' => 0,
                'humanPrice' => 0
            );
			
            // Add image
            if( array_key_exists($aProduct['templateId'], $this->aImages['template']) ) {
				$aImage = $this->aImages['template'][ $aProduct['templateId'] ];
				if( !empty($aImage['filename']) ) {
					$aProduct['image'] = '/images/custom/ProductTemplate/' . IMAGE_TN_DIRECTORY . '/' . $aImage['filename'];
				} else {
					$aProduct['image'] = '/images/custom/ProductTemplate/' . IMAGE_TN_DIRECTORY . '/small' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'];
				}                
            } elseif(array_key_exists($aProduct['productId'], $this->aImages['variant']) ) {
				$aImage = $this->aImages['variant'][ $aProduct['templateId'] ];
				if( !empty($aImage['filename']) ) {
					$aProduct['image'] = '/images/custom/ProductTemplate/' . IMAGE_TN_DIRECTORY . '/' . $aImage['filename'];
				} else {
					$aProduct['image'] = '/images/custom/ProductTemplate/' . IMAGE_TN_DIRECTORY . '/small' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'];
				}
            } else {
				$aProduct['image'] = '/images/templates/terrarex/product-logo-image.png';
			}
            
            // Add prices
            $aProduct['dataPrice'] = calculatePrice( $aProduct['productPrice'], array( 'profile' => 'default' ) );
            $aProduct['humanPrice'] = calculatePrice( $aProduct['productPrice'], array( 'profile' => 'human' ) );
            
            // Assamble html
            $sProductLayout = $GLOBALS['productLayout'][ PRODUCT_LIST_LAYOUT ]['template'];
			foreach( $aProduct as $sField => $sValue ) {                
				if( strpos( $sProductLayout, $sField ) ) {
					$sProductLayout = str_replace( '[' . $sField . ']', $sValue, $sProductLayout );
				}
			}
            
            $aList[] = $sProductLayout;
        }
        
        return '<div class="productList">' . implode( "\r\n", $aList ) . '</div>';
    }
    
}