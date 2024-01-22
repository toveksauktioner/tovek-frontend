<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clPaymentDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entPayment' => array(
				'paymentId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'paymentTitleTextId' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'paymentDescriptionTextId' => array(
					'type' => 'string',
					'title' => _( 'Description' )
				),
				'paymentPriceAllowed' => array(
					'type' => 'array',
					'values' => array(
						'yes' => _( 'Yes' ),
						'no' => _( 'No' )
					),
					'title' => _( 'Price allowed' )
				),
				'paymentPrice' => array(
					'type' => 'float',
					'title' => _( 'Price' )
				),
				'paymentStatus' => array(
					'type' => 'array',
					'values' => array(
						'inactive' => _( 'Inactive' ),
						'active' => _( 'Active' )
					),
					'title' => _( 'Status' )
				),
				'paymentClass' => array(
					'type' => 'string',
					'title' => _( 'Payment class' )
				),
				'paymentType' => array(
					'type' => 'array',
					'values' => array(						
						'providerHosted' => _( 'Hosted by provider' ),
						'inSiteView' => _( 'In site view based' )
					),
					'title' => _( 'Type' )
				),
				'paymentSort' => array(
					'type' => 'integer',
					'title' => _( 'Sort' )
				),
				'paymentCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			),
			'entPaymentToCountry' => array(
				'paymentId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'ID' )
				),
				'countryId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'ID' )
				)
			),
			'entPaymentToOrderField' => array(
				'paymentId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'ID' )
				),
				'orderField' => array(
					'type' => 'string',
					'index' => true,
					'title' => _( 'Field' )
				)
			)
		);
		$this->sPrimaryEntity = 'entPayment';
		$this->sPrimaryField = 'paymentId';
		$this->aFieldsDefault = '*';

		$this->init();
		
		$this->aHelpers = array(
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'paymentTitleTextId',
					'paymentDescriptionTextId'
				),				
				'sTextEntity' => 'entPaymentText'				
			) )
		);
	}

	public function read( $aParams = array() ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'paymentId' => null,
			'class' => null,
			'status' => 'active'
		);
		$aCriterias = array();

		$aDaoParams = array(
			'fields' => $aParams['fields']
		);

		if( $aParams['status'] !== null ) $aCriterias[] = 'paymentStatus = ' . $this->oDb->escapeStr( $aParams['status'] );
		if( $aParams['class'] !== null ) $aCriterias[] = 'paymentClass = ' . $this->oDb->escapeStr( $aParams['class'] );

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		if( $aParams['paymentId'] !== null ) return $this->readDataByPrimary( $aParams['paymentId'], $aDaoParams );
		return $this->readData( $aDaoParams );
	}

	public function updateSort( $aPrimaryIds ) {				
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );
		
		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'paymentSort', array(
			'entities' => 'entPayment',
			'primaryField' => 'paymentId'
		) );
	}

	/**
	 *
	 * Payment to order field
	 *
	 */
	
	public function createPaymentToOrderField( $aData ) {
		$aParams = array(
			'entities' => 'entPaymentToOrderField',
			'groupKey' => 'createPaymentToOrderField',
			'fields' => array(
				'paymentId',
				'orderField'
			)
		);
		return parent::createMultipleData( $aData, $aParams );
	}
	
	public function readPaymentToOrderField( $iPaymentId ) {
		$aParams = array(
			'entities' => 'entPaymentToOrderField',
			'criterias' => 'entPaymentToOrderField.paymentId = ' . $this->oDb->escapeStr( $iPaymentId ),
			'withHelpers' => false
		);		
		return parent::readData( $aParams );
	}
	
	public function deletePaymentToOrderField( $iPaymentId ) {
		$aParams = array(
			'entities' => 'entPaymentToOrderField',
			'criterias' => 'entPaymentToOrderField.paymentId = ' . $this->oDb->escapeStr( $iPaymentId ),
			'withHelpers' => false
		);		
		return parent::deleteData( $aParams );
	}
}
