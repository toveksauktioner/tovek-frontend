<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/customer/config/cfCustomer.php';

class clCustomer extends clModuleBase {

	public $oUser;
	public $oOrder;

	public function __construct() {
		$this->sModuleName = 'Customer';
		$this->sModulePrefix = 'customer';
		
		$this->oDao = clRegistry::get( 'clCustomerDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/customer/models' );
		
		$this->initBase();
		
		// Hook on user module
		$this->oUser = clRegistry::get( 'clUserManager' );
		$this->oDao->oUserDao = $this->oUser->oDao;
		
		if( file_exists(PATH_MODULE . '/order') ) {
			// Hook on order module
			$this->oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
			$this->oDao->oOrderDao = $this->oOrder->oDao;
		}
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		
		$aData += array(
			$this->sModulePrefix . 'Created' => date( 'Y-m-d H:i:s' ),
			'customerNumber' => $this->nextCustomerNumber()
		);
		
		if( $this->oDao->createData($aData, $aParams) ) {
			$iCustomerId = $this->oDao->oDb->lastId();
			
			// Customer group
			if( !empty($aData['customerGroup']) ) {
				$this->updateGroupForCustomer( $iCustomerId, $aData['customerGroup'] );
			}
			
			return $iCustomerId;
		}
		
		return false;
	}
	
	public function nextCustomerNumber() {
		$iHighestNumber = current(current( $this->oDao->readData( array(
 			'entities' => 'entCustomer',
			'fields' => 'MAX(customerNumber) AS highestNumber'
		) ) ));
		
		if( empty($iHighestNumber) ) {
			$iHighestNumber = 1000;
		} else {
			$iHighestNumber++;
		}
		
		return $iHighestNumber;
	}
	
	public function read( $aFields = array(), $mPrimaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'fields' => $aFields,
			'customerId' => $mPrimaryId,
			'blacklisted' => 'no'
		);		
		
		return $this->oDao->read( $aParams );
	}
	
	public function readAll( $aFields = array(), $mPrimaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'fields' => $aFields,
			'customerId' => $mPrimaryId,
			'blacklisted' => '*'
		);		
		
		return $this->oDao->read( $aParams );
	}
	
	public function readByCustomerNumber( $mCustomerNumber = null, $aFields = array(), $sBlacklisted = 'no' ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'fields' => $aFields,
			'customerNumber' => $mCustomerNumber,
			'blacklisted' => $sBlacklisted
		);		
		
		return $this->oDao->read( $aParams );
	}
	
	public function readByUserId( $mUserId = null, $aFields = array(), $sBlacklisted = 'no' ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'fields' => $aFields,
			'userId' => $mUserId,
			'blacklisted' => $sBlacklisted
		);		
		
		return $this->oDao->read( $aParams );
	}
	
	public function readCustomerGroup( $aFields = array(), $mGroupId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'fields' => $aFields,
			'groupId' => $mGroupId
		);		
		
		return $this->oDao->readGroup( $aParams );
	}
	
	public function readCustomerCategories( $iCustomerId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$iCustomerId = $iCustomerId != null ? $iCustomerId : (!empty($_SESSION['customer']['customerId']) ? $_SESSION['customer']['customerId'] : null);
		return $iCustomerId != null ? $this->oDao->readCustomerCategories( $iCustomerId ) : false;
	}
	
	public function searchByEmail( $sEmail ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$this->oDao->setCriterias( array(
			'userEmail' => array(
				'type' => '=',				
				'fields' => 'userEmail',
				'value' => $sEmail
			),
			'orderEmail' => array(
				'type' => '=',				
				'fields' => 'orderEmail',
				'value' => $sEmail
			)
		), 'OR' );
		
		$aData = $this->readAll( array(
			'customerId',
			'customerNumber',
			'groupId',
			'userEmail',
			'orderEmail'			
		) );
		
		$this->oDao->sCriterias = null;
		
		return $aData;
	}
	
	public function update( $iPrimaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aParams = array(
			'groupKey' => 'update' . $this->sModuleName,
			'withHelpers' => false
		);
		
		$aData[$this->sModulePrefix . 'Updated'] = date( 'Y-m-d H:i:s' );
		
		$result = $this->oDao->updateDataByPrimary( $iPrimaryId, $aData, $aParams );
		
		if( $result !== false && !empty($aData['customerGroup']) ) {
			// Customer group
			$this->updateGroupForCustomer( $iPrimaryId, $aData['customerGroup'] );
		}
		
		return $result;
	}
	
	public function delete( $iPrimaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->delete( $iPrimaryId );
	}
	
	public function deleteByUserId( $iUserId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aCustomerData = $this->readByUserId( $iUserId, 'customerId' );
		
		if( !empty($aCustomerData) ) {
			$iCustomerId = current(current( $aCustomerData ));
			
			$this->clearGroupForCustomer( $iCustomerId );
			
			return $this->delete( $iCustomerId );
		}
		
		return false;
	}
	
	public function updateGroupForCustomer( $iCustomerId, $mGroupId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		// Remove all current groups for customer
		$this->clearGroupForCustomer( $iCustomerId );
		
		return $this->oDao->addCustomerToGroup( $iCustomerId, $mGroupId );
	}
	
	public function clearGroupForCustomer( $iCustomerId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->clearGroupForCustomer( $iCustomerId );
	}
	
	public function createGroup( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aParams = array(
			'entities' => 'entCustomerGroup',
			'groupKey' => 'createCustomerGroup'
		);		
		
		if( $this->oDao->createData($aData, $aParams) ) {
			return $this->oDao->oDb->lastId();
		}
		
		return false;
	}
	
	public function updateGroup( $iGroupId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aParams = array(
			'entities' => 'entCustomerGroup',
			'groupKey' => 'updateCustomerGroup',
			'criterias' => 'groupId = ' . $this->oDao->oDb->escapeStr( $iGroupId )
		);		
		
		return $this->oDao->updateData( $aData, $aParams );
	}
	
	public function deleteGroup( $iGroupId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aParams = array(
			'entities' => 'entCustomerGroup',
			'groupKey' => 'deleteCustomerGroup',
			'criterias' => 'groupId = ' . $this->oDao->oDb->escapeStr( $iGroupId )
		);		
		
		return $this->oDao->deleteData( $aParams );
	}
	
}