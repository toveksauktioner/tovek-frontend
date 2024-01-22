<?php

require_once PATH_FUNCTION . '/fOutputHtml.php';

class clOutputHtmlGridTable {

	private $aAttributes = array();
	private $aData = array();
	private $aDataDict = array();
	private $aTableDataDict = array();
	private $sBody = '';
	private $sFooter = '';
	private $sPagination = '';
	private $sTitle;
	private $sRowGridStyle;

	/**
	 *
	 */
	public function __construct( $aDataDict = array(), $aParams = array() ) {
		$this->init( $aDataDict, $aParams );
	}

	public function init( $aDataDict, $aParams = array() ) {
		$aParams += array(
			'attributes' => array(),
			'data' => null,
			'title' => '',
			'rowGridStyle' => null
		);

		$this->aDataDict = array();
		foreach( $aDataDict as $value ) {
			$this->aDataDict += $value;
		}
		$this->aTableDataDict = $this->aDataDict;

		if( $aParams['data'] !== null ) $this->aData = $aParams['data'];

		$this->aAttributes = $aParams['attributes'];
		$this->sTitle = $aParams['title'];
		$this->sRowGridStyle = $aParams['rowGridStyle'];
	}

	public function addBodyEntry( $aEntry, $aAttributes = array() ) {
		if( !empty($this->sRowGridStyle) ) $aAttributes['style'] = 'grid-template-columns: ' . $this->sRowGridStyle . ';';
    $this->sBody .= self::createRow( self::createDataRow( $aEntry ), $aAttributes );
	}

	public function addBodyGroup( $aGroup, $aAttributes = array() ) {
		$sContent = '';
		foreach( $aGroup as $sClass => $aRowEntry ) {
			$sContent .= self::createRow( self::createDataRow($aRowEntry), array(
				'class' => $sClass
			) );
		}
		$this->sBody .= '
			<article' . createAttributes( $aAttributes ) . '>' . $sContent . '</article>';
	}

	public function addFooterEntry( $aEntry, $aAttributes = array() ) {
		if( !empty($this->sRowGridStyle) ) $aAttributes['style'] = 'grid-template-columns: ' . $this->sRowGridStyle . ';';
		$this->sFooter .= self::createRow( self::createDataRow($aEntry), $aAttributes );
	}

	public static function createData( $sContent, $aAttributes = array() ) {
    if( !empty($aAttributes['class']) ) {
      $aAttributes['class'] .= ' dataCell';
    } else {
      $aAttributes['class'] = 'dataCell';
    }

		return '
					<div' . createAttributes( $aAttributes ) . '>' . $sContent . '</div>';
	}

	public function addPagination( $sPagination ) {
    $this->sPagination .= $sPagination;
	}

	public function createDataRow( $aEntry ) {
		$sValues = '';
		foreach( $aEntry as $valueKey => $value ) {
			if( !array_key_exists($valueKey, $this->aTableDataDict) ) continue;
			if( is_array($value) ) {
				if( empty($value['attributes']) ) $value['attributes'] = array();
				$value['attributes']['class'] = !empty($value['attributes']['class']) ? $value['attributes']['class'] .= ' ' . $valueKey : $valueKey;
				$sValues .= self::createData( $value['value'], $value['attributes'] );
			} else {
				$sValues .= self::createData( $value, array( 'class' => $valueKey ) );
			}
		}
		return $sValues;
	}

	public function createDataRowByDataKey( $aData ) {
		$aRow = array();

		foreach( $this->aTableDataDict as $sLabel => $aParams ) {
			if( array_key_exists($sLabel, $aData) ) {
				$aRow[$sLabel] = $aData[$sLabel];
			} else {
				$aRow[$sLabel] = '';
			}
		}

		return $aRow;
	}

	public static function createRow( $sContent, $aAttributes = array() ) {
    if( !empty($aAttributes['class']) ) {
      $aAttributes['class'] .= ' dataRow';
    } else {
      $aAttributes['class'] = 'dataRow';
    }

		return '
				<div' . createAttributes( $aAttributes ) . '>' . $sContent . '
				</div>';
	}

	public static function createBody( $sContent, $aAttributes = array() ) {
    if( !empty($aAttributes['class']) ) {
      $aAttributes['class'] .= ' body';
    } else {
      $aAttributes['class'] = 'body';
    }

		return '
			<div' . createAttributes( $aAttributes ) . '>' . $sContent . '
			</div>';
	}

	public static function createFooter( $sContent, $aAttributes = array() ) {
		return '
			<footer' . createAttributes( $aAttributes ) . '>' . $sContent . '
			</footer>';
	}

	public static function createHeader( $aHeader, $aAttributes = array(), $aRowAttributes = array() ) {
		$sOutput = '';
		foreach( $aHeader as $key => $value ) {
			if( empty($value['title']) ) $value['title'] = '';
			if( empty($value['attributes']) ) $value['attributes'] = array();

			$value['attributes']['class'] = $key . ( !empty($value['attributes']['class']) ? ' ' . $value['attributes']['class'] : '' );
			$sOutput .= self::createData( $value['title'], $value['attributes'] );
		}
		return '
			<header' . createAttributes( $aAttributes ) . '>' . self::createRow( $sOutput, $aRowAttributes ) . '
			</header>';
	}

	public static function createTable( $sContent, $aAttributes = array() ) {
		return '
		<section' . createAttributes( $aAttributes ) . '>' . $sContent . '
		</section>';
	}

	public function render( $aAttributes = array() ) {
		if( !empty($this->aData) ) $this->renderDataEntries( $this->aData );
		if( empty($this->sBody) ) return;

		$sContent = '';
		$sContent .= $this->renderHeader();
		$sContent .= $this->renderData();
		$sContent .= $this->renderFooter();
		$sContent .= $this->sPagination;

		return $this->renderTable( $sContent, $aAttributes );
	}

	public function renderData() {
		if( empty($this->sBody) ) return;
		return self::createBody( $this->sBody );
	}

	public function renderDataEntries( $aData ) {
		foreach( $aData as $aEntry ) {
			$this->addBodyEntry( $aEntry );
		}
	}

	public function renderFooter() {
		if( empty($this->sFooter) ) return;
		return self::createFooter( $this->sFooter );
	}

	public function renderHeader() {
		$aHeader = array();
		$aRowAttributes = array();

		foreach( $this->aTableDataDict as $key => $value ) {
			$aHeader[$key] = $value;
		}

		if( !empty($this->sRowGridStyle) ) $aRowAttributes['style'] = 'grid-template-columns: ' . $this->sRowGridStyle . ';';

		if( empty($aHeader) ) return;
		return self::createHeader( $aHeader, array(), $aRowAttributes );
	}

	public function renderTable( $sContent, $aAttributes = array() ) {
		if( !empty( $this->sTitle ) ) {
			$sContent = '
			<caption>' . $this->sTitle . '</caption>' . $sContent;
		}
		if( !empty( $this->sSummary ) ) {
			$this->aAttributes['summary'] = $this->sSummary;
		}
		if( !empty($this->aAttributes['class']) ) {
			$this->aAttributes['class'] .= ' gridTable';
		} else {
			$this->aAttributes['class'] = 'gridTable';
		}

		$aAttributes += $this->aAttributes;

		return self::createTable( $sContent, $aAttributes );
	}

	public function setTableDataDict( $aTableDataDict ) {
		foreach( $this->aDataDict as $key => $value ) {
			if( isset($aTableDataDict[$key]) ) {
				$aTableDataDict[$key] += $this->aDataDict[$key];
			}
		}
		$this->aTableDataDict = $aTableDataDict;
	}

	public function reset() {
		$this->sTitle = '';
		$this->sBody = '';
		$this->sFooter = '';
		$this->aData = array();
	}

}
