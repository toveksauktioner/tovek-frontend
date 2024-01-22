<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clVatDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entVat' => array(
				'vatId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'vatCountryId' => array(
					'type' => 'integer',
					'index' => true,
					'required' => true,
					'title' => _( 'Country' )
				),
				'vatValue' => array(
					'type' => 'float',
					'title' => _( 'Vat' )
				),
				'vatCreated' => array(
					'type' => 'datetime'
				)
			)
		);

		$this->sPrimaryField = 'vatId';
		$this->sPrimaryEntity = 'entVat';
		$this->aFieldsDefault = array(
			'vatId',
			'vatCountryId',
			'vatValue'
		);

		$this->init();
	}

	public function deleteByCountry( $countryId ) {
		$aParams = array();

		if( is_array($countryId) ) {
			$aParams['criterias'] = "vatCountryId IN (" . implode( ", ", array_map('intval', $countryId) ) . ")";
		} else {
			$aParams['criterias'] = 'vatCountryId = ' . (int) $countryId;
		}

		return $this->deleteData( $aParams );
	}

	public function readByCountry( $countryId, $aFields = array() ) {
		$aParams = array(
			'fields' => $aFields
		);

		if( is_array($countryId) ) {
			$aParams['criterias'] = "vatCountryId IN (" . implode( ", ", array_map('intval', $countryId) ) . ")";
		} else {
			$aParams['criterias'] = 'vatCountryId = ' . (int) $countryId;
		}

		return $this->readData( $aParams );
	}

	public function updateByCountry( $countryId, $aData ) {
		if( is_array($countryId) ) {
			$aParams['criterias'] = "vatCountryId IN (" . implode( ", ", array_map('intval', $countryId) ) . ")";
		} else {
			$aParams['criterias'] = 'vatCountryId = ' . (int) $countryId;
		}

		return $this->updateData( $aData, $aParams );
	}

}