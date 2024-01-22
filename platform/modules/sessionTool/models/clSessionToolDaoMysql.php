<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clSessionToolDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entSessionStorage' => array(
				'sessionId' => array(
					'type' => 'integer',
					'title' => _( 'ID' ),
					'primary' => true,
					'autoincrement' => true					
				),
				'sessionLastIp' => array(
					'type' => 'integer',
					'title' => _( 'Last IP' ),
					'required' => true
				),
				'sessionLastIpGeo' => array(
					'type' => 'string',
					'title' => _( 'Last IP geo' )
				),
				'sessionUserAgent' => array(
					'type' => 'string',
					'title' => _( 'User agent' ),
					'required' => true
				),
				'sessionData' => array(
					'type' => 'string',
					'title' => _( 'Data' ),
					'required' => true
				),
				'sessionUserId' => array(
					'type' => 'integer',
					'title' => _( 'User ID' )
				),
				'sessionTimestamp' => array(
					'type' => 'integer',
					'title' => _( 'Timestamp' ),
					'required' => true
				)
			)
		);
		
		$this->sPrimaryField = 'sessionId';
		$this->sPrimaryEntity = 'entSessionStorage';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}
	
	public function read( $aParams = array() ) {
        $aParams += array(
			'fields' => $this->aFieldsDefault,
            'sessionId' => null,
			'userId' => null
		);
		
		$aDaoParams = array(
			'fields' => $aParams['fields']
		);
		
		$aCriterias = array();
		
		if( $aParams['sessionId'] !== null ) {
			$aCriterias[] = 'sessionId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), (array) $aParams['sessionId']) ) . ')';			
		}
		
		if( $aParams['userId'] !== null ) {
			$aCriterias[] = 'sessionUserId IN(' . implode( ', ', array_map('intval', (array) $aParams['userId']) ) . ')';			
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return parent::readData( $aDaoParams );
    }
	
}