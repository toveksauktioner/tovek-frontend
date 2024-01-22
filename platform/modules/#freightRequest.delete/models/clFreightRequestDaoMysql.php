<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 10/12/2014 by Renfors
 * Description:
 * DAO file for entFreightRequest
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clFreightRequestDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entFreightRequest' => array(
				'requestId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'requestReferral' => array(
					'type' => 'string',
					'title' => _( 'Invoice no' )
				),
				'requestMessage' => array(
					'type' => 'string',
					'title' => _( 'Meddelande' )
				),
				'requestTransportService' => array(
					'type' => 'string',
					'title' => _( 'Typ' )
				),
				'requestParcelCount' => array(
					'type' => 'integer',
					'title' => _( 'Antal' )
				),
				'requestParcelSize' => array(
					'type' => 'array',
					'values' => array(
						'parcel' => _( 'Paket' ),
						'halfpallet' => _( 'Halvpall' ),
						'pallet' => _( 'Helpall' ),
						'specialpallet' => _( 'Specialpall' )
					),
					'title' => _( 'Storlek' )
				),
				'requestParcelWeight' => array(
					'type' => 'float',
					'title' => _( 'Vikt per kolli' ) . ' (kg)'
				),
				'requestParcelContents' => array(
					'type' => 'string',
					'title' => _( 'Innehåll' )
				),
				'requestCost' => array(
					'type' => 'float',
					'title' => _( 'Kostnad' )
				),
				'requestStatus' => array(
					'type' => 'array',
					'values' => array(
						'requested' => _( 'Requested' ),
						'suggested' => _( 'Suggested' ),
						'accepted' => _( 'Accepted' ),
						'declined' => _( 'Declined' ),
						'shipped' => _( 'Shipped' )
					),
					'title' => _( 'Status' )
				),
				'requestCalculatedDelivery' => array(
					'type' => 'date',
					'title' => _( 'Beräknat leveransdatum' )
				),
				'requestSenderId' => array(
					'type' => 'int',
					'title' => _( 'Avsändaradress' )
				),
				'freightRequestCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'freightRequestUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Changed' )
				),
				// Foreign key's
				// 'requestInvoiceId' => array(
				// 	'type' => 'integer'
				// ),
				'requestUnifaunOrderId' => array(
					'type' => 'integer'
				)
			)
		);

		$this->sPrimaryEntity = 'entFreightRequest';
		$this->sPrimaryField = 'requestId';
		$this->aFieldsDefault = array( '*' );

		$this->init();
	}

	/**
	 * Combined dao function for reading data
	 * based on foreign key's
	 */
	public function readByForeignKey( $aParams ) {
		$aDaoParams = array();
		$sCriterias = array();

		$aParams += array(
			'requestInvoiceId' => null,
			'requestUnifaunOrderId' => null
		);

		$aDaoParams['fields'] = $aParams['fields'];

		if( $aParams['requestInvoiceId'] !== null ) {
			if( is_array($aParams['requestInvoiceId']) ) {
				$aCriterias[] = 'requestInvoiceId IN(' . implode( ', ', array_map('intval', $aParams['requestInvoiceId']) ) . ')';
			} else {
				$aCriterias[] = 'requestInvoiceId = ' . (int) $aParams['requestInvoiceId'];
			}
		}

		if( $aParams['requestUnifaunOrderId'] !== null ) {
			if( is_array($aParams['requestUnifaunOrderId']) ) {
				$aCriterias[] = 'requestUnifaunOrderId IN(' . implode( ', ', array_map('intval', $aParams['requestUnifaunOrderId']) ) . ')';
			} else {
				$aCriterias[] = 'requestUnifaunOrderId = ' . (int) $aParams['requestUnifaunOrderId'];
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

}
