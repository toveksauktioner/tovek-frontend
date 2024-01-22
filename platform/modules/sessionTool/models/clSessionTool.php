<?php

require_once PATH_CORE . '/clModuleBase.php';

class clSessionTool extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'SessionTool';
		$this->sModulePrefix = 'sessionTool';
		
		$this->oDao = clRegistry::get( 'clSessionToolDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/sessionTool/models' );
		
		$this->initBase();		
	}
	
	/**
	 * Create and/or update should not be preformed by this module
	 */
	public function create( $aData ) { return false; }	
	public function update( $sSessionId, $aData ) { return false; }
	
	public function read( $aFields = array(), $sSessionId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'sessionId' => $sSessionId
		) );
	}
	
	public function readByUser( $mUserId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'fields' => $aFields,
			'userId' => $mUserId
		) );
	}
	
	public function unserialize_php($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
	
}