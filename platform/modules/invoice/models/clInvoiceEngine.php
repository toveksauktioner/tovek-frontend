<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 01/04/2014 by Renfors
 * Description:
 * A collective class for handling user invoices.
 * It has been copied and modified from clAuctionEngine
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */

require_once PATH_MODULE . '/invoice/config/cfInvoice.php';

class clInvoiceEngine extends clInvoiceEngineBase {

	public $sModuleName;

	protected $sEngineMode;

	protected $oInvoiceDebug;
	protected $oInvoiceMaintenance;

	/**
	 * Variable containers for Invoice module parts
	 */
	protected $oInvoice;
	protected $oInvoiceLine;
	protected $oInvoiceQueue;
	protected $oInvoiceQueueLine;
	protected $oInvoiceOrder;
	protected $oInvoicePaymentLog;

	public function __construct() {
		$this->sModuleName = 'InvoiceEngine';

		/**
		 * This module´s engine file can be set to different modes,
		 * to behave in different ways.
		 * [ 'normal', 'debug', 'maintenance' ]
		 */
		$this->sEngineMode = INVOICE_DEFAULT_MODE;

		$this->initBase();
	}

	/**
	 * Without extending any classes this function handles
	 * debug and maintenance function calls.
	 */
	public function __call( $sFunction, $aArguments ) {
		if( $this->sEngineMode == 'normal' && strpos($sFunction, '_in_') !== false ) {
			$aFunction = explode('_in_', $sFunction);
			$sMethod = $aFunction[0];
			$sClass = $aFunction[1];
			$this->{$sClass} = clRegistry::get( 'cl' . $sClass, PATH_MODULE . '/invoice/models' );
			if( method_exists($this->{$sClass}, $sMethod) ) {
				return call_user_func_array( array( $this->{$sClass}, $sMethod ), $aArguments );
			}
		} elseif( $this->sEngineMode == 'debug' ) {
			$this->oInvoiceDebug = clRegistry::get( 'clInvoiceDebug', PATH_MODULE . '/invoice/models' );
			if( method_exists($this->oInvoiceMaintenance, $sFunction) ) {
				return call_user_func_array( array( $this->oInvoiceMaintenance, $sFunction ), $aArguments );
			}
		} elseif( $this->sEngineMode == 'maintenance' ) {
			$this->oInvoiceMaintenance = clRegistry::get( 'clInvoiceMaintenance', PATH_MODULE . '/invoice/models' );
			if( method_exists($this->oInvoiceMaintenance, $sFunction) ) {
				return call_user_func_array( array( $this->oInvoiceMaintenance, $sFunction ), $aArguments );
			}
		} else {
			throw new Exception( 'Function "' . $sFunction . '" does not exist.' );
		}
	}

	/**
	 * My work here is done...
	 */
	public function __destruct() {
		if( $this->sEngineMode == 'debug' ) {
			$this->summeryResult();
		} elseif( $this->sEngineMode == 'maintenance' ) {
			$this->maintenanceCheck();
		} else {
			// Do probably nothing here...
		}
	}

	/**
	 * Switch engine mode
	 */
	public function setMode( $sMode ) {
		if( !in_array($sMode, $GLOBALS['invoiceEngineModes']) ) {
			return false;
		}
		$this->sEngineMode = $sMode;
		return 'Engine mode set to: ' . $sMode;
	}

	/**
	 * Read current engine mode
	 */
	public function currentMode() {
		return 'Engine mode: ' . $this->sEngineMode;
	}

	/**
	 * Get a dao
	 */
	public function getDao( $sType ) {
		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/invoice/models' );
		return $this->$sModule->oDao;
	}



	/* * *
	 * Create invoices from a finished auction
	 * Part id is mandatory (change 2014-11-25 by Renfors)
	 * * */
	public function createInvoicesFromAuction( $iAuctionId, $iAuctionPartId ) {
		$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

		// Get auction data
		$aAuctionData = $oAuctionEngine->readAuction( array(
			'fields' => '*',
			'auctionId' => $iAuctionId,
			'partId' => $iAuctionPartId,
			'partStatus' => array(
				'running',
				'ending'
			)
		) );
		$aPartData = array();
		foreach( $aAuctionData as $entry ) {
			$aPartData[$entry['partId']] = $entry;
		}

		$aAuctionPartId = (array) $iAuctionPartId;

		if( !empty($aPartData) ) {

			// Loop through parts and collect invoice data
			// Exclude the items won by recallers
			$aUserInvoiceData = array();
			foreach( $aAuctionPartId as $iPartId ) {
				$aPartItems = $oAuctionEngine->readByAuction_in_AuctionItem( $iAuctionId, $iPartId, array(
					'itemId',
					'itemWinningBidId',
					'itemRecalled'
				) );

				foreach( $aPartItems as $entry ) {
					if( !empty($entry['itemWinningBidId']) ) {
						$aWinningBid = current( $oAuctionEngine->read( 'AuctionBid', array(
							'bidValue',
							'bidUserId'
						), $entry['itemWinningBidId']) );

						if( $entry['itemRecalled'] != 'yes' ) {
							$aUserInvoiceData[$aWinningBid['bidUserId']][$entry['itemId']] = $aWinningBid['bidValue'];
						}
					}
				}
			}

			// Loop through invoice array and create invoices with lines
			if( !empty($aUserInvoiceData) ) {
				$oUserManager = clRegistry::get( 'clUserManager' );

				$iFirstInvoiceId = 0;
				$iLastInvoiceId = 0;
				foreach( $aUserInvoiceData as $iUserId => $aInvoiceItems ) {
					if( is_array($aInvoiceItems) ) {
						$aMainAuctionData = current( $aAuctionData );
						$aUserData = current( $oUserManager->read(array('*'), $iUserId) );
						$sAuctionInformation = '
							<p>' . mb_strtoupper( (($aPartData[$iAuctionPartId]['auctionType'] == 'net') ? _('Net auction') : _('Live auction')) ) . ' (' . $iAuctionId . ') ' . mb_strtoupper( ( !empty($aPartData[$iAuctionPartId]['partLocation']) ? $aPartData[$iAuctionPartId]['partLocation'] : $aPartData[$iAuctionPartId]['auctionLocation'] ) ) . ' ' . date( 'ymd', strtotime($aPartData[$iAuctionPartId]['partAuctionStart']) ) . '</p>';

						$sInvoiceDate = substr( $aPartData[$iAuctionPartId]['partAuctionStart'], 0, 10 );

						// Invoice credit days is calculated
						$iInvoiceCreditDays = (int)( (strtotime($aPartData[$iAuctionPartId]['auctionLastPayDate']) - strtotime($sInvoiceDate)) / 86400 );

						$aData = array(
							'invoiceType' 						=> 'invoice',
							'invoiceInformation'			=> $sAuctionInformation,
							'invoiceFirstname' 				=> $aUserData['infoFirstname'],
							'invoiceSurname' 					=> $aUserData['infoSurname'],
							'invoiceCompanyName' 			=> $aUserData['infoName'],
							'invoiceAddress' 					=> $aUserData['infoAddress'],
							'invoiceZipCode' 					=> $aUserData['infoZipCode'],
							'invoiceCity' 						=> $aUserData['infoCity'],
							'invoiceCountryCode' 			=> $aUserData['infoCountryCode'],
							'invoiceUserId' 					=> $iUserId,
							'invoiceAuctionId' 				=> $iAuctionId,
							'invoiceAuctionPartId' 		=> $iAuctionPartId,
							'invoiceFee'							=> INVOICE_DEFAULT_FEE,
							'invoiceLateInterest'			=> INVOICE_DEFAULT_LATE_INTEREST,
							'invoiceCreditDays'				=> $iInvoiceCreditDays, 															// INVOICE_DEFAULT_CREDIT_DAYS = 30
							'invoiceDate'							=> $sInvoiceDate
						);

						$iInvoiceId = $this->create( 'Invoice', $aData );
						$aErr = clErrorHandler::getValidationError( 'createInvoice' );

						if( empty($aErr) && $iInvoiceId ) {
							foreach( $aInvoiceItems as $iItemId => $iItemValue ) {
								$aItemData = current( $oAuctionEngine->read( 'AuctionItem', array(
									'itemTitle',
									'itemSortNo',
									'itemVatValue',
									'itemFeeType',
									'itemFeeValue'
								), $iItemId ) );

								if( !empty($aItemData) ) {
									switch( $aItemData['itemFeeType'] ) {
										case 'percent':
											$fItemFee = $iItemValue * $aItemData['itemFeeValue'] / 100;
											break;

										case 'sek':
											$fItemFee = $aItemData['itemFeeValue'];
											break;

										case 'none':
										default:
											$fItemFee = 0;
									}

									$aLineData = array(
										'invoiceLineTitle' 			=> _( 'Call' ) . $aItemData['itemSortNo'] . ': ' . $aItemData['itemTitle'],
										'invoiceLineQuantity'		=> 1,
										'invoiceLinePrice'			=> $iItemValue,
										'invoiceLineVatValue'		=> $aItemData['itemVatValue'],
										'invoiceLineFee'				=> $fItemFee,
										'invoiceLineInvoiceId' 	=> $iInvoiceId,
										'invoiceLineUserId' 		=> $iUserId,
										'invoiceLineAuctionId'	=> $iAuctionId,
										'invoiceLineItemId'			=> $iItemId
									);

									$iInvoiceLineId = $this->create( 'InvoiceLine', $aLineData );
									$aErr = clErrorHandler::getValidationError( 'createInvoiceLine' );

									if( empty($aErr) ) {
										$this->oInvoice->setTotalAmount( $iInvoiceId );
									}
								}
							}

							if( empty($aErr) ) {
								$this->oInvoice->send( $iInvoiceId, SITE_MAIL_FROM );
							}

							if( empty($iFirstInvoiceId) ) {
								$iFirstInvoiceId = $iInvoiceId;
							}
							$iLastInvoiceId = $iInvoiceId;
						}
					}
				}

				// Send report about created invoices
				if( !empty($iFirstInvoiceId) && !empty($iLastInvoiceId) ) {
					$oUserNotification = clRegistry::get( 'clUserNotification', PATH_MODULE . '/userNotification/models' );

					$iFirstInvoiceNo = current( current($this->read('Invoice', 'invoiceNo', $iFirstInvoiceId)) );
					$iLastInvoiceNo = current( current($this->read('Invoice', 'invoiceNo', $iLastInvoiceId)) );

					$oUserNotification->create( array(
						'notificationTitle' => _( 'Nya fakturor' ),
						'notificationMessage' => $iFirstInvoiceNo . ' - ' . $iLastInvoiceNo,
						'notificationUserId' => INVOICE_END_AUCTION_NOTIFICATION_TO_USER_ID
					) );
				}

				return true;
			} else {
				// Error report
				return false;
			}
		} else {
			// Error report - no auction part data (probably due to the auction part already have been ended)
			return false;
		}
	}

	/* * *
	 * Invoices can be queued and creater later on.
	 * This function creates a new invoice with lines from a queued one and returns the created invoice id.
	 * The queued invoice is then deleted
	 * * */
	public function createInvoiceFromInvoiceQueue( $iInvoiceQueueId ) {
		$aQueueData = current( $this->read("InvoiceQueue", '*', $iInvoiceQueueId) );

		if( !empty($aQueueData) ) {
			$aQueueLines = $this->readByInvoiceQueue_in_InvoiceQueueLine( $iInvoiceQueueId );

			if( !empty($aQueueLines) ) {
				unset( $aQueueData['invoiceQueueId'] );

				// Force invoice to the current date
				$aQueueData['invoiceDate'] = date( 'Y-m-d' );

				$iInvoiceId = $this->create( 'Invoice', $aQueueData );
				$aErr = clErrorHandler::getValidationError( 'createInvoice' );

				if( empty($aErr) ) {
					foreach( $aQueueLines as  $aQueueLineData ) {
						$iInvoiceQueueLineId = $aQueueLineData['invoiceQueueLineId'];
						unset( $aQueueLineData['invoiceQueueLineId'] );
						unset( $aQueueLineData['invoiceLineInvoiceQueueId'] );
						$aQueueLineData['invoiceLineInvoiceId'] = $iInvoiceId;

						$iInvoiceLineId = $this->create( 'InvoiceLine', $aQueueLineData );
						$aErr += clErrorHandler::getValidationError( 'createInvoiceLine' );

						if( empty($aErr) ) {
							$this->delete( 'InvoiceQueueLine', $iInvoiceQueueLineId );
						}
					}

					if( empty($aErr) ) {
						$this->delete( 'InvoiceQueue', $iInvoiceQueueId );
						$this->setTotalAmount_in_Invoice( $iInvoiceId );
						$this->send_in_Invoice( $iInvoiceId, SITE_MAIL_FROM, null, null, false );
						return $iInvoiceId;
					}
				}
			}
		}

		return false;
	}

	/* * *
	 * Create an Invoice order based on invoices. This is done before inititializing payment
	 * $aInvoice carries the invoice/invoices
	 * * */
	public function createInvoiceOrder( $mInvoice, $iPaymentType ) {
		$aErr = array();

		// User must be logged in (i.e. have userId set in session)
		if( !empty($_SESSION['userId']) || !ctype_digit($_SESSION['userId']) ) {
			$aInvoice = (array) $mInvoice;

			// Fetch invoice data from database
			$aInvoiceData = $this->read('Invoice', array(
				'invoiceId',
				'invoiceNo',
				'invoiceStatus',
				'invoiceType',
				'invoiceTotalAmount',
				'invoiceTotalVat',
				'invoiceLocked',
				'invoiceOrderId'
			), $aInvoice );

			// Sum the invoices
			$fSumAmount = $fSumVat = 0;
			foreach( $aInvoiceData as $entry ) {

				// All this invoice's payments
				$aInvoicePaymentData = arrayToSingle( $this->readByInvoice_in_InvoicePaymentLog($entry['invoiceId'], array('logAmount')), null, 'logAmount' ) ;
				$fSumRemaining = $entry['invoiceTotalAmount'] - array_sum( $aInvoicePaymentData );

				$fSumAmount += $fSumRemaining;
				$fSumVat += $entry['invoiceTotalVat'];

				// Check if invoice is already paid
				if( $entry['invoiceStatus'] == 'paid' ) {
					$aErr[] = sprintf( _('Invoice %s is already paid'), $entry['invoiceNo']);
				}

				// Check if a order for this invoice is already initiated and in intermediate state
				if( !empty($entry['invoiceOrderId']) ) {
					$sInvoiceOrderStatus = $this->getInvoiceOrderStatus_in_InvoiceOrder( $entry['invoiceOrderId'] );

					if( $sInvoiceOrderStatus == 'intermediate' ) {
						$aErr[] = '
							<a href="?cancelPaymentInvoiceOrderId=' . $entry['invoiceOrderId'] . '" class="button small" style="float: right;">' . _( 'Avbryt pågående') . '</a>
							<p><strong>' . sprintf( _('En betalning för frakturan - %s - har redan påbörjats.'), $entry['invoiceNo'] ) . '</strong></p>
							<p>' . _( 'Du kan avbryta alla pågående och sen påbörja en ny.' ) . '</p>';
					}
				}
			}

			if( empty($aErr) ) {
				// Create the invoice order
				$aInvoiceOrderData = array(
					'invoiceOrderCreated' => date( 'Y-m-d H:i:s' ),
					'invoiceOrderUserId' => $_SESSION['userId'],
					'invoiceOrderTotalAmount' => $fSumAmount,
					'invoiceOrderTotalVat' => $fSumVat,
					'invoiceOrderPaymentId' => $iPaymentType
				);
				$iInvoiceOrderId = $this->create( 'InvoiceOrder', $aInvoiceOrderData );

				if( ($iInvoiceOrderId !== false) && empty($aErr) ) {
					// Set the order id and locked the invoice if it isn't already

					$bUpdateInvoices = true;
					foreach( $aInvoiceData as $entry ) {
						if( $entry['invoiceLocked'] == 'yes' ) {
							$bUpdateInvoice = $this->update( 'Invoice', $entry['invoiceId'], array(
								'invoiceOrderId' => $iInvoiceOrderId
							) );
						} else {
							$bUpdateInvoice = $this->update( 'Invoice', $entry['invoiceId'], array(
								'invoiceLocked' => 'yes',
								'invoiceLockedDate' => date( 'Y-m-d H:i:s' ),
								'invoiceLockedByUserId' => $_SESSION['userId'],
								'invoiceOrderId' => $iInvoiceOrderId
							) );
						}

						if( $bUpdateInvoice === false ) $bUpdateInvoices = false;
					}

					if( $bUpdateInvoices === false ) {
						// Set the current invoice order to cancelled since it is invalid
						$this->oInvoiceEngine->update( 'InvoiceOrder', $iInvoiceOrderId, array(
							'invoiceOrderStatus' => 'cancelled'
						) );

						$aErr[] = _( 'An error occured. Try again or contact customer support if the problem persits.' );
					}
				}
			}
		} else {
			$aErr[] = _( 'You do not seem to be logged in.' );
		}

		if( isset($iInvoiceOrderId) && empty($aErr) ) {
			return $iInvoiceOrderId;
		} else {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->setError( $aErr );

			return false;
		}
	}

	/* * *
	 * Create a copy of invoice with credit status
	 * aData is modifications to make to the copy - structred like this:
	 * {
	 *	 'invoice' {
	 *		 field => value
 	 *	 },
	 *	 'invoiceLines' {
	 *		 lineId {
	 *		 	field => value
 	 *	 	 }
 	 *	 }
 	 * }
	 * * */
	public function createCreditInvoice( $iInvoiceId = null, $iInvoiceLineId = null, $aData = array(), $bIncludeInvoiceFee = false ) {
		$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

		$aInvoice = array();
		$aInvoiceLine = array();

		// Specify which fields to read
		$aInvoiceFields = array(
			'invoiceNo',
			'invoiceFirstname',
			'invoiceSurname',
			'invoiceCompanyName',
			'invoiceAddress',
			'invoiceZipCode',
			'invoiceCity',
			'invoiceCountryCode',
			'invoiceLateInterest',
			'invoiceVat',
			'invoiceCreditDays',
			'invoiceUserId',
			'invoiceAuctionId',
			'invoiceAuctionPartId',
		);
		if( $bIncludeInvoiceFee === true ) {
			$aInvoiceFields[] = 'invoiceFee';
		}
		$aInvoiceLineFields = array(
			'invoiceLineId',
			'invoiceLineTitle',
			'invoiceLineQuantity',
			'invoiceLinePrice',
			'invoiceLineVatValue',
			'invoiceLineFee',
			'invoiceLineUserId',
			'invoiceLineAuctionId',
			'invoiceLineInvoiceId',
			'invoiceLineItemId',
		);

		// Specify which fields to unset before Copy
		$aInvoiceUnsetFields = array(
			'invoiceNo'
		);
		$aInvoiceLineUnsetFields = array(
			'invoiceLineId',
			'invoiceLineInvoiceId'
		);

		// Read original invoice data
		if( !empty($iInvoiceId) ) {
	    $aInvoice = current( $this->read('Invoice', $aInvoiceFields, $iInvoiceId) );
		}

		// Read invoice line data
		if( empty($iInvoiceLineId) ) {
			// All lines
			if( !empty($iInvoiceId) ) {
	    	$aInvoiceLine = valueToKey( 'invoiceLineId', $this->readByInvoice_in_InvoiceLine($iInvoiceId, $aInvoiceLineFields) );
			}

		} else {
			// Read specific line data
	    $aInvoiceLine = valueToKey( 'invoiceLineId', $this->read('InvoiceLine', $aInvoiceLineFields, $iInvoiceLineId) );

			if( !empty($aInvoiceLine) && empty($iInvoiceId) ) {
				// If just the line was specified then set invoice data from that
				$aFirstLine = current( $aInvoiceLine );
				$iInvoiceId = $aFirstLine['invoiceLineInvoiceId'];
		    $aInvoice = current( $this->read('Invoice', $aInvoiceFields, $iInvoiceId) );
			}
		}

		// Make the credit invoice
		if( !empty($aInvoice) && !empty($aInvoiceLine) ) {
			$iInvoiceNo = $aInvoice['invoiceNo'];

			// Unset fields id in invoice
			if( !empty($aInvoiceUnsetFields) ) {
				foreach( $aInvoiceUnsetFields as $sField ) {
					unset( $aInvoice[ $sField ] );
				}
			}

			// Auction info
			$sAuctionInfo = '';
			if( !empty($aInvoice['invoiceAuctionId']) && !empty($aInvoice['invoiceAuctionPartId']) ) {
				$aAuctionData = current( $oAuctionEngine->readAuction( array(
					'fields' => array(
						'auctionLocation',
						'partAuctionStart'
					),
					'auctionStatus' => '*',
					'partStatus' => '*',
					'auctionId' => $aInvoice['invoiceAuctionId'],
					'partId' => $aInvoice['invoiceAuctionPartId']
				) ) );

				if( !empty($aAuctionData) ) {
					$sAuctionInfo = substr( $aAuctionData['partAuctionStart'], 0, 10 ) . ' ' . $aAuctionData['auctionLocation'];
				}
			}

			// Merge invoice data with new info
			$aCreditInvoice = array(
				'invoiceStatus' => 'unpaid',
				'invoiceType' => 'credit',
				'invoiceInformation' => _( 'Kredit för faktura' ) . ' ' . $iInvoiceNo . ( !empty($aInvoice['invoiceAuctionId']) ? ' (' . $aInvoice['invoiceAuctionId'] . ')' : '' ) . ( !empty($sAuctionInfo) ? '<br>' . $sAuctionInfo : '' ),
				'invoiceDate' => date( 'Y-m-d' ),
				'invoiceParentInvoiceId' => $iInvoiceId,
				'invoiceParentType' => 'credit'
			) + ( !empty($aData['invoice']) ? (array) $aData['invoice'] : array() ) + $aInvoice;

			// Unset invoice line fields and merge with new info
			$aCreditInvoiceLine = array();
			foreach( $aInvoiceLine as $iLineId => $aLine ) {

				// Merge new values
				$aCreditInvoiceLine[ $iLineId ] = array(
					'invoiceLineParentLineId' => $iLineId,
					'invoiceLineParentType' => 'credit'
				) + (!empty($aData['invoiceLine'][ $iLineId ]) ? $aData['invoiceLine'][ $iLineId ] : array() ) + $aLine;

				// Unset values
				if( !empty($aInvoiceLineUnsetFields) ) {
					foreach( $aInvoiceLineUnsetFields as $sField ) {
						unset( $aCreditInvoiceLine[ $iLineId ][ $sField ] );
					}
				}

			}

			// Create invoice and lines
			if( !empty($aCreditInvoiceLine) ) {
				if( $iNewInvoiceId = $this->create( "Invoice", $aCreditInvoice ) ) {

					foreach( $aCreditInvoiceLine as $aCreditLine ) {
						$mResult = $this->create( "InvoiceLine", array(
							'invoiceLineInvoiceId' => $iNewInvoiceId
						) + $aCreditLine );
					}

					return $iNewInvoiceId;
				}
			}

		}

		return false;
	}

	/* * *
	 * Create a copy of invoice And usually assign it to another user
	 * aData is modifications to make to the copy - structred like this:
	 * {
	 *	 'invoice' {
	 *		 field => value
 	 *	 },
	 *	 'invoiceLines' {
	 *		 lineId {
	 *		 	field => value
 	 *	 	 }
 	 *	 }
 	 * }
	 * * */
	public function createInvoiceCopy( $iInvoiceId = null, $iInvoiceLineId = null, $aData = array() ) {
		$aInvoice = array();
		$aInvoiceLine = array();

		// Specify which fields to read
		$aInvoiceFields = array(
			'invoiceNo',
			'invoiceInformation',
			'invoiceType',
			'invoiceFirstname',
			'invoiceSurname',
			'invoiceCompanyName',
			'invoiceAddress',
			'invoiceZipCode',
			'invoiceCity',
			'invoiceCountryCode',
			'invoiceFee',
			'invoiceLateInterest',
			'invoiceVat',
			'invoiceCreditDays',
			'invoiceUserId',
			'invoiceAuctionId',
			'invoiceAuctionPartId',
		);
		$aInvoiceLineFields = array(
			'invoiceLineId',
			'invoiceLineTitle',
			'invoiceLineQuantity',
			'invoiceLinePrice',
			'invoiceLineVatValue',
			'invoiceLineFee',
			'invoiceLineUserId',
			'invoiceLineAuctionId',
			'invoiceLineInvoiceId',
			'invoiceLineItemId',
		);

		// Specify which fields to unset before Copy
		$aInvoiceUnsetFields = array();
		$aInvoiceLineUnsetFields = array(
			'invoiceLineId',
			'invoiceLineInvoiceId'
		);

		// Read original invoice data
		if( !empty($iInvoiceId) ) {
	    $aInvoice = current( $this->read('Invoice', $aInvoiceFields, $iInvoiceId) );
		}

		// Read invoice line data
		if( empty($iInvoiceLineId) ) {
			// All lines
			if( !empty($iInvoiceId) ) {
	    	$aInvoiceLine = valueToKey( 'invoiceLineId', $this->readByInvoice_in_InvoiceLine($iInvoiceId, $aInvoiceLineFields) );
			}

		} else {
			// Read specific line data
	    $aInvoiceLine = valueToKey( 'invoiceLineId', $this->read('InvoiceLine', $aInvoiceLineFields, $iInvoiceLineId) );

			if( !empty($aInvoiceLine) && empty($iInvoiceId) ) {
				// If just the line was specified then set invoice data from that
				$aFirstLine = current( $aInvoiceLine );
				$iInvoiceId = $aFirstLine['invoiceLineInvoiceId'];
		    $aInvoice = current( $this->read('Invoice', $aInvoiceFields, $iInvoiceId) );
			}
		}

		// Make the credit invoice
		if( !empty($aInvoice) && !empty($aInvoiceLine) ) {

			// Unset fields id in invoice
			if( !empty($aInvoiceUnsetFields) ) {
				foreach( $aInvoiceUnsetFields as $sField ) {
					unset( $aInvoice[ $sField ] );
				}
			}

			// Merge invoice data with new info
			$aCreditInvoice = array(
				'invoiceStatus' => 'unpaid',
				'invoiceDate' => date( 'Y-m-d' ),
				'invoiceParentInvoiceId' => $iInvoiceId,
				'invoiceParentType' => 'copy'
			) + ( !empty($aData['invoice']) ? (array) $aData['invoice'] : array() ) + $aInvoice;

			// Unset invoice line fields and merge with new info
			$aCreditInvoiceLine = array();
			foreach( $aInvoiceLine as $iLineId => $aLine ) {

				// Merge new values
				$aCreditInvoiceLine[ $iLineId ] = array(
					'invoiceLineParentLineId' => $iLineId,
					'invoiceLineParentType' => 'copy',
					'invoiceLineUserId' => $aCreditInvoice['invoiceUserId']
				) + (!empty($aData['invoiceLine'][ $iLineId ]) ? $aData['invoiceLine'][ $iLineId ] : array() ) + $aLine;

				// Unset values
				if( !empty($aInvoiceLineUnsetFields) ) {
					foreach( $aInvoiceLineUnsetFields as $sField ) {
						unset( $aCreditInvoiceLine[ $iLineId ][ $sField ] );
					}
				}

			}

			// Create invoice and lines
			if( !empty($aCreditInvoiceLine) ) {
				if( $iNewInvoiceId = $this->create( "Invoice", $aCreditInvoice ) ) {

					foreach( $aCreditInvoiceLine as $aCreditLine ) {
						$mResult = $this->create( "InvoiceLine", array(
							'invoiceLineInvoiceId' => $iNewInvoiceId
						) + $aCreditLine );
					}

					return $iNewInvoiceId;
				}
			}

		}

		return false;
	}

	/* * *
	 * Generate a receipt for the transaction
	 * * */
	public function generateInvoiceOrderReceiptHtml( $iInvoiceOrderId ) {
		$sOutput = '';

		$oInvoiceDao = $this->getDao( 'Invoice' );

		// Fetch invoice order data
		$aInvoiceOrderData = current( $this->read('InvoiceOrder', '*', $iInvoiceOrderId) );

		if( !empty($aInvoiceOrderData) ) {
			clFactory::loadClassFile( 'clOutputHtmlTable' );

			$oOutputHtmlTable = new clOutputHtmlTable( $oInvoiceDao->getDataDict(), array(
				'attributes' => array(
					'style' => 'width: 100%;'
				)
			) );

			$oOutputHtmlTable->setTableDataDict( array(
				'invoiceNo' => array(
					'title' => _( 'Invoice no' )
				),
				'invoiceInformatiton' => array(
					'title' => _( 'Invoice information' )
				),
				'invoicePayedAmount' => array(
					'title' => _( 'Invoice payed amount' )
				)
			) );

			// Fetch invoices connected to the order
			$aInvoice = $this->readByInvoiceOrder_in_Invoice( $iInvoiceOrderId, '*' );

			foreach( $aInvoice as $entry ) {
				// All this invoice's payments
				$aInvoicePaymentData = arrayToSingle( $this->readByInvoice_in_InvoicePaymentLog($entry['invoiceId'], array('logAmount')), null, 'logAmount' ) ;
				$fSumRemaining = $entry['invoiceTotalAmount'] - array_sum( $aInvoicePaymentData );

				$row = array(
					'invoiceNo' => $entry['invoiceNo'],
					'invoiceInformatiton' => $entry['invoiceInformation'],
					'invoicePayedAmount' => calculatePrice( $fSumRemaining )
				);

				$oOutputHtmlTable->addBodyEntry( $row );
			}

			// Order total
			$row = array(
				'invoiceNo' => '',
				'invoiceInformatiton' => '',
				'invoicePayedAmount' => calculatePrice( $aInvoiceOrderData['invoiceOrderTotalAmount'] )
			);
			$oOutputHtmlTable->addFooterEntry( $row );

			// Order info
			$sOutput .= '
			<h1>' . _( 'Payment receipt' ) . '</h1>
			<div class="invoiceOrderReceipt">
				<dl class="marginal">
					<dt>' . _( 'Order ID' ) . '</dt>
					<dd>' . $iInvoiceOrderId . '</dd>
					<dt>' . _( 'Created' ) . '</dt>
					<dd>' . mb_substr( $aInvoiceOrderData['invoiceOrderCreated'], 0, 16 ) . '</dd>
				</dl>
				<div class="invoiceOrderItems" style="width: 100%;">
					<h2>' . _( 'Invoices' ) . '</h2>
					' . $oOutputHtmlTable->render() .
				'</div>
			</div>';

			return $sOutput;
		}

		return false;
	}

}

/**
 * Base class for basic functions (create, createMultiple, read, update, delete).
 * @uses function( 'subclass/type', ... )
 */
abstract class clInvoiceEngineBase {

	public $oAcl;

	protected $aEvents = array();
	protected $oEventHandler;

	protected function initBase() {
		$oUser = clRegistry::get( 'clUser' );
		$this->setAcl( $oUser->oAcl );

		$this->oEventHandler = clRegistry::get( 'clEventHandler' );
		$this->oEventHandler->addListener( $this, $this->aEvents );
	}

	public function setAcl( $oAcl ) {
		$this->oAcl = $oAcl;
	}

	public function create( $sType, $aData ) {
		if( $this->sEngineMode != 'normal' ) {
			throw new Exception( 'Denied! EngineMode is set to ' . $this->sEngineMode );
		}

		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/invoice/models' );
		return call_user_func( array( $this->$sModule, 'create' ), $aData );
	}

	public function createMultiple( $sType, $aData, $aFields ) {
		if( $this->sEngineMode != 'normal' ) {
			throw new Exception( 'Denied! EngineMode is set to ' . $this->sEngineMode );
		}

		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/invoice/models' );
		$sDao = 'oDao';
		return call_user_func( array( $this->$sModule, 'createMultiple' ), $aData, $aFields );
	}

	public function read( $sType, $aFields = array(), $primaryId = null ) {
		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/invoice/models' );
		return call_user_func( array( $this->$sModule, 'read' ), $aFields, $primaryId );
	}

	public function update( $sType, $primaryId, $aData ) {
		if( $this->sEngineMode != 'normal' ) {
			throw new Exception( 'Denied! EngineMode is set to ' . $this->sEngineMode );
		}

		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/invoice/models' );
		return call_user_func( array( $this->$sModule, 'update' ), $primaryId, $aData );
	}

	public function delete( $sType, $primaryId = null ) {
		if( $this->sEngineMode != 'normal' ) {
			throw new Exception( 'Denied! EngineMode is set to ' . $this->sEngineMode );
		}

		$sModule = 'o' . $sType;
		$this->$sModule = clRegistry::get( 'cl' . $sType, PATH_MODULE . '/invoice/models' );
		return call_user_func( array( $this->$sModule, 'delete' ), $primaryId );
	}

}
