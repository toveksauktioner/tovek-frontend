<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clUserAgreementDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entUserAgreement' => array(
				'agreementId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'agreementTitleTextId' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'agreementHighlightsTextId' => array(
					'type' => 'string',
					'title' => _( 'Highlights' )
				),
				'agreementContentTextId' => array(
					'type' => 'string',
					'title' => _( 'Content' ),
					'required' => true
				),
				'agreementRequired' => array(
					'type' => 'array',
					'values' => array(
						'new' => _( 'New registrations' ),
						'all' => _( 'All users' )
					),
					'title' => _( 'Required for' )
				),
				// Misc
				'agreementCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'agreementActivated' => array(
					'type' => 'datetime',
					'title' => _( 'Activated' )
				)
			)
		);

		$this->sPrimaryField = 'agreementId';
		$this->sPrimaryEntity = 'entUserAgreement';
		$this->aFieldsDefault = '*';
		$this->aSortingDefault = array(
			'agreementActivated' => 'DESC'
		);

		$this->init();

		$this->aHelpers = array(
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'agreementTitleTextId',
					'agreementHighlightsTextId',
					'agreementContentTextId',
				),
				'sTextEntity' => 'entUserAgreementText'
			) )
		);
	}

	public function read( $aParams = array() ) {
		$aParams += array(
			'fields' => $this->aFieldsDefault,
			'agreementId' => null,
			'agreementRequired' => null,
			'activated' => null,
			'sorting' => null
		);
		$aCriterias = array();

		$aDaoParams = array(
			'fields' => $aParams['fields'],
		);

		if( $aParams['agreementId'] !== null ) {
			if( is_array($aParams['agreementId']) ) {
				$aCriterias[] = 'agreementId IN(' . implode( ', ', array_map('intval', $aParams['agreementId']) ) . ')';
			} else {
				$aCriterias[] = 'agreementId = ' . (int) $aParams['agreementId'];
			}
		}
		if( $aParams['agreementRequired'] !== null ) {
			$aCriterias[] = 'agreementRequired = ' . $this->oDb->escapeStr( $aParams['agreementRequired'] );
		}
		if( $aParams['activated'] === true ) {
			$aCriterias[] = 'agreementActivated IS NOT NULL ';
		}
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		$aDaoParams['sorting'] = ( !empty($aParams['sorting']) ? $aParams['sorting'] : $this->aSortingDefault );

		return $this->readData($aDaoParams);
	}
}
