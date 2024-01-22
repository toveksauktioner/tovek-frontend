<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clAclDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entAcl' => array(
				'aclId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
				),
				'aclType' => array(
					'type' => 'array',
					'values' => array(
						'dao' => _( 'DAO' ),
						'view' => _( 'View' ),
						'layout' => _( 'Layout' ),
						'userGroup' => _( 'User group' )
					)
				),
				'aclAroId' => array(
					'type' => 'string',
					'required' => true,
					'index' => true
				),
				'aclAroType' => array(
					'type' => 'array',
					'values' => array(
						'user' => 'user',
						'userGroup' => 'userGroup'
					),
					'required' => true,
					'index' => true
				),
				'aclAcoKey' => array(
					'type' => 'string',
					'required' => true,
					'index' => true
				),
				'aclAccess' => array(
					'type' => 'array',
					'values' => array(
						'deny' => _( 'Deny' ),
						'allow' => _( 'Allow' )
					),
					'required' => true
				)
			)
		);
		$this->sPrimaryField = 'aclId';
		$this->sPrimaryEntity = 'entAcl';
		$this->aFieldsDefault = array(
			'aclAcoKey',
			'aclAccess'
		);
		$this->init();
	}

	public function delete( $aParams ) {
		$aParams += array(
			'acoKey' => null,
			'aclType' => 'dao',
			'aroId' => null,
			'aroType' => null
		);
		$aDaoParams = array();
		$aCriterias = array(
			'aclType = ' . $this->oDb->escapeStr( $aParams['aclType'] )
		);

		if( $aParams['acoKey'] !== null ) {
			if( is_array($aParams['acoKey']) ) {
				$aCriterias[] = 'aclAcoKey IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['acoKey']) ) . ')';
			} else {
				$aCriterias[] = 'aclAcoKey = ' . $this->oDb->escapeStr( $aParams['acoKey'] );
			}
		}
		if( $aParams['aroId'] !== null && $aParams['aroType'] !== null ) {
			if( is_array($aParams['aroId']) ) {
				$aCriterias[] = 'aclAroId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['aroId']) ) . ')';
			} else {
				$aCriterias[] = 'aclAroId = ' . $this->oDb->escapeStr( $aParams['aroId'] );
			}
			$aCriterias[] = 'aclAroType = ' . $this->oDb->escapeStr( $aParams['aroType'] );
		}
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->deleteData( $aDaoParams );
	}

	public function read( $aParams ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'acoKey' => null,
			'aclType' => 'dao',
			'aroId' => null,
			'aroType' => null,
		);

		$aDaoParams = array(
			'fields' => $aParams['fields']
		);
		$aCriterias = array(
			'aclType = ' . $this->oDb->escapeStr( $aParams['aclType'] )
		);
		$aAroCriterias = array();

		if( $aParams['aroId'] !== null ) {
			foreach( $aParams['aroId'] as $sAroType => $aroId ) {
				if( is_array($aroId) ) {
					$aAroCriterias[$sAroType] = '(aclAroId IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aroId) ) . ')';
				} else {
					$aAroCriterias[$sAroType] = '(aclAroId = ' . $this->oDb->escapeStr( $aroId );
				}

				$aAroCriterias[$sAroType] .= ' AND aclAroType = ' . $this->oDb->escapeStr( $sAroType ) . ')';
			}
			$aCriterias[] = '(' . implode( ' OR ', $aAroCriterias ) . ')';
		}

		if( $aParams['aroType'] !== null ) $aCriterias[] = 'aclAroType = ' . $this->oDb->escapeStr( $aParams['aroType'] );

		if( !empty($aParams['acoKey']) ) {
			$aParams['acoKey'] = (array) $aParams['acoKey'];
			$aParams['acoKey'][] = 'superuser';
			$aCriterias[] = 'aclAcoKey IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['acoKey']) ) . ')';
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

}
