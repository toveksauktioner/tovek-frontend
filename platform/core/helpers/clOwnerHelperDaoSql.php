<?php

class clOwnerHelperDaoSql {

	public $ownerId = null;

	private $sOwnerField;

	public function __construct( $aParams ) {
		if( isset($aParams['ownerId']) ) $this->ownerId = $aParams['ownerId'];
		if( !empty($aParams['sOwnerField']) ) $this->sOwnerField = $aParams['sOwnerField'];
	}

	public function createData( $aData, $aParams ) {
		$aData[$this->sOwnerField] = $this->ownerId;
		return array(
			'aData' => $aData,
			'aParams' => $aParams
		);
	}

	public function deleteData( $aParams ) {
		return $this->setCriteria( $aParams );
	}

	public function readData( $aParams ) {
		return $this->setCriteria( $aParams );
	}

	public function setCriteria( $aParams ) {
		$sCriteria = '';
		if( $this->ownerId !== null ) {
			if( is_array($this->ownerId) ) {
				$sCriteria .= $this->sOwnerField . ' IN(' . implode( ', ', array_map('intval', $this->ownerId) ) . ')';
			} else {
				$sCriteria .= $this->sOwnerField . ' = ' . (int) $this->ownerId;
			}
			$aParams['criterias'] = ( !empty($aParams['criterias']) ? $aParams['criterias'] . ' AND ' : '' ) . $sCriteria;
		}
		return $aParams;
	}

	public function updateData( $aData, $aParams ) {
		$aParams = $this->setCriteria( $aParams );
		return array(
			'aData' => $aData,
			'aParams' => $aParams
		);
	}

}
