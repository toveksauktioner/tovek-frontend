<?php

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
if( !empty($GLOBALS['viewParams']['auction']['auctionShowInfo.php']['item']) ) {
    $aItem = $GLOBALS['viewParams']['auction']['auctionShowInfo.php']['item'];

} elseif( !empty($_GET['itemId']) ) {
    $aItem = current( $oAuctionEngine->readAuctionItem( array(
        'itemId' => $_GET['itemId'],
        'status' => '*',
        'fields' => '*'
    ) ) );

} else if( empty($_GET['auctionId']) ) {
    return;

}

// Function for getting system texts
$oSystemText = clRegistry::get( 'clSystemText', PATH_MODULE . '/systemText/models' );
$aSystemTexts = valueToKey( 'systemTextKey', $oSystemText->readWithParams([
  'systemTextGroup' => 'ROP'
]) );

/**
 * Old item?
 */
$bOldItem = false;
if( empty($aItem) && !empty($_GET['itemId']) ) {
	$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
	$oBackEnd->setSource( 'entAuctionItem', 'itemId' );
	$aItem = current( $oBackEnd->read( '*', $_GET['itemId'] ) );
	$oBackEnd->oDao->sCriterias = null;
	$aItem['auctionType'] = 'net';

	$bOldItem = true;
}

if( !empty($aItem) ) {
	$_GET['auctionId'] = $aItem['itemAuctionId'];
	$_GET['partId'] = $aItem['itemPartId'];
}

/**
 * Function just for creating custom 'h3' as breakpoint.
 */
if( !function_exists('informationBlock') ) {
  function informationBlock( $sIconClass, $sTitle, $sInformation, $bUnfolded = false  ) {
  	$sId = 'toggleInformation' . str_replace( ' ', '', $sTitle );

  	return '
  		<input type="checkbox" class="toggleInformation" id="' . $sId . '"' . ( $bUnfolded ? ' checked="checked"' : '' ) . '>
      <i class="' . $sIconClass . '"></i>
  		<label for="' . $sId . '">
  			' . $sTitle . '
				<div class="toggleArrow">
					<span class="fold"><i class="fas fa-chevron-down"></i></span>
					<span class="unfold"><i class="fas fa-chevron-right"></i></span>
				</div>
			</label>
  		<div class="information">
  			' . $sInformation . '
  		</div>';
  }
}

/**
 * Function just for creating custom block partTitle
 */
if( !function_exists('informationBlockPart') ) {
  function informationBlockPart( $sIconClass, $sInformation ) {
  	return '
      <div class="blockPart">
        <i class="' . $sIconClass . '"></i>
    		<div class="partInformation">' . $sInformation . '</div>
      </div>';
  }
}

/**
 * Function just for creating help button
 */
if( !function_exists('helpButton') ) {
  function helpButton( $sText, $sSearchQ ) {
    global $oRouter;

  	return '
      <a href="' . $oRouter->getPath( 'guestHelp' ) . '?q=' . $sSearchQ . '" class="button white small narrow helpButton popupLink no-print" data-size="full">
        <span class="responsive desktop">' . _( 'Frågor & svar' ) . ' ' . $sText . '</span>
        <span class="responsive tablet"> ' . _( 'Frågor & svar' ) . '</span>
        <span class="responsive mobile">' . _( 'Hjälp' ) . '</span>
      </a>';
  }
}

if( !empty($_GET['auctionId']) && !empty($_GET['partId']) ) {
	// Auction data
	$aAuction = current( $oAuctionEngine->readAuction( array(
		'fields' => '*',
		'partId' => (int) $_GET['partId'],
		'auctionStatus' => '*',
		'partStatus' => '*'
	) ) );

	/**
	 * Old Auction?
	 */
	$bOldAuction = false;
	if( empty($aAuction) ) {
		$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
		$oBackEnd->setSource( 'entAuction', 'auctionId' );
		$aAuction = current( $oBackEnd->read( '*', $_GET['auctionId'] ) );
		$oBackEnd->oDao->sCriterias = null;

		$bOldAuction = true;
	}

	if( !empty($aAuction) ) {
		// Auction address data
		$aAuctionAddresses = valueToKey( 'addressId', $oAuctionEngine->readAuctionAddressByAuctionPart_in_Auction($_GET['partId']) );

		/**
		 * Old address?
		 */
		$bOldAddress = false;
		if( empty($aAuctionAddresses) ) {
			$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
			$oBackEnd->setSource( 'entAuctionAddress', 'addressId' );
			$oBackEnd->oDao->setCriterias( array(
				'addressPartId' => array(
					'fields' => 'addressPartId',
					'value' => $_GET['partId']
				),
			) );
			$aAuctionAddresses = valueToKey( 'addressId', $oBackEnd->read( '*' ) );
			$oBackEnd->oDao->sCriterias = null;

			$bOldAddress = true;
		}

		// If the item has an specific address connection, just use that one.
		if( !empty($aItem['itemAddressId']) ) {
			$aAuctionAddresses = array( $aAuctionAddresses[ $aItem['itemAddressId'] ] );
		}

		/**
		 * Auction description
		 */
		$sAuctionDescription = '';
		if( !empty($aItem) ) {
			// If viewed is a single call

			if( $aItem['itemVatValue'] > 0 ) {
				$sAuctionDescription .= $aSystemTexts['INFO_ITEM_VAT_YES']['systemTextMessage'];
			}
			if( $aItem['itemFeeValue'] > 0 ) {
				$sAuctionDescription .= $aSystemTexts['INFO_ITEM_FEE_YES']['systemTextMessage'];
			}
		} else {
			$sAuctionDescription .= $aSystemTexts['INFO_ITEM_CATALOGUE']['systemTextMessage'];

		}
		$sAuctionDescription .= ( !empty($aAuction['auctionDescription']) ? '<p>' . $aAuction['auctionDescription'] . '</p>' : '' );
    $sAuctionDescription .= $oSystemText->replaceParams( $aSystemTexts['INFO_ITEM_INFO'] );


		/*
		 * Auction addresses info
		 */
		$sShowingInfo = helpButton( _('om visning'), 'visning' );
		$sCollectInfo = helpButton( _('om avhämtning'), 'avhämtning' );
		$sLoadingInfo = '';
		$sFreightInfo = helpButton( _('om frakt'), 'frakt' );

		foreach( $aAuctionAddresses as $aAuctionAddress ) {

      // Location information used for both showing and collecting
      $sLocationInformation = '';
      if( empty($aAuctionAddress['addressHidden']) || ($aAuctionAddress['addressHidden'] == 'no') ) {
        if( !empty($aAuctionAddress['addressAddress']) ) {
    			$sLocationInformation = '
    				<a href="https://maps.google.com/?q=' . urlencode($aAuctionAddress['addressAddress']) . '" target="_blank">' . $aAuctionAddress['addressAddress'] . '</a>';
        }

        if( !empty($aAuctionAddress['addressAddressDescription']) ) {
          $sLocationInformation .= '
            <p>
              <strong>' . _( 'Vägbeskrivning' ) . '</strong>
              ' . $aAuctionAddress['addressAddressDescription'] . '
            </p>';
        }

        $sLocationInformation = informationBlockPart( 'fas fa-map-marker-alt', $sLocationInformation  );
      }

			// Showing data
      $sShowingInfo .= '
        <h4>' . $aAuctionAddress['addressTitle'] . '</h4>
        ' . $sLocationInformation;

      // Showing time
      $sShowingTimeInfo = '';
			if( !empty(trim($aAuctionAddress['addressShowingSpecial'])) ) {
				$sShowingTimeInfo .= '
					<strong>' . _( 'Tid enligt överenskommelse på telefon' ) . ':</strong> ' . $aAuctionAddress['addressShowingSpecial'];

        $sPreRegistrationDeadline = _( 'tid enligt överenskommelse på telefon' );
        $sPreRegistrationInfo = '';

			} else if( !empty($aAuctionAddress['addressShowingStart']) && ($aAuctionAddress['addressShowingStart'] != '0000-00-00 00:00:00') ) {
				$iShowingStartTime = strtotime( $aAuctionAddress['addressShowingStart'] );
				$iShowingEndTime = strtotime( $aAuctionAddress['addressShowingEnd'] );
				$sShowingTimeInfo .= '
					<strong>' . ucfirst( formatIntlDate('EEEE', $iShowingStartTime) ) . 'en den ' . formatIntlDate( 'd MMM', $iShowingStartTime ) . ' mellan kl. ' . formatIntlDate( 'HH:mm', $iShowingStartTime ) . '-' . formatIntlDate( 'HH:mm', $iShowingEndTime ) . '</strong>.';

        // Last pre reg time is 24 hours before show end
        // If it is monday - pre reg time is on friday before
        $iPreRegSecondsBefore = 86400;
        if( formatIntlDate('e', $iShowingStartTime) == 1 ) $iPreRegSecondsBefore *= 3;
        $iPreRegTime = $iShowingEndTime - $iPreRegSecondsBefore;
        $sPreRegistrationDeadline = sprintf( _('senast den %s kl. 12.00'), formatIntlDate('d MMM', $iPreRegTime) );
        $sPreRegistrationInfo = sprintf( _('30 minuter visning mellan %s-%s.'), formatIntlDate('HH:mm', $iShowingStartTime), formatIntlDate('HH:mm', $iShowingEndTime) );
			}

      $sShowingInfo .= informationBlockPart( 'fas fa-clock', $sShowingTimeInfo  );


      // Pre registration
      if( !empty($aAuctionAddress['addressPreRegistration']) && ($aAuctionAddress['addressPreRegistration'] != 'no') ) {
        $aPreRegReplace = [
          'preRegDeadline' => $sPreRegistrationDeadline,
          'preRegInfo' => $sPreRegistrationInfo
        ];

        if( !empty(AUCTION_PARTNER_SHOW_INFO[ $aAuctionAddress['addressPreRegistration'] ]) ) {
          $aPreRegReplace += [
            'preRegPhone' => AUCTION_PARTNER_SHOW_INFO[ $aAuctionAddress['addressPreRegistration'] ]['phone'],
            'preRegEmail' => AUCTION_PARTNER_SHOW_INFO[ $aAuctionAddress['addressPreRegistration'] ]['email']
          ];
        }

        $sPreRegInfo = $oSystemText->replaceParams( $aSystemTexts['INFO_ITEM_SHOW_PREREG'], $aPreRegReplace );
        $sShowingInfo .= informationBlockPart( 'fas fa-exclamation-triangle', $sPreRegInfo  );
      }


      // Extra info for showing part
			if( !empty($aAuctionAddress['addressShowingInfo']) ) {
        $sShowingInfo .= informationBlockPart( 'fas fa-info-circle', $aAuctionAddress['addressShowingInfo']  );
			}

			// Collect data
      $sCollectInfo .= '
        <h4>' . $aAuctionAddress['addressTitle'] . ':</h4>
        ' . $sLocationInformation;


      // Colllect time
      $sCollectTimeInfo = '';
			if( !empty(trim($aAuctionAddress['addressCollectSpecial'])) ) {
				$sCollectTimeInfo .= '
					<strong>' . _( 'Tid enligt överenskommelse på telefon' ) . ':</strong> ' . $aAuctionAddress['addressCollectSpecial'];

			} else if( !empty($aAuctionAddress['addressCollectStart']) && ($aAuctionAddress['addressCollectStart'] != '0000-00-00 00:00:00') ) {
				$iCollectStartTime = strtotime( $aAuctionAddress['addressCollectStart'] );
				$iCollectEndTime = strtotime( $aAuctionAddress['addressCollectEnd'] );
				$sCollectTimeInfo .= '
					<strong>' . ucfirst( formatIntlDate('EEEE', $iCollectStartTime) ) . 'en den ' . formatIntlDate( 'd MMM', $iCollectStartTime ) . ' mellan kl. ' . formatIntlDate( 'HH:mm', $iCollectStartTime ) . '-' . formatIntlDate( 'HH:mm', $iCollectEndTime ) . '</strong>.';
			}

      $sCollectInfo .= informationBlockPart( 'fas fa-clock', $sCollectTimeInfo  );

			if( !empty($aAuctionAddress['addressCollectInfo']) ) {
        $sCollectInfo .= informationBlockPart( 'fas fa-info-circle', $aAuctionAddress['addressCollectInfo']  );
			}

      switch( $aAuctionAddress['addressFreightHelp'] ) {
        case 'yes':
          $sFreightInfo .= $aSystemTexts['INFO_ITEM_FREIGHT_YES']['systemTextMessage'];
          break;

        case 'no':
          $sFreightInfo .= $aSystemTexts['INFO_ITEM_FREIGHT_NO']['systemTextMessage'];
          break;

        case 'custom':
          $sFreightInfo .= $aAuctionAddress['addressFreightInfo'];
          break;

        default:
				  if( !empty(AUCTION_PARTNER_SHOW_INFO[ $aAuctionAddress['addressFreightHelp'] ]) ) {

            $sFreightInfo .= $oSystemText->replaceParams( $aSystemTexts['INFO_ITEM_FREIGHT_CONTACT'], [
              'freightHelpName' => $aAuctionAddress['addressFreightHelp'],
              'freightHelpPhone' => AUCTION_PARTNER_SHOW_INFO[ $aAuctionAddress['addressFreightHelp'] ]['phone'],
              'freightHelpEmail' => AUCTION_PARTNER_SHOW_INFO[ $aAuctionAddress['addressFreightHelp'] ]['email']
            ] );

          }
			}

			switch( $aAuctionAddress['addressForkliftHelp'] ) {
        case 'yes':
          $sLoadingInfo .= $aSystemTexts['INFO_ITEM_LOADING_HELP_YES']['systemTextMessage'];
          break;

        case 'no':
          $sLoadingInfo .= $aSystemTexts['INFO_ITEM_LOADING_HELP_NO']['systemTextMessage'];
          break;

        case 'custom':
          $sLoadingInfo .= $aAuctionAddress['addressLoadingInfo'];
          break;

        default:
				  // Do nothing
			}
		}

    // General collect info
    if( !empty($sCollectInfo) ) {
      $sCollectInfo .= $aSystemTexts['INFO_ITEM_COLLECT_GENERAL']['systemTextMessage'];
    }

    // Contact info
		if( !empty($aAuction['auctionContactDescription']) ) {
			$aAuctionContactDescription = explode( "\n", $aAuction['auctionContactDescription'] );

			$sAuctionContactDescription = '';
			foreach( $aAuctionContactDescription as $sRow ) {
				$aWords = explode( ' ', $sRow );

				foreach( $aWords as $sWord ) {
					$sAuctionContactDescription .= ' ' . ( stristr($sWord, '@') ? '<a href="mailto:' . $sWord . '">' . $sWord . '</a>' : $sWord );
				}

				$sAuctionContactDescription .= '<br />';
			}

			$sAuctionContactDescription = '
				<p>' . $sAuctionContactDescription . '</p>';
		}

    // Payment info
		if( !empty($aAuction['auctionLastPayDate']) ) {
      $sPaymentInfo = helpButton( _('om betalning'), 'betal' );
      $sPaymentInfo .= $oSystemText->replaceParams( $aSystemTexts['INFO_ITEM_PAYMENT'], [
        'paymentLastDate' => $aAuction['auctionLastPayDate']
      ] );
		}


		/**
		 * Output
		 */
		if( empty($aItem) ) {
      $sAuctionTitle = ( !empty($aAuction['partAuctionTitle']) ? $aAuction['partAuctionTitle'] : $aAuction['auctionTitle'] );
			echo '
				<h1>' . $sAuctionTitle . '</h1>';
		}

    echo '
      <ul class="informationTable">
        ' . ( !empty($sAuctionDescription) ? '<li>' . informationBlock('fas fa-info-circle', 'Information', $sAuctionDescription, true ). '</li>' : '' ) . '
        ' . ( !empty($sAuctionContactDescription) ? '<li>' . informationBlock('fas fa-phone', 'Frågor?', $sAuctionContactDescription ). '</li>' : '' ) . '
        ' . ( !empty($sShowingInfo) ? '<li>' . informationBlock('fas fa-eye', 'Visning', $sShowingInfo ). '</li>' : '' ) . '
        ' . ( !empty($sPaymentInfo) ? '<li>' . informationBlock('fas fa-wallet', 'Betalning', $sPaymentInfo ). '</li>' : '' ) . '
        ' . ( !empty($sCollectInfo) ? '<li>' . informationBlock('fas fa-truck-loading', 'Avhämtning', $sCollectInfo ). '</li>' : '' ) . '
        ' . ( !empty($sLoadingInfo) ? '<li>' . informationBlock('fas fa-people-carry', 'Lasthjälp med truck', $sLoadingInfo ). '</li>' : '' ) . '
        ' . ( !empty($sFreightInfo) ? '<li>' . informationBlock('fas fa-truck', 'Frakthjälp', $sFreightInfo ). '</li>' : '' ) . '
      </ul>';

    // Auction catalogue
    if( !empty($aItem) ) {
      echo '
        <a href="' . $oRouter->getPath( 'guestPrintAuction' ) . '?auctionId=' . $aAuction['auctionId'] . '&partId=' . $aAuction['partId'] . '" class="button white" target="_blank"><i class="fas fa-print"></i> ' . _( 'Auktionsinfo och roplista för utskrift' ) . '</a>';
    }

	} else {
		echo '
		<p style="text-align: center;">
			<span style="font-size: 28px; color: #c4c4c4; font-weight: 300;">
				- ' . _( 'Something went wrong' ) . ' -
			</span>
		</p>';
	}
} else {
	echo '
		<p style="text-align: center;">
			<span style="font-size: 28px; color: #c4c4c4; font-weight: 300;">
				- ' . _( 'No auction to show' ) . ' -
			</span>
		</p>';
}
