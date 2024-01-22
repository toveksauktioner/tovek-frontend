<?php

class clJournalHelperDaoSql {

	public $aParams = array();
	public $oDao;

	public function __construct( clDaoBaseSql $oDao, $aParams = array() ) {
		$this->oDao = $oDao;

		$aParams += array(
			'aJournalFields' => array()
		);
		$this->aParams = $aParams;
	}

	public function read( $aParams = array(), $aDaoParams = array() ) {
		$aParams += array(
			'fields' => $this->oDao->aFieldsDefault,
			'journalId' => null,
			'status' => 'active',
			'mode' => 'current'
		);
		$aCriterias = array();

		$aDaoParams += array(
			'fields' => $aParams['fields']
		);

		if( $aParams['status'] !== null ) {
			if( is_array($aParams['status']) ) {
				$aParams['status'] = array_map( array($this->oDao->oDb, 'escapeStr'), $aParams['status'] );
				$aCriterias[] = $this->aParams['aJournalFields']['status'] . ' IN(' . implode( ', ', $aParams['status'] ) . ')';
			} else {
				$aCriterias[] = $this->aParams['aJournalFields']['status'] . ' = ' . $this->oDao->oDb->escapeStr( $aParams['status'] );
			}
		}

		if( $aParams['mode'] !== null ) {
			switch( $aParams['mode'] ) {
				case 'future':
					$aCriterias[] = '(' . $this->aParams['aJournalFields']['publishStart'] . ' > NOW() OR ' . $this->aParams['aJournalFields']['publishStart'] . ' = "0000-00-00")';
					break;
				case 'past':
					$aCriterias[] = '(' . $this->aParams['aJournalFields']['publishEnd'] . ' < NOW() OR ' . $this->aParams['aJournalFields']['publishEnd'] . ' = "0000-00-00")';
					break;
				case 'current':
				default:
					$aCriterias[] = '(' . $this->aParams['aJournalFields']['publishStart'] . ' <= NOW() OR ' . $this->aParams['aJournalFields']['publishStart'] . ' = "0000-00-00")';
					$aCriterias[] = '(' . $this->aParams['aJournalFields']['publishEnd'] . ' >= NOW() OR ' . $this->aParams['aJournalFields']['publishEnd'] . ' = "0000-00-00")';
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = ( !empty($aDaoParams['criterias']) ? $aDaoParams['criterias'] . ' AND ' : '' ) . implode( ' AND ', $aCriterias );

		if( $aParams['journalId'] !== null ) return $this->oDao->readDataByPrimary( $aParams['journalId'], $aDaoParams );
		return $this->oDao->readData( $aDaoParams );
	}

}