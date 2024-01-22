<?php

$oDashboardLink = clRegistry::get( 'clDashboardLink', PATH_MODULE . '/dashboard/models' );

// Sync
$oDashboardLink->sync();

// Sort
$oDashboardLink->oDao->aSorting = array(
	'linkSort' => 'ASC',
	'linkId' => 'ASC'
);

// All links
$aLinks = $oDashboardLink->read();

if( !empty($aLinks) ) {
	$sLinkList = '
		<ul>';
		
	foreach( $aLinks as $aLink ) {
		$sLinkTitle = $_SESSION['langId'] == '1' ? $aLink['linkTextSwedish'] : $aLink['linkTextEnglish'];
		
		$sLinkList .= '
			<li>
				<a href="' . $aLink['linkUrl'] . '" target="_blank">' . $sLinkTitle . '</a>
			</li>';
	}
	
	$sLinkList .= '
		</ul>';
		
	echo '
		<div class="view dashboard listLinks">
			<h3>' . _( 'Good to have links' ) . '</h3>
			<section>
				' . $sLinkList . '
			</section>
		</div>';
}