<?php


$aEntry = array (
    'itemId' => '236107',
    'itemSortNo' => '5',
    'itemSortLetter' => '',
    'itemTitle' => 'Träningsmaskin Ortus Fitness PW 06 Quadriceps Superfort',
    'itemSummary' => '',
    'itemDescription' => '',
    'itemInformation' => '',
    'itemRegistrationPlate' => '',
    'itemRegCertArrived' => '',
    'itemVehicleBrand' => '',
    'itemVehicleModel' => '',
    'itemVehicleModelYear' => '',
    'itemVehicleMileageDistance' => '',
    'itemVehicleMileageTime' => '',
    'itemVehicleComment' => '',
    'itemVehicleMascusCatalog' => '',
    'itemVehicleMascusProductDefinition' => '',
    'itemWinningBidId' => '687839',
    'itemWinningBidValue' => '1100',
    'itemWinningUserId' => '37409',
    'itemWinnerMailed' => 'yes',
    'itemMinBid' => '1000',
    'itemBidCount' => '2',
    'itemStatus' => 'active',
    'itemEndTime' => '2019-06-14 11:52:47',
    'itemMarketValue' => '0',
    'itemFeeType' => 'sek',
    'itemFeeValue' => '300',
    'itemVatValue' => '25',
    'itemLocation' => 'Ätran',
    'itemRecalled' => 'no',
    'itemHot' => 'yes',
    'itemViewedCount' => '136',
    'itemComment' => '',
    'itemNeedsAttention' => 'no',
    'itemCreatedByUserId' => '0',
    'itemAuctionId' => '921',
    'itemPartId' => '909',
    'itemSubmissionId' => '12602',
    'itemSubmissionCustomId' => '',
    'itemAddressId' => '2',
    'itemVehicleArchiveImageId' => '0',
    'itemCreated' => '0000-00-00 00:00:00',
    'itemUpdated' => '0000-00-00 00:00:00',
    'itemAutoBidLocked' => 'no',
    'auctionId' => '921',
    'auctionType' => 'net',
    'auctionInternalName' => '',
    'auctionInternalProject' => '',
    'auctionTitle' => 'KK: Atlantic Business Center AB m.fl. inlämnare med bl.a. gymutrustning, sängar, arbetskläder',
    'auctionShortTitle' => '',
    'auctionSummary' => '',
    'auctionDescription' => 'Nätauktion på KK: Atlantic Business Center AB m.fl. inlämnare med bl.a. Gymutrustning, sängar, arbetskläder',
    'auctionContactDescription' => '0346-48775',
    'auctionLocation' => 'Ätran',
    'auctionLastPayDate' => '2015-06-17',
    'auctionArchiveStatus' => 'active',
    'auctionStatus' => 'active',
    'auctionViewedCount' => '9846',
    'auctionCreated' => '2019-01-04 15:51:16',
    'auctionUpdated' => '0000-00-00 00:00:00',
    'partId' => '909',
    'partTitle' => '',
    'partDescription' => '',
    'partLocation' => '',
    'partPreBidding' => 'no',
    'partAuctionStart' => '2015-06-12 10:00:00',
    'partStatus' => 'ended',
    'partHaltedTime' => '0000-00-00 00:00:00',
    'partCreated' => '2015-06-05 09:57:18',
    'partReviewValue' => '3',
    'partReviewComment' => '',
    'partAuctionId' => '921',
    'routeId' => '31223',
    'objectId' => '236107',
    'objectType' => 'AuctionItem',
    'routeLayoutKey' => 'guestAuctionItems',
    'routePathLangId' => '1',
    'routePath' => '/auktion/rop/träningsmaskin-ortus-fitness-pw-06-quadriceps-superfort/236107',
    'routeCreated' => '2019-02-19 08:17:55',
    'routeUpdated' => '0000-00-00 00:00:00'
);

echo '
    <div class="view auction relatedItemList">
        <div class="container">
            <h2>' . _( 'Similar items' ) . '</h2>
            <ul>
                <li>                
                    <div class="imageContainer">
                        <p class="image">
                            <a href="' . SITE_PROTOCOL . '://' . SITE_DOMAIN . '?view=auctionClassic/itemShowAjax.php&itemId=' . $aEntry['itemId'] . '&noCss=true" data-item-id="' . $aEntry['itemId'] . '" class="ajax">
                                <img src="/images/custom/AuctionItem/tn/medium1.png" alt="" />
                            </a>
                        </p>
                    </div>
                    <div class="infoContainer">
                        <h3>
                            <span class="bidNo">' . _( 'Call' ) . ' ' . $aEntry['itemSortNo'] . '</span>
                            <span class="title">' . $aEntry['itemTitle'] . '</span>
                        </h3>	
                        <p class="location">' . $aEntry['itemLocation'] . ' (<a href="#">' . _( 'show on map' ) . '</a>)</p>
                        <ul class="data">
                            <li><strong>' . _( 'Auction' ) . ':</strong> <span>' . $aEntry['auctionTitle'] . '</span></li>
                            <li><strong>' . _( 'Ends' ) . ':</strong> <span>' . convertTime( $aEntry['itemEndTime'], $aEntry['itemId'] ) . '</span></li>
                            <li><strong>' . _( 'Bid' ) . ':</strong> <span></span></li>
                            <li><strong>' . _( 'Vat' ) . ':</strong> <span>' . $aEntry['itemVatValue'] . '%</span></li>
                            <li><strong>' . _( 'Calling fee' ) . ':</strong> <span>' . calculatePrice( $aEntry['itemFeeValue'], array('profile' => 'human') ) . '</span></li>
                        </ul>
                    </div>
                </li>
                <li>                
                    <div class="imageContainer">
                        <p class="image">
                            <a href="' . SITE_PROTOCOL . '://' . SITE_DOMAIN . '?view=auctionClassic/itemShowAjax.php&itemId=' . $aEntry['itemId'] . '&noCss=true" data-item-id="' . $aEntry['itemId'] . '" class="ajax">
                                <img src="/images/custom/AuctionItem/tn/medium1.png" alt="" />
                            </a>
                        </p>
                    </div>
                    <div class="infoContainer">
                        <h3>
                            <span class="bidNo">' . _( 'Call' ) . ' ' . $aEntry['itemSortNo'] . '</span>
                            <span class="title">' . $aEntry['itemTitle'] . '</span>
                        </h3>	
                        <p class="location">' . $aEntry['itemLocation'] . ' (<a href="#">' . _( 'show on map' ) . '</a>)</p>
                        <ul class="data">
                            <li><strong>' . _( 'Auction' ) . ':</strong> <span>' . $aEntry['auctionTitle'] . '</span></li>
                            <li><strong>' . _( 'Ends' ) . ':</strong> <span>' . convertTime( $aEntry['itemEndTime'], $aEntry['itemId'] ) . '</span></li>
                            <li><strong>' . _( 'Bid' ) . ':</strong> <span></span></li>
                            <li><strong>' . _( 'Vat' ) . ':</strong> <span>' . $aEntry['itemVatValue'] . '%</span></li>
                            <li><strong>' . _( 'Calling fee' ) . ':</strong> <span>' . calculatePrice( $aEntry['itemFeeValue'], array('profile' => 'human') ) . '</span></li>
                        </ul>
                    </div>
                </li>
                <li>                
                    <div class="imageContainer">
                        <p class="image">
                            <a href="' . SITE_PROTOCOL . '://' . SITE_DOMAIN . '?view=auctionClassic/itemShowAjax.php&itemId=' . $aEntry['itemId'] . '&noCss=true" data-item-id="' . $aEntry['itemId'] . '" class="ajax">
                                <img src="/images/custom/AuctionItem/tn/medium1.png" alt="" />
                            </a>
                        </p>
                    </div>
                    <div class="infoContainer">
                        <h3>
                            <span class="bidNo">' . _( 'Call' ) . ' ' . $aEntry['itemSortNo'] . '</span>
                            <span class="title">' . $aEntry['itemTitle'] . '</span>
                        </h3>	
                        <p class="location">' . $aEntry['itemLocation'] . ' (<a href="#">' . _( 'show on map' ) . '</a>)</p>
                        <ul class="data">
                            <li><strong>' . _( 'Auction' ) . ':</strong> <span>' . $aEntry['auctionTitle'] . '</span></li>
                            <li><strong>' . _( 'Ends' ) . ':</strong> <span>' . convertTime( $aEntry['itemEndTime'], $aEntry['itemId'] ) . '</span></li>
                            <li><strong>' . _( 'Bid' ) . ':</strong> <span></span></li>
                            <li><strong>' . _( 'Vat' ) . ':</strong> <span>' . $aEntry['itemVatValue'] . '%</span></li>
                            <li><strong>' . _( 'Calling fee' ) . ':</strong> <span>' . calculatePrice( $aEntry['itemFeeValue'], array('profile' => 'human') ) . '</span></li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>';