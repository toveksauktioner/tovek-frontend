<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clUnifaunDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entUnifaunOrder' => array(
				'unifaunId' => array(
					'type' => 'integer',
					'autoincrement' => true,
					'index' => true,
					'title' => _( 'ID' )
				),
				'unifaunOrderNo' => array(
					'type' => 'string',
					'index' => true,
					'title' => _( 'Order No' )
				),

				'unifaunParcelCount' => array(
					'type' => 'integer',
					'title' => _( 'Parcel count' )

				),
				'unifaunParcelNo' => array(
					'type' => 'string',
					'title' => _( 'Parcel number' )
				),
				'unifaunPartnerId' => array(
					'type' => 'string',
					'title' => _( 'Partner Id' )
				),
				'unifaunPrintDate' => array(
					'type' => 'datetime',
					'title' => _( 'Print date' )
				),
				'unifaunReference' => array(
					'type' => 'string',
					'title' => _( 'Reference' )
				),
				'unifaunServiceId' => array(
					'type' => 'string',
					'title' => _( 'Service Id' )
				),
				'unifaunShipDate' => array(
					'type' => 'datetime',
					'title' => _( 'Shipping date' )
				),
				'unifaunShipmentNo' => array(
					'type' => 'string',
					'title' => _( 'Shipment number' )
				),

				'unifaunCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			),
			'entUnifaunLog' => array(
				'unifaunLogId' => array(
					'type' => 'integer',
					'autoincrement' => true,
					'index' => true,
					'title' => _( 'ID' )
				),
				'unifaunLogErrorCode' => array(
					'type' => 'string',
					'index' => true,
					'title' => _( 'Error code' )
				),
				'unifaunLogTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'unifaunLogMessage' => array(
					'type' => 'string',
					'title' => _( 'Message' )
				),
				'unifaunLogType' => array(
					'type' => 'array',
					'values' => array(
						'success' => _( 'Success' ),
						'failiure' => _( 'Failiure' )
					),
					'title' => _( 'Type' )
				),
				'unifaunLogCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);

		$this->sPrimaryField = 'unifaunId';
		$this->sPrimaryEntity = 'entUnifaunOrder';
		$this->aFieldsDefault = array( '*' );

		$this->init();
	}

	public function createLog($aData, $aParams) {
		$aParams['entities'] = 'entUnifaunLog';
		return $this->createData( $aData, $aParams );
	}

	public function readData( $aParams = array() ) {
		$aParams += array(
			'invoiceId' => null,
			'fields' => $this->aFieldsDefault
		);

		$aCriterias = array();
		if( !empty($aParams['invoiceId']) ) {
			$aCriterias[] = 'unifaunOrderNo = ' . intval($aParams['invoiceId']);
		}

		$aParams['criterias'] = implode(' AND ', $aCriterias );
		return clDaoBaseSql::readData($aParams);
	}

}
