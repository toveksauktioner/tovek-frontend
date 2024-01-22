<?php

/* * * *
 * Filename: view.php
 * Created: 23/09/2014 by Renfors
 * Reference:
 * Description: View file for checking a previous credit Rating
 * * * */

$oCreditRating = clRegistry::get( 'clCreditRatingCreditsafe', PATH_MODULE . '/creditRating/models' );
$oUserManager = clRegistry::get( 'clUserManager' );

if( isset($_GET['userId']) && ctype_digit($_GET['userId']) ) {
	$iRatingId = current( current($oUserManager->read('infoCreditRatingId', $_GET['userId'])) );
	
	if( ctype_digit($iRatingId) ) $_GET['ratingId'] = $iRatingId;
}

if( isset($_GET['ratingId']) && ctype_digit($_GET['ratingId']) ) {
	$aCreditRating = current( $oCreditRating->read(null, $_GET['ratingId']) );	
	
	echo '
		<div class="view ratingResult">
			<h1>' . _( 'Credit rating result' ) . '</h1>
			<table>
				<thead>
					<tr>
						<th>' . _( 'Service' ) . '</th>
						<th>' . _( 'Function' ) . '</th>
						<th>' . _( 'Search date' ) . '</th>
						<th>' . _( 'Search pin' ) . '</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>' . $aCreditRating['ratingService'] . '</td>
						<td>' . $aCreditRating['ratingServiceFunction'] . '</td>
						<td>' . $aCreditRating['ratingCreated'] . '</td>
						<td>' . $aCreditRating['ratingSearchPin'] . '</td>
					</tr>
				</tbody>
			</table>
			<div class="resultXML">
				' . $aCreditRating['ratingResultData'] . '
			</div>
		</div>';
}