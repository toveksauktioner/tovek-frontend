<?php

// This file is a test to use with Google Merchant.
// Don't know if it will be used 

exit;

  // Bootstrap platform
require_once( dirname(dirname(__FILE__)) . '/platform/core/bootstrap.php' );

$aFields = [
  'id',               // required
  'title',            // required
  'description',      // required
  'link',             // required
  'image_link',       // required
  'availability',     // required
  'expiration_date',  // Format: 2016-07-11T11:07+0100
  'price',            // required
  'contition',
];

// Dependency files
$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );

$oAuctionItem->oDao->aSorting = [
  'itemEndTime' => 'ASC'
];

$oAuctionItem->oDao->setCriterias( [
  'active' => [
    'type' => '=',
    'value' => 'active',
    'fields' => 'itemStatus'
  ]
] );

$aItems = $oAuctionItem->read( [
    'itemId',
    'itemTitle',
    'itemEndTime',
    'itemMinBid',
    'routePath'
] );



echo '<pre>';
print_r( $aItems );
echo '</pre>';

echo 'id	title	description	link	price	availability	image_link	gtin	mpn	brand	update_type';

Product Example:
A2B4	Mens Pique Polo Shirt	Made from 100% organic cotton, this classic red men's polo has a slim fit and signature logo embroidered on the left chest. Machine wash cold; imported.		http://www.your_website.com/item1-info-page.html	15.00 USD	in_stock	http://www.your_website.com/image1.jpg	1367426	64286482	YourBrand	merge
-
