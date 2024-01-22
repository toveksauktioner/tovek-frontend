<?php

$sOutput = '';

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oAuctionSearchDao = $oAuctionEngine->getDao( 'AuctionSearch' );
$oAuctionSearchDao->aSorting = array( 'searchCreated' => 'DESC' );
$oAuctionSearchDao->setEntries( 10 );

if( !empty($_SESSION['userId']) ) {
	// Data
	$aSearchData = $oAuctionEngine->readByUser_in_AuctionSearch();

	if( !empty($aSearchData) ) {

		$sOutput = '';

		foreach( $aSearchData as $entry ) {
			if( empty($entry['searchString']) ) continue;

			$aClass = array();

			$sOutput .= '
				<li' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '') . '>
					<div>
						<a href="' . $oRouter->getPath( 'guestAuctionSearch' ) . '?searchQuery=' . $entry['searchString'] . '">
							' . $entry['searchString'] . '
						</a>
					</div>
				</li>';
		}

	}

	if( !empty($sOutput) ) {
		echo '
			<div class="view userListSearch">
				<h2>' . _( 'Tidigare sökningar' ) . '</h2>
				<ul class="searches">
					', $sOutput, '
				</ul>
			</div>';
	}
}

// Stylesheet
$oTemplate->addStyle( array(
	'key' => 'searchesViewStylesheet',
	'content' => '
		.view.userListSearch { margin: 50px 0 30px; }
			ul.searches { list-style: none; margin-top: 10px; padding-top: 10px; border-top: 1px solid #F4F3F3; }
				ul.searches li { padding: 2.5px 0; display: inline-block; margin: 0 0.313em 0.313em 0; border: 1px solid #e0e0e0;S border-left: none; border-radius: 0 13px 13px 0; -webkit-border-radius: 0 13px 13px 0; -moz-border-radius: 0 13px 13px 0; background: #e0e0e0; }
					ul.searches li a { display: block; font-size: 13px; line-height: 35px; color: #000; padding: 0 15px 0 44px; background: url("/images/templates/tovek2014/icon-white-check-circle.png") no-repeat 7px 5px; }
				ul.searches li:hover { background: #c8e1f9; border: 1px solid #c8e1f9; }
			ul.searches:after { content: " "; display: block; height: 0; line-height: 0; clear: both; visibility: hidden; }
	'
) );
