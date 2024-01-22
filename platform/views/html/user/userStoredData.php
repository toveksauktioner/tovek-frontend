<?php

$sUserData = '';
$sUserNewsletterSetting = '';
$sUserBidData = '';
$sUserNotes = '';
$aCsvData = array();

$oUser = clRegistry::get( 'clUser' );
	$aUserDataDict = $oUser->oDao->getDataDict();
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$oUserSettings = clRegistry::get( 'clUserSettings', PATH_MODULE . '/userSettings/models' );
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oUserNote = clRegistry::get( 'clUserNote', PATH_MODULE . '/userNote/models' );

if( !empty($_POST['frmRequestDeletion']) ) {
	$oUser->updateData( array(
		'userDeletionRequest' => 'requested'
	) );
}

// Settings
$aAvailableSettings = valueToKey( 'settingsKey', $oUserSettings->readByUserGroup('user') );
$aUserSettings = arrayToSingle( $oUserSettings->readUserSetting($_SESSION['userId']), 'settingsKey', 'settingsValue' );

// Countries
$aCountriesData = valueToKey( 'countryId', $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName',
	'countryIsoCode2'
) ) );

// User data
$aUserFields = $aUserDataDict['entUser'] + $aUserDataDict['entUserInfo'];
unset(
	$aUserFields['userLastSessionId'],
	$aUserFields['userId'],
	$aUserFields['userPass'],
	$aUserFields['userGrantedStatus'],
	$aUserFields['userCheckedByAdmin'],
	$aUserFields['infoUserId'],
	$aUserFields['infoVat'],
	$aUserFields['infoApproved'],
	$aUserFields['infoCountryCode'],
	$aUserFields['infoCreditChecked'],
	$aUserFields['userAcceptedAgreementId']
);
$aUserData = (array) $oUser->readData( array_keys($aUserFields) );

// Remove delivery address country if the address is the same as invoice address
if( empty($aUserData['infoDeliveryAddress']) || ($aUserData['infoDeliveryAddress'] == $aUserData['infoAddress']) ) {
	unset($aUserData['infoDeliveryCountry']);
}
// User bids
$aUserBids = groupByValue( 'bidItemId', $oAuctionEngine->readByUser_in_AuctionBid($_SESSION['userId']) );
$aUserAutoBids = groupByValue( 'autoItemId', $oAuctionEngine->readByUser_in_AuctionAutoBid($_SESSION['userId']) );

// Get item and auction info
if( !empty($aUserBids) ) {
	$aItems = groupByValue( 'itemPartId', $oAuctionEngine->read('AuctionItem', array(
		'itemId',
		'itemSortNo',
		'itemTitle',
		'itemAuctionId',
		'itemPartId'
	), array_keys($aUserBids)) );
	$aAuctions = $oAuctionEngine->readAuction( [
		'fields' => [
			'auctionId',
			'partId',
			'auctionTitle',
			'partLocation',
			'partAuctionStart'
		],
		'partId' => array_keys($aItems)
	] );
}

// User notes
$aUserNotes = $oUserNote->readByUserId( $_SESSION['userId'] );

// Assemble data
foreach( $aUserData as $sFieldName => $sFieldData ) {
	switch( $sFieldName ) {
		case 'userLastIp':
			$sData = long2Ip( $sFieldData );
			break;

		default:
			$sData = $sFieldData;
	}

	if( (substr($sFieldName, -7) == 'Country') && !empty($aCountriesData[ $sFieldData ]['countryName']) ) {
		$sData = _( $aCountriesData[ $sFieldData ]['countryName'] );
	}

	if( ($aUserFields[ $sFieldName ]['type'] == 'array') && !empty($aUserFields[ $sFieldName ]['values'][ $sData ]) ) {
		$sData = $aUserFields[ $sFieldName ]['values'][ $sData ];
	}

	if( !empty($sFieldData) && ($sFieldData != '-') ) {
		$sTitle = ( !empty($aUserFields[ $sFieldName ][ 'title' ]) ? $aUserFields[ $sFieldName ][ 'title' ] : $sFieldName );

		$sUserData .= '
			<tr>
				<td class="title ' . $sFieldName . '">' . $sTitle . '</td>
				<td class="data">' . $sData . '</td>
			</tr>';

		$aCsvData[] = array(
			'title' => $sTitle,
			'data' => $sData
		);
	}
}

$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );
if( !empty($aUserData['userEmail']) ) {
	$aNewsletterData = $oNewsletterSubscriber->readByEmail( $aUserData['userEmail'], 'subscriberId' );
	if( !empty($aNewsletterData) ) {
		$sUserNewsletterSetting .= '
			<hr>
			' . _( 'Du prenumererar på vårt nyhetsbrev' );

		$aCsvData[] = array(
			'title' => _( 'Nyhetsbrev' ),
			'data' => _( 'Yes' )
		);
	}
}

if( !empty($aAuctions) ) {
	$sUserBidData .= '
		<hr>
		<h2>' . _( 'Bud' ) . '</h2>
		<table class="bidData">
			<tbody>';

	foreach( $aAuctions as $aAuctionData ) {
		if( !empty($aItems[ $aAuctionData['partId'] ]) ) {
			$sUserBidData .= '
				<tr>
					<td colspan="4" class="auction">
						<h4>' . $aAuctionData['auctionTitle'] . ' - ' . $aAuctionData['partLocation'] . ' ' . mb_substr( $aAuctionData['partAuctionStart'], 0, 10) . '</h4>
					</td>
				</tr>';

			foreach( $aItems[ $aAuctionData['partId'] ] as $aItemData ) {
				$sUserBidData .= '
					<tr>
						<td class="item itemSortNo">' . _( 'Item' ) . ' ' . $aItemData['itemSortNo'] . '</td>
						<td colspan="3" class="item">' . $aItemData['itemTitle'] . '</td>
					</tr>';

				// Merge the autobid and bid data
				$aItemBids = array();
				if( !empty($aUserAutoBids[ $aItemData['itemId'] ]) ) {
					foreach( $aUserAutoBids[ $aItemData['itemId'] ] as $aAutoBidData ) {
						$aItemBids[ $aAutoBidData['autoCreated'] ][] = array(
							'type' => _( 'Auto bid' ),
							'bid' => $aAutoBidData['autoMaxBid']
						);
					}
				}

				foreach( $aUserBids[ $aItemData['itemId'] ] as $aBidData ) {
					$aItemBids[ $aBidData['bidCreated'] ][] = array(
						'type' => _( 'Bid' ),
						'bid' => $aBidData['bidValue']
					);
				}

				// Sort by creation date
				ksort( $aItemBids );

				// Assemble the bid output
				foreach( $aItemBids as $sCreated => $aBidGroupData ) {
					foreach( $aBidGroupData as $aBidData ) {
						$sUserBidData .= '
							<tr>
								<td>&nbsp;</td>
								<td>' . $sCreated . '</td>
								<td>' . $aBidData['type'] . '</td>
								<td>' . $aBidData['bid'] . '</td>
							</tr>';

						$aCsvData[] = array(
							'title' => _( 'Bid' ),
							'data' => $aBidData['type'] . ' ' . _( 'på' ) . ' ' . _( 'Item' ) . ' ' . $aItemData['itemSortNo'] . ' ' . _( 'i auktion' ) . ' ' . $aAuctionData['auctionTitle'] . ' - ' . $aAuctionData['partLocation'] . ' ' . mb_substr( $aAuctionData['partAuctionStart'], 0, 10)
						);
					}
				}
			}
			
		}
	}

	$sUserBidData .= '
			</tbody>
		</table>';
}

// Request deletion form
switch( $aUserData['userDeletionRequest'] ) {
	case 'requested':
		$sRequestDeletionForm = _( 'Din begäran om borttag av uppgifterna behandlas av administratör.' );
		break;

	case 'accepted':
		$sRequestDeletionForm = _( 'Uppgifterna har tagits bort på din begäran.' );
		break;

	case 'declined':
		$sRequestDeletionForm = _( 'Begäran om borttag har nekats. Ett mail med orsaken har skickats till ovanstående email.' );
		break;

	default:
		$sRequestDeletionForm = '
			<form method="post">
				<div class="field">
					<div class="tabs">
						<span class="tab languageSelector selected" data-lang="swedish">Svenska</span>
						<span class="tab languageSelector" data-lang="english">English</span>
					</div>
					<input type="checkbox" name="frmRequestDeletion" id="requestDeletionAccept" value="1">
					<label for="requestDeletionAccept">
						<span class="lang swedish">' . _( 'Jag inser att genom att skicka in denna begäran så förstår jag att mitt konto på tovek.se inte längre kommer att fungera för att logga in eller lägga bud. Åtgärden går inte att ångra när den väl är genomförd.' ) . '</span>
						<span class="lang english">' . _( 'I realise that by sending this request my account at tovek.se will not work to log in or place bids. Once done the data cannot be restored.' ) . '</span>
					</label>
				</div>
				<button type="submit" disabled="diabled" id="requestDeletionBtn">
					<span class="lang swedish">' . _( 'Begär att uppgifterna tas bort' ) . '</span>
					<span class="lang english">' . _( 'Request removal of the data above' ) . '</span>
				</button>
			</form>';
}

// User notes
if( !empty($aUserNotes) ) {
	$sUserNotes .= '
		<hr>
		<h2>' . _( 'Notes' ) . '</h2>';

	foreach( $aUserNotes as $aNoteData ) {
		$sUserNotes .= '
			<div class="userNote">
				<h4>' . substr($aNoteData['noteCreated'], 0, 10) . ' - ' . $aNoteData['noteTitle'] . '</h4>
				<p>' . $aNoteData['noteMessage'] . '</p>
			</div>';

		$aCsvData[] = array(
			'title' => _( 'Note' ),
			'data' => substr($aNoteData['noteCreated'], 0, 10) . ' - ' . $aNoteData['noteTitle'] . ': ' . $aNoteData['noteMessage']
		);
	}
}

if( !empty($_GET['download']) && !empty($aCsvData) ) {
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=data.csv');

	$oOutput = fopen( 'php://output', 'w' );

	fputcsv( $oOutput, array(
		_( 'Title' ),
		_( 'Data' )
	) );

	foreach( $aCsvData as $aCsvRow ) {
		fputcsv( $oOutput, $aCsvRow );
	}
	exit;
}

echo '
	<div class="view user storedData">
		<div id="downloadData">
			<a href="?download=1" class="icon iconText iconDown" target="_blank">' . _( 'Ladda ner som CSV-fil' ) . '</a>
		</div>
		<h2>' . _( 'User data' ) . '</h2>
		<table class="userData">
			<tbody>
				' . $sUserData . '
			</tbody>
		</table>
		' . $sUserNewsletterSetting . '
		' . $sUserNotes . '
		' . $sUserBidData . '
		<hr>
		<div class="requestDeletion">
		' . $sRequestDeletionForm . '
		</div>
	</div>
	<script>
		$("#requestDeletionAccept").click( function() {
			$("#requestDeletionBtn").prop( "disabled", !$(this).prop("checked") );
		} );
	</script>';
