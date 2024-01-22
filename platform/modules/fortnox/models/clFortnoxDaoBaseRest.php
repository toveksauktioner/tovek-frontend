<?php

abstract class clFortnoxDaoBaseRest {
	
	public $aDataDict = array();
	public $sPrimaryEntity;
	public $sPrimaryField;
	
	public $aDataActions = array();
	public $aDataFilters = array();
	
	public $aData = array();
	public $aEntities = array();
	public $aFields = array();
	
	protected function init() {
		if( empty($this->sPrimaryEntity) ) $this->sPrimaryEntity = key( $this->aDataDict );
		if( empty($this->aEntities) ) $this->aEntities = array_keys( $this->aDataDict );		
	}
	
	public function getDataDict( $entity = null ) {
		if( $entity === null ) return $this->aDataDict;
		return array_intersect_key( $this->aDataDict, array_flip((array) $entity) );
	}
	
}