<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/infoContent/config/cfInfoContent.php';

class clInfoContent extends clModuleBase {

    public function __construct() {
        $this->sModulePrefix = 'content';
		$this->sModuleName = 'InfoContent';

        $this->oDao = clRegistry::get( 'clInfoContentDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/infoContent/models' );
		$this->initBase();
    }

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;

		$aData[$this->sModulePrefix . 'Created'] = date( 'Y-m-d H:i:s' );
		$aData[$this->sModulePrefix . 'Updated'] = date( 'Y-m-d H:i:s' );
		$aData['textLangId'] = $GLOBALS['defaultLangId'];

		$oViewHtml = clRegistry::get( 'clViewHtml' );
		$aData['contentViewId'] = $oViewHtml->create( array('viewModuleKey' => 'infoContent', 'viewFile' => 'show.php') );
		if( $aData['contentViewId'] === false ) return false;

		$aParams['groupKey'] = 'createInfoContent';
		return $this->oDao->createData( $aData, $aParams ) ? $this->oDao->oDb->lastId() : false;
	}
	
	public function createRevision( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$aData['revisionLangId'] = $this->oDao->iLangId;
		$aData['revisionCreated'] = date( 'Y-m-d H:i:s' );
		
		$aParams['groupKey'] = 'createInfoContent';
		return $this->oDao->createRevision( $aData, $aParams ) ? $this->oDao->oDb->lastId() : false;
	}

	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$iViewId = $this->read( 'contentViewId', $primaryId );
		if( !empty($iViewId) ) {
			$iViewId = current( current($iViewId) );

			$this->oEventHandler->triggerEvent( array(
				'preDeleteView' => $iViewId
			), 'internal' );
		}

		return $this->oDao->delete( $primaryId );
	}
	
	public function deleteRevision( $primaryId = null ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		if( $primaryId === null ) return false;
		
		return $this->oDao->deleteRevision( $primaryId );
	}

	public function importRevision( $primaryId = null ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		if( $primaryId === null ) return false;
		
		$aRevisionData = $this->readRevisionByPrimary( array(
			'revisionContent',
			'contentId',
			'revisionLangId'
		), $primaryId );
		if( empty($aRevisionData) ) return false;
		
		$this->oDao->setLang( $aRevisionData[0]['revisionLangId'] );
		return $this->update( $aRevisionData[0]['contentId'], array(
			'contentTextId' => $aRevisionData[0]['revisionContent']
		) );
	}

	public function read( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		if( $primaryId !== null ) {
			$aParams['contentId'] = $primaryId;
			return $this->oDao->read( $aParams );
		}
		return $this->oDao->read( $aParams );
	}

	public function readByKey( $key, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' 	=> $aFields,
			'key' 		=> $key
		);

		return $this->oDao->read( $aParams );
	}

	public function readByView( $viewId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'viewId' => $viewId
		);

		return $this->oDao->read( $aParams );
	}

	public function readRevision( $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		
		return $this->oDao->readRevision( $aParams );
	}

	public function readRevisionByPrimary( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		if( $primaryId === null ) return false;
		$aParams = array(
			'fields' => $aFields,
			'langId' => null
		);
		if( $primaryId !== null ) {
			$aParams['revisionId'] = $primaryId;
		}
		
		return $this->oDao->readRevision( $aParams );
	}

	public function readRevisionByContentId( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		if( $primaryId !== null ) {
			$aParams['contentId'] = $primaryId;
		}
		
		return $this->oDao->readRevision( $aParams );
	}

	public function update( $iPrimaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );		
		$aParams['groupKey'] = 'updateInfoContent';
		$aData[$this->sModulePrefix . 'Updated'] = date( 'Y-m-d H:i:s' );
		
		return $this->oDao->updateDataByPrimary( $iPrimaryId, $aData, $aParams );
	}
	
	public function updateRevision( $iPrimaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'updateInfoContent';
		return $this->oDao->updateRevision( $iPrimaryId, $aData );
	}

}
