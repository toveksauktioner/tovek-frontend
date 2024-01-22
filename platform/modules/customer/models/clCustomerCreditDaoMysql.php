<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clCustomerCreditDaoMysql extends clDaoBaseSql {
	
	public $aValidationError = array();
	
	public function __construct() {
		$this->aDataDict = array(
			'entCustomerCredit' => array(
				'creditId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'creditCustomerId' => array(
					'type' => 'integer',
					'title' => _( 'Customer' )
				),
				'creditValue' => array(
					'type' => 'float',
					'title' => _( 'Value' )
				),
				'creditValueType' => array(
					'type' => 'array',
					'title' => _( 'Type' ),
					'values' => array(
						'credit' => _( 'Credit' ),
						'debt' => _( 'Debt' )				
					)
				),
				'creditStatus' => array(
					'type' => 'array',
					'title' => _( 'Status' ),
					'values' => array(
						'available' => _( 'Available' ),
						'locked' => _( 'Locked' )				
					)
				),
				'creditCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'creditUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			),
			'entCustomerCreditTransaction' => array(
				'transactionId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Transaction ID' )
				),
				'transactionCustomId' => array(
					'type' => 'float',
					'title' => _( 'Custom ID' )
				),
				'transactionValue' => array(
					'type' => 'float',
					'title' => _( 'Value' )
				),
				'transactionType' => array(
					'type' => 'array',
					'title' => _( 'Type' ),
					'values' => array(
						'deposit' => _( 'Deposit' ),
						'withdrawal' => _( 'Withdrawal' )
					)
				),
				'transactionDescription' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'transactionCreditId' => array(
					'type' => 'integer',
					'required' => true
				),
				'transactionCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		
		$this->sPrimaryField = 'creditId';
		$this->sPrimaryEntity = 'entCustomerCredit';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}
	
	public function read( $aParams = array() ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'creditId' => null,
			'customerId' => null
		);
		
		$aDaoParams = array();
		
		/**
		 * Read by credit ID
		 */
		if( $aParams['creditId'] !== null ) {
			if( is_array($aParams['creditId']) ) {
				$aCriterias[] = 'creditId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['creditId']) ) . ')';
			} else {
				$aCriterias[] = 'creditId = ' . $this->oDb->escapeStr( $aParams['creditId'] );
			}
		}
		
		/**
		 * Read by user ID
		 */
		if( $aParams['customerId'] !== null ) {
			if( is_array($aParams['customerId']) ) {
				$aCriterias[] = 'creditCustomerId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['customerId']) ) . ')';
			} else {
				$aCriterias[] = 'creditCustomerId = ' . $this->oDb->escapeStr( $aParams['customerId'] );
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		$aDaoParams += array(
			'fields' => $aParams['fields']
		);
		
		return parent::readData( $aDaoParams );		
	}
	
	public function readTransactions( $aParams = array() ) {
		$aParams += array(
			'fields' => array_keys( $this->aDataDict['entCustomerCreditTransaction'] ),
			'creditId' => null
		);
		
		$aDaoParams = array();
		
		/**
		 * Read by credit ID
		 */
		if( $aParams['creditId'] !== null ) {
			if( is_array($aParams['creditId']) ) {
				$aCriterias[] = 'transactionCreditId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['creditId']) ) . ')';
			} else {
				$aCriterias[] = 'transactionCreditId = ' . $this->oDb->escapeStr( $aParams['creditId'] );
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		$aDaoParams += array(
			'fields' => $aParams['fields'],
			'entities' => 'entCustomerCreditTransaction'
		);
		
		return parent::readData( $aDaoParams );		
	}
	
	public function makeTransaction( $aParams = array() ) {
		$aParams += array(
			'creditId' => null,
			'value' => 0,
			'type' => null
		);
		
		/**
		 * Transaction validation
		 */
		if( !$this->validateTransaction($aParams) ) {
			return $this->aValidationError;
		}
		
		/**
		 * Read current credit value
		 */
		$aCreditData = current( $this->readData( array(
			'fields' => '*',
			'criterias' => 'creditId = ' . (int) $aParams['creditId']
		) ) );
		
		if( empty($aCreditData) ) {
			$this->aValidationError[] = _( 'No credit data' );
			return $this->aValidationError;
		}
		
		if( $aCreditData['creditValueType'] == 'debt' ) {
			$aCreditData['creditValue'] = 0 - (float) $aCreditData['creditValue'];
		}
		
		switch( $aParams['type'] ) {
			case 'deposit':
				$fNewCredit = $aCreditData['creditValue'] + $aParams['value'];
				break;
			
			case 'withdrawal':								
				if( $aParams['value'] < 0 ) {
					$fNewCredit = $aCreditData['creditValue'] - abs( $aParams['value'] );				
				} else {
					$fNewCredit = $aCreditData['creditValue'] - $aParams['value'];
				}
				break;
			
			default:
				$this->aValidationError[] = _( 'Unkown transaction type' );
				return $this->aValidationError;
				break;
		}		
		
		/**
		 * Credit validation
		 */
		if( !$this->validateCredit($fNewCredit) ) {
			return $this->aValidationError;
		}
		
		/**
		 * Credit value type check
		 */
		if( $aCreditData['creditValueType'] == 'debt' && $fNewCredit > 0 ) {
			$sNewValueType = 'credit';
		} elseif( $aCreditData['creditValueType'] == 'credit' && $fNewCredit < 0 ) {
			$sNewValueType = 'debt';
		} else {
			$sNewValueType = $aCreditData['creditValueType'];
		}
		
		/**
		 * Store transaction
		 */
		$this->createData( array(
			'transactionValue' => $aParams['value'],
			'transactionType' => $aParams['type'],
			'transactionCreditId' => $aParams['creditId'],
			'transactionCreated' => date( 'Y-m-d H:i:s' )
		), array(
			'entities' => 'entCustomerCreditTransaction'
		) );
		
		/**
		 * Update credit value
		 */
		$this->updateDataByPrimary( $aParams['creditId'], array(
			'creditValue' => $fNewCredit,
			'creditValueType' => $sNewValueType
		) );
		
		return true;
	}
	
	public function validateTransaction( $aParams ) {
		/**
		 * Read config values
		 */
		$oConfig = clFactory::create( 'clConfig' );
		$aConfigData = arrayToSingle( $oConfig->oDao->readData( array(
			'fields' => '*',
			'criterias' => 'configGroupKey = ' . $oConfig->oDao->oDb->escapeStr( 'CustomerCredit' )
		) ), 'configKey', 'configValue' );
		
		if( !empty($aConfigData['creditMinTransactionValue']) && $aConfigData['creditMinTransactionValue'] != '0' && $aParams['value'] < $aConfigData['creditMinTransactionValue'] ) {
			$this->aValidationError[] = sprintf( _( 'Transaction must be heigher then %s' ), $aConfigData['creditMinTransactionValue'] );
		}
		
		if( !empty($aConfigData['creditMaxTransactionValue']) &&  $aConfigData['creditMaxTransactionValue'] != '0' && $aParams['value'] > $aConfigData['creditMaxTransactionValue'] ) {
			$this->aValidationError[] = sprintf( _( 'Transaction must be lower then %s' ), $aConfigData['creditMinTransactionValue'] );
		}
		
		return empty($this->aValidationError) ? true : false;
	}
	
	public function validateCredit( $fCredit ) {
		/**
		 * Read config values
		 */
		$oConfig = clFactory::create( 'clConfig' );
		$aConfigData = arrayToSingle( $oConfig->oDao->readData( array(
			'fields' => '*',
			'criterias' => 'configGroupKey = ' . $oConfig->oDao->oDb->escapeStr( 'CustomerCredit' )
		) ), 'configKey', 'configValue' );
		
		/**
		 * Max credit
		 */
		if( $aConfigData['creditMaxCredit'] != 0 && $fCredit > $aConfigData['creditMaxCredit'] ) {
			$this->aValidationError[] = sprintf( _( 'Credit can not be heigher then %s' ), $aConfigData['creditMaxCredit'] );
		}
		
		/**
		 * Max debt
		 */
		$aConfigData['creditMaxDebt'] = 0 - (float) $aConfigData['creditMaxDebt'];
		if( $fCredit < 0 && $fCredit < $aConfigData['creditMaxDebt'] ) {
			$this->aValidationError[] = sprintf( _( 'Debt can not be heigher then %s' ), $aConfigData['creditMaxDebt'] );
		}
		
		return empty($this->aValidationError) ? true : false;
	}
	
	
	
	
}