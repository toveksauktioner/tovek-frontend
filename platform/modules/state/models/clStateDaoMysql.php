<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clStateDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entState' => array(
				'stateId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
                'stateTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'stateCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			),
            'entStateCommunal' => array(
				'communalId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
                'communalStateId' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
                'communalTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'communalCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		
		$this->sPrimaryField = 'stateId';
		$this->sPrimaryEntity = 'entState';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}
    
    public function addCommunal( $iStateId, $aCommunal ) {
        $aData = array();
        foreach( $aCommunal as $sCommunal ) {
            $aData[] = array(
                'communalStateId' => $iStateId,
                'communalTitle' => $sCommunal,
                'communalCreated' => date( 'Y-m-d H:i:s' )
            );
        }
        return $this->createMultipleData( $aData, array(
            'entities' => 'entStateCommunal',
            'fields' => array(
                'communalStateId',
                'communalTitle',
                'communalCreated'
            )
        ) );
    }
    
	public function readCommunal( $aParams = array() ) {
        $aParams += array(
			'fields' => array_keys( $this->aDataDict['entStateCommunal'] ),
            'stateId' => null
		);
		
		$aDaoParams = array(
			'entities' => 'entStateCommunal',
			'fields' => $aParams['fields']
		);
		
		$aCriterias = array();
		
		if( $aParams['stateId'] !== null ) {
			if( is_array($aParams['stateId']) ) {
				$aCriterias[] = 'communalStateId IN(' . implode( ', ', array_map('intval', $aParams['stateId']) ) . ')';
			} else {
				$aCriterias[] = 'communalStateId = ' . (int) $aParams['stateId'];
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return parent::readData( $aDaoParams );
    }
	
}