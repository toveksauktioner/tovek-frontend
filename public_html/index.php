<?php

/**
 * $Id: index.php 3355 2018-11-13 13:58:22Z mikael $
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * This is the file that handles the main entry level of http traffic into argoPlatform
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author: mikael $
 * @version		Subversion: $Revision: 3355 $, $Date: 2018-11-13 14:58:22 +0100 (tis, 13 nov 2018) $
 */
//if($_SERVER["HTTP_X_REAL_IP"] != "70.34.217.241") { echo "Vi har problem just nu. Pågående auktion kommer att köras om. Mer info kommer."; exit; }
/*** Maintenance block ***/
// $aAccessList = array(
// 	'37.152.60.119',
// 	'98.128.229.42'
// );
// list( $sIpAddressFirst ) = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR'] );
// if( empty($sIpAddressFirst) || !in_array($sIpAddressFirst, $aAccessList) ) {
// 	/*** Maintenance ***/
// 	header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
// 	header( 'Location: https://tovek.se/maintenance.php' );
// 	exit;
// }

error_reporting( E_ALL );
ini_set( 'display_errors', true );
ini_set( 'display_startup_errors', true );
ini_set( 'memory_limit', '128M' );
#ini_set( 'memory_limit', '256M' );
ini_set( 'magic_quotes_gpc', 0 );
ini_set( 'magic_quotes_runtime', 0 );
ini_set( 'magic_quotes_sybase', 0 );

/*** Include and check workaround ***/
require_once dirname( __FILE__ ) . '/../platform/functions/fWorkaround.php';
workaroundCheck();

/*** Output buffer container ***/
$sOutputBuffer = '';

/*** Include config ***/
require_once dirname( __FILE__ ) . '/../platform/config/cfBase.php';

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

// if( empty($_SERVER['HTTP_BUNNYCDN_LBKEY']) || ($_SERVER['HTTP_BUNNYCDN_LBKEY'] != 'j0YiR5gUfYiZmQrWSLV6opVewPUZFwJD21K1W9dkKWg4EnYSjbS5c9') ) {
// 	/*** Maintenance ***/
// 	header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
// 	header( 'Location: /maintenance.php' );
// 	exit;
// }

/*** Include data functions ***/
require_once PATH_FUNCTION . '/fData.php';
require_once PATH_FUNCTION . '/fRoute.php';
require_once PATH_FUNCTION . '/fTime.php';
require_once PATH_FUNCTION . '/fUser.php';
require_once PATH_FUNCTION . '/fMoney.php';

if( $GLOBALS['debug'] === true ) {
	require_once PATH_FUNCTION . '/fDevelopment.php';
}

/*** Benchmark ***/
if( $GLOBALS['benchmark'] === true ) {
	require_once PATH_CORE . '/clBenchmark.php';
	$oBenchmark = new clBenchmark;
	$oBenchmark->timer( 'start', 'pageGenerationTime' );
}

/*** Check´s if the request is made by ajax ***/
if(
   ( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) ||
   ( !empty($_GET['ajax']) && empty($_GET['event']) )
  ) {
	/*** Ajax request ***/
	$_GET['ajax'] = true;
}

/*** Handle critical exceptions ***/
function outputCriticalException( $oException ) {
	if( $GLOBALS['debug'] === true ) {
		return printf( _( 'Exception: "%s" on line %s in file %s at code %s' ), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
	}
	return printf( _( 'Exception: "%s"' ), $oException->getMessage() );
}

try {
	/*** Dynamic domain handling ***/
	//if( $_SERVER['SERVER_NAME'] != SITE_DEFAULT_DOMAIN ) {
	//	switch( $_SERVER['SERVER_NAME'] ) {
	//		case 'mydomain.com':
	//			$_SESSION['langId'] = 2;
	//			$GLOBALS['superConstants']['SITE_DOMAIN'] = 'mydomain.com';
	//			break;
	//	}
	//}

	/*** Set currency and monetary default value ***/
	$GLOBALS['currency'] = $GLOBALS['defaultCurrency'];
	$GLOBALS['currencyFormat'] = $GLOBALS['defaultCurrencyFormat'];
	$GLOBALS['vatInclusion'] = $GLOBALS['defaultVatInclusion'];

	/*** Set language default value ***/
	$GLOBALS['langIdEdit'] = $GLOBALS['langId'] = $GLOBALS['defaultLangId'];

	/*** Set pre-choosed values ***/
	if( isset($_SESSION['langIdEdit']) )			$GLOBALS['langIdEdit']	   = $_SESSION['langIdEdit'];
	if( isset($_SESSION['langId']) )				$GLOBALS['langId']		   = $_SESSION['langId'];
	if( isset($_SESSION['currency']) )				$GLOBALS['currency']	   = $_SESSION['currency'];
	if( isset($_SESSION['currencyFormat']) )		$GLOBALS['currencyFormat'] = $_SESSION['currencyFormat'];
	if( isset($_SESSION['vatInclusion']) ) 			$GLOBALS['vatInclusion']   = $_SESSION['vatInclusion'];

	/*** Change values on request ***/
	if( !empty($_REQUEST['changeLang']) )		   	$GLOBALS['langId']		   = (int) $_REQUEST['changeLang'];
	if( !empty($_GET['langIdEdit']) )			   	$GLOBALS['langIdEdit']	   = (int) $_GET['langIdEdit'];
	if( !empty($_GET['changeCurrency']) )		   	$GLOBALS['currency']	   = $_GET['changeCurrency'];
	if( !empty($_GET['changeCurrencyFormat']) )	   	$GLOBALS['currencyFormat'] = $_GET['changeCurrencyFormat'];
	if( !empty($_REQUEST['changeVatInclusion']) )  	$GLOBALS['vatInclusion']   = $_REQUEST['changeVatInclusion'];

	/*** Store choosed values ***/
	$_SESSION['langId']		    = $GLOBALS['langId'];
	$_SESSION['langIdEdit']	    = $GLOBALS['langIdEdit'];
	$_SESSION['currency']	    = $GLOBALS['currency'];
	$_SESSION['currencyFormat'] = $GLOBALS['currencyFormat'];
	$_SESSION['vatInclusion']   = setVatInclusion( $GLOBALS['vatInclusion'] );

	/*** Load factory and necessary class files ***/
	require_once PATH_CORE . '/clFactory.php';
	clFactory::loadClassFile( 'clRegistry' );

	/*** Autoloader check ***/
	if( AUTOLOADER_ENABLE === true ) {
		// Register autoloader
		spl_autoload_register( AUTOLOADER_FUNCTION );
	}

	/*** Set encodning ***/
	$oLocale = clRegistry::get( 'clLocale' );
	$oLocale->setEncoding( 'UTF-8' );

	/*** Get current route path ***/
	$sRoutePath = getRoutePath();

	/*** Administration in swedish ***/
	if( mb_substr($sRoutePath, 0, 6 ) == '/admin' ) {
		$_SESSION['langId'] = $GLOBALS['langId'] = $GLOBALS['defaultAdminLangId'];
		$GLOBALS['userLang'] = 'sv_SE';
	}

	/*** Read locales ***/
	$oLocale->oDao->aSorting = array( 'localeSort' => 'ASC' );
	$GLOBALS['Locales'] = $aLocales = arrayToSingle( $oLocale->read( array('localeId', 'localeCode') ), 'localeId', 'localeCode' );
	$oLocale->oDao->aSorting = array();

	/*** Set lang if the language exists in database **/
	if( array_key_exists($GLOBALS['langId'], $aLocales) ) {
		$GLOBALS['userLang'] = $aLocales[$GLOBALS['langId']];
	} elseif( mb_substr($sRoutePath, 0, 6) != '/admin' ) {
		// Locale that was not active was choosen, revert to default language
		$GLOBALS['langId'] = $GLOBALS['defaultLangId'];
		if( array_key_exists($GLOBALS['langId'], $aLocales) ) {
			$GLOBALS['userLang'] = $aLocales[$GLOBALS['langId']];
		}
	}

	/*** Set locales ***/
	$oLocale->setLocale( $GLOBALS['userLang'] );

	/*** Include and define additional constants ***/
	require_once PATH_CONFIG . '/cfConstant.php';

	require_once PATH_CONFIG . '/cfRegularExpressions.php';

	/*** With VAT determined, include money profiles ***/
	require_once PATH_CONFIG . '/cfMoneyProfile.php';

	/*** Include custom configs ***/
	require_once PATH_CONFIG . '/cfDefcon.php';
	require_once PATH_CONFIG . '/cfAuction.php';

	/*** Initialization router module and set path ***/
	$oRouter = clRegistry::get( 'clRouter' );

	/*** Needs to be required here cuz of locales.. ***/
	require_once PATH_CONFIG . '/cfUserText.php';

	clFactory::loadClassFile( 'clLogger' );
	clFactory::loadClassFile( 'clErrorHandler' );
	clFactory::loadClassFile( 'clOutput' );
	clFactory::loadClassFile( 'clUser' );

} catch( Throwable $oThrowable ) {
	outputCriticalException( $oThrowable );

} catch( Exception $oException ) {
	outputCriticalException( $oException );

}

try {
	/*** Must be before the user loads, otherwise the the default timezone won´t be set correctly ***/
	date_default_timezone_set( SITE_DEFAULT_TIMEZONE );

	/*** Set user to guest ***/
	$oUser = new clUser();
	$oUser->setGroup( array(
		'guest' => _( 'Guest' )
	) );

	/*** If the user is logged in, set user permissions ***/
	if( !empty($_SESSION['userId']) ) {
		$oUser->iId = $_SESSION['userId'];
		$oUser->setData();
		$oUser->isOnline();
		if( !empty($_SESSION['user']['groups']) ) $oUser->aGroups = $_SESSION['user']['groups'];
		if( !empty($_SESSION['user']['acl']) ) $oUser->oAcl->setAcl( $_SESSION['user']['acl'] );
		if( !empty($_SESSION['user']['aclGroups']) ) $oUser->oAclGroups->setAcl( $_SESSION['user']['aclGroups'] );
	}

	/*** Set user in registry ***/
	clRegistry::set( 'clUser', $oUser );

	/*** Apply acl to the locale module ***/
	$oLocale->setAcl( $oUser->oAcl );

	/*** Apply acl to the router module ***/
	$oRouter->setAcl( $oUser->oAcl );

	/*** Instantiating event module ***/
	$oEventHandler = clRegistry::get( 'clEventHandler' );

	/*** Handle events ***/
	$aEvents = array();
	if( (empty($_GET['event'])) ) $_GET['event'] = array();
	if( !empty($_POST['event']) ) $_GET['event'] = (array) $_GET['event'] + (array) $_POST['event'];
	foreach( (array) $_GET['event'] as $sEvent ) {
		if( !empty( $_GET[$sEvent] ) ) $aEvents[$sEvent] = (array) $_GET[$sEvent];
	}
	if( empty($_GET['event']) ) unset($_GET['event']);

	/*** Trigger existing events ***/
	if( !empty($aEvents) ) $oEventHandler->triggerEvent( $aEvents );

	/*** Do the routing ***/
	$oRouter->route();

	if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
		/**
		 * Version switch
		 */
		if( !empty($_GET['siteVersion']) ) {
			$_SESSION['siteVersion'] = $_GET['siteVersion'];
			if( $_SESSION['siteVersion'] == 'classic' ) $oRouter->redirect( '/klassiskt' );
			else $oRouter->redirect( '/' );
		}
		// if( $oRouter->sPath == '/' && empty($_SESSION['siteVersion']) ) {
		// 	$_SESSION['siteVersion'] = !empty($_COOKIE['siteVersion']) ? $_COOKIE['siteVersion'] : 'classic';
		// 	if( empty($_COOKIE['siteVersion']) ) {
		// 		setcookie( 'siteVersion', $_SESSION['siteVersion'], time() + (86400 * 30) ); // 86400 = 1 day
		// 	}
		// 	$oRouter->redirect( '/klassiskt' );
		//
		// } elseif( $oRouter->sPath == '/' && $_SESSION['siteVersion'] == 'classic' ) {
		// 	$oRouter->redirect( '/klassiskt' );
		// }
	}

	/*** Run user-triggered cronJobs ***/
	//$oArgoCron = clRegistry::get( 'clArgoCron' );
	//$oArgoCron->check( $oRouter->sCurrentLayoutKey );

	/*** Instantiating layout and template modules ***/
	$oLayout = clRegistry::get( 'clLayoutHtml' );
	$oTemplate = clRegistry::get( 'clTemplateHtml' );

	/*** Administration should always be available ***/
	if( mb_substr($oRouter->sPath, 0, 6) == '/admin' || $oRouter->sCurrentTemplateFile == 'callback.php' || (
		!empty($_SESSION['user']['groups']) && (
			array_key_exists( 'admin', $_SESSION['user']['groups'] ) ||
			array_key_exists( 'superuser', $_SESSION['user']['groups'] )
		)
	) ) {
		// Force webpage status to 'online'
		$sSiteStatus = 'online';
	} else {
		$sSiteStatus = SITE_STATUS;
	}

	/*** Displayed content depending on site status ***/
	switch( $sSiteStatus ) {
		/*** Blank white page ***/
		case 'offline':
			header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
			header( 'Status: 503 Service Temporarily Unavailable' );
			header( 'Retry-After: 300' );

			$oTemplate->setTitle( $GLOBALS['text']['webpageStatus']['downTitle'] . ' - ' . SITE_TITLE );
			$oTemplate->setTemplate( 'empty.php' );
			$oTemplate->setContent( $GLOBALS['text']['webpageStatus']['down'] );
			break;

		/*** White page, with message ***/
		case 'maintenance':
			header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
			header( 'Status: 503 Service Temporarily Unavailable' );
			header( 'Retry-After: 300' );

			$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );

			$oTemplate->setTitle( $GLOBALS['text']['webpageStatus']['downTitle'] . ' - ' . SITE_TITLE );
			$oTemplate->setTemplate( 'empty.php' );
			$oTemplate->setContent( (($aContent = $oInfoContent->read('contentTextId', 26)) ? $aContent[0]['contentTextId'] : null) );
			break;

		/*** White page, with message ***/
		case 'construction':
			header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
			header( 'Status: 503 Service Temporarily Unavailable' );
			header( 'Retry-After: 300' );

			$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );

			$oTemplate->setTitle( $GLOBALS['text']['webpageStatus']['downTitle'] . ' - ' . SITE_TITLE );
			$oTemplate->setTemplate( 'empty.php' );
			$oTemplate->setContent( (($aContent = $oInfoContent->read('contentTextId', 27)) ? $aContent[0]['contentTextId'] : null) );
			break;

		/*** Render layout and views normally ***/
		case 'online': default:
			/*** Check´s if the route exists in the database ***/
			if( !empty($oRouter->sCurrentLayoutKey) ) {
				if( !in_array($oRouter->sCurrentTemplateFile, array('tovek.php','tovekClassic.php')) ) {
					/*** Insert layout css. Uses no key so that ajax loaded layouts doesn't replace the current css  ***/
					$oTemplate->addLink( array(
						'href' => '/css/index.php?include=layouts/' . $oRouter->sCurrentLayoutKey
					) );
				}

				/*** Render ***/
				if( !empty($_GET['ajax']) ) {
					if( !empty($_GET['view']) ) {
						$aView = explode( '/', $_GET['view'] );
						switch( count($aView) ) {
							case 2: // Path without leading slash
								$sViewModuleKey = $aView[0];
								$sViewFile = $aView[1];
								break;

							case 3: // Path with leading slash
								$sViewModuleKey = $aView[1];
								$sViewFile = $aView[2];
								break;

							default: // Strange formating
								$sOutputBuffer .= _( 'View not accessible' );
						}

						if( !empty($sViewModuleKey) && !empty($sViewFile) ) {
							$oView = clRegistry::get( 'clViewHtml' );
							$aViewId = current( $oView->oDao->read( array(
								'fields' => 'viewId',
								'viewModuleKey' => $sViewModuleKey,
								'viewFile' => $sViewFile
							) ) );

							if( !empty($aViewId) ) {
								$iViewId = (int) $aViewId['viewId'];

								$oAclView = new clAcl();
								if( $oUser->iId !== null ) $oAclView->setAro( $oUser->iId, 'user' );
								$oAclView->setAro( array_keys($oUser->aGroups), 'userGroup' );
								$oAclView->readToAclByAro( 'view', $iViewId );

								if( $oAclView->isAllowed( $iViewId ) ) {
									$sOutputBuffer .= $oLayout->renderView( $_GET['view'] );
								} else {
									$sOutputBuffer .= _( 'View not accessible' );
								}
							} else {
								$sOutputBuffer .= _( 'View not accessible' );
							}
						}

					} elseif( !empty($_GET['section']) ) {
						$sOutputBuffer .= $oLayout->render( null, null, $_GET['section'] );

					} else {
						$sOutputBuffer .= $oLayout->render();
					}

				} else {
					$oTemplate->setContent( $oLayout->render() );
				}

				/*** Route, scroll to point ***/
				if( $oRouter->getScrollTo() !== false ) {
					$oTemplate->addBottom( array(
						'key' => 'jsScrollTo',
						'content' => '
							<script>
								$(window).load( function() {
									$("html, body").animate( {
										scrollTop: $("#' . $oRouter->getScrollTo() . '").offset().top
									}, 1000 );
								} );
							</script>
						'
					) );
				}

			/*** Page not found ***/
			} else {
				header( "HTTP/1.0 404 Not Found" );

				/*** 404 message within a layout ***/
				$oLayout->sLayoutFile = 'main.php';

				$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
				$s404Content = (($aContent = $oInfoContent->read('contentTextId', 29)) ? $aContent[0]['contentTextId'] : '404 - ' . _( 'Page not found' ));

				$sLayoutContent = $oLayout->renderCustom( array( '{tplMain}' => $s404Content), $oLayout->sLayoutFile );

				$oTemplate->setTitle( $GLOBALS['text']['webpageStatus']['404Title'] . ' - ' . SITE_TITLE );
				$oTemplate->setTemplate( 'tovek.php' );
				$oTemplate->setContent( $sLayoutContent );
			}
		break;
	}

	/*** Render template ***/
	$sOutputBuffer .= $oTemplate->render();

	/*** Benchmarks ***/
	if( $GLOBALS['benchmark'] === true ) {
		$oBenchmark->timer( 'stop', 'pageGenerationTime' );

		// Add benchmark result to output
		$sOutputBuffer .= '
			<div>
				<h2>Benchmark</h2>
				' . $oBenchmark->timer( 'show', 'pageGenerationTime' ) . '<br />
				Load average: ' . $oBenchmark->averageSystemLoad() . '<br />
				' . $oBenchmark->timer( 'show', '*' ) . '<br />
				Memory Usage / Peak: ' . $oBenchmark->memoryUsage() . ' (bytes)
			</div>';
	}

} catch( Throwable $oThrowable ) {
	clErrorHandler::setException( $oThrowable );

} catch( Exception $oException ) {
	clErrorHandler::setException( $oException );

}

try {
	clOutput::render( $sOutputBuffer );

} catch( Throwable $oThrowable ) {
	outputCriticalException( $oThrowable );

} catch( Exception $oException ) {
	outputCriticalException( $oException );

}
