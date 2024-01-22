<html>
<head>
	<meta charset="utf-8" />
	<title><?php echo _( 'Invoice' ) ?></title>
	<style>
		/* Generic & tools */
		body { font-size: 12px; font-family: arial, verdana, sans-serif; width: 100%; margin: 0; padding: 0; }				
		table { vertical-align: top; }		
		.above { font-size: 8px; display: block; margin-left: -10px; float: left; }		
		.rounded { display: block; border:0.1mm solid #000000; border-radius: 2mm; }
		.clear { clear: both; line-height:0; height:0; font-size:0; margin:0; padding:0; }
		.smallerText { font-size: 10px; }
		
		/* Layout */
		#logo { float: left; width: 49%; clear: both; padding-bottom: 15px; }
		
		.infoTop { float: left; width: 49%; margin-right: 10px; }
			.title {  }
				.pageAnnotation { width: 100%; font-size: 18px; }
				.pageAnnotation td { padding: 10px 10px 10px 15px; }
				.pageAnnotation .pageNo { padding: 0px 10px 10px 15px; text-align: right; vertical-align: top; font-size: 14px; }
			.invoiceno { float: left; padding: 0 0 0 15px; margin: 5px 0 0 0; width: 65%; }
				.invoiceno .above { display: inline-block; width: 40%; }
				.invoiceno .below { float: left; display: inline-block; width: 37%; }
				
			.expire { float: right; padding-left: 15px; margin-top: 0; width: 22%;  }
				.expire .above { display: inline-block; padding-bottom: 5.5px; }
				.expire .below { float: left; display: inline-block; }
				
			.deliveryAdress { float: left; width: 44%; padding: 0 0 0 15px; margin-top: 5px; height: 80px; }
			.invoiceAdress { float: right; margin-right: 14px; width: 46.6%; padding: 0 0 0 15px; margin-top: 0px; height: 80px; }
		
		table.middleInfo { width: 90%; clear: both; float: left; height: 100px; line-height: 18px; padding-top: 20px; }
			table.middleInfo td.titleTextFirst { width: 10%; }
			table.middleInfo td.titleTextSecond { width: 10%; }
			
			table.middleInfo td.valueTextFirst { width: 31.5%; }
			table.middleInfo td.valueTextSecond { width: 20%; }
		
		.productListHeader { width: 100%; margin-top: 20px; padding: 0 10px; }
		.productListHeader table { width: 100%; }
		
		.productList { position: relative; height: 420px; margin-top: 5px; padding: 0 10px; }
		.productList table { width: 100%; display: block; height: 325px; }
		strong.forts {  }
		
		td.col1 { width: 126px; }
		td.col2 { width: 235px; }
		td.col3 { width: 49px; text-align: right; }
		td.col3-1 { width: 95px; }
		td.col4 { width: 80px; text-align: right; }
		td.col5 { width: 100px; text-align: right; }
		td { }
		div.footer { margin-top: 5px; height: 40px; padding: 0 20px; }
		
		table.bottomText { width: 96%; margin: 1% 2% 0 2%; }
			table.bottomText td { width: 25%; }
	</style>
</head>
<body>
	<?php
		echo $sContent;
	?>	
</body>
</html>