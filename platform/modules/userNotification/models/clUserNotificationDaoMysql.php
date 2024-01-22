<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clUserNotificationDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entUserNotification' => array(
				'notificationId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'notificationTitle' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Title' )
				),
				'notificationMessage' => array(
					'type' => 'string',
					'title' => _( 'Message' )
				),
				'notificationType' => array(
					'type' => 'array',
					'required' => true,
					'values' => array(
						'email' => _( 'E-post' ),
						'sms' => _( 'SMS' ),
					),
					'title' => _( 'Type' )
				),
				'notificationUrl' => array(
					'type' => 'string',
					'title' => _( 'URL' )
				),
				'notificationRead' => array(
					'type' => 'array',
					'required' => true,
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' ),
					),
					'title' => _( 'Read' )
				),
				'notificationSent' => array(
					'type' => 'array',
					'required' => true,
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' ),
					),
					'title' => _( 'Sent by email' )
				),
				'notificationCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'notificationUserId' => array(
					'type' => 'integer'
				),
			)
		);

		$this->sPrimaryField = 'notificationId';
		$this->sPrimaryEntity = 'entUserNotification';

		$this->init();
	}

	/* * *
	 * Combined dao function for reading data
	 * based on foreign key's
	 * * */
	public function readByForeignKey( $aParams, $aFields = null ) {
		$aDaoParams = array();
		$sCriterias = array();

		$aParams += array(
			'fields' => $aFields,
			'notificationTitle' => null,
			'notificationType' => null,
			'notificationUrl' => null,
			'notificationRead' => null,
			'notificationSent' => null,
			'notificationUserId' => null,
		);

		$aDaoParams['fields'] = $aParams['fields'];

		if( $aParams['notificationTitle'] !== null ) {
			$aCriterias[] = 'notificationTitle = ' . $this->oDb->escapeStr( $aParams['notificationTitle'] );
		}

		if( $aParams['notificationType'] !== null ) {
			$aCriterias[] = 'notificationType = ' . $this->oDb->escapeStr( $aParams['notificationType'] );
		}

		if( $aParams['notificationUrl'] !== null ) {
			$aCriterias[] = 'notificationUrl = ' . $this->oDb->escapeStr( $aParams['notificationUrl'] );
		}

		if( $aParams['notificationRead'] !== null ) {
			$aCriterias[] = 'notificationRead = ' . $this->oDb->escapeStr( $aParams['notificationRead'] );
		}

		if( $aParams['notificationSent'] !== null ) {
			$aCriterias[] = 'notificationSent = ' . $this->oDb->escapeStr( $aParams['notificationSent'] );
		}

		if( $aParams['notificationUserId'] !== null ) {
			if( is_array($aParams['notificationUserId']) ) {
				$aCriterias[] = 'notificationUserId IN(' . implode( ', ', array_map('intval', $aParams['notificationUserId']) ) . ')';
			} else {
				$aCriterias[] = 'notificationUserId = ' . (int) $aParams['notificationUserId'];
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

}
