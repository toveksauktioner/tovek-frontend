<?php

// Category list
$sCategoryList = '';

$oCategory = clRegistry::get( 'clCustomerCategory', PATH_MODULE . '/customer/models' );

// Edit language
$oCategory->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

if( empty($aCategoriesData) ) {
	$aCategoriesData = $oCategory->aHelpers['oTreeHelper']->readWithChildren( 0, array(
		'categoryId',
		'categoryTitleTextId' => 'categoryTitleTextId',
		'categoryLeft',
		'categoryRight'
	) );
	$aCategoriesData2 = arrayToSingle( $oCategory->read( array(
		'categoryId',
		'categoryTitleTextId' => 'categoryTitleTextId'
	) ), 'categoryId', 'categoryTitleTextId' );
}

if( !empty($aCategoriesData) ) {
	$iPreviousDepth = 0;
	$sCategoryList .= '
		<div class="treecontrol">
			<a title="" href="#">' . _( 'Close all' ) . '</a> |
			<a title="" href="#">' . _( 'Open all' ) . '</a> | 
			<a title="" href="#">' . _( 'Toggle all' ) . '</a>
		</div>
		<ul class="treeList">';
	
	// For admin routes in administration language
	$oRouter->oDao->setLang( $GLOBALS['langId'] );
	$sUrlCustomers = $oRouter->getPath( 'adminCustomers' );
	$sUrlCustomerCategories = $oRouter->getPath( 'adminCustomerCategories' );
	$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );
	
	foreach( $aCategoriesData as $entry ) {
		if( $entry['depth'] > $iPreviousDepth ) {
			$sCategoryList .= '
			<ul>';
		} elseif(  $entry['depth'] < $iPreviousDepth  ) {
			$sCategoryList .= str_repeat( '
			</ul>
			</li>', $iPreviousDepth - $entry['depth'] );
		}

		$sCategoryList .= '
			<li>
				<a href="' . $sUrlCustomers . '?categoryId=' . $entry['categoryId'] . '">' . $aCategoriesData2[ $entry['categoryId'] ] . '</a>
				<a href="' . $sUrlCustomerCategories . '?categoryId=' . $entry['categoryId'] . '" class="icon iconEdit"><span>' . _( 'Edit' ) . '</span></a>
				<a href="' . $sUrlCustomerCategories . '?event=deleteCustomerCategory&amp;deleteCustomerCategory=' . $entry['categoryId'] . '" class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>';

		$sCategoryList .= ($entry['categoryRight'] - $entry['categoryLeft']) === 1 ? '</li>' : '';
		$iPreviousDepth = $entry['depth'];
	}
	$sCategoryList .= str_repeat( '
			</ul>
			</li>', $iPreviousDepth );
	$sCategoryList .= '
		</ul>';
		
	$oTemplate->addScript( array(
		'key' => 'jqueryTreeview',
		'src' => '/js/jquery.treeview.js'
	) );
	$oTemplate->addLink( array(
		'key' => 'jqueryTreeviewCss',
		'href' => '/css/jquery.treeview.css'
	) );
	$oTemplate->addBottom( array(
		'key' => 'jqueryTreeviewInit',
		'content' => '
			<script type="text/javascript">
				$(".treeList").treeview({
					control: ".treecontrol",
					persist: "location",
					collapsed: true
				});
			</script>
		'
	) );
} else {
	$sCategoryList .= '
		<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view categoryListEdit">
		<h3>' . _( 'Categories' ) . '</h3>'
		. $sCategoryList . '
	</div>';

$oCategory->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );