<?php

// This view require group key to be set
if( empty($_GET['groupKey']) ) return;

$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
$oNavigation->oDao->setLang( $GLOBALS['langIdEdit'] );
$oNavigation->setGroupKey( $_GET['groupKey'] );
$aNavigationItems = $oNavigation->read( array(
	'navigationId',
	'navigationUrl',
	'navigationTitle',
	'navigationLeft',
	'navigationRight'
) );
$aPlatformRoutes = arrayToSingle( $oRouter->read( 'routePath' ), null, 'routePath' );

$sOutput = '';

if( !empty($aNavigationItems) ) {
	$iPreviousDepth = 0;
	$sNavigationList = '';
	$bNoIcons = true;
	foreach( $aNavigationItems as $entry ) {
		if( $entry['depth'] > $iPreviousDepth ) {
			$sNavigationList .= '
			<ul>';
		} elseif(  $entry['depth'] < $iPreviousDepth  ) {
			$sNavigationList .= str_repeat( '
			</ul>
			</li>', $iPreviousDepth - $entry['depth'] );
		}

		$sLiClass = 'regular';
		$sIcon = '';

		// Check if the route exists in our system
		if( !empty($entry['navigationUrl']) && !in_array($entry['navigationUrl'], $aPlatformRoutes) ) {
			$sLiClass = 'missing';
			$sIcon = '<a href="' . $oRouter->getPath( 'adminInfoContentPageAdd' ) . '?useNav=' . $entry['navigationId'] . '" title="' . _( 'Create page for this link' ) . '"><span class="icon iconMissing"><span>' . _( 'Missing' ) . '</span></span></a>';
			$bNoIcons = false;
		}
		
		// Check if the route is external
		if( mb_substr($entry['navigationUrl'], 0, 4) == 'http' || mb_substr($entry['navigationUrl'], 0, 3) == 'www' ) {
			$sLiClass = 'external';
			$sIcon = '<span class="icon iconExternal"><span>' . _( 'External' ) . '</span></span>';
			$bNoIcons = false;
		}

		// Check if the entry is a special entry
		if( !empty($entry['navigationUrl']) && $entry['navigationUrl'] == '#' ) {
			$sLiClass = 'special';
			$sIcon = '<span class="icon iconInfo"><span>' . _( 'Info' ) . '</span></span>';
			$bNoIcons = false;
		}

		$sNavigationList .= '
			<li id="foo_' . $entry['navigationId'] . '" class="' . $sLiClass . '" data-nav-id="' . $entry['navigationId'] . '" data-nav-depth="' . $entry['depth'] . '">' . $sIcon . '' . $entry['navigationTitle'] . '
				<a href="?event=deleteNavigation&amp;deleteNavigation[]=' . $entry['navigationId'] . '&amp;deleteNavigation[]=' . $_GET['groupKey'] . '&amp;' . stripGetStr( array('event', 'deleteNavigation') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>
				<a href="?navigationId=' . $entry['navigationId'] . '&amp;' . stripGetStr( array('navigationId') ) . '" class="icon iconText iconEdit"><span>' . _( 'Edit' ) . '</span></a>';

		$sNavigationList .= ($entry['navigationRight'] - $entry['navigationLeft']) === 1 ? '</li>' : '';
		$iPreviousDepth = $entry['depth'];
	}
	$sNavigationList .= str_repeat( '
			</ul>
			</li>', $iPreviousDepth );

	$sOutput = '
		<ul class="treeList' . ($bNoIcons !== false ? ' noIcons' : '' ) . '">
			' . $sNavigationList . '
		</ul>';

	if( !$bNoIcons ) {
		$oTemplate->addBottom( array(
			'key' => 'jsTooltipNavigationIcons',
			'content' => '
			<div class="tooltip missing">
				<p>' . _('This route appears to be missing from the system') . '</p>
			</div>
			<div class="tooltip external">
				<p>' . _('This route appears to lead to an external site') . '</p>
			</div>
			<div class="tooltip special">
				<p>' . _('This route is linked to a special route. Most likly this navigation item is used for a icon or alike in the menu') . '.</p>
			</div>

			<script>
				$(document).ready( function() {
					$(".missing, .external, .special").mouseover(function() {
						currentlyHovering = $( "div.tooltip." + $(this).attr("class") );
						timer = setTimeout(function() {
							currentlyHovering.css({opacity:0.8}).show();
						}, 500 );
					}).mousemove(function(kmouse){
						currentlyHovering.css({left:kmouse.pageX+15, top:kmouse.pageY+15});
					}).mouseout(function(){
						clearInterval(timer);
						currentlyHovering.hide();
					});

				} );
			</script>'
		) );
	}
	
} else {
	$sOutput = '
		<strong>' . _( 'There are no items to show' ) . '</strong>';
		
}

echo '
	<h2>' . _( 'Menu' ) . '</h2>
	' . $sOutput;

$oTemplate->addBottom( array(
	'key' => 'jsNavigationSortable',
	'content' => '
		<script>
			$("ul.treeList").sortable( {
				update : function( event, ui ) {
					// Moved id
					var iMovedId = $(ui.item).data("nav-id");

					// Find next sibling
					var iNextSibling = null;
					var iSiblingDepth = null;
					var order = $("ul.treeList li").map( function() {
						//sNavList = sNavList + "order[]=" + $(this).data("nav-id") + "&";
						if( iNextSibling == true && iSiblingDepth == $(this).data("nav-depth") ) {
							iNextSibling = $(this).data("nav-id");
						}
						if( $(this).data("nav-id") == iMovedId ) {
							iNextSibling = true;
							iSiblingDepth = $(this).data("nav-depth");
						}
					} ).get();

					// Update
					$.ajax( {
						type: "POST",
						url: "' . $oRouter->sPath . '?navigationId=" + iMovedId + "&groupKey=' . $_GET['groupKey'] . '",
						data: {
							navigationRelation: "prevSibling",
							navigationTarget: iNextSibling,
							frmNavigationMove: "1"
						},
						success: function() {

						}
					} );
				}
			} );
		</script>
	'
) );

$oNavigation->oDao->setLang( $GLOBALS['langId'] );
