<?php

require_once PATH_CORE . '/clModuleBase.php';

class clFile extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'file';

		$this->oDao = clRegistry::get( 'clFileDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/file/models' );
		$this->initBase();
	}
	
	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		
		$aData += array(
			$this->sModulePrefix . 'Created' => date( 'Y-m-d H:i:s' ),
			'fileExtension' => getFileExtension( $aData['filename'] )
		);
		
		// Check file size
		$aFiles = current( $_FILES );
		foreach( $aFiles['size'] as $iKey => $iSize ) {
			if( $iSize > $GLOBALS['upload_max_filesize'] ) {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataError' => sprintf( $GLOBALS['errorMsg']['validation']['filesize'], $aFiles['name'][ $iKey ], $GLOBALS['uploadMaxSizeLabel'] )
				) );
				return false;
			}
		}
		
		if( $this->oDao->createData($aData, $aParams) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
			return $this->oDao->oDb->lastId();
		}
		return false;
	}
	
	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$aFiles = $this->read( array(
			'fileId',
			'filename',
			'fileParentType',
			'fileParentId'
		), $primaryId );
		
		foreach( $aFiles as $entry ) {
			if( is_file(PATH_CUSTOM_FILE . '/' . $entry['fileParentType'] . '/' . $entry['filename']) ) unlink( PATH_CUSTOM_FILE . '/' . $entry['fileParentType'] . '/' . $entry['filename'] );
		}
		
		return $this->oDao->deleteDataByPrimary( $primaryId );
	}
	
	public static function deleteByParent( $iParentId, $sParentType ) {
		$aPrimaryIds = array();
		$oFile = clRegistry::get( 'clFile', PATH_MODULE . '/product/models' );
		$aFiles = $oFile->readByParent( $iParentId, $sParentType, array(
			'fileId',
			'filename',
			'fileParentType',
			'fileParentId'
		) );
		
		foreach( $aFiles as $entry ) {
			if( is_file(PATH_CUSTOM_FILE . '/' . $entry['fileParentType'] . '/' . $entry['filename']) ) unlink( PATH_CUSTOM_FILE . '/' . $entry['fileParentType'] . '/' . $entry['filename'] );
		}
		
		if( !empty($aPrimaryIds) ) return $this->oDao->deleteDataByPrimary( $aPrimaryIds );
		return false;
	}
	
	public function readByParent( $parentId, $sParentType, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'parentType' => $sParentType,
			'parentId' => $parentId
		);
		return $this->oDao->read( $aParams );
	}

	/**
	 * Download file
	 */
	public function getFile( $iFileId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		if( empty($_SESSION['userId']) ) return false;
		
		$oAccess = clFactory::create( 'clFileAccess', PATH_MODULE . '/file/models' );
		if( !$oAccess->hasAccess($iFileId, $_SESSION['userId']) ) return false;
		
		$aData = current( $this->read( '*', $iFileId ) );
		$sFile = PATH_CUSTOM_FILE . '/' . $aData['fileParentType'] . '/' . $aData['filename'];
		if( !file_exists($sFile) ) return false;
		
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream');
		header( 'Content-Disposition: attachment; filename=' . sprintf('"%s"', addcslashes(basename($sFile), '"\\')) ); 
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Connection: Keep-Alive' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize($sFile) );
		
		readfile( $sFile );
		die();
	}
	
}
