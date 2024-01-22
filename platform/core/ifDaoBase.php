<?php

interface ifDaoBase {

	public function createData( $aData, $aParams = array() );
	public function createMultipleData( $aData, $aParams = array() );
	
	public function deleteData( $aParams = array() );
	public function deleteDataByPrimary( $primary, $aParams = array() );
	
	public function readData( $aParams = array() );
	public function readDataByPrimary( $primary, $fields = array() );
	public function readLastId();
	
	public function updateData( $aData, $aParams = array() );
	public function updateDataByPrimary( $primary, $aData, $aParams = array() );

}