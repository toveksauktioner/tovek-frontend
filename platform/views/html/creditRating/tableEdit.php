<?php

/* * * *
 * Filename: tableEdit.php
 * Created: 15/09/2014 by Renfors
 * Reference:
 * Description: View file for listing credit rating checkups.
 * * * */

$oCreditRating = clRegistry::get( 'clCreditRating', PATH_MODULE . '/creditRating/models' );
	$aDataDict = $oCreditRating->oDao->aDataDict;

// Sorting
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oCreditRating->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('ratingCreated' => 'DESC') )
) );
$oSorting->setSortingDataDict( array(
	'ratingId' => array(),
	'ratingService' => array(),
	'ratingServiceFunction' => array(),
	'ratingSearchPin' => array(),
	'ratingStatus' => array(),
	'ratingResultData' => array(),
	'ratingCreated' => array()
) );

// Data
$aData = $oCreditRating->read( array(
	'ratingId',
	'ratingService',
	'ratingServiceFunction',
	'ratingSearchPin',
	'ratingStatus',
	'ratingResultData',
	'ratingCreated'
) );


if( !empty($aData) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $aDataDict );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'ratingControls' => array(
			'title' => ''
		)
	) );	
	
	foreach( $aData as $entry ) {
		$row = array(
			'ratingId' => $entry['ratingId'],
			'ratingService' => $entry['ratingService'],
			'ratingServiceFunction' => $entry['ratingServiceFunction'],
			'ratingSearchPin' => $entry['ratingSearchPin'],
			'ratingStatus' => '
				<span class="' . $entry['ratingStatus'] . '">
					' . $aDataDict['entCreditRating']['ratingStatus']['values'][$entry['ratingStatus']] . '
				</span>',
			'ratingResultData' => ( ($entry['ratingStatus'] == 'fail') ? $entry['ratingResultData'] : '' ),
			'ratingCreated' => $entry['ratingCreated'],
			'ratingControls' => '
				<a href="?ratingId=' . $entry['ratingId'] . '" class="ajax icon iconInfo iconText">' . _( 'Show' ) . '</a>'
		);
		
		$oOutputHtmlTable->addBodyEntry( $row );
	}

	$sOutput = $oOutputHtmlTable->render();	
} else {

	$sOutput = '<strong>' . _('There are no items to show') . '</strong>';	
}

echo '
	<div class="view ratingTableEdit">
		<h1>' . _( 'Rating' ) . '</h1>
		' . $sOutput . '
	</div>';
