<?php

/* * * *
 * Filename: clUserNoteDaoMysql.php
 * Created: 13/10/2015 by Renfors
 * Reference: database-overview.mwb
 * Description: See clUserNote.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clUserNoteDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entUserNote' => array(
				'userNoteId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Note ID' )
				),
				'noteWriter' => array(
					'type' => 'string',
					'title' => _( 'Writer' )
				),
				'noteTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'noteMessage' => array(
					'type' => 'string',
					'title' => _( 'Message' )
				),
				'noteCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				// Foreign key's
				'noteUserId' => array(
					'type' => 'integer'
				)
			)
		);
		$this->sPrimaryEntity = 'entUserNote';
		$this->sPrimaryField = 'userNoteId';		
		$this->aFieldsDefault = array( '*' );
		
		$this->init();
	}

	/* * *
	 * Combined dao function for reading data
	 * based on foreign key's
	 * * */
	public function readByForeignKey( $aParams ) {
		$aDaoParams = array();
		$sCriterias = array();
		
		$aParams += array(
			'noteUserId' => null
		);
		
		$aDaoParams['fields'] = $aParams['fields'];
		
		if( $aParams['noteUserId'] !== null ) {
			if( is_array($aParams['noteUserId']) ) {
				$aCriterias[] = 'noteUserId IN(' . implode( ', ', array_map('intval', $aParams['noteUserId']) ) . ')';
			} else {
				$aCriterias[] = 'noteUserId = ' . (int) $aParams['noteUserId'];
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->readData( $aDaoParams );
	}
	
}
