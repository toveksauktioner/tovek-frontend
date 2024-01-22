<?php

require_once PATH_CORE . '/clModuleBase.php';

class clArgoCron extends clModuleBase {

	private $aCronJobs;

	public function __construct() {
		$this->sModuleName = 'ArgoCron';
		$this->sModulePrefix = 'argoCron';
		
		$this->oDao = clRegistry::get( 'clArgoCronDao' . DAO_TYPE_DEFAULT_ENGINE );
		
		$this->initBase();		
		$this->fetchAll();
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array('groupKey' => 'createArgoCron');
		$aData['cronCreated'] = date( 'Y-m-d H:i:s' );		
		if( $this->oDao->createData($aData, $aParams) ) return $this->oDao->oDb->lastId();
		return false;
	}
	
	public function fetchAll() {
		$aData = $this->oDao->readData();
		if( !empty($aData) ) {
			$this->aCronJobs = valueToKey( 'cronLayoutKeyTrigger', $aData );
		} else {
			$this->aCronJobs = false;
		}
	}
	
	/**
	 * @return boolen true equal to a valid check
	 */
	public function check( $sCurrentLayoutKey ) {
		if( $this->aCronJobs === false ) return true;
		if( !array_key_exists($sCurrentLayoutKey, $this->aCronJobs) ) return true;
		
		$aCronJob = &$this->aCronJobs[ $sCurrentLayoutKey ];		
		if( (time() - $aCronJob['cronLastRun']) < $aCronJob['cronTimeInterval'] ) return true;
		
		// Update last run time before to prevent overlapping runs
		$this->oDao->updateDataByPrimary( $aCronJob['cronId'], array(
			'cronLastRun' => time()
		) );
		
		switch( $aCronJob['cronType'] ) {
			case 'file':
				include $aCronJob['cronTypeRelation'];
				break;
			case 'event':
				$this->oEventHandler->triggerEvent( array(
					$aCronJob['cronTypeRelation'] => array( true, $this->sModuleName ),
				), 'internal' );
				break;
			default:
				break;
		}
		
		return true;
	}
	
}