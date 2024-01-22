<?php

// List and show logsfiles from platform

$sLogs = '';
$sLogLinks = '';
$aLogs = array();
$aFileMaketimes = array();

$aLogfiles = scandir( PATH_LOG );

foreach( $aLogfiles as $log ) {
	if( !is_file(PATH_LOG . '/' . $log) ) continue;
	$aFileMaketimes[$log] = filemtime(PATH_LOG . '/' . $log);
	$aLogs[$log] = file_get_contents(PATH_LOG . '/' . $log);	
}

asort($aFileMaketimes);
end($aFileMaketimes);
$sLatestLog = key($aFileMaketimes);
foreach( $aLogs as $sLogKey => $sLogContent) {
	$aLineData = array_reverse(explode("\n", $sLogContent));	
	$iLines = count($aLineData) - 1;
	
	$sLogContent = '';
	$iCount = 0;
	foreach( $aLineData as $sLogLine) {
		if( $iCount > 40 ) break;
		$sLogContent .= $sLogLine . PHP_EOL;
		++$iCount;
	}
	
	$sLogs .= '
	<div id="' . str_replace(array('.','-'), '', $sLogKey) . '">
		<pre>' . $sLogContent . '</pre>
	</div>';
	
	$sLogLinks .= '
	<li' . ($sLatestLog == $sLogKey ? ' class="latestLog"' : '' ) . '><a href="#' . str_replace(array('.','-'), '', $sLogKey) . '" title="' . date(DATE_RSS, $aFileMaketimes[$sLogKey]) . '"><span>' . $sLogKey . ' (' . $iLines . ')</span></a></li>';
}

if( !empty($sLogs) ) {
	$sLogs = '
	<div id="tabs">
		<ul>
			' . $sLogLinks . '
		</ul>
		' . $sLogs . '
	</div>';
} else {
	$sLogs = '
	<strong>' . _('There are no items to show') . '</strong>';
}

// Ajax refresh this page
if( empty($_GET['ajax']) ) {
	$oTemplate->addBottom( array(
		'key' => 'jQueryAjaxRefreshLogs',
		'content' => '
		<script>
			function doRefresh() {
				$.ajax( {
					url: "' . $oRouter->sPath . '?ajax=true",
					cache: false,
					timeout: 5000,
					beforeSend: function() {
						$("#tabs").addClass("ajaxLoad");
					},
					success: function(html) {
						$("#tabs").removeClass("ajaxLoad");
					
						var $tabs = $("#tabs").tabs();
						var selected = $tabs.tabs("option", "selected");					
						
						$(".showLogs").html(html);
						
						var $tabs = $("#tabs").tabs();
						$tabs.tabs("select", selected);
					}
				} );	
			}

			setInterval(doRefresh,5000);
		</script>'
	) );
}

echo '
<div class="showLogs view">
	<h1>' . _( 'Logs' ) . '</h1>
	' . $sLogs . '
</div>';
