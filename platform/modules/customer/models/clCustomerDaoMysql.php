<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clCustomerDaoMysql extends clDaoBaseSql {
	
	public $oUserDao;
	public $oOrderDao;
	
	public function __construct() {				
		// Hook on user module dao
		$oUser = clRegistry::get( 'clUserManager' );
		$this->oUserDao = $oUser->oDao;
		
		if( file_exists(PATH_MODULE . '/order') ) {
			// Hook on order module dao
			$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
			$this->oOrderDao = $oOrder->oDao;
		}
		
		$this->aDataDict = array(
			'entCustomer' => array(
				'customerId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				// Info
				'customerNumber' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'Customer number' )
				),				
				'customerDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'customerBlacklisted' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Blacklisted' )
				),
				// Misc
				'customerUserId' => array(
					'type' => 'integer',
					'title' => _( 'User ID' )
				),
				'customerLastOrderId' => array(
					'type' => 'integer',
					'title' => _( 'Last order ID' )
				),
				'customerCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			),
			'entCustomerGroup' => array(
				'groupId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'groupNameTextId' => array(
					'type' => 'string',
					'title' => _( 'Name' )
				),
				'groupVatInclusion' => array(
					'type' => 'array',
					'values' => array(
						'yes' => _( 'Yes' ),
						'no' => _( 'No' )
					),
					'title' => _( 'Price with VAT' )
				),
				'groupAutoGrantedUsage' => array(
					'type' => 'array',
					'values' => array(
						'yes' => _( 'Yes' ),
						'no' => _( 'No' )
					),
					'title' => _( 'Auto granted for usage' )
				),
				// Misc
				'groupCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			),
			'entCustomerToCustomerGroup' => array(
				'customerId' => array(
					'type' => 'integer',
					'index' => true
				),
				'groupId' => array(
					'type' => 'integer',
					'index' => true
				)
			),
			'entCustomerToCategory' => array(
				'customerId' => array(
					'type' => 'integer',
					'index' => true
				),
				'categoryId' => array(
					'type' => 'integer',
					'index' => true
				)
			),
			// Additional hooked on dataDicts
			'entUser' => $this->oUserDao->aDataDict['entUser'],
			'entUserInfo' => $this->oUserDao->aDataDict['entUserInfo'],
			'entOrder' => file_exists(PATH_MODULE . '/order') ? $this->oOrderDao->aDataDict['entOrder'] : array()
		);
		
		$this->sPrimaryField = 'customerId';
		$this->sPrimaryEntity = 'entCustomer';
		$this->aFieldsDefault = '*';
		
		$this->init();
		
		$this->aHelpers = array(
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'groupNameTextId'					
				),				
				'sTextEntity' => 'entCustomerText',
				'sOriginEntity' => 'entCustomerGroup'
			) )
		);
	}
	
	public function read( $aParams = array() ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'customerId' => null,
			'customerNumber' => null,
			'userId' => null,
			'blacklisted' => 'no'
		);
		
		$aDaoParams = array();
		$aCriterias = array();
		$aEntities = array();
		$aDuplicates = array();
		
		/**
		 * Handling of all fields situation
		 */
		if( $aParams['fields'] == '*' ) {
			$aReadFields = array();
			foreach( $this->aDataDict as $aDataDict ) {
				$aReadFields = array_merge( $aReadFields, array_keys($aDataDict) );
			}
			$aParams['fields'] = $aReadFields;
		}
		
		/**
		 * Dynamic join handling
		 */
		if( is_array($aParams['fields']) ) {
			$aEntitiesExtended = array();			
			foreach( $aParams['fields'] as $key => $sField ) {
				/**
				 * entOrder
				 */
				if( !empty($this->oOrderDao) && array_key_exists($sField, $this->oOrderDao->aDataDict['entOrder']) ) {
					if( !in_array('entOrder', $aEntities) ) {
						// Read customer's latest order
						$aEntitiesExtended[] = 'LEFT JOIN entOrder ON entCustomer.customerLastOrderId = entOrder.orderId';
						$aEntities[] = 'entOrder';
					}
					$aParams['fields'][$key] = 'entOrder.' . $sField;
				}
				/**
				 * entUser
				 */
				if( array_key_exists($sField, $this->oUserDao->aDataDict['entUser']) ) {
					if( !in_array('entUser', $aEntities) ) {
						// Read additional user related data
						$aEntitiesExtended[] = 'LEFT JOIN entUser ON entCustomer.customerUserId = entUser.userId';
						$aEntities[] = 'entUser';
					}
					$aParams['fields'][$key] = 'entUser.' . $sField;
				}
				/**
				 * entUserInfo
				 */
				if( array_key_exists($sField, $this->oUserDao->aDataDict['entUserInfo']) ) {
					if( !in_array('entUserInfo', $aEntities) ) {
						// Read additional user related data
						$aEntitiesExtended[] = 'LEFT JOIN entUserInfo ON entCustomer.customerUserId = entUserInfo.infoUserId';
						$aEntities[] = 'entUserInfo';
					}
					$aParams['fields'][$key] = 'entUserInfo.' . $sField;
				}
				/**
				 * entCustomerGroup
				 */
				if( array_key_exists($sField, $this->aDataDict['entCustomerGroup']) ) {
					if( !in_array('entCustomerGroup', $aEntities) ) {
						// Read additional user related data
						$aEntitiesExtended[] = 'LEFT JOIN entCustomerToCustomerGroup ON entCustomer.customerId = entCustomerToCustomerGroup.customerId';
						$aEntitiesExtended[] = 'LEFT JOIN entCustomerGroup ON entCustomerToCustomerGroup.groupId = entCustomerGroup.groupId';
						$aEntities[] = 'entCustomerGroup';
					}
					$aParams['fields'][$key] = 'entCustomerGroup.' . $sField;
				}
				/**
				 * entCustomerToCategory
				 */
				if( array_key_exists($sField, $this->aDataDict['entCustomerToCategory']) ) {
					if( !in_array('entCustomerToCategory', $aEntities) ) {
						// Read additional user related data
						$aEntitiesExtended[] = 'LEFT JOIN entCustomerToCategory ON entCustomer.customerId = entCustomerToCategory.customerId';
						$aEntities[] = 'entCustomerToCategory';
					}
					$aParams['fields'][$key] = 'entCustomerToCategory.' . $sField;
				}
				if( $sField == 'customerId' && !in_array($sField, $aDuplicates) ) {
					$aParams['fields'][$key] = 'entCustomer.' . $sField;
				}
				
				if( in_array($sField, $aDuplicates) ) {
					unset( $aParams['fields'][$key] );
				} else {
					$aDuplicates[] = $sField;
				}
			}
			if( !empty($aEntitiesExtended) ) {
				$aDaoParams['entitiesExtended'] = $this->sPrimaryEntity . ' ' . implode( ' ', $aEntitiesExtended );
			}
		}
		
		/**
		 * Read by customer ID
		 */
		if( $aParams['customerId'] !== null ) {
			if( is_array($aParams['customerId']) ) {
				$aCriterias[] = 'entCustomer.customerId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['customerId']) ) . ')';
			} else {
				$aCriterias[] = 'entCustomer.customerId = ' . $this->oDb->escapeStr( $aParams['customerId'] );
			}
		}
		
		/**
		 * Read by customer number
		 */
		if( $aParams['customerNumber'] !== null ) {
			if( is_array($aParams['customerNumber']) ) {
				$aCriterias[] = 'customerNumber IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['customerNumber']) ) . ')';
			} else {
				$aCriterias[] = 'customerNumber = ' . $this->oDb->escapeStr( $aParams['customerNumber'] );
			}
		}
		
		/**
		 * Read by user ID
		 */
		if( $aParams['userId'] !== null ) {
			if( is_array($aParams['userId']) ) {
				$aCriterias[] = 'customerUserId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['userId']) ) . ')';
			} else {
				$aCriterias[] = 'customerUserId = ' . $this->oDb->escapeStr( $aParams['userId'] );
			}
		}
		
		/**
		 * Blacklisted or not
		 */
		if( in_array($aParams['blacklisted'], array_keys($this->aDataDict['entCustomer']['customerBlacklisted']['values'])) ) {
			$aCriterias[] = 'customerBlacklisted = ' . $this->oDb->escapeStr( $aParams['blacklisted'] );
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		$aDaoParams += array(
			'fields' => $aParams['fields'],
			'groupBy' => 'entCustomer.customerId',
			'withHelpers' => in_array( 'entCustomerGroup', $aEntities ) ? true : false
		);
		
		return parent::readData( $aDaoParams );
	}
	
	public function readGroup( $aParams = array() ) {
		$aParams += array(
			'entities' => 'entCustomerGroup',
			'fields' => array_keys( $this->aDataDict['entCustomerGroup'] ),
			'groupId' => null
		);
		
		$aDaoParams = array();
		$aCriterias = array();
		
		/**
		 * Read by user ID
		 */
		if( $aParams['groupId'] !== null ) {
			if( is_array($aParams['groupId']) ) {
				$aCriterias[] = 'groupId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['groupId']) ) . ')';
			} else {
				$aCriterias[] = 'groupId = ' . $this->oDb->escapeStr( $aParams['groupId'] );
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		$aDaoParams += array(
			'entities' => $aParams['entities'],
			'fields' => $aParams['fields']
		);
		
		return parent::readData( $aDaoParams );
	}
	
	public function updateGroup( $aParams = array() ) {
		$aParams += array(
			'entities' => 'entCustomerToCustomerGroup',
			'customerId' => null,
			'groupId' => null
		);
		
		if( $aParams['customerId'] === null ) return false;
		if( $aParams['groupId'] === null ) return false;
		
		$aDaoParams = array();
		$aCriterias = array();
		
		/**
		 * Customer ID
		 */
		if( $aParams['customerId'] !== null ) {
			if( is_array($aParams['customerId']) ) {
				$aCriterias[] = 'entCustomerToCustomerGroup.customerId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['customerId']) ) . ')';
			} else {
				$aCriterias[] = 'entCustomerToCustomerGroup.customerId = ' . $this->oDb->escapeStr( $aParams['customerId'] );
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		$aDaoParams += array(
			'entities' => $aParams['entities'],
			'groupKey' => 'updateCustomer'
		);
		
		$aData['groupId'] = $aParams['groupId'];
		
		return parent::updateData( $aData, $aDaoParams );
	}
	
	public function delete( $iPrimaryId ) {		
		if( $this->deleteData( array(
			'entities' => 'entCustomer',
			'criterias' => 'customerId = ' . $this->oDb->escapeStr( $iPrimaryId ),
			'groupKey' => 'deleteCustomer',
			'withHelpers' => false
		) ) ) {			
			// Delete customerToCategory
			$this->deleteData( array(
				'entities' => 'entCustomerToCategory',
				'criterias' => 'customerId = ' . $this->oDb->escapeStr( $iPrimaryId ),
				'groupKey' => 'deleteCustomer',
				'withHelpers' => false
			) );
			
			// Delete customerToCustomerGroup
			$this->deleteData( array(
				'entities' => 'entCustomerToCustomerGroup',
				'criterias' => 'customerId = ' . $this->oDb->escapeStr( $iPrimaryId ),
				'groupKey' => 'deleteCustomer',
				'withHelpers' => false
			) );
			
			return true;
		}
		
		return false;
	}
	
	public function deleteByUserId( $iUserId ) {		
		return $this->deleteData( array(
			'entities' => 'entCustomer',
			'criterias' => 'customerUserId = ' . $this->oDb->escapeStr( $iUserId ),
			'groupKey' => 'deleteCustomer',
			'withHelpers' => false
		) );
	}
	
	public function addCustomerToGroup( $iCustomerId, $mGroupId ) {
		$aDaoParams = array(
			'entities' => 'entCustomerToCustomerGroup',
			'groupKey' => 'updateCustomer',
			'fields' => array(
				'customerId',
				'groupId'
			)
		);
		
		$aData = array();
		foreach( (array) $mGroupId as $iGroupId ) {
			$aData[] = array(
				'customerId' => $iCustomerId,
				'groupId' => $iGroupId
			);
		}
		
		return parent::createMultipleData( $aData, $aDaoParams );
	}
	
	public function clearGroupForCustomer( $iCustomerId ) {
		$aDaoParams = array(
			'entities' => 'entCustomerToCustomerGroup',
			'criterias' => 'customerId = ' . $this->oDb->escapeStr( $iCustomerId ),
			'groupKey' => 'updateCustomer',
			'withHelpers' => false
		);
		return parent::deleteData( $aDaoParams );		
	}
	
	public function readCustomerCategories( $iCustomerId ) {
		$aDaoParams = array(
			'fields' => 'entCustomerToCategory.categoryId',
			'entities' => 'entCustomerToCategory',
			'criterias' => 'customerId = ' . $this->oDb->escapeStr( $iCustomerId ),			
			'withHelpers' => false
		);	
		return parent::readData( $aDaoParams );
	}
	
}



















