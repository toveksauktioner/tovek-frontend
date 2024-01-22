<?php

require_once PATH_CORE . '/clSortingBase.php';

class clOutputHtmlSorting extends clSortingBase {
	
	public $aParams = array(); 

	public function __construct( $oDao, $aParams ) {
		$aParams += array(
			'currentSort' => array(),
			'getVariable' => 'sort'
		);
		
		$this->aParams = $aParams;
		$this->start( $oDao, $aParams['currentSort'] );
	}
	
	public function render() {
		$aOutput = array();
		$aSort = array();
		foreach( $this->aCurrentSort as $key => $value ) {
			if( is_array($value) ) {
				$aSort[$key] = !empty( $value['direction'] ) ? $value['direction'] : '';
			} else {
				$aSort[$key] = $value;
			}
		}
		
		$oRouter = clRegistry::get( 'clRouter' );

		foreach( $this->aSortingDataDict as $sField => $value ) {
			if( !empty($value['notSortable']) ) {
				$aOutput[$sField]['title'] = $value['title'];
				continue;
			}
			
			$bSortActive = array_key_exists($sField, $aSort);
			$aOutput[$sField]['title'] = '<a href="' . $oRouter->sPath . '?' . $this->aParams['getVariable'] . '=' . $sField . '&amp;' . $this->aParams['getVariable'] . 'Direction=' . 
				( ($bSortActive && $aSort[$sField] == 'DESC') ? 'ASC' : 'DESC' ) . '&amp;' . stripGetStr( array($this->aParams['getVariable'], $this->aParams['getVariable'] . 'Direction') ) . '" title="' . $value['title'] . '" class="ajax' . 
				( $bSortActive ? ' sorting-active sorting-' . $aSort[$sField] . '"><span>' . 
				( ($bSortActive && $aSort[$sField] == 'DESC') ? '&#9660;' : '&#9650;' ) . '</span> ' : '">' ) . $value['title'] . '</a>';
		}
		$this->end();
		return $aOutput;
	}

}