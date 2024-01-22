<?php

//$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
//$sFooter = (($aFooter = $oInfoContent->read('contentTextId', 30)) ? $aFooter[0]['contentTextId'] : null );

?><!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]> <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]> <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="utf-8" />
	<link rel="shortcut icon" href="<?php echo (SITE_DEFAULT_PROTOCOL == 'http' ? 'http' : 'https'); ?>://<?php echo SITE_DOMAIN; ?>/images/templates/tovek2014/favicon.ico" />

	<!-- Stylesheet [Tovek 2014] -->
	<link href="/css/templates/printPreview/?ver=2" rel="stylesheet" media="screen" />
	<link href="/css/templates/printPreview/print/" rel="stylesheet" media="print" />

	<!-- Additional stylesheet -->
	<!--<link href="/css/font/open-sans.css" rel="stylesheet" media="all" />-->
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400,400italic,600,600italic,700,700italic,800,800italic,300italic,300' rel='stylesheet' type='text/css'>
	<link href="/css/jquery-ui-1.8.9.custom.css" rel="stylesheet" />
	<!--[if lte IE 7]>
		<link rel="stylesheet" type="text/css" href="/css/ie.css" />
	<![endif]-->

	<!-- jQuery -->
	<script src="/js/jquery/jquery-3.2.1.min.js"></script>
	<script src="/js/jquery/jquery-ui-1.12.1.min.js"></script>

	<script>
		$( function() {
			var flip = 0;
			$("a.toggleShow").each(function() {
				sTarget = this.href.substring(this.href.indexOf("#"));
				if( !$(sTarget).hasClass("show") ) $(sTarget).hide();
			}).on("click", function() {
				sTarget = this.href.substring(this.href.indexOf("#"));
				if( $(sTarget)[0].tagName.toLowerCase() == "tr" ) {
					if( flip++ % 2 == 0 ) {
						$(sTarget).show();
					} else {
						$(sTarget).hide();
						flip = 0;
					}
				} else {
					$(sTarget).slideToggle("fast");
				}
				return false;
			});
		} );

				$(document).on( 'click', 'a#print', function(event) {
			event.preventDefault();

				$("title").html( "" );
				$("#topbar").html( "<p>Vänligen välj önskad skrivare för din utskrift i rutan som kommer upp...</p>" );
				//$("#header").css( "opacity", "0.2" );
				//$("#content").css( "opacity", "0.2" );
				//$("#footer").css( "opacity", "0.2" );

				window.print();

				$("#topbar").html( " \
					<ul> \
						<li> \
							<img src=\"/images/templates/printPreview/icon-print.png\" alt=\"\" /> \
							<a href=\"#\" id=\"print\">Skriv ut den här sidan</a> \
						</li> \
					</ul>" );

				//$("#header").css( "opacity", "1" );
				//$("#content").css( "opacity", "1" );
				//$("#footer").css( "opacity", "1" );
		} );
	</script>

	<?php echo $sTop; ?>
</head>
<body>
	<div id="wrapper">
		<div id="topbar">
			<ul>
				<li>
					<img src="/images/templates/printPreview/icon-print.png" alt="" />
					<a href="#" id="print">Skriv ut den här sidan</a>
				</li>
			</ul>
		</div>
		<div id="header">
			<div id="logo">
				<img src="/images/templates/printPreview/logo-dark.png" alt="" />
			</div>
			<div id="addressInfo">
				<p>
					<span>Adress:</span> Box 158, 311 51 Ätran
				</p>
				<p>
					<span>Tel:</span> 0346-487 70
				</p>
				<p>
					<span>E-post:</span> <a href="mailto:info@tovek.se">info@tovek.se</a>
				</p>
			</div>
		</div>
		<div id="content">
			<?php
				echo $sContent;
			?>
		</div>
		<div id="footer">
			<div id="name">
				Toveks auktioner AB
			</div>
			<div id="date">
				<?php
					echo date( 'Y-m-d' );
				?>
			</div>
		</div>
	</div>
</body>
</html>
