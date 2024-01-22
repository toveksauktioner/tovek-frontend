<?php

interface ifSorting {
	public function render();
}

abstract class clSortingBase {

	protected $aDataDict;
	protected $aSortingDataDict;
	protected $oDao;
	protected $aCurrentSort;
	
	public function start( $oDao, $aCurrentSort = array() ) {
		$aDirection = array(
			'ASC',
			'DESC'
		);
		$this->oDao = $oDao;
		
		// Only allow proper sorting direction. Avoids SQL injections.
		if( !empty($aCurrentSort) ) {
			foreach( $aCurrentSort as $sField => $sDirection ) {
				if( !in_array(mb_strtoupper($sDirection), $aDirection ) ) {
					unset( $aCurrentSort[$sField] );
				}
			}
		}
		
		$aDataDict = $oDao->getDataDict();
		$this->aDataDict = array();
		foreach( $aDataDict as $value ) {
			$this->aDataDict += $value;
		}

		$this->aSortingDataDict = $this->aDataDict;
		
		$this->aCurrentSort = !empty( $aCurrentSort ) ? (array) $aCurrentSort : array( key($this->aDataDict) => '' );
		$this->setDaoSorting();
	}
	
	public function end() {
		$this->oDao->aSorting = array();
	}
	
	public function setDaoSorting() {
		// Only allow sorting on fields in $this->aSortingDataDict
		$this->oDao->aSorting = array_intersect_key( $this->aCurrentSort, $this->aSortingDataDict );
	}
	
	public function setSortingDataDict( $aSortingDataDict ) {
		foreach( $this->aDataDict as $key => $value ) {
			if( isset($aSortingDataDict[$key]) ) {
				$aSortingDataDict[$key] += $this->aDataDict[$key];
			}
		}
		$this->aSortingDataDict = $aSortingDataDict;
		$this->setDaoSorting();
	}

}