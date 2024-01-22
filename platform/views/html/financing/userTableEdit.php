<?php

$sOutput = '';

$oFinancing = clRegistry::get( 'clFinancing', PATH_MODULE . '/financing/models' );
	$aFinancingDataDict = $oFinancing->oDao->aDataDict;
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
	$oInvoiceDao = $oInvoiceEngine->getDao( 'Invoice' );

if( !empty($_GET['useFinancing']) ) {
	$aFinancing = current( $oFinancing->read('financingUserId', $_GET['useFinancing']) );

	if( !empty($aFinancing) && ($aFinancing['financingUserId'] == $_SESSION['userId'])) {
		$oFinancing->updateFinancing( $_GET['useFinancing'], array(
			'financingLocalStatus' => 'requested'
		) );
	}
}


$aItemId = null;
$bCancelPossible = true;
if( $oRouter->sCurrentLayoutKey == 'userInvoiceList' ) {
	// Invoice page -  show only items on invoices that are unpayed or partpayed

	$bCancelPossible = false;

	$oInvoiceDao->setCriterias( array(
		'unpaid' => array(
			'fields' => 'invoiceStatus',
			'type' => 'in',
			'value' => array(
				'unpaid',
				'partpaid'
			)
		)
	) );
	$aInvoices = arrayToSingle( $oInvoiceEngine->readByUser_in_Invoice($_SESSION['userId'], 'invoiceId'), null, 'invoiceId' );
	if( !empty($aInvoices) ) {
		$aItemId = arrayToSingle( $oInvoiceEngine->readByInvoice_in_InvoiceLine($aInvoices, 'invoiceLineItemId'), null, 'invoiceLineItemId' );
	}
}

// Read items with financing applications
$aFinancings = $oFinancing->readFinancingToItem( array(
  'userId' => $_SESSION['userId'],
	'itemId' => $aItemId,
	'includeFinancingData' => true
) );


if( !empty($aFinancings) ) {
	$aItems = valueToKey( 'itemId', $oAuctionEngine->readAuctionItem( array(
		'fields' => array(
			'itemId',
			'itemSortNo',
			'itemTitle',
			'itemStatus',
			'itemWinningUserId',
			'itemEndTime',
			'routePath'
		),
		'itemId' => arrayToSingle( $aFinancings, null, 'itemId' ),
		'status' => array(
			'active',
			'ended'
		)
	) ) );

  foreach( $aFinancings as $entry ) {
		if( empty($aItems[ $entry['itemId'] ]) ) continue;
		$aThisItem = $aItems[ $entry['itemId'] ];
		$aButtons = array();
		$sInvoiceNo = '';

		if( $aThisItem['itemStatus'] != 'ended' ) {
			list( $sEndDate, $sEndTime ) = explode( ' ', $aThisItem['itemEndTime'] );

			$sItemEndTime = '
				<datetime>
					<date>' . $sEndDate . '</date>
					<time>' . $sEndTime . '</time>
				</datetime>';

			$sItemWinner = '';

		} else {
			$sItemEndTime = _( 'Avslutat' );

			if( $_SESSION['userId'] == $aThisItem['itemWinningUserId'] ) {
				$sItemEndTime .= ' (' . _( 'Du vann' ) . ')';

				if( $entry['financingStatus'] == 'ready_to_ship' ) {
					switch( $entry['financingLocalStatus'] ) {
						case 'requested':
							$aButtons[] = '<span class="button disabled">' . _( 'Hanteras' ) . '</span>';
							break;

						case 'cancelled':
							$aButtons[] = '<span class="button cancel">' . _( 'Nekad' ) . '</span>';
							break;

						case 'pending':
						default:
							$aButtons[] = '<a href="?useFinancing=' . $entry['financingId'] . '" class="button submit">' . _( 'Finansiera' ) . '</a>';
					}
				}

				$aInvoiceLine = $oInvoiceEngine->readByItem_in_InvoiceLine( $entry['itemId'] );

				if( !empty($aInvoiceLine) ) {
					$aInvoiceType = arrayToSingle( $oInvoiceEngine->read('invoice', array(
						'invoiceId',
						'invoiceType'
					), arrayToSingle($aInvoiceLine, null, 'invoiceLineInvoiceId')), 'invoiceId', 'invoiceType' );

					$aLastInvoiceLine = array();
					foreach( $aInvoiceLine as $aLine ) {
						if( !empty($aInvoiceType[ $aLine['invoiceLineInvoiceId'] ]) && ($aInvoiceType[ $aLine['invoiceLineInvoiceId'] ] == 'invoice') &&  (empty($aLastInvoiceLine) || ($aLine['invoiceLineId'] > $aLastInvoiceLine['invoiceLineId'])) ) {
							$aLastInvoiceLine = $aLine;
						}
					}

					$sInvoiceNo = current( current($oInvoiceEngine->read('Invoice', 'invoiceNo', $aLastInvoiceLine['invoiceLineInvoiceId'])) );
				}
			} else {
				$sItemEndTime .= ' (' . _( 'Du vann inte' ) . ')';
			}
		}

		if( $bCancelPossible ) {
			// $aButtons[] = '<a href="#" class="button small cancel"><i class="fas fa-times"></i></a>';
		}

		$sOutput .= '
			<div class="financingItem">
				<div class="title">
					<a href="' . $aThisItem['routePath'] . '">' . _( 'Rop' ) . ' ' . $aThisItem['itemSortNo'] . ': <strong>' . $aThisItem['itemTitle'] . '</strong></a>
				</div>
				<div class="endTime"><h3>' . _( 'Slutar' ) . '</h3>' . $sItemEndTime . '</div>
				<div class="financingValue"><h3>' . _( 'Värde' ) . '</h3>' . $entry['requestedValue'] . '</div>
				<div class="financingStatus"><h3>' . _( 'Status' ) . '</h3>' . $aFinancingDataDict['entFinancing']['financingStatus']['values'][ $entry['financingStatus'] ] . '</div>
				<div class="invoiceNo">' . ( !empty($sInvoiceNo) ? '<h3>' . _('Faktura') . '</h3> ' . $sInvoiceNo : '' ) . '</div>
				<div class="financingControls">' . implode( '&nbsp;', $aButtons ) . '</div>
			</div>';
  }
}

if( !empty($sOutput) ) {
	echo '
	  <div class="view financing userTableEdit">
			<h1>' . _( 'Finansiering' ) . '</h1>
	    ' . $sOutput . '
	  </div>';
}

// Reset dao settings
$oInvoiceDao->setCriterias( [] );
