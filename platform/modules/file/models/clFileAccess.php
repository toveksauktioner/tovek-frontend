<?php

require_once PATH_CORE . '/clModuleBase.php';

class clFileAccess extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'File';
		$this->sModulePrefix = 'access';
		
		$this->oDao = clRegistry::get( 'clFileAccessDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/file/models' );
		
		$this->initBase();		
	}
    
    /**
     * Access check method
     */
    public function hasAccess( $iFile, $iUser = null ) {
        $this->oAcl->hasAccess( 'read' . $this->sModuleName );
        if( $iUser === null ) $iUser = $_SESSION['userId'];
        $aData = arrayToSingle( $this->readByUser( $iUser ), 'accessFileId', 'accessStatus' );
        return empty($aData[ $iFile ]) ? false : ( $aData[ $iFile ] == 'allow' ? true : false );
    }
    
    /**
     * Shortcut create method
     */
    public function createByUser( $iUser, $mFile ) {
        $this->oAcl->hasAccess( 'write' . $this->sModuleName );
        $aFiles = is_array($mFile) ? $mFile : (array) $mFile;
        return $this->oDao->createByUser( $iUser, $aFiles );
    }
    
    /**
     * Set/created 'allow' for given user(s) to given file(s)
     */
    public function grantAccess( $mUser, $mFile ) {
        $this->oAcl->hasAccess( 'write' . $this->sModuleName );
        $aUsers = is_array($mUser) ? $mUser : (array) $mUser;
        $aFiles = is_array($mFile) ? $mFile : (array) $mFile;
        return $this->oDao->grantAccess( $aUsers, $aFiles );
    }
    
    /**
     * Set/created 'disallow' for given user(s) to given file(s) without delete
     */
    public function revokeAccess( $mUser, $mFile ) {
        $this->oAcl->hasAccess( 'write' . $this->sModuleName );
        $aUsers = is_array($mUser) ? $mUser : (array) $mUser;
        $aFiles = is_array($mFile) ? $mFile : (array) $mFile;
        return $this->oDao->revokeAccess( $aUsers, $aFiles );
    }
    
    /**
     * Shortcut read method
     */
    public function readByUser( $iUser ) {
        $this->oAcl->hasAccess( 'read' . $this->sModuleName );
        return $this->oDao->read( array( 'user' => $iUser ) );
    }
    
}