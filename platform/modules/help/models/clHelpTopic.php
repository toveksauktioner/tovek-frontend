<?php

require_once PATH_CORE . '/clModuleBase.php';

class clHelpTopic extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'HelpTopic';
		$this->sModulePrefix = 'helpTopic';

		$this->oDao = clRegistry::get( 'clHelpTopicDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/help/models' );

		$this->initBase();
	}

	public function read( $aFields = array(), $mTopicId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'topicId' => $mTopicId,
			'status' => 'active'
		) );
	}

	public function readAll( $aFields = array(), $mTopicId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'topicId' => $mTopicId,
			'status' => '*'
		) );
	}

	public function readWithCategory( $aFields = array(), $mTopicId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'withCategory' => true,
			'topicId' => $mTopicId,
			'status' => 'active'
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

	// public function updateSort() {
	// 	$this->oAcl->hasAccess( 'write' . $this->sModuleName );
	// 	if( empty($_GET['categoryId']) ) return false;
	//
	// 	$aPrimaryIds = func_get_args();
	// 	return $this->oDao->updateSort( $aPrimaryIds, $_GET['categoryId'] );
	// }

	// Topic to category relation
	public function readTopicToCategory( $mTopicId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readTopicToCategory( $mTopicId );
	}
	public function createTopicToCategory( $mTopicId, $mCategoryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createTopicToCategory( $mTopicId, $mCategoryId );
	}
	public function deleteTopicToCategory( $mTopicId, $mCategoryId = null ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteTopicToCategory( $mTopicId, $mCategoryId );
	}

	// Read by category
	public function readByCategory( $mCategoryId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'categoryId' => $mCategoryId
		) );
	}
	public function readAllByCategory( $mCategoryId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'categoryId' => $mCategoryId,
			'status' => '*'
		) );
	}

	// Topic relations
	public function readTopicRelation( $mTopicId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readTopicToCategory( $mTopicId );
	}
	public function createTopicRelation( $mTopicId, $mRelationId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createTopicToCategory( $mTopicId, $mRelationId );
	}
	public function deleteTopicRelation( $iTopicId, $iRelationId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteTopicToCategory( $iTopicId, $iRelationId );
	}

}
