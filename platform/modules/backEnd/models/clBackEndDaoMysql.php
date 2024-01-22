<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clBackEndDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		// Pre-define empty variables
        $this->aDataDict = array();		
		$this->sPrimaryEntity = '';
        $this->sPrimaryField = '';		
		$this->aFieldsDefault = '*';
		
		$this->init();
        
        // Switch database
        $this->switchToSecondary();
	}
    
    /**
     * Set source
     */
    public function setSource( $sEntity, $sField ) {
		$this->sPrimaryEntity = $sEntity;
        $this->sPrimaryField = $sField;		
    }
}