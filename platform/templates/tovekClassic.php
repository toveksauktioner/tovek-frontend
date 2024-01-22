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

/**
 * Google analytics UA code
 */
$oConfig = clRegistry::get( 'clConfig' );
$sUAcode = (($aData = $oConfig->read('configValue', 'googleAnalyticsCode')) ? current(current( $aData )) : '');

/**
 * Cookie information
 */
$bCookie = (($aData = $oConfig->read('configValue', 'SITE_COOKIE')) ? current(current( $aData )) : '') == 'false' ? false : true;

?><!DOCTYPE html>
<html class="no-js" lang="<?php echo substr($GLOBALS['Locales'][ $_SESSION['langId'] ], 0, 2) ?>">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="utf-8" />
	<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0" />

	<!-- Stylesheet -->
	<link href="/css/index.php?include=templates/tovekClassic/&ver=2" rel="stylesheet" />
	<link href="/css/index.php?include=templates/tovekClassic/print" rel="stylesheet" media="print" />
	<link href="/css/index.php?include=jquery.ui/1.12.1/jquery-ui.min.css" rel="stylesheet" />

	<!-- Common stylesheet for different versions of the site  -->
	<link href="/css/index.php?include=templates/tovekCommon/&ver=5" rel="stylesheet" />

	<!-- JavaScripts -->
	<script src="/js/jquery/jquery-3.2.1.min.js"></script>
	<script src="/js/jquery/jquery-ui-1.12.1.min.js"></script>
	<script src="/js/jquery-ui-timepicker-addon.js"></script>

	<!-- Colorbox -->
	<link href="/css/jquery.colorbox.css" rel="stylesheet" />
	<script src="/js/jquery.colorbox2-min.js"></script>

	<!-- Additional javascript -->
	<script src="/js/modernizr.js"></script>
	<script src="/js/jquery.timer.js"></script>
	<script src="/js/spin.min.js"></script>
	<script src="/js/templates/tovekClassic/dynamic.config.js.php?ver=8"></script>
	<script src="/js/templates/tovekClassic/functions.js?ver=8"></script>

	<script src="/js/templates/tovekClassic/clock.js?ver=8"></script>
	<script src="/js/templates/tovekClassic/dynamic.js.php?ver=8"></script>

	<!-- Auction related javascript -->
	<script src="/js/templates/tovekClassic/auction.js?ver=9"></script>

	<!-- Template related javascript -->
	<script src="/js/templates/tovekClassic/tovek2014.js.php?ver=8"></script>

	<!-- Shared js -->
	<script src="/js/templates/tovekCommon/localization.js"></script>
	<script src="/js/templates/tovekCommon/connectionChecker.js"></script>
	<script src="/js/templates/tovekCommon/popup.js"></script>

	<?php if( !empty($sUAcode) && SITE_RELEASE_MODE === true ) { ?><script>
		// Google analytics
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', '<?php echo $sUAcode; ?>', 'auto');
		ga('send', 'pageview');
	</script><?php } ?>

	<?php echo $sTop; ?>
</head>
<body ontouchstart="" class="bottomNoticeActive <?php echo (!empty($oRouter->sCurrentLayoutBodyClass) ? $oRouter->sCurrentLayoutBodyClass : ''); ?>">
	<section id="topbar">
		<?php
			echo $oLayout->renderView( 'classic/composed_topbar.php' );
		?>
		<a href="/?siteVersion=new" style="font-size: .875em; position: absolute; top: 1.75em; border-radius: .25em; right: 1em; padding: 0 1em; line-height: 2em; background: rgba(255,255,255,.5);">Testa nya</a>
	</section>
	<div id="pusher">
		<header>
			<div class="wrapper">
				<section id="headbar">
					<div id="logoarea">
						<a href="<?php echo $oRouter->getPath( 'classicGuestStart' ); ?>">
							<img src="/images/templates/tovekClassic/img-logo<?php echo ( !empty($GLOBALS['logoSeasonal']) ? '-' . $GLOBALS['logoSeasonal'] : '' ); ?>.png" alt="logo" />
						</a>
					</div>
					<div id="spotlight">
						<?php
							echo $oLayout->renderView( 'classic/composed_listHeader.php' );
						?>
					</div>
					<div id="mobileMenu">
						<label for="navToggle">
							<span><?php echo _( 'Menu' ); ?></span>
							<img src="/images/templates/tovekClassic/img-mobile-menu.png" alt="" />
						</label>
						<input type="checkbox" id="navToggle" name="navToggle" style="display: none;" />
						<?php
							if( !empty($_SESSION['userId']) ) {
								echo $oLayout->renderView( 'classic/composed_userPanel.php' );
							} else {
								echo $oLayout->renderView( 'classic/composed_mobileList.php' );
							}
						?>
					</div>
				</section>
			</div>
		</header>
		<main role="main">
			<section id="userbar">
				<div class="innerContainer">
					<?php
						echo $oLayout->renderView( 'navigation/listCrumbs.php' );
						echo $oLayout->renderView( 'user/userInfo.php' );
					?>
				</div>
			</section>
			<section id="newsRow" class="newsBar">
				<?php
					echo $oLayout->renderView( 'puff/listNotification.php' );
				?>
			</section>
			<div id="importantMessage">
				<div class="container">
					<?php
						//echo $sGlobalErrorMsg;
					?>
				</div>
			</div>
			<section id="sectionWrapper">
				<div class="desktop">
					<?php
						echo $oLayout->renderView( 'classic/composed_mainHeadTabs.php' );
					?>
				</div>
				<div class="mobile">
					<?php
						$oParser = clRegistry::get( 'clUserAgentParser' );
						$aUserAgent = $oParser->parse( $_SERVER['HTTP_USER_AGENT'] );
						if( !empty($aUserAgent['os']) && !isset($_GET['all']) ) {
							switch( $aUserAgent['os'] ) {
								case 'Android':
								case 'iPad':
								case 'iPhone':
									echo $oLayout->renderView( 'classic/composed_mobileHeadTabs.php' );
									break;
							}
						}
					?>
				</div>
			</section>
			<div class="wrapper">
				<section id="layoutBlocks">
					<?php
						echo $oLayout->renderView( 'puff/list.php' );
					?>
				</section>
				<?php
					echo $sContent;
				?>
			</div>
		</main>
	</div>
	<footer>
		<div id="bottombar">
			<div class="wrapper">
				<?php
					echo $oLayout->renderView( 'classic/composed_bottomBar.php' );
				?>
			</div>
		</div>
		<div id="bottominfo">
			<div class="wrapper">
				<?php
					if( $sFooter !== null) echo $sFooter;
				?>
				<span class="clear"></span>
			</div>
		</div>
		<div id="argonova">
			<a href="https://argonova.se" target="_blank" class="argonova">Webbdesign - Webbyrå / Reklambyrå Argonova Systems</a>
		</div>
	</footer>

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
		if( (Modernizr.websockets) && (<?php echo ( SERVICE_WEBSOCKET_ENABLED ? 'true' : 'false'); ?>) ) {
			/* WebSocket is supported and enabled. You can proceed with your code */
			$.getScript( "/js/services/wsPushService.js?ver=2", function() {
				// Loaded
			} );
		} else {
			/* WebSocket is not supported or disabled. You can proceed with your code */
			$.getScript( "/js/services/ajaxPullService.js?ver=2", function() {
				// Loaded
			} );
		}
	</script>

	<!-- Google Analytics -->
	<script src="https://www.google-analytics.com/ga.js" type="text/javascript"></script>
	<script type="text/javascript">
		try {
		var pageTracker = _gat._getTracker("UA-12585921-1");
		pageTracker._trackPageview();
		} catch(err) {}
	</script>

	<!-- ImBox -->
	<script type="text/javascript">
		var _sid = '1634';
	  (function() {
	    var se = document.createElement('script'); se.type = 'text/javascript'; se.async = true;
	    se.src = 'https://files.imbox.io/app/dist/initWidget.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(se, s);
	  })();

		// Extra script for opening FAQ
		$( document ).on( "click", ".openImboxFaq", function(ev) {
				ev.preventDefault();
			_imbox.push( ['openFAQ'] );
		} );
		$( document ).on( "click", ".openImboxForm", function(ev) {
				ev.preventDefault();
			_imbox.push( ['openForm'] );
		} );
	</script>
	<!-- //ImBox Script -->
</body>
</html>
