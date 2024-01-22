<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clUserPassRetrievalDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entUserPassRetrieval' => array(
				'retrievalId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'retrievalActivationKey' => array(
					'type' => 'string',
					'required' => true,
					'index' => true,
					'min' => 32,
					'max' => 32,
					'title' => _( 'Activation key' )
				),
				'retrievalPass' => array(
					'type' => 'string',
					'required' => true,
					'min' => 128,
					'max' => 128,
					'title' => _( 'Password' )
				),
				'retrievalUserId' => array(
					'type' => 'integer',
					'title' => _( 'User ID' )
				),
				'retrievalIp' => array(
					'type' => 'integer'
				),
				'retrievalCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);

		$this->sPrimaryField = 'retrievalId';
		$this->sPrimaryEntity = 'entUserPassRetrieval';
		$this->aFieldsDefault = '*';

		$this->init();
	}

	public function read( $aParams = array() ) {
        $aParams += array(
			'fields' => $this->aFieldsDefault,
            'retrievalId' => null,
			'activationKey' => null
		);

		$aDaoParams = array(
			'fields' => $aParams['fields']
		);

		$aCriterias = array();

		if( $aParams['retrievalId'] !== null ) {
			if( is_array($aParams['retrievalId']) ) {
				$aCriterias[] = 'retrievalId IN(' . implode( ', ', array_map('intval', $aParams['retrievalId']) ) . ')';
			} else {
				$aCriterias[] = 'retrievalId = ' . (int) $aParams['retrievalId'];
			}
		}

		if( $aParams['activationKey'] !== null ) {
			if( is_array($aParams['activationKey']) ) {
				$aCriterias[] = 'retrievalActivationKey IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['activationKey']) ) . ')';
			} else {
				$aCriterias[] = 'retrievalActivationKey = ' . $this->oDb->escapeStr( $aParams['activationKey'] );
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return parent::readData( $aDaoParams );
    }

	public function activateByKey( $sKey ) {
		$oUser = clRegistry::get( 'clUser' );
		$this->aDataDict += $oUser->oDao->getDataDict();
		$sKey = $this->oDb->escapeStr( $sKey );
		$aParams = array(
			'entities' => array(
				'entUserPassRetrieval',
				'entUser'
			),
			'entitiesExtended' => 'entUserPassRetrieval LEFT JOIN entUser ON entUserPassRetrieval.retrievalUserId = entUser.userId',
			'criterias' => 'retrievalActivationKey = ' . $sKey,
			'dataEscape' => false
		);
		$aData = array(
			'userPass' => 'retrievalPass'
		);
		if( $this->updateData($aData, $aParams) ) {
			// $aParams = array(
			// 	'criterias' => 'retrievalActivationKey = ' . $sKey
			// );
			// $this->deleteData( $aParams );
			return true;
		}
		return false;
	}

}
