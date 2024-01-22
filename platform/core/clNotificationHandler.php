<?php

class clNotificationHandler {
	
	public $aNotifications = array();
	public $aErrors = array();
	
	public function __construct() {
		if( !isset($_SESSION['notification']) ) {
			$_SESSION['notification'] = array();
		}
	}
	
	public function set( $aNotifications ) {
		$this->aNotifications += (array) $aNotifications;
	}
	
	public function get( $aKeys = array() ) {
		return !empty( $aKeys ) ? array_intersect_key($this->aNotifications, array_flip($aKeys)) : $this->aNotifications;
	}
	
	public function add( $sText ) {
		$this->aNotifications[] = $sText;
	}
	
	public function setError( $aErrors ) {
		$this->aErrors += (array) $aErrors;
	}
	
	public function getError( $aKeys = array() ) {
		return !empty( $aKeys ) ? array_intersect_key($this->aErrors, array_flip($aKeys)) : $this->aErrors;
	}
	
	public function addError( $sError ) {
		$this->aErrors[] = $sError;
	}
	
	public function setSessionNotifications( $aNotifications ) {
		$_SESSION['notification'] += (array) $aNotifications;
	}
	
	public function addSessionNotifications() {
		$this->set( $_SESSION['notification'] );
		$_SESSION['notification'] = array();
	}
	
}
