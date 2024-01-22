<?php

class clLocale {

	public $oAcl;
	public $oAclGroups;

	private $sEncoding;
	private $aCurrencyCodeToLandCode = array();

	protected $aEvents = array();
	protected $oEventHandler;

	public function __construct() {
		$this->sModulePrefix = 'locale';
		$this->sModuleName = 'Locale';

		$this->oDao = clRegistry::get( 'clLocaleDao' . DAO_TYPE_DEFAULT_ENGINE );

		// ACL
		clFactory::loadClassFile( 'clAcl' );
		$this->oAcl = new clAcl();
		$this->oAclGroups = new clAcl();

		$this->oEventHandler = clRegistry::get( 'clEventHandler' );
		$this->oEventHandler->addListener( $this, $this->aEvents );
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		$aData[$this->sModulePrefix . 'Created'] = date( 'Y-m-d H:i:s' );

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
		$result = $this->oDao->deleteDataByPrimary( $primaryId );
		if( !empty($result) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'The data has been deleted' )
			) );
		}
		return $result;
	}

	public function generateLocaleList( $aLocales = array() ) {

		// Fields to read from database
		$aFields = array(
			'localeId',
			'localeTitle',
			'localeCode',
			'localeTitle'
		);

		if( empty( $aLocales ) ) {
			// Read all locales and sort
			$this->oDao->aSorting = array( 'localeSort' => 'ASC' );
			$aLocales = $this->read( $aFields );
			$this->oDao->aSorting = array();
		} else {
			// Read provided locales
			$aLocales = $this->read( $aFields, $aLocales );
		}

		$sLocales = '<ul id="localeList">';

			foreach( (array) $aLocales as $aLocale ) {
				$sLocales .= '
					<li class="' . $aLocale['localeCode'] . ( !empty($GLOBALS['langId']) && $aLocale['localeId'] == $GLOBALS['langId'] ? ' active' : '' ) . '">
						<a href="/?changeLang=' . $aLocale['localeId'] . '" lang="' . $aLocale['localeCode'] . '" title="' . $aLocale['localeTitle'] . '">' . $aLocale['localeTitle'] . '</a>
					</li>
				';
			}

		$sLocales .= '</ul>';

		return $sLocales;
	}

	public function generateCurrencyList( $aLocales = array() ) {
		$oRouter = clRegistry::get( 'clRouter' );

		if( empty($aCurrencies) ) {
			$aCurrencies = $this->readCurrency( array(
				'localeDefaultCurrency'
			) );
		}

		$sCurrencies = '
			<ul id="currencyList">';

		foreach( (array) $aCurrencies as $aCurrency ) {

			$sCurrencies .= '
				<li class="' . $aCurrency['localeDefaultCurrency'] . ( !empty($GLOBALS['currency']) && $aCurrency['localeDefaultCurrency'] == $GLOBALS['currency'] ? ' active' : '' ) . '">
					<a href="' . $oRouter->sPath . '?changeCurrency=' . $aCurrency['localeDefaultCurrency'] . '">' . $aCurrency['localeDefaultCurrency'] . '</a>
				</li>';
		}

		$sCurrencies .= '
			</ul>';

		return $sCurrencies;
	}

	public function getLandCodeByCurrencyCode( $sCurrencyCode, $aLocales = array() ) {
		$aLocales = (array) $aLocales + (array) $GLOBALS['Locales'];
		if( empty($aLocales) ) return $GLOBALS['defaultCurrency'];

		$sCurrencyCode = strtoupper($sCurrencyCode);

		if( empty($this->aCurrencyCodeToLandCode[$sCurrencyCode]) ) {
			$aCurrencyData = $this->readCurrency( array( 'localeDefaultMonetary', 'localeDefaultCurrency') );
			foreach( $aCurrencyData as $aData ) {
				$this->aCurrencyCodeToLandCode[ $aData['localeDefaultCurrency'] ] = $aData['localeDefaultMonetary'];
			}

			$this->setLocale( $GLOBALS['userLang'] );
		}

		if( empty($this->aCurrencyCodeToLandCode[$sCurrencyCode]) ) {
			throw new Exception( sprintf( _( 'If you would like to convert %s, you have to insert a land locale that have %s as monetary into the locale table.' ), $sCurrencyCode, $sCurrencyCode ) );
		}

		return $this->aCurrencyCodeToLandCode[$sCurrencyCode];
	}

	public function read( $aFields = array(), $primaryId = null ) {
		$aParams = array(
			'fields' => $aFields,
			'criterias' => "localeUse IN('language','both')"
		);
		if( $primaryId !== null ) return $this->oDao->readDataByPrimary($primaryId, $aParams);
		return $this->oDao->readData( $aParams );
	}

	public function readCurrency( $aFields = array(), $primaryId = null ) {
		$aParams = array(
			'fields' => $aFields,
			'criterias' => "localeUse IN('money','both')"
		);
		if( $primaryId !== null ) return $this->oDao->readDataByPrimary($primaryId, $aParams);
		return $this->oDao->readData( $aParams );
	}

	public function setEncoding( $sEncodning ) {
		return $this->sEncoding = $sEncodning;
	}

	public function setAcl( $oAcl ) {
		$this->oAcl = $oAcl;
	}

	public function setLocale( $sUserLang ) {
		putenv( 'LC_ALL=' . $sUserLang . '.' . $this->sEncoding );
		setlocale( LC_ALL, $sUserLang . '.' . $this->sEncoding );
		bindtextdomain( 'default', PATH_LOCALE );
		textdomain( 'default' );
	}

	public function setMonetary( $sUserLang ) {
		setlocale( LC_MONETARY, $sUserLang . '.' . $this->sEncoding );
	}

	public function update( $primaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'update' . $this->sModuleName;

		$result = $this->oDao->updateDataByPrimary( $primaryId, $aData, $aParams );
		if( $result !== false ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
		}
		return $result;
	}

}
