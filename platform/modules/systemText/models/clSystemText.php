<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_BACK_PLATFORM . '/modules/systemText/config/cfSystemText.php';

class clSystemText extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'systemText';

		$this->oDao = clRegistry::get( 'clSystemTextDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/systemText/models' );
		$this->initBase();

    $this->oDao->switchToSecondary();
	}

  // Data can be read by id or key
	public function read( $aFields = array(), $mKeyOrId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);

    if( !empty($mKeyOrId) ) {
      if( is_numeric($mKeyOrId) ) {
        return $this->oDao->readDataByPrimary( $mKeyOrId, $aParams );

      } else {
        $aParams['criterias'] = 'systemTextKey = ' . $this->oDao->oDb->escapeStr( $mKeyOrId );
      }
    }

		return $this->oDao->readData( $aParams );
	}

  public function readWithParams( $aParams ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		$aParams += array(
			'systemTextGroup' => null,
      'fields' => null
		);

		$aCriterias = array();
		$aDaoParams = array();

		if( !empty($aParams['fields']) ) $aDaoParams['fields'] = $aParams['fields'];

		if( !empty($aParams['systemTextGroup']) ) {
			if( is_array($aParams['systemTextGroup']) ) {
				$aCriterias[] = 'systemTextGroup IN (' . implode( ', ', array_map(array($this->oDao->oDb, 'escapeStr'), $aParams['systemTextGroup']) ) . ')';
			} else {
				$aCriterias[] = 'systemTextGroup = ' . $this->oDao->oDb->escapeStr( $aParams['systemTextGroup'] );
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->oDao->readData( $aDaoParams );
	}

  function replaceParams( $aText, $aData = null ) {
    $oRouter = clRegistry::get( 'clRouter' );
		$sText = $aText['systemTextMessage'];

    if( empty($aData) ) $aData = [];
    $aData += json_decode( $aText['systemTextParams'], true );

    foreach( $aData as $sKey => $sValue ) {
      if( substr($sKey, 0, 5) == 'path-' ) {
        $sValue = $oRouter->getPath( substr($sKey, 5) );
      }
      $sText = str_replace( '{' . $sKey . '}', $sValue, $sText );
    }
    return $sText;
  }

}
