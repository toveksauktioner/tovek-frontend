<?php

require_once PATH_FUNCTION . '/fOutputHtml.php';
require_once PATH_FUNCTION . '/fMoney.php';

class clOutputHtmlAuction {

	private $aDataDict = array();
	private $aAuctionData = array();
	private $aItemData = array();

	private $sListKey;
	private $sViewFile;

	private $sTitle;

	private $iEntries;
	private $iEntriesSequence;
	private $iEntriesTotal;
	private $iEntriesShown;
	private $bListAll;

	public $sActiveSortType;

	private $oImage;
	private $oObjectStorage;

	public function __construct( $aParams = array(), $aDataDict = array() ) {
		$this->init( $aParams, $aDataDict );
	}

	public function init( $aParams = array(), $aDataDict = array()  ) {
		if( !empty($aDataDict) ) {
			$this->aDataDict = $aDataDict;
		} else {
			$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
			$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );
			$this->aDataDict = $oAuctionItemDao->getDataDict();

			// Read favorites
			if( empty($oAuctionEngine->aUserFavoriteItems) && !empty($_SESSION['userId']) ) {
				$aUserFavoriteItems = $oAuctionEngine->readFavoritesByUser_in_AuctionItem( $_SESSION['userId'] );
				if( !empty($aUserFavoriteItems) ) {
					$oAuctionEngine->aUserFavoriteItems = arrayToSingle( $aUserFavoriteItems, 'itemId', 'itemId' );
				}
			}
		}

		// Image module
		$this->oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
		$this->oObjectStorage = clRegistry::get( 'clObjectStorage', PATH_MODULE . '/objectStorage/models' );

		$this->setParams( $aParams );
	}

	public function setParams( $aParams ) {
		// Sorting
		$this->aSortTypes = array(
			'no' => _( 'Efter rop-nr.' ),
			'title' => _( 'Efter namn' ),
			'highestBid' => _( 'Högsta slutpris' ),
			'lowestBid' => _( 'Lägsta slutpris' )
		);
		if( !empty($_GET['sortBy']) ) {
			$this->sActiveSortType = $_GET['sortBy'];
		} else {
			$this->sActiveSortType = !empty($aParams['sortType']) ? $aParams['sortType'] : null;
		}

		$this->sListKey = !empty($aParams['listKey']) ? $aParams['listKey'] : null;
		$this->bArchived = ( in_array($this->sListKey, array('itemListArchived','wonItems')) ? true : false );
		$this->sViewFile = !empty($aParams['viewFile']) ? $aParams['viewFile'] : null;
		$this->sTitle = !empty($aParams['title']) ? $aParams['title'] : null;
		$this->bNextAuctionButton = isset($aParams['nextAuctionButton']) ? $aParams['nextAuctionButton'] : true;
		$this->aSearchForm = !empty($aParams['searchForm']) ? $aParams['searchForm'] : null;
		$this->bShowEnded = !empty($aParams['showEnded']) ? $aParams['showEnded'] : false;
		$this->sPaginationType = !empty($aParams['paginationType']) ? $aParams['paginationType'] : 'normal';
		$this->iPaginationStartNo = !empty($aParams['paginationStartNo']) ? $aParams['paginationStartNo'] : 1;
		$this->iPaginationFirstActivePage = !empty($aParams['paginationFistActivePage']) ? $aParams['paginationFistActivePage'] : false;
		$this->aAdditionalFilterTools = !empty($aParams['additionalFilterTools']) ? $aParams['additionalFilterTools'] : false;
		$this->aTags = !empty($aParams['tags']) ? $aParams['tags'] : false;

		// Entries
		$this->iEntriesShown = 0;
		$this->iEntries = AUCTION_ITEM_PAGINATION; # !empty($aParams['entries']) ? $aParams['entries'] : null;
		$this->iEntriesSequence = !empty($aParams['entriesSequence']) ? $aParams['entriesSequence'] : 1;
		$this->iEntriesTotal = !empty($aParams['entriesTotal']) ? $aParams['entriesTotal'] : 0;

		// if( $this->iEntriesSequence !== null ) {
		// 	$this->iEntriesTotal = $this->iEntries * $this->iEntriesSequence;
		// } else {
		// 	$this->iEntriesTotal = $this->iEntries;
		// }

		// List all
		$this->bListAll = false;
		if( DEFCON_LEVEL >= 5 && isset($aParams['listAll']) && $aParams['listAll'] ) {
			// $this->iEntriesTotal = null;
			$this->bListAll = true;
		}

		// Image params
		$this->oImage->setParams( array('parentType' => 'AuctionItem') );
		$this->oImage->oDao->aSorting = array(
			'imageSort' => 'ASC',
			'imageCreated' => 'ASC'
		);
		$this->oImage->oDao->setEntries( 1 );

		// Cookie data
		// if( !empty($_COOKIE['AuctionItemLists']) ) {
		// 	$aCookieData = json_decode( $_COOKIE['AuctionItemLists'], true );
		// 	$oRouter = clRegistry::get( 'clRouter' );
		// 	if( array_key_exists($this->sListKey, $aCookieData) && urldecode($oRouter->sRefererRoute) != $oRouter->sPath && $oRouter->sPath == $aCookieData[ $this->sListKey ]['routePath'] ) {
		// 		$this->sActiveViewMode = $aCookieData[ $this->sListKey ]['viewmode'];
		// 		$this->iEntries = !empty($aCookieData[ $this->sListKey ]['entries']) ? $aCookieData[ $this->sListKey ]['entries'] : $this->iEntries;
		// 		$this->iEntriesSequence = $aCookieData[ $this->sListKey ]['entriesSequence'];
		// 		$this->iEntriesTotal = $this->iEntries * $this->iEntriesSequence;
		// 	}
		// }
	}

	public function addAuctionData( $aAuctionData ) {
		$this->aAuctionData = $aAuctionData;
	}

	public function addItemData( $aItemData ) {
		$this->aItemData = $aItemData;
	}

	public function createSortTypeList( $aSortTypes = array() ) {
		if( in_array($this->sListKey, array('userBidItems','favItems','wonItems')) ) return '';

		if( empty($aSortTypes) ) $aSortTypes = $this->aSortTypes;
		$oRouter = clRegistry::get( 'clRouter' );

		// if( !empty($_GET['sortBy']) ) {
		// 	$aSortTypes2 = array( $_GET['sortBy'] => $aSortTypes[ $_GET['sortBy'] ] );
		// 	foreach( $aSortTypes as $sType => $sTypeLabel ) {
		// 		if( $sType == $_GET['sortBy'] ) continue;
		// 		$aSortTypes2[ $sType ] = $sTypeLabel;
		// 	}
		// 	$aSortTypes = $aSortTypes2;
		// }

		$sActiveSort = _( 'Sort by' );
		$sSortTypeList = '';
		foreach( $aSortTypes as $sType => $sTypeLabel ) {
			$aClass = array();

			if( $sType == $this->sActiveSortType ) {
				$aClass[] = 'active';
				$sActiveSort = $sTypeLabel;
			}

			$sSortTypeList .= '
				<li' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '') . '>
				<a class="ajax" href="?sortBy=' . $sType . '">' . $sTypeLabel . '</a>
			</li>';
		}
		return '
			<div class="sorting">
				<span class="label">
					<i class="fas fa-filter icon">&nbsp;</i>
					<span>
						' . $sActiveSort . '
						<i class="fas fa-chevron-down arrow"></i>
					</span>
				</span>
				<div class="optionsContainer">
					<ul class="options">
						' . $sSortTypeList . '
					</ul>
				</div>
			</div>';
		// &#8897; &#x022C1; &xvee; &Vee; &bigvee;
	}

	public function createSearchForm( $sMethod = 'GET' ) {
		if( empty($this->aSearchForm) ) return;

		$aIgnoreHidden = $this->aSearchForm;
		$aIgnoreHidden[] = 'page';

		$oRouter = clRegistry::get( 'clRouter' );

		$sFields = '';
		foreach( $this->aSearchForm as $sSearchField ) {
			$sFields .= '
				<div class="field text noicon">
					<input type="number" name="' . $sSearchField . '" id="' . $sSearchField . '" class="text" placeholder="' . _( 'Rop-nr' ) . '">
				</div>';
		}

		$sHidden = '';
		if( $sMethod == 'GET' )
		foreach( $_GET as $key => $value ) {
			if( in_array($key, $aIgnoreHidden) ) {
				unset( $_GET[$key] );
			} else {
				$sHidden .= '
					<input type="hidden" name="' . $key . '" value="' . $value . '" />';
			}
		}

		$sAction = $oRouter->sPath;
		if( $sMethod != 'GET' ) {
			$sAction = '?' . http_build_query( $_GET );
		}

		return '
			<form ' . ( !empty($sAction) ? 'action="' . $sAction . '"' : '' ) . ' method="' . $sMethod . '" class="newForm noLabel searchForm small">
			' . $sFields . '
				' . ( !empty($sHidden) ? '<div class="hidden">' . $sHidden . '</div>' : '' ) . '
				<p class="buttons">
					<button type="submit"><i class="fas fa-search"></i></button>
				</p>
			</form>';
	}

	public function generateFavoriteLink( $iItemId, $sRoute = null ) {
		// $oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

		// $sStatus = 'true';

		// if( !empty($_SESSION['userId']) ) {
		// 	if( !empty($oAuctionEngine->aUserFavoriteItems) && in_array($iItemId, $oAuctionEngine->aUserFavoriteItems) ) {
		// 		$sStatus = 'false';
		// 	}
		// 	$sRoute = "?ajax=true&status=" . $sStatus . "&event=favoriteItem&favoriteItem=" . $iItemId;
		// 	$sClass = "ajax favLink";

		// } else {
		// 	$oRouter = clRegistry::get( 'clRouter' );
		// 	$sClass = "ajax favLink popupLink";
		// 	if( empty($sRoute) ) $sRoute = $oRouter->getPath( 'userLogin' );
		// }

		// if( !empty($oAuctionEngine->aUserFavoriteItems) && !empty($oAuctionEngine->aUserFavoriteItems[ $iItemId ]) ) {
		// 	$sClass .= ' selected';
		// }

		return '
			<div class="favLinkContainer" data-item-id="' . $iItemId . '"><i class="fas fa-star"></i></div>';
	}

	public function createList() {
		$sItemEntry = '';
		$sItemList = '';
		$iTime = time();

		$oRouter = clRegistry::get( 'clRouter' );
		$sSignupUrl = $sRoute = $oRouter->getPath( 'userLogin' );

		// Check for selected items in session
		// $iGUISelectedItem = ( (!empty($this->aAuctionData['partId']) && !empty($_SESSION['browser']['auction'][ $this->aAuctionData['partId'] ]['auctionSelectedItem'])) ? $_SESSION['browser']['auction'][ $this->aAuctionData['partId'] ]['auctionSelectedItem'] : null );


		// S3 Object Storage image data
		$this->oObjectStorage->oDao->aSorting = [
			'entObjectStorageToDbObject.parentSort' => 'ASC',
			'entObjectStorage.objectId' => 'ASC'
		];
 		$aObjectStorageImageData = groupByValue( 'parentId', $this->oObjectStorage->readWithParams([
			'type' => 'image',
			'parentTable' => 'entAuctionItem',
			'parentId' => arrayToSingle($this->aItemData, null, 'itemId'),
			'includeVariants' => true
		]) );
		$this->oObjectStorage->oDao->aSorting = [];
// echo '<pre>' . print_r( $aObjectStorageImageData, true ) . '</pre>';

		$iCount = 0;
		foreach( $this->aItemData as $aEntry ) {
			$aClass = array( 'itemEntry' );

			// Continue if past in time
			//if( time() > strtotime($aEntry['itemEndTime']) ) continue;
			$iEndTime = strtotime( $aEntry['itemEndTime'] );
			$iTimeSinceEnded = $iTime - $iEndTime;

			/**
			 * Ended item
			 */
			$bEnded = false;
			if( $iTimeSinceEnded > 0 ) {
				$bEnded = true;
			}

			/**
			 * Cancelled item
			 */
			$bCancelled = false;
			if( $aEntry['itemMinBid'] == 0 && $aEntry['itemMarketValue'] == 0 ) {
				$bCancelled = true;
				$aClass[] = 'cancelled';
			}

			// if( $this->iEntriesTotal !== null && $iCount >= $this->iEntriesTotal ) break;


			if( !empty($aEntry['partStatus']) && $aEntry['partStatus'] == 'halted' ) $aClass[] = 'halted';
			if( ($aEntry['itemStatus'] == 'ended') || ($iEndTime < $iTime) ) $aClass[] = 'ended';


			$sImage = '';
			$sAllImagesButton = '';

			// Image (S3 Object Storage)
			if( !empty($aObjectStorageImageData[ $aEntry['itemId'] ]) && ($aEntry['itemMinBid'] > 0) ) {
				// Note: if the item min bid is 0 the item is cancelled and shall not show any images.
				$aImage = [];

				// Get the first object in list with all variants
				$iFirstObjectId = null;
				foreach( $aObjectStorageImageData[ $aEntry['itemId'] ] as $aObjectStorageImage ) {
					if( !empty($iFirstObjectId) && ($aObjectStorageImage['objectId'] != $iFirstObjectId) ) break;

					$aImage[ $aObjectStorageImage['objectVariant'] ] = $aObjectStorageImage;
					$iFirstObjectId = $aObjectStorageImage['objectId'];
				}

				$sImage = '
					<picture class="loadImages">
						<source media="(min-width: 650px)" srcset="/images/templates/tovek/itemPreLoadImage.png" data-srcset="' . $aImage['small']['objectUrl'] . '">
						<source media="(min-width: 465px)" srcset="/images/templates/tovek/itemPreLoadImage.png" data-srcset="' . $aImage['medium']['objectUrl'] . '">
						<img src="/images/templates/tovek/itemPreLoadImage.png" data-src="' . $aImage['small']['objectUrl'] . '" alt="" />
					</picture>';
			}

			// No images found
			if( empty($sImage) ) {
	 			$sImage = '
	 				 <picture>
	 					<source media="(min-width: 650px)" srcset="/images/templates/tovek/itemEmptyImage.png">
	 					<source media="(min-width: 465px)" srcset="/images/templates/tovek/itemEmptyImage.png">
	 					<img src="/images/templates/tovek/itemEmptyImage.png" alt="no-image" />
	 				</picture>';

			} else {
				$sAllImagesButton = '
					<a href="?ajax=1&view=auctionAjax/itemShowImages.php&itemId=' . $aEntry['itemId'] . '" class="ajax popupLink full button white small" data-size="full" data-back-text="' . _( 'Tillbaka till ropet' ) . '">
						<i class="fas fa-images">&nbsp;</i>' . _( 'Bilder / Info' ) . '
					</a>';
			}



			/**
			 * Bid value
			 */
			 $sCurrentBid = '<span class="itemCurrentBid itemCurrentBid' . $aEntry['itemId'] . '" data-item-id="' . $aEntry['itemId'] . '"></span>';
 			if( !$this->bArchived ) {
				$fBidValue = !empty($aEntry['bidValue']) ? $aEntry['bidValue'] : $aEntry['itemMinBid'];
				// $sCurrentBid = '';
				$sCurrentBidTime = !empty($aEntry['bidCreated']) ? $aEntry['bidCreated'] : ''; // 'Lagt 30 jan 2019';
				if( !empty($aEntry['auctionType']) && $aEntry['auctionType'] != 'live' ) {
					$sCurrentBid = '<span class="itemCurrentBid itemCurrentBid' . $aEntry['itemId'] . '" data-item-id="' . $aEntry['itemId'] . '">' . (empty($fBidValue) || $fBidValue == '0' ? _( 'Expires' ) : '') . '</span>';
					if( !empty($fBidValue) || $fBidValue != '0' ) {
						$sCurrentBidTime = '<span class="itemCurrentBidTime' . $aEntry['itemId'] . '"></span>';
					}
				}
				$sNextBidValue = '';

			} else {
				$fBidValue = !empty($aEntry['itemWinningBidValue']) ? $aEntry['itemWinningBidValue'] : $aEntry['itemMinBid'];
				$iBidCount = !empty($aEntry['itemBidCount']) ? $aEntry['itemBidCount'] : 0;
				$sCurrentBid = '<span>' . calculatePrice( $fBidValue, array('profile' => 'human') ) . ' (' . $iBidCount . ')</span>';
			}

			/**
			 * Handling of shown bid price
			 */
			if( !empty($this->aAuctionData[ $aEntry['itemAuctionId'] ]) ) {
				$sAuctionTitle = $this->aAuctionData[ $aEntry['itemAuctionId'] ]['auctionTitle'];
			} elseif( !empty($aEntry['auctionTitle']) ) {
				$sAuctionTitle = $aEntry['auctionTitle'];
			} else {
				$sAuctionTitle = '';
			}

			// Registration plate is not included in title after 30 days
			$sRegNo = '';
			if( !empty($aEntry['itemRegistrationPlate']) && ($iTimeSinceEnded < 2592000) ) {
				$sRegNo = ' [' . $aEntry['itemRegistrationPlate'] . ']';
			}

			/**
			 * Bid form
			 */
			$sBidForm = '';
			$sBidHistory = '';
			//$oLayout = clRegistry::get( 'clLayoutHtml' );
			//$GLOBALS['viewParams']['auction']['bidFormAdd.php']['item'] = $aEntry;
			//$GLOBALS['viewParams']['auction']['bidList.php']['item'] = $aEntry;
			//$sBidForm = $oLayout->renderView( 'auction/bidFormAdd.php' );
			//$sBidHistory = $oLayout->renderView( 'auction/bidList.php' );

			$sLocation = '';
			if( !empty($aEntry['itemAddressId']) ) {
				/**
				 * Google maps address
				 */
				$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
				$oBackEnd->setSource( 'entAuctionAddress', 'addressId' );
				$aAddress = current( $oBackEnd->read(array(
				 'addressAddress',
				 'addressTitle',
				 'addressHidden'
			 ), $aEntry['itemAddressId']) );
				if( !empty($aAddress) ) {

					if( $aAddress['addressHidden'] != 'yes' ) {
						// Google maps link
						$sMapUrl = 'https://www.google.com/maps/search/?api=1&query=';
	 					if( !empty(trim($aAddress['addressAddress'])) ) {
							$sMapUrl .= preg_replace( '/\s+/', '+', $aAddress['addressAddress'] );
						} elseif( !empty(trim($aAddress['addressTitle'])) ) {
							$sMapUrl .= preg_replace( '/\s+/', '+', $aAddress['addressTitle'] );
						}

						$sLocation = '
							<div class="tool itemLocation">
								<a href="' . $sMapUrl . '" class="itemMap" data-item-id="' . $aEntry['itemId'] . '" target="_blank"><i class="fas fa-map-marker-alt">&nbsp;</i><span class="long">' . $aAddress['addressTitle'] . '</span></a>
							</div>';

					} else {
						$sLocation = '
							<div class="tool itemLocation">
								<i class="fas fa-map-marker-alt">&nbsp;</i><span class="long">' . $aAddress['addressTitle'] . '</span>
							</div>';
					}

				}
			}

			if( !$this->bArchived ) {
				$sItemUrl = $aEntry['routePath'];
			} else {
				$sItemUrl = $oRouter->getPath( 'guestAuctionItemShowArchived' ) . '?itemId=' . $aEntry['itemId'];
			}

			$sTitle = '
				<h3>
					<small><span class="long">' . _( 'Call' ) . ' </span>' . $aEntry['itemSortNo'] . '</small>
					<span class="itemTitle"><a href="' . $sItemUrl . '" data-size="full">' . $aEntry['itemTitle'] . $sRegNo . '</a></span>
				</h3>';
			$sPlainTitle = '<h3><small>' . _( 'Call' ) . ' ' . $aEntry['itemSortNo'] . ':</small> ' . $aEntry['itemTitle'] . $sRegNo . '<h3>';

			if( $bCancelled == true ) {
				$sTitle = '
					<h3>
						<span class="bidNo">' . _( 'Call' ) . ' ' . $aEntry['itemSortNo'] . '</span>
						<span class="itemTitle">' . $aEntry['itemTitle'] . $sRegNo . '</span>
					</h3>';
			}

			if( in_array($this->sListKey, array('itemListArchived','wonItems')) ) {
				$aEntry['routePath'] = '/klassiskt/rop?itemId=' . $aEntry['itemId'] . '';
			}

			// Bid button
			$sBidButton = '';
			if( !$bCancelled ) {
				$sBidButtonLink = '';

				switch( $this->sListKey ) {
					case 'itemListArchived':
						// Archive list - no bid button
						break;

					default:
						if( !$bEnded ) {
							$sBidButtonLink = '
								<a href="' . $aEntry['routePath'] . '" class="button submit info small narrow" data-item-id="' . $aEntry['itemId'] . '">' . _( 'Lägg bud' ) . '</a>';
						}
				}

				$sBidButton = '
					<div class="links">
						<div class="moreLink">
							' . $sBidButtonLink . '
						</div>
					</div>';
			}

			// Tags
			$sTags = '';
			if( !empty($this->aTags) ) {
				foreach( $this->aTags['data'] as $sTagKey => $aTagItems ) {
					if( in_array($aEntry['itemId'], $aTagItems) ) {
						$sTags .= '<span class="tag ' . $sTagKey . '">' . $this->aTags['values'][ $sTagKey ] . '</span>';
					}
				}
			}

			if( !empty($sTags) ) $sBidButton = '<div class="userBidStatus">' . $sTags . '</div>';

			$sEndTime = convertTime( $aEntry['itemEndTime'], $aEntry['itemId'] );

				$sItemEntry = '
					<div class="listType listCompact" data-item-id="' . $aEntry['itemId'] . '">
						<div class="favorite">
							' . ( $bCancelled == false ? $this->generateFavoriteLink( $aEntry['itemId'], $sSignupUrl ) : '' ) . '
						</div>
						<div class="title">' . $sTitle . '</div>
						<div class="location">' . $sLocation . '</div>
						' . $sEndTime . '
						<div class="currentBid">' . $sCurrentBid . '</div>
						<div class="placeBid">' . $sBidButton . '</div>
					</div>
					<div class="listType listNormal" data-item-id="' . $aEntry['itemId'] . '">
						<div class="imageContainer">
							<p class="image">
							' . $sImage . '
							' . $sAllImagesButton . '
							</p>
						</div>
						<div class="information">
							<div class="title">' . ( $bCancelled == false ? $this->generateFavoriteLink( $aEntry['itemId'], $sSignupUrl ) : '' ) . $sTitle . '</div>
							<ul class="data">
								<li><strong>' . _( 'Moms' ) . '</strong> <span>' . $aEntry['itemVatValue'] . '%</span></li>
								' . ($bCancelled == false ? '
								<li><strong>' . _( 'Avgift' ) . '</strong> <span>' . calculatePrice( $aEntry['itemFeeValue'], array('profile' => 'human') ) . '</span> exkl. moms</li>
								' : '') . '
								<li><strong>' . _( 'Plats' ) . '</strong> ' . $sLocation . '</li>
								<li><strong>' . _( 'Bud' ) . '</strong> ' . $sCurrentBid . '</li>
							</ul>
							<div class="links">
								<div class="moreLink">
									<a href="' . $sItemUrl . '" class="button small" data-item-id="' . $aEntry['itemId'] . '" target="_blank">' . _( 'Öppna ropet på egen flik' ) . '&nbsp;<i class="fas fa-external-link-square-alt"></i></a>
								</div>
							</div>
						</div>
						<div class="itemEssentials">
							<div class="container">
								<div class="placeBid">' . $sBidButton . '</div>
								<div class="currentBid">' . $sCurrentBid . '</div>
								' . $sEndTime . '
							</div>
						</div>
				</div>
				<div class="infoPopup" id="infoPopup' . $aEntry['itemId'] . '">
					<div class="container"></div>
				</div>';

			if( $this->sListKey != 'ajax' ) {
				$sItemList .= '
					<li' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . ' id="itemEntry' . $aEntry['itemId'] . '" data-item-id="' . $aEntry['itemId'] . '" data-sort-no="' . $aEntry['itemSortNo'] . '">
						' . $sItemEntry . '
					</li>';
			} else {
				$sItemList .= $sItemEntry;
			}

			++$iCount;
		}

		$this->iEntriesShown = $iCount;

		return '
			<ul class="items mixed">
				' . $sItemList . '
			</ul>';
	}

	public function createPaginationBar( $bBottomBar = true ) {
		$oRouter = clRegistry::get( 'clRouter' );
		$sReturnHtml = '';
		$sPages = '';

		$iPages = ceil( $this->iEntriesTotal / $this->iEntries );
		$bThisLastPage = false;

		if( $iPages > 0 ) {
			$sPageHeading = ( ($this->sPaginationType == 'auction') ? _('Visa rop') : _('Visa sida') );

			for( $iPage=1; $iPage<=$iPages; $iPage++ ) {
				$aClass = array();
				$iStart = ($this->iEntries * ($iPage - 1)) + $this->iPaginationStartNo;
				$iEnd = $iStart + $this->iEntries - 1;
				if( $iEnd > ($this->iEntriesTotal + $this->iPaginationStartNo) ) $iEnd = $this->iEntriesTotal + $this->iPaginationStartNo - 1;

				if( $iPage == $this->iEntriesSequence ) {
					$aClass[] = 'current';
					if( $iPage == $iPages ) $bThisLastPage = true;
				}

				if( $iPage < $this->iPaginationFirstActivePage ) $aClass[] = 'ended';

				$iPageText = ( ($this->sPaginationType == 'auction') ? $iStart . '-' . $iEnd : $iPage );

				$sPages .= '
					<a href="' . $oRouter->sPath . '?page=' . $iPage . '" class="' . implode( ' ', $aClass ) . '" title="' . $iPageText . '">' . $iPageText . '</a>';
				}

				// Previous and next auction nav
				// Shown on last page
				$sAuctionNav = '';
				if( $bBottomBar && $bThisLastPage && !empty($GLOBALS['auctionNav']) ) {
					$sPrevious = $sNext = '';
					if( !empty($GLOBALS['auctionNav']['previous']) ) {
						$sPrevious = '
							<strong class="responsive desktop">' . _( 'Föregående auktion' ) . '</strong>
							<a href="' . $GLOBALS['auctionNav']['previous']['url'] . '">
								<i class="fas fa-chevron-left"></i>
								<div class="text">
									<span class="responsive desktop">' . $GLOBALS['auctionNav']['previous']['text'] . '</span>
									<span class="responsive tablet mobile">' . _( 'Föregående auktion' ) . '</span>
								</div>
							</a>';
					}
					if( !empty($GLOBALS['auctionNav']['next']) ) {
						$sNext = '
							<strong class="responsive desktop">' . _( 'Nästa auktion' ) . '</strong>
							<a href="' . $GLOBALS['auctionNav']['next']['url'] . '">
								<div class="text">
									<span class="responsive desktop">' . $GLOBALS['auctionNav']['next']['text'] . '</span>
									<span class="responsive tablet mobile">' . _( 'Nästa auktion' ) . '</span>
								</div>
								<i class="fas fa-chevron-right"></i>
							</a>';
					}
					$sAuctionNav .= '
						<div class="auctionNav">
							<div class="previous">' . $sPrevious . '</div>
							<div class="next">' . $sNext . '</div>
						</div>';
				}

				$sReturnHtml .= '
					<div class="paginationBar">
						<h4>' . $sPageHeading . '</h4>
						<div class="pages ' . $this->sPaginationType . '">' . $sPages . '</div>
						' . ( $bBottomBar ? '<div class="toTop">
							<a href="' . $oRouter->sPath . '#listTop">' . _( 'Till toppen' ) . ' <i class="fas fa-arrow-circle-up"></i></a>
						</div>' : '' ) . '
						' . $sAuctionNav . '
					</div>';
			}

		return $sReturnHtml;
	}

	public function renderToolbar() {
		$sViewType = ( !empty($_SESSION['browser']['auctionListView']) ? $_SESSION['browser']['auctionListView'] : 'normal' );

		if( !empty($this->iPaginationFirstActivePage) || ($this->sListKey == 'userItemsList') ) {
			$sFilterTool = '
				<span class="filterOption">
					<input type="checkbox" id="listEndedItems" ' . ( !empty($this->bShowEnded) ? 'checked' : '' ) . '>
					<label for="listEndedItems"><span class="responsive desktop tablet">' . _( 'Visa' ) . ' </span>' . _( 'avslutade' ) . '</label>
				</span>';

		} else {
			// $sFilterTool = $this->createSortTypeList();
			$sFilterTool = '
				<span class="filterOption">
					<input type="checkbox" id="listEndedItems" checked disabled>
					<label for="listEndedItems">' . _( 'Visa avslutade' ) . '</label>
				</span>';
		}

		$sAdditionalFilter = '';
		if( !empty($this->aAdditionalFilterTools) ) {
			foreach( $this->aAdditionalFilterTools as $sGroupKey => $aGroupData ) {
				$sAdditionalFilter .= '
					<div class="filterGroup ' . $sGroupKey . '" style="grid-template-columns: repeat(' . ( count($aGroupData['options']))  . ', fit-content(100%)) auto;">
						<span class="title">' . $aGroupData['title'] . '</span>';

				foreach( $aGroupData['options'] as $sOption ) {
					$sAdditionalFilter .= '
						<span class="filterOption">
							' . $sOption . '
						</span>';
				}

				$sAdditionalFilter .= '
					</div>';
			}
		}

		return '
			<div class="toolbar">
				<div class="tool filter">
					' . $sFilterTool . '
				</div>
				<div class="tool">
				' . $this->createSearchForm() . '
				</div>
				<div class="tool">
					<div class="listTypeSelect normal ' . ( ($sViewType == 'normal') ? 'selected' : '' ) . '" data-list-type="normal"><i class="fas fa-pause"></i></div>
				</div>
				<div class="tool">
					<div class="listTypeSelect compact ' . ( ($sViewType == 'compact') ? 'selected' : '' ) . '" data-list-type="compact"><i class="fas fa-align-justify"></i></div>
				</div>
				<div class="tool additionalFilter">
					' . $sAdditionalFilter . '
				</div>
			</div>';
	}

	public function renderListContent() {
		$this->updateCookie();

		$sContent = $this->createList();

		return '
			' . $sContent . '
			<span class="clear"></span>
			' . $this->createPaginationBar();
	}

	public function renderList() {
		$this->updateCookie();

		$sContent = $this->createList();

		$sViewType = 'normal'; //( !empty($_SESSION['browser']['auctionListView']) ? $_SESSION['browser']['auctionListView'] : 'normal' );

		return '
			<div class="listWrapper ' . $sViewType . ' ' . ( !empty($this->bShowEnded) ? 'showEnded' : '' ) . '" id="' . $this->sListKey . '">
			' . $this->createPaginationBar( false ) . '
			' . $sContent . '
				' . $this->createPaginationBar( true ) . '
			</div>';
	}

	public function render() {
		$this->updateCookie();
		$sList = ( !empty($this->aItemData) ? $this->renderList() : '<div class="emptyResult">' . _('Inga rop att visa...') . '</span>' );
		$sToolbar = $this->renderToolbar();

		return
			$sToolbar .
			$sList;
	}

	public function updateCookie() {
		// Trying to inactivate this and see what happens
		return false;

		$aNewCookieEntry = array();

		$aSkipLists = array(
			'wonItems',
			'favItems',
			'userBidItems'
		);

		if( in_array($this->sListKey, $aSkipLists) ) {
			return true;
		}

		/*** Ajax fix for Internt Explorer ***/
		$oRouter = clRegistry::get( 'clRouter' );
		$sPath = $oRouter->sPath;
		$sPath = str_replace( '%C3%A5', 'å', $sPath );
		$sPath = str_replace( '%C3%A4', 'ä', $sPath );
		$sPath = str_replace( '%C3%B6', 'ö', $sPath );

		$aNewCookieEntry = array(
			'listKey' => $this->sListKey,
			'viewFile' => $this->sViewFile,
			'title' => $this->sTitle,
			'sortType' => $this->sActiveSortType,
			'entries' => $this->iEntries,
			'entriesTotal' => count($this->aItemData),
			'entriesSequence' => !empty($this->iEntriesSequence) ? $this->iEntriesSequence : $this->iEntriesSequence,
			'routePath' => $sPath
		);

		// List all
		if( $this->bListAll == true ) {
			$aNewCookieEntry['entriesSequence'] = ceil( $aNewCookieEntry['entriesTotal'] / $this->iEntries );
		}

		if( !empty($_COOKIE['AuctionItemLists']) ) {
			$aCookieData = json_decode( $_COOKIE['AuctionItemLists'], true );
			foreach( $aSkipLists as $sList ) {
				unset( $aCookieData[$sList] );
			}
			$aCookieData[$this->sListKey] = $aNewCookieEntry;
		} else {
			$aCookieData[$this->sListKey] = $aNewCookieEntry;
		}

		//setcookie( 'AuctionItemLists', json_encode($aCookieData), time()+3600*26, '/' );
		setcookie( 'AuctionItemLists', json_encode($aCookieData), time()+3600, '/' );
	}

}
