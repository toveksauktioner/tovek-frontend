<?php

$oUser = clRegistry::get( 'clUser' );

$sGroupKey = array_key_exists( 'super', $oUser->aGroups ) ? 'super' : ( array_key_exists('admin', $oUser->aGroups) ? 'admin' : null );
if( $sGroupKey === null ) {
	$oUser->logout();
	unset( $oUser );
}

$GLOBALS['viewParams']['navigation']['list.php']['groupKey'] = $sGroupKey;

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
	<link rel="stylesheet" href="/css?include=templates/aroma/">

	<!-- Favicons -->
	<link rel="apple-touch-icon" href="/images/templates/aroma/favicons/apple-touch-icon.png">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-192x192.png" sizes="192x192">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-160x160.png" sizes="160x160">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-96x96.png" sizes="96x96">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-16x16.png" sizes="16x16">
	<link rel="icon" type="image/png" href="/images/templates/aroma/favicons/favicon-32x32.png" sizes="32x32">

	<script src="/js/jquery-1.11.3.min.js"></script>
	<script src="/js/jquery-ui-1.11.4.min.js"></script>	
	<script src="/js/jquery.colorbox-min.js"></script>
	<script src="/js/jquery-ui-timepicker-addon.js"></script>
	<script src="/js/jquery.cookie.js"></script>
	<script src="/js/modernizr.js"></script>
</head>
<body ontouchstart="" style="min-width: 100%;">

<div id="page" style="min-width:400px;">
	<div id="content" style="padding: 1em;">
		<?php echo $sContent; ?>
	</div>
</div>
	
<?php echo $sBottom; ?>

</body>
</html>
