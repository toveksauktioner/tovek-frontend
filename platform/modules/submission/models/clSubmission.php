<?php

/* * * * *
 * Filename: clSubmission.php
 * Created: 18/03/2014 by Mikael
 * Reference: database-overview.mwb
 * Description:
 * * * */

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_FUNCTION . '/fMoney.php';

class clSubmission extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'Submission';
		$this->sModulePrefix = 'submission';

		$this->oDao = clRegistry::get( 'clSubmissionDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/submission/models' );

		$this->initBase();

		$this->oDao->switchToSecondary();
	}

	/**
	 * Copy a submission to another auction
	 * Check if a copy already exists on that auction - then just return the submission id
	 */
	public function createCopy( $iSubmissionFromId, $iAuctionId, $aAlteredData = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		// First check if the submission is connected to this auction
		$aAuctionSubmissions = arrayToSingle( $this->readByAuction($iAuctionId, 'submissionId'), null, 'submissionId' );

		if( !in_array($iSubmissionFromId, $aAuctionSubmissions) ) {

			// Check if this submission have been copied to this auction already
			$aCopyExists = $this->readByCopyFrom( $iSubmissionFromId, $iAuctionId, 'submissionId' );

			if( empty($aCopyExists) ) {
				$sSavedCriterias = $this->oDao->sCriterias;
				$this->oDao->setCriterias();
				$aFromData = current( $this->read(null, $iSubmissionFromId) );
				$this->oDao->sCriterias = $sSavedCriterias;

				unset(
					$aFromData['submissionId'],
					$aFromData['submissionPaymentDate'],
					$aFromData['submissionBillingLocked'],
					$aFromData['submissionBillingLockedDate'],
					$aFromData['submissionMoreGoodsAvailable'],
					$aFromData['submissionSettlementDate'],
					$aFromData['submissionStatus'],
					$aFromData['submissionPartnerStatus'],
					$aFromData['submissionSigned'],
					$aFromData['submissionSignedDate'],
					$aFromData['submissionSentToUser'],
					$aFromData['submissionNotes'],
					$aFromData['submissionCreated'],
					$aFromData['submissionBillingLockedByUserId'],
					$aFromData['submissionSentToUserId'],
					$aFromData['submissionImportCustomId'],
					$aFromData['submissionVismaOrderId'],
					$aFromData['submissionCreatedByUserId'],
					$aFromData['submissionCopyFromSubmissionId'],
					$aFromData['submissionAuctionPartId']
				);

				$aToData = $aAlteredData + $aFromData + array(
					'submissionCopyFromSubmissionId' => $iSubmissionFromId,
					'submissionPaymentDate' => date( "Y-m-d" )
				);

				$iSubmissionToId = $this->create( $aToData );

			} else {
				$iSubmissionToId = current( current($aCopyExists) );
			}

		} else {
			$iSubmissionToId =  $iSubmissionFromId;
		}

		return $iSubmissionToId;
	}

	/**
	 * Read combined data from entSubmission, entSubmissionReport and entSubmissionAuctionReport
	 */
	public function readExtended( $aFields = array(), $sJoin = 'left' ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);

		switch( $sJoin ) {
			case 'inner':
				$sJoinType = 'INNER';
				break;

			case 'right':
				$sJoinType = 'RIGHT';
				break;

			case 'left':
			default:
				$sJoinType = 'LEFT';
		}

		return $this->oDao->readExtendedData( $aParams, $sJoinType );
	}

	/* * *
	 * Generate Submission HTML for gerneric purpose. For presentation on page, PDF and mail
	 * The bIntResult is to return only the values (used in accounting)
	 * generateSubmissionReportHtml and generateSubmissionAuctionReportHtml are helpers for the main generateSubmissionHtml function
	 *
	 * * */
	public function generateSubmissionReportHtml( $iReportId, $bPdf = false, $bIntResult = false, $bOnlyFirstPage = false, $aData = null ) {
		// Generic function for old and new submission reports. The new ones have different layout. Old are just to show old exactly as before

		if( empty($aData['report']) ) {
			$aReportData = current( $this->readReport(array(
				'reportSubmissionId',
				'reportVersion'
			), $iReportId) );
		} else {
			$aReportData = $aData['report'];
		}

		if( !empty($aReportData) ) {
			if( $aReportData['reportVersion'] == 'pre2017' ) {
				return $this->generateSubmissionHtmlOld( $aReportData['reportSubmissionId'], $bPdf, $bIntResult, $bOnlyFirstPage, 'report' );
			} else {
				return $this->generateSubmissionHtml( $aReportData['reportSubmissionId'], $iReportId, $bPdf, $bIntResult, $bOnlyFirstPage, 'report', $aData );
			}
		}

		return false;
	}
	public function generateSubmissionAuctionReportHtml( $iReportId, $bPdf = false, $bIntResult = false, $bOnlyFirstPage = false, $aData = null ) {

		if( empty($aData['auctionReport']) ) {
			$aAuctionReportData = current( $this->readAuctionReport('reportSubmissionId', $iReportId) );
		} else {
			$aAuctionReportData = $aData['auctionReport'];
		}

		if( !empty($aAuctionReportData) ) {
			return $this->generateSubmissionHtml( $aAuctionReportData['reportSubmissionId'], $iReportId, $bPdf, $bIntResult, $bOnlyFirstPage, 'auctionReport', $aData );
		}

		return false;

	}
	public function generateSubmissionHtml( $iSubmissionId, $iReportId = null, $bPdf = false, $bIntResult = false, $bOnlyFirstPage = false, $sType = 'submission', $aData = null ) {
		$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

		if( !$bIntResult ) {
				$oSubmissionComments = clRegistry::get( 'clSubmissionComments', PATH_MODULE . '/submission/models' );
				clFactory::loadClassFile( 'clOutputHtmlTable' );
		}

		$sOutput = '';
		$aIntResult = array(
			'billingLines' => array(
				'preVat' => array(),
				'postVat' => array(),
				'preVatSum' => 0,
				'postVatSum' => 0
			),
			'lineCount' => 0,
			'lineSoldCount' => 0,
			'lineVatValue' => ( ($sType == 'auctionReport') ? 0 : 25 ),
		);

		$aTemplateVariables = array(
			'reportNo' => ''
		);
		if( !$bIntResult ) {
			$aTemplateVariables = array(
				'submissionId' => $iSubmissionId,
				'sectionClass' => '',
				'logoSrc' => '/images/templates/argoWhite/pdf-logo.png',
				'typeTitle' => _( 'Inlämning' ),
				'submitterNo' => '',
				'sectionSubmissionLinePages' => '',
				'lineVatValue' => ( ($sType == 'auctionReport') ? 0 : 25 ),
				'preVatLines' => '',
				'postVatLines' => '',
				'postVatLinesStyle' => 'style="display: none;"',
				'payoutString' => ''
			);
		}

		$aDataDict = $this->oDao->getDataDict();

		// First clear criterias and save it to reset in the end
		$aSubmissionCriterias = $this->oDao->sCriterias;
		$this->oDao->sCriterias = null;

		// Get the information for the footer
		if( !$bIntResult ) {
			$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
			$sFooter = (($aFooter = $oInfoContent->read('contentTextId', 28)) ? $aFooter[0]['contentTextId'] : null );
		}

		// Get invoice data
		if( !empty($aData['submission']) ) {
			$aSubmissionData = $aData['submission'];
		} else {
			$aSubmissionData = current( $this->read(null, $iSubmissionId) );
		}


		// Fetch submission report data
		$aSubmissionLinesParams = array();
		if( $sType == 'report' ) {
			// Report
			if( !empty($aData['report']) ) {
				$aReportData = $aData['report'];
			} else {
				if( !empty($iReportId) && empty($aReportData) ) {
					$aReportData = current( $this->readReport('*', $iReportId, $iSubmissionId) );
				} else {
					$aReportData = current( $this->readReport('*', null, $iSubmissionId) );
				}
			}

			if( !empty($aReportData) ) {
				if( !$bIntResult ) {
					$aTemplateVariables['reportNo'] = $aReportData['submissionReportNo'];
				}

				$aSubmissionLinesParams['lineReportId'] = $aReportData['submissionReportId'];

				// Overwrite submission data
				$aSubmissionData['submissionCommissionFee'] = $aReportData['submissionReportCommissionFee'];

				$aSubmissionData['submissionCommissionMax'] = $aReportData['submissionReportCommissionMax'];

				$aSubmissionData['submissionPaymentDate'] = $aReportData['submissionReportPaymentDate'];

				if( $aReportData['submissionReportMarketingFeeType'] == 'sek' ) {
					$aSubmissionData['submissionMarketingFeeSum'] = $aReportData['submissionReportMarketingFeeValue'];
					$aSubmissionData['submissionMarketingFee'] = 0;
				} else {
					$aSubmissionData['submissionMarketingFeeSum'] = 0;
					$aSubmissionData['submissionMarketingFee'] = $aReportData['submissionReportMarketingFeeValue'];
				}

			}

			if( !$bIntResult ) {
				$aTemplateVariables['typeTitle'] = _( 'Självfaktura' );
			}

		} else if( $sType == 'auctionReport' ) {
			// Auction report
			if( !empty($aData['auctionReport']) ) {
				$aReportData = $aData['auctionReport'];
			} else {
				if( !empty($iReportId) ) {
					$aReportData = current( $this->readAuctionReport('*', $iReportId, $iSubmissionId) );
				} else {
					$aReportData = current( $this->readAuctionReport('*', null, $iSubmissionId) );
				}
			}

			if( !empty($aReportData) ) {
				if( !$bIntResult ) {
					$aTemplateVariables['reportNo'] = 'A' . $aReportData['submissionAuctionReportId'];
				}

				$aSubmissionLinesParams['lineAuctionReportId'] = $aReportData['submissionAuctionReportId'];

				// Overwrite submission data
				$aSubmissionData['submissionCommissionFee'] = $aReportData['submissionAuctionReportCommissionFee'];

				$aSubmissionData['submissionCommissionMax'] = $aReportData['submissionAuctionReportCommissionMax'];

				$aSubmissionData['submissionPaymentDate'] = $aReportData['submissionAuctionReportPaymentDate'];

				if( $aReportData['submissionAuctionReportMarketingFeeType'] == 'sek' ) {
					$aSubmissionData['submissionMarketingFeeSum'] = $aReportData['submissionAuctionReportMarketingFeeValue'];
					$aSubmissionData['submissionMarketingFee'] = 0;
				} else {
					$aSubmissionData['submissionMarketingFeeSum'] = 0;
					$aSubmissionData['submissionMarketingFee'] = $aReportData['submissionAuctionReportMarketingFeeValue'];
				}

			}
			if( !$bIntResult ) {
				$aTemplateVariables['typeTitle'] =  _( 'Auktionsredovisning' );
			}

		}

		// Submission varianbles saved after report data is read because it might have changed it.
		if( !$bIntResult ) {
			$aTemplateVariables += $aSubmissionData;
		}

		// Fetch auction data
		if( !empty($aSubmissionData['submissionAuctionId']) ) {
			$aAuctionData = $oAuctionEngine->readAuction( array(
				'fields' => array(
					'partAuctionStart',
					'auctionId',
					'auctionLocation'
				),
				'auctionId' => $aSubmissionData['submissionAuctionId'],
				'auctionStatus' => '*',
				'partStatus' => '*'
			) );
			$aAuctionData = end( $aAuctionData );

			// Date for submission/report and auction
			if( !empty($aAuctionData['partAuctionStart']) ) {
				$sAuctionTime = strtotime( $aAuctionData['partAuctionStart'] );
				$iReportTime = $sAuctionTime + 604800;
			} else {
				$sAuctionTime = time();
				$iReportTime = $sAuctionTime;
			}

			$aTemplateVariables += array(
				'auctionDate' => date( 'Ymd', $sAuctionTime ),
				'auctionLocation' => $aAuctionData['auctionLocation'],
				'auctionInfo' => _( 'Gällande auktion' ) . ': ' . $aAuctionData['auctionId'] . ' ' . $aAuctionData['auctionLocation'] . ' ' .  date( 'ymd', $sAuctionTime )
			);
		} else {
			$aTemplateVariables += array(
				'auctionDate' => date( 'Ymd' ),
				'auctionLocation' => '',
				'auctionInfo' => '&nbsp;'
			);

			$iReportTime = time();
		}
		// Override the calculated report time if set
		if( !empty($aSubmissionData['submissionSettlementDate']) && ($aSubmissionData['submissionSettlementDate'] != '0000-00-00') ) {
			$iReportTime = strtotime( $aSubmissionData['submissionSettlementDate'] );
		}
		$aTemplateVariables['reportDate'] = date( 'Y-m-d', $iReportTime );


		// Define variables to use for summation
		$fTotalSum = 0;
		$fVatSum = 0;

		if( !empty($aSubmissionData) ) {

			// Fetch submitter data
			if( !$bIntResult ) {
				$oSubmitter = clRegistry::get( 'clSubmitter', PATH_MODULE . '/submitter/models' );
				$aSubmitterData = current( $oSubmitter->read(array(
					'submitterId',
					'submitterNo',
					'submitterPin',
					'submitterType'
				), $aSubmissionData['submissionSubmitterId']) );
				if( !empty($aSubmitterData['submitterPin']) && (stristr('-', $aSubmitterData['submitterPin'])) ) {
					$aSubmitterData['submitterPin'] = substr( $aSubmitterData['submitterPin'], 0, 6 ) . '-' . substr( $aSubmitterData['submitterPin'], 6 );
				}
				if( !empty($aSubmitterData) ) {
					$aTemplateVariables = $aSubmitterData + $aTemplateVariables;
				}
			}

			// Fetch submission
			$this->oDao->setEntries( 0 );
			$aSubmitterLines = $this->oDao->readLines( $aSubmissionLinesParams + array(
				'lineSubmissionId' => $iSubmissionId,
				'fields' => array(
					'lineId',
					'lineSortNo',
					'lineTitle',
					'lineValue',
					'lineVatValue',
					'lineRecalled',
					'lineAuctionItemId'
				)
			) );
			if( !empty($aSubmitterLines) ) {
				$aTemplateVariables['lineVatValue'] = $aSubmitterLines[0]['lineVatValue'];
				$aIntResult['lineVatValue'] = $aSubmitterLines[0]['lineVatValue'];

				// There was an miscalculation on four reports
				// on the four reports must stay the same.
				// Therefor the exclusion below
				if( ($sType == 'report') && in_array($aTemplateVariables['reportNo'], array(32684,32822,32894,32951)) ) {
					$aIntResult['lineVatValue'] = 25;
				}

				$aIntResult['lineCount'] = count( $aSubmitterLines );
			}

			// Fetch item data
			$aItemData = array();
			$aItems = arrayToSingle( $aSubmitterLines, null, 'lineAuctionItemId' );
			if( !empty($aItems) ) {
				$aItemData = valueToKey( 'itemId', $oAuctionEngine->read('AuctionItem', array(
					'itemId',
					'itemFeeType',
					'itemFeeValue'
				), $aItems) );
			}


			// Fetch comments for the submission
			if( !$bIntResult ) {
				$aSubmissionComments = $oSubmissionComments->readByRelation( 'submission', $iSubmissionId );
				$aComments = array();
				foreach( $aSubmissionComments as $aComment ) {
					if( isset($aComments[ $aComment['commentRelationField'] ]) ) {
						$aComments[ $aComment['commentRelationField'] ]++;
					} else {
						$aComments[ $aComment['commentRelationField'] ] = 1;
					}
				}
				$aTemplateVariables['submitterPinCommentClass'] = ( !empty($aComments['submitterPin']) ? ' gotComments' : '' );
				$aTemplateVariables['submitterReferralCommentClass'] = ( !empty($aComments['submitterReferral']) ? ' gotComments' : '' );
				$aTemplateVariables['submitterAddressCommentClass'] = ( !empty($aComments['submitterAddress']) ? ' gotComments' : '' );
				$aTemplateVariables['submissionCommissionFeeCommentClass'] = ( !empty($aComments['submissionCommissionFee']) ? ' gotComments' : '' );
				$aTemplateVariables['submissionMarketingFeeCommentClass'] = ( !empty($aComments['submissionMarketingFee']) ? ' gotComments' : '' );
				$aTemplateVariables['submissionRecallFeeCommentClass'] = ( !empty($aComments['submissionRecallFee']) ? ' gotComments' : '' );
				$aTemplateVariables['submissionPaymentToTypeCommentClass'] = ( !empty($aComments['submissionPaymentToType']) ? ' gotComments' : '' );


				// Layout variables
				$aTemplateVariables['sectionClass'] = ( $bPdf ? '' : ' preview' );
				$aTemplateVariables['logoSrc'] = ( $bPdf ? PATH_PUBLIC : '' ) . '/images/templates/argoWhite/pdf-logo.png';
				$aTemplateVariables['moreGoodsAvailable'] =  ( ($aSubmissionData['submissionMoreGoodsAvailable'] == 'no') ? '<br><em class="commentingArea' . ( !empty($aComments['submissionMoreGoodsAvailable']) ? ' gotComments' : '' ) . '" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submissionMoreGoodsAvailable">' . _( 'Alla varor är sålda' ) . '</em>' : '' );
			}


			$fLinesBidSum = 0;
			$fLinesTotalWithoutVatSum25 = 0;
			$fLinesTotalWithoutVatSum12 = 0;
			$fLinesTotalWithoutVatSum0 = 0;
			$fLinesTotalVat = 0;
			$fLinesTotalVat25 = 0;
			$fLinesTotalVat12 = 0;
			$fLinesRecallSum = 0;
			$fLinesFeeSum = 0;

			foreach( $aSubmitterLines as $entry ) {
				$fLineVatValue = ( !empty($entry['lineVatValue']) ? $entry['lineVatValue'] : 0 );

				$fLinesBidSum += $entry['lineValue'];

				if( $entry['lineRecalled'] != 'yes' ) {
					// Count sold objects
					if( $entry['lineValue'] > 0 ) {
						$aIntResult['lineSoldCount']++;
					}

					$fThisItemVat = $entry['lineValue'] * $fLineVatValue / 100;

					$fLinesTotalVat += $fThisItemVat;

					// Split sums (VAT and Total)
					switch( $fLineVatValue ) {
						case 25:
							$fLinesTotalWithoutVatSum25 += $entry['lineValue'];
							$fLinesTotalVat25 += $fThisItemVat;
							break;

						case 12:
							$fLinesTotalWithoutVatSum12 += $entry['lineValue'];
							$fLinesTotalVat12 += $fThisItemVat;
							break;

						case 0:
						default:
							$fLinesTotalWithoutVatSum0 += $entry['lineValue'];

					}

					// Calculate fees (feeType 'none' doesn't do anything)
					// This should only apply on sold object (value > 0)
					if( !empty($aItemData[$entry['lineAuctionItemId']]) && ($entry['lineValue'] > 0) ) {
						switch( $aItemData[$entry['lineAuctionItemId']]['itemFeeType'] ) {
							case 'sek':
								$fLinesFeeSum += $aItemData[$entry['lineAuctionItemId']]['itemFeeValue'];
								break;

							case 'percent':
								$fLinesFeeSum += ( $entry['lineValue'] * $aItemData[$entry['lineAuctionItemId']]['itemFeeValue'] / 100 );
								break;
						}
					}
				} else {
					$fLinesRecallSum += $entry['lineValue'];
				}
			}
			$fLinesTotalWithoutVatSum = $fLinesBidSum - $fLinesRecallSum;
			$fLinesTotalSum = $fLinesTotalWithoutVatSum + $fLinesTotalVat;

			// Store the values in return array (returned if bIntResult is true)
			$aIntResult += array(
				'linesBidSum' 							=> $fLinesBidSum,
				'linesRecallSum' 						=> $fLinesRecallSum,
				'linesTotalWithoutVatSum' 	=> $fLinesTotalWithoutVatSum,
				'linesTotalWithoutVatSum25' => $fLinesTotalWithoutVatSum25,
				'linesTotalWithoutVatSum12' => $fLinesTotalWithoutVatSum12,
				'linesTotalWithoutVatSum0' 	=> $fLinesTotalWithoutVatSum0,
				'linesTotalVat' 						=> $fLinesTotalVat,
				'linesTotalVat25' 					=> $fLinesTotalVat25,
				'linesTotalVat12' 					=> $fLinesTotalVat12,
				'linesTotalSum' 						=> $fLinesTotalSum,
				'linesTotalFee' 						=> $fLinesFeeSum,
				'reportDate'								=> ( !empty($iReportTime) ? date('Y-m-d', $iReportTime) : '' )
			);

			// Commission can have a max value in SEK
			if( !empty($aSubmissionData['submissionCommissionMax']) ) {
				$fCommissionValue = $aSubmissionData['submissionCommissionMax'];
				if( !$bIntResult ) {
					$aTemplateVariables['submissionCommissionFeeLabel'] = '';
				}
			} else {
				$fCommissionValue = $fLinesTotalWithoutVatSum * $aSubmissionData['submissionCommissionFee'] / 100;
				if( !$bIntResult ) {
					$aTemplateVariables['submissionCommissionFeeLabel'] = ' ' . $aSubmissionData['submissionCommissionFee'] . '%';
				}
			}

			// Marketing value can be either percentual or value
			if( !empty($aSubmissionData['submissionMarketingFeeSum']) ) {
				$fMarketingValue = $aSubmissionData['submissionMarketingFeeSum'];
			} else {
				$fMarketingValue = $fLinesBidSum * $aSubmissionData['submissionMarketingFee'] / 100;
			}

			$fCostsTotalSum = $fCommissionValue + $fMarketingValue;

			// Store the values in return array (returned if bIntResult is true)
			$aIntResult += array(
				'commissionFeeValue'	=> $fCommissionValue,
				'marketingFeeValue' 	=> $fMarketingValue,
				'feeSum' 							=> $fCostsTotalSum
			);

			$aPriceParams = array(
				'decimals' => 2
			);
			$aTotalPriceParams = array(
				'format' => array (
					'money' => true,
					'vatLabel' => false,
					'currency' => false
				)
			);

			if( !$bIntResult ) {
				$aTemplateVariables += array(
					'linesBidSum' => calculatePrice( $fLinesBidSum, $aPriceParams ),
					'commissionFeeValue' => calculatePrice( $fCommissionValue, $aPriceParams ),
					'marketingValue' => calculatePrice( $fMarketingValue, $aPriceParams ),
					'linesTotalVat' => calculatePrice( $fLinesTotalVat, $aPriceParams )
				);
			}


			// Billing lines
			$aBillingLinesParams = array(
				'billingLineSubmissionId' => $iSubmissionId,
				'fields' => array(
					'billingLineId',
					'billingLineTitle',
					'billingLineValue'
				)
			);
			if( $sType == 'report' ) {
				$aBillingLinesParams['billingLineSubmissionReportId'] = $iReportId;
			}
			if( $sType == 'auctionReport' ) {
				$aBillingLinesParams['billingLineSubmissionAuctionReportId'] = $iReportId;
			}

			// Pre vat lines
			$aSubmissionPreVatLines = $this->oDao->readBillingLines( $aBillingLinesParams + array(
				'billingLineType' => 'preVat'
			) );

			foreach( $aSubmissionPreVatLines as $aPreVatLines ) {
				// Fetch comments for the billing line
				if( !$bIntResult ) {
					$aBillingLineComments = $oSubmissionComments->readByRelation( 'submissionBillingLine', $aPreVatLines['billingLineId'] );
					$aBLComments = array();
					foreach( $aBillingLineComments as $aComment ) {
						if( isset($aComments[ $aComment['commentRelationField'] ]) ) {
							$aBLComments[ $aComment['commentRelationField'] ]++;
						} else {
							$aBLComments[ $aComment['commentRelationField'] ] = 1;
						}
					}

					$aTemplateVariables['preVatLines'] .= '
						<tr>
							<td class="col1" width="33%">&nbsp;</td>
							<td class="col2 commentingArea' . ( !empty($aBLComments['billingLineTitle']) ? ' gotComments' : '' ) . '" width="33%" align="right" data-relation-type="submissionBillingLine" data-relation-id="' . $aPreVatLines['billingLineId'] . '" data-relation-field="billingLineTitle">' . $aPreVatLines['billingLineTitle'] . '</td>
							<td class="col3 commentingArea' . ( !empty($aBLComments['billingLineValue']) ? ' gotComments' : '' ) . '" width="25%" align="right" data-relation-type="submissionBillingLine" data-relation-id="' . $aPreVatLines['billingLineId'] . '" data-relation-field="billingLineValue">' . calculatePrice( $aPreVatLines['billingLineValue'], $aPriceParams ) . '</td>
						</tr>';
				}

				$fCostsTotalSum += $aPreVatLines['billingLineValue'];

				// Store the values in return array (returned if bIntResult is true)
				$aIntResult['billingLines']['preVat'][] = array(
					'title'	=> $aPreVatLines['billingLineTitle'],
					'value' => $aPreVatLines['billingLineValue']
				);
				$aIntResult['billingLines']['preVatSum'] += $aPreVatLines['billingLineValue'];
			}

			// Post vat lines
			$aSubmissionPostVatLines = $this->oDao->readBillingLines( $aBillingLinesParams + array(
				'billingLineType' => 'postVat'
			) );
			foreach( $aSubmissionPostVatLines as $aPostVatLines ) {
				// Fetch comments for the billing line
				if( !$bIntResult ) {
					$aBillingLineComments = $oSubmissionComments->readByRelation( 'submissionBillingLine', $aPostVatLines['billingLineId'] );
					$aBLComments = array();
					foreach( $aBillingLineComments as $aComment ) {
						if( isset($aComments[ $aComment['commentRelationField'] ]) ) {
							$aBLComments[ $aComment['commentRelationField'] ]++;
						} else {
							$aBLComments[ $aComment['commentRelationField'] ] = 1;
						}
					}

					$aTemplateVariables['postVatLinesStyle'] = '';
					$aTemplateVariables['postVatLines'] .= '
						<tr>
							<td class="col1" width="33%">&nbsp;</td>
							<td class="col2 commentingArea' . ( !empty($aBLComments['billingLineTitle']) ? ' gotComments' : '' ) . '" width="33%" align="right" data-relation-type="submissionBillingLine" data-relation-id="' . $aPostVatLines['billingLineId'] . '" data-relation-field="billingLineTitle">' . $aPostVatLines['billingLineTitle'] . '</td>
							<td class="col3 commentingArea' . ( !empty($aBLComments['billingLineValue']) ? ' gotComments' : '' ) . '" width="25%" align="right" data-relation-type="submissionBillingLine" data-relation-id="' . $aPostVatLines['billingLineId'] . '" data-relation-field="billingLineValue">' . calculatePrice( $aPostVatLines['billingLineValue'], $aPriceParams ) . '</td>
						</tr>';
				}

				$fCostsTotalSum += $aPostVatLines['billingLineValue'];

				// Store the values in return array (returned if bIntResult is true)
				$aIntResult['billingLines']['postVat'][] = array(
					'title'	=> $aPostVatLines['billingLineTitle'],
					'value' => $aPostVatLines['billingLineValue']
				);
				$aIntResult['billingLines']['postVatSum'] += $aPostVatLines['billingLineValue'];
			}



			if( $sType != 'auctionReport' ) {
				// Values for normal reports

				$fCommissionGroupReductionSum = $fCommissionValue + $fMarketingValue;
				$fCommissionGroupSum = $fLinesBidSum - $fCommissionGroupReductionSum;
				$fCommissionGroupVat = $fCommissionGroupSum * $aIntResult['lineVatValue'] / 100;
				$fCommissionGroupWithVatSum = $fCommissionGroupSum + $fCommissionGroupVat;
				$fPreVatCostsSum = $aIntResult['billingLines']['preVatSum'];
				$fPreVatCostsVat = $fPreVatCostsSum * SUBMISSION_VAT / 100;
				$fPostVatCostsSum = $aIntResult['billingLines']['postVatSum'];
				$fCostGroupSum = $fPreVatCostsSum + $fPreVatCostsVat + $fPostVatCostsSum;
				$fCostsTotalVat = $fCostsTotalSum * SUBMISSION_VAT / 100;
				$fTotalSum = $fCommissionGroupWithVatSum - $fCostGroupSum;
				$fTotalRoundedSum = round( $fTotalSum );
				$fRoundValue = $fTotalRoundedSum - $fTotalSum;

				if( !$bIntResult ) {
					$aTemplateVariables += array(
						'commissionGroupSum' => calculatePrice( $fCommissionGroupSum, $aPriceParams),
						'commissionGroupVat' => calculatePrice( $fCommissionGroupVat, $aPriceParams),
						'commissionGroupWithVatSum' => calculatePrice( $fCommissionGroupWithVatSum, $aPriceParams ),
						'costCostPreVatGroupVat' => calculatePrice( $fPreVatCostsVat, $aPriceParams ),
						'costCostPreVatGroupSum' => calculatePrice( ($fPreVatCostsSum + $fPreVatCostsVat), $aPriceParams ),
						'costCostPostVatGroupSum' => calculatePrice( $fPostVatCostsSum, $aPriceParams ),
						'costGroupSum' => calculatePrice( $fCostGroupSum, $aPriceParams ),
						'costsTotal'	=> calculatePrice( $fCostsTotalSum, $aPriceParams ),
						'totalSum' => calculatePrice( $fTotalSum, $aPriceParams ),
						'totalRoundedSum' => calculatePrice( $fTotalRoundedSum, $aTotalPriceParams ),
						'roundValue' => calculatePrice( $fRoundValue, $aPriceParams )
					);
				}

				// Store the values in return array (returned if bIntResult is true)
				$aIntResult += array(
					'commissionGroupReductionSum' => $fCommissionGroupReductionSum,
					'commissionGroupVat' => $fCommissionGroupVat,
					'preVatCostsVat'	=> $fPreVatCostsVat,
					'costsTotal'	=> $fCostsTotalSum,
					'totalRoundedSum' => $fTotalRoundedSum,
					'roundValue' => $fRoundValue
				);

			} else {
				// Values for auction report

				$fCommissionVatValue = $fCommissionValue * SUBMISSION_VAT / 100;
				$fCommissionGroupSum = $fCommissionValue + $fCommissionVatValue;
				$fPreVatCostsSum = $fMarketingValue + $aIntResult['billingLines']['preVatSum'];
				$fPreVatCostsVat = $fPreVatCostsSum * SUBMISSION_VAT / 100;
				$fPostVatCostsSum = $aIntResult['billingLines']['postVatSum'];
				$fCostGroupSum = $fPreVatCostsSum + $fPreVatCostsVat + $fPostVatCostsSum;
				$fCostsTotalVat = $fCostsTotalSum * SUBMISSION_VAT / 100;
				$fTotalSum = $fLinesBidSum - $fCommissionGroupSum - $fCostGroupSum;
				$fTotalRoundedSum = round( $fTotalSum );
				$fRoundValue = $fTotalRoundedSum - $fTotalSum;

				if( !$bIntResult ) {
					$aTemplateVariables += array(
						'commissionFeeVatValue' => calculatePrice( $fCommissionVatValue, $aPriceParams),
						'commissionGroupSum' => calculatePrice( $fCommissionGroupSum, $aPriceParams),
						'costCostPreVatGroupVat' => calculatePrice( $fPreVatCostsVat, $aPriceParams ),
						'costCostPreVatGroupSum' => calculatePrice( ($fPreVatCostsSum + $fPreVatCostsVat), $aPriceParams ),
						'costCostPostVatGroupSum' => calculatePrice( $fPostVatCostsSum, $aPriceParams ),
						'costGroupSum' => calculatePrice( $fCostGroupSum, $aPriceParams ),
						'costsTotal'	=> calculatePrice( $fCostsTotalSum, $aPriceParams ),
						'totalSum' => calculatePrice( $fTotalSum, $aPriceParams ),
						'totalRoundedSum' => calculatePrice( $fTotalRoundedSum, $aTotalPriceParams ),
						'roundValue' => calculatePrice( $fRoundValue, $aPriceParams )
					);
				}

				// Store the vat for costs and store the values in return array (returned if bIntResult is true)
				$aIntResult += array(
					'commissionFeeVatValue'	=> $fCommissionVatValue,
					'preVatCostsVat'	=> $fPreVatCostsVat,
					'costsVat'	=> $fCostsTotalVat,
					'costsTotal'	=> $fCostsTotalSum,
					'totalRoundedSum' => $fTotalRoundedSum,
					'roundValue' => $fRoundValue
				);
			}

			if( !empty($aSubmissionData['submissionPaymentDate']) && ($aSubmissionData['submissionPaymentDate'] != '0000-00-00') ) {
				$sPayoutDate = $aSubmissionData['submissionPaymentDate'];
			} else {
				$iPayoutTime = ( $aSubmissionData['submissionPaymentDays'] * 86400 ) + strtotime( $aTemplateVariables['auctionDate'] );
				$sPayoutDate = ( !empty($iPayoutTime) ? date("Y-m-d", $iPayoutTime) : '' );
			}

			if( !$bIntResult ) {
				$aTemplateVariables += array(
					'payoutDate' => $sPayoutDate
				);
			}

			// Store the values in return array (returned if bIntResult is true)
			$aIntResult += array(
				'costsTotal'	=> $fCostsTotalSum,
				'totalRoundedSum' => $fTotalRoundedSum,
				'roundValue' => $fRoundValue,
				'payoutDate' => $sPayoutDate
			);

			if( !$bIntResult ) {
				switch( $aSubmissionData['submissionPaymentToType'] ) {
					case 'cash':
						$aTemplateVariables['paymentString'] = $aDataDict['entSubmission']['submissionPaymentToType']['values'][$aSubmissionData['submissionPaymentToType']];
						break;

					case 'pg':
					case 'bg':
					case 'account':
						$aTemplateVariables['paymentString'] = _( 'to' ) . ' ' . $aDataDict['entSubmission']['submissionPaymentToType']['values'][$aSubmissionData['submissionPaymentToType']] . ' ' . $aSubmissionData['submissionPaymentToAccount'];
						break;

					case '':
					default:
						$aTemplateVariables['paymentString'] = '';
				}

				if( $fTotalRoundedSum > 0 ) {
					// The payout info is only set if there is a positive sum
					$aTemplateVariables['payoutString'] = _( 'Utbetalas' ) . ' ' . $aTemplateVariables['payoutDate'] . ' ' . $aTemplateVariables['paymentString'];
				}
			}

			// Signature date
			if( !$bIntResult ) {
				if( $aSubmissionData['submissionSigned'] == 'yes' ) {
					if( !empty($aSubmissionData['submissionSignedDate']) ) {
						$aTemplateVariables['signedDate'] = $aSubmissionData['submissionSignedDate'];

					} else if( $aSubmissionData['submissionBillingLockedDate'] != '0000-00-00 00:00:00' ) {
						$aTemplateVariables['signedDate'] = substr( $aSubmissionData['submissionBillingLockedDate'], 0, 10 );

					}
				} else {
					$aTemplateVariables['signedDate'] = date( "Y-m-d" );
				}

				// Signature
				$aTemplateVariables['signImage'] = ( ($aSubmissionData['submissionSigned'] == 'yes') ? '<img src="' . ( $bPdf ? PATH_PUBLIC : '' ) . '/images/templates/argoWhite/martin-signatur.png"' . ( $bPdf ? ' width="25%"' : '' ) . '>' : '&nbsp;' );
				$aTemplateVariables['signName'] = _( 'Martin Tovek' );
			}



			/********************************************************************************************
			* Lines - all the ) to this report are organized into html and inserted into the template
			*********************************************************************************************/
			if( $bOnlyFirstPage !== true ) {
				$iLinesPerPage = SUBMISSION_ITEMS_PER_PAGE;
				$iPage = 1;
				$iPages = ceil( count($aSubmitterLines) / $iLinesPerPage );

				$iCount = 1;
				$sTableContent = '';
				foreach( $aSubmitterLines as $entry ) {

					// Fetch comments for the  line
					if( !$bIntResult ) {
						$aLineComments = $oSubmissionComments->readByRelation( 'submissionLine', $entry['lineId'] );
						$aLComments = array();
						foreach( $aLineComments as $aComment ) {
							if( isset($aComments[ $aComment['commentRelationField'] ]) ) {
								$aLComments[ $aComment['commentRelationField'] ]++;
							} else {
								$aLComments[ $aComment['commentRelationField'] ] = 1;
							}
						}
					}

					$fLineVatSum = $entry['lineValue'] * $entry['lineVatValue'] / 100;

					if( !$bIntResult ) {
						$sTableContent .= '
											<tr>
												<td class="commentingArea' . ( !empty($aLComments['lineSortNo']) ? ' gotComments' : '' ) . '" data-relation-type="submissionLine" data-relation-id="' . $entry['lineId'] . '" data-relation-field="lineSortNo">' . $entry['lineSortNo'] . '</td>
												<td class="commentingArea' . ( !empty($aLComments['lineTitle']) ? ' gotComments' : '' ) . '" data-relation-type="submissionLine" data-relation-id="' . $entry['lineId'] . '" data-relation-field="lineTitle">' . $entry['lineTitle'] . '</td>
												<td class="commentingArea' . ( !empty($aLComments['lineValue']) ? ' gotComments' : '' ) . '" data-relation-type="submissionLine" data-relation-id="' . $entry['lineId'] . '" data-relation-field="lineValue">' . calculatePrice( $entry['lineValue'], $aPriceParams ) . '</td>
												<td class="commentingArea' . ( !empty($aLComments['lineVatValue']) ? ' gotComments' : '' ) . '" data-relation-type="submissionLine" data-relation-id="' . $entry['lineId'] . '" data-relation-field="lineVatValue">' . calculatePrice( $fLineVatSum, $aPriceParams ) . '</td>
											</tr>';

						if( (($iCount % $iLinesPerPage) == 0) || ($iCount == count($aSubmitterLines)) ) {
							$aTemplateVariables['sectionSubmissionLinePages'] .= '
								<section class="submission' . ( $bPdf ? '' : ' preview' ) . ' submissionItemList">
									<header>
										<div class="paging">' . _( 'Page' ) . ' ' . $iPage . ' ' . _( 'of' ) . ' ' . $iPages . '</div>
										<h1>' . _( 'Auction list' ) . ' ' . $aSubmissionData['submissionTitle'] . '</h1>
										<p>' . $aTemplateVariables['typeTitle'] . ' nr ' . $aTemplateVariables['reportNo'] . '</p>
									</header>
									<div class="content">
										<table class="dataTable">
											<thead>
												<tr>
													<th>' . _( 'Call no' ) . '</th>
													<th>' . _( 'Product' ) . '</th>
													<th>' . _( 'Price' ) . '</th>
													<th>' . _( 'VAT' ) . '</th>
												</tr>
											</thead>
											<tbody>
												' . $sTableContent . '
											</tbody>
										</table>
									</div>
								</section>';

								$sTableContent = '';
								$iPage++;
						}
					}

					$iCount++;
				}
			}
		}

		// Reset the saved criterias
		$this->oDao->sCriterias = $aSubmissionCriterias;

		if( $bIntResult ) {

			// Store the total values
			$aIntResult += array(
				'totalSum'	=> $fTotalSum,
				'vatSum'		=> $fVatSum
			);

			return $aIntResult;
		} else {
			if( $sType == 'auctionReport' ) {
				$sOutput = file_get_contents( PATH_TEMPLATE . '/submissionAuctionReport.php' );
			} else {
				$sOutput = file_get_contents( PATH_TEMPLATE . '/submissionReport.php' );
			}

			foreach( $aTemplateVariables as $key => $value ) {
				$sOutput = str_replace( '{' . $key . '}', $value, $sOutput );
			}

			return $sOutput;
		}
	}

	/* * *
	 * Lock submission from editing
	 * * */
	public function lock( $iSubmissionId ) {
		$aData = array(
			'submissionBillingLocked' => 'yes',
			'submissionBillingLockedDate' => date( 'Y-m-d H:i:s' ),
			'submissionBillingLockedByUserId' => ( isset($_SESSION['userId']) ? $_SESSION['userId'] : null )
		);
		$this->update( $iSubmissionId, $aData );
	}

	/* * *
	 * Unlock submission to enable editing
	 * * */
	public function unlock( $iSubmissionId ) {
		$aData = array(
			'submissionBillingLocked' => 'no',
			'submissionBillingLockedDate' => null,
			'submissionBillingLockedByUserId' => null
		);
		$this->update( $iSubmissionId, $aData );
	}

	/* * *
	 * Send the submission as email to the user it belongs to
	 * The current (admin) user is set to sender by default
	 * sendReport and sendAuctionReport are variants for the main send function
	 * * */
	public function sendReport( $iReportId, $sSender = null, $sReceiver = null, $sAdditionalMessage = null ) {
		$aReportData = current( $this->readReport('reportSubmissionId', $iReportId) );
		if( !empty($aReportData) ) {
			return $this->send( $aReportData['reportSubmissionId'], $sSender, $sReceiver, $sAdditionalMessage, 'report', $iReportId );
		}

		return false;
	}
	public function sendAuctionReport( $iReportId, $sSender = null, $sReceiver = null, $sAdditionalMessage = null ) {
		$aAuctionReportData = current( $this->readAuctionReport('reportSubmissionId', $iReportId) );
		if( !empty($aAuctionReportData) ) {
			return $this->send( $aAuctionReportData['reportSubmissionId'], $sSender, $sReceiver, $sAdditionalMessage, 'auctionReport', $iReportId );
		}

		return false;
	}
	public function send( $iSubmissionId, $sSender = null, $sReceiver = null, $sAdditionalMessage = null, $sType = 'submission', $iReportId = null ) {
		$oSubmitter = clRegistry::get( 'clSubmitter', PATH_MODULE . '/submitter/models' );

		if( empty($sSender) ) {
			// Fetch mail from current user
			$oUser = clRegistry::get( 'clUser' );
			$sSender = $oUser->readData( 'userEmail' );
		}

		// Fetch submission report no
		$iReportNo = '';
		if( $sType == 'report' ) {
			// Report
			$aReportData = valueToKey( 'submissionReportId', $this->readReport(array(
				'submissionReportId',
				'submissionReportNo'
			), null, $iSubmissionId) );

			if( empty($iReportId) ) {
				$aReportData = current( $aReportData );
				$iReportId = $aReportData['submissionReportId'];
				$iReportNo = $aReportData['submissionReportNo'];
			} else {
				if( !empty($aReportData[$iReportId]) ) {
					$iReportNo = $aReportData[$iReportId]['submissionReportNo'];
				}
			}
		} else if( $sType == 'auctionReport' ) {
			// Auction report
			if( empty($iReportId) ) {
				$aAuctionReportData = $this->readReport( 'submissionReportId', null, $iSubmissionId );
				if( !empty($aAuctionReportData) ) {
					$iReportId = current( current( $aAuctionReportData ) );
				}
			}

			if( !empty($iReportId) ) {
				$iReportNo = 'A' . $iReportId;
			}
		}

		// Fetch invoice data
		$aSubmissionData = current( parent::read( array(
			'submissionTitle',
			'submissionSubmitterId'
		), $iSubmissionId) );

		if( empty($sReceiver) ) {
			$sReceiver = current( current($oSubmitter->read('submitterEmail', $aSubmissionData['submissionSubmitterId'])) );
		}

		if( !empty($sReceiver) ) {

			// Get different content depending on type
			switch( $sType ) {
				case 'report':
					$sHtmlContent = $this->generateSubmissionReportHtml( $iReportId, true );
					break;

				case 'auctionReport':
					$sHtmlContent = $this->generateSubmissionAuctionReportHtml( $iReportId, true );
					break;

				case 'submission':
				default:
					$sHtmlContent = $this->generateSubmissionHtml( $iSubmissionId, true );
			}

			// Generate PDF file
			clFactory::loadClassFile( 'clTemplateHtml' );
			$oSubmissionTemplate = new clTemplateHtml();
			$oSubmissionTemplate->setTemplate( 'pdfSubmission.php' );
			$oSubmissionTemplate->setTitle( _( 'Submission report' ) . ' ' . $iReportNo );
			$oSubmissionTemplate->setContent( $sHtmlContent );

			$oMPdf = clRegistry::get( 'clMPdf', PATH_CORE . '/mPdf', array(
					'mode' => 'utf-8',
					'margin-left' => 11,
					'margin-right' => 11,
					'margin-top' => 12.5,
					'margin-bottom' => 0,
					'margin-header' => 0,
					'margin-footer' => 0
			) );
			$oMPdf->loadHtml( $oSubmissionTemplate->render() );
			$sFileName = _( 'Submission report' ) . '-' . $iReportNo . '-' . date( 'YmdHis' ) . '.pdf';
			$sSubmissionPdf = $oMPdf->output( $sFileName, 'S' ); // S = return as string

			// Construct the email
			$sContent = $GLOBALS['submission']['sendSubmission']['bodyHtml'];

			// Add information
			$sContent = str_replace( '{submissionNo}', $iReportNo, $sContent );
			$sContent = str_replace( '{submissionTitle}', $aSubmissionData['submissionTitle'], $sContent );

			//Add additional message if supplied
			$sAdditionalMessage = ( !empty($sAdditionalMessage) ? '<p>' . $sAdditionalMessage . '</p>' : '' );
			$sContent = str_replace( '{submissionAdditionalMessage}', $sAdditionalMessage, $sContent );

			// Attachments
			$aAttachments = array(
				0 => array(
					'name' => $sFileName,
					'content' => $sSubmissionPdf
				)
			);

			switch( $sType ) {
				case 'report':
					$aBillingLines = $this->readBillingLines( array(
						'billingLineSubmissionId' => $iSubmissionId,
						'billingLineSubmissionReportId' => $iReportId,
						'fields' => array(
							'billingLineSubmitterFeeMailPdf',
							'billingLineSubmitterFeeId'
						)
					) );
					break;

				case 'auctionReport':
					$aBillingLines = $this->readBillingLines( array(
						'billingLineSubmissionId' => $iSubmissionId,
						'billingLineSubmissionAuctionReportId' => $iReportId,
						'fields' => array(
							'billingLineSubmitterFeeMailPdf',
							'billingLineSubmitterFeeId'
						)
					) );
					break;

				case 'submission':
				default:
					$aBillingLines = $this->readBillingLines( array(
						'billingLineSubmissionId' => $iSubmissionId,
						'fields' => array(
							'billingLineSubmitterFeeMailPdf',
							'billingLineSubmitterFeeId'
						)
					) );
			}
			if( !empty($aBillingLines) ) {
				$aIncludePdfSubmitterFeeId = array();
				foreach( $aBillingLines as $aBillingLine ) {
					if( $aBillingLine['billingLineSubmitterFeeMailPdf'] == 'yes' ) {
						$aIncludePdfSubmitterFeeId[] = $aBillingLine['billingLineSubmitterFeeId'];
					}
				}
				$aIncludePdfSubmitterFeeId = array_unique( $aIncludePdfSubmitterFeeId );

				foreach( $aIncludePdfSubmitterFeeId as $iSubmitterFeeId ) {
					$sFileNameExtra = _( 'Submission report' ) . '-' . $iReportNo . '-' . date( 'YmdHis' ) . '-' . $iSubmitterFeeId . '.pdf';
					$sFilePath = PATH_CUSTOM_FILE . '/SubmitterFee/' . $iSubmitterFeeId . '.pdf';

					$aAttachments[] = array(
						'name' => $sFileNameExtra,
						'content' => file_get_contents($sFilePath)
					);
				}
			}

			// Send
			$oMailHandler = clRegistry::get( 'clMailHandler', PATH_MODULE . '/mailHandler/models' );
			$oMailHandler->send( array(
				'from' => 'Toveks auktioner <' . $sSender . '>',
				'to' => $sReceiver,
				'title' => _( 'Auktionsavräkning från Toveks Auktioner AB' ),
				'content' => $sContent,
				'attachments' => $aAttachments
			) );

			// Fetch invoice data
			$aSubmissionData = parent::update( $iSubmissionId, array(
				'submissionSentToUser' => date( 'Y-m-d H:i:s' )
			) );
		}
	}

	/* * *
	 * $mUserId, cud be given as 'array' or 'integer'
	 * * */
	public function readByUser( $mUserId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submissionUserId' => $mUserId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * $mAuctionId, cud be given as 'array' or 'integer'
	 * * */
	public function readBySubmitter( $mSubmitterId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submissionSubmitterId' => $mSubmitterId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * $mUserId, cud be given as 'array' or 'integer'
	 * * */
	public function readBySentToUser( $mUserId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submissionSentToUserId' => $mUserId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * Find submissions that are connected to a specific aution with a specific import submission id
	 * * */
	public function readByImportSubmission( $iAcutionId, $sImportSubmissionId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submissionAuctionId' => $iAcutionId,
			'submissionImportCustomId' => $sImportSubmissionId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * $mSubmissionId, cud be given as 'array' or 'integer'
	 * $mAuctionId, cud be given as 'array' or 'integer'
	 * * */
	public function readByCopyFrom( $mSubmissionId, $mAuctionId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submissionCopyFromSubmissionId' => $mSubmissionId,
			'submissionAuctionId' => $mAuctionId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * $mAuctionId, cud be given as 'array' or 'integer'
	 * * */
	public function readByAuction( $mAuctionId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submissionAuctionId' => $mAuctionId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/* * *
	 * $mSubmissionNo, cud be given as 'array' or 'integer'
	 * * */
	public function readBySubmissionNo( $mSubmissionNo, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'submissionNo' => $mSubmissionNo
		);
		return $this->oDao->readByForeignKey( $aParams );
	}



	/* * *
	 *  LINES
	 * Read line connected to the submission
	 * * */
	public function readLine( $aFields, $iLineId = null ) {
		return $this->oDao->readLine( $aFields, $iLineId );
	}

	/* * *
	 * Combined dao function for reading ) connected to the submission
	 * * */
	public function readLines( $aParams = array() ) {
		return $this->oDao->readLines( $aParams );
	}

	/* * *
	 * Create a line connected to the submission
	 * * */
	public function createLine( $aData ) {

		return $this->oDao->createLine( $aData );
	}

	/* * *
	 * Update a line connected to the submission
	 * * */
	public function updateLine( $iLineId, $aData ) {
		return $this->oDao->updateLine( $iLineId, $aData );
	}

	/* * *
	 * Delete a line connected to the submission
	 * * */
	public function deleteLine( $iLineId ) {
		return $this->oDao->deleteLine( $iLineId );
	}

	/* * *
	 * Lines marked for remember list will change back to normal line
	 * * */
	public function deleteRememberStatus( $iLineId ) {
		$aData = array(
			'lineRememberValue' => null,
			'lineRememberFeeValue' => null,
			'lineRememberSource' => null,
			'lineRememberSourceType' => null
		);

		return $this->oDao->updateLine( $iLineId, $aData );
	}

	/* * *
	 * Set the price value for a line (only auction calls) to zero
	 * * */
	public function submissionLinesSetToZero( $sLines ) {
		if( !empty($_GET['submissionLinesSetToZeroSubmission']) ) {
			$aSubmissionLines = $this->readLines( array(
				'lineSubmissionId' => $_GET['submissionLinesSetToZeroSubmission'],
				'fields' => array(
					'lineId',
				  'lineTitle',
					'lineSortNo',
					'lineValue',
				  'lineVatValue',
					'lineAuctionItemId',
					'lineSubmissionId'
				)
			) );

			if( !empty($aSubmissionLines) ) {

				// Get locked info for connected submissions
				$aSubmissionLocked = arrayToSingle( $this->read( array(
					'submissionId',
					'submissionBillingLocked'
				), arrayToSingle($aSubmissionLines, null, 'lineSubmissionId') ), 'submissionId', 'submissionBillingLocked' );

				// Split the text string into ids
				$aTempLines = str_replace( ' ', '', $sLines );
				$aTempLines = explode( ',', $sLines );
				$aLines = array();
				$aItems = array();

				foreach( $aTempLines as $sTempLine ) {
					if( ctype_digit($sTempLine) ) {
						$aLines[] = $sTempLine;
					} else if( stristr($sLines, '-') ) {

						// Interval
						list( $iIntervalMin, $iIntervalMax ) = explode( '-', $sTempLine );

						if( ctype_digit($iIntervalMin) && ctype_digit($iIntervalMax) ) {
							for( $i=$iIntervalMin; $i<=$iIntervalMax; $i++ ) {
								$aLines[] = $i;
							}
						}
					}
				}

				if( !empty($aLines) ) {

					// Get invoice values to for eventual crediting
					$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
				  $aInvoiceLineByItems = valueToKey( 'invoiceLineItemId', $oInvoiceEngine->readByItem_in_InvoiceLine( arrayToSingle($aSubmissionLines, null, 'lineAuctionItemId'), array(
				    'invoiceLineItemId',
				    'invoiceLinePrice',
						'invoiceLineFee'
				  ) ) );

					foreach( $aSubmissionLines as $entry ) {
						if( !empty($entry['lineAuctionItemId']) && in_array($entry['lineSortNo'], $aLines) ) {

							if( !empty($aInvoiceLineByItems[ $entry['lineAuctionItemId'] ]) ) {
								$fPrice = $aInvoiceLineByItems[ $entry['lineAuctionItemId'] ]['invoiceLinePrice'];
								$fFee = $aInvoiceLineByItems[ $entry['lineAuctionItemId'] ]['invoiceLineFee'];
							} else {
								$fPrice = null;
								$fFee = null;
							}

							if( $aSubmissionLocked[ $entry['lineSubmissionId'] ] == 'yes' ) {
								// Submission locked  - make a new line
								// Changed 2017-12-06 to DO NOTHING - locked submissions shall not be altered at all

				        /*$aNewLineData = array(
				          'lineSubmissionId' => 0,
									'lineSortNo' => null,
				          'lineValue' => null,
				          'lineRememberValue' => $entry['lineValue'],
				          'lineRememberFeeValue' => $fFee
				        ) + $entry;

								unset( $aNewLineData['lineId'] );

								$this->createLine( $aNewLineData );*/

							} else {
								// Submission unlocked - change the line

								if( $entry['lineValue'] > 0 ) {
									$this->updateLine( $entry['lineId'], array(
										'lineValue' => 0,
										'lineRememberValue' => $entry['lineValue'],
										'lineRememberFeeValue' => $fFee,
										'lineRememberSource' => 'zeroed'
									) );
								}

							}

						}
					}
				}
			}
		}
	}


	/* * *
	 *  BILLING LINES
	 * Read billing line connected to the submission
	 * * */
	public function readBillingLine( $aFields, $iBillingLineId = null ) {
		return $this->oDao->readBillingLine( $aFields, $iBillingLineId );
	}

	/* * *
	 * Combined dao function for reading billing )
	 * * */
	public function readBillingLines( $aParams = array() ) {
		return $this->oDao->readBillingLines( $aParams );
	}

	/* * *
	 * Create a billing line connected to the submission
	 * * */
	public function createBillingLine( $aData ) {
		return $this->oDao->createBillingLine( $aData );
	}

	/* * *
	 * Delete a billing line connected to the submission
	 * * */
	public function deleteBillingLine( $iBillingLineId ) {
		return $this->oDao->deleteBillingLine( $iBillingLineId );
	}

	/* * *
	 * Update a billing line connected to the submission
	 * * */
	public function updateBillingLine( $iBillingLine, $aData ) {
		return $this->oDao->updateBillingLine( $iBillingLine, $aData );
	}


	/* * *
	 * REPORT and AUCTION REPORT
	 * Shared functions
	 * Create a reoprts from submission id
	 * $mForce forces a creation of reports even if the submission don't have lines can be false | 'report' | 'auctionReport' | 'both'
	 * $mForce can also be set via  $_GET['createReportFromSubmissionForce']
	 * * */
	public function createReportFromSubmission( $iSubmissionId, $mForce = false ) {

		if( $mForce === false ) {
			if( !empty($_GET['createReportFromSubmissionForce']) ) {
				$mForce = $_GET['createReportFromSubmissionForce'];
			}
		}

		// Fetch existing report data
		$aReport = $this->readReport( 'submissionReportId', null, $iSubmissionId ) + $this->readAuctionReport( 'submissionAuctionReportId', null, $iSubmissionId );

		if( empty($aReport) ) {

			// Fetch submission data
			$aSubmission = current( $this->read( array(
				'submissionId',
				'submissionAuctionId',
				'submissionCommissionFee',
				'submissionMarketingFee',
				'submissionMarketingFeeSum'
			), $iSubmissionId ) );

			if( !empty($aSubmission) ) {
				$bAuctionEnded = true;

				$fSubmissionCommissionFee = $aSubmission['submissionCommissionFee'];

				if( !empty($aSubmission['submissionMarketingFeeSum']) && ($aSubmission['submissionMarketingFeeSum'] > 0) ) {
					$fSubmissionMarketingFee = $aSubmission['submissionMarketingFeeSum'];
					$sSubmissionMarketingFeeType = 'sek';
				} else {
					$fSubmissionMarketingFee = $aSubmission['submissionMarketingFee'];
					$sSubmissionMarketingFeeType = 'percent';
				}

				$aReportLines = array();
				if( !empty($aSubmission['submissionAuctionId']) ) {
					// Fetch auction data (if connection exists).
					// All auction parts must be ended for the report to be creted
					$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
					$aAuctionParts = arrayToSingle( $oAuctionEngine->readAuction( array(
						'fields' => array(
							'partId',
							'partStatus',
						),
						'auctionId' => $aSubmission['submissionAuctionId'],
						'auctionStatus' => '*',
						'partStatus' => '*',
					) ), 'partId', 'partStatus' );

					foreach( $aAuctionParts as $sPartStatus ) {
						if( $sPartStatus != 'ended' ) {
							$bAuctionEnded = false;
						}
					}

					// Fetch all submission lines, check for vat. Separate the lines depending on vat
					$aLines = $this->readLines( array(
						'lineSubmissionId' => $iSubmissionId,
						'fields' => array(
							'lineId',
							'lineVatValue'
						)
					) );

					if( !empty($aLines) ) {
						foreach( $aLines as $aLine ) {
							$aReportLines[ $aLine['lineVatValue'] ][] = $aLine['lineId'];
						}
					}
				}

				// Create reports. One per vat value (0 = report, other = auctionReport)
				if( !empty($aReportLines) ) {

					foreach( $aReportLines as $iVat => $aLines ) {

						if( $iVat > 0 ) {
							// Create report
							if( $bAuctionEnded === true ) {
								$iSubmissionReportId = $this->createReport( array(
									'submissionReportCreated' => date( 'Y-m-d H:i:s' ),
									'submissionReportCommissionFee' => $fSubmissionCommissionFee,
									'submissionReportMarketingFeeType' => $sSubmissionMarketingFeeType,
									'submissionReportMarketingFeeValue' => $fSubmissionMarketingFee,
									'reportSubmissionId' => $aSubmission['submissionId'],
									'reportAuctionId' => $aSubmission['submissionAuctionId']
								) );

								foreach( $aLines as $iLineId ) {
									$this->updateLine( $iLineId, array(
										'lineReportId' => $iSubmissionReportId
									) );
								}
							}

						} else {
							// Create auction report
							if( $bAuctionEnded === true ) {
								$iSubmissionAuctionReportId = $this->createAuctionReport( array(
									'submissionAuctionReportCreated' => date( 'Y-m-d H:i:s' ),
									'submissionAuctionReportCommissionFee' => $fSubmissionCommissionFee,
									'submissionAuctionReportMarketingFeeType' => $sSubmissionMarketingFeeType,
									'submissionAuctionReportMarketingFeeValue' => $fSubmissionMarketingFee,
									'reportSubmissionId' => $aSubmission['submissionId'],
									'reportAuctionId' => $aSubmission['submissionAuctionId']
								) );

								foreach( $aLines as $iLineId ) {
									$this->updateLine( $iLineId, array(
										'lineAuctionReportId' => $iSubmissionAuctionReportId
									) );
								}
							}
						}
					}

				} else if( ($mForce != false) && ($bAuctionEnded === true) ) {
					if( ($mForce == 'report') || ($mForce == 'both' ) ) {
						$iSubmissionReportId = $this->createReport( array(
							'submissionReportCreated' => date( 'Y-m-d H:i:s' ),
							'submissionReportCommissionFee' => $fSubmissionCommissionFee,
							'submissionReportMarketingFeeType' => $sSubmissionMarketingFeeType,
							'submissionReportMarketingFeeValue' => $fSubmissionMarketingFee,
							'reportSubmissionId' => $aSubmission['submissionId'],
							'reportAuctionId' => $aSubmission['submissionAuctionId']
						) );
					}

					if( ($mForce == 'auctionReport') || ($mForce == 'both' ) ) {
						$iSubmissionAuctionReportId = $this->createAuctionReport( array(
							'submissionAuctionReportCreated' => date( 'Y-m-d H:i:s' ),
							'submissionAuctionReportCommissionFee' => $fSubmissionCommissionFee,
							'submissionAuctionReportMarketingFeeType' => $sSubmissionMarketingFeeType,
							'submissionAuctionReportMarketingFeeValue' => $fSubmissionMarketingFee,
							'reportSubmissionId' => $aSubmission['submissionId'],
							'reportAuctionId' => $aSubmission['submissionAuctionId']
						) );
					}
				}

				return $bAuctionEnded;
			} else {
				// Submission doesn't exist
				return false;
			}
		} else {
			// Submission report already exist
			return false;
		}
	}

	/* * *
	 * Create reports for all submissions in auction
	 * * */
	public function createReportFromAuction( $iAuctionId ) {
		if( $iAuctionId !== 0 ) {
			$aSubmissions = $this->oDao->readByForeignKey( array(
				'fields' =>'submissionId',
				'submissionAuctionId' => $iAuctionId
			) );

			if( !empty($aSubmissions) ) {
				foreach( $aSubmissions as $aSubmission ) {
					$this->createReportFromSubmission( $aSubmission['submissionId'] );
				}
			}
		}
	}


	/* * *
	 * REPORT
	 * Read submission report connected to the submission
	 * * */
	public function readReport( $aFields, $iReportId = null, $iSubmissionId = null ) {
		return $this->oDao->readReport( $aFields, $iReportId, $iSubmissionId );
	}

	 public function readReportByNo( $iReportNo, $aFields = null ) {
		 return $this->oDao->readReportByNo( $iReportNo, $aFields );
	 }

	/* * *
	 * Create a submission report
	 * * */
	public function createReport( $aData ) {
		return $this->oDao->createReport( $aData );
	}

	/* * *
	 * Delete a submission report
	 * * */
	public function deleteReport( $iReportId ) {
		return $this->oDao->deleteReport( $iReportId );
	}

	/* * *
	 * Update a submission report
	 * * */
	public function updateReport( $iReportId, $aData ) {
		return $this->oDao->updateReport( $iReportId, $aData );
	}


	/* * *
	 * AUCTION REPORT
	 * Read submission auction report connected to the submission
	 * * */
	public function readAuctionReport( $aFields, $iReportId = null, $iSubmissionId = null ) {
		return $this->oDao->readAuctionReport( $aFields, $iReportId, $iSubmissionId );
	}

	/* * *
	 * Create a submission auction report
	 * * */
	public function createAuctionReport( $aData ) {
		return $this->oDao->createAuctionReport( $aData );
	}

	/* * *
	 * Delete a submission auction report
	 * * */
	public function deleteAuctionReport( $iReportId ) {
		return $this->oDao->deleteAuctionReport( $iReportId );
	}

	/* * *
	 * Update a submission auction report
	 * * */
	public function updateAuctionReport( $iReportId, $aData ) {
		return $this->oDao->updateAuctionReport( $iReportId, $aData );
	}


	/* * *
	 * Create invoices from a finished auction
	 * Part id is mandatory
	 * * */
	public function createSubmissionLinesFromAuction( $iAuctionId, $iAuctionPartId ) {
		$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

		// Get auction data
		$aAuctionData = $oAuctionEngine->readAuction( array(
			'auctionId' => $iAuctionId,
			'partId' => $iAuctionPartId,
			'auctionStatus' => '*',
			'partStatus' => '*'
		) );
		$aPartData = array();
		foreach( $aAuctionData as $entry ) {
			$aPartData[$entry['partId']] = $entry;
		}

		$aAuctionPartId = (array) $iAuctionPartId;

		if( !empty($aPartData) ) {
			// Loop through parts and collect submission line data
			$aPartItems = $oAuctionEngine->readWithVehicleData_in_AuctionItem( array(
				'fields' => array(
					'itemId',
					'itemSortNo',
					'itemSortLetter',
					'itemTitle',
					'itemWinningBidId',
					'itemWinningUserId',
					'itemWinningBidValue',
					'itemVatValue',
					'itemRecalled',
					'itemSubmissionId',
					'vehicleLicencePlate'
				),
				'itemAuctionId' => $iAuctionId,
				'itemPartId' => $aAuctionPartId
			) );

			if( !empty($aPartItems) ) {
				// Loop through the items and create submission lines
				foreach( $aPartItems as $entry ) {

					$fWinningBid = 0;
					if( !empty($entry['itemWinningUserId']) ) {
						// $aWinningBid = current( $oAuctionEngine->read( 'AuctionBid', array(
						// 	'bidValue',
						// 	'bidUserId'
						// ), $entry['itemWinningBidId']) );

						// Temporary change to get bid without entAuctionBid
						$aWinningBid = array(
							'bidUserId' => $entry['itemWinningUserId'],
							'bidValue' => $entry['itemWinningBidValue']
						);

						$fWinningBid = $aWinningBid['bidValue'];
					}

					$aData = array(
						'lineTitle' => $entry['itemTitle'] . ( !empty($entry['vehicleLicencePlate']) ? ' (' . $entry['vehicleLicencePlate'] . ')' : '' ),
						'lineSortNo' => $entry['itemSortNo'],
						'lineSortLetter' => $entry['itemSortLetter'],
						'lineValue' => $fWinningBid,
						'lineVatValue' => $entry['itemVatValue'],
						'lineRecalled' => $entry['itemRecalled'],
						'lineAuctionItemId' => $entry['itemId'],
						'lineSubmissionId' => $entry['itemSubmissionId']
					);


          $aItemSubmissionLineData = $this->readLines( array(
            'fields' => 'lineId',
            'lineAuctionItemId' => $entry['itemId']
          ) );
					if( !empty($aItemSubmissionLineData) ) {
						$aItemSubmissionLineId = current( current($aItemSubmissionLineData) );

						if( ctype_digit($aItemSubmissionLineId) ) {
							$this->oDao->updateLine( $aItemSubmissionLineId, $aData );
						} else {
							$this->oDao->createLine( $aData );
						}
					} else {
						$this->oDao->createLine( $aData );
					}
				}

				return true;
			} else {
				// Error report
				return false;
			}
		} else {
			// Error report - No auction part data (probably due to the part being ended already)
			return false;
		}
	}


	/* Old html version kept for historical reasons to show old reports as before */
	public function generateSubmissionHtmlOld( $iSubmissionId, $bPdf = false, $bIntResult = false, $bOnlyFirstPage = false ) {
		clFactory::loadClassFile( 'clOutputHtmlTable' );
		$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
		$oSubmissionComments = clRegistry::get( 'clSubmissionComments', PATH_MODULE . '/submission/models' );

		$sOutput = '';
		$aIntResult = array(
			'billingLines' => array(
				'preVat' => array(),
				'postVat' => array(),
				'preVatSum' => 0,
				'postVatSum' => 0
			),
			'lineCount' => 0,
			'lineSoldCount' => 0
		);

		$aDataDict = $this->oDao->getDataDict();

		// First clear criterias and save it to reset in the end
		$aSubmissionCriterias = $this->oDao->sCriterias;
		$this->oDao->sCriterias = null;

		// Get the information for the footer
		$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
		$sFooter = (($aFooter = $oInfoContent->read('contentTextId', 28)) ? $aFooter[0]['contentTextId'] : null );

		// Get invoice data
		$aSubmissionData = current( $this->read(null, $iSubmissionId) );

		// Fetch auction data
		if( !empty($aSubmissionData['submissionAuctionId']) ) {
			$aAuctionData = $oAuctionEngine->readAuction( array(
				'fields' => 'partAuctionStart',
				'auctionId' => $aSubmissionData['submissionAuctionId'],
				'auctionStatus' => '*',
				'partStatus' => '*'
			) );
			$aAuctionData = end( $aAuctionData );
		} else {
			// Inaktiverat för framtida behov
			/*$aTitleWords = explode( ' ', $aSubmissionData['submissionTitle'] );
			$sTitleDate = '20' . end($aTitleWords);
			if( ctype_digit($sTitleDate) ) {
				$aAuctionData = array(
					'partAuctionStart' => date( 'Y-m-d', strtotime($sTitleDate) )
				);
			}*/
		}

		// Define variables to use for summation
		$fTotalSum = 0;
		$fVatSum = 0;

		if( !empty($aSubmissionData) ) {

			// Fetch submitter data
			$oSubmitter = clRegistry::get( 'clSubmitter', PATH_MODULE . '/submitter/models' );
			$aSubmitterData = current( $oSubmitter->read(array(
				'submitterId',
				'submitterNo',
				'submitterPin',
				'submitterType'
			), $aSubmissionData['submissionSubmitterId']) );

			// Fetch submission lines
			$aSubmitterLines = $this->oDao->readLines( array(
				'lineSubmissionId' => $iSubmissionId,
				'fields' => array(
					'lineId',
					'lineSortNo',
					'lineTitle',
					'lineValue',
					'lineVatValue',
					'lineRecalled',
					'lineAuctionItemId'
				)
			) );
			$aIntResult['lineCount'] = count( $aSubmitterLines );

			// Fetch item data
			$aItemData = array();
			$aItems = arrayToSingle( $aSubmitterLines, null, 'lineAuctionItemId' );
			if( !empty($aItems) ) {
				$aItemData = valueToKey( 'itemId', $oAuctionEngine->read('AuctionItem', array(
					'itemId',
					'itemFeeType',
					'itemFeeValue'
				), $aItems) );
			}

			$aReportData = current( $this->readReport(array(
				'submissionReportId',
				'submissionReportNo',
				'submissionReportCreated'
			), null, $iSubmissionId) );

			// Date for submission/report
			if( !empty($aAuctionData['partAuctionStart']) ) {
				$sSetOffTime = strtotime( $aAuctionData['partAuctionStart'] ) + 604800;
			} else if( isset($aReportData['submissionReportCreated']) ) {
				$aAuctionData = array(
					'partAuctionStart' => $aReportData['submissionReportCreated']
				);
				$sSetOffTime = strtotime( $aAuctionData['partAuctionStart'] );
			} else {
				$aAuctionData = array(
					'partAuctionStart' => date( 'Y-m-d' )
				);
				$sSetOffTime = strtotime( $aAuctionData['partAuctionStart'] );
			}

			// Override the calculated set-off time if set
			if( !empty($aSubmissionData['submissionSettlementDate']) && ($aSubmissionData['submissionSettlementDate'] != '0000-00-00') ) {
				$sSetOffTime = strtotime( $aSubmissionData['submissionSettlementDate'] );
			}

			if( !empty($aSubmitterData['submitterPin']) ) {
				$aSubmitterData['submitterPin'] = substr( $aSubmitterData['submitterPin'], 0, 6 ) . '-' . substr( $aSubmitterData['submitterPin'], 6 );
			}

			// Fetch comments for the submission
			$aSubmissionComments = $oSubmissionComments->readByRelation( 'submission', $iSubmissionId );
			$aComments = array();
			foreach( $aSubmissionComments as $aComment ) {
				if( isset($aComments[ $aComment['commentRelationField'] ]) ) {
					$aComments[ $aComment['commentRelationField'] ]++;
				} else {
					$aComments[ $aComment['commentRelationField'] ] = 1;
				}
			}

			$sOutput .= '
				<section class="submission' . ( $bPdf ? '' : ' preview' ) . ' pre2017">
					<header>
						<div class="logo" style="width: 30%; float: left;"><img src="' . ( $bPdf ? PATH_PUBLIC : '' ) . '/images/templates/argoWhite/pdf-logo.png" width="100%" /></div>
						<div class="billingInfo" style="width: 65%; float: right;">
							<table class="dataTable">
								<thead>
									<tr>
										<th>' . _( 'Set-off' ) . '</th>
										<th>' . _( 'Submitter' ) . '</th>
										<th>' . _( 'Personal/Company pin' ) . '</th>
										<th>' . _( 'Självfakturanummer' ) . '</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>' . date( 'Y-m-d', $sSetOffTime ) . '</td>
										<td>' . ( !empty($aSubmitterData['submitterNo']) ? $aSubmitterData['submitterNo'] : '' ) . '</td>
										<td class="commentingArea' . ( !empty($aComments['submitterPin']) ? ' gotComments' : '' ) . '" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submitterPin">' . $aSubmitterData['submitterPin'] . '</td>
										<td>' . ( !empty($aReportData['submissionReportNo']) ? $aReportData['submissionReportNo'] : '' ) . '</td>
									</tr>
								</tbody>
							</table>
							<div class="address commentingArea' . ( !empty($aComments['submitterAddress']) ? ' gotComments' : '' ) . '" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submitterAddress" style="width: 50%; float: right;">
								' . $aSubmissionData['submissionCompanyName'] . '<br />
								' . $aSubmissionData['submissionFirstname'] . ' ' . $aSubmissionData['submissionSurname'] . '<br />
								' . $aSubmissionData['submissionAddress'] . '<br />
								' . $aSubmissionData['submissionZipCode'] . ' ' . $aSubmissionData['submissionCity'] . '
							</div>
						</div>
					</header>
					<div class="content">
						' .( ($aSubmissionData['submissionMoreGoodsAvailable'] == 'no') ? '<br><em class="commentingArea' . ( !empty($aComments['submissionMoreGoodsAvailable']) ? ' gotComments' : '' ) . '" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submissionMoreGoodsAvailable">' . _( 'Alla varor är sålda' ) . '</em>' : '' ) . '
						<table class="dataTable">
							<thead>
								<tr>
									<th>' . _( 'Referral' ) . '</th>
									<th colspan="2">' . _( ' Your referral' ) . '</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="submissionTitle commentingArea' . ( !empty($aComments['submissionTitle']) ? ' gotComments' : '' ) . '" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submissionTitle">
										' . _( 'Report of auction in' ) . ' '  . $aSubmissionData['submissionTitle'] . '
									</td>
									<td class="submissionReferral commentingArea' . ( !empty($aComments['submissionReferral']) ? ' gotComments' : '' ) . '" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submissionReferral">' . $aSubmissionData['submissionReferral'] . '</td>
									<td class="submissionInfo">' . count($aSubmitterLines) . ' ' . _( 'number of calls according to the auction list' ) . '</td>
								</tr>
							</tbody>
						</table>';

			$fLinesBidSum = 0;
			$fLinesTotalWithoutVatSum25 = 0;
			$fLinesTotalWithoutVatSum12 = 0;
			$fLinesTotalWithoutVatSum0 = 0;
			$fLinesTotalVat = 0;
			$fLinesTotalVat25 = 0;
			$fLinesTotalVat12 = 0;
			$fLinesRecallSum = 0;
			$fLinesFeeSum = 0;

			foreach( $aSubmitterLines as $entry ) {
				$fLineVatValue = ( !empty($entry['lineVatValue']) ? $entry['lineVatValue'] : 0 );

				$fLinesBidSum += $entry['lineValue'];

				if( $entry['lineRecalled'] != 'yes' ) {
					// Count sold items
					if( $entry['lineValue'] > 0 ) {
						$aIntResult['lineSoldCount']++;
					}

					$fThisItemVat = $entry['lineValue'] * $fLineVatValue / 100;

					$fLinesTotalVat += $fThisItemVat;

					// Split sums (VAT and Total)
					switch( $fLineVatValue ) {
						case 25:
							$fLinesTotalWithoutVatSum25 += $entry['lineValue'];
							$fLinesTotalVat25 += $fThisItemVat;
							break;

						case 12:
							$fLinesTotalWithoutVatSum12 += $entry['lineValue'];
							$fLinesTotalVat12 += $fThisItemVat;
							break;

						case 0:
						default:
							$fLinesTotalWithoutVatSum0 += $entry['lineValue'];

					}

					// Calculate fees (feeType 'none' doesn't do anything)
					// This should only apply on sold object (value > 0)
					if( !empty($aItemData[$entry['lineAuctionItemId']]) && ($entry['lineValue'] > 0) ) {
						switch( $aItemData[$entry['lineAuctionItemId']]['itemFeeType'] ) {
							case 'sek':
								$fLinesFeeSum += $aItemData[$entry['lineAuctionItemId']]['itemFeeValue'];
								break;

							case 'percent':
								$fLinesFeeSum += ( $entry['lineValue'] * $aItemData[$entry['lineAuctionItemId']]['itemFeeValue'] / 100 );
								break;
						}
					}
				} else {
					$fLinesRecallSum += $entry['lineValue'];
				}
			}
			$fLinesTotalWithoutVatSum = $fLinesBidSum - $fLinesRecallSum;
			$fLinesTotalSum = $fLinesTotalWithoutVatSum + $fLinesTotalVat;

			// Store the values in return array (returned if bIntResult is true)
			$aIntResult += array(
				'linesBidSum' 							=> $fLinesBidSum,
				'linesRecallSum' 						=> $fLinesRecallSum,
				'linesTotalWithoutVatSum' 	=> $fLinesTotalWithoutVatSum,
				'linesTotalWithoutVatSum25' => $fLinesTotalWithoutVatSum25,
				'linesTotalWithoutVatSum12' => $fLinesTotalWithoutVatSum12,
				'linesTotalWithoutVatSum0' 	=> $fLinesTotalWithoutVatSum0,
				'linesTotalVat' 						=> $fLinesTotalVat,
				'linesTotalVat25' 					=> $fLinesTotalVat25,
				'linesTotalVat12' 					=> $fLinesTotalVat12,
				'linesTotalSum' 						=> $fLinesTotalSum,
				'linesTotalFee' 						=> $fLinesFeeSum
			);

			$sOutput .= '
						<table class="dataTable submissionBillingCalculation">
							<tbody>
								<tr>
									<td class="col1" width="50%">&nbsp;</td>
									<td class="col2" width="25%" align="right">&nbsp;</td>
									<td class="col3" width="20%">' . _( 'SEK' ) . '</td>
									<td class="col4" width="15%" align="right">' . calculatePrice( $fLinesBidSum, array('decimals' => 2) ) . '</td>
								</tr>
								<tr>
									<td class="col1" width="50%">&nbsp;</td>
									<td class="col2" width="25%" align="right">&nbsp;</td>
									<td class="col3" width="20%">' . _( 'Recalled items' ) . '</td>
									<td class="col4" width="15%" align="right">' . calculatePrice( $fLinesRecallSum, array('decimals' => 2) ) . '</td>
								</tr>
								<tr>
									<td class="col1" width="50%"></td>
									<td class="col2" width="25%" align="right"></td>
									<td class="col3" width="20%" style="border-top: 1px solid #ccc"></td>
									<td class="col4" width="15%" align="right" style="border-top: 1px solid #ccc"></td>
								</tr>
								' . ( ($fLinesTotalWithoutVatSum25 != 0) ? '
									<tr>
										<td class="col1" width="50%">&nbsp;</td>
										<td class="col2" width="25%" align="right">&nbsp;</td>
										<td class="col3" width="20%">' . _( 'Sum' ) . ' (25%)</td>
										<td class="col4" width="15%" align="right">' . calculatePrice( $fLinesTotalWithoutVatSum25, array('decimals' => 2) ) . '</td>
								 </tr>
								' : '' ) . '
								' . ( ($fLinesTotalWithoutVatSum12 != 0) ? '
									<tr>
										<td class="col1" width="50%">&nbsp;</td>
										<td class="col2" width="25%" align="right">&nbsp;</td>
										<td class="col3" width="20%">' . _( 'Sum' ) . ' (12%)</td>
										<td class="col4" width="15%" align="right">' . calculatePrice( $fLinesTotalWithoutVatSum12, array('decimals' => 2) ) . '</td>
									</tr>
								' : '' ) . '
								' . ( ($fLinesTotalWithoutVatSum0 != 0) ? '
									<tr>
										<td class="col1" width="50%">&nbsp;</td>
										<td class="col2" width="25%" align="right">&nbsp;</td>
										<td class="col3" width="20%">' . _( 'Sum' ) . ' (0%)</td>
										<td class="col4" width="15%" align="right">' . calculatePrice( $fLinesTotalWithoutVatSum0, array('decimals' => 2) ) . '</td>
									</tr>
								' : '' ) . '
								<tr>
									<td class="col1" width="50%"></td>
									<td class="col2" width="25%" align="right"></td>
									<td class="col3" width="20%" style="border-top: 1px solid #ccc"></td>
									<td class="col4" width="15%" align="right" style="border-top: 1px solid #ccc"></td>
								</tr>
								' . ( ($fLinesTotalVat25 != 0) ? '
									<tr>
										<td class="col1" width="50%">&nbsp;</td>
										<td class="col2" width="25%" align="right">&nbsp;</td>
										<td class="col3" width="20%">' . _( 'VAT' ) . ' 25%</td>
										<td class="col4" width="15%" align="right">' . calculatePrice( $fLinesTotalVat25, array('decimals' => 2) ) . '</td>
									</tr>
								' : '' ) . '
								' . ( ($fLinesTotalVat12 != 0) ? '
									<tr>
										<td class="col1" width="50%">&nbsp;</td>
										<td class="col2" width="25%" align="right">&nbsp;</td>
										<td class="col3" width="20%">' . _( 'VAT' ) . ' 12%</td>
										<td class="col4" width="15%" align="right">' . calculatePrice( $fLinesTotalVat12, array('decimals' => 2) ) . '</td>
									</tr>
								' : '' ) . '
								<tr>
									<td class="col1" width="50%">&nbsp;</td>
									<td class="col2" width="25%" align="right">&nbsp;</td>
									<td class="col3" width="20%" style="border-top: 1px solid #ccc">&nbsp;</td>
									<td class="col4" width="15%" align="right" style="border-top: 1px solid #ccc">' . calculatePrice( $fLinesTotalSum, array('decimals' => 2) ) . '</td>
								</tr>';

			$fCommissionValue = $fLinesTotalWithoutVatSum * $aSubmissionData['submissionCommissionFee'] / 100;
			$fMarketingValue = $fLinesBidSum * $aSubmissionData['submissionMarketingFee'] / 100;
			$fRecallValue = $fLinesRecallSum * $aSubmissionData['submissionRecallFee'] / 100;
			$fBillingFee = ( ($aSubmissionData['submissionPaymentToType'] == 'cash') ? $aSubmissionData['submissionBillingFee'] : 0 );
			$fCostsTotalSum = $fCommissionValue + $fMarketingValue + $fRecallValue + $fBillingFee;

			// Store the values in return array (returned if bIntResult is true)
			$aIntResult += array(
				'commissionFeeValue'	=> $fCommissionValue,
				'marketingFeeValue' 	=> $fMarketingValue,
				'recallFeeValue' 			=> $fRecallValue,
				'billingFee' 					=> $fBillingFee,
				'feeSum' 							=> $fCostsTotalSum
			);

			$sOutput .= '
								<tr>
									<td class="col1 commentingArea' . ( !empty($aComments['submissionCommissionFee']) ? ' gotComments' : '' ) . '" width="50%" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submissionCommissionFee">' . $aSubmissionData['submissionCommissionFee'] . ' % ' . _( 'commission' ) . '</td>
									<td class="col2" width="25%" align="right">' . calculatePrice( $fCommissionValue, array('decimals' => 2) ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>
								<tr>
									<td class="col1 commentingArea' . ( !empty($aComments['submissionMarketingFee']) ? ' gotComments' : '' ) . '" width="50%" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submissionMarketingFee">' . $aSubmissionData['submissionMarketingFee'] . ' % ' . _( 'marketing fee' ) . '</td>
									<td class="col2" width="25%" align="right">' . calculatePrice( $fMarketingValue, array('decimals' => 2) ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>
								<tr>
									<td class="col1 commentingArea' . ( !empty($aComments['submissionRecallFee']) ? ' gotComments' : '' ) . '" width="50%" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submissionRecallFee">' . $aSubmissionData['submissionRecallFee'] . ' % ' . _( 'part in ads when recalled' ) . '</td>
									<td class="col2" width="25%" align="right">' . calculatePrice( $fRecallValue, array('decimals' => 2) ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>';
			if( $aSubmissionData['submissionPaymentToType'] == 'cash' ) {
				$sOutput .= '
								<tr>
									<td class="col1 commentingArea' . ( !empty($aComments['submissionBillingNoteFee']) ? ' gotComments' : '' ) . '" width="50%" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submissionBillingNoteFee">' . _( 'Billing note fee' ) . '</td>
									<td class="col2" width="25%" align="right">' . calculatePrice( $fBillingFee, array('decimals' => 2) ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>';
			}

			$sOutput .= '
								<tr>
									<td colspan="4">&nbsp;</td>
								</tr>';

			$aSubmissionPreVatLines = $this->oDao->readBillingLines( array(
				'billingLineSubmissionId' => $iSubmissionId,
				'billingLineType' => 'preVat',
				'fields' => array(
					'billingLineId',
					'billingLineTitle',
					'billingLineValue'
				)
			) );

			foreach( $aSubmissionPreVatLines as $aPreVatLines ) {

				// Fetch comments for the billing line
				$aBillingLineComments = $oSubmissionComments->readByRelation( 'submissionBillingLine', $aPreVatLines['billingLineId'] );
				$aBLComments = array();
				foreach( $aBillingLineComments as $aComment ) {
					if( isset($aComments[ $aComment['commentRelationField'] ]) ) {
						$aBLComments[ $aComment['commentRelationField'] ]++;
					} else {
						$aBLComments[ $aComment['commentRelationField'] ] = 1;
					}
				}

				$sOutput .= '
								<tr>
									<td class="col1 commentingArea' . ( !empty($aBLComments['billingLineTitle']) ? ' gotComments' : '' ) . '" width="50%" data-relation-type="submissionBillingLine" data-relation-id="' . $aPreVatLines['billingLineId'] . '" data-relation-field="billingLineTitle">' . $aPreVatLines['billingLineTitle'] . '</td>
									<td class="col2 commentingArea' . ( !empty($aBLComments['billingLineValue']) ? ' gotComments' : '' ) . '" width="25%" align="right" data-relation-type="submissionBillingLine" data-relation-id="' . $aPreVatLines['billingLineId'] . '" data-relation-field="billingLineValue">' . calculatePrice( $aPreVatLines['billingLineValue'], array('decimals' => 2) ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>';

				$fCostsTotalSum += $aPreVatLines['billingLineValue'];

				// Store the values in return array (returned if bIntResult is true)
				$aIntResult['billingLines']['preVat'][] = array(
					'title'	=> $aPreVatLines['billingLineTitle'],
					'value' => $aPreVatLines['billingLineValue']
				);
				$aIntResult['billingLines']['preVatSum'] += $aPreVatLines['billingLineValue'];
			}

			$fCostsTotalVat = $fCostsTotalSum * SUBMISSION_VAT / 100;

			// Store the values in return array (returned if bIntResult is true)
			$aIntResult += array(
				'costsVat'	=> $fCostsTotalVat
			);

			$sOutput .= '
								<tr>
									<td class="col1" width="50%" style="border-top: 1px solid #ccc">&nbsp;</td>
									<td class="col2" width="25%" align="right" style="border-top: 1px solid #ccc">' . calculatePrice( $fCostsTotalSum, array('decimals' => 2) ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>
								<tr>
									<td colspan="4">&nbsp;</td>
								</tr>
								<tr>
									<td class="col1" width="50%">' . _( 'VAT' ) . ' ' . SUBMISSION_VAT . '%</td>
									<td class="col2" width="25%" align="right">' . calculatePrice( $fCostsTotalVat, array('decimals' => 2) ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>';

			$fCostsTotalSum += $fCostsTotalVat;
			$sOutput .= '
								<tr>
									<td class="col1" width="50%" style="border-top: 1px solid #ccc">&nbsp;</td>
									<td class="col2" width="25%" align="right" style="border-top: 1px solid #ccc">' . calculatePrice( $fCostsTotalSum, array('decimals' => 2) ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>
								<tr>
									<td colspan="4">&nbsp;</td>
								</tr>';

			$aSubmissionPostVatLines = $this->oDao->readBillingLines( array(
				'billingLineSubmissionId' => $iSubmissionId,
				'billingLineType' => 'postVat',
				'fields' => array(
					'billingLineId',
					'billingLineTitle',
					'billingLineValue'
				)
			) );
			foreach( $aSubmissionPostVatLines as $aPostVatLines ) {

				// Fetch comments for the billing line
				$aBillingLineComments = $oSubmissionComments->readByRelation( 'submissionBillingLine', $aPostVatLines['billingLineId'] );
				$aBLComments = array();
				foreach( $aBillingLineComments as $aComment ) {
					if( isset($aComments[ $aComment['commentRelationField'] ]) ) {
						$aBLComments[ $aComment['commentRelationField'] ]++;
					} else {
						$aBLComments[ $aComment['commentRelationField'] ] = 1;
					}
				}

				$sOutput .= '
								<tr>
									<td class="col1 commentingArea' . ( !empty($aBLComments['billingLineTitle']) ? ' gotComments' : '' ) . '" width="50%" data-relation-type="submissionBillingLine" data-relation-id="' . $aPostVatLines['billingLineId'] . '" data-relation-field="billingLineTitle">' . $aPostVatLines['billingLineTitle'] . '</td>
									<td class="col2 commentingArea' . ( !empty($aBLComments['billingLineValue']) ? ' gotComments' : '' ) . '" width="25%" align="right" data-relation-type="submissionBillingLine" data-relation-id="' . $aPostVatLines['billingLineId'] . '" data-relation-field="billingLineValue">' . calculatePrice( $aPostVatLines['billingLineValue'], array('decimals' => 2) ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>';
				$fCostsTotalSum += $aPostVatLines['billingLineValue'];

				// Store the values in return array (returned if bIntResult is true)
				$aIntResult['billingLines']['postVat'][] = array(
					'title'	=> $aPostVatLines['billingLineTitle'],
					'value' => $aPostVatLines['billingLineValue']
				);
				$aIntResult['billingLines']['postVatSum'] += $aPostVatLines['billingLineValue'];
			}

			$sOutput .= '
								<tr>
									<td class="col1" width="50%" style="border-top: 1px solid #ccc">' . _( 'Cost sum' ) . '</td>
									<td class="col2" width="25%" align="right" style="border-top: 1px solid #ccc">' . calculatePrice( $fCostsTotalSum ) . '</td>
									<td class="col3" width="20%">&nbsp;</td>
									<td class="col4" width="15%" align="right">&nbsp;</td>
								</tr>
								<tr>
									<td class="col1" width="50%">&nbsp;</td>
									<td class="col2" width="25%" align="right">&nbsp;</td>
									<td class="col3" width="20%">' . _( 'Cost deduction' ) . '</td>
									<td class="col4" width="15%" align="right">' . calculatePrice( $fCostsTotalSum ) . '</td>
								</tr>
								<tr>
									<td colspan="4">&nbsp;</td>
								</tr>';

			$fTotalSum = $fLinesTotalSum - round($fCostsTotalSum);
			$fTotalRoundedSum = round( $fTotalSum );
			$fRoundValue = $fTotalRoundedSum - $fTotalSum;
			$iPayoutTime = ( $aSubmissionData['submissionPaymentDays'] * 86400 ) + strtotime( $aAuctionData['partAuctionStart'] );
			$sPayoutDate = date( "Y-m-d", $iPayoutTime );

			// Store the values in return array (returned if bIntResult is true)
			$aIntResult += array(
				'costsTotal'	=> $fCostsTotalSum,
				'totalRoundedSum' => $fTotalRoundedSum,
				'roundValue' => $fRoundValue
			);

			switch( $aSubmissionData['submissionPaymentToType'] ) {
				case 'cash':
					$sPaymentString = $aDataDict['entSubmission']['submissionPaymentToType']['values'][$aSubmissionData['submissionPaymentToType']];
					break;

				case 'pg':
				case 'bg':
				case 'account':
					$sPaymentString = _( 'to' ) . ' ' . $aDataDict['entSubmission']['submissionPaymentToType']['values'][$aSubmissionData['submissionPaymentToType']] . ' ' . $aSubmissionData['submissionPaymentToAccount'];
					break;

				case '':
				default:
					$sPaymentString = '';

			}

			$sSignedDate = '';
			if( $aSubmissionData['submissionSigned'] == 'yes' ) {
				if( !empty($aSubmissionData['submissionSignedDate']) ) {
					$sSignedDate = $aSubmissionData['submissionSignedDate'];
				} else if( $aSubmissionData['submissionBillingLockedDate'] != '0000-00-00 00:00:00' ) {
					$sSignedDate = substr( $aSubmissionData['submissionBillingLockedDate'], 0, 10 );
				}
			} else {
				$sSignedDate = date( "Y-m-d" );
			}

			$sOutput .= '
								<tr>
									<td class="col1" width="50%">&nbsp;</td>
									<td class="col2" width="25%" align="right">&nbsp;</td>
									<td class="col3" width="20%">' . _( 'Rounding' ) . '</td>
									<td class="col4" width="15%" align="right">' . calculatePrice( $fRoundValue, array('decimals' => 2) ) . '</td>
								</tr>
								<tr>
									<td class="col1" width="50%">' . _( 'Ätran den' ) . ' ' . $sSignedDate . '</td>
									<td class="col2" width="25%" align="right">&nbsp;</td>
									<td class="col3" width="20%" style="border-top: 1px solid #ccc">' . _( 'To bill (SEK)' ) . '</td>
									<td class="col4" width="15%" align="right" style="border-top: 1px solid #ccc">' . calculatePrice( $fTotalRoundedSum, array('decimals' => 2) ) . '</td>
								</tr>
								<tr>
									<td class="col1 sign" width="50%">' . ( ($aSubmissionData['submissionSigned'] == 'yes') ? '<img src="' . ( $bPdf ? PATH_PUBLIC : '' ) . '/images/templates/argoWhite/martin-signatur.png"' . ( $bPdf ? ' width="25%"' : '' ) . '>' : '&nbsp;') . '</td>
									<td class="col2" width="25%" align="right">&nbsp;</td>
									<td colspan="2" width="35%">&nbsp;</td>
								</tr>
								<tr>
									<td class="col1" width="50%">' . _( ' Martin Tovek ' ) . '</td>
									<td class="col2" width="25%" align="right">&nbsp;</td>
									<td colspan="2" width="35%" class="commentingArea' . ( !empty($aComments['submissionPaymentToType']) ? ' gotComments' : '' ) . '" data-relation-type="submission" data-relation-id="' . $iSubmissionId . '" data-relation-field="submissionPaymentToType">' . _( 'Payout' ) . ' ' . $sPayoutDate . '<br>' . $sPaymentString . '</td>
								</tr>';

			$sOutput .= '
							</tbody>
						</table>
					</div>
					<footer>' . $sFooter . '</footer>
				</section>';

			if( $bOnlyFirstPage !== true ) {
				$iLinesPerPage = SUBMISSION_ITEMS_PER_PAGE;
				$iPage = 1;
				$iPages = ceil( count($aSubmitterLines) / $iLinesPerPage );

				$iCount = 1;
				$sTableContent = '';
				foreach( $aSubmitterLines as $entry ) {

					// Fetch comments for the  line
					$aLineComments = $oSubmissionComments->readByRelation( 'submissionLine', $entry['lineId'] );
					$aLComments = array();
					foreach( $aLineComments as $aComment ) {
						if( isset($aComments[ $aComment['commentRelationField'] ]) ) {
							$aLComments[ $aComment['commentRelationField'] ]++;
						} else {
							$aLComments[ $aComment['commentRelationField'] ] = 1;
						}
					}

					$fLineVatSum = $entry['lineValue'] * $entry['lineVatValue'] / 100;

					$sTableContent .= '
											<tr>
												<td class="commentingArea' . ( !empty($aLComments['lineSortNo']) ? ' gotComments' : '' ) . '" data-relation-type="submissionLine" data-relation-id="' . $entry['lineId'] . '" data-relation-field="lineSortNo">' . $entry['lineSortNo'] . '</td>
												<td class="commentingArea' . ( !empty($aLComments['lineTitle']) ? ' gotComments' : '' ) . '" data-relation-type="submissionLine" data-relation-id="' . $entry['lineId'] . '" data-relation-field="lineTitle">' . $entry['lineTitle'] . '</td>
												<td class="commentingArea' . ( !empty($aLComments['lineValue']) ? ' gotComments' : '' ) . '" data-relation-type="submissionLine" data-relation-id="' . $entry['lineId'] . '" data-relation-field="lineValue">' . calculatePrice( $entry['lineValue'], array('decimals' => 2) ) . '</td>
												<td class="commentingArea' . ( !empty($aLComments['lineVatValue']) ? ' gotComments' : '' ) . '" data-relation-type="submissionLine" data-relation-id="' . $entry['lineId'] . '" data-relation-field="lineVatValue">' . calculatePrice( $fLineVatSum, array('decimals' => 2) ) . '</td>
												<td class="commentingArea' . ( !empty($aLComments['lineRecalled']) ? ' gotComments' : '' ) . '" data-relation-type="submissionLine" data-relation-id="' . $entry['lineId'] . '" data-relation-field="lineRecalled">' . ( ($entry['lineRecalled'] == 'yes') ? _( 'Yes' ) : '' ) . '</td>
											</tr>';

					if( (($iCount % $iLinesPerPage) == 0) || ($iCount == count($aSubmitterLines)) ) {
						$sOutput .= '
							<section class="submission' . ( $bPdf ? '' : ' preview' ) . ' pre2017 submissionItemList">
								<header>
									<div class="paging">' . _( 'Page' ) . ' ' . $iPage . ' ' . _( 'of' ) . ' ' . $iPages . '</div>
									<h1>' . _( 'Auction list' ) . ' ' . $aSubmissionData['submissionTitle'] . '</h1>
									<p>' . _( 'Självfakturanummer' ) . ' ' . $aReportData['submissionReportNo'] . '</p>
								</header>
								<div class="content">
									<table class="dataTable">
										<thead>
											<tr>
												<th>' . _( 'Call no' ) . '</th>
												<th>' . _( 'Product' ) . '</th>
												<th>' . _( 'Price' ) . '</th>
												<th>' . _( 'VAT' ) . '</th>
												<th>' . _( 'Recalled' ) . '</th>
											</tr>
										</thead>
										<tbody>
											' . $sTableContent . '
										</tbody>
									</table>
								</div>
							</section>';

							$sTableContent = '';
							$iPage++;
					}

					$iCount++;
				}
			}
		}

		// Reset the saved criterias
		$this->oDao->sCriterias = $aSubmissionCriterias;

		if( $bIntResult ) {

			// Store the total values
			$aIntResult += array(
				'totalSum'	=> $fTotalSum,
				'vatSum'		=> $fVatSum
			);

			return $aIntResult;
		} else {
			return $sOutput;
		}
	}

}
