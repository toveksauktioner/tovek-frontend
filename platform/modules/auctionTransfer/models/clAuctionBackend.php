<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 18/03/2014 by Mikael
 * Description:
 * A comment about this file.
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */

require_once PATH_CORE . '/clModuleBase.php';

class clAuctionBackend extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'AuctionBackend';
		$this->sModulePrefix = 'auctionBackend';

		$this->oDao = clRegistry::get( 'clAuctionBackendDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/auctionTransfer/models' );

		$this->initBase();
        
        $this->oDao->switchToSecondary();
	}

    /**
	 * Auction: Function for reading auction
	 */
	public function readAuction( $aParams ) {
		$aFields = !empty($aParams['fields']) ? $aParams['fields'] : array();
		$iAuctionId = !empty($aParams['auctionId']) ? $aParams['auctionId'] : null;
		$iPartId = !empty($aParams['partId']) ? $aParams['partId'] : null;
        
		$aStatus = array();
		$aStatus['auctionStatus'] = !empty($aParams['auctionStatus']) ? $aParams['auctionStatus'] : 'active';
		$aStatus['partStatus'] = !empty($aParams['partStatus']) ? $aParams['partStatus'] : 'running';
        
		return $this->read( $aFields, $iAuctionId, $iPartId, $aStatus );
	}
    
	/**
	 *
	 */
	public function read( $aFields = array(), $iAuctionId = null, $iPartId = null, $aStatus = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'auctionId' => $iAuctionId,
			'partId' => $iPartId,
			'auctionStatus' => !empty($aStatus['auctionStatus']) ? $aStatus['auctionStatus'] : 'active',
			'partStatus' => !empty($aStatus['partStatus']) ? $aStatus['partStatus'] : 'running'
		);
		return $this->oDao->read( $aParams );
	}

	/**
	 * For manual auction part creating
	 */
	public function createAuctionPart( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		if( !empty($aData['partStatus']) && ($aData['partStatus'] == 'running') && empty($aData['partPublished']) ) {
			$aData['partPublished'] = date( 'Y-m-d H:i:s' );
		}

		return $this->oDao->createAuctionPart( $aData );
	}

	/**
	 *
	 */
	public function updateAuctionPart( $iPartId, $aData = array() ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		if( !empty($aData['partStatus']) && ($aData['partStatus'] == 'running') && empty($aData['partPublished']) ) {
			$aCurrentData = current( $this->oDao->readAuctionPart($iPartId, 'partStatus') );

			if( empty($aCurrentData) || ($aCurrentData['partStatus'] != $aData['partStatus']) ) {
				$aData['partPublished'] = date( 'Y-m-d H:i:s' );
			}
		}

		return $this->oDao->updateAuctionPart( $iPartId, $aData );
	}

	/**
	 *
	 */
	public function readAuctionPartByAuction( $iAuctionId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readAuctionPartByAuction( $iAuctionId );
	}

	/**
	 *
	 */
	public function deleteAuctionPart( $iPartId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteAuctionPart( $iPartId );
	}

	/**
	 * Wrong name!
	 */
	public function deleteAuctionParts( $iAuctionId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		/**
		 * Check for existing items
		 */
		$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
		$aItemIds = arrayToSingle( $oAuctionItem->readByAuction( $iAuctionId, null, array('itemId','itemAuctionId','itemPartId') ), null, 'itemId' );
		if( !empty($aItemIds) ) {
			foreach( $aItemIds as $iItemId ) {
				// Make item part-less
				$oAuctionItem->update( $iItemId, array(
					'itemAuctionId' => '',
					'itemPartId' => ''
				) );
			}
		}

		/**
		 * Delete all auction parts by 'deleteAuctionPart' funciton
		 */
		$aPartIds = arrayToSingle( $this->readAuctionPartByAuction( $iAuctionId ), null, 'partId' );
		if( !empty($aPartIds) ) foreach( $aPartIds as $iPartId ) $this->deleteAuctionPart( $iPartId );

		return true;
	}

	/**
	 * Read auction address
	 */
	public function readAuctionAddress( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		return $this->oDao->readAuctionAddress( $aParams, $primaryId );
	}

	/**
	 * Create auction address
	 */
	public function createAuctionAddress( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createAuctionAddress( $aData );
	}

	/**
	 * Update auction address
	 */
	public function updateAuctionAddress( $iAddressId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->updateAuctionAddress( $iAddressId, $aData );
	}

	/**
	 * Read auction address
	 */
	public function readAuctionAddressByAuctionPart( $iPartId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readAuctionAddressByAuctionPart( $iPartId );
	}

	/**
	 * Delete auction addresses
	 */
	public function deleteAuctionAddress( $iAddressId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteAuctionAddress( $iAddressId );
	}
	public function deleteAuctionAddresses( $iAuctionId = null, $iPartId = null ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteAuctionAddresses( $iAuctionId, $iPartId );
	}

	public function increaseViewedCount( $iAuctionId ) {
		return $this->oDao->increaseViewedCount( $iAuctionId );
	}

	/**
	 * Partner auction functions
	 */
	public function readAuctionToUser( $iAuctionId = null, $iUserId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readAuctionToUser( $iAuctionId, $iUserId );
	}

	public function createAuctionToUser( $iAuctionId, $iUserId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createAuctionToUser( $iAuctionId, $iUserId );
	}

	public function deleteAuctionToUser( $iAuctionId, $iUserId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteAuctionToUser( $iAuctionId, $iUserId );
	}

	/**
	 *
	 * This function is dedicated for the API
	 *
	 */
	public function createByApi( $aData ) {
		/**
		 * Default values
		 */
		$aData += array(
			'auctionType' => 'net',
			'auctionLocation' => 'API',
			'auctionStatus' => 'inactive'
		);

		// Force inactive status at all times
		$aData['auctionStatus'] = 'inactive';

		try {
			$this->oAcl->hasAccess( 'write' . $this->sModuleName );

			$iAuctionId = $this->create( $aData );
			$aErr = clErrorHandler::getValidationError( 'createAuction' );

			if( empty($aErr) ) {
				$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
				$oRouter = clRegistry::get( 'clRouter' );

				require_once PATH_FUNCTION . '/fOutputHtml.php';

				// Create route
				$oRouter->oDao->aSorting = array( 'routeId' => 'ASC' );
				$oRouter->oDao->setEntries( 1 );
				$sRoutePath = strToUrl( $oRouter->getPath('guestAuction') . '/' . $aData['auctionTitle'] . '/' . $iAuctionId );
				if( $oAuctionEngine->createRoute( $sRoutePath, 'auction', $iAuctionId ) !== false ) {
					/**
					 * Create a auction part, with route
					 */
					$iPartId = $this->createAuctionPart( array(
						'partTitle' => '',
						'partStatus' => 'upcomming',
						'partAuctionId' => $iAuctionId
					) );
					if( ctype_digit($iPartId) ) {
						// Success
						$sRoutePath = $sRoutePath . '/' . $iPartId;
						if( $oAuctionEngine->createRoute( $sRoutePath, 'auctionPart', $iPartId ) !== false ) {
							// Success
						} else {
							// Error
							$aErr['createAuction'] = 'Unkown error';
						}
					} else {
						// Error
						$aErr['createAuction'] = 'Unkown error';
					}

				} else {
					// Problem with creating route
					$aErr['createAuction'] = 'Problem with creating route';
				}

				if( empty($aErr) ) {
					return $iAuctionId;
				}

				return false;
			}

			return false;

		} catch( Throwable $oThrowable ) {
			echo printf( _('Exception: "%s" on line %s in file %s at code %s'), $oThrowable->getMessage(), $oThrowable->getLine(), $oThrowable->getFile(), $oThrowable->getCode() );
			die();
			//echo '<pre>';
			//echo printf( _('Exception: "%s" on line %s in file %s at code %s'), $oThrowable->getMessage(), $oThrowable->getLine(), $oThrowable->getFile(), $oThrowable->getCode() );
			//var_dump( $oThrowable );
			//die;

		} catch( Exception $oException ) {
			echo printf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
			die();
			//echo '<pre>';
			//echo printf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
			//var_dump( $oException );
			//die;

		}
	}

	/**
	 *
	 * This function is dedicated for the API
	 *
	 */
	public function readByApi( $aFields = array(), $iAuctionId = null ) {
		if( is_array($aFields) && !empty($aFields) ) {
			$aFields[] = 'partId';
			$aFields[] = 'partTitle';
			$aFields[] = 'partDescription';
		}
		return $this->read( $aFields, $iAuctionId, null, array(
			'auctionStatus' => '*',
			'partStatus' => '*'
		) );
	}

}
