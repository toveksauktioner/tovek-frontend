<?php

require_once PATH_CORE . '/clModuleBase.php';

class clFaqQuestion extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'FaqQuestion';
		$this->sModulePrefix = 'question';
		
		$this->oDao = clRegistry::get( 'clFaqQuestionDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/faq/models' );
		
		$this->initBase();		
	}

	public function read( $aFields = array(), $mQuestionId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'questionId' => $mQuestionId,
			'status' => 'active'
		) );
	}
	
	public function readAll( $aFields = array(), $mQuestionId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'questionId' => $mQuestionId,
			'status' => '*'
		) );
	}
	
	public function readByCategory( $mCategory, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'categoryId' => $mCategory,
			'status' => 'active'
		) );
	}
	
	public function readAllByCategory( $mCategory, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'categoryId' => $mCategory,
			'status' => '*'
		) );
	}
	
	public function delete( $iPrimaryId ) {
		// Delete news
		$result = parent::delete( $iPrimaryId );
		if( is_int($result) ) {			
			// Route
			$oRouter = clRegistry::get( 'clRouter' );
			$iRouteId = current(current( $oRouter->readByObject( $iPrimaryId, $this->sModuleName, 'entRoute.routeId' ) ));		
			if( !empty($iRouteId) ) {
				// Delete route
				$oRouter->deleteRouteToObjectByRoute( $iRouteId );
				$oRouter->delete( $iRouteId );
			}
		}		
		return $result;
	}
	
	public function updateSort() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		if( empty($_GET['categoryId']) ) return false;
		
		$aPrimaryIds = func_get_args();
		return $this->oDao->updateSort( $aPrimaryIds, $_GET['categoryId'] );
	}
	
}