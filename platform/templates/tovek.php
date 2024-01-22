<?php

/**
 * Central place bid init
 */
if( !empty($_POST['frmPlaceBid']) ) {
	// Determ type
	$_POST['bidType'] = isset($_POST['submitPost']) ? 'normal' : 'auto';

	$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
	$aResult = $oAuctionEngine->placeBid( $_POST );

	if( $aResult['result'] == 'success' ) {
		$oNotification->setSessionNotifications( array(
			'dataSaved' => _( 'The data has been saved' )
		) );
	} else {
		$iCount = 0;
		foreach( $aResult['error'] as $sError ) {
			$oNotification->setSessionNotifications( array(
				'dataError' . $iCount => $sError
			) );
			$iCount++;
		}
	}

	$oRouter->redirect( $oRouter->sPath );
}

/**
 * Locales
 */
// $oLocales = clRegistry::get( 'clLocale' );
// $sLocales = $oLocales->generateLocaleList( $GLOBALS['Locales'] );

/**
 * Footer and other info blocks
 */
$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
$sFooter = (($aFooter = $oInfoContent->read('contentTextId', 30)) ? $aFooter[0]['contentTextId'] : null );
$sJavascriptNotice = (($aJavascriptNotice = $oInfoContent->read('contentTextId', 48)) ? $aJavascriptNotice[0]['contentTextId'] : null );
$sConnectionNotice = (($aConnectionNotice = $oInfoContent->read('contentTextId', 49)) ? $aConnectionNotice[0]['contentTextId'] : null );
$sHelpNotice = (($aConnectionNotice = $oInfoContent->read('contentTextId', 61)) ? $aConnectionNotice[0]['contentTextId'] : null );

/**
 * Google analytics UA code
 */
$oConfig = clRegistry::get( 'clConfig' );
// $sUAcode = (($aData = $oConfig->read('configValue', 'googleAnalyticsCode')) ? current(current( $aData )) : '');

/**
 * Cookie information
 */
$bCookie = (($aData = $oConfig->read('configValue', 'SITE_COOKIE')) ? current(current( $aData )) : '') == 'false' ? false : true;

$sUserScripts = '';
// if( !empty($_SESSION['userId']) ) {
	$sUserScripts .= '
		<script src="/js/templates/tovekCommon/connectionChecker.js?ver=3"></script>
		<script src="/js/templates/tovek/dynamic.config.js.php?ver=4"></script>
		<script src="/js/templates/tovek/functions.js?ver=4"></script>
		<script src="/js/templates/tovek/init.js?ver=27"></script>
		<script src="/js/templates/tovek/dynamic.js.php?ver=3"></script>
		<script src="/js/templates/tovek/clock.js?ver=20"></script>';
// }

?><!DOCTYPE html>
<html class="no-js" lang="<?php echo substr($GLOBALS['Locales'][ $_SESSION['langId'] ], 0, 2) ?>">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="utf-8" />
	<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0" />

	<!-- Sajt icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="/images/favicons/apple-touch-icon.png" />
	<link rel="icon" type="image/png" href="/images/favicons/favicon-32x32.png" sizes="32x32" />
	<link rel="icon" type="image/png" href="/images/favicons/favicon-16x16.png" sizes="16x16" />
	<link rel="manifest" href="/images/favicons/manifest.json" />
	<link rel="mask-icon" href="/images/favicons/safari-pinned-tab.svg" color="#5bbad5" />
	<link rel="shortcut icon" href="/images/favicons/favicon.ico" />
	<meta name="msapplication-config" content="/images/favicons/browserconfig.xml" />
	<meta name="theme-color" content="#ffffff" />

	<!-- Cookie bot -->
	<script id="Cookiebot" src="https://consent.cookiebot.eu/uc.js" data-cbid="a0764733-4cbe-45ef-a15b-3326a6680a77" type="text/javascript" async></script>

	<!-- Stylesheet -->
	<link href="/css/index.php?include=templates/tovek/&ver=22" rel="stylesheet" />
	<link href="/css/index.php?include=templates/tovek/print" rel="stylesheet" media="print" />

	<!-- jQuery -->
	<script src="/js/jquery/jquery-3.2.1.min.js"></script>
	<!--script src="/js/jquery/jquery-ui-1.12.1.min.js"></script-->
	<script src="/js/jquery.timer.js"></script>

	<!-- JavaScripts -->
	<?php echo $sUserScripts; ?>
	<script src="/js/modernizr.js"></script>
	<script src="/js/templates/tovek/auction.js?ver=24"></script>

	<!-- Shared js -->
	<!--script src="/js/templates/tovekCommon/localization.js"></script-->
	<script src="/js/templates/tovekCommon/popup.js?ver=1"></script>

	<?php echo $sTop; ?>

	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-FVGSG8DE5R"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'G-FVGSG8DE5R');
	</script>
</head>
<body ontouchstart="" class="bottomNoticeActive <?php echo (!empty($oRouter->sCurrentLayoutBodyClass) ? $oRouter->sCurrentLayoutBodyClass : ''); ?>">
	<div id="wrapper">
		<header>
			<div class="container">
				<div id="logo">
					<a href="/">
						<img src="/images/templates/tovek/img-logo.png" alt="logo" class="block" />
					</a>
				</div>
				<nav data-title="<?php echo _( 'Menu' ); ?>">
					<input type="checkbox" name="btnBurger" id="btnBurger" />
					<label for="btnBurger" class="btnBurger"><i class="fas fa-bars"></i></label>
					<div class="container">
						<?php
							$GLOBALS['viewParams']['navigation']['list.php']['groupKey'] = 'guest';
							echo $oLayout->renderView( 'navigation/list.php' );

								$GLOBALS['viewParams']['navigation']['list.php']['groupKey'] = 'user';
								echo $oLayout->renderView( 'navigation/list.php' );

								echo '
								<ul class="navMain user" id="userNavLoggedOut">
										<li class="subTree odd first last">
											<a href="/logga-in" class="popupLink ajax"><i class="fas fa-user"></i>' . _( 'Konto' ) . '</a>
											<ul>
												<li class="subFirst odd">
													<a href="/logga-in?returnTo=' . $oRouter->sPath . '" class="popupLink ajax"></i>' . _( 'Logga in' ) . '</a>
												</li>
												<li class="subLast even">
													<a href="/logga-in">' . _( 'Registrera' ) . '</a>
												</li>
											</ul>
										</li>
									</ul>';
						?>
					</div>
				</nav>
			</div>
		</header>
		<section id="userbar">
			<div class="innerContainer">
				<?php
					echo '<div id="homeButton"><a href="/" class="button narrow small"><i class="fa fa-home"></i></a></div>';
					echo $oLayout->renderView( 'translation/languageSelector.php' );
					echo '<div class="empty"></div>';
					echo $oLayout->renderView( 'help/button.php' );
					echo $oLayout->renderView( 'user/userInfo.php' );
				?>
		    <div class="view global searchButton">
					<a href="<?php echo $oRouter->getPath( 'guestSearchForm' ); ?>" class="popupLink button narrow small white"><i class="fas fa-search"></i><span class="extended"><?php echo _( 'Sök' ); ?></span></a>
				</div>
			</div>
		</section>
		<section id="notificationRow" class="notificationBar">
		</section>
		<section id="newsRow" class="newsBar">
			<?php
				echo $oLayout->renderView( 'puff/listNotification.php' );
			?>
		</section>
		<section id="layoutBlocks">
			<?php
				echo $oLayout->renderView( 'puff/list.php' );
			?>
		</section>
		<section id="layoutContainer">
			<?php
				// Layout with content
				echo $sContent;
			?>
		</section>
		<footer>
			<div class="container">
				<?php
					if( $sFooter !== null) echo $sFooter;
				?>
			</div>
		</footer>
	</div>

	<div id="bottomNotification">
		<div id="javascriptNotice" class="warning">
			<div class="container"><?php echo $sJavascriptNotice; ?></div>
		</div>
		<div id="connectionNotice" class="warning">
			<div class="container"><?php echo $sConnectionNotice; ?></div>
		</div>
		<?php
			if( $bCookie === true ) echo $oLayout->renderView( 'cookie/showNotification.php' );
		?>
	</div>

	<?php echo $sBottom; ?>

	<!-- Update services -->
	<script>
		// if( (Modernizr.websockets) && (<?php echo ( SERVICE_WEBSOCKET_ENABLED ? 'true' : 'false'); ?>) ) {
		// 	/* WebSocket is supported and enabled. You can proceed with your code */
		// 	$.getScript( "/js/services/wsPushService.js", function() {
		// 		// Loaded
		// 	} );
		// } else {
		// 	/* WebSocket is not supported or disabled. You can proceed with your code */
		// 	$.getScript( "/js/services/ajaxPullService.js", function() {
		// 		// Loaded
		// 	} );
		// }
	</script>

	<script src="/js/templates/tovek/scripts.js?ver=2"></script>
	<script type="text/javascript" src="/js/magiczoom.js"></script>
	<script type="text/javascript" src="/js/magicscroll.js"></script>
</body>
</html>
