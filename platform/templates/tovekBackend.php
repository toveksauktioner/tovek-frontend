<?php

$oUser = clRegistry::get( 'clUser' );

$sGroupKey = array_key_exists( 'super', $oUser->aGroups ) ? 'super' : ( array_key_exists('admin', $oUser->aGroups) ? 'admin' : ( array_key_exists('partner', $oUser->aGroups) ? 'partner' : null ) );
if( empty($_SESSION['user']['groupKey']) ) $_SESSION['user']['groupKey'] = $sGroupKey;
if( !empty($_GET['userGroupKey']) && array_key_exists( $_GET['userGroupKey'], $oUser->aGroups ) ) $_SESSION['user']['groupKey'] = $_GET['userGroupKey'];

if( $sGroupKey === null ) {
	$oUser->logout();
	unset( $oUser );
}

$GLOBALS['viewParams']['navigation']['list.php']['groupKey'] = $_SESSION['user']['groupKey'];

?><!DOCTYPE html>
<html class="no-js">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,300;1,400;1,600;1,700;1,800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="//backend.tovek.se/css/?include=templates/tovekBackend/&ver=1">

  <!-- jQuery loaded via CDN -->
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>

	<!-- Touch-Punch (for jQuery UI support on touch devices) -->
	<script src="/js/jquery.ui.touch-punch.min.js"></script>

	<!-- Select 2 -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

	<!-- Tiny MCE -->
	<script src="<?php echo TINY_MCE_SRC; ?>" referrerpolicy="origin"></script>
	<script src="/js/templates/aroma/tinymce.js"></script>

	<!-- Other scripts -->
	<script src="/js/popup.js"></script>

	<?php echo $sTop; ?>
</head>
<body>
<div id="wrapper">
	<header>
		<a href="/admin" class="logo"><img src="//backend.tovek.se/images/templates/tovekBackend/backend-logo.png"></a>
		<input type="checkbox" style="display: none;" id="navToggle">
		<label for="navToggle"><i class="fas fa-bars"></i></label>
		<div class="breadcrumbs">
			<?php
				echo $oLayout->renderView( 'navigation/listCrumbs.php' );
			?>
		</div>
		<nav class="navMain">
			<?php
				echo $oLayout->renderView( 'navigation/adminList.php' );
			?>
		</nav>
	</header>
	<div id="argoBody">
		<div id="page">
			<div id="content">
				<?php echo $sContent; ?>
			</div>
		</div>
	</div>
</div>
<footer></footer>
<?php echo $oLayout->renderView( 'commandCenter/prompt.php' ); ?>
<?php echo $sBottom; ?>
</body>
</html>
