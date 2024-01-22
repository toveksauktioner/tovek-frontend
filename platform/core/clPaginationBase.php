<?php

interface ifPagination {
	public function render();
}

abstract class clPaginationBase {

	public $iEntriesTotal;
	public $iPageMax;

	protected $iEntries;
	protected $iCurrentPage;
	protected $oDao;
	
	public function start( $oDao, $iCurrentPage = null, $iEntries = null ) {
		if( $iCurrentPage === null ) $iCurrentPage = 1;
		if( $iEntries === null ) $iEntries = 30;

		$this->iEntries = $iEntries;
		$this->oDao = $oDao;
		$this->setCurrentPage( $iCurrentPage );
		$this->oDao->bEntriesTotal = true;
	}
	
	public function end() {
		$this->oDao->bEntriesTotal = false;
		$this->oDao->setEntries( 0 );
		$this->iEntriesTotal = $this->oDao->iLastEntriesTotal;
		
		if( !empty($this->iEntries) ) {
			$this->iPageMax = ceil( $this->iEntriesTotal / $this->iEntries );
		} else {
			$this->iPageMax = $this->iEntriesTotal;
		}
	}

	private function setCurrentPage( $iPage ) {
		$this->iCurrentPage = ( (int) $iPage > 0 ) ? (int) $iPage : 1; 
		
		if( !empty($this->iEntries) ) {
			$this->oDao->setEntries( $this->iEntries, ($this->iCurrentPage - 1) * $this->iEntries );
		}
	}

}