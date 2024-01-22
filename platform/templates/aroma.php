<?php

/**
 *
 * Admin type
 *
 */
if( !isset($_SESSION['adminType']) || $_SESSION['adminType'] != 'new' ) {
	$oRouter->redirect( 'https://backend.tovek.se/admin/login' );
}

$oUser = clRegistry::get( 'clUser' );

// Change lang to swedish for admin only
if( $_SESSION['langId'] != $GLOBALS['defaultAdminLangId'] ) {
	$_SESSION['langId'] = $GLOBALS['defaultAdminLangId'];
	$GLOBALS['langId'] = $GLOBALS['defaultAdminLangId'];
}
$oRouter->oDao->setLang( $GLOBALS['langId'] );

$sGroupKey = array_key_exists( 'super', $oUser->aGroups ) ? 'super' : ( array_key_exists('admin', $oUser->aGroups) ? 'admin' : null );
if( empty($_SESSION['user']['groupKey']) ) $_SESSION['user']['groupKey'] = $sGroupKey;
if( !empty($_GET['userGroupKey']) && array_key_exists( $_GET['userGroupKey'], $oUser->aGroups ) ) $_SESSION['user']['groupKey'] = $_GET['userGroupKey'];

if( $sGroupKey === null ) {
	$oUser->logout();
	unset( $oUser );
}

// Find editing language
$oLocale = clRegistry::get( 'clLocale' );
$aLocales = $oLocale->read();

$sEditLang = '';
foreach( $aLocales as $aLocale ) {
	if( $aLocale['localeId'] == $_SESSION['langIdEdit'] ) {
		$sEditLang = strtoupper( substr($aLocale['localeCode'], 0, 2) );
	}
}

if( $sGroupKey != 'super' ) {
	/**
	 * Admin message
	 */
	$oAdminMessage = clRegistry::get( 'clAdminMessage', PATH_MODULE . '/adminMessage/models' );
	$aMessages = $oAdminMessage->readByUser( $_SESSION['userId'] );
	if( !empty($aMessages) ) {
		$GLOBALS['viewParams']['adminMessage']['show.php']['messages'] = $aMessages;
		$sAdminMessage = $oLayout->renderView( 'adminMessage/show.php' );
	} else $sAdminMessage = '';
}

?><!DOCTYPE html>
<html class="no-js">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="utf-8" />
	<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0" />

	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,400italic,600,700,800">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lora:400,400italic,700,700italic">

	<?php echo $sTop; ?>

	<link rel="stylesheet" href="/css/jquery.colorbox.admin.css">
	<link rel="stylesheet" href="/css/jquery-ui-timepicker-addon.css" />
	<link rel="stylesheet" href="/css/index.php?include=templates/aroma/">

	<!-- Favicons -->
	<link rel="apple-touch-icon" href="/images/templates/aroma/favicons/apple-touch-icon.png">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-192x192.png" sizes="192x192">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-160x160.png" sizes="160x160">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-96x96.png" sizes="96x96">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-16x16.png" sizes="16x16">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-32x32.png" sizes="32x32">

	<script src="/js/modernizr.js"></script>
</head>
<body ontouchstart="">
	<?php
		if( !empty($sAdminMessage) ) {
			echo $sAdminMessage;
		}
	?>
	<div id="wrapper">
		<header>
			<div class="container">
				<a href="/admin" class="logo" title="<?php echo _( 'Go to administration overview' ) ?>">
					<img src="/images/templates/aroma/logo.png" alt="Aroma CMS Logo" />
				</a>

				<nav class="controls">
					<ul class="edit">
						<?php
							if( SITE_SEO_ADJUSTED === true ) {
								echo '
								<li class="seo"><span>' . _( 'SEO-adjusted' ) . '</span></li>';
							}
							echo '
								<li class="frontEnd"><a href="/" target="_blank" title="' . _( 'Open front end in a new window' ) . '"><span>' . _( 'Front end' ) . '</span></a></li>
								<li class="language" title="' . _( 'Editing language' ) . '">
									<a href="#" data-modal="#languageSelect"><span>' . _( 'Editing language' ) . ' (' . $sEditLang . ')</span></a>
									<div id="languageSelect">
										' . $oLayout->renderView( 'locale/formSelect.php' ) . '
									</div>
								</li>
							';
						?>
					</ul>
					<ul class="generic">
						<?php
							echo '
								<li class="settings"><a href="' . $oRouter->getPath( 'adminConfigPanel' ) . '" title="' . _( 'Site settings' ) . '"><span>' . _( 'Settings' ) . '</span></a></li>
								<li class="username"><span class="greeting">' . _( 'Hi' ) . ' </span><a href="' . $oRouter->getPath( 'adminAccountSettings' ) . '" title="' . _( 'User settings' ) . '"><span>' . $oUser->readData( 'username' ) . '</span></a>
								<li class="logOut"><a href="' . $oRouter->getPath( 'userLogout' ) . '" title="' . _( 'Log out' ) . '"><span>' . _( 'Log out' ) . '</span></a></li>
							';
						?>
					</ul>
				</nav>
				<nav class="help">
					<ul>
						<?php

							if( array_key_exists( 'super', $oUser->aGroups ) ) {
								echo '
									<li class="options">
										<a href="#" data-modal="#navigationSelect">' . _( 'Editing options' ) . '</a>
										<div id="navigationSelect">' . $oLayout->renderView( '/navigation/formSelect.php' ) . '</div>
									</li>
								';
							}
						?>
					</ul>
				</nav>
			</div>
		</header>

		<div class="container">
			<aside>
				<nav id="mainMenu">
					<?php echo $oLayout->renderView( 'navigation/adminList.php' ); ?>
				</nav>
			</aside>

			<div id="content">
				<?php echo $sContent; ?>
			</div>
		</div>

		<footer>
			<address class="contact">
				<p><a href="http://argonova.se/" target="_blank">Argonova Systems</a>, Albanoliden 5, 506 30 Borås</p>
				<p>Tel: 033 - 20 75 00. Fax: 033 - 20 75 01. E-post: <a href="mailto:info@argonova.se">info@argonova.se</a></p>
			</address>
			<div class="copyright">
				<p><a href="http://argonova.se/" target="_blank">&copy; Copyright – Argonova Systems, din reklambyrå / webbyrå</a></p>
				<p>Logotyp, hemsida, webbdesign &amp; internetmarknadsföring</p>
				<p>AromaCMS är en produkt av Argonova Systems</p>
			</div>
		</footer>
	</div>

	<!--<script src="/js/jquery-1.11.3.min.js"></script>
	<script src="/js/jquery-ui-1.11.4.min.js"></script>-->
	<script src="/js/jquery/jquery-3.2.1.min.js"></script>
	<script src="/js/jquery/jquery-ui-1.12.1.min.js"></script>

	<script src="/js/jquery.cookie.js"></script>
	<script src="/js/jquery.colorbox-min.js"></script>
	<script src="/js/jquery-ui-timepicker-addon.js"></script>
	<script src="/js/templates/aroma/admin.js"></script>
	<script src="/js/templates/aroma/UI.js"></script>

	<script src="<?php echo TINY_MCE_SRC; ?>" referrerpolicy="origin"></script>
	<script src="/js/templates/aroma/tinymce.js"></script>
	<?php echo $sBottom; ?>
	<?php echo $oLayout->renderView( 'admin/guide.php' ); ?>
</body>
</html>
