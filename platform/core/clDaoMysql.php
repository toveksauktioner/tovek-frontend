<?php

class clDaoMysql {
	private $oDao;
	
	public function __construct( $oDao = null ) {
		if( $oDao !== null ) $this->setDao( $oDao );
	}
	
	public function updateSortOrder( $aPrimaryIds, $sSortCol, $aParams ) {
		$aParams += array(
			'primaryField' => $this->oDao->sPrimaryField
		);
		
		$sPrimary = implode( ', ', $aPrimaryIds );
		$aParams['criterias'] = ( !empty($aParams['criterias']) ? $aParams['criterias'] . ' AND ' : '' ) . $aParams['primaryField'] . ' IN(' . $sPrimary . ')';
		return $this->oDao->oDb->write( 'UPDATE ' . $aParams['entities'] . ' SET ' . $sSortCol . ' = FIELD(' . $aParams['primaryField'] . ', ' . $sPrimary . ') WHERE ' . $aParams['criterias'] );
	}
	
	public function setDao( $oDao ) {
		$this->oDao = $oDao;
	}
}
