<?php

error_reporting( E_ALL );
ini_set( 'display_errors', true );
ini_set( 'display_startup_errors', true );
ini_set( 'memory_limit', '128M' );
ini_set( 'magic_quotes_gpc', 0 );
ini_set( 'magic_quotes_runtime', 0 );
ini_set( 'magic_quotes_sybase', 0 );

/*** Include and check workaround ***/
require_once dirname(dirname( __FILE__ )) . '/functions/fWorkaround.php';
workaroundCheck();

require_once dirname( dirname(__FILE__) ) . '/config/cfBase.php';

/*** Include data functions ***/
require_once PATH_FUNCTION . '/fData.php';
require_once PATH_FUNCTION . '/fUser.php';
require_once PATH_FUNCTION . '/fRoute.php';
require_once PATH_FUNCTION . '/fTime.php';
require_once PATH_FUNCTION . '/fMoney.php';

if( SITE_SESSION_HANDLER == 'custom' ) {
	/*** Init the custom session handler ***/
	require_once PATH_CORE . '/clSessionHandler.php';
	$oSessionHandler = new clSessionHandler();
	session_set_save_handler( $oSessionHandler, true );
}

/*** Set session timeout ***/
ini_set( 'session.gc_maxlifetime', SITE_SESSION_TIMEOUT );

/*** Start session ***/
session_start();

function outputCriticalException( $oException ) {
	if( !empty($GLOBALS['debug']) ) {
		echo sprintf(_('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode());
	} else {
		echo $oException->getMessage();
	}
}

if( $GLOBALS['debug'] === true ) {
	require_once PATH_FUNCTION . '/fDevelopment.php';
}

try{
	$GLOBALS['langId'] = $GLOBALS['defaultLangId'];

	if( !isset($_SESSION['langIdEdit']) ) $_SESSION['langIdEdit'] = $GLOBALS['langId'];
	if( !empty($_GET['langIdEdit']) ) $_SESSION['langIdEdit'] = (int) $_GET['langIdEdit'];

	if( !isset($_SESSION['langId']) ) $_SESSION['langId'] = $GLOBALS['langId'];
	if( !empty($_GET['changeLang']) ) $_SESSION['langId'] = (int) $_GET['changeLang'];
	//if( !empty($_GET['changeLang']) ) $GLOBALS['langId'] = (int) $_GET['changeLang'];

	$GLOBALS['langId'] = $_SESSION['langId'];
	$GLOBALS['langIdEdit'] = $_SESSION['langIdEdit'];

	/*** Set currency and monetary default value ***/
	$GLOBALS['currency'] = $GLOBALS['defaultCurrency'];
	$GLOBALS['currencyFormat'] = $GLOBALS['defaultCurrencyFormat'];
	$GLOBALS['vatInclusion'] = $GLOBALS['defaultVatInclusion'];
	$_SESSION['currency']	    = $GLOBALS['currency'];
	$_SESSION['currencyFormat'] = $GLOBALS['currencyFormat'];
	$_SESSION['vatInclusion']   = setVatInclusion( $GLOBALS['vatInclusion'] );

	if( $GLOBALS['langId'] == 2 ) {
		$GLOBALS['userLang'] = 'en_GB';
	} else {
		$GLOBALS['userLang'] = 'sv_SE';
	}

	putenv('LC_ALL=' . $GLOBALS['userLang'] . '.UTF-8');
	setlocale(LC_ALL, $GLOBALS['userLang'] . '.UTF-8');
	bindtextdomain('default', PATH_LOCALE);
	textdomain('default');

	date_default_timezone_set( SITE_DEFAULT_TIMEZONE );

	require_once PATH_CORE . '/clFactory.php';
	clFactory::loadClassFile( 'clRegistry' );
	clFactory::loadClassFile( 'clErrorHandler' );
	clFactory::loadClassFile( 'clOutput' );
	clFactory::loadClassFile( 'clUser' );

	/*** With VAT determined, include money profiles ***/
	require_once PATH_CONFIG . '/cfMoneyProfile.php';

	/*** Include and define additional constants ***/
	require_once PATH_CONFIG . '/cfConstant.php';

	if( !empty($_SESSION['userId']) ) {
		$oUser = new clUser();
		$oUser->iId = $_SESSION['userId'];
		if( !empty($_SESSION['user']['groups']) ) $oUser->aGroups = $_SESSION['user']['groups'];
		if( !empty($_SESSION['user']['acl']) ) $oUser->oAcl->setAcl( $_SESSION['user']['acl'] );
		if( !empty($_SESSION['user']['aclGroups']) ) $oUser->oAclGroups->setAcl( $_SESSION['user']['aclGroups'] );
	} else {
		$oUser = new clUser();
		$oUser->setGroup( array(
			'guest' => _( 'Guest' )
		) );
	}
	clRegistry::set( 'clUser', $oUser );

} catch( Exception $e ) {
	outputCriticalException( $e );
}
