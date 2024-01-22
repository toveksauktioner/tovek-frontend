<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clParentChildDaoMysql extends clDaoBaseSql {

	private $oDao;

	public function __construct( clDaoBaseSql $oDao ) {
		$this->oDao = $oDao;
	}

	public function read( $aParams = array(), $aDaoParams = array() ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'journalId' => null,
			'status' => 'active',
			'mode' => 'current'
		);
		$aCriterias = array();

		$aDaoParams += array(
			'fields' => $aParams['fields']
		);

		if( $aParams['status'] !== null ) $aCriterias[] = $this->aJournalFields['status'] . ' = ' . $this->oDb->escapeStr( $aParams['status'] );

		if( $aParams['mode'] !== null ) {
			switch( $aParams['mode'] ) {
				case 'future':
					$aCriterias[] = $this->aJournalFields['publishStart'] . ' > NOW()';
					break;
				case 'past':
					$aCriterias[] = $this->aJournalFields['publishEnd'] . ' < NOW()';
					break;
				case 'current':
				default:
					$aCriterias[] = $this->aJournalFields['publishStart'] . ' <= NOW()';
					$aCriterias[] = $this->aJournalFields['publishEnd'] . ' >= NOW()';
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = ( !empty($aDaoParams['criterias']) ? $aDaoParams['criterias'] . ' AND ' : '' ) . implode( ' AND ', $aCriterias );

		if( $aParams['journalId'] !== null ) return $this->readDataByPrimary( $aParams['journalId'], $aDaoParams );
		return $this->readData( $aDaoParams );
	}

}
