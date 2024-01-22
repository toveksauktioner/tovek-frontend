<?php

require_once PATH_CORE . '/clModuleBase.php';

class clPayment extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'payment';

		$this->oDao = clRegistry::get( 'clPaymentDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/payment/models' );
		$this->initBase();
	}

	public function read( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'paymentId' => $primaryId
		);
		return $this->oDao->read( $aParams );
	}

	public function readAll( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'paymentId' => $primaryId,
			'status' => null
		);
		return $this->oDao->read( $aParams );
	}
	
	public function readByClass( $sClass, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'class' => $sClass,
			'status' => null
		);
		return $this->oDao->read( $aParams );
	}

	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		// Only superuser is allowed to delete
		$oUser = clRegistry::get( 'clUser' );
		if( !array_key_exists( 'super', $oUser->aGroups ) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataError' => _( 'You are not authorized to delete a payment method' )
			) );
			return false;
		}
		
		$result = $this->oDao->deleteDataByPrimary( $primaryId );
		if( !empty($result) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'The data has been deleted' )
			) );
		}
		return $result;
	}

	public function updateSort() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aPrimaryIds = func_get_args();		
		return $this->oDao->updateSort( $aPrimaryIds );
	}

	// Payment to country below
	public function createPaymentToCountry( $iPaymentId, $aCountryIds ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aData = array();
		foreach( $aCountryIds as $iCountryId ) {
			$aData[] = array(
				'paymentId' => $iPaymentId,
				'countryId' => $iCountryId
			);
		}
		
		return $this->oDao->createMultipleData( $aData, array(
			'entities' => 'entPaymentToCountry',
			'fields' => array(
				'paymentId',
				'countryId'
			)
		) );
	}
	
	public function readPaymentToCountry( $mPaymentId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'entities' => 'entPaymentToCountry',
			'fields' => array(
				'paymentId',
				'countryId'
			)
		);
		
		if( $mPaymentId !== null ) {
			if( is_array($mPaymentId) ) {
				$aParams['criterias'] = 'entPaymentToCountry.paymentId IN(' . implode( ', ', array_map('intval', $mPaymentId) ) . ')';
			} else {
				$aParams['criterias'] = 'entPaymentToCountry.paymentId = ' . (int) $mPaymentId;
			}
		}
		
		return $this->oDao->readData( $aParams );
	}
	
	public function readPaymentByCountry( $iCountryId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readData( array(
			'entitiesExtended' => 'entPayment LEFT JOIN entPaymentToCountry ON entPayment.paymentId = entPaymentToCountry.paymentId',
			'criterias' => 'entPayment.paymentStatus = "active" AND entPaymentToCountry.countryId = ' . (int) $iCountryId
		) );
	}
	
	public function deletePaymentToCountry( $iPaymentId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		// Remove temporary helpers
		$aHelpers = $this->oDao->aHelpers;
		$this->oDao->aHelpers = null;
		
		$this->oDao->deleteData( array(
			'entities' => 'entPaymentToCountry',
			'criterias' => 'entPaymentToCountry.paymentId = ' . $iPaymentId
		) );
		
		// Restore helpers
		$this->oDao->aHelpers = $aHelpers;
	}
	
	public function updatePaymentToCountry( $iPaymentId, $aCountryIds ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$this->deletePaymentToCountry( $iPaymentId );
		$this->createPaymentToCountry( $iPaymentId, $aCountryIds );
		return true;
	}

	/**
	 *
	 * Payment to order field
	 *
	 */

	public function createPaymentToOrderField( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createPaymentToOrderField( $aData );
	}
	
	public function readPaymentToOrderField( $iPaymentId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readPaymentToOrderField( $iPaymentId );
	}
	
	public function deletePaymentToOrderField( $iPaymentId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deletePaymentToOrderField( $iPaymentId );
	}
	
}
