<?php

$oGoogleAnalytics = clRegistry::get( 'clGoogleAnalytics', PATH_MODULE . '/googleAnalytics/models/' );

/* --- One way --- */
//$oGoogleAnalytics->requestReportData( array(
//	'startDate' => '2011-01-25',
//	'endDate' => '2011-03-30'
//) );
//$aData1 = $oGoogleAnalytics->getData();

/* --- Another way --- */
$sStorageLable = 'March';
$aData2 = $oGoogleAnalytics->getData( array(
	'iMonth' => 3										   
), $sStorageLable );

echo '
	<h1>Google Analytics</h1>
	<table width="350">
		<tbody>
			<tr>
				<th>' . _( 'Pageviews' ) . '</th>
				<td>' . $aData2['pageviews'] . '</td>
			</tr>
			<tr>
				<th>' . _( 'Visits' ) . '</th>
				<td>' . $aData2['visits'] . '</td>
			</tr>
			<tr>
				<th>' . _( 'Updated' ) . '</th>
				<td>' . $aData2['updated'] . '</td>
			</tr>
		</tbody>
	</table>';
