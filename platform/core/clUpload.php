<?php

require_once PATH_FUNCTION . '/fFileSystem.php';

class clUpload {
	
	private $aParams = array();
	
	public function __construct( $aParams = array() ) {
		$this->setParams( $aParams );
		foreach( $_FILES as $key => $value ) {
			foreach( $value as $key2 => $value2 ) {
				$_FILES[$key][$key2] = (array) $_FILES[$key][$key2];
			}
		}
	}
	
	public function upload() {
		if( !isset($_FILES[$this->aParams['key']]) ) return false;
		
		if( !is_array($_FILES[$this->aParams['key']]['error']) ) {
			/**
			 * Single upload to multi
			 */
			foreach( $_FILES[$this->aParams['key']] as $sKey => $mValue ) {
				$_FILES[$this->aParams['key']][$sKey] = (array) $mValue;
			}
			if( !is_array($this->aParams['newFileName']) ) {
				$this->aParams['newFileName'] = (array) $this->aParams['newFileName'];
			}
		}
		
		$aErr = array();
		
		foreach( $_FILES[$this->aParams['key']]['error'] as $key => $iErr ) {
			if( $iErr == UPLOAD_ERR_OK ) {
				if( empty($_FILES[$this->aParams['key']]['type'][$key]) ) {
					$_FILES[$this->aParams['key']]['type'][$key] = readMimeType( $_FILES[$this->aParams['key']]['tmp_name'][$key] );
				}
				
				if( $_FILES[$this->aParams['key']]['size'][$key] > $this->aParams['maxSize'] ) {
					// Filesize check
					$aErr[$this->aParams['key']][] = sprintf( $GLOBALS['errorMsg']['validation']['filesize'], $_FILES[$this->aParams['key']]['name'][$key] );
				}
				if( !empty($this->aParams['allowedMime']) && !array_key_exists($_FILES[$this->aParams['key']]['type'][$key], $this->aParams['allowedMime']) ) {
					// Filetype check
					$aErr[$this->aParams['key']][] = sprintf( $GLOBALS['errorMsg']['validation']['filetype'], $_FILES[$this->aParams['key']]['name'][$key] );
				}
				
				if( empty($aErr) ) {
					if( $this->aParams['newFileName'] !== null ) {
						// Rename
						$_FILES[$this->aParams['key']]['name'][$key] = $this->aParams['newFileName'][$key];
					}					
					if( move_uploaded_file($_FILES[$this->aParams['key']]['tmp_name'][$key], $this->aParams['destination'] . $_FILES[$this->aParams['key']]['name'][$key]) === false ) {
						// Could not move file
						$aErr[$this->aParams['key']][] = sprintf( $GLOBALS['errorMsg']['validation']['moveFile'], $_FILES[$this->aParams['key']]['name'][$key] );
					}
				}
			}
		}
		return $aErr;
	}
	
	public function setParams( $aParams ) {
		$aParams += array(
			'allowedMime' => array(),
			'destination' => '',
			'key' => null,
			'maxSize' => $GLOBALS['upload_max_filesize'], // 15 MB
			'newFileName' => null
		);
		$this->aParams = $aParams;
	}
	
}