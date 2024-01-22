<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 20/03/2014 by Renfors
 * Description:
 * Main file for managing user invoices
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */

require_once PATH_MODULE . '/invoice/config/cfInvoice.php';
require_once PATH_FUNCTION . '/fData.php';
require_once PATH_FUNCTION . '/fMoney.php';
require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_BACK_PLATFORM . '/modules/qrCode/config/cfQrCode.php';

class clInvoice extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'Invoice';
		$this->sModulePrefix = 'invoice';

		$this->oDao = clRegistry::get( 'clInvoiceDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/invoice/models' );

		$this->initBase();

		$this->oDao->switchToSecondary();
	}

	/* * *
	 *  Override create function to set created all the time
	 * * */
	public function create( $aData ) {
		$aData += array(
			'invoiceCreated' => date( 'Y-m-d H:i:s' )
		);

		$mInvoiceId = parent::create( $aData );
		$this->setOcrNumber( $mInvoiceId );
		return $mInvoiceId;
	}

	/* * *
	 * Check validity of the data before committing to Fortnox
	 * $aInvoiceData is array with at least invoiceNo and invoiceSentToFortnox fields
	 * $bErrorReport is a possibility to check validity without error output
	 * * */
	public function checkFortnoxValidity( $aInvoiceData, $bErrorOutput = true ) {
		$aErr = array();
		$aAlreadySent = array();

		if( !empty($aInvoiceData) && is_array($aInvoiceData) ) {
			$iLastInvoiceNo = false;
			$iFirstInvoiceNo = false;

			$aInvoiceSortedData = arrayToSingle( $aInvoiceData, 'invoiceNo', 'invoiceSentToFortnox' );
			ksort( $aInvoiceSortedData );

			foreach( $aInvoiceSortedData as $iInvoiceNo => $sSentToFortnox ) {

				if( ($iLastInvoiceNo !== false) && ($iInvoiceNo != ($iLastInvoiceNo + 1)) ) {
					if( !in_array(_('Not in series'), $aErr) ){
						$aErr[] = _( 'Not in series' );
					}
				}
				if( $sSentToFortnox == 'yes' ) {
					$aAlreadySent[] = $iInvoiceNo;
				}

				if( empty($iFirstInvoiceNo) ) $iFirstInvoiceNo = $iInvoiceNo;
				$iLastInvoiceNo = $iInvoiceNo;
			}
		} else {
			$aErr[] = _( 'No data selected' );
		}

		$aLastSentToFortnox = $this->oDao->oDb->query( "SELECT MAX(invoiceNo) FROM entInvoice t1 WHERE invoiceSentToFortnox = 'yes'" );
		if( !empty($aLastSentToFortnox) ) {
			$iLastSentToFortnoxInvoiceNo = current( current($aLastSentToFortnox) );
			if( $iLastSentToFortnoxInvoiceNo != ($iFirstInvoiceNo - 1) ) {
				$aErr[] = _( 'First invoice in series must be' ) . ' ' . ($iLastSentToFortnoxInvoiceNo + 1);
			}
		}

		if( !empty($aAlreadySent) ) {
			sort( $aAlreadySent );
			$aErr[] = implode( ', ', $aAlreadySent ) . ' ' . _( 'is already sent to Fortnox' );
		}

		if( !empty($aErr) ) {
			if( $bErrorOutput === true ) {
				$oNotificationHandler = clRegistry::get( 'clNotificationHandler' );
				$oNotificationHandler->setError( $aErr );
			}

			return false;
		} else {
			return true;
		}
	}

	public function getInvoiceHeader( $aParams = [], $iInvoiceId = null ) {
		clFactory::loadClassFile( 'clOutputHtmlTable' );

		if( !empty($iInvoiceId) ) {
			// Fetch invoice and customer data
			$aParams += current( $this->read(null, $iInvoiceId) );
			if( !empty($aParams['invoiceUserId']) ) {
				$oUserManager = clRegistry::get( 'clUserManager' );
				$aParams += current( $oUserManager->read(array(
					'userCustomerNo'
				), $aParams['invoiceUserId']) );
			}
		}

		$aParams += [
			'pdf' => false
		];

		$oOutputHtmlTable = new clOutputHtmlTable( [
			'invoiceBaseInfo' => [
				'invoiceDate' => [
					'title' => $GLOBALS['invoiceTypeTitles'][$aParams['invoiceType']]
				],
				'customerNo' => [
					'title' => _( 'Customer no' )
				],
				'invoiceNo' => [
					'title' => _( 'Fakturanr.' )
				],
				'page' => [ 'title' => _( 'Page' ) ]
			]
		] );
		$row = [
			'invoiceDate' => $aParams['invoiceDate'],
			'customerNo' => $aParams['userCustomerNo'],
			'invoiceNo' => $aParams['invoiceNo'],
			'page' => '{PAGENO} ' . _('av') . ' {nbpg}'
		];
		$oOutputHtmlTable->addBodyEntry( $row );

		$sCompanyName = ( !empty($aParams['invoiceCompanyName']) ? '<p>' . $aParams['invoiceCompanyName'] . '</p>' : '' );
		$sPersonName = ( !empty($aParams['invoiceFirstname']) ? '<p>' . $aParams['invoiceFirstname'] . ' ' . $aParams['invoiceSurname'] . '</p>' : '' );


		$sFilePath = QR_CODE_DEFAULT_IMAGE_PATH . '/invoice/' . $iInvoiceId . '.' . QR_CODE_FILE_TYPE;
		if( file_exists($sFilePath) ) {
			switch(QR_CODE_FILE_TYPE) {
				case 'svg':
					$sQrSource = 'data:image/svg+xml;base64,' . base64_encode( file_get_contents($sFilePath) );
					break;

				case 'png':
					$sQrSource = 'data:image/png;base64,' . base64_encode( file_get_contents($sFilePath) );
					break;
			}
		}

		$sHeader = '
			<header class="invoiceHeader">
				<div class="logo"><img src="' . ( $aParams['pdf'] ? PATH_PUBLIC : '' ) . '/images/pdf/pdf-logo' . ( isset($aParams['stamp']) ? '-stamp-' . $aParams['stamp'] . '' : '' ) . '.png" width="100%" /></div>
				<div class="invoiceBaseInfo">
					' . $oOutputHtmlTable->render( [
							'class' => 'dataTable invoiceDataTable'
					] ) . '
					<div class="invoiceAddress">
						' . ( ($sCompanyName != $sPersonName) ? $sCompanyName : '' ) . '
						' . $sPersonName . '
						<p>' . $aParams['invoiceAddress'] . '</p>
						<p>' . $aParams['invoiceZipCode'] . ' ' . $aParams['invoiceCity'] . '</p>
					</div>
				</div>
				<div class="qr">
					' . ( !empty($sQrSource) ? '<img src="' . $sQrSource . '">' : '' ) . '
				</div>
			</header>';

		return $sHeader;
	}

	public function getInvoiceFooter() {
		$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
		$sFooter = '
			<footer class="invoiceFooter">
				' . (($aFooter = $oInfoContent->read('contentTextId', 41)) ? $aFooter[0]['contentTextId'] : '' ) . '
			</footer>';
		return $sFooter;
	}

	public function globalSearch( $sSearchString = null, $iSearchNo = null, $aInFields = array(), $aGetFields = array(), $iLimit = 20 ) {
		$this->oDao->sCriterias = '';
		$this->oDao->setEntries( $iLimit );

		if( empty($aInFields) ) {
			$aInFields = array(
				'invoiceNo',
				// 'invoiceInformation',
				'invoiceFirstname',
				'invoiceSurname',
				'invoiceCompanyName',
				// 'invoiceAddress',
				// 'invoiceZipCode',
				// 'invoiceCity'
			);
		}

		if( empty($aGetFields) ) {
			$aGetFields = array(
				'invoiceId AS identifier',
				'invoiceNo AS no',
				"CONCAT(invoiceCompanyName, ', ', invoiceFirstname, ', ', invoiceSurname) AS name",
			);
		}

		$aSearchCriterias = array();
		if( !empty($iSearchNo) ) {
			$aSearchCriterias['searchNo'] = array(
				'type' => '=',
				'value' => $iSearchNo,
				'fields' => 'invoiceNo'
			);
		}
		if( !empty($sSearchString) ) {
			$aSearchWords = explode( ' ', $sSearchString );
			foreach( $aSearchWords as $sWord ) {
				if( mb_strlen($sWord) >= 3 ) {
					$aSearchCriterias[$sWord] = array(
						'type' => 'like',
						'value' => $sWord,
						'fields' => $aInFields
					);
				}
			}
		}

		$this->oDao->setCriterias( $aSearchCriterias );
		$aSearchResult = $this->read( $aGetFields );

		$this->oDao->sCriterias = '';
		$this->oDao->setEntries( 0 );
		return $aSearchResult;
	}

	/* * *
	 * Lock invoice from editing
	 * $aData can be used to alter other fields at the same time (i.e. when setting the sent to Fortnox prop)
	 * * */
	public function lock( $iInvoiceId, $aData = array() ) {
		$aData += array(
			'invoiceLocked' => 'yes',
			'invoiceLockedDate' => date( 'Y-m-d H:i:s' ),
			'invoiceLockedByUserId' => ( isset($_SESSION['userId']) ? $_SESSION['userId'] : null )
		);
		$this->update( $iInvoiceId, $aData );
	}

	/* * *
	 * Send the invoice as email to the user it belongs to
	 * The current (admin) user is set to sender by default
	 * * */
	public function send( $iInvoiceId, $sSender = null, $sReceiver = null, $sAdditionalMessage = null, $bAttachments = true, $sInvoiceTemplate = 'sendAuctionInvoice' ) {
		$oUserManager = clRegistry::get( 'clUserManager' );

		if( empty($sSender) ) {
			// Fetch mail from current user
			$oUser = clRegistry::get( 'clUser' );
			$sSender = $oUser->readData( 'userEmail' );
		}

		// Fetch invoice data
		$aInvoiceData = current( parent::read( array(
			'invoiceNo',
			'invoiceInformation',
			'invoiceUserId',
			'invoiceAuctionId'
		), $iInvoiceId) );

		if( empty($sReceiver) ) {
			$sReceiver = current( current($oUserManager->read('userEmail', $aInvoiceData['invoiceUserId'])) );
		}

		if( !empty($sReceiver) ) {

			// Attachments can be ignored
			if( $bAttachments === true ) {
				// Generate PDF file
				clFactory::loadClassFile( 'clTemplateHtml' );
				$oInvoiceTemplate = new clTemplateHtml();
				$oInvoiceTemplate->setTemplate( 'pdfInvoice.php' );
				$oInvoiceTemplate->setTitle( _( 'Invoice' ) . ' ' . $aInvoiceData['invoiceNo'] );
				$oInvoiceTemplate->setContent( $this->generateInvoiceHtml($iInvoiceId, true) );

				require_once PATH_CORE . '/mPdf/clMPdf.php';
				$oMPdf = new clMPdf();
				$oMPdf->loadHtml( $oInvoiceTemplate->render() );
				$oMPdf->setHtmlHeader( [] );
				$oMPdf->setHtmlFooter( $this->getInvoiceFooter(), _( 'Invoice' ) . '-' . $aInvoiceData['invoiceNo'] );
				$sFileName = _( 'Invoice' ) . '-' . $aInvoiceData['invoiceNo'] . '-' . date( 'YmdHis' ) . '.pdf';
				$sInvoicePdf = $oMPdf->output( $sFileName, 'S' ); // S = return as string
				unset( $oMPdf->oMPdf );
				unset( $oMPdf );
			}

			// Construct the email
			// Note different texts for auction invoices and other invoices
			if( empty($aInvoiceData['invoiceAuctionId']) ) {
				$sContent = $GLOBALS['invoice'][ $sInvoiceTemplate ]['bodyHtml'];

				// Add information
				$sContent = str_replace( '{invoiceNo}', $aInvoiceData['invoiceNo'], $sContent );
				$sContent = str_replace( '{invoiceInformation}', $aInvoiceData['invoiceInformation'], $sContent );

				//Add additional message if supplied
				$sAdditionalMessage = ( !empty($sAdditionalMessage) ? '<strong>' . _( 'Meddelande till dig' ) . ':</strong><p>' . $sAdditionalMessage . '</p><br /><hr />' : '' );
				$sContent = str_replace( '{invoiceAdditionalMessage}', $sAdditionalMessage, $sContent );
			} else {
				// Get Auction and Address information
				$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
				$oInvoiceLine = clRegistry::get( 'clInvoiceLine', PATH_MODULE . '/invoice/models' );

				$aLineItems = arrayToSingle( $oInvoiceLine->readByInvoice($iInvoiceId, 'invoiceLineItemId'), null, 'invoiceLineItemId' );
				$aLineItemsData = $oAuctionEngine->read( 'AuctionItem', array(
					'itemSortNo',
					'itemAddressId',
					'itemPartId'
				), $aLineItems );
				$aItemData = arrayToSingle( $aLineItemsData, 'itemSortNo', 'itemAddressId' );

				// Pickup info
				$sCollectInfo = '';
				$bFreightHelp = false;

				if( !empty($aLineItemsData) ) {
					$aItemAddresses = $oAuctionEngine->readAuctionAddress_in_Auction( '*', $aItemData );

					if( !empty($aItemAddresses) ) {
						foreach( $aItemAddresses as $aItemAddress ) {

							$aAddressItems = array();
							foreach( $aItemData as $iItemSortNo => $iItemAddressId ) {
								if( ($iItemAddressId == $aItemAddress['addressId']) ) {
									$aAddressItems[] = $iItemSortNo;
								}
							}

							$sCollectInfo .= '
								<p>
									' . _( 'Item' ) . ' ' . implode( ', ', $aAddressItems ) . ': <br />';

							$sCollectInfo .= '
								<strong>' . $aItemAddress['addressTitle'] . ':</strong> ' . $aItemAddress['addressAddress'];

							if( !empty($aItemAddress['addressCollectStart']) && ($aItemAddress['addressCollectStart'] != '0000-00-00 00:00:00') ) {
								$iCollectStartTime = strtotime( $aItemAddress['addressCollectStart'] );
								$iCollectEndTime = strtotime( $aItemAddress['addressCollectEnd'] );
								$sCollectInfo .= '
									<strong>' . ucfirst( formatIntlDate('EEEE', $iCollectStartTime) ) . 'en den ' . formatIntlDate( 'd MMM', $iCollectStartTime ) . ' mellan kl. ' . formatIntlDate( 'HH:mm', $iCollectStartTime ) . '-' . formatIntlDate( 'HH:mm', $iCollectEndTime ) . '</strong>.';

								if( !empty($aItemAddress['addressCollectSpecial']) ) {
									$sCollectInfo .= '
										<br><strong>' . _( 'För mer info' ) . ':</strong> ' . $aItemAddress['addressCollectSpecial'] . '</strong>.';
								}
							} else {
								if( !empty($aItemAddress['addressCollectSpecial']) ) {
									$sCollectInfo .= '
										<br><strong>' . _( 'Tid enligt överenskommelse på telefon' ) . ':</strong> ' . $aItemAddress['addressCollectSpecial'] . '</strong>.';
								}
							}

							if( !empty($aItemAddress['addressCollectInfo']) ) {
								$sCollectInfo .= '
									<br>' . $aItemAddress['addressCollectInfo'];
							}

							if( !empty($aItemAddress['addressAddressDescription']) ) {
								$sCollectInfo .= '
									<p><strong>' . _( 'Vägbeskrivning' ) . '</strong>
									<br>' . $aItemAddress['addressAddressDescription'] . '</p>';
							}

							if( $aItemAddress['addressFreightHelp'] == 'yes' ) {
								$sCollectInfo .= '
									<p><strong>Frakthjälp finns mot betalning.</strong> Frakthjälp skall beställas senast 2 dagar innan ordinarie utlämningsdag.</p>';
								$bFreightHelp = true;
							} else {
								$sCollectInfo .= '
									<p><strong>Frakthjälp finns inte.</strong></p>';
								$bFreightHelp = true;
							}

							switch( $aItemAddress['addressForkliftHelp'] ) {
								case 'yes':
									$sCollectInfo .= '
										<p><strong>Lasthjälp med truck finns.</strong> Kontakta auktionsplatsansvarig senast 2 dagar innan ordinarie utlämningsdag.</p>';
									break;

								case 'no':
									$sCollectInfo .= '
										<p><strong>Lasthjälp med truck finns inte.</strong></p>';
									break;

									case 'custom':
										$sCollectInfo .= '
											<p><strong>Lasthjälp med truck?</strong> ' . strip_tags($aItemAddress['addressLoadingInfo']) . '</p>';
										break;

									default:
										// Do nothing
							}

							$sCollectInfo .= '</p>';
						}
					} else {
						$aAuctionParts = arrayToSingle( $aLineItemsData, 'itemSortNo', 'itemPartId' );
						$aPartAddresses = $oAuctionEngine->readAuctionAddressByAuctionPart_in_Auction( $aAuctionParts );

						// If there is only one address connected to part - use this for items that are not connected to any address
						if( count($aPartAddresses) == 1 ) {
							$aItemAddress = current($aPartAddresses);

							$sCollectInfo = '
								<strong>' . $aItemAddress['addressTitle'] . ':</strong> ' . $aItemAddress['addressAddress'];

							if( !empty($aItemAddress['addressCollectStart']) && ($aItemAddress['addressCollectStart'] != '0000-00-00 00:00:00') ) {
								$iCollectStartTime = strtotime( $aItemAddress['addressCollectStart'] );
								$iCollectEndTime = strtotime( $aItemAddress['addressCollectEnd'] );
								$sCollectInfo .= '
									<strong>' . ucfirst( formatIntlDate('EEEE', $iCollectStartTime) ) . 'en den ' . formatIntlDate( 'd MMM', $iCollectStartTime ) . ' mellan kl. ' . formatIntlDate( 'HH:mm', $iCollectStartTime ) . '-' . formatIntlDate( 'HH:mm', $iCollectEndTime ) . '</strong>.';

								if( !empty($aItemAddress['addressCollectSpecial']) ) {
									$sCollectInfo .= '
										<br><strong>' . _( 'För mer info' ) . ':</strong> ' . $aItemAddress['addressCollectSpecial'] . '</strong>.';
								}
							} else {
								if( !empty($aItemAddress['addressCollectSpecial']) ) {
									$sCollectInfo .= '
										<br><strong>' . _( 'Tid enligt överenskommelse på telefon' ) . ':</strong> ' . $aItemAddress['addressCollectSpecial'] . '</strong>.';
								}
							}

							if( !empty($aItemAddress['addressAddressDescription']) ) {
								$sCollectInfo .= '
									<p><strong>' . _( 'Vägbeskrivning' ) . '</strong>
									<br>' . $aItemAddress['addressAddressDescription'] . '</p>';
							}

							if( !empty($aItemAddress['addressCollectInfo']) ) {
								$sCollectInfo .= '
									<br>' . $aItemAddress['addressCollectInfo'];
							}

							if( $aItemAddress['addressFreightHelp'] == 'yes' ) {
								$sCollectInfo .= '
									<p><strong>Frakthjälp finns mot betalning.</strong> Frakthjälp skall beställas senast 2 dagar innan ordinarie utlämningsdag.</p>';
								$bFreightHelp = true;
							} else {
								$sCollectInfo .= '
									<p><strong>Frakthjälp finns inte.</strong></p>';
								$bFreightHelp = true;
							}

							switch( $aItemAddress['addressForkliftHelp'] ) {
								case 'yes':
									$sCollectInfo .= '
										<p><strong>Lasthjälp med truck finns.</strong> Kontakta auktionsplatsansvarig senast 2 dagar innan ordinarie utlämningsdag.</p>';
									break;

								case 'no':
									$sCollectInfo .= '
										<p><strong>Lasthjälp med truck finns inte.</strong></p>';
									break;

									case 'custom':
										$sCollectInfo .= '
											<p><strong>Lasthjälp med truck?</strong> ' . strip_tags($aItemAddress['addressLoadingInfo']) . '</p>';
										break;

									default:
										// Do nothing
							}

							$sCollectInfo .= '</p>';
						}
					}
				}

				$sAuctionPickupInformation = '';
				if( !empty($sCollectInfo) ) {
					$sAuctionPickupInformation = '
						<h2>Avh&auml;mtning:</h2>
						<p>' . $sCollectInfo . '</p>
						<p>Medtag erforderliga verktyg för eventuell demontering av vunnen vara, samt bärhjälp, palltruck, säckkärra, samt pallar och packmaterial, om det så skulle behövas, finns ej på plats. Demontering av auktionsobjekt skall ombesörjas av köparen. Detta skall ske fackmannamässigt.<br /></p>
						<p>Varor som ej har hämtats på utsatt tid kommer att debiteras en dygnshyra om 400: - exkl moms per objekt och dygn samt eventuella merkostnader för godset.</p>';
				}

				$sContent = $GLOBALS['invoice'][ $sInvoiceTemplate ]['bodyHtml'];

				$aAuctionData = current( $oAuctionEngine->readAuction(array(
					'fields' => array(
						'auctionLastPayDate',
						'auctionContactDescription'
					),
					'auctionId' => $aInvoiceData['invoiceAuctionId'],
					'aucitonStatus' => '*',
					'partStatus' => '*'
				)) );

				// Add information
				$sContent = str_replace( '{invoiceNo}', $aInvoiceData['invoiceNo'], $sContent );
				$sContent = str_replace( '{invoiceInformation}', $aInvoiceData['invoiceInformation'], $sContent );
				$sContent = str_replace( '{auctionPaymentDate}', $aAuctionData['auctionLastPayDate'], $sContent );
				$sContent = str_replace( '{auctionPickupInformation}', $sAuctionPickupInformation, $sContent );
				$sContent = str_replace( '{auctionPickupContact}', $aAuctionData['auctionContactDescription'], $sContent );

				//Add additional message if supplied
				$sAdditionalMessage = ( !empty($sAdditionalMessage) ? '<strong>' . _( 'Meddelande till dig' ) . ':</strong><p>' . $sAdditionalMessage . '</p><br /><hr />' : '' );
				$sContent = str_replace( '{invoiceAdditionalMessage}', $sAdditionalMessage, $sContent );
			}

			if( $bAttachments === true ) {
				$sContent = str_replace( '{invoiceAttachedTitle}', _('Fakturan finns bifogad men också på nätet.'), $sContent );
			} else {
				$sContent = str_replace( '{invoiceAttachedTitle}', _('Fakturan finns under dina fakturor på nätet.'), $sContent );
			}

			// Send
			require_once PATH_MODULE . '/mailHandler/models/clMailHandler.php';
			$oMailHandler = new clMailHandler();
			$aSendData = array(
				'from' => 'Toveks auktioner <' . $sSender . '>',
				'to' => $sReceiver,
				'title' => _( 'Your invoice from Toveks Auktioner' ),
				'content' => $sContent
			);
			if( $bAttachments === true ) {
				$aSendData += array(
					'attachments' => array(
						0 => array(
							'name' => $sFileName,
							'content' => $sInvoicePdf
						)
					)
				);
			}
			$bSent = $oMailHandler->send( $aSendData );
			unset( $oMailHandler );
			return $bSent;
		}

		return false;
	}

	/* * *
	 * Send data to Fortnox - with validity check
	 * * */
	public function sendInvoicesToFortnox( $aInvoiceData ) {
		$bFortnoxValid = $this->checkFortnoxValidity( $aInvoiceData );
		if( $bFortnoxValid ) {
			// Arrange data to fit Fortnox structure

			// Send to Fortnox

			// Error reporting
		}

		return false;
	}

	/* * *
	 * Set OCR number
	 * * */
	public function setOcrNumber( $iInvoiceId ) {
		if( !empty($iInvoiceId) ) {
			$iInvoiceNo = current( current($this->read('invoiceNo', $iInvoiceId)) );
			$iOcrNumber = createOcrNumber( $iInvoiceNo );

			parent::update( $iInvoiceId, array(
				'invoiceOcrNumber' => $iOcrNumber
			) );

			return $iOcrNumber;
		}

		return false;
	}

	/* * *
	 * Generate Invoice HTML for gerneric purpose. For presentation on page, PDF and mail
	 * * */
	public function generateInvoiceHtml( $iInvoiceId, $bPdf = false, $aExtra = array() ) {
		clFactory::loadClassFile( 'clOutputHtmlTable' );

		$sOutput = '';

		$aDataDict = $this->oDao->getDataDict();

		// Get the information for the footer
		$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
		$sReminderFee = (($aReminderFee = $oInfoContent->read('contentTextId', 42)) ? $aReminderFee[0]['contentTextId'] : null );

		// Get invoice data
		$aInvoiceData = current( $this->read(null, $iInvoiceId) );

		// Define variables to use for summation
		$fTotalSum = 0;
		$fVatSum = 0;
		$aVatSum = array();
		$bAnyLineGotVat = false;
		$bInvoiceHasVat = ( $aInvoiceData['invoiceVat'] == 'yes' );

		$aPriceFormat = array(
			'additional' => array(
				'decimals' => 2,
			)
		);
		$aTotalPriceFormat = array(
			'additional' => array(
				'format' => array(
					'money' => true
				),
				'currencyFormat' => 'i'
			)
		);

		if( !empty($aInvoiceData) ) {

			// Fetch user data (main purpose it to determine if the invoice should have VAT)
			$oUserManager = clRegistry::get( 'clUserManager' );
			$aUserData = current( $oUserManager->read(array(
				'userCustomerNo',
				'infoVat'
			), $aInvoiceData['invoiceUserId']) );
			$bInvoiceHasVat = ( ($aUserData['infoVat'] == 'yes') ? true : false );

			// Fetch the invoice rows
			$oInvoiceLine = clRegistry::get( 'clInvoiceLine', PATH_MODULE . '/invoice/models' );
			$aInvoiceLineData = $oInvoiceLine->readByInvoice( $iInvoiceId );
			// Make an array to sort by
			$aSortLineData = array();
			foreach( $aInvoiceLineData as $key => $aInvoiceLine ) {
				$aSortLineData[$key] = $aInvoiceLine['invoiceLineTitle'] . ' - ' . $aInvoiceLine['invoiceLineId'];
			}
			// Sort the lines in natural order
			natcasesort( $aSortLineData );
			$aTempLineData = array();
			foreach( $aSortLineData as $key => $value ) {
				$aTempLineData[] = $aInvoiceLineData[$key];
			}
			$aInvoiceLineData = $aTempLineData;

			// Fetch item data based on lines
			if( !empty($aInvoiceLineData) ) {
				$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
				$aItems = valueToKey( 'itemId', $oAuctionEngine->read( 'AuctionItem', array(
					'itemId',
					'itemSortNo',
					'itemSubmissionId',
					'itemVehicleDataId'
				), arrayToSingle($aInvoiceLineData, null, 'invoiceLineItemId') ) );

				if( !empty($aItems) ) {
					$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
					$oBackEnd->setSource( 'entVehicleData', 'vehicleDataId' );
					$aVechicleData = arrayToSingle( $oBackEnd->read( array(
						'vehicleDataId',
						'vehicleLicencePlate'
					), arrayToSingle($aItems, null, 'itemVehicleDataId') ), 'vehicleDataId', 'vehicleLicencePlate' );
					$oBackEnd->oDao->sCriterias = null;

					foreach( $aItems as &$aItem ) {
						if( !empty($aItem['itemVehicleDataId']) && !empty($aVechicleData[ $aItem['itemVehicleDataId'] ]) ) {
							$aItem['vehicleLicencePlate'] = $aVechicleData[ $aItem['itemVehicleDataId'] ];
						}
					}
				}
			}

			// ------------------------------------------------------------------
			// The info with references
			$sInvoiceDetails = '
				<div class="content">
					<div class="invoiceDetails">';

			// ------------------------------------------------------------------
			// Table with Our reference and other legal info
			$aTableDataDict = array(
				'invoiceLegal' => array(
					'ourRef' => array( 'title' => _('Our reference') ),
					'terms' => array( 'title' => '' )
				)
			);
			$oHtmlTableLegal = new clOutputHtmlTable( $aTableDataDict );
			$row = array(
				'ourRef' => $aInvoiceData['invoiceInformation'],
				'terms' => $sReminderFee
			);
			$oHtmlTableLegal->addBodyEntry( $row );

			$sInvoiceDetails .= $oHtmlTableLegal->render( [
					'class' => 'dataTable invoiceDataTable'
			] );
			// ------------------------------------------------------------------

			// ------------------------------------------------------------------
			// Table with invoice rows
			if( !empty($aInvoiceData['invoiceAuctionId']) ) {
				$aTableDataDict = array(
					'invoiceLines' => array(
						'lineTitle' => array( 'title' => _('Title') ),
						'lineSubmitterId' => array( 'title' => _('Inl.') ),
						'lineFee' => array( 'title' => _('Auction fee') ),
						'lineAmount' => array( 'title' => _('Amount') ),
						'lineVat' => array( 'title' => _('VAT') ),
					)
				);
			} else {
				$aTableDataDict = array(
					'invoiceLines' => array(
						'lineTitle' => array( 'title' => _('Title') ),
						'lineFee' => array( 'title' => _('Auction fee') ),
						'lineAmount' => array( 'title' => _('Amount') ),
						'lineVat' => array( 'title' => _('VAT') ),
					)
				);
			}
			if( !$bInvoiceHasVat ) unset( $aTableDataDict['invoiceLines']['lineVat'] );

			$oHtmlTableLines = new clOutputHtmlTable( $aTableDataDict );
			foreach( $aInvoiceLineData as $entry  ) {
				if( empty($entry['invoiceLineQuantity']) ) {
					$entry['invoiceLineQuantity'] = 1;
				}
				// Price and VAT calculations
				$fPrice = $entry['invoiceLinePrice'];
				$fPriceSum = $entry['invoiceLinePrice'] * $entry['invoiceLineQuantity'];
				$iVat = $entry['invoiceLineVatValue'];
				$fVat = $fPriceSum * $iVat / 100;
				$fPriceWithVat = $fPriceSum + $fVat;

				// Add to total sums
				$fTotalSum += ( $bInvoiceHasVat ? $fPriceWithVat : $fPriceSum );
				$fVatSum += $fVat;
				if( $iVat != 0 ) $bAnyLineGotVat = true;

				if( isset($aVatSum[$iVat]) ) {
					$aVatSum[$iVat]['value'] += $fVat;
					$aVatSum[$iVat]['net'] += $fPriceSum;
					$aVatSum[$iVat]['gross'] += $fPriceWithVat;
				} else {
					$aVatSum[$iVat] = array(
						'value' => $fVat,
						'net' => $fPriceSum,
						'gross' => $fPriceWithVat
					);
				}

				// Add line fee to total sums
				$fFee = $entry['invoiceLineFee'];
				$fVat = $fFee * INVOICE_DEFAULT_VAT / 100;
				$fFeeWithVat = $fFee + $fVat;

				$fTotalSum += ( $bInvoiceHasVat ? $fFeeWithVat : $fFee );
				$fVatSum += $fVat;
				if( $fFee > 0 ) {

					if( isset($aVatSum[INVOICE_DEFAULT_VAT]) ) {
						$aVatSum[INVOICE_DEFAULT_VAT]['value'] += $fVat;
						$aVatSum[INVOICE_DEFAULT_VAT]['net'] += $fFee;
						$aVatSum[INVOICE_DEFAULT_VAT]['gross'] += $fFeeWithVat;
					} else {
						$aVatSum[INVOICE_DEFAULT_VAT] = array(
							'value' => $fVat,
							'net' => $fFee,
							'gross' => $fFeeWithVat
						);
					}
				}

				// Get submitter no
				$aSubmitterNo = '';
				if( ($iVat == 0) && !empty($aItems[$entry['invoiceLineItemId']]['itemSubmissionId']) ) {
					$oSubmission = clRegistry::get( 'clSubmission', PATH_MODULE . '/submission/models' );
					$oSubmitter = clRegistry::get( 'clSubmitter', PATH_MODULE . '/submitter/models' );

					$aSubmissionData = $oSubmission->read( 'submissionSubmitterId', $aItems[$entry['invoiceLineItemId']]['itemSubmissionId'] );
					if( !empty($aSubmissionData) ) {
						$aSubmitterNo = current( current($oSubmitter->read('submitterNo', current(current($aSubmissionData)))) );
					}
				}

				$row = array(
					'lineTitle' => $entry['invoiceLineTitle'] . ( !empty($aItems[$entry['invoiceLineItemId']]['vehicleLicencePlate']) ? ' (' .$aItems[$entry['invoiceLineItemId']]['vehicleLicencePlate'] . ')' : '' ),
					'lineSubmitterId' => $aSubmitterNo,
					'lineQty' => $entry['invoiceLineQuantity'],
					'lineFee' => calculatePrice( $fFee, $aPriceFormat ),
					'linePrice' => calculatePrice( $fPrice, $aPriceFormat ),
					'lineAmount' => calculatePrice( $fPriceSum, $aPriceFormat ),
					'lineVat' => $iVat . '%',
				);
				$oHtmlTableLines->addBodyEntry( $row );
			}

			//------------------------------------------------------------
			// The calculations have changed
			// The fee shall always have vat
			// This is a simple fix. More development needed to handle old invoices
			$bAnyLineGotVat = true;
			// -----------------------------------------------------------


			// Invoice fee line (Inserted before VAT if any line got VAT)
			if( /*($aInvoiceData['invoiceFee'] > 0) &&*/ $bAnyLineGotVat ) {
				$fFee = $aInvoiceData['invoiceFee'];
				$fVat = $fFee * INVOICE_DEFAULT_VAT / 100;
				$fFeeWithVat = $fFee + $fVat;

				$fTotalSum += ( $bInvoiceHasVat ? $fFeeWithVat : $fFee );
				$fVatSum += $fVat;

				if( isset($aVatSum[INVOICE_DEFAULT_VAT]) ) {
					$aVatSum[INVOICE_DEFAULT_VAT]['value'] += $fVat;
					$aVatSum[INVOICE_DEFAULT_VAT]['net'] += $fFee;
					$aVatSum[INVOICE_DEFAULT_VAT]['gross'] += $fFeeWithVat;
				} else {
					$aVatSum[INVOICE_DEFAULT_VAT] = array(
						'value' => $fVat,
						'net' => $fFee,
						'gross' => $fFeeWithVat
					);
				}

				$row = array(
					'lineTitle' => _( 'Invoice fee' ),
					'lineSubmitterId' => '',
					'lineQty' => '',
					'lineFee' => '',
					'linePrice' => '',
					'lineAmount' => calculatePrice( $fFee, $aPriceFormat ),
					'lineVat' => INVOICE_DEFAULT_VAT . '%',
				);
				$oHtmlTableLines->addFooterEntry( $row );
			}

			// Total VAT line
			if( $bInvoiceHasVat ) {
				$row = array(
					'lineTitle' => _( 'VAT' ),
					'lineSubmitterId' => '',
					'lineQty' => '',
					'lineFee' => '',
					'linePrice' => '',
					'lineAmount' => calculatePrice( $fVatSum, $aPriceFormat ),
					'lineVat' => '',
				);
				$oHtmlTableLines->addFooterEntry( $row );
			}

			// Invoice fee line (Inserted after VAT if no line got VAT)
			if( ($aInvoiceData['invoiceFee'] != 0) && !$bAnyLineGotVat ) {
				$fFee = $aInvoiceData['invoiceFee'];

				$fTotalSum += $fFee;

				$row = array(
					'lineTitle' => _( 'Invoice fee' ),
					'lineSubmitterId' => '',
					'lineQty' => '',
					'lineFee' => '',
					'linePrice' => '',
					'lineAmount' => calculatePrice( $fFee, $aPriceFormat ),
					'lineVat' => '',
				);
				$oHtmlTableLines->addFooterEntry( $row );
			}

			// Total roundup
			$fTotalSumRound = round( $fTotalSum );
			if( $fTotalSum != $fTotalSumRound  ) {
				$fRoundValue = $fTotalSumRound - $fTotalSum;
				$fTotalSum = $fTotalSumRound;

				$row = array(
					'lineTitle' => _( 'Rounding' ),
					'lineSubmitterId' => '',
					'lineQty' => '',
					'lineFee' => '',
					'linePrice' => '',
					'lineAmount' => calculatePrice( $fRoundValue, $aPriceFormat ),
					'lineVat' => '',
				);
				$oHtmlTableLines->addFooterEntry( $row );
			}


			$sInvoiceLines = $oHtmlTableLines->render( [
				'class' => 'dataTable invoiceLines invoiceDataTable'
			] );
			// ------------------------------------------------------------------

			$sOutput .= '
						</div>
					</div>';


			$sInvoiceTotalSummary = '
				<div class="contentSummary">';

			// ------------------------------------------------------------------
			// VAT table
			if( $bInvoiceHasVat ) {
				$aTableDataDict = array(
					'invoiceVat' => array(
						'vatPercent' => array( 'title' => _('VAT') . '%' ),
						'vatValue' => array( 'title' => _('VAT') ),
						'net' => array( 'title' => _('Net') ),
						'gross' => array( 'title' => _('Gross') )
					)
				);
				$oHtmlTableVat = new clOutputHtmlTable( $aTableDataDict );


				krsort( $aVatSum );
				foreach( $aVatSum as $iVatPercent => $aVatSumData ) {
					$row = array(
						'vatPercent' => $iVatPercent,
						'vatValue' => calculatePrice( round($aVatSumData['value']), $aPriceFormat ),
						'net' => calculatePrice( $aVatSumData['net'], $aPriceFormat ),
						'gross' => calculatePrice( round($aVatSumData['gross']), $aPriceFormat )
					);
					$oHtmlTableVat->addBodyEntry( $row );
				}

				$sInvoiceTotalSummary .= '
					<div class="invoiceVatTable">
						' . $oHtmlTableVat->render( [
								'class' => 'dataTable invoiceDataTable'
						] ) . '
						<p class="info">' . _( 'Invoice and auction fee are always subject to 25% VAT' ) . '</p>
					</div>';
			}
			// ------------------------------------------------------------------

			// ------------------------------------------------------------------
			// To pay or receive
			$sInvoiceTotalSummary .= '
					<div class="toPayOrReceive">
						<span class="label">' . ( ($aInvoiceData['invoiceType'] == 'credit') ? _('To receive') : _('To pay') ) . ': </span>
						' . calculatePrice( $fTotalSum, $aTotalPriceFormat ) . '
					</div>';
			// ------------------------------------------------------------------

			// ------------------------------------------------------------------
			// Payment info and terms

			if( !empty($aInvoiceData['invoiceDueDate']) && ($aInvoiceData['invoiceDueDate'] != '0000-00-00') ) {
				$sDueDate = $aInvoiceData['invoiceDueDate'];
			} else {
			$iInvoiceDateTime = strtotime(  $aInvoiceData['invoiceDate'] );
			$iDueTime = $iInvoiceDateTime + ( $aInvoiceData['invoiceCreditDays'] * 86400 );
			$sDueDate = date( 'Y-m-d', $iDueTime );
			}
			
			$sDueDateText = ( ($aInvoiceData['invoiceType'] != 'credit') ? sprintf( _('Betalning oss tillhanda: %s'), $sDueDate ) : '' );
			$sDueDateInterestText = ( ($aInvoiceData['invoiceLateInterest'] != 0) ? sprintf( _('Efter förfallodagen debiteras %s &#037; i dröjsmålsränta'), $aInvoiceData['invoiceLateInterest'] ) : '' );
			$iActualCreditDays = ( !empty($aInvoiceData['invoiceAuctionId']) ? '0' : $aInvoiceData['invoiceCreditDays'] );
			$sCreditDaysText = ( ctype_digit($iActualCreditDays) ? $iActualCreditDays . ' ' . $aDataDict['entInvoice']['invoiceCreditDays']['title'] : '' );
			$iOcrNumber = ( !empty($aInvoiceData['invoiceOcrNumber']) ? $aInvoiceData['invoiceOcrNumber'] : (!empty($aInvoiceData['invoiceNo']) ? createOcrNumber($aInvoiceData['invoiceNo']) : '') );

			$sInvoiceTotalSummary .= '
					<div class="invoicePaymentTerms">
						<div class="lateInterest">' . $sDueDateInterestText . '</div>
						<div class="creditDays">' . $sCreditDaysText . '</div>
						<div class="dueDate">' . $sDueDateText . '</div>
						<div class="paymentTarget">' . _( 'Pay to' ) . ': <strong>' . INVOICE_PAYMENT_TARGET . '</strong></div>
						<div class="ocr">' . _( 'OCR' ) . ': <strong>' . $iOcrNumber . '</strong></div>
					</div>';
			// ------------------------------------------------------------------

			$sInvoiceTotalSummary .= '
				</div>';



			// ------------------------------------------------------------------
			// Assemble all the pages

			$sOutput = '
				<section class="invoice' . ( $bPdf ? '' : ' preview' ) . '">
					' . $sInvoiceDetails . '
					' . $sInvoiceLines . '
					' . $sInvoiceTotalSummary . '
				</section>';

			// ------------------------------------------------------------------
		}

		return $sOutput;
	}

	/* * *
	 * Read the invoices connected to auction
	 * * */
	public function readByAuction( $iAuctionId, $aFields = array() ) {
		$aParams = array(
			'invoiceAuctionId' => $iAuctionId,
			'fields' => $aFields
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Read the invoices connected to auction part
	 * * */
	public function readByAuctionPart( $iAuctionPartId, $aFields = array() ) {
		$aParams = array(
			'invoiceAuctionPartId' => $iAuctionPartId,
			'fields' => $aFields
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Read the invoices connected to auction part
	 * * */
	public function readByAuctionPartAndUser( $iAuctionPartId, $iUserId, $aFields = array() ) {
		$aParams = array(
			'invoiceAuctionPartId' => $iAuctionPartId,
			'invoiceUserId' => $iUserId,
			'fields' => $aFields
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Read the invoices connected to an freight request
	 * * */
	public function readByFreightRequest( $iRequestId, $aFields = array() ) {
		$aParams = array(
			'invoiceFreightRequestId' => $iRequestId,
			'fields' => $aFields
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Read the invoices connected to an parent invoice
	 * Type is a possible filter (copy|credit)
	 * * */
	public function readByParent( $iParentId, $sParentType = null, $aFields = array() ) {
		$aParams = array(
			'invoiceParentInvoiceId' => $iParentId,
			'invoiceParentType' => $sParentType,
			'fields' => $aFields
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Read the invoices connected to an freight request
	 * * */
	public function readByOcrNumber( $iOcrNumber, $aFields = array() ) {
		$aParams = array(
			'invoiceOcrNumber' => $iOcrNumber,
			'fields' => $aFields
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Read the users invoices
	 * $aData carries all the filtering options (paid, unpaid ...)
	 * * */
	public function readByUser( $iUserId, $aData = array() ) {
		$aParams = array(
			'invoiceUserId' => $iUserId,
			'fields' => $aData
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Read the invoices connected to an invvoice order
	 * $aData carries all the filtering options (paid, unpaid ...)
	 * * */
	public function readByInvoiceOrder( $iInvoiceOrderId, $aData = array() ) {
		$aParams = array(
			'invoiceOrderId' => $iInvoiceOrderId,
			'fields' => $aData
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Read the invoices by invoice no
	 * * */
	public function readByInvoiceNo( $iInvoiceNo, $aFields = array() ) {
		$aParams = array(
			'invoiceNo' => $iInvoiceNo,
			'fields' => $aFields
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Calculates the total amount of the invoice
	 * There are all kinds of calculations here to use by accounting functions
	 * * */
	public function getCalculatedSums( $iInvoiceId ) {

		// Get invoice data
		$aInvoiceData = current( $this->read(null, $iInvoiceId) );

		// Define variables to use for summation
		$fTotalSum = 0;
		$fTotalSumPreVat = 0;
		$fVatSum = 0;
		$aVatSum = array();
		$bAnyLineGotVat = false;
		$bAnyLineGotFee = false;
		//$bInvoiceHasVat = ( $aInvoiceData['invoiceVat'] == 'yes' ); // Not needed anymore. VAT is checked on the lines

		// Populate an basic array to summarize accounting values in
		$aAccountingSum = array();
		foreach( $GLOBALS['invoiceAccccountingCategories'] as $sKey => $aCategories ) {
			$aAccountingSum[$sKey] = $aCategories + $GLOBALS['invoiceAccccountingSums'];
		}

		if( !empty($aInvoiceData) ) {

			// Fetch the invoice rows
			$oInvoiceLine = clRegistry::get( 'clInvoiceLine', PATH_MODULE . '/invoice/models' );
			$aInvoiceLineData = $oInvoiceLine->readByInvoice( $iInvoiceId );

			// ------------------------------------------------------------------
			// Table with invoice rows

			foreach( $aInvoiceLineData as $entry  ) {
				if( empty($entry['invoiceLineQuantity']) ) {
					$entry['invoiceLineQuantity'] = 1;
				}

				// Price and VAT calculations
				$fPrice = $entry['invoiceLinePrice'];
				$fPriceSum = $entry['invoiceLinePrice'] * $entry['invoiceLineQuantity'];
				$iVat = $entry['invoiceLineVatValue'];
				$fVat = $fPriceSum * $iVat / 100;
				$fPriceWithVat = $fPriceSum + $fVat;

				// Add to total sums
				$fTotalSum += $fPriceWithVat;
				$fTotalSumPreVat += $fPriceSum;
				$fVatSum += $fVat;
				if( $iVat != 0 ) $bAnyLineGotVat = true;

				// Add line fee to total sums
				$fFee = $entry['invoiceLineFee'];
				$fFeeVat = $fFee * INVOICE_DEFAULT_VAT / 100;
				$fFeeWithVat = $fFee + $fFeeVat;

				$fTotalSum += $fFeeWithVat;
				$fTotalSumPreVat += $fFee;
				$fVatSum += $fFeeVat;
				if( $fFee > 0 ) $bAnyLineGotFee = true;

				// Accounting values
				switch( $iVat ) {
					case 0:
						if( $fFee > 0 ) {
							if( !empty($entry['invoiceLineAccountingCode']) ) {
								// The line has an specific account code
								$aAccountingSum['INVOICE_ITEMFEE_NOVAT']['accountingCodes'][ $entry['invoiceLineAccountingCode'] ] = $entry['invoiceLineAccountingCode'];
								if( empty($aAccountingSum['INVOICE_ITEMFEE_NOVAT'][ $entry['invoiceLineAccountingCode'] ]) ) {
									$aAccountingSum['INVOICE_ITEMFEE_NOVAT'][ $entry['invoiceLineAccountingCode'] ] = $fPriceSum;
								} else {
									$aAccountingSum['INVOICE_ITEMFEE_NOVAT'][ $entry['invoiceLineAccountingCode'] ] += $fPriceSum;
								}
							} else {
								$aAccountingSum['INVOICE_ITEMFEE_NOVAT']['net'] += $fPriceSum;
							}
							$aAccountingSum['INVOICE_ITEMFEE_NOVAT']['itemFee'] += $fFee;
							$aAccountingSum['INVOICE_ITEMFEE_NOVAT']['vat'] += $fFeeVat;
						} else {
							if( !empty($entry['invoiceLineAccountingCode']) ) {
								// The line has an specific account code
								$aAccountingSum['INVOICE_NOVAT']['accountingCodes'][ $entry['invoiceLineAccountingCode'] ] = $entry['invoiceLineAccountingCode'];
								if( empty($aAccountingSum['INVOICE_NOVAT'][ $entry['invoiceLineAccountingCode'] ]) ) {
									$aAccountingSum['INVOICE_NOVAT'][ $entry['invoiceLineAccountingCode'] ] = $fPriceSum;
								} else {
									$aAccountingSum['INVOICE_NOVAT'][ $entry['invoiceLineAccountingCode'] ] += $fPriceSum;
								}
							} else {
								$aAccountingSum['INVOICE_NOVAT']['net'] += $fPriceSum;
							}
						}
						break;

					case 12:
						if( !empty($entry['invoiceLineAccountingCode']) ) {
							// The line has an specific account code
							$aAccountingSum['INVOICE_VAT_12%']['accountingCodes'][ $entry['invoiceLineAccountingCode'] ] = $entry['invoiceLineAccountingCode'];
							if( empty($aAccountingSum['INVOICE_VAT_12%'][ $entry['invoiceLineAccountingCode'] ]) ) {
								$aAccountingSum['INVOICE_VAT_12%'][ $entry['invoiceLineAccountingCode'] ] = $fPriceSum;
							} else {
								$aAccountingSum['INVOICE_VAT_12%'][ $entry['invoiceLineAccountingCode'] ] += $fPriceSum;
							}
						} else {
							$aAccountingSum['INVOICE_VAT_12%']['net'] += $fPriceSum;
						}
						$aAccountingSum['INVOICE_VAT_12%']['vat'] += $fVat;

						if( $fFee > 0 ) {
							$aAccountingSum['INVOICE_ITEMFEE_VAT']['itemFee'] += $fFee;
							$aAccountingSum['INVOICE_ITEMFEE_VAT']['vat'] += $fFeeVat;
						}
						break;

					case 25:
						if( $fFee > 0 ) {
							if( !empty($entry['invoiceLineAccountingCode']) ) {
								// The line has an specific account code
								$aAccountingSum['INVOICE_ITEMFEE_VAT']['accountingCodes'][ $entry['invoiceLineAccountingCode'] ] = $entry['invoiceLineAccountingCode'];
								if( empty($aAccountingSum['INVOICE_ITEMFEE_VAT'][ $entry['invoiceLineAccountingCode'] ]) ) {
									$aAccountingSum['INVOICE_ITEMFEE_VAT'][ $entry['invoiceLineAccountingCode'] ] = $fPriceSum;
								} else {
									$aAccountingSum['INVOICE_ITEMFEE_VAT'][ $entry['invoiceLineAccountingCode'] ] += $fPriceSum;
								}
							} else {
								$aAccountingSum['INVOICE_ITEMFEE_VAT']['net'] += $fPriceSum;
							}
							$aAccountingSum['INVOICE_ITEMFEE_VAT']['itemFee'] += $fFee;
							$aAccountingSum['INVOICE_ITEMFEE_VAT']['vat'] += $fVat + $fFeeVat;
						} else {
							if( !empty($entry['invoiceLineAccountingCode']) ) {
								// The line has an specific account code
								$aAccountingSum['INVOICE_VAT']['accountingCodes'][ $entry['invoiceLineAccountingCode'] ] = $entry['invoiceLineAccountingCode'];
								if( empty($aAccountingSum['INVOICE_VAT'][ $entry['invoiceLineAccountingCode'] ]) ) {
									$aAccountingSum['INVOICE_VAT'][ $entry['invoiceLineAccountingCode'] ] = $fPriceSum;
								} else {
									$aAccountingSum['INVOICE_VAT'][ $entry['invoiceLineAccountingCode'] ] += $fPriceSum;
								}
							} else {
								$aAccountingSum['INVOICE_VAT']['net'] += $fPriceSum;
							}
							$aAccountingSum['INVOICE_VAT']['vat'] += $fVat + $fFeeVat;
						}
					default:

				}
			}

			//------------------------------------------------------------
			// The calculations have changed
			// The fee shall always have vat
			// This is a simple fix. More development needed to handle old invoices
			$bAnyLineGotVat = true;
			// -----------------------------------------------------------

			// Invoice fee line (Inserted before VAT if any line got VAT)
			if( /*($aInvoiceData['invoiceFee'] > 0) &&*/ $bAnyLineGotVat ) {
				$fFee = $aInvoiceData['invoiceFee'];
				$fVat = $fFee * INVOICE_DEFAULT_VAT / 100;
				$fFeeWithVat = $fFee + $fVat;

				$fTotalSum += $fFeeWithVat;
				$fTotalSumPreVat += $fFee;
				$fVatSum += $fVat;

				// Accounting values
				if( $bAnyLineGotFee ) {
					$aAccountingSum['INVOICE_ITEMFEE_VAT']['fee'] += $fFee;
					$aAccountingSum['INVOICE_ITEMFEE_VAT']['vat'] += $fVat;
				} else {
					$aAccountingSum['INVOICE_VAT']['fee'] += $fFee;
					$aAccountingSum['INVOICE_VAT']['vat'] += $fVat;
				}
			}

			// Invoice fee line (Inserted after VAT if no line got VAT)
			if( /*($aInvoiceData['invoiceFee'] > 0) &&*/ !$bAnyLineGotVat ) {
				$fFee = $aInvoiceData['invoiceFee'];

				$fTotalSum += $fFee;
				$fTotalSumPreVat += $fFee;

				// Accounting values
				if( $bAnyLineGotFee ) {
					$aAccountingSum['INVOICE_ITEMFEE_NOVAT']['fee'] += $fFee;
				} else {
					$aAccountingSum['INVOICE_NOVAT']['fee'] += $fFee;
				}
			}

			// Total roundup
			$fTotalSumRound = round( $fTotalSum );
			if( $fTotalSum != $fTotalSumRound  ) {
				$fRoundValue = $fTotalSumRound - $fTotalSum;
				$fTotalSum = $fTotalSumRound;

				// Accounting values
				if( $bAnyLineGotVat ) {
					if( $bAnyLineGotFee ) {
						$aAccountingSum['INVOICE_ITEMFEE_VAT']['round'] += $fRoundValue;
					} else {
						$aAccountingSum['INVOICE_VAT']['round'] += $fRoundValue;
					}
				} else {
					if( $bAnyLineGotFee ) {
						$aAccountingSum['INVOICE_ITEMFEE_NOVAT']['round'] += $fRoundValue;
					} else {
						$aAccountingSum['INVOICE_NOVAT']['round'] += $fRoundValue;
					}
				}
			}
			// Roundup before VAT
			$fTotalSumPreVatRound = round( $fTotalSumPreVat );
			if( $fTotalSumPreVat != $fTotalSumPreVatRound  ) {
				$fRoundValue = $fTotalSumPreVatRound - $fTotalSumPreVat;
				$fTotalSumPreVat = $fTotalSumPreVatRound;
			}
			// ------------------------------------------------------------------
		}

		$aData = array(
			'invoiceTotalAmount' => ( ($aInvoiceData['invoiceType'] == 'credit') ? -$fTotalSum : $fTotalSum ),
			'invoiceTotalPreVat' => ( ($aInvoiceData['invoiceType'] == 'credit') ? -$fTotalSumPreVat : $fTotalSumPreVat ),
			'invoiceTotalVat' => ( ($aInvoiceData['invoiceType'] == 'credit') ? -$fVatSum : $fVatSum )
		) + array( 'accountingSum' => $aAccountingSum );

		return $aData;
	}

	/* * *
	 * Calculates the total amount of the invoice and saves the result in the database
	 * * */
	public function setTotalAmount( $iInvoiceId ) {

		$aData = $this->getCalculatedSums( $iInvoiceId );
		$this->oDao->updateDataByPrimary( $iInvoiceId, $aData );

		// Save calculated sums for accounting purposes
		$oAccountingCalculations = clRegistry::get( 'clAccountingPreCalculation', PATH_MODULE . '/accounting/models' );
		if( $aData['invoiceTotalAmount'] < 0 ) {
			$aAccountingData = array(
				'1510' => -$aData['invoiceTotalAmount']
			);
			$sAccountingType = 'credit';
		} else  {
			$aAccountingData = array(
				'1510' => $aData['invoiceTotalAmount']
			);
			$sAccountingType = 'debit';
		}
		foreach( $aData['accountingSum'] as $sType => $aTypeData ) {
			foreach( $aTypeData['accountingCodes'] as $sSumKey => $iAccountingCode ) {
				if( !empty($aTypeData[$sSumKey]) ) {
					if( empty($aAccountingData[ $sType ][ $iAccountingCode ]) ) {
						$aAccountingData[ $sType ][ $iAccountingCode ] = $aTypeData[$sSumKey];
					} else {
						$aAccountingData[ $sType ][ $iAccountingCode ] += $aTypeData[$sSumKey];
					}
				}
			}
		}
		$oAccountingCalculations->createGroup( 'invoice', $iInvoiceId, $sAccountingType, $aAccountingData );

		return $aData['invoiceTotalAmount'];
	}

	/**
	 * Set the collected status
	 */
	public function setCollected( $iInvoiceId, $sCollectedStatus = 'yes' ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$aData = array( 'invoiceCollected' => $sCollectedStatus );

		return $this->oDao->updateDataByPrimary( $iInvoiceId, $aData );
	}

}
