<?php

	function genericMessage( $sKey, $mReplaceItemName ) {

		$aMultiOcurringMessages	= array(
			'itemContent'			=> _( 'Custom text for %s may be entered here.' ),
			'itemContentTiny'		=> _( 'Custom text for %s may be entered here.' ) . ' ' . _( 'You may also add additional content like headings, links or images by using the different icons in the toolbar.' ),
			'itemEdit'				=> _( 'This is where you edit the information about %s.' ),
			'itemList'				=> _( 'When you create %s it will end up here.' ),
			'itemNew'				=> _( 'This is where you create %s.' ),
			'itemDateEndOptional'	=> _( 'If you want %s to only be shown until a specific date, you may choose one here. May be left empty.' ),
			'itemDateStartOptional'	=> _( 'If you want %s to be shown from a specific date, you may choose one here. If left empty, it will be published imediately on save.' ),
			'itemStatus'			=> _( 'The status indicates whether %s should be available to visitors or not.' ),
			'metaOptional'			=> _( 'These fields is mostly used for SEO (Search Engine Optimization) to help search engines interpret the content. These fields are optional and may be left empty.' ),
			'routeOptional'			=> _( 'Enter an adress for %s here. If you don\'t provide an adress the system will generate one for you. Internal adresses always begins with a slash ("/").' ),
			'title'					=> _( 'This is where you enter a title for %s.' ),
		);

		return sprintf( $aMultiOcurringMessages[ $sKey ], $mReplaceItemName );
	}

	$aSteps	= array(
		// Navigation
		'.view.navigation.formAdd'																=> genericMessage( 'itemNew', _( 'a menu item' ) ),
		'.view.navigation.formAdd .ui-tabs-nav'													=> _( 'Your visitors may belong to different groups. All visitors are guest, but if your site allow visitors to log in, you may optionally display a different menu to these users.' ),
		'.view.navigation.formAdd #navigationList'												=> genericMessage( 'itemList', _( 'a menu item' ) ),
		'.view.navigation.formAdd #navigationAdd'												=> genericMessage( 'itemEdit', _( 'a menu item' ) ),
		'.view.navigation.formAdd #navigationAdd #navigationTitle'								=> genericMessage( 'title', _( 'your menu item' ) ),
		'.view.navigation.formAdd #navigationAdd #navigationUrl'									=> _( 'Enter an adress here. This is where your user ends up when clicking the link. Internal links should start with "/", and all external links start with "http://" or "https://".' ),
		'.view.navigation.formAdd #navigationAdd #navigationOpenIn'								=> _( 'If your link is external you\'d might want the link to open up in a new window or tab. This allows your visitors to stay on your site.' ),
		'.view.navigation.formAdd #navigationAdd .move.form'										=> _( 'This is where you position your menu item.' ),
		'.view.navigation.formAdd #navigationAdd #navigationRelation'							=> _( 'How do you want to position your menu item? Before, after or inside another item? Pick the relation here.' ),
		'.view.navigation.formAdd #navigationAdd #navigationTarget'								=> _( 'What other menu item were you thinking of in the previous step?' ),

		// Info pages
		'.view.infoContentPageFormAdd .field:has( #layoutTitleTextId )'							=> genericMessage( 'title', _( 'your page' ) ) . ' ' . _( 'The title often appears in the top of your visitor\'s browser window, their history and search engines.' ),
		'.view.infoContentPageFormAdd .fieldRoutePath'											=> genericMessage( 'routeOptional', _( 'your page' ) ),
		'.view.infoContentPageFormAdd .fieldGroup.editor'										=> genericMessage( 'itemContentTiny', _( 'your page' ) ),
		'.view.infoContentPageFormAdd #aside .fieldGroup.status'								=> _( 'The page status changes who is able to view the page. If only the administrator should be able to view the page, choose "preview".' ),
		'.view.infoContentPageFormAdd #aside .fieldGroup.navigation'							=> _( 'Would you like to have the page added to your main menu? Check this.' ),
		'.view.infoContentPageFormAdd .fieldGroup.metadata'										=> genericMessage( 'metaOptional', _( 'your page' ) ),
		'.infoContentRevisions'																	=> _( 'Made a mistake? This is the history of your content. You may reset your current content and use your old content here.' ),
		'.infoContentTable'																		=> _( 'Parts of the site that are present on multiple places may be edit here. The footer is a common example of a info content block. Click on the info content block that you would like to edit.' ),

		// Slideshow
		'.view.slideshow.tableEdit .iconAdd'														=> genericMessage( 'itemNew', _( 'a new slide' ) ),
		'.view.slideshow.tableEdit .dataTable'													=> genericMessage( 'itemList', _( 'a slide' ) ) . ' ' . _( 'Click and drag a thumbnail to sort.' ),
		'.view.slideshowImageFormAdd .field:has( #slideshowImageUpload )'						=> _( 'Click the button and locate the image file on your computer. The image should be at least the size that are described in the hint to avoid damaging the site desgin. ' ),
		'.view.slideshowImageFormAdd .field:has( #slideshowImageStatus )'						=> genericMessage( 'itemStatus', _( 'a slide' ) ),
		'.view.slideshowImageFormAdd .field:has( #slideshowImageStart )'						=> genericMessage( 'itemDateStartOptional', _( 'a slide' ) ),
		'.view.slideshowImageFormAdd .field:has( #slideshowImageEnd )'							=> genericMessage( 'itemDateEndOptional', _( 'a slide' ) ),
		'.view.slideshowImageFormAdd .field:has( #slideshowImageTextId )'						=> genericMessage( 'itemContentTiny', _( 'a slide' ) ),
		'.view.layoutRelationTableEdit .dataTable:first'										=> _( 'The slide is shown on these pages.' ),
		'.view.layoutRelationTableEdit .dataTable:last'											=> _( 'These are all the pages on your site. Click on "Add" or "Remove" to add or remove from a page.' ),

		// News
		'.view.news.tableEdit'																	=> genericMessage( 'itemList', _( 'an article' ) ),
		'.newsFormAdd.view .field:has(#newsTitleTextId)'										=> genericMessage( 'title', _( 'an article' ) ),
		'.newsFormAdd.view .field:has(#routePath)'												=> genericMessage( 'routeOptional', _( 'an article' ) ),
		'.newsFormAdd.view .field:has(#newsSummaryTextId)'										=> _( 'The summary is a short introduction to the news. This text will be shown in the news list and in the beginning of the article.' ),
		'.newsFormAdd.view .field:has( #newsStatus )'											=> genericMessage( 'itemStatus', _( 'an article' ) ),
		'.newsFormAdd.view .field:has( #newsPublishStart )'										=> genericMessage( 'itemDateStartOptional', _( 'an article' ) ),
		'.newsFormAdd.view .field:has( #newsPublishEnd )'										=> genericMessage( 'itemDateEndOptional', _( 'an article' ) ),
		'.newsFormAdd.view .fieldGroup.editor'													=> genericMessage( 'itemContentTiny', _( 'an article' )  ),
		'.newsFormAdd.view .fieldGroup.metadata'												=> genericMessage( 'metaOptional', _( 'an article' )  ),

		// Orders
		'.orderTableEdit.view .ui-tabs li:has( a[href*=\"status=new\"] )'						=> _( 'Received orders with completed payment process. Note: completed payment process, must not be equal to paid. Please see payment status for this.' ),
		'.orderTableEdit.view .ui-tabs li:has( a[href*=\"status=intermediate\"] )'				=> _( 'Received orders with NOT completed payment process. The order has been created but not finished.' ),
		'.orderTableEdit.view .ui-tabs li:has( a[href*=\"status=processed\"] )'					=> _( 'A processed order is a managed order. Place orders here that you have handled in some way, but not yet is finish with.' ),
		'.orderTableEdit.view .ui-tabs li:has( a[href*=\"status=completed\"] )'					=> _( 'A completed order is a finished order. Place orders here that not need any more attention from you. Orders here is seen as a sales and will be counted for e.g. in the statistics.' ),
		'.orderTableEdit.view .ui-tabs li:has( a[href*=\"status=cancelled\"] )'					=> _( 'A cancelled order is a removed order. Orders here will not be take in to account by the system e.g. in the statistics.' ),
		'.orderTableEdit.view .ui-tabs li:has( a[href*=\"status=all\"] )'						=> _( 'Here is all orders listed, independent of order status or other specifications.' ),

		// Freight types
		'.view.freightTypesTable .dataTable'													=> _( 'Your available freight types' ),
		'.view.freightTypesTable .dataTable tr td.productRelationCount'							=> _( 'Amount of products that are possible to order with a specific type of freight' ),
		'.view.freightTypesTable .dataTable tr td.freightTypeControls .iconPackageLink'			=> _( 'From here can you handle freight addons depending on the total weight of a order' ),
		'.view.freightTypesTable .dataTable tr td.freightTypeControls .iconUser'				=> _( 'From here can you select which type of customers that a spcific freight type should be available for' ),
	);

?>
<link rel="stylesheet" href="/css/introjs.css">
<script src="/js/intro.js"></script>
<script>
	<?php
		// Output the steps as javascript objects
		$sSteps = '';
		foreach ($aSteps as $sSelector => &$sString) {
			$sSteps .= '"' . $sSelector . '" : "' . addslashes( $sString ) . '",';
		}
		echo 'var introSteps = {' . $sSteps . '};';
	?>

	$(function() {
		// Walk through the steps
		var steps = [];
		for( var i in introSteps ) {
			// If step element is found on page
			var elements = $( i );
			if( elements.length ) {
				// Add as an available step
				steps.push({
					element: elements[0],
					intro : introSteps[i]
				})
			}
		}

		// If any steps are available
		if( steps.length ) {
			// Create tour guide link
			var $listItem = $('<li class="intro"><a href="#"><?php echo _( 'Help' ); ?></a></li>').prependTo( '#wrapper > header nav.help > ul' );

			// Create the intro
			var tour = introJs();
			tour.setOptions({
				tooltipPosition		: 'auto',
				positionPrecedence	: ['left', 'right', 'bottom', 'top'],
				steps: steps
			});

			// On click, start introduction
			$( 'a', $listItem ).click(function( e ) {
				e.preventDefault();
				tour.start();
			})
		}
	});
</script>