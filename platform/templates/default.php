<?php

/**
 * Locales
 */
// $oLocales = clRegistry::get( 'clLocale' );
// $sLocales = $oLocales->generateLocaleList( $GLOBALS['Locales'] );

/**
 * Footer
 */
$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
$sFooter = (($aFooter = $oInfoContent->read('contentTextId', 30)) ? $aFooter[0]['contentTextId'] : null );

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
	<link href="/css?include=templates/default/" rel="stylesheet" />
	<link href="/css?include=templates/default/print" rel="stylesheet" media="print" />
	<link href="/css?include=jquery-ui-1.8.9.custom.css" rel="stylesheet" />

	<!-- Scripts -->
	<script src="/js/jquery-1.11.1.min.js"></script>
	<script src="/js/jquery-ui-1.11.4.min.js"></script>
	<script src="/js/modernizr.js"></script>
	<script src="/js/templates/default/scripts.js"></script>

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
<body ontouchstart=""<?php echo (!empty($oRouter->sCurrentLayoutBodyClass) ? ' class="' . $oRouter->sCurrentLayoutBodyClass . '"' : ''); ?>>
	<?php
		if( $bCookie === true ) echo $oLayout->renderView( 'cookie/showNotification.php' );
	?>
	<div id="wrapper">
		<header>
			<div id="logo">
				<a href="/">
					<img src="/images/templates/default/logo.png" alt="logo" class="block" />
				</a>
			</div>
			<nav data-title="<?php echo _( 'Menu' ); ?>">
				<?php
					$GLOBALS['viewParams']['navigation']['list.php']['groupKey'] = 'guest';
					echo $oLayout->renderView( 'navigation/list.php' );
				?>
			</nav>
		</header>
		<?php
			// Layout with content
			echo $sContent;
		?>
		<footer>
			<div class="view infoContent">
				<?php
					if( $sFooter !== null) echo $sFooter;
				?>
			</div>
			<div class="credits argonova">
				<a href="http://argonova.se" target="_blank">Webbdesign</a> - <a href="http://argonova.se/webbsystem/publiceringsverktyg/cms.php" target="_blank">Webbyrå</a> / <a href="http://argonova.se/referenser" target="_blank">Reklambyrå</a> <a href="http://argonova-ehandel.se" target="_blank">Argonova Systems</a>
			</div>
		</footer>
	</div>
	<?php echo $sBottom; ?>
</body>
</html>
