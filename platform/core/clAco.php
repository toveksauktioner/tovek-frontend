<?php

require_once PATH_CORE . '/clModuleBase.php';

class clAco extends clModuleBase {
	
	public function __construct() {
		$this->sModulePrefix = 'aco';
		$this->oDao = clRegistry::get( 'clAcoDao' . DAO_TYPE_DEFAULT_ENGINE );
		
		$this->initBase();
	}
	
	public function readByGroup( $sAcoGroup ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );		
		return $this->oDao->readData( array(
			'criterias' => 'acoGroup = ' . $this->oDao->oDb->escapeStr( $sAcoGroup )
		) );
	}
	
	public function deleteByGroup( $sAcoGroup ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$oAcl = clRegistry::get( 'clAcl' );
		$aAcoKeys = arrayToSingle( $this->readByGroup( $sAcoGroup ), null, 'acoKey' );
		if( !empty($aAcoKeys) ) {
			$oAcl->oDao->delete( array(
				'acoKey' => $aAcoKeys
			) );
		}
		
		return $this->oDao->deleteData( array(
			'criterias' => 'acoGroup = ' . $this->oDao->oDb->escapeStr( $sAcoGroup )
		) );
	}
	
	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$oAcl = clRegistry::get( 'clAcl' );
		$oAcl->oDao->delete( array(
			'acoKey' => arrayToSingle( $this->read( 'acoKey', $primaryId ), null, 'acoKey' )
		) );
		
		return parent::delete( $primaryId );
	}
}