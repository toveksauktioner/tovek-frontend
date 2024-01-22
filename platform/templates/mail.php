<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<?php echo $sTop; ?>
		<style>
			body{
				font:11px Arial,Verdana,sans-serif;
				background-color:#eeeeee;
				margin:40px;
				padding:0;
			}
			a img{
				border:0;
			}
			table#content{
				width:760px;
				background-color:#fff;
				border:3px solid #bbbbbb;
				border-collapse:collapse;
				padding:0;
			}
			table#content td{
				padding:0 50px;
			}
			table#content td td{
				padding:5px;
			}
			td#logotype{
				height:150px;
			}
			td#footer,
			td#header
			{
				text-align:center;
				height:40px;
			}
			td#footer span{
				color:#666;
			}
			hr{
				color:#ccc;
				background:#ccc none;
				height:1px;
				border:0;
			}
		</style>
	</head>
<body>
<table id="content">
	<tr>
		<td id="header">&nbsp;</td>
	</tr>
    <tr>
        <td><?php echo $sContent; ?></td>
    </tr>
    <tr>
        <td id="footer">&nbsp;</td>
    </tr>
</table>

<?php echo $sBottom; ?>
</body>
</html>
