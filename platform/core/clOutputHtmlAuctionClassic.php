<?php

require_once PATH_FUNCTION . '/fOutputHtml.php';
require_once PATH_FUNCTION . '/fMoney.php';

class clOutputHtmlAuctionClassic {

	private $aDataDict = array();
	private $aAuctionData = array();
	private $aItemData = array();

	private $sListKey;
	private $sViewFile;

	private $sTitle;

	private $iEntries;
	private $iEntriesSequence;
	private $iEntriesTotal;
	private $bListAll;

	public $sActiveViewMode;
	public $sActiveSortType;

	private $oImage;

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

		$this->setParams( $aParams );
	}

	public function setParams( $aParams ) {
		// Viewmode
		$this->aViewModes = array(
			'detailed',
			'mixed',
			'square'
		);

		// Viewmode selection
		if( !empty($_GET['viewmode']) ) {
			$this->sActiveViewMode = $_GET['viewmode'];
		} elseif( !empty($_SESSION['listViewMode']) ) {
			$this->sActiveViewMode = $_SESSION['listViewMode'];
		} else {
			$this->sActiveViewMode = !empty($aParams['viewmode']) ? $aParams['viewmode'] : null;
		}


		// Sorting
		$this->aSortTypes = array(
			'itemNo' => _( 'By item no' ),
			'endTime' => _( 'By end time' ),
			'alphabetically' => _( 'Alphabetically' ),
			'ended' => _( 'Ended items' )
		);
		if( !empty($_GET['sortBy']) ) {
			$this->sActiveSortType = $_GET['sortBy'];
		} else {
			$this->sActiveSortType = !empty($aParams['sortType']) ? $aParams['sortType'] : null;
		}

		$this->sListKey = !empty($aParams['listKey']) ? $aParams['listKey'] : null;
		$this->sViewFile = !empty($aParams['viewFile']) ? $aParams['viewFile'] : null;
		$this->sTitle = !empty($aParams['title']) ? $aParams['title'] : null;
		$this->bNextAuctionButton = isset($aParams['nextAuctionButton']) ? $aParams['nextAuctionButton'] : true;
		$this->aSearchForm = !empty($aParams['searchForm']) ? $aParams['searchForm'] : null;

		// Entries
		$this->iEntries = AUCTION_ITEM_PAGINATION; # !empty($aParams['entries']) ? $aParams['entries'] : null;
		$this->iEntriesSequence = !empty($aParams['entriesSequence']) ? $aParams['entriesSequence'] : 1;
		if( $this->iEntriesSequence !== null ) {
			$this->iEntriesTotal = $this->iEntries * $this->iEntriesSequence;
		} else {
			$this->iEntriesTotal = $this->iEntries;
		}

		// List all
		$this->bListAll = false;
		if( DEFCON_LEVEL >= 5 && isset($aParams['listAll']) && $aParams['listAll'] ) {
			$this->iEntriesTotal = null;
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
		if( !empty($_COOKIE['AuctionClassicItemLists']) ) {
			$aCookieData = json_decode( $_COOKIE['AuctionClassicItemLists'], true );
			$oRouter = clRegistry::get( 'clRouter' );
			if( array_key_exists($this->sListKey, $aCookieData) && $oRouter->sRefererRoute != $oRouter->sPath ) {
				$this->sActiveViewMode = $aCookieData[ $this->sListKey ]['viewmode'];
				$this->iEntries = !empty($aCookieData[ $this->sListKey ]['entries']) ? $aCookieData[ $this->sListKey ]['entries'] : $this->iEntries;
				$this->iEntriesSequence = $aCookieData[ $this->sListKey ]['entriesSequence'];
				$this->iEntriesTotal = $this->iEntries * $this->iEntriesSequence;
			}
		}
	}

	public function addAuctionData( $aAuctionData ) {
		$this->aAuctionData = $aAuctionData;
	}

	public function addItemData( $aItemData ) {
		$this->aItemData = $aItemData;
	}

	public function createViewModeList( $aViewModes = array() ) {
		if( empty($aViewModes) ) $aViewModes = $this->aViewModes;
		$oRouter = clRegistry::get( 'clRouter' );

		if( !empty($this->aItemData) ) {
			$aFirstItem = current( $this->aItemData );
			$iAuctionId = $aFirstItem['itemAuctionId'];
			$iPartId = $aFirstItem['itemPartId'];
		}

		$sViewmodeList = '';
		foreach( $aViewModes as $sMode ) {
			$aClass = array();

			$aClass[] = 'transitionAll';

			if( $sMode == $this->sActiveViewMode ) {
				$aClass[] = 'active';
			}

			$aClass[] = 'selectable';

			$sViewmodeList .= '
				<li' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . '>
					<a class="ajax transitionAll"
						href="' . $oRouter->sPath . '?changeViewmode=' . $sMode . '"
						data-ajax-href="' . $oRouter->sPath . '?ajax=true&view=' . $this->sViewFile . '&listKey=' . $this->sListKey . '&entriesSequence=' . $this->iEntriesSequence . '&viewmode=' . $sMode . '&sortBy=' . $this->sActiveSortType . '&routePath=' . $oRouter->sPath . (!empty($iAuctionId) ? '&auctionId=' . $iAuctionId : '') . (!empty($iPartId) ? '&partId=' . $iPartId : '') . '"
						data-ajax-targetClass=".listWrapper"
						data-ajax-targetid="#' . $this->sListKey . '"
					>&nbsp;</a>
				</li>';
		}
		return '
			<ul class="viewmodes">
				' . $sViewmodeList . '
			</ul>';
	}

	public function createSortTypeList( $aSortTypes = array() ) {
		if( in_array($this->sListKey, array('userBidItems','favItems','wonItems')) ) return '';

		if( empty($aSortTypes) ) $aSortTypes = $this->aSortTypes;
		$oRouter = clRegistry::get( 'clRouter' );

		if( !empty($this->aItemData) ) {
			$aFirstItem = current( $this->aItemData );
			$iAuctionId = $aFirstItem['itemAuctionId'];
			$iPartId = $aFirstItem['itemPartId'];
		}

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
					<a class="ajax"
						href="' . $oRouter->sPath . '?sortBy=' . $sType . '"
						data-ajax-href="' . $oRouter->sPath . '?ajax=true&view=' . $this->sViewFile . '&listKey=' . $this->sListKey . '&entriesSequence=' . $this->iEntriesSequence . '&viewmode=' . $this->sActiveViewMode . '&sortBy=' . $sType . '&routePath=' . $oRouter->sPath . (!empty($iAuctionId) ? '&auctionId=' . $iAuctionId : '') . (!empty($iPartId) ? '&partId=' . $iPartId : '') . '"
						data-ajax-targetClass=".listWrapper"
						data-ajax-targetid="#' . $this->sListKey . '"
					>' . $sTypeLabel . '</a>
				</li>';
		}
		return '
			<div class="sorting">
				<div class="selector">' . $sActiveSort . '</div>
				<div class="optionsContainer transitionAll">
					<ul class="options">
						' . $sSortTypeList . '
					</ul>
				</div>
			</div>';
	}

	public function generateFavoriteLink( $iItemId, $sRoute = null ) {
		$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

		if( !empty($_SESSION['userId']) ) {
			$sStatus = 'true';
			if( !empty($oAuctionEngine->aUserFavoriteItems) && in_array($iItemId, $oAuctionEngine->aUserFavoriteItems) ) {
				$sStatus = 'false';
			}
			$sRoute = "?ajax=true&status=" . $sStatus . "&event=favoriteItem&favoriteItem=" . $iItemId;
			$sClass = "ajax favLink";
		} else {
			$oRouter = clRegistry::get( 'clRouter' );
			$sClass = "";
			if( empty($sRoute) ) $sRoute = $oRouter->getPath( 'guestUserSignup' );
		}

		if( !empty($oAuctionEngine->aUserFavoriteItems) && !empty($oAuctionEngine->aUserFavoriteItems[ $iItemId ]) ) {
			return '
				<a class="' . $sClass . '" data-status="true" href="' . $sRoute . '">
					<img src="/images/templates/tovekClassic/icon-fav-list.png" alt="" />
				</a>';
		} else {
			return '
				<a class="' . $sClass . '" data-status="false" href="' . $sRoute . '">
					<img src="/images/templates/tovekClassic/icon-fav-grey-list.png" alt="" />
				</a>';
		}
	}

	public function createTableRows() {
		$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

		$oRouter = clRegistry::get( 'clRouter' );
		$sSignupUrl = $oRouter->getPath( 'guestUserSignup' );

		$sItems = '';
		$sItemList = '';
		$iTime = time();

		$iCount = 1;
		foreach( $this->aItemData as $entry ) {
			// Continue if past in time
			//if( time() > strtotime($entry['itemEndTime']) ) continue;

			/**
			 * Cancelled item
			 */
			$bCancelled = false;
			if( $entry['itemMinBid'] == 0 && $entry['itemMarketValue'] == 0 ) {
				$bCancelled = true;
			}

			if( $this->iEntriesTotal !== null && $iCount >= $this->iEntriesTotal ) break;

			$aClass = array();

			if( $iCount % 2 == 0 ) $aClass[] = 'odd';
			if( $iCount % 4 == 0 ) $aClass[] = 'fourth';
			if( !empty($entry['partStatus']) && $entry['partStatus'] == 'halted' ) $aClass[] = 'halted';
			if( $entry['itemStatus'] == 'ended' ) $aClass[] = 'ended';

			// Development
			if( empty($entry['bidValue']) ) $entry['bidValue'] = '0';

			// Auction type
			$sAuctionType = '';

			if( !empty($this->aAuctionData[ $entry['itemAuctionId'] ]) ) {
				$sAuctionTitle = $this->aAuctionData[ $entry['itemAuctionId'] ]['auctionTitle'];

				if( $this->aAuctionData[$entry['itemAuctionId']]['auctionType'] == 'live' ) {
					$sAuctionType = '<img src="/images/templates/tovekClassic/icon-live-yellow-list.png" alt="" />';
				}

			} elseif( !empty($entry['auctionTitle']) ) {
				$sAuctionTitle = $entry['auctionTitle'];

				if( $entry['auctionType'] == 'live' ) {
					$sAuctionType = '<img src="/images/templates/tovekClassic/icon-live-yellow-list.png" alt="" />';
				}

			} else {
				$sAuctionTitle = '';
			}

			/**
			 * Handling of shown bid price
			 */
			$fBidValue = !empty($entry['bidValue']) ? $entry['bidValue'] : $entry['itemMinBid'];
			if( empty($fBidValue) || $fBidValue == '0' ) {
				$fBidPrice = _( 'Expires' );
			} else {
				$fBidPrice = calculatePrice( $fBidValue, array(
					'format' => array(
						'money' => true
					),
				) ) . ' (' . $entry['itemBidCount'] . ')';
			}

			$sBid = '&nbsp;';
			if( !empty($entry['auctionType']) && $entry['auctionType'] != 'live' ) {
				$sBid = '
					<img src="/images/templates/tovekClassic/icon-bid-list.png" alt="" />
					<span id="itemBid' . $entry['itemId'] . '" class="itemCurrentBid itemCurrentBid' . $entry['itemId'] . '" data-item-id="' . $entry['itemId'] . '">
						' . $fBidPrice . '
					</span>';
			}

			/**
			 * Bid value
			 */
			//$fBidValue = !empty($aEntry['bidValue']) ? $aEntry['bidValue'] : $aEntry['itemMinBid'];
			//$sCurrentBid = '';
			//$sCurrentBidTime = !empty($aEntry['bidCreated']) ? $aEntry['bidCreated'] : ''; // 'Lagt 30 jan 2019';
			//if( !empty($aEntry['auctionType']) && $aEntry['auctionType'] != 'live' ) {
			//	$sCurrentBid = '<span class="itemCurrentBid itemCurrentBid' . $aEntry['itemId'] . '" data-item-id="' . $aEntry['itemId'] . '">' . (empty($fBidValue) || $fBidValue == '0' ? _( 'Expires' ) : '') . '</span>';
			//	if( !empty($fBidValue) || $fBidValue != '0' ) {
			//		$sCurrentBidTime = '<span class="itemCurrentBidTime' . $aEntry['itemId'] . '"></span>';
			//	}
			//}
			//$sNextBidValue = '';

			// Registration plate is not included in title after 30 days
			$iTimeSinceEnded = $iTime - strtotime( $entry['itemEndTime'] );
			$sRegNo = '';
			if( !empty($entry['itemVehicleDataId']) && ($iTimeSinceEnded < 2592000) ) {
				$oVehicleData = clRegistry::get( 'clVehicleData', PATH_MODULE . '/vehicle/models' );
				$aVehicleData = current( $oVehicleData->read('vehicleLicencePlate', $entry['itemVehicleDataId']) );
				if( !empty($aVehicleData) ) {
					$sRegNo = ' [' . $aVehicleData['vehicleLicencePlate'] . ']';
				}
			}

			$sItems = '
				<td class="auctionType">' . $sAuctionType . '</td>
				<td class="auctionTitle">' . shortenString( $sAuctionTitle, 35 ) . '</td>
				<td class="itemLocation">' . $entry['itemLocation'] . '</td>
				<td class="itemSortNo">
					<img src="/images/templates/tovekClassic/icon-item-list.png" alt="" />
					' . (!empty($entry['itemSortNo']) ? $entry['itemSortNo'] : '') . '
				</td>
				<td class="itemDescription">' . shortenString( $entry['itemTitle'], 60 ) . $sRegNo . '</td>
				<td class="itemEndTime">' . convertTime( $entry['itemEndTime'], $entry['itemId'] ) . '</td>
				<td class="itemBid">' . $sBid . '</td>
				<td class="itemFavorite">
					' . ($bCancelled == false ? $this->generateFavoriteLink( $entry['itemId'], $sSignupUrl ) : '') . '
				</td>';

			if( $this->sListKey != 'ajax' ) {
				$sItemList .= '
					<tr' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '') . ' data-item-id="' . $entry['itemId'] . '" data-ajax-href="' . (SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . '?view=classic/auction_itemShowAjax.php&itemId=' . $entry['itemId'] . '&noCss=true">
						' . $sItems . '
					</tr>';
			} else {
				$sItemList .= $sItems;
			}

			++$iCount;
		}

		if( $iCount == 1 ) {
			return false;
		} else {
			return $sItemList;
		}
	}

	public function createTable() {
		$sRows = $this->createTableRows();

		if( $sRows !== false ) {
			return '
			<table class="items detailed">
				<thead>
					<tr>
						<th class="auctionType"></th>
						<th class="auctionTitle">' . _( 'Auction' ) . '</th>
						<th class="itemLocation">' . _( 'City' ) . '</th>
						<th class="itemSortNo"><img src="/images/templates/tovekClassic/icon-item-list.png" alt="" /> ' . _( 'Item' ) . '</th>
						<th class="itemDescription"><img src="/images/templates/tovekClassic/icon-info-list.png" alt="" /> ' . _( 'Description' ) . '</th>
						<th class="itemEndTime"><img src="/images/templates/tovekClassic/icon-endtime-list.png" alt="" /> ' . _( 'End time' ) . '</th>
						<th class="itemBid"><img src="/images/templates/tovekClassic/icon-bid-list.png" alt="" /> ' . _( 'Bid' ) . '</th>
						<th class="itemFavorite"></th>
					</tr>
				</thead>
				<tbody>
					' . $this->createTableRows() . '
				</tbody>
			</table>';
		} else {
			return _( 'Something went wrong' );
		}
	}

	public function createMixedList() {
		$sItems = '';
		$sItemList = '';
		$iTime = time();

		try {
			$oRouter = clRegistry::get( 'clRouter' );
			$sSignupUrl = $oRouter->getPath( 'guestUserSignup' );

			$iCount = 0;
			foreach( $this->aItemData as $entry ) {
				// Continue if past in time
				//if( time() > strtotime($entry['itemEndTime']) ) continue;

				/**
				 * Cancelled item
				 */
				$bCancelled = false;
				if( $entry['itemMinBid'] == 0 && $entry['itemMarketValue'] == 0 ) {
					$bCancelled = true;
				}

				$iTimeSinceEnded = $iTime - strtotime( $entry['itemEndTime'] );

				if( $this->iEntriesTotal !== null && $iCount >= $this->iEntriesTotal ) break;

				$aClass = array();

				if( $iCount % 2 == 0 ) $aClass[] = 'odd';
				if( $iCount % 4 == 0 ) $aClass[] = 'fourth';
				if( $entry['partStatus'] == 'halted' ) $aClass[] = 'halted';
				if( $entry['itemStatus'] == 'ended' ) $aClass[] = 'ended';
				if( $bCancelled == true ) $aClass[] = 'cancelled';

				// Development
				if( empty($entry['bidValue']) ) $entry['bidValue'] = '0';

				// Image data
				$this->oImage->oDao->switchToSecondary();
				$aImageData = array();
				if( ($iTimeSinceEnded < 2592000) || empty($entry['itemVehicleArchiveImageId']) ) {
					$aImageData = $this->oImage->readByParent( $entry['itemId'], array(
						'imageId',
						'imageFileExtension',
						'imageParentId'
					) );
				} else if( !empty($entry['itemVehicleArchiveImageId']) ) {
					$aImageData = $this->oImage->read( array(
						'imageId',
						'imageFileExtension',
						'imageParentId'
					), $entry['itemVehicleArchiveImageId'] );
				}
				$this->oImage->oDao->switchToPrimary();

				$sImage = '
					<img src="/images/templates/tovekClassic/img-auction-item-no-image.png" alt="" />';
				if( !empty($aImageData) && ($entry['itemMinBid'] > 0) ) {
					// Note: if the item min bid is 0 the item is cancelled and shall not show any images.

					$aImage = current($aImageData);

					$sImage = '
						' . ($entry['auctionType'] == 'live' ? '
							<img src="/images/templates/tovekClassic/icon-live-yellow-imageicon.png" alt="" class="liveicon" />
						' : '') . '
						<img src="/images/custom/AuctionItem/tn/small' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'] . '" alt="" />';

				} elseif( empty($aImageData) && !empty($entry['auctionOldAuctionId']) && !empty($entry['itemOldItemId']) && ($entry['itemMinBid'] > 0) ) {
					// Note: if the item min bid is 0 the item is cancelled and shall not show any images.

					$sOldDir = PATH_PUBLIC . '/images/oldAuctions/' . $entry['auctionOldAuctionId'] . '/' . $entry['itemOldItemId'];
					if( is_dir($sOldDir) ) {
						$aOldImages = scandir( $sOldDir );

						if( $aOldImages !== false ) {
							unset( $aOldImages[0], $aOldImages[1] );

							$aOldImageGrouped = array();
							foreach( $aOldImages as $sFile ) {
								if( substr($sFile, 0, 6) == 'medium' ) {
									$sOldImageDir = '/images/oldAuctions/' . $entry['auctionOldAuctionId'] . '/' . $entry['itemOldItemId'];

									$sImage = '
										' . ($entry['auctionType'] == 'live' ? '
											<img src="/images/templates/tovekClassic/icon-live-yellow-imageicon.png" alt="" class="liveicon" />
										' : '') . '
										<img style="max-height: 210px;" src="' . $sOldImageDir . '/' . $sFile . '" alt="" />';

									break;
								}
							}
						}
					}
				}

				$fBidValue = !empty($entry['bidValue']) ? $entry['bidValue'] : $entry['itemMinBid'];
				if( empty($fBidValue) || $fBidValue == '0' ) {
					$fBidPrice = _( 'Expires' );
				} else {
					$fBidPrice = calculatePrice( $fBidValue, array(
						'format' => array(
							'money' => true
						),
					) ) . ' (' . $entry['itemBidCount'] . ')';
				}

				$sBid = '';
				if( !empty($entry['auctionType']) && $entry['auctionType'] != 'live' ) {
					$sBid = '
						<p class="bid"><strong>' . _( 'Bid' ) . ':</strong> <img src="/images/templates/tovekClassic/icon-bid-list.png" alt="" />
							<span id="itemBid' . $entry['itemId'] . '" class="itemCurrentBid itemCurrentBid' . $entry['itemId'] . '" data-item-id="' . $entry['itemId'] . '">
								' . $fBidPrice . '
							</span>
						</p>';
				}

				/**
				 * Handling of shown bid price
				 */
				if( !empty($this->aAuctionData[ $entry['itemAuctionId'] ]) ) {
					$sAuctionTitle = $this->aAuctionData[ $entry['itemAuctionId'] ]['auctionTitle'];
				} elseif( !empty($entry['auctionTitle']) ) {
					$sAuctionTitle = $entry['auctionTitle'];
				} else {
					$sAuctionTitle = '';
				}

				// Registration plate is not included in title after 30 days
				$sRegNo = '';
				if( !empty($entry['itemVehicleDataId']) && ($iTimeSinceEnded < 2592000) ) {
					$oVehicleData = clRegistry::get( 'clVehicleData', PATH_MODULE . '/vehicle/models' );
					$aVehicleData = current( $oVehicleData->read('vehicleLicencePlate', $entry['itemVehicleDataId']) );
					if( !empty($aVehicleData) ) {
						$sRegNo = ' [' . $aVehicleData['vehicleLicencePlate'] . ']';
					}
				}

				$sItems = '
					<div class="imageContainer">
						<p class="image">
							<a class="ajax" data-item-id="' . $entry['itemId'] . '" href="' . (SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . '?view=classic/auction_itemShowAjax.php&itemId=' . $entry['itemId'] . '&noCss=true">
								' . $sImage . '
							</a>
						</p>
					</div>
					<div class="info">
						<a class="ajax" data-item-id="' . $entry['itemId'] . '" href="' . (SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . '?view=classic/auction_itemShowAjax.php&itemId=' . $entry['itemId'] . '&noCss=true">
							<p class="bidNo">' . _( 'Call' ) . ' ' . $entry['itemSortNo'] . '</p>
							<h4>' . $entry['itemTitle'] . $sRegNo . '</h4>
							<p class="location">' . $entry['itemLocation'] . '</p>
							<div class="metadata">
								<p class="auction"><strong>' . _( 'Auction' ) . ':</strong> ' . shortenString( $sAuctionTitle, 35 ) . '</p>
								<p class="endTime"><strong>' . _( 'Ends' ) . ':</strong> ' . convertTime( $entry['itemEndTime'], $entry['itemId'] ) . '</p>
								' . $sBid . '
							</div>
						</a>
					</div>
					<div class="itemFavorite">
						' . ($bCancelled == false ? $this->generateFavoriteLink( $entry['itemId'], $sSignupUrl ) : '') . '
					</div>';

				if( $this->sListKey != 'ajax' ) {
					$sItemList .= '
						<li' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . ' data-item-id="' . $entry['itemId'] . '">
							' . $sItems . '
						</li>';
				} else {
					$sItemList .= $sItems;
				}

				++$iCount;
			}
		} catch( Throwable $oThrowable ) {
			echo '<pre>';
			var_dump( $oThrowable );
			die;

		} catch( Exception $oException ) {
			echo '<pre>';
			var_dump( $oException );
			die;

		}

		return $sItemList;
	}

	public function createSquareList() {
		$sItems = '';
		$sItemList = '';
		$iTime = time();

		$oRouter = clRegistry::get( 'clRouter' );
		$sSignupUrl = $oRouter->getPath( 'guestUserSignup' );

		$iCount = 0;
		foreach( $this->aItemData as $entry ) {
			// Continue if past in time
			//if( time() > strtotime($entry['itemEndTime']) ) continue;
			$iTimeSinceEnded = $iTime - strtotime( $entry['itemEndTime'] );

			/**
			 * Cancelled item
			 */
			$bCancelled = false;
			if( $entry['itemMinBid'] == 0 && $entry['itemMarketValue'] == 0 ) {
				$bCancelled = true;
			}

			if( $this->iEntriesTotal !== null && $iCount >= $this->iEntriesTotal ) break;

			$aClass = array();

			if( $iCount % 2 == 0 ) $aClass[] = 'odd';
			if( $iCount % 4 == 0 ) $aClass[] = 'fourth';
			if( $entry['partStatus'] == 'halted' ) $aClass[] = 'halted';
			if( $entry['itemStatus'] == 'ended' ) $aClass[] = 'ended';

			$sFavImage = '<img src="/images/templates/tovekClassic/icon-fav-grey-list.png" alt="" />';
			if( !empty($oAuctionEngine->aUserFavoriteItems[ $entry['itemId'] ]) ) {
				$sFavImage = '<img src="/images/templates/tovekClassic/icon-fav-list.png" alt="" />';
			}

			// Development
			if( empty($entry['bidValue']) ) $entry['bidValue'] = '0';

			// Image data
			$this->oImage->oDao->switchToSecondary();
			$aImageData = array();
		 	if( ($iTimeSinceEnded < 2592000) || empty($entry['itemVehicleArchiveImageId']) ) {
		 		$aImageData = $this->oImage->readByParent( $entry['itemId'], array(
		 			'imageId',
		 			'imageFileExtension',
		 			'imageParentId'
		 		) );
		 	} else if( !empty($entry['itemVehicleArchiveImageId']) ) {
		 		$aImageData = $this->oImage->read( array(
		 			'imageId',
		 			'imageFileExtension',
		 			'imageParentId'
		 		), $entry['itemVehicleArchiveImageId'] );
		 	}
			$this->oImage->oDao->switchToPrimary();
			$sImage = '
				<img src="/images/templates/tovekClassic/img-auction-item-no-image.png" alt="" />';
			if( !empty($aImageData) ) {
				$aImage = current($aImageData);

				$sImage = '
					' . ($entry['auctionType'] == 'live' ? '
						<img src="/images/templates/tovekClassic/icon-live-yellow-imageicon.png" alt="" class="liveicon" />
					' : '') . '
					<img src="/images/custom/AuctionItem/tn/small' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'] . '" alt="" />';

			} elseif( empty($aImageData) && !empty($entry['auctionOldAuctionId']) && !empty($entry['itemOldItemId']) ) {
				$sOldDir = PATH_PUBLIC . '/images/oldAuctions/' . $entry['auctionOldAuctionId'] . '/' . $entry['itemOldItemId'];
				if( is_dir($sOldDir) ) {
					$aOldImages = scandir( $sOldDir );

					if( $aOldImages !== false ) {
						unset( $aOldImages[0], $aOldImages[1] );

						$aOldImageGrouped = array();
						foreach( $aOldImages as $sFile ) {
							if( substr($sFile, 0, 6) == 'medium' ) {
								$sOldImageDir = '/images/oldAuctions/' . $entry['auctionOldAuctionId'] . '/' . $entry['itemOldItemId'];

								$sImage = '
									' . ($entry['auctionType'] == 'live' ? '
										<img src="/images/templates/tovekClassic/icon-live-yellow-imageicon.png" alt="" class="liveicon" />
									' : '') . '
									<img style="max-width: 281px;" src="' . $sOldImageDir . '/' . $sFile . '" alt="" />';


								break;
							}
						}
					}
				}
			}

			/**
			 * Handling of shown bid price
			 */
			$fBidValue = !empty($entry['bidValue']) ? $entry['bidValue'] : $entry['itemMinBid'];
			if( empty($fBidValue) || $fBidValue == '0' ) {
				$fBidPrice = _( 'Expires' );
			} else {
				$fBidPrice = calculatePrice( $fBidValue, array(
					'format' => array(
						'money' => true
					),
				) ) . ' (' . $entry['itemBidCount'] . ')';
			}

			$sBid = '';
			if( !empty($entry['auctionType']) && $entry['auctionType'] != 'live' ) {
				$sBid = '
					<strong>' . _( 'Bid' ) . ':</strong> <img src="/images/templates/tovekClassic/icon-bid-list.png" alt="" />
					<span id="itemBid' . $entry['itemId'] . '" class="itemCurrentBid itemCurrentBid' . $entry['itemId'] . '" data-item-id="' . $entry['itemId'] . '">
						' . $fBidPrice . '
					</span>';
			}

			if( mb_strlen($entry['itemTitle']) > 60 ) {
				$entry['itemTitle'] = mb_substr( $entry['itemTitle'], 0, 57 ) . '...';
			}

			// Registration plate is not included in title after 30 days
			$sRegNo = '';
			if( !empty($entry['itemVehicleDataId']) && ($iTimeSinceEnded < 2592000) ) {
				$oVehicleData = clRegistry::get( 'clVehicleData', PATH_MODULE . '/vehicle/models' );
				$aVehicleData = current( $oVehicleData->read('vehicleLicencePlate', $entry['itemVehicleDataId']) );
				if( !empty($aVehicleData) ) {
					$sRegNo = ' [' . $aVehicleData['vehicleLicencePlate'] . ']';
				}
			}

			$sItems = '
				<a class="ajax" data-item-id="' . $entry['itemId'] . '" href="' . (SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . '?view=classic/auction_itemShowAjax.php&itemId=' . $entry['itemId'] . '&noCss=true">
					<div class="imageContainer">
						<p class="image">
							' . $sImage . '
						</p>
					</div>
					<div class="information">
						<p class="bidNo">' . _( 'Call' ) . ' ' . $entry['itemSortNo'] . '</p>
						<h4>' . $entry['itemTitle'] . $sRegNo . '</h4>
						<p class="metadata">
							<strong>' . _( 'Ends' ) . ':</strong> ' . convertTime( $entry['itemEndTime'], $entry['itemId'] ) . '<br />
							' . $sBid . '
						</p>
						<div class="itemFavorite">
							' . ($bCancelled == false ? $this->generateFavoriteLink( $entry['itemId'], $sSignupUrl ) : '') . '
						</div>
					</div>
				</a>';

			if( $this->sListKey != 'ajax' ) {
				$sItemList .= '
					<li' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . ' data-item-id="' . $entry['itemId'] . '">
						' . $sItems . '
					</li>';
			} else {
				$sItemList .= $sItems;
			}

			++$iCount;
		}

		return $sItemList;
	}

	public function createList( $sType ) {
		if( $sType == 'mixed' ) {
			return '
				<ul class="items mixed">
					' . $this->createMixedList() . '
				</ul>';
		}
		if( $sType == 'square' ) {
			return '
				<ul class="items square">
					' . $this->createSquareList() . '
				</ul>';
		}
		return false;
	}

	public function createPaginationBar() {
		$oRouter = clRegistry::get( 'clRouter' );

		$iEntriesTotal = count( $this->aItemData );

		if( $this->iEntriesSequence === null ) {
			$iEntriesSequence = 2;
			$iEntriesShown = $this->iEntries;
		} else {
			$iEntriesSequence = $this->iEntriesSequence + 1;
			$iEntriesShown = $this->iEntries * $this->iEntriesSequence;
		}
		if( $iEntriesTotal <= $this->iEntries ) {
			$iEntriesShown = $iEntriesTotal;
		}

		$sMoreLink = '';
		$other = '.';
		$aCurrentItem = current( $this->aItemData );

		if( $iEntriesTotal > $this->iEntries ) {
			$sMoreLink = '
				<a class="ajax transitionAll"
					href="' . $oRouter->sPath . '?auctionId=' . $aCurrentItem['itemAuctionId'] . '&partId=' . $aCurrentItem['itemPartId'] . '&listKey=' . $this->sListKey . '&entriesSequence=' . $iEntriesSequence . '"
					data-ajax-href="' . $oRouter->sPath . '?ajax=true&view=' . $this->sViewFile . '&auctionId=' . $aCurrentItem['itemAuctionId'] . '&partId=' . $aCurrentItem['itemPartId'] . '&listKey=' . $this->sListKey . '&sortBy=' . $this->sActiveSortType . '&entriesSequence=' . $iEntriesSequence . '&viewmode=' . $this->sActiveViewMode . '&routePath=' . $oRouter->sPath . '"
					data-ajax-targetClass=".listWrapper"
					data-ajax-targetid="#' . $this->sListKey . '"
				>' . sprintf( _( 'Show next %s' ), $this->iEntries ) . '</a>';

			if( DEFCON_LEVEL >= 5 && AUCTION_ITEM_SHOW_ALL === true ) {
				// Show all button
				$sMoreLink .= '
				<a class="ajax transitionAll"
					href="' . $oRouter->sPath . '?auctionId=' . $aCurrentItem['itemAuctionId'] . '&partId=' . $aCurrentItem['itemPartId'] . '&listKey=' . $this->sListKey . '&entriesSequence=' . $iEntriesSequence . '&listAll=1"
					data-ajax-href="' . $oRouter->sPath . '?ajax=true&view=' . $this->sViewFile . '&auctionId=' . $aCurrentItem['itemAuctionId'] . '&partId=' . $aCurrentItem['itemPartId'] . '&listKey=' . $this->sListKey . '&sortBy=' . $this->sActiveSortType . '&entriesSequence=' . $iEntriesSequence . '&listAll=1&viewmode=' . $this->sActiveViewMode . '&routePath=' . $oRouter->sPath . '"
					data-ajax-targetClass=".listWrapper"
					data-ajax-targetid="#' . $this->sListKey . '"
				>' . _( 'Visa alla' ) . '</a>';
			}

			if( ($iEntriesShown >= $iEntriesTotal) || ($this->iEntriesTotal == null) ) {
				$iEntriesShown = $iEntriesTotal;
				$sMoreLink = '
					<a class="ajax transitionAll"
					href="' . $oRouter->sPath . '?auctionId=' . $aCurrentItem['itemAuctionId'] . '&partId=' . $aCurrentItem['itemPartId'] . '&listKey=' . $this->sListKey . '&entriesSequence=' . $iEntriesSequence . '"
					data-ajax-href="' . $oRouter->sPath . '?ajax=true&view=' . $this->sViewFile . '&auctionId=' . $aCurrentItem['itemAuctionId'] . '&partId=' . $aCurrentItem['itemPartId'] . '&listKey=' . $this->sListKey . '&sortBy=' . $this->sActiveSortType . '&entriesSequence=1&viewmode=' . $this->sActiveViewMode . '&routePath=' . $oRouter->sPath . '"
					data-ajax-targetClass=".listWrapper"
					data-ajax-targetid="#' . $this->sListKey . '"
				>' . _( 'Hide again' ) . '</a>';
			}
		}

		if( ($iEntriesShown >= $iEntriesTotal) && ($this->bNextAuctionButton === true) ) {
			$aCurrentItem = current( $this->aItemData );
			$sMoreLink .= '
				<a class="nextButton" id="nextAuctionBtn" href="#" data-auction-part-id="' . $aCurrentItem['partId'] . '">' . _( 'Next auction' ) . '<img src="/images/templates/tovekClassic/icon-blue-arrow-right.png"></a>';
		}

		return '
			<div class="paginationBar">
				<div class="counter">
					<span>' . sprintf( _( 'Show %s of %s items' ), $iEntriesShown, $iEntriesTotal ) . '</span>
				</div>
				<div class="moreButton">
					' . $sMoreLink . '
				</div>
				<div class="toTop">
					<a href="#listTop">' . _( 'To topp' ) . ' <img src="/images/templates/tovekClassic/icon-black-arrow.png" alt="" /></a>
				</div>
			</div>';
	}

	public function createSearchForm( $sMethod = 'GET' ) {
		if( empty($this->aSearchForm) ) return;

		$sFields = '';
		foreach( $this->aSearchForm as $sSearchField ) {
			$sFields .= '
				<div class="field text search">
					<input type="text" name="' . $sSearchField . '" class="text" placeholder="' . _( 'Rop-nr.' ) . '">
				</div>';
		}

		$sHidden = '';
		if( $sMethod == 'GET' )
		foreach( $_GET as $key => $value ) {
			if( in_array($key, $this->aSearchForm) ) {
				unset( $_GET[$key] );
			} else {
				$sHidden .= '
					<input type="hidden" name="' . $key . '" value="' . $value . '" />';
			}
		}

		$sAction = '';
		if( $sMethod != 'GET' ) {
			$sAction = '?' . http_build_query( $_GET );
		}

		return '
			<form ' . ( !empty($sAction) ? 'action="' . $sAction . '"' : '' ) . ' method="' . $sMethod . '" class="itemNoSearch">
				' . $sFields . '
				' . ( !empty($sHidden) ? '<div class="hidden">' . $sHidden . '</div>' : '' ) . '
				<p class="buttons">
					<button type="submit" class="rounded"><i class="fas fa-search"></i></button>
				</p>
			</form>';
	}

	public function renderToolbar() {
		return '
			<h2 id="lista"><a id="listTop"></a>' . $this->sTitle . '</h2>
			<div class="listToolbar">
				' . $this->createSearchForm() . '
				' . $this->createSortTypeList() . '
				' . $this->createViewModeList() . '
			</div>';
	}

	public function renderListContent() {
		$this->updateCookie();

		$sContent = '';
		if( $this->sActiveViewMode == 'detailed' ) $sContent = $this->createTable();
		if( $this->sActiveViewMode == 'mixed' ) $sContent = $this->createList( 'mixed' );
		if( $this->sActiveViewMode == 'square' ) $sContent = $this->createList( 'square' );

		return '
			' . $sContent . '
			<span class="clear"></span>
			' . $this->createPaginationBar();
	}

	public function renderList() {
		$this->updateCookie();

		$sContent = '';
		if( !empty($this->aItemData) ) {
			if( $this->sActiveViewMode == 'detailed' ) $sContent = $this->createTable();
			if( $this->sActiveViewMode == 'mixed' ) $sContent = $this->createList( 'mixed' );
			if( $this->sActiveViewMode == 'square' ) $sContent = $this->createList( 'square' );
		} else {
			$sContent = '<em>' . _( 'No auction items to show' ) . '</em>';
		}

		return '
			<div class="listWrapper" id="' . $this->sListKey . '">
				' . $sContent . '
				<span class="clear"></span>
				' . $this->createPaginationBar() . '
			</div>';
	}

	public function render() {
		$this->updateCookie();

		return
			$this->renderToolbar() .
			$this->renderList();
	}

	public function updateCookie() {
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
			'viewmode' => $this->sActiveViewMode,
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

		if( !empty($_COOKIE['AuctionClassicItemLists']) ) {
			$aCookieData = json_decode( $_COOKIE['AuctionClassicItemLists'], true );
			foreach( $aSkipLists as $sList ) {
				unset( $aCookieData[$sList] );
			}
			$aCookieData[$this->sListKey] = $aNewCookieEntry;
		} else {
			$aCookieData[$this->sListKey] = $aNewCookieEntry;
		}

		//setcookie( 'AuctionClassicItemLists', json_encode($aCookieData), time()+3600*26, '/' );
		setcookie( 'AuctionClassicItemLists', json_encode($aCookieData), time()+3600, '/' );
	}

}
