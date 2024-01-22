<?php

$oLayout = clRegistry::get( 'clLayoutHtml' );
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

$oAuctionDao = $oAuctionEngine->getDao( 'Auction' );
$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );

$aSearchVariables = array(
	'auctions' => array(),
	'tags' => array()
);

// Get all active auctions
$aActiveAuctions = $oAuctionEngine->read( 'Auction', array(
	'auctionId',
	'auctionTitle'
) );
$aActiveAuctionIds = arrayToSingle( $aActiveAuctions, null, 'auctionId' );

// Get all tags in active auctions
if( !empty($aActiveAuctionIds) ) {
	$aTags = $oAuctionEngine->readRelationByAuction_in_AuctionTag( $aActiveAuctionIds );
	$aTagIds = arrayToSingle( $aTags, null, 'relationTagId' );
}

// Remove all other selections if the route is a tag route
$aObjectData = current($oRouter->readObjectByRoute($oRouter->iCurrentRouteId) );
if( !empty($aObjectData) && ($aObjectData['objectType'] == 'AuctionTag') ) {
	unset( $_SESSION['auctionSearch'] );

	$_GET['tagId'] = $aObjectData['objectId'];
}

// Search in archive parameter
if( isset($_GET['searchArchive']) ) {
	$_SESSION['auctionSearch']['searchArchive'] = (bool) $_GET['searchArchive'];
} else {
	$_SESSION['auctionSearch']['searchArchive'] = false;
}

// Check if nothing is selected
$bNothingSelected = true;
if( !empty($_SESSION['auctionSearch']['auctions']) ) {
	foreach( $_SESSION['auctionSearch']['auctions'] as $bSelected ) {
		if( $bSelected ) $bNothingSelected = false;
	}
}
if( !empty($_SESSION['auctionSearch']['tags']) ) {
	foreach( $_SESSION['auctionSearch']['tags'] as $bSelected ) {
		if( $bSelected ) $bNothingSelected = false;
	}
}

// Search by search phrase
$bNewSearchQuery = false;
if( !empty($_GET['searchQuery']) ) {
	if( !isset($_SESSION['auctionSearch']['searchQuery']) || ($_GET['searchQuery'] != $_SESSION['auctionSearch']['searchQuery']) ) {
		unset( $_SESSION['auctionSearch']['auctions'] );
		unset( $_SESSION['auctionSearch']['tags'] );

		$_SESSION['auctionSearch']['searchQuery'] = $_GET['searchQuery'];
		$bNewSearchQuery = true;
	}
}
// The search for words is done before auction and tag switching because a search difference will empty the tags and auctions
$aQuerySearchItems = false;
if( !empty($_SESSION['auctionSearch']['searchQuery']) ) {
	// Keep track of user's search strings
	$oAuctionEngine->create_in_AuctionSearch( array(
		'searchString' => $_SESSION['auctionSearch']['searchQuery'],
		'searchUserId' => !empty($_SESSION['userId']) ? $_SESSION['userId'] : '',
		'searchCreated' => date( 'Y-m-d H:i:s' )
	) );

	if( $_SESSION['auctionSearch']['searchArchive'] ) {
		$sReadStatus = 'ended';
	} else {
		$sReadStatus = 'active';
	}

	// Item data
	$aQuerySearchItems = arrayToSingle( $oAuctionEngine->readAuctionItem( array(
		'search' => $_SESSION['auctionSearch']['searchQuery'],
		'fields' => array( 'itemId', 'itemAuctionId' ),
		'status' => $sReadStatus
	) ), 'itemId', 'itemAuctionId' );

	// Auto-select tags and auctions (if new search)
	if( ($bNewSearchQuery === true) && !empty($aQuerySearchItems) ) {
		$aForceSelectTags = $oAuctionEngine->readRelationByItem_in_AuctionTag( array_keys($aQuerySearchItems) );
		foreach( $aForceSelectTags as $entry ) {
			$_SESSION['auctionSearch']['tags'][$entry['relationTagId']] = true;
		}

		foreach( $aQuerySearchItems as $iAuctionId ) {
			$_SESSION['auctionSearch']['auctions'][$iAuctionId] = true;
		}
	}

	// Change array to item ids
	$aQuerySearchItems = array_keys( $aQuerySearchItems );
}

// Handle the switching of auctions
$bAllAuctionsSelected = true;
if( !empty($_SESSION['auctionSearch']['auctions']) ) {
	foreach( $_SESSION['auctionSearch']['auctions'] as $key => $value ) {
		if( $value === false ) $bAllAuctionsSelected = false;
	}
}
if( isset($_GET['auctionId']) ) {
	if( $_GET['auctionId'] == 'all' ) {
		$bAllAuctionsSelected = !$bAllAuctionsSelected;
		$_SESSION['auctionSearch']['auctions'] = array_fill_keys( array_keys($_SESSION['auctionSearch']['auctions']), $bAllAuctionsSelected );
	} else {
		if( isset($_SESSION['auctionSearch']['auctions'][$_GET['auctionId']]) ) {
			$_SESSION['auctionSearch']['auctions'][$_GET['auctionId']] = ( $_SESSION['auctionSearch']['auctions'][$_GET['auctionId']] ? false : true );
		} else {
			$_SESSION['auctionSearch']['auctions'][$_GET['auctionId']] = true;
		}

		// If nothing is selected, all tags in selected auction should be selected
		if( $bNothingSelected ) {
			$aForceSelectTags = $oAuctionEngine->readRelationByAuction_in_AuctionTag( $_GET['auctionId'] );
			foreach( $aForceSelectTags as $entry ) {
				$_SESSION['auctionSearch']['tags'][$entry['relationTagId']] = true;
			}
		}
	}
}

// Handle the switching of tags
$bAllTagsSelected = true;
if( !empty($_SESSION['auctionSearch']['tags']) ) {
	foreach( $_SESSION['auctionSearch']['tags'] as $key => $value ) {
		if( $value === false ) $bAllTagsSelected = false;
	}
}
if( isset($_GET['tagId']) ) {
	if( $_GET['tagId'] == 'all' ) {
		$bAllTagsSelected = !$bAllTagsSelected;

		$_SESSION['auctionSearch']['tags'] = array_fill_keys( array_keys($_SESSION['auctionSearch']['tags']), $bAllTagsSelected );
	} else {
		if( isset($_SESSION['auctionSearch']['tags'][$_GET['tagId']]) ) {
			$_SESSION['auctionSearch']['tags'][$_GET['tagId']] = !$_SESSION['auctionSearch']['tags'][$_GET['tagId']];
		} else {
			$_SESSION['auctionSearch']['tags'][$_GET['tagId']] = true;
		}

		// If nothing is selected, all auctions in selected tag should be selected
		if( $bNothingSelected ) {
			$aForceSelectAuctions = $oAuctionEngine->readRelationByTag_in_AuctionTag( $_GET['tagId'] );
			foreach( $aForceSelectAuctions as $entry ) {
				$_SESSION['auctionSearch']['auctions'][$entry['relationAuctionId']] = true;
			}
		}
	}
}

// Arrange the search session with auctions
$aAuctionSession = array();
foreach( $aActiveAuctionIds as $iAuctionId ) {
	if( isset($_SESSION['auctionSearch']['auctions'][$iAuctionId]) && $_SESSION['auctionSearch']['auctions'][$iAuctionId] ) {
		$aAuctionSession[$iAuctionId] = true;
		$aSearchVariables['auctions'][] = $iAuctionId;
	} else {
		$aAuctionSession[$iAuctionId] = false;
	}
}
$_SESSION['auctionSearch']['auctions'] = $aAuctionSession;

// Arrange the search session with tags
$aTagSession = array();
if( !empty($aTagIds) ) {
	foreach( $aTagIds as $iTagId ) {
		if( isset($_SESSION['auctionSearch']['tags'][$iTagId]) && $_SESSION['auctionSearch']['tags'][$iTagId] ) {
			$aTagSession[$iTagId] = true;
			$aSearchVariables['tags'][] = $iTagId;
		} else {
			$aTagSession[$iTagId] = false;
		}
	}
}
$_SESSION['auctionSearch']['tags'] = $aTagSession;


$aAuctionSearchItems = array();
if( !empty($aSearchVariables['auctions']) ) {
	$aAuctionSearchItems = arrayToSingle( $oAuctionEngine->readByAuction_in_AuctionItem($aSearchVariables['auctions'], null, array('itemId')), null, 'itemId' );
}
$aTagSearchItems = array();
if( !empty($aSearchVariables['tags']) ) {
	$aTagSearchItems = arrayToSingle( $oAuctionEngine->readRelationByTag_in_AuctionTag($aSearchVariables['tags'], array('relationItemId')), null, 'relationItemId' );
}

// Merge the auction, tag and search query selections
if( $_SESSION['auctionSearch']['searchArchive'] ) {
	$aItemIds = $aQuerySearchItems;
} else if( !empty($_SESSION['auctionSearch']['searchQuery']) ) {
	/**
	 * Custom by Mikael 6/7/2015
	 * Reson: items without tag did not show up
	 * This ads items without tags
	 */
	$aNoTagItems = array_diff( $aQuerySearchItems, $aTagSearchItems );
	$aTagSearchItems = array_merge( $aNoTagItems, $aTagSearchItems );

	$aItemIds = array_intersect( $aAuctionSearchItems, $aTagSearchItems, $aQuerySearchItems );
} else {
	$aItemIds = array_intersect( $aAuctionSearchItems, $aTagSearchItems );
}

// "Search" by item found by auction and/or tag
if( !empty($aItemIds) ) {

	// Sort
	$oAuctionItemDao->aSorting = array(
		'itemSortNo' => 'ASC'
	);

	// Search in archive?
	if( $_SESSION['auctionSearch']['searchArchive'] ) {
		$sReadStatus = 'ended';
	} else {
		$sReadStatus = 'active';
	}

	// Item data
	$aItemData = $oAuctionEngine->readAuctionItem( array(
		'fields' => '*',
		'itemId' => $aItemIds,
		'status' => $sReadStatus
	) );
	$oAuctionItemDao->aSorting = null;
	$oAuctionItemDao->sCriterias = null;
}

// Create auction select list
$sAuctionList ='';
if( !empty($aActiveAuctionIds) ) {
	// Auction data
	$aAuctionData = valueToKey( 'auctionId', $oAuctionEngine->read( 'Auction', array(
		'auctionId',
		'auctionTitle',
		'auctionLocation',
	), $aActiveAuctionIds ) );

	$sAuctionList .= '
		<div class="auctionSelect selectContainer">
			<p>
				<a href="#" id="auctionDisplay" class="selectToggle"><img src="/images/templates/tovekClassic/bg-black-arrow-up.png" alt="" /></a>&nbsp;
				' . _( 'Select auctions to search in') . '
				<a href="?auctionId=all&tagId=all" class="selectAll' . ( $bAllAuctionsSelected ? ' active' : '' ) . '">' . _( 'All auctions' ) . '</a>
			</p>
			<ul class="selectList">';

	foreach( $aAuctionData as $iAuctionId => $aAuction ) {
		$aClass = array();
		if( $_SESSION['auctionSearch']['auctions'][$iAuctionId] ) {
			$aClass[] = 'active';
		} else {
			$aClass[] = 'inactive';
		}

		$sAuctionList .= '
				<li>
					<div' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '') . '>
						<a href="' . $oRouter->getPath( 'guestAuctionSearch' ) . '?auctionId=' . $iAuctionId . '" title="' . $aAuction['auctionTitle'] . '"><strong>' . $aAuction['auctionTitle'] . '</strong><span class="location">' . $aAuction['auctionLocation'] . '</span><div class="location">' . $aAuction['auctionLocation'] . '</div></a>
					</div>
				</li>';
	}

	$sAuctionList .= '
			</ul>
		</div>';
}

// Create tag select list
$sTagList ='';
if( !empty($aTagIds) ) {
	// Tag data
	$aTagData = valueToKey( 'tagId', $oAuctionEngine->read( 'AuctionTag', '*', $aTagIds ) );

	$sTagList .= '
		<div class="tagSelect selectContainer">
			<p>
				<a href="#" id="tagDisplay" class="selectToggle"><img src="/images/templates/tovekClassic/bg-black-arrow-up.png" alt="" /></a>&nbsp;
				' . _( 'Select tags to search for') . '
				<a href="?tagId=all&auctionId=all" class="selectAll' . ( $bAllTagsSelected ? ' active' : '' ) . '">' . _( 'All tags' ) . '</a>
			</p>
			<ul class="selectList">';

	foreach( $aTagData as $iTagId => $aTag ) {
		$aClass = array();
		if( $_SESSION['auctionSearch']['tags'][$iTagId] ) {
			$aClass[] = 'active';
		} else {
			$aClass[] = 'inactive';
		}

		$sTagList .= '
				<li>
					<div' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '') . '>
						<a href="' . $oRouter->getPath( 'guestAuctionSearch' ) . '?tagId=' . $iTagId . '"><strong>' . $aTag['tagTitle'] . '</strong></a>
					</div>
				</li>';
	}

	$sTagList .= '
			</ul>
		</div>';
}

// Print auction and tag select lists
if( $_SESSION['auctionSearch']['searchArchive'] == false ) {

	/*** Check´s if the request is made by ajax ***/
	if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
		// Ajax
	} else {
		$sTagSelect = '
			<div class="auctionTagSelect">
				' . $sAuctionList . '
				' . $sTagList . '
				<br class="clear" />
			</div>';
	}
}


if( !empty($aItemData) ) {
	// Item related variables
	$aItemIds = arrayToSingle($aItemData, null, 'itemId');
	$aItemToAuction = arrayToSingle($aItemData, 'itemId', 'itemAuctionId');

	// Auction data
	$aAuctionData = valueToKey( 'auctionId', $oAuctionEngine->read( 'Auction', array(
		'auctionId',
		'auctionType',
		'auctionTitle',
		'auctionSummary',
		'auctionDescription',
		'auctionLocation',
		'auctionArchiveStatus',
		'auctionCreated',
		'partId',
		'partTitle',
		'partDescription',
		'partPreBidding',
		'partAuctionStart',
		'partStatus',
		'partHaltedTime',
		'partCreated',
		'partAuctionId'
	), $aActiveAuctionIds ) );

	// The item list
	if( !empty($aItemData) ) {
		// Entries sequence
		$iEntriesSequence = null;
		if( !empty($_GET['entriesSequence']) ) {
			$iEntriesSequence = $_GET['entriesSequence'];
		} elseif( !empty($_COOKIE['AuctionItemLists']) ) {
			$aParams = json_decode( $_COOKIE['AuctionItemLists'], true );

			if( !empty($aParams['auctionSearch']['entriesSequence']) ) {
				$iEntriesSequence = $aParams['auctionSearch']['entriesSequence'];
			}
		}

		// Item list
		clFactory::loadClassFile( 'clOutputHtmlAuction' );
		$oOutputHtmlAuctionItems = new clOutputHtmlAuction( array(
			'listKey' => 'auctionSearch',
			'viewFile' => 'auction/searchResult.php',
			'title' => '&nbsp;',
			'viewmode' => 'mixed',
			'sortType' => 'itemNo',
			'entries' => '8',
			'entriesSequence' => $iEntriesSequence,
			'listAll' => !empty($_GET['listAll']) ? $_GET['listAll'] : false
		) );
		$oOutputHtmlAuctionItems->addAuctionData( $aAuctionData );
		$oOutputHtmlAuctionItems->addItemData( $aItemData );

		///*** Check´s if the request is made by ajax ***/
		//if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
		//	// Ajax
		//	echo $oOutputHtmlAuctionItems->renderListContent();
		//} else {
			// Normal
			echo '
				<div class="view auction itemList searchList">
					<h1>' . _( 'Sökning på' ) . ': "' . $_SESSION['auctionSearch']['searchQuery'] . '"</h1>
					' . $oOutputHtmlAuctionItems->render() . '
				</div>';
		//}
	}
}

if( empty($aItemData) ) {
	// Reset search

	$sSearchString = !empty($_SESSION['auctionSearch']['searchQuery']) ? $_SESSION['auctionSearch']['searchQuery'] : '';

	echo '
		<div class="view auction itemList searchList">
			<h1>' . _( 'Sökning på' ) . ': "' . $sSearchString . '"</h1>
			' . _( 'Inget hittades på sökningen' ) . '"' . $sSearchString . '". ' . _( 'Letar du efter avslutade rop så kan du kryssa i rutan "Avslutade auktioner".' ) . '
			<br /><br />
		</div>';
}

// JavaScript
$oTemplate->addBottom( array(
	'key' => 'searchViewScript',
	'content' => '
		<script>
			$("a.selectToggle").click( function(event) {
				event.preventDefault();
				$( this ).parent().parent().find("ul.selectList").slideToggle( "fast", function() {
					if( $(this).is(":visible") ) {
						$( this ).parent().find("a.selectToggle img").attr( "src", "/images/templates/tovekClassic/bg-black-arrow-up.png" );
					} else {
						$( this ).parent().find("a.selectToggle img").attr( "src", "/images/templates/tovekClassic/bg-black-arrow-down.png" );
					}
				} );
			} );
	'
) );
