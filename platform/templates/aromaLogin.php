<!DOCTYPE html>
<html class="no-js">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="utf-8" />
	<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0" />

	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,400italic,600,700,800">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lora:400,400italic,700,700italic">

	<?php echo $sTop; ?>

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
	<div id="wrapper">
		<header>
			<div class="container">
				<div class="logo">
					<img src="/images/templates/aroma/logo.png" alt="Aroma CMS Logo" />
				</div>
			</div>
		</header>

		<div class="container">
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
	<script src="/js/jquery-1.11.3.min.js"></script>
	<script src="/js/jquery-ui-1.11.4.min.js"></script>
	<script src="/js/jquery.cookie.js"></script>
	<?php echo $sBottom; ?>
</body>
</html>