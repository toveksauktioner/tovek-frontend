<?php

require_once PATH_CORE . '/clModuleBase.php';

class clAuctionItem extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'AuctionItem';
		$this->sModulePrefix = 'auctionItem';

		$this->oDao = clRegistry::get( 'clAuctionItemDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/auction/models' );

		$this->initBase();
	}

	/**
	 * Read by auction
	 * $mAuctionId, cud be given as 'array' or 'integer'
	 */
	public function readByAuction( $mAuctionId, $iPartId = null, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'itemAuctionId' => $mAuctionId,
			'itemPartId' => $iPartId
		) );
	}

	/**
	 * Read by submission
	 * @param mixed $mSubmissionId, cud be given as 'array' or 'integer'
	 */
	public function readBySubmission( $mSubmissionId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'itemSubmissionId' => $mSubmissionId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/**
	 * Read by submission import custom
	 * Rread items by the submission value given in import (itemSubmissionCustomId)
	 */
	public function readBySubmissionImportCustom( $iAuctionId, $sSubmissionImportCustomId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'itemAuctionId' => $iAuctionId,
			'itemSubmissionCustomId' => $sSubmissionImportCustomId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/**
	 * Read by address
	 * @param mixed $mAddressId, could be given as 'array' or 'integer'
	 */
	public function readByAddress( $mAddressId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'itemAddressId' => $mAddressId
		);
		return $this->oDao->readByForeignKey( $aParams );
	}

	/**
	 * Read next sort number
	 * Get next sort number for items in an auction
	 */
	public function readNextSortNumber( $iActionId, $iPartId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aNextNumber = current( $this->readByAuction( $iActionId, $iPartId, array(
			'MAX(itemSortNo)'
		) ) );
		return $aNextNumber['MAX(itemSortNo)'] !== null ? (int) $aNextNumber['MAX(itemSortNo)'] + 1 : false;
	}

	/**
	 * Read item relation
	 */
	public function readItemRelation( $iFromRelationId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readItemRelation( $iFromRelationId );
	}

	/**
	 * Function to mark/unmark item as favorite
	 */
	public function updateFavoriteItem( $iItemId, $iUserId, $bStatus ) {
		//$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->updateFavoriteItem( $iItemId, $iUserId, $bStatus );
	}

	/**
	 * Function read favorite item
	 */
	public function readFavoritesByUser( $iUserId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readFavoritesByUser( $iUserId );
	}

	/**
	 * - JUST FOR DEVELOPMENT TIME! -
	 * Update end time
	 */
	public function updateEndTimeByAuctionPart( $iPartId, $sStartDate = null, $iInterval = 2 ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		if( !empty($sStartDate) && ($sStartDate > date('Y-m-d H:i:s')) ) {
			return $this->oDao->updateEndTimeByAuctionPart( $iPartId, $sStartDate, $iInterval );
		}

		return false;
	}

	/**
	 * Update status by part ID
	 */
	public function updateStatusByAuctionPart( $iPartId, $sStatus ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->updateStatusByAuctionPart( $iPartId, $sStatus );
	}

	public function increaseViewedCount( $iItemId ) {
		return $this->oDao->increaseViewedCount( $iItemId );
	}

	/**
	 * Send winner mail
	 *  - Construct and send mail to winner
	 */
	public function sendWinnerMail( $iItemId, $aRecipient = [], $bTest = false ) {
		$oEmailQueue = clRegistry::get( 'clEmailQueue', PATH_MODULE . '/email/models' );
		$oSystemText = clRegistry::get( 'clSystemText', PATH_MODULE . '/systemText/models' );
		$oAuction = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );

		$aMailText = current( $oSystemText->read(null, 'USER_AUCTION_WINNER_MAIL') );
		$aSystemTexts = valueToKey( 'systemTextKey', $oSystemText->readWithParams([
		  'systemTextGroup' => 'ROP'
		]) );
		
		$aItem = current( $this->read([
			 'itemId',
			 'itemSortNo',
			 'itemTitle',
			 'itemWinningBidId',
			 'itemWinnerMailed',
			 'itemAddressId'
		], $iItemId) );

		if( !empty($aItem) && ($aItem['itemWinnerMailed'] != 'yes') ) {
			$aAddress = current( $oAuction->readAuctionAddress([
				'addressId',
				'addressTitle',
				'addressAddress',
				'addressCollectSpecial',
				'addressCollectStart',
				'addressCollectEnd',
				'addressFreightHelp',
				'addressCollectInfo'
			], $aItem['itemAddressId']) );

      $sItemAddress = '';
      if( !empty($aAddress) ) {
        $sCollectInfo = '
            <strong>' . $aAddress['addressTitle'] . ':</strong> ' . $aAddress['addressAddress'];

				if( !empty(trim($aAddress['addressCollectSpecial'])) ) {
					$sCollectInfo .= '
						<strong>' . _( 'Tid enligt överenskommelse på telefon' ) . ':</strong> ' . $aAddress['addressCollectSpecial'];

				} else if( !empty($aAddress['addressCollectStart']) && ($aAddress['addressCollectStart'] != '0000-00-00 00:00:00') ) {
					$iCollectStartTime = strtotime( $aAddress['addressCollectStart'] );
					$iCollectEndTime = strtotime( $aAddress['addressCollectEnd'] );
					$sCollectInfo .= '
						<strong>' . ucfirst( formatIntlDate('EEEE', $iCollectStartTime) ) . 'en den ' . formatIntlDate( 'd MMM', $iCollectStartTime ) . ' mellan kl. ' . formatIntlDate( 'HH:mm', $iCollectStartTime ) . '-' . formatIntlDate( 'HH:mm', $iCollectEndTime ) . '</strong>.';
				}

        if( $aAddress['addressFreightHelp'] == 'yes' ) {
            $sCollectInfo .= $aSystemTexts['INFO_ITEM_LOADING_HELP_YES']['systemTextMessage'];
        }

        $sItemAddress .= '
          <li>' . $sCollectInfo . '</li>';


        if( !empty($aAddress['addressCollectInfo']) ) {
          $sItemAddress .= '
            <li>' . $aAddress['addressCollectInfo'] . '</li>';
        }
      }


			$sContent = $oSystemText->replaceParams( $aMailText, [
				'collectInfo' => $sItemAddress
			] );

			$aTemplateData = [
				'title_1' => _( 'Rop' ) . ' ' . $aItem['itemSortNo'],
				'title_2' => $aItem['itemTitle'],
				'content' => str_replace( ["\n", "\r"], '', $sContent )
			] + json_decode($aMailText['systemTextParams'], true) + SENDGRID_TEMPLATE_DEFAULT_DATA;

			if( empty($aRecipient) || $bTest ) {
				$aRecipient = [
					'name' => 'Markus Renfors',
					'email' => 'markus@tovek.se'
				];
			}

			$mResult = $oEmailQueue->create( [
				'queueService' => 'sendgrid',
				'queueTo' => json_encode( $aRecipient ),
				'queueFrom' => json_encode( SENDGRID_OUTGOING_MAIL['frontend'] ),
				'queueTemplate' => 'frontend',
				'queueTemplateData' => json_encode( $aTemplateData ),
			] );

			if( $mResult ) {
				$this->update( $aItem['itemId'], [
					'itemWinnerMailed' => 'yes'
				] );
			}
		}

	}

}
